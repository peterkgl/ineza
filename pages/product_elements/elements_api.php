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

        $query = "SELECT pe.*, p.code AS product_code, p.name AS product_name 
                  FROM product_elements pe 
                  JOIN products p ON pe.product_id = p.id 
                  ORDER BY p.code ASC, pe.display_order ASC, pe.element_code ASC";
        $result = mysqli_query($conn, $query);
        $elements = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $elements[] = [
                    'id' => (int)$row['id'],
                    'product_id' => (int)$row['product_id'],
                    'element_code' => $row['element_code'],
                    'element_name' => $row['element_name'],
                    'unit' => $row['unit'],
                    'display_order' => (int)$row['display_order'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'product_elements', 'Product Elements List', 'User viewed the product elements list');
        sendResponse(true, 'Product elements retrieved successfully.', $elements);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_product_element')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create product elements.');
        }

        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $elementCode = isset($_POST['element_code']) ? trim($_POST['element_code']) : '';
        $elementName = isset($_POST['element_name']) ? trim($_POST['element_name']) : '';
        $unit = isset($_POST['unit']) ? trim($_POST['unit']) : '%';
        $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

        if ($productId <= 0 || empty($elementCode) || empty($elementName)) {
            sendResponse(false, 'Product, element code, and element name are required.');
        }

        $prodCheck = mysqli_query($conn, "SELECT name, code FROM products WHERE id = $productId LIMIT 1");
        if (!$prodCheck || mysqli_num_rows($prodCheck) === 0) {
            sendResponse(false, 'Invalid parent product selected.');
        }
        $prodRow = mysqli_fetch_assoc($prodCheck);
        $productCode = $prodRow['code'];

        $elementCodeEsc = mysqli_real_escape_string($conn, $elementCode);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_elements WHERE product_id = $productId AND element_code = '$elementCodeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'An element with this code already exists for the selected product.');
        }

        mysqli_begin_transaction($conn);

        try {
            $elementNameEsc = mysqli_real_escape_string($conn, $elementName);
            $unitEsc = mysqli_real_escape_string($conn, $unit);

            $insertElement = "INSERT INTO product_elements (product_id, element_code, element_name, unit, display_order, created_by) 
                              VALUES ($productId, '$elementCodeEsc', '$elementNameEsc', '$unitEsc', $displayOrder, $userId)";
            
            if (mysqli_query($conn, $insertElement)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'product_id' => $productId,
                    'element_code' => $elementCode,
                    'element_name' => $elementName,
                    'unit' => $unit,
                    'display_order' => $displayOrder,
                    'product_code' => $productCode,
                    'product_name' => $prodRow['name']
                ];

                logAudit($conn, 'CREATE', 'product_elements', $elementCode, "Created product element: $elementName for product $productCode", null, $newValues);
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
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $elementCode = isset($_POST['element_code']) ? trim($_POST['element_code']) : '';
        $elementName = isset($_POST['element_name']) ? trim($_POST['element_name']) : '';
        $unit = isset($_POST['unit']) ? trim($_POST['unit']) : '%';
        $displayOrder = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;

        if ($id <= 0 || $productId <= 0 || empty($elementCode) || empty($elementName)) {
            sendResponse(false, 'Valid ID, product, element code, and element name are required.');
        }

        $fetchQuery = "SELECT * FROM product_elements WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product element not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $prodCheck = mysqli_query($conn, "SELECT name, code FROM products WHERE id = $productId LIMIT 1");
        if (!$prodCheck || mysqli_num_rows($prodCheck) === 0) {
            sendResponse(false, 'Invalid parent product selected.');
        }
        $prodRow = mysqli_fetch_assoc($prodCheck);
        $productCode = $prodRow['code'];

        $elementCodeEsc = mysqli_real_escape_string($conn, $elementCode);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_elements WHERE product_id = $productId AND element_code = '$elementCodeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'An element with this code already exists for the selected product.');
        }

        mysqli_begin_transaction($conn);

        try {
            $elementNameEsc = mysqli_real_escape_string($conn, $elementName);
            $unitEsc = mysqli_real_escape_string($conn, $unit);

            $updateElement = "UPDATE product_elements SET 
                                product_id = $productId, 
                                element_code = '$elementCodeEsc', 
                                element_name = '$elementNameEsc', 
                                unit = '$unitEsc', 
                                display_order = $displayOrder, 
                                updated_by = $userId, 
                                updated_at = CURRENT_TIMESTAMP 
                              WHERE id = $id";
            
            if (mysqli_query($conn, $updateElement)) {
                $newValues = [
                    'id' => $id,
                    'product_id' => $productId,
                    'element_code' => $elementCode,
                    'element_name' => $elementName,
                    'unit' => $unit,
                    'display_order' => $displayOrder,
                    'product_code' => $productCode,
                    'product_name' => $prodRow['name']
                ];

                logAudit($conn, 'UPDATE', 'product_elements', $elementCode, "Updated product element: $elementName for product $productCode", $oldValues, $newValues);
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

        $fetchQuery = "SELECT pe.*, p.code AS product_code FROM product_elements pe JOIN products p ON pe.product_id = p.id WHERE pe.id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product element not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $elementCode = $oldValues['element_code'];
        $elementName = $oldValues['element_name'];
        $productCode = $oldValues['product_code'];

        mysqli_begin_transaction($conn);

        try {
            $deleteElement = "DELETE FROM product_elements WHERE id = $id";
            if (mysqli_query($conn, $deleteElement)) {
                logAudit($conn, 'DELETE', 'product_elements', $elementCode, "Deleted product element: $elementName for product $productCode", $oldValues);
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
