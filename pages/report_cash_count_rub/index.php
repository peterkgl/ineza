<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'cash_count_rub';
$report_title = 'INEZA CASH COUNT RUBAYA';
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

// Get latest count date if not specified
$target_date = $filter_date;
if (empty($target_date)) {
    $latest_res = mysqli_query($conn, "SELECT MAX(count_date) as max_date FROM cash_counts WHERE report_slug = '{$report_slug_esc}'");
    if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
        $target_date = $latest_row['max_date'] ?? date('Y-m-d');
    } else {
        $target_date = date('Y-m-d');
    }
}

// Fetch denomination counts
$counts = [];
$res_cc = mysqli_query($conn, "SELECT denomination, currency, quantity FROM cash_counts WHERE report_slug = '{$report_slug_esc}' AND count_date = '{$target_date}'");
if ($res_cc) {
    while ($cc_row = mysqli_fetch_assoc($res_cc)) {
        $counts[$cc_row['currency']][(int)$cc_row['denomination']] = (int)$cc_row['quantity'];
    }
}

$rwf_denoms = [5000, 2000, 1000, 500];
$usd_denoms = [100, 50, 20, 10, 5, 1];
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
  .cash-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 14px;
  }
  @media (max-width: 768px) {
    .cash-grid {
      grid-template-columns: 1fr;
    }
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
        <div class="page-sub">INEZA African Mining Ltd — Rubaya Site Cash Reconciliation Count</div>
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
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Count Date</label>
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

      <div class="cash-grid">
        <!-- RWF Cash Count -->
        <div class="recon-section">
          <div class="recon-section-title">Cash Count (RWF)</div>
          <?php
          $total_rwf = 0;
          foreach ($rwf_denoms as $denom):
              $qty = $counts['RWF'][$denom] ?? 0;
              $subtotal = $denom * $qty;
              $total_rwf += $subtotal;
          ?>
            <div class="recon-row">
              <span><?php echo number_format($denom); ?> RWF Note</span>
              <span><?php echo $qty; ?> x = <strong><?php echo number_format($subtotal); ?> RWF</strong></span>
            </div>
          <?php endforeach; ?>
          
          <!-- MMO -->
          <?php
          $mmo_qty = $counts['RWF'][1] ?? 0;
          $total_rwf += $mmo_qty;
          ?>
          <div class="recon-row">
            <span>Mobile Money Balance (MMO)</span>
            <span><strong><?php echo number_format($mmo_qty); ?> RWF</strong></span>
          </div>

          <div class="recon-row total">
            <span>Total Cash RWF</span>
            <span><?php echo number_format($total_rwf); ?> RWF</span>
          </div>
        </div>

        <!-- USD Cash Count -->
        <div class="recon-section">
          <div class="recon-section-title">Cash Count (USD)</div>
          <?php
          $total_usd = 0;
          foreach ($usd_denoms as $denom):
              $qty = $counts['USD'][$denom] ?? 0;
              $subtotal = $denom * $qty;
              $total_usd += $subtotal;
          ?>
            <div class="recon-row">
              <span>$<?php echo $denom; ?> Bill</span>
              <span><?php echo $qty; ?> x = <strong>$<?php echo number_format($subtotal); ?></strong></span>
            </div>
          <?php endforeach; ?>

          <div class="recon-row total">
            <span>Total Cash USD</span>
            <span>$<?php echo number_format($total_usd); ?></span>
          </div>
        </div>
      </div>

    </div>
  </div>

  </div>
</div>

</body>
</html>
