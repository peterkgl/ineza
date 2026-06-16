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

if (empty($_SESSION['permissions_token'])) {
    $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['permissions_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['permissions_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

$coreCodes = [
    'view_permissions', 'create_permission', 'edit_permission', 'delete_permission',
    'view_roles', 'create_role', 'edit_role', 'delete_role',
    'view_users', 'create_user', 'edit_user', 'delete_user',
    'view_currencies', 'create_currency', 'edit_currency', 'delete_currency',
    'view_exchange_rates', 'create_exchange_rate', 'edit_exchange_rate', 'delete_exchange_rate'
];

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_permissions')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view permissions.');
        }

        $query = "SELECT p.id, p.permition_name, p.permition_code, p.created_at, COUNT(DISTINCT rp.role_id) as role_count
                  FROM permissions p
                  LEFT JOIN role_permissions rp ON p.id = rp.permission_id
                  GROUP BY p.id
                  ORDER BY p.permition_name ASC";
        $result = mysqli_query($conn, $query);
        $permissions = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permissions[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['permition_name'],
                    'code' => $row['permition_code'],
                    'created_at' => $row['created_at'],
                    'role_count' => (int)$row['role_count'],
                    'is_core' => in_array($row['permition_code'], $coreCodes)
                ];
            }
        }

        logAudit($conn, 'VIEW', 'permissions', 'Permissions List', 'User viewed the permissions list');
        sendResponse(true, 'Permissions retrieved successfully.', $permissions);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_permission')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create permissions.');
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? strtolower(trim($_POST['code'])) : '';

        if (empty($name) || empty($code)) {
            sendResponse(false, 'Permission name and code are required.');
        }

        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            sendResponse(false, 'Permission code must contain only lowercase letters, numbers, and underscores.');
        }

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $codeEsc = mysqli_real_escape_string($conn, $code);

        $chkCode = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A permission with this code already exists.');
        }

        $chkName = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_name = '$nameEsc' LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A permission with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $insertPerm = "INSERT INTO permissions (permition_name, permition_code) VALUES ('$nameEsc', '$codeEsc')";
            if (mysqli_query($conn, $insertPerm)) {
                $newPermId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newPermId,
                    'name' => $name,
                    'code' => $code
                ];

                logAudit($conn, 'CREATE', 'permissions', $code, "Created permission: $name", null, $newValues);
                $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Permission created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create permission: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_permission')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit permissions.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $code = isset($_POST['code']) ? strtolower(trim($_POST['code'])) : '';

        if ($id <= 0 || empty($name) || empty($code)) {
            sendResponse(false, 'Valid ID, permission name, and code are required.');
        }

        if (!preg_match('/^[a-z0-9_]+$/', $code)) {
            sendResponse(false, 'Permission code must contain only lowercase letters, numbers, and underscores.');
        }

        $fetchQuery = "SELECT * FROM permissions WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Permission not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $oldCode = $oldValues['permition_code'];

        if (in_array($oldCode, $coreCodes)) {
            sendResponse(false, 'Core system permissions are immutable and cannot be updated.');
        }

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $codeEsc = mysqli_real_escape_string($conn, $code);

        $chkCode = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A permission with this code already exists.');
        }

        $chkName = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_name = '$nameEsc' AND id != $id LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A permission with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $updatePerm = "UPDATE permissions SET permition_name = '$nameEsc', permition_code = '$codeEsc' WHERE id = $id";
            if (mysqli_query($conn, $updatePerm)) {
                $newValues = [
                    'id' => $id,
                    'name' => $name,
                    'code' => $code
                ];

                logAudit($conn, 'UPDATE', 'permissions', $code, "Updated permission: $name", $oldValues, $newValues);
                $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Permission updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update permission: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_permission')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete permissions.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid permission ID.');
        }

        $fetchQuery = "SELECT * FROM permissions WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Permission not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['permition_code'];
        $name = $oldValues['permition_name'];

        if (in_array($code, $coreCodes)) {
            sendResponse(false, 'Core system permissions cannot be deleted.');
        }

        mysqli_begin_transaction($conn);

        try {
            mysqli_query($conn, "DELETE FROM role_permissions WHERE permission_id = $id");
            $deletePerm = "DELETE FROM permissions WHERE id = $id";
            if (mysqli_query($conn, $deletePerm)) {
                logAudit($conn, 'DELETE', 'permissions', $code, "Deleted permission: $name", $oldValues);
                $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Permission deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete permission: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
