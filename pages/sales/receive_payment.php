<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'create_sale')) {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['sales_token'])) {
    $_SESSION['sales_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$successMessage = '';
$zeroAmount = 0.0;

function resolveAccountDetails($conn, $accountRef) {
    if ($accountRef === null || $accountRef === '') {
        return null;
    }

    $numericRef = is_numeric($accountRef) ? (int)$accountRef : 0;
    $accountRefEsc = (string)$accountRef;

    $stmt = mysqli_prepare($conn, "
        SELECT id, account_code, account_type_id, account_name
        FROM accounts
        WHERE id = ? OR account_code = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'is', $numericRef, $accountRefEsc);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$account) {
        return null;
    }

    return [
        'id' => (int)$account['id'],
        'account_code' => (int)$account['account_code'],
        'parent_account_id' => (int)$account['account_type_id'],
        'account_name' => $account['account_name']
    ];
}

$saleId = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saleId = isset($_POST['sale_id']) ? (int)$_POST['sale_id'] : 0;
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';

    if (empty($postToken) || $postToken !== $_SESSION['sales_token']) {
        $errors[] = 'Transaction token mismatch or session expired. Please refresh the page and try again.';
    } else {
        $amountCurrency = isset($_POST['amount_currency']) ? (float)$_POST['amount_currency'] : $zeroAmount;
        $paymentDate = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : date('Y-m-d');
        $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'CASH';
        $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
        $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $exchangeRate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 1.0;

        if ($saleId <= 0) {
            $errors[] = 'A valid sale is required.';
        }
        if ($amountCurrency <= 0) {
            $errors[] = 'Please enter a payment amount greater than zero.';
        }
        if (empty($paymentDate)) {
            $errors[] = 'Payment date is required.';
        }
        if ($accountId <= 0) {
            $errors[] = 'Please select a cash or bank account.';
        }
        if ($exchangeRate <= 0) {
            $errors[] = 'Exchange rate must be greater than zero.';
        }

        if (empty($errors)) {
            mysqli_begin_transaction($conn);

            try {
                $saleQuery = mysqli_query($conn, "SELECT s.*, c.name AS customer_name, c.customer_code, c.receivable_account_id, c.id AS customer_id, s.currency AS sale_currency
                    FROM sells s
                    JOIN customer c ON s.customer_id = c.id
                    WHERE s.id = $saleId LIMIT 1");
                if (!$saleQuery || mysqli_num_rows($saleQuery) === 0) {
                    throw new Exception('Selected sale was not found.');
                }
                $sale = mysqli_fetch_assoc($saleQuery);

                $customerId = (int)$sale['customer_id'];
                $saleCurrency = trim($sale['sale_currency'] ?? 'USD');
                $saleCurrencyQuery = mysqli_query($conn, "SELECT id FROM currencies WHERE code = '" . mysqli_real_escape_string($conn, $saleCurrency) . "' LIMIT 1");
                $currencyId = 2;
                if ($saleCurrencyQuery && mysqli_num_rows($saleCurrencyQuery) > 0) {
                    $currencyRow = mysqli_fetch_assoc($saleCurrencyQuery);
                    $currencyId = (int)$currencyRow['id'];
                }

                $paidQuery = mysqli_query($conn, "SELECT COALESCE(SUM(amount_allocated), 0) AS paid_total
                    FROM customer_payment_allocations
                    WHERE sale_id = $saleId");
                $paidTotal = $zeroAmount;
                if ($paidQuery && mysqli_num_rows($paidQuery) > 0) {
                    $paidRow = mysqli_fetch_assoc($paidQuery);
                    $paidTotal = (float)$paidRow['paid_total'];
                }

                $outstanding = (float)$sale['total_value_usd'] - $paidTotal;
                if ($amountCurrency > $outstanding + 0.01) {
                    throw new Exception('Payment amount exceeds the outstanding balance.');
                }

                $cashAccount = mysqli_query($conn, "SELECT id, account_code, account_name, account_type_id FROM accounts WHERE id = $accountId AND is_active = 1 LIMIT 1");
                if (!$cashAccount || mysqli_num_rows($cashAccount) === 0) {
                    throw new Exception('Selected cash or bank account was not found.');
                }
                $cashAccountRow = mysqli_fetch_assoc($cashAccount);
                $cashAccountCode = (int)$cashAccountRow['account_code'];
                $cashParentAccountId = (int)$cashAccountRow['account_type_id'];

                $receivableAccountRef = $sale['receivable_account_id'];
                if (empty($receivableAccountRef)) {
                    throw new Exception('The customer does not have a receivable account configured.');
                }
                $receivableAccount = resolveAccountDetails($conn, $receivableAccountRef);
                if (!$receivableAccount || empty($receivableAccount['account_code'])) {
                    throw new Exception('Customer receivable account was not found.');
                }
                $receivableAccountCode = (int)$receivableAccount['account_code'];
                $receivableParentAccountId = (int)$receivableAccount['parent_account_id'];

                $amountBase = $amountCurrency * $exchangeRate;
                $paymentNo = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $paymentNoEsc = mysqli_real_escape_string($conn, $paymentNo);
                $descriptionEsc = mysqli_real_escape_string($conn, empty($description) ? "Payment received for sale: {$sale['sale_no']}" : $description);
                $refNoEsc = mysqli_real_escape_string($conn, $referenceNo);
                $paymentDateEsc = mysqli_real_escape_string($conn, $paymentDate);
                $paymentMethodEsc = mysqli_real_escape_string($conn, $paymentMethod);

                $insertPayment = "INSERT INTO customer_payments (
                    payment_number, customer_id, account_id, currency_id, exchange_rate, amount_currency, amount_base, payment_date, payment_method, reference_no, description, status, created_by
                ) VALUES (
                    '$paymentNoEsc', $customerId, $accountId, $currencyId, $exchangeRate, $amountCurrency, $amountBase, '$paymentDateEsc', '$paymentMethodEsc', '$refNoEsc', '$descriptionEsc', 'POSTED', $userId
                )";
                if (!mysqli_query($conn, $insertPayment)) {
                    throw new Exception('Failed to insert customer payment: ' . mysqli_error($conn));
                }
                $paymentId = mysqli_insert_id($conn);

                $insertAllocation = "INSERT INTO customer_payment_allocations (customer_payment_id, sale_id, amount_allocated)
                    VALUES ($paymentId, $saleId, $amountCurrency)";
                if (!mysqli_query($conn, $insertAllocation)) {
                    throw new Exception('Failed to allocate payment to the sale: ' . mysqli_error($conn));
                }

                $dateStr = date('Ymd', strtotime($paymentDate));
                $prefix = 'JE-' . $dateStr . '-';
                $journalCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM journal_entries WHERE journal_no LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'");
                $journalCount = 0;
                if ($journalCountQuery) {
                    $journalCountRow = mysqli_fetch_assoc($journalCountQuery);
                    $journalCount = (int)($journalCountRow['cnt'] ?? 0);
                }
                $journalNo = $prefix . str_pad($journalCount + 1, 4, '0', STR_PAD_LEFT);
                $journalDescription = 'Customer payment received: ' . $paymentNo;

                $insertJournalHeader = mysqli_prepare($conn, "
                    INSERT INTO journal_entries
                    (journal_no, entry_date, description, statuss, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                if (!$insertJournalHeader) {
                    throw new Exception('Prepare failed: ' . mysqli_error($conn));
                }
                $journalStatus = 'AUTO POSTED';
                mysqli_stmt_bind_param($insertJournalHeader, 'ssssi', $journalNo, $paymentDate, $journalDescription, $journalStatus, $userId);
                if (!mysqli_stmt_execute($insertJournalHeader)) {
                    throw new Exception(mysqli_error($conn));
                }
                $journalEntryId = mysqli_insert_id($conn);
                mysqli_stmt_close($insertJournalHeader);

                $insertJournalLine = mysqli_prepare($conn, "
                    INSERT INTO journal_entry_lines
                    (
                        journal_entry_id,
                        parent_account_id,
                        account_id,
                        debit,
                        credit,
                        currency_id,
                        exchange_rate,
                        amount_currency,
                        amount_base,
                        description
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$insertJournalLine) {
                    throw new Exception('Prepare failed: ' . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($insertJournalLine, 'iiiddiddds', $journalEntryId, $cashParentAccountId, $cashAccountCode, $amountCurrency, $zeroAmount, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($insertJournalLine, 'iiiddiddds', $journalEntryId, $receivableParentAccountId, $receivableAccountCode, $zeroAmount, $amountCurrency, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }
                mysqli_stmt_close($insertJournalLine);

                $newOutstanding = $outstanding - $amountCurrency;
                if ($newOutstanding <= 0.01) {
                    mysqli_query($conn, "UPDATE sells SET status = 'paid' WHERE id = $saleId");
                }

                logAudit($conn, 'CREATE', 'customer_payments', $paymentNo, "Recorded customer payment: $paymentNo", null, [
                    'sale_id' => $saleId,
                    'customer_id' => $customerId,
                    'amount_currency' => $amountCurrency,
                    'journal_no' => $journalNo
                ]);

                mysqli_commit($conn);
                $_SESSION['sales_token'] = bin2hex(random_bytes(32));
                header('Location: index.php?payment=success');
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = $e->getMessage();
            }
        }
    }
}

if ($saleId <= 0) {
    $errors[] = 'A valid sale was not provided.';
}

if (empty($errors)) {
    $saleQuery = mysqli_query($conn, "SELECT s.*, c.name AS customer_name, c.customer_code, c.receivable_account_id, c.id AS customer_id
        FROM sells s
        JOIN customer c ON s.customer_id = c.id
        WHERE s.id = $saleId LIMIT 1");
    if (!$saleQuery || mysqli_num_rows($saleQuery) === 0) {
        $errors[] = 'Selected sale was not found.';
    } else {
        $sale = mysqli_fetch_assoc($saleQuery);

        $paidQuery = mysqli_query($conn, "SELECT COALESCE(SUM(amount_allocated), 0) AS paid_total FROM customer_payment_allocations WHERE sale_id = $saleId");
        $paidTotal = $zeroAmount;
        if ($paidQuery && mysqli_num_rows($paidQuery) > 0) {
            $paidRow = mysqli_fetch_assoc($paidQuery);
            $paidTotal = (float)$paidRow['paid_total'];
        }

        $sale['outstanding_amount'] = (float)$sale['total_value_usd'] - $paidTotal;
    }
}

$accounts = [];
$accQuery = mysqli_query($conn, "SELECT id, account_code, account_name FROM accounts WHERE account_type_id = 1 AND is_active = 1 ORDER BY account_name ASC");
if ($accQuery) {
    while ($row = mysqli_fetch_assoc($accQuery)) {
        $accounts[] = $row;
    }
}

$paymentMethods = ['CASH', 'BANK_TRANSFER', 'CHEQUE', 'MOBILE_MONEY'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Receive Customer Payment</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/sales.css">
</head>
<body>
<?php include '../include/sidebar.php'; ?>
<div class="main">
  <?php $page_title = 'Receive Payment'; include '../include/navbar.php'; ?>
  <div class="content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Receive Customer Payment</h1>
        <div class="page-sub">Post a receipt against an existing sale and create the linked journal entries.</div>
      </div>
      <div class="page-actions">
        <a href="index.php" class="btn-sm" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">Back to Sales</a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger" style="margin-bottom: 16px;">
        <ul style="margin: 0; padding-left: 18px;">
          <?php foreach ($errors as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
      <div class="alert alert-success" style="margin-bottom: 16px;">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($sale)): ?>
      <div class="card" style="max-width: 760px; margin: 0 auto; padding: 24px;">
        <div style="display:grid; gap:12px; margin-bottom: 24px;">
          <div><strong>Sale:</strong> <?php echo htmlspecialchars($sale['sale_no']); ?></div>
          <div><strong>Customer:</strong> <?php echo htmlspecialchars($sale['customer_name'] . ' (' . $sale['customer_code'] . ')'); ?></div>
          <div><strong>Invoice Total:</strong> $<?php echo number_format((float)$sale['total_value_usd'], 2); ?></div>
          <div><strong>Outstanding Balance:</strong> $<?php echo number_format((float)$sale['outstanding_amount'], 2); ?></div>
        </div>

        <form method="post" action="receive_payment.php">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['sales_token']); ?>">
          <input type="hidden" name="sale_id" value="<?php echo (int)$sale['id']; ?>">
          <input type="hidden" name="exchange_rate" value="<?php echo htmlspecialchars((string)($sale['exchange_rate'] ?? 1.0)); ?>">

          <div class="form-grid-2">
            <div class="form-group">
              <label for="amountCurrency">Amount Received *</label>
              <input type="number" id="amountCurrency" name="amount_currency" class="form-control" step="any" min="0.01" value="<?php echo number_format(max((float)$sale['outstanding_amount'], $zeroAmount), 2, '.', ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="paymentDate">Payment Date *</label>
              <input type="date" id="paymentDate" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="paymentMethod">Payment Method</label>
              <select id="paymentMethod" name="payment_method" class="form-control">
                <?php foreach ($paymentMethods as $method): ?>
                  <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $method === 'CASH' ? 'selected' : ''; ?>><?php echo htmlspecialchars($method); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="accountId">Cash / Bank Account *</label>
              <select id="accountId" name="account_id" class="form-control" required>
                <option value="">-- Select Account --</option>
                <?php foreach ($accounts as $account): ?>
                  <option value="<?php echo (int)$account['id']; ?>"><?php echo htmlspecialchars($account['account_name'] . ' (' . $account['account_code'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="referenceNo">Reference / Transaction ID</label>
              <input type="text" id="referenceNo" name="reference_no" class="form-control" placeholder="e.g. TXN-102930291">
            </div>
            <div class="form-group">
              <label for="description">Notes</label>
              <input type="text" id="description" name="description" class="form-control" placeholder="Optional receipt notes">
            </div>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top: 20px;">
            <a href="index.php" class="btn-sm" style="text-decoration:none;">Cancel</a>
            <button type="submit" class="btn-sm btn-primary">Record Payment</button>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card" style="max-width: 760px; margin: 0 auto; padding: 24px;">
        <p>No sale selected or the sale could not be loaded.</p>
      </div>
    <?php endif; ?>
  </div>
</div>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
