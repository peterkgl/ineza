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

        $query = "SELECT l.*, p.product_name, p.product_code 
                  FROM lots l 
                  LEFT JOIN product p ON l.product_id = p.id 
                  ORDER BY l.opening_date DESC";
        $result = mysqli_query($conn, $query);
        $lots = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $lots[] = [
                    'id' => (int)$row['id'],
                    'lots_code' => $row['lots_code'],
                    'product_id' => (int)$row['product_id'],
                    'product_name' => $row['product_name'] ? ($row['product_name'] . ' (' . $row['product_code'] . ')') : '—',
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
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $opening_date = isset($_POST['opening_date']) ? trim($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? trim($_POST['closing_date']) : null;

        if (empty($lots_code) || empty($opening_date) || $product_id <= 0) {
            sendResponse(false, 'Lot code, product, and opening date are required.');
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
            $insertQuery = "INSERT INTO lots (lots_code, product_id, opening_date, closing_date) VALUES ('$codeEsc', $product_id, '" . mysqli_real_escape_string($conn, $opening_date) . "', $closingVal)";
            
            if (mysqli_query($conn, $insertQuery)) {
                $newId = mysqli_insert_id($conn);
                $newValues = [
                    'id' => $newId,
                    'lots_code' => $lots_code,
                    'product_id' => $product_id,
                    'opening_date' => $opening_date,
                    'closing_date' => $closing_date
                ];

                // Carry over balances from the most recently closed lot of the same product
                $oldLotQuery = "SELECT id, lots_code FROM lots 
                                WHERE product_id = $product_id 
                                  AND closing_date IS NOT NULL 
                                  AND statuss = 1 
                                ORDER BY closing_date DESC, id DESC LIMIT 1";
                $oldLotResult = mysqli_query($conn, $oldLotQuery);
                if ($oldLotResult && mysqli_num_rows($oldLotResult) > 0) {
                    $oldLotRow = mysqli_fetch_assoc($oldLotResult);
                    $oldLotId = (int)$oldLotRow['id'];
                    $oldLotCode = $oldLotRow['lots_code'];

                    // Fetch active stocks from the old lot
                    $stockQuery = "SELECT warehouse_id, product_id, uom_id, closing, avg_cost_per_kg_usd, avg_cost_per_kg_rwf, total_value_usd, total_value_rwf 
                                   FROM stock 
                                   WHERE lot_id = $oldLotId AND closing > 0";
                    $stockResult = mysqli_query($conn, $stockQuery);
                    if ($stockResult) {
                        while ($stRow = mysqli_fetch_assoc($stockResult)) {
                            $warehouse_id = (int)$stRow['warehouse_id'];
                            $prod_id = (int)$stRow['product_id'];
                            $uom_id = (int)$stRow['uom_id'];
                            $carried_qty = (float)$stRow['closing'];
                            $avg_cost_usd = (float)$stRow['avg_cost_per_kg_usd'];
                            $avg_cost_rwf = (float)$stRow['avg_cost_per_kg_rwf'];
                            $tot_val_usd = (float)$stRow['total_value_usd'];
                            $tot_val_rwf = (float)$stRow['total_value_rwf'];

                            // Insert new stock record representing the carry over
                            $today = date('Y-m-d');
                            $insertStock = "INSERT INTO stock (
                                                warehouse_id, product_id, lot_id, uom_id, 
                                                qty_purchased, qty_sold, qty_adjusted, 
                                                avg_cost_per_kg_usd, avg_cost_per_kg_rwf, 
                                                total_value_usd, total_value_rwf, 
                                                opening, closing, last_rolled_over_at
                                            ) VALUES (
                                                $warehouse_id, $prod_id, $newId, $uom_id, 
                                                0, 0, $carried_qty, 
                                                $avg_cost_usd, $avg_cost_rwf, 
                                                $tot_val_usd, $tot_val_rwf, 
                                                0.0, $carried_qty, '$today'
                                            )";
                            if (!mysqli_query($conn, $insertStock)) {
                                throw new Exception("Failed to carry over stock to new lot: " . mysqli_error($conn));
                            }

                            // Insert CARRYOVER_IN movement record
                            $notes = "Stock carried over from closed lot '" . $oldLotCode . "' to newly created lot '" . $lots_code . "'. Quantity: " . $carried_qty . " kg. Added to Closing quantity.";
                            $notesEsc = mysqli_real_escape_string($conn, $notes);
                            $insertMvt = "INSERT INTO stock_movement (
                                              movement_type, warehouse_id, product_id, lot_id, uom_id, 
                                              qty_kg, unit_cost_usd, unit_cost_rwf, total_value_usd, total_value_rwf, 
                                              reference_type, reference_id, movement_date, notes, created_by, 
                                              opening, closing
                                          ) VALUES (
                                              'CARRYOVER_IN', $warehouse_id, $prod_id, $newId, $uom_id, 
                                              $carried_qty, $avg_cost_usd, $avg_cost_rwf, $tot_val_usd, $tot_val_rwf, 
                                              'lots', $newId, '$opening_date', '$notesEsc', $userId, 
                                              0.0, $carried_qty
                                          )";
                            if (!mysqli_query($conn, $insertMvt)) {
                                throw new Exception("Failed to insert opening stock movement: " . mysqli_error($conn));
                            }
                        }
                    }

                    // Update the old lot's status to 0 because its closing stock has now been transferred to the new lot.
                    $updateOldLotStatus = mysqli_query($conn, "UPDATE lots SET statuss = 0 WHERE id = $oldLotId");
                    if (!$updateOldLotStatus) {
                        throw new Exception("Failed to update old closed lot status to 0: " . mysqli_error($conn));
                    }
                }

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
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $opening_date = isset($_POST['opening_date']) ? trim($_POST['opening_date']) : '';
        $closing_date = isset($_POST['closing_date']) ? trim($_POST['closing_date']) : null;

        if ($id <= 0 || empty($lots_code) || empty($opening_date) || $product_id <= 0) {
            sendResponse(false, 'Valid ID, lot code, product, and opening date are required.');
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
                                product_id = $product_id,
                                opening_date = '" . mysqli_real_escape_string($conn, $opening_date) . "',
                                closing_date = $closingVal
                            WHERE id = $id";
            
            if (mysqli_query($conn, $updateQuery)) {
                $newValues = [
                    'id' => $id,
                    'lots_code' => $lots_code,
                    'product_id' => $product_id,
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
        if (!hasPermission($conn, $userId, 'close_lot')) {
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
        $product_id = (int)$oldValues['product_id'];

        // Check if lot is already closed
        if ($oldValues['closing_date']) {
            sendResponse(false, 'Lot is already closed.');
        }

        mysqli_begin_transaction($conn);
        try {
            $today = date('Y-m-d');
            $update = mysqli_query($conn, "UPDATE lots SET closing_date = '$today' WHERE id = $id");
            if ($update) {
                // Set closing stock balance to the final stock qty_on_hand
                $updateStockQuery = "UPDATE stock SET closing = qty_on_hand WHERE lot_id = $id";
                if (!mysqli_query($conn, $updateStockQuery)) {
                    throw new Exception("Failed to update stock balances on lot close: " . mysqli_error($conn));
                }

                // Check if another open lot exists for the same product
                $nextLotQuery = "SELECT id, lots_code, opening_date FROM lots 
                                 WHERE product_id = $product_id 
                                   AND closing_date IS NULL 
                                   AND id != $id 
                                 ORDER BY opening_date ASC, id ASC LIMIT 1";
                $nextLotResult = mysqli_query($conn, $nextLotQuery);
                if ($nextLotResult && mysqli_num_rows($nextLotResult) > 0) {
                    $nextLotRow = mysqli_fetch_assoc($nextLotResult);
                    $nextLotId = (int)$nextLotRow['id'];
                    $nextLotCode = $nextLotRow['lots_code'];
                    $nextLotOpeningDate = $nextLotRow['opening_date'];

                    // Fetch active stocks from the closed lot (where closing > 0)
                    $stockQuery = "SELECT warehouse_id, product_id, uom_id, closing, avg_cost_per_kg_usd, avg_cost_per_kg_rwf, total_value_usd, total_value_rwf 
                                   FROM stock 
                                   WHERE lot_id = $id AND closing > 0";
                    $stockResult = mysqli_query($conn, $stockQuery);
                    if ($stockResult) {
                        while ($stRow = mysqli_fetch_assoc($stockResult)) {
                            $warehouse_id = (int)$stRow['warehouse_id'];
                            $prod_id = (int)$stRow['product_id'];
                            $uom_id = (int)$stRow['uom_id'];
                            $carried_qty = (float)$stRow['closing'];
                            $avg_cost_usd = (float)$stRow['avg_cost_per_kg_usd'];
                            $avg_cost_rwf = (float)$stRow['avg_cost_per_kg_rwf'];
                            $tot_val_usd = (float)$stRow['total_value_usd'];
                            $tot_val_rwf = (float)$stRow['total_value_rwf'];

                            // Check if stock record exists in the next lot
                            $chkStock = mysqli_query($conn, "SELECT id, opening, closing FROM stock WHERE warehouse_id = $warehouse_id AND product_id = $prod_id AND lot_id = $nextLotId LIMIT 1");
                            if ($chkStock && mysqli_num_rows($chkStock) > 0) {
                                $stockRow = mysqli_fetch_assoc($chkStock);
                                $stockId = (int)$stockRow['id'];
                                $current_opening = (float)$stockRow['opening'];
                                $current_closing = (float)$stockRow['closing'];
                                $new_closing = $current_closing + $carried_qty;

                                $updateStock = "UPDATE stock SET 
                                                    qty_adjusted = qty_adjusted + $carried_qty,
                                                    closing = $new_closing,
                                                    last_rolled_over_at = '$today'
                                                WHERE id = $stockId";
                                if (!mysqli_query($conn, $updateStock)) {
                                    throw new Exception("Failed to update stock in next lot: " . mysqli_error($conn));
                                }
                            } else {
                                $current_opening = 0.0;
                                $current_closing = 0.0;
                                $new_closing = $carried_qty;

                                $insertStock = "INSERT INTO stock (
                                                    warehouse_id, product_id, lot_id, uom_id, 
                                                    qty_purchased, qty_sold, qty_adjusted, 
                                                    avg_cost_per_kg_usd, avg_cost_per_kg_rwf, 
                                                    total_value_usd, total_value_rwf, 
                                                    opening, closing, last_rolled_over_at
                                                ) VALUES (
                                                    $warehouse_id, $prod_id, $nextLotId, $uom_id, 
                                                    0, 0, $carried_qty, 
                                                    $avg_cost_usd, $avg_cost_rwf, 
                                                    $tot_val_usd, $tot_val_rwf, 
                                                    0.0, $new_closing, '$today'
                                                )";
                                if (!mysqli_query($conn, $insertStock)) {
                                    throw new Exception("Failed to insert stock in next lot: " . mysqli_error($conn));
                                }
                            }

                            // Insert CARRYOVER_IN movement record
                            $notes = "Stock carried over from closed lot '" . $oldValues['lots_code'] . "' to open lot '" . $nextLotCode . "'. Quantity: " . $carried_qty . " kg. Added to Closing quantity.";
                            $notesEsc = mysqli_real_escape_string($conn, $notes);
                            $insertMvt = "INSERT INTO stock_movement (
                                              movement_type, warehouse_id, product_id, lot_id, uom_id, 
                                              qty_kg, unit_cost_usd, unit_cost_rwf, total_value_usd, total_value_rwf, 
                                              reference_type, reference_id, movement_date, notes, created_by, 
                                              opening, closing
                                          ) VALUES (
                                              'CARRYOVER_IN', $warehouse_id, $prod_id, $nextLotId, $uom_id, 
                                              $carried_qty, $avg_cost_usd, $avg_cost_rwf, $tot_val_usd, $tot_val_rwf, 
                                              'lots', $nextLotId, '$today', '$notesEsc', $userId, 
                                              $current_opening, $new_closing
                                          )";
                            if (!mysqli_query($conn, $insertMvt)) {
                                throw new Exception("Failed to insert carryover stock movement: " . mysqli_error($conn));
                            }
                        }
                    }

                    // Update the closed lot's status to 0
                    $updateStatus = mysqli_query($conn, "UPDATE lots SET statuss = 0 WHERE id = $id");
                    if (!$updateStatus) {
                        throw new Exception("Failed to update closed lot status to 0: " . mysqli_error($conn));
                    }
                }

                // Roll over other open lots immediately (update last_rolled_over_at)
                $rollQuery = "UPDATE stock s
                              JOIN lots l ON s.lot_id = l.id
                              SET s.last_rolled_over_at = '$today'
                              WHERE l.closing_date IS NULL";
                if (!mysqli_query($conn, $rollQuery)) {
                    throw new Exception("Failed to roll over open lots stock: " . mysqli_error($conn));
                }

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

    case 'details':
        if (!hasPermission($conn, $userId, 'view_lots')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view lots.');
        }

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkLot = mysqli_query($conn, "SELECT l.*, p.product_name, p.product_code 
                                       FROM lots l 
                                       LEFT JOIN product p ON l.product_id = p.id 
                                       WHERE l.id = $id LIMIT 1");
        if (!$chkLot || mysqli_num_rows($chkLot) === 0) {
            sendResponse(false, 'Lot not found.');
        }
        $lot = mysqli_fetch_assoc($chkLot);

        // Fetch stock records associated with this lot
        $stockQuery = "SELECT s.*, pr.product_name, pr.product_code, w.warehouse_name, w.warehouse_code, uom.code as uom_code
                       FROM stock s
                       JOIN product pr ON s.product_id = pr.id
                       JOIN warehouses w ON s.warehouse_id = w.id
                       LEFT JOIN unit_of_measure uom ON s.uom_id = uom.id
                       WHERE s.lot_id = $id";
        $stockResult = mysqli_query($conn, $stockQuery);
        $stockRecords = [];
        if ($stockResult) {
            while ($row = mysqli_fetch_assoc($stockResult)) {
                $stockRecords[] = [
                    'product_name' => $row['product_name'] . ' (' . $row['product_code'] . ')',
                    'warehouse_name' => $row['warehouse_name'] . ' (' . $row['warehouse_code'] . ')',
                    'opening' => (float)$row['opening'],
                    'closing' => (float)$row['closing'],
                    'uom_code' => $row['uom_code'] ?? 'kg'
                ];
            }
        }

        sendResponse(true, 'Lot details fetched successfully.', [
            'lot' => [
                'id' => (int)$lot['id'],
                'lots_code' => $lot['lots_code'],
                'product_id' => (int)$lot['product_id'],
                'product_name' => $lot['product_name'] ? ($lot['product_name'] . ' (' . $lot['product_code'] . ')') : '—',
                'opening_date' => $lot['opening_date'],
                'closing_date' => $lot['closing_date'],
                'status' => $lot['closing_date'] ? 'CLOSED' : 'OPEN'
            ],
            'stock' => $stockRecords
        ]);
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
