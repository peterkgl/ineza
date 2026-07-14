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

if (empty($_SESSION['sales_token'])) {
    $_SESSION['sales_token'] = bin2hex(random_bytes(32));
}

function sendResponse($success, $message, $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'token' => $_SESSION['sales_token']
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postToken = isset($_POST['token']) ? $_POST['token'] : '';
    if (empty($postToken) || $postToken !== $_SESSION['sales_token']) {
        http_response_code(400);
        sendResponse(false, 'Transaction token mismatch or session expired. Please refresh the page and try again.');
    }
}

// Helper to generate next available customer account code
function getNextCustomerAccountCode() {
    global $conn;
    
    // Accounts Receivable group range (1002-1999)
    $rangeStart = 1001; // AR type is 1001, codes must start from 1002
    $rangeEnd = 1999;
    
    $existingCodes = [];
    
    // Check account_types table
    $queryTypes = "SELECT code FROM account_types 
                   WHERE code >= $rangeStart 
                     AND code <= $rangeEnd 
                   ORDER BY code ASC";
    $resultTypes = mysqli_query($conn, $queryTypes);
    if ($resultTypes) {
        while ($row = mysqli_fetch_assoc($resultTypes)) {
            $existingCodes[] = (int)$row['code'];
        }
    }
    
    // Check accounts table
    $queryAccounts = "SELECT account_code FROM accounts 
                      WHERE account_code >= $rangeStart 
                        AND account_code <= $rangeEnd 
                      ORDER BY account_code ASC";
    $resultAccounts = mysqli_query($conn, $queryAccounts);
    if ($resultAccounts) {
        while ($row = mysqli_fetch_assoc($resultAccounts)) {
            $existingCodes[] = (int)$row['account_code'];
        }
    }
    
    $existingCodes = array_unique($existingCodes);
    sort($existingCodes);
    
    $nextCode = $rangeStart + 1; // 1002
    while (in_array($nextCode, $existingCodes)) {
        $nextCode++;
        if ($nextCode > $rangeEnd) {
            return null;
        }
    }
    
    return (string)$nextCode;
}

function resolveAccountDetails($conn, $accountRef) {
    if ($accountRef === null || $accountRef === '') {
        return null;
    }

    $numericRef = is_numeric($accountRef) ? (int)$accountRef : 0;
    $accountRefEsc = (string)$accountRef;

    $stmt = mysqli_prepare($conn, "
        SELECT id, account_code, account_type_id
        FROM accounts
        WHERE id = ? OR account_code = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "is", $numericRef, $accountRefEsc);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$account) {
        return null;
    }

    return [
        'id' => (int)$account['id'],
        'account_code' => (string)$account['account_code'],
        'parent_account_id' => (int)$account['account_type_id']
    ];
}

switch ($action) {
    case 'get_stock':
        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $warehouseId = isset($_GET['warehouse_id']) ? (int)$_GET['warehouse_id'] : 0;
        $lotId = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : 0;

        if ($productId <= 0 || $warehouseId <= 0 || $lotId <= 0) {
            sendResponse(false, 'Valid product_id, warehouse_id, and lot_id are required.');
        }

        $query = "SELECT qty_on_hand, avg_cost_per_kg_usd 
                  FROM stock 
                  WHERE product_id = $productId 
                    AND warehouse_id = $warehouseId 
                    AND lot_id = $lotId 
                  LIMIT 1";
        $res = mysqli_query($conn, $query);
        if ($res && mysqli_num_rows($res) > 0) {
            $row = mysqli_fetch_assoc($res);
            sendResponse(true, 'Stock retrieved successfully.', [
                'qty_on_hand' => (float)$row['qty_on_hand'],
                'avg_cost_per_kg_usd' => (float)$row['avg_cost_per_kg_usd']
            ]);
        } else {
            sendResponse(true, 'No stock record found.', [
                'qty_on_hand' => 0.0,
                'avg_cost_per_kg_usd' => 0.0
            ]);
        }
        break;

    case 'get_lot_inventory':
        $lotId = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : 0;
        if ($lotId <= 0) {
            sendResponse(false, 'Valid lot_id is required.');
        }

        $query = "SELECT s.product_id, p.product_name, p.product_code, s.warehouse_id, w.warehouse_name, w.warehouse_code, s.qty_on_hand
                  FROM stock s
                  JOIN product p ON s.product_id = p.id
                  JOIN warehouses w ON s.warehouse_id = w.id
                  WHERE s.lot_id = $lotId AND s.qty_on_hand > 0
                  ORDER BY p.product_name ASC, w.warehouse_name ASC";
        $res = mysqli_query($conn, $query);
        $inventory = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $inventory[] = [
                    'product_id' => (int)$row['product_id'],
                    'product_name' => $row['product_name'],
                    'product_code' => $row['product_code'],
                    'warehouse_id' => (int)$row['warehouse_id'],
                    'warehouse_name' => $row['warehouse_name'],
                    'warehouse_code' => $row['warehouse_code'],
                    'qty_on_hand' => (float)$row['qty_on_hand']
                ];
            }
        }
        sendResponse(true, 'Lot inventory retrieved successfully.', $inventory);
        break;

    case 'list':
        if (!hasPermission($conn, $userId, 'view_sales') && 
            !hasPermission($conn, $userId, 'create_sale') && 
            !hasPermission($conn, $userId, 'edit_sale') && 
            !hasPermission($conn, $userId, 'delete_sale')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to view sales.');
        }

        $query = "SELECT s.*, c.name as customer_name, c.customer_code, w.warehouse_name,
                         (SELECT COALESCE(SUM(amount_allocated), 0) FROM customer_payment_allocations WHERE sale_id = s.id) as total_paid
                  FROM sells s
                  JOIN customer c ON s.customer_id = c.id
                  JOIN warehouses w ON s.warehouse_id = w.id
                  ORDER BY s.sale_date DESC, s.id DESC";
        
        $result = mysqli_query($conn, $query);
        $sales = [];

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $saleId = (int)$row['id'];
                
                // Fetch line items
                $itemsQuery = "SELECT si.*, pr.product_name, pr.product_code, l.lots_code, w.warehouse_name, uom.code as uom_code
                               FROM sells_item si
                               JOIN product pr ON si.product_id = pr.id
                               JOIN lots l ON si.lot_id = l.id
                               JOIN warehouses w ON si.warehouse_id = w.id
                               LEFT JOIN unit_of_measure uom ON si.uom_id = uom.id
                               WHERE si.sells_id = $saleId";
                $itemsResult = mysqli_query($conn, $itemsQuery);
                $items = [];
                if ($itemsResult) {
                    while ($iRow = mysqli_fetch_assoc($itemsResult)) {
                        $items[] = [
                            'id' => (int)$iRow['id'],
                            'product_id' => (int)$iRow['product_id'],
                            'product_name' => $iRow['product_name'],
                            'product_code' => $iRow['product_code'],
                            'lot_id' => (int)$iRow['lot_id'],
                            'lots_code' => $iRow['lots_code'],
                            'warehouse_id' => (int)$iRow['warehouse_id'],
                            'warehouse_name' => $iRow['warehouse_name'],
                            'uom_code' => $iRow['uom_code'] ?? 'kg',
                            'quantity_kg' => (float)$iRow['quantity_kg'],
                            'price_per_kg_usd' => (float)$iRow['price_per_kg_usd'],
                            'price_per_kg_rwf' => (float)$iRow['price_per_kg_rwf'],
                            'line_value_usd' => (float)$iRow['line_value_usd'],
                            'line_value_rwf' => (float)$iRow['line_value_rwf'],
                            'cogs_total_usd' => (float)$iRow['cogs_total_usd']
                        ];
                    }
                }
                
                $paymentsQuery = "SELECT cpa.id, cp.payment_number, cp.payment_date, cp.payment_method, cp.reference_no, cp.description, cp.amount_currency, cp.amount_base, cp.currency_id, cur.code as currency_code, cpa.amount_allocated
                                  FROM customer_payment_allocations cpa
                                  JOIN customer_payments cp ON cpa.customer_payment_id = cp.id
                                  LEFT JOIN currencies cur ON cp.currency_id = cur.id
                                  WHERE cpa.sale_id = $saleId
                                  ORDER BY cp.payment_date ASC, cp.id ASC";
                $paymentsResult = mysqli_query($conn, $paymentsQuery);
                $paymentHistory = [];
                if ($paymentsResult) {
                    while ($payRow = mysqli_fetch_assoc($paymentsResult)) {
                        $paymentHistory[] = [
                            'id' => (int)$payRow['id'],
                            'payment_number' => $payRow['payment_number'],
                            'payment_date' => $payRow['payment_date'],
                            'payment_method' => $payRow['payment_method'],
                            'reference_no' => $payRow['reference_no'],
                            'description' => $payRow['description'],
                            'currency_code' => $payRow['currency_code'] ?? 'USD',
                            'amount_currency' => (float)$payRow['amount_currency'],
                            'amount_base' => (float)$payRow['amount_base'],
                            'amount_allocated' => (float)$payRow['amount_allocated']
                        ];
                    }
                }

                $row['id'] = $saleId;
                $row['customer_id'] = (int)$row['customer_id'];
                $row['warehouse_id'] = (int)$row['warehouse_id'];
                $row['total_qty_kg'] = (float)$row['total_qty_kg'];
                $row['total_value_rwf'] = (float)$row['total_value_rwf'];
                $row['total_value_usd'] = (float)$row['total_value_usd'];
                $row['exchange_rate'] = (float)$row['exchange_rate'];
                $row['total_paid'] = (float)$row['total_paid'];
                $row['items'] = $items;
                $row['payment_history'] = $paymentHistory;
                $sales[] = $row;
            }
        }

        logAudit($conn, 'VIEW', 'sells', 'Sales List', 'User viewed the sales list');
        sendResponse(true, 'Sales orders retrieved successfully.', $sales);
        break;

    case 'create':
        if (!hasPermission($conn, $userId, 'create_sale')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to record sales.');
        }

        // Parse POST inputs
        $customerMode = isset($_POST['customer_mode']) ? trim($_POST['customer_mode']) : 'select';
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        
        // New customer fields
        $custName = isset($_POST['cust_name']) ? trim($_POST['cust_name']) : '';
        $custType = isset($_POST['cust_type']) ? trim($_POST['cust_type']) : 'company';
        $custNif = isset($_POST['cust_nif']) ? trim($_POST['cust_nif']) : '';
        $custPhone = isset($_POST['cust_phone']) ? trim($_POST['cust_phone']) : '';
        $custEmail = isset($_POST['cust_email']) ? trim($_POST['cust_email']) : '';
        $custAddress = isset($_POST['cust_address']) ? trim($_POST['cust_address']) : '';
        $custCountry = isset($_POST['cust_country']) ? trim($_POST['cust_country']) : '';
        $custNotes = isset($_POST['cust_notes']) ? trim($_POST['cust_notes']) : '';

        // Sale header fields
        $saleDate = isset($_POST['sale_date']) ? trim($_POST['sale_date']) : '';
        $deliveryDate = isset($_POST['delivery_date']) ? trim($_POST['delivery_date']) : '';
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'USD';
        $exchangeRate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 1.0;
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        $saleNo = isset($_POST['sale_no']) ? trim($_POST['sale_no']) : '';

        // Payment fields are intentionally disabled in the current sales flow.
        // $amountPaid = isset($_POST['amount_paid']) ? (float)$_POST['amount_paid'] : 0.0;
        // $paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'CASH';
        // $accountId = isset($_POST['account_id']) ? (int)$_POST['account_id'] : 0;
        // $referenceNo = isset($_POST['reference_no']) ? trim($_POST['reference_no']) : '';

        // Items JSON
        $itemsJson = isset($_POST['items']) ? $_POST['items'] : '[]';
        $items = json_decode($itemsJson, true);

        if (empty($saleDate)) {
            sendResponse(false, 'Sale date is required.');
        }
        if (empty($items) || !is_array($items)) {
            sendResponse(false, 'At least one sale product line is required.');
        }
        if ($exchangeRate <= 0) {
            sendResponse(false, 'Invalid exchange rate.');
        }

        mysqli_begin_transaction($conn);

        try {
            // Step 1: Customer setup
            if ($customerMode === 'new') {
                if (empty($custName)) {
                    throw new Exception('New customer name is required.');
                }
                $custNameEsc = mysqli_real_escape_string($conn, $custName);
                
                // Check if customer name exists
                $chkCust = mysqli_query($conn, "SELECT id FROM customer WHERE name = '$custNameEsc' LIMIT 1");
                if ($chkCust && mysqli_num_rows($chkCust) > 0) {
                    throw new Exception('A customer with this name already exists.');
                }

                // Generate Account
                $nextCode = getNextCustomerAccountCode();
                if ($nextCode === null) {
                    throw new Exception('No available account codes in the Accounts Receivable range (1002-1999).');
                }

                $accountNameEsc = mysqli_real_escape_string($conn, $custName . ' - Accounts Receivable');
                $insertAccountQuery = "INSERT INTO accounts 
                                       (account_type_id, account_code, account_name, is_active, description)
                                       VALUES (6, '$nextCode', '$accountNameEsc', 1, 'Auto-created account for customer: $custNameEsc')";
                
                if (!mysqli_query($conn, $insertAccountQuery)) {
                    throw new Exception('Failed to create customer account: ' . mysqli_error($conn));
                }
                
                $receivableAccountCode = $nextCode;
                if (empty($receivableAccountCode)) {
                    throw new Exception('Failed to resolve the newly created customer receivable account code.');
                }

                // Insert Customer
                $custCode = 'CUST-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $insertCustQuery = "INSERT INTO customer 
                                    (customer_code, customer_type, name, nif, phone, email, address, country, receivable_account_id, currency, is_active, notes)
                                    VALUES (
                                        '$custCode', 
                                        '" . mysqli_real_escape_string($conn, $custType) . "', 
                                        '$custNameEsc', 
                                        '" . mysqli_real_escape_string($conn, $custNif) . "', 
                                        '" . mysqli_real_escape_string($conn, $custPhone) . "', 
                                        '" . mysqli_real_escape_string($conn, $custEmail) . "', 
                                        '" . mysqli_real_escape_string($conn, $custAddress) . "', 
                                        '" . mysqli_real_escape_string($conn, $custCountry) . "', 
                                        '$receivableAccountCode', 
                                        '$currency', 
                                        1, 
                                        '" . mysqli_real_escape_string($conn, $custNotes) . "'
                                    )";
                
                if (!mysqli_query($conn, $insertCustQuery)) {
                    throw new Exception('Failed to create customer: ' . mysqli_error($conn));
                }
                $customerId = mysqli_insert_id($conn);
            } else {
                if ($customerId <= 0) {
                    throw new Exception('Please select a valid customer.');
                }
                // Verify existing customer
                $chkCust = mysqli_query($conn, "SELECT id FROM customer WHERE id = $customerId LIMIT 1");
                if (!$chkCust || mysqli_num_rows($chkCust) === 0) {
                    throw new Exception('Selected customer not found.');
                }
            }

            // Step 2: Generate Unique Sale Order Number
            if (empty($saleNo)) {
                $saleNo = 'SALE-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
            }
            $saleNoEsc = mysqli_real_escape_string($conn, $saleNo);
            $chkSaleNo = mysqli_query($conn, "SELECT id FROM sells WHERE sale_no = '$saleNoEsc' LIMIT 1");
            if ($chkSaleNo && mysqli_num_rows($chkSaleNo) > 0) {
                throw new Exception("Sale number '$saleNo' already exists. Please choose a different number or let it auto-generate.");
            }

            // Step 3: Compute Totals and validate items
            $totalQtyKg = 0.0;
            $totalValueUsd = 0.0;
            $totalValueRwf = 0.0;
            $headerWarehouseId = 0;

            $validatedItems = [];
            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $lotId = (int)$item['lot_id'];
                $warehouseId = (int)$item['warehouse_id'];
                $qty = (float)$item['quantity_kg'];
                $price = (float)$item['price_per_kg'];

                if ($productId <= 0 || $lotId <= 0 || $warehouseId <= 0 || $qty <= 0 || $price <= 0) {
                    throw new Exception('Invalid product line items entered.');
                }

                if ($headerWarehouseId === 0) {
                    $headerWarehouseId = $warehouseId;
                }

                // Check stock availability
                $stockQuery = "SELECT id, qty_purchased, qty_sold, qty_adjusted, qty_on_hand, avg_cost_per_kg_usd, avg_cost_per_kg_rwf, total_value_usd, total_value_rwf, opening, closing
                               FROM stock 
                               WHERE warehouse_id = $warehouseId AND product_id = $productId AND lot_id = $lotId 
                               FOR UPDATE";
                $stockResult = mysqli_query($conn, $stockQuery);
                if (!$stockResult || mysqli_num_rows($stockResult) === 0) {
                    throw new Exception("No stock record found for product ID $productId in warehouse ID $warehouseId, lot ID $lotId.");
                }
                $stockRow = mysqli_fetch_assoc($stockResult);
                $qtyOnHand = (float)$stockRow['qty_on_hand'];

                if ($qty > $qtyOnHand) {
                    throw new Exception("Insufficient stock. Requested: $qty kg, Available: $qtyOnHand kg in selected warehouse/lot.");
                }

                // Calculate lines
                if ($currency === 'USD') {
                    $valUsd = $qty * $price;
                    $valRwf = $valUsd * $exchangeRate;
                    $priceUsd = $price;
                    $priceRwf = $price * $exchangeRate;
                } else {
                    $valRwf = $qty * $price;
                    $valUsd = $valRwf / $exchangeRate;
                    $priceRwf = $price;
                    $priceUsd = $price / $exchangeRate;
                }

                $totalQtyKg += $qty;
                $totalValueUsd += $valUsd;
                $totalValueRwf += $valRwf;

                // Primary element check
                $primaryElementId = 'NULL';
                $primaryPct = 'NULL';
                $compQuery = "SELECT product_element_id FROM product_element_composition WHERE product_id = $productId AND is_primary_grade = 1 LIMIT 1";
                $compResult = mysqli_query($conn, $compQuery);
                if ($compResult && mysqli_num_rows($compResult) > 0) {
                    $primaryElementId = (int)mysqli_fetch_assoc($compResult)['product_element_id'];
                }

                $validatedItems[] = [
                    'product_id' => $productId,
                    'lot_id' => $lotId,
                    'warehouse_id' => $warehouseId,
                    'quantity_kg' => $qty,
                    'price_per_kg_usd' => $priceUsd,
                    'price_per_kg_rwf' => $priceRwf,
                    'line_value_usd' => $valUsd,
                    'line_value_rwf' => $valRwf,
                    'primary_element_id' => $primaryElementId,
                    'stock_id' => (int)$stockRow['id'],
                    'stock_row' => $stockRow
                ];
            }

            // Step 4: Insert Sells Header
            $delDateVal = !empty($deliveryDate) ? "'" . mysqli_real_escape_string($conn, $deliveryDate) . "'" : "NULL";
            $notesEsc = mysqli_real_escape_string($conn, $notes);

            $insertSaleQuery = "INSERT INTO sells (
                                    sale_no, customer_id, sale_date, delivery_date, warehouse_id, 
                                    total_qty_kg, total_value_rwf, total_value_usd, exchange_rate, currency, 
                                    status, notes, created_by
                                ) VALUES (
                                    '$saleNoEsc', $customerId, '$saleDate', $delDateVal, $headerWarehouseId, 
                                    $totalQtyKg, $totalValueRwf, $totalValueUsd, $exchangeRate, '$currency', 
                                    'confirmed', '$notesEsc', $userId
                                )";
            
            if (!mysqli_query($conn, $insertSaleQuery)) {
                throw new Exception('Failed to create sale header: ' . mysqli_error($conn));
            }
            $saleId = mysqli_insert_id($conn);

            // Step 5: Save Sells Items & Update Stocks
            $cogsTotalAmountCurrency = 0.0;
            $salesTotalAmountCurrency = 0.0;
            $saleLineAccounts = [];

            foreach ($validatedItems as $vItem) {
                $stockRow = $vItem['stock_row'];
                $stockId = $vItem['stock_id'];
                
                $avgCostUsd = (float)$stockRow['avg_cost_per_kg_usd'];
                $avgCostRwf = (float)$stockRow['avg_cost_per_kg_rwf'];
                $cogsTotalUsd = $vItem['quantity_kg'] * $avgCostUsd;
                $cogsTotalRwf = $vItem['quantity_kg'] * $avgCostRwf;
                $cogsTotalAmountCurrency = ($currency === 'USD') ? $cogsTotalUsd : $cogsTotalRwf;
                $salesLineAmountCurrency = ($currency === 'USD') ? $vItem['line_value_usd'] : $vItem['line_value_rwf'];

                // Insert sells_item
                $insertItemQuery = "INSERT INTO sells_item (
                                        sells_id, product_id, lot_id, warehouse_id, uom_id, 
                                        quantity_kg, price_per_kg_usd, price_per_kg_rwf, line_value_usd, line_value_rwf, 
                                        primary_element_id, cogs_per_kg_usd, cogs_total_usd, notes
                                    ) VALUES (
                                        $saleId, 
                                        {$vItem['product_id']}, 
                                        {$vItem['lot_id']}, 
                                        {$vItem['warehouse_id']}, 
                                        1, 
                                        {$vItem['quantity_kg']}, 
                                        {$vItem['price_per_kg_usd']}, 
                                        {$vItem['price_per_kg_rwf']}, 
                                        {$vItem['line_value_usd']}, 
                                        {$vItem['line_value_rwf']}, 
                                        {$vItem['primary_element_id']}, 
                                        $avgCostUsd, 
                                        $cogsTotalUsd, 
                                        ''
                                    )";
                if (!mysqli_query($conn, $insertItemQuery)) {
                    throw new Exception('Failed to insert sale line item: ' . mysqli_error($conn));
                }

                // Update stock values: decrement closing only and update qty_sold
                $newQtySold = (float)$stockRow['qty_sold'] + $vItem['quantity_kg'];
                $newTotalValueUsd = (float)$stockRow['total_value_usd'] - $cogsTotalUsd;
                $newTotalValueRwf = (float)$stockRow['total_value_rwf'] - $cogsTotalRwf;
                
                if ($newTotalValueUsd < 0) $newTotalValueUsd = 0;
                if ($newTotalValueRwf < 0) $newTotalValueRwf = 0;

                // Products sold must always be deducted from the closing stock only.
                $newClosing = (float)$stockRow['closing'] - $vItem['quantity_kg'];
                if ($newClosing < 0) $newClosing = 0;

                $updateStockQuery = "UPDATE stock SET 
                                        qty_sold = $newQtySold, 
                                        total_value_usd = $newTotalValueUsd, 
                                        total_value_rwf = $newTotalValueRwf, 
                                        closing = $newClosing
                                     WHERE id = $stockId";
                if (!mysqli_query($conn, $updateStockQuery)) {
                    throw new Exception('Failed to update inventory stock levels: ' . mysqli_error($conn));
                }

                $saleLineAccounts[] = [
                    'product_id' => $vItem['product_id'],
                    'cogs_amount' => $cogsTotalAmountCurrency,
                    'sales_amount' => $salesLineAmountCurrency,
                    'cogs_total_usd' => $cogsTotalUsd,
                    'cogs_total_rwf' => $cogsTotalRwf,
                    'sales_total_usd' => $vItem['line_value_usd'],
                    'sales_total_rwf' => $vItem['line_value_rwf']
                ];

                // Log stock movement
                $mvtNotesEsc = mysqli_real_escape_string($conn, "Sold via order: $saleNo");
                $insertMvtQuery = "INSERT INTO stock_movement (
                                       movement_type, warehouse_id, product_id, lot_id, uom_id, 
                                       qty_kg, unit_cost_usd, unit_cost_rwf, total_value_usd, total_value_rwf, 
                                       reference_type, reference_id, movement_date, notes, created_by, 
                                       opening, closing
                                   ) VALUES (
                                       'SALE_OUT', 
                                       {$vItem['warehouse_id']}, 
                                       {$vItem['product_id']}, 
                                       {$vItem['lot_id']}, 
                                       1, 
                                       {$vItem['quantity_kg']}, 
                                       $avgCostUsd, 
                                       $avgCostRwf, 
                                       $cogsTotalUsd, 
                                       $cogsTotalRwf, 
                                       'sells', 
                                       $saleId, 
                                       '$saleDate', 
                                       '$mvtNotesEsc', 
                                       $userId, 
                                       {$stockRow['opening']}, 
                                       $newClosing
                                   )";
                if (!mysqli_query($conn, $insertMvtQuery)) {
                    throw new Exception('Failed to create stock movement record: ' . mysqli_error($conn));
                }
            }

            $customerAccount = null;
            $customerAccountInfo = null;
            $customerAccountQuery = mysqli_query($conn, "
                SELECT c.receivable_account_id
                FROM customer c
                WHERE c.id = $customerId
                LIMIT 1
            ");
            if ($customerAccountQuery && mysqli_num_rows($customerAccountQuery) > 0) {
                $customerRow = mysqli_fetch_assoc($customerAccountQuery);
                $customerAccount = $customerRow['receivable_account_id'];
                $customerAccountInfo = resolveAccountDetails($conn, $customerAccount);
            }

            if (!$customerAccountInfo || empty($customerAccountInfo['account_code'])) {
                throw new Exception('Customer receivable account was not found.');
            }

            $currencyId = 2;
            $currencyQuery = mysqli_query($conn, "SELECT id FROM currencies WHERE code = '" . mysqli_real_escape_string($conn, $currency) . "' LIMIT 1");
            if ($currencyQuery && mysqli_num_rows($currencyQuery) > 0) {
                $currencyRow = mysqli_fetch_assoc($currencyQuery);
                $currencyId = (int)$currencyRow['id'];
            }

            $dateStr = date('Ymd', strtotime($saleDate));
            $prefix = 'JE-' . $dateStr . '-';
            $journalCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM journal_entries WHERE journal_no LIKE '" . mysqli_real_escape_string($conn, $prefix) . "%'");
            $journalCount = 0;
            if ($journalCountQuery) {
                $journalCountRow = mysqli_fetch_assoc($journalCountQuery);
                $journalCount = (int)($journalCountRow['cnt'] ?? 0);
            }
            $journalNo = $prefix . str_pad($journalCount + 1, 4, '0', STR_PAD_LEFT);
            $journalDescription = 'Sales recorded: ' . $saleNo;

            $insertJournalHeader = mysqli_prepare($conn, "
                INSERT INTO journal_entries
                (journal_no, entry_date, description, statuss, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            if (!$insertJournalHeader) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }
            $journalStatus = 'AUTO POSTED';
            mysqli_stmt_bind_param($insertJournalHeader, "ssssi", $journalNo, $saleDate, $journalDescription, $journalStatus, $userId);
            if (!mysqli_stmt_execute($insertJournalHeader)) {
                throw new Exception(mysqli_error($conn));
            }
            $journalEntryId = mysqli_insert_id($conn);
            mysqli_stmt_close($insertJournalHeader);

            $insertJournalLine = mysqli_prepare($conn, "
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
            if (!$insertJournalLine) {
                throw new Exception('Prepare failed: ' . mysqli_error($conn));
            }

            $zeroAmount = 0.0;

            foreach ($saleLineAccounts as $lineAccount) {
                $productInfoQuery = mysqli_query($conn, "
                    SELECT p.id,
                           p.inventory_account_id,
                           p.cogs_account_id,
                           p.sales_account_id,
                           inv.id AS inventory_account_id_value,
                           inv.account_code AS inventory_account_code_value,
                           inv.account_type_id AS inventory_parent_account_id,
                           cog.id AS cogs_account_id_value,
                           cog.account_code AS cogs_account_code_value,
                           cog.account_type_id AS cogs_parent_account_id,
                           sal.id AS sales_account_id_value,
                           sal.account_code AS sales_account_code_value,
                           sal.account_type_id AS sales_parent_account_id
                    FROM product p
                    LEFT JOIN accounts inv ON inv.account_code = p.inventory_account_id
                    LEFT JOIN accounts cog ON cog.account_code = p.cogs_account_id
                    LEFT JOIN accounts sal ON sal.account_code = p.sales_account_id
                    WHERE p.id = " . (int)$lineAccount['product_id'] . "
                    LIMIT 1
                ");
                if (!$productInfoQuery || mysqli_num_rows($productInfoQuery) === 0) {
                    throw new Exception('Product accounting accounts were not found.');
                }
                $productInfo = mysqli_fetch_assoc($productInfoQuery);

                $inventoryAccountCode = isset($productInfo['inventory_account_code_value']) ? (int)$productInfo['inventory_account_code_value'] : 0;
                $inventoryParentAccountId = isset($productInfo['inventory_parent_account_id']) ? (int)$productInfo['inventory_parent_account_id'] : 0;
                $cogsAccountCode = isset($productInfo['cogs_account_code_value']) ? (int)$productInfo['cogs_account_code_value'] : 0;
                $cogsParentAccountId = isset($productInfo['cogs_parent_account_id']) ? (int)$productInfo['cogs_parent_account_id'] : 0;
                $salesAccountCode = isset($productInfo['sales_account_code_value']) ? (int)$productInfo['sales_account_code_value'] : 0;
                $salesParentAccountId = isset($productInfo['sales_parent_account_id']) ? (int)$productInfo['sales_parent_account_id'] : 0;

                if ($inventoryAccountCode <= 0 || $cogsAccountCode <= 0 || $salesAccountCode <= 0) {
                    throw new Exception('Inventory, COGS, or sales account was not found for a selected product.');
                }

                $cogsAmountCurrency = $lineAccount['cogs_amount'];
                $salesAmountCurrency = $lineAccount['sales_amount'];
                $amountBase = $cogsAmountCurrency * $exchangeRate;
                $revenueBaseAmount = $salesAmountCurrency * $exchangeRate;

                mysqli_stmt_bind_param($insertJournalLine, "iiiddiddds", $journalEntryId, $cogsParentAccountId, $cogsAccountCode, $cogsAmountCurrency, $zeroAmount, $currencyId, $exchangeRate, $cogsAmountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($insertJournalLine, "iiiddiddds", $journalEntryId, $inventoryParentAccountId, $inventoryAccountCode, $zeroAmount, $cogsAmountCurrency, $currencyId, $exchangeRate, $cogsAmountCurrency, $amountBase, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                $customerAccountCode = isset($customerAccountInfo['account_code']) ? (int)$customerAccountInfo['account_code'] : 0;
                if ($customerAccountCode <= 0) {
                    throw new Exception('Customer receivable account code was not found.');
                }

                mysqli_stmt_bind_param($insertJournalLine, "iiiddiddds", $journalEntryId, $customerAccountInfo['parent_account_id'], $customerAccountCode, $salesAmountCurrency, $zeroAmount, $currencyId, $exchangeRate, $salesAmountCurrency, $revenueBaseAmount, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }

                mysqli_stmt_bind_param($insertJournalLine, "iiiddiddds", $journalEntryId, $salesParentAccountId, $salesAccountCode, $zeroAmount, $salesAmountCurrency, $currencyId, $exchangeRate, $salesAmountCurrency, $revenueBaseAmount, $journalDescription);
                if (!mysqli_stmt_execute($insertJournalLine)) {
                    throw new Exception(mysqli_error($conn));
                }
            }

            mysqli_stmt_close($insertJournalLine);

            /*
            // Payment processing is disabled in the current sales flow.
            $amountPaid = 0.0;
            $paymentMethod = 'CASH';
            $accountId = 0;
            $referenceNo = '';

            if ($amountPaid > 0) {
                if ($accountId <= 0) {
                    throw new Exception('Please select a valid Cash/Bank Account for the payment.');
                }
                
                $paymentCurrencyId = 2;
                $curResult = mysqli_query($conn, "SELECT id FROM currencies WHERE code = '" . mysqli_real_escape_string($conn, $currency) . "' LIMIT 1");
                if ($curResult && mysqli_num_rows($curResult) > 0) {
                    $paymentCurrencyId = (int)mysqli_fetch_assoc($curResult)['id'];
                }

                if ($currency === 'USD') {
                    $amountBase = $amountPaid;
                } else {
                    $amountBase = $amountPaid / $exchangeRate;
                }

                $payNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
                $payNumberEsc = mysqli_real_escape_string($conn, $payNumber);
                $refNoEsc = mysqli_real_escape_string($conn, $referenceNo);
                $payDescEsc = mysqli_real_escape_string($conn, "Payment received for sale order: $saleNo");

                $insertPayQuery = "INSERT INTO customer_payments (
                                       payment_number, customer_id, account_id, currency_id, exchange_rate, 
                                       amount_currency, amount_base, payment_date, payment_method, reference_no, 
                                       description, status, created_by
                                   ) VALUES (
                                       '$payNumberEsc', 
                                       $customerId, 
                                       $accountId, 
                                       $paymentCurrencyId, 
                                       $exchangeRate, 
                                       $amountPaid, 
                                       $amountBase, 
                                       '$saleDate', 
                                       '" . mysqli_real_escape_string($conn, $paymentMethod) . "', 
                                       '$refNoEsc', 
                                       '$payDescEsc', 
                                       'POSTED', 
                                       $userId
                                   )";
                
                if (!mysqli_query($conn, $insertPayQuery)) {
                    throw new Exception('Failed to record customer payment: ' . mysqli_error($conn));
                }
                $paymentId = mysqli_insert_id($conn);

                $insertAllocQuery = "INSERT INTO customer_payment_allocations (
                                         customer_payment_id, sale_id, amount_allocated
                                     ) VALUES (
                                         $paymentId, 
                                         $saleId, 
                                         $amountPaid
                                     )";
                if (!mysqli_query($conn, $insertAllocQuery)) {
                    throw new Exception('Failed to allocate customer payment: ' . mysqli_error($conn));
                }

                $totalVal = ($currency === 'USD') ? $totalValueUsd : $totalValueRwf;
                if (($amountPaid + 0.01) >= $totalVal) {
                    mysqli_query($conn, "UPDATE sells SET status = 'paid' WHERE id = $saleId");
                }
            }
            */

            // insert finance staff into journal entries and journale entries lines for accounting purposes
            // record debit cogs and credit stock in journal entries
            // record debit accounts receivable and credit sales revenue in journal entries
            

            // Log Audit
            logAudit($conn, 'CREATE', 'sells', $saleNo, "Recorded sales order: $saleNo", null, [
                'id' => $saleId,
                'sale_no' => $saleNo,
                'customer_id' => $customerId,
                'total_qty_kg' => $totalQtyKg,
                'total_value_usd' => $totalValueUsd,
                'amount_paid' => 0
            ]);

            $_SESSION['sales_token'] = bin2hex(random_bytes(32));
            mysqli_commit($conn);
            sendResponse(true, "Sales order '$saleNo' recorded successfully.", ['id' => $saleId]);
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to save sales transaction: ' . $e->getMessage());
        }
        break;

    case 'delete':
        if (!hasPermission($conn, $userId, 'delete_sale')) {
            http_response_code(403);
            sendResponse(false, 'Forbidden: You do not have permission to delete sales.');
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            sendResponse(false, 'Invalid ID.');
        }

        $chkSale = mysqli_query($conn, "SELECT * FROM sells WHERE id = $id LIMIT 1");
        if (!$chkSale || mysqli_num_rows($chkSale) === 0) {
            sendResponse(false, 'Sales order not found.');
        }
        $oldValues = mysqli_fetch_assoc($chkSale);
        $saleNo = $oldValues['sale_no'];

        mysqli_begin_transaction($conn);

        try {
            // 1. Fetch sells items to revert stock
            $itemsQuery = "SELECT * FROM sells_item WHERE sells_id = $id";
            $itemsRes = mysqli_query($conn, $itemsQuery);
            if ($itemsRes) {
                while ($item = mysqli_fetch_assoc($itemsRes)) {
                    $productId = (int)$item['product_id'];
                    $lotId = (int)$item['lot_id'];
                    $warehouseId = (int)$item['warehouse_id'];
                    $qty = (float)$item['quantity_kg'];
                    $cogsTotalUsd = (float)$item['cogs_total_usd'];

                    // Get current stock record
                    $stockQuery = "SELECT id, qty_sold, total_value_usd, total_value_rwf, avg_cost_per_kg_rwf, closing 
                                   FROM stock 
                                   WHERE warehouse_id = $warehouseId AND product_id = $productId AND lot_id = $lotId 
                                   FOR UPDATE";
                    $stockRes = mysqli_query($conn, $stockQuery);
                    if ($stockRes && mysqli_num_rows($stockRes) > 0) {
                        $stockRow = mysqli_fetch_assoc($stockRes);
                        $stockId = (int)$stockRow['id'];

                        $revertQtySold = (float)$stockRow['qty_sold'] - $qty;
                        if ($revertQtySold < 0) $revertQtySold = 0;

                        $cogsTotalRwf = $qty * (float)$stockRow['avg_cost_per_kg_rwf'];

                        $revertTotalValueUsd = (float)$stockRow['total_value_usd'] + $cogsTotalUsd;
                        $revertTotalValueRwf = (float)$stockRow['total_value_rwf'] + $cogsTotalRwf;

                        // Revert closing stock
                        $revertClosing = (float)$stockRow['closing'] + $qty;

                        $updateStockQuery = "UPDATE stock SET 
                                                qty_sold = $revertQtySold, 
                                                total_value_usd = $revertTotalValueUsd, 
                                                total_value_rwf = $revertTotalValueRwf, 
                                                closing = $revertClosing 
                                             WHERE id = $stockId";
                        if (!mysqli_query($conn, $updateStockQuery)) {
                            throw new Exception('Failed to revert stock levels: ' . mysqli_error($conn));
                        }
                    }
                }
            }

            // 2. Delete stock movement
            mysqli_query($conn, "DELETE FROM stock_movement WHERE reference_type = 'sells' AND reference_id = $id");

            // 3. Delete allocations and payments associated
            $allocQuery = "SELECT customer_payment_id FROM customer_payment_allocations WHERE sale_id = $id";
            $allocRes = mysqli_query($conn, $allocQuery);
            $paymentsToDelete = [];
            if ($allocRes) {
                while ($alloc = mysqli_fetch_assoc($allocRes)) {
                    $paymentsToDelete[] = (int)$alloc['customer_payment_id'];
                }
            }

            mysqli_query($conn, "DELETE FROM customer_payment_allocations WHERE sale_id = $id");

            if (!empty($paymentsToDelete)) {
                $payIds = implode(',', $paymentsToDelete);
                mysqli_query($conn, "DELETE FROM customer_payments WHERE id IN ($payIds)");
            }

            // 4. Delete sells items
            mysqli_query($conn, "DELETE FROM sells_item WHERE sells_id = $id");

            // 5. Delete sells header
            if (mysqli_query($conn, "DELETE FROM sells WHERE id = $id")) {
                logAudit($conn, 'DELETE', 'sells', $saleNo, "Deleted sales order: $saleNo", $oldValues);
                $_SESSION['sales_token'] = bin2hex(random_bytes(32));
                mysqli_commit($conn);
                sendResponse(true, "Sales order '$saleNo' deleted successfully.");
            } else {
                throw new Exception(mysqli_error($conn));
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            sendResponse(false, 'Failed to delete sales order: ' . $e->getMessage());
        }
        break;

    default:
        http_response_code(400);
        sendResponse(false, 'Invalid action requested.');
        break;
}
?>
