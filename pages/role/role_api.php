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

if (empty($_SESSION['roles_token'])) {
    $_SESSION['roles_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['roles_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['roles_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_roles')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view roles.');
        }

        $query = "SELECT r.id, r.name, r.description, r.created_at, GROUP_CONCAT(DISTINCT rp.permission_id) as permission_ids, COUNT(DISTINCT ur.user_id) as user_count
                  FROM roles r
                  LEFT JOIN role_permissions rp ON r.id = rp.role_id
                  LEFT JOIN user_roles ur ON r.id = ur.role_id
                  GROUP BY r.id
                  ORDER BY r.name ASC";
        $result = mysqli_query($conn, $query);
        $roles = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $permIds = [];
                if ($row['permission_ids'] !== null && $row['permission_ids'] !== '') {
                    $permIds = array_map('intval', explode(',', $row['permission_ids']));
                }
                $roles[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'permission_ids' => $permIds,
                    'user_count' => (int)$row['user_count']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'roles', 'Roles List', 'User viewed the roles list');
        sendResponse(true, 'Roles retrieved successfully.', $roles);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_role')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create roles.');
        }

        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $permsInput = isset($_POST['permissions']) ? $_POST['permissions'] : '';

        if (empty($name)) {
            sendResponse(false, 'Role name is required.');
        }

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $chkName = mysqli_query($conn, "SELECT id FROM roles WHERE name = '$nameEsc' LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A role with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $descEsc = mysqli_real_escape_string($conn, $description);
            $insertRole = "INSERT INTO roles (name, description) VALUES ('$nameEsc', '$descEsc')";
            
            if (mysqli_query($conn, $insertRole)) {
                $newRoleId = mysqli_insert_id($conn);
                
                $selectedPerms = [];
                if (!empty($permsInput)) {
                    $permsArr = explode(',', $permsInput);
                    foreach ($permsArr as $pId) {
                        $pId = (int)$pId;
                        if ($pId > 0) {
                            $insertRP = "INSERT INTO role_permissions (role_id, permission_id) VALUES ($newRoleId, $pId)";
                            mysqli_query($conn, $insertRP);
                            $selectedPerms[] = $pId;
                        }
                    }
                }

                $newValues = [
                    'id' => $newRoleId,
                    'name' => $name,
                    'description' => $description,
                    'permissions' => $selectedPerms
                ];

                logAudit($conn, 'CREATE', 'roles', $name, "Created role: $name", null, $newValues);
                $_SESSION['roles_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Role created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create role: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_role')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit roles.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $permsInput = isset($_POST['permissions']) ? $_POST['permissions'] : '';

        if ($id <= 0 || empty($name)) {
            sendResponse(false, 'Valid ID and role name are required.');
        }

        $fetchQuery = "SELECT r.*, GROUP_CONCAT(rp.permission_id) as permission_ids
                       FROM roles r
                       LEFT JOIN role_permissions rp ON r.id = rp.role_id
                       WHERE r.id = $id
                       GROUP BY r.id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Role not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $oldName = $oldValues['name'];

        if ($oldName === 'admin' && strtolower($name) !== 'admin') {
            sendResponse(false, 'The name of the administrative role cannot be changed.');
        }

        $nameEsc = mysqli_real_escape_string($conn, $name);
        $chkName = mysqli_query($conn, "SELECT id FROM roles WHERE name = '$nameEsc' AND id != $id LIMIT 1");
        if ($chkName && mysqli_num_rows($chkName) > 0) {
            sendResponse(false, 'A role with this name already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $descEsc = mysqli_real_escape_string($conn, $description);
            $updateRole = "UPDATE roles SET name = '$nameEsc', description = '$descEsc' WHERE id = $id";
            
            if (mysqli_query($conn, $updateRole)) {
                mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $id");
                
                $selectedPerms = [];
                if (!empty($permsInput)) {
                    $permsArr = explode(',', $permsInput);
                    foreach ($permsArr as $pId) {
                        $pId = (int)$pId;
                        if ($pId > 0) {
                            $insertRP = "INSERT INTO role_permissions (role_id, permission_id) VALUES ($id, $pId)";
                            mysqli_query($conn, $insertRP);
                            $selectedPerms[] = $pId;
                        }
                    }
                }

                $newValues = [
                    'id' => $id,
                    'name' => $name,
                    'description' => $description,
                    'permissions' => $selectedPerms
                ];

                logAudit($conn, 'UPDATE', 'roles', $name, "Updated role: $name", $oldValues, $newValues);
                $_SESSION['roles_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Role updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update role: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_role')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete roles.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid role ID.');
        }

        $fetchQuery = "SELECT * FROM roles WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Role not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $roleName = $oldValues['name'];

        if ($roleName === 'admin') {
            sendResponse(false, 'The system administrative role "admin" cannot be deleted.');
        }

        $chkUsers = mysqli_query($conn, "SELECT COUNT(*) as count FROM user_roles WHERE role_id = $id");
        $usersCount = 0;
        if ($chkUsers) {
            $usersCount = (int)mysqli_fetch_assoc($chkUsers)['count'];
        }

        if ($usersCount > 0) {
            sendResponse(false, 'This role cannot be deleted because it is currently assigned to ' . $usersCount . ' user(s).');
        }

        mysqli_begin_transaction($conn);

        try {
            mysqli_query($conn, "DELETE FROM role_permissions WHERE role_id = $id");
            $deleteRole = "DELETE FROM roles WHERE id = $id";
            if (mysqli_query($conn, $deleteRole)) {
                logAudit($conn, 'DELETE', 'roles', $roleName, "Deleted role: $roleName", $oldValues);
                $_SESSION['roles_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Role deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete role: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
