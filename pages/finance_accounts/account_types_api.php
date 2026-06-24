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

if (empty($_SESSION['account_type_token'])) {
    $_SESSION['account_type_token'] = bin2hex(random_bytes(32));
}

// Hard-coded account groups with their ranges
$accountGroupRanges = [
    -1 => ['range_start' => 1000, 'range_end' => 1999],
    -2 => ['range_start' => 2000, 'range_end' => 2999],
    -3 => ['range_start' => 3000, 'range_end' => 3999],
    -4 => ['range_start' => 4000, 'range_end' => 4999],
    -5 => ['range_start' => 5000, 'range_end' => 5999],
    -6 => ['range_start' => 6000, 'range_end' => 6999]
];

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['account_type_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['account_type_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_account_types')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view account types.');
        }

        $query = "SELECT t.*, p.name as parent_name, p.code as parent_code 
                  FROM account_types t 
                  LEFT JOIN account_types p ON t.parent_id = p.id 
                  ORDER BY t.code ASC";
        $result = mysqli_query($conn, $query);
        $types = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $types[] = [
                    'id' => (int)$row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'parent_id' => $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
                    'parent_name' => $row['parent_name'],
                    'parent_code' => $row['parent_code'],
                    'is_editable' => (int)$row['is_editable'],
                    'is_deletable' => (int)$row['is_deletable'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'is_hardcoded' => (int)$row['id'] < 0
                ];
            }
        }

        logAudit($conn, 'VIEW', 'account_types', 'Account Types List', 'User viewed the account types list');

        sendResponse(true, 'Account types retrieved successfully.', $types);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_account_type')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create an account type.');
        }

        global $accountGroupRanges;

        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Account type code and name are required.');
        }

        if (strlen($code) > 10) {
            sendResponse(false, 'Account type code cannot exceed 10 characters.');
        }

        // Validate code is within the correct range for the selected parent group
        if ($parent_id === null || !isset($accountGroupRanges[$parent_id])) {
            sendResponse(false, 'Invalid parent group.');
        }

        $range = $accountGroupRanges[$parent_id];
        $codeNum = (int)$code;

        if ($codeNum < $range['range_start'] + 1 || $codeNum > $range['range_end']) {
            sendResponse(false, "Code must be between " . ($range['range_start'] + 1) . " and " . $range['range_end'] . " for this group.");
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $nameEsc = mysqli_real_escape_string($conn, $name);

        // Check if code is unique
        $checkQuery = "SELECT id FROM account_types WHERE code = '$codeEsc' LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "An account type with code '$code' already exists.");
        }

        // Check if name is unique
        $checkNameQuery = "SELECT id FROM account_types WHERE name = '$nameEsc' LIMIT 1";
        $checkNameResult = mysqli_query($conn, $checkNameQuery);
        if ($checkNameResult && mysqli_num_rows($checkNameResult) > 0) {
            sendResponse(false, "An account type with name '$name' already exists.");
        }

        mysqli_begin_transaction($conn);

        try {
            $insertQuery = "INSERT INTO account_types (code, name, parent_id, is_editable, is_deletable) 
                            VALUES ('$codeEsc', '$nameEsc', $parent_id, 1, 1)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                
                $newValues = [
                    'id' => $newId,
                    'code' => $code,
                    'name' => $name,
                    'parent_id' => $parent_id,
                    'is_editable' => 1,
                    'is_deletable' => 1
                ];

                logAudit($conn, 'CREATE', 'account_types', $code, "Created new account type: $name ($code)", null, $newValues);

                $_SESSION['account_type_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account type '$code' created successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create account type: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_account_type')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit account types.');
        }

        global $accountGroupRanges;

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM account_types WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Account type not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        if ((int)$oldValues['is_editable'] === 0) {
            sendResponse(false, 'This is a system-defined account type and cannot be modified.');
        }

        if ($parent_id !== null && $parent_id === $id) {
            sendResponse(false, 'An account type cannot be its own parent.');
        }

        // Validate code is within the correct range for the selected parent group
        if ($parent_id === null || !isset($accountGroupRanges[$parent_id])) {
            sendResponse(false, 'Invalid parent group.');
        }

        $range = $accountGroupRanges[$parent_id];
        $codeNum = (int)$code;

        if ($codeNum < $range['range_start'] + 1 || $codeNum > $range['range_end']) {
            sendResponse(false, "Code must be between " . ($range['range_start'] + 1) . " and " . $range['range_end'] . " for this group.");
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $nameEsc = mysqli_real_escape_string($conn, $name);

        // Check if code is unique (excluding current type)
        $checkQuery = "SELECT id FROM account_types WHERE code = '$codeEsc' AND id != $id LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "Another account type with code '$code' already exists.");
        }

        // Check if name is unique (excluding current type)
        $checkNameQuery = "SELECT id FROM account_types WHERE name = '$nameEsc' AND id != $id LIMIT 1";
        $checkNameResult = mysqli_query($conn, $checkNameQuery);
        if ($checkNameResult && mysqli_num_rows($checkNameResult) > 0) {
            sendResponse(false, "Another account type with name '$name' already exists.");
        }

        mysqli_begin_transaction($conn);

        try {
            $updateQuery = "UPDATE account_types SET 
                            code = '$codeEsc', 
                            name = '$nameEsc', 
                            parent_id = $parent_id, 
                            updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'code' => $code,
                    'name' => $name,
                    'parent_id' => $parent_id
                ];

                logAudit($conn, 'UPDATE', 'account_types', $code, "Updated account type: $name ($code)", $oldValues, $newValues);

                $_SESSION['account_type_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account type '$code' updated successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update account type: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_account_type')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete account types.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid account type ID.');
        }

        $fetchQuery = "SELECT * FROM account_types WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Account type not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['code'];

        if ((int)$oldValues['is_deletable'] === 0) {
            sendResponse(false, 'This is a system-defined account type and cannot be deleted.');
        }

        // Check if referenced in accounts table
        $accQuery = "SELECT COUNT(*) as count FROM accounts WHERE account_type_id = $id";
        $accResult = mysqli_query($conn, $accQuery);
        $accCount = mysqli_fetch_assoc($accResult)['count'] ?? 0;
        if ($accCount > 0) {
            sendResponse(false, "Cannot delete account type '$code' because it is in use by $accCount account(s).");
        }

        // Check if referenced by other account types as parent
        $childQuery = "SELECT COUNT(*) as count FROM account_types WHERE parent_id = $id";
        $childResult = mysqli_query($conn, $childQuery);
        $childCount = mysqli_fetch_assoc($childResult)['count'] ?? 0;
        if ($childCount > 0) {
            sendResponse(false, "Cannot delete account type '$code' because it has $childCount sub-types.");
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteQuery = "DELETE FROM account_types WHERE id = $id";
            if (mysqli_query($conn, $deleteQuery)) {
                logAudit($conn, 'DELETE', 'account_types', $code, "Deleted account type: " . $oldValues['name'] . " ($code)", $oldValues);

                $_SESSION['account_type_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Account type '$code' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete account type: ' . $e->getMessage());
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
        $query = "SELECT id FROM account_types WHERE code = '$codeEsc'";
        if ($id > 0) {
            $query .= " AND id != $id";
        }
        $query .= " LIMIT 1";

        $result = mysqli_query($conn, $query);
        $exists = ($result && mysqli_num_rows($result) > 0);

        echo json_encode(['success' => true, 'exists' => $exists]);
        exit();
        break;

    case 'get_next_code':
        $parentId = isset($_GET['parent_id']) ? trim($_GET['parent_id']) : '';
        
        global $accountGroupRanges;
        
        if (!isset($accountGroupRanges[$parentId])) {
            echo json_encode(['success' => false, 'message' => 'Invalid parent group']);
            exit();
        }
        
        $range = $accountGroupRanges[$parentId];
        $rangeStart = $range['range_start'];
        $rangeEnd = $range['range_end'];
        
        $query = "SELECT code FROM account_types WHERE code >= '$rangeStart' AND code <= '$rangeEnd' ORDER BY code ASC";
        $result = mysqli_query($conn, $query);
        
        $existingCodes = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $existingCodes[] = (int)$row['code'];
            }
        }
        
        // Find next available code starting from range_start + 1
        $nextCode = $rangeStart + 1;
        while (in_array($nextCode, $existingCodes)) {
            $nextCode++;
            if ($nextCode > $rangeEnd) {
                echo json_encode(['success' => false, 'message' => 'No available codes in this range']);
                exit();
            }
        }
        
        echo json_encode(['success' => true, 'next_code' => (string)$nextCode]);
        exit();
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
