<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_roles')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['roles_token'])) {
    $_SESSION['roles_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_role');
$canEdit = hasPermission($conn, $userId, 'edit_role');
$canDelete = hasPermission($conn, $userId, 'delete_role');

$permsQuery = "SELECT id, permition_name, permition_code FROM permissions ORDER BY permition_name ASC";
$permsResult = mysqli_query($conn, $permsQuery);
$allPermissions = [];
if ($permsResult) {
    while ($row = mysqli_fetch_assoc($permsResult)) {
        $allPermissions[] = $row;
    }
}

// Fetch Roles
$query = "SELECT r.id, r.name, r.description, r.created_at, GROUP_CONCAT(DISTINCT rp.permission_id) as permission_ids, COUNT(DISTINCT ur.user_id) as user_count
          FROM roles r
          LEFT JOIN role_permissions rp ON r.id = rp.role_id
          LEFT JOIN user_roles ur ON r.id = ur.role_id
          GROUP BY r.id
          ORDER BY r.name ASC";
$result = mysqli_query($conn, $query);
$rolesData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $permIds = [];
        if ($row['permission_ids'] !== null && $row['permission_ids'] !== '') {
            $permIds = array_map('intval', explode(',', $row['permission_ids']));
        }
        $rolesData[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'permission_ids' => $permIds,
            'user_count' => (int)$row['user_count']
        ];
    }
}

// Calculate stats
$totalRoles = count($rolesData);
$activeRolesCount = 0;
foreach ($rolesData as $r) {
    if ($r['user_count'] > 0) {
        $activeRolesCount++;
    }
}

// Get seeded permissions count
$permsCountQuery = "SELECT COUNT(*) as cnt FROM permissions";
$permsCountRes = mysqli_query($conn, $permsCountQuery);
$totalPermissionsCount = 0;
if ($permsCountRes) {
    $row = mysqli_fetch_assoc($permsCountRes);
    $totalPermissionsCount = (int)$row['cnt'];
}

// Max permissions in a single role
$maxPermissionsInRole = 0;
foreach ($rolesData as $r) {
    $count = count($r['permission_ids']);
    if ($count > $maxPermissionsInRole) {
        $maxPermissionsInRole = $count;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Roles</title>
<meta name="description" content="Manage security roles and permission settings.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/role.css">
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

  <?php $page_title = "Role Management"; include '../include/navbar.php'; ?>

  <div class="content" id="rolesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          Security Roles
        </h1>
        <div class="page-sub">Configure user access levels and system permissions controls</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-roles">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalRoles; ?></div>
        <div class="stat-label">Defined Roles</div>
      </div>

      <div class="stat-card" id="card-assigned-roles">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </div>
          <span class="stat-trend trend-up">Usage</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeRolesCount; ?></div>
        <div class="stat-label">Active Assigned Roles</div>
      </div>

      <div class="stat-card" id="card-total-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <span class="stat-trend trend-down">System</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $totalPermissionsCount; ?></div>
        <div class="stat-label">Seeded Permissions</div>
      </div>

      <div class="stat-card" id="card-max-permissions">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          </div>
          <span class="stat-trend trend-warn">Capacity</span>
        </div>
        <div class="stat-val" id="stat-base"><?php echo $maxPermissionsInRole; ?></div>
        <div class="stat-label">Max Permissions in Role</div>
      </div>
    </div>

    <div class="roles-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Configured Security Roles</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="rolesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Role Name</th>
                <th>Description</th>
                <th>Privileges Assigned</th>
                <th>Created Date</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="rolesList">
              <?php if (empty($rolesData)): ?>
                <tr>
                  <td colspan="6" class="table-empty">No roles defined yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($rolesData as $index => $r): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $nameVal = htmlspecialchars($r['name']);
                    $descVal = $r['description'] ? htmlspecialchars($r['description']) : '—';
                    $createdVal = htmlspecialchars(explode(' ', $r['created_at'])[0]);
                    $permCount = count($r['permission_ids']);
                    $permBadgeClass = $permCount > 0 ? 'pill-green' : '';
                    $permBadge = '<span class="status-pill ' . $permBadgeClass . '">' . $permCount . ' Privileges</span>';

                    $deleteBtn = '';
                    if (strtolower($r['name']) !== 'admin') {
                        $deleteBtn = '<button class="btn-icon-only delete" title="Delete Role" data-id="' . $r['id'] . '">' .
                          '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' .
                        '</button>';
                    }
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $nameVal; ?></strong></td>
                    <td><?php echo $descVal; ?></td>
                    <td><?php echo $permBadge; ?></td>
                    <td><?php echo $createdVal; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end;">
                        <button class="btn-icon-only edit" title="Edit Role" data-id="<?php echo $r['id']; ?>">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <?php echo $deleteBtn; ?>
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
          <div class="card-title" id="formTitle">Add Security Role</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="roleForm" novalidate>
          <input type="hidden" id="roleIdInput" name="id" value="">
          <input type="hidden" id="roleToken" name="token" value="<?php echo htmlspecialchars($_SESSION['roles_token']); ?>">

          <div class="form-group">
            <label for="roleName">Role Name</label>
            <input type="text" id="roleName" name="name" class="form-control" placeholder="e.g. auditor" required>
          </div>

          <div class="form-group">
            <label for="roleDescription">Description</label>
            <input type="text" id="roleDescription" name="description" class="form-control" placeholder="e.g. Audit logs viewing rights" required>
          </div>

          <div class="form-group">
            <label>Assigned Permissions</label>
            <div class="permissions-container" id="permsContainer">
              <?php foreach ($allPermissions as $p): ?>
                <div class="checkbox-group" style="margin: 6px 0;">
                  <input type="checkbox" name="permissions[]" value="<?php echo $p['id']; ?>" id="perm-<?php echo $p['id']; ?>" class="perm-checkbox">
                  <label for="perm-<?php echo $p['id']; ?>" style="margin:0; cursor:pointer;">
                    <span><?php echo htmlspecialchars($p['permition_name']); ?></span>
                  </label>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Role</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to configure security roles.
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
      Delete Security Role
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this security role? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script>
  window.initialRolesData = <?php echo json_encode($rolesData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/role.js"></script>
</body>
</html>
