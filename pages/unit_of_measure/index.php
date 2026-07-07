<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_unit_of_measure')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['unit_of_measure_token'])) {
    $_SESSION['unit_of_measure_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_unit_of_measure');
$canEdit = hasPermission($conn, $userId, 'edit_unit_of_measure');
$canDelete = hasPermission($conn, $userId, 'delete_unit_of_measure');

// Fetch units
$query = "SELECT u.* FROM unit_of_measure u ORDER BY u.code ASC";
$result = mysqli_query($conn, $query);
$unitsData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $unitsData[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'symbol' => $row['symbol'],
            'is_active' => (int)$row['is_active'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
}

// Calculate stats
$totalUnits = count($unitsData);
$activeUnits = 0;
foreach ($unitsData as $u) {
    if ($u['is_active']) {
        $activeUnits++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Units of Measure</title>
<meta name="description" content="Manage units of measure for products.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/lots.css">
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

  <?php $page_title = "Units of Measure"; include '../include/navbar.php'; ?>

  <div class="content" id="unitOfMeasureContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3V3zm2 2v14h14V5H5z"/><path d="M7 9h10v2H7zM7 13h10v2H7z"/></svg>
          Units of Measure
        </h1>
        <div class="page-sub">Manage measurement units for products</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
      <div class="stat-card" id="card-total-units">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3V3zm2 2v14h14V5H5z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalUnits; ?></div>
        <div class="stat-label">Registered Units</div>
      </div>

      <div class="stat-card" id="card-active-units">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24"><polyline points="22 4 12 14.01 9 11.01"/><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeUnits; ?></div>
        <div class="stat-label">Active Units</div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="lots-grid">

      <!-- Left Column (List) -->
      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
          <div class="card-title">Unit Directory</div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search units..." style="max-width: 150px; padding: 5px 8px; font-size: 12px; margin: 0;">
            <select id="statusFilter" class="form-control" style="max-width: 100px; padding: 5px 8px; font-size: 12px; margin: 0;">
              <option value="">All</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>

        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="unitsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Symbol</th>
                <th>Status</th>
                <th style="width: 120px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="unitsList">
              <?php if (empty($unitsData)): ?>
                <tr>
                  <td colspan="6" class="table-empty">No units of measure configured yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($unitsData as $index => $u): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $codeVal = htmlspecialchars($u['code']);
                    $nameVal = htmlspecialchars($u['name']);
                    $symbolVal = $u['symbol'] ? htmlspecialchars($u['symbol']) : '—';
                    $statusLabel = $u['is_active'] ? '<span class="status-pill pill-green">Active</span>' : '<span class="status-pill pill-red">Inactive</span>';
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $codeVal; ?></strong></td>
                    <td><?php echo $nameVal; ?></td>
                    <td><?php echo $symbolVal; ?></td>
                    <td><?php echo $statusLabel; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end;">
                        <?php if ($canEdit): ?>
                          <button class="btn-icon-only edit" title="Edit Unit" data-id="<?php echo $u['id']; ?>">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                          </button>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                          <button class="btn-icon-only delete" title="Delete Unit" data-id="<?php echo $u['id']; ?>">
                            <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
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
      </div>

      <!-- Right Column (Form Card) -->
      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Unit</div>
        </div>

        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="unitForm" novalidate>
          <input type="hidden" id="unitIdInput" name="id" value="">
          <input type="hidden" id="unitToken" name="token" value="<?php echo htmlspecialchars($_SESSION['unit_of_measure_token']); ?>">

          <div class="form-group">
            <label for="unitCode">Code *</label>
            <input type="text" id="unitCode" name="code" class="form-control" placeholder="e.g., KG, LB, MT" required>
          </div>

          <div class="form-group">
            <label for="unitName">Name *</label>
            <input type="text" id="unitName" name="name" class="form-control" placeholder="e.g., Kilogram" required>
          </div>

          <div class="form-group">
            <label for="unitSymbol">Symbol</label>
            <input type="text" id="unitSymbol" name="symbol" class="form-control" placeholder="e.g., kg">
          </div>

          <div class="form-group">
            <label class="checkbox-container">
              <input type="checkbox" id="unitIsActive" name="is_active" value="1" checked>
              <span class="checkbox-checkmark"></span>
              <span class="checkbox-label">Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Unit</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to configure units of measure.
          </div>
        <?php endif; ?>
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
      Delete Unit
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this unit of measure? This action is permanent.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script>
  window.initialUnitsData = <?php echo json_encode($unitsData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/unit_of_measure.js"></script>
</body>
</html>
