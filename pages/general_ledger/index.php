<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Permission Check
if (!hasPermission($conn, $userId, 'view_general_ledger')) {
    header("Location: ../dashboard");
    exit();
}

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$startDateEsc = mysqli_real_escape_string($conn, $startDate);
$endDateEsc = mysqli_real_escape_string($conn, $endDate);

// Fetch accounts with their type details
$accountsQuery = "
    SELECT a.id, a.account_code, a.account_name, t.name as type_name, t.code as type_code, t.parent_id as parent_type_id
    FROM accounts a
    JOIN account_types t ON a.account_type_id = t.id
    ORDER BY a.account_code ASC";
$accountsRes = mysqli_query($conn, $accountsQuery);
$ledgerData = [];

$totalOpening = 0.0;
$totalDebitChange = 0.0;
$totalCreditChange = 0.0;
$totalClosing = 0.0;

if ($accountsRes) {
    while ($row = mysqli_fetch_assoc($accountsRes)) {
        $accId = (int)$row['id'];
        $parentType = (int)$row['parent_type_id'];
        
        // Debit normal accounts: Assets (-1), Cost of Sales (-5), Expenses (-6)
        // Credit normal accounts: Liabilities (-2), Equity (-3), Revenue (-4)
        $isDebitNormal = in_array($parentType, [-1, -5, -6]) || (int)$row['type_code'] < 2000 || (int)$row['type_code'] >= 5000;

        // Query Opening Balance (before $startDate)
        $openQuery = "
            SELECT SUM(jel.debit) as debits, SUM(jel.credit) as credits 
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = $accId AND je.statuss = 'POSTED' AND je.entry_date < '$startDateEsc'";
        $openRes = mysqli_query($conn, $openQuery);
        $openRow = mysqli_fetch_assoc($openRes);
        $openDebits = (float)($openRow['debits'] ?? 0.0);
        $openCredits = (float)($openRow['credits'] ?? 0.0);

        if ($isDebitNormal) {
            $openingBalance = $openDebits - $openCredits;
        } else {
            $openingBalance = $openCredits - $openDebits;
        }

        // Query Changes (during range)
        $changeQuery = "
            SELECT SUM(jel.debit) as debits, SUM(jel.credit) as credits 
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = $accId AND je.statuss = 'POSTED' AND je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc'";
        $changeRes = mysqli_query($conn, $changeQuery);
        $changeRow = mysqli_fetch_assoc($changeRes);
        $debitChange = (float)($changeRow['debits'] ?? 0.0);
        $creditChange = (float)($changeRow['credits'] ?? 0.0);

        // Compute Closing
        if ($isDebitNormal) {
            $closingBalance = $openingBalance + $debitChange - $creditChange;
        } else {
            $closingBalance = $openingBalance + $creditChange - $debitChange;
        }

        $ledgerData[] = [
            'id' => $accId,
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'type_name' => $row['type_name'],
            'is_debit_normal' => $isDebitNormal,
            'opening_balance' => $openingBalance,
            'debit_change' => $debitChange,
            'credit_change' => $creditChange,
            'closing_balance' => $closingBalance
        ];

        $totalOpening += $openingBalance;
        $totalDebitChange += $debitChange;
        $totalCreditChange += $creditChange;
        $totalClosing += $closingBalance;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — General Ledger Report</title>
<meta name="description" content="View general ledger opening balances, transaction movements, and ending balances.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/general_ledger.css">
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

  <?php $page_title = "General Ledger Report"; include '../include/navbar.php'; ?>

  <div class="content" id="ledgerContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5v-15z"/></svg>
          General Ledger Summaries
        </h1>
        <div class="page-sub">Yearly ledger statement accounts balance details</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="exportCsvBtn">
          Export CSV
        </button>
        <button class="btn-sm btn-primary" onclick="window.print()">
          Print Report
        </button>
      </div>
    </div>

    <!-- ===== FILTER PANEL ===== -->
    <div class="ledger-container">
      <div class="ledger-filters">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%; align-items: flex-end;">
          <div class="form-group" style="flex: 1; min-width: 160px;">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
          </div>
          <div class="form-group" style="flex: 1; min-width: 160px;">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
          </div>
          <div class="form-group" style="flex: 1.5; min-width: 200px;">
            <label for="searchInput">Search Account</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Search code or name...">
          </div>
          <button type="submit" class="btn-sm btn-primary" style="padding: 8px 16px; font-size: 13px;">Filter</button>
        </form>
      </div>

      <!-- ===== LEDGER CARD ===== -->
      <div class="ledger-card">
        <div class="ledger-card-header">
          <div class="ledger-card-title">Chart Account Balances</div>
          <div style="font-size: 12.5px; color: var(--text2);">Reporting range: <strong><?php echo htmlspecialchars($startDate); ?></strong> to <strong><?php echo htmlspecialchars($endDate); ?></strong></div>
        </div>

        <div class="note-table-wrapper">
          <table class="ledger-table" id="ledgerTable">
            <thead>
              <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th>Account Type</th>
                <th class="num-val">Opening Balance</th>
                <th class="num-val">Debit Change</th>
                <th class="num-val">Credit Change</th>
                <th class="num-val">Closing Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($ledgerData) === 0): ?>
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--text2); padding: 24px;">No ledger accounts configured.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($ledgerData as $acc): ?>
                  <tr class="ledger-row">
                    <td style="font-family: monospace; font-weight: 500;" class="acc-code"><?php echo htmlspecialchars($acc['account_code']); ?></td>
                    <td style="font-weight: 500;" class="acc-name"><?php echo htmlspecialchars($acc['account_name']); ?></td>
                    <td style="color: var(--text2);"><?php echo htmlspecialchars($acc['type_name']); ?></td>
                    <td class="num-val"><?php echo number_format($acc['opening_balance'], 2); ?></td>
                    <td class="num-val" style="color: var(--blue);"><?php echo $acc['debit_change'] > 0 ? number_format($acc['debit_change'], 2) : '-'; ?></td>
                    <td class="num-val" style="color: var(--orange);"><?php echo $acc['credit_change'] > 0 ? number_format($acc['credit_change'], 2) : '-'; ?></td>
                    <td class="num-val" style="font-weight: 600;"><?php echo number_format($acc['closing_balance'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
                
                <!-- Grand Totals -->
                <tr class="total-row">
                  <td colspan="3" style="text-align: right; padding-right: 18px; color: var(--text);">Totals Volume</td>
                  <td class="num-val"><?php echo number_format($totalOpening, 2); ?></td>
                  <td class="num-val"><?php echo number_format($totalDebitChange, 2); ?></td>
                  <td class="num-val"><?php echo number_format($totalCreditChange, 2); ?></td>
                  <td class="num-val"><?php echo number_format($totalClosing, 2); ?></td>
                </tr>
              <?php endif; ?>
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
    const searchInput = document.getElementById('searchInput');
    const rows = document.querySelectorAll('.ledger-row');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const code = row.querySelector('.acc-code').textContent.toLowerCase();
            const name = row.querySelector('.acc-name').textContent.toLowerCase();
            if (query === '' || code.includes(query) || name.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // CSV Exporter
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - General Ledger Report\r\n";
        csvContent += "Reporting Range: <?php echo $startDate; ?> to <?php echo $endDate; ?>\r\n\r\n";
        csvContent += "Account Code,Account Name,Account Type,Opening Balance (Frw),Debit Change (Frw),Credit Change (Frw),Closing Balance (Frw)\r\n";

        const tableRows = document.querySelectorAll('#ledgerTable tbody tr');
        tableRows.forEach(row => {
            if (row.classList.contains('total-row')) {
                const cells = row.querySelectorAll('td');
                csvContent += `"Totals Volume",,,${cells[1].textContent.replace(/,/g, '')},${cells[2].textContent.replace(/,/g, '')},${cells[3].textContent.replace(/,/g, '')},${cells[4].textContent.replace(/,/g, '')}\r\n`;
            } else {
                const code = row.querySelector('.acc-code').textContent.trim();
                const name = row.querySelector('.acc-name').textContent.trim().replace(/,/g, '');
                const type = row.querySelectorAll('td')[2].textContent.trim();
                const cells = row.querySelectorAll('.num-val');
                
                const opening = cells[0].textContent.replace(/,/g, '').replace(/-/g, '0');
                const debits = cells[1].textContent.replace(/,/g, '').replace(/-/g, '0');
                const credits = cells[2].textContent.replace(/,/g, '').replace(/-/g, '0');
                const closing = cells[3].textContent.replace(/,/g, '').replace(/-/g, '0');

                csvContent += `"${code}","${name}","${type}",${opening},${debits},${credits},${closing}\r\n`;
            }
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "general_ledger_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
</body>
</html>
