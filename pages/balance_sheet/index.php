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

function getBalanceForCategory($conn, $category, $asOfDate) {
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);
    $whereClause = "";
    switch ($category) {
        case 'ppe':
            $whereClause = "(a.account_code LIKE '15%' OR a.account_code LIKE '16%' OR LOWER(a.account_name) LIKE '%property%' OR LOWER(a.account_name) LIKE '%plant%' OR LOWER(a.account_name) LIKE '%equipment%') AND t.parent_id = -1";
            break;
        case 'inventory':
            $whereClause = "(a.account_code LIKE '13%' OR a.account_code LIKE '14%' OR LOWER(a.account_name) LIKE '%inventory%' OR LOWER(a.account_name) LIKE '%stock%') AND t.parent_id = -1";
            break;
        case 'receivables':
            $whereClause = "(a.account_type_id = 6 OR LOWER(a.account_name) LIKE '%receivable%' OR a.account_code LIKE '11%') AND t.parent_id = -1";
            break;
        case 'cash':
            $whereClause = "(a.account_code LIKE '10%' OR LOWER(a.account_name) LIKE '%cash%' OR LOWER(a.account_name) LIKE '%bank%' OR LOWER(a.account_name) LIKE '%bk%' OR LOWER(a.account_name) LIKE '%momo%') AND t.parent_id = -1 AND a.account_type_id != 6";
            break;
        case 'share_capital':
            $whereClause = "(a.account_code LIKE '30%' OR LOWER(a.account_name) LIKE '%share%' OR LOWER(a.account_name) LIKE '%capital%') AND t.parent_id = -3";
            break;
        case 'retained_earnings':
            $whereClause = "(a.account_code LIKE '32%' OR LOWER(a.account_name) LIKE '%retained%' OR LOWER(a.account_name) LIKE '%earnings%') AND t.parent_id = -3";
            break;
        case 'long_term_loans':
            $whereClause = "(a.account_code LIKE '22%' OR LOWER(a.account_name) LIKE '%loan%' OR LOWER(a.account_name) LIKE '%long term%') AND t.parent_id = -2";
            break;
        case 'payables':
            $whereClause = "(a.account_type_id = 3 OR LOWER(a.account_name) LIKE '%payable%' OR a.account_code LIKE '200%' OR a.account_code LIKE '201%') AND t.parent_id = -2";
            break;
        case 'tax_payable':
            $whereClause = "(LOWER(a.account_name) LIKE '%tax%' OR LOWER(a.account_name) LIKE '%vat%' OR LOWER(a.account_name) LIKE '%rra%') AND t.parent_id = -2";
            break;
        case 'other_liabilities':
            $whereClause = "t.parent_id = -2 AND NOT (a.account_type_id = 3 OR LOWER(a.account_name) LIKE '%payable%' OR a.account_code LIKE '200%' OR a.account_code LIKE '201%' OR a.account_code LIKE '22%' OR LOWER(a.account_name) LIKE '%loan%' OR LOWER(a.account_name) LIKE '%long term%' OR LOWER(a.account_name) LIKE '%tax%' OR LOWER(a.account_name) LIKE '%vat%' OR LOWER(a.account_name) LIKE '%rra%')";
            break;
        default:
            return 0.0;
    }

    $isDebitNormal = in_array($category, ['ppe', 'inventory', 'receivables', 'cash']);

    $sql = "
        SELECT COALESCE(SUM(jel.debit), 0.00) as debits,
               COALESCE(SUM(jel.credit), 0.00) as credits
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND $whereClause";
        
    $res = mysqli_query($conn, $sql);
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $deb = (float)$row['debits'];
        $cred = (float)$row['credits'];
        return $isDebitNormal ? ($deb - $cred) : ($cred - $deb);
    }
    return 0.0;
}

function getRetainedEarningsWithNetIncome($conn, $asOfDate) {
    $asOfDateEsc = mysqli_real_escape_string($conn, $asOfDate);
    $retainedRaw = getBalanceForCategory($conn, 'retained_earnings', $asOfDate);
    
    $revenueSql = "
        SELECT COALESCE(SUM(jel.credit - jel.debit), 0.00) as net_revenue
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -4";
    $revRes = mysqli_query($conn, $revenueSql);
    $revRow = mysqli_fetch_assoc($revRes);
    $netRevenue = (float)($revRow['net_revenue'] ?? 0.0);

    $cogsSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_cogs
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -5";
    $cogsRes = mysqli_query($conn, $cogsSql);
    $cogsRow = mysqli_fetch_assoc($cogsRes);
    $netCogs = (float)($cogsRow['net_cogs'] ?? 0.0);

    $expSql = "
        SELECT COALESCE(SUM(jel.debit - jel.credit), 0.00) as net_exp
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN accounts a ON jel.account_id = a.id
        JOIN account_types t ON a.account_type_id = t.id
        WHERE je.statuss = 'POSTED' AND je.entry_date <= '$asOfDateEsc' AND t.parent_id = -6";
    $expRes = mysqli_query($conn, $expSql);
    $expRow = mysqli_fetch_assoc($expRes);
    $netExp = (float)($expRow['net_exp'] ?? 0.0);

    $currentPeriodNetIncome = $netRevenue - $netCogs - $netExp;
    return $retainedRaw + $currentPeriodNetIncome;
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : 2022;

// Query distinct years from POSTED entries
$yearsQuery = "SELECT DISTINCT YEAR(entry_date) as yr FROM journal_entries WHERE statuss = 'POSTED' ORDER BY yr DESC";
$yearsRes = mysqli_query($conn, $yearsQuery);
$availableYears = [];
if ($yearsRes) {
    while ($row = mysqli_fetch_assoc($yearsRes)) {
        $availableYears[] = (int)$row['yr'];
    }
}
if (empty($availableYears)) {
    $availableYears = [2022, 2021, 2020];
}
if (!in_array($selectedYear, $availableYears)) {
    $availableYears[] = $selectedYear;
    rsort($availableYears);
}

$year1 = $selectedYear;
$year2 = $selectedYear - 1;
$year3 = $selectedYear - 2;

// Calculate Year 1
$ppe_22 = getBalanceForCategory($conn, 'ppe', "$year1-12-31");
$inv_22 = getBalanceForCategory($conn, 'inventory', "$year1-12-31");
$rec_22 = getBalanceForCategory($conn, 'receivables', "$year1-12-31");
$cash_22 = getBalanceForCategory($conn, 'cash', "$year1-12-31");
$capital_22 = getBalanceForCategory($conn, 'share_capital', "$year1-12-31");
$retained_22 = getRetainedEarningsWithNetIncome($conn, "$year1-12-31");
$loans_22 = getBalanceForCategory($conn, 'long_term_loans', "$year1-12-31");
$pay_22 = getBalanceForCategory($conn, 'payables', "$year1-12-31");
$other_liab_22 = getBalanceForCategory($conn, 'other_liabilities', "$year1-12-31");
$tax_22 = getBalanceForCategory($conn, 'tax_payable', "$year1-12-31");

// Calculate Year 2
$ppe_21 = getBalanceForCategory($conn, 'ppe', "$year2-12-31");
$inv_21 = getBalanceForCategory($conn, 'inventory', "$year2-12-31");
$rec_21 = getBalanceForCategory($conn, 'receivables', "$year2-12-31");
$cash_21 = getBalanceForCategory($conn, 'cash', "$year2-12-31");
$capital_21 = getBalanceForCategory($conn, 'share_capital', "$year2-12-31");
$retained_21 = getRetainedEarningsWithNetIncome($conn, "$year2-12-31");
$loans_21 = getBalanceForCategory($conn, 'long_term_loans', "$year2-12-31");
$pay_21 = getBalanceForCategory($conn, 'payables', "$year2-12-31");
$other_liab_21 = getBalanceForCategory($conn, 'other_liabilities', "$year2-12-31");
$tax_21 = getBalanceForCategory($conn, 'tax_payable', "$year2-12-31");

// Calculate Year 3
$ppe_20 = getBalanceForCategory($conn, 'ppe', "$year3-12-31");
$inv_20 = getBalanceForCategory($conn, 'inventory', "$year3-12-31");
$rec_20 = getBalanceForCategory($conn, 'receivables', "$year3-12-31");
$cash_20 = getBalanceForCategory($conn, 'cash', "$year3-12-31");
$capital_20 = getBalanceForCategory($conn, 'share_capital', "$year3-12-31");
$retained_20 = getRetainedEarningsWithNetIncome($conn, "$year3-12-31");
$loans_20 = getBalanceForCategory($conn, 'long_term_loans', "$year3-12-31");
$pay_20 = getBalanceForCategory($conn, 'payables', "$year3-12-31");
$other_liab_20 = getBalanceForCategory($conn, 'other_liabilities', "$year3-12-31");
$tax_20 = getBalanceForCategory($conn, 'tax_payable', "$year3-12-31");

// Subtotals and Totals Calculations
$total_nca_22 = $ppe_22;
$total_ca_22 = $inv_22 + $rec_22 + $cash_22;
$total_assets_22 = $total_nca_22 + $total_ca_22;
$total_equity_22 = $capital_22 + $retained_22;
$total_ncl_22 = $loans_22;
$total_cl_22 = $pay_22 + $other_liab_22 + $tax_22;
$total_liabilities_22 = $total_ncl_22 + $total_cl_22;
$total_eq_liab_22 = $total_equity_22 + $total_liabilities_22;

$total_nca_21 = $ppe_21;
$total_ca_21 = $inv_21 + $rec_21 + $cash_21;
$total_assets_21 = $total_nca_21 + $total_ca_21;
$total_equity_21 = $capital_21 + $retained_21;
$total_ncl_21 = $loans_21;
$total_cl_21 = $pay_21 + $other_liab_21 + $tax_21;
$total_liabilities_21 = $total_ncl_21 + $total_cl_21;
$total_eq_liab_21 = $total_equity_21 + $total_liabilities_21;

$total_nca_20 = $ppe_20;
$total_ca_20 = $inv_20 + $rec_20 + $cash_20;
$total_assets_20 = $total_nca_20 + $total_ca_20;
$total_equity_20 = $capital_20 + $retained_20;
$total_ncl_20 = $loans_20;
$total_cl_20 = $pay_20 + $other_liab_20 + $tax_20;
$total_liabilities_20 = $total_ncl_20 + $total_cl_20;
$total_eq_liab_20 = $total_equity_20 + $total_liabilities_20;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Statement of Financial Position (Balance Sheet)</title>
<meta name="description" content="Statement of Financial Position for DETROIT CROWNED GOLDEN BUSINESS 'CGB' Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/balance_sheet.css">
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

  <?php $page_title = "Balance Sheet"; include '../include/navbar.php'; ?>

  <div class="content" id="balanceSheetContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M12 22V2M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Statement of Financial Position
        </h1>
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

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Total Assets -->
      <div class="stat-card" id="stat-total-assets">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-up">Assets</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($total_assets_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Total Assets (<?php echo $year1; ?>)</div>
      </div>

      <!-- Total Equity -->
      <div class="stat-card" id="stat-total-equity">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-blue">Equity</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($total_equity_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Capital and Reserves (<?php echo $year1; ?>)</div>
      </div>

      <!-- Total Liabilities -->
      <div class="stat-card" id="stat-total-liabilities">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Liabilities</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($total_liabilities_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Total Liabilities (<?php echo $year1; ?>)</div>
      </div>

      <!-- Cash Position -->
      <div class="stat-card" id="stat-cash-position">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-up">Cash</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($cash_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Cash &amp; cash equivalents (<?php echo $year1; ?>)</div>
      </div>
    </div>

    <!-- ===== YEAR FILTER SEARCHABLE DROPDOWN ===== -->
    <div class="year-filter-container">
      <span class="dropdown-label">Select Fiscal Year</span>
      <div class="custom-dropdown" id="yearCustomDropdown">
        <button type="button" class="dropdown-trigger" id="dropdownTriggerBtn">Fiscal Year <?php echo $selectedYear; ?></button>
        <div class="dropdown-menu">
          <div class="dropdown-search-wrapper">
            <input type="text" class="dropdown-search" id="dropdownSearchInput" placeholder="Search year..." autocomplete="off">
          </div>
          <div class="dropdown-options-list" id="dropdownOptionsList">
            <?php foreach ($availableYears as $yr): ?>
              <div class="dropdown-option <?php echo $yr === $selectedYear ? 'selected' : ''; ?>" data-value="<?php echo $yr; ?>">Fiscal Year <?php echo $yr; ?></div>
            <?php endforeach; ?>
            <div class="no-results" id="dropdownNoResults">No years found</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          
          <h3>Statement of financial position</h3>
          <p>As at 31 December <?php echo $year1; ?></p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="balance-sheet-table">
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
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <!-- Assets Section -->
              <tr class="section-header-row">
                <td colspan="5">Assets</td>
              </tr>
              
              <!-- Non Current Assets -->
              <tr class="subsection-header-row">
                <td colspan="5">Non Current Assets</td>
              </tr>
               <!-- Property, Plant and Equipment -->
               <tr>
                <td class="row-label">Property, Plant and Equipment</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($ppe_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($ppe_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($ppe_20, 0); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_nca_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_nca_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_nca_20, 0); ?></td>
              </tr>
              
              <!-- Current Assets -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Assets</td>
              </tr>
              <tr>
                <td class="row-label">Inventory</td>
                <td class="num-note">7</td>
                <td class="num-val"><?php echo number_format($inv_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($inv_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($inv_20, 0); ?></td>
              </tr>
              <tr>
                <td class="row-label">Accounts receivables</td>
                <td class="num-note">6</td>
                <td class="num-val"><?php echo number_format($rec_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($rec_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($rec_20, 0); ?></td>
              </tr>
              <tr>
                <td class="row-label">Cash and cash equivalents</td>
                <td class="num-note">5</td>
                <td class="num-val"><?php echo number_format($cash_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($cash_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($cash_20, 0); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Current Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_ca_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_ca_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_ca_20, 0); ?></td>
              </tr>
              
              <!-- Total Assets -->
              <tr class="grand-total-row">
                <td class="row-label">Total Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_assets_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_assets_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_assets_20, 0); ?></td>
              </tr>
              
              <!-- Equity and Liabilities Section -->
              <tr class="section-header-row">
                <td colspan="5">Equity and liabilities</td>
              </tr>
              
              <!-- Capital and reserves -->
              <tr class="subsection-header-row">
                <td colspan="5">Capital and reserves</td>
              </tr>
              <tr>
                <td class="row-label">Share Capital</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($capital_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($capital_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($capital_20, 0); ?></td>
              </tr>
              <tr>
                <td class="row-label">Retained earnings</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($retained_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($retained_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($retained_20, 0); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Equity</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_equity_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_equity_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_equity_20, 0); ?></td>
              </tr>
              
              <!-- Non Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Non Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Long term loans and other LT liabilities</td>
                <td class="num-note">10</td>
                <td class="num-val"><?php echo $loans_22 > 0 ? number_format($loans_22, 0) : '-'; ?></td>
                <td class="num-val"><?php echo $loans_21 > 0 ? number_format($loans_21, 0) : '-'; ?></td>
                <td class="num-val"><?php echo $loans_20 > 0 ? number_format($loans_20, 0) : '-'; ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo $total_ncl_22 > 0 ? number_format($total_ncl_22, 0) : '-'; ?></td>
                <td class="num-val"><?php echo $total_ncl_21 > 0 ? number_format($total_ncl_21, 0) : '-'; ?></td>
                <td class="num-val"><?php echo $total_ncl_20 > 0 ? number_format($total_ncl_20, 0) : '-'; ?></td>
              </tr>
              
              <!-- Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Accounts payables</td>
                <td class="num-note">9</td>
                <td class="num-val"><?php echo number_format($pay_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($pay_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($pay_20, 0); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other current liabilities</td>
                <td class="num-note">9</td>
                <td class="num-val"><?php echo number_format($other_liab_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($other_liab_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($other_liab_20, 0); ?></td>
              </tr>
              <tr>
                <td class="row-label">Current Tax Payable</td>
                <td class="num-note">11</td>
                <td class="num-val"><?php echo number_format($tax_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($tax_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($tax_20, 0); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_cl_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_cl_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_cl_20, 0); ?></td>
              </tr>
              
              <!-- Total Liabilities -->
              <tr class="subtotal-row">
                <td class="row-label" style="padding-left: 18px;">Total liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_liabilities_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_liabilities_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_liabilities_20, 0); ?></td>
              </tr>
              
              <!-- Total equity and liabilities -->
              <tr class="grand-total-row">
                <td class="row-label">Total equity and liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_22, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_21, 0); ?></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_20, 0); ?></td>
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
    // Dropdown toggle
    const dropdown = document.getElementById('yearCustomDropdown');
    const trigger = document.getElementById('dropdownTriggerBtn');
    const searchInput = document.getElementById('dropdownSearchInput');
    const options = dropdown.querySelectorAll('.dropdown-option');
    const noResults = document.getElementById('dropdownNoResults');

    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            searchInput.focus();
        }
    });

    // Dropdown option click
    options.forEach(opt => {
        opt.addEventListener('click', function() {
            const yearVal = this.getAttribute('data-value');
            window.location.href = `?year=${yearVal}`;
        });
    });

    // Search logic
    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        let matchCount = 0;
        options.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            if (text.includes(query)) {
                opt.style.display = 'block';
                matchCount++;
            } else {
                opt.style.display = 'none';
            }
        });
        noResults.style.display = matchCount === 0 ? 'block' : 'none';
    });

    // Close when clicking outside
    document.addEventListener('click', function() {
        dropdown.classList.remove('open');
    });

    // Stop propagation inside dropdown menu
    dropdown.querySelector('.dropdown-menu').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "DETROIT CROWNED GOLDEN BUSINESS \"CGB\" Ltd - Statement of financial position\r\n\r\n";
        
        // Header Row
        csvContent += `,Notes,${<?php echo $year1; ?>} (Frw),${<?php echo $year2; ?>} (Frw),${<?php echo $year3; ?>} (Frw)\r\n`;

        const table = document.getElementById('balance-sheet-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            if (row.classList.contains('section-header-row')) {
                const headerText = row.textContent.trim().replace(/,/g, '');
                csvContent += `\r\n"${headerText}",,,\r\n`;
            } else if (row.classList.contains('subsection-header-row')) {
                const subHeaderText = row.textContent.trim().replace(/,/g, '');
                csvContent += `"${subHeaderText}",,,\r\n`;
            } else {
                const labelCell = row.querySelector('.row-label');
                if (!labelCell) return;
                const label = labelCell.textContent.trim().replace(/,/g, '');
                
                const noteCell = row.querySelector('.num-note');
                const note = noteCell ? noteCell.textContent.trim() : '';
                
                const cells = row.querySelectorAll('.num-val');
                
                let val2022 = cells[0] ? cells[0].textContent.trim().replace(/,/g, '') : '';
                let val2021 = cells[1] ? cells[1].textContent.trim().replace(/,/g, '') : '';
                let val2020 = cells[2] ? cells[2].textContent.trim().replace(/,/g, '') : '';

                csvContent += `"${label}",${note},${val2022},${val2021},${val2020}\r\n`;
            }
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "cgb_statement_of_financial_position_<?php echo $year1; ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
