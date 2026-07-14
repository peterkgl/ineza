<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_report_cash_count_rub')) {
    header("Location: ../dashboard");
    exit();
}
$report_slug = 'cash_count_rub';
$report_title = 'INEZA CASH COUNT RUBAYA';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Custom labels mapping is disabled

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

// Fetch exchange rate from database
$rate = 1455.0; // Rubaya default fallback
$rate_query = "
    SELECT er.rate 
    FROM exchange_rates er
    JOIN currencies fc ON er.from_currency_id = fc.id
    JOIN currencies tc ON er.to_currency_id = tc.id
    WHERE (fc.code = 'RWF' AND tc.code = 'USD') OR (fc.code = 'USD' AND tc.code = 'RWF')
      AND er.rate_date <= '" . mysqli_real_escape_string($conn, $target_date) . "'
    ORDER BY er.rate_date DESC, er.id DESC 
    LIMIT 1
";
$rate_res = mysqli_query($conn, $rate_query);
if ($rate_res && mysqli_num_rows($rate_res) > 0) {
    $rate_row = mysqli_fetch_assoc($rate_res);
    $rate = (float)$rate_row['rate'];
} else {
    // Fallback to latest overall rate
    $rate_query = "
        SELECT er.rate 
        FROM exchange_rates er
        JOIN currencies fc ON er.from_currency_id = fc.id
        JOIN currencies tc ON er.to_currency_id = tc.id
        WHERE (fc.code = 'RWF' AND tc.code = 'USD') OR (fc.code = 'USD' AND tc.code = 'RWF')
        ORDER BY er.rate_date DESC, er.id DESC 
        LIMIT 1
    ";
    $rate_res = mysqli_query($conn, $rate_query);
    if ($rate_res && $rate_row = mysqli_fetch_assoc($rate_res)) {
        $rate = (float)$rate_row['rate'];
    }
}
if ($rate < 1.0 && $rate > 0) {
    $rate = 1.0 / $rate;
}

// Fetch denomination counts for both HQ and Rubaya on this date
$counts = [];
$consolidated = [];
$res_cc = mysqli_query($conn, "SELECT report_slug, denomination, currency, quantity FROM cash_counts WHERE count_date = '{$target_date}'");
if ($res_cc) {
    while ($cc_row = mysqli_fetch_assoc($res_cc)) {
        $slug = $cc_row['report_slug'];
        $denom = (int)$cc_row['denomination'];
        $curr = $cc_row['currency'];
        $qty = (int)$cc_row['quantity'];

        if ($slug === $report_slug) {
            $counts[$curr][$denom] = $qty;
        }

        if (!isset($consolidated[$curr][$denom])) {
            $consolidated[$curr][$denom] = 0;
        }
        $consolidated[$curr][$denom] += $qty;
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

      <div class="table-wrapper" style="padding: 16px;">
        <?php
        // Derive denomination lists dynamically from what is recorded in the database
        // (union of keys from both this site and consolidated so the table is always complete)
        $rwf_keys = array_unique(array_merge(
            array_keys($counts['RWF'] ?? []),
            array_keys($consolidated['RWF'] ?? [])
        ));
        $usd_keys = array_unique(array_merge(
            array_keys($counts['USD'] ?? []),
            array_keys($consolidated['USD'] ?? [])
        ));
        rsort($rwf_keys); // highest denomination first
        rsort($usd_keys);
        $rwf_denoms_full = $rwf_keys;
        $usd_denoms_full = $usd_keys;

        // Petty cash totals (Left Side — this site only)
        $total_rwf = 0.0;
        $total_usd = 0.0;
        foreach ($rwf_denoms_full as $d) {
            $total_rwf += (float)($counts['RWF'][$d] ?? 0);
        }
        foreach ($usd_denoms_full as $d) {
            $total_usd += (float)($counts['USD'][$d] ?? 0);
        }
        $total_usd_equiv = $total_usd + ($total_rwf / $rate);

        // Consolidated totals (Right Side — all sites combined)
        $total_con_rwf = 0.0;
        $total_con_usd = 0.0;
        foreach ($rwf_denoms_full as $d) {
            $total_con_rwf += (float)($consolidated['RWF'][$d] ?? 0);
        }
        foreach ($usd_denoms_full as $d) {
            $total_con_usd += (float)($consolidated['USD'][$d] ?? 0);
        }
        $total_con_usd_equiv = $total_con_usd + ($total_con_rwf / $rate);

        $is_empty = ($total_rwf == 0.0 && $total_usd == 0.0);
        ?>
        <?php if ($is_empty): ?>
          <div style="padding: 12px 16px; margin-bottom: 16px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            No cash count recorded for this date.
          </div>
        <?php endif; ?>
        <table class="excel-table" style="min-width: 100%;">
          <thead>
            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="5">PETTY CASH ON HAND</td>
              <td>Daniel MAKASI</td>
              <td colspan="2"></td>
              <td colspan="5">CONSOLIDATED CASH COUNT</td>
            </tr>
            <tr style="font-weight: 600;">
              <td>As of:</td>
              <td><?php echo htmlspecialchars($target_date); ?></td>
              <td></td>
              <td style="text-align: right;">Amount</td>
              <td></td>
              <td>Rate: <?php echo $rate; ?></td>
              <td colspan="2"></td>
              <td>As of:</td>
              <td><?php echo htmlspecialchars($target_date); ?></td>
              <td></td>
              <td style="text-align: right;">Amount</td>
              <td></td>
            </tr>
          </thead>
          <tbody>
            <!-- RWF counts -->
            <tr style="font-weight: 700; background: var(--excel-zebra-bg);">
              <td>In Francs</td>
              <td colspan="4">Left Side Count (RWF)</td>
              <td></td>
              <td colspan="2"></td>
              <td>In Francs</td>
              <td colspan="4">Right Side Count (RWF)</td>
            </tr>
            <?php for ($i = 0; $i < count($rwf_denoms_full); $i++):
                $r_denom = $rwf_denoms_full[$i];
                // Left side: this site's petty cash
                $r_qty = (int)($counts['RWF'][$r_denom] ?? 0);
                $r_val = (float)($r_denom * $r_qty);
                // Right side: consolidated (all sites)
                $rc_qty = (int)($consolidated['RWF'][$r_denom] ?? 0);
                $rc_val = (float)($r_denom * $rc_qty);
                $label = ($r_denom === 1) ? 'MMO' : number_format($r_denom);
            ?>
              <tr>
                <td><?php echo $label; ?></td>
                <td><?php echo $r_denom; ?></td>
                <td style="text-align: right;"><?php echo $r_qty > 0 ? $r_qty : ''; ?></td>
                <td style="text-align: right;"><?php echo $r_val > 0 ? number_format($r_val) : ''; ?></td>
                <td></td>
                <td></td>
                <td colspan="2"></td>
                <td><?php echo $label; ?></td>
                <td><?php echo $r_denom; ?></td>
                <td style="text-align: right;"><?php echo $rc_qty > 0 ? $rc_qty : ''; ?></td>
                <td style="text-align: right;"><?php echo $rc_val > 0 ? number_format($rc_val) : ''; ?></td>
                <td></td>
              </tr>
            <?php endfor; ?>
            
            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="3" style="text-align: right;">Total RWF:</td>
              <td style="text-align: right;"><?php echo number_format($total_rwf); ?></td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
              <td colspan="3" style="text-align: right;">Total RWF:</td>
              <td style="text-align: right;"><?php echo number_format($total_con_rwf); ?></td>
              <td></td>
            </tr>

            <!-- USD counts -->
            <tr style="font-weight: 700; background: var(--excel-zebra-bg); border-top: 2px solid var(--border);">
              <td>In USD$</td>
              <td colspan="4">Left Side Count (USD)</td>
              <td></td>
              <td colspan="2"></td>
              <td>In USD$</td>
              <td colspan="4">Right Side Count (USD)</td>
            </tr>
            <?php for ($i = 0; $i < count($usd_denoms_full); $i++):
                $u_denom = $usd_denoms_full[$i];
                // Left side: this site's petty cash
                $u_qty = (int)($counts['USD'][$u_denom] ?? 0);
                $u_val = (float)($u_denom * $u_qty);
                // Right side: consolidated (all sites)
                $uc_qty = (int)($consolidated['USD'][$u_denom] ?? 0);
                $uc_val = (float)($u_denom * $uc_qty);
            ?>
              <tr>
                <td>$<?php echo $u_denom; ?> Bill</td>
                <td><?php echo $u_denom; ?></td>
                <td style="text-align: right;"><?php echo $u_qty > 0 ? $u_qty : ''; ?></td>
                <td style="text-align: right;"><?php echo $u_val > 0 ? '$' . number_format($u_val) : ''; ?></td>
                <td></td>
                <td></td>
                <td colspan="2"></td>
                <td>$<?php echo $u_denom; ?> Bill</td>
                <td><?php echo $u_denom; ?></td>
                <td style="text-align: right;"><?php echo $uc_qty > 0 ? $uc_qty : ''; ?></td>
                <td style="text-align: right;"><?php echo $uc_val > 0 ? '$' . number_format($uc_val) : ''; ?></td>
                <td></td>
              </tr>
            <?php endfor; ?>

            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="3" style="text-align: right;">Total USD:</td>
              <td style="text-align: right;">$<?php echo number_format($total_usd); ?></td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
              <td colspan="3" style="text-align: right;">Total USD:</td>
              <td style="text-align: right;">$<?php echo number_format($total_con_usd); ?></td>
              <td></td>
            </tr>

            <tr style="font-weight: 700; background: var(--excel-accent-bg); border-top: 2px solid var(--border);">
              <td colspan="3">TOTAL US$ ACTUAL CASH EQUIVALENT:</td>
              <td style="text-align: right;">$<?php echo number_format($total_usd_equiv, 2); ?></td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
              <td colspan="3">TOTAL US$ ACTUAL CASH EQUIVALENT:</td>
              <td style="text-align: right;">$<?php echo number_format($total_con_usd_equiv, 2); ?></td>
              <td></td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
