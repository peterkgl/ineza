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
    var currentTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
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
                  $qtyAdj = number_format($row['qty_adjusted'], 2);
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
                  <td style="text-align: right; color: var(--text2);"><?php echo $qtyAdj; ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo $qtyOpening; ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo $qtyClosing; ?></td>
                  <td style="text-align: right; font-weight: 600;"><?php echo $qtyHand; ?></td>
                  <td style="text-align: right; color: var(--text2);"><?php echo $avgCost; ?></td>
                  <td style="text-align: right; font-weight: 600; color: var(--green);"><?php echo $totVal; ?></td>
                  <td style="text-align: center;">
                    <a href="movements.php?product_id=<?php echo $row['product_id']; ?>" 
                       class="btn-sm" 
                       style="padding: 4px 8px; font-size: 11px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                      Stock Movement
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
