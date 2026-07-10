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

        $query = "SELECT p.*, uom.code as uom_code, uom.name as uom_name,
                         inv.account_code as inv_code, inv.account_name as inv_name,
                         sal.account_code as sal_code, sal.account_name as sal_name,
                         cog.account_code as cog_code, cog.account_name as cog_name
                  FROM product p
                  LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
                  LEFT JOIN accounts inv ON p.inventory_account_id = inv.account_code
                  LEFT JOIN accounts sal ON p.sales_account_id = sal.account_code
                  LEFT JOIN accounts cog ON p.cogs_account_id = cog.account_code
                  ORDER BY p.product_code ASC";
        $result = mysqli_query($conn, $query);
        $products = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $products[] = [
                    'id' => (int)$row['id'],
                    'product_code' => $row['product_code'],
                    'product_name' => $row['product_name'],
                    'uom_id' => (int)$row['uom_id'],
                    'uom_code' => $row['uom_code'],
                    'uom_name' => $row['uom_name'],
                    'inventory_account_id' => $row['inventory_account_id'] ? (int)$row['inventory_account_id'] : null,
                    'sales_account_id' => $row['sales_account_id'] ? (int)$row['sales_account_id'] : null,
                    'cogs_account_id' => $row['cogs_account_id'] ? (int)$row['cogs_account_id'] : null,
                    'inventory_account_code' => $row['inv_code'],
                    'inventory_account_name' => $row['inv_name'],
                    'sales_account_code' => $row['sal_code'],
                    'sales_account_name' => $row['sal_name'],
                    'cogs_account_code' => $row['cog_code'],
                    'cogs_account_name' => $row['cog_name'],
                    'description' => $row['description'],
                    'is_active' => (int)$row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'product', 'Products List', 'User viewed the products list');
        sendResponse(true, 'Products retrieved successfully.', $products);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_product')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create products.');
        }

        $product_code = isset($_POST['product_code']) ? strtoupper(trim($_POST['product_code'])) : '';
        $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        $inventory_account_id = isset($_POST['inventory_account_id']) && $_POST['inventory_account_id'] !== '' ? (int)$_POST['inventory_account_id'] : null;
        $sales_account_id = isset($_POST['sales_account_id']) && $_POST['sales_account_id'] !== '' ? (int)$_POST['sales_account_id'] : null;
        $cogs_account_id = isset($_POST['cogs_account_id']) && $_POST['cogs_account_id'] !== '' ? (int)$_POST['cogs_account_id'] : null;

        if (empty($product_code) || empty($product_name) || empty($uom_id)) {
            sendResponse(false, 'Product code, product name, and unit of measure are required.');
        }

        $codeEsc = mysqli_real_escape_string($conn, $product_code);
        $chkCode = mysqli_query($conn, "SELECT id FROM product WHERE product_code = '$codeEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A product with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $product_name);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $invEsc = $inventory_account_id !== null ? $inventory_account_id : "NULL";
            $salEsc = $sales_account_id !== null ? $sales_account_id : "NULL";
            $cogEsc = $cogs_account_id !== null ? $cogs_account_id : "NULL";

            $insertProduct = "INSERT INTO product (product_code, product_name, uom_id, inventory_account_id, sales_account_id, cogs_account_id, description, is_active) 
                              VALUES ('$codeEsc', '$nameEsc', $uom_id, $invEsc, $salEsc, $cogEsc, '$descEsc', $is_active)";
            
            if (mysqli_query($conn, $insertProduct)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'product_code' => $product_code,
                    'product_name' => $product_name,
                    'uom_id' => $uom_id,
                    'inventory_account_id' => $inventory_account_id,
                    'sales_account_id' => $sales_account_id,
                    'cogs_account_id' => $cogs_account_id,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'CREATE', 'product', $product_code, "Created product: $product_name", null, $newValues);
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
        $product_code = isset($_POST['product_code']) ? strtoupper(trim($_POST['product_code'])) : '';
        $product_name = isset($_POST['product_name']) ? trim($_POST['product_name']) : '';
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;

        $inventory_account_id = isset($_POST['inventory_account_id']) && $_POST['inventory_account_id'] !== '' ? (int)$_POST['inventory_account_id'] : null;
        $sales_account_id = isset($_POST['sales_account_id']) && $_POST['sales_account_id'] !== '' ? (int)$_POST['sales_account_id'] : null;
        $cogs_account_id = isset($_POST['cogs_account_id']) && $_POST['cogs_account_id'] !== '' ? (int)$_POST['cogs_account_id'] : null;

        if ($id <= 0 || empty($product_code) || empty($product_name) || empty($uom_id)) {
            sendResponse(false, 'Valid ID, product code, product name, and unit of measure are required.');
        }

        $fetchQuery = "SELECT * FROM product WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $codeEsc = mysqli_real_escape_string($conn, $product_code);
        $chkCode = mysqli_query($conn, "SELECT id FROM product WHERE product_code = '$codeEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A product with this code already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $nameEsc = mysqli_real_escape_string($conn, $product_name);
            $descEsc = mysqli_real_escape_string($conn, $description);

            $invEsc = $inventory_account_id !== null ? $inventory_account_id : "NULL";
            $salEsc = $sales_account_id !== null ? $sales_account_id : "NULL";
            $cogEsc = $cogs_account_id !== null ? $cogs_account_id : "NULL";

            $updateProduct = "UPDATE product SET 
                              product_code = '$codeEsc', 
                              product_name = '$nameEsc', 
                              uom_id = $uom_id, 
                              inventory_account_id = $invEsc,
                              sales_account_id = $salEsc,
                              cogs_account_id = $cogEsc,
                              description = '$descEsc',
                              is_active = $is_active, 
                              updated_at = CURRENT_TIMESTAMP 
                              WHERE id = $id";
            
            if (mysqli_query($conn, $updateProduct)) {
                $newValues = [
                    'id' => $id,
                    'product_code' => $product_code,
                    'product_name' => $product_name,
                    'uom_id' => $uom_id,
                    'inventory_account_id' => $inventory_account_id,
                    'sales_account_id' => $sales_account_id,
                    'cogs_account_id' => $cogs_account_id,
                    'description' => $description,
                    'is_active' => $is_active
                ];

                logAudit($conn, 'UPDATE', 'product', $product_code, "Updated product: $product_name", $oldValues, $newValues);
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

        $fetchQuery = "SELECT * FROM product WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Product not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);
        $product_code = $oldValues['product_code'];
        $product_name = $oldValues['product_name'];

        $chkElements = mysqli_query($conn, "SELECT COUNT(*) as count FROM product_element_composition WHERE product_id = $id");
        $elementsCount = 0;
        if ($chkElements) {
            $elementsCount = (int)mysqli_fetch_assoc($chkElements)['count'];
        }

        if ($elementsCount > 0) {
            sendResponse(false, 'This product cannot be deleted because it currently has ' . $elementsCount . ' element(s) configured.');
        }

        mysqli_begin_transaction($conn);

        try {
            $deleteProduct = "DELETE FROM product WHERE id = $id";
            if (mysqli_query($conn, $deleteProduct)) {
                logAudit($conn, 'DELETE', 'product', $product_code, "Deleted product: $product_name", $oldValues);
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
