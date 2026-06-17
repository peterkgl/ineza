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

if (empty($_SESSION['products_token'])) {
    $_SESSION['products_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['products_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['products_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_products')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view products.');
        }

        $query = "SELECT * FROM products ORDER BY code ASC";
        $result = mysqli_query($conn, $query);
        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = [
                    'id' => (int)$row['id'],
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'full_name' => $row['full_name'],
                    'unit_of_measure' => $row['unit_of_measure'],
                    'description' => $row['description'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'products', 'Products List', 'User viewed the products list');
        sendResponse(true, 'Products retrieved successfully.', $products);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_product')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create products.');
        }

        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $unit = isset($_POST['unit_of_measure']) ? trim($_POST['unit_of_measure']) : 'kg';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Product code and name are required.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM products WHERE code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A product with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $fullNameEsc = mysqli_real_escape_string($conn, $full_name);
            $unitEsc = mysqli_real_escape_string($conn, $unit);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $insertProduct = "INSERT INTO products (code, name, full_name, unit_of_measure, description, is_active, created_by) 
                              VALUES ('$codeEsc', '$nameEsc', '$fullNameEsc', '$unitEsc', '$descEsc', $is_active, $userId)";
            
            if (mysqli_query($conn, $insertProduct)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'code' => $code,
                    'name' => $name,
                    'full_name' => $full_name,
                    'unit_of_measure' => $unit,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'CREATE', 'products', $code, "Created product: $name", null, $newValues);
                $_SESSION['products_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create product: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_product')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit products.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['code']) ? strtoupper(trim($_POST['code'])) : '';
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
        $unit = isset($_POST['unit_of_measure']) ? trim($_POST['unit_of_measure']) : 'kg';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, product code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM products WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM products WHERE code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A product with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $fullNameEsc = mysqli_real_escape_string($conn, $full_name);
            $unitEsc = mysqli_real_escape_string($conn, $unit);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $updateProduct = "UPDATE products SET 
                                code = '$codeEsc', 
                                name = '$nameEsc', 
                                full_name = '$fullNameEsc', 
                                unit_of_measure = '$unitEsc', 
                                description = '$descEsc', 
                                is_active = $is_active, 
                                updated_by = $userId, 
                                updated_at = CURRENT_TIMESTAMP 
                              WHERE id = $id";
            
            if (mysqli_query($conn, $updateProduct)) {
                $newValues = [
                    'id' => $id,
                    'code' => $code,
                    'name' => $name,
                    'full_name' => $full_name,
                    'unit_of_measure' => $unit,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'UPDATE', 'products', $code, "Updated product: $name", $oldValues, $newValues);
                $_SESSION['products_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update product: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_product')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete products.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid product ID.');
        }

        $fetchQuery = "SELECT * FROM products WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['code'];
        $name = $oldValues['name'];

        $chkElements = mysqli_query($conn, "SELECT COUNT(*) as count FROM product_elements WHERE product_id = $id");
        $elementsCount = 0;
        if ($chkElements) {
            $elementsCount = (int)mysqli_fetch_assoc($chkElements)['count'];
        }

        if ($elementsCount > 0) {
            sendResponse(false, 'This product cannot be deleted because it currently has ' . $elementsCount . ' element(s) configured.');
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteProduct = "DELETE FROM products WHERE id = $id";
            if (mysqli_query($conn, $deleteProduct)) {
                logAudit($conn, 'DELETE', 'products', $code, "Deleted product: $name", $oldValues);
                $_SESSION['products_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete product: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
