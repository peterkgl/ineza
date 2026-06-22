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

// Fetch accounts for dropdown
$accountsList = [];
$accResult = mysqli_query($conn, "SELECT id, account_code, account_name FROM accounts WHERE is_active = 1 ORDER BY account_code ASC");
if ($accResult) {
    while ($row = mysqli_fetch_assoc($accResult)) {
        $accountsList[] = $row;
    }
}

// Fetch currencies for dropdown
$currenciesList = [];
$curResult = mysqli_query($conn, "SELECT id, code, name FROM currencies WHERE is_active = 1 ORDER BY code ASC");
if ($curResult) {
    while ($row = mysqli_fetch_assoc($curResult)) {
        $currenciesList[] = $row;
    }
}

// Fetch suppliers with JOINs
$query = "SELECT s.*, 
                 a.account_code, a.account_name,
                 c.code as currency_code, c.name as currency_name
          FROM suppliers s
          LEFT JOIN accounts a ON s.payables_account_id = a.id
          LEFT JOIN currencies c ON s.currency_id = c.id
          ORDER BY s.name ASC";
$result = mysqli_query($conn, $query);
$suppliersData = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $suppliersData[] = [
            'id' => (int)$row['id'],
            'supplier_type' => $row['supplier_type'],
            'name' => $row['name'],
            'nif' => $row['nif'],
            'vat_reg_no' => $row['vat_reg_no'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'address' => $row['address'],
            'payables_account_id' => $row['payables_account_id'] !== null ? (int)$row['payables_account_id'] : null,
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'currency_id' => $row['currency_id'] !== null ? (int)$row['currency_id'] : null,
            'currency_code' => $row['currency_code'],
            'currency_name' => $row['currency_name'],
            'region' => $row['region'],
            'is_active' => (int)$row['is_active'],
            'notes' => $row['notes'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
}

// Calculate stats
$totalSuppliers = count($suppliersData);
$activeSuppliers = 0;
$inactiveSuppliers = 0;
$coopsCount = 0;
foreach ($suppliersData as $s) {
    if ($s['is_active'] === 1) {
        $activeSuppliers++;
    } else {
        $inactiveSuppliers++;
    }
    if ($s['supplier_type'] === 'cooperative') {
        $coopsCount++;
    }
}
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
        <div class="stat-val" id="stat-total"><?php echo $totalSuppliers; ?></div>
        <div class="stat-label">Registered Suppliers</div>
      </div>

      <div class="stat-card" id="card-active-suppliers">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val" id="stat-active"><?php echo $activeSuppliers; ?></div>
        <div class="stat-label">Active Suppliers</div>
      </div>

      <div class="stat-card" id="card-inactive-suppliers">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Suspended</span>
        </div>
        <div class="stat-val" id="stat-inactive"><?php echo $inactiveSuppliers; ?></div>
        <div class="stat-label">Inactive Suppliers</div>
      </div>

      <div class="stat-card" id="card-coops-count">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
          </div>
          <span class="stat-trend trend-warn">Coops</span>
        </div>
        <div class="stat-val" id="stat-coops"><?php echo $coopsCount; ?></div>
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

        <div class="table-container">
          <table class="data-table" id="suppliersTable">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Name</th>
                <th>Type</th>
                <th>TIN (NIF)</th>
                <th>Phone</th>
                <th>Payables Account</th>
                <th>Currency</th>
                <th>Region</th>
                <th>Status</th>
                <th style="width: 80px; text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody id="suppliersList">
              <?php if (empty($suppliersData)): ?>
                <tr>
                  <td colspan="10" class="table-empty">No suppliers configured yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($suppliersData as $index => $s): ?>
                  <?php
                    $globalIndex = $index + 1;
                    $nameVal = htmlspecialchars($s['name']);
                    $typeVal = htmlspecialchars(ucfirst($s['supplier_type']));
                    $nifVal = $s['nif'] ? htmlspecialchars($s['nif']) : '—';
                    $phoneVal = $s['phone'] ? htmlspecialchars($s['phone']) : '—';
                    $payablesVal = $s['account_code'] ? htmlspecialchars($s['account_code']) . ' - ' . htmlspecialchars($s['account_name']) : '—';
                    $currencyVal = $s['currency_code'] ? htmlspecialchars($s['currency_code']) : '—';
                    $regionVal = $s['region'] ? htmlspecialchars($s['region']) : '—';
                    $statusLabel = $s['is_active'] === 1
                      ? '<span class="status-pill pill-green">Active</span>'
                      : '<span class="status-pill pill-red">Inactive</span>';
                  ?>
                  <tr>
                    <td><?php echo $globalIndex; ?></td>
                    <td><strong><?php echo $nameVal; ?></strong></td>
                    <td><span class="code-badge"><?php echo $typeVal; ?></span></td>
                    <td><?php echo $nifVal; ?></td>
                    <td><?php echo $phoneVal; ?></td>
                    <td><?php echo $payablesVal; ?></td>
                    <td><?php echo $currencyVal; ?></td>
                    <td><?php echo $regionVal; ?></td>
                    <td><?php echo $statusLabel; ?></td>
                    <td style="text-align: right;">
                      <div class="action-buttons" style="justify-content: flex-end;">
                        <button class="btn-icon-only edit" title="Edit Supplier" data-id="<?php echo $s['id']; ?>">
                          <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        </button>
                        <button class="btn-icon-only delete" title="Delete Supplier" data-id="<?php echo $s['id']; ?>">
                          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
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
            <label for="supplierPayablesAccount">Payables Account</label>
            <select id="supplierPayablesAccount" name="payables_account_id" class="form-control">
              <option value="">(Select Payables Account)</option>
              <?php foreach ($accountsList as $acc): ?>
                <option value="<?php echo $acc['id']; ?>"><?php echo htmlspecialchars($acc['account_code']) . ' - ' . htmlspecialchars($acc['account_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="supplierCurrency">Currency</label>
            <select id="supplierCurrency" name="currency_id" class="form-control" required>
              <option value="">(Select Currency)</option>
              <?php foreach ($currenciesList as $cur): ?>
                <option value="<?php echo $cur['id']; ?>"><?php echo htmlspecialchars($cur['code']) . ' - ' . htmlspecialchars($cur['name']); ?></option>
              <?php endforeach; ?>
            </select>
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

<script>
  window.initialSuppliersData = <?php echo json_encode($suppliersData); ?>;
  window.accountsList = <?php echo json_encode($accountsList); ?>;
  window.currenciesList = <?php echo json_encode($currenciesList); ?>;
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/suppliers.js"></script>
</body>
</html>
