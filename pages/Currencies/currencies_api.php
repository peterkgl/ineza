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

if (empty($_SESSION['currency_token'])) {
    $_SESSION['currency_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['currency_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['currency_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_currencies')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view currencies.');
        }

        $query = "SELECT * FROM currencies ORDER BY is_base_currency DESC, code ASC";
        $result = mysqli_query($conn, $query);
        $currencies = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $currencies[] = [
                    'id' => (int)$row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'symbol' => $row['symbol'],
                    'is_base_currency' => (int)$row['is_base_currency'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'currencies', 'Currencies List', 'User viewed the currencies list');

        sendResponse(true, 'Currencies retrieved successfully.', $currencies);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_currency')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create a currency.');
        }

        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
        $is_base_currency = isset($_POST['is_base_currency']) && $_POST['is_base_currency'] === '1' ? 1 : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Currency code and name are required.');
        }

        if (strlen($code) > 5) {
            sendResponse(false, 'Currency code cannot exceed 5 characters.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $checkQuery = "SELECT id FROM currencies WHERE code = '$codeEsc' LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "A currency with code '$code' already exists.");
        }

        mysqli_begin_transaction($conn);

        try {
            if ($is_base_currency === 1) {
                $resetQuery = "UPDATE currencies SET is_base_currency = 0";
                mysqli_query($conn, $resetQuery);
            }

            $nameEsc = mysqli_real_escape_string($conn, $name);
            $symbolEsc = mysqli_real_escape_string($conn, $symbol);

            $insertQuery = "INSERT INTO currencies (code, name, symbol, is_base_currency, is_active, created_by) 
                            VALUES ('$codeEsc', '$nameEsc', '$symbolEsc', $is_base_currency, $is_active, $userId)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                
                $newValues = [
                    'id' => $newId,
                    'code' => $code,
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_base_currency' => $is_base_currency,
                    'is_active' => $is_active,
                    'created_by' => $userId
                ];

                logAudit($conn, 'CREATE', 'currencies', $code, "Created new currency: $name ($code)", null, $newValues);

                $_SESSION['currency_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Currency '$code' created successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create currency: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_currency')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit currencies.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
        $is_base_currency = isset($_POST['is_base_currency']) && $_POST['is_base_currency'] === '1' ? 1 : 0;
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, currency code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM currencies WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Currency not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $checkQuery = "SELECT id FROM currencies WHERE code = '$codeEsc' AND id != $id LIMIT 1";
        $checkResult = mysqli_query($conn, $checkQuery);
        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendResponse(false, "Another currency with code '$code' already exists.");
        }

        mysqli_begin_transaction($conn);

        try {
            if ($is_base_currency === 1) {
                $resetQuery = "UPDATE currencies SET is_base_currency = 0";
                mysqli_query($conn, $resetQuery);
            } else {
                if ((int)$oldValues['is_base_currency'] === 1) {
                    $countQuery = "SELECT COUNT(*) as count FROM currencies WHERE is_base_currency = 1 AND id != $id";
                    $countResult = mysqli_query($conn, $countQuery);
                    $countRow = mysqli_fetch_assoc($countResult);
                    if ($countRow['count'] == 0) {
                        sendResponse(false, 'System must have at least one base currency. You cannot unset the base status of this currency without setting another.');
                    }
                }
            }

            $nameEsc = mysqli_real_escape_string($conn, $name);
            $symbolEsc = mysqli_real_escape_string($conn, $symbol);

            $updateQuery = "UPDATE currencies SET 
                                code = '$codeEsc', 
                                name = '$nameEsc', 
                                symbol = '$symbolEsc', 
                                is_base_currency = $is_base_currency, 
                                is_active = $is_active, 
                                updated_by = $userId, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'code' => $code,
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_base_currency' => $is_base_currency,
                    'is_active' => $is_active,
                    'updated_by' => $userId
                ];

                logAudit($conn, 'UPDATE', 'currencies', $code, "Updated currency: $name ($code)", $oldValues, $newValues);

                $_SESSION['currency_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Currency '$code' updated successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update currency: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_currency')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete currencies.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid currency ID.');
        }

        $fetchQuery = "SELECT * FROM currencies WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Currency not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['code'];

        if ((int)$oldValues['is_base_currency'] === 1) {
            sendResponse(false, 'Cannot delete the base reporting currency.');
        }

        $erQuery = "SELECT COUNT(*) as count FROM exchange_rates WHERE from_currency_id = $id OR to_currency_id = $id";
        $erResult = mysqli_query($conn, $erQuery);
        $erCount = mysqli_fetch_assoc($erResult)['count'] ?? 0;
        if ($erCount > 0) {
            sendResponse(false, "Cannot delete currency '$code' because it is referenced in exchange rates.");
        }

        $saQuery = "SELECT COUNT(*) as count FROM supplier_advances WHERE currency_id = $id";
        $saResult = mysqli_query($conn, $saQuery);
        $saCount = mysqli_fetch_assoc($saResult)['count'] ?? 0;
        if ($saCount > 0) {
            sendResponse(false, "Cannot delete currency '$code' because it is referenced in supplier advances.");
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteQuery = "DELETE FROM currencies WHERE id = $id";
            if (mysqli_query($conn, $deleteQuery)) {
                logAudit($conn, 'DELETE', 'currencies', $code, "Deleted currency: " . $oldValues['name'] . " ($code)", $oldValues);

                $_SESSION['currency_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, "Currency '$code' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete currency: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
