<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_account_types') && 
    !hasPermission($conn, $userId, 'create_account_type') && 
    !hasPermission($conn, $userId, 'edit_account_type') && 
    !hasPermission($conn, $userId, 'delete_account_type')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['account_type_token'])) {
    $_SESSION['account_type_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_account_type');
$canEdit = hasPermission($conn, $userId, 'edit_account_type');
$canDelete = hasPermission($conn, $userId, 'delete_account_type');

// Get all account types from database (including hard-coded groups)
$query = "SELECT a.*, p.name as parent_name, p.code as parent_code 
          FROM account_types a 
          LEFT JOIN account_types p ON a.parent_id = p.id 
          ORDER BY a.code ASC";
$result = mysqli_query($conn, $query);
$typesData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $typesData[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'parent_id' => $row['parent_id'] !== null ? (int)$row['parent_id'] : null,
            'parent_name' => $row['parent_name'],
            'parent_code' => $row['parent_code'],
            'is_editable' => (int)$row['is_editable'],
            'is_deletable' => (int)$row['is_deletable'],
            'is_hardcoded' => (int)$row['id'] < 0
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Account Types</title>
<meta name="description" content="Manage chart of accounts groups and account categories.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/account_types.css">
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

  <?php $page_title = "Account Types Configuration"; include '../include/navbar.php'; ?>

  <div class="content" id="accountTypesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
          Account Categories & Types
        </h1>
        <div class="page-sub">Configure account groups, root categories, and organizational sub-types</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="account-types-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Account Types</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="accountTypesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Parent Group</th>
                <th>System Type</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="accountTypesList">
              <?php
              $rowNum = 1;
              $filteredTypesData = array_filter($typesData, function($type) {
                  return !$type['is_hardcoded'];
              });
              
              if (!empty($filteredTypesData)) {
                  foreach ($filteredTypesData as $row) {
                      $isSystem = ((int)$row['is_editable'] === 0 && (int)$row['is_deletable'] === 0);
                      $systemBadge = $isSystem 
                          ? '<span class="status-pill" style="background:var(--blue-bg); color:var(--blue)">System Lock</span>' 
                          : '<span class="status-pill" style="background:var(--bg); color:var(--text3)">User Defined</span>';

                      $editDisabled = ((int)$row['is_editable'] === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : '');
                      $deleteDisabled = ((int)$row['is_deletable'] === 0 ? 'disabled style="opacity:0.3; cursor:not-allowed;"' : '');

                      $actions = '<div class="action-buttons" style="justify-content: flex-end;">';
                      if ($canEdit) {
                          $actions .= '<button class="btn-icon-only edit" data-id="' . $row['id'] . '" title="Edit" ' . $editDisabled . '>';
                          $actions .= '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                          $actions .= '</button>';
                      }
                      if ($canDelete) {
                          $actions .= '<button class="btn-icon-only delete" data-id="' . $row['id'] . '" title="Delete" ' . $deleteDisabled . '>';
                          $actions .= '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
                          $actions .= '</button>';
                      }
                      $actions .= '</div>';

                      echo '<tr>';
                      echo '<td>' . $rowNum++ . '</td>';
                      echo '<td><span class="code-badge" style="font-weight:600; font-family:monospace;">' . htmlspecialchars($row['code']) . '</span></td>';
                      echo '<td class="td-name" style="font-weight:500;">' . htmlspecialchars($row['name']) . '</td>';
                      echo '<td>' . ($row['parent_name'] ? '<span class="parent-type-badge">' . htmlspecialchars($row['parent_name']) . ' (' . htmlspecialchars($row['parent_code']) . ')</span>' : '—') . '</td>';
                      echo '<td>' . $systemBadge . '</td>';
                      echo '<td style="text-align: right;">' . $actions . '</td>';
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="6" class="table-empty">No account types registered yet.</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
        <script>
          window.initialAccountTypesData = <?php echo json_encode($typesData); ?>;
        </script>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Account Type</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="accountTypeForm" novalidate>
          <input type="hidden" id="accountTypeId" name="id" value="">
          <input type="hidden" id="accountTypeToken" name="token" value="<?php echo htmlspecialchars($_SESSION['account_type_token']); ?>">

          <div class="form-group">
            <label for="accountTypeParent">Parent Group</label>
            <select id="accountTypeParent" name="parent_id" class="form-control" required>
              <option value="">Select a parent group</option>
              <?php
              // Only show hard-coded groups in the parent selector
              foreach ($typesData as $type) {
                  if ($type['is_hardcoded']) {
                      echo '<option value="' . $type['id'] . '">' . htmlspecialchars($type['name']) . '</option>';
                  }
              }
              ?>
            </select>
          </div>

          <div class="form-group">
            <label for="accountTypeCode">Account Type Code</label>
            <input type="text" id="accountTypeCode" name="code" class="form-control" placeholder="e.g. 1300, 6500" maxlength="10" required readonly style="background:var(--card);">
            <div id="codeValidationFeedback"></div>
          </div>

          <div class="form-group">
            <label for="accountTypeName">Account Type Name</label>
            <input type="text" id="accountTypeName" name="name" class="form-control" placeholder="e.g. Operating Expenses" required>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Account Type</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
        <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
          You do not have administrative permission to create or edit account types.
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
      Delete Account Type
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this account type? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red)">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/account_types.js"></script>
</body>
</html>
