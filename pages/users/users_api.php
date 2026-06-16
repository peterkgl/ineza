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

if (empty($_SESSION['users_token'])) {
    $_SESSION['users_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['users_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['users_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_users')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view users.');
        }

        $query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.is_active, u.created_at, r.id as role_id, r.name as role_name
                  FROM users u
                  LEFT JOIN user_roles ur ON u.id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.id
                  ORDER BY u.id DESC";
        $result = mysqli_query($conn, $query);
        $users = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $users[] = [
                    'id' => (int)$row['id'],
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'email' => $row['email'],
                    'phone_number' => $row['phone_number'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'role_id' => $row['role_id'] ? (int)$row['role_id'] : null,
                    'role_name' => $row['role_name'] ? $row['role_name'] : 'No Role'
                ];
            }
        }

        logAudit($conn, 'VIEW', 'users', 'Users List', 'User viewed the users list');
        sendResponse(true, 'Users retrieved successfully.', $users);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_user')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create users.');
        }

        $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || $role_id <= 0) {
            sendResponse(false, 'First name, last name, email, password, and role are required.');
        }

        $emailEsc = mysqli_real_escape_string($conn, $email);
        $chkEmail = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
        if ($chkEmail && mysqli_num_rows($chkEmail) > 0) {
            sendResponse(false, 'A user with this email address already exists.');
        }

        $chkRole = mysqli_query($conn, "SELECT id, name FROM roles WHERE id = $role_id LIMIT 1");
        if (!$chkRole || mysqli_num_rows($chkRole) === 0) {
            sendResponse(false, 'Selected role is invalid.');
        }
        $roleName = mysqli_fetch_assoc($chkRole)['name'];

        mysqli_begin_transaction($conn);

        try {
            $firstEsc = mysqli_real_escape_string($conn, $first_name);
            $lastEsc = mysqli_real_escape_string($conn, $last_name);
            $phoneEsc = mysqli_real_escape_string($conn, $phone_number);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $hashEsc = mysqli_real_escape_string($conn, $hash);

            $insertUser = "INSERT INTO users (first_name, last_name, email, phone_number, password_hash, is_active) 
                           VALUES ('$firstEsc', '$lastEsc', '$emailEsc', '$phoneEsc', '$hashEsc', $is_active)";
            
            if (mysqli_query($conn, $insertUser)) {
                $newUserId = mysqli_insert_id($conn);
                
                $insertRole = "INSERT INTO user_roles (user_id, role_id) VALUES ($newUserId, $role_id)";
                if (mysqli_query($conn, $insertRole)) {
                    $newValues = [
                        'id' => $newUserId,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone_number' => $phone_number,
                        'role_id' => $role_id,
                        'role_name' => $roleName,
                        'is_active' => $is_active
                    ];

                    logAudit($conn, 'CREATE', 'users', "$first_name $last_name", "Created user: $email with role $roleName", null, $newValues);
                    $_SESSION['users_token'] = bin2hex(random_bytes(32));
                    mysqli_commit($conn);
                    sendResponse(true, 'User created successfully.', $newValues);
                } else {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create user: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_user')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit users.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($first_name) || empty($last_name) || empty($email) || $role_id <= 0) {
            sendResponse(false, 'Valid ID, first name, last name, email, and role are required.');
        }

        $fetchQuery = "SELECT u.*, ur.role_id 
                       FROM users u 
                       LEFT JOIN user_roles ur ON u.id = ur.user_id 
                       WHERE u.id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'User not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $emailEsc = mysqli_real_escape_string($conn, $email);
        $chkEmail = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc' AND id != $id LIMIT 1");
        if ($chkEmail && mysqli_num_rows($chkEmail) > 0) {
            sendResponse(false, 'A user with this email address already exists.');
        }

        $chkRole = mysqli_query($conn, "SELECT id, name FROM roles WHERE id = $role_id LIMIT 1");
        if (!$chkRole || mysqli_num_rows($chkRole) === 0) {
            sendResponse(false, 'Selected role is invalid.');
        }
        $roleName = mysqli_fetch_assoc($chkRole)['name'];

        mysqli_begin_transaction($conn);

        try {
            $firstEsc = mysqli_real_escape_string($conn, $first_name);
            $lastEsc = mysqli_real_escape_string($conn, $last_name);
            $phoneEsc = mysqli_real_escape_string($conn, $phone_number);

            $pwdUpdatePart = "";
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $hashEsc = mysqli_real_escape_string($conn, $hash);
                $pwdUpdatePart = ", password_hash = '$hashEsc'";
            }

            $updateUser = "UPDATE users SET 
                            first_name = '$firstEsc', 
                            last_name = '$lastEsc', 
                            email = '$emailEsc', 
                            phone_number = '$phoneEsc', 
                            is_active = $is_active 
                            $pwdUpdatePart 
                           WHERE id = $id";
            
            if (mysqli_query($conn, $updateUser)) {
                mysqli_query($conn, "DELETE FROM user_roles WHERE user_id = $id");
                
                $insertRole = "INSERT INTO user_roles (user_id, role_id) VALUES ($id, $role_id)";
                if (mysqli_query($conn, $insertRole)) {
                    $newValues = [
                        'id' => $id,
                        'first_name' => $first_name,
                        'last_name' => $last_name,
                        'email' => $email,
                        'phone_number' => $phone_number,
                        'role_id' => $role_id,
                        'role_name' => $roleName,
                        'is_active' => $is_active
                    ];

                    logAudit($conn, 'UPDATE', 'users', "$first_name $last_name", "Updated user: $email with role $roleName", $oldValues, $newValues);
                    $_SESSION['users_token'] = bin2hex(random_bytes(32));
                    mysqli_commit($conn);
                    sendResponse(true, 'User updated successfully.', $newValues);
                } else {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update user: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_user')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete users.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid user ID.');
        }

        if ($id === $userId) {
            sendResponse(false, 'You cannot delete your own account.');
        }

        $fetchQuery = "SELECT u.*, ur.role_id 
                       FROM users u 
                       LEFT JOIN user_roles ur ON u.id = ur.user_id 
                       WHERE u.id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'User not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        mysqli_begin_transaction($conn);

        try {
            mysqli_query($conn, "DELETE FROM user_roles WHERE user_id = $id");
            $deleteUser = "DELETE FROM users WHERE id = $id";
            if (mysqli_query($conn, $deleteUser)) {
                logAudit($conn, 'DELETE', 'users', $oldValues['first_name'] . ' ' . $oldValues['last_name'], "Deleted user: " . $oldValues['email'], $oldValues);
                $_SESSION['users_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'User deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete user: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
