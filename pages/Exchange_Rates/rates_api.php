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

if (empty($_SESSION['rates_token'])) {
    $_SESSION['rates_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['rates_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['rates_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_exchange_rates')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view exchange rates.');
        }

        $query = "SELECT er.*, fc.code as from_code, tc.code as to_code 
                  FROM exchange_rates er
                  JOIN currencies fc ON er.from_currency_id = fc.id
                  JOIN currencies tc ON er.to_currency_id = tc.id
                  ORDER BY er.rate_date DESC, er.id DESC";
        $result = mysqli_query($conn, $query);
        $rates = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rates[] = [
                    'id' => (int)$row['id'],
                    'from_currency_id' => (int)$row['from_currency_id'],
                    'to_currency_id' => (int)$row['to_currency_id'],
                    'from_code' => $row['from_code'],
                    'to_code' => $row['to_code'],
                    'rate' => $row['rate'],
                    'rate_date' => $row['rate_date'],
                    'source' => $row['source'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'exchange_rates', 'Exchange Rates List', 'User viewed the exchange rates list');

        sendResponse(true, 'Exchange rates retrieved successfully.', $rates);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_exchange_rate')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create exchange rates.');
        }

        $from_id = isset($_POST['from_currency_id']) ? (int)$_POST['from_currency_id'] : 0;
        $to_id = isset($_POST['to_currency_id']) ? (int)$_POST['to_currency_id'] : 0;
        $rate = isset($_POST['rate']) ? trim($_POST['rate']) : '';
        $rate_date = isset($_POST['rate_date']) ? trim($_POST['rate_date']) : '';
        $source = isset($_POST['source']) ? trim($_POST['source']) : '';

        if ($from_id <= 0 || $to_id <= 0 || empty($rate) || empty($rate_date)) {
            sendResponse(false, 'From currency, to currency, exchange rate, and date are required.');
        }

        if ($from_id === $to_id) {
            sendResponse(false, 'From and to currencies must be different.');
        }

        $rate_val = floatval($rate);
        if ($rate_val <= 0) {
            sendResponse(false, 'Exchange rate must be a positive number.');
        }

        $chkFrom = mysqli_query($conn, "SELECT id, code FROM currencies WHERE id = $from_id LIMIT 1");
        $chkTo = mysqli_query($conn, "SELECT id, code FROM currencies WHERE id = $to_id LIMIT 1");
        if (mysqli_num_rows($chkFrom) === 0 || mysqli_num_rows($chkTo) === 0) {
            sendResponse(false, 'Selected currency is invalid.');
        }
        $fromCode = mysqli_fetch_assoc($chkFrom)['code'];
        $toCode = mysqli_fetch_assoc($chkTo)['code'];

        mysqli_begin_transaction($conn);

        try {
            $rateEsc = mysqli_real_escape_string($conn, $rate);
            $dateEsc = mysqli_real_escape_string($conn, $rate_date);
            $sourceEsc = mysqli_real_escape_string($conn, $source);

            $insertQuery = "INSERT INTO exchange_rates (from_currency_id, to_currency_id, rate, rate_date, source, created_by) 
                            VALUES ($from_id, $to_id, $rateEsc, '$dateEsc', '$sourceEsc', $userId)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'from_currency_id' => $from_id,
                    'to_currency_id' => $to_id,
                    'from_code' => $fromCode,
                    'to_code' => $toCode,
                    'rate' => $rate,
                    'rate_date' => $rate_date,
                    'source' => $source,
                    'created_by' => $userId
                ];

                logAudit($conn, 'CREATE', 'exchange_rates', "$fromCode>$toCode", "Created rate: 1 $fromCode = $rate $toCode", null, $newValues);

                $_SESSION['rates_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, 'Exchange rate created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create exchange rate: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_exchange_rate')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit exchange rates.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $from_id = isset($_POST['from_currency_id']) ? (int)$_POST['from_currency_id'] : 0;
        $to_id = isset($_POST['to_currency_id']) ? (int)$_POST['to_currency_id'] : 0;
        $rate = isset($_POST['rate']) ? trim($_POST['rate']) : '';
        $rate_date = isset($_POST['rate_date']) ? trim($_POST['rate_date']) : '';
        $source = isset($_POST['source']) ? trim($_POST['source']) : '';

        if ($id <= 0 || $from_id <= 0 || $to_id <= 0 || empty($rate) || empty($rate_date)) {
            sendResponse(false, 'Valid ID, from currency, to currency, exchange rate, and date are required.');
        }

        if ($from_id === $to_id) {
            sendResponse(false, 'From and to currencies must be different.');
        }

        $rate_val = floatval($rate);
        if ($rate_val <= 0) {
            sendResponse(false, 'Exchange rate must be a positive number.');
        }

        $fetchQuery = "SELECT * FROM exchange_rates WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Exchange rate not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $chkFrom = mysqli_query($conn, "SELECT id, code FROM currencies WHERE id = $from_id LIMIT 1");
        $chkTo = mysqli_query($conn, "SELECT id, code FROM currencies WHERE id = $to_id LIMIT 1");
        if (mysqli_num_rows($chkFrom) === 0 || mysqli_num_rows($chkTo) === 0) {
            sendResponse(false, 'Selected currency is invalid.');
        }
        $fromCode = mysqli_fetch_assoc($chkFrom)['code'];
        $toCode = mysqli_fetch_assoc($chkTo)['code'];

        mysqli_begin_transaction($conn);

        try {
            $rateEsc = mysqli_real_escape_string($conn, $rate);
            $dateEsc = mysqli_real_escape_string($conn, $rate_date);
            $sourceEsc = mysqli_real_escape_string($conn, $source);

            $updateQuery = "UPDATE exchange_rates SET 
                                from_currency_id = $from_id, 
                                to_currency_id = $to_id, 
                                rate = '$rateEsc', 
                                rate_date = '$dateEsc', 
                                source = '$sourceEsc', 
                                updated_by = $userId, 
                                updated_at = CURRENT_TIMESTAMP 
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'from_currency_id' => $from_id,
                    'to_currency_id' => $to_id,
                    'from_code' => $fromCode,
                    'to_code' => $toCode,
                    'rate' => $rate,
                    'rate_date' => $rate_date,
                    'source' => $source,
                    'updated_by' => $userId
                ];

                logAudit($conn, 'UPDATE', 'exchange_rates', "$fromCode>$toCode", "Updated rate: 1 $fromCode = $rate $toCode", $oldValues, $newValues);

                $_SESSION['rates_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, 'Exchange rate updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update exchange rate: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_exchange_rate')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete exchange rates.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid exchange rate ID.');
        }

        $fetchQuery = "SELECT * FROM exchange_rates WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Exchange rate not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        
        $from_id = $oldValues['from_currency_id'];
        $to_id = $oldValues['to_currency_id'];
        $chkFrom = mysqli_query($conn, "SELECT code FROM currencies WHERE id = $from_id LIMIT 1");
        $chkTo = mysqli_query($conn, "SELECT code FROM currencies WHERE id = $to_id LIMIT 1");
        $fromCode = mysqli_fetch_assoc($chkFrom)['code'] ?? 'UNK';
        $toCode = mysqli_fetch_assoc($chkTo)['code'] ?? 'UNK';

        mysqli_begin_transaction($conn);

        try {
            $deleteQuery = "DELETE FROM exchange_rates WHERE id = $id";
            if (mysqli_query($conn, $deleteQuery)) {
                logAudit($conn, 'DELETE', 'exchange_rates', "$fromCode>$toCode", "Deleted rate: 1 $fromCode = " . $oldValues['rate'] . " $toCode", $oldValues);

                $_SESSION['rates_token'] = bin2hex(random_bytes(32));

                mysqli_commit($conn);
                sendResponse(true, 'Exchange rate deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete exchange rate: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
