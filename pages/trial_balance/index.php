<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Permission Check
if (!hasPermission($conn, $userId, 'view_trial_balance')) {
    header("Location: ../dashboard");
    exit();
}

$endDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$endDateEsc = mysqli_real_escape_string($conn, $endDate);

// Fetch ledger totals per account up to date
$query = "
    SELECT a.id, a.account_code, a.account_name, t.code as type_code, t.parent_id as parent_type_id,
           COALESCE(SUM(jel.debit), 0.00) as total_debit,
           COALESCE(SUM(jel.credit), 0.00) as total_credit
    FROM accounts a
    JOIN account_types t ON a.account_type_id = t.id
    LEFT JOIN journal_entry_lines jel ON a.id = jel.account_id
    LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.statuss = 'POSTED' AND je.entry_date <= '$endDateEsc'
    GROUP BY a.id
    ORDER BY a.account_code ASC";

$result = mysqli_query($conn, $query);
$trialData = [];
$grandDebit = 0.0;
$grandCredit = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $parentType = (int)$row['parent_type_id'];
        $isDebitNormal = in_array($parentType, [-1, -5, -6]) || (int)$row['type_code'] < 2000 || (int)$row['type_code'] >= 5000;
        
        $debitVal = 0.0;
        $creditVal = 0.0;

        if ($isDebitNormal) {
            $net = (float)$row['total_debit'] - (float)$row['total_credit'];
            if ($net > 0) {
                $debitVal = $net;
            } elseif ($net < 0) {
                $creditVal = abs($net);
            }
        } else {
            $net = (float)$row['total_credit'] - (float)$row['total_debit'];
            if ($net > 0) {
                $creditVal = $net;
            } elseif ($net < 0) {
                $debitVal = abs($net);
            }
        }

        // Only list accounts that have had transactions or non-zero balances
        if ($row['total_debit'] > 0 || $row['total_credit'] > 0 || $debitVal > 0 || $creditVal > 0) {
            $trialData[] = [
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'debit' => $debitVal,
                'credit' => $creditVal
            ];

            $grandDebit += $debitVal;
            $grandCredit += $creditVal;
        }
    }
}

$isBalanced = abs($grandDebit - $grandCredit) < 0.01;
$diff = abs($grandDebit - $grandCredit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Trial Balance Report</title>
<meta name="description" content="View general ledger trial balance debits and credits reconciliation.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/trial_balance.css">
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

  <?php $page_title = "Trial Balance Report"; include '../include/navbar.php'; ?>

  <div class="content" id="trialContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Trial Balance Reconciliation
        </h1>
        <div class="page-sub">Verify system debit and credit balances matching</div>
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

    <div class="trial-container">
      
      <!-- ===== BALANCE CHECK ALERTS ===== -->
      <?php if ($isBalanced): ?>
        <div class="balance-alert-banner balanced">
          <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.5;"><polyline points="20 6 9 17 4 12"/></svg>
          <span>Ledger is fully balanced. Total debits match total credits perfectly.</span>
        </div>
      <?php else: ?>
        <div class="balance-alert-banner unbalanced">
          <svg viewBox="0 0 24 24" style="width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 2.5;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span>Warning: The ledger is unbalanced by <strong>Frw <?php echo number_format($diff, 2); ?></strong>. Please inspect recent manual entries.</span>
        </div>
      <?php endif; ?>

      <!-- ===== FILTER PANEL ===== -->
      <div class="trial-filters">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%; align-items: flex-end;">
          <div class="form-group" style="flex: 1; min-width: 180px;">
            <label for="date">As Of Date</label>
            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
          </div>
          <div class="form-group" style="flex: 2; min-width: 240px;">
            <label for="searchInput">Search Account</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Search code or name...">
          </div>
          <button type="submit" class="btn-sm btn-primary" style="padding: 8px 16px; font-size: 13px;">Reconcile</button>
        </form>
      </div>

      <!-- ===== TRIAL BALANCE CARD ===== -->
      <div class="trial-card">
        <div class="trial-card-header">
          <div class="trial-card-title">General Ledger Balances</div>
          <div style="font-size: 12.5px; color: var(--text2);">Balances compiled as of: <strong><?php echo htmlspecialchars($endDate); ?></strong></div>
        </div>

        <div class="note-table-wrapper">
          <table class="trial-table" id="trialTable">
            <thead>
              <tr>
                <th>Account Code</th>
                <th>Account Name</th>
                <th class="num-val" style="width: 20%;">Debit Balance (Frw)</th>
                <th class="num-val" style="width: 20%;">Credit Balance (Frw)</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($trialData) === 0): ?>
                <tr>
                  <td colspan="4" style="text-align: center; color: var(--text2); padding: 24px;">No active balances recorded.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($trialData as $row): ?>
                  <tr class="trial-row">
                    <td style="font-family: monospace; font-weight: 500;" class="acc-code"><?php echo htmlspecialchars($row['account_code']); ?></td>
                    <td style="font-weight: 500;" class="acc-name"><?php echo htmlspecialchars($row['account_name']); ?></td>
                    <td class="num-val"><?php echo $row['debit'] > 0 ? number_format($row['debit'], 2) : '-'; ?></td>
                    <td class="num-val"><?php echo $row['credit'] > 0 ? number_format($row['credit'], 2) : '-'; ?></td>
                  </tr>
                <?php endforeach; ?>
                
                <!-- Totals -->
                <tr class="total-row">
                  <td colspan="2" style="text-align: right; padding-right: 18px; color: var(--text);">Reconciliation Totals</td>
                  <td class="num-val"><?php echo number_format($grandDebit, 2); ?></td>
                  <td class="num-val"><?php echo number_format($grandCredit, 2); ?></td>
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
    const rows = document.querySelectorAll('.trial-row');

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
        csvContent += "INEZA African Mining Ltd - Trial Balance Report\r\n";
        csvContent += "As Of Date: <?php echo $endDate; ?>\r\n\r\n";
        csvContent += "Account Code,Account Name,Debit Balance (Frw),Credit Balance (Frw)\r\n";

        const tableRows = document.querySelectorAll('#trialTable tbody tr');
        tableRows.forEach(row => {
            if (row.classList.contains('total-row')) {
                const cells = row.querySelectorAll('td');
                csvContent += `"Reconciliation Totals",,${cells[1].textContent.replace(/,/g, '')},${cells[2].textContent.replace(/,/g, '')}\r\n`;
            } else {
                const code = row.querySelector('.acc-code').textContent.trim();
                const name = row.querySelector('.acc-name').textContent.trim().replace(/,/g, '');
                const cells = row.querySelectorAll('.num-val');
                
                const debit = cells[0].textContent.replace(/,/g, '').replace(/-/g, '0');
                const credit = cells[1].textContent.replace(/,/g, '').replace(/-/g, '0');

                csvContent += `"${code}","${name}",${debit},${credit}\r\n`;
            }
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "trial_balance_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
</body>
</html>
