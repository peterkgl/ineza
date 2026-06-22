<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_permissions')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['permissions_token'])) {
    $_SESSION['permissions_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_permission');
$canEdit = hasPermission($conn, $userId, 'edit_permission');
$canDelete = hasPermission($conn, $userId, 'delete_permission');

$coreCodes = [
    'view_permissions', 'create_permission', 'edit_permission', 'delete_permission',
    'view_roles', 'create_role', 'edit_role', 'delete_role',
    'view_users', 'create_user', 'edit_user', 'delete_user',
    'view_currencies', 'create_currency', 'edit_currency', 'delete_currency',
    'view_exchange_rates', 'create_exchange_rate', 'edit_exchange_rate', 'delete_exchange_rate'
];

$query = "SELECT p.id, p.permition_name, p.permition_code, p.created_at, COUNT(DISTINCT rp.role_id) as role_count
          FROM permissions p
          LEFT JOIN role_permissions rp ON p.id = rp.permission_id
          GROUP BY p.id
          ORDER BY p.permition_name ASC";
$result = mysqli_query($conn, $query);
$permissionsData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $permissionsData[] = [
            'id' => (int)$row['id'],
            'name' => $row['permition_name'],
            'code' => $row['permition_code'],
            'created_at' => $row['created_at'],
            'role_count' => (int)$row['role_count'],
            'is_core' => in_array($row['permition_code'], $coreCodes)
        ];
    }
}
$totalPermissions = count($permissionsData);
$mappedPermissions = 0;
$unmappedPermissions = 0;
$corePermissions = 0;
foreach ($permissionsData as $p) {
    if ($p['role_count'] > 0) {
        $mappedPermissions++;
    } else {
        $unmappedPermissions++;
    }
    if ($p['is_core']) {
        $corePermissions++;
    }
}
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
        <div class="stat-val" id="stat-total"><?php echo $totalPermissions; ?></div>
        <div class="stat-label">Defined Permissions</div>
      </div>

      <div class="stat-card" id="card-mapped-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="stat-trend trend-up">Mapped</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $mappedPermissions; ?></div>
        <div class="stat-label">Mapped Permissions</div>
      </div>

      <div class="stat-card" id="card-unmapped-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Unused</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $unmappedPermissions; ?></div>
        <div class="stat-label">Unmapped Permissions</div>
      </div>

      <div class="stat-card" id="card-core-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <span class="stat-trend trend-warn">Core</span>
        </div>
        <div class="stat-val" id="stat-base"><?php echo $corePermissions; ?></div>
        <div class="stat-label">Protected Core Settings</div>
      </div>
    </div>

    <div class="permissions-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Security Privileges</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="permissionsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Permission Name</th>
                <th>System Code</th>
                <th>Roles Assigned</th>
                <th>Created Date</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="permissionsList">
              <?php if (empty($permissionsData)): ?>
                <tr>
                  <td colspan="6" class="table-empty">No permissions defined yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($permissionsData as $index => $p): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $nameVal = htmlspecialchars($p['name']);
                    $codeVal = htmlspecialchars($p['code']);
                    $createdVal = htmlspecialchars(explode(' ', $p['created_at'])[0]);
                    $roleBadgeClass = $p['role_count'] > 0 ? 'pill-green' : '';
                    $roleBadge = '<span class="status-pill ' . $roleBadgeClass . '">' . $p['role_count'] . ' Roles</span>';

                    $actionsHtml = '';
                    if (!$p['is_core']) {
                        $actionsHtml = '<button class="btn-icon-only edit" title="Edit Permission" data-id="' . $p['id'] . '">' .
                            '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>' .
                          '</button>' .
                          '<button class="btn-icon-only delete" title="Delete Permission" data-id="' . $p['id'] . '">' .
                            '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' .
                          '</button>';
                    } else {
                        $actionsHtml = '<span title="Core Permission System Lock" style="color:var(--text3); cursor:not-allowed; display:inline-flex; align-items:center; gap:4px; font-size:11px;"><svg viewBox="0 0 24 24" style="width:12px; height:12px; stroke:currentColor; fill:none; stroke-width:2;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg> System</span>';
                    }
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $nameVal; ?></strong></td>
                    <td><span class="code-badge"><?php echo $codeVal; ?></span></td>
                    <td><?php echo $roleBadge; ?></td>
                    <td><?php echo $createdVal; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end; align-items:center; min-height:26px;">
                        <?php echo $actionsHtml; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
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

<script>
  window.initialPermissionsData = <?php echo json_encode($permissionsData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/permissions.js"></script>
</body>
</html>
