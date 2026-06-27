<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_stock_movement')) {
    header("Location: ../dashboard");
    exit();
}

$selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// Fetch all active products for the dropdown filter
$products = [];
$prodQuery = mysqli_query($conn, "SELECT id, product_code, product_name FROM product ORDER BY product_name ASC");
if ($prodQuery) {
    while ($row = mysqli_fetch_assoc($prodQuery)) {
        $products[] = $row;
    }
}

// Build the movement query
$whereClause = "";
if ($selectedProductId > 0) {
    $whereClause = "WHERE sm.product_id = $selectedProductId";
}

$query = "SELECT sm.*, 
                 CONCAT(u.first_name, ' ', u.last_name) AS creator_name, 
                 uom.code AS uom_code, 
                 p.purchase_no,
                 w.warehouse_name, 
                 w.warehouse_code, 
                 l.lots_code,
                 pr.product_name,
                 pr.product_code
          FROM stock_movement sm
          LEFT JOIN users u ON sm.created_by = u.id
          LEFT JOIN unit_of_measure uom ON sm.uom_id = uom.id
          LEFT JOIN purchasing p ON sm.reference_type = 'purchasing' AND sm.reference_id = p.id
          LEFT JOIN warehouses w ON sm.warehouse_id = w.id
          LEFT JOIN lots l ON sm.lot_id = l.id
          LEFT JOIN product pr ON sm.product_id = pr.id
          $whereClause
          ORDER BY sm.movement_date DESC, sm.id DESC";

$result = mysqli_query($conn, $query);
$movements = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $movements[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Stock Movements</title>
<meta name="description" content="View historical ledger of mineral inventory inflows and outflows.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/stock.css">
<style>
  .clickable-row {
    cursor: pointer;
    transition: background-color 0.15s ease;
  }
  .clickable-row:hover {
    background-color: var(--border) !important;
  }
  .btn-icon-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--orange);
    transition: transform 0.2s;
  }
  .btn-icon-link:hover {
    transform: translateX(2px);
  }
</style>
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

  <?php $page_title = "Inventory Ledger"; include '../include/navbar.php'; ?>

  <div class="content" id="stockContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="stroke: var(--orange); stroke-width: 2; fill: none; width: 22px; height: 22px; margin-right: 8px;">
            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
          </svg>
          Stock Movements Log
        </h1>
        <div class="page-sub">Track all inflows, outflows, and adjustments for physical inventory.</div>
      </div>
      <div class="page-actions">
        <a href="index.php" class="btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
          <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; stroke-width: 2; fill: none;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to Stock
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
        <div class="card-title">Historical Log</div>
        <div style="display: flex; gap: 8px; align-items: center;">
          <label for="productFilter" style="font-size: 11px; font-weight: 600; text-transform: uppercase; color: var(--text2);">Filter Product:</label>
          <select id="productFilter" class="form-control" style="max-width: 220px; padding: 5px 8px; font-size: 12px; margin: 0;" onchange="filterProduct(this.value)">
            <option value="0">All Products</option>
            <?php foreach ($products as $p): ?>
              <option value="<?php echo $p['id']; ?>" <?php echo ($selectedProductId === (int)$p['id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($p['product_name'] . ' (' . $p['product_code'] . ')'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Product</th>
              <th>Warehouse</th>
              <th>Lot Code</th>
              <th style="text-align: right;">Quantity</th>
              <th style="text-align: right;">Opening (kg)</th>
              <th style="text-align: right;">Closing (kg)</th>
              <th style="text-align: right;">Unit Cost (USD)</th>
              <th style="text-align: right;">Total Value (USD)</th>
              <th>Reference</th>
              <th>Recorded By</th>
              <th style="width: 100px; text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($movements)): ?>
              <tr>
                <td colspan="13" class="table-empty">No stock movements recorded.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($movements as $m): ?>
                <?php
                  $date = htmlspecialchars($m['movement_date']);
                  $prod = htmlspecialchars($m['product_name'] . ' (' . $m['product_code'] . ')');
                  $wh = htmlspecialchars($m['warehouse_name'] . ' (' . $m['warehouse_code'] . ')');
                  $lot = htmlspecialchars($m['lots_code'] ?? '—');
                  $qty = number_format((float)$m['qty_kg'], 2) . ' ' . htmlspecialchars($m['uom_code'] ?? 'kg');
                  
                  $cost = $m['unit_cost_usd'] !== null ? '$' . number_format((float)$m['unit_cost_usd'], 2) : '—';
                  $total = $m['total_value_usd'] !== null ? '$' . number_format((float)$m['total_value_usd'], 2) : '—';
                  
                  // Reference
                  $ref = '—';
                  if ($m['reference_type'] === 'purchasing' && $m['purchase_no']) {
                    $ref = htmlspecialchars($m['purchase_no']);
                  } elseif ($m['reference_type']) {
                    $ref = htmlspecialchars(strtoupper($m['reference_type']) . ' #' . $m['reference_id']);
                  }
                  
                  $creator = htmlspecialchars($m['creator_name'] ?? 'System');
                  
                  // Badge styling
                  $typeBadge = '';
                  switch ($m['movement_type']) {
                    case 'PURCHASE_IN':
                      $typeBadge = '<span class="status-pill pill-green">PURCHASE IN</span>';
                      break;
                    case 'SALE_OUT':
                      $typeBadge = '<span class="status-pill pill-red">SALE OUT</span>';
                      break;
                    case 'TRANSFER_IN':
                      $typeBadge = '<span class="status-pill pill-blue">TRANSFER IN</span>';
                      break;
                    case 'TRANSFER_OUT':
                      $typeBadge = '<span class="status-pill pill-orange">TRANSFER OUT</span>';
                      break;
                    case 'ADJUSTMENT_IN':
                      $typeBadge = '<span class="status-pill pill-green">ADJ IN</span>';
                      break;
                    case 'ADJUSTMENT_OUT':
                      $typeBadge = '<span class="status-pill pill-red">ADJ OUT</span>';
                      break;
                    default:
                      $typeBadge = '<span class="status-pill">' . htmlspecialchars($m['movement_type']) . '</span>';
                  }
                ?>
                <tr class="clickable-row" onclick="window.location='movement_details.php?id=<?php echo $m['id']; ?>'">
                  <td style="white-space: nowrap;"><?php echo $date; ?></td>
                  <td><?php echo $typeBadge; ?></td>
                  <td class="td-bold"><?php echo $prod; ?></td>
                  <td class="td-name"><?php echo $wh; ?></td>
                  <td><span class="status-pill pill-purple" style="font-weight: 600;"><?php echo $lot; ?></span></td>
                  <td style="text-align: right; font-weight: 500;"><?php echo $qty; ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo number_format((float)$m['opening'], 2) . ' ' . htmlspecialchars($m['uom_code'] ?? 'kg'); ?></td>
                  <td style="text-align: right; color: var(--text2); font-weight: 500;"><?php echo number_format((float)$m['closing'], 2) . ' ' . htmlspecialchars($m['uom_code'] ?? 'kg'); ?></td>
                  <td style="text-align: right; color: var(--text2);"><?php echo $cost; ?></td>
                  <td style="text-align: right; font-weight: 600; color: var(--green);"><?php echo $total; ?></td>
                  <td style="font-weight: 500; font-size: 11px;"><?php echo $ref; ?></td>
                  <td style="color: var(--text2);"><?php echo $creator; ?></td>
                  <td style="text-align: center;" onclick="event.stopPropagation();">
                    <a href="movement_details.php?id=<?php echo $m['id']; ?>" class="btn-icon-link" title="View Details">
                      <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; stroke: currentColor; stroke-width: 2; fill: none;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
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
<script>
  function filterProduct(productId) {
    if (productId > 0) {
      window.location.href = 'movements.php?product_id=' + productId;
    } else {
      window.location.href = 'movements.php';
    }
  }
</script>
</body>
</html>
