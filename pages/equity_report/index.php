<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Security access check using standard view_accounts permission
if (!hasPermission($conn, $userId, 'view_accounts')) {
    header("Location: ../dashboard");
    exit();
}

// ────────────────────────────────────────────────────────────────────────────
// Database Equity Calculation Functions
// ────────────────────────────────────────────────────────────────────────────

function getAccountTypeBalance($conn, $accountTypeId, $asOfDate, $balanceType = 'debit') {
    $accountTypeId = (int)$accountTypeId;
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);

    $stmt = mysqli_prepare($conn, "
        SELECT COALESCE(SUM(jel.debit), 0.00) AS debits,
               COALESCE(SUM(jel.credit), 0.00) AS credits
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON a.account_code = CAST(jel.account_id AS CHAR)
                      AND a.account_type_id = jel.parent_account_id
        WHERE je.entry_date <= ?
          AND a.account_type_id = ?
    ");

    if (!$stmt) {
        return 0.0;
    }

    mysqli_stmt_bind_param($stmt, 'si', $asOfDateEsc, $accountTypeId);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        return 0.0;
    }

    $debits = (float)$row['debits'];
    $credits = (float)$row['credits'];

    if (strtolower($balanceType) === 'credit') {
        return $credits - $debits;
    }

    return $debits - $credits;
}

function getAccountTypeChildren($conn, $parentId) {
    $parentId = (int)$parentId;
    $stmt = mysqli_prepare($conn, "
        SELECT id, code, name, parent_id
        FROM account_types
        WHERE parent_id = ?
        ORDER BY code ASC, name ASC
    ");

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 'i', $parentId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $rows = [];

    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = [
            'id' => (int)$row['id'],
            'code' => $row['code'],
            'name' => $row['name'],
            'parent_id' => (int)$row['parent_id']
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function buildEquityRows($conn, $parentId, $asOfDate, $balanceType = 'credit') {
    $rows = [];
    foreach (getAccountTypeChildren($conn, $parentId) as $type) {
        $rows[] = [
            'id' => $type['id'],
            'code' => $type['code'],
            'name' => $type['name'],
            'balance' => getAccountTypeBalance($conn, $type['id'], $asOfDate, $balanceType)
        ];
    }
    return $rows;
}

function getBalanceForTypeSelection($rows, $typeIds = [], $typeCodes = []) {
    $total = 0.0;

    foreach ($rows as $row) {
        $rowId = isset($row['id']) ? (int)$row['id'] : 0;
        $rowCode = isset($row['code']) ? (string)$row['code'] : '';
        $matchesTypeId = !empty($typeIds) && in_array($rowId, $typeIds, true);
        $matchesTypeCode = !empty($typeCodes) && in_array($rowCode, $typeCodes, true);

        if ($matchesTypeId || $matchesTypeCode) {
            $total += (float)$row['balance'];
        }
    }

    return $total;
}

function calculateIncomeStatementBalances($conn, $year) {
    $asOfDate = "$year-12-31";

    $revenueRows = buildEquityRows($conn, -4, $asOfDate, 'credit');
    $cogsRows = buildEquityRows($conn, -5, $asOfDate, 'debit');
    $expenseRows = buildEquityRows($conn, -6, $asOfDate, 'debit');

    $revenue = 0.0;
    foreach ($revenueRows as $row) {
        $revenue += (float)$row['balance'];
    }

    $cogs = 0.0;
    foreach ($cogsRows as $row) {
        $cogs += (float)$row['balance'];
    }

    $totalExpenses = 0.0;
    foreach ($expenseRows as $row) {
        $totalExpenses += (float)$row['balance'];
    }

    $adminExpenses = getBalanceForTypeSelection($expenseRows, [48]);
    $financeCosts = getBalanceForTypeSelection($expenseRows, [49]);
    $taxExpenses = getBalanceForTypeSelection($expenseRows, [18]);
    $otherOperatingExpenses = $totalExpenses - $adminExpenses - $financeCosts - $taxExpenses;

    $grossProfit = $revenue - $cogs;
    $operatingProfit = $grossProfit - $otherOperatingExpenses - $adminExpenses;
    $profitBeforeTax = $operatingProfit - $financeCosts;
    $profitAfterTax = $profitBeforeTax - $taxExpenses;

    return [
        'revenue' => $revenue,
        'cogs' => $cogs,
        'gross_profit' => $grossProfit,
        'admin_expenses' => $adminExpenses,
        'finance_costs' => $financeCosts,
        'tax_expenses' => $taxExpenses,
        'operating_profit' => $operatingProfit,
        'profit_before_tax' => $profitBeforeTax,
        'profit_after_tax' => $profitAfterTax,
        'total_comprehensive_income' => $profitAfterTax,
    ];
}

function calculateEquityStatementBalances($conn, $asOfDate) {
    $equityRows = buildEquityRows($conn, -3, $asOfDate, 'credit');

    $shareCapital = getBalanceForTypeSelection($equityRows, [28, 29, 30]);
    $retainedEarnings = getBalanceForTypeSelection($equityRows, [32]);
    $currentYearEarnings = getBalanceForTypeSelection($equityRows, [33]);
    $reserves = getBalanceForTypeSelection($equityRows, [34]);
    $otherComprehensiveIncome = getBalanceForTypeSelection($equityRows, [35]);
    $priorPeriodAdjustments = getBalanceForTypeSelection($equityRows, [36]);
    $drawings = getBalanceForTypeSelection($equityRows, [31]);

    return [
        'share_capital' => $shareCapital,
        'retained_earnings' => $retainedEarnings + ($currentYearEarnings != 0.0 ? $currentYearEarnings : 0.0),
        'reserves' => $reserves,
        'other_comprehensive_income' => $otherComprehensiveIncome,
        'prior_period_adjustments' => $priorPeriodAdjustments,
        'drawings' => $drawings,
        'total_equity' => $shareCapital + $retainedEarnings + $reserves + $otherComprehensiveIncome + $priorPeriodAdjustments - $drawings,
    ];
}

// ────────────────────────────────────────────────────────────────────────────
// Date Range Filtering
// ────────────────────────────────────────────────────────────────────────────

$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

// Load available distinct years of posted entries
$yearsQuery = "SELECT DISTINCT YEAR(entry_date) as yr FROM journal_entries WHERE statuss = 'POSTED' ORDER BY yr DESC";
$yearsRes = mysqli_query($conn, $yearsQuery);
$availableYears = [];
if ($yearsRes) {
    while ($row = mysqli_fetch_assoc($yearsRes)) {
        $availableYears[] = (int)$row['yr'];
    }
}
$defaultYear = !empty($availableYears) ? $availableYears[0] : 2022;

$startDate = '';
$endDate = '';
$periodLabel = '';

if (!empty($filter_start) && !empty($filter_end)) {
    $startDate = $filter_start;
    $endDate = $filter_end;
    $periodLabel = "For the period from " . date('j F Y', strtotime($startDate)) . " to " . date('j F Y', strtotime($endDate));
} elseif (!empty($filter_date)) {
    $startDate = $filter_date;
    $endDate = $filter_date;
    $periodLabel = "For the day " . date('j F Y', strtotime($startDate));
} elseif (!empty($filter_year) && !empty($filter_month)) {
    $startDate = "{$filter_year}-" . sprintf('%02d', $filter_month) . "-01";
    $endDate = date('Y-m-t', strtotime($startDate));
    $periodLabel = "For the month ended " . date('F Y', strtotime($startDate));
} elseif (!empty($filter_year)) {
    $startDate = "{$filter_year}-01-01";
    $endDate = "{$filter_year}-12-31";
    $periodLabel = "For the year ended 31 December {$filter_year}";
} else {
    // Default to the latest year
    $filter_year = $defaultYear;
    $startDate = "{$defaultYear}-01-01";
    $endDate = "{$defaultYear}-12-31";
    $periodLabel = "For the year ended 31 December {$defaultYear}";
}

// Calculate balances
$dayBeforeOpening = date('Y-m-d', strtotime($startDate . ' -1 day'));
$openingBalances = calculateEquityStatementBalances($conn, $dayBeforeOpening);
$closingBalances = calculateEquityStatementBalances($conn, $endDate);

$opening_capital = $openingBalances['share_capital'];
$opening_retained = $openingBalances['retained_earnings'];
$opening_total = $openingBalances['total_equity'];

$closing_capital = $closingBalances['share_capital'];
$closing_retained = $closingBalances['retained_earnings'];
$closing_total = $closingBalances['total_equity'];

$incomeStatementBalances = calculateIncomeStatementBalances($conn, date('Y', strtotime($endDate)));
$profit = $incomeStatementBalances['profit_after_tax'];

$capital_movement = $closing_capital - $opening_capital;
$other_movements = ($closing_retained - $opening_retained) - $profit;

// Calculate YoY Equity Growth trend (comparison against same period previous year)
$prevYearEnd = date('Y-m-d', strtotime($endDate . ' -1 year'));
$prevBalances = calculateEquityStatementBalances($conn, $prevYearEnd);
$prev_total = $prevBalances['total_equity'];

$yoYGrowthVal = $closing_total - $prev_total;
$yoYGrowthPct = ($prev_total > 0) ? round(($yoYGrowthVal / $prev_total) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Statement of Changes in Equity</title>
<meta name="description" content="Statement of Changes in Equity report for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/equity_report.css">
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

  <?php $page_title = "Statement of Changes in Equity"; include '../include/navbar.php'; ?>

  <div class="content" id="equityContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Statement of Changes in Equity
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Financial Statement Report</div>
      </div>
      <div class="page-actions">
        <a class="btn-sm btn-primary" href="../include/export.php?sheet=equity_report&filter_year=<?php echo urlencode($filter_year); ?>&filter_month=<?php echo urlencode($filter_month); ?>&filter_date=<?php echo urlencode($filter_date); ?>&filter_start=<?php echo urlencode($filter_start); ?>&filter_end=<?php echo urlencode($filter_end); ?>" id="exportBtn">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export to Excel
        </a>
        <button class="btn-sm" onclick="window.print()">
          Print Report
        </button>
      </div>
    </div>

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Total Equity -->
      <div class="stat-card" id="stat-total-equity">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">Equity</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($closing_total); ?></div>
        <div class="stat-label">Total Equity Balance</div>
      </div>

      <!-- Share Capital -->
      <div class="stat-card" id="stat-share-capital">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-orange">Capital</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($closing_capital); ?></div>
        <div class="stat-label">Share Capital</div>
      </div>

      <!-- Retained Earnings -->
      <div class="stat-card" id="stat-retained-earnings">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">Earnings</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($closing_retained); ?></div>
        <div class="stat-label">Retained Earnings</div>
      </div>

      <!-- Net profit/loss -->
      <div class="stat-card" id="stat-equity-growth">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-up"><?php echo ($yoYGrowthPct >= 0 ? '+' : '') . $yoYGrowthPct . '%'; ?></span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($profit); ?></div>
        <div class="stat-label">Net Profit (Period)</div>
      </div>
    </div>

    <!-- ===== DATE FILTERS FORM ===== -->
    <form method="GET" class="equity-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 12px 16px; background: var(--card); border-radius: var(--radius); margin-bottom: 24px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <label style="font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px;">Year</label>
        <select name="filter_year" style="padding: 7px 12px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); font-size: 13px; min-width: 90px; color: var(--text);">
          <option value="">All</option>
          <?php foreach ($availableYears as $y): ?>
            <option value="<?php echo $y; ?>" <?php echo ($filter_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <label style="font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px;">Month</label>
        <select name="filter_month" style="padding: 7px 12px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); font-size: 13px; min-width: 120px; color: var(--text);">
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
        <label style="font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px;">Specific Date</label>
        <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 7px 12px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); font-size: 13px; color: var(--text);">
      </div>
      <div style="display: flex; flex-direction: column; gap: 4px;">
        <label style="font-size: 11px; font-weight: 600; color: var(--text2); text-transform: uppercase; letter-spacing: 0.5px;">Date Range</label>
        <div style="display: flex; gap: 6px; align-items: center;">
          <input type="date" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>" style="padding: 7px 12px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); font-size: 13px; color: var(--text);">
          <span style="font-size: 12px; color: var(--text2);">to</span>
          <input type="date" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>" style="padding: 7px 12px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); font-size: 13px; color: var(--text);">
        </div>
      </div>
      <div style="display: flex; gap: 6px;">
        <button type="submit" style="padding: 7px 18px; border-radius: var(--radius); border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(99,102,241,0.3);">
          Apply
        </button>
        <a href="?" style="padding: 7px 14px; border-radius: var(--radius); border: 1px solid var(--border); background: var(--card); color: var(--text2); font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.2s;">
          Clear
        </a>
      </div>
    </form>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container">
      
      <!-- DYNAMIC EQUITY REPORT CARD -->
      <div class="equity-year-card">
        <div class="equity-banner">
          <h3>Statement of Changes in Equity</h3>
          <p><?php echo htmlspecialchars($periodLabel); ?></p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table">
            <thead>
              <tr>
                <th></th>
                <th>Share Capital</th>
                <th>Retained Earnings</th>
                <th>Total Equity</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <!-- Opening Balance Row -->
              <tr class="accent-row">
                <td class="row-label">Opening balance (1 Jan <?php echo date('Y', strtotime($startDate)); ?>)</td>
                <td class="num-val"><?php echo $opening_capital > 0 ? number_format($opening_capital) : '-'; ?></td>
                <td class="num-val"><?php echo $opening_retained > 0 ? number_format($opening_retained) : '-'; ?></td>
                <td class="num-val"><?php echo $opening_total > 0 ? number_format($opening_total) : '-'; ?></td>
              </tr>
              
              <!-- Profit/Loss Row -->
              <tr>
                <td class="row-label">Profit (loss) for the Period</td>
                <td class="num-val">-</td>
                <td class="num-val"><?php echo $profit != 0 ? number_format($profit) : '-'; ?></td>
                <td class="num-val"><?php echo $profit != 0 ? number_format($profit) : '-'; ?></td>
              </tr>

              <!-- Share Capital Additions/Movement Row (if any) -->
              <?php if ($capital_movement != 0): ?>
              <tr>
                <td class="row-label">Issue of Share Capital / Movements</td>
                <td class="num-val"><?php echo number_format($capital_movement); ?></td>
                <td class="num-val">-</td>
                <td class="num-val"><?php echo number_format($capital_movement); ?></td>
              </tr>
              <?php endif; ?>

              <!-- Other Retained Earnings Movements Row (if any) -->
              <?php if ($other_movements != 0): ?>
              <tr>
                <td class="row-label">Other adjustments / Dividends</td>
                <td class="num-val">-</td>
                <td class="num-val"><?php echo number_format($other_movements); ?></td>
                <td class="num-val"><?php echo number_format($other_movements); ?></td>
              </tr>
              <?php endif; ?>
              
              <!-- Closing Balance Row -->
              <tr class="total-row">
                <td class="row-label">Closing balance (<?php echo date('j Dec Y', strtotime($endDate)); ?>)</td>
                <td class="num-val"><?php echo $closing_capital > 0 ? number_format($closing_capital) : '-'; ?></td>
                <td class="num-val"><?php echo $closing_retained > 0 ? number_format($closing_retained) : '-'; ?></td>
                <td class="num-val"><?php echo $closing_total > 0 ? number_format($closing_total) : '-'; ?></td>
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
