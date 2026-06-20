<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_audit_logs')) {
    header("Location: ../dashboard");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Audit Logs</title>
<meta name="description" content="View system transaction audit history logs.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/audit_logs.css">
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

  <?php $page_title = "System Audit Logs"; include '../include/navbar.php'; ?>

  <div class="content" id="auditContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Audit History Trail
        </h1>
        <div class="page-sub">Trace system-wide user actions, logs, changes, and queries</div>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-logs">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Total Logs Recorded</div>
      </div>

      <div class="stat-card" id="card-creates">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
          </div>
          <span class="stat-trend trend-up">Creates</span>
        </div>
        <div class="stat-val" id="stat-creates">0</div>
        <div class="stat-label">Create Mutations</div>
      </div>

      <div class="stat-card" id="card-updates">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Updates</span>
        </div>
        <div class="stat-val" id="stat-updates">0</div>
        <div class="stat-label">Update Mutations</div>
      </div>

      <div class="stat-card" id="card-deletes">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </div>
          <span class="stat-trend trend-down">Deletes</span>
        </div>
        <div class="stat-val" id="stat-deletes">0</div>
        <div class="stat-label">Delete Mutations</div>
      </div>
    </div>

    <div class="card">
      <div class="audit-controls">
        <div class="search-box">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="auditSearch" placeholder="Type to search all fields in real-time...">
        </div>
        <div class="limit-box">
          <label for="auditLimit">Entries per page:</label>
          <select id="auditLimit" class="form-control" style="width: auto; padding: 4px 8px;">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
        </div>
      </div>

      <div id="alertPlaceholder"></div>

      <div style="overflow-x: auto;">
        <table class="data-table" id="auditTable">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th style="width: 150px;">Timestamp</th>
              <th>Performed By</th>
              <th style="width: 100px;">Action</th>
              <th style="width: 120px;">Target Table</th>
              <th>Record Name</th>
              <th>Description</th>
              <th style="width: 80px; text-align: right;">Details</th>
            </tr>
          </thead>
          <tbody id="auditList">
            <tr>
              <td colspan="8" class="table-empty">Loading audit logs history trail...</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination-footer">
        <div class="pagination-info" id="paginationInfo">Showing 0 to 0 of 0 entries</div>
        <div class="pagination-nav" id="paginationNav"></div>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<div class="details-modal-overlay" id="detailsOverlay" style="display: none;">
  <div class="details-modal">
    <div class="details-header">
      <div class="details-title">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Audit Log Payload Details
      </div>
      <button class="details-close" id="detailsCloseBtn">&times;</button>
    </div>
    <div class="details-body" id="detailsBody">
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/audit_logs.js"></script>
</body>
</html>
