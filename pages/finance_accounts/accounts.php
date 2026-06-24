<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_accounts')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['account_token'])) {
    $_SESSION['account_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_account');
$canEdit = hasPermission($conn, $userId, 'edit_account');
$canDelete = hasPermission($conn, $userId, 'delete_account');

// Traverse up to find root parent and determine valid range for dropdown options
function getOptionRange($conn, $typeId, &$cache = []) {
    if (isset($cache[$typeId])) {
        return $cache[$typeId];
    }
    
    $currentId = $typeId;
    $rootType = null;
    $depth = 0;
    
    while ($currentId > 0 && $depth < 10) {
        $q = "SELECT id, code, name, parent_id FROM account_types WHERE id = $currentId LIMIT 1";
        $res = mysqli_query($conn, $q);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            $rootType = $row;
            if ($row['parent_id'] === null || (int)$row['parent_id'] === 0 || (int)$row['parent_id'] === $currentId) {
                break;
            }
            $currentId = (int)$row['parent_id'];
            $depth++;
        } else {
            break;
        }
    }
    
    if ($rootType) {
        $rootCode = (int)$rootType['code'];
        $res = [
            'min' => $rootCode,
            'max' => $rootCode + 999,
            'root_name' => $rootType['name'],
            'root_code' => $rootType['code']
        ];
        $cache[$typeId] = $res;
        return $res;
    }
    
    return null;
}

$typeQuery = "SELECT id, code, name FROM account_types WHERE id > 0 ORDER BY code ASC";
$typeResult = mysqli_query($conn, $typeQuery);
$typeOptionsHtml = "";
$cache = [];

if ($typeResult) {
    while ($row = mysqli_fetch_assoc($typeResult)) {
        $range = getOptionRange($conn, (int)$row['id'], $cache);
        $dataAttrs = "";
        if ($range) {
            $dataAttrs = "data-min='{$range['min']}' data-max='{$range['max']}' data-root='" . htmlspecialchars($range['root_name']) . " ({$range['root_code']})'";
        }
        $typeOptionsHtml .= "<option value='{$row['id']}' {$dataAttrs}>" . htmlspecialchars($row['name']) . " (" . htmlspecialchars($row['code']) . ")</option>";
    }
}

// Fetch Accounts Data for Server-Side Rendering
$accountsQuery = "SELECT a.*, t.name as account_type_name, t.code as account_type_code
                  FROM accounts a 
                  JOIN account_types t ON a.account_type_id = t.id 
                  ORDER BY a.account_code ASC";
$accountsResult = mysqli_query($conn, $accountsQuery);
$initialAccountsData = [];
$totalAccounts = 0;
$activeAccounts = 0;
$inactiveAccounts = 0;

if ($accountsResult) {
    while ($row = mysqli_fetch_assoc($accountsResult)) {
        $initialAccountsData[] = [
            'id' => (int)$row['id'],
            'account_type_id' => (int)$row['account_type_id'],
            'account_type_name' => $row['account_type_name'],
            'account_type_code' => $row['account_type_code'],
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'is_active' => (int)$row['is_active'],
            'description' => $row['description'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
        
        if ((int)$row['is_active'] === 1) {
            $activeAccounts++;
        } else {
            $inactiveAccounts++;
        }
    }
}
$totalAccounts = count($initialAccountsData);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Accounts</title>
<meta name="description" content="Manage system financial accounts, chart of accounts codes, balances, and descriptions.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/accounts.css">
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

  <?php $page_title = "Financial Accounts Management"; include '../include/navbar.php'; ?>

  <div class="content" id="accountsContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
          Chart of Accounts
        </h1>
        <div class="page-sub">Create and configure ledger accounts, starting balances, and descriptions</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-accounts">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total"><?php echo $totalAccounts; ?></div>
        <div class="stat-label">Total Accounts</div>
      </div>

      <div class="stat-card" id="card-active-accounts">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeAccounts; ?></div>
        <div class="stat-label">Active Accounts</div>
      </div>

      <div class="stat-card" id="card-inactive-accounts">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          </div>
          <span class="stat-trend trend-down">Inactive</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $inactiveAccounts; ?></div>
        <div class="stat-label">Inactive Accounts</div>
      </div>
    </div>

    <div class="accounts-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Accounts</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search accounts..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div class="table-container">
          <table class="data-table" id="accountsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Type</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="accountsList">
              <?php
              $rowNum = 1;
              if (!empty($initialAccountsData)) {
                  foreach ($initialAccountsData as $acc) {
                      $statusBadge = $acc['is_active'] == 1 
                          ? '<span class="status-pill pill-green">Active</span>' 
                          : '<span class="status-pill pill-red">Inactive</span>';

                      $actions = '<div class="action-buttons" style="justify-content: flex-end;">';
                      if ($canEdit) {
                          $actions .= '<button class="btn-icon-only edit" data-id="' . $acc['id'] . '" title="Edit">';
                          $actions .= '<svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>';
                          $actions .= '</button>';
                      }
                      if ($canDelete) {
                          $actions .= '<button class="btn-icon-only delete" data-id="' . $acc['id'] . '" title="Delete">';
                          $actions .= '<svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
                          $actions .= '</button>';
                      }
                      $actions .= '</div>';

                      echo '<tr>';
                      echo '<td>' . $rowNum++ . '</td>';
                      echo '<td><span class="code-badge" style="font-weight:600; font-family:monospace;">' . htmlspecialchars($acc['account_code']) . '</span></td>';
                      echo '<td class="td-name" style="font-weight:500;">' . htmlspecialchars($acc['account_name']) . '</td>';
                      echo '<td><span class="parent-type-badge">' . htmlspecialchars($acc['account_type_name']) . ' (' . htmlspecialchars($acc['account_type_code']) . ')</span></td>';
                      echo '<td>' . $statusBadge . '</td>';
                      echo '<td style="text-align: right;">' . $actions . '</td>';
                      echo '</tr>';
                  }
              } else {
                  echo '<tr><td colspan="6" class="table-empty">No financial accounts found.</td></tr>';
              }
              ?>
            </tbody>
          </table>
        </div>
        <script>
          window.initialAccountsData = <?php echo json_encode($initialAccountsData); ?>;
        </script>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Account</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="accountForm" novalidate>
          <input type="hidden" id="accountId" name="id" value="">
          <input type="hidden" id="accountToken" name="token" value="<?php echo htmlspecialchars($_SESSION['account_token']); ?>">

          <div class="form-group">
            <label for="accountType">Account Type Category</label>
            <select id="accountType" name="account_type_id" class="form-control" required>
              <option value="">-- Select Type --</option>
              <?php echo $typeOptionsHtml; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="accountCode">Account Code</label>
            <input type="text" id="accountCode" name="account_code" class="form-control" placeholder="Auto-generated upon type selection" maxlength="20" required readonly style="background:var(--bg); opacity:0.8; cursor:not-allowed;">
            <div id="codeValidationFeedback"></div>
          </div>

          <div class="form-group">
            <label for="accountName">Account Name</label>
            <input type="text" id="accountName" name="account_name" class="form-control" placeholder="e.g. Cash on Hand, Accounts Payable" required>
          </div>

          <div class="form-group">
            <label for="description">Description / Notes</label>
            <textarea id="description" name="description" class="form-control" placeholder="Optional notes about this account..." rows="3"></textarea>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="accountIsActive" name="is_active" value="1" checked>
            <span>Activate Account</span>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Account</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit accounts.
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
      Delete Account
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this account? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/accounts.js"></script>
</body>
</html>
