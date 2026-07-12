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

function buildIncomeStatementRows($conn, $parentId, $asOfDate, $balanceType = 'debit') {
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

    $revenueRows = buildIncomeStatementRows($conn, -4, $asOfDate, 'credit');
    $cogsRows = buildIncomeStatementRows($conn, -5, $asOfDate, 'debit');
    $expenseRows = buildIncomeStatementRows($conn, -6, $asOfDate, 'debit');

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

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

$yearsQuery = "SELECT DISTINCT YEAR(entry_date) as yr FROM journal_entries WHERE statuss = 'POSTED' ORDER BY yr DESC";
$yearsRes = mysqli_query($conn, $yearsQuery);
$availableYears = [];
if ($yearsRes) {
    while ($row = mysqli_fetch_assoc($yearsRes)) {
        $availableYears[] = (int)$row['yr'];
    }
}
if (empty($availableYears)) {
    for ($y = date('Y'); $y >= 2020; $y--) {
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

$balancesYear1 = calculateIncomeStatementBalances($conn, $year1);
$balancesYear2 = calculateIncomeStatementBalances($conn, $year2);
$balancesYear3 = calculateIncomeStatementBalances($conn, $year3);

$revenue_22 = $balancesYear1['revenue'];
$cogs_22 = $balancesYear1['cogs'];
$gross_profit_22 = $balancesYear1['gross_profit'];
$admin_expenses_22 = $balancesYear1['admin_expenses'];
$finance_costs_22 = $balancesYear1['finance_costs'];
$tax_expenses_22 = $balancesYear1['tax_expenses'];
$operating_profit_22 = $balancesYear1['operating_profit'];
$profit_before_tax_22 = $balancesYear1['profit_before_tax'];
$profit_after_tax_22 = $balancesYear1['profit_after_tax'];
$total_comprehensive_income_22 = $balancesYear1['total_comprehensive_income'];

$revenue_21 = $balancesYear2['revenue'];
$cogs_21 = $balancesYear2['cogs'];
$gross_profit_21 = $balancesYear2['gross_profit'];
$admin_expenses_21 = $balancesYear2['admin_expenses'];
$finance_costs_21 = $balancesYear2['finance_costs'];
$tax_expenses_21 = $balancesYear2['tax_expenses'];
$operating_profit_21 = $balancesYear2['operating_profit'];
$profit_before_tax_21 = $balancesYear2['profit_before_tax'];
$profit_after_tax_21 = $balancesYear2['profit_after_tax'];
$total_comprehensive_income_21 = $balancesYear2['total_comprehensive_income'];

$revenue_20 = $balancesYear3['revenue'];
$cogs_20 = $balancesYear3['cogs'];
$gross_profit_20 = $balancesYear3['gross_profit'];
$admin_expenses_20 = $balancesYear3['admin_expenses'];
$finance_costs_20 = $balancesYear3['finance_costs'];
$tax_expenses_20 = $balancesYear3['tax_expenses'];
$operating_profit_20 = $balancesYear3['operating_profit'];
$profit_before_tax_20 = $balancesYear3['profit_before_tax'];
$profit_after_tax_20 = $balancesYear3['profit_after_tax'];
$total_comprehensive_income_20 = $balancesYear3['total_comprehensive_income'];

$gross_margin_22 = $revenue_22 > 0 ? ($gross_profit_22 / $revenue_22) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Statement of Profit or Loss and Other Comprehensive Income</title>
<meta name="description" content="Statement of profit or loss and Other Comprehensive Income for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/comprehensive_income.css">
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

  <?php $page_title = "Comprehensive Income"; include '../include/navbar.php'; ?>

  <div class="content" id="incomeContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Comprehensive Income Statement
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

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Total Revenue -->
      <div class="stat-card" id="stat-revenue">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Revenue</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($revenue_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Gross Revenue (<?php echo $year1; ?>)</div>
      </div>

      <!-- Gross Profit -->
      <div class="stat-card" id="stat-gross-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">53.0% GP</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($gross_profit_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Gross Profit (<?php echo $year1; ?>)</div>
      </div>

      <!-- Operating Profit -->
      <div class="stat-card" id="stat-operating-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-blue">EBIT</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($operating_profit_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Operating Profit (<?php echo $year1; ?>)</div>
      </div>

      <!-- Net Profit -->
      <div class="stat-card" id="stat-net-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">Net Profit</span>
        </div>
        <div class="stat-val">$ <?php echo number_format($profit_after_tax_22 / 1000000, 1); ?>M</div>
        <div class="stat-label">Comprehensive Income (<?php echo $year1; ?>)</div>
      </div>
    </div>

    <div class="year-filter-container" style="margin: 20px 0 0; display: flex; align-items: center; gap: 10px;">
      <label class="dropdown-label" for="yearSelect">Select Fiscal Year</label>
      <form method="get" style="display: inline-flex; align-items: center; gap: 8px;">
        <select id="yearSelect" name="year" onchange="this.form.submit()" style="padding: 8px 12px; border-radius: 8px; border: 1px solid var(--border); background: var(--card-bg); color: var(--text);">
          <?php foreach ($availableYears as $yr): ?>
            <option value="<?php echo $yr; ?>" <?php echo $yr === $selectedYear ? 'selected' : ''; ?>>Fiscal Year <?php echo $yr; ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">INEZA African Mining Ltd</div>
          <h3>Statement of profit or loss and Other Comprehensive Income</h3>
          <p>For the year ended 31 December <?php echo $year1; ?></p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="comprehensive-income-table">
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
              <tr class="accent-row">
                <td class="row-label">Revenue</td>
                <td class="num-note">1</td>
                <td class="num-val"><?php echo number_format($revenue_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($revenue_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($revenue_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Cost of sales</td>
                <td class="num-note">2</td>
                <td class="num-val"><?php echo number_format($cogs_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($cogs_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($cogs_20, 2); ?></td>
              </tr>
              <tr style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Gross Profit</td>
                <td class="num-note"></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($gross_profit_22, 2); ?></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($gross_profit_21, 2); ?></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($gross_profit_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Administrative expenses</td>
                <td class="num-note">3</td>
                <td class="num-val"><?php echo number_format($admin_expenses_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($admin_expenses_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($admin_expenses_20, 2); ?></td>
              </tr>
              <tr style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Operating Profit(loss)</td>
                <td class="num-note"></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($operating_profit_22, 2); ?></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($operating_profit_21, 2); ?></td>
                <td class="num-val" style="font-weight: 600;"><?php echo number_format($operating_profit_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Finance costs</td>
                <td class="num-note">4</td>
                <td class="num-val"><?php echo number_format($finance_costs_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($finance_costs_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($finance_costs_20, 2); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label">Profit (Loss) before tax</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($profit_before_tax_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($profit_before_tax_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($profit_before_tax_20, 2); ?></td>
              </tr>
              <tr>
                <td class="row-label">Income tax expenses</td>
                <td class="num-note">11</td>
                <td class="num-val"><?php echo number_format($tax_expenses_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($tax_expenses_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($tax_expenses_20, 2); ?></td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label">Profit (Loss) after tax</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($profit_after_tax_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($profit_after_tax_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($profit_after_tax_20, 2); ?></td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total comprehensive income</td>
                <td class="num-note"></td>
                <td class="num-val"><?php echo number_format($total_comprehensive_income_22, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_comprehensive_income_21, 2); ?></td>
                <td class="num-val"><?php echo number_format($total_comprehensive_income_20, 2); ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Comprehensive Income Statement\r\n\r\n";
        
        // Header Row
        csvContent += ",Notes,2022 ($),2021 ($),2020 ($)\r\n";

        const table = document.getElementById('comprehensive-income-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const label = row.querySelector('.row-label').textContent.trim().replace(/,/g, '');
            
            const noteCell = row.querySelector('.num-note');
            const note = noteCell ? noteCell.textContent.trim() : '';
            
            const cells = row.querySelectorAll('.num-val');
            
            let val2022 = cells[0].textContent.trim().replace(/,/g, '');
            let val2021 = cells[1].textContent.trim().replace(/,/g, '');
            let val2020 = cells[2].textContent.trim().replace(/,/g, '');

            csvContent += `"${label}",${note},${val2022},${val2021},${val2020}\r\n`;
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "ineza_comprehensive_income_statement.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
