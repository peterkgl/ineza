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

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

// Check permission
if (!hasPermission($conn, $userId, 'view_stock')) {
    http_response_code(403);
    sendResponse(false, 'Forbidden: You do not have permission to view stock details.');
}

switch ($action) {
    case 'movements':
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

        if ($product_id <= 0) {
            sendResponse(false, 'Valid product_id is required.');
        }

        // Fetch movements for the product across all warehouses and lots, ordered chronologically
        $query = "SELECT sm.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name, uom.code AS uom_code, p.purchase_no,
                         w.warehouse_name, w.warehouse_code, l.lots_code
                  FROM stock_movement sm
                  LEFT JOIN users u ON sm.created_by = u.id
                  LEFT JOIN unit_of_measure uom ON sm.uom_id = uom.id
                  LEFT JOIN purchasing p ON sm.reference_type = 'purchasing' AND sm.reference_id = p.id
                  LEFT JOIN warehouses w ON sm.warehouse_id = w.id
                  LEFT JOIN lots l ON sm.lot_id = l.id
                  WHERE sm.product_id = $product_id
                  ORDER BY sm.id ASC";

        $result = mysqli_query($conn, $query);
        $movements = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $movements[] = [
                    'id' => (int)$row['id'],
                    'movement_type' => $row['movement_type'],
                    'qty_kg' => (float)$row['qty_kg'],
                    'unit_cost_usd' => $row['unit_cost_usd'] !== null ? (float)$row['unit_cost_usd'] : null,
                    'unit_cost_rwf' => $row['unit_cost_rwf'] !== null ? (float)$row['unit_cost_rwf'] : null,
                    'total_value_usd' => $row['total_value_usd'] !== null ? (float)$row['total_value_usd'] : null,
                    'total_value_rwf' => $row['total_value_rwf'] !== null ? (float)$row['total_value_rwf'] : null,
                    'reference_type' => $row['reference_type'],
                    'reference_id' => $row['reference_id'],
                    'purchase_no' => $row['purchase_no'],
                    'warehouse_name' => $row['warehouse_name'] ? ($row['warehouse_name'] . ' (' . $row['warehouse_code'] . ')') : '—',
                    'lots_code' => $row['lots_code'],
                    'movement_date' => $row['movement_date'],
                    'notes' => $row['notes'],
                    'creator_name' => $row['creator_name'] ?? 'System',
                    'uom_code' => $row['uom_code'] ?? 'kg'
                ];
            }
            sendResponse(true, 'Movements fetched successfully.', $movements);
        } else {
            sendResponse(false, 'Failed to fetch movements: ' . mysqli_error($conn));
        }
        break;

    case 'adjust':
        // Check permission
        if (!hasPermission($conn, $userId, 'view_stock')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to adjust stock.');
        }

        $stock_id = isset($_POST['stock_id']) ? (int)$_POST['stock_id'] : 0;
        $adjustment_type = isset($_POST['adjustment_type']) ? trim($_POST['adjustment_type']) : '';
        $quantity = isset($_POST['quantity']) ? (float)$_POST['quantity'] : 0.0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

        if ($stock_id <= 0 || !in_array($adjustment_type, ['loss', 'gain']) || $quantity <= 0) {
            sendResponse(false, 'Valid Stock ID, adjustment type (loss/gain), and quantity (> 0) are required.');
        }

        // Fetch current stock values
        $stockQuery = mysqli_query($conn, "SELECT * FROM stock WHERE id = $stock_id LIMIT 1");
        if (!$stockQuery || mysqli_num_rows($stockQuery) === 0) {
            sendResponse(false, 'Stock record not found.');
        }
        $stock = mysqli_fetch_assoc($stockQuery);

        $uom_id = (int)$stock['uom_id'];
        $warehouse_id = (int)$stock['warehouse_id'];
        $product_id = (int)$stock['product_id'];
        $lot_id = (int)$stock['lot_id'];
        $current_closing = (float)$stock['closing'];
        $current_adjusted = (float)$stock['qty_adjusted'];
        $current_opening = (float)$stock['opening'];

        mysqli_begin_transaction($conn);
        try {
            if ($adjustment_type === 'loss') {
                $new_adjusted = $current_adjusted - $quantity;
                $new_closing = $current_closing - $quantity;
                $movement_type = 'ADJUSTMENT_OUT';
            } else { // gain
                $new_adjusted = $current_adjusted + $quantity;
                $new_closing = $current_closing + $quantity;
                $movement_type = 'ADJUSTMENT_IN';
            }

            if ($new_closing < 0) {
                $new_closing = 0.0; // Avoid negative closing stock
            }

            // Update the stock table: Only Closing and Adjusted value are updated. Opening is NOT changed.
            $updateStock = "UPDATE stock SET 
                                qty_adjusted = $new_adjusted,
                                closing = $new_closing
                            WHERE id = $stock_id";
            if (!mysqli_query($conn, $updateStock)) {
                throw new Exception("Failed to update stock: " . mysqli_error($conn));
            }

            // Save adjustment as a new record in the Stock Movement table
            $today = date('Y-m-d');
            $notesEsc = mysqli_real_escape_string($conn, $notes ? $notes : "Manual adjustment: " . ucfirst($adjustment_type));
            $insertMvt = "INSERT INTO stock_movement (
                              movement_type, warehouse_id, product_id, lot_id, uom_id, 
                              qty_kg, movement_date, notes, created_by, 
                              opening, closing
                          ) VALUES (
                              '$movement_type', $warehouse_id, $product_id, $lot_id, $uom_id, 
                              $quantity, '$today', '$notesEsc', $userId, 
                              $current_opening, $new_closing
                          )";
            if (!mysqli_query($conn, $insertMvt)) {
                throw new Exception("Failed to insert stock movement: " . mysqli_error($conn));
            }

            logAudit($conn, 'UPDATE', 'stock', "Adjustment: " . ucfirst($adjustment_type), "Adjusted stock id $stock_id by $quantity kg (" . ucfirst($adjustment_type) . ")", $stock, [
                'id' => $stock_id,
                'qty_adjusted' => $new_adjusted,
                'closing' => $new_closing
            ]);

            mysqli_commit($conn);
            sendResponse(true, "Stock adjusted successfully.", [
                'qty_adjusted' => $new_adjusted,
                'closing' => $new_closing
            ]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, "Failed to adjust stock: " . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
