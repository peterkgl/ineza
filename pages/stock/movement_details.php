<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_stock_movement')) {
    header("Location: ../dashboard");
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selectedProductId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$selectedLotId = isset($_GET['lot_id']) ? (int)$_GET['lot_id'] : 0;

// Construct redirect URL dynamically to preserve parameters
$redirectParams = [];
if ($selectedProductId > 0) $redirectParams[] = "product_id=" . $selectedProductId;
if ($selectedLotId > 0) $redirectParams[] = "lot_id=" . $selectedLotId;
$redirectUrl = "movements.php" . (!empty($redirectParams) ? "?" . implode("&", $redirectParams) : "");

if ($id <= 0) {
    header("Location: " . $redirectUrl);
    exit();
}

// Fetch the stock movement record with basic details
$query = "SELECT sm.*, 
                 CONCAT(u.first_name, ' ', u.last_name) AS creator_name, 
                 uom.code AS uom_code, 
                 w.warehouse_name, 
                 w.warehouse_code, 
                 l.lots_code,
                 l.closing_date AS lot_closing_date,
                 pr.product_name,
                 pr.product_code
          FROM stock_movement sm
          LEFT JOIN users u ON sm.created_by = u.id
          LEFT JOIN unit_of_measure uom ON sm.uom_id = uom.id
          LEFT JOIN warehouses w ON sm.warehouse_id = w.id
          LEFT JOIN lots l ON sm.lot_id = l.id
          LEFT JOIN product pr ON sm.product_id = pr.id
          WHERE sm.id = $id
          LIMIT 1";

$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: " . $redirectUrl);
    exit();
}

$movement = mysqli_fetch_assoc($result);

// Fetch related purchase & supplier info if it's a purchase movement
$purchase = null;
if ($movement['reference_type'] === 'purchasing' && $movement['reference_id'] > 0) {
    $purchId = (int)$movement['reference_id'];
    $purchQuery = "SELECT p.*, 
                          s.name AS supplier_name, 
                          s.email AS supplier_email, 
                          s.phone AS supplier_phone, 
                          s.address AS supplier_address,
                          s.nif AS supplier_nif,
                          s.supplier_type
                   FROM purchasing p
                   LEFT JOIN suppliers s ON p.supplier_id = s.id
                   WHERE p.id = $purchId
                   LIMIT 1";
    $purchRes = mysqli_query($conn, $purchQuery);
    if ($purchRes && mysqli_num_rows($purchRes) > 0) {
        $purchase = mysqli_fetch_assoc($purchRes);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Stock Movement Details</title>
<meta name="description" content="View comprehensive details of a specific inventory ledger record.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/stock.css">
<style>
  .details-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
  }
  @media (max-width: 900px) {
    .details-container {
      grid-template-columns: 1fr;
    }
  }
  .info-group {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border);
  }
  .info-group:last-child {
    border-bottom: none;
  }
  .info-label {
    font-size: 10.5px;
    text-transform: uppercase;
    color: var(--text3);
    font-weight: 600;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
  }
  .info-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--text);
  }
  .info-value.bold {
    font-weight: 600;
  }
  .info-value.amount {
    color: var(--green);
    font-weight: 600;
  }
  .info-value-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }
  .card-section-title {
    font-size: 12px;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--orange);
    letter-spacing: 0.5px;
    margin-bottom: 16px;
    border-left: 3px solid var(--orange);
    padding-left: 8px;
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

  <?php $page_title = "Movement Ledger Record"; include '../include/navbar.php'; ?>

  <div class="content" id="stockContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="stroke: var(--orange); stroke-width: 2; fill: none; width: 22px; height: 22px; margin-right: 8px;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
          Movement Ledger Details
        </h1>
        <div class="page-sub">Comprehensive ledger and source document audit trail.</div>
      </div>
      <div class="page-actions">
        <a href="<?php echo htmlspecialchars($redirectUrl); ?>" class="btn-sm" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
          <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; stroke: currentColor; stroke-width: 2; fill: none;"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
          Back to Movements
        </a>
      </div>
    </div>

    <div class="details-container">
      
      <!-- Left Column: Movement Details -->
      <div class="card" style="padding: 24px;">
        <div class="card-section-title">Ledger Entry Details</div>
        
        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Movement Date</div>
            <div class="info-value"><?php echo htmlspecialchars($movement['movement_date']); ?></div>
          </div>
          <div class="info-group">
            <div class="info-label">Movement Type</div>
            <div class="info-value">
              <?php
                switch ($movement['movement_type']) {
                  case 'PURCHASE_IN':
                    echo '<span class="status-pill pill-green">PURCHASE IN</span>';
                    break;
                  case 'SALE_OUT':
                    echo '<span class="status-pill pill-red">SALE OUT</span>';
                    break;
                  case 'TRANSFER_IN':
                    echo '<span class="status-pill pill-blue">TRANSFER IN</span>';
                    break;
                  case 'TRANSFER_OUT':
                    echo '<span class="status-pill pill-orange">TRANSFER OUT</span>';
                    break;
                  case 'ADJUSTMENT_IN':
                    echo '<span class="status-pill pill-green">ADJ IN</span>';
                    break;
                  case 'ADJUSTMENT_OUT':
                    echo '<span class="status-pill pill-red">ADJ OUT</span>';
                    break;
                  default:
                    echo '<span class="status-pill">' . htmlspecialchars($movement['movement_type']) . '</span>';
                }
              ?>
            </div>
          </div>
        </div>

        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Product</div>
            <div class="info-value bold"><?php echo htmlspecialchars($movement['product_name'] . ' (' . $movement['product_code'] . ')'); ?></div>
          </div>
          <div class="info-group">
            <div class="info-label">Warehouse</div>
            <div class="info-value"><?php echo htmlspecialchars($movement['warehouse_name'] . ' (' . $movement['warehouse_code'] . ')'); ?></div>
          </div>
        </div>

        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Lot Code / Status</div>
            <div class="info-value" style="display: inline-flex; align-items: center; gap: 8px; flex-wrap: wrap;">
              <span class="status-pill pill-purple" style="font-weight: 600;"><?php echo htmlspecialchars($movement['lots_code'] ?? '—'); ?></span>
              <?php if ($movement['lot_id']): ?>
                <?php if ($movement['lot_closing_date'] !== null): ?>
                  <span class="status-pill pill-red" style="font-size: 10.5px; font-weight: 600; padding: 2px 8px;">CLOSED</span>
                <?php else: ?>
                  <span class="status-pill pill-green" style="font-size: 10.5px; font-weight: 600; padding: 2px 8px;">OPEN</span>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
          <div class="info-group">
            <div class="info-label">Quantity</div>
            <div class="info-value bold"><?php echo number_format((float)$movement['qty_kg'], 2) . ' ' . htmlspecialchars($movement['uom_code'] ?? 'kg'); ?></div>
          </div>
        </div>

        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Opening Balance</div>
            <div class="info-value bold"><?php echo number_format((float)$movement['opening'], 2) . ' ' . htmlspecialchars($movement['uom_code'] ?? 'kg'); ?></div>
          </div>
          <div class="info-group">
            <div class="info-label">Closing Balance</div>
            <div class="info-value bold"><?php echo number_format((float)$movement['closing'], 2) . ' ' . htmlspecialchars($movement['uom_code'] ?? 'kg'); ?></div>
          </div>
        </div>

        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Unit Cost (USD)</div>
            <div class="info-value"><?php echo $movement['unit_cost_usd'] !== null ? number_format((float)$movement['unit_cost_usd'], 2) . ' USD' : '—'; ?></div>
          </div>
          <div class="info-group">
            <div class="info-label">Total Value (USD)</div>
            <div class="info-value amount"><?php echo $movement['total_value_usd'] !== null ? number_format((float)$movement['total_value_usd'], 2) . ' USD' : '—'; ?></div>
          </div>
        </div>

        <div class="info-value-grid">
          <div class="info-group">
            <div class="info-label">Recorded By</div>
            <div class="info-value"><?php echo htmlspecialchars($movement['creator_name'] ?? 'System'); ?></div>
          </div>
          <div class="info-group">
            <div class="info-label">Created At</div>
            <div class="info-value"><?php echo htmlspecialchars($movement['created_at']); ?></div>
          </div>
        </div>

        <div class="info-group" style="border-bottom: none;">
          <div class="info-label">Movement Notes / Remarks</div>
          <div class="info-value" style="font-style: italic; color: var(--text2);"><?php echo $movement['notes'] ? htmlspecialchars($movement['notes']) : 'No remarks recorded.'; ?></div>
        </div>
      </div>

      <!-- Right Column: Related Transaction & Supplier Info -->
      <div class="card" style="padding: 24px;">
        <?php if ($purchase): ?>
          <div class="card-section-title">Supplier Information</div>
          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">Supplier Name</div>
              <div class="info-value bold"><?php echo htmlspecialchars($purchase['supplier_name']); ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Supplier Code / Type</div>
              <div class="info-value" style="text-transform: capitalize;"><?php echo htmlspecialchars($purchase['supplier_type'] ?? '—'); ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">Phone Number</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['supplier_phone'] ?? '—'); ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Email Address</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['supplier_email'] ?? '—'); ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">NIF Number</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['supplier_nif'] ?? '—'); ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Address</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['supplier_address'] ?? '—'); ?></div>
            </div>
          </div>

          <div class="card-section-title" style="margin-top: 10px;">Source Purchase Order Details</div>
          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">Purchase Reference #</div>
              <div class="info-value bold" style="color: var(--blue);"><?php echo htmlspecialchars($purchase['purchase_no']); ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Purchase Date</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['purchase_date']); ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">Delivery Date</div>
              <div class="info-value"><?php echo htmlspecialchars($purchase['delivery_date']); ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Exchange Rate</div>
              <div class="info-value"><?php echo number_format((float)$purchase['exchange_rate'], 2) . ' RWF / USD'; ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">Net Paid (USD)</div>
              <div class="info-value amount" style="color: var(--orange);"><?php echo number_format((float)$purchase['net_paid_supplier_usd'], 2) . ' USD'; ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">Purchase Value (RWF)</div>
              <div class="info-value" style="font-weight: 600;"><?php echo number_format((float)$purchase['purchase_value_rwf'], 0) . ' RWF'; ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group">
              <div class="info-label">RRA Tax (USD)</div>
              <div class="info-value" style="color: var(--red);"><?php echo number_format((float)$purchase['tax_rra'], 2) . ' USD'; ?></div>
            </div>
            <div class="info-group">
              <div class="info-label">RMA Tax (USD)</div>
              <div class="info-value" style="color: var(--red);"><?php echo number_format((float)$purchase['tax_rma'], 2) . ' USD'; ?></div>
            </div>
          </div>

          <div class="info-value-grid">
            <div class="info-group" style="border-bottom: none;">
              <div class="info-label">Inkomane Tax (USD)</div>
              <div class="info-value" style="color: var(--red);"><?php echo number_format((float)$purchase['tax_inkomane'], 2) . ' USD'; ?></div>
            </div>
            <div class="info-group" style="border-bottom: none;">
              <div class="info-label">Production Charges</div>
              <div class="info-value" style="color: var(--text3);"><?php echo number_format((float)$purchase['production_charges'], 2) . ' USD'; ?></div>
            </div>
          </div>
        <?php else: ?>
          <div class="card-section-title">Reference Document Details</div>
          <div class="info-group">
            <div class="info-label">Reference Type</div>
            <div class="info-value" style="text-transform: uppercase;"><?php echo htmlspecialchars($movement['reference_type'] ? $movement['reference_type'] : 'Manual Ledger entry'); ?></div>
          </div>
          <div class="info-group" style="border-bottom: none;">
            <div class="info-label">Reference ID</div>
            <div class="info-value"><?php echo htmlspecialchars($movement['reference_id'] ? $movement['reference_id'] : '—'); ?></div>
          </div>
          <div style="margin-top: 40px; text-align: center; color: var(--text3);">
            <svg viewBox="0 0 24 24" style="width: 48px; height: 48px; stroke: currentColor; stroke-width: 1.5; fill: none; margin-bottom: 12px; opacity: 0.5;">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
            </svg>
            <div style="font-size: 13px;">No supplier or purchase invoice linked to this movement.</div>
          </div>
        <?php endif; ?>
      </div>

    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
