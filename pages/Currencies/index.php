<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_currencies')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['currency_token'])) {
    $_SESSION['currency_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_currency');
$canEdit = hasPermission($conn, $userId, 'edit_currency');
$canDelete = hasPermission($conn, $userId, 'delete_currency');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Currencies</title>
<meta name="description" content="Manage transaction currencies, exchange rates, and base system currency configuration.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/currencies.css">
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

  <?php $page_title = "Currencies Management"; include '../include/navbar.php'; ?>

  <div class="content" id="currenciesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Currencies Configuration
        </h1>
        <div class="page-sub">Manage system transaction currencies, active statuses, and reporting settings</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-currencies">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Total Currencies</div>
      </div>

      <div class="stat-card" id="card-active-currencies">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Active Currencies</div>
      </div>

      <div class="stat-card" id="card-base-currency">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          </div>
          <span class="stat-trend trend-warn">Base</span>
        </div>
        <div class="stat-val" id="stat-base">-</div>
        <div class="stat-label">System Reporting Base</div>
      </div>

      <div class="stat-card" id="card-inactive-currencies">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          </div>
          <span class="stat-trend trend-down">Inactive</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Inactive / Disabled</div>
      </div>
    </div>

    <div class="currencies-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Currencies</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="currenciesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Symbol</th>
                <th>Base Currency</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="currenciesList">
              <?php
              $query = "SELECT * FROM currencies ORDER BY code ASC";
              $result = mysqli_query($conn, $query);
              $currenciesData = [];
              $rowNum = 1;
              if ($result && mysqli_num_rows($result) > 0) {
                  while ($row = mysqli_fetch_assoc($result)) {
                      $currenciesData[] = [
                          'id' => (int)$row['id'],
                          'code' => $row['code'],
                          'name' => $row['name'],
                          'symbol' => $row['symbol'],
                          'is_base_currency' => (int)$row['is_base_currency'],
                          'is_active' => (int)$row['is_active']
                      ];

                      $baseBadge = $row['is_base_currency'] == 1 
                          ? '<span class="status-pill pill-blue">Yes</span>' 
                          : '<span class="status-pill pill-red">No</span>';

                      $statusBadge = $row['is_active'] == 1 
                          ? '<span class="status-pill pill-green">Active</span>' 
                          : '<span class="status-pill pill-red">Inactive</span>';

                      $actions = '<div class="action-buttons" style="justify-content: flex-end;">';
                      if ($canEdit) {
                          $actions .= '<button class="btn-icon-only edit" data-id="' . $row['id'] . '" title="Edit">';
                          $actions .= '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                          $actions .= '</button>';
                      }
                      if ($canDelete) {
                          $actions .= '<button class="btn-icon-only delete" data-id="' . $row['id'] . '" title="Delete">';
                          $actions .= '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
                          $actions .= '</button>';
                      }
                      $actions .= '</div>';

                      echo '<tr>';
                      echo '<td>' . $rowNum++ . '</td>';
                      echo '<td class="td-bold">' . htmlspecialchars($row['code']) . '</td>';
                      echo '<td class="td-name">' . htmlspecialchars($row['name']) . '</td>';
                      echo '<td style="font-family:monospace; font-weight:600;">' . htmlspecialchars($row['symbol']) . '</td>';
                      echo '<td>' . $baseBadge . '</td>';
                      echo '<td>' . $statusBadge . '</td>';
                      echo '<td style="text-align: right;">' . $actions . '</td>';
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="7" class="table-empty">No currencies registered yet.</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
        <script>
          window.initialCurrenciesData = <?php echo json_encode($currenciesData); ?>;
        </script>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Currency</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="currencyForm" novalidate>
          <input type="hidden" id="currencyId" name="id" value="">
          <input type="hidden" id="currencyToken" name="token" value="<?php echo htmlspecialchars($_SESSION['currency_token']); ?>">

          <div class="form-group">
            <label for="currencyCode">Currency Code</label>
            <input type="text" id="currencyCode" name="code" class="form-control" placeholder="e.g. USD, RWF" maxlength="5" required>
          </div>

          <div class="form-group">
            <label for="currencyName">Currency Name</label>
            <input type="text" id="currencyName" name="name" class="form-control" placeholder="e.g. US Dollar, Rwandan Franc" required>
          </div>

          <div class="form-group">
            <label for="currencySymbol">Symbol</label>
            <input type="text" id="currencySymbol" name="symbol" class="form-control" placeholder="e.g. $, RF, €" maxlength="5">
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="currencyIsBase" name="is_base_currency" value="1">
            <span>Set as base reporting currency</span>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="currencyIsActive" name="is_active" value="1" checked>
            <span>Activate currency</span>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Currency</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit currencies.
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
      Delete Currency
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this currency? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/currencies.js"></script>
</body>
</html>
