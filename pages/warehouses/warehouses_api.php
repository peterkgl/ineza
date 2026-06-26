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

if (empty($_SESSION['warehouses_token'])) {
    $_SESSION['warehouses_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['warehouses_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['warehouses_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_warehouses')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view warehouses.');
        }

        $query = "SELECT * FROM warehouses ORDER BY warehouse_code ASC";
        $result = mysqli_query($conn, $query);
        $warehouses = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $warehouses[] = [
                    'id' => (int)$row['id'],
                    'warehouse_code' => $row['warehouse_code'],
                    'warehouse_name' => $row['warehouse_name'],
                    'address' => $row['address'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'warehouses', 'Warehouses List', 'User viewed the warehouses list');
        sendResponse(true, 'Warehouses retrieved successfully.', $warehouses);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_warehouse')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create warehouses.');
        }

        $code = isset($_POST['warehouse_code']) ? strtoupper(trim($_POST['warehouse_code'])) : '';
        $name = isset($_POST['warehouse_name']) ? trim($_POST['warehouse_name']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Warehouse code and name are required.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM warehouses WHERE warehouse_code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A warehouse with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $addressEsc = mysqli_real_escape_string($conn, $address);

            $insertWarehouse = "INSERT INTO warehouses (warehouse_code, warehouse_name, address, is_active, created_by) 
                                VALUES ('$codeEsc', '$nameEsc', '$addressEsc', $is_active, $userId)";
            
            if (mysqli_query($conn, $insertWarehouse)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'warehouse_code' => $code,
                    'warehouse_name' => $name,
                    'address' => $address,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'CREATE', 'warehouses', $code, "Created warehouse: $name", null, $newValues);
                $_SESSION['warehouses_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Warehouse created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create warehouse: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_warehouse')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit warehouses.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['warehouse_code']) ? strtoupper(trim($_POST['warehouse_code'])) : '';
        $name = isset($_POST['warehouse_name']) ? trim($_POST['warehouse_name']) : '';
        $address = isset($_POST['address']) ? trim($_POST['address']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, warehouse code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM warehouses WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Warehouse not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM warehouses WHERE warehouse_code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A warehouse with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $addressEsc = mysqli_real_escape_string($conn, $address);

            $updateWarehouse = "UPDATE warehouses SET 
                                  warehouse_code = '$codeEsc', 
                                  warehouse_name = '$nameEsc', 
                                  address = '$addressEsc', 
                                  is_active = $is_active
                                WHERE id = $id";
            
            if (mysqli_query($conn, $updateWarehouse)) {
                $newValues = [
                    'id' => $id,
                    'warehouse_code' => $code,
                    'warehouse_name' => $name,
                    'address' => $address,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'UPDATE', 'warehouses', $code, "Updated warehouse: $name", $oldValues, $newValues);
                $_SESSION['warehouses_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Warehouse updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update warehouse: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_warehouse')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete warehouses.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid warehouse ID.');
        }

        $fetchQuery = "SELECT * FROM warehouses WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Warehouse not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['warehouse_code'];
        $name = $oldValues['warehouse_name'];

        // Enforce transactional safety with references checks
        $actualTables = [
            'stock_movement' => 'stock movements',
            'purchasing' => 'purchase records',
        ];
        $isReferenced = false;
        $referencingTable = '';

        foreach ($actualTables as $tbl => $label) {
            $chk = mysqli_query($conn, "SELECT COUNT(*) as count FROM `$tbl` WHERE warehouse_id = $id");
            if ($chk) {
                $count = (int)mysqli_fetch_assoc($chk)['count'];
                if ($count > 0) {
                    $isReferenced = true;
                    $referencingTable = $label;
                    break;
                }
            }
        }
        
        // Also check stock table
        if (!$isReferenced) {
            $chkStock = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock WHERE warehouse_id = $id AND qty_on_hand > 0");
            if ($chkStock) {
                $count = (int)mysqli_fetch_assoc($chkStock)['count'];
                if ($count > 0) {
                    $isReferenced = true;
                    $referencingTable = 'active stock inventory';
                }
            }
        }

        if ($isReferenced) {
            sendResponse(false, "This warehouse cannot be deleted because it is currently referenced in transactions (table: $referencingTable).");
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteWarehouse = "DELETE FROM warehouses WHERE id = $id";
            if (mysqli_query($conn, $deleteWarehouse)) {
                logAudit($conn, 'DELETE', 'warehouses', $code, "Deleted warehouse: $name", $oldValues);
                $_SESSION['warehouses_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Warehouse deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete warehouse: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
