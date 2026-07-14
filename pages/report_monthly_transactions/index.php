<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_report_monthly_transactions')) {
    header("Location: ../dashboard");
    exit();
}
$report_slug = 'monthly_transactions';
$report_title = 'Monthly Transactions';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Custom labels mapping is disabled

// Parse filters
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Date condition
$date_col = "entry_date";
$date_cond = "";
if (!empty($filter_date)) {
    $date_cond = " AND DATE({$date_col}) = '" . mysqli_real_escape_string($conn, $filter_date) . "' ";
} elseif (!empty($filter_start) && !empty($filter_end)) {
    $date_cond = " AND {$date_col} BETWEEN '" . mysqli_real_escape_string($conn, $filter_start) . "' AND '" . mysqli_real_escape_string($conn, $filter_end) . "' ";
} elseif (!empty($filter_year) && !empty($filter_month)) {
    $date_cond = " AND YEAR({$date_col}) = '" . mysqli_real_escape_string($conn, $filter_year) . "' AND MONTH({$date_col}) = '" . mysqli_real_escape_string($conn, $filter_month) . "' ";
} elseif (!empty($filter_year)) {
    $date_cond = " AND YEAR({$date_col}) = '" . mysqli_real_escape_string($conn, $filter_year) . "' ";
} elseif (!empty($filter_month)) {
    $date_cond = " AND MONTH({$date_col}) = '" . mysqli_real_escape_string($conn, $filter_month) . "' ";
}

// Fetch distinct months dynamically
$month_cols = [];
$months_res = mysqli_query($conn, "SELECT DISTINCT DATE_FORMAT(je.entry_date, '%Y-%m') as tx_month FROM journal_entries je WHERE je.statuss = 'POSTED' {$date_cond} ORDER BY tx_month ASC");
if ($months_res) {
    while ($m_row = mysqli_fetch_assoc($months_res)) {
        $month_cols[] = $m_row['tx_month'];
    }
}
if (empty($month_cols)) {
    $month_cols[] = date('Y-m');
}

// Fetch distinct accounts with posted transactions dynamically
$accounts = [];
$acc_res = mysqli_query($conn, "SELECT DISTINCT a.id, a.account_code, a.account_name, a.is_active FROM accounts a JOIN journal_entry_lines jel ON a.id = jel.account_id JOIN journal_entries je ON jel.journal_entry_id = je.id WHERE je.statuss = 'POSTED' {$date_cond} ORDER BY a.account_code ASC");
if ($acc_res) {
    while ($a_row = mysqli_fetch_assoc($acc_res)) {
        $accounts[] = $a_row;
    }
}

// Build matrix
$matrix = [];
foreach ($accounts as $acc) {
    $matrix[$acc['id']] = [
        'active' => $acc['is_active'] ? 'Y' : 'N',
        'name' => $acc['account_name'],
        'code' => $acc['account_code'],
        'months' => array_fill_keys($month_cols, 0.0),
        'total' => 0.0
    ];
}

// Fetch balances group by month and account
$bal_query = "SELECT DATE_FORMAT(je.entry_date, '%Y-%m') as tx_month,
                     a.id as account_id,
                     SUM(jel.debit) as total_debit, SUM(jel.credit) as total_credit
              FROM journal_entry_lines jel
              JOIN journal_entries je ON jel.journal_entry_id = je.id
              JOIN accounts a ON jel.account_id = a.id
              WHERE je.statuss = 'POSTED' {$date_cond}
              GROUP BY tx_month, a.id";
$bal_res = mysqli_query($conn, $bal_query);
if ($bal_res) {
    while ($b_row = mysqli_fetch_assoc($bal_res)) {
        $acc_id = $b_row['account_id'];
        $m = $b_row['tx_month'];
        $net = (float)$b_row['total_debit'] - (float)$b_row['total_credit'];
        if (isset($matrix[$acc_id])) {
            $matrix[$acc_id]['months'][$m] = $net;
        }
    }
}

// Calculate totals
$column_totals = array_fill_keys($month_cols, 0.0);
$grand_total = 0.0;
foreach ($matrix as $acc_id => &$row_data) {
    $row_total = 0.0;
    foreach ($row_data['months'] as $m => $val) {
        $row_total += $val;
        $column_totals[$m] += $val;
    }
    $row_data['total'] = $row_total;
    $grand_total += $row_total;
}
unset($row_data);
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
        <div class="page-sub">INEZA African Mining Ltd — Monthly Trial Balance Transactions</div>
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
      <div class="report-card">
      <div class="report-toolbar">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="reportSearch" placeholder="Search values in table..." onkeyup="searchTable()">
        </div>
        <div class="toolbar-actions">
          <label class="toggle-label">
            <input type="checkbox" id="hideEmptyRows" onchange="toggleEmptyRows(this)">
            <span>Hide Empty Rows</span>
          </label>
        </div>
      </div>

      <!-- Date Filters -->
      <form method="GET" class="report-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 12px 16px; background: var(--card-bg, #fff); border-radius: 10px; margin-bottom: 12px; border: 1px solid var(--border, #e5e7eb); box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Year</label>
          <select name="filter_year" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; min-width: 90px; color: var(--text, #111);">
            <option value="">All</option>
            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
              <option value="<?php echo $y; ?>" <?php echo ($filter_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Month</label>
          <select name="filter_month" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; min-width: 120px; color: var(--text, #111);">
            <option value="">All</option>
            <?php
            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            for ($m = 1; $m <= 12; $m++):
            ?>
              <option value="<?php echo $m; ?>" <?php echo ($filter_month == $m) ? 'selected' : ''; ?>><?php echo $months[$m-1]; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Specific Date</label>
          <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Date Range</label>
          <div style="display: flex; gap: 6px; align-items: center;">
            <input type="date" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
            <span style="font-size: 12px; color: var(--text-secondary, #9ca3af);">to</span>
            <input type="date" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
          </div>
        </div>
        <div style="display: flex; gap: 6px;">
          <button type="submit" style="padding: 7px 18px; border-radius: 8px; border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(99,102,241,0.3);">
            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; vertical-align: -2px; margin-right: 4px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
          </button>
          <a href="?" style="padding: 7px 14px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); color: var(--text-secondary, #6b7280); font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.2s;">
            Clear
          </a>
        </div>
      </form>

      <div class="table-wrapper" style="padding: 16px;">
        <?php
        // Dynamic account and transaction data are prepared at the top of the page.
        ?>
        <?php if ($grand_total == 0.0): ?>
          <div style="padding: 12px 16px; margin: 12px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            No posted journal entries found for the selected dates. All account balances are shown as zero.
          </div>
        <?php endif; ?>
        <table class="excel-table" id="reportTable">
          <thead>
            <tr style="background: var(--bg); font-weight: 600;">
              <th>Y/N</th>
              <th>Account Name</th>
              <?php foreach ($month_cols as $m): ?>
                <th><?php echo $m . '-01'; ?></th>
              <?php endforeach; ?>
              <th>TOTAL</th>
              <th>GRAND TOTAL</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matrix as $rowId => $row_data): ?>
              <tr class="data-row">
                <td style="text-align: center;"><?php echo htmlspecialchars($row_data['active']); ?></td>
                <td><?php echo htmlspecialchars($row_data['name']); ?></td>
                <?php foreach ($month_cols as $m): ?>
                  <td style="text-align: right;"><?php echo $row_data['months'][$m] != 0 ? '$' . number_format($row_data['months'][$m], 2) : '0'; ?></td>
                <?php endforeach; ?>
                <td style="text-align: right; font-weight: 600;"><?php echo '$' . number_format($row_data['total'], 2); ?></td>
                <td style="text-align: right; font-weight: 600;"><?php echo '$' . number_format($row_data['total'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="2" style="text-align: right;">Total:</td>
              <?php foreach ($month_cols as $m): ?>
                <td style="text-align: right;"><?php echo '$' . number_format($column_totals[$m], 2); ?></td>
              <?php endforeach; ?>
              <td style="text-align: right;"><?php echo '$' . number_format($grand_total, 2); ?></td>
              <td style="text-align: right;"><?php echo '$' . number_format($grand_total, 2); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  </div>
</div>

<script>
  function searchTable() {
    var input = document.getElementById("reportSearch");
    if (!input) return;
    var filter = input.value.toLowerCase();
    var table = document.getElementById("reportTable");
    if (!table) return;
    var trs = table.getElementsByClassName("data-row");
    
    for (var i = 0; i < trs.length; i++) {
      var tr = trs[i];
      var tds = tr.getElementsByTagName("td");
      var rowMatches = false;
      
      for (var j = 0; j < tds.length; j++) {
        var td = tds[j];
        var text = td.textContent || td.innerText;
        
        td.innerHTML = text.replace(/<span class="highlight">(.*?)<\/span>/gi, '$1');
        
        if (filter !== "" && text.toLowerCase().indexOf(filter) > -1) {
          rowMatches = true;
          var regex = new RegExp("(" + escapeRegExp(filter) + ")", "gi");
          td.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
        }
      }
      
      if (filter === "") {
        tr.style.display = "";
      } else {
        if (rowMatches) {
          tr.style.display = "";
        } else {
          tr.style.display = "none";
        }
      }
    }
  }

  function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function toggleEmptyRows(checkbox) {
    var table = document.getElementById("reportTable");
    if (!table) return;
    var trs = table.getElementsByClassName("empty-row");
    for (var i = 0; i < trs.length; i++) {
      trs[i].style.display = checkbox.checked ? "none" : "";
    }
    searchTable();
  }
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
