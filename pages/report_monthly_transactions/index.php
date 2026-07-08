<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
$report_slug = 'monthly_transactions';
$report_title = 'Monthly Transactions';
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
$query = "SELECT DATE_FORMAT(je.entry_date, '%Y-%m') as tx_month,
            a.account_name, a.account_code,
            SUM(jel.debit) as total_debit, SUM(jel.credit) as total_credit
          FROM journal_entry_lines jel
          JOIN journal_entries je ON jel.journal_entry_id = je.id
          JOIN accounts a ON jel.account_id = a.id
          WHERE je.statuss = 'POSTED' {$date_cond}
          GROUP BY tx_month, a.id
          ORDER BY tx_month ASC, a.account_code ASC";
$db_res = mysqli_query($conn, $query);
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
        <div class="page-sub">INEZA African Mining Ltd — Monthly Trial Balance Transactions</div>
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
        <?php
        $excel_accounts = [
            4 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING USD'],
            5 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING RWF'],
            6 => ['active' => 'N', 'name' => 'EQUITY - INEZA AFRICAN MINING EURO'],
            7 => ['active' => 'N', 'name' => 'Accounts Receivables'],
            8 => ['active' => 'N', 'name' => 'Accounts Receivables - '],
            9 => ['active' => 'N', 'name' => 'Accounts Receivables - '],
            10 => ['active' => 'N', 'name' => 'Accounts Receivable - Others'],
            11 => ['active' => 'N', 'name' => 'Advances - Employees'],
            12 => ['active' => 'N', 'name' => 'Advances to Star Metal'],
            13 => ['active' => 'Y', 'name' => 'Advances to O/E - Pierre GATAMA'],
            14 => ['active' => 'N', 'name' => 'Advances to O/E - MUVUNYI DIEDONNE'],
            15 => ['active' => 'N', 'name' => 'Advances to O/E - Andrew KAYITARE'],
            16 => ['active' => 'N', 'name' => 'Advances to O/E - Daniel Makasi'],
            17 => ['active' => 'Y', 'name' => 'Advances to O/E - Charles MUNYANEZA'],
            18 => ['active' => 'N', 'name' => 'Advances to O/E - Olivier '],
            19 => ['active' => 'N', 'name' => 'Advances to O/E - Yury Ilin'],
            20 => ['active' => 'N', 'name' => 'Advances to O/E - Nsana Jean'],
            21 => ['active' => 'N', 'name' => 'Advances to O/E - MUSABYEYEZU Justine'],
            22 => ['active' => 'Y', 'name' => 'Advances to O/E - GEDEON'],
            24 => ['active' => 'N', 'name' => 'Advances - Suppliers'],
            25 => ['active' => 'N', 'name' => 'Advances to O/E - Murindwa Andre'],
            26 => ['active' => 'N', 'name' => 'Advances to O/E - Darius BIMENYIMANA'],
            27 => ['active' => 'N', 'name' => 'Advances to O/E - Murego Paulin'],
            28 => ['active' => 'N', 'name' => 'Advances to O/E - Jean Bosco YAMFASHIJE'],
            29 => ['active' => 'N', 'name' => 'Advances to O/E - Fidele BIZIMANA'],
            30 => ['active' => 'N', 'name' => 'Advances to O/E - Jeanne MUKAMUDENGE'],
            31 => ['active' => 'N', 'name' => 'Advances to O/E - Fanny MUKAMUGEMA'],
            32 => ['active' => 'N', 'name' => 'Advances to O/E - Richard AKAYEZU'],
            33 => ['active' => 'N', 'name' => 'Advances to O/E - Marc NSHIMYUMUREMYI'],
            34 => ['active' => 'N', 'name' => 'Advances to O/E - AHADI Furaha'],
            35 => ['active' => 'N', 'name' => 'Advances to O/E - Athanase MBARUBUKEYE'],
            36 => ['active' => 'N', 'name' => 'Advances to O/E - Michel NSHIMIYIMANA'],
            37 => ['active' => 'N', 'name' => 'Advances to O/E - Maman Innocent'],
            38 => ['active' => 'N', 'name' => 'Advances to O/E - Maestro HABUMUGISHA'],
            39 => ['active' => 'N', 'name' => 'Advances to O/E - Bertin RUTABINGWA'],
            40 => ['active' => 'N', 'name' => 'Advances - Cooperatives'],
            41 => ['active' => 'N', 'name' => 'Advances - Others'],
            42 => ['active' => 'N', 'name' => 'Prepayments'],
            43 => ['active' => 'N', 'name' => 'Prepaid Rent'],
            44 => ['active' => 'Y', 'name' => 'Rental Deposits'],
            45 => ['active' => 'Y', 'name' => 'Stocks - Tin'],
            46 => ['active' => 'N', 'name' => 'Stocks - Coltan'],
            47 => ['active' => 'N', 'name' => 'Stocks - Others'],
            48 => ['active' => 'N', 'name' => 'Asset Under Construction - '],
            50 => ['active' => 'N', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING USD'],
            51 => ['active' => 'Y', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING RWF'],
            52 => ['active' => 'N', 'name' => 'Due from EQUITY - INEZA AFRICAN MINING EURO'],
            53 => ['active' => 'N', 'name' => 'Land & Buildings - @ Cost'],
            54 => ['active' => 'N', 'name' => 'Land & Buildings - Accum Depre'],
            55 => ['active' => 'N', 'name' => 'Motor Vehicles - @ Cost'],
            56 => ['active' => 'N', 'name' => 'Motor Vehicles - Accum Depre'],
            57 => ['active' => 'N', 'name' => 'Computer Equipment - @ Cost'],
            58 => ['active' => 'N', 'name' => 'Computer Equipment - Accum Depre'],
            59 => ['active' => 'N', 'name' => 'Office Equipment - @ Cost'],
            60 => ['active' => 'N', 'name' => 'Office Equipment - Accum Depre'],
            61 => ['active' => 'N', 'name' => 'Furniture & Fittings - @ Cost'],
            62 => ['active' => 'N', 'name' => 'Furniture & Fittings - Accum Depre'],
            63 => ['active' => 'N', 'name' => 'Other Fixed Assets - @ Cost'],
            64 => ['active' => 'N', 'name' => 'Other Fixed Assets - Accum Depre'],
            65 => ['active' => 'N', 'name' => 'Leasehold Improvements'],
            66 => ['active' => 'N', 'name' => 'Leasehold Improvements - Amortization'],
            67 => ['active' => 'Y', 'name' => 'Mineral Processing Equipment - @ Cost'],
            68 => ['active' => 'N', 'name' => 'Mineral Processing Equipment - Accum Depre'],
            69 => ['active' => 'N', 'name' => 'Goodwill / Intangible Assets'],
            70 => ['active' => 'Y', 'name' => 'Investments'],
            72 => ['active' => 'N', 'name' => 'Accounts Payable'],
            73 => ['active' => 'N', 'name' => 'Accounts Payable - Others'],
            74 => ['active' => 'N', 'name' => 'Accrued Liabilities'],
            75 => ['active' => 'N', 'name' => 'Advances from METALEKSPO SIA'],
            76 => ['active' => 'Y', 'name' => 'Advances from Star Metal Company'],
            77 => ['active' => 'N', 'name' => 'Advances from MUREGO AND GATAMA'],
            78 => ['active' => 'N', 'name' => 'Advances from Partner'],
            79 => ['active' => 'N', 'name' => 'Salaries Payable'],
            80 => ['active' => 'N', 'name' => 'Consultancy Fee Payable'],
            81 => ['active' => 'N', 'name' => 'Rent Payable'],
            82 => ['active' => 'N', 'name' => 'Commission Payable'],
            83 => ['active' => 'N', 'name' => 'Severance Payable'],
            84 => ['active' => 'N', 'name' => 'Payroll Tax Payable'],
            85 => ['active' => 'N', 'name' => 'InCome Tax Payable'],
            86 => ['active' => 'N', 'name' => 'Long Term Liabilities'],
            87 => ['active' => 'N', 'name' => 'Investment from Star Metal'],
            88 => ['active' => 'N', 'name' => 'Investment from XY'],
            89 => ['active' => 'N', 'name' => 'Common shares'],
            90 => ['active' => 'N', 'name' => 'Loans Payable - '],
            91 => ['active' => 'Y', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING USD'],
            92 => ['active' => 'N', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING RWF'],
            93 => ['active' => 'N', 'name' => 'Due to EQUITY - INEZA AFRICAN MINING EURO'],
            94 => ['active' => 'N', 'name' => 'Member\'s Equity'],
            95 => ['active' => 'N', 'name' => 'Retained Income / (Accumulated Loss)'],
            96 => ['active' => 'N', 'name' => 'Prior Period Adjustment'],
            98 => ['active' => 'N', 'name' => 'Sales'],
            100 => ['active' => 'N', 'name' => 'Export Costs'],
            101 => ['active' => 'N', 'name' => 'Export Packaging'],
            102 => ['active' => 'N', 'name' => 'Export Taxes'],
            103 => ['active' => 'N', 'name' => 'Cost of Sales - Minerals Transport, Taxes & Tags'],
            104 => ['active' => 'N', 'name' => 'Cost of Sales - Travel & Transport'],
            105 => ['active' => 'N', 'name' => 'Cost of Sales - Salaries & Wages'],
            106 => ['active' => 'N', 'name' => 'Cost of Sales - Others'],
            107 => ['active' => 'N', 'name' => 'Sample Costs'],
            108 => ['active' => 'N', 'name' => 'Cooperative Fees'],
            110 => ['active' => 'N', 'name' => 'Advertising & Promotions'],
            111 => ['active' => 'N', 'name' => 'Amortization'],
            112 => ['active' => 'N', 'name' => 'Bad Debts'],
            113 => ['active' => 'Y', 'name' => 'Bank Charges'],
            114 => ['active' => 'N', 'name' => 'Cleaning & Hygiene'],
            115 => ['active' => 'N', 'name' => 'Computer Supplies & Others'],
            116 => ['active' => 'N', 'name' => 'Consulting Fees'],
            117 => ['active' => 'N', 'name' => 'Courier & Postage'],
            118 => ['active' => 'N', 'name' => 'Commission fees'],
            119 => ['active' => 'N', 'name' => 'Custom Duties & Taxes'],
            120 => ['active' => 'N', 'name' => 'Depreciation'],
            121 => ['active' => 'N', 'name' => 'Donations'],
            122 => ['active' => 'Y', 'name' => 'Electricity & Water'],
            123 => ['active' => 'N', 'name' => 'Insurance Expense'],
            124 => ['active' => 'N', 'name' => 'Legal Fees'],
            125 => ['active' => 'N', 'name' => 'Machinery Hire'],
            126 => ['active' => 'N', 'name' => 'Exploration and Evaluation Cost'],
            127 => ['active' => 'N', 'name' => 'Medical Costs'],
            128 => ['active' => 'N', 'name' => 'Membership Fees & Dues'],
            129 => ['active' => 'Y', 'name' => 'Motor Vehicle Expenses'],
            130 => ['active' => 'N', 'name' => 'Office Supplies'],
            131 => ['active' => 'N', 'name' => 'Other Consumables'],
            132 => ['active' => 'N', 'name' => 'Pft/Loss on Foreign Exchange'],
            133 => ['active' => 'N', 'name' => 'Professional Fees'],
            134 => ['active' => 'Y', 'name' => 'Rent Expense'],
            135 => ['active' => 'N', 'name' => 'Rent Site'],
            136 => ['active' => 'Y', 'name' => 'Repairs & Maintenance'],
            137 => ['active' => 'N', 'name' => 'Representation & Entertainment'],
            138 => ['active' => 'N', 'name' => 'Safety'],
            139 => ['active' => 'N', 'name' => 'Salaries'],
            140 => ['active' => 'Y', 'name' => 'Salary - Others'],
            141 => ['active' => 'N', 'name' => 'Software License'],
            142 => ['active' => 'N', 'name' => 'Management Fee'],
            143 => ['active' => 'N', 'name' => 'Security'],
            144 => ['active' => 'N', 'name' => 'Staff Training'],
            145 => ['active' => 'Y', 'name' => 'Staff Welfare'],
            146 => ['active' => 'N', 'name' => 'Staff Welfare Site'],
            147 => ['active' => 'N', 'name' => 'Taxes, Permits & Licenses'],
            148 => ['active' => 'N', 'name' => 'Concession License Fee'],
            149 => ['active' => 'N', 'name' => 'Telecommunications'],
            150 => ['active' => 'Y', 'name' => 'Transport'],
            151 => ['active' => 'Y', 'name' => 'Travel & Accommodation'],
            152 => ['active' => 'N', 'name' => 'Visa Fees'],
            153 => ['active' => 'N', 'name' => 'Sundry Account'],
            154 => ['active' => 'Y', 'name' => 'Miscellaneous Expense'],
            156 => ['active' => 'N', 'name' => 'Interest Income'],
            157 => ['active' => 'N', 'name' => 'Interest Expense'],
            158 => ['active' => 'N', 'name' => 'Bad Debts Recovered'],
            159 => ['active' => 'N', 'name' => 'Pft/Loss on Sale of Non Current Assets'],
            160 => ['active' => 'N', 'name' => 'Other Income'],
            161 => ['active' => 'N', 'name' => 'Other Loss'],
            162 => ['active' => 'N', 'name' => 'Provision for Income Tax'],
            164 => ['active' => 'Y', 'name' => 'Total Cash-Out Net of Cash-In'],
            168 => ['active' => 'Y', 'name' => 'Petty Cash Fund - INEZA'],
            169 => ['active' => 'N', 'name' => 'Funds to Sites - Rubaya'],
            171 => ['active' => '', 'name' => 'Equity $ Withdrawals'],
            172 => ['active' => '', 'name' => 'Petty Cash Payments'],
            174 => ['active' => '', 'name' => 'Monthly Exp -2025'],
            175 => ['active' => '', 'name' => 'Monthly Exp -2026'],
            176 => ['active' => '', 'name' => 'Petty Cash Fund - 2025'],
            177 => ['active' => '', 'name' => 'Petty Cash Fund - 2026'],
            178 => ['active' => '', 'name' => 'Equity $ Deposits'],
        ];

        $translation = [
            'Stocks - Tin' => 'Stocks - Tin',
            'Stocks - Coltan' => 'Stocks - Coltan',
            'Stocks - Tantalum' => 'Stocks - Tin',
            'Sales - Tin' => 'Sales',
            'Sales - Coltan' => 'Sales',
            'Sales - Tantalum' => 'Sales',
            'Trade Receivables' => 'Accounts Receivables',
            'Long Term Loans' => 'Long Term Liabilities',
            'Eugene ndayishimiye - Accounts Payable' => 'Accounts Payable',
            'EQUITY US$ ACCOUNT' => 'EQUITY - INEZA AFRICAN MINING USD',
            'Investments' => 'Investments',
            'Bank Charges' => 'Bank Charges',
            'Petty Cash Fund - INEZA' => 'Petty Cash Fund - INEZA',
            'Due from EQUITY - INEZA AFRICAN MINING RWF' => 'Due from EQUITY - INEZA AFRICAN MINING RWF',
            'Advances from Star Metal Company' => 'Advances from Star Metal Company',
            'Funds to Sites - Rubaya' => 'Funds to Sites - Rubaya',
            'Advances to O/E - Charles MUNYANEZA' => 'Advances to O/E - Charles MUNYANEZA',
            'Travel & Accommodation' => 'Travel & Accommodation',
            'Staff Welfare' => 'Staff Welfare',
            'Transport' => 'Transport',
            'Miscellaneous Expense' => 'Miscellaneous Expense',
            'Advances to O/E - GEDEON' => 'Advances to O/E - GEDEON'
        ];

        $month_cols = ['2025-05', '2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12'];

        $matrix = [];
        foreach ($excel_accounts as $rowId => $acc) {
            $matrix[$rowId] = [
                'active' => $acc['active'],
                'name' => $acc['name'],
                'months' => array_fill_keys($month_cols, 0.0),
                'total' => 0.0
            ];
        }

        $name_to_row_ids = [];
        foreach ($excel_accounts as $rowId => $acc) {
            $name_to_row_ids[strtolower(trim($acc['name']))][] = $rowId;
        }

        // Process query results
        if ($db_res) {
            while ($db_row = mysqli_fetch_assoc($db_res)) {
                $db_name = $db_row['account_name'];
                $m = $db_row['tx_month'];
                $debit = (float)$db_row['total_debit'];
                $credit = (float)$db_row['total_credit'];
                $net = $debit - $credit;
                
                $target_name = $translation[$db_name] ?? $db_name;
                $key = strtolower(trim($target_name));
                
                if (isset($name_to_row_ids[$key])) {
                    foreach ($name_to_row_ids[$key] as $rowId) {
                        if (isset($matrix[$rowId]['months'][$m])) {
                            $matrix[$rowId]['months'][$m] += $net;
                        }
                    }
                }
            }
        }

        // Calculate totals
        $column_totals = array_fill_keys($month_cols, 0.0);
        $grand_total = 0.0;

        foreach ($matrix as $rowId => &$row_data) {
            $row_total = 0.0;
            foreach ($row_data['months'] as $m => $val) {
                $row_total += $val;
                $column_totals[$m] += $val;
            }
            $row_data['total'] = $row_total;
            $grand_total += $row_total;
        }
        unset($row_data);
        ?>
        <?php if ($grand_total == 0.0): ?>
          <div style="padding: 12px 16px; margin: 12px; background: var(--alert-amber-bg); border: 1px solid var(--amber); border-radius: 6px; color: var(--amber); font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px;">
            <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            No posted journal entries found for the selected dates. All account balances are shown as zero.
          </div>
        <?php endif; ?>
        <table class="excel-table" id="reportTable">
          <thead>
            <tr style="background: var(--bg); font-weight: 600;">
              <th>Y/N</th>
              <th>Account Name</th>
              <?php foreach ($month_cols as $m): ?>
                <th><?php echo $m . '-01'; ?></th>
              <?php endforeach; ?>
              <th>TOTAL</th>
              <th>GRAND TOTAL</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($matrix as $rowId => $row_data): ?>
              <tr class="data-row">
                <td style="text-align: center;"><?php echo htmlspecialchars($row_data['active']); ?></td>
                <td><?php echo htmlspecialchars($row_data['name']); ?></td>
                <?php foreach ($month_cols as $m): ?>
                  <td style="text-align: right;"><?php echo $row_data['months'][$m] != 0 ? '$' . number_format($row_data['months'][$m], 2) : '0'; ?></td>
                <?php endforeach; ?>
                <td style="text-align: right; font-weight: 600;"><?php echo '$' . number_format($row_data['total'], 2); ?></td>
                <td style="text-align: right; font-weight: 600;"><?php echo '$' . number_format($row_data['total'], 2); ?></td>
              </tr>
            <?php endforeach; ?>
            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="2" style="text-align: right;">Total:</td>
              <?php foreach ($month_cols as $m): ?>
                <td style="text-align: right;"><?php echo '$' . number_format($column_totals[$m], 2); ?></td>
              <?php endforeach; ?>
              <td style="text-align: right;"><?php echo '$' . number_format($grand_total, 2); ?></td>
              <td style="text-align: right;"><?php echo '$' . number_format($grand_total, 2); ?></td>
            </tr>
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
</body>
</html>
