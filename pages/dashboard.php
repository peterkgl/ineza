<?php
require_once 'login/auth.php';
require_once __DIR__ . '/../config/db.php';

// Helper to format numbers with K/M suffix
function formatNumberWithSuffix($num, $prefix = '') {
    if ($num >= 1000000) {
        return $prefix . number_format($num / 1000000, 1) . 'M';
    } elseif ($num >= 1000) {
        return $prefix . number_format($num / 1000, 1) . 'K';
    }
    return $prefix . number_format($num);
}

// Helper to format diffs with parentheses for negative values
function formatDiff($val, $prefix = '') {
    if ($val < 0) {
        return '(' . $prefix . number_format(abs($val)) . ')';
    }
    return $prefix . number_format($val);
}

// Fetch exchange rate from RWF (1) to USD (2)
$rate = 1400.0;
$rate_res = mysqli_query($conn, "SELECT rate FROM exchange_rates WHERE from_currency_id = 1 AND to_currency_id = 2 ORDER BY rate_date DESC, id DESC LIMIT 1");
if ($rate_res && $rate_row = mysqli_fetch_assoc($rate_res)) {
    $rate = (float)$rate_row['rate'];
}

// Function to calculate GL book balance for an account code
function getBookBalance($conn, $account_code, $target_date = null) {
    if (!$target_date) $target_date = date('Y-m-d');
    $q = "SELECT SUM(jel.debit) - SUM(jel.credit) as bal
          FROM journal_entry_lines jel
          JOIN journal_entries je ON jel.journal_entry_id = je.id
          JOIN accounts a ON (jel.account_id = a.id OR a.account_code = CAST(jel.account_id AS CHAR))
          WHERE a.account_code = '" . mysqli_real_escape_string($conn, $account_code) . "'
            AND je.statuss = 'POSTED'
            AND je.entry_date <= '" . mysqli_real_escape_string($conn, $target_date) . "'";
    $res = mysqli_query($conn, $q);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (float)($row['bal'] ?? 0.0);
    }
    return 0.0;
}

// Function to calculate statement balance
function getStatementBalance($conn, $report_slug, $target_date = null) {
    if (!$target_date) $target_date = date('Y-m-d');
    $q = "SELECT balance FROM bank_statement_balances
          WHERE report_slug = '" . mysqli_real_escape_string($conn, $report_slug) . "'
            AND as_of_date <= '" . mysqli_real_escape_string($conn, $target_date) . "'
          ORDER BY as_of_date DESC LIMIT 1";
    $res = mysqli_query($conn, $q);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        return (float)$row['balance'];
    }
    return null;
}

// Get targets
$usd_bal = getStatementBalance($conn, 'bank_recon_usd');
if ($usd_bal === null) {
    $usd_bal = getBookBalance($conn, '1010-01');
}

$rwf_bal = getStatementBalance($conn, 'bank_recon_rwf');
if ($rwf_bal === null) {
    $rwf_bal = getBookBalance($conn, '1010-03');
}

$eur_bal = getStatementBalance($conn, 'bank_recon_eur');
if ($eur_bal === null) {
    $eur_bal = getBookBalance($conn, '1010-02');
}

$total_cash_usd = $usd_bal + ($rwf_bal / $rate) + ($eur_bal * 1.08);

// Function to calculate Reconciliation Gap
function getReconDiff($conn, $report_slug, $acct_code) {
    $target_date = date('Y-m-d');
    $report_slug_esc = mysqli_real_escape_string($conn, $report_slug);
    $latest_res = mysqli_query($conn, "SELECT MAX(as_of_date) as max_date FROM bank_statement_balances WHERE report_slug = '{$report_slug_esc}'");
    if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
        $target_date = $latest_row['max_date'] ?? date('Y-m-d');
    }
    
    $book_query = "SELECT SUM(debit) - SUM(credit) as book_bal 
                   FROM journal_entry_lines jel
                   JOIN journal_entries je ON jel.journal_entry_id = je.id
                   JOIN accounts a ON (jel.account_id = a.id OR a.account_code = CAST(jel.account_id AS CHAR))
                   WHERE a.account_code = '{$acct_code}' AND je.statuss = 'POSTED' AND je.entry_date <= '{$target_date}'";
    $book_res = mysqli_query($conn, $book_query);
    $book_row = mysqli_fetch_assoc($book_res);
    $book_balance = (float)($book_row['book_bal'] ?? 0.0);
    
    $statement_query = "SELECT balance FROM bank_statement_balances 
                        WHERE report_slug = '{$report_slug_esc}' AND as_of_date = '{$target_date}' LIMIT 1";
    $statement_res = mysqli_query($conn, $statement_query);
    $statement_row = $statement_res ? mysqli_fetch_assoc($statement_res) : null;
    $statement_balance = $statement_row ? (float)$statement_row['balance'] : 0.0;
    
    $items_query = "SELECT item_type, amount FROM bank_recon_items 
                    WHERE report_slug = '{$report_slug_esc}' AND as_of_date = '{$target_date}'";
    $items_res = mysqli_query($conn, $items_query);
    
    $total_transit = 0.0;
    $total_outstanding = 0.0;
    $total_unrecorded = 0.0;
    if ($items_res) {
        while ($item_row = mysqli_fetch_assoc($items_res)) {
            if ($item_row['item_type'] === 'outstanding_check') {
                $total_outstanding += (float)$item_row['amount'];
            } elseif ($item_row['item_type'] === 'deposit_in_transit') {
                $total_transit += (float)$item_row['amount'];
            } elseif ($item_row['item_type'] === 'unrecorded_payment') {
                $total_unrecorded += (float)$item_row['amount'];
            }
        }
    }
    
    $adjusted_bank = $statement_balance + $total_transit - $total_outstanding;
    $adjusted_book = $book_balance + $total_unrecorded;
    return $adjusted_bank - $adjusted_book;
}

$rwf_diff = getReconDiff($conn, 'bank_recon_rwf', '1010-03');
$usd_diff = getReconDiff($conn, 'bank_recon_usd', '1010-01');
$eur_diff = getReconDiff($conn, 'bank_recon_eur', '1010-02');

// Fetch dynamic Accounts Payable
$payable_query = "SELECT s.name AS supplier_name, l.lots_code,
                         COALESCE(SUM(p.quantity_kg), 0) AS total_weight,
                         COALESCE(SUM(p.purchase_value_usd), 0) AS total_amount,
                         COALESCE(sa.total_advances, 0) AS total_advances,
                         (COALESCE(SUM(p.purchase_value_usd), 0) - COALESCE(sa.total_advances, 0)) AS balance_due,
                         p.purchase_date AS tx_date
                  FROM purchasing p
                  JOIN suppliers s ON p.supplier_id = s.id
                  JOIN lots l ON p.lot_id = l.id
                  LEFT JOIN (
                      SELECT supplier_id, advance_date, SUM(amount) AS total_advances
                      FROM supplier_advances
                      GROUP BY supplier_id, advance_date
                  ) sa ON sa.supplier_id = s.id AND sa.advance_date = p.purchase_date
                  GROUP BY s.id, l.id, p.purchase_date, sa.total_advances
                  ORDER BY p.purchase_date ASC, s.name ASC";
$payable_res = mysqli_query($conn, $payable_query);
$payable_rows = [];
$total_payable_usd = 0.0;
$unpaid_suppliers = [];
if ($payable_res) {
    while ($row = mysqli_fetch_assoc($payable_res)) {
        $payable_rows[] = $row;
        $bal = (float)$row['balance_due'];
        if ($bal > 0) {
            $total_payable_usd += $bal;
            $unpaid_suppliers[$row['supplier_name']] = true;
        }
    }
}
$unpaid_suppliers_count = count($unpaid_suppliers);

// Fetch Mineral Stock
$tin_stock = 0.0;
$ta_stock = 0.0;
$stock_res = mysqli_query($conn, "SELECT product_id, COALESCE(SUM(qty_on_hand), 0) as stock_qty FROM stock GROUP BY product_id");
if ($stock_res) {
    while ($row = mysqli_fetch_assoc($stock_res)) {
        if ($row['product_id'] == 1) {
            $tin_stock = (float)$row['stock_qty'];
        } elseif ($row['product_id'] == 2) {
            $ta_stock = (float)$row['stock_qty'];
        }
    }
}
$total_stock = $tin_stock + $ta_stock;
$tin_pct = $total_stock > 0 ? ($tin_stock / $total_stock) * 100 : 0;
$ta_pct = $total_stock > 0 ? ($ta_stock / $total_stock) * 100 : 0;

// Fetch latest purchase/transaction date
$latest_date = null;
$date_res = mysqli_query($conn, "SELECT MAX(purchase_date) as max_date FROM purchasing");
if ($date_res && $row = mysqli_fetch_assoc($date_res)) {
    $latest_date = $row['max_date'];
}
if (!$latest_date) {
    $latest_date = date('Y-m-d');
}
$as_of_label = date('F j, Y', strtotime($latest_date));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Financial Dashboard</title>
<meta name="description" content="INEZA African Mining Ltd financial management dashboard — cash lead schedule, bank accounts, purchase logs, and mineral stock tracking.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../src/css/dashboard.css">
<link rel="stylesheet" href="../src/css/sidebar.css">
<link rel="stylesheet" href="../src/css/navbar.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include './include/sidebar.php'; ?>

<!-- ========== MAIN ========== -->
<div class="main">

  <?php $page_title = "Cash Lead Schedule"; include './include/navbar.php'; ?>

  <!-- ========== CONTENT ========== -->
  <div class="content" id="dashboardContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
          Cash Lead Schedule
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — As of <?php echo htmlspecialchars($as_of_label); ?></div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="exportBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export
        </button>
        <button class="btn-sm btn-primary" id="addEntryBtn">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Entry
        </button>
      </div>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="stats-grid">
      <!-- Total Cash -->
      <div class="stat-card" id="stat-total-cash">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">Total</span>
        </div>
        <div class="stat-val">$<?php echo number_format($total_cash_usd); ?></div>
        <div class="stat-label">Total Cash Balance</div>
      </div>

      <!-- USD Equity -->
      <div class="stat-card" id="stat-usd">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-blue">USD</span>
        </div>
        <div class="stat-val">$<?php echo number_format($usd_bal); ?></div>
        <div class="stat-label">US$ Equity Balance</div>
      </div>

      <!-- RWF Equity -->
      <div class="stat-card" id="stat-rwf">
        <div class="stat-top">
          <div class="stat-icon" style="background:#FBF0EB">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">RWF</span>
        </div>
        <div class="stat-val"><?php echo formatNumberWithSuffix($rwf_bal, 'RWF '); ?></div>
        <div class="stat-label">RWF Equity ($<?php echo number_format($rwf_bal / $rate); ?>)</div>
      </div>

      <!-- Euro Equity -->
      <div class="stat-card" id="stat-eur">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">EUR</span>
        </div>
        <div class="stat-val">€<?php echo number_format($eur_bal); ?></div>
        <div class="stat-label">Euro Equity (RWF <?php echo formatNumberWithSuffix($eur_bal * 1500); ?>)</div>
      </div>
    </div>

    <!-- ===== MAIN GRID ===== -->
    <div class="main-grid">

      <!-- LEFT COLUMN -->
      <div class="left-col">
        <!-- ACCOUNTS PAYABLE TABLE -->
        <div class="card">
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div class="card-title">Accounts Payable — Mineral Suppliers</div>
            <div class="card-actions" style="display: flex; align-items: center; gap: 8px;">
              <input type="text" id="dashboardSearch" class="form-control" placeholder="Search..." style="max-width: 180px; padding: 6px 10px; font-size: 12px; margin: 0;">
              <button class="btn-sm">Filter</button>
              <button class="btn-sm">Export CSV</button>
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Supplier</th>
                <th>Lot</th>
                <th>Weight (kg)</th>
                <th>Est. Amount</th>
                <th>Advances</th>
                <th>Balance Due</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="payableList">
              <?php
              $row_idx = 0;
              if (!empty($payable_rows)):
                  foreach ($payable_rows as $row):
                      $row_idx++;
                      $w = (float)$row['total_weight'];
                      $amt = (float)$row['total_amount'];
                      $adv = (float)$row['total_advances'];
                      $bal = (float)$row['balance_due'];
                      
                      // Status pill logic
                      if ($adv == 0) {
                          $status_class = 'pill-red';
                          $status_text = 'Unpaid';
                      } elseif ($bal <= 0) {
                          $status_class = 'pill-green';
                          $status_text = 'Paid';
                      } else {
                          $status_class = 'pill-amber';
                          $status_text = 'Partial';
                      }
              ?>
                <tr>
                  <td><?php echo $row_idx; ?></td>
                  <td class="td-name"><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                  <td><span class="code-badge"><?php echo htmlspecialchars($row['lots_code']); ?></span></td>
                  <td><?php echo $w > 0 ? number_format($w, 1) : '—'; ?></td>
                  <td class="td-bold">$<?php echo number_format($amt, 2); ?></td>
                  <td><?php echo $adv > 0 ? '$' . number_format($adv, 2) : '$0'; ?></td>
                  <td class="td-bold">$<?php echo number_format($bal, 2); ?></td>
                  <td><span class="status-pill <?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                </tr>
              <?php
                  endforeach;
              else:
              ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 24px; color: var(--text2);">No accounts payable records found.</td>
                </tr>
              <?php
              endif;
              ?>
            </tbody>
          </table>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-col">
        <!-- FINANCIAL ALERTS -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Financial Alerts</div>
          </div>
          <div class="alert-list">
            <!-- Total Payable Alert -->
            <?php if ($total_payable_usd > 0): ?>
              <div class="alert-item red">
                <svg class="alert-icon" style="stroke:var(--red)" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <div>
                  <div class="alert-title">Total Payable — $<?php echo number_format($total_payable_usd, 2); ?> outstanding</div>
                  <div class="alert-sub">Estimated unpaid minerals to <?php echo $unpaid_suppliers_count; ?> supplier<?php echo $unpaid_suppliers_count > 1 ? 's' : ''; ?></div>
                </div>
              </div>
            <?php else: ?>
              <div class="alert-item green" style="border-color:var(--green)">
                <svg class="alert-icon" style="stroke:var(--green)" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div>
                  <div class="alert-title" style="color:var(--green)">Total Payable — Fully Paid</div>
                  <div class="alert-sub">No outstanding payables to suppliers</div>
                </div>
              </div>
            <?php endif; ?>

            <!-- RWF Bank Recon Alert -->
            <?php if ($rwf_diff != 0.0): ?>
              <div class="alert-item amber">
                <svg class="alert-icon" style="stroke:var(--amber)" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
                <div>
                  <div class="alert-title">Bank Recon Diff — <?php echo formatDiff($rwf_diff, 'Frw '); ?> RWF Equity</div>
                  <div class="alert-sub">Reconciliation gap in RWF Equity account</div>
                </div>
              </div>
            <?php else: ?>
              <div class="alert-item green" style="border-color:var(--green)">
                <svg class="alert-icon" style="stroke:var(--green)" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div>
                  <div class="alert-title" style="color:var(--green)">RWF Bank Recon — Balanced</div>
                  <div class="alert-sub">RWF Equity reconciliation is balanced</div>
                </div>
              </div>
            <?php endif; ?>

            <!-- USD Bank Recon Alert -->
            <?php if ($usd_diff != 0.0): ?>
              <div class="alert-item amber">
                <svg class="alert-icon" style="stroke:var(--amber)" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
                <div>
                  <div class="alert-title">Bank Recon Diff — <?php echo formatDiff($usd_diff, '$'); ?> USD Equity</div>
                  <div class="alert-sub">Outstanding items in US$ bank reconciliation</div>
                </div>
              </div>
            <?php else: ?>
              <div class="alert-item green" style="border-color:var(--green)">
                <svg class="alert-icon" style="stroke:var(--green)" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div>
                  <div class="alert-title" style="color:var(--green)">USD Bank Recon — Balanced</div>
                  <div class="alert-sub">USD Equity reconciliation is balanced</div>
                </div>
              </div>
            <?php endif; ?>

            <!-- EUR Bank Recon Alert -->
            <?php if ($eur_diff != 0.0): ?>
              <div class="alert-item blue">
                <svg class="alert-icon" style="stroke:var(--blue)" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <div>
                  <div class="alert-title">Euro Recon Diff — <?php echo formatDiff($eur_diff, '€'); ?></div>
                  <div class="alert-sub">Reconciliation variance on Euro Equity</div>
                </div>
              </div>
            <?php else: ?>
              <div class="alert-item green" style="border-color:var(--green)">
                <svg class="alert-icon" style="stroke:var(--green)" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                <div>
                  <div class="alert-title" style="color:var(--green)">EUR Bank Recon — Balanced</div>
                  <div class="alert-sub">EUR Equity reconciliation is balanced</div>
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- MINERAL STOCK SUMMARY -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Mineral Stock Summary</div>
          </div>
          <div class="cat-list">
            <!-- Tin -->
            <div class="cat-item">
              <div class="cat-icon" style="background:#FBF0EB">
                <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Tin (Sn) — Stock Available</div>
                <div class="cat-count">Under processing</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:<?php echo $tin_pct; ?>%"></div></div>
              <div class="cat-val"><?php echo number_format($tin_stock, 1); ?> kg</div>
            </div>
            <!-- Tantalum -->
            <div class="cat-item">
              <div class="cat-icon" style="background:var(--blue-bg)">
                <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Tantalum (Ta) — Stock Available</div>
                <div class="cat-count">Under processing</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:<?php echo $ta_pct; ?>%;background:var(--blue)"></div></div>
              <div class="cat-val"><?php echo number_format($ta_stock, 1); ?> kg</div>
            </div>
            <!-- Total -->
            <div class="cat-item">
              <div class="cat-icon" style="background:var(--green-bg)">
                <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Total Mineral Stock</div>
                <div class="cat-count">Sn + Ta combined</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:100%;background:var(--green)"></div></div>
              <div class="cat-val"><?php echo number_format($total_stock, 1); ?> kg</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../src/js/navbar.js"></script>
<script src="../src/js/sidebar.js"></script>
<script src="../src/js/dashboard.js"></script>
</body>
</html>
