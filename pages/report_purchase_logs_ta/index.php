<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'purchase_logs_ta';
$report_title = 'Tantalum Purchase Logs';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Custom labels mapping is disabled

// Parse filters
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Date condition
$date_col = "purchase_date";
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
$query = "SELECT p.*, s.name as supplier_name, l.lots_code, peg.grade_pct 
          FROM purchasing p
          JOIN suppliers s ON p.supplier_id = s.id
          JOIN lots l ON p.lot_id = l.id
          LEFT JOIN purchasing_element_grade peg ON peg.purchasing_id = p.id AND peg.product_element_id = 1
          WHERE p.product_id = 2 {$date_cond}
          ORDER BY p.purchase_date ASC, p.id ASC";
          
$db_res = mysqli_query($conn, $query);
$total_qty = 0.0;
$total_val_rwf = 0.0;
$total_val_usd = 0.0;
$total_grade_weight = 0.0;
$rows = [];
if ($db_res) {
    while ($db_row = mysqli_fetch_assoc($db_res)) {
        $qty = (float)$db_row['quantity_kg'];
        $grade = (float)($db_row['grade_pct'] ?? 0);
        $val_rwf = (float)$db_row['purchase_value_rwf'];
        $val_usd = (float)$db_row['purchase_value_usd'];
        
        $total_qty += $qty;
        $total_val_rwf += $val_rwf;
        $total_val_usd += $val_usd;
        $total_grade_weight += $grade * $qty;
        
        $rows[] = $db_row;
    }
}
$avg_price = $total_qty > 0 ? $total_val_rwf / $total_qty : 0.0;
$avg_grade = $total_qty > 0 ? $total_grade_weight / $total_qty : 0.0;
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
  .metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
  }
  .metric-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }
  .metric-title {
    font-size: 11px;
    text-transform: uppercase;
    color: var(--text-secondary, #6b7280);
    letter-spacing: 0.5px;
    margin-bottom: 6px;
    font-weight: 600;
  }
  .metric-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
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
        <div class="page-sub">INEZA African Mining Ltd — Tantalite Mineral Purchases Logs</div>
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
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-title">Total Weight</div>
            <div class="metric-value"><?php echo number_format($total_qty, 1); ?> kg</div>
          </div>
          <div class="metric-card">
            <div class="metric-title">Wtd. Average Price</div>
            <div class="metric-value">RWF <?php echo number_format($avg_price, 0); ?></div>
          </div>
          <div class="metric-card">
            <div class="metric-title">Average Grade (Ta2O5)</div>
            <div class="metric-value"><?php echo number_format($avg_grade, 4); ?>%</div>
          </div>
        </div>

        <table class="excel-table" id="reportTable">
          <thead>
            <tr style="background: var(--bg); font-weight: 600;">
              <th>No</th>
              <th>Delivery Date</th>
              <th>D/Down</th>
              <th>To HO</th>
              <th>Lot #</th>
              <th>Negociant</th>
              <th>Inventory Code</th>
              <th>Cfr/Commission</th>
              <th>Quantity (Kg)</th>
              <th>Ta205%</th>
              <th>Price (Rwf$)</th>
              <th>Purchase Value (Rwf)</th>
              <th>Rate</th>
              <th>Value ($)</th>
              <th>Supplier ($)</th>
              <th>Charges/Kg</th>
              <th>Price /Ta</th>
              <th>$ Price/ Kg</th>
              <th>Ta205%</th>
              <th>Ta %</th>
              <th>Nb205%</th>
              <th>Fe%</th>
              <th>Bal %</th>
              <th>Balance (Kg)</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $idx = 0;
            $cum_qty = 0;
            if (empty($rows)):
            ?>
              <tr>
                <td colspan="24" style="text-align: center; padding: 24px; color: var(--text2);">No purchase log records found.</td>
              </tr>
            <?php
            else:
            foreach ($rows as $row): 
                $idx++;
                $qty = (float)$row['quantity_kg'];
                $grade = (float)($row['grade_pct'] ?? 0);
                $cum_qty += $qty;
                
                // Calculations matching sheet formulas but formatted as values
                $ta_pct = $grade * 0.819;
                $nb_pct = $grade * 0.204; // Nb2O5 multiplier
                $fe_pct = $grade * 0.054; // Fe multiplier
                $bal_pct = $grade * 0.458; // MnO2/Balance multiplier
            ?>
              <tr class="data-row">
                <td><?php echo $idx; ?></td>
                <td><?php echo htmlspecialchars($row['delivery_date'] ?? ''); ?></td>
                <td></td>
                <td><?php echo htmlspecialchars($row['purchase_date']); ?></td>
                <td><?php echo htmlspecialchars($row['lots_code']); ?></td>
                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($row['inventory_code'] ?? ''); ?></td>
                <td></td>
                <td style="text-align: right;"><?php echo number_format($qty, 1); ?></td>
                <td style="text-align: right;"><?php echo number_format($grade * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($row['price_per_kg_rwf'], 0); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['purchase_value_rwf'], 0); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['exchange_rate'], 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format($row['purchase_value_usd'], 2); ?></td>
                <td style="text-align: right;">$<?php echo number_format($row['net_paid_supplier_usd'], 2); ?></td>
                <td style="text-align: right;"><?php echo number_format($row['charges_per_kg'], 2); ?></td>
                <td style="text-align: right;"><?php echo $row['price_per_ta_unit'] > 0 ? number_format($row['price_per_ta_unit'], 2) : ''; ?></td>
                <td style="text-align: right;"><?php echo $row['price_per_kg_usd'] > 0 ? number_format($row['price_per_kg_usd'], 2) : ''; ?></td>
                <td style="text-align: right;"><?php echo number_format($grade * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($ta_pct * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($nb_pct * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($fe_pct * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($bal_pct * 100, 2); ?>%</td>
                <td style="text-align: right;"><?php echo number_format($cum_qty, 1); ?></td>
              </tr>
            <?php endforeach; endif; ?>
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
