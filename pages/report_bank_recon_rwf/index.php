<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'bank_recon_rwf';
$report_title = 'Bank Reconciliation — EQUITY RWF - INEZA';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Custom labels mapping is disabled

// Parse filters
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Get target date
$target_date = $filter_date;
if (empty($target_date)) {
    $latest_res = mysqli_query($conn, "SELECT MAX(as_of_date) as max_date FROM bank_statement_balances WHERE report_slug = '{$report_slug_esc}'");
    if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
        $target_date = $latest_row['max_date'] ?? date('Y-m-d');
    } else {
        $target_date = date('Y-m-d');
    }
}

$acct_code = '1010-03'; // RWF account
$curr_symbol = 'RWF ';

// Book balance
$book_query = "SELECT SUM(debit) - SUM(credit) as book_bal 
               FROM journal_entry_lines jel
               JOIN journal_entries je ON jel.journal_entry_id = je.id
               JOIN accounts a ON jel.account_id = a.id
               WHERE a.account_code = '{$acct_code}' AND je.statuss = 'POSTED' AND je.entry_date <= '{$target_date}'";
$book_res = mysqli_query($conn, $book_query);
$book_row = mysqli_fetch_assoc($book_res);
$book_balance = (float)($book_row['book_bal'] ?? 0.0);

// Bank Statement balance
$statement_query = "SELECT balance FROM bank_statement_balances 
                    WHERE report_slug = '{$report_slug_esc}' AND as_of_date = '{$target_date}' LIMIT 1";
$statement_res = mysqli_query($conn, $statement_query);
$statement_row = $statement_res ? mysqli_fetch_assoc($statement_res) : null;
$statement_balance = $statement_row ? (float)$statement_row['balance'] : 0.0;

// Reconciling items
$items_query = "SELECT * FROM bank_recon_items 
                WHERE report_slug = '{$report_slug_esc}' AND as_of_date = '{$target_date}'";
$items_res = mysqli_query($conn, $items_query);

$outstanding_checks = [];
$deposits_in_transit = [];
$unrecorded_payments = [];
if ($items_res) {
    while ($item_row = mysqli_fetch_assoc($items_res)) {
        if ($item_row['item_type'] === 'outstanding_check') {
            $outstanding_checks[] = $item_row;
        } elseif ($item_row['item_type'] === 'deposit_in_transit') {
            $deposits_in_transit[] = $item_row;
        } elseif ($item_row['item_type'] === 'unrecorded_payment') {
            $unrecorded_payments[] = $item_row;
        }
    }
}

$total_transit = 0.0;
foreach ($deposits_in_transit as $t) $total_transit += (float)$t['amount'];
$total_outstanding = 0.0;
foreach ($outstanding_checks as $c) $total_outstanding += (float)$c['amount'];
$total_unrecorded = 0.0;
foreach ($unrecorded_payments as $u) $total_unrecorded += (float)$u['amount'];

$adjusted_bank = $statement_balance + $total_transit - $total_outstanding;
$adjusted_book = $book_balance + $total_unrecorded;
$diff = $adjusted_bank - $adjusted_book;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — <?php echo htmlspecialchars($report_title); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/reports.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
<style>
  .recon-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 14px;
  }
  .recon-section {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
  }
  .recon-section-title {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 14px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
  }
  .recon-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed var(--border);
    font-size: 13px;
  }
  .recon-row:last-child {
    border-bottom: none;
  }
  .recon-row.total {
    font-weight: 700;
    border-top: 1px solid var(--text);
    border-bottom: 2px double var(--text);
    padding-top: 10px;
    font-size: 14px;
  }
  .recon-row.difference {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    padding: 12px;
    border-radius: 8px;
    font-weight: 700;
    color: #b91c1c;
  }
  [data-theme="dark"] .recon-row.difference {
    background: #2d1a1a;
    border-color: #7f1d1d;
    color: #f87171;
  }
</style>
</head>
<body>

<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="main">

  <?php include __DIR__ . '/../include/navbar.php'; ?>

  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <?php echo htmlspecialchars($report_title); ?>
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Bank Reconciliation Statement (RWF Account)</div>
      </div>
      <div class="page-actions">
        <a class="btn-sm btn-primary" href="../include/export.php?sheet=<?php echo urlencode($report_slug); ?>&filter_year=<?php echo urlencode($filter_year); ?>&filter_month=<?php echo urlencode($filter_month); ?>&filter_date=<?php echo urlencode($filter_date); ?>&filter_start=<?php echo urlencode($filter_start); ?>&filter_end=<?php echo urlencode($filter_end); ?>" id="exportBtn">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export to Excel
        </a>
        <button class="btn-sm" onclick="window.print()">
          Print Report
        </button>
      </div>
    </div>

    <!-- Report Container -->
    <div class="report-container page-<?php echo htmlspecialchars($report_slug); ?>">
      <div class="report-card" style="padding: 16px;">
      
      <!-- Date Filters -->
      <form method="GET" class="report-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 12px 16px; background: var(--card-bg, #fff); border-radius: 10px; margin-bottom: 12px; border: 1px solid var(--border, #e5e7eb); box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Reconciliation Date</label>
          <input type="date" name="filter_date" value="<?php echo htmlspecialchars($target_date); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
        </div>
        <div style="display: flex; gap: 6px;">
          <button type="submit" style="padding: 7px 18px; border-radius: 8px; border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(99,102,241,0.3);">
            Apply
          </button>
          <a href="?" style="padding: 7px 14px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); color: var(--text-secondary, #6b7280); font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.2s;">
            Reset
          </a>
        </div>
      </form>

      <div class="table-wrapper" style="padding: 16px;">
        <?php if ($statement_balance == 0.0 && empty($outstanding_checks) && empty($unrecorded_payments) && empty($deposits_in_transit)): ?>
          <div style="padding: 12px 16px; margin-bottom: 16px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            No bank statement balance or reconciliation items recorded for this date.
          </div>
        <?php endif; ?>
        <table class="excel-table" style="min-width: 100%;">
          <thead>
            <tr style="background: var(--excel-blue-bg); color: var(--excel-red-text); font-weight: 700;">
              <td colspan="5" style="border: 1px solid var(--border);">INPUT IN BLUE CELLS ONLY</td>
              <td colspan="2" style="text-align: right; border: 1px solid var(--border);">Difference:</td>
              <td style="text-align: right; font-weight: 700; border: 1px solid var(--border);"><?php echo $curr_symbol . number_format($diff, 2); ?></td>
              <td style="border: 1px solid var(--border);">diff.</td>
            </tr>
            <tr style="font-weight: 700;">
              <td colspan="9" style="font-size: 14px; border: none; padding-top: 15px;">INEZA AFRICAN MINING Ltd</td>
            </tr>
            <tr style="font-weight: 700;">
              <td colspan="9" style="font-size: 16px; border: none;">BANK RECONCILIATON - Equity</td>
            </tr>
            <tr>
              <td colspan="9" style="font-weight: 600; border: none; padding-bottom: 15px;"><?php echo htmlspecialchars($target_date); ?></td>
            </tr>
          </thead>
          <tbody>
            <!-- Statement Balance -->
            <tr style="font-weight: 600; background: var(--bg);">
              <td>EQUITY Balance - As of</td>
              <td></td>
              <td><?php echo htmlspecialchars($target_date); ?></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td style="text-align: right; background: var(--excel-blue-bg); color: var(--excel-blue-text); font-weight: 700;"><?php echo number_format($statement_balance, 2); ?></td>
              <td style="color: var(--text-secondary);">...as per bank statement ending balance</td>
            </tr>

            <!-- Add Header -->
            <tr style="font-weight: 700; background: var(--excel-zebra-bg);">
              <td>Add:</td>
              <td>Unrecorded Bank Payments</td>
              <td></td>
              <td>Date</td>
              <td>Check#</td>
              <td style="text-align: right;">Amount</td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

            <!-- Unrecorded Payments Rows -->
            <?php if (empty($unrecorded_payments)): ?>
              <tr>
                <td></td>
                <td style="color: var(--text-secondary); font-style: italic;">No unrecorded bank payments</td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right;">0.00</td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php else: foreach ($unrecorded_payments as $up): ?>
              <tr>
                <td></td>
                <td><?php echo htmlspecialchars($up['description']); ?></td>
                <td></td>
                <td><?php echo htmlspecialchars($up['reference']); ?></td>
                <td></td>
                <td style="text-align: right;"><?php echo number_format($up['amount'], 2); ?></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php endforeach; endif; ?>

            <!-- Transit Header -->
            <tr style="font-weight: 700; background: var(--excel-zebra-bg);">
              <td></td>
              <td>Deposit in Transit</td>
              <td></td>
              <td>Date</td>
              <td>Check#</td>
              <td style="text-align: right;">Amount</td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

            <!-- Deposits in Transit Rows -->
            <?php if (empty($deposits_in_transit)): ?>
              <tr>
                <td></td>
                <td style="color: var(--text-secondary); font-style: italic;">No deposits in transit</td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right;">0.00</td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php else: foreach ($deposits_in_transit as $dit): ?>
              <tr>
                <td></td>
                <td><?php echo htmlspecialchars($dit['description']); ?></td>
                <td></td>
                <td><?php echo htmlspecialchars($dit['reference']); ?></td>
                <td></td>
                <td style="text-align: right;"><?php echo number_format($dit['amount'], 2); ?></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php endforeach; endif; ?>

            <!-- Total Additions -->
            <tr style="font-weight: 600; background: var(--bg);">
              <td></td>
              <td>Total Additions</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td style="text-align: right; font-weight: 700;"><?php echo number_format($total_transit + $total_unrecorded, 2); ?></td>
              <td></td>
            </tr>

            <!-- Adjusted Bank Balance -->
            <tr style="font-weight: 700; background: var(--excel-header-bg);">
              <td>Adjusted Bank Balance</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td style="text-align: right; font-weight: 700;"><?php echo number_format($adjusted_bank, 2); ?></td>
              <td></td>
            </tr>

            <!-- Deduct Header -->
            <tr style="font-weight: 700; background: var(--excel-zebra-bg);">
              <td>Deduct:</td>
              <td>EQUITY Outstanding Checks</td>
              <td></td>
              <td>Date</td>
              <td>Check#</td>
              <td style="text-align: right;">Amount</td>
              <td></td>
              <td></td>
              <td></td>
            </tr>

            <!-- Outstanding Checks Rows -->
            <?php if (empty($outstanding_checks)): ?>
              <tr>
                <td></td>
                <td style="color: var(--text-secondary); font-style: italic;">No outstanding checks</td>
                <td></td>
                <td></td>
                <td></td>
                <td style="text-align: right;">0.00</td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php else: foreach ($outstanding_checks as $oc): ?>
              <tr>
                <td></td>
                <td><?php echo htmlspecialchars($oc['description']); ?></td>
                <td></td>
                <td><?php echo htmlspecialchars($oc['reference']); ?></td>
                <td></td>
                <td style="text-align: right;"><?php echo number_format($oc['amount'], 2); ?></td>
                <td></td>
                <td></td>
                <td></td>
              </tr>
            <?php endforeach; endif; ?>

            <!-- Total Outstanding -->
            <tr style="font-weight: 600; background: var(--bg);">
              <td></td>
              <td>Total Outstanding Checks</td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td style="text-align: right; font-weight: 700;"><?php echo number_format($total_outstanding, 2); ?></td>
              <td></td>
            </tr>

            <!-- Adjusted Book Balance -->
            <tr style="font-weight: 700; background: var(--excel-accent-bg);">
              <td>UC SAS BOOK Balance - as of</td>
              <td></td>
              <td><?php echo htmlspecialchars($target_date); ?></td>
              <td></td>
              <td></td>
              <td></td>
              <td></td>
              <td style="text-align: right; font-weight: 700;"><?php echo number_format($adjusted_book, 2); ?></td>
              <td style="color: var(--text-secondary);">...as per book balance or Equity excel report</td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  </div>
</div>

</body>
</html>
