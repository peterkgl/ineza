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

if (empty($_SESSION['lots_token'])) {
    $_SESSION['lots_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['lots_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['lots_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_lots')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view lots.');
        }

        $query = "SELECT l.* FROM lots l ORDER BY l.opening_date DESC";
        $result = mysqli_query($conn, $query);
        $lots = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lots[] = [
                    'id' => (int)$row['id'],
                    'lots_code' => $row['lots_code'],
                    'opening_date' => $row['opening_date'],
                    'closing_date' => $row['closing_date'],
                    'status' => $row['closing_date'] ? 'CLOSED' : 'OPEN'
                ];
            }
        }

        logAudit($conn, 'VIEW', 'lots', 'Lots List', 'User viewed the lots list');
        sendResponse(true, 'Lots retrieved successfully.', $lots);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create lots.');
        }

        $lots_code = isset($_POST['lots_code']) ? trim($_POST['lots_code']) : '';
        $opening_date = isset($_POST['opening_date']) ? trim($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? trim($_POST['closing_date']) : null;

        if (empty($lots_code) || empty($opening_date)) {
            sendResponse(false, 'Lot code and opening date are required.');
        }

        // Check if lot code already exists
        $codeEsc = mysqli_real_escape_string($conn, $lots_code);
        $chkCode = mysqli_query($conn, "SELECT id FROM lots WHERE lots_code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A lot with this code already exists.');
        }

        mysqli_begin_transaction($conn);
        try {
            $closingVal = $closing_date ? "'" . mysqli_real_escape_string($conn, $closing_date) . "'" : 'NULL';
            $insertQuery = "INSERT INTO lots (lots_code, opening_date, closing_date) VALUES ('$codeEsc', '" . mysqli_real_escape_string($conn, $opening_date) . "', $closingVal)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'lots_code' => $lots_code,
                    'opening_date' => $opening_date,
                    'closing_date' => $closing_date
                ];
                logAudit($conn, 'CREATE', 'lots', $lots_code, "Created lot: $lots_code", null, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '$lots_code' created successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create lot: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit lots.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $lots_code = isset($_POST['lots_code']) ? trim($_POST['lots_code']) : '';
        $opening_date = isset($_POST['opening_date']) ? trim($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? trim($_POST['closing_date']) : null;

        if ($id <= 0 || empty($lots_code) || empty($opening_date)) {
            sendResponse(false, 'Valid ID, lot code, and opening date are required.');
        }

        // Fetch current lot values
        $fetchQuery = "SELECT * FROM lots WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        // Check if lot is already closed
        if ($oldValues['closing_date']) {
            sendResponse(false, 'Closed lots cannot be edited.');
        }

        // Check if lot code is unique (excluding current lot)
        $codeEsc = mysqli_real_escape_string($conn, $lots_code);
        $chkCode = mysqli_query($conn, "SELECT id FROM lots WHERE lots_code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A lot with this code already exists.');
        }

        mysqli_begin_transaction($conn);
        try {
            $closingVal = $closing_date ? "'" . mysqli_real_escape_string($conn, $closing_date) . "'" : 'NULL';
            $updateQuery = "UPDATE lots SET 
                                lots_code = '$codeEsc',
                                opening_date = '" . mysqli_real_escape_string($conn, $opening_date) . "',
                                closing_date = $closingVal
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'lots_code' => $lots_code,
                    'opening_date' => $opening_date,
                    'closing_date' => $closing_date
                ];
                logAudit($conn, 'UPDATE', 'lots', $oldValues['lots_code'], "Updated lot: " . $oldValues['lots_code'], $oldValues, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '" . $oldValues['lots_code'] . "' updated successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update lot: ' . $e->getMessage());
        }
        break;

    case 'close':
        if (!hasPermission($conn, $userId, 'edit_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to close lots.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkLot = mysqli_query($conn, "SELECT * FROM lots WHERE id = $id LIMIT 1");
        if (!$chkLot || mysqli_num_rows($chkLot) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $oldValues = mysqli_fetch_assoc($chkLot);

        // Check if lot is already closed
        if ($oldValues['closing_date']) {
            sendResponse(false, 'Lot is already closed.');
        }

        mysqli_begin_transaction($conn);
        try {
            $today = date('Y-m-d');
            $update = mysqli_query($conn, "UPDATE lots SET closing_date = '$today' WHERE id = $id");
            if ($update) {
                $newValues = $oldValues;
                $newValues['closing_date'] = $today;
                logAudit($conn, 'UPDATE', 'lots', $oldValues['lots_code'], "Closed lot: " . $oldValues['lots_code'], $oldValues, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '" . $oldValues['lots_code'] . "' has been closed.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to close lot: ' . $e->getMessage());
        }
        break;

    case 'open':
        // This action is disabled - closed lots cannot be reopened
        http_response_code(403);
        sendResponse(false, 'Closed lots cannot be reopened.');
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete lots.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkLot = mysqli_query($conn, "SELECT * FROM lots WHERE id = $id LIMIT 1");
        if (!$chkLot || mysqli_num_rows($chkLot) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $oldValues = mysqli_fetch_assoc($chkLot);
        $lotCode = $oldValues['lots_code'];

        // Check dependencies
        $dependencies = [];
        $chkPurch = mysqli_query($conn, "SELECT COUNT(*) as count FROM purchasing WHERE lot_id = $id");
        if ($chkPurch) {
            $count = (int)mysqli_fetch_assoc($chkPurch)['count'];
            if ($count > 0) $dependencies[] = "$count purchase(s)";
        }

        if (!empty($dependencies)) {
            sendResponse(false, 'This lot cannot be deleted because it is referenced in: ' . implode(', ', $dependencies) . '.');
        }

        mysqli_begin_transaction($conn);
        try {
            $delete = mysqli_query($conn, "DELETE FROM lots WHERE id = $id");
            if ($delete) {
                logAudit($conn, 'DELETE', 'lots', $lotCode, "Deleted lot: $lotCode", $oldValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '$lotCode' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete lot: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
