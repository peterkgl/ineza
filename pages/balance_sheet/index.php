<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Security access check using standard view_balance_sheet permission
if (!hasPermission($conn, $userId, 'view_balance_sheet')) {
    header("Location: ../dashboard");
    exit();
}

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

function isNonCurrentAssetType($row) {
    $rowId = isset($row['id']) ? (int)$row['id'] : 0;
    $rowCode = isset($row['code']) ? (string)$row['code'] : '';

    return in_array($rowId, [5, 12, 13], true) || in_array($rowCode, ['1005', '1012', '1013'], true);
}

function isNonCurrentLiabilityType($row) {
    $rowId = isset($row['id']) ? (int)$row['id'] : 0;
    $rowCode = isset($row['code']) ? (string)$row['code'] : '';

    return in_array($rowId, [22, 23, 24, 26], true) || in_array($rowCode, ['2008', '2009', '2010', '2012'], true);
}

function buildBalanceSheetRows($conn, $parentId, $asOfDate, $balanceType = 'debit') {
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

function sumBalances($rows, $filterCallback = null) {
    $total = 0.0;
    foreach ($rows as $row) {
        if ($filterCallback) {
            $matches = is_callable($filterCallback) ? $filterCallback($row) : $filterCallback($row['name']);
            if (!$matches) {
                continue;
            }
        }
        $total += (float)$row['balance'];
    }
    return $total;
}

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

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
  for ($y = date('Y'); $y >= 2025; $y--) {
      $availableYears[] = $y;
  }
}
if (!in_array($selectedYear, $availableYears)) {
    $availableYears[] = $selectedYear;
    rsort($availableYears);
}

$year1 = $selectedYear;
$year2 = $selectedYear - 1;
$year3 = $selectedYear - 2;

function calculateIncomeStatementResult($conn, $year) {
    $asOfDate = "$year-12-31";
    $revenueRows = buildBalanceSheetRows($conn, -4, $asOfDate, 'credit');
    $cogsRows = buildBalanceSheetRows($conn, -5, $asOfDate, 'debit');
    $expenseRows = buildBalanceSheetRows($conn, -6, $asOfDate, 'debit');

    $revenue = 0.0;
    foreach ($revenueRows as $row) {
        $revenue += (float)$row['balance'];
    }

    $cogs = 0.0;
    foreach ($cogsRows as $row) {
        $cogs += (float)$row['balance'];
    }

    $expenses = 0.0;
    foreach ($expenseRows as $row) {
        $expenses += (float)$row['balance'];
    }

    return $revenue - $cogs - $expenses;
}

function calculateYearBalances($conn, $year) {
    $asOfDate = "$year-12-31";
    $assetRows = buildBalanceSheetRows($conn, -1, $asOfDate, 'debit');
    $liabilityRows = buildBalanceSheetRows($conn, -2, $asOfDate, 'credit');
    $equityRows = buildBalanceSheetRows($conn, -3, $asOfDate, 'credit');

    $ppe = getBalanceForTypeSelection($assetRows, [5]);
    $inv = getBalanceForTypeSelection($assetRows, [4]);
    $rec = getBalanceForTypeSelection($assetRows, [2]);
    $otherReceivables = getBalanceForTypeSelection($assetRows, [3]);
    $cash = getBalanceForTypeSelection($assetRows, [1]);
    $prepayments = getBalanceForTypeSelection($assetRows, [6]);
    $employeeAdvances = getBalanceForTypeSelection($assetRows, [7]);
    $supplierAdvances = getBalanceForTypeSelection($assetRows, [8]);
    $customerAdvances = getBalanceForTypeSelection($assetRows, [9]);
    $cooperativeAdvances = getBalanceForTypeSelection($assetRows, [10]);
    $otherAdvances = getBalanceForTypeSelection($assetRows, [11]);
    $intangibleAssets = getBalanceForTypeSelection($assetRows, [12]);
    $deferredTaxAssets = getBalanceForTypeSelection($assetRows, [13]);
    $otherAssets = getBalanceForTypeSelection($assetRows, [14]);

    $capital = getBalanceForTypeSelection($equityRows, [28]);
    $partnerCapital = getBalanceForTypeSelection($equityRows, [29]);
    $memberEquity = getBalanceForTypeSelection($equityRows, [30]);
    $currentYearEarnings = getBalanceForTypeSelection($equityRows, [33]);
    $incomeStatementResult = calculateIncomeStatementResult($conn, $year);
    $retained = getBalanceForTypeSelection($equityRows, [32]) + ($currentYearEarnings != 0.0 ? $currentYearEarnings : $incomeStatementResult);
    $reserves = getBalanceForTypeSelection($equityRows, [34]);
    $otherComprehensiveIncome = getBalanceForTypeSelection($equityRows, [35]);
    $priorPeriodAdjustments = getBalanceForTypeSelection($equityRows, [36]);
    $ownerDrawings = getBalanceForTypeSelection($equityRows, [31]);

    $loans = getBalanceForTypeSelection($liabilityRows, [22, 23]);
    $pay = getBalanceForTypeSelection($liabilityRows, [15]);
    $accruedLiabilities = getBalanceForTypeSelection($liabilityRows, [16]);
    $payrollLiabilities = getBalanceForTypeSelection($liabilityRows, [17]);
    $tax = getBalanceForTypeSelection($liabilityRows, [18]);
    $customerDeposits = getBalanceForTypeSelection($liabilityRows, [19]);
    $deferredRevenue = getBalanceForTypeSelection($liabilityRows, [20]);
    $shortTermLoans = getBalanceForTypeSelection($liabilityRows, [21]);
    $intercompanyPayables = getBalanceForTypeSelection($liabilityRows, [25]);
    $otherLiab = getBalanceForTypeSelection($liabilityRows, [27]);
    $deferredTaxLiabilities = getBalanceForTypeSelection($liabilityRows, [26]);
    $provisions = getBalanceForTypeSelection($liabilityRows, [24]);
    $leaseLiabilities = getBalanceForTypeSelection($liabilityRows, [23]);
    $longTermLoans = getBalanceForTypeSelection($liabilityRows, [22]);

    $totalNca = sumBalances($assetRows, 'isNonCurrentAssetType');
    $totalCa = sumBalances($assetRows, function($row) { return !isNonCurrentAssetType($row); });
    $totalAssets = $totalNca + $totalCa;

    $totalNcl = sumBalances($liabilityRows, 'isNonCurrentLiabilityType');
    $totalCl = sumBalances($liabilityRows, function($row) { return !isNonCurrentLiabilityType($row); });
    $totalLiabilities = $totalNcl + $totalCl;

    $totalEquity = $capital + $partnerCapital + $memberEquity + $retained + $reserves + $otherComprehensiveIncome + $priorPeriodAdjustments - $ownerDrawings;
    $totalEqLiab = $totalEquity + $totalLiabilities;

    return [
        'ppe' => $ppe,
        'inv' => $inv,
        'rec' => $rec,
        'other_receivables' => $otherReceivables,
        'cash' => $cash,
        'prepayments' => $prepayments,
        'employee_advances' => $employeeAdvances,
        'supplier_advances' => $supplierAdvances,
        'customer_advances' => $customerAdvances,
        'cooperative_advances' => $cooperativeAdvances,
        'other_advances' => $otherAdvances,
        'intangible_assets' => $intangibleAssets,
        'deferred_tax_assets' => $deferredTaxAssets,
        'other_assets' => $otherAssets,
        'capital' => $capital,
        'partner_capital' => $partnerCapital,
        'member_equity' => $memberEquity,
        'retained' => $retained,
        'reserves' => $reserves,
        'other_comprehensive_income' => $otherComprehensiveIncome,
        'prior_period_adjustments' => $priorPeriodAdjustments,
        'owner_drawings' => $ownerDrawings,
        'loans' => $loans,
        'pay' => $pay,
        'accrued_liabilities' => $accruedLiabilities,
        'payroll_liabilities' => $payrollLiabilities,
        'other_liab' => $otherLiab,
        'tax' => $tax,
        'customer_deposits' => $customerDeposits,
        'deferred_revenue' => $deferredRevenue,
        'short_term_loans' => $shortTermLoans,
        'intercompany_payables' => $intercompanyPayables,
        'deferred_tax_liabilities' => $deferredTaxLiabilities,
        'provisions' => $provisions,
        'lease_liabilities' => $leaseLiabilities,
        'long_term_loans' => $longTermLoans,
        'total_nca' => $totalNca,
        'total_ca' => $totalCa,
        'total_assets' => $totalAssets,
        'total_equity' => $totalEquity,
        'total_ncl' => $totalNcl,
        'total_cl' => $totalCl,
        'total_liabilities' => $totalLiabilities,
        'total_eq_liab' => $totalEqLiab,
    ];
}

$balancesYear1 = calculateYearBalances($conn, $year1);
$balancesYear2 = calculateYearBalances($conn, $year2);
$balancesYear3 = calculateYearBalances($conn, $year3);

$ppe_22 = $balancesYear1['ppe'];
$inv_22 = $balancesYear1['inv'];
$rec_22 = $balancesYear1['rec'];
$other_receivables_22 = $balancesYear1['other_receivables'];
$cash_22 = $balancesYear1['cash'];
$prepayments_22 = $balancesYear1['prepayments'];
$employee_advances_22 = $balancesYear1['employee_advances'];
$supplier_advances_22 = $balancesYear1['supplier_advances'];
$customer_advances_22 = $balancesYear1['customer_advances'];
$cooperative_advances_22 = $balancesYear1['cooperative_advances'];
$other_advances_22 = $balancesYear1['other_advances'];
$intangible_assets_22 = $balancesYear1['intangible_assets'];
$deferred_tax_assets_22 = $balancesYear1['deferred_tax_assets'];
$other_assets_22 = $balancesYear1['other_assets'];
$capital_22 = $balancesYear1['capital'];
$partner_capital_22 = $balancesYear1['partner_capital'];
$member_equity_22 = $balancesYear1['member_equity'];
$retained_22 = $balancesYear1['retained'];
$reserves_22 = $balancesYear1['reserves'];
$other_comprehensive_income_22 = $balancesYear1['other_comprehensive_income'];
$prior_period_adjustments_22 = $balancesYear1['prior_period_adjustments'];
$owner_drawings_22 = $balancesYear1['owner_drawings'];
$loans_22 = $balancesYear1['loans'];
$pay_22 = $balancesYear1['pay'];
$accrued_liabilities_22 = $balancesYear1['accrued_liabilities'];
$payroll_liabilities_22 = $balancesYear1['payroll_liabilities'];
$other_liab_22 = $balancesYear1['other_liab'];
$tax_22 = $balancesYear1['tax'];
$customer_deposits_22 = $balancesYear1['customer_deposits'];
$deferred_revenue_22 = $balancesYear1['deferred_revenue'];
$short_term_loans_22 = $balancesYear1['short_term_loans'];
$intercompany_payables_22 = $balancesYear1['intercompany_payables'];
$deferred_tax_liabilities_22 = $balancesYear1['deferred_tax_liabilities'];
$provisions_22 = $balancesYear1['provisions'];
$lease_liabilities_22 = $balancesYear1['lease_liabilities'];
$long_term_loans_22 = $balancesYear1['long_term_loans'];
$total_nca_22 = $balancesYear1['total_nca'];
$total_ca_22 = $balancesYear1['total_ca'];
$total_assets_22 = $balancesYear1['total_assets'];
$total_equity_22 = $balancesYear1['total_equity'];
$total_ncl_22 = $balancesYear1['total_ncl'];
$total_cl_22 = $balancesYear1['total_cl'];
$total_liabilities_22 = $balancesYear1['total_liabilities'];
$total_eq_liab_22 = $balancesYear1['total_eq_liab'];

$ppe_21 = $balancesYear2['ppe'];
$inv_21 = $balancesYear2['inv'];
$rec_21 = $balancesYear2['rec'];
$other_receivables_21 = $balancesYear2['other_receivables'];
$cash_21 = $balancesYear2['cash'];
$prepayments_21 = $balancesYear2['prepayments'];
$employee_advances_21 = $balancesYear2['employee_advances'];
$supplier_advances_21 = $balancesYear2['supplier_advances'];
$customer_advances_21 = $balancesYear2['customer_advances'];
$cooperative_advances_21 = $balancesYear2['cooperative_advances'];
$other_advances_21 = $balancesYear2['other_advances'];
$intangible_assets_21 = $balancesYear2['intangible_assets'];
$deferred_tax_assets_21 = $balancesYear2['deferred_tax_assets'];
$other_assets_21 = $balancesYear2['other_assets'];
$capital_21 = $balancesYear2['capital'];
$partner_capital_21 = $balancesYear2['partner_capital'];
$member_equity_21 = $balancesYear2['member_equity'];
$retained_21 = $balancesYear2['retained'];
$reserves_21 = $balancesYear2['reserves'];
$other_comprehensive_income_21 = $balancesYear2['other_comprehensive_income'];
$prior_period_adjustments_21 = $balancesYear2['prior_period_adjustments'];
$owner_drawings_21 = $balancesYear2['owner_drawings'];
$loans_21 = $balancesYear2['loans'];
$pay_21 = $balancesYear2['pay'];
$accrued_liabilities_21 = $balancesYear2['accrued_liabilities'];
$payroll_liabilities_21 = $balancesYear2['payroll_liabilities'];
$other_liab_21 = $balancesYear2['other_liab'];
$tax_21 = $balancesYear2['tax'];
$customer_deposits_21 = $balancesYear2['customer_deposits'];
$deferred_revenue_21 = $balancesYear2['deferred_revenue'];
$short_term_loans_21 = $balancesYear2['short_term_loans'];
$intercompany_payables_21 = $balancesYear2['intercompany_payables'];
$deferred_tax_liabilities_21 = $balancesYear2['deferred_tax_liabilities'];
$provisions_21 = $balancesYear2['provisions'];
$lease_liabilities_21 = $balancesYear2['lease_liabilities'];
$long_term_loans_21 = $balancesYear2['long_term_loans'];
$total_nca_21 = $balancesYear2['total_nca'];
$total_ca_21 = $balancesYear2['total_ca'];
$total_assets_21 = $balancesYear2['total_assets'];
$total_equity_21 = $balancesYear2['total_equity'];
$total_ncl_21 = $balancesYear2['total_ncl'];
$total_cl_21 = $balancesYear2['total_cl'];
$total_liabilities_21 = $balancesYear2['total_liabilities'];
$total_eq_liab_21 = $balancesYear2['total_eq_liab'];

$ppe_20 = $balancesYear3['ppe'];
$inv_20 = $balancesYear3['inv'];
$rec_20 = $balancesYear3['rec'];
$other_receivables_20 = $balancesYear3['other_receivables'];
$cash_20 = $balancesYear3['cash'];
$prepayments_20 = $balancesYear3['prepayments'];
$employee_advances_20 = $balancesYear3['employee_advances'];
$supplier_advances_20 = $balancesYear3['supplier_advances'];
$customer_advances_20 = $balancesYear3['customer_advances'];
$cooperative_advances_20 = $balancesYear3['cooperative_advances'];
$other_advances_20 = $balancesYear3['other_advances'];
$intangible_assets_20 = $balancesYear3['intangible_assets'];
$deferred_tax_assets_20 = $balancesYear3['deferred_tax_assets'];
$other_assets_20 = $balancesYear3['other_assets'];
$capital_20 = $balancesYear3['capital'];
$partner_capital_20 = $balancesYear3['partner_capital'];
$member_equity_20 = $balancesYear3['member_equity'];
$retained_20 = $balancesYear3['retained'];
$reserves_20 = $balancesYear3['reserves'];
$other_comprehensive_income_20 = $balancesYear3['other_comprehensive_income'];
$prior_period_adjustments_20 = $balancesYear3['prior_period_adjustments'];
$owner_drawings_20 = $balancesYear3['owner_drawings'];
$loans_20 = $balancesYear3['loans'];
$pay_20 = $balancesYear3['pay'];
$accrued_liabilities_20 = $balancesYear3['accrued_liabilities'];
$payroll_liabilities_20 = $balancesYear3['payroll_liabilities'];
$other_liab_20 = $balancesYear3['other_liab'];
$tax_20 = $balancesYear3['tax'];
$customer_deposits_20 = $balancesYear3['customer_deposits'];
$deferred_revenue_20 = $balancesYear3['deferred_revenue'];
$short_term_loans_20 = $balancesYear3['short_term_loans'];
$intercompany_payables_20 = $balancesYear3['intercompany_payables'];
$deferred_tax_liabilities_20 = $balancesYear3['deferred_tax_liabilities'];
$provisions_20 = $balancesYear3['provisions'];
$lease_liabilities_20 = $balancesYear3['lease_liabilities'];
$long_term_loans_20 = $balancesYear3['long_term_loans'];
$total_nca_20 = $balancesYear3['total_nca'];
$total_ca_20 = $balancesYear3['total_ca'];
$total_assets_20 = $balancesYear3['total_assets'];
$total_equity_20 = $balancesYear3['total_equity'];
$total_ncl_20 = $balancesYear3['total_ncl'];
$total_cl_20 = $balancesYear3['total_cl'];
$total_liabilities_20 = $balancesYear3['total_liabilities'];
$total_eq_liab_20 = $balancesYear3['total_eq_liab'];

// Subtotals and Totals Calculations
$total_nca_22 = $ppe_22 + $intangible_assets_22 + $deferred_tax_assets_22;
$total_ca_22 = $inv_22 + $rec_22 + $other_receivables_22 + $cash_22 + $prepayments_22 + $employee_advances_22 + $supplier_advances_22 + $customer_advances_22 + $cooperative_advances_22 + $other_advances_22 + $other_assets_22;
$total_assets_22 = $total_nca_22 + $total_ca_22;
$total_equity_22 = $capital_22 + $partner_capital_22 + $member_equity_22 + $retained_22 + $reserves_22 + $other_comprehensive_income_22 + $prior_period_adjustments_22 - $owner_drawings_22;
$total_ncl_22 = $long_term_loans_22 + $lease_liabilities_22 + $provisions_22 + $deferred_tax_liabilities_22;
$total_cl_22 = $pay_22 + $accrued_liabilities_22 + $payroll_liabilities_22 + $tax_22 + $customer_deposits_22 + $deferred_revenue_22 + $short_term_loans_22 + $intercompany_payables_22 + $other_liab_22;
$total_liabilities_22 = $total_ncl_22 + $total_cl_22;
$total_eq_liab_22 = $total_equity_22 + $total_liabilities_22;

$total_nca_21 = $ppe_21 + $intangible_assets_21 + $deferred_tax_assets_21;
$total_ca_21 = $inv_21 + $rec_21 + $other_receivables_21 + $cash_21 + $prepayments_21 + $employee_advances_21 + $supplier_advances_21 + $customer_advances_21 + $cooperative_advances_21 + $other_advances_21 + $other_assets_21;
$total_assets_21 = $total_nca_21 + $total_ca_21;
$total_equity_21 = $capital_21 + $partner_capital_21 + $member_equity_21 + $retained_21 + $reserves_21 + $other_comprehensive_income_21 + $prior_period_adjustments_21 - $owner_drawings_21;
$total_ncl_21 = $long_term_loans_21 + $lease_liabilities_21 + $provisions_21 + $deferred_tax_liabilities_21;
$total_cl_21 = $pay_21 + $accrued_liabilities_21 + $payroll_liabilities_21 + $tax_21 + $customer_deposits_21 + $deferred_revenue_21 + $short_term_loans_21 + $intercompany_payables_21 + $other_liab_21;
$total_liabilities_21 = $total_ncl_21 + $total_cl_21;
$total_eq_liab_21 = $total_equity_21 + $total_liabilities_21;

$total_nca_20 = $ppe_20 + $intangible_assets_20 + $deferred_tax_assets_20;
$total_ca_20 = $inv_20 + $rec_20 + $other_receivables_20 + $cash_20 + $prepayments_20 + $employee_advances_20 + $supplier_advances_20 + $customer_advances_20 + $cooperative_advances_20 + $other_advances_20 + $other_assets_20;
$total_assets_20 = $total_nca_20 + $total_ca_20;
$total_equity_20 = $capital_20 + $partner_capital_20 + $member_equity_20 + $retained_20 + $reserves_20 + $other_comprehensive_income_20 + $prior_period_adjustments_20 - $owner_drawings_20;
$total_ncl_20 = $long_term_loans_20 + $lease_liabilities_20 + $provisions_20 + $deferred_tax_liabilities_20;
$total_cl_20 = $pay_20 + $accrued_liabilities_20 + $payroll_liabilities_20 + $tax_20 + $customer_deposits_20 + $deferred_revenue_20 + $short_term_loans_20 + $intercompany_payables_20 + $other_liab_20;
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
                <th>$</th>
                <th>$</th>
                <th>$</th>
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
              <tr>
                <td class="row-label">Property, Plant and Equipment</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($ppe_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($ppe_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($ppe_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Intangible Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($intangible_assets_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($intangible_assets_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($intangible_assets_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Deferred Tax Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($deferred_tax_assets_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_tax_assets_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_tax_assets_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other Non Current Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($other_assets_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_assets_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_assets_20, 2); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_nca_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_nca_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_nca_20, 2); ?></td>
              </tr>

              <!-- Current Assets -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Assets</td>
              </tr>
              <tr>
                <td class="row-label">Inventory</td>
                <td class="num-note">7</td>
                <td class="num-val"><?php echo number_format($inv_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($inv_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($inv_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Accounts Receivables</td>
                <td class="num-note">6</td>
                <td class="num-val"><?php echo number_format($rec_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($rec_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($rec_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other Receivables</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($other_receivables_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_receivables_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_receivables_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Cash and Cash Equivalents</td>
                <td class="num-note">5</td>
                <td class="num-val"><?php echo number_format($cash_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($cash_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($cash_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Prepayments</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($prepayments_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($prepayments_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($prepayments_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Employee Advances</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($employee_advances_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($employee_advances_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($employee_advances_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Supplier Advances</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($supplier_advances_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($supplier_advances_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($supplier_advances_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Customer Advances</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($customer_advances_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($customer_advances_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($customer_advances_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Cooperative Advances</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($cooperative_advances_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($cooperative_advances_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($cooperative_advances_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other Advances</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($other_advances_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_advances_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_advances_20, 2); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Current Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_ca_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_ca_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_ca_20, 2); ?></td>
              </tr>

              <!-- Total Assets -->
              <tr class="grand-total-row">
                <td class="row-label">Total Assets</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_assets_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_assets_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_assets_20, 2); ?></td>
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
                <td class="num-val"><?php echo number_format($capital_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($capital_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($capital_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Partner Capital</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($partner_capital_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($partner_capital_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($partner_capital_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Member Equity</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($member_equity_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($member_equity_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($member_equity_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Retained earnings</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($retained_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($retained_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($retained_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Reserves</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($reserves_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($reserves_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($reserves_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other Comprehensive Income</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($other_comprehensive_income_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_comprehensive_income_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_comprehensive_income_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Prior Period Adjustments</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($prior_period_adjustments_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($prior_period_adjustments_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($prior_period_adjustments_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Less: Owner Drawings</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format(-$owner_drawings_22, 2); ?></td>
                <td class="num-val"><?php echo number_format(-$owner_drawings_21, 2); ?></td>
                <td class="num-val"><?php echo number_format(-$owner_drawings_20, 2); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Equity</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_equity_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_equity_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_equity_20, 2); ?></td>
              </tr>

              <!-- Non Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Non Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Long Term Loans</td>
                <td class="num-note">10</td>
                <td class="num-val"><?php echo $long_term_loans_22 > 0 ? number_format($long_term_loans_22, 2) : '-'; ?></td>
                <td class="num-val"><?php echo $long_term_loans_21 > 0 ? number_format($long_term_loans_21, 2) : '-'; ?></td>
                <td class="num-val"><?php echo $long_term_loans_20 > 0 ? number_format($long_term_loans_20, 2) : '-'; ?></td>
              </tr>
              <tr>
                <td class="row-label">Lease Liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($lease_liabilities_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($lease_liabilities_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($lease_liabilities_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Provisions</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($provisions_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($provisions_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($provisions_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Deferred Tax Liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($deferred_tax_liabilities_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_tax_liabilities_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_tax_liabilities_20, 2); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo $total_ncl_22 > 0 ? number_format($total_ncl_22, 2) : '-'; ?></td>
                <td class="num-val"><?php echo $total_ncl_21 > 0 ? number_format($total_ncl_21, 2) : '-'; ?></td>
                <td class="num-val"><?php echo $total_ncl_20 > 0 ? number_format($total_ncl_20, 2) : '-'; ?></td>
              </tr>

              <!-- Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Accounts Payables</td>
                <td class="num-note">9</td>
                <td class="num-val"><?php echo number_format($pay_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($pay_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($pay_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Accrued Liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($accrued_liabilities_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($accrued_liabilities_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($accrued_liabilities_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Payroll Liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($payroll_liabilities_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($payroll_liabilities_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($payroll_liabilities_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Current Tax Payable</td>
                <td class="num-note">11</td>
                <td class="num-val"><?php echo number_format($tax_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($tax_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($tax_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Customer Deposits</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($customer_deposits_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($customer_deposits_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($customer_deposits_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Deferred Revenue</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($deferred_revenue_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_revenue_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($deferred_revenue_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Short Term Loans</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($short_term_loans_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($short_term_loans_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($short_term_loans_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Intercompany Payables</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($intercompany_payables_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($intercompany_payables_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($intercompany_payables_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Other Current Liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($other_liab_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_liab_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($other_liab_20, 2); ?></td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_cl_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_cl_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_cl_20, 2); ?></td>
              </tr>

              <!-- Total Liabilities -->
              <tr class="subtotal-row">
                <td class="row-label" style="padding-left: 18px;">Total liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_liabilities_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_liabilities_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_liabilities_20, 2); ?></td>
              </tr>

              <!-- Total equity and liabilities -->
              <tr class="grand-total-row">
                <td class="row-label">Total equity and liabilities</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_eq_liab_20, 2); ?></td>
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
