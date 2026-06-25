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

if (empty($_SESSION['unit_of_measure_token'])) {
    $_SESSION['unit_of_measure_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['unit_of_measure_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['unit_of_measure_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_unit_of_measure')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view units of measure.');
        }

        $query = "SELECT u.* FROM unit_of_measure u ORDER BY u.code ASC";
        $result = mysqli_query($conn, $query);
        $units = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $units[] = [
                    'id' => (int)$row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'symbol' => $row['symbol'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'unit_of_measure', 'Units of Measure List', 'User viewed the units of measure list');
        sendResponse(true, 'Units of measure retrieved successfully.', $units);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_unit_of_measure')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create units of measure.');
        }

        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Code and name are required.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM unit_of_measure WHERE code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A unit of measure with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $symbolEsc = $symbol !== null ? "'" . mysqli_real_escape_string($conn, $symbol) . "'" : 'NULL';

            $insertUnit = "INSERT INTO unit_of_measure (code, name, symbol, is_active) 
                          VALUES ('$codeEsc', '$nameEsc', $symbolEsc, $is_active)";
            
            if (mysqli_query($conn, $insertUnit)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'code' => $code,
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'CREATE', 'unit_of_measure', $code, "Created unit of measure: $name", null, $newValues);
                $_SESSION['unit_of_measure_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Unit of measure created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create unit of measure: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_unit_of_measure')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit units of measure.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : null;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM unit_of_measure WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Unit of measure not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM unit_of_measure WHERE code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A unit of measure with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $symbolEsc = $symbol !== null ? "'" . mysqli_real_escape_string($conn, $symbol) . "'" : 'NULL';

            $updateUnit = "UPDATE unit_of_measure SET 
                          code = '$codeEsc', 
                          name = '$nameEsc', 
                          symbol = $symbolEsc,
                          is_active = $is_active, 
                          updated_at = CURRENT_TIMESTAMP 
                          WHERE id = $id";
            
            if (mysqli_query($conn, $updateUnit)) {
                $newValues = [
                    'id' => $id,
                    'code' => $code,
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'UPDATE', 'unit_of_measure', $code, "Updated unit of measure: $name", $oldValues, $newValues);
                $_SESSION['unit_of_measure_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Unit of measure updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update unit of measure: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_unit_of_measure')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete units of measure.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid unit of measure ID.');
        }

        $fetchQuery = "SELECT * FROM unit_of_measure WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Unit of measure not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['code'];
        $name = $oldValues['name'];

        $chkProducts = mysqli_query($conn, "SELECT COUNT(*) as count FROM product WHERE uom_id = $id");
        $productsCount = 0;
        if ($chkProducts) {
            $productsCount = (int)mysqli_fetch_assoc($chkProducts)['count'];
        }

        if ($productsCount > 0) {
            sendResponse(false, 'This unit of measure cannot be deleted because it is used by ' . $productsCount . ' product(s).');
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteUnit = "DELETE FROM unit_of_measure WHERE id = $id";
            if (mysqli_query($conn, $deleteUnit)) {
                logAudit($conn, 'DELETE', 'unit_of_measure', $code, "Deleted unit of measure: $name", $oldValues);
                $_SESSION['unit_of_measure_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Unit of measure deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete unit of measure: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>