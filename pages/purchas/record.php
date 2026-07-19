<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    if (!hasPermission($conn, $userId, 'edit_purchas')) {
        header("Location: index.php");
        exit();
    }
} else {
    if (!hasPermission($conn, $userId, 'create_purchas')) {
        header("Location: index.php");
        exit();
    }
}

if (empty($_SESSION['purchas_token'])) {
    $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
}

// Load dropdown options
$accounts = [];
$accQuery = mysqli_query($conn, "SELECT id, account_code, account_name FROM accounts WHERE is_active = 1 ORDER BY account_code ASC");
if ($accQuery) {
    while ($row = mysqli_fetch_assoc($accQuery)) {
        $accounts[] = $row;
    }
}

$currenciesList = [];
$curQuery = mysqli_query($conn, "SELECT id, code, name FROM currencies WHERE is_active = 1 ORDER BY name ASC");
if ($curQuery) {
    while ($row = mysqli_fetch_assoc($curQuery)) {
        $currenciesList[] = $row;
    }
}

$exchangeRatesList = [];
$erQuery = mysqli_query($conn, "SELECT er.id, er.rate, er.rate_date, fc.code as from_code, tc.code as to_code 
                                FROM exchange_rates er
                                JOIN currencies fc ON er.from_currency_id = fc.id
                                JOIN currencies tc ON er.to_currency_id = tc.id
                                ORDER BY er.rate_date DESC, er.id DESC");
if ($erQuery) {
    while ($row = mysqli_fetch_assoc($erQuery)) {
        $exchangeRatesList[] = $row;
    }
}

$suppliers = [];
$supQuery = mysqli_query($conn, "SELECT id, name FROM suppliers WHERE is_active = 1 ORDER BY name ASC");
if ($supQuery) {
    while ($row = mysqli_fetch_assoc($supQuery)) {
        $suppliers[] = $row;
    }
}

$warehouses = [];
$whQuery = mysqli_query($conn, "SELECT id, warehouse_name FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name ASC");
if ($whQuery) {
    while ($row = mysqli_fetch_assoc($whQuery)) {
        $warehouses[] = $row;
    }
}

$lots = [];
$lotQuery = mysqli_query($conn, "SELECT id, lots_code, product_id FROM lots WHERE closing_date IS NULL ORDER BY lots_code ASC");
if ($lotQuery) {
    while ($row = mysqli_fetch_assoc($lotQuery)) {
        $lots[] = $row;
    }
}

$products = [];
$prodQuery = mysqli_query($conn, "SELECT id, product_code, product_name, uom_id FROM product WHERE is_active = 1 ORDER BY product_name ASC");
if ($prodQuery) {
    while ($row = mysqli_fetch_assoc($prodQuery)) {
        $products[] = $row;
    }
}

$uoms = [];
$uomQuery = mysqli_query($conn, "SELECT id, code, name FROM unit_of_measure WHERE is_active = 1 ORDER BY code ASC");
if ($uomQuery) {
    while ($row = mysqli_fetch_assoc($uomQuery)) {
        $uoms[] = $row;
    }
}

// Load tax/levy settings for embedding in JS
$taxSettings = [];
$settingKeys = ['tax_rate_rra','tax_rate_rma_tin','tax_rate_rma_coltan','tax_rate_rma_wolframite','tax_rate_inkomane_tin','tax_rate_inkomane_coltan','tax_rate_inkomane_wolframite'];
foreach ($settingKeys as $sk) {
    $skEsc = mysqli_real_escape_string($conn, $sk);
    $skQ = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = '$skEsc' LIMIT 1");
    if ($skQ && mysqli_num_rows($skQ) > 0) {
        $taxSettings[$sk] = (float)mysqli_fetch_assoc($skQ)['setting_value'];
    } else {
        $defaults = ['tax_rate_rra'=>3.0,'tax_rate_rma_tin'=>50.0,'tax_rate_rma_coltan'=>125.0,'tax_rate_rma_wolframite'=>50.0,'tax_rate_inkomane_tin'=>20.0,'tax_rate_inkomane_coltan'=>40.0,'tax_rate_inkomane_wolframite'=>20.0];
        $taxSettings[$sk] = $defaults[$sk] ?? 0.0;
    }
}

// Fetch purchase record if editing
$purchaseRecord = null;
if ($id > 0) {
    $query = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, w.warehouse_name, uom.code as uom_code
              FROM purchasing p
              JOIN product pr ON p.product_id = pr.id
              JOIN lots l ON p.lot_id = l.id
              JOIN suppliers s ON p.supplier_id = s.id
              JOIN warehouses w ON p.warehouse_id = w.id
              LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
              WHERE p.id = $id LIMIT 1";
    $res = mysqli_query($conn, $query);
    if ($res && mysqli_num_rows($res) > 0) {
        $purchaseRecord = mysqli_fetch_assoc($res);
        
        // Fetch grades
        $gradesQuery = "SELECT peg.*, pe.element_code, pe.element_name, pe.symbol 
                        FROM purchasing_element_grade peg
                        JOIN product_element pe ON peg.product_element_id = pe.id
                        WHERE peg.purchasing_id = $id";
        $gradesResult = mysqli_query($conn, $gradesQuery);
        $grades = [];
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

        // Find primary element
        $primaryElementId = null;
        $compQuery = "SELECT product_element_id FROM product_element_composition WHERE product_id = {$purchaseRecord['product_id']} AND is_primary_grade = 1 LIMIT 1";
        $compResult = mysqli_query($conn, $compQuery);
        if ($compResult && mysqli_num_rows($compResult) > 0) {
            $primaryElementId = (int)mysqli_fetch_assoc($compResult)['product_element_id'];
        }

        $purchaseRecord['id'] = $id;
        $purchaseRecord['lot_id'] = (int)$purchaseRecord['lot_id'];
        $purchaseRecord['product_id'] = (int)$purchaseRecord['product_id'];
        $purchaseRecord['supplier_id'] = (int)$purchaseRecord['supplier_id'];
        $purchaseRecord['warehouse_id'] = (int)$purchaseRecord['warehouse_id'];
        $purchaseRecord['uom_id'] = (int)$purchaseRecord['uom_id'];
        $purchaseRecord['quantity_kg'] = (float)$purchaseRecord['quantity_kg'];
        $purchaseRecord['price_per_kg_rwf'] = $purchaseRecord['price_per_kg_rwf'] !== null ? (float)$purchaseRecord['price_per_kg_rwf'] : null;
        $purchaseRecord['purchase_value_rwf'] = $purchaseRecord['purchase_value_rwf'] !== null ? (float)$purchaseRecord['purchase_value_rwf'] : null;
        $purchaseRecord['exchange_rate'] = $purchaseRecord['exchange_rate'] !== null ? (float)$purchaseRecord['exchange_rate'] : null;
        $purchaseRecord['purchase_value_usd'] = $purchaseRecord['purchase_value_usd'] !== null ? (float)$purchaseRecord['purchase_value_usd'] : null;
        $purchaseRecord['net_paid_supplier_usd'] = $purchaseRecord['net_paid_supplier_usd'] !== null ? (float)$purchaseRecord['net_paid_supplier_usd'] : null;
        $purchaseRecord['charges_per_kg'] = $purchaseRecord['charges_per_kg'] !== null ? (float)$purchaseRecord['charges_per_kg'] : null;
        $purchaseRecord['price_per_ta_unit'] = $purchaseRecord['price_per_ta_unit'] !== null ? (float)$purchaseRecord['price_per_ta_unit'] : null;
        $purchaseRecord['price_per_kg_usd'] = $purchaseRecord['price_per_kg_usd'] !== null ? (float)$purchaseRecord['price_per_kg_usd'] : null;
        $purchaseRecord['lme_price'] = $purchaseRecord['lme_price'] !== null ? (float)$purchaseRecord['lme_price'] : null;
        $purchaseRecord['tc_charges'] = $purchaseRecord['tc_charges'] !== null ? (float)$purchaseRecord['tc_charges'] : null;
        $purchaseRecord['fluc'] = $purchaseRecord['fluc'] !== null ? (float)$purchaseRecord['fluc'] : null;
        $purchaseRecord['lme_paid'] = $purchaseRecord['lme_paid'] !== null ? (float)$purchaseRecord['lme_paid'] : null;
        $purchaseRecord['tax_rra'] = $purchaseRecord['tax_rra'] !== null ? (float)$purchaseRecord['tax_rra'] : null;
        $purchaseRecord['tax_rma'] = $purchaseRecord['tax_rma'] !== null ? (float)$purchaseRecord['tax_rma'] : null;
        $purchaseRecord['tax_inkomane'] = $purchaseRecord['tax_inkomane'] !== null ? (float)$purchaseRecord['tax_inkomane'] : null;
        $purchaseRecord['production_charges'] = $purchaseRecord['production_charges'] !== null ? (float)$purchaseRecord['production_charges'] : null;

        $purchaseRecord['grades'] = $grades;
        $purchaseRecord['primary_element_id'] = $primaryElementId;

        // Ensure the purchase's lot is in the lots list (even if closed)
        $hasLot = false;
        foreach ($lots as $l) {
            if ($l['id'] == $purchaseRecord['lot_id']) {
                $hasLot = true;
                break;
            }
        }
        if (!$hasLot) {
            $currLotQuery = mysqli_query($conn, "SELECT id, lots_code, product_id FROM lots WHERE id = {$purchaseRecord['lot_id']} LIMIT 1");
            if ($currLotQuery && mysqli_num_rows($currLotQuery) > 0) {
                $lots[] = mysqli_fetch_assoc($currLotQuery);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — <?php echo $id > 0 ? 'Edit' : 'Record'; ?> Purchase</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/purchas.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include '../include/sidebar.php'; ?>

<div class="main">

  <?php $page_title = ($id > 0 ? "Edit Purchase" : "Record Purchase"); include '../include/navbar.php'; ?>

  <div class="content" id="purchasContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          <?php echo $id > 0 ? 'Edit Purchase: ' . htmlspecialchars($purchaseRecord['purchase_no']) : 'Record Mining Purchase'; ?>
        </h1>
        <div class="page-sub">Fill in the fields to <?php echo $id > 0 ? 'update the' : 'record a new'; ?> mineral purchase receipt</div>
      </div>
      <div class="page-actions">
        <a href="index.php" class="btn-sm" style="text-decoration: none; display: flex; align-items: center; justify-content: center;">
          <svg class="btn-icon" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to List
        </a>
      </div>
    </div>

    <!-- Wizard Main Container -->
    <div class="purchas-card card" style="max-width: 900px; margin: 0 auto; padding: 24px;">
      <!-- Wizard Progress Indicators -->
      <div class="wizard-progress">
        <div class="wizard-progress-bar"></div>
        <div class="wizard-step active" data-step="1">
          <div class="wizard-step-label"><span class="step-num">01</span> Details</div>
        </div>
        <div class="wizard-step" data-step="2">
          <div class="wizard-step-label"><span class="step-num">02</span> Product</div>
        </div>
        <div class="wizard-step" data-step="3">
          <div class="wizard-step-label"><span class="step-num">03</span> Grades</div>
        </div>
        <div class="wizard-step" data-step="4">
          <div class="wizard-step-label"><span class="step-num">04</span> Pricing</div>
        </div>
      </div>

      <div id="formAlertPlaceholder"></div>

      <form id="purchasForm" novalidate>
        <input type="hidden" id="purchaseIdInput" name="id" value="<?php echo $id; ?>">
        <input type="hidden" id="purchasToken" name="token" value="<?php echo htmlspecialchars($_SESSION['purchas_token']); ?>">

        <!-- STEP 1: General Details -->
        <div class="wizard-step-content active" id="step1">
          <div class="form-grid-2">
            <div class="form-group">
              <label for="purchaseNo">Purchase Number (Auto if blank)</label>
              <input type="text" id="purchaseNo" name="purchase_no" class="form-control" placeholder="e.g. PURC-20260626-001">
            </div>
            <div class="form-group">
              <label for="purchaseDate">Purchase Date *</label>
              <input type="date" id="purchaseDate" name="purchase_date" class="form-control" required>
            </div>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label for="deliveryNo">Delivery Note Number</label>
              <input type="text" id="deliveryNo" name="delivery_no" class="form-control" placeholder="e.g. DN-59302">
            </div>
            <div class="form-group">
              <label for="deliveryDate">Delivery Date</label>
              <input type="date" id="deliveryDate" name="delivery_date" class="form-control">
            </div>
          </div>
          <div class="form-grid-3">
            <div class="form-group">
              <label for="supplierId">Supplier *</label>
              <select id="supplierId" name="supplier_id" class="form-control" required>
                <option value="">-- Select Supplier --</option>
                <?php foreach ($suppliers as $s): ?>
                  <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="warehouseId">Warehouse *</label>
              <select id="warehouseId" name="warehouse_id" class="form-control" required>
                <option value="">-- Select Warehouse --</option>
                <?php foreach ($warehouses as $w): ?>
                  <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['warehouse_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="lotId">Lot *</label>
              <select id="lotId" name="lot_id" class="form-control" required>
                <option value="">-- Select Lot --</option>
                <?php foreach ($lots as $l): ?>
                  <option value="<?php echo $l['id']; ?>" data-product-id="<?php echo $l['product_id']; ?>"><?php echo htmlspecialchars($l['lots_code']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label for="inventoryCode">Inventory Code / Tag</label>
              <input type="text" id="inventoryCode" name="inventory_code" class="form-control" placeholder="e.g. TAG-COLTAN-09B">
            </div>
            <div class="form-group">
              <label for="negociant">Negociant</label>
              <input type="text" id="negociant" name="negociant" class="form-control" placeholder="e.g. Christine">
            </div>
          </div>
          <input type="hidden" id="accountId" name="account_id">
        </div>

        <!-- STEP 2: Product & Elements Selection -->
        <div class="wizard-step-content" id="step2">
          <div class="form-grid-3">
            <div class="form-group" style="grid-column: span 2;">
              <label for="productId">Product *</label>
              <select id="productId" name="product_id" class="form-control" required>
                <option value="">-- Select Product --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?php echo $p['id']; ?>" data-uom-id="<?php echo $p['uom_id']; ?>" data-product-code="<?php echo htmlspecialchars(strtoupper($p['product_code'])); ?>"><?php echo htmlspecialchars($p['product_name']); ?> (<?php echo htmlspecialchars($p['product_code']); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="uomId">Unit of Measure</label>
              <select id="uomId" name="uom_id" class="form-control">
                <?php foreach ($uoms as $u): ?>
                  <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']); ?> (<?php echo htmlspecialchars($u['code']); ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          
          <div class="form-group">
            <label for="quantityKg">Quantity (kg) *</label>
            <input type="number" id="quantityKg" name="quantity_kg" class="form-control" placeholder="0.00" step="0.01" min="0.0001" required>
          </div>

          <div class="elements-config-box">
            <div style="font-size: 11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px;">Product Chemical Elements *</div>
            <div style="font-size: 10px; color:var(--text3); margin-top:2px;">Select elements present in this product and choose one as the primary grade element.</div>
            <div class="elements-list" id="elementsListContainer">
              <div style="color:var(--text3); font-size:12px; padding:10px;">Select a product first.</div>
            </div>
          </div>
        </div>

        <!-- STEP 3: Element Grades Analysis -->
        <div class="wizard-step-content" id="step3">
          <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:15px;">Specify Grade Percentages</div>
          <div class="grades-grid" id="gradesInputContainer">
            <!-- Dynamic grade inputs go here -->
          </div>
        </div>

        <!-- STEP 4: Pricing & Financials (Product-Specific) -->
        <div class="wizard-step-content" id="step4">

          <!-- Exchange Rate Section (shared by all product types) -->
          <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:12px;">Exchange Rate</div>
          <div class="form-grid-4" style="background: var(--bg); padding: 16px; border-radius: var(--radius); border: 1px solid var(--border); margin-bottom: 20px;">
            <div class="form-group">
              <label for="purchaseCurrencyId">Purchase Currency *</label>
              <select id="purchaseCurrencyId" name="purchase_currency_id" class="form-control" required>
                <?php foreach ($currenciesList as $c): ?>
                  <option value="<?php echo $c['id']; ?>" <?php echo ($c['code'] === 'RWF') ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="purchaseCurrencyValue">Currency Value *</label>
              <input type="number" id="purchaseCurrencyValue" class="form-control" placeholder="1.00" step="0.01" value="1" required>
            </div>
            <div class="form-group">
              <label for="exchangeCurrencyId">Exchange Currency *</label>
              <select id="exchangeCurrencyId" class="form-control" required>
                <?php foreach ($currenciesList as $c): ?>
                  <option value="<?php echo $c['id']; ?>" <?php echo ($c['code'] === 'USD') ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="exchangeRateValue">Exchange Rate (RWF/USD) *</label>
              <input type="number" id="exchangeRateValue" class="form-control" placeholder="0.00068" step="0.01" value="0.00068" required>
            </div>
          </div>
          <!-- Pricing Method Selection -->
          <div class="form-group" style="margin-bottom: 20px;">
            <label style="font-weight: 600; color: var(--text);">Pricing Method *</label>
            <div style="display: flex; gap: 16px; margin-top: 8px;">
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: var(--text2);">
                <input type="radio" name="pricing_method" value="lme" checked style="accent-color: var(--primary);">
                Automatic (Formula-Based)
              </label>
              <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 13px; font-weight: 500; color: var(--text2);">
                <input type="radio" name="pricing_method" value="manual" style="accent-color: var(--primary);">
                Manual Price Entry
              </label>
            </div>
          </div>
          <!-- Hidden legacy input for API & DB compatibility -->
          <input type="hidden" id="exchangeRate" name="exchange_rate" value="1400.00">
          <!-- Hidden pricing computed values (populated by calcSN/calcTA/calcW03) -->
          <input type="hidden" id="pricePerKgUsd" name="price_per_kg_usd">
          <input type="hidden" id="pricePerKgRwf" name="price_per_kg_rwf">
          <input type="hidden" id="sharedPurchaseValueUsd" name="purchase_value_usd">
          <input type="hidden" id="sharedPurchaseValueRwf" name="purchase_value_rwf">
          <input type="hidden" id="sharedTaxRra" name="tax_rra">
          <input type="hidden" id="sharedTaxRma" name="tax_rma">
          <input type="hidden" id="sharedTaxInkomane" name="tax_inkomane">
          <input type="hidden" id="sharedNetPaid" name="net_paid_supplier_usd">
          <input type="hidden" id="sharedProductionCharges" name="production_charges">
          <input type="hidden" id="sharedLmePrice" name="lme_price">
          <input type="hidden" id="sharedFluc" name="fluc">
          <input type="hidden" id="sharedTcCharges" name="tc_charges">
          <input type="hidden" id="sharedLmePaid" name="lme_paid">
          <input type="hidden" id="sharedProductionChargesPerKg" name="production_charges_per_kg">
          <!-- Hidden ta unit (used for TA form, also sent in form data) -->
          <input type="hidden" id="pricePerTaUnit" name="price_per_ta_unit">

          <!-- Product-type message (shows when no product selected) -->
          <div id="pricingNoProduct" style="padding: 24px; text-align: center; color: var(--text3); font-size: 13px; border: 1px dashed var(--border); border-radius: var(--radius); margin-bottom: 16px;">
            <svg viewBox="0 0 24 24" style="width: 32px; height: 32px; stroke: var(--text3); fill: none; stroke-width: 1.5; margin-bottom: 8px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>Select a product in Step 2 to see the pricing form.</div>
          </div>

          <!-- ========================================================== -->
          <!-- SN (Tin / Cassiterite) PRICING FORM                        -->
          <!-- Matches: Purchase Logs_SN Excel Sheet                      -->
          <!-- ========================================================== -->
          <div id="pricingFormSN" style="display:none;">
            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
              <span style="background:var(--blue-bg); color:var(--blue); padding:2px 8px; border-radius:4px; font-size:10px;">SN</span>
              Tin (Cassiterite) Pricing — LME Formula
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="sn_lme_price">Full LME (USD / Ton) *</label>
                <input type="number" id="sn_lme_price" class="form-control" placeholder="e.g. 32800" step="0.01" oninput="calcSN()">
              </div>
              <div class="form-group">
                <label for="sn_fluc">Fluc (USD / Ton)</label>
                <input type="number" id="sn_fluc" class="form-control" placeholder="0.00" step="0.01" oninput="calcSN()" value="0">
              </div>
              <div class="form-group">
                <label for="sn_lme_paid">LME Paid (USD / Ton)</label>
                <input type="number" id="sn_lme_paid" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="sn_tc_charges">TC (USD / Ton)</label>
                <input type="number" id="sn_tc_charges" class="form-control" placeholder="e.g. 3000" step="0.01" oninput="calcSN()" value="0">
              </div>
              <div class="form-group">
                <label for="sn_prod_charges_rate">Production Charges (USD / kg)</label>
                <input type="number" id="sn_prod_charges_rate" class="form-control" placeholder="e.g. 3.50" step="0.01" oninput="calcSN()" value="0">
              </div>
              <div class="form-group">
                <label>Sn% Grade (from Step 3)</label>
                <div id="sn_grade_display" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; font-weight: 600; color: var(--text); min-height: 38px;">—</div>
              </div>
            </div>

            <!-- SN Calculated Results -->
            <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px; margin-top: 8px; margin-bottom: 16px;">
              <div style="font-size:11px; font-weight:600; color:var(--text3); text-transform:uppercase; margin-bottom:10px;">Calculated Values (Excel Formula)</div>
              <div class="form-grid-3">
                <div class="form-group" style="margin-bottom:0;">
                  <label style="font-size:11px; color:var(--text3);">$ Price / Kg (USD)</label>
                  <div id="sn_price_per_kg" style="font-size:15px; font-weight:700; color:var(--green); padding:4px 0;">$0.00</div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label style="font-size:11px; color:var(--text3);">Purchase Value ($)</label>
                  <div id="sn_purchase_value_usd" style="font-size:15px; font-weight:700; color:var(--text); padding:4px 0;">$0.00</div>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label style="font-size:11px; color:var(--text3);">Cost/Kg (RWF)</label>
                  <div id="sn_price_per_kg_rwf" style="font-size:15px; font-weight:700; color:var(--text2); padding:4px 0;">0.00 RWF</div>
                </div>
              </div>
            </div>

            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:10px;">Government Taxes Retained</div>
            <div class="form-grid-3">
              <div class="form-group">
                <label for="sn_tax_rra">RRA <?php echo number_format($taxSettings['tax_rate_rra'], 1); ?>% (USD)</label>
                <input type="number" id="sn_tax_rra" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="sn_tax_rma">RMA <?php echo number_format($taxSettings['tax_rate_rma_tin'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="sn_tax_rma" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="sn_tax_inkomane">INKOMANE <?php echo number_format($taxSettings['tax_rate_inkomane_tin'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="sn_tax_inkomane" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="sn_prod_charges">Production Charges Total (USD)</label>
                <input type="number" id="sn_prod_charges" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="sn_net_paid">Net Paid to Supplier (USD)</label>
                <input type="number" id="sn_net_paid" class="form-control" placeholder="0.00" step="0.01" readonly style="font-weight:700; color:var(--green);">
              </div>
            </div>

            <!-- Stored values populated by calcSN() into shared hidden inputs above -->
          </div>

          <!-- ========================================================== -->
          <!-- TA (Tantalum / Coltan) PRICING FORM                        -->
          <!-- Matches: Purchase Logs_Ta Excel Sheet                      -->
          <!-- ========================================================== -->
          <div id="pricingFormTA" style="display:none;">
            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
              <span style="background:var(--orange-bg); color:var(--orange); padding:2px 8px; border-radius:4px; font-size:10px;">TA</span>
              Tantalum / Coltan Pricing — Ta Unit Formula
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="ta_price_per_ta">Price / Ta (USD/kg/%Ta) *</label>
                <input type="number" id="ta_price_per_ta" class="form-control" placeholder="e.g. 1.58" step="0.01" oninput="calcTA()">
              </div>
              <div class="form-group">
                <label>Ta205% Grade (from Step 3)</label>
                <div id="ta_grade_display" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; font-weight: 600; color: var(--text); min-height: 38px;">—</div>
              </div>
              <div class="form-group">
                <label>$ Price / Kg (USD)</label>
                <div id="ta_price_per_kg" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; font-weight: 700; color: var(--green); min-height: 38px;">$0.00</div>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="ta_prod_charges_rate">Production Charges (USD / kg)</label>
                <input type="number" id="ta_prod_charges_rate" class="form-control" placeholder="e.g. 3.50" step="0.01" oninput="calcTA()" value="0">
              </div>
              <div class="form-group">
                <label>Purchase Value ($)</label>
                <div id="ta_purchase_value_usd" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; font-weight: 700; color: var(--text); min-height: 38px;">$0.00</div>
              </div>
            </div>

            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:10px;">Government Taxes Retained</div>
            <div class="form-grid-3">
              <div class="form-group">
                <label for="ta_tax_rra">RRA <?php echo number_format($taxSettings['tax_rate_rra'], 1); ?>% (USD)</label>
                <input type="number" id="ta_tax_rra" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="ta_tax_rma">RMA <?php echo number_format($taxSettings['tax_rate_rma_coltan'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="ta_tax_rma" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="ta_tax_inkomane">INKOMANE <?php echo number_format($taxSettings['tax_rate_inkomane_coltan'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="ta_tax_inkomane" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="ta_prod_charges">Production Charges Total (USD)</label>
                <input type="number" id="ta_prod_charges" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="ta_net_paid">Supplier ($) — Net Paid (USD)</label>
                <input type="number" id="ta_net_paid" class="form-control" placeholder="0.00" step="0.01" readonly style="font-weight:700; color:var(--green);">
              </div>
            </div>

            <!-- Stored values populated by calcTA() into shared hidden inputs above -->
          </div>

          <!-- ========================================================== -->
          <!-- W03 (Wolframite / Tungsten) PRICING FORM                   -->
          <!-- Matches: Purchase Logs_W03 Excel Sheet                     -->
          <!-- ========================================================== -->
          <div id="pricingFormW03" style="display:none;">
            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:12px; display:flex; align-items:center; gap:8px;">
              <span style="background:var(--green-bg); color:var(--green); padding:2px 8px; border-radius:4px; font-size:10px;">W03</span>
              Wolframite / Tungsten Pricing — MTU Formula
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="w03_lme_price">MTU Paid (USD / MTU) *</label>
                <input type="number" id="w03_lme_price" class="form-control" placeholder="e.g. 1000" step="0.01" oninput="calcW03()">
              </div>
              <div class="form-group">
                <label for="w03_rmb_price">RMB Price / MTU (USD) *</label>
                <input type="number" id="w03_rmb_price" class="form-control" placeholder="e.g. 1500" step="0.01" oninput="calcW03()">
              </div>
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="w03_tc_charges">TC (USD / MTU)</label>
                <input type="number" id="w03_tc_charges" class="form-control" placeholder="0.00" step="0.01" oninput="calcW03()" value="0">
              </div>
              <div class="form-group">
                <label for="w03_transport_rwf">Transport (RWF / kg)</label>
                <input type="number" id="w03_transport_rwf" class="form-control" placeholder="e.g. 2000" step="0.01" oninput="calcW03()" value="2000">
              </div>
              <div class="form-group">
                <label for="w03_prod_charges_rate">Production Charges (USD / kg)</label>
                <input type="number" id="w03_prod_charges_rate" class="form-control" placeholder="0.00" step="0.01" oninput="calcW03()" value="0">
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label>WO3% Grade (from Step 3)</label>
                <div id="w03_grade_display" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 14px; font-weight: 600; color: var(--text); min-height: 38px;">—</div>
              </div>
              <div class="form-group">
                <label>$ Price / Kg (USD)</label>
                <div id="w03_price_per_kg" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; font-weight: 700; color: var(--green); min-height: 38px;">$0.00</div>
              </div>
              <div class="form-group">
                <label>Purchase Value ($)</label>
                <div id="w03_purchase_value_usd" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; font-weight: 700; color: var(--text); min-height: 38px;">$0.00</div>
              </div>
            </div>

            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; letter-spacing:0.3px; margin-bottom:10px;">Government Taxes Retained</div>
            <div class="form-grid-3">
              <div class="form-group">
                <label for="w03_tax_rra">RRA <?php echo number_format($taxSettings['tax_rate_rra'], 1); ?>% (USD)</label>
                <input type="number" id="w03_tax_rra" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="w03_tax_rma">RMA <?php echo number_format($taxSettings['tax_rate_rma_wolframite'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="w03_tax_rma" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="w03_tax_inkomane">INKOMANE <?php echo number_format($taxSettings['tax_rate_inkomane_wolframite'], 0); ?> RWF/kg (USD)</label>
                <input type="number" id="w03_tax_inkomane" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="w03_prod_charges">Production Charges Total (USD)</label>
                <input type="number" id="w03_prod_charges" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
              <div class="form-group">
                <label for="w03_net_paid">Net Paid to Supplier (USD)</label>
                <input type="number" id="w03_net_paid" class="form-control" placeholder="0.00" step="0.01" readonly style="font-weight:700; color:var(--green);">
              </div>
              <div class="form-group">
                <label>Purchase Value (RWF)</label>
                <div id="w03_purchase_value_rwf" style="padding: 8px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius); font-size: 15px; font-weight: 700; color: var(--text2); min-height: 38px;">0.00 RWF</div>
              </div>
            </div>

            <!-- Stored values populated by calcW03() into shared hidden inputs above -->
            <input type="hidden" id="w03_prod_charges_per_kg_hidden" name="production_charges_per_kg">
          </div>

          <!-- Status and Notes (common to all product types) -->
          <div id="pricingCommonFields" style="display:none;">
            <div style="height:1px; background: var(--border); margin: 20px 0;"></div>
            <!-- Summary Preview -->
            <div class="pricing-summary" id="pricingSummaryBlock">
              <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; margin-bottom:8px;">Pricing Calculation Summary</div>
              <div id="pricingSummaryPreview"></div>
            </div>
            <div style="height:1px; background: var(--border); margin: 20px 0;"></div>
            <div class="form-grid-2">
              <div class="form-group">
                <label for="status">Transaction Status</label>
                <select id="status" name="status" class="form-control">
                  <option value="pending">Pending</option>
                  <option value="confirmed">Confirmed</option>
                  <option value="received">Received</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
              <div class="form-group">
                <label id="amountInCurrencyLabel">Purchase Amount in Currency</label>
                <input type="number" id="purchaseAmountInCurrency" name="purchase_amount_in_currency" class="form-control" placeholder="0.00" step="0.01" readonly>
              </div>
            </div>
            <div class="form-group">
              <label for="notes">Notes / Remarks</label>
              <textarea id="notes" name="notes" class="form-control" placeholder="Enter transaction details or notes..." rows="3"></textarea>
            </div>
          </div>

        </div>
      </form>

      <!-- Wizard Controls -->
      <div class="purchas-wizard-footer" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between;">
        <a href="index.php" class="btn-sm" style="text-decoration: none; display: flex; align-items: center; justify-content: center; background: var(--bg); border: 1px solid var(--border); color: var(--text);">Cancel</a>
        <div style="display: flex; gap: 8px;">
          <button type="button" class="btn-sm" id="prevBtn" style="display: none; background: var(--bg); border: 1px solid var(--border); color: var(--text);">Back</button>
          <button type="button" class="btn-sm btn-primary" id="nextBtn">Next: Product Selection</button>
        </div>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<?php
$latestRate = 1400.0;
if (!empty($exchangeRatesList)) {
    foreach ($exchangeRatesList as $r) {
        if ($r['from_code'] === 'USD' && $r['to_code'] === 'RWF') {
            $latestRate = (float)$r['rate'];
            break;
        }
    }
}
?>
<script>
  window.editingPurchaseData = <?php echo ($id > 0 && $purchaseRecord) ? json_encode($purchaseRecord) : 'null'; ?>;
  window.currenciesList = <?php echo json_encode($currenciesList); ?>;
  window.exchangeRatesList = <?php echo json_encode($exchangeRatesList); ?>;
  window.latestExchangeRate = <?php echo $latestRate; ?>;
  window.taxSettings = <?php echo json_encode($taxSettings); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/record.js"></script>
</body>
</html>
