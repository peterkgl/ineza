<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit();
}

$userId = (int)$_SESSION['user_id'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

if (empty($_SESSION['journal_entries_token'])) {
    $_SESSION['journal_entries_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['journal_entries_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['journal_entries_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'generate_no':
        if (!hasPermission($conn, $userId, 'view_journal_entries')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission.');
        }

        $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        
        $dateStr = date('Ymd', strtotime($date));
        $prefix = "JE-" . $dateStr . "-";
        
        $query = "SELECT COUNT(*) as cnt FROM journal_entries WHERE journal_no LIKE 'JE-{$dateStr}-%'";
        $result = mysqli_query($conn, $query);
        $count = 0;
        if ($result && $row = mysqli_fetch_assoc($result)) {
            $count = (int)$row['cnt'];
        }
        
        $nextNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        $journalNo = $prefix . $nextNum;
        
        sendResponse(true, 'Generated journal number successfully.', ['journal_no' => $journalNo]);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_journal_entry')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission.');
        }

        $entryDate = isset($_POST['entry_date']) ? trim($_POST['entry_date']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $currencyId = isset($_POST['currency_id']) ? (int)$_POST['currency_id'] : 1;
        $exchangeRate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 1.0;
        $linesJson = isset($_POST['lines']) ? $_POST['lines'] : '[]';

        if (empty($entryDate)) {
            sendResponse(false, 'Entry date is required.');
        }
        
        $lines = json_decode($linesJson, true);
        if (!is_array($lines) || count($lines) < 2) {
            sendResponse(false, 'At least two transaction lines are required.');
        }

        $totalDebit = 0.0;
        $totalCredit = 0.0;
        $validatedLines = [];

        foreach ($lines as $index => $line) {
            $accountId = isset($line['account_id']) ? trim((string)$line['account_id']) : '';
            $parentAccountId = isset($line['parent_account_id']) ? (int)$line['parent_account_id'] : 0;
            $debit = isset($line['debit']) ? (float)$line['debit'] : 0.0;
            $credit = isset($line['credit']) ? (float)$line['credit'] : 0.0;
            $lineDesc = isset($line['description']) ? trim($line['description']) : '';

            if ($accountId === '') {
                sendResponse(false, "Line " . ($index + 1) . ": A valid account must be selected.");
            }

            if ($debit < 0 || $credit < 0) {
                sendResponse(false, "Line " . ($index + 1) . ": Debit and credit amounts must be non-negative.");
            }

            if ($debit > 0 && $credit > 0) {
                sendResponse(false, "Line " . ($index + 1) . ": A line cannot have both Debit and Credit amounts.");
            }

            if ($debit == 0 && $credit == 0) {
                sendResponse(false, "Line " . ($index + 1) . ": Specify either a Debit or Credit amount.");
            }

            // Verify account exists and resolve the account code + parent account
            $accountCode = (int)$accountId;
            $accQuery = "SELECT id, account_code, account_name, account_type_id FROM accounts WHERE account_code = $accountCode LIMIT 1";
            $accRes = mysqli_query($conn, $accQuery);
            if (!$accRes || mysqli_num_rows($accRes) === 0) {
                $accQueryFallback = "SELECT id, account_code, account_name, account_type_id FROM accounts WHERE id = $accountCode LIMIT 1";
                $accRes = mysqli_query($conn, $accQueryFallback);
            }
            if (!$accRes || mysqli_num_rows($accRes) === 0) {
                sendResponse(false, "Line " . ($index + 1) . ": Account does not exist.");
            }
            $accountRow = mysqli_fetch_assoc($accRes);
            $resolvedAccountCode = isset($accountRow['account_code']) ? (int)$accountRow['account_code'] : $accountCode;
            $resolvedParentAccountId = $parentAccountId > 0 ? $parentAccountId : (int)($accountRow['account_type_id'] ?? 0);

            $totalDebit += $debit;
            $totalCredit += $credit;

            $validatedLines[] = [
                'account_id' => $resolvedAccountCode,
                'parent_account_id' => $resolvedParentAccountId,
                'debit' => $debit,
                'credit' => $credit,
                'description' => $lineDesc
            ];
        }

        // Validate Debit equals Credit (allow small float delta)
        if (abs($totalDebit - $totalCredit) > 0.01) {
            sendResponse(false, sprintf("Journal entry is not balanced. Total Debits: %s, Total Credits: %s. Difference: %s.", number_format($totalDebit, 2), number_format($totalCredit, 2), number_format(abs($totalDebit - $totalCredit), 2)));
        }

        // Start Transaction
        mysqli_begin_transaction($conn);

        try {
            // Generate Journal Number
            $dateStr = date('Ymd', strtotime($entryDate));
            $prefix = "JE-" . $dateStr . "-";
            $query = "SELECT COUNT(*) as cnt FROM journal_entries WHERE journal_no LIKE 'JE-{$dateStr}-%' FOR UPDATE";
            $result = mysqli_query($conn, $query);
            $count = 0;
            if ($result && $row = mysqli_fetch_assoc($result)) {
                $count = (int)$row['cnt'];
            }
            $nextNum = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
            $journalNo = $prefix . $nextNum;

            // Insert Journal Entry Header
            $insertHeader = "INSERT INTO journal_entries (journal_no, entry_date, description, statuss, created_by, created_at)
                             VALUES ('" . mysqli_real_escape_string($conn, $journalNo) . "', '$entryDate', '" . mysqli_real_escape_string($conn, $description) . "', 'POSTED', $userId, NOW())";
            
            if (!mysqli_query($conn, $insertHeader)) {
                throw new Exception("Failed to insert journal entry header: " . mysqli_error($conn));
            }
            
            $journalEntryId = mysqli_insert_id($conn);

            // Insert Lines
            foreach ($validatedLines as $line) {
                $amountCurrency = $line['debit'] > 0 ? $line['debit'] : $line['credit'];
                $amountBase = $amountCurrency * $exchangeRate;

                $insertLine = "INSERT INTO journal_entry_lines (
                                    journal_entry_id, parent_account_id, account_id, debit, credit, currency_id, exchange_rate, amount_currency, amount_base, description
                               ) VALUES (
                                    $journalEntryId, {$line['parent_account_id']}, {$line['account_id']}, {$line['debit']}, {$line['credit']}, $currencyId, $exchangeRate, $amountCurrency, $amountBase, '" . mysqli_real_escape_string($conn, $line['description']) . "'
                               )";
                
                if (!mysqli_query($conn, $insertLine)) {
                    throw new Exception("Failed to insert journal line: " . mysqli_error($conn));
                }
            }

            // Commit Transaction
            mysqli_commit($conn);

            // Log Action
            $newValues = [
                'id' => $journalEntryId,
                'journal_no' => $journalNo,
                'entry_date' => $entryDate,
                'total_debit' => $totalDebit,
                'description' => $description
            ];
            logAudit($conn, 'CREATE', 'journal_entries', $journalNo, "Created manual journal entry: $journalNo", null, $newValues);

            sendResponse(true, "Journal entry $journalNo saved and posted successfully.", ['id' => $journalEntryId]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, "System Error: " . $e->getMessage());
        }
        break;

    case 'cancel':
        if (!hasPermission($conn, $userId, 'cancel_journal_entry')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid journal entry ID.');
        }

        // Fetch original status and entry
        $query = "SELECT * FROM journal_entries WHERE id = $id LIMIT 1";
        $result = mysqli_query($conn, $query);
        if (!$result || mysqli_num_rows($result) === 0) {
            sendResponse(false, 'Journal entry not found.');
        }
        $entry = mysqli_fetch_assoc($result);

        if ($entry['statuss'] === 'CANCELLED') {
            sendResponse(false, 'Journal entry is already cancelled.');
        }

        // Update status to CANCELLED
        $update = "UPDATE journal_entries SET statuss = 'CANCELLED' WHERE id = $id";
        if (mysqli_query($conn, $update)) {
            $oldValues = ['statuss' => $entry['statuss']];
            $newValues = ['statuss' => 'CANCELLED'];
            logAudit($conn, 'CANCEL', 'journal_entries', $entry['journal_no'], "Cancelled journal entry: " . $entry['journal_no'], $oldValues, $newValues);
            sendResponse(true, "Journal entry " . $entry['journal_no'] . " has been successfully cancelled.");
        } else {
            sendResponse(false, 'Failed to cancel journal entry: ' . mysqli_error($conn));
        }
        break;

    default:
        http_response_code(404);
        sendResponse(false, 'Invalid action specified.');
        break;
}
?>
