<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_permissions')) {
    http_response_code(403);
    echo "<div style='font-family:\"Inter\", sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#E53E3E;'>Access Denied</h2>";
    echo "<p>You do not have permission to access this page.</p>";
    echo "<a href='../dashboard'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

if (empty($_SESSION['permissions_token'])) {
    $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_permission');
$canEdit = hasPermission($conn, $userId, 'edit_permission');
$canDelete = hasPermission($conn, $userId, 'delete_permission');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Permissions</title>
<meta name="description" content="Manage security permissions and configuration settings.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/permissions.css">
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

  <?php $page_title = "Permission Management"; include '../include/navbar.php'; ?>

  <div class="content" id="permissionsContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          System Permissions
        </h1>
        <div class="page-sub">Administer functional authorization privilege rules</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Defined Permissions</div>
      </div>

      <div class="stat-card" id="card-mapped-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="stat-trend trend-up">Mapped</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Mapped Permissions</div>
      </div>

      <div class="stat-card" id="card-unmapped-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Unused</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Unmapped Permissions</div>
      </div>

      <div class="stat-card" id="card-core-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <span class="stat-trend trend-warn">Core</span>
        </div>
        <div class="stat-val" id="stat-base">0</div>
        <div class="stat-label">Protected Core Settings</div>
      </div>
    </div>

    <div class="permissions-grid">

      <div class="card">
        <div class="card-header">
          <div class="card-title">Registered Security Privileges</div>
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="permissionsTable">
            <thead>
              <tr>
                <th>Permission Name</th>
                <th>System Code</th>
                <th>Roles Assigned</th>
                <th>Created Date</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="permissionsList">
              <tr>
                <td colspan="5" class="table-empty">Loading system permissions...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add Permission</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="permissionForm" novalidate>
          <input type="hidden" id="permissionIdInput" name="id" value="">
          <input type="hidden" id="permissionToken" name="token" value="<?php echo htmlspecialchars($_SESSION['permissions_token']); ?>">

          <div class="form-group">
            <label for="permissionName">Permission Name</label>
            <input type="text" id="permissionName" name="name" class="form-control" placeholder="e.g. view elements" required>
          </div>

          <div class="form-group">
            <label for="permissionCode">Permission Code</label>
            <input type="text" id="permissionCode" name="code" class="form-control" placeholder="e.g. view_elements" required>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Permission</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to configure system permissions.
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
      Delete System Permission
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this system permission? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/permissions.js"></script>
</body>
</html>
