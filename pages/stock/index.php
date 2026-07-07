<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_stock')) {
    header("Location: ../dashboard");
    exit();
}

$query = "SELECT 
            s.*, 
            w.warehouse_name, 
            w.warehouse_code,
            pr.product_name, 
            pr.product_code,
            l.lots_code, 
            l.closing_date AS lot_closing_date,
            uom.code AS uom_code
          FROM stock s
          JOIN warehouses w ON s.warehouse_id = w.id
          JOIN product pr ON s.product_id = pr.id
          JOIN lots l ON s.lot_id = l.id
          LEFT JOIN unit_of_measure uom ON s.uom_id = uom.id
          ORDER BY w.warehouse_name ASC, pr.product_name ASC, l.lots_code ASC";

$result = mysqli_query($conn, $query);
$stockData = [];
$totalQty = 0.0;
$totalValUsd = 0.0;
$totalValRwf = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $stockData[] = [
            'id' => (int)$row['id'],
            'warehouse_id' => (int)$row['warehouse_id'],
            'warehouse_name' => $row['warehouse_name'],
            'warehouse_code' => $row['warehouse_code'],
            'product_id' => (int)$row['product_id'],
            'product_name' => $row['product_name'],
            'product_code' => $row['product_code'],
            'lot_id' => (int)$row['lot_id'],
            'lots_code' => $row['lots_code'],
            'lot_closing_date' => $row['lot_closing_date'],
            'qty_purchased' => (float)$row['qty_purchased'],
            'qty_sold' => (float)$row['qty_sold'],
            'qty_adjusted' => (float)$row['qty_adjusted'],
            'qty_on_hand' => (float)$row['qty_on_hand'],
            'opening' => (float)$row['opening'],
            'closing' => (float)$row['closing'],
            'avg_cost_per_kg_usd' => (float)$row['avg_cost_per_kg_usd'],
            'avg_cost_per_kg_rwf' => (float)$row['avg_cost_per_kg_rwf'],
            'total_value_usd' => (float)$row['total_value_usd'],
            'total_value_rwf' => (float)$row['total_value_rwf'],
            'uom_code' => $row['uom_code'] ?? 'kg'
        ];
        $totalQty += (float)$row['qty_on_hand'];
        $totalValUsd += (float)$row['total_value_usd'];
        $totalValRwf += (float)$row['total_value_rwf'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Stock Levels</title>
<meta name="description" content="View mineral inventory stock levels and movements.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/stock.css">
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

  <?php $page_title = "Mineral Inventory Stock"; include '../include/navbar.php'; ?>

  <div class="content" id="stockContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          Stock Summary
        </h1>
        <div class="page-sub">Monitor physical mineral inventory balances, UOM metrics, valuations, and ledger logs.</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh Data
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          </div>
          <span class="stat-trend trend-blue">On Hand</span>
        </div>
        <div class="stat-val" id="stat-total-qty"><?php echo number_format($totalQty, 2); ?> <span style="font-size: 14px; font-weight: 500;">kg</span></div>
        <div class="stat-label">Total Mineral Quantity</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">USD</span>
        </div>
        <div class="stat-val" id="stat-total-usd">$<?php echo number_format($totalValUsd, 2); ?></div>
        <div class="stat-label">Stock Valuation (USD)</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--purple-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--purple)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-purple">RWF</span>
        </div>
        <div class="stat-val" id="stat-total-rwf">Frw <?php echo number_format($totalValRwf, 0); ?></div>
        <div class="stat-label">Stock Valuation (RWF)</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div class="card-title">Available Warehouse Stocks</div>
        <input type="text" id="searchInput" class="form-control" placeholder="Search stock (warehouse, product, lot)..." style="max-width: 320px; padding: 6px 12px; font-size: 12px; margin: 0;">
      </div>

      <div class="table-container">
        <table class="data-table" id="stockTable">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th>Warehouse</th>
              <th>Product</th>
              <th>Lot Code</th>
              <th style="text-align: right;">Purchased (kg)</th>
              <th style="text-align: right;">Sold (kg)</th>
              <th style="text-align: right;">Adjusted (kg)</th>
              <th style="text-align: right;">Opening (kg)</th>
              <th style="text-align: right;">Closing (kg)</th>
              <th style="text-align: right;">On Hand (kg)</th>
              <th style="text-align: right;">Avg Cost (USD/kg)</th>
              <th style="text-align: right;">Total Value (USD)</th>
              <th style="width: 150px; text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody id="stockList">
            <?php if (empty($stockData)): ?>
              <tr>
                <td colspan="13" class="table-empty">No stock records found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($stockData as $index => $row): ?>
                <?php
                  $globalIndex = $index + 1;
                  $whName = htmlspecialchars($row['warehouse_name'] . ' (' . $row['warehouse_code'] . ')');
                  $prodName = htmlspecialchars($row['product_name'] . ' (' . $row['product_code'] . ')');
                  $lotCode = htmlspecialchars($row['lots_code']);
                  $qtyPurch = number_format($row['qty_purchased'], 2);
                  $qtySold = number_format($row['qty_sold'], 2);
                  
                  $adjVal = (float)$row['qty_adjusted'];
                  if ($adjVal < 0) {
                      $qtyAdjHtml = '<span style="color: var(--red); font-weight: 500;">-' . number_format(abs($adjVal), 2) . '</span>';
                  } elseif ($adjVal > 0) {
                      $qtyAdjHtml = '<span style="color: var(--green); font-weight: 500;">+' . number_format($adjVal, 2) . '</span>';
                  } else {
                      $qtyAdjHtml = '0.00';
                  }
                  
                  $qtyOpening = number_format($row['opening'], 2);
                  $qtyClosing = number_format($row['closing'], 2);
                  $qtyHand = number_format($row['qty_on_hand'], 2);
                  $avgCost = '$' . number_format($row['avg_cost_per_kg_usd'], 2);
                  $totVal = '$' . number_format($row['total_value_usd'], 2);
                ?>
                <tr class="stock-row" data-id="<?php echo $row['id']; ?>">
                  <td><?php echo $globalIndex; ?></td>
                  <td class="td-name"><?php echo $whName; ?></td>
                  <td class="td-bold"><?php echo $prodName; ?></td>
                  <td><span class="status-pill pill-purple" style="font-weight:600;"><?php echo $lotCode; ?></span></td>
                  <td style="text-align: right; color: var(--text2);"><?php echo $qtyPurch; ?></td>
                  <td style="text-align: right; color: var(--text2);"><?php echo $qtySold; ?></td>
                  <td style="text-align: right;"><?php echo $qtyAdjHtml; ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo $qtyOpening; ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo $qtyClosing; ?></td>
                  <td style="text-align: right; font-weight: 600;"><?php echo $qtyHand; ?></td>
                  <td style="text-align: right; color: var(--text2);"><?php echo $avgCost; ?></td>
                  <td style="text-align: right; font-weight: 600; color: var(--green);"><?php echo $totVal; ?></td>
                  <td style="text-align: center;">
                    <div style="display: flex; gap: 4px; justify-content: center; align-items: center;">
                      <a href="movements.php?product_id=<?php echo $row['product_id']; ?>&lot_id=<?php echo $row['lot_id']; ?>" 
                         class="btn-sm" 
                         style="padding: 4px 8px; font-size: 11px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                        Stock Movement
                      </a>
                      <?php if (empty($row['lot_closing_date'])): ?>
                      <button class="btn-sm adjust-btn" 
                              data-id="<?php echo $row['id']; ?>"
                              data-wh-name="<?php echo $whName; ?>"
                              data-prod-name="<?php echo $prodName; ?>"
                              data-lot-code="<?php echo $lotCode; ?>"
                              style="padding: 4px 8px; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; background: var(--orange); border-color: var(--orange); cursor: pointer;">
                        Adjusted
                      </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>    <div class="bottom-spacer"></div>
  </div>
</div>

<style>
@keyframes slideInRight {
  from {
    transform: translateX(100%);
  }
  to {
    transform: translateX(0);
  }
}
#adjustCloseBtn:hover {
  background: var(--bg) !important;
  color: var(--text) !important;
}
</style>

<!-- Slide-out Drawer: Adjust Stock -->
<div class="confirm-modal-overlay" id="adjustStockModalOverlay" style="display: none; justify-content: flex-end; align-items: stretch;">
  <div class="confirm-modal" style="width: 100%; max-width: 400px; height: 100vh; border-radius: 16px 0 0 16px; margin: 0; display: flex; flex-direction: column; padding: 0; box-shadow: -10px 0 30px rgba(0, 0, 0, 0.1); border: none; border-left: 1px solid var(--border); animation: slideInRight 0.25s cubic-bezier(0.16, 1, 0.3, 1);">
    
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid var(--border);">
      <div class="confirm-title" style="font-size: 15px; font-weight: 600; color: var(--text); margin: 0; display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24" style="stroke: var(--orange); fill: none; stroke-width: 2; width: 20px; height: 20px;"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Adjust Stock Balance
      </div>
      <button id="adjustCloseBtn" style="background: none; border: none; color: var(--text3); cursor: pointer; padding: 4px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; transition: background 0.15s, color 0.15s;">
        <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; stroke: currentColor; fill: none; stroke-width: 2.5;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    
    <!-- Body (Scrollable) -->
    <div class="confirm-body" style="text-align: left; margin: 0; padding: 24px; flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 20px;">
      <!-- Stock Reference Card -->
      <div style="display: flex; flex-direction: column; gap: 8px; background: var(--bg); padding: 14px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: inset 0 1px 2px rgba(0,0,0,0.015);">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
          <span style="font-size: 11px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Warehouse</span>
          <span id="adjustWhName" style="font-size: 12px; font-weight: 600; color: var(--text);"></span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px dashed var(--border); padding-bottom: 8px;">
          <span style="font-size: 11px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Product</span>
          <span id="adjustProdName" style="font-size: 12px; font-weight: 600; color: var(--text);"></span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 11px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Lot Code</span>
          <span id="adjustLotCode" style="font-size: 12px; font-weight: 600; color: var(--text);"></span>
        </div>
      </div>
      
      <div id="adjustAlertPlaceholder"></div>

      <form id="adjustStockForm" novalidate style="display: flex; flex-direction: column; gap: 18px; width: 100%; box-sizing: border-box;">
        <input type="hidden" id="adjustStockId" name="stock_id">
        
        <!-- Segmented Control for Adjustment Type -->
        <div class="form-group" style="margin-bottom: 0; width: 100%; display: flex; flex-direction: column; gap: 8px; box-sizing: border-box;">
          <label style="display: block; font-size: 11px; font-weight: 600; color: var(--text2); margin: 0; text-transform: uppercase; letter-spacing: 0.5px; box-sizing: border-box; width: 100%;">Adjustment Type *</label>
          <div style="display: flex; gap: 10px; width: 100%; box-sizing: border-box;">
            <!-- Loss Option -->
            <label id="adjustTypeLossLabel" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 8px; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all 0.2s ease-in-out; background: var(--bg); user-select: none; box-sizing: border-box;">
              <input type="radio" name="adjustment_type" value="loss" style="display: none;">
              <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; stroke: var(--red); fill: none; stroke-width: 2.5; margin-bottom: 6px;"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              <span style="font-size: 12px; font-weight: 600; color: var(--text2); transition: color 0.2s;">Loss</span>
            </label>
            <!-- Gain Option -->
            <label id="adjustTypeGainLabel" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 12px 8px; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all 0.2s ease-in-out; background: var(--bg); user-select: none; box-sizing: border-box;">
              <input type="radio" name="adjustment_type" value="gain" style="display: none;">
              <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; stroke: var(--green); fill: none; stroke-width: 2.5; margin-bottom: 6px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              <span style="font-size: 12px; font-weight: 600; color: var(--text2); transition: color 0.2s;">Gain</span>
            </label>
          </div>
        </div>

        <!-- Quantity Input -->
        <div class="form-group" style="margin-bottom: 0; width: 100%; display: flex; flex-direction: column; gap: 8px; box-sizing: border-box;">
          <label for="adjustQty" style="display: block; font-size: 11px; font-weight: 600; color: var(--text2); margin: 0; text-transform: uppercase; letter-spacing: 0.5px; box-sizing: border-box; width: 100%;">Quantity *</label>
          <input type="number" id="adjustQty" name="quantity" class="form-control" placeholder="e.g. 0.00 kg" min="0.0001" step="any" required style="font-size: 13.5px; height: 38px; width: 100%; box-sizing: border-box; display: block; margin: 0; padding: 8px 10px;">
        </div>

        <!-- Notes / Reason -->
        <div class="form-group" style="margin-bottom: 0; width: 100%; display: flex; flex-direction: column; gap: 8px; box-sizing: border-box;">
          <label for="adjustNotes" style="display: block; font-size: 11px; font-weight: 600; color: var(--text2); margin: 0; text-transform: uppercase; letter-spacing: 0.5px; box-sizing: border-box; width: 100%;">Notes / Reason</label>
          <input type="text" id="adjustNotes" name="notes" class="form-control" placeholder="e.g. Inventory discrepancy audit" style="font-size: 13px; height: 38px; width: 100%; box-sizing: border-box; display: block; margin: 0; padding: 8px 10px;">
        </div>
      </form>
    </div>
    
    <!-- Footer -->
    <div class="confirm-footer" style="border-top: 1px solid var(--border); padding: 20px 24px; display: flex; justify-content: flex-end; gap: 10px; background: var(--card); z-index: 10;">
      <button class="btn-sm" id="adjustCancelBtn" style="height: 36px; padding: 0 16px; font-size: 12px; font-weight: 500;">Cancel</button>
      <button class="btn-sm btn-primary" id="adjustSaveBtn" style="background:var(--orange); border-color:var(--orange); height: 36px; padding: 0 18px; font-size: 12px; font-weight: 600; transition: all 0.2s;">Save Adjustment</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
  var adjustStockModalOverlay = document.getElementById("adjustStockModalOverlay");
  var adjustStockForm = document.getElementById("adjustStockForm");
  var adjustWhName = document.getElementById("adjustWhName");
  var adjustProdName = document.getElementById("adjustProdName");
  var adjustLotCode = document.getElementById("adjustLotCode");
  var adjustStockId = document.getElementById("adjustStockId");
  var adjustQty = document.getElementById("adjustQty");
  var adjustNotes = document.getElementById("adjustNotes");
  var adjustCancelBtn = document.getElementById("adjustCancelBtn");
  var adjustCloseBtn = document.getElementById("adjustCloseBtn");
  var adjustSaveBtn = document.getElementById("adjustSaveBtn");
  var adjustAlertPlaceholder = document.getElementById("adjustAlertPlaceholder");

  // Segmented Type Controls
  var adjustTypeLossLabel = document.getElementById("adjustTypeLossLabel");
  var adjustTypeGainLabel = document.getElementById("adjustTypeGainLabel");
  var lossRadio = adjustTypeLossLabel.querySelector('input[type="radio"]');
  var gainRadio = adjustTypeGainLabel.querySelector('input[type="radio"]');

  function updateAdjustmentTypeUI(selectedType) {
    if (selectedType === "loss") {
      // Set Loss style
      adjustTypeLossLabel.style.background = "var(--red-bg)";
      adjustTypeLossLabel.style.borderColor = "rgba(163, 45, 45, 0.4)";
      adjustTypeLossLabel.style.boxShadow = "0 0 0 3px rgba(163, 45, 45, 0.1)";
      adjustTypeLossLabel.querySelector("span").style.color = "var(--red)";
      
      // Reset Gain style
      adjustTypeGainLabel.style.background = "var(--bg)";
      adjustTypeGainLabel.style.borderColor = "var(--border)";
      adjustTypeGainLabel.style.boxShadow = "none";
      adjustTypeGainLabel.querySelector("span").style.color = "var(--text2)";
      
      lossRadio.checked = true;
    } else if (selectedType === "gain") {
      // Set Gain style
      adjustTypeGainLabel.style.background = "var(--green-bg)";
      adjustTypeGainLabel.style.borderColor = "rgba(15, 110, 86, 0.4)";
      adjustTypeGainLabel.style.boxShadow = "0 0 0 3px rgba(15, 110, 86, 0.1)";
      adjustTypeGainLabel.querySelector("span").style.color = "var(--green)";
      
      // Reset Loss style
      adjustTypeLossLabel.style.background = "var(--bg)";
      adjustTypeLossLabel.style.borderColor = "var(--border)";
      adjustTypeLossLabel.style.boxShadow = "none";
      adjustTypeLossLabel.querySelector("span").style.color = "var(--text2)";
      
      gainRadio.checked = true;
    } else {
      // Reset both
      adjustTypeLossLabel.style.background = "var(--bg)";
      adjustTypeLossLabel.style.borderColor = "var(--border)";
      adjustTypeLossLabel.style.boxShadow = "none";
      adjustTypeLossLabel.querySelector("span").style.color = "var(--text2)";
      
      adjustTypeGainLabel.style.background = "var(--bg)";
      adjustTypeGainLabel.style.borderColor = "var(--border)";
      adjustTypeGainLabel.style.boxShadow = "none";
      adjustTypeGainLabel.querySelector("span").style.color = "var(--text2)";
      
      lossRadio.checked = false;
      gainRadio.checked = false;
    }
  }

  // Click listeners for the segmented controls
  adjustTypeLossLabel.addEventListener("click", function () {
    updateAdjustmentTypeUI("loss");
  });
  adjustTypeGainLabel.addEventListener("click", function () {
    updateAdjustmentTypeUI("gain");
  });
  
  // Attach event listener to Adjust buttons
  var adjustBtns = document.querySelectorAll(".adjust-btn");
  adjustBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var id = btn.getAttribute("data-id");
      var whName = btn.getAttribute("data-wh-name");
      var prodName = btn.getAttribute("data-prod-name");
      var lotCode = btn.getAttribute("data-lot-code");
      
      adjustStockId.value = id;
      adjustWhName.textContent = whName;
      adjustProdName.textContent = prodName;
      adjustLotCode.textContent = lotCode;
      
      // Reset form
      adjustStockForm.reset();
      updateAdjustmentTypeUI(null);
      adjustAlertPlaceholder.innerHTML = "";
      
      adjustStockModalOverlay.style.display = "flex";
    });
  });
  
  if (adjustCancelBtn) {
    adjustCancelBtn.addEventListener("click", function () {
      adjustStockModalOverlay.style.display = "none";
    });
  }

  if (adjustCloseBtn) {
    adjustCloseBtn.addEventListener("click", function () {
      adjustStockModalOverlay.style.display = "none";
    });
  }
  
  if (adjustStockForm) {
    adjustStockForm.addEventListener("submit", function (e) {
      e.preventDefault();
      submitAdjustment();
    });
  }
  
  if (adjustSaveBtn) {
    adjustSaveBtn.addEventListener("click", function () {
      submitAdjustment();
    });
  }
  
  function submitAdjustment() {
    var stockId = adjustStockId.value;
    var checkedRadio = adjustStockForm.querySelector('input[name="adjustment_type"]:checked');
    var type = checkedRadio ? checkedRadio.value : "";
    var qty = parseFloat(adjustQty.value);
    var notes = adjustNotes.value.trim();
    
    if (!type) {
      showModalAlert("Adjustment type (Loss or Gain) is required.");
      return;
    }
    if (isNaN(qty) || qty <= 0) {
      showModalAlert("A valid quantity greater than 0 is required.");
      return;
    }
    
    adjustSaveBtn.disabled = true;
    adjustSaveBtn.textContent = "Saving...";
    
    var formData = new URLSearchParams();
    formData.append("stock_id", stockId);
    formData.append("adjustment_type", type);
    formData.append("quantity", qty);
    formData.append("notes", notes);
    
    fetch("stock_api.php?action=adjust", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: formData.toString()
    })
    .then(function (res) { return res.json(); })
    .then(function (result) {
      adjustSaveBtn.disabled = false;
      adjustSaveBtn.textContent = "Save Adjustment";
      
      if (result.success) {
        adjustStockModalOverlay.style.display = "none";
        window.location.reload();
      } else {
        showModalAlert(result.message);
      }
    })
    .catch(function (err) {
      console.error(err);
      adjustSaveBtn.disabled = false;
      adjustSaveBtn.textContent = "Save Adjustment";
      showModalAlert("An error occurred while saving the adjustment.");
    });
  }
  
  function showModalAlert(message) {
    adjustAlertPlaceholder.innerHTML = '<div class="alert-banner error" style="margin-bottom: 12px;">' +
      '<svg viewBox="0 0 24 24" style="width:15px; height:15px; fill:none; stroke-width:2; stroke:currentColor;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>' +
      '<span>' + escapeHtml(message) + '</span></div>';
  }
  
  function escapeHtml(str) {
    if (!str) return "";
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }

  // Search filter
  var searchInput = document.getElementById("searchInput");
  if (searchInput) {
    searchInput.addEventListener("input", function () {
      var query = searchInput.value.toLowerCase().trim();
      var rows = document.querySelectorAll(".stock-row");
      rows.forEach(function (row) {
        var text = row.textContent.toLowerCase();
        if (text.indexOf(query) !== -1) {
          row.style.display = "";
        } else {
          row.style.display = "none";
        }
      });
    });
  }
});
</script>
</html>
