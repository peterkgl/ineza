<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_purchas')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['purchas_token'])) {
    $_SESSION['purchas_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_purchas');
$canEdit = hasPermission($conn, $userId, 'edit_purchas');
$canDelete = hasPermission($conn, $userId, 'delete_purchas');

// Load dropdown options
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

$uoms = [];
$uomQuery = mysqli_query($conn, "SELECT id, code, name FROM unit_of_measure WHERE is_active = 1 ORDER BY code ASC");
if ($uomQuery) {
    while ($row = mysqli_fetch_assoc($uomQuery)) {
        $uoms[] = $row;
    }
}

// Fetch purchases data for first render
$purchasesData = [];
$query = "SELECT p.*, pr.product_name, pr.product_code, l.lots_code, s.name as supplier_name, w.warehouse_name, uom.code as uom_code
          FROM purchasing p
          JOIN product pr ON p.product_id = pr.id
          JOIN lots l ON p.lot_id = l.id
          JOIN suppliers s ON p.supplier_id = s.id
          JOIN warehouses w ON p.warehouse_id = w.id
          LEFT JOIN unit_of_measure uom ON p.uom_id = uom.id
          ORDER BY p.purchase_date DESC, p.id DESC";

$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $pId = (int)$row['id'];
        
        // Fetch grades
        $gradesQuery = "SELECT peg.*, pe.element_code, pe.element_name, pe.symbol 
                        FROM purchasing_element_grade peg
                        JOIN product_element pe ON peg.product_element_id = pe.id
                        WHERE peg.purchasing_id = $pId";
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
        
        $purchasesData[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Purchases & Logistics</title>
<meta name="description" content="Manage and record mining product purchases, element grading, and supplier pricing metrics.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/lots.css">
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

  <?php $page_title = "Mining Purchases"; include '../include/navbar.php'; ?>

  <div class="content" id="purchasContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          Purchases Registry
        </h1>
        <div class="page-sub">Record mineral purchase receipts, element compositions, and price metrics</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
        <?php if ($canCreate): ?>
          <a href="record.php" class="btn-sm btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
            <svg class="btn-icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Record Purchase
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card" id="card-total-purchases">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Transactions</div>
      </div>

      <div class="stat-card" id="card-total-qty">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-up">Quantity</span>
        </div>
        <div class="stat-val" id="stat-qty">0 kg</div>
        <div class="stat-label">Accumulated Weight</div>
      </div>

      <div class="stat-card" id="card-total-value">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Value</span>
        </div>
        <div class="stat-val" id="stat-value">$0.00</div>
        <div class="stat-label">Total Outlay (USD)</div>
      </div>
    </div>

    <!-- Purchases Table Card -->
    <div class="purchas-grid">
      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
          <div class="card-title">Recorded Receipts</div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search purchases..." style="max-width: 200px; padding: 5px 8px; font-size: 12px; margin: 0;">
            <select id="statusFilter" class="form-control" style="max-width: 130px; padding: 5px 8px; font-size: 12px; margin: 0;">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="confirmed">Confirmed</option>
              <option value="received">Received</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="purchasesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Purchase No</th>
                <th>Lot</th>
                <th>Product</th>
                <th>Supplier</th>
                <th>Negociant</th>
                <th>Warehouse</th>
                <th>Quantity</th>
                <th>Total Value</th>
                <th>Status</th>
                <th>Date</th>
                <th style="width: 120px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="purchasesList">
              <tr>
                <td colspan="12" class="table-empty">Loading purchases data...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<!-- Modal: Confirm Delete -->
<div class="confirm-modal-overlay" id="confirmOverlay" style="display: none;">
  <div class="confirm-modal">
    <div class="confirm-title">
      <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
      Delete Purchase Record
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this purchase receipt? This action is permanent and will revert any inventory changes.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<!-- Modal: Purchase Details View -->
<div class="confirm-modal-overlay" id="detailModalOverlay" style="display: none;">
  <div class="confirm-modal" style="max-width: 650px; width: 90%;">
    <div class="confirm-title" style="display: flex; justify-content: space-between; align-items: center;">
      <span style="display: flex; align-items: center; gap: 8px;">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Purchase Transaction Details
      </span>
      <button class="purchas-modal-close" id="detailCloseBtn" title="Close" style="padding: 2px;">
        <svg viewBox="0 0 24 24" style="width:16px; height:16px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="confirm-body" id="detailContent" style="max-height: 70vh; overflow-y: auto; text-align: left; padding: 20px 0;">
      <!-- Details populated via JS -->
    </div>
  </div>
</div>

<!-- Modal: Change Purchase Status -->
<div class="status-modal-overlay" id="statusModalOverlay">
  <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['purchas_token']); ?>">
  <div class="status-modal">
    <div class="status-modal-header">
      <span class="modal-title-text">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Change Purchase Status
      </span>
      <button class="purchas-modal-close" id="statusCloseBtn" title="Close">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <div class="status-modal-body">
      <div class="status-current">
        <span class="status-current-label">Current Status:</span>
        <span id="statusCurrentPill"></span>
      </div>
      <div class="status-options-grid">
        <label class="status-option" data-status="pending">
          <input type="radio" name="new_status" value="pending">
          <div class="status-option-icon icon-pending">
            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          </div>
          <span class="status-option-label">Pending</span>
          <span class="status-option-desc">Initial entry, not yet confirmed</span>
        </label>
        <label class="status-option" data-status="confirmed">
          <input type="radio" name="new_status" value="confirmed">
          <div class="status-option-icon icon-confirmed">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="status-option-label">Confirmed</span>
          <span class="status-option-desc">Verified and approved</span>
        </label>
        <label class="status-option" data-status="received">
          <input type="radio" name="new_status" value="received">
          <div class="status-option-icon icon-received">
            <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          </div>
          <span class="status-option-label">Received</span>
          <span class="status-option-desc">Goods delivered to warehouse</span>
        </label>
        <label class="status-option" data-status="cancelled">
          <input type="radio" name="new_status" value="cancelled">
          <div class="status-option-icon icon-cancelled">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="status-option-label">Cancelled</span>
          <span class="status-option-desc">Voided or reversed</span>
        </label>
      </div>
    </div>
    <div class="status-modal-footer">
      <button class="btn-sm" id="statusCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="statusSaveBtn">Update Status</button>
    </div>
  </div>
</div>

<script>
  window.initialPurchasesData = <?php echo json_encode($purchasesData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/purchas.js"></script>
</body>
</html>
