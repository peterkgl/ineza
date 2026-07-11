<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'create_sale')) {
    header("Location: index.php");
    exit();
}

if (empty($_SESSION['sales_token'])) {
    $_SESSION['sales_token'] = bin2hex(random_bytes(32));
}

// Load dropdown options
$customers = [];
$custQuery = mysqli_query($conn, "SELECT id, name, customer_code, customer_type FROM customer WHERE is_active = 1 ORDER BY name ASC");
if ($custQuery) {
    while ($row = mysqli_fetch_assoc($custQuery)) {
        $customers[] = $row;
    }
}

$warehouses = [];
$whQuery = mysqli_query($conn, "SELECT id, warehouse_name, warehouse_code FROM warehouses WHERE is_active = 1 ORDER BY warehouse_name ASC");
if ($whQuery) {
    while ($row = mysqli_fetch_assoc($whQuery)) {
        $warehouses[] = $row;
    }
}

$lots = [];
$lotQuery = mysqli_query($conn, "SELECT id, lots_code FROM lots WHERE closing_date IS NULL ORDER BY lots_code ASC");
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

$accounts = [];
$accQuery = mysqli_query($conn, "SELECT id, account_code, account_name FROM accounts WHERE account_type_id = 1 AND is_active = 1 ORDER BY account_name ASC");
if ($accQuery) {
    while ($row = mysqli_fetch_assoc($accQuery)) {
        $accounts[] = $row;
    }
}

$currencies = [];
$curQuery = mysqli_query($conn, "SELECT id, code, name, symbol, is_base_currency FROM currencies WHERE is_active = 1 ORDER BY code ASC");
if ($curQuery) {
    while ($row = mysqli_fetch_assoc($curQuery)) {
        $currencies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Record Sales Order</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/sales.css">
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

  <?php $page_title = "Record Sales Order"; include '../include/navbar.php'; ?>

  <div class="content" id="salesRecordContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Record Mineral Sales Order
        </h1>
        <div class="page-sub">Create sales invoice, adjust warehouse lot stocks, and apply customer payments.</div>
      </div>
      <div class="page-actions">
        <a href="index.php" class="btn-sm" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
          <svg class="btn-icon" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to List
        </a>
      </div>
    </div>

    <!-- Wizard Form Container -->
    <div class="sales-card card" style="max-width: 900px; margin: 0 auto; padding: 24px;">
      
      <!-- Wizard Progress Indicators -->
      <div class="wizard-progress">
        <div class="wizard-progress-bar"></div>
        <div class="wizard-step active" data-step="1">
          <div class="wizard-step-label"><span class="step-num">01</span> Customer</div>
        </div>
        <div class="wizard-step" data-step="2">
          <div class="wizard-step-label"><span class="step-num">02</span> Products</div>
        </div>
        <div class="wizard-step" data-step="3">
          <div class="wizard-step-label"><span class="step-num">03</span> Payment</div>
        </div>
        <div class="wizard-step" data-step="4">
          <div class="wizard-step-label"><span class="step-num">04</span> Summary</div>
        </div>
      </div>

      <div id="formAlertPlaceholder"></div>

      <form id="salesForm" novalidate>
        <input type="hidden" id="salesToken" name="token" value="<?php echo htmlspecialchars($_SESSION['sales_token']); ?>">

        <!-- STEP 1: Customer Details -->
        <div class="wizard-step-content active" id="step1">
          <div class="form-group">
            <label style="font-weight: 500; margin-bottom: 8px; display: block;">Customer Source Selection</label>
            <div style="display: flex; gap: 20px; margin-bottom: 15px;">
              <label style="display: flex; align-items: center; gap: 8px; font-weight: 400; cursor: pointer;">
                <input type="radio" name="customer_mode" value="select" checked style="width: auto;">
                Select Existing Customer
              </label>
              <label style="display: flex; align-items: center; gap: 8px; font-weight: 400; cursor: pointer;">
                <input type="radio" name="customer_mode" value="new" style="width: auto;">
                Register New Customer
              </label>
            </div>
          </div>

          <!-- Existing Customer Selection -->
          <div id="customerSelectSection" class="form-group">
            <label for="customerId">Choose Customer *</label>
            <select id="customerId" name="customer_id" class="form-control" required style="width: 100%;">
              <option value="">-- Select Customer --</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['customer_code']); ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- New Customer Creation Form -->
          <div id="customerNewSection" style="display: none; border-top: 1px solid var(--border); padding-top: 15px;">
            <div style="font-size: 12px; font-weight: 600; color: var(--text2); text-transform: uppercase; margin-bottom: 12px;">New Customer Demographics</div>
            <div class="form-grid-2">
              <div class="form-group">
                <label for="custName">Customer / Corporate Name *</label>
                <input type="text" id="custName" name="cust_name" class="form-control" placeholder="e.g. Rwanda Mining Trading Ltd">
              </div>
              <div class="form-group">
                <label for="custType">Customer Type</label>
                <select id="custType" name="cust_type" class="form-control">
                  <option value="company">Company</option>
                  <option value="individual">Individual</option>
                  <option value="government">Government</option>
                </select>
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="custNif">NIF / Tax ID</label>
                <input type="text" id="custNif" name="cust_nif" class="form-control" placeholder="e.g. 100293021">
              </div>
              <div class="form-group">
                <label for="custVat">VAT Registration No</label>
                <input type="text" id="custVat" name="cust_vat_reg_no" class="form-control" placeholder="e.g. VAT-RWA-102938">
              </div>
            </div>

            <div class="form-grid-3">
              <div class="form-group">
                <label for="custContact">Contact Person</label>
                <input type="text" id="custContact" name="cust_contact_person" class="form-control" placeholder="Full Name">
              </div>
              <div class="form-group">
                <label for="custPhone">Phone Number</label>
                <input type="text" id="custPhone" name="cust_phone" class="form-control" placeholder="+250 788 000 000">
              </div>
              <div class="form-group">
                <label for="custEmail">Email Address</label>
                <input type="email" id="custEmail" name="cust_email" class="form-control" placeholder="contact@company.com">
              </div>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label for="custCountry">Country</label>
                <input type="text" id="custCountry" name="cust_country" class="form-control" placeholder="e.g. Rwanda, Belgium">
              </div>
              <div class="form-group">
                <label for="custAddress">Billing / Export Address</label>
                <input type="text" id="custAddress" name="cust_address" class="form-control" placeholder="Street Address, City">
              </div>
            </div>

            <div class="form-group">
              <label for="custNotes">Administrative Notes</label>
              <textarea id="custNotes" name="cust_notes" class="form-control" placeholder="Payment habits, special discounts, credit rating info..." rows="2"></textarea>
            </div>
          </div>
        </div>

        <!-- STEP 2: Products & Line Items -->
        <div class="wizard-step-content" id="step2">
          <div class="form-grid-3">
            <div class="form-group" style="grid-column: span 2;">
              <label for="saleNo">Invoice / Sales Order Number (Auto if blank)</label>
              <input type="text" id="saleNo" name="sale_no" class="form-control" placeholder="e.g. SALE-2026-0001">
            </div>
            <div class="form-group">
              <label for="saleDate">Sale Date *</label>
              <input type="date" id="saleDate" name="sale_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group">
              <label for="paymentTerms">Payment Terms</label>
              <input type="text" id="paymentTerms" name="payment_terms" class="form-control" placeholder="e.g. Net 30, COD">
            </div>
            <div class="form-group">
              <label for="incoterms">Incoterms</label>
              <input type="text" id="incoterms" name="incoterms" class="form-control" placeholder="e.g. FOB Kigali, CIF Antwerp">
            </div>
            <div class="form-group">
              <label for="exportPermitNo">Export Permit No</label>
              <input type="text" id="exportPermitNo" name="export_permit_no" class="form-control" placeholder="e.g. EXP-RMA-2026-928">
            </div>
          </div>

          <div class="form-group">
            <label for="destinationCountry">Destination Country</label>
            <input type="text" id="destinationCountry" name="destination_country" class="form-control" placeholder="e.g. China, Belgium, USA">
          </div>

          <!-- Product Line Builder -->
          <div class="line-builder-box" style="margin-top: 24px; border: 1px solid var(--border); border-radius: 8px; padding: 16px; background: var(--bg2);">
            <div style="font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center;">
              <span>Add Product Line Item</span>
              <span id="stockCheckStatus" style="font-weight: 500; font-family: monospace; text-transform: none; color: var(--text3);">Select Warehouse/Product/Lot</span>
            </div>
            
            <div class="form-grid-3">
              <div class="form-group">
                <label for="lineWarehouseId">Warehouse *</label>
                <select id="lineWarehouseId" class="form-control">
                  <option value="">-- Select --</option>
                  <?php foreach ($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['warehouse_name'] . ' (' . $w['warehouse_code'] . ')'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="lineProductId">Product *</label>
                <select id="lineProductId" class="form-control">
                  <option value="">-- Select --</option>
                  <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['product_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label for="lineLotId">Lot *</label>
                <select id="lineLotId" class="form-control">
                  <option value="">-- Select --</option>
                  <?php foreach ($lots as $l): ?>
                    <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['lots_code']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-grid-3" style="align-items: flex-end;">
              <div class="form-group">
                <label for="lineQuantity">Quantity (kg) *</label>
                <input type="number" id="lineQuantity" class="form-control" placeholder="0.00" step="any" min="0.0001">
              </div>
              <div class="form-group">
                <label for="linePrice">Price per kg *</label>
                <input type="number" id="linePrice" class="form-control" placeholder="0.00" step="any" min="0.0001">
              </div>
              <div class="form-group">
                <button type="button" class="btn-sm btn-primary" id="addLineBtn" style="width: 100%; display: flex; justify-content: center; align-items: center; height: 38px;">
                  Add Line
                </button>
              </div>
            </div>
          </div>

          <!-- Product Lines Table -->
          <div style="margin-top: 15px;">
            <div style="font-size: 12px; font-weight: 500; margin-bottom: 8px;">Order Products List</div>
            <div class="table-container" style="border: 1px solid var(--border); border-radius: 6px;">
              <table class="data-table" id="linesTable" style="margin: 0;">
                <thead>
                  <tr>
                    <th>Warehouse</th>
                    <th>Product</th>
                    <th>Lot</th>
                    <th style="text-align: right;">Qty (kg)</th>
                    <th style="text-align: right;">Price / kg</th>
                    <th style="text-align: right;">Total</th>
                    <th style="width: 50px; text-align: center;">Action</th>
                  </tr>
                </thead>
                <tbody id="linesList">
                  <tr class="empty-row">
                    <td colspan="7" style="text-align: center; color: var(--text3); padding: 15px;">No products added yet. Use the builder above.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- STEP 3: Payment Details -->
        <div class="wizard-step-content" id="step3">
          <div class="form-grid-3">
            <div class="form-group">
              <label for="currency">Billing Currency *</label>
              <select id="currency" name="currency" class="form-control" required>
                <?php foreach ($currencies as $cur): ?>
                  <option value="<?php echo $cur['code']; ?>" data-symbol="<?php echo $cur['symbol']; ?>" <?php echo $cur['is_base_currency'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cur['name'] . ' (' . $cur['code'] . ')'); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label for="exchangeRate">Exchange Rate (vs USD Base)</label>
              <input type="number" id="exchangeRate" name="exchange_rate" class="form-control" placeholder="1.00" step="any" value="1.00">
            </div>
            <div class="form-group">
              <label>Calculated Order Total</label>
              <div id="displayOrderTotal" style="font-size: 18px; font-weight: 600; padding: 6px 12px; background: var(--bg2); border: 1px solid var(--border); border-radius: 4px; color: var(--text1);">$0.00</div>
            </div>
          </div>

          <div style="border-top: 1px solid var(--border); margin-top: 20px; padding-top: 20px;">
            <div style="font-size: 13px; font-weight: 600; color: var(--text2); text-transform: uppercase; margin-bottom: 12px;">Customer Payment Information</div>
            
            <div class="form-grid-3">
              <div class="form-group">
                <label for="amountPaid">Amount Paid *</label>
                <input type="number" id="amountPaid" name="amount_paid" class="form-control" placeholder="0.00" step="any" value="0.00" min="0">
              </div>
              <div class="form-group">
                <label for="paymentMethod">Payment Method</label>
                <select id="paymentMethod" name="payment_method" class="form-control">
                  <option value="CASH">Cash</option>
                  <option value="BANK_TRANSFER">Bank Transfer</option>
                  <option value="CHEQUE">Cheque</option>
                  <option value="MOBILE_MONEY">Mobile Money</option>
                </select>
              </div>
              <div class="form-group">
                <label for="accountId">Cash/Bank Account *</label>
                <select id="accountId" name="account_id" class="form-control">
                  <option value="">-- Select Cash/Bank Account --</option>
                  <?php foreach ($accounts as $a): ?>
                    <option value="<?php echo $a['id']; ?>"><?php echo htmlspecialchars($a['account_name'] . ' (' . $a['account_code'] . ')'); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label for="referenceNo">Payment Reference / Txn ID</label>
              <input type="text" id="referenceNo" name="reference_no" class="form-control" placeholder="e.g. TXN-102930291, Bank Ref Number">
            </div>
          </div>
        </div>

        <!-- STEP 4: Transaction Summary -->
        <div class="wizard-step-content" id="step4">
          <div style="font-size: 13px; font-weight: 600; color: var(--text2); text-transform: uppercase; margin-bottom: 15px; border-bottom: 1px solid var(--border); padding-bottom: 8px;">Order &amp; Payment Summary Details</div>
          
          <div class="summary-box" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div>
              <table style="width:100%; font-size: 13px; border-collapse: collapse;">
                <tr style="height: 32px; border-bottom: 1px solid var(--border);">
                  <td style="color:var(--text3);">Customer:</td>
                  <td style="font-weight:600; text-align:right;" id="sumCustomer"> Rwandan Mining Ltd</td>
                </tr>
                <tr style="height: 32px; border-bottom: 1px solid var(--border);">
                  <td style="color:var(--text3);">Order Number:</td>
                  <td style="font-weight:600; text-align:right;" id="sumSaleNo">SALE-102031</td>
                </tr>
                <tr style="height: 32px; border-bottom: 1px solid var(--border);">
                  <td style="color:var(--text3);">Order Date:</td>
                  <td style="font-weight:600; text-align:right;" id="sumSaleDate">2026-06-28</td>
                </tr>
                <tr style="height: 32px; border-bottom: 1px solid var(--border);">
                  <td style="color:var(--text3);">Total Items:</td>
                  <td style="font-weight:600; text-align:right;" id="sumTotalItems">0 lines</td>
                </tr>
              </table>
            </div>
            
            <div style="background: var(--bg2); border: 1px solid var(--border); border-radius: 8px; padding: 16px; display: flex; flex-direction: column; justify-content: center; gap: 8px;">
              <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text3);">
                <span>Total Invoice Value:</span>
                <span style="font-weight: 600; color: var(--text1);" id="sumTotalVal">$0.00</span>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--green);">
                <span>Total Amount Paid:</span>
                <span style="font-weight: 600;" id="sumAmountPaid">$0.00</span>
              </div>
              <div style="display: flex; justify-content: space-between; font-size: 13px; border-top: 1px solid var(--border); padding-top: 8px; color: var(--red); font-weight:600;">
                <span>Balance Receivable:</span>
                <span id="sumBalance">$0.00</span>
              </div>
            </div>
          </div>

          <div style="margin-top: 24px;">
            <label for="notes">General Sales Memo / Remarks</label>
            <textarea id="notes" name="notes" class="form-control" placeholder="Export details, terms, special shipment arrangements..." rows="3"></textarea>
          </div>
        </div>
      </form>

      <!-- Wizard Controls -->
      <div class="sales-wizard-footer" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; justify-content: space-between;">
        <a href="index.php" class="btn-sm" style="text-decoration: none; display: flex; align-items: center; justify-content: center; background: var(--bg); border: 1px solid var(--border); color: var(--text);">Cancel</a>
        <div style="display: flex; gap: 8px;">
          <button type="button" class="btn-sm" id="prevBtn" style="display: none; background: var(--bg); border: 1px solid var(--border); color: var(--text);">Back</button>
          <button type="button" class="btn-sm btn-primary" id="nextBtn">Next: Product Lines</button>
        </div>
      </div>

    </div>
    
    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/sales.js"></script>
</body>
</html>
