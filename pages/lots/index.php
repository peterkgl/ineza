<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_lots')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['lots_token'])) {
    $_SESSION['lots_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_lot');
$canEdit = hasPermission($conn, $userId, 'edit_lot');
$canDelete = hasPermission($conn, $userId, 'delete_lot');

// Fetch lots
$query = "SELECT l.* FROM lots l ORDER BY l.opening_date DESC";
$result = mysqli_query($conn, $query);
$lotsData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $lotsData[] = [
            'id' => (int)$row['id'],
            'lots_code' => $row['lots_code'],
            'opening_date' => $row['opening_date'],
            'closing_date' => $row['closing_date'],
            'status' => $row['closing_date'] ? 'CLOSED' : 'OPEN'
        ];
    }
}

// Calculate stats
$totalLots = count($lotsData);
$openLots = 0;
$closedLots = 0;
foreach ($lotsData as $l) {
    if ($l['closing_date']) {
        $closedLots++;
    } else {
        $openLots++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Lots Management</title>
<meta name="description" content="Manage and track raw mineral lots, including opening balances, stock statuses, and transfers.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/lots.css">
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

  <?php $page_title = "Lots Management"; include '../include/navbar.php'; ?>

  <div class="content" id="lotsContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
          Lots Module
        </h1>
        <div class="page-sub">Track stock lots, carryover balances, and purchase/sale transaction bounds</div>
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
      <div class="stat-card" id="card-total-lots">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalLots; ?></div>
        <div class="stat-label">Registered Lots</div>
      </div>

      <div class="stat-card" id="card-open-lots">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="22 4 12 14.01 9 11.01"/><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/></svg>
          </div>
          <span class="stat-trend trend-up">Open</span>
        </div>
        <div class="stat-val" id="stat-open"><?php echo $openLots; ?></div>
        <div class="stat-label">Active / Open Lots</div>
      </div>

      <div class="stat-card" id="card-closed-lots">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <span class="stat-trend trend-down">Closed</span>
        </div>
        <div class="stat-val" id="stat-closed"><?php echo $closedLots; ?></div>
        <div class="stat-label">Archived / Closed Lots</div>
      </div>
    </div>

    <!-- Main Grid -->
    <div class="lots-grid">

      <!-- Left Column (List) -->
      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
          <div class="card-title">Lot Directory</div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search Lot No..." style="max-width: 150px; padding: 5px 8px; font-size: 12px; margin: 0;">
            <select id="statusFilter" class="form-control" style="max-width: 100px; padding: 5px 8px; font-size: 12px; margin: 0;">
              <option value="">All Statuses</option>
              <option value="OPEN">Open</option>
              <option value="CLOSED">Closed</option>
            </select>
          </div>
        </div>

        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="lotsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Lot Code</th>
                <th>Opening Date</th>
                <th>Closing Date</th>
                <th>Status</th>
                <th style="width: 120px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="lotsList">
              <?php if (empty($lotsData)): ?>
                <tr>
                  <td colspan="6" class="table-empty">No lots configured yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($lotsData as $index => $l): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $lotCodeVal = htmlspecialchars($l['lots_code']);
                    $openingDateVal = $l['opening_date'] ? htmlspecialchars($l['opening_date']) : '—';
                    $closingDateVal = $l['closing_date'] ? htmlspecialchars($l['closing_date']) : '—';
                    $isOpen = !$l['closing_date'];
                    $statusLabel = $isOpen ? '<span class="status-pill pill-green">Open</span>' : '<span class="status-pill pill-red">Closed</span>';
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $lotCodeVal; ?></strong></td>
                    <td><?php echo $openingDateVal; ?></td>
                    <td><?php echo $closingDateVal; ?></td>
                    <td><?php echo $statusLabel; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end;">
                        <button class="btn-icon-only view-details" title="View Details" data-id="<?php echo $l['id']; ?>">
                          <svg viewBox="0 0 24 24" style="stroke: var(--blue);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        </button>
                        <?php if ($canEdit && $isOpen): ?>
                          <button class="btn-icon-only edit" title="Edit Lot" data-id="<?php echo $l['id']; ?>">
                            <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                          </button>
                        <?php endif; ?>
                        <?php if ($isOpen): ?>
                          <?php if ($canEdit): ?>
                            <button class="btn-icon-only close-btn" title="Close Lot" data-id="<?php echo $l['id']; ?>">
                              <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            </button>
                          <?php endif; ?>
                        <?php endif; ?>
                        <?php if ($canDelete): ?>
                          <button class="btn-icon-only delete" title="Delete Lot" data-id="<?php echo $l['id']; ?>">
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
          <div class="card-title" id="formTitle">Add New Lot</div>
        </div>

        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="lotForm" novalidate>
          <input type="hidden" id="lotIdInput" name="id" value="">
          <input type="hidden" id="lotToken" name="token" value="<?php echo htmlspecialchars($_SESSION['lots_token']); ?>">

          <div class="form-group">
            <label for="lotCode">Lot Code *</label>
            <input type="text" id="lotCode" name="lots_code" class="form-control" placeholder="e.g. Lot 1-Ta" required>
          </div>

          <div class="form-group">
            <label for="lotOpeningDate">Opening Date *</label>
            <input type="date" id="lotOpeningDate" name="opening_date" class="form-control" required value="<?php echo date('Y-m-d'); ?>">
          </div>

          <div class="form-group">
            <label for="lotClosingDate">Closing Date</label>
            <input type="date" id="lotClosingDate" name="closing_date" class="form-control">
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Lot</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to configure lots.
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
      Delete Lot
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this lot configuration? This action is permanent.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<!-- Modal: Lot Details -->
<div class="confirm-modal-overlay" id="detailsModalOverlay" style="display: none;">
  <div class="confirm-modal" style="max-width: 600px; width: 90%;">
    <div class="confirm-title" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px; font-weight: 600;">
      <svg viewBox="0 0 24 24" style="stroke: var(--blue);"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      Lot Details: <span id="detailsLotCode"></span>
    </div>
    <div class="confirm-body" style="text-align: left;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
        <div>
          <div style="font-size: 10px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Opening Date</div>
          <div id="detailsOpeningDate" style="font-size: 14px; font-weight: 500; margin-top: 4px;"></div>
        </div>
        <div>
          <div style="font-size: 10px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Closing Date</div>
          <div id="detailsClosingDate" style="font-size: 14px; font-weight: 500; margin-top: 4px;"></div>
        </div>
        <div>
          <div style="font-size: 10px; text-transform: uppercase; color: var(--text3); font-weight: 600; letter-spacing: 0.5px;">Status</div>
          <div id="detailsStatus" style="font-size: 14px; font-weight: 500; margin-top: 4px;"></div>
        </div>
      </div>
      
      <div style="font-size: 11px; text-transform: uppercase; color: var(--orange); font-weight: 600; margin-bottom: 8px; border-left: 3px solid var(--orange); padding-left: 8px; letter-spacing: 0.5px;">Stock Balances</div>
      <div class="table-container" style="max-height: 250px; overflow-y: auto;">
        <table class="data-table" style="font-size: 12px; width: 100%;">
          <thead>
            <tr>
              <th>Warehouse</th>
              <th>Product</th>
              <th style="text-align: right;">Opening (kg)</th>
              <th style="text-align: right;">Closing (kg)</th>
            </tr>
          </thead>
          <tbody id="detailsStockList">
            <!-- Dynamic list -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="confirm-footer" style="border-top: 1px solid var(--border); padding-top: 12px; margin-top: 16px;">
      <button class="btn-sm" id="closeDetailsModalBtn">Close</button>
    </div>
  </div>
</div>

<script>
  window.initialLotsData = <?php echo json_encode($lotsData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/lots.js"></script>
</body>
</html>
