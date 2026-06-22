<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_warehouses')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['warehouses_token'])) {
    $_SESSION['warehouses_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_warehouse');
$canEdit = hasPermission($conn, $userId, 'edit_warehouse');
$canDelete = hasPermission($conn, $userId, 'delete_warehouse');

$query = "SELECT * FROM warehouses ORDER BY warehouse_code ASC";
$result = mysqli_query($conn, $query);
$warehousesData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $warehousesData[] = [
            'id' => (int)$row['id'],
            'warehouse_code' => $row['warehouse_code'],
            'warehouse_name' => $row['warehouse_name'],
            'address' => $row['address'],
            'is_active' => (int)$row['is_active'],
            'created_at' => $row['created_at']
        ];
    }
}

// Calculate stats
$totalWarehouses = count($warehousesData);
$activeWarehouses = 0;
$inactiveWarehouses = 0;
foreach ($warehousesData as $w) {
    if ($w['is_active'] === 1) {
        $activeWarehouses++;
    } else {
        $inactiveWarehouses++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Warehouses</title>
<meta name="description" content="Manage inventory storage warehouses.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/warehouses.css">
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

  <?php $page_title = "Warehouse Configuration"; include '../include/navbar.php'; ?>

  <div class="content" id="warehousesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8M2 7l10-5 10 5-10 5z"/></svg>
          Warehouses
        </h1>
        <div class="page-sub">Manage inventory storage locations, codes, and physical addresses</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-warehouses">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8M2 7l10-5 10 5-10 5z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalWarehouses; ?></div>
        <div class="stat-label">Total Warehouses</div>
      </div>

      <div class="stat-card" id="card-active-warehouses">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeWarehouses; ?></div>
        <div class="stat-label">Active Warehouses</div>
      </div>

      <div class="stat-card" id="card-inactive-warehouses">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $inactiveWarehouses; ?></div>
        <div class="stat-label">Inactive Warehouses</div>
      </div>
    </div>

    <div class="warehouses-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Storage Locations</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="warehousesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Warehouse Code</th>
                <th>Warehouse Name</th>
                <th>Address</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="warehousesList">
              <?php if (empty($warehousesData)): ?>
                <tr>
                  <td colspan="6" class="table-empty">No warehouses found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($warehousesData as $index => $w): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $codeVal = htmlspecialchars($w['warehouse_code']);
                    $nameVal = htmlspecialchars($w['warehouse_name']);
                    $addressVal = $w['address'] ? htmlspecialchars($w['address']) : '—';
                    $statusBadge = $w['is_active'] === 1 
                      ? '<span class="status-pill pill-green">Active</span>' 
                      : '<span class="status-pill pill-red">Inactive</span>';

                    $actions = '';
                    $actions .= '<div class="action-buttons" style="justify-content: flex-end;">';
                    if ($canCreate || $canEdit) {
                        $actions .= '<button class="btn-icon-only edit" data-id="' . $w['id'] . '" title="Edit">';
                        $actions .= '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                        $actions .= '</button>';
                    }
                    if ($canDelete) {
                        $actions .= '<button class="btn-icon-only delete" data-id="' . $w['id'] . '" title="Delete">';
                        $actions .= '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
                        $actions .= '</button>';
                    }
                    $actions .= '</div>';
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td class="td-bold"><?php echo $codeVal; ?></td>
                    <td class="td-name"><?php echo $nameVal; ?></td>
                    <td><?php echo $addressVal; ?></td>
                    <td><?php echo $statusBadge; ?></td>
                    <td style="text-align: right;"><?php echo $actions; ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Warehouse</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="warehouseForm" novalidate>
          <input type="hidden" id="warehouseIdInput" name="id" value="">
          <input type="hidden" id="warehouseToken" name="token" value="<?php echo htmlspecialchars($_SESSION['warehouses_token']); ?>">

          <div class="form-group">
            <label for="warehouseCode">Warehouse Code</label>
            <input type="text" id="warehouseCode" name="warehouse_code" class="form-control" placeholder="e.g. WH-A, WH-KIGALI" required>
          </div>

          <div class="form-group">
            <label for="warehouseName">Warehouse Name</label>
            <input type="text" id="warehouseName" name="warehouse_name" class="form-control" placeholder="e.g. Kigali Main Terminal" required>
          </div>

          <div class="form-group">
            <label for="warehouseAddress">Address</label>
            <input type="text" id="warehouseAddress" name="address" class="form-control" placeholder="e.g. KK 15 Rd, Kigali, Rwanda">
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="warehouseActive" name="is_active" value="1" checked>
            <label for="warehouseActive" style="margin: 0; cursor: pointer;">
              <span>Mark warehouse as Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Warehouse</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit warehouses.
          </div>
        <?php endif; ?>
      </div>

    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<div class="confirm-modal-overlay" id="confirmOverlay" style="display: none;">
  <div class="confirm-modal">
    <div class="confirm-title">
      <svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
      Delete Warehouse
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this warehouse configuration? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script>
  window.initialWarehousesData = <?php echo json_encode($warehousesData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/warehouses.js"></script>
</body>
</html>
