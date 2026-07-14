<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_sales') && 
    !hasPermission($conn, $userId, 'create_sale') && 
    !hasPermission($conn, $userId, 'edit_sale') && 
    !hasPermission($conn, $userId, 'delete_sale')) {
    header("Location: ../dashboard");
    exit();
}

if (empty($_SESSION['sales_token'])) {
    $_SESSION['sales_token'] = bin2hex(random_bytes(32));
}

$canCreate = hasPermission($conn, $userId, 'create_sale');
$canDelete = hasPermission($conn, $userId, 'delete_sale');

// Fetch sales orders
$salesData = [];
$query = "SELECT s.*, c.name as customer_name, c.customer_code, w.warehouse_name, w.warehouse_code,
                 (SELECT COALESCE(SUM(amount_allocated), 0) FROM customer_payment_allocations WHERE sale_id = s.id) as total_paid
          FROM sells s
          JOIN customer c ON s.customer_id = c.id
          JOIN warehouses w ON s.warehouse_id = w.id
          ORDER BY s.sale_date DESC, s.id DESC";

$result = mysqli_query($conn, $query);

$totalSalesUsd = 0.0;
$totalPaidUsd = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $saleId = (int)$row['id'];
        
        // Convert to USD for stats
        $valUsd = (float)$row['total_value_usd'];
        $paidUsd = 0.0;
        
        // Sum total paid in USD
        $paidQuery = "SELECT cp.amount_base 
                      FROM customer_payment_allocations cpa
                      JOIN customer_payments cp ON cpa.customer_payment_id = cp.id
                      WHERE cpa.sale_id = $saleId";
        $paidRes = mysqli_query($conn, $paidQuery);
        if ($paidRes) {
            while ($pRow = mysqli_fetch_assoc($paidRes)) {
                $paidUsd += (float)$pRow['amount_base'];
            }
        }

        $row['id'] = $saleId;
        $row['total_qty_kg'] = (float)$row['total_qty_kg'];
        $row['total_value_rwf'] = (float)$row['total_value_rwf'];
        $row['total_value_usd'] = (float)$row['total_value_usd'];
        $row['exchange_rate'] = (float)$row['exchange_rate'];
        $row['total_paid_usd'] = $paidUsd;
        $row['outstanding_usd'] = $valUsd - $paidUsd;
        
        $totalSalesUsd += $valUsd;
        $totalPaidUsd += $paidUsd;

        $salesData[] = $row;
    }
}

$outstandingUsd = $totalSalesUsd - $totalPaidUsd;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Sales & Operations</title>
<meta name="description" content="Manage and record mineral product sales, customer invoicing, and payments.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/sales.css">
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

  <?php $page_title = "Sales Management"; include '../include/navbar.php'; ?>

  <div class="content" id="salesContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Mineral Sales & Invoices
        </h1>
        <div class="page-sub">Track mineral sale orders, exports, customer payments, and accounts receivable balances.</div>
      </div>
      <div class="page-actions">
        <?php if ($canCreate): ?>
          <a href="record.php" class="btn-sm btn-primary" style="text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <svg class="btn-icon" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Record Sale
          </a>
        <?php endif; ?>
        <button class="btn-sm" id="refreshBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Refresh List
        </button>
      </div>
    </div>

    <!-- Stats Section -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Gross Sales</span>
        </div>
        <div class="stat-val">$<?php echo number_format($totalSalesUsd, 2); ?></div>
        <div class="stat-label">Total Sales (USD)</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Collected</span>
        </div>
        <div class="stat-val">$<?php echo number_format($totalPaidUsd, 2); ?></div>
        <div class="stat-label">Payments Received</div>
      </div>

      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--purple-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--purple)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-purple">Receivables</span>
        </div>
        <div class="stat-val">$<?php echo number_format($outstandingUsd, 2); ?></div>
        <div class="stat-label">Outstanding Receivables</div>
      </div>
    </div>

    <!-- Alert Banner -->
    <div id="alertPlaceholder"></div>

    <!-- Sales List Table Card -->
    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px;">
        <div class="card-title">Recorded Sales Logs</div>
        <input type="text" id="searchInput" class="form-control" placeholder="Search sales (No, Customer, Warehouse)..." style="max-width: 320px; padding: 6px 12px; font-size: 12px; margin: 0;">
      </div>

      <div class="table-container">
        <table class="data-table" id="salesTable">
          <thead>
            <tr>
              <th style="width: 50px;">#</th>
              <th>Sale Order No</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Warehouse</th>
              <th style="text-align: right;">Total Qty (kg)</th>
              <th style="text-align: right;">Total Value (USD)</th>
              <th style="text-align: right;">Paid (USD)</th>
              <th style="text-align: right;">Outstanding (USD)</th>
              <th style="text-align: center;">Status</th>
              <th style="width: 150px; text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody id="salesList">
            <?php if (empty($salesData)): ?>
              <tr>
                <td colspan="11" class="table-empty">No sales records found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($salesData as $index => $row): ?>
                <?php
                  $globalIndex = $index + 1;
                  $statusClass = 'status-' . strtolower($row['status']);
                  $statusLabel = strtoupper($row['status']);
                ?>
                <tr data-id="<?php echo $row['id']; ?>">
                  <td><?php echo $globalIndex; ?></td>
                  <td style="font-weight: 500; font-family: monospace;"><?php echo htmlspecialchars($row['sale_no']); ?></td>
                  <td>
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($row['customer_name']); ?></div>
                    <div style="font-size: 10px; color: var(--text3); font-family: monospace;"><?php echo htmlspecialchars($row['customer_code']); ?></div>
                  </td>
                  <td><?php echo htmlspecialchars($row['sale_date']); ?></td>
                  <td>
                    <div><?php echo htmlspecialchars($row['warehouse_name']); ?></div>
                    <div style="font-size: 10px; color: var(--text3); font-family: monospace;"><?php echo htmlspecialchars($row['warehouse_code']); ?></div>
                  </td>
                  <td style="text-align: right; font-weight: 500;"><?php echo number_format($row['total_qty_kg'], 2); ?></td>
                  <td style="text-align: right; font-weight: 500; color: var(--text1);">$<?php echo number_format($row['total_value_usd'], 2); ?></td>
                  <td style="text-align: right; font-weight: 500; color: var(--green);">$<?php echo number_format($row['total_paid_usd'], 2); ?></td>
                  <td style="text-align: right; font-weight: 500; color: <?php echo $row['outstanding_usd'] > 0 ? 'var(--red)' : 'var(--text3)'; ?>;">
                    $<?php echo number_format($row['outstanding_usd'], 2); ?>
                  </td>
                  <td style="text-align: center;">
                    <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                  </td>
                  <td style="text-align: center;">
                    <div style="display: flex; gap: 6px; justify-content: center;">
                      <button class="btn-sm btn-outline view-btn" data-id="<?php echo $row['id']; ?>" title="View Details">
                        <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                      </button>
                      <a class="btn-sm btn-outline" href="receive_payment.php?sale_id=<?php echo $row['id']; ?>" title="Receive Payment">
                        <!-- <svg viewBox="0 0 24 24"><path d="M12 2v20"/><path d="M17 7l-5-5-5 5"/><path d="M7 17l5 5 5-5"/></svg> -->
                         Receive Payment
                      </a>
                      <?php if ($canDelete): ?>
                        <button class="btn-sm btn-outline delete-btn text-danger" data-id="<?php echo $row['id']; ?>" data-no="<?php echo htmlspecialchars($row['sale_no']); ?>" title="Delete">
                          <svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Detail View Modal -->
    <div class="modal-backdrop" id="viewModal">
      <div class="modal-container" style="max-width: 700px;">
        <div class="modal-header">
          <div class="modal-title" id="modalSaleNo">Sale Details</div>
          <button class="modal-close" id="closeViewModal">&times;</button>
        </div>
        <div class="modal-body" id="modalDetailsBody">
          <!-- Populated by JS -->
        </div>
        <div class="modal-footer">
          <button class="btn-sm" id="closeViewModalBtn">Close Details</button>
        </div>
      </div>
    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<input type="hidden" id="salesToken" value="<?php echo htmlspecialchars($_SESSION['sales_token']); ?>">

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script src="../../src/js/sales.js"></script>
</body>
</html>
