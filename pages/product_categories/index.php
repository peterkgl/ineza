<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_product_categories')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['categories_token'])) {
    $_SESSION['categories_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_product_category');
$canEdit = hasPermission($conn, $userId, 'edit_product_category');
$canDelete = hasPermission($conn, $userId, 'delete_product_category');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Product Categories</title>
<meta name="description" content="Manage transaction product and mineral categories.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/product_categories.css">
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

  <?php $page_title = "Product Category Configuration"; include '../include/navbar.php'; ?>

  <div class="content" id="categoriesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          Product Categories
        </h1>
        <div class="page-sub">Administer raw minerals grades and product category codes</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-categories">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Total Categories</div>
      </div>

      <div class="stat-card" id="card-active-categories">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Active Categories</div>
      </div>

      <div class="stat-card" id="card-inactive-categories">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Inactive Categories</div>
      </div>
    </div>

    <div class="categories-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Product Categories Log</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="categoriesTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Category Code</th>
                <th>Category Name</th>
                <th>Description</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="categoriesList">
              <tr>
                <td colspan="6" class="table-empty">Loading product categories...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Category</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="categoryForm" novalidate>
          <input type="hidden" id="categoryIdInput" name="id" value="">
          <input type="hidden" id="categoryToken" name="token" value="<?php echo htmlspecialchars($_SESSION['categories_token']); ?>">

          <div class="form-group">
            <label for="categoryCode">Category Code</label>
            <input type="text" id="categoryCode" name="category_code" class="form-control" placeholder="e.g. ORES, CONC, PROC" required>
          </div>

          <div class="form-group">
            <label for="categoryName">Category Name</label>
            <input type="text" id="categoryName" name="category_name" class="form-control" placeholder="e.g. Mineral Ores" required>
          </div>

          <div class="form-group">
            <label for="categoryDescription">Description</label>
            <input type="text" id="categoryDescription" name="description" class="form-control" placeholder="e.g. Unprocessed raw mineral ores">
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="categoryActive" name="is_active" value="1" checked>
            <label for="categoryActive" style="margin: 0; cursor: pointer;">
              <span>Mark category as Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Category</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit product categories.
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
      Delete Product Category
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this product category? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/product_categories.js"></script>
</body>
</html>
