<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_suppliers')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['suppliers_token'])) {
    $_SESSION['suppliers_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_supplier');
$canEdit = hasPermission($conn, $userId, 'edit_supplier');
$canDelete = hasPermission($conn, $userId, 'delete_supplier');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Suppliers</title>
<meta name="description" content="Manage transaction suppliers, cooperatives, and mining corporate entities.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/suppliers.css">
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

  <?php $page_title = "Supplier Management"; include '../include/navbar.php'; ?>

  <div class="content" id="suppliersContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          Suppliers
        </h1>
        <div class="page-sub">Manage raw mineral source providers, mining cooperatives, and companies</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <div class="stats-grid">
      <div class="stat-card" id="card-total-suppliers">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val" id="stat-total">0</div>
        <div class="stat-label">Registered Suppliers</div>
      </div>

      <div class="stat-card" id="card-active-suppliers">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active">0</div>
        <div class="stat-label">Active Suppliers</div>
      </div>

      <div class="stat-card" id="card-inactive-suppliers">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive">0</div>
        <div class="stat-label">Inactive Suppliers</div>
      </div>

      <div class="stat-card" id="card-coops-count">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          </div>
          <span class="stat-trend trend-warn">Coops</span>
        </div>
        <div class="stat-val" id="stat-coops">0</div>
        <div class="stat-label">Mining Cooperatives</div>
      </div>
    </div>

    <div class="suppliers-grid">

      <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
          <div class="card-title">Registered Suppliers Directory</div>
          <input type="text" id="searchInput" class="form-control" placeholder="Search..." style="max-width: 240px; padding: 6px 10px; font-size: 12px; margin: 0;">
        </div>
        
        <div id="alertPlaceholder"></div>

        <div style="overflow-x: auto;">
          <table class="data-table" id="suppliersTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Name</th>
                <th>Type</th>
                <th>TIN (NIF)</th>
                <th>Phone</th>
                <th>Region</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="suppliersList">
              <tr>
                <td colspan="8" class="table-empty">Loading suppliers database...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="card" id="formCard">
        <div class="card-header">
          <div class="card-title" id="formTitle">Add New Supplier</div>
        </div>
        
        <div id="formAlertPlaceholder"></div>

        <?php if ($canCreate || $canEdit): ?>
        <form id="supplierForm" novalidate>
          <input type="hidden" id="supplierIdInput" name="id" value="">
          <input type="hidden" id="supplierToken" name="token" value="<?php echo htmlspecialchars($_SESSION['suppliers_token']); ?>">

          <div class="form-group">
            <label for="supplierType">Supplier Type</label>
            <select id="supplierType" name="supplier_type" class="form-control" required>
              <option value="individual">Individual Practitioner</option>
              <option value="cooperative">Mining Cooperative</option>
              <option value="company">Corporate Company</option>
            </select>
          </div>

          <div class="form-group">
            <label for="supplierName">Supplier/Entity Name</label>
            <input type="text" id="supplierName" name="name" class="form-control" placeholder="e.g. Jean Bosco, Coopérative de Minage" required>
          </div>

          <div class="form-group">
            <label for="supplierNif">NIF (Tax ID / TIN)</label>
            <input type="text" id="supplierNif" name="nif" class="form-control" placeholder="e.g. 100293847">
          </div>

          <div class="form-group">
            <label for="supplierVat">VAT Registration No</label>
            <input type="text" id="supplierVat" name="vat_reg_no" class="form-control" placeholder="e.g. VAT-928472">
          </div>

          <div class="form-group">
            <label for="supplierPhone">Phone Contact</label>
            <input type="text" id="supplierPhone" name="phone" class="form-control" placeholder="e.g. +250 788 000 000">
          </div>

          <div class="form-group">
            <label for="supplierEmail">Email Address</label>
            <input type="email" id="supplierEmail" name="email" class="form-control" placeholder="e.g. supplier@example.com">
          </div>

          <div class="form-group">
            <label for="supplierAddress">Physical Address</label>
            <input type="text" id="supplierAddress" name="address" class="form-control" placeholder="e.g. KN 5 Rd, Kigali">
          </div>

          <div class="form-group">
            <label for="supplierRegion">Mining Region / Zone</label>
            <input type="text" id="supplierRegion" name="region" class="form-control" placeholder="e.g. Southern Province, Rutsiro">
          </div>

          <div class="form-group">
            <label for="supplierNotes">Administrative Notes</label>
            <input type="text" id="supplierNotes" name="notes" class="form-control" placeholder="e.g. Approved cassiterite local producer">
          </div>

          <div class="checkbox-group">
            <input type="checkbox" id="supplierActive" name="is_active" value="1" checked>
            <label for="supplierActive" style="margin: 0; cursor: pointer;">
              <span>Mark supplier as Active</span>
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 20px;">
            <button type="submit" class="btn-sm btn-primary" style="flex: 1;" id="saveBtn">Save Supplier</button>
            <button type="button" class="btn-sm" style="flex: 1; display: none;" id="cancelBtn">Cancel</button>
          </div>
        </form>
        <?php else: ?>
          <div style="color:var(--text3); font-size:12px; padding:20px 0; text-align:center;">
            You do not have administrative permission to create or edit suppliers.
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
      Delete Supplier
    </div>
    <div class="confirm-body" id="confirmBody">
      Are you sure you want to delete this supplier configuration? This action is permanent and cannot be undone.
    </div>
    <div class="confirm-footer">
      <button class="btn-sm" id="confirmCancelBtn">Cancel</button>
      <button class="btn-sm btn-primary" id="confirmDeleteBtn" style="background:var(--red); border-color:var(--red);">Delete</button>
    </div>
  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/suppliers.js"></script>
</body>
</html>
