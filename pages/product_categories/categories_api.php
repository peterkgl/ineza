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

if (empty($_SESSION['categories_token'])) {
    $_SESSION['categories_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['categories_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['categories_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_product_categories')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view product categories.');
        }

        $query = "SELECT * FROM product_categories ORDER BY category_name ASC";
        $result = mysqli_query($conn, $query);
        $categories = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $categories[] = [
                    'id' => (int)$row['id'],
                    'category_code' => $row['category_code'],
                    'category_name' => $row['category_name'],
                    'description' => $row['description'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'product_categories', 'Product Categories List', 'User viewed the product categories list');
        sendResponse(true, 'Product categories retrieved successfully.', $categories);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_product_category')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create product categories.');
        }

        $code = isset($_POST['category_code']) ? strtoupper(trim($_POST['category_code'])) : '';
        $name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if (empty($code) || empty($name)) {
            sendResponse(false, 'Category code and name are required.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_categories WHERE category_code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A category with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $insertCategory = "INSERT INTO product_categories (category_code, category_name, description, is_active) 
                               VALUES ('$codeEsc', '$nameEsc', '$descEsc', $is_active)";
            
            if (mysqli_query($conn, $insertCategory)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'category_code' => $code,
                    'category_name' => $name,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'CREATE', 'product_categories', $code, "Created product category: $name", null, $newValues);
                $_SESSION['categories_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product category created successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create product category: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_product_category')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit product categories.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $code = isset($_POST['category_code']) ? strtoupper(trim($_POST['category_code'])) : '';
        $name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        if ($id <= 0 || empty($code) || empty($name)) {
            sendResponse(false, 'Valid ID, category code, and name are required.');
        }

        $fetchQuery = "SELECT * FROM product_categories WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product category not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $code);
        $chkCode = mysqli_query($conn, "SELECT id FROM product_categories WHERE category_code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A category with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $name);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $updateCategory = "UPDATE product_categories SET 
                                category_code = '$codeEsc', 
                                category_name = '$nameEsc', 
                                description = '$descEsc', 
                                is_active = $is_active 
                               WHERE id = $id";
            
            if (mysqli_query($conn, $updateCategory)) {
                $newValues = [
                    'id' => $id,
                    'category_code' => $code,
                    'category_name' => $name,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'UPDATE', 'product_categories', $code, "Updated product category: $name", $oldValues, $newValues);
                $_SESSION['categories_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product category updated successfully.', $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update product category: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_product_category')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete product categories.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid category ID.');
        }

        $fetchQuery = "SELECT * FROM product_categories WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product category not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $code = $oldValues['category_code'];
        $name = $oldValues['category_name'];

        // Verify no products use this category
        $chkProducts = mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE category_id = $id");
        $productsCount = 0;
        if ($chkProducts) {
            $productsCount = (int)mysqli_fetch_assoc($chkProducts)['count'];
        }

        if ($productsCount > 0) {
            sendResponse(false, 'This category cannot be deleted because it is currently assigned to ' . $productsCount . ' product(s).');
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteCategory = "DELETE FROM product_categories WHERE id = $id";
            if (mysqli_query($conn, $deleteCategory)) {
                logAudit($conn, 'DELETE', 'product_categories', $code, "Deleted product category: $name", $oldValues);
                $_SESSION['categories_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, 'Product category deleted successfully.');
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete product category: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
