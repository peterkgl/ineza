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
    var currentTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
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
                  <option value="<?php echo $p['id']; ?>" data-uom-id="<?php echo $p['uom_id']; ?>"><?php echo htmlspecialchars($p['product_name']); ?> (<?php echo htmlspecialchars($p['product_code']); ?>)</option>
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
            <input type="number" id="quantityKg" name="quantity_kg" class="form-control" placeholder="0.00" step="any" min="0.0001" required>
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

        <!-- STEP 4: Pricing & Financials -->
        <div class="wizard-step-content" id="step4">
          <div class="form-grid-3">
            <div class="form-group">
              <label for="exchangeRate">Exchange Rate (RWF/USD)</label>
              <input type="number" id="exchangeRate" name="exchange_rate" class="form-control" placeholder="1400.00" step="any" value="1400.00">
            </div>
            <div class="form-group">
              <label for="lmePrice">LME Price (USD / Ton or Unit)</label>
              <input type="number" id="lmePrice" name="lme_price" class="form-control" placeholder="e.g. 180000.00" step="any">
            </div>
            <div class="form-group">
              <label for="tcCharges">TC Charges (USD / Ton)</label>
              <input type="number" id="tcCharges" name="tc_charges" class="form-control" placeholder="e.g. 120.00" step="any">
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="pricePerKgUsd">Price per kg (USD) *</label>
              <input type="number" id="pricePerKgUsd" name="price_per_kg_usd" class="form-control" placeholder="600" step="any" required>
            </div>
            <div class="form-group">
              <label for="pricePerKgRwf">Price per kg (RWF)</label>
              <input type="number" id="pricePerKgRwf" name="price_per_kg_rwf" class="form-control" placeholder="600" step="any">
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="purchaseValueUsd">Purchase Value (USD)</label>
              <input type="number" id="purchaseValueUsd" name="purchase_value_usd" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
            <div class="form-group">
              <label for="purchaseValueRwf">Purchase Value (RWF)</label>
              <input type="number" id="purchaseValueRwf" name="purchase_value_rwf" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group">
              <label for="taxRra">RRA Tax (USD)</label>
              <input type="number" id="taxRra" name="tax_rra" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
            <div class="form-group">
              <label for="taxRma">RMA Tax (USD)</label>
              <input type="number" id="taxRma" name="tax_rma" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
            <div class="form-group">
              <label for="taxInkomane">Inkomane Tax (USD)</label>
              <input type="number" id="taxInkomane" name="tax_inkomane" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
          </div>

          <div class="form-grid-2">
            <!-- <div class="form-group">
              <label for="chargesPerKg">Other Charges per kg</label>
              <input type="number" id="chargesPerKg" name="charges_per_kg" class="form-control" placeholder="0.00" step="any">
            </div> -->
            <!-- <div class="form-group">
              <label for="pricePerTaUnit">Price per Ta Unit</label>
              <input type="number" id="pricePerTaUnit" name="price_per_ta_unit" class="form-control" placeholder="0.00" step="any">
            </div> -->
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="productionChargesPerKg">Production Charges per kg (USD)</label>
              <input type="number" id="productionChargesPerKg" name="production_charges_per_kg" class="form-control" placeholder="e.g. 3.50" step="any">
            </div>
            <div class="form-group">
              <label for="productionCharges">Production Charges (Total USD)</label>
              <input type="number" id="productionCharges" name="production_charges" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label for="netPaidSupplierUsd">Net Paid to Supplier (USD)</label>
              <input type="number" id="netPaidSupplierUsd" name="net_paid_supplier_usd" class="form-control" placeholder="0.00" step="any" readonly>
            </div>
            <div class="form-group">
              <label for="status">Transaction Status</label>
              <select id="status" name="status" class="form-control">
                <option value="pending">Pending</option>
                <option value="confirmed">Confirmed</option>
                <option value="received">Received</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="notes">Notes / Remarks</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Enter transaction details or notes..." rows="3"></textarea>
          </div>

          <!-- Real-time calculation preview card -->
          <div class="pricing-summary">
            <div style="font-size:11px; font-weight:600; color:var(--text2); text-transform:uppercase; margin-bottom:8px;">Pricing Calculation Summary</div>
            <div id="pricingSummaryPreview">
              <!-- Rendered via JS -->
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

<script>
  window.editingPurchaseData = <?php echo ($id > 0 && $purchaseRecord) ? json_encode($purchaseRecord) : 'null'; ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/record.js"></script>
</body>
</html>
