<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_products')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['products_token'])) {
    $_SESSION['products_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_product');
$canEdit = hasPermission($conn, $userId, 'edit_product');
$canDelete = hasPermission($conn, $userId, 'delete_product');

// Load all active accounts for dropdown options
$accountsQuery = "SELECT id, account_code, account_name FROM accounts WHERE is_active = 1 ORDER BY account_code ASC";
$accountsResult = mysqli_query($conn, $accountsQuery);
$accountOptionsHtml = "<option value=''>-- Select Account --</option>";
if ($accountsResult) {
    while ($row = mysqli_fetch_assoc($accountsResult)) {
        $accountOptionsHtml .= "<option value='{$row['id']}'>[" . htmlspecialchars($row['account_code']) . "] " . htmlspecialchars($row['account_name']) . "</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Products</title>
<meta name="description" content="Manage transaction products and minerals grades codes.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/products.css">
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

  <?php $page_title = "Product Configuration"; include '../include/navbar.php'; ?>

  <div class="content" id="productsContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          Mining Products
        </h1>
        <div class="page-sub">Administer raw minerals grades, codes, and metric parameters</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-products">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Total Products</div>
      </div>

      <div class="stat-card" id="card-active-products">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Active Products</div>
      </div>

      <div class="stat-card" id="card-inactive-products">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Inactive Products</div>
      </div>

      <div class="stat-card" id="card-units-count">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">Units</span>
        </div>
        <div class="stat-val" id="stat-base">0</div>
        <div class="stat-label">Unique Unit Types</div>
      </div>
    </div>

    <div class="products-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Minerals Log</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="productsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Code</th>
                <th>Name</th>
                <th>Mineral Designation</th>
                <th>Unit</th>
                <th>Inventory A/C</th>
                <th>Sales A/C</th>
                <th>COGS A/C</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="productsList">
              <tr>
                <td colspan="10" class="table-empty">Loading mining products...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Product</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="productForm" novalidate>
          <input type="hidden" id="productIdInput" name="id" value="">
          <input type="hidden" id="productToken" name="token" value="<?php echo htmlspecialchars($_SESSION['products_token']); ?>">

          <div class="form-group">
            <label for="productCode">Product Code</label>
            <input type="text" id="productCode" name="code" class="form-control" placeholder="e.g. Sn, Ta, Nb" required>
          </div>

          <div class="form-group">
            <label for="productName">Product Name</label>
            <input type="text" id="productName" name="name" class="form-control" placeholder="e.g. Tin, Tantalum" required>
          </div>

          <div class="form-group">
            <label for="productFullName">Chemical / Mineral Full Designation</label>
            <input type="text" id="productFullName" name="full_name" class="form-control" placeholder="e.g. Cassiterite SnO2">
          </div>

          <div class="form-group">
            <label for="productUnit">Unit of Measure</label>
            <input type="text" id="productUnit" name="unit_of_measure" class="form-control" placeholder="e.g. kg, tonnes" value="kg" required>
          </div>

          <div class="form-group">
            <label for="productDescription">Description</label>
            <input type="text" id="productDescription" name="description" class="form-control" placeholder="e.g. Concentrated tin ore logs">
          </div>

          <div class="form-group">
            <label for="inventoryAccount">Inventory Account</label>
            <select id="inventoryAccount" name="inventory_account_id" class="form-control">
              <?php echo $accountOptionsHtml; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="salesAccount">Sales Account</label>
            <select id="salesAccount" name="sales_account_id" class="form-control">
              <?php echo $accountOptionsHtml; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="cogsAccount">Cost of Goods Sold (COGS) Account</label>
            <select id="cogsAccount" name="cogs_account_id" class="form-control">
              <?php echo $accountOptionsHtml; ?>
            </select>
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="productActive" name="is_active" value="1" checked>
            <label for="productActive" style="margin: 0; cursor: pointer;">
              <span>Mark product as Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Product</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit products.
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
      Delete Product
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this product configuration? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/products.js"></script>
</body>
</html>
