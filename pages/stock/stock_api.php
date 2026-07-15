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

            // Create journal entry for stock adjustment
            $dateStr = date('Ymd', strtotime($today));
            $prefix = "JE-" . $dateStr . "-";
            $stmt = mysqli_prepare($conn, "
                SELECT COUNT(*) AS cnt
                FROM journal_entries
                WHERE journal_no LIKE CONCAT(?, '%')
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "s", $prefix);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $count = mysqli_fetch_assoc($result)['cnt'];
            mysqli_stmt_close($stmt);

            $journalNo = $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

            $stmt = mysqli_prepare($conn, "
                SELECT
                    p.inventory_account_id AS inventory_account_code,
                    p.cogs_account_id AS cogs_account_code,
                    inv.account_code AS inventory_account_id,
                    inv.account_type_id AS inventory_parent_account_id,
                    cog.account_code AS cogs_account_id,
                    cog.account_type_id AS cogs_parent_account_id
                FROM product p
                LEFT JOIN accounts inv ON inv.account_code = p.inventory_account_id
                LEFT JOIN accounts cog ON cog.account_code = p.cogs_account_id
                WHERE p.id = ?
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $productInfo = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$productInfo || empty($productInfo['inventory_account_id'])) {
                throw new Exception('Inventory account was not found for the selected product.');
            }

            $inventoryAccount = (int)$productInfo['inventory_account_id'];
            $inventoryParentAccount = isset($productInfo['inventory_parent_account_id']) ? (int)$productInfo['inventory_parent_account_id'] : 0;
            $effectAccount = null;
            $effectParentAccount = 0;
            $debitAccountId = $inventoryAccount;
            $creditAccountId = $inventoryAccount;
            $debitParentAccountId = $inventoryParentAccount;
            $creditParentAccountId = $inventoryParentAccount;

            if ($adjustment_type === 'loss') {
                $effectAccount = isset($productInfo['cogs_account_id']) && (int)$productInfo['cogs_account_id'] > 0 ? (int)$productInfo['cogs_account_id'] : $inventoryAccount;
                $effectParentAccount = isset($productInfo['cogs_parent_account_id']) ? (int)$productInfo['cogs_parent_account_id'] : $inventoryParentAccount;
                $debitAccountId = $effectAccount;
                $creditAccountId = $inventoryAccount;
                $debitParentAccountId = $effectParentAccount;
                $creditParentAccountId = $inventoryParentAccount;
                $journalDescription = 'Stock adjustment loss for product #' . $product_id;
            } else {
                $effectAccount = isset($productInfo['cogs_account_id']) && (int)$productInfo['cogs_account_id'] > 0 ? (int)$productInfo['cogs_account_id'] : $inventoryAccount;
                $effectParentAccount = isset($productInfo['cogs_parent_account_id']) ? (int)$productInfo['cogs_parent_account_id'] : $inventoryParentAccount;
                $debitAccountId = $inventoryAccount;
                $creditAccountId = $effectAccount;
                $debitParentAccountId = $inventoryParentAccount;
                $creditParentAccountId = $effectParentAccount;
                $journalDescription = 'Stock adjustment gain for product #' . $product_id;
            }

            $stmt = mysqli_prepare($conn, "
                INSERT INTO journal_entries
                (journal_no, entry_date, description, statuss, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            $status = 'POSTED';
            mysqli_stmt_bind_param($stmt, "ssssi", $journalNo, $today, $journalDescription, $status, $userId);
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }
            $journalEntryId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            $currencyId = isset($stock['purchase_currency_id']) && (int)$stock['purchase_currency_id'] > 0 ? (int)$stock['purchase_currency_id'] : 2;
            $exchangeRate = isset($stock['exchange_rate']) && (float)$stock['exchange_rate'] > 0 ? (float)$stock['exchange_rate'] : 1.0;

            $unitCost = null;
            if ($currencyId === 1) {
                $unitCost = isset($stock['avg_cost_per_kg_rwf']) ? (float)$stock['avg_cost_per_kg_rwf'] : null;
            } else {
                $unitCost = isset($stock['avg_cost_per_kg_usd']) ? (float)$stock['avg_cost_per_kg_usd'] : null;
            }

            if ($unitCost === null || $unitCost < 0) {
                $unitCost = 0.0;
            }

            $amountCurrency = $unitCost * (float)$quantity;
            $amountBase = $amountCurrency * $exchangeRate;
            $zeroAmount = 0.0;
            $lineStmt = mysqli_prepare($conn, "
                INSERT INTO journal_entry_lines
                (
                    journal_entry_id,
                    parent_account_id,
                    account_id,
                    debit,
                    credit,
                    currency_id,
                    exchange_rate,
                    amount_currency,
                    amount_base,
                    description
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$lineStmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }

            if ($adjustment_type === 'loss') {
                mysqli_stmt_bind_param($lineStmt, "iiiddiddds", $journalEntryId, $debitParentAccountId, $debitAccountId, $amountCurrency, $zeroAmount, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($lineStmt)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($lineStmt, "iiiddiddds", $journalEntryId, $creditParentAccountId, $creditAccountId, $zeroAmount, $amountCurrency, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($lineStmt)) {
                    throw new Exception(mysqli_error($conn));
                }
            } else {
                mysqli_stmt_bind_param($lineStmt, "iiiddiddds", $journalEntryId, $debitParentAccountId, $debitAccountId, $amountCurrency, $zeroAmount, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($lineStmt)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($lineStmt, "iiiddiddds", $journalEntryId, $creditParentAccountId, $creditAccountId, $zeroAmount, $amountCurrency, $currencyId, $exchangeRate, $amountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($lineStmt)) {
                    throw new Exception(mysqli_error($conn));
                }
            }

            mysqli_stmt_close($lineStmt);

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
