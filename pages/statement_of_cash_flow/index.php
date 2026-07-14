<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Security access check using standard view_statement_of_cash_flow permission
if (!hasPermission($conn, $userId, 'view_statement_of_cash_flow')) {
    header("Location: ../dashboard");
    exit();
}

function getAccountBalancesForDate($conn, $asOfDate) {
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);
    $stmt = mysqli_prepare($conn, "
        SELECT a.id, a.account_code, a.account_name, t.parent_id AS parent_type_id,
               COALESCE(SUM(jel.debit), 0.00) AS total_debit,
               COALESCE(SUM(jel.credit), 0.00) AS total_credit
        FROM accounts a
        JOIN account_types t ON a.account_type_id = t.id
        LEFT JOIN journal_entry_lines jel
            ON (a.id = jel.account_id OR a.account_code = CAST(jel.account_id AS CHAR))
           AND a.account_type_id = jel.parent_account_id
        LEFT JOIN journal_entries je
            ON jel.journal_entry_id = je.id
           AND je.statuss = 'POSTED'
           AND je.entry_date <= ?
        WHERE a.is_active = 1
        GROUP BY a.id
        ORDER BY CAST(a.account_code AS UNSIGNED), a.account_code ASC
    ");

    if (!$stmt) {
        return [];
    }

    mysqli_stmt_bind_param($stmt, 's', $asOfDateEsc);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $parentType = (int)$row['parent_type_id'];
        $isDebitNormal = in_array($parentType, [-1, -5, -6], true);
        $net = ((float)$row['total_debit'] - (float)$row['total_credit']);
        $balance = $isDebitNormal ? $net : ((float)$row['total_credit'] - (float)$row['total_debit']);

        $rows[] = [
            'id' => (int)$row['id'],
            'account_code' => (string)$row['account_code'],
            'account_name' => (string)$row['account_name'],
            'parent_type_id' => $parentType,
            'balance' => $balance
        ];
    }

    mysqli_stmt_close($stmt);
    return $rows;
}

function getBalanceForParentType($accounts, $parentType) {
    $total = 0.0;
    foreach ($accounts as $account) {
        if ((int)$account['parent_type_id'] === (int)$parentType) {
            $total += (float)$account['balance'];
        }
    }
    return $total;
}

function getBalanceForAccountCodes($accounts, $codes = [], $patterns = []) {
    $total = 0.0;
    $codes = array_map('strval', $codes);

    foreach ($accounts as $account) {
        $accountCode = (string)$account['account_code'];
        $accountName = strtolower((string)$account['account_name']);
        $matchesCode = !empty($codes) && in_array($accountCode, $codes, true);
        $matchesPattern = false;

        if (!$matchesCode && !empty($patterns)) {
            foreach ($patterns as $pattern) {
                if (strpos($accountName, strtolower((string)$pattern)) !== false) {
                    $matchesPattern = true;
                    break;
                }
            }
        }

        if ($matchesCode || $matchesPattern) {
            $total += (float)$account['balance'];
        }
    }

    return $total;
}

function hasPostedEntriesForYear($conn, $year) {
    $stmt = mysqli_prepare($conn, "SELECT 1 FROM journal_entries WHERE statuss = 'POSTED' AND YEAR(entry_date) = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'i', $year);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $hasData = mysqli_num_rows($result) > 0;
    mysqli_stmt_close($stmt);

    return $hasData;
}

function formatCurrencyValue($value, $hasData = true) {
    if (!$hasData) {
        return '—';
    }

    return number_format((float)$value, 0);
}

function calculateCashFlowMetrics($conn, $year, $priorYearAccounts = []) {
    $asOfDate = "$year-12-31";
    $accounts = getAccountBalancesForDate($conn, $asOfDate);

    $cash = getBalanceForAccountCodes($accounts, ['1001', '1021', '1031', '1041', '1051', '1061'], ['cash', 'bank', 'petty']);
    $inventory = getBalanceForAccountCodes($accounts, ['1004', '1024', '1034'], ['stock', 'inventory']);
    $receivables = getBalanceForAccountCodes($accounts, ['1002', '1003'], ['receivable', 'debtor']);
    $payables = getBalanceForAccountCodes($accounts, ['2001', '2002'], ['payable']);
    $otherCurrentLiabilities = getBalanceForAccountCodes($accounts, [], ['accrued', 'payroll', 'provision', 'deferred revenue', 'deposit', 'intercompany']);
    $nonCurrentAssets = getBalanceForAccountCodes($accounts, ['1005', '1006', '1007', '1008', '1009'], ['property', 'plant', 'equipment', 'intangible', 'deferred tax', 'building', 'vehicle']);
    $loans = getBalanceForAccountCodes($accounts, ['2008', '2009', '2010', '2012'], ['loan', 'borrow']);
    $capital = getBalanceForAccountCodes($accounts, ['3001', '3002', '3003'], ['share', 'capital', 'partner', 'member']);
    $reserves = getBalanceForAccountCodes($accounts, ['3007'], ['reserve']);
    $drawings = getBalanceForAccountCodes($accounts, ['3004'], ['drawing']);

    $netIncome = getBalanceForParentType($accounts, -4) - getBalanceForParentType($accounts, -5) - getBalanceForParentType($accounts, -6);
    $taxExpense = getBalanceForAccountCodes($accounts, [], ['tax']);

    $operatingCashFlow = $netIncome
        + ((isset($priorYearAccounts['inventory']) ? $priorYearAccounts['inventory'] : 0.0) - $inventory)
        + ((isset($priorYearAccounts['receivables']) ? $priorYearAccounts['receivables'] : 0.0) - $receivables)
        + ($payables - (isset($priorYearAccounts['payables']) ? $priorYearAccounts['payables'] : 0.0))
        + ($otherCurrentLiabilities - (isset($priorYearAccounts['other_current_liabilities']) ? $priorYearAccounts['other_current_liabilities'] : 0.0))
        - $taxExpense;

    $investingCashFlow = -($nonCurrentAssets - (isset($priorYearAccounts['non_current_assets']) ? $priorYearAccounts['non_current_assets'] : 0.0));
    $financingCashFlow = ($loans - (isset($priorYearAccounts['loans']) ? $priorYearAccounts['loans'] : 0.0))
        + ($capital - (isset($priorYearAccounts['capital']) ? $priorYearAccounts['capital'] : 0.0))
        + ($reserves - (isset($priorYearAccounts['reserves']) ? $priorYearAccounts['reserves'] : 0.0))
        - $drawings;

    $netCashFlow = $operatingCashFlow + $investingCashFlow + $financingCashFlow;
    $openingCash = isset($priorYearAccounts['cash']) ? (float)$priorYearAccounts['cash'] : 0.0;
    $closingCash = $openingCash + $netCashFlow;

    return [
        'year' => $year,
        'operating' => $operatingCashFlow,
        'investing' => $investingCashFlow,
        'financing' => $financingCashFlow,
        'net' => $netCashFlow,
        'opening_cash' => $openingCash,
        'closing_cash' => $closingCash,
        'cash' => $cash,
        'inventory' => $inventory,
        'receivables' => $receivables,
        'payables' => $payables,
        'other_current_liabilities' => $otherCurrentLiabilities,
        'non_current_assets' => $nonCurrentAssets,
        'loans' => $loans,
        'capital' => $capital,
        'reserves' => $reserves,
        'drawings' => $drawings,
        'tax_expense' => $taxExpense,
        'net_income' => $netIncome,
    ];
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$year1 = $selectedYear;
$year2 = $selectedYear - 1;
$year3 = $selectedYear - 2;
$previousYear = $selectedYear - 1;

$yearsQuery = "SELECT DISTINCT YEAR(entry_date) AS yr FROM journal_entries WHERE statuss = 'POSTED' ORDER BY yr DESC";
$yearsRes = mysqli_query($conn, $yearsQuery);
$availableYears = [];
if ($yearsRes) {
    while ($row = mysqli_fetch_assoc($yearsRes)) {
        $availableYears[] = (int)$row['yr'];
    }
}
if (empty($availableYears)) {
    for ($y = date('Y'); $y >= 2025; $y--) {
        $availableYears[] = $y;
    }
}
if (!in_array($selectedYear, $availableYears, true)) {
    $availableYears[] = $selectedYear;
    rsort($availableYears);
}

$emptyMetrics = [
    'operating' => 0.0,
    'investing' => 0.0,
    'financing' => 0.0,
    'net' => 0.0,
    'opening_cash' => 0.0,
    'closing_cash' => 0.0,
    'cash' => 0.0,
    'inventory' => 0.0,
    'receivables' => 0.0,
    'payables' => 0.0,
    'other_current_liabilities' => 0.0,
    'non_current_assets' => 0.0,
    'loans' => 0.0,
    'capital' => 0.0,
    'reserves' => 0.0,
    'drawings' => 0.0,
    'tax_expense' => 0.0,
    'net_income' => 0.0,
];

$year3HasData = hasPostedEntriesForYear($conn, $year3);
$year2HasData = hasPostedEntriesForYear($conn, $year2);
$currentYearHasData = hasPostedEntriesForYear($conn, $selectedYear);

$year3Metrics = $year3HasData ? calculateCashFlowMetrics($conn, $year3) : $emptyMetrics;
$year2Metrics = $year2HasData ? calculateCashFlowMetrics($conn, $year2, [
    'inventory' => $year3Metrics['inventory'],
    'receivables' => $year3Metrics['receivables'],
    'payables' => $year3Metrics['payables'],
    'other_current_liabilities' => $year3Metrics['other_current_liabilities'],
    'non_current_assets' => $year3Metrics['non_current_assets'],
    'loans' => $year3Metrics['loans'],
    'capital' => $year3Metrics['capital'],
    'reserves' => $year3Metrics['reserves'],
    'cash' => $year3Metrics['cash'],
]) : $emptyMetrics;
$currentYearMetrics = $currentYearHasData ? calculateCashFlowMetrics($conn, $selectedYear, [
    'inventory' => $year2HasData ? $year2Metrics['inventory'] : 0.0,
    'receivables' => $year2HasData ? $year2Metrics['receivables'] : 0.0,
    'payables' => $year2HasData ? $year2Metrics['payables'] : 0.0,
    'other_current_liabilities' => $year2HasData ? $year2Metrics['other_current_liabilities'] : 0.0,
    'non_current_assets' => $year2HasData ? $year2Metrics['non_current_assets'] : 0.0,
    'loans' => $year2HasData ? $year2Metrics['loans'] : 0.0,
    'capital' => $year2HasData ? $year2Metrics['capital'] : 0.0,
    'reserves' => $year2HasData ? $year2Metrics['reserves'] : 0.0,
    'cash' => $year2HasData ? $year2Metrics['cash'] : 0.0,
]) : $emptyMetrics;

$previousYearMetrics = $year2HasData ? $year2Metrics : $emptyMetrics;
$operatingCurrent = $currentYearHasData ? $currentYearMetrics['operating'] : 0.0;
$investingCurrent = $currentYearHasData ? $currentYearMetrics['investing'] : 0.0;
$financingCurrent = $currentYearHasData ? $currentYearMetrics['financing'] : 0.0;
$netCurrent = $currentYearHasData ? $currentYearMetrics['net'] : 0.0;
$openingCashCurrent = $currentYearHasData ? $currentYearMetrics['opening_cash'] : 0.0;
$closingCashCurrent = $currentYearHasData ? $currentYearMetrics['closing_cash'] : 0.0;
$cashEndCurrent = $currentYearHasData ? $currentYearMetrics['cash'] : 0.0;

$operatingPrevious = $year2HasData ? $previousYearMetrics['operating'] : 0.0;
$investingPrevious = $year2HasData ? $previousYearMetrics['investing'] : 0.0;
$financingPrevious = $year2HasData ? $previousYearMetrics['financing'] : 0.0;
$netPrevious = $year2HasData ? $previousYearMetrics['net'] : 0.0;
$openingCashPrevious = $year2HasData ? $previousYearMetrics['opening_cash'] : 0.0;
$closingCashPrevious = $year2HasData ? $previousYearMetrics['closing_cash'] : 0.0;
$cashEndPrevious = $year2HasData ? $previousYearMetrics['cash'] : 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Statement of Cash Flows</title>
<meta name="description" content="Statement of Cash Flows report for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/statement_of_cash_flow.css">
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

  <?php $page_title = "Statement of Cash Flows"; include '../include/navbar.php'; ?>

  <div class="content" id="cashFlowContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Statement of Cash Flows
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Financial Years <?php echo $year3; ?> – <?php echo $year1; ?></div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="exportCsvBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export CSV
        </button>
        <button class="btn-sm btn-primary" onclick="window.print()">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
          Print Report
        </button>
      </div>
    </div>

    <div class="year-filter-container" style="margin-bottom: 16px;">
      <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
        <div class="form-group" style="min-width: 180px;">
          <label for="year">Fiscal Year</label>
          <select id="year" name="year" class="form-control">
            <?php foreach ($availableYears as $yr): ?>
              <option value="<?php echo $yr; ?>" <?php echo $yr === $selectedYear ? 'selected' : ''; ?>><?php echo $yr; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn-sm btn-primary">Apply</button>
      </form>
    </div>

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Operating Cash Flows -->
      <div class="stat-card" id="stat-operating-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Operating</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($operatingCurrent, 0); ?></div>
        <div class="stat-label">Net Operating Cash Flow (<?php echo $selectedYear; ?>)</div>
      </div>

      <!-- Investing Cash Flows -->
      <div class="stat-card" id="stat-investing-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-orange">Investing</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($investingCurrent, 0); ?></div>
        <div class="stat-label">Net Investing Cash Flow (<?php echo $selectedYear; ?>)</div>
      </div>

      <!-- Financing Cash Flows -->
      <div class="stat-card" id="stat-financing-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Financing</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($financingCurrent, 0); ?></div>
        <div class="stat-label">Net Financing Cash Flow (<?php echo $selectedYear; ?>)</div>
      </div>

      <!-- Cash at Year End -->
      <div class="stat-card" id="stat-cash-end">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">Cash Position</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($cashEndCurrent, 0); ?></div>
        <div class="stat-label">Cash equivalents at Year End</div>
      </div>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">INEZA African Mining Ltd</div>
          <h3>Statement of cash flows</h3>
          <p>For the year ended 31 December <?php echo $selectedYear; ?></p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="cash-flow-table">
            <thead>
              <tr>
                <th></th>
                <th class="num-note">Notes</th>
                <th><?php echo $year1; ?></th>
                <th><?php echo $year2; ?></th>
                <th><?php echo $year3; ?></th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th></th>
                <th>$</th>
                <th>$</th>
                <th>$</th>
              </tr>
            </thead>
            <tbody>
              <!-- Section: Operating Activities -->
              <tr class="accent-row">
                <td class="row-label" style="font-weight: 600;">Cash flow from operating activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Net profit before tax</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['net_income'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['net_income'], $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['net_income'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="padding-left: 20px; font-weight: 500;">Profit(loss) before working capital changes</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['net_income'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['net_income'], $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['net_income'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="padding-left: 20px; font-weight: 600;">Working capital changes</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">(Increase)/decrease in inventory</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['inventory'] - $currentYearMetrics['inventory'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['inventory'] - $previousYearMetrics['inventory'], $year2HasData && $year3HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue(0, $year3HasData); ?></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">(Increase)/decrease in account receivables</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['receivables'] - $currentYearMetrics['receivables'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['receivables'] - $previousYearMetrics['receivables'], $year2HasData && $year3HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue(0, $year3HasData); ?></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">Increase/(decrease) in account payables</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['payables'] - $previousYearMetrics['payables'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['payables'] - $year3Metrics['payables'], $year2HasData && $year3HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue(0, $year3HasData); ?></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">Increase/(decrease) in other current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['other_current_liabilities'] - $previousYearMetrics['other_current_liabilities'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['other_current_liabilities'] - $year3Metrics['other_current_liabilities'], $year2HasData && $year3HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue(0, $year3HasData); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="padding-left: 20px; font-weight: 500;">Cash flows from operations</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['operating'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['operating'], $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['operating'], $year3HasData); ?></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Tax paid</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($currentYearMetrics['tax_expense'], $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($previousYearMetrics['tax_expense'], $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['tax_expense'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net Cash flow from operating activities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($operatingCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($operatingPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['operating'], $year3HasData); ?></td>
              </tr>

              <!-- Section: Investing Activities -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Cash flows from Investing activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Acquisition of Non-current assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($investingCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($investingPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['investing'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net cash flow from investing activities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($investingCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($investingPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['investing'], $year3HasData); ?></td>
              </tr>

              <!-- Section: Financing Activities -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Cash flow from financing activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Acquisition/ (payment of loan)</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($financingCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($financingPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['financing'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net Cash flow from financing activities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($financingCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($financingPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['financing'], $year3HasData); ?></td>
              </tr>

              <!-- Summary Totals -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net cash inflow (Outflow) for the period</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($netCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($netPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['net'], $year3HasData); ?></td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="font-weight: 600;">Cash and cash equivalents at the beginning</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($openingCashCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($openingCashPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['opening_cash'], $year3HasData); ?></td>
              </tr>
              <tr class="total-row">
                <td class="row-label" style="font-weight: 600;">Cash and cash equivalents at the end</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo formatCurrencyValue($closingCashCurrent, $currentYearHasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($closingCashPrevious, $year2HasData); ?></td>
                <td class="num-val"><?php echo formatCurrencyValue($year3Metrics['closing_cash'], $year3HasData); ?></td>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Statement of Cash Flows\r\n\r\n";
        
        // Header Row
        csvContent += ",Notes,<?php echo $year1; ?> ($),<?php echo $year2; ?> ($),<?php echo $year3; ?> ($)\r\n";

        const table = document.getElementById('cash-flow-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const label = row.querySelector('.row-label').textContent.trim().replace(/,/g, '');
            
            const noteCell = row.querySelector('.num-note');
            const note = noteCell ? noteCell.textContent.trim() : '';
            
            const cells = row.querySelectorAll('.num-val');
            
            let valYear1 = cells[0] ? cells[0].textContent.trim().replace(/,/g, '') : '';
            let valYear2 = cells[1] ? cells[1].textContent.trim().replace(/,/g, '') : '';
            let valYear3 = cells[2] ? cells[2].textContent.trim().replace(/,/g, '') : '';

            if (valYear1 || valYear2 || valYear3) {
                csvContent += `"${label}",${note},${valYear1},${valYear2},${valYear3}\r\n`;
            } else {
                csvContent += `"${label}",${note},,,\r\n`;
            }
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "ineza_statement_of_cash_flows.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
