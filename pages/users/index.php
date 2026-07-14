<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_users') && 
    !hasPermission($conn, $userId, 'create_user') && 
    !hasPermission($conn, $userId, 'edit_user') && 
    !hasPermission($conn, $userId, 'delete_user')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['users_token'])) {
    $_SESSION['users_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_user');
$canEdit = hasPermission($conn, $userId, 'edit_user');
$canDelete = hasPermission($conn, $userId, 'delete_user');

$rolesQuery = "SELECT id, name, description FROM roles ORDER BY name ASC";
$rolesResult = mysqli_query($conn, $rolesQuery);
$roles = [];
if ($rolesResult) {
    while ($row = mysqli_fetch_assoc($rolesResult)) {
        $roles[] = $row;
    }
}

// Fetch users
$query = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.is_active, u.created_at, r.id as role_id, r.name as role_name
          FROM users u
          LEFT JOIN user_roles ur ON u.id = ur.user_id
          LEFT JOIN roles r ON ur.role_id = r.id
          ORDER BY u.id DESC";
$result = mysqli_query($conn, $query);
$usersData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $usersData[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'email' => $row['email'],
            'phone_number' => $row['phone_number'],
            'is_active' => (int)$row['is_active'],
            'created_at' => $row['created_at'],
            'role_id' => $row['role_id'] ? (int)$row['role_id'] : null,
            'role_name' => $row['role_name'] ? $row['role_name'] : 'No Role'
        ];
    }
}

// Calculate stats
$totalUsers = count($usersData);
$activeUsers = 0;
$inactiveUsers = 0;
$distinctRoles = [];
foreach ($usersData as $u) {
    if ($u['is_active'] === 1) {
        $activeUsers++;
    } else {
        $inactiveUsers++;
    }
    if ($u['role_id'] !== null) {
        $distinctRoles[$u['role_id']] = true;
    }
}
$activeRolesAssignedCount = count($distinctRoles);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Users</title>
<meta name="description" content="Manage application users and security roles.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/users.css">
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

  <?php $page_title = "User Management"; include '../include/navbar.php'; ?>

  <div class="content" id="usersContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          System Users
        </h1>
        <div class="page-sub">Administer staff profile records and system permissions</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-users">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalUsers; ?></div>
        <div class="stat-label">Total Users</div>
      </div>

      <div class="stat-card" id="card-active-users">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeUsers; ?></div>
        <div class="stat-label">Active Users</div>
      </div>

      <div class="stat-card" id="card-inactive-users">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $inactiveUsers; ?></div>
        <div class="stat-label">Inactive Users</div>
      </div>

      <div class="stat-card" id="card-roles-assigned">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <span class="stat-trend trend-warn">Security</span>
        </div>
        <div class="stat-val" id="stat-base"><?php echo $activeRolesAssignedCount; ?></div>
        <div class="stat-label">Active Security Roles</div>
      </div>
    </div>

    <div class="users-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered System Staff</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="usersTable" data-current-user-id="<?php echo (int)$userId; ?>">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Assigned Role</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="usersList">
              <?php if (empty($usersData)): ?>
                <tr>
                  <td colspan="7" class="table-empty">No users registered yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($usersData as $index => $u): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $nameVal = htmlspecialchars($u['first_name'] . ' ' . $u['last_name']);
                    $phoneVal = $u['phone_number'] ? htmlspecialchars($u['phone_number']) : '—';
                    $roleVal = htmlspecialchars($u['role_name']);
                    $statusLabel = $u['is_active'] === 1
                      ? '<span class="status-pill pill-green">Active</span>'
                      : '<span class="status-pill pill-red">Inactive</span>';

                    $deleteBtn = '';
                    if ($u['id'] !== (int)$userId) {
                        $deleteBtn = '<button class="btn-icon-only delete" title="Delete User" data-id="' . $u['id'] . '">' .
                          '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>' .
                        '</button>';
                    }
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $nameVal; ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo $phoneVal; ?></td>
                    <td><span class="code-badge"><?php echo $roleVal; ?></span></td>
                    <td><?php echo $statusLabel; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end;">
                        <button class="btn-icon-only edit" title="Edit User" data-id="<?php echo $u['id']; ?>">
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
          <div class="card-title" id="formTitle">Add New User</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="userForm" novalidate>
          <input type="hidden" id="userIdInput" name="id" value="">
          <input type="hidden" id="userToken" name="token" value="<?php echo htmlspecialchars($_SESSION['users_token']); ?>">

          <div class="form-group">
            <label for="firstName">First Name</label>
            <input type="text" id="firstName" name="first_name" class="form-control" placeholder="e.g. Eugene" required>
          </div>

          <div class="form-group">
            <label for="lastName">Last Name</label>
            <input type="text" id="lastName" name="last_name" class="form-control" placeholder="e.g. Peter" required>
          </div>

          <div class="form-group">
            <label for="userEmail">Email Address</label>
            <input type="email" id="userEmail" name="email" class="form-control" placeholder="e.g. eugene@gmail.com" required>
          </div>

          <div class="form-group">
            <label for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone_number" class="form-control" placeholder="e.g. 0785750...">
          </div>

          <div class="form-group">
            <label for="password">Password <span id="passwordTip" style="text-transform:none; font-weight:normal; color:var(--text3); display:none;">(Leave blank to keep unchanged)</span></label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
          </div>

          <div class="form-group">
            <label for="userRole">Security Role</label>
            <select id="userRole" name="role_id" class="form-control" required>
              <option value="">Select Role</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name'] . ' - ' . $r['description']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="userActive" name="is_active" value="1" checked>
            <label for="userActive" style="margin: 0; cursor: pointer;">
              <span>Mark profile as Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save User</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit system users.
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
      Delete User Profile
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this user profile? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script>
  window.initialUsersData = <?php echo json_encode($usersData); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/users.js"></script>
</body>
</html>
