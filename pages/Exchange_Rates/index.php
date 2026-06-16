<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_exchange_rates')) {
    http_response_code(403);
    echo "<div style='font-family:\"Inter\", sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#E53E3E;'>Access Denied</h2>";
    echo "<p>You do not have permission to access this page.</p>";
    echo "<a href='../dashboard'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

if (empty($_SESSION['rates_token'])) {
    $_SESSION['rates_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_exchange_rate');
$canEdit = hasPermission($conn, $userId, 'edit_exchange_rate');
$canDelete = hasPermission($conn, $userId, 'delete_exchange_rate');

$currQuery = "SELECT id, code, name FROM currencies WHERE is_active = 1 ORDER BY code ASC";
$currResult = mysqli_query($conn, $currQuery);
$currencies = [];
if ($currResult) {
    while ($row = mysqli_fetch_assoc($currResult)) {
        $currencies[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Exchange Rates</title>
<meta name="description" content="Manage transaction currency conversion exchange rates.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/exchange_rates.css">
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

  <?php $page_title = "Exchange Rates Management"; include '../include/navbar.php'; ?>

  <div class="content" id="ratesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Exchange Rates Configuration
        </h1>
        <div class="page-sub">Manage currency conversions and rate tracking values</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-rates">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Total Rate Logs</div>
      </div>

      <div class="stat-card" id="card-active-pairs">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="stat-trend trend-up">Pairs</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Unique Currency Pairs</div>
      </div>

      <div class="stat-card" id="card-last-date">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <span class="stat-trend trend-warn">Latest</span>
        </div>
        <div class="stat-val" id="stat-base">-</div>
        <div class="stat-label">Last Updated Date</div>
      </div>

      <div class="stat-card" id="card-sources">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          </div>
          <span class="stat-trend trend-down">Sources</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Unique Sources Used</div>
      </div>
    </div>

    <div class="rates-grid">

      <div class="card">
        <div class="card-header">
          <div class="card-title">Registered Rates Log</div>
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="ratesTable">
            <thead>
              <tr>
                <th>Date</th>
                <th>From</th>
                <th>To</th>
                <th>Exchange Rate</th>
                <th>Source</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="ratesList">
              <tr>
                <td colspan="6" class="table-empty">Loading exchange rates...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add Exchange Rate</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="rateForm" novalidate>
          <input type="hidden" id="rateId" name="id" value="">
          <input type="hidden" id="rateToken" name="token" value="<?php echo htmlspecialchars($_SESSION['rates_token']); ?>">

          <div class="form-group">
            <label for="rateFrom">From Currency</label>
            <select id="rateFrom" name="from_currency_id" class="form-control" required>
              <option value="">Select Currency</option>
              <?php foreach ($currencies as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="rateTo">To Currency</label>
            <select id="rateTo" name="to_currency_id" class="form-control" required>
              <option value="">Select Currency</option>
              <?php foreach ($currencies as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['code'] . ' - ' . $c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="rateValue">Rate (From per 1 Unit of To)</label>
            <input type="number" id="rateValue" name="rate" class="form-control" placeholder="e.g. 1250.00" step="any" required>
          </div>

          <div class="form-group">
            <label for="rateDate">Rate Date</label>
            <input type="date" id="rateDate" name="rate_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label for="rateSource">Source / Provider</label>
            <input type="text" id="rateSource" name="source" class="form-control" placeholder="e.g. BNR, Bloomberg">
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Rate</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit exchange rates.
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
      Delete Exchange Rate
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this exchange rate record? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/exchange_rates.js"></script>
</body>
</html>
