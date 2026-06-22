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

        $query = "SELECT l.*, 
                         p.name as product_name, p.code as product_code,
                         s.name as supplier_name,
                         w.warehouse_name as warehouse_name,
                         c.code as currency_code,
                         u.first_name, u.last_name
                  FROM lots l
                  LEFT JOIN products p ON l.product_id = p.id
                  LEFT JOIN suppliers s ON l.supplier_id = s.id
                  LEFT JOIN warehouses w ON l.warehouse_id = w.id
                  LEFT JOIN currencies c ON l.currency_id = c.id
                  LEFT JOIN users u ON l.created_by = u.id
                  ORDER BY l.created_at DESC";
        $result = mysqli_query($conn, $query);
        $lots = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lots[] = [
                    'id' => (int)$row['id'],
                    'lot_number' => $row['lot_number'],
                    'product_id' => (int)$row['product_id'],
                    'product_name' => $row['product_name'],
                    'product_code' => $row['product_code'],
                    'supplier_id' => $row['supplier_id'] !== null ? (int)$row['supplier_id'] : null,
                    'supplier_name' => $row['supplier_name'],
                    'received_date' => $row['received_date'],
                    'quantity_received' => (float)$row['quantity_received'],
                    'remaining_quantity' => (float)$row['remaining_quantity'],
                    'unit_cost' => $row['unit_cost'] !== null ? (float)$row['unit_cost'] : null,
                    'currency_id' => $row['currency_id'] !== null ? (int)$row['currency_id'] : null,
                    'currency_code' => $row['currency_code'],
                    'exchange_rate' => $row['exchange_rate'] !== null ? (float)$row['exchange_rate'] : null,
                    'status' => $row['status'],
                    'closing_date' => $row['closing_date'],
                    'description' => $row['description'],
                    'created_by' => $row['created_by'] !== null ? (int)$row['created_by'] : null,
                    'created_by_name' => $row['first_name'] ? trim($row['first_name'] . ' ' . $row['last_name']) : 'System',
                    'created_at' => $row['created_at'],
                    'warehouse_id' => $row['warehouse_id'] !== null ? (int)$row['warehouse_id'] : null,
                    'warehouse_name' => $row['warehouse_name']
                ];
            }
        }

        logAudit($conn, 'VIEW', 'lots', 'Lots List', 'User viewed the lots list');
        sendResponse(true, 'Lots retrieved successfully.', $lots);
        break;

    case 'get_carryover':
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if ($productId <= 0) {
            sendResponse(false, 'Invalid product ID.');
        }

        // Fetch the most recently created lot for this product to get its closing balance
        $coQuery = "SELECT remaining_quantity FROM lots WHERE product_id = $productId ORDER BY id DESC LIMIT 1";
        $coResult = mysqli_query($conn, $coQuery);
        $carryover = 0.00;
        if ($coResult && mysqli_num_rows($coResult) > 0) {
            $carryover = (float)mysqli_fetch_assoc($coResult)['remaining_quantity'];
        }
        sendResponse(true, 'Carryover balance retrieved.', ['carryover' => $carryover]);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create lots.');
        }

        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $supplier_id = isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $warehouse_id = isset($_POST['warehouse_id']) && $_POST['warehouse_id'] !== '' ? (int)$_POST['warehouse_id'] : null;
        $received_date = isset($_POST['received_date']) ? trim($_POST['received_date']) : '';
        $quantity_received = isset($_POST['quantity_received']) ? (float)$_POST['quantity_received'] : 0.00;
        $remaining_quantity = isset($_POST['remaining_quantity']) ? (float)$_POST['remaining_quantity'] : $quantity_received;
        $unit_cost = isset($_POST['unit_cost']) && $_POST['unit_cost'] !== '' ? (float)$_POST['unit_cost'] : null;
        $currency_id = isset($_POST['currency_id']) && $_POST['currency_id'] !== '' ? (int)$_POST['currency_id'] : null;
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $status = 'OPEN';

        if ($product_id <= 0 || empty($received_date)) {
            sendResponse(false, 'Product mineral and opening date are required.');
        }

        // Verify product exists
        $chkProduct = mysqli_query($conn, "SELECT code, name FROM products WHERE id = $product_id LIMIT 1");
        if (!$chkProduct || mysqli_num_rows($chkProduct) === 0) {
            sendResponse(false, 'Selected product does not exist.');
        }
        $prodInfo = mysqli_fetch_assoc($chkProduct);
        $productCode = $prodInfo['code'];

        // If status is OPEN, check if there is another open lot globally
        if ($status === 'OPEN') {
            $chkOpen = mysqli_query($conn, "SELECT lot_number FROM lots WHERE status = 'OPEN' LIMIT 1");
            if ($chkOpen && mysqli_num_rows($chkOpen) > 0) {
                $openLotName = mysqli_fetch_assoc($chkOpen)['lot_number'];
                sendResponse(false, "Cannot create an OPEN lot because lot '$openLotName' is currently OPEN. Only one lot can be open at a time. Please close it first.");
            }
        }

        // Business Rule: Fetch previous lot closing balance to become new opening balance
        $prevQuery = "SELECT remaining_quantity FROM lots WHERE product_id = $product_id ORDER BY id DESC LIMIT 1";
        $prevResult = mysqli_query($conn, $prevQuery);
        if ($prevResult && mysqli_num_rows($prevResult) > 0) {
            $carryoverBalance = (float)mysqli_fetch_assoc($prevResult)['remaining_quantity'];
            if ($carryoverBalance > 0) {
                $quantity_received = $carryoverBalance;
                $remaining_quantity = $carryoverBalance;
            }
        }

        // Auto-generate unique lot number: LOT-[PRODUCT_CODE]-[YYYYMMDD]-[SEQ]
        $formattedDate = date('Ymd', strtotime($received_date));
        $seq = 1;
        $lotNumber = "";
        do {
            $seqStr = str_pad($seq, 3, '0', STR_PAD_LEFT);
            $lotNumber = "LOT-{$productCode}-{$formattedDate}-{$seqStr}";
            $chkUnique = mysqli_query($conn, "SELECT id FROM lots WHERE lot_number = '$lotNumber' LIMIT 1");
            $exists = ($chkUnique && mysqli_num_rows($chkUnique) > 0);
            $seq++;
        } while ($exists);

        // Optional relationship validations
        if ($supplier_id !== null) {
            $chkSupp = mysqli_query($conn, "SELECT id FROM suppliers WHERE id = $supplier_id LIMIT 1");
            if (!$chkSupp || mysqli_num_rows($chkSupp) === 0) {
                sendResponse(false, 'Selected supplier does not exist.');
            }
        }
        if ($warehouse_id !== null) {
            $chkWare = mysqli_query($conn, "SELECT id FROM warehouses WHERE id = $warehouse_id LIMIT 1");
            if (!$chkWare || mysqli_num_rows($chkWare) === 0) {
                sendResponse(false, 'Selected warehouse does not exist.');
            }
        }
        if ($currency_id !== null) {
            $chkCurr = mysqli_query($conn, "SELECT id FROM currencies WHERE id = $currency_id LIMIT 1");
            if (!$chkCurr || mysqli_num_rows($chkCurr) === 0) {
                sendResponse(false, 'Selected currency does not exist.');
            }
        }

        mysqli_begin_transaction($conn);
        try {
            $supVal = $supplier_id !== null ? $supplier_id : "NULL";
            $whVal = $warehouse_id !== null ? $warehouse_id : "NULL";
            $currVal = $currency_id !== null ? $currency_id : "NULL";
            $costVal = $unit_cost !== null ? $unit_cost : "NULL";
            $rateVal = $exchange_rate !== null ? $exchange_rate : "NULL";
            $descEsc = mysqli_real_escape_string($conn, $description);

            $insertQuery = "INSERT INTO lots (lot_number, product_id, supplier_id, received_date, quantity_received, remaining_quantity, unit_cost, currency_id, exchange_rate, status, description, created_by, warehouse_id)
                            VALUES ('$lotNumber', $product_id, $supVal, '$received_date', $quantity_received, $remaining_quantity, $costVal, $currVal, $rateVal, '$status', '$descEsc', $userId, $whVal)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'lot_number' => $lotNumber,
                    'product_id' => $product_id,
                    'status' => $status,
                    'quantity_received' => $quantity_received,
                    'remaining_quantity' => $remaining_quantity
                ];
                logAudit($conn, 'CREATE', 'lots', $lotNumber, "Created lot: $lotNumber", null, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '$lotNumber' created successfully.", $newValues);
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
        $supplier_id = isset($_POST['supplier_id']) && $_POST['supplier_id'] !== '' ? (int)$_POST['supplier_id'] : null;
        $warehouse_id = isset($_POST['warehouse_id']) && $_POST['warehouse_id'] !== '' ? (int)$_POST['warehouse_id'] : null;
        $received_date = isset($_POST['received_date']) ? trim($_POST['received_date']) : '';
        $quantity_received = isset($_POST['quantity_received']) ? (float)$_POST['quantity_received'] : 0.00;
        $remaining_quantity = isset($_POST['remaining_quantity']) ? (float)$_POST['remaining_quantity'] : $quantity_received;
        $unit_cost = isset($_POST['unit_cost']) && $_POST['unit_cost'] !== '' ? (float)$_POST['unit_cost'] : null;
        $currency_id = isset($_POST['currency_id']) && $_POST['currency_id'] !== '' ? (int)$_POST['currency_id'] : null;
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : null;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        if ($id <= 0 || empty($received_date)) {
            sendResponse(false, 'Valid ID and opening date are required.');
        }

        // Fetch current lot values
        $fetchQuery = "SELECT * FROM lots WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        // Business Rule: Closed lots are read-only
        if (strtoupper($oldValues['status']) === 'CLOSED') {
            sendResponse(false, 'Closed lots are read-only and cannot be modified.');
        }

        mysqli_begin_transaction($conn);
        try {
            $supVal = $supplier_id !== null ? $supplier_id : "NULL";
            $whVal = $warehouse_id !== null ? $warehouse_id : "NULL";
            $currVal = $currency_id !== null ? $currency_id : "NULL";
            $costVal = $unit_cost !== null ? $unit_cost : "NULL";
            $rateVal = $exchange_rate !== null ? $exchange_rate : "NULL";
            $descEsc = mysqli_real_escape_string($conn, $description);

            $updateQuery = "UPDATE lots SET 
                                supplier_id = $supVal,
                                warehouse_id = $whVal,
                                received_date = '$received_date',
                                quantity_received = $quantity_received,
                                remaining_quantity = $remaining_quantity,
                                unit_cost = $costVal,
                                currency_id = $currVal,
                                exchange_rate = $rateVal,
                                description = '$descEsc'
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'supplier_id' => $supplier_id,
                    'warehouse_id' => $warehouse_id,
                    'received_date' => $received_date,
                    'quantity_received' => $quantity_received,
                    'remaining_quantity' => $remaining_quantity,
                    'status' => $oldValues['status']
                ];
                logAudit($conn, 'UPDATE', 'lots', $oldValues['lot_number'], "Updated lot: " . $oldValues['lot_number'], $oldValues, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '" . $oldValues['lot_number'] . "' updated successfully.", $newValues);
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update lot: ' . $e->getMessage());
        }
        break;

    case 'open':
        if (!hasPermission($conn, $userId, 'open_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to open lots.');
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

        // Check if there is another open lot
        $chkOpen = mysqli_query($conn, "SELECT lot_number FROM lots WHERE status = 'OPEN' AND id != $id LIMIT 1");
        if ($chkOpen && mysqli_num_rows($chkOpen) > 0) {
            $openLotName = mysqli_fetch_assoc($chkOpen)['lot_number'];
            sendResponse(false, "Cannot open this lot because lot '$openLotName' is currently OPEN. Only one lot can be open at a time.");
        }

        mysqli_begin_transaction($conn);
        try {
            $update = mysqli_query($conn, "UPDATE lots SET status = 'OPEN', closing_date = NULL WHERE id = $id");
            if ($update) {
                $newValues = $oldValues;
                $newValues['status'] = 'OPEN';
                $newValues['closing_date'] = null;
                logAudit($conn, 'UPDATE', 'lots', $oldValues['lot_number'], "Opened lot: " . $oldValues['lot_number'], $oldValues, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '" . $oldValues['lot_number'] . "' has been opened.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to open lot: ' . $e->getMessage());
        }
        break;

    case 'close':
        if (!hasPermission($conn, $userId, 'close_lot')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to close lots.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $forceClose = isset($_POST['force_close']) && $_POST['force_close'] === '1';

        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkLot = mysqli_query($conn, "SELECT * FROM lots WHERE id = $id LIMIT 1");
        if (!$chkLot || mysqli_num_rows($chkLot) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $oldValues = mysqli_fetch_assoc($chkLot);

        // Business Rule: "A lot cannot be closed until all stock balances are transferred or confirmed."
        $remainingQty = (float)$oldValues['remaining_quantity'];
        if ($remainingQty > 0 && !$forceClose) {
            // Send warning back to JS so it can prompt the user for confirmation
            sendResponse(false, 'warning_balance', ['remaining_quantity' => $remainingQty]);
        }

        mysqli_begin_transaction($conn);
        try {
            $today = date('Y-m-d');
            $update = mysqli_query($conn, "UPDATE lots SET status = 'CLOSED', closing_date = '$today' WHERE id = $id");
            if ($update) {
                $newValues = $oldValues;
                $newValues['status'] = 'CLOSED';
                $newValues['closing_date'] = $today;
                logAudit($conn, 'UPDATE', 'lots', $oldValues['lot_number'], "Closed lot: " . $oldValues['lot_number'], $oldValues, $newValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '" . $oldValues['lot_number'] . "' has been closed.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to close lot: ' . $e->getMessage());
        }
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
        $lotNo = $oldValues['lot_number'];

        // Business Rule: "A lot cannot be deleted if it already has purchases, sales, or stock transactions."
        $dependencies = [];

        // Check purchase_items
        $chkPurch = mysqli_query($conn, "SELECT COUNT(*) as count FROM purchase_items WHERE lot_id = $id");
        if ($chkPurch) {
            $count = (int)mysqli_fetch_assoc($chkPurch)['count'];
            if ($count > 0) $dependencies[] = "$count purchase item(s)";
        }

        // Check sale_items
        $chkSales = mysqli_query($conn, "SELECT COUNT(*) as count FROM sale_items WHERE lot_id = $id");
        if ($chkSales) {
            $count = (int)mysqli_fetch_assoc($chkSales)['count'];
            if ($count > 0) $dependencies[] = "$count sale item(s)";
        }

        // Check stock_movements
        $chkStock = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_movements WHERE lot_id = $id");
        if ($chkStock) {
            $count = (int)mysqli_fetch_assoc($chkStock)['count'];
            if ($count > 0) $dependencies[] = "$count stock movement(s)";
        }

        // Check inventory_count_items
        $chkInv = mysqli_query($conn, "SELECT COUNT(*) as count FROM inventory_count_items WHERE lot_id = $id");
        if ($chkInv) {
            $count = (int)mysqli_fetch_assoc($chkInv)['count'];
            if ($count > 0) $dependencies[] = "$count inventory count item(s)";
        }

        // Check stock_adjustment_items
        $chkAdj = mysqli_query($conn, "SELECT COUNT(*) as count FROM stock_adjustment_items WHERE lot_id = $id");
        if ($chkAdj) {
            $count = (int)mysqli_fetch_assoc($chkAdj)['count'];
            if ($count > 0) $dependencies[] = "$count stock adjustment item(s)";
        }

        if (!empty($dependencies)) {
            sendResponse(false, 'This lot cannot be deleted because it is referenced in: ' . implode(', ', $dependencies) . '.');
        }

        mysqli_begin_transaction($conn);
        try {
            $delete = mysqli_query($conn, "DELETE FROM lots WHERE id = $id");
            if ($delete) {
                logAudit($conn, 'DELETE', 'lots', $lotNo, "Deleted lot: $lotNo", $oldValues);
                $_SESSION['lots_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Lot '$lotNo' deleted successfully.");
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
