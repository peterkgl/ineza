<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_report_cash_count_hq')) {
    header("Location: ../dashboard");
    exit();
}
$report_slug = 'cash_count_hq';
$report_title = 'INEZA Cash Count HQ';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

$filter_year  = $_GET['filter_year']  ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date  = $_GET['filter_date']  ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end   = $_GET['filter_end']   ?? '';

$target_date = $filter_date;
if (empty($target_date)) {
    $latest_res = mysqli_query($conn,
        "SELECT MAX(count_date) AS max_date
         FROM cash_counts
         WHERE report_slug = '{$report_slug_esc}'"
    );
    if ($latest_res && $latest_row = mysqli_fetch_assoc($latest_res)) {
        $target_date = $latest_row['max_date'] ?? date('Y-m-d');
    } else {
        $target_date = date('Y-m-d');
    }
}
$target_date_esc = mysqli_real_escape_string($conn, $target_date);

$currencies_meta = [];
$curr_res = mysqli_query($conn,
    "SELECT id, code, name, symbol, is_base_currency
     FROM currencies
     WHERE is_active = 1
     ORDER BY code ASC"
);
if ($curr_res) {
    while ($curr_row = mysqli_fetch_assoc($curr_res)) {
        $currencies_meta[$curr_row['code']] = [
            'id'      => (int)$curr_row['id'],
            'name'    => $curr_row['name'],
            'symbol'  => $curr_row['symbol'] ?? $curr_row['code'],
            'is_base' => (int)$curr_row['is_base_currency'],
        ];
    }
}

$rate = null;

$rate_sql_base = "
    SELECT er.rate,
           fc.code AS from_code,
           tc.code AS to_code
    FROM exchange_rates er
    JOIN currencies fc ON er.from_currency_id = fc.id
    JOIN currencies tc ON er.to_currency_id   = tc.id
    WHERE (
        (fc.code = 'RWF' AND tc.code = 'USD')
        OR
        (fc.code = 'USD' AND tc.code = 'RWF')
    )
";

$rate_res = mysqli_query($conn,
    $rate_sql_base .
    "AND er.rate_date <= '{$target_date_esc}'
     ORDER BY er.rate_date DESC, er.id DESC
     LIMIT 1"
);
if ($rate_res && $rate_row = mysqli_fetch_assoc($rate_res)) {
    $raw = (float)$rate_row['rate'];
    if ($rate_row['from_code'] === 'USD' && $rate_row['to_code'] === 'RWF') {
        $rate = ($raw > 0) ? 1.0 / $raw : null;
    } else {
        $rate = ($raw > 1.0) ? $raw : (($raw > 0 && $raw < 1.0) ? 1.0 / $raw : null);
    }
}

if ($rate === null) {
    $rate_res = mysqli_query($conn,
        $rate_sql_base .
        "ORDER BY er.rate_date DESC, er.id DESC
         LIMIT 1"
    );
    if ($rate_res && $rate_row = mysqli_fetch_assoc($rate_res)) {
        $raw = (float)$rate_row['rate'];
        if ($rate_row['from_code'] === 'USD' && $rate_row['to_code'] === 'RWF') {
            $rate = ($raw > 0) ? 1.0 / $raw : null;
        } else {
            $rate = ($raw > 1.0) ? $raw : (($raw > 0 && $raw < 1.0) ? 1.0 / $raw : null);
        }
    }
}

$counts      = [];
$consolidated = [];

$res_cc = mysqli_query($conn,
    "SELECT report_slug, denomination, currency, quantity
     FROM cash_counts
     WHERE count_date = '{$target_date_esc}'"
);
if ($res_cc) {
    while ($cc_row = mysqli_fetch_assoc($res_cc)) {
        $slug  = $cc_row['report_slug'];
        $denom = (int)$cc_row['denomination'];
        $curr  = $cc_row['currency'];
        $qty   = (int)$cc_row['quantity'];

        if ($slug === $report_slug) {
            $counts[$curr][$denom] = $qty;
        }

        if (!isset($consolidated[$curr][$denom])) {
            $consolidated[$curr][$denom] = 0;
        }
        $consolidated[$curr][$denom] += $qty;
    }
}

$denom_lists = [];

$all_currencies_in_data = array_unique(
    array_merge(array_keys($counts), array_keys($consolidated))
);
foreach ($all_currencies_in_data as $curr_code) {
    $keys = array_unique(array_merge(
        array_keys($counts[$curr_code]       ?? []),
        array_keys($consolidated[$curr_code] ?? [])
    ));
    rsort($keys);
    $denom_lists[$curr_code] = $keys;
}

$site_totals = [];
$con_totals  = [];

foreach ($denom_lists as $curr_code => $denoms) {
    $site_totals[$curr_code] = 0.0;
    $con_totals[$curr_code]  = 0.0;
    foreach ($denoms as $d) {
        $site_totals[$curr_code] += (float)$d * (float)($counts[$curr_code][$d]       ?? 0);
        $con_totals[$curr_code]  += (float)$d * (float)($consolidated[$curr_code][$d] ?? 0);
    }
}

$site_rwf = $site_totals['RWF'] ?? 0.0;
$site_usd = $site_totals['USD'] ?? 0.0;
$con_rwf  = $con_totals['RWF']  ?? 0.0;
$con_usd  = $con_totals['USD']  ?? 0.0;

$site_usd_equiv = ($rate !== null) ? $site_usd + ($site_rwf / $rate) : null;
$con_usd_equiv  = ($rate !== null) ? $con_usd  + ($con_rwf  / $rate) : null;

$is_empty = (array_sum($site_totals) == 0.0 && array_sum($con_totals) == 0.0);
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

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <?php echo htmlspecialchars($report_title); ?>
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — HQ Cash Reconciliation Count</div>
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

    <div class="report-container page-<?php echo htmlspecialchars($report_slug); ?>">
      <div class="report-card" style="padding: 16px;">

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

      <?php if ($rate === null): ?>
        <div style="padding: 10px 16px; margin-bottom: 12px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
          <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; flex-shrink: 0; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
          No exchange rate found in the database. USD-equivalent totals cannot be calculated. Please add an exchange rate in the Currencies section.
        </div>
      <?php endif; ?>

      <div class="table-wrapper" style="padding: 16px;">
        <?php if ($is_empty): ?>
          <div style="padding: 12px 16px; margin-bottom: 16px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            No cash count recorded for this date.
          </div>
        <?php endif; ?>

        <?php if (!$is_empty): ?>
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
              <td>
                Rate:
                <?php if ($rate !== null): ?>
                  <?php echo number_format($rate, 2); ?>
                <?php else: ?>
                  <span style="color: var(--amber); font-size: 11px;">N/A</span>
                <?php endif; ?>
              </td>
              <td colspan="2"></td>
              <td>As of:</td>
              <td><?php echo htmlspecialchars($target_date); ?></td>
              <td></td>
              <td style="text-align: right;">Amount</td>
              <td></td>
            </tr>
          </thead>
          <tbody>

            <?php
            $display_order = ['RWF', 'USD'];
            $other_currencies = array_diff(array_keys($denom_lists), $display_order);
            $ordered_currencies = array_merge(
                array_intersect($display_order, array_keys($denom_lists)),
                $other_currencies
            );

            foreach ($ordered_currencies as $curr_code):
                $denoms         = $denom_lists[$curr_code];
                $symbol         = $currencies_meta[$curr_code]['symbol'] ?? $curr_code;
                $is_rwf         = ($curr_code === 'RWF');
                $site_total_val = $site_totals[$curr_code] ?? 0.0;
                $con_total_val  = $con_totals[$curr_code]  ?? 0.0;

                $section_label = $is_rwf
                    ? 'In Francs'
                    : (($curr_code === 'USD') ? 'In USD$' : 'In ' . htmlspecialchars($curr_code));
                $left_label  = ($curr_code === 'RWF') ? 'Left Side Count (RWF)'  : "Left Side Count ({$curr_code})";
                $right_label = ($curr_code === 'RWF') ? 'Right Side Count (RWF)' : "Right Side Count ({$curr_code})";
                $total_label = 'Total ' . htmlspecialchars($curr_code) . ':';
            ?>

            <tr style="font-weight: 700; background: var(--excel-zebra-bg);<?php echo ($curr_code !== 'RWF') ? ' border-top: 2px solid var(--border);' : ''; ?>">
              <td><?php echo $section_label; ?></td>
              <td colspan="4"><?php echo $left_label; ?></td>
              <td></td>
              <td colspan="2"></td>
              <td><?php echo $section_label; ?></td>
              <td colspan="4"><?php echo $right_label; ?></td>
            </tr>

            <?php foreach ($denoms as $denom):
                $site_qty = (int)($counts[$curr_code][$denom] ?? 0);
                $site_val = (float)$denom * $site_qty;

                $con_qty = (int)($consolidated[$curr_code][$denom] ?? 0);
                $con_val = (float)$denom * $con_qty;

                if ($is_rwf) {
                    $denom_label = ($denom === 1) ? 'MMO' : number_format($denom);
                } else {
                    $denom_label = $symbol . number_format($denom) . ' Bill';
                }
            ?>
              <tr>
                <td><?php echo $denom_label; ?></td>
                <td><?php echo number_format($denom); ?></td>
                <td style="text-align: right;"><?php echo $site_qty > 0 ? number_format($site_qty) : ''; ?></td>
                <td style="text-align: right;"><?php echo $site_val > 0 ? ($is_rwf ? number_format($site_val) : $symbol . number_format($site_val)) : ''; ?></td>
                <td></td>
                <td></td>
                <td colspan="2"></td>
                <td><?php echo $denom_label; ?></td>
                <td><?php echo number_format($denom); ?></td>
                <td style="text-align: right;"><?php echo $con_qty > 0 ? number_format($con_qty) : ''; ?></td>
                <td style="text-align: right;"><?php echo $con_val > 0 ? ($is_rwf ? number_format($con_val) : $symbol . number_format($con_val)) : ''; ?></td>
                <td></td>
              </tr>
            <?php endforeach; ?>

            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="3" style="text-align: right;"><?php echo $total_label; ?></td>
              <td style="text-align: right;">
                <?php echo $is_rwf ? number_format($site_total_val) : $symbol . number_format($site_total_val); ?>
              </td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
              <td colspan="3" style="text-align: right;"><?php echo $total_label; ?></td>
              <td style="text-align: right;">
                <?php echo $is_rwf ? number_format($con_total_val) : $symbol . number_format($con_total_val); ?>
              </td>
              <td></td>
            </tr>

            <?php endforeach; ?>

            <tr style="font-weight: 700; background: var(--excel-accent-bg); border-top: 2px solid var(--border);">
              <td colspan="3">TOTAL US$ ACTUAL CASH EQUIVALENT:</td>
              <td style="text-align: right;">
                <?php if ($site_usd_equiv !== null): ?>
                  $<?php echo number_format($site_usd_equiv, 2); ?>
                <?php else: ?>
                  <span style="color: var(--amber); font-size: 11px;">N/A — no rate</span>
                <?php endif; ?>
              </td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
              <td colspan="3">TOTAL US$ ACTUAL CASH EQUIVALENT:</td>
              <td style="text-align: right;">
                <?php if ($con_usd_equiv !== null): ?>
                  $<?php echo number_format($con_usd_equiv, 2); ?>
                <?php else: ?>
                  <span style="color: var(--amber); font-size: 11px;">N/A — no rate</span>
                <?php endif; ?>
              </td>
              <td></td>
            </tr>

          </tbody>
        </table>
        <?php endif; ?>
      </div>

    </div>
  </div>

  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
