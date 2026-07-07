<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'bank_recon_usd';
$report_title = 'Bank Reconciliation — $ EQUITY INEZA';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Fetch custom labels map from database
$label_map = [];
$label_query = "SELECT `original_label`, `custom_label` FROM `report_labels` WHERE `report_slug` = '{$report_slug_esc}'";
$label_res = mysqli_query($conn, $label_query);
if ($label_res) {
    while ($label_row = mysqli_fetch_assoc($label_res)) {
        $label_map[$label_row['original_label']] = $label_row['custom_label'];
    }
}

function get_label($val, $label_map) {
    return isset($label_map[$val]) ? $label_map[$val] : $val;
}

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

$acct_code = '1010-01'; // USD account
$curr_symbol = '$';

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
        <div class="page-sub">INEZA African Mining Ltd — Bank Reconciliation Statement (USD Account)</div>
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

      <div class="recon-container">
        <!-- Bank reconciliation statement -->
        <div class="recon-section">
          <div class="recon-section-title">Reconciliation of Bank Statement Balance</div>
          
          <div class="recon-row">
            <span>Balance per Bank Statement (as of <?php echo htmlspecialchars($target_date); ?>)</span>
            <span><strong><?php echo $curr_symbol . number_format($statement_balance, 2); ?></strong></span>
          </div>
          
          <div class="recon-row" style="color: var(--orange); font-weight: 600; margin-top: 10px;">
            <span>Add: Deposits in Transit</span>
            <span></span>
          </div>
          <?php foreach ($deposits_in_transit as $dit): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary);">
              <span><?php echo htmlspecialchars($dit['description']); ?> (<?php echo htmlspecialchars($dit['reference']); ?>)</span>
              <span><?php echo $curr_symbol . number_format($dit['amount'], 2); ?></span>
            </div>
          <?php endforeach; if (empty($deposits_in_transit)): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary); font-style: italic;">
              <span>No deposits in transit</span>
              <span><?php echo $curr_symbol; ?>0.00</span>
            </div>
          <?php endif; ?>
          
          <div class="recon-row" style="color: var(--orange); font-weight: 600; margin-top: 10px;">
            <span>Deduct: Outstanding Checks</span>
            <span></span>
          </div>
          <?php foreach ($outstanding_checks as $oc): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary);">
              <span><?php echo htmlspecialchars($oc['description']); ?> (<?php echo htmlspecialchars($oc['reference']); ?>)</span>
              <span>(<?php echo $curr_symbol . number_format($oc['amount'], 2); ?>)</span>
            </div>
          <?php endforeach; if (empty($outstanding_checks)): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary); font-style: italic;">
              <span>No outstanding checks</span>
              <span><?php echo $curr_symbol; ?>0.00</span>
            </div>
          <?php endif; ?>

          <div class="recon-row total">
            <span>Adjusted Bank Balance</span>
            <span><?php echo $curr_symbol . number_format($adjusted_bank, 2); ?></span>
          </div>
        </div>

        <!-- Book balance reconciliation -->
        <div class="recon-section">
          <div class="recon-section-title">Reconciliation of General Ledger (Book) Balance</div>
          
          <div class="recon-row">
            <span>Balance per General Ledger (as of <?php echo htmlspecialchars($target_date); ?>)</span>
            <span><strong><?php echo $curr_symbol . number_format($book_balance, 2); ?></strong></span>
          </div>

          <div class="recon-row" style="color: var(--orange); font-weight: 600; margin-top: 10px;">
            <span>Add/Deduct: Unrecorded Bank Transactions</span>
            <span></span>
          </div>
          <?php foreach ($unrecorded_payments as $up): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary);">
              <span><?php echo htmlspecialchars($up['description']); ?> (<?php echo htmlspecialchars($up['reference']); ?>)</span>
              <span><?php echo $curr_symbol . number_format($up['amount'], 2); ?></span>
            </div>
          <?php endforeach; if (empty($unrecorded_payments)): ?>
            <div class="recon-row" style="padding-left: 20px; color: var(--text-secondary); font-style: italic;">
              <span>No unrecorded bank transactions</span>
              <span><?php echo $curr_symbol; ?>0.00</span>
            </div>
          <?php endif; ?>

          <div class="recon-row total">
            <span>Adjusted Book Balance</span>
            <span><?php echo $curr_symbol . number_format($adjusted_book, 2); ?></span>
          </div>
        </div>

        <!-- Reconciliation difference -->
        <div class="recon-row difference">
          <span>Reconciliation Difference (Bank vs. Book)</span>
          <span><?php echo $curr_symbol . number_format($diff, 2); ?></span>
        </div>
      </div>

    </div>
  </div>

  </div>
</div>

</body>
</html>
