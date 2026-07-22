<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized session.']);
    exit();
}

$action = $_REQUEST['action'] ?? '';

if ($action === 'save_statement_balance') {
    $report_slug = mysqli_real_escape_string($conn, $_POST['report_slug'] ?? '');
    $as_of_date = mysqli_real_escape_string($conn, $_POST['as_of_date'] ?? date('Y-m-d'));
    $balance = (float)($_POST['balance'] ?? 0.0);

    if (empty($report_slug)) {
        echo json_encode(['success' => false, 'message' => 'Missing account slug.']);
        exit();
    }

    // Try to resolve account_id from report_slug
    $accountId = null;
    if (strpos($report_slug, 'acc_') === 0) {
        $accountId = intval(substr($report_slug, 4));
    } else {
        $acctCodeMatch = '';
        if ($report_slug === 'bank_recon_rwf') $acctCodeMatch = '1010';
        elseif ($report_slug === 'bank_recon_usd') $acctCodeMatch = '1020';
        
        if (!empty($acctCodeMatch)) {
            $acctRes = mysqli_query($conn, "SELECT id FROM accounts WHERE account_code = '$acctCodeMatch' LIMIT 1");
            if ($acctRes && $acctRow = mysqli_fetch_assoc($acctRes)) {
                $accountId = intval($acctRow['id']);
            }
        }
    }

    $chk = mysqli_query($conn, "SELECT id, balance, account_id FROM bank_statement_balances WHERE report_slug = '$report_slug' AND as_of_date = '$as_of_date'");
    $old_bal = 0.00;
    
    if ($row = mysqli_fetch_assoc($chk)) {
        $id = $row['id'];
        $old_bal = (float)$row['balance'];
        $acctIdCol = $accountId ? ", account_id = $accountId" : "";
        $sql = "UPDATE bank_statement_balances SET balance = $balance $acctIdCol WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            $change_amt = $balance - $old_bal;
            if ($accountId) {
                mysqli_query($conn, "INSERT INTO bank_balance_history (account_id, report_slug, as_of_date, old_balance, new_balance, change_amount, notes, created_by, created_at) VALUES ($accountId, '$report_slug', '$as_of_date', $old_bal, $balance, $change_amt, 'Updated via Bank Reconciliation', $userId, NOW())");
            }
            logAudit($conn, 'UPDATE', 'bank_statement_balances', "Balance ID $id", "Updated bank statement balance for $report_slug ($as_of_date)", ['balance' => $old_bal], ['balance' => $balance]);
            echo json_encode(['success' => true, 'message' => 'Bank statement balance updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    } else {
        $acctIdField = $accountId ? ", account_id" : "";
        $acctIdVal = $accountId ? ", $accountId" : "";
        $sql = "INSERT INTO bank_statement_balances (report_slug, as_of_date, balance $acctIdField) VALUES ('$report_slug', '$as_of_date', $balance $acctIdVal)";
        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            $change_amt = $balance - $old_bal;
            if ($accountId) {
                mysqli_query($conn, "INSERT INTO bank_balance_history (account_id, report_slug, as_of_date, old_balance, new_balance, change_amount, notes, created_by, created_at) VALUES ($accountId, '$report_slug', '$as_of_date', $old_bal, $balance, $change_amt, 'Set via Bank Reconciliation', $userId, NOW())");
            }
            logAudit($conn, 'INSERT', 'bank_statement_balances', "Balance ID $new_id", "Set bank statement balance for $report_slug ($as_of_date)", null, ['balance' => $balance]);
            echo json_encode(['success' => true, 'message' => 'Bank statement balance set successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    }
    exit();
}

if ($action === 'save_recon_item') {
    $id = (int)($_POST['id'] ?? 0);
    $report_slug = mysqli_real_escape_string($conn, $_POST['report_slug'] ?? '');
    $as_of_date = mysqli_real_escape_string($conn, $_POST['as_of_date'] ?? date('Y-m-d'));
    $item_type = mysqli_real_escape_string($conn, $_POST['item_type'] ?? 'deposit_in_transit');
    $item_date = mysqli_real_escape_string($conn, $_POST['item_date'] ?? date('Y-m-d'));
    $reference = mysqli_real_escape_string($conn, $_POST['reference'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0.0);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');

    $allowed_types = ['outstanding_check', 'deposit_in_transit', 'unrecorded_payment'];
    if (!in_array($item_type, $allowed_types)) {
        echo json_encode(['success' => false, 'message' => 'Invalid item type.']);
        exit();
    }

    if ($id > 0) {
        $sql = "UPDATE bank_recon_items SET 
                    report_slug = '$report_slug',
                    as_of_date = '$as_of_date',
                    item_type = '$item_type',
                    item_date = '$item_date',
                    reference = '$reference',
                    amount = $amount,
                    description = '$description'
                WHERE id = $id";
        if (mysqli_query($conn, $sql)) {
            logAudit($conn, 'UPDATE', 'bank_recon_items', "Item #$id", "Updated recon item: $description ($amount)", null, ['amount' => $amount, 'type' => $item_type]);
            echo json_encode(['success' => true, 'message' => 'Reconciling item updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    } else {
        $sql = "INSERT INTO bank_recon_items (report_slug, as_of_date, item_type, item_date, reference, amount, description) 
                VALUES ('$report_slug', '$as_of_date', '$item_type', '$item_date', '$reference', $amount, '$description')";
        if (mysqli_query($conn, $sql)) {
            $new_id = mysqli_insert_id($conn);
            logAudit($conn, 'INSERT', 'bank_recon_items', "Item #$new_id", "Added recon item: $description ($amount)", null, ['amount' => $amount, 'type' => $item_type]);
            echo json_encode(['success' => true, 'message' => 'Reconciling item added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    }
    exit();
}

if ($action === 'delete_recon_item') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID.']);
        exit();
    }

    $chk = mysqli_query($conn, "SELECT description, amount FROM bank_recon_items WHERE id = $id");
    $item_info = mysqli_fetch_assoc($chk);

    $sql = "DELETE FROM bank_recon_items WHERE id = $id";
    if (mysqli_query($conn, $sql)) {
        logAudit($conn, 'DELETE', 'bank_recon_items', "Item #$id", "Deleted recon item: " . ($item_info['description'] ?? ''), $item_info, null);
        echo json_encode(['success' => true, 'message' => 'Reconciling item deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

if ($action === 'toggle_reconcile') {
    $target_type = $_POST['target_type'] ?? 'journal_line';
    $id = (int)($_POST['id'] ?? 0);
    $is_reconciled = !empty($_POST['is_reconciled']) ? 1 : 0;

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transaction ID.']);
        exit();
    }

    if ($target_type === 'recon_item') {
        $table = 'bank_recon_items';
    } else {
        $table = 'journal_entry_lines';
    }

    $recon_at = $is_reconciled ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
    $sql = "UPDATE $table SET is_reconciled = $is_reconciled, reconciled_at = $recon_at WHERE id = $id";
    
    if (mysqli_query($conn, $sql)) {
        $status_label = $is_reconciled ? 'Checked / Reconciled' : 'Unchecked / Pending';
        logAudit($conn, 'UPDATE', $table, "Record #$id", "Marked transaction #$id as $status_label", null, ['is_reconciled' => $is_reconciled]);
        echo json_encode([
            'success' => true, 
            'is_reconciled' => $is_reconciled, 
            'message' => "Transaction marked as $status_label."
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
    }
    exit();
}

if ($action === 'bulk_reconcile') {
    $account_id = (int)($_POST['account_id'] ?? 0);
    $account_code = mysqli_real_escape_string($conn, $_POST['account_code'] ?? '');
    $target_date = mysqli_real_escape_string($conn, $_POST['target_date'] ?? date('Y-m-d'));
    $is_reconciled = !empty($_POST['is_reconciled']) ? 1 : 0;

    if ($account_id > 0 || !empty($account_code)) {
        $where_acct = $account_id > 0 ? "account_id = $account_id" : "account_id IN (SELECT id FROM accounts WHERE account_code = '$account_code')";
        $recon_at = $is_reconciled ? "'" . date('Y-m-d H:i:s') . "'" : "NULL";
        
        $sql = "UPDATE journal_entry_lines jel
                JOIN journal_entries je ON jel.journal_entry_id = je.id
                SET jel.is_reconciled = $is_reconciled, jel.reconciled_at = $recon_at
                WHERE $where_acct AND je.statuss = 'POSTED' AND je.entry_date <= '$target_date'";
        
        if (mysqli_query($conn, $sql)) {
            $affected = mysqli_affected_rows($conn);
            $label = $is_reconciled ? 'reconciled' : 'unreconciled';
            echo json_encode(['success' => true, 'message' => "Successfully marked $affected transactions as $label."]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing account identifier.']);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action requested.']);

