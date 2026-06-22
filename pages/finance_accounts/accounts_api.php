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

if (empty($_SESSION['account_token'])) {
    $_SESSION['account_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['account_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['account_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

function getAccountTypeRange($conn, $account_type_id) {
    $current_id = (int)$account_type_id;
    $root_type = null;
    $depth = 0;
    
    while ($current_id > 0 && $depth < 10) {
        $q = "SELECT id, code, name, parent_id FROM account_types WHERE id = $current_id LIMIT 1";
        $res = mysqli_query($conn, $q);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $root_type = $row;
            if ($row['parent_id'] === null || (int)$row['parent_id'] === 0 || (int)$row['parent_id'] === $current_id) {
                break;
            }
            $current_id = (int)$row['parent_id'];
            $depth++;
        } else {
            break;
        }
    }
    
    if ($root_type) {
        $root_code = (int)$root_type['code'];
        return [
            'min' => $root_code,
            'max' => $root_code + 999,
            'root_name' => $root_type['name'],
            'root_code' => $root_type['code']
        ];
    }
    return null;
}

switch ($action) {
    case 'generate_code':
        if (!hasPermission($conn, $userId, 'view_accounts')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission.');
        }

        $account_type_id = isset($_GET['account_type_id']) ? (int)$_GET['account_type_id'] : 0;
        if ($account_type_id <= 0) {
            sendResponse(false, 'Invalid account type.');
        }

        // Get the account type code
        $typeQuery = "SELECT code, name FROM account_types WHERE id = $account_type_id LIMIT 1";
        $typeResult = mysqli_query($conn, $typeQuery);
        if (!$typeResult || mysqli_num_rows($typeResult) === 0) {
            sendResponse(false, 'Account type not found.');
        }
        $typeRow = mysqli_fetch_assoc($typeResult);
        $typeCode = $typeRow['code'];

        // Get the max account code used under this account type category
        $maxQuery = "SELECT MAX(CAST(account_code AS UNSIGNED)) as max_code FROM accounts WHERE account_type_id = $account_type_id";
        $maxResult = mysqli_query($conn, $maxQuery);
        $maxCode = null;
        if ($maxResult && $maxRow = mysqli_fetch_assoc($maxResult)) {
            $maxCode = $maxRow['max_code'];
        }

        $candidate = null;
        if ($maxCode !== null && $maxCode > 0) {
            $candidate = (int)$maxCode + 10;
        } else {
            // First code formula
            $tcInt = (int)$typeCode;
            if ($tcInt % 1000 == 0) {
                $candidate = $tcInt + 110;
            } else {
                $candidate = $tcInt + 10;
            }
        }

        // Loop to find the next unused code globally in the accounts table
        $nextCode = null;
        $found = false;
        $attempts = 0;
        while (!$found && $attempts < 100) {
            $checkQ = "SELECT id FROM accounts WHERE account_code = '$candidate' LIMIT 1";
            $checkR = mysqli_query($conn, $checkQ);
            if ($checkR && mysqli_num_rows($checkR) > 0) {
                $candidate += 10;
                $attempts++;
            } else {
                $nextCode = $candidate;
                $found = true;
            }
        }

        if (!$found) {
            sendResponse(false, 'Unable to generate a unique account code after 100 attempts.');
        }

        // Validate range based on selected account type's root parent
        $range = getAccountTypeRange($conn, $account_type_id);
        if ($range) {
            if ($nextCode < $range['min'] || $nextCode > $range['max']) {
                sendResponse(false, "Cannot generate code: Generated code $nextCode exceeds the maximum allowed range limit of {$range['max']} for {$range['root_name']}.");
            }
        } else {
            sendResponse(false, 'Selected account type range could not be verified.');
        }

        sendResponse(true, 'Code generated successfully.', ['code' => (string)$nextCode]);
        break;

    case 'list':
        if (!hasPermission($conn, $userId, 'view_accounts')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view accounts.');
        }

        $query = "SELECT a.*, t.name as account_type_name, t.code as account_type_code
                  FROM accounts a 
                  JOIN account_types t ON a.account_type_id = t.id 
                  ORDER BY a.account_code ASC";
        $result = mysqli_query($conn, $query);
        $accounts = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $openingBalance = (float)$row['opening_balance'];

                $accounts[] = [
                    'id' => (int)$row['id'],
                    'account_type_id' => (int)$row['account_type_id'],
                    'account_type_name' => $row['account_type_name'],
                    'account_type_code' => $row['account_type_code'],
                    'account_code' => $row['account_code'],
                    'account_name' => $row['account_name'],
                    'opening_balance' => $openingBalance,
                    'is_active' => (int)$row['is_active'],
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'accounts', 'Accounts List', 'User viewed the financial accounts list');

        sendResponse(true, 'Accounts retrieved successfully.', $accounts);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_account')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create an account.');
        }

        $account_type_id = isset($_POST['account_type_id']) ? (int)$_POST['account_type_id'] : 0;
        $account_code = isset($_POST['account_code']) ? trim($_POST['account_code']) : '';
        $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
        $opening_balance = isset($_POST['opening_balance']) && $_POST['opening_balance'] !== '' ? (float)$_POST['opening_balance'] : 0.00;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($account_type_id <= 0 || empty($account_code) || empty($account_name)) {
            sendResponse(false, 'Account Type, Account Code, and Account Name are required.');
        }

        $account_code_esc = mysqli_real_escape_string($conn, $account_code);
        $account_name_esc = mysqli_real_escape_string($conn, $account_name);
        $description_esc = $description !== null ? "'" . mysqli_real_escape_string($conn, $description) . "'" : "NULL";

        // Validate code uniqueness
        $checkQuery = "SELECT id FROM accounts WHERE account_code = '$account_code_esc' LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "An account with code '$account_code' already exists.");
        }

        // Validate range based on selected account type's root parent
        $range = getAccountTypeRange($conn, $account_type_id);
        if ($range) {
            $codeInt = (int)$account_code;
            if ($codeInt < $range['min'] || $codeInt > $range['max']) {
                sendResponse(false, "Account code must be between {$range['min']} and {$range['max']} for account type under {$range['root_name']} ({$range['root_code']}).");
            }
        } else {
            sendResponse(false, 'Selected account type is invalid.');
        }

        mysqli_begin_transaction($conn);

        try {
            $insertQuery = "INSERT INTO accounts (account_type_id, account_code, account_name, opening_balance, is_active, description) 
                            VALUES ($account_type_id, '$account_code_esc', '$account_name_esc', $opening_balance, $is_active, $description_esc)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                
                $newValues = [
                    'id' => $newId,
                    'account_type_id' => $account_type_id,
                    'account_code' => $account_code,
                    'account_name' => $account_name,
                    'opening_balance' => $opening_balance,
                    'current_balance' => $opening_balance,
                    'is_active' => $is_active,
                    'description' => $description
                ];

                logAudit($conn, 'CREATE', 'accounts', $account_code, "Created new account: $account_name ($account_code)", null, $newValues);

                $_SESSION['account_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account '$account_code' created successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create account: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_account')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit accounts.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $account_type_id = isset($_POST['account_type_id']) ? (int)$_POST['account_type_id'] : 0;
        $account_code = isset($_POST['account_code']) ? trim($_POST['account_code']) : '';
        $account_name = isset($_POST['account_name']) ? trim($_POST['account_name']) : '';
        $opening_balance = isset($_POST['opening_balance']) && $_POST['opening_balance'] !== '' ? (float)$_POST['opening_balance'] : 0.00;
        $description = isset($_POST['description']) ? trim($_POST['description']) : null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || $account_type_id <= 0 || empty($account_code) || empty($account_name)) {
            sendResponse(false, 'Valid ID, Account Type, Account Code, and Account Name are required.');
        }

        $fetchQuery = "SELECT * FROM accounts WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Account not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $account_code_esc = mysqli_real_escape_string($conn, $account_code);
        $account_name_esc = mysqli_real_escape_string($conn, $account_name);
        $description_esc = $description !== null ? "'" . mysqli_real_escape_string($conn, $description) . "'" : "NULL";

        // Validate code uniqueness
        $checkQuery = "SELECT id FROM accounts WHERE account_code = '$account_code_esc' AND id != $id LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "Another account with code '$account_code' already exists.");
        }

        // Validate range based on selected account type's root parent
        $range = getAccountTypeRange($conn, $account_type_id);
        if ($range) {
            $codeInt = (int)$account_code;
            if ($codeInt < $range['min'] || $codeInt > $range['max']) {
                sendResponse(false, "Account code must be between {$range['min']} and {$range['max']} for account type under {$range['root_name']} ({$range['root_code']}).");
            }
        } else {
            sendResponse(false, 'Selected account type is invalid.');
        }

        mysqli_begin_transaction($conn);

        try {
            $updateQuery = "UPDATE accounts SET 
                                account_type_id = $account_type_id, 
                                account_code = '$account_code_esc', 
                                account_name = '$account_name_esc', 
                                opening_balance = $opening_balance, 
                                is_active = $is_active, 
                                description = $description_esc, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $sumQuery = "SELECT 
                                 COALESCE(SUM(jel.debit), 0) as total_debit,
                                 COALESCE(SUM(jel.credit), 0) as total_credit
                             FROM journal_entry_lines jel 
                             JOIN journal_entries je ON jel.journal_entry_id = je.id 
                             WHERE jel.account_id = $id AND je.statuss = 'POSTED'";
                $sumRes = mysqli_query($conn, $sumQuery);
                $totalDebit = 0;
                $totalCredit = 0;
                if ($sumRes && $sumRow = mysqli_fetch_assoc($sumRes)) {
                    $totalDebit = (float)$sumRow['total_debit'];
                    $totalCredit = (float)$sumRow['total_credit'];
                }

                $rootCode = (int)$range['root_code'];
                if (($rootCode >= 1000 && $rootCode < 2000) || ($rootCode >= 5000 && $rootCode <= 6999)) {
                    $currentBalance = $opening_balance + ($totalDebit - $totalCredit);
                } else {
                    $currentBalance = $opening_balance + ($totalCredit - $totalDebit);
                }

                $newValues = [
                    'id' => $id,
                    'account_type_id' => $account_type_id,
                    'account_code' => $account_code,
                    'account_name' => $account_name,
                    'opening_balance' => $opening_balance,
                    'current_balance' => $currentBalance,
                    'is_active' => $is_active,
                    'description' => $description
                ];

                logAudit($conn, 'UPDATE', 'accounts', $account_code, "Updated account: $account_name ($account_code)", $oldValues, $newValues);

                $_SESSION['account_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account '$account_code' updated successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update account: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_account')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete accounts.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid account ID.');
        }

        $fetchQuery = "SELECT * FROM accounts WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Account not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $account_code = $oldValues['account_code'];

        // In this schema, we don't have transaction tables yet, but if we did, we would check for dependencies here.
        // As it stands, it is safe to delete.

        mysqli_begin_transaction($conn);

        try {
            $deleteQuery = "DELETE FROM accounts WHERE id = $id";
            if (mysqli_query($conn, $deleteQuery)) {
                logAudit($conn, 'DELETE', 'accounts', $account_code, "Deleted account: " . $oldValues['account_name'] . " ($account_code)", $oldValues);

                $_SESSION['account_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account '$account_code' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete account: ' . $e->getMessage());
        }
        break;

    case 'check_code':
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if (empty($code)) {
            echo json_encode(['success' => true, 'exists' => false]);
            exit();
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $query = "SELECT id FROM accounts WHERE account_code = '$codeEsc'";
        if ($id > 0) {
            $query .= " AND id != $id";
        }
        $query .= " LIMIT 1";

        $result = mysqli_query($conn, $query);
        $exists = ($result && mysqli_num_rows($result) > 0);

        echo json_encode(['success' => true, 'exists' => $exists]);
        exit();
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
