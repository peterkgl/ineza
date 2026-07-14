<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Security access check using standard view_note permission
if (!hasPermission($conn, $userId, 'view_note')) {
    header("Location: ../dashboard");
    exit();
}

// Helper to fetch distinct years with posted journal entries
function getAvailableYears($conn) {
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
    return $availableYears;
}

// Dynamic helper to calculate balance of a list of accounts or account types
function getNoteAccountBalances($conn, $options, $year, $isBS = true, $isDebitNormal = true) {
    $date = "$year-12-31";
    $yearStart = "$year-01-01";
    
    $whereClauses = [];
    $orClauses = [];
    
    if (!empty($options['codes'])) {
        $codesStr = "'" . implode("','", array_map(function($c) use ($conn) { return mysqli_real_escape_string($conn, $c); }, $options['codes'])) . "'";
        $orClauses[] = "a.account_code IN ($codesStr)";
    }
    if (isset($options['code_prefix'])) {
        $prefix = mysqli_real_escape_string($conn, $options['code_prefix']);
        $orClauses[] = "a.account_code LIKE '$prefix%'";
    }
    if (isset($options['parent_type_id'])) {
        $parentTypeId = (int)$options['parent_type_id'];
        $orClauses[] = "t.parent_id = $parentTypeId";
    }
    if (isset($options['type_codes'])) {
        $typeStr = "'" . implode("','", array_map(function($c) use ($conn) { return mysqli_real_escape_string($conn, $c); }, $options['type_codes'])) . "'";
        $orClauses[] = "t.code IN ($typeStr)";
    }
    if (isset($options['type_ids'])) {
        $typeIdsStr = implode(",", array_map('intval', $options['type_ids']));
        $orClauses[] = "a.account_type_id IN ($typeIdsStr)";
    }

    if (!empty($orClauses)) {
        $whereClauses[] = "(" . implode(" OR ", $orClauses) . ")";
    }
    
    if (!empty($options['exclude_codes'])) {
        $excStr = "'" . implode("','", array_map(function($c) use ($conn) { return mysqli_real_escape_string($conn, $c); }, $options['exclude_codes'])) . "'";
        $whereClauses[] = "a.account_code NOT IN ($excStr)";
    }
    if (!empty($options['exclude_type_codes'])) {
        $excTypeStr = "'" . implode("','", array_map(function($c) use ($conn) { return mysqli_real_escape_string($conn, $c); }, $options['exclude_type_codes'])) . "'";
        $whereClauses[] = "t.code NOT IN ($excTypeStr)";
    }
    if (!empty($options['exclude_type_ids'])) {
        $excTypeIdsStr = implode(",", array_map('intval', $options['exclude_type_ids']));
        $whereClauses[] = "a.account_type_id NOT IN ($excTypeIdsStr)";
    }

    $whereSql = "";
    if (!empty($whereClauses)) {
        $whereSql = " AND " . implode(" AND ", $whereClauses);
    }
    
    if ($isBS) {
        $dateCond = "je.entry_date <= '$date'";
    } else {
        $dateCond = "je.entry_date >= '$yearStart' AND je.entry_date <= '$date'";
    }
    
    $sql = "
        SELECT a.id, a.account_code, a.account_name, t.code as type_code, t.parent_id as parent_type_id,
               COALESCE(SUM(CASE WHEN $dateCond THEN jel.debit ELSE 0.00 END), 0.00) as total_debit,
               COALESCE(SUM(CASE WHEN $dateCond THEN jel.credit ELSE 0.00 END), 0.00) as total_credit
        FROM accounts a
        JOIN account_types t ON a.account_type_id = t.id
        LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.statuss = 'POSTED'
        WHERE a.is_active = 1 $whereSql
        GROUP BY a.id
        ORDER BY a.account_code ASC";
        
    $res = mysqli_query($conn, $sql);
    $accountsData = [];
    
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $deb = (float)$row['total_debit'];
            $cred = (float)$row['total_credit'];
            $bal = $isDebitNormal ? ($deb - $cred) : ($cred - $deb);
            
            $accountsData[] = [
                'code' => $row['account_code'],
                'name' => $row['account_name'],
                'balance' => $bal
            ];
        }
    }
    return $accountsData;
}

// Function to fetch and merge dynamic years data for a list of accounts
function getMergedNoteData($conn, $options, $years, $isBS = true, $isDebitNormal = true) {
    $accountsMap = [];
    
    foreach ($years as $yr) {
        $balances = getNoteAccountBalances($conn, $options, $yr, $isBS, $isDebitNormal);
        foreach ($balances as $acc) {
            $code = $acc['code'];
            if (!isset($accountsMap[$code])) {
                $accountsMap[$code] = [
                    'code' => $code,
                    'name' => $acc['name'],
                    'balances' => []
                ];
            }
            $accountsMap[$code]['balances'][$yr] = $acc['balance'];
        }
    }
    
    // Filter out accounts where the balance is 0.0 in all years
    $filtered = [];
    foreach ($accountsMap as $code => $acc) {
        $hasValue = false;
        foreach ($years as $yr) {
            if (isset($acc['balances'][$yr]) && abs($acc['balances'][$yr]) > 0.01) {
                $hasValue = true;
                break;
            }
        }
        if ($hasValue) {
            $filtered[] = $acc;
        }
    }
    
    return $filtered;
}

// Function to render a note card table with dynamic years
function renderNoteCard($noteNum, $noteTitle, $accounts, $years) {
    $totals = [];
    foreach ($years as $yr) {
        $totals[$yr] = 0.0;
    }
    
    foreach ($accounts as $acc) {
        foreach ($years as $yr) {
            $totals[$yr] += $acc['balances'][$yr] ?? 0.0;
        }
    }
    
    $formatVal = function($val) {
        if ($val === null || abs($val) < 0.01) {
            return '-';
        }
        if ($val < 0) {
            return '(' . number_format(abs($val), 0) . ')';
        }
        return number_format($val, 0);
    };
    ?>
    <div class="note-card">
      <div class="note-table-wrapper">
        <table class="note-table">
          <thead>
            <tr class="note-header-row">
              <td colspan="<?php echo count($years) + 1; ?>"><span class="note-num"><?php echo $noteNum; ?></span> <?php echo htmlspecialchars($noteTitle); ?></td>
            </tr>
            <tr class="year-header-row">
              <th></th>
              <?php foreach ($years as $yr): ?>
                <th><?php echo $yr; ?></th>
              <?php endforeach; ?>
            </tr>
            <tr class="currency-header-row">
              <th></th>
              <?php foreach ($years as $yr): ?>
                <th>Frw</th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($accounts)): ?>
              <tr>
                <td class="row-label">No active balances</td>
                <?php foreach ($years as $yr): ?>
                  <td class="num-val">-</td>
                <?php endforeach; ?>
              </tr>
            <?php else: ?>
              <?php foreach ($accounts as $acc): ?>
                <tr>
                  <td class="row-label"><?php echo htmlspecialchars($acc['name']); ?></td>
                  <?php foreach ($years as $yr): ?>
                    <td class="num-val"><?php echo $formatVal($acc['balances'][$yr] ?? 0.0); ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            <tr class="total-row">
              <td class="row-label">Total</td>
              <?php foreach ($years as $yr): ?>
                <td class="num-val"><?php echo $formatVal($totals[$yr]); ?></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <?php
    return $totals;
}

// Year filter setup
$availableYears = getAvailableYears($conn);
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $availableYears[0];
if (!in_array($selectedYear, $availableYears)) {
    $availableYears[] = $selectedYear;
    rsort($availableYears);
}

// Display all actual years from database in the tables
$years = $availableYears;

// Fetch and merge note data from database (no hardcoded fallbacks!)
// Note 1: Revenue
$note1_data = getMergedNoteData($conn, ['parent_type_id' => -4], $years, false, false);

// Note 2: Cost of sales
$note2_data = getMergedNoteData($conn, ['parent_type_id' => -5], $years, false, true);

// Note 3: Administrative expenses
$note3_data = getMergedNoteData($conn, ['parent_type_id' => -6, 'exclude_type_codes' => ['6013']], $years, false, true);

// Note 4: Finance costs
$note4_data = getMergedNoteData($conn, ['type_codes' => ['6013']], $years, false, true);

// Note 5: Cash and cash equivalents
$note5_data = getMergedNoteData($conn, ['type_ids' => [1], 'codes' => ['1010']], $years, true, true);

// Note 6: Accounts receivables
$note6_data = getMergedNoteData($conn, ['codes' => ['1100'], 'type_ids' => [2, 6]], $years, true, true);

// Note 7: Inventory
$note7_data = getMergedNoteData($conn, ['codes' => ['1300', '1024', '1034', '1044'], 'type_ids' => [4]], $years, true, true);

// Note 9: Accounts payables / Other current liabilities
$note9_pay_balances = [];
$note9_oth_balances = [];
foreach ($years as $yr) {
    $pay = getNoteAccountBalances($conn, ['codes' => ['2001'], 'type_ids' => [15, 3]], $yr, true, false);
    $note9_pay_balances[$yr] = array_sum(array_column($pay, 'balance'));
    
    $oth = getNoteAccountBalances($conn, ['codes' => ['2100'], 'type_ids' => [16, 17, 19, 20, 21, 23, 25, 26, 27]], $yr, true, false);
    $note9_oth_balances[$yr] = array_sum(array_column($oth, 'balance'));
}

$note9_data = [];
$hasPay = false;
$hasOth = false;
foreach ($years as $yr) {
    if (abs($note9_pay_balances[$yr]) > 0.01) $hasPay = true;
    if (abs($note9_oth_balances[$yr]) > 0.01) $hasOth = true;
}
if ($hasPay) {
    $note9_data[] = [
        'code' => '2001',
        'name' => 'Accounts payables',
        'balances' => $note9_pay_balances
    ];
}
if ($hasOth) {
    $note9_data[] = [
        'code' => '2100',
        'name' => 'Other current liabilities',
        'balances' => $note9_oth_balances
    ];
}

// Note 10: Long term loans
$note10_data = getMergedNoteData($conn, ['codes' => ['2200'], 'type_ids' => [22]], $years, true, false);

// Note 11: Current Tax Payable
$note11_data = getMergedNoteData($conn, ['codes' => ['2400'], 'type_ids' => [18]], $years, true, false);

// Calculate Stat Cards totals for the selected year
$totalRevenueY1 = 0.0;
foreach ($note1_data as $acc) {
    $totalRevenueY1 += $acc['balances'][$selectedYear] ?? 0.0;
}

$totalCogsY1 = 0.0;
foreach ($note2_data as $acc) {
    $totalCogsY1 += $acc['balances'][$selectedYear] ?? 0.0;
}

$totalAdminY1 = 0.0;
foreach ($note3_data as $acc) {
    $totalAdminY1 += $acc['balances'][$selectedYear] ?? 0.0;
}

$totalFinanceY1 = 0.0;
foreach ($note4_data as $acc) {
    $totalFinanceY1 += $acc['balances'][$selectedYear] ?? 0.0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Notes to the Financial Statements</title>
<meta name="description" content="Notes to the Financial Statements for DETROIT CROWNED GOLDEN BUSINESS 'CGB' Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/note.css">
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

  <?php $page_title = "Notes to Statements"; include '../include/navbar.php'; ?>

  <div class="content" id="noteContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Notes to the Financial Statements
        </h1>
        <div class="page-sub">DETROIT CROWNED GOLDEN BUSINESS "CGB" Ltd — Year Ended 31st December <?php echo $selectedYear; ?></div>
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

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Total Revenue (Note 1) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">Note 1</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalRevenueY1 / 1000000, 1); ?>M</div>
        <div class="stat-label">Sales Revenue (<?php echo $selectedYear; ?>)</div>
      </div>

      <!-- Cost of Sales (Note 2) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-orange">Note 2</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalCogsY1 / 1000000, 1); ?>M</div>
        <div class="stat-label">Cost of Goods Sold (<?php echo $selectedYear; ?>)</div>
      </div>

      <!-- Administrative Expenses (Note 3) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <span class="stat-trend trend-orange">Note 3</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalAdminY1 / 1000000, 1); ?>M</div>
        <div class="stat-label">Total Admin Expenses</div>
      </div>

      <!-- Finance Costs (Note 4) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-blue">Note 4</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalFinanceY1 / 1000000, 1); ?>M</div>
        <div class="stat-label">Total Finance Costs (<?php echo $selectedYear; ?>)</div>
      </div>
    </div>


    <!-- ===== NOTES STACK ===== -->
    <div class="note-container">
      <?php
      renderNoteCard('1', 'Revenue', $note1_data, $years);
      renderNoteCard('2', 'Cost of sales', $note2_data, $years);
      renderNoteCard('3', 'Administrative expenses', $note3_data, $years);
      renderNoteCard('4', 'Finance costs', $note4_data, $years);
      renderNoteCard('5', 'Cash and cash equivalents', $note5_data, $years);
      renderNoteCard('6', 'Accounts receivables', $note6_data, $years);
      renderNoteCard('7', 'Inventory', $note7_data, $years);
      renderNoteCard('9', 'Accounts payables / Other current liabilities', $note9_data, $years);
      renderNoteCard('10', 'Long term loans and other LT liabilities', $note10_data, $years);
      renderNoteCard('11', 'Current Tax Payable', $note11_data, $years);
      ?>
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
        csvContent += "DETROIT CROWNED GOLDEN BUSINESS \"CGB\" Ltd - Notes to the Financial Statements\r\n\r\n";
        
        const tables = document.querySelectorAll('.note-table');
        tables.forEach(table => {
            const noteHeader = table.querySelector('.note-header-row td').textContent.trim().replace(/,/g, '');
            // Create commas dynamically based on the number of years
            const commas = ",".repeat(<?php echo count($years); ?>);
            csvContent += `"${noteHeader}"${commas}\r\n`;
            csvContent += `,<?php echo implode(' (Frw),', $years); ?> (Frw)\r\n`;
            
            const rows = table.querySelectorAll('tbody tr:not(.note-header-row):not(.year-header-row):not(.currency-header-row)');
            rows.forEach(row => {
                const labelCell = row.querySelector('.row-label');
                if (!labelCell) return;
                const label = labelCell.textContent.trim().replace(/,/g, '');
                
                const cells = row.querySelectorAll('.num-val');
                let cellVals = [];
                cells.forEach(cell => {
                    cellVals.push(cell.textContent.trim().replace(/,/g, ''));
                });
                
                csvContent += `"${label}",${cellVals.join(',')}\r\n`;
            });
            csvContent += "\r\n"; // Blank line between notes
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "cgb_financial_notes_report_<?php echo $selectedYear; ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
