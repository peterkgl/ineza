<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

if ($action === 'save_bank_balance') {
    $accountId = intval($_POST['account_id'] ?? 0);
    $asOfDate = mysqli_real_escape_string($conn, $_POST['as_of_date'] ?? date('Y-m-d'));
    $balance = floatval($_POST['balance'] ?? 0.0);
    $notes = mysqli_real_escape_string($conn, trim($_POST['notes'] ?? ''));

    if ($accountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Please select a valid bank account.']);
        exit();
    }

    if (empty($asOfDate)) {
        $asOfDate = date('Y-m-d');
    }

    // Verify account exists & belongs to Cash & Cash Equivalents
    $acctQuery = mysqli_query($conn, "
        SELECT a.id, a.account_code, a.account_name, at.name as account_type_name 
        FROM accounts a 
        JOIN account_types at ON a.account_type_id = at.id 
        WHERE a.id = $accountId AND (at.name = 'Cash & Cash Equivalents' OR a.account_type_id = 1)
        LIMIT 1
    ");

    if (!$acctQuery || mysqli_num_rows($acctQuery) === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected account is not a valid Bank / Cash & Cash Equivalents account.']);
        exit();
    }

    $account = mysqli_fetch_assoc($acctQuery);
    $acctCode = $account['account_code'];

    // Determine report_slug matching Bank Reconciliation standards
    $reportSlug = 'acc_' . $accountId;
    if ($acctCode === '1031' || $acctCode === '1010-03' || strpos(strtolower($account['account_name']), 'rwf') !== false || $acctCode == '1010') {
        $reportSlug = 'bank_recon_rwf';
    } elseif ($acctCode === '1041' || $acctCode === '1010-01' || strpos(strtolower($account['account_name']), 'usd') !== false || $acctCode == '1020') {
        $reportSlug = 'bank_recon_usd';
    } elseif ($acctCode === '1051' || $acctCode === '1010-04' || strpos(strtolower($account['account_name']), 'euro') !== false || strpos(strtolower($account['account_name']), 'eur') !== false) {
        $reportSlug = 'bank_recon_euro';
    }

    $slugEsc = mysqli_real_escape_string($conn, $reportSlug);

    // Check existing balance entry in bank_statement_balances
    $chkStmt = mysqli_query($conn, "
        SELECT id, balance 
        FROM bank_statement_balances 
        WHERE (account_id = $accountId OR report_slug = '$slugEsc') 
          AND as_of_date = '$asOfDate'
        LIMIT 1
    ");

    $oldBalance = 0.00;
    $idToUpdate = 0;

    if ($chkStmt && mysqli_num_rows($chkStmt) > 0) {
        $existingRow = mysqli_fetch_assoc($chkStmt);
        $idToUpdate = $existingRow['id'];
        $oldBalance = (float)$existingRow['balance'];
        
        $sql = "UPDATE bank_statement_balances SET balance = $balance, account_id = $accountId, report_slug = '$slugEsc', updated_at = NOW() WHERE id = $idToUpdate";
        $ok = mysqli_query($conn, $sql);
    } else {
        // Find previous latest balance prior to this as_of_date for logging old_balance context if available
        $prevBalQuery = mysqli_query($conn, "
            SELECT balance FROM bank_statement_balances 
            WHERE (account_id = $accountId OR report_slug = '$slugEsc') AND as_of_date < '$asOfDate'
            ORDER BY as_of_date DESC LIMIT 1
        ");
        if ($prevBalQuery && mysqli_num_rows($prevBalQuery) > 0) {
            $prevRow = mysqli_fetch_assoc($prevBalQuery);
            $oldBalance = (float)$prevRow['balance'];
        }

        $sql = "INSERT INTO bank_statement_balances (account_id, report_slug, as_of_date, balance) VALUES ($accountId, '$slugEsc', '$asOfDate', $balance)";
        $ok = mysqli_query($conn, $sql);
    }

    if ($ok) {
        $changeAmount = $balance - $oldBalance;
        
        // Log movement entry into bank_balance_history
        $histSql = "
            INSERT INTO bank_balance_history 
            (account_id, report_slug, as_of_date, old_balance, new_balance, change_amount, notes, created_by, created_at) 
            VALUES 
            ($accountId, '$slugEsc', '$asOfDate', $oldBalance, $balance, $changeAmount, '$notes', $userId, NOW())
        ";
        mysqli_query($conn, $histSql);

        // Audit Trail
        logAudit(
            $conn, 
            'RECORD_BALANCE', 
            'bank_statement_balances', 
            "Account #{$acctCode} - {$account['account_name']}", 
            "Recorded bank statement balance of RWF " . number_format($balance, 2) . " as of $asOfDate ($notes)", 
            ['balance' => $oldBalance], 
            ['balance' => $balance]
        );

        echo json_encode([
            'success' => true, 
            'message' => 'Bank statement balance saved successfully.',
            'data' => [
                'account_id' => $accountId,
                'as_of_date' => $asOfDate,
                'new_balance' => $balance,
                'old_balance' => $oldBalance,
                'change_amount' => $changeAmount
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

if ($action === 'get_balance_history') {
    $accountId = intval($_GET['account_id'] ?? 0);
    if ($accountId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid account ID.']);
        exit();
    }

    $historyQuery = "
        SELECT 
            h.*,
            CONCAT(u.first_name, ' ', u.last_name) AS created_by_name
        FROM bank_balance_history h
        LEFT JOIN users u ON h.created_by = u.id
        WHERE h.account_id = $accountId
        ORDER BY h.as_of_date DESC, h.id DESC
    ";

    $res = mysqli_query($conn, $historyQuery);
    $history = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $history[] = [
                'id' => $row['id'],
                'as_of_date' => $row['as_of_date'],
                'old_balance' => (float)$row['old_balance'],
                'new_balance' => (float)$row['new_balance'],
                'change_amount' => (float)$row['change_amount'],
                'notes' => $row['notes'],
                'created_by_name' => !empty($row['created_by_name']) ? $row['created_by_name'] : 'System User',
                'created_at' => $row['created_at']
            ];
        }
    }

    echo json_encode(['success' => true, 'history' => $history]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit();
