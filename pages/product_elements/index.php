<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_product_elements')) {
    http_response_code(403);
    echo "<div style='font-family:\"Inter\", sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h2 style='color:#E53E3E;'>Access Denied</h2>";
    echo "<p>You do not have permission to access this page.</p>";
    echo "<a href='../dashboard'>Back to Dashboard</a>";
    echo "</div>";
    exit();
}

if (empty($_SESSION['elements_token'])) {
    $_SESSION['elements_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_product_element');
$canEdit = hasPermission($conn, $userId, 'edit_product_element');
$canDelete = hasPermission($conn, $userId, 'delete_product_element');

$products = [];
$productsRes = mysqli_query($conn, "SELECT id, code, name FROM products WHERE is_active = 1 ORDER BY name ASC");
if ($productsRes) {
    while ($row = mysqli_fetch_assoc($productsRes)) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Product Elements</title>
<meta name="description" content="Configure element components, grade metrics, and unit configurations for products.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/product_elements.css">
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

  <?php $page_title = "Product Elements Configuration"; include '../include/navbar.php'; ?>

  <div class="content" id="elementsContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          Product Elements
        </h1>
        <div class="page-sub">Configure and map chemical grade compositions, mineral qualities, and measurement parameters</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-elements">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Configured Elements</div>
      </div>

      <div class="stat-card" id="card-mapped-products">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
          </div>
          <span class="stat-trend trend-up">Products</span>
        </div>
        <div class="stat-val" id="stat-products">0</div>
        <div class="stat-label">Products Mapped</div>
      </div>

      <div class="stat-card" id="card-unit-types">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">Units</span>
        </div>
        <div class="stat-val" id="stat-units">0</div>
        <div class="stat-label">Common Unit Types</div>
      </div>

      <div class="stat-card" id="card-max-order">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><polyline points="18 15 12 9 6 15"/></svg>
          </div>
          <span class="stat-trend trend-blue">Highest</span>
        </div>
        <div class="stat-val" id="stat-max-order">0</div>
        <div class="stat-label">Highest Display Order</div>
      </div>
    </div>

    <div class="elements-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Configured Product Elements</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="elementsTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Product</th>
                <th>Element Code</th>
                <th>Element Name</th>
                <th>Unit</th>
                <th>Display Order</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="elementsList">
              <tr>
                <td colspan="7" class="table-empty">Loading product elements...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add Product Element</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="elementForm" novalidate>
          <input type="hidden" id="elementIdInput" name="id" value="">
          <input type="hidden" id="elementToken" name="token" value="<?php echo htmlspecialchars($_SESSION['elements_token']); ?>">

          <div class="form-group">
            <label for="productSelect">Parent Product</label>
            <select id="productSelect" name="product_id" class="form-control" required>
              <option value="">-- Select Product --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($p['name'] . ' (' . $p['code'] . ')'); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="elementCode">Element Code</label>
            <input type="text" id="elementCode" name="element_code" class="form-control" placeholder="e.g. Sn%, Fe%, Ta2O5%" required>
          </div>

          <div class="form-group">
            <label for="elementName">Element Name</label>
            <input type="text" id="elementName" name="element_name" class="form-control" placeholder="e.g. Tin Grade, Iron Grade" required>
          </div>

          <div class="form-group">
            <label for="elementUnit">Unit of Measure</label>
            <input type="text" id="elementUnit" name="unit" class="form-control" placeholder="e.g. %, ppm" value="%" required>
          </div>

          <div class="form-group">
            <label for="displayOrder">Display Order</label>
            <input type="number" id="displayOrder" name="display_order" class="form-control" min="0" max="255" value="0" required>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Element</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit product elements.
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
      Delete Product Element
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this product element configuration? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/product_elements.js"></script>
</body>
</html>
