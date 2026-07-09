<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'petty_cash_rub';
$report_title = 'PC INEZA RUB';
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

// Fetch database records
$query = "SELECT je.entry_date as tx_date, je.description as je_desc,
            jel.debit as deposit, jel.credit as withdrawal, jel.description as details,
            offset_a.account_name as offsetting_account_name
          FROM journal_entry_lines jel
          JOIN journal_entries je ON jel.journal_entry_id = je.id
          JOIN accounts a ON jel.account_id = a.id
          LEFT JOIN journal_entry_lines offset_jel ON offset_jel.journal_entry_id = je.id AND offset_jel.id != jel.id
          LEFT JOIN accounts offset_a ON offset_jel.account_id = offset_a.id
          WHERE a.account_code = '1010-04' AND je.statuss = 'POSTED' {$date_cond}
          ORDER BY je.entry_date ASC, je.id ASC";
          
$db_res = mysqli_query($conn, $query);
$balance = 0.0;
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
        <div class="page-sub">INEZA African Mining Ltd — Rubaya Site Petty Cash Ledger</div>
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
        <table class="excel-table" id="reportTable">
          <thead>
            <tr style="background: var(--bg); font-weight: 600;">
              <th>Date</th>
              <th>Day</th>
              <th>USD</th>
              <th>USD</th>
              <th>Paid To Whom/ From</th>
              <th>Location</th>
              <th>Remarks</th>
              <th>Bon de Sortie</th>
              <th>Receipt#</th>
              <th>Use of Funds</th>
              <th>Supporting Docs Ref#</th>
              <th>JV Ref.</th>
              <th>Account for FS</th>
              <th>For Cash Flow</th>
              <th>Account</th>
              <th>Physical</th>
              <th>Ledger Bal</th>
              <th>Diff.</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $has_data = false;
            if ($db_res && mysqli_num_rows($db_res) > 0):
                $has_data = true;
                while ($row = mysqli_fetch_assoc($db_res)):
                    $deposit = (float)$row['deposit'];
                    $withdrawal = (float)$row['withdrawal'];
                    $cash_val = $deposit > 0 ? $deposit : -$withdrawal;
                    $balance = round($balance + $cash_val, 2);
                    $who = $row['je_desc'];
                    if (preg_match('/To\/From:\s*(.*?)\s*\|\s*Details:/i', $row['je_desc'], $matches)) {
                        $who = $matches[1];
                    }
            ?>
              <tr class="data-row">
                <td><?php echo htmlspecialchars($row['tx_date']); ?></td>
                <td><?php echo date('l', strtotime($row['tx_date'])); ?></td>
                <td style="text-align: right;"><?php echo $withdrawal > 0 ? '$' . number_format($withdrawal, 2) : ''; ?></td>
                <td style="text-align: right;"><?php echo $deposit > 0 ? '$' . number_format($deposit, 2) : ''; ?></td>
                <td><?php echo htmlspecialchars($who); ?></td>
                <td>Rubaya</td>
                <td><?php echo htmlspecialchars($row['details']); ?></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td><?php echo htmlspecialchars($row['offsetting_account_name'] ?? ''); ?></td>
                <td></td>
                <td style="text-align: right; font-weight: 600;"><?php echo '$' . number_format($balance, 2); ?></td>
                <td>0</td>
              </tr>
            <?php 
                endwhile;
            else:
            ?>
              <tr>
                <td colspan="18" style="text-align: center; padding: 24px; color: var(--text2);">No petty cash transactions found for the selected period.</td>
              </tr>
            <?php
            endif;
            ?>
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
