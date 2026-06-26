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

if (empty($_SESSION['purchas_token'])) {
    $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['purchas_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['purchas_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

// Helper to update stock values
function updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $qty_diff, $value_usd_diff, $value_rwf_diff) {
    $warehouse_id = (int)$warehouse_id;
    $product_id = (int)$product_id;
    $lot_id = (int)$lot_id;
    $uom_id = (int)$uom_id;
    
    $chk = mysqli_query($conn, "SELECT id, qty_purchased, total_value_usd, total_value_rwf FROM stock WHERE product_id = $product_id LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $row = mysqli_fetch_assoc($chk);
        $stockId = $row['id'];
        $new_qty = (float)$row['qty_purchased'] + (float)$qty_diff;
        $new_val_usd = (float)$row['total_value_usd'] + (float)$value_usd_diff;
        $new_val_rwf = (float)$row['total_value_rwf'] + (float)$value_rwf_diff;
        
        // Prevent negative stock
        if ($new_qty < 0) $new_qty = 0;
        if ($new_val_usd < 0) $new_val_usd = 0;
        if ($new_val_rwf < 0) $new_val_rwf = 0;
        
        $avg_cost_usd = $new_qty > 0 ? $new_val_usd / $new_qty : 0;
        $avg_cost_rwf = $new_qty > 0 ? $new_val_rwf / $new_qty : 0;
        
        $update = "UPDATE stock SET 
                      qty_purchased = $new_qty, 
                      total_value_usd = $new_val_usd, 
                      total_value_rwf = $new_val_rwf, 
                      avg_cost_per_kg_usd = $avg_cost_usd, 
                      avg_cost_per_kg_rwf = $avg_cost_rwf 
                   WHERE id = $stockId";
        return mysqli_query($conn, $update);
    } else {
        if ($qty_diff > 0) {
            $avg_cost_usd = $value_usd_diff / $qty_diff;
            $avg_cost_rwf = $value_rwf_diff / $qty_diff;
            
            $insert = "INSERT INTO stock (warehouse_id, product_id, lot_id, uom_id, qty_purchased, qty_sold, qty_adjusted, total_value_usd, total_value_rwf, avg_cost_per_kg_usd, avg_cost_per_kg_rwf) 
                       VALUES ($warehouse_id, $product_id, $lot_id, $uom_id, $qty_diff, 0, 0, $value_usd_diff, $value_rwf_diff, $avg_cost_usd, $avg_cost_rwf)";
            return mysqli_query($conn, $insert);
        }
    }
    return true;
}

switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view purchases.');
        }

        $query = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, w.warehouse_name, uom.code as uom_code
                  FROM purchasing p
                  JOIN product pr ON p.product_id = pr.id
                  JOIN lots l ON p.lot_id = l.id
                  JOIN suppliers s ON p.supplier_id = s.id
                  JOIN warehouses w ON p.warehouse_id = w.id
                  LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
                  ORDER BY p.purchase_date DESC, p.id DESC";
        
        $result = mysqli_query($conn, $query);
        $purchases = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $pId = (int)$row['id'];
                
                // Fetch associated grades
                $gradesQuery = "SELECT peg.*, pe.element_code, pe.element_name, pe.symbol 
                                FROM purchasing_element_grade peg
                                JOIN product_element pe ON peg.product_element_id = pe.id
                                WHERE peg.purchasing_id = $pId";
                $gradesResult = mysqli_query($conn, $gradesQuery);
                $grades = [];
                $primaryElementId = null;
                
                if ($gradesResult) {
                    while ($gRow = mysqli_fetch_assoc($gradesResult)) {
                        $grades[] = [
                            'product_element_id' => (int)$gRow['product_element_id'],
                            'element_code' => $gRow['element_code'],
                            'element_name' => $gRow['element_name'],
                            'symbol' => $gRow['symbol'],
                            'grade_pct' => (float)$gRow['grade_pct'],
                            'notes' => $gRow['notes']
                        ];
                    }
                }
                
                // Find primary grade from product composition
                $compQuery = "SELECT product_element_id FROM product_element_composition WHERE product_id = {$row['product_id']} AND is_primary_grade = 1 LIMIT 1";
                $compResult = mysqli_query($conn, $compQuery);
                if ($compResult && mysqli_num_rows($compResult) > 0) {
                    $primaryElementId = (int)mysqli_fetch_assoc($compResult)['product_element_id'];
                }

                $row['id'] = $pId;
                $row['lot_id'] = (int)$row['lot_id'];
                $row['product_id'] = (int)$row['product_id'];
                $row['supplier_id'] = (int)$row['supplier_id'];
                $row['warehouse_id'] = (int)$row['warehouse_id'];
                $row['uom_id'] = (int)$row['uom_id'];
                $row['quantity_kg'] = (float)$row['quantity_kg'];
                $row['price_per_kg_rwf'] = $row['price_per_kg_rwf'] !== null ? (float)$row['price_per_kg_rwf'] : null;
                $row['purchase_value_rwf'] = $row['purchase_value_rwf'] !== null ? (float)$row['purchase_value_rwf'] : null;
                $row['exchange_rate'] = $row['exchange_rate'] !== null ? (float)$row['exchange_rate'] : null;
                $row['purchase_value_usd'] = $row['purchase_value_usd'] !== null ? (float)$row['purchase_value_usd'] : null;
                $row['net_paid_supplier_usd'] = $row['net_paid_supplier_usd'] !== null ? (float)$row['net_paid_supplier_usd'] : null;
                $row['charges_per_kg'] = $row['charges_per_kg'] !== null ? (float)$row['charges_per_kg'] : null;
                $row['price_per_ta_unit'] = $row['price_per_ta_unit'] !== null ? (float)$row['price_per_ta_unit'] : null;
                $row['price_per_kg_usd'] = $row['price_per_kg_usd'] !== null ? (float)$row['price_per_kg_usd'] : null;
                $row['lme_price'] = $row['lme_price'] !== null ? (float)$row['lme_price'] : null;
                $row['tc_charges'] = $row['tc_charges'] !== null ? (float)$row['tc_charges'] : null;
                $row['tax_rra'] = $row['tax_rra'] !== null ? (float)$row['tax_rra'] : null;
                $row['tax_rma'] = $row['tax_rma'] !== null ? (float)$row['tax_rma'] : null;
                $row['tax_inkomane'] = $row['tax_inkomane'] !== null ? (float)$row['tax_inkomane'] : null;
                $row['production_charges'] = $row['production_charges'] !== null ? (float)$row['production_charges'] : null;
                
                $row['grades'] = $grades;
                $row['primary_element_id'] = $primaryElementId;
                
                $purchases[] = $row;
            }
        }

        logAudit($conn, 'VIEW', 'purchasing', 'Purchases List', 'User viewed the purchases list');
        sendResponse(true, 'Purchases retrieved successfully.', $purchases);
        break;

    case 'get_product_elements':
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if ($productId <= 0) {
            sendResponse(false, 'Valid product ID is required.');
        }

        $query = "SELECT pe.id, pe.element_code, pe.element_name, pe.symbol, pec.is_primary_grade
                  FROM product_element_composition pec
                  JOIN product_element pe ON pec.product_element_id = pe.id
                  WHERE pec.product_id = $productId AND pe.is_active = 1
                  ORDER BY pec.display_order ASC, pe.element_code ASC";
        $result = mysqli_query($conn, $query);
        $elements = [];

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $elements[] = [
                    'id' => (int)$row['id'],
                    'element_code' => $row['element_code'],
                    'element_name' => $row['element_name'],
                    'symbol' => $row['symbol'],
                    'is_primary_grade' => (int)$row['is_primary_grade']
                ];
            }
        } else {
            // Fallback to loading all active product elements
            $fallbackQuery = "SELECT id, element_code, element_name, symbol, 0 as is_primary_grade
                              FROM product_element
                              WHERE is_active = 1
                              ORDER BY element_code ASC";
            $fallbackResult = mysqli_query($conn, $fallbackQuery);
            if ($fallbackResult) {
                while ($row = mysqli_fetch_assoc($fallbackResult)) {
                    $elements[] = [
                        'id' => (int)$row['id'],
                        'element_code' => $row['element_code'],
                        'element_name' => $row['element_name'],
                        'symbol' => $row['symbol'],
                        'is_primary_grade' => 0
                    ];
                }
            }
        }

        sendResponse(true, 'Product elements loaded.', $elements);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to create purchases.');
        }

        $purchase_date = isset($_POST['purchase_date']) && !empty($_POST['purchase_date']) ? trim($_POST['purchase_date']) : date('Y-m-d');
        $delivery_date = isset($_POST['delivery_date']) && !empty($_POST['delivery_date']) ? trim($_POST['delivery_date']) : null;
        $purchase_no = isset($_POST['purchase_no']) ? trim($_POST['purchase_no']) : '';
        $delivery_no = isset($_POST['delivery_no']) ? trim($_POST['delivery_no']) : null;
        $inventory_code = isset($_POST['inventory_code']) ? trim($_POST['inventory_code']) : null;
        
        $lot_id = isset($_POST['lot_id']) ? (int)$_POST['lot_id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $warehouse_id = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
        $quantity_kg = isset($_POST['quantity_kg']) ? (float)$_POST['quantity_kg'] : 0.0;
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;

        // Financial/Pricing Metrics
        $price_per_kg_rwf = isset($_POST['price_per_kg_rwf']) && $_POST['price_per_kg_rwf'] !== '' ? (float)$_POST['price_per_kg_rwf'] : null;
        $purchase_value_rwf = isset($_POST['purchase_value_rwf']) && $_POST['purchase_value_rwf'] !== '' ? (float)$_POST['purchase_value_rwf'] : null;
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : null;
        $purchase_value_usd = isset($_POST['purchase_value_usd']) && $_POST['purchase_value_usd'] !== '' ? (float)$_POST['purchase_value_usd'] : null;
        $net_paid_supplier_usd = isset($_POST['net_paid_supplier_usd']) && $_POST['net_paid_supplier_usd'] !== '' ? (float)$_POST['net_paid_supplier_usd'] : null;
        $charges_per_kg = isset($_POST['charges_per_kg']) && $_POST['charges_per_kg'] !== '' ? (float)$_POST['charges_per_kg'] : null;
        $price_per_ta_unit = isset($_POST['price_per_ta_unit']) && $_POST['price_per_ta_unit'] !== '' ? (float)$_POST['price_per_ta_unit'] : null;
        $price_per_kg_usd = isset($_POST['price_per_kg_usd']) && $_POST['price_per_kg_usd'] !== '' ? (float)$_POST['price_per_kg_usd'] : null;
        $lme_price = isset($_POST['lme_price']) && $_POST['lme_price'] !== '' ? (float)$_POST['lme_price'] : null;
        $tc_charges = isset($_POST['tc_charges']) && $_POST['tc_charges'] !== '' ? (float)$_POST['tc_charges'] : null;
        $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : null;
        $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : null;
        $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : null;
        $production_charges = isset($_POST['production_charges']) && $_POST['production_charges'] !== '' ? (float)$_POST['production_charges'] : null;
        
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

        // Elements data structure: elements[element_id] = grade_pct
        $elements_grades = isset($_POST['elements']) ? $_POST['elements'] : [];
        $primary_element_id = isset($_POST['primary_element_id']) ? (int)$_POST['primary_element_id'] : 0;
        $element_notes = isset($_POST['element_notes']) ? $_POST['element_notes'] : [];

        // Validation
        if (empty($purchase_no)) {
            // Generate purchase code
            $purchase_no = 'PUR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        }

        if ($lot_id <= 0 || $product_id <= 0 || $supplier_id <= 0 || $warehouse_id <= 0 || $quantity_kg <= 0) {
            sendResponse(false, 'Lot, Product, Supplier, Warehouse, and Quantity (greater than 0) are required.');
        }

        // Check if purchase number already exists
        $pNoEsc = mysqli_real_escape_string($conn, $purchase_no);
        $chkCode = mysqli_query($conn, "SELECT id FROM purchasing WHERE purchase_no = '$pNoEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A purchase with this reference number already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            $delNoEsc = $delivery_no !== null ? "'" . mysqli_real_escape_string($conn, $delivery_no) . "'" : "NULL";
            $invEsc = $inventory_code !== null ? "'" . mysqli_real_escape_string($conn, $inventory_code) . "'" : "NULL";
            $delDateEsc = $delivery_date !== null ? "'" . mysqli_real_escape_string($conn, $delivery_date) . "'" : "NULL";
            $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL";
            $statusEsc = mysqli_real_escape_string($conn, $status);

            // Nullable decimals helpers
            $p_kg_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 'NULL';
            $p_val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 'NULL';
            $ex_rate = $exchange_rate !== null ? $exchange_rate : 'NULL';
            $p_val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 'NULL';
            $n_paid = $net_paid_supplier_usd !== null ? $net_paid_supplier_usd : 'NULL';
            $chg_kg = $charges_per_kg !== null ? $charges_per_kg : 'NULL';
            $p_ta = $price_per_ta_unit !== null ? $price_per_ta_unit : 'NULL';
            $p_kg_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 'NULL';
            $lme = $lme_price !== null ? $lme_price : 'NULL';
            $tc = $tc_charges !== null ? $tc_charges : 'NULL';
            $t_rra = $tax_rra !== null ? $tax_rra : 'NULL';
            $t_rma = $tax_rma !== null ? $tax_rma : 'NULL';
            $t_inko = $tax_inkomane !== null ? $tax_inkomane : 'NULL';
            $prod_chg = $production_charges !== null ? $production_charges : 'NULL';

            // Insert into purchasing
            $insertPurch = "INSERT INTO purchasing (
                purchase_no, delivery_no, inventory_code, delivery_date, purchase_date,
                lot_id, product_id, supplier_id, warehouse_id, quantity_kg, uom_id,
                price_per_kg_rwf, purchase_value_rwf, exchange_rate, purchase_value_usd,
                net_paid_supplier_usd, charges_per_kg, price_per_ta_unit, price_per_kg_usd,
                lme_price, tc_charges, tax_rra, tax_rma, tax_inkomane, production_charges,
                status, notes, created_by
            ) VALUES (
                '$pNoEsc', $delNoEsc, $invEsc, $delDateEsc, '$purchase_date',
                $lot_id, $product_id, $supplier_id, $warehouse_id, $quantity_kg, $uom_id,
                $p_kg_rwf, $p_val_rwf, $ex_rate, $p_val_usd,
                $n_paid, $chg_kg, $p_ta, $p_kg_usd,
                $lme, $tc, $t_rra, $t_rma, $t_inko, $prod_chg,
                '$statusEsc', $notesEsc, $userId
            )";

            if (!mysqli_query($conn, $insertPurch)) {
                throw new Exception("Failed to insert purchasing record: " . mysqli_error($conn));
            }

            $purchasingId = mysqli_insert_id($conn);

            // Insert into purchase_items
            $itemUnitPrice = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
            $itemAmount = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;

            $insertItem = "INSERT INTO purchase_items (
                purchase_id, lot_id, product_id, quantity, unit_price, amount
            ) VALUES (
                $purchasingId, $lot_id, $product_id, $quantity_kg, $itemUnitPrice, $itemAmount
            )";

            if (!mysqli_query($conn, $insertItem)) {
                throw new Exception("Failed to insert purchase item: " . mysqli_error($conn));
            }

            // Insert grades & sync product composition
            // First clear and rebuild composition elements if they are updated
            if (!empty($elements_grades)) {
                // Delete existing composition to sync
                mysqli_query($conn, "DELETE FROM product_element_composition WHERE product_id = $product_id");

                $displayOrder = 0;
                foreach ($elements_grades as $elementId => $gradePct) {
                    $elementId = (int)$elementId;
                    $gradePctVal = !empty($gradePct) ? (float)$gradePct : 0.0;
                    $gradeNotesVal = isset($element_notes[$elementId]) ? mysqli_real_escape_string($conn, $element_notes[$elementId]) : '';
                    $isPrimary = ($elementId === $primary_element_id) ? 1 : 0;

                    // Insert grade
                    $insertGrade = "INSERT INTO purchasing_element_grade (
                        purchasing_id, product_element_id, grade_pct, notes
                    ) VALUES (
                        $purchasingId, $elementId, $gradePctVal, '$gradeNotesVal'
                    )";
                    if (!mysqli_query($conn, $insertGrade)) {
                        throw new Exception("Failed to save grade for element $elementId: " . mysqli_error($conn));
                    }

                    // Rebuild composition
                    $insertComp = "INSERT INTO product_element_composition (
                        product_id, product_element_id, is_primary_grade, display_order
                    ) VALUES (
                        $product_id, $elementId, $isPrimary, $displayOrder
                    )";
                    if (!mysqli_query($conn, $insertComp)) {
                        throw new Exception("Failed to save composition for element $elementId: " . mysqli_error($conn));
                    }
                    $displayOrder++;
                }
            }

            // Update Stock and create movement if received
            if ($statusEsc === 'received') {
                $val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;
                $val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 0.0;
                
                if (!updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg, $val_usd, $val_rwf)) {
                    throw new Exception("Failed to update inventory stock.");
                }

                // Create Stock Movement
                $unit_cost_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
                $unit_cost_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 0.0;

                $insertMovement = "INSERT INTO stock_movement (
                    movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                    unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                    reference_type, reference_id, movement_date, notes, created_by
                ) VALUES (
                    'PURCHASE_IN', $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg,
                    $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                    'purchasing', $purchasingId, '$purchase_date', $notesEsc, $userId
                )";

                if (!mysqli_query($conn, $insertMovement)) {
                    throw new Exception("Failed to create stock movement: " . mysqli_error($conn));
                }
            }

            // Audit Log
            $newValues = [
                'id' => $purchasingId,
                'purchase_no' => $purchase_no,
                'product_id' => $product_id,
                'quantity_kg' => $quantity_kg,
                'purchase_value_usd' => $purchase_value_usd
            ];
            logAudit($conn, 'CREATE', 'purchasing', $purchase_no, "Recorded mining purchase: $purchase_no", null, $newValues);
            
            $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
            mysqli_commit($conn);
            sendResponse(true, "Purchase record '$purchase_no' created successfully.", $newValues);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to create purchase: ' . $e->getMessage());
        }
        break;

    case 'update':
        if (!hasPermission($conn, $userId, 'edit_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to edit purchases.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Valid purchase ID is required.');
        }

        // Fetch current purchase values
        $fetchQuery = "SELECT * FROM purchasing WHERE id = $id LIMIT 1";
        $fetchResult = mysqli_query($conn, $fetchQuery);
        if (!$fetchResult || mysqli_num_rows($fetchResult) === 0) {
            sendResponse(false, 'Purchase record not found.');
        }
        $oldValues = mysqli_fetch_assoc($fetchResult);

        $purchase_date = isset($_POST['purchase_date']) && !empty($_POST['purchase_date']) ? trim($_POST['purchase_date']) : date('Y-m-d');
        $delivery_date = isset($_POST['delivery_date']) && !empty($_POST['delivery_date']) ? trim($_POST['delivery_date']) : null;
        $purchase_no = isset($_POST['purchase_no']) ? trim($_POST['purchase_no']) : $oldValues['purchase_no'];
        $delivery_no = isset($_POST['delivery_no']) ? trim($_POST['delivery_no']) : null;
        $inventory_code = isset($_POST['inventory_code']) ? trim($_POST['inventory_code']) : null;
        
        $lot_id = isset($_POST['lot_id']) ? (int)$_POST['lot_id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $warehouse_id = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
        $quantity_kg = isset($_POST['quantity_kg']) ? (float)$_POST['quantity_kg'] : 0.0;
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;

        // Financial/Pricing Metrics
        $price_per_kg_rwf = isset($_POST['price_per_kg_rwf']) && $_POST['price_per_kg_rwf'] !== '' ? (float)$_POST['price_per_kg_rwf'] : null;
        $purchase_value_rwf = isset($_POST['purchase_value_rwf']) && $_POST['purchase_value_rwf'] !== '' ? (float)$_POST['purchase_value_rwf'] : null;
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : null;
        $purchase_value_usd = isset($_POST['purchase_value_usd']) && $_POST['purchase_value_usd'] !== '' ? (float)$_POST['purchase_value_usd'] : null;
        $net_paid_supplier_usd = isset($_POST['net_paid_supplier_usd']) && $_POST['net_paid_supplier_usd'] !== '' ? (float)$_POST['net_paid_supplier_usd'] : null;
        $charges_per_kg = isset($_POST['charges_per_kg']) && $_POST['charges_per_kg'] !== '' ? (float)$_POST['charges_per_kg'] : null;
        $price_per_ta_unit = isset($_POST['price_per_ta_unit']) && $_POST['price_per_ta_unit'] !== '' ? (float)$_POST['price_per_ta_unit'] : null;
        $price_per_kg_usd = isset($_POST['price_per_kg_usd']) && $_POST['price_per_kg_usd'] !== '' ? (float)$_POST['price_per_kg_usd'] : null;
        $lme_price = isset($_POST['lme_price']) && $_POST['lme_price'] !== '' ? (float)$_POST['lme_price'] : null;
        $tc_charges = isset($_POST['tc_charges']) && $_POST['tc_charges'] !== '' ? (float)$_POST['tc_charges'] : null;
        $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : null;
        $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : null;
        $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : null;
        $production_charges = isset($_POST['production_charges']) && $_POST['production_charges'] !== '' ? (float)$_POST['production_charges'] : null;
        
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

        $elements_grades = isset($_POST['elements']) ? $_POST['elements'] : [];
        $primary_element_id = isset($_POST['primary_element_id']) ? (int)$_POST['primary_element_id'] : 0;
        $element_notes = isset($_POST['element_notes']) ? $_POST['element_notes'] : [];

        if ($lot_id <= 0 || $product_id <= 0 || $supplier_id <= 0 || $warehouse_id <= 0 || $quantity_kg <= 0) {
            sendResponse(false, 'Lot, Product, Supplier, Warehouse, and Quantity (greater than 0) are required.');
        }

        // Check reference code uniqueness (excluding current record)
        $pNoEsc = mysqli_real_escape_string($conn, $purchase_no);
        $chkCode = mysqli_query($conn, "SELECT id FROM purchasing WHERE purchase_no = '$pNoEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A purchase with this reference number already exists.');
        }

        mysqli_begin_transaction($conn);

        try {
            // 1. Revert inventory stock of old values if it was received
            if ($oldValues['status'] === 'received') {
                $old_qty = (float)$oldValues['quantity_kg'];
                $old_val_usd = (float)$oldValues['purchase_value_usd'];
                $old_val_rwf = (float)$oldValues['purchase_value_rwf'];
                if (!updateStock($conn, $oldValues['warehouse_id'], $oldValues['product_id'], $oldValues['lot_id'], $oldValues['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf)) {
                    throw new Exception("Failed to adjust inventory stock.");
                }

                // 2. Delete old stock movement
                mysqli_query($conn, "DELETE FROM stock_movement WHERE reference_type = 'purchasing' AND reference_id = $id");
            }

            // 3. Update purchasing table
            $delNoEsc = $delivery_no !== null ? "'" . mysqli_real_escape_string($conn, $delivery_no) . "'" : "NULL";
            $invEsc = $inventory_code !== null ? "'" . mysqli_real_escape_string($conn, $inventory_code) . "'" : "NULL";
            $delDateEsc = $delivery_date !== null ? "'" . mysqli_real_escape_string($conn, $delivery_date) . "'" : "NULL";
            $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL";
            $statusEsc = mysqli_real_escape_string($conn, $status);

            $p_kg_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 'NULL';
            $p_val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 'NULL';
            $ex_rate = $exchange_rate !== null ? $exchange_rate : 'NULL';
            $p_val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 'NULL';
            $n_paid = $net_paid_supplier_usd !== null ? $net_paid_supplier_usd : 'NULL';
            $chg_kg = $charges_per_kg !== null ? $charges_per_kg : 'NULL';
            $p_ta = $price_per_ta_unit !== null ? $price_per_ta_unit : 'NULL';
            $p_kg_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 'NULL';
            $lme = $lme_price !== null ? $lme_price : 'NULL';
            $tc = $tc_charges !== null ? $tc_charges : 'NULL';
            $t_rra = $tax_rra !== null ? $tax_rra : 'NULL';
            $t_rma = $tax_rma !== null ? $tax_rma : 'NULL';
            $t_inko = $tax_inkomane !== null ? $tax_inkomane : 'NULL';
            $prod_chg = $production_charges !== null ? $production_charges : 'NULL';

            $updatePurch = "UPDATE purchasing SET
                purchase_no = '$pNoEsc',
                delivery_no = $delNoEsc,
                inventory_code = $invEsc,
                delivery_date = $delDateEsc,
                purchase_date = '$purchase_date',
                lot_id = $lot_id,
                product_id = $product_id,
                supplier_id = $supplier_id,
                warehouse_id = $warehouse_id,
                quantity_kg = $quantity_kg,
                uom_id = $uom_id,
                price_per_kg_rwf = $p_kg_rwf,
                purchase_value_rwf = $p_val_rwf,
                exchange_rate = $ex_rate,
                purchase_value_usd = $p_val_usd,
                net_paid_supplier_usd = $n_paid,
                charges_per_kg = $chg_kg,
                price_per_ta_unit = $p_ta,
                price_per_kg_usd = $p_kg_usd,
                lme_price = $lme,
                tc_charges = $tc,
                tax_rra = $t_rra,
                tax_rma = $t_rma,
                tax_inkomane = $t_inko,
                production_charges = $prod_chg,
                status = '$statusEsc',
                notes = $notesEsc,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = $id";

            if (!mysqli_query($conn, $updatePurch)) {
                throw new Exception("Failed to update purchasing record: " . mysqli_error($conn));
            }

            // 4. Update purchase_items
            $itemUnitPrice = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
            $itemAmount = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;

            $updateItem = "UPDATE purchase_items SET
                lot_id = $lot_id,
                product_id = $product_id,
                quantity = $quantity_kg,
                unit_price = $itemUnitPrice,
                amount = $itemAmount
                WHERE purchase_id = $id";

            if (!mysqli_query($conn, $updateItem)) {
                throw new Exception("Failed to update purchase item: " . mysqli_error($conn));
            }

            // 5. Delete old grades & rebuild
            mysqli_query($conn, "DELETE FROM purchasing_element_grade WHERE purchasing_id = $id");

            if (!empty($elements_grades)) {
                // Sync compositions
                mysqli_query($conn, "DELETE FROM product_element_composition WHERE product_id = $product_id");

                $displayOrder = 0;
                foreach ($elements_grades as $elementId => $gradePct) {
                    $elementId = (int)$elementId;
                    $gradePctVal = !empty($gradePct) ? (float)$gradePct : 0.0;
                    $gradeNotesVal = isset($element_notes[$elementId]) ? mysqli_real_escape_string($conn, $element_notes[$elementId]) : '';
                    $isPrimary = ($elementId === $primary_element_id) ? 1 : 0;

                    // Insert grade
                    $insertGrade = "INSERT INTO purchasing_element_grade (
                        purchasing_id, product_element_id, grade_pct, notes
                    ) VALUES (
                        $id, $elementId, $gradePctVal, '$gradeNotesVal'
                    )";
                    if (!mysqli_query($conn, $insertGrade)) {
                        throw new Exception("Failed to save grade for element $elementId: " . mysqli_error($conn));
                    }

                    // Rebuild composition
                    $insertComp = "INSERT INTO product_element_composition (
                        product_id, product_element_id, is_primary_grade, display_order
                    ) VALUES (
                        $product_id, $elementId, $isPrimary, $displayOrder
                    )";
                    if (!mysqli_query($conn, $insertComp)) {
                        throw new Exception("Failed to save composition for element $elementId: " . mysqli_error($conn));
                    }
                    $displayOrder++;
                }
            }

            // 6. Update Stock with new values if status is received
            if ($statusEsc === 'received') {
                $val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;
                $val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 0.0;
                
                if (!updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg, $val_usd, $val_rwf)) {
                    throw new Exception("Failed to update inventory stock.");
                }

                // 7. Create new Stock Movement
                $unit_cost_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
                $unit_cost_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 0.0;

                $insertMovement = "INSERT INTO stock_movement (
                    movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                    unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                    reference_type, reference_id, movement_date, notes, created_by
                ) VALUES (
                    'PURCHASE_IN', $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg,
                    $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                    'purchasing', $id, '$purchase_date', $notesEsc, $userId
                )";

                if (!mysqli_query($conn, $insertMovement)) {
                    throw new Exception("Failed to create stock movement: " . mysqli_error($conn));
                }
            }

            // Audit log
            $newValues = [
                'id' => $id,
                'purchase_no' => $purchase_no,
                'product_id' => $product_id,
                'quantity_kg' => $quantity_kg,
                'purchase_value_usd' => $purchase_value_usd
            ];
            logAudit($conn, 'UPDATE', 'purchasing', $purchase_no, "Updated purchase: $purchase_no", $oldValues, $newValues);
            
            $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
            mysqli_commit($conn);
            sendResponse(true, "Purchase record '$purchase_no' updated successfully.", $newValues);

        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update purchase: ' . $e->getMessage());
        }
        break;

    case 'update_status':
        if (!hasPermission($conn, $userId, 'edit_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to change purchase status.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

        if ($id <= 0) {
            sendResponse(false, 'Valid purchase ID is required.');
        }

        $allowedStatuses = ['pending', 'confirmed', 'received', 'cancelled'];
        if (!in_array($newStatus, $allowedStatuses)) {
            sendResponse(false, 'Invalid status value. Allowed: ' . implode(', ', $allowedStatuses));
        }

        // Fetch current record (all fields to allow stock reversion or inclusion)
        $chkPurch = mysqli_query($conn, "SELECT * FROM purchasing WHERE id = $id LIMIT 1");
        if (!$chkPurch || mysqli_num_rows($chkPurch) === 0) {
            sendResponse(false, 'Purchase record not found.');
        }
        $currentRow = mysqli_fetch_assoc($chkPurch);
        $oldStatus = $currentRow['status'];
        $purchase_no = $currentRow['purchase_no'];

        if ($oldStatus === $newStatus) {
            sendResponse(true, 'Status is already set to ' . strtoupper($newStatus) . '.');
        }

        mysqli_begin_transaction($conn);

        try {
            $statusEsc = mysqli_real_escape_string($conn, $newStatus);

            // Trigger stock additions/removals based on transition
            if ($oldStatus !== 'received' && $newStatus === 'received') {
                // Ensure duplicate check: check if movement already exists
                $checkMvt = mysqli_query($conn, "SELECT id FROM stock_movement WHERE reference_type = 'purchasing' AND reference_id = $id LIMIT 1");
                if (!$checkMvt || mysqli_num_rows($checkMvt) === 0) {
                    $quantity_kg = (float)$currentRow['quantity_kg'];
                    $val_usd = $currentRow['purchase_value_usd'] !== null ? (float)$currentRow['purchase_value_usd'] : 0.0;
                    $val_rwf = $currentRow['purchase_value_rwf'] !== null ? (float)$currentRow['purchase_value_rwf'] : 0.0;
                    
                    if (!updateStock($conn, $currentRow['warehouse_id'], $currentRow['product_id'], $currentRow['lot_id'], $currentRow['uom_id'], $quantity_kg, $val_usd, $val_rwf)) {
                        throw new Exception("Failed to update inventory stock.");
                    }

                    // Create Stock Movement
                    $unit_cost_usd = $currentRow['price_per_kg_usd'] !== null ? (float)$currentRow['price_per_kg_usd'] : 0.0;
                    $unit_cost_rwf = $currentRow['price_per_kg_rwf'] !== null ? (float)$currentRow['price_per_kg_rwf'] : 0.0;
                    $notesEsc = $currentRow['notes'] !== null ? "'" . mysqli_real_escape_string($conn, $currentRow['notes']) . "'" : "NULL";
                    $purchase_date = $currentRow['purchase_date'];

                    $insertMovement = "INSERT INTO stock_movement (
                        movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                        unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                        reference_type, reference_id, movement_date, notes, created_by
                    ) VALUES (
                        'PURCHASE_IN', {$currentRow['warehouse_id']}, {$currentRow['product_id']}, {$currentRow['lot_id']}, {$currentRow['uom_id']}, $quantity_kg,
                        $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                        'purchasing', $id, '$purchase_date', $notesEsc, $userId
                    )";

                    if (!mysqli_query($conn, $insertMovement)) {
                        throw new Exception("Failed to create stock movement: " . mysqli_error($conn));
                    }
                }
            } else if ($oldStatus === 'received' && $newStatus !== 'received') {
                // Revert inventory stock
                $old_qty = (float)$currentRow['quantity_kg'];
                $old_val_usd = (float)$currentRow['purchase_value_usd'];
                $old_val_rwf = (float)$currentRow['purchase_value_rwf'];
                if (!updateStock($conn, $currentRow['warehouse_id'], $currentRow['product_id'], $currentRow['lot_id'], $currentRow['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf)) {
                    throw new Exception("Failed to adjust inventory stock.");
                }

                // Delete stock movement
                mysqli_query($conn, "DELETE FROM stock_movement WHERE reference_type = 'purchasing' AND reference_id = $id");
            }

            // Update status in the database
            $updateQuery = "UPDATE purchasing SET status = '$statusEsc', updated_at = CURRENT_TIMESTAMP WHERE id = $id";
            if (!mysqli_query($conn, $updateQuery)) {
                throw new Exception("Failed to update status in database: " . mysqli_error($conn));
            }

            logAudit($conn, 'UPDATE', 'purchasing', $purchase_no, "Status changed from $oldStatus to $newStatus", ['status' => $oldStatus], ['status' => $newStatus]);
            $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
            mysqli_commit($conn);
            sendResponse(true, "Purchase '$purchase_no' status changed to " . strtoupper($newStatus) . ".");

        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to update status: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete purchases.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkPurch = mysqli_query($conn, "SELECT * FROM purchasing WHERE id = $id LIMIT 1");
        if (!$chkPurch || mysqli_num_rows($chkPurch) === 0) {
            sendResponse(false, 'Purchase not found.');
        }
        $oldValues = mysqli_fetch_assoc($chkPurch);
        $purchase_no = $oldValues['purchase_no'];

        mysqli_begin_transaction($conn);

        try {
            // 1. Subtract values from stock if it was received
            if ($oldValues['status'] === 'received') {
                $old_qty = (float)$oldValues['quantity_kg'];
                $old_val_usd = (float)$oldValues['purchase_value_usd'];
                $old_val_rwf = (float)$oldValues['purchase_value_rwf'];
                if (!updateStock($conn, $oldValues['warehouse_id'], $oldValues['product_id'], $oldValues['lot_id'], $oldValues['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf)) {
                    throw new Exception("Failed to adjust inventory stock.");
                }

                // 2. Delete stock movement
                mysqli_query($conn, "DELETE FROM stock_movement WHERE reference_type = 'purchasing' AND reference_id = $id");
            }

            // 3. Delete grades
            mysqli_query($conn, "DELETE FROM purchasing_element_grade WHERE purchasing_id = $id");

            // 4. Delete purchase items
            mysqli_query($conn, "DELETE FROM purchase_items WHERE purchase_id = $id");

            // 5. Delete purchasing row
            if (mysqli_query($conn, "DELETE FROM purchasing WHERE id = $id")) {
                logAudit($conn, 'DELETE', 'purchasing', $purchase_no, "Deleted purchase: $purchase_no", $oldValues);
                $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Purchase record '$purchase_no' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete purchase: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
