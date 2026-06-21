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

        $query = "SELECT p.*, 
                         inv.account_code as inventory_code, inv.account_name as inventory_name,
                         sal.account_code as sales_code, sal.account_name as sales_name,
                         cogs.account_code as cogs_code, cogs.account_name as cogs_name,
                         pc.category_code, pc.category_name
                  FROM products p
                  LEFT JOIN accounts inv ON p.inventory_account_id = inv.id
                  LEFT JOIN accounts sal ON p.sales_account_id = sal.id
                  LEFT JOIN accounts cogs ON p.cogs_account_id = cogs.id
                  LEFT JOIN product_categories pc ON p.category_id = pc.id
                  ORDER BY p.code ASC";
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
                    'inventory_account_id' => $row['inventory_account_id'] !== null ? (int)$row['inventory_account_id'] : null,
                    'inventory_code' => $row['inventory_code'],
                    'inventory_name' => $row['inventory_name'],
                    'sales_account_id' => $row['sales_account_id'] !== null ? (int)$row['sales_account_id'] : null,
                    'sales_code' => $row['sales_code'],
                    'sales_name' => $row['sales_name'],
                    'cogs_account_id' => $row['cogs_account_id'] !== null ? (int)$row['cogs_account_id'] : null,
                    'cogs_code' => $row['cogs_code'],
                    'cogs_name' => $row['cogs_name'],
                    'category_id' => $row['category_id'] !== null ? (int)$row['category_id'] : null,
                    'category_code' => $row['category_code'],
                    'category_name' => $row['category_name'],
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
        $inventory_account_id = isset($_POST['inventory_account_id']) && $_POST['inventory_account_id'] !== '' ? (int)$_POST['inventory_account_id'] : null;
        $sales_account_id = isset($_POST['sales_account_id']) && $_POST['sales_account_id'] !== '' ? (int)$_POST['sales_account_id'] : null;
        $cogs_account_id = isset($_POST['cogs_account_id']) && $_POST['cogs_account_id'] !== '' ? (int)$_POST['cogs_account_id'] : null;
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

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

            $invVal = $inventory_account_id !== null ? $inventory_account_id : "NULL";
            $salVal = $sales_account_id !== null ? $sales_account_id : "NULL";
            $cogsVal = $cogs_account_id !== null ? $cogs_account_id : "NULL";
            $catVal = $category_id !== null ? $category_id : "NULL";

            $insertProduct = "INSERT INTO products (code, name, full_name, unit_of_measure, description, inventory_account_id, sales_account_id, cogs_account_id, is_active, created_by, category_id) 
                              VALUES ('$codeEsc', '$nameEsc', '$fullNameEsc', '$unitEsc', '$descEsc', $invVal, $salVal, $cogsVal, $is_active, $userId, $catVal)";
            
            if (mysqli_query($conn, $insertProduct)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'code' => $code,
                    'name' => $name,
                    'full_name' => $full_name,
                    'unit_of_measure' => $unit,
                    'description' => $description,
                    'inventory_account_id' => $inventory_account_id,
                    'sales_account_id' => $sales_account_id,
                    'cogs_account_id' => $cogs_account_id,
                    'category_id' => $category_id,
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
        $inventory_account_id = isset($_POST['inventory_account_id']) && $_POST['inventory_account_id'] !== '' ? (int)$_POST['inventory_account_id'] : null;
        $sales_account_id = isset($_POST['sales_account_id']) && $_POST['sales_account_id'] !== '' ? (int)$_POST['sales_account_id'] : null;
        $cogs_account_id = isset($_POST['cogs_account_id']) && $_POST['cogs_account_id'] !== '' ? (int)$_POST['cogs_account_id'] : null;
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;

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

            $invVal = $inventory_account_id !== null ? $inventory_account_id : "NULL";
            $salVal = $sales_account_id !== null ? $sales_account_id : "NULL";
            $cogsVal = $cogs_account_id !== null ? $cogs_account_id : "NULL";
            $catVal = $category_id !== null ? $category_id : "NULL";

            $updateProduct = "UPDATE products SET 
                                code = '$codeEsc', 
                                name = '$nameEsc', 
                                full_name = '$fullNameEsc', 
                                unit_of_measure = '$unitEsc', 
                                description = '$descEsc', 
                                inventory_account_id = $invVal,
                                sales_account_id = $salVal,
                                cogs_account_id = $cogsVal,
                                category_id = $catVal,
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
                    'inventory_account_id' => $inventory_account_id,
                    'sales_account_id' => $sales_account_id,
                    'cogs_account_id' => $cogs_account_id,
                    'category_id' => $category_id,
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
