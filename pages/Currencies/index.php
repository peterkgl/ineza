<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_currencies')) {
    http_response_code(403);
    echo "<div style='font-family:\"Inter\", sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#E53E3E;'>Access Denied</h2>";
    echo "<p>You do not have permission to access this page.</p>";
    echo "<a href='../dashboard'>Back to Dashboard</a>";
    echo "</div>";
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
    var currentTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
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
        <div class="card-header">
          <div class="card-title">Registered Currencies</div>
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="currenciesTable">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Symbol</th>
                <th>Base Currency</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="currenciesList">
              <tr>
                <td colspan="6" class="table-empty">Loading currencies...</td>
              </tr>
            </tbody>
          </table>
        </div>
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
