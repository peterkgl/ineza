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

if (empty($_SESSION['elements_token'])) {
    $_SESSION['elements_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['elements_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['elements_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_product_elements')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view product elements.');
        }

        $query = "SELECT pe.* 
                  FROM product_element pe 
                  ORDER BY pe.element_code ASC";
        $result = mysqli_query($conn, $query);
        $elements = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $elements[] = [
                    'id' => (int)$row['id'],
                    'element_code' => $row['element_code'],
                    'element_name' => $row['element_name'],
                    'symbol' => $row['symbol'],
                    'description' => $row['description'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'product_element', 'Product Elements List', 'User viewed the product elements list');
        sendResponse(true, 'Product elements retrieved successfully.', $elements);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_product_element')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create product elements.');
        }

        $elementCode = isset($_POST['element_code']) ? trim($_POST['element_code']) : '';
        $elementName = isset($_POST['element_name']) ? trim($_POST['element_name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if (empty($elementCode) || empty($elementName)) {
            sendResponse(false, 'Element code and element name are required.');
        }

        $elementCodeEsc = mysqli_real_escape_string($conn, $elementCode);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_element WHERE element_code = '$elementCodeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'An element with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $elementNameEsc = mysqli_real_escape_string($conn, $elementName);
            $symbolEsc = mysqli_real_escape_string($conn, $symbol);
            $descriptionEsc = mysqli_real_escape_string($conn, $description);

            $insertElement = "INSERT INTO product_element (element_code, element_name, symbol, description, is_active) 
                              VALUES ('$elementCodeEsc', '$elementNameEsc', '$symbolEsc', '$descriptionEsc', $isActive)";
            
            if (mysqli_query($conn, $insertElement)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'element_code' => $elementCode,
                    'element_name' => $elementName,
                    'symbol' => $symbol,
                    'description' => $description,
                    'is_active' => $isActive
                ];

                logAudit($conn, 'CREATE', 'product_element', $elementCode, "Created product element: $elementName", null, $newValues);
                $_SESSION['elements_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product element created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create product element: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_product_element')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit product elements.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $elementCode = isset($_POST['element_code']) ? trim($_POST['element_code']) : '';
        $elementName = isset($_POST['element_name']) ? trim($_POST['element_name']) : '';
        $symbol = isset($_POST['symbol']) ? trim($_POST['symbol']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

        if ($id <= 0 || empty($elementCode) || empty($elementName)) {
            sendResponse(false, 'Valid ID, element code, and element name are required.');
        }

        $fetchQuery = "SELECT * FROM product_element WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product element not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $elementCodeEsc = mysqli_real_escape_string($conn, $elementCode);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_element WHERE element_code = '$elementCodeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'An element with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $elementNameEsc = mysqli_real_escape_string($conn, $elementName);
            $symbolEsc = mysqli_real_escape_string($conn, $symbol);
            $descriptionEsc = mysqli_real_escape_string($conn, $description);

            $updateElement = "UPDATE product_element SET 
                                element_code = '$elementCodeEsc', 
                                element_name = '$elementNameEsc', 
                                symbol = '$symbolEsc', 
                                description = '$descriptionEsc', 
                                is_active = $isActive, 
                                updated_at = CURRENT_TIMESTAMP 
                              WHERE id = $id";
            
            if (mysqli_query($conn, $updateElement)) {
                $newValues = [
                    'id' => $id,
                    'element_code' => $elementCode,
                    'element_name' => $elementName,
                    'symbol' => $symbol,
                    'description' => $description,
                    'is_active' => $isActive
                ];

                logAudit($conn, 'UPDATE', 'product_element', $elementCode, "Updated product element: $elementName", $oldValues, $newValues);
                $_SESSION['elements_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product element updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update product element: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_product_element')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete product elements.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid product element ID.');
        }

        $fetchQuery = "SELECT * FROM product_element WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product element not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $elementCode = $oldValues['element_code'];
        $elementName = $oldValues['element_name'];

        mysqli_begin_transaction($conn);

        try {
            $deleteElement = "DELETE FROM product_element WHERE id = $id";
            if (mysqli_query($conn, $deleteElement)) {
                logAudit($conn, 'DELETE', 'product_element', $elementCode, "Deleted product element: $elementName", $oldValues);
                $_SESSION['elements_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product element deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete product element: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
