<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'create_purchas')) {
    header('Location: index.php');
    exit();
}

if (empty($_SESSION['purchas_token'])) {
    $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
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

$selectedPurchaseId = isset($_GET['purchase_id']) ? (int)$_GET['purchase_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedPurchaseId = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';

    if (empty($postToken) || $postToken !== $_SESSION['purchas_token']) {
        $errors[] = 'Transaction token mismatch or session expired. Please refresh the page and try again.';
    } else {
        $amountCurrency = isset($_POST['amount_currency']) ? (float)$_POST['amount_currency'] : $zeroAmount;
        $paymentDate = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : date('Y-m-d');
        $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
        $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $exchangeRate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 1.0;

        if ($selectedPurchaseId <= 0) {
            $errors[] = 'Please select a purchase to pay.';
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
                $purchaseQuery = mysqli_query($conn, "
                    SELECT p.*, s.name AS supplier_name, s.payables_account_id, s.currency_id AS supplier_currency_id
                    FROM purchasing p
                    JOIN suppliers s ON s.id = p.supplier_id
                    WHERE p.id = $selectedPurchaseId
                    LIMIT 1
                ");
                if (!$purchaseQuery || mysqli_num_rows($purchaseQuery) === 0) {
                    throw new Exception('Selected purchase was not found.');
                }
                $purchase = mysqli_fetch_assoc($purchaseQuery);

                $supplierId = (int)$purchase['supplier_id'];
                $allocatedQuery = mysqli_query($conn, "
                    SELECT COALESCE(SUM(amount_allocated), 0) AS allocated_total
                    FROM supplier_payment_allocations
                    WHERE purchase_id = $selectedPurchaseId
                ");
                $allocatedTotal = $zeroAmount;
                if ($allocatedQuery && mysqli_num_rows($allocatedQuery) > 0) {
                    $allocatedRow = mysqli_fetch_assoc($allocatedQuery);
                    $allocatedTotal = (float)$allocatedRow['allocated_total'];
                }

                $baseAmount = (float)($purchase['net_paid_supplier_usd'] !== null && (float)$purchase['net_paid_supplier_usd'] > 0 ? $purchase['net_paid_supplier_usd'] : $purchase['purchase_value_usd']);
                $outstanding = $baseAmount - $allocatedTotal;
                if ($amountCurrency > $outstanding + 0.01) {
                    throw new Exception('Payment amount exceeds the outstanding balance for this purchase.');
                }

                $cashAccountQuery = mysqli_query($conn, "
                    SELECT id, account_code, account_name, account_type_id
                    FROM accounts
                    WHERE id = $accountId AND is_active = 1
                    LIMIT 1
                ");
                if (!$cashAccountQuery || mysqli_num_rows($cashAccountQuery) === 0) {
                    throw new Exception('Selected cash or bank account was not found.');
                }
                $cashAccountRow = mysqli_fetch_assoc($cashAccountQuery);
                $cashAccountCode = (int)$cashAccountRow['account_code'];
                $cashParentAccountId = (int)$cashAccountRow['account_type_id'];

                $supplierAccountRef = $purchase['payables_account_id'];
                if (empty($supplierAccountRef)) {
                    throw new Exception('The supplier does not have a payable account configured.');
                }
                $supplierAccount = resolveAccountDetails($conn, $supplierAccountRef);
                if (!$supplierAccount || empty($supplierAccount['account_code'])) {
                    throw new Exception('Supplier payable account was not found.');
                }
                $supplierAccountCode = (int)$supplierAccount['account_code'];
                $supplierParentAccountId = (int)$supplierAccount['parent_account_id'];

                $currencyId = (int)($purchase['supplier_currency_id'] ?? 2);
                $amountBase = $amountCurrency * $exchangeRate;
                $paymentNo = 'SUPP-PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $paymentNoEsc = mysqli_real_escape_string($conn, $paymentNo);
                $descriptionEsc = mysqli_real_escape_string($conn, empty($description) ? "Supplier payment for purchase: {$purchase['purchase_no']}" : $description);
                $refNoEsc = mysqli_real_escape_string($conn, $referenceNo);
                $paymentDateEsc = mysqli_real_escape_string($conn, $paymentDate);

                $dateStr = date('Ymd', strtotime($paymentDate));
                $prefix = 'JE-' . $dateStr . '-';
                $journalCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM journal_entries WHERE journal_no LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'");
                $journalCount = 0;
                if ($journalCountQuery) {
                    $journalCountRow = mysqli_fetch_assoc($journalCountQuery);
                    $journalCount = (int)($journalCountRow['cnt'] ?? 0);
                }
                $journalNo = $prefix . str_pad($journalCount + 1, 4, '0', STR_PAD_LEFT);
                $journalDescription = 'Supplier payment: ' . $paymentNo;

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
                mysqli_stmt_bind_param($insertJournalLine, 'iiiddiddds', $journalEntryId, $supplierParentAccountId, $supplierAccountCode,$amountCurrency, $zeroAmount,  $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($insertJournalLine, 'iiiddiddds', $journalEntryId, $cashParentAccountId, $cashAccountCode,$zeroAmount, $amountCurrency,  $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_close($insertJournalLine);

                $insertPayment = mysqli_prepare($conn, "
                    INSERT INTO supplier_payments
                    (supplier_id, account_id, currency_id, exchange_rate, amount_currency, amount_base, journal_entry_id, status, payment_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$insertPayment) {
                    throw new Exception('Prepare failed: ' . mysqli_error($conn));
                }
                $paymentStatus = 'approved';
                mysqli_stmt_bind_param($insertPayment, 'iiidddiss', $supplierId, $accountId, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalEntryId, $paymentStatus, $paymentDate);
                if (!mysqli_stmt_execute($insertPayment)) {
                    throw new Exception(mysqli_error($conn));
                }
                $paymentId = mysqli_insert_id($conn);
                mysqli_stmt_close($insertPayment);

                $insertAllocation = "INSERT INTO supplier_payment_allocations (supplier_payment_id, purchase_id, amount_allocated)
                    VALUES ($paymentId, $selectedPurchaseId, $amountCurrency)";
                if (!mysqli_query($conn, $insertAllocation)) {
                    throw new Exception('Failed to allocate payment to the purchase: ' . mysqli_error($conn));
                }

                $insertAdvance = "INSERT INTO supplier_advances
                    (supplier_id, currency_id, amount, exchange_rate, advance_date, purpose, status, notes, created_by)
                    VALUES ($supplierId, $currencyId, $amountCurrency, $exchangeRate, '$paymentDateEsc', 'Supplier payment', 'PAID', '$descriptionEsc', $userId)";
                if (!mysqli_query($conn, $insertAdvance)) {
                    throw new Exception('Failed to record supplier advance/payment entry: ' . mysqli_error($conn));
                }

                logAudit($conn, 'CREATE', 'supplier_payments', $paymentNo, "Recorded supplier payment: $paymentNo", null, [
                    'purchase_id' => $selectedPurchaseId,
                    'supplier_id' => $supplierId,
                    'amount_currency' => $amountCurrency,
                    'journal_no' => $journalNo
                ]);

                mysqli_commit($conn);
                $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
                header('Location: pay_supplier.php?payment=success');
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $errors[] = $e->getMessage();
            }
        }
    }
}

$purchaseOptions = [];
$purchaseQuery = mysqli_query($conn, "
    SELECT p.id, p.purchase_no, p.purchase_date, p.supplier_id, p.purchase_value_usd, p.net_paid_supplier_usd, p.exchange_rate, s.name AS supplier_name,
           COALESCE((SELECT SUM(amount_allocated) FROM supplier_payment_allocations spa WHERE spa.purchase_id = p.id), 0) AS allocated_amount
    FROM purchasing p
    JOIN suppliers s ON s.id = p.supplier_id
    WHERE s.is_active = 1
    ORDER BY p.purchase_date DESC, p.id DESC
");
if ($purchaseQuery) {
    while ($row = mysqli_fetch_assoc($purchaseQuery)) {
        $baseAmount = (float)($row['net_paid_supplier_usd'] !== null && (float)$row['net_paid_supplier_usd'] > 0 ? $row['net_paid_supplier_usd'] : $row['purchase_value_usd']);
        $allocatedAmount = (float)$row['allocated_amount'];
        $outstanding = $baseAmount - $allocatedAmount;
        if ($outstanding > 0.01) {
            $purchaseOptions[] = [
                'id' => (int)$row['id'],
                'purchase_no' => $row['purchase_no'],
                'supplier_name' => $row['supplier_name'],
                'purchase_date' => $row['purchase_date'],
                'outstanding' => $outstanding,
                'exchange_rate' => (float)($row['exchange_rate'] ?? 1.0)
            ];
        }
    }
}

if ($selectedPurchaseId <= 0 && !empty($purchaseOptions)) {
    $selectedPurchaseId = (int)$purchaseOptions[0]['id'];
}

$selectedPurchase = null;
foreach ($purchaseOptions as $purchaseOption) {
    if ((int)$purchaseOption['id'] === $selectedPurchaseId) {
        $selectedPurchase = $purchaseOption;
        break;
    }
}

$accounts = [];
$accQuery = mysqli_query($conn, "SELECT id, account_code, account_name FROM accounts WHERE account_type_id = 1 AND is_active = 1 ORDER BY account_name ASC");
if ($accQuery) {
    while ($row = mysqli_fetch_assoc($accQuery)) {
        $accounts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Pay Supplier</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/purchas.css">
</head>
<body>
<?php include '../include/sidebar.php'; ?>
<div class="main">
  <?php $page_title = 'Pay Supplier'; include '../include/navbar.php'; ?>
  <div class="content">
    <div class="page-header">
      <div>
        <h1 class="page-title">Pay Supplier</h1>
        <div class="page-sub">Record a supplier payment against an outstanding purchase and post the linked journal entries.</div>
      </div>
      <div class="page-actions">
        <a href="index.php" class="btn-sm" style="text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">Back to Purchases</a>
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

    <?php if (!empty($successMessage) || (isset($_GET['payment']) && $_GET['payment'] === 'success')): ?>
      <div class="alert alert-success" style="margin-bottom: 16px;">
        Supplier payment recorded successfully.
      </div>
    <?php endif; ?>

    <div class="card" style="max-width: 780px; margin: 0 auto; padding: 24px;">
      <?php if (empty($purchaseOptions)): ?>
        <p>No outstanding supplier purchases are available to pay right now.</p>
      <?php else: ?>
        <div style="display:grid; gap:12px; margin-bottom: 24px;">
          <div><strong>Outstanding purchases:</strong></div>
          <div class="table-container">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Purchase No</th>
                  <th>Supplier</th>
                  <th>Outstanding</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($purchaseOptions as $option): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($option['purchase_no']); ?></td>
                    <td><?php echo htmlspecialchars($option['supplier_name']); ?></td>
                    <td style="text-align:right;">$<?php echo number_format((float)$option['outstanding'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <form method="post" action="pay_supplier.php">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['purchas_token']); ?>">
          <input type="hidden" name="exchange_rate" value="<?php echo htmlspecialchars((string)($selectedPurchase['exchange_rate'] ?? 1.0)); ?>">

          <div class="form-group">
            <label for="purchaseId">Purchase to Pay *</label>
            <select id="purchaseId" name="purchase_id" class="form-control" required onchange="updateOutstandingAmount()">
              <?php foreach ($purchaseOptions as $option): ?>
                <option value="<?php echo (int)$option['id']; ?>" data-outstanding="<?php echo number_format((float)$option['outstanding'], 2, '.', ''); ?>" data-exchange-rate="<?php echo number_format((float)$option['exchange_rate'], 6, '.', ''); ?>" <?php echo ((int)$option['id'] === $selectedPurchaseId) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($option['purchase_no'] . ' — ' . $option['supplier_name'] . ' — Outstanding $' . number_format((float)$option['outstanding'], 2)); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="amountCurrency">Amount to Pay *</label>
              <input type="number" id="amountCurrency" name="amount_currency" class="form-control" step="any" min="0.01" value="<?php echo number_format((float)($selectedPurchase['outstanding'] ?? 0), 2, '.', ''); ?>" required>
            </div>
            <div class="form-group">
              <label for="paymentDate">Payment Date *</label>
              <input type="date" id="paymentDate" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="accountId">Cash / Bank Account *</label>
              <select id="accountId" name="account_id" class="form-control" required>
                <option value="">-- Select Account --</option>
                <?php foreach ($accounts as $account): ?>
                  <option value="<?php echo (int)$account['id']; ?>"><?php echo htmlspecialchars($account['account_name'] . ' (' . $account['account_code'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="referenceNo">Reference / Transaction ID</label>
              <input type="text" id="referenceNo" name="reference_no" class="form-control" placeholder="e.g. EFT-1001">
            </div>
          </div>

          <div class="form-group">
            <label for="description">Notes</label>
            <input type="text" id="description" name="description" class="form-control" placeholder="Optional supplier payment notes">
          </div>

          <div style="display:flex; justify-content:flex-end; gap:8px; margin-top: 20px;">
            <a href="index.php" class="btn-sm" style="text-decoration:none;">Cancel</a>
            <button type="submit" class="btn-sm btn-primary">Record Payment</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script>
function updateOutstandingAmount() {
  var select = document.getElementById('purchaseId');
  var amountInput = document.getElementById('amountCurrency');
  var exchangeInput = document.querySelector('input[name="exchange_rate"]');
  var selected = select.options[select.selectedIndex];
  if (!selected) {
    return;
  }
  var outstanding = selected.getAttribute('data-outstanding') || '0';
  var exchangeRate = selected.getAttribute('data-exchange-rate') || '1';
  if (amountInput) {
    amountInput.value = outstanding;
  }
  if (exchangeInput) {
    exchangeInput.value = exchangeRate;
  }
}
window.addEventListener('DOMContentLoaded', updateOutstandingAmount);
</script>
</body>
</html>
