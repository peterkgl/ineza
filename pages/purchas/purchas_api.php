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



function getSetting($conn, $key, $default = '') {
    $key = mysqli_real_escape_string($conn, $key);
    $q = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$key' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        return mysqli_fetch_assoc($q)['setting_value'];
    }
    return $default;
}

function formula_price_per_kg_usd($conn, $lme_price, $tc_charges, $price_per_ta_unit, $grade_pct, $manual_price_usd, $product_type, $fluc = 0.0) {
    if ($product_type === 'tin') {
        $lme_paid = $lme_price - $fluc;
        if ($lme_paid > 0) {
            $grade_fraction = $grade_pct / 100.0;
            return (($lme_paid * $grade_fraction) - $tc_charges) / 1000.0;
        } else {
            return $manual_price_usd;
        }
    } elseif ($product_type === 'wolframite') {
        $lme_paid = $lme_price - $fluc;
        if ($lme_paid > 0) {
            $grade_fraction = $grade_pct / 100.0;
            return (($lme_paid * $grade_fraction) - $tc_charges) / 10.0;
        } else {
            return $manual_price_usd;
        }
    } else { // tantalum / coltan
        if ($price_per_ta_unit > 0) {
            return $grade_pct * $price_per_ta_unit;
        } else {
            return $manual_price_usd;
        }
    }
}

function formula_price_per_kg_rwf($price_per_kg_usd, $exchange_rate) {
    return $price_per_kg_usd * $exchange_rate;
}

function formula_purchase_value_usd($price_per_kg_usd, $quantity_kg) {
    return $price_per_kg_usd * $quantity_kg;
}

function formula_purchase_value_rwf($quantity_kg, $price_per_kg_rwf) {
    return $quantity_kg * $price_per_kg_rwf;
}

function formula_tax_rra($conn, $lme_price, $grade_pct, $quantity_kg, $purchase_value_usd, $product_type, $fluc = 0.0) {
    $rra_rate = (float)getSetting($conn, 'tax_rate_rra', '3.0') / 100.0;
    
    if ($product_type === 'tin') {
        $lme_paid = $lme_price - $fluc;
        if ($lme_paid > 0) {
            $grade_fraction = $grade_pct / 100.0;
            $tax = (($lme_paid * $grade_fraction) - 800.0) / 1000.0 * $quantity_kg * $rra_rate;
            return max(0.0, $tax);
        } else {
            return $purchase_value_usd * $rra_rate;
        }
    } elseif ($product_type === 'wolframite') {
        if ($lme_price > 0) {
            $grade_fraction = $grade_pct / 100.0;
            // Wolframite RRA tax uses Full LME (lme_price), not LME Paid
            return $quantity_kg * $grade_fraction * ($lme_price / 10.0) * $rra_rate;
        } else {
            return $purchase_value_usd * $rra_rate;
        }
    } else { // tantalum / coltan
        return $purchase_value_usd * $rra_rate;
    }
}

function formula_tax_rma($conn, $quantity_kg, $exchange_rate, $product_type) {
    if ($exchange_rate <= 0) return 0.0;
    
    if ($product_type === 'tin') {
        $rate_rwf = (float)getSetting($conn, 'tax_rate_rma_tin', '50.0');
    } elseif ($product_type === 'wolframite') {
        $rate_rwf = (float)getSetting($conn, 'tax_rate_rma_wolframite', '50.0');
    } else { // tantalum / coltan
        $rate_rwf = (float)getSetting($conn, 'tax_rate_rma_coltan', '125.0');
    }
    
    return ($quantity_kg * $rate_rwf) / $exchange_rate;
}

function formula_tax_inkomane($conn, $quantity_kg, $exchange_rate, $product_type) {
    if ($exchange_rate <= 0) return 0.0;
    
    if ($product_type === 'tin') {
        $rate_rwf = (float)getSetting($conn, 'tax_rate_inkomane_tin', '20.0');
    } elseif ($product_type === 'wolframite') {
        $rate_rwf = (float)getSetting($conn, 'tax_rate_inkomane_wolframite', '20.0');
    } else { // tantalum / coltan
        $rate_rwf = (float)getSetting($conn, 'tax_rate_inkomane_coltan', '40.0');
    }
    
    return ($quantity_kg * $rate_rwf) / $exchange_rate;
}

function formula_production_charges($quantity_kg, $production_charges_per_kg) {
    return $quantity_kg * $production_charges_per_kg;
}

function formula_net_paid_supplier($purchase_value_usd, $tax_rra, $tax_rma, $tax_inkomane, $production_charges) {
    return $purchase_value_usd - $tax_rra - $tax_rma - $tax_inkomane - $production_charges;
}

/**
 * Calculations for Purchase Metrics matching the Excel sheet
 */
function calculatePurchaseMetrics($conn, $quantity_kg, $exchange_rate, $lme_price, $tc_charges, $production_charges_per_kg, $price_per_ta_unit, $grade_pct, $manual_price_usd = 0.0, $product_type = 'tin', $fluc = 0.0) {
    $quantity_kg = (float)$quantity_kg;
    $exchange_rate = (float)$exchange_rate;
    $lme_price = (float)$lme_price;
    $tc_charges = (float)$tc_charges;
    $production_charges_per_kg = (float)$production_charges_per_kg;
    $price_per_ta_unit = (float)$price_per_ta_unit;
    $grade_pct = (float)$grade_pct;
    $manual_price_usd = (float)$manual_price_usd;
    $fluc = (float)$fluc;

    $price_usd = formula_price_per_kg_usd($conn, $lme_price, $tc_charges, $price_per_ta_unit, $grade_pct, $manual_price_usd, $product_type, $fluc);
    if ($manual_price_usd > 0.0) {
        $price_usd = $manual_price_usd;
    }
    $price_rwf = formula_price_per_kg_rwf($price_usd, $exchange_rate);
    $val_usd = formula_purchase_value_usd($price_usd, $quantity_kg);
    $val_rwf = formula_purchase_value_rwf($quantity_kg, $price_rwf);
    
    $tax_rra = formula_tax_rra($conn, $lme_price, $grade_pct, $quantity_kg, $val_usd, $product_type, $fluc);
    $tax_rma = formula_tax_rma($conn, $quantity_kg, $exchange_rate, $product_type);
    $tax_inkomane = formula_tax_inkomane($conn, $quantity_kg, $exchange_rate, $product_type);
    $prod_charges = formula_production_charges($quantity_kg, $production_charges_per_kg);
    
    $net_supplier = formula_net_paid_supplier($val_usd, $tax_rra, $tax_rma, $tax_inkomane, $prod_charges);

    return [
        'price_per_kg_usd' => $price_usd,
        'price_per_kg_rwf' => $price_rwf,
        'purchase_value_usd' => $val_usd,
        'purchase_value_rwf' => $val_rwf,
        'tax_rra' => $tax_rra,
        'tax_rma' => $tax_rma,
        'tax_inkomane' => $tax_inkomane,
        'production_charges' => $prod_charges,
        'net_paid_supplier_usd' => $net_supplier,
        'lme_paid' => $lme_price - $fluc
    ];
}

/**
 * Automatically retrieves the supplier's payables account ID, or creates one if it doesn't exist.
 */
function getOrCreateSupplierAccount($conn, $supplierId) {
    $supplierId = (int)$supplierId;
    if ($supplierId <= 0) return null;
    
    $res = mysqli_query($conn, "SELECT name, payables_account_id FROM suppliers WHERE id = $supplierId LIMIT 1");
    if (!$res || mysqli_num_rows($res) === 0) {
        return null;
    }
    
    $supplier = mysqli_fetch_assoc($res);
    if ($supplier['payables_account_id'] !== null && (int)$supplier['payables_account_id'] > 0) {
        return (int)$supplier['payables_account_id'];
    }
    
    // Auto-create account for supplier
    $name = $supplier['name'];
    $nameEsc = mysqli_real_escape_string($conn, $name);
    
    // Get Accounts Payable type ID (3)
    $accountsPayableTypeId = 3;
    
    // Generate next account code in 2001-2999 range (under Liabilities)
    $rangeStart = 2000;
    $rangeEnd = 2999;
    
    $existingCodes = [];
    $queryTypes = "SELECT code FROM account_types WHERE code >= $rangeStart AND code <= $rangeEnd";
    $resTypes = mysqli_query($conn, $queryTypes);
    if ($resTypes) {
        while ($row = mysqli_fetch_assoc($resTypes)) {
            $existingCodes[] = (int)$row['code'];
        }
    }
    $queryAccounts = "SELECT account_code FROM accounts WHERE account_code >= $rangeStart AND account_code <= $rangeEnd";
    $resAccounts = mysqli_query($conn, $queryAccounts);
    if ($resAccounts) {
        while ($row = mysqli_fetch_assoc($resAccounts)) {
            $existingCodes[] = (int)$row['account_code'];
        }
    }
    $existingCodes = array_unique($existingCodes);
    sort($existingCodes);
    
    $nextCode = $rangeStart + 1;
    while (in_array($nextCode, $existingCodes)) {
        $nextCode++;
        if ($nextCode > $rangeEnd) {
            return null;
        }
    }
    
    $accountNameEsc = mysqli_real_escape_string($conn, $name . ' - Accounts Payable');
    $insertAccount = "INSERT INTO accounts (account_type_id, account_code, account_name, is_active, description)
                      VALUES ($accountsPayableTypeId, '$nextCode', '$accountNameEsc', 1, 'Auto-created account for supplier: $nameEsc')";
    if (mysqli_query($conn, $insertAccount)) {
        $accountId = mysqli_insert_id($conn);
        // Update supplier
        mysqli_query($conn, "UPDATE suppliers SET payables_account_id = $accountId WHERE id = $supplierId");
        return $accountId;
    }
    
    return null;
}

// Helper to update stock values
function updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $qty_diff, $value_usd_diff, $value_rwf_diff, $purchase_currency_id = null, $purchase_amount_in_currency_diff = null, $exchange_rate = null, $converted_amount_diff = null) {
    $warehouse_id = (int)$warehouse_id;
    $product_id = (int)$product_id;
    $lot_id = (int)$lot_id;
    $uom_id = (int)$uom_id;
    
    $chk = mysqli_query($conn, "SELECT id, qty_purchased, qty_sold, qty_adjusted, total_value_usd, total_value_rwf, opening, closing, purchase_amount_in_currency, converted_amount FROM stock WHERE warehouse_id = $warehouse_id AND product_id = $product_id AND lot_id = $lot_id LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        $row = mysqli_fetch_assoc($chk);
        $stockId = $row['id'];
        $new_qty_purchased = (float)$row['qty_purchased'] + (float)$qty_diff;
        if ($new_qty_purchased < 0) $new_qty_purchased = 0;
        
        $new_qty_on_hand = $new_qty_purchased - (float)$row['qty_sold'] + (float)$row['qty_adjusted'];
        if ($new_qty_on_hand < 0) $new_qty_on_hand = 0;

        $new_val_usd = (float)$row['total_value_usd'] + (float)$value_usd_diff;
        $new_val_rwf = (float)$row['total_value_rwf'] + (float)$value_rwf_diff;
        if ($new_val_usd < 0) $new_val_usd = 0;
        if ($new_val_rwf < 0) $new_val_rwf = 0;
        
        $avg_cost_usd = $new_qty_on_hand > 0 ? $new_val_usd / $new_qty_on_hand : 0;
        $avg_cost_rwf = $new_qty_on_hand > 0 ? $new_val_rwf / $new_qty_on_hand : 0;
        
        $opening = (float)$row['opening'];
        $closing = $new_qty_on_hand;
        
        $update = "UPDATE stock SET 
                      qty_purchased = $new_qty_purchased, 
                      total_value_usd = $new_val_usd, 
                      total_value_rwf = $new_val_rwf, 
                      avg_cost_per_kg_usd = $avg_cost_usd, 
                      avg_cost_per_kg_rwf = $avg_cost_rwf,
                      closing = $closing";

        if ($purchase_currency_id !== null) {
            $update .= ", purchase_currency_id = " . (int)$purchase_currency_id;
        }
        if ($purchase_amount_in_currency_diff !== null) {
            $new_amt_curr = (float)$row['purchase_amount_in_currency'] + (float)$purchase_amount_in_currency_diff;
            if ($new_amt_curr < 0) $new_amt_curr = 0;
            $update .= ", purchase_amount_in_currency = " . $new_amt_curr;
        }
        if ($exchange_rate !== null) {
            $update .= ", exchange_rate = " . (float)$exchange_rate;
        }
        if ($converted_amount_diff !== null) {
            $new_conv_amt = (float)$row['converted_amount'] + (float)$converted_amount_diff;
            if ($new_conv_amt < 0) $new_conv_amt = 0;
            $update .= ", converted_amount = " . $new_conv_amt;
        }

        $update .= " WHERE id = $stockId";
        if (mysqli_query($conn, $update)) {
            return [
                'success' => true,
                'opening' => $opening,
                'closing' => $closing
            ];
        }
    } else {
        if ($qty_diff > 0) {
            $avg_cost_usd = $value_usd_diff / $qty_diff;
            $avg_cost_rwf = $value_rwf_diff / $qty_diff;
            
            $opening = 0.0000;
            $closing = $qty_diff;
            $today = date('Y-m-d');
            
            $currency_id_val = $purchase_currency_id !== null ? (int)$purchase_currency_id : 'NULL';
            $amt_in_curr_val = $purchase_amount_in_currency_diff !== null ? (float)$purchase_amount_in_currency_diff : 'NULL';
            $ex_rate_val = $exchange_rate !== null ? (float)$exchange_rate : 'NULL';
            $conv_amt_val = $converted_amount_diff !== null ? (float)$converted_amount_diff : 'NULL';

            $insert = "INSERT INTO stock (warehouse_id, product_id, lot_id, uom_id, qty_purchased, qty_sold, qty_adjusted, total_value_usd, total_value_rwf, avg_cost_per_kg_usd, avg_cost_per_kg_rwf, opening, closing, last_rolled_over_at, purchase_currency_id, purchase_amount_in_currency, exchange_rate, converted_amount) 
                       VALUES ($warehouse_id, $product_id, $lot_id, $uom_id, $qty_diff, 0, 0, $value_usd_diff, $value_rwf_diff, $avg_cost_usd, $avg_cost_rwf, $opening, $closing, '$today', $currency_id_val, $amt_in_curr_val, $ex_rate_val, $conv_amt_val)";
            if (mysqli_query($conn, $insert)) {
                return [
                    'success' => true,
                    'opening' => $opening,
                    'closing' => $closing
                ];
            }
        }
    }
    return false;
}

if (!defined('PREVENT_API_EXECUTION')) {
switch ($action) {
    case 'list':
        if (!hasPermission($conn, $userId, 'view_purchas')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view purchases.');
        }

        $query = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, w.warehouse_name, uom.code as uom_code,
                         a.account_code, a.account_name, cur.code as currency_code
                   FROM purchasing p
                   JOIN product pr ON p.product_id = pr.id
                   JOIN lots l ON p.lot_id = l.id
                   JOIN suppliers s ON p.supplier_id = s.id
                   JOIN warehouses w ON p.warehouse_id = w.id
                   LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
                   LEFT JOIN accounts a ON p.account_id = a.id
                   LEFT JOIN currencies cur ON p.purchase_currency_id = cur.id
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
                $row['purchase_currency_id'] = $row['purchase_currency_id'] !== null ? (int)$row['purchase_currency_id'] : null;
                $row['purchase_amount_in_currency'] = $row['purchase_amount_in_currency'] !== null ? (float)$row['purchase_amount_in_currency'] : null;
                $row['converted_amount'] = $row['converted_amount'] !== null ? (float)$row['converted_amount'] : null;
                $row['currency_code'] = $row['currency_code'] ?? 'USD';
                
                $paymentsQuery = "SELECT spa.id, sp.payment_date, sp.amount_currency, sp.amount_base, sp.exchange_rate, sp.status, spa.amount_allocated, cur.code as currency_code
                                  FROM supplier_payment_allocations spa
                                  JOIN supplier_payments sp ON spa.supplier_payment_id = sp.id
                                  LEFT JOIN currencies cur ON sp.currency_id = cur.id
                                  WHERE spa.purchase_id = $pId
                                  ORDER BY sp.payment_date ASC, sp.id ASC";
                $paymentsResult = mysqli_query($conn, $paymentsQuery);
                $paymentHistory = [];
                if ($paymentsResult) {
                    while ($payRow = mysqli_fetch_assoc($paymentsResult)) {
                        $paymentHistory[] = [
                            'id' => (int)$payRow['id'],
                            'payment_date' => $payRow['payment_date'],
                            'status' => $payRow['status'],
                            'currency_code' => $payRow['currency_code'] ?? 'USD',
                            'amount_currency' => (float)$payRow['amount_currency'],
                            'amount_base' => (float)$payRow['amount_base'],
                            'exchange_rate' => (float)$payRow['exchange_rate'],
                            'amount_allocated' => (float)$payRow['amount_allocated']
                        ];
                    }
                }

                $row['grades'] = $grades;
                $row['primary_element_id'] = $primaryElementId;
                $row['payment_history'] = $paymentHistory;
                
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

        // Load all active elements from product_element table
        // LEFT JOIN product_element_composition to check is_primary_grade and if it's default composition element
        $query = "SELECT pe.id, pe.element_code, pe.element_name, pe.symbol, 
                         COALESCE(pec.is_primary_grade, 0) as is_primary_grade,
                         IF(pec.id IS NOT NULL, 1, 0) as is_default_composition
                  FROM product_element pe
                  LEFT JOIN product_element_composition pec 
                    ON pe.id = pec.product_element_id AND pec.product_id = $productId
                  WHERE pe.is_active = 1
                  ORDER BY pe.element_code ASC";
        $result = mysqli_query($conn, $query);
        $elements = [];

        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $elements[] = [
                    'id' => (int)$row['id'],
                    'element_code' => $row['element_code'],
                    'element_name' => $row['element_name'],
                    'symbol' => $row['symbol'],
                    'is_primary_grade' => (int)$row['is_primary_grade'],
                    'is_default_composition' => (int)$row['is_default_composition']
                ];
            }
        }

        sendResponse(true, 'Product elements loaded.', $elements);
        break;

    case 'get_settings':
        $settingsKeys = [
            'tax_rate_rra',
            'tax_rate_rma_tin',
            'tax_rate_rma_coltan',
            'tax_rate_rma_wolframite',
            'tax_rate_inkomane_tin',
            'tax_rate_inkomane_coltan',
            'tax_rate_inkomane_wolframite'
        ];
        $settingsData = [];
        foreach ($settingsKeys as $key) {
            $settingsData[$key] = (float)getSetting($conn, $key, '0');
        }
        sendResponse(true, 'Settings loaded.', $settingsData);
        break;

    case 'calculate':
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $quantity_kg = isset($_GET['quantity_kg']) ? (float)$_GET['quantity_kg'] : 0.0;
        $exchange_rate = isset($_GET['exchange_rate']) ? (float)$_GET['exchange_rate'] : 1400.0;
        $lme_price = isset($_GET['lme_price']) ? (float)$_GET['lme_price'] : 0.0;
        $tc_charges = isset($_GET['tc_charges']) ? (float)$_GET['tc_charges'] : 0.0;
        $production_charges_per_kg = isset($_GET['production_charges_per_kg']) ? (float)$_GET['production_charges_per_kg'] : 0.0;
        $price_per_ta_unit = isset($_GET['price_per_ta_unit']) ? (float)$_GET['price_per_ta_unit'] : 0.0;
        $grade_pct = isset($_GET['grade_pct']) ? (float)$_GET['grade_pct'] : 0.0;
        $manual_price_usd = isset($_GET['price_per_kg_usd']) ? (float)$_GET['price_per_kg_usd'] : 0.0;
        $fluc = isset($_GET['fluc']) ? (float)$_GET['fluc'] : 0.0;

        $product_type = 'tin';
        if ($product_id > 0) {
            $prodQuery = mysqli_query($conn, "SELECT product_code, product_name FROM product WHERE id = $product_id LIMIT 1");
            if ($prodQuery && mysqli_num_rows($prodQuery) > 0) {
                $prod = mysqli_fetch_assoc($prodQuery);
                $p_code = strtoupper($prod['product_code']);
                $p_name = strtolower($prod['product_name']);
                if (strpos($p_name, 'coltan') !== false || strpos($p_name, 'tantalum') !== false || strpos($p_code, 'TA') !== false) {
                    $product_type = 'tantalum';
                } elseif (strpos($p_name, 'wolframite') !== false || strpos($p_name, 'tungsten') !== false || strpos($p_code, 'W') !== false) {
                    $product_type = 'wolframite';
                }
            }
        }

        $results = calculatePurchaseMetrics($conn, $quantity_kg, $exchange_rate, $lme_price, $tc_charges, $production_charges_per_kg, $price_per_ta_unit, $grade_pct, $manual_price_usd, $product_type, $fluc);
        sendResponse(true, 'Calculated values retrieved.', $results);
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
        $negociant = isset($_POST['negociant']) ? trim($_POST['negociant']) : null;
        
        $lot_id = isset($_POST['lot_id']) ? (int)$_POST['lot_id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $warehouse_id = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
        $quantity_kg = isset($_POST['quantity_kg']) ? (float)$_POST['quantity_kg'] : 0.0;
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;

        // Financial/Pricing Inputs
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : 1400.0;
        $charges_per_kg = isset($_POST['charges_per_kg']) && $_POST['charges_per_kg'] !== '' ? (float)$_POST['charges_per_kg'] : null;
        $production_charges_per_kg = isset($_POST['production_charges_per_kg']) && $_POST['production_charges_per_kg'] !== '' ? (float)$_POST['production_charges_per_kg'] : null;
        $price_per_ta_unit = isset($_POST['price_per_ta_unit']) && $_POST['price_per_ta_unit'] !== '' ? (float)$_POST['price_per_ta_unit'] : null;
        $lme_price = isset($_POST['lme_price']) && $_POST['lme_price'] !== '' ? (float)$_POST['lme_price'] : null;
        $tc_charges = isset($_POST['tc_charges']) && $_POST['tc_charges'] !== '' ? (float)$_POST['tc_charges'] : null;
        $fluc = isset($_POST['fluc']) && $_POST['fluc'] !== '' ? (float)$_POST['fluc'] : null;
        $lme_paid = isset($_POST['lme_paid']) && $_POST['lme_paid'] !== '' ? (float)$_POST['lme_paid'] : null;
        
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

        // Auto-create/retrieve supplier payables account
        $account_id = getOrCreateSupplierAccount($conn, $supplier_id);

        // Check if purchase number already exists
        $pNoEsc = mysqli_real_escape_string($conn, $purchase_no);
        $chkCode = mysqli_query($conn, "SELECT id FROM purchasing WHERE purchase_no = '$pNoEsc' LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A purchase with this reference number already exists.');
        }

        // Determine product type
        $product_type = 'tin';
        if ($product_id > 0) {
            $prodQuery = mysqli_query($conn, "SELECT product_code, product_name FROM product WHERE id = $product_id LIMIT 1");
            if ($prodQuery && mysqli_num_rows($prodQuery) > 0) {
                $prod = mysqli_fetch_assoc($prodQuery);
                $p_code = strtoupper($prod['product_code']);
                $p_name = strtolower($prod['product_name']);
                if (strpos($p_name, 'coltan') !== false || strpos($p_name, 'tantalum') !== false || strpos($p_code, 'TA') !== false) {
                    $product_type = 'tantalum';
                } elseif (strpos($p_name, 'wolframite') !== false || strpos($p_name, 'tungsten') !== false || strpos($p_code, 'W') !== false) {
                    $product_type = 'wolframite';
                }
            }
        }

        // Extract primary grade
        $grade_pct = 0.0;
        if ($primary_element_id > 0 && isset($elements_grades[$primary_element_id])) {
            $grade_pct = (float)$elements_grades[$primary_element_id];
        }

        // Perform server-side calculations based on pricing method
        $pricing_method = isset($_POST['pricing_method']) ? trim($_POST['pricing_method']) : 'lme';
        $manual_price_usd = isset($_POST['price_per_kg_usd']) && $_POST['price_per_kg_usd'] !== '' ? (float)$_POST['price_per_kg_usd'] : 0.0;

        if ($pricing_method === 'manual') {
            $price_per_kg_usd = $manual_price_usd;
            $price_per_kg_rwf = $price_per_kg_usd * $exchange_rate;
            $purchase_value_usd = $price_per_kg_usd * $quantity_kg;
            $purchase_value_rwf = $price_per_kg_rwf * $quantity_kg;
            $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : 0.0;
            $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : 0.0;
            $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : 0.0;
            $production_charges = $quantity_kg * $production_charges_per_kg;
            $net_paid_supplier_usd = $purchase_value_usd - $tax_rra - $tax_rma - $tax_inkomane - $production_charges;
        } else {
            if ($lme_price !== null && $fluc !== null) {
                $lme_paid = $lme_price - $fluc;
            }
            $metrics = calculatePurchaseMetrics($conn, $quantity_kg, $exchange_rate, $lme_price, $tc_charges, $production_charges_per_kg, $price_per_ta_unit, $grade_pct, $manual_price_usd, $product_type, $fluc);
            $price_per_kg_usd = $metrics['price_per_kg_usd'];
            $price_per_kg_rwf = $metrics['price_per_kg_rwf'];
            $purchase_value_usd = $metrics['purchase_value_usd'];
            $purchase_value_rwf = $metrics['purchase_value_rwf'];
            $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : $metrics['tax_rra'];
            $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : $metrics['tax_rma'];
            $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : $metrics['tax_inkomane'];
            $production_charges = $metrics['production_charges'];
            $net_paid_supplier_usd = $purchase_value_usd - $tax_rra - $tax_rma - $tax_inkomane - $production_charges;
            $lme_paid = $metrics['lme_paid'];
        }

        mysqli_begin_transaction($conn);

        try {
            $purchase_currency_id = isset($_POST['purchase_currency_id']) ? (int)$_POST['purchase_currency_id'] : 2; // Default to USD (2)
            
            // Find currency code
            $currencyCode = 'USD';
            $currQuery = mysqli_query($conn, "SELECT code FROM currencies WHERE id = $purchase_currency_id LIMIT 1");
            if ($currQuery && mysqli_num_rows($currQuery) > 0) {
                $currencyCode = mysqli_fetch_assoc($currQuery)['code'];
            }
            
            // Calculate purchase_amount_in_currency and converted_amount based on selected currency
            if ($currencyCode === 'RWF') {
                $purchase_amount_in_currency = $purchase_value_rwf;
                $converted_amount = $purchase_value_usd;
            } else {
                $purchase_amount_in_currency = $purchase_value_usd;
                $converted_amount = $purchase_value_rwf;
            }

            $delNoEsc = $delivery_no !== null ? "'" . mysqli_real_escape_string($conn, $delivery_no) . "'" : "NULL";
            $invEsc = $inventory_code !== null ? "'" . mysqli_real_escape_string($conn, $inventory_code) . "'" : "NULL";
            $accEsc = $account_id !== null ? (int)$account_id : "NULL";
            $delDateEsc = $delivery_date !== null ? "'" . mysqli_real_escape_string($conn, $delivery_date) . "'" : "NULL";
            $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL";
            $statusEsc = mysqli_real_escape_string($conn, $status);
            $negEsc = $negociant !== null ? "'" . mysqli_real_escape_string($conn, $negociant) . "'" : "NULL";

            // Nullable decimals helpers
            $p_kg_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 'NULL';
            $p_val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 'NULL';
            $ex_rate = $exchange_rate !== null ? $exchange_rate : 'NULL';
            $p_val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 'NULL';
            $n_paid = $net_paid_supplier_usd !== null ? $net_paid_supplier_usd : 'NULL';
            $chg_kg = $charges_per_kg !== null ? $charges_per_kg : 'NULL';
            $prod_chg_kg = $production_charges_per_kg !== null ? $production_charges_per_kg : 'NULL';
            $p_ta = $price_per_ta_unit !== null ? $price_per_ta_unit : 'NULL';
            $p_kg_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 'NULL';
            $lme = $lme_price !== null ? $lme_price : 'NULL';
            $tc = $tc_charges !== null ? $tc_charges : 'NULL';
            $flucVal = $fluc !== null ? $fluc : 'NULL';
            $lmePaidVal = $lme_paid !== null ? $lme_paid : 'NULL';
            $t_rra = $tax_rra !== null ? $tax_rra : 'NULL';
            $t_rma = $tax_rma !== null ? $tax_rma : 'NULL';
            $t_inko = $tax_inkomane !== null ? $tax_inkomane : 'NULL';
            $prod_chg = $production_charges !== null ? $production_charges : 'NULL';

            $methodEsc = mysqli_real_escape_string($conn, $pricing_method);

            $p_curr_id = (int)$purchase_currency_id;
            $p_amt_curr = $purchase_amount_in_currency !== null ? (float)$purchase_amount_in_currency : 'NULL';
            $p_conv_amt = $converted_amount !== null ? (float)$converted_amount : 'NULL';

            // Insert into purchasing
            $insertPurch = "INSERT INTO purchasing (
                purchase_no, delivery_no, inventory_code, account_id, delivery_date, purchase_date,
                lot_id, product_id, supplier_id, negociant, warehouse_id, quantity_kg, uom_id,
                price_per_kg_rwf, purchase_value_rwf, exchange_rate, purchase_value_usd,
                net_paid_supplier_usd, charges_per_kg, production_charges_per_kg, price_per_ta_unit, price_per_kg_usd, pricing_method,
                lme_price, tc_charges, fluc, lme_paid, tax_rra, tax_rma, tax_inkomane, production_charges,
                status, notes, created_by, purchase_currency_id, purchase_amount_in_currency, converted_amount
            ) VALUES (
                '$pNoEsc', $delNoEsc, $invEsc, $accEsc, $delDateEsc, '$purchase_date',
                $lot_id, $product_id, $supplier_id, $negEsc, $warehouse_id, $quantity_kg, $uom_id,
                $p_kg_rwf, $p_val_rwf, $ex_rate, $p_val_usd,
                $n_paid, $chg_kg, $prod_chg_kg, $p_ta, $p_kg_usd, '$methodEsc',
                $lme, $tc, $flucVal, $lmePaidVal, $t_rra, $t_rma, $t_inko, $prod_chg,
                '$statusEsc', $notesEsc, $userId, $p_curr_id, $p_amt_curr, $p_conv_amt
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

            // Sync primary element in product_element_composition
            if ($primary_element_id > 0) {
                mysqli_query($conn, "UPDATE product_element_composition SET is_primary_grade = 0 WHERE product_id = $product_id");
                $chkComp = mysqli_query($conn, "SELECT id FROM product_element_composition WHERE product_id = $product_id AND product_element_id = $primary_element_id LIMIT 1");
                if ($chkComp && mysqli_num_rows($chkComp) > 0) {
                    mysqli_query($conn, "UPDATE product_element_composition SET is_primary_grade = 1 WHERE product_id = $product_id AND product_element_id = $primary_element_id");
                } else {
                    mysqli_query($conn, "INSERT INTO product_element_composition (product_id, product_element_id, is_primary_grade, display_order) VALUES ($product_id, $primary_element_id, 1, 0)");
                }
            }

            // Insert grades into purchasing_element_grade
            if (!empty($elements_grades)) {
                foreach ($elements_grades as $elementId => $gradePct) {
                    $elementId = (int)$elementId;
                    $gradePctVal = !empty($gradePct) ? (float)$gradePct : 0.0;
                    $gradeNotesVal = isset($element_notes[$elementId]) ? mysqli_real_escape_string($conn, $element_notes[$elementId]) : '';

                    // Insert grade
                    $insertGrade = "INSERT INTO purchasing_element_grade (
                        purchasing_id, product_element_id, grade_pct, notes
                    ) VALUES (
                        $purchasingId, $elementId, $gradePctVal, '$gradeNotesVal'
                    )";
                    if (!mysqli_query($conn, $insertGrade)) {
                        $err = mysqli_error($conn);
                        if (strpos($err, 'Duplicate entry') !== false) {
                            throw new Exception("Duplicate chemical element selected. Each element can only be added once per purchase.");
                        }
                        throw new Exception("Failed to save grade for element $elementId: " . $err);
                    }
                }
            }

            // Update Stock and create movement if received
            if ($statusEsc === 'received') {
                $val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;
                $val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 0.0;
                
                $stock_info = updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg, $val_usd, $val_rwf, $purchase_currency_id, $purchase_amount_in_currency, $exchange_rate, $converted_amount);
                if (!$stock_info) {
                    throw new Exception("Failed to update inventory stock.");
                }
                $mvt_opening = $stock_info['opening'];
                $mvt_closing = $stock_info['closing'];

                // Create Stock Movement
                $unit_cost_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
                $unit_cost_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 0.0;

                $currency_id_val = $purchase_currency_id !== null ? (int)$purchase_currency_id : 'NULL';
                $amt_in_curr_val = $purchase_amount_in_currency !== null ? (float)$purchase_amount_in_currency : 'NULL';
                $ex_rate_val = $exchange_rate !== null ? (float)$exchange_rate : 'NULL';
                $conv_amt_val = $converted_amount !== null ? (float)$converted_amount : 'NULL';

                $insertMovement = "INSERT INTO stock_movement (
                    movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                    unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                    reference_type, reference_id, movement_date, notes, created_by,
                    opening, closing, purchase_currency_id, purchase_amount_in_currency, exchange_rate, converted_amount
                ) VALUES (
                    'PURCHASE_IN', $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg,
                    $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                    'purchasing', $purchasingId, '$purchase_date', $notesEsc, $userId,
                    $mvt_opening, $mvt_closing, $currency_id_val, $amt_in_curr_val, $ex_rate_val, $conv_amt_val
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

            // insert into journal_entries
            // journal entry no

            $date = isset($_GET['date']) ? trim($_GET['date']) : date('Y-m-d');

            if (empty($date) || !strtotime($date)) {
                $date = date('Y-m-d');
            }

            $dateStr  = date('Ymd', strtotime($date));
            $prefix   = "JE-" . $dateStr . "-";
            $description = "Purchase recorded: " . $purchase_no;

            // Insert journal entry and lines. Use numeric values for calculations
            // and let exceptions bubble up so the outer transaction can rollback.

            // Generate Journal Number
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

            // Get Inventory Account
            $stmt = mysqli_prepare($conn, "
                SELECT inventory_account_id, accounts.account_type_id
                FROM product
                INNER JOIN accounts 
                ON accounts.account_code = product.inventory_account_id
                WHERE product.id = ?
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $product_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$product) {
                throw new Exception("Product not found.");
            }
            $inventoryAccount = isset($product['inventory_account_id']) ? (int)$product['inventory_account_id'] : 0;
            $InventoryParentAccount = isset($product['account_type_id']) ? (int)$product['account_type_id'] : 0;

            if ($inventoryAccount <= 0) {
                throw new Exception("Inventory account was not found for the selected product.");
            }

            // Get Supplier Account
            $stmt = mysqli_prepare($conn, "
                SELECT payables_account_id, accounts.account_type_id
                FROM suppliers
                INNER JOIN accounts
                    ON accounts.account_code = suppliers.payables_account_id
                WHERE suppliers.id = ?
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "i", $supplier_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $supplier = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$supplier) {
                throw new Exception("Supplier account not found.");
            }
            $supplierAccount = isset($supplier['payables_account_id']) ? (int)$supplier['payables_account_id'] : 0;
            $supplierAccountType = isset($supplier['account_type_id']) ? (int)$supplier['account_type_id'] : 0;

            if ($supplierAccount <= 0) {
                throw new Exception("Supplier payables account was not found.");
            }

            // get RRA tax account
            $stmt = mysqli_prepare($conn, "
                SELECT account_code, accounts.account_type_id
                FROM accounts
                WHERE account_code = '2024'
                LIMIT 1
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rraTaxAccount = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$rraTaxAccount) {
                throw new Exception("RRA tax account not found.");
            }
            $rraTaxAccountId = isset($rraTaxAccount['account_code']) ? (int)$rraTaxAccount['account_code'] : 0;
            $rraTaxAccountType = isset($rraTaxAccount['account_type_id']) ? (int)$rraTaxAccount['account_type_id'] : 0;

            // get RMA tax account and INKOMANE
            $stmt = mysqli_prepare($conn, "
                SELECT account_code, accounts.account_type_id
                FROM accounts
                WHERE account_code = '2034'
                LIMIT 1
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $rmaTaxAccount = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$rmaTaxAccount) {
                throw new Exception("RMA tax account not found.");
            }
            $rmaTaxAccountId = isset($rmaTaxAccount['account_code']) ? (int)$rmaTaxAccount['account_code'] : 0;
            $rmaTaxAccountType = isset($rmaTaxAccount['account_type_id']) ? (int)$rmaTaxAccount['account_type_id'] : 0;

            // get INKOMANE tax account
            $stmt = mysqli_prepare($conn, "
                SELECT account_code, accounts.account_type_id
                FROM accounts
                WHERE account_code = '2044'
                LIMIT 1
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $inkomaneTaxAccount = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$inkomaneTaxAccount) {
                throw new Exception("INKOMANE tax account not found.");
            }
            $inkomaneTaxAccountId = isset($inkomaneTaxAccount['account_code']) ? (int)$inkomaneTaxAccount['account_code'] : 0;
            $inkomaneTaxAccountType = isset($inkomaneTaxAccount['account_type_id']) ? (int)$inkomaneTaxAccount['account_type_id'] : 0;

            // get production charges account
            $stmt = mysqli_prepare($conn, "
                SELECT account_code, accounts.account_type_id
                FROM accounts
                WHERE account_code = '2054'
                LIMIT 1
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $productionChargesAccount = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$productionChargesAccount) {
                throw new Exception("PRODUCTION_CHARGES_PAYABLE account not found.");
            }
            $productionChargesAccountId = isset($productionChargesAccount['account_code']) ? (int)$productionChargesAccount['account_code'] : 0;
            $productionChargesAccountType = isset($productionChargesAccount['account_type_id']) ? (int)$productionChargesAccount['account_type_id'] : 0;

            // Insert Journal Header
            $status = "AUTO POSTED";
            $stmt = mysqli_prepare($conn, "
                INSERT INTO journal_entries
                (journal_no, entry_date, description, statuss, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            mysqli_stmt_bind_param(
                $stmt,
                "ssssi",
                $journalNo,
                $purchase_date,
                $description,
                $status,
                $userId
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }
            $journalEntryId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Ensure we use numeric exchange rate and purchase values (not SQL 'NULL' strings)
            $ex_rate_num = isset($exchange_rate) ? (float)$exchange_rate : 0.0;
            $purchaseValueUsd = isset($purchase_value_usd) ? (float)$purchase_value_usd : 0.0;
            $taxRraAmount = isset($tax_rra) ? (float)$tax_rra : 0.0;
            $taxRmaAmount = isset($tax_rma) ? (float)$tax_rma : 0.0;
            $inkomaneTaxAmount = isset($tax_inkomane) ? (float)$tax_inkomane : 0.0;
            $productionChargesAmount = isset($production_charges) ? (float)$production_charges : 0.0;
            $supplierAmount = isset($net_paid_supplier_usd) ? (float)$net_paid_supplier_usd : 0.0;
            $currencyId = 2;

            // Prepare Journal Line Statement
            $stmt = mysqli_prepare($conn, "
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
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }

            // Debit Inventory for total purchase value
            $debit = $purchaseValueUsd;
            $credit = 0.0;
            $amountBase = $purchaseValueUsd * $ex_rate_num;

            mysqli_stmt_bind_param(
                $stmt,
                "iiiddiddds",
                $journalEntryId,
                $InventoryParentAccount,
                $inventoryAccount,
                $debit,
                $credit,
                $currencyId,
                $ex_rate_num,
                $purchaseValueUsd,
                $amountBase,
                $description
            );
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception(mysqli_error($conn));
            }

            $creditLines = [
                [
                    'parent_account_id' => $rraTaxAccountType,
                    'account_id' => $rraTaxAccountId,
                    'amount' => $taxRraAmount,
                ],
                [
                    'parent_account_id' => $rmaTaxAccountType,
                    'account_id' => $rmaTaxAccountId,
                    'amount' => $taxRmaAmount,
                ],
                [
                    'parent_account_id' => $inkomaneTaxAccountType,
                    'account_id' => $inkomaneTaxAccountId,
                    'amount' => $inkomaneTaxAmount,
                ],
                [
                    'parent_account_id' => $productionChargesAccountType,
                    'account_id' => $productionChargesAccountId,
                    'amount' => $productionChargesAmount,
                ],
                [
                    'parent_account_id' => $supplierAccountType,
                    'account_id' => $supplierAccount,
                    'amount' => $supplierAmount,
                ],
            ];

            foreach ($creditLines as $creditLine) {
                if ((float)$creditLine['amount'] <= 0.0) {
                    continue;
                }

                $debit = 0.0;
                $credit = (float)$creditLine['amount'];
                $lineAmountBase = $credit * $ex_rate_num;

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiiddiddds",
                    $journalEntryId,
                    $creditLine['parent_account_id'],
                    $creditLine['account_id'],
                    $debit,
                    $credit,
                    $currencyId,
                    $ex_rate_num,
                    $credit,
                    $lineAmountBase,
                    $description
                );
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(mysqli_error($conn));
                }
            }

            mysqli_stmt_close($stmt);
            
            $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
            mysqli_commit($conn);
            sendResponse(true, "Purchase record '$purchase_no' created successfully.", $newValues);

        } catch (Exception $e) {
            mysqli_rollback($conn);

            $msg = $e->getMessage();

            if (strpos($msg, 'Duplicate entry') !== false) {
                $msg = "Duplicate chemical element selected. Each element can only be added once per purchase.";
            }

            sendResponse(false, $msg);
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
        $negociant = isset($_POST['negociant']) ? trim($_POST['negociant']) : null;
        
        $lot_id = isset($_POST['lot_id']) ? (int)$_POST['lot_id'] : 0;
        $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        $supplier_id = isset($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : 0;
        $warehouse_id = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
        $quantity_kg = isset($_POST['quantity_kg']) ? (float)$_POST['quantity_kg'] : 0.0;
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 1;

        // Financial/Pricing Metrics
        $exchange_rate = isset($_POST['exchange_rate']) && $_POST['exchange_rate'] !== '' ? (float)$_POST['exchange_rate'] : 1400.0;
        $charges_per_kg = isset($_POST['charges_per_kg']) && $_POST['charges_per_kg'] !== '' ? (float)$_POST['charges_per_kg'] : null;
        $production_charges_per_kg = isset($_POST['production_charges_per_kg']) && $_POST['production_charges_per_kg'] !== '' ? (float)$_POST['production_charges_per_kg'] : null;
        $price_per_ta_unit = isset($_POST['price_per_ta_unit']) && $_POST['price_per_ta_unit'] !== '' ? (float)$_POST['price_per_ta_unit'] : null;
        $lme_price = isset($_POST['lme_price']) && $_POST['lme_price'] !== '' ? (float)$_POST['lme_price'] : null;
        $tc_charges = isset($_POST['tc_charges']) && $_POST['tc_charges'] !== '' ? (float)$_POST['tc_charges'] : null;
        $fluc = isset($_POST['fluc']) && $_POST['fluc'] !== '' ? (float)$_POST['fluc'] : null;
        $lme_paid = isset($_POST['lme_paid']) && $_POST['lme_paid'] !== '' ? (float)$_POST['lme_paid'] : null;
        
        $status = isset($_POST['status']) ? trim($_POST['status']) : 'pending';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

        $elements_grades = isset($_POST['elements']) ? $_POST['elements'] : [];
        $primary_element_id = isset($_POST['primary_element_id']) ? (int)$_POST['primary_element_id'] : 0;
        $element_notes = isset($_POST['element_notes']) ? $_POST['element_notes'] : [];

        if ($lot_id <= 0 || $product_id <= 0 || $supplier_id <= 0 || $warehouse_id <= 0 || $quantity_kg <= 0) {
            sendResponse(false, 'Lot, Product, Supplier, Warehouse, and Quantity (greater than 0) are required.');
        }

        // Auto-create/retrieve supplier payables account
        $account_id = getOrCreateSupplierAccount($conn, $supplier_id);

        // Check reference code uniqueness (excluding current record)
        $pNoEsc = mysqli_real_escape_string($conn, $purchase_no);
        $chkCode = mysqli_query($conn, "SELECT id FROM purchasing WHERE purchase_no = '$pNoEsc' AND id != $id LIMIT 1");
        if ($chkCode && mysqli_num_rows($chkCode) > 0) {
            sendResponse(false, 'A purchase with this reference number already exists.');
        }

        // Determine product type
        $product_type = 'tin';
        if ($product_id > 0) {
            $prodQuery = mysqli_query($conn, "SELECT product_code, product_name FROM product WHERE id = $product_id LIMIT 1");
            if ($prodQuery && mysqli_num_rows($prodQuery) > 0) {
                $prod = mysqli_fetch_assoc($prodQuery);
                $p_code = strtoupper($prod['product_code']);
                $p_name = strtolower($prod['product_name']);
                if (strpos($p_name, 'coltan') !== false || strpos($p_name, 'tantalum') !== false || strpos($p_code, 'TA') !== false) {
                    $product_type = 'tantalum';
                } elseif (strpos($p_name, 'wolframite') !== false || strpos($p_name, 'tungsten') !== false || strpos($p_code, 'W') !== false) {
                    $product_type = 'wolframite';
                }
            }
        }

        // Extract primary grade
        $grade_pct = 0.0;
        if ($primary_element_id > 0 && isset($elements_grades[$primary_element_id])) {
            $grade_pct = (float)$elements_grades[$primary_element_id];
        }

        // Perform server-side calculations based on pricing method
        $pricing_method = isset($_POST['pricing_method']) ? trim($_POST['pricing_method']) : 'lme';
        $manual_price_usd = isset($_POST['price_per_kg_usd']) && $_POST['price_per_kg_usd'] !== '' ? (float)$_POST['price_per_kg_usd'] : 0.0;

        if ($pricing_method === 'manual') {
            $price_per_kg_usd = $manual_price_usd;
            $price_per_kg_rwf = $price_per_kg_usd * $exchange_rate;
            $purchase_value_usd = $price_per_kg_usd * $quantity_kg;
            $purchase_value_rwf = $price_per_kg_rwf * $quantity_kg;
            $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : 0.0;
            $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : 0.0;
            $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : 0.0;
            $production_charges = $quantity_kg * $production_charges_per_kg;
            $net_paid_supplier_usd = $purchase_value_usd - $tax_rra - $tax_rma - $tax_inkomane - $production_charges;
        } else {
            if ($lme_price !== null && $fluc !== null) {
                $lme_paid = $lme_price - $fluc;
            }
            $metrics = calculatePurchaseMetrics($conn, $quantity_kg, $exchange_rate, $lme_price, $tc_charges, $production_charges_per_kg, $price_per_ta_unit, $grade_pct, $manual_price_usd, $product_type, $fluc);
            $price_per_kg_usd = $metrics['price_per_kg_usd'];
            $price_per_kg_rwf = $metrics['price_per_kg_rwf'];
            $purchase_value_usd = $metrics['purchase_value_usd'];
            $purchase_value_rwf = $metrics['purchase_value_rwf'];
            $tax_rra = isset($_POST['tax_rra']) && $_POST['tax_rra'] !== '' ? (float)$_POST['tax_rra'] : $metrics['tax_rra'];
            $tax_rma = isset($_POST['tax_rma']) && $_POST['tax_rma'] !== '' ? (float)$_POST['tax_rma'] : $metrics['tax_rma'];
            $tax_inkomane = isset($_POST['tax_inkomane']) && $_POST['tax_inkomane'] !== '' ? (float)$_POST['tax_inkomane'] : $metrics['tax_inkomane'];
            $production_charges = $metrics['production_charges'];
            $net_paid_supplier_usd = $purchase_value_usd - $tax_rra - $tax_rma - $tax_inkomane - $production_charges;
            $lme_paid = $metrics['lme_paid'];
        }

        mysqli_begin_transaction($conn);

        try {
            // 1. Revert inventory stock of old values if it was received
            if ($oldValues['status'] === 'received') {
                $old_qty = (float)$oldValues['quantity_kg'];
                $old_val_usd = (float)$oldValues['purchase_value_usd'];
                $old_val_rwf = (float)$oldValues['purchase_value_rwf'];
                $old_currency_id = $oldValues['purchase_currency_id'] !== null ? (int)$oldValues['purchase_currency_id'] : 'NULL';
                $old_amt_in_curr = $oldValues['purchase_amount_in_currency'] !== null ? (float)$oldValues['purchase_amount_in_currency'] : 0.0;
                $old_ex_rate = $oldValues['exchange_rate'] !== null ? (float)$oldValues['exchange_rate'] : 0.0;
                $old_conv_amt = $oldValues['converted_amount'] !== null ? (float)$oldValues['converted_amount'] : 0.0;

                if (!updateStock($conn, $oldValues['warehouse_id'], $oldValues['product_id'], $oldValues['lot_id'], $oldValues['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf, $oldValues['purchase_currency_id'], -$old_amt_in_curr, $oldValues['exchange_rate'], -$old_conv_amt)) {
                    throw new Exception("Failed to adjust inventory stock.");
                }

                // 2. Delete old stock movement
                mysqli_query($conn, "DELETE FROM stock_movement WHERE reference_type = 'purchasing' AND reference_id = $id");
            }

            // 3. Update purchasing table
            $delNoEsc = $delivery_no !== null ? "'" . mysqli_real_escape_string($conn, $delivery_no) . "'" : "NULL";
            $invEsc = $inventory_code !== null ? "'" . mysqli_real_escape_string($conn, $inventory_code) . "'" : "NULL";
            $accEsc = $account_id !== null ? (int)$account_id : "NULL";
            $delDateEsc = $delivery_date !== null ? "'" . mysqli_real_escape_string($conn, $delivery_date) . "'" : "NULL";
            $notesEsc = $notes !== null ? "'" . mysqli_real_escape_string($conn, $notes) . "'" : "NULL";
            $statusEsc = mysqli_real_escape_string($conn, $status);
            $negEsc = $negociant !== null ? "'" . mysqli_real_escape_string($conn, $negociant) . "'" : "NULL";

            $p_kg_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 'NULL';
            $p_val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 'NULL';
            $ex_rate = $exchange_rate !== null ? $exchange_rate : 'NULL';
            $p_val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 'NULL';
            $n_paid = $net_paid_supplier_usd !== null ? $net_paid_supplier_usd : 'NULL';
            $chg_kg = $charges_per_kg !== null ? $charges_per_kg : 'NULL';
            $prod_chg_kg = $production_charges_per_kg !== null ? $production_charges_per_kg : 'NULL';
            $p_ta = $price_per_ta_unit !== null ? $price_per_ta_unit : 'NULL';
            $p_kg_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 'NULL';
            $lme = $lme_price !== null ? $lme_price : 'NULL';
            $tc = $tc_charges !== null ? $tc_charges : 'NULL';
            $flucVal = $fluc !== null ? $fluc : 'NULL';
            $lmePaidVal = $lme_paid !== null ? $lme_paid : 'NULL';
            $t_rra = $tax_rra !== null ? $tax_rra : 'NULL';
            $t_rma = $tax_rma !== null ? $tax_rma : 'NULL';
            $t_inko = $tax_inkomane !== null ? $tax_inkomane : 'NULL';
            $prod_chg = $production_charges !== null ? $production_charges : 'NULL';

            $methodEsc = mysqli_real_escape_string($conn, $pricing_method);

            $purchase_currency_id = isset($_POST['purchase_currency_id']) ? (int)$_POST['purchase_currency_id'] : 2; // Default to USD (2)
            
            // Find currency code
            $currencyCode = 'USD';
            $currQuery = mysqli_query($conn, "SELECT code FROM currencies WHERE id = $purchase_currency_id LIMIT 1");
            if ($currQuery && mysqli_num_rows($currQuery) > 0) {
                $currencyCode = mysqli_fetch_assoc($currQuery)['code'];
            }
            
            // Calculate purchase_amount_in_currency and converted_amount based on selected currency
            if ($currencyCode === 'RWF') {
                $purchase_amount_in_currency = $purchase_value_rwf;
                $converted_amount = $purchase_value_usd;
            } else {
                $purchase_amount_in_currency = $purchase_value_usd;
                $converted_amount = $purchase_value_rwf;
            }

            $p_curr_id = (int)$purchase_currency_id;
            $p_amt_curr = $purchase_amount_in_currency !== null ? (float)$purchase_amount_in_currency : 'NULL';
            $p_conv_amt = $converted_amount !== null ? (float)$converted_amount : 'NULL';

            $updatePurch = "UPDATE purchasing SET
                purchase_no = '$pNoEsc',
                delivery_no = $delNoEsc,
                inventory_code = $invEsc,
                account_id = $accEsc,
                delivery_date = $delDateEsc,
                purchase_date = '$purchase_date',
                lot_id = $lot_id,
                product_id = $product_id,
                supplier_id = $supplier_id,
                negociant = $negEsc,
                warehouse_id = $warehouse_id,
                quantity_kg = $quantity_kg,
                uom_id = $uom_id,
                price_per_kg_rwf = $p_kg_rwf,
                purchase_value_rwf = $p_val_rwf,
                exchange_rate = $ex_rate,
                purchase_value_usd = $p_val_usd,
                net_paid_supplier_usd = $n_paid,
                charges_per_kg = $chg_kg,
                production_charges_per_kg = $prod_chg_kg,
                price_per_ta_unit = $p_ta,
                price_per_kg_usd = $p_kg_usd,
                pricing_method = '$methodEsc',
                lme_price = $lme,
                tc_charges = $tc,
                fluc = $flucVal,
                lme_paid = $lmePaidVal,
                tax_rra = $t_rra,
                tax_rma = $t_rma,
                tax_inkomane = $t_inko,
                production_charges = $prod_chg,
                status = '$statusEsc',
                notes = $notesEsc,
                purchase_currency_id = $p_curr_id,
                purchase_amount_in_currency = $p_amt_curr,
                converted_amount = $p_conv_amt,
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

            // Sync primary element in product_element_composition
            if ($primary_element_id > 0) {
                mysqli_query($conn, "UPDATE product_element_composition SET is_primary_grade = 0 WHERE product_id = $product_id");
                $chkComp = mysqli_query($conn, "SELECT id FROM product_element_composition WHERE product_id = $product_id AND product_element_id = $primary_element_id LIMIT 1");
                if ($chkComp && mysqli_num_rows($chkComp) > 0) {
                    mysqli_query($conn, "UPDATE product_element_composition SET is_primary_grade = 1 WHERE product_id = $product_id AND product_element_id = $primary_element_id");
                } else {
                    mysqli_query($conn, "INSERT INTO product_element_composition (product_id, product_element_id, is_primary_grade, display_order) VALUES ($product_id, $primary_element_id, 1, 0)");
                }
            }

            // Insert new grades
            if (!empty($elements_grades)) {
                foreach ($elements_grades as $elementId => $gradePct) {
                    $elementId = (int)$elementId;
                    $gradePctVal = !empty($gradePct) ? (float)$gradePct : 0.0;
                    $gradeNotesVal = isset($element_notes[$elementId]) ? mysqli_real_escape_string($conn, $element_notes[$elementId]) : '';

                    // Insert grade
                    $insertGrade = "INSERT INTO purchasing_element_grade (
                        purchasing_id, product_element_id, grade_pct, notes
                    ) VALUES (
                        $id, $elementId, $gradePctVal, '$gradeNotesVal'
                    )";
                    if (!mysqli_query($conn, $insertGrade)) {
                        $err = mysqli_error($conn);
                        if (strpos($err, 'Duplicate entry') !== false) {
                            throw new Exception("Duplicate chemical element selected. Each element can only be added once per purchase.");
                        }
                        throw new Exception("Failed to save grade for element $elementId: " . $err);
                    }
                }
            }

            // 6. Update Stock with new values if status is received
            if ($statusEsc === 'received') {
                $val_usd = $purchase_value_usd !== null ? $purchase_value_usd : 0.0;
                $val_rwf = $purchase_value_rwf !== null ? $purchase_value_rwf : 0.0;
                
                $stock_info = updateStock($conn, $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg, $val_usd, $val_rwf, $purchase_currency_id, $purchase_amount_in_currency, $exchange_rate, $converted_amount);
                if (!$stock_info) {
                    throw new Exception("Failed to update inventory stock.");
                }
                $mvt_opening = $stock_info['opening'];
                $mvt_closing = $stock_info['closing'];

                // 7. Create new Stock Movement
                $unit_cost_usd = $price_per_kg_usd !== null ? $price_per_kg_usd : 0.0;
                $unit_cost_rwf = $price_per_kg_rwf !== null ? $price_per_kg_rwf : 0.0;

                $currency_id_val = $purchase_currency_id !== null ? (int)$purchase_currency_id : 'NULL';
                $amt_in_curr_val = $purchase_amount_in_currency !== null ? (float)$purchase_amount_in_currency : 'NULL';
                $ex_rate_val = $exchange_rate !== null ? (float)$exchange_rate : 'NULL';
                $conv_amt_val = $converted_amount !== null ? (float)$converted_amount : 'NULL';

                $insertMovement = "INSERT INTO stock_movement (
                    movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                    unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                    reference_type, reference_id, movement_date, notes, created_by,
                    opening, closing, purchase_currency_id, purchase_amount_in_currency, exchange_rate, converted_amount
                ) VALUES (
                    'PURCHASE_IN', $warehouse_id, $product_id, $lot_id, $uom_id, $quantity_kg,
                    $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                    'purchasing', $id, '$purchase_date', $notesEsc, $userId,
                    $mvt_opening, $mvt_closing, $currency_id_val, $amt_in_curr_val, $ex_rate_val, $conv_amt_val
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
            $msg = $e->getMessage();
            if (strpos($msg, 'Duplicate entry') !== false) {
                $msg = "Duplicate chemical element selected. Each element can only be added once per purchase.";
            }
            sendResponse(false, $msg);
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
                    
                    $currency_id = $currentRow['purchase_currency_id'];
                    $amt_in_curr = $currentRow['purchase_amount_in_currency'] !== null ? (float)$currentRow['purchase_amount_in_currency'] : 0.0;
                    $ex_rate = $currentRow['exchange_rate'] !== null ? (float)$currentRow['exchange_rate'] : 0.0;
                    $conv_amt = $currentRow['converted_amount'] !== null ? (float)$currentRow['converted_amount'] : 0.0;

                    $stock_info = updateStock($conn, $currentRow['warehouse_id'], $currentRow['product_id'], $currentRow['lot_id'], $currentRow['uom_id'], $quantity_kg, $val_usd, $val_rwf, $currency_id, $amt_in_curr, $ex_rate, $conv_amt);
                    if (!$stock_info) {
                        throw new Exception("Failed to update inventory stock.");
                    }
                    $mvt_opening = $stock_info['opening'];
                    $mvt_closing = $stock_info['closing'];

                    // Create Stock Movement
                    $unit_cost_usd = $currentRow['price_per_kg_usd'] !== null ? (float)$currentRow['price_per_kg_usd'] : 0.0;
                    $unit_cost_rwf = $currentRow['price_per_kg_rwf'] !== null ? (float)$currentRow['price_per_kg_rwf'] : 0.0;
                    $notesEsc = $currentRow['notes'] !== null ? "'" . mysqli_real_escape_string($conn, $currentRow['notes']) . "'" : "NULL";
                    $purchase_date = $currentRow['purchase_date'];

                    $currency_id_val = $currency_id !== null ? (int)$currency_id : 'NULL';
                    $amt_in_curr_val = $amt_in_curr !== 0.0 ? $amt_in_curr : 'NULL';
                    $ex_rate_val = $ex_rate !== 0.0 ? $ex_rate : 'NULL';
                    $conv_amt_val = $conv_amt !== 0.0 ? $conv_amt : 'NULL';

                    $insertMovement = "INSERT INTO stock_movement (
                        movement_type, warehouse_id, product_id, lot_id, uom_id, qty_kg, 
                        unit_cost_rwf, unit_cost_usd, total_value_rwf, total_value_usd, 
                        reference_type, reference_id, movement_date, notes, created_by,
                        opening, closing, purchase_currency_id, purchase_amount_in_currency, exchange_rate, converted_amount
                    ) VALUES (
                        'PURCHASE_IN', {$currentRow['warehouse_id']}, {$currentRow['product_id']}, {$currentRow['lot_id']}, {$currentRow['uom_id']}, $quantity_kg,
                        $unit_cost_rwf, $unit_cost_usd, $val_rwf, $val_usd,
                        'purchasing', $id, '$purchase_date', $notesEsc, $userId,
                        $mvt_opening, $mvt_closing, $currency_id_val, $amt_in_curr_val, $ex_rate_val, $conv_amt_val
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
                $old_currency_id = $currentRow['purchase_currency_id'];
                $old_amt_in_curr = $currentRow['purchase_amount_in_currency'] !== null ? (float)$currentRow['purchase_amount_in_currency'] : 0.0;
                $old_ex_rate = $currentRow['exchange_rate'] !== null ? (float)$currentRow['exchange_rate'] : 0.0;
                $old_conv_amt = $currentRow['converted_amount'] !== null ? (float)$currentRow['converted_amount'] : 0.0;

                if (!updateStock($conn, $currentRow['warehouse_id'], $currentRow['product_id'], $currentRow['lot_id'], $currentRow['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf, $old_currency_id, -$old_amt_in_curr, $old_ex_rate, -$old_conv_amt)) {
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
                $old_currency_id = $oldValues['purchase_currency_id'];
                $old_amt_in_curr = $oldValues['purchase_amount_in_currency'] !== null ? (float)$oldValues['purchase_amount_in_currency'] : 0.0;
                $old_ex_rate = $oldValues['exchange_rate'] !== null ? (float)$oldValues['exchange_rate'] : 0.0;
                $old_conv_amt = $oldValues['converted_amount'] !== null ? (float)$oldValues['converted_amount'] : 0.0;

                if (!updateStock($conn, $oldValues['warehouse_id'], $oldValues['product_id'], $oldValues['lot_id'], $oldValues['uom_id'], -$old_qty, -$old_val_usd, -$old_val_rwf, $old_currency_id, -$old_amt_in_curr, $old_ex_rate, -$old_conv_amt)) {
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
        }
}
?>
