<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Permission Check
if (!hasPermission($conn, $userId, 'view_account_ledger')) {
    header("Location: ../dashboard");
    exit();
}

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$accountId = isset($_GET['account_id']) ? (int)$_GET['account_id'] : 0;

$startDateEsc = mysqli_real_escape_string($conn, $startDate);
$endDateEsc = mysqli_real_escape_string($conn, $endDate);

// Fetch accounts list for dropdown
$accountsQuery = "SELECT id, account_code, account_name FROM accounts WHERE is_active = 1 ORDER BY account_code ASC";
$accountsRes = mysqli_query($conn, $accountsQuery);
$accounts = [];
if ($accountsRes) {
    while ($row = mysqli_fetch_assoc($accountsRes)) {
        $accounts[] = $row;
    }
}

// Fetch ledger details if account selected
$accountDetail = null;
$openingBalance = 0.0;
$ledgerLines = [];
$totalDebit = 0.0;
$totalCredit = 0.0;
$isDebitNormal = true;

if ($accountId > 0) {
    // Fetch account details
    $accDetQuery = "
        SELECT a.*, t.code as type_code, t.parent_id as parent_type_id
        FROM accounts a
        JOIN account_types t ON a.account_type_id = t.id
        WHERE a.id = $accountId LIMIT 1";
    $accDetRes = mysqli_query($conn, $accDetQuery);
    if ($accDetRes && mysqli_num_rows($accDetRes) > 0) {
        $accountDetail = mysqli_fetch_assoc($accDetRes);
        $parentType = (int)$accountDetail['parent_type_id'];
        $isDebitNormal = in_array($parentType, [-1, -5, -6]) || (int)$accountDetail['type_code'] < 2000 || (int)$accountDetail['type_code'] >= 5000;
        
        // Calculate Opening Balance (posted pre start date)
        $openQuery = "
            SELECT SUM(jel.debit) as debits, SUM(jel.credit) as credits 
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = $accountId AND je.statuss = 'POSTED' AND je.entry_date < '$startDateEsc'";
        $openRes = mysqli_query($conn, $openQuery);
        $openRow = mysqli_fetch_assoc($openRes);
        $openDebits = (float)($openRow['debits'] ?? 0.0);
        $openCredits = (float)($openRow['credits'] ?? 0.0);

        if ($isDebitNormal) {
            $openingBalance = $openDebits - $openCredits;
        } else {
            $openingBalance = $openCredits - $openDebits;
        }

        // Fetch Detailed Transactions
        $linesQuery = "
            SELECT jel.id as line_id, jel.debit, jel.credit, jel.description as line_desc,
                   je.id as je_id, je.journal_no, je.entry_date, je.description as header_desc
            FROM journal_entry_lines jel
            JOIN journal_entries je ON jel.journal_entry_id = je.id
            WHERE jel.account_id = $accountId AND je.statuss = 'POSTED' AND je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc'
            ORDER BY je.entry_date ASC, je.id ASC, jel.id ASC";
        $linesRes = mysqli_query($conn, $linesQuery);
        if ($linesRes) {
            $running = $openingBalance;
            while ($row = mysqli_fetch_assoc($linesRes)) {
                $deb = (float)$row['debit'];
                $cred = (float)$row['credit'];

                if ($isDebitNormal) {
                    $running += ($deb - $cred);
                } else {
                    $running += ($cred - $deb);
                }

                $totalDebit += $deb;
                $totalCredit += $cred;

                $row['running_balance'] = $running;
                $ledgerLines[] = $row;
            }
        }
    }
}
$closingBalance = $openingBalance + ($isDebitNormal ? ($totalDebit - $totalCredit) : ($totalCredit - $totalDebit));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Account Ledger Report</title>
<meta name="description" content="View chronological audit list of debits, credits, and running balance for any account.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/account_ledger.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include '../include/sidebar.php'; ?>

<div class="main">

  <?php $page_title = "Account Ledger Report"; include '../include/navbar.php'; ?>

  <div class="content" id="ledgerContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5v-15z"/></svg>
          Account Audit Ledger
        </h1>
        <div class="page-sub">Audit chronological detail lines of any ledger account</div>
      </div>
      <div class="page-actions">
        <?php if ($accountId > 0): ?>
          <button class="btn-sm" id="exportCsvBtn">
            Export CSV
          </button>
        <?php endif; ?>
        <button class="btn-sm btn-primary" onclick="window.print()">
          Print Ledger
        </button>
      </div>
    </div>

    <div class="ledger-container">

      <!-- ===== FILTER PANEL ===== -->
      <div class="ledger-filters">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%; align-items: flex-end;">
          <div class="form-group" style="flex: 2; min-width: 240px;">
            <label for="account_id">Select Ledger Account *</label>
            <select id="account_id" name="account_id" class="form-control" required>
              <option value="">-- Select Account --</option>
              <?php foreach ($accounts as $a): ?>
                <option value="<?php echo $a['id']; ?>" <?php echo $accountId === (int)$a['id'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($a['account_name'] . ' (' . $a['account_code'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group" style="flex: 1; min-width: 150px;">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
          </div>
          <div class="form-group" style="flex: 1; min-width: 150px;">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
          </div>
          <button type="submit" class="btn-sm btn-primary" style="padding: 8px 16px; font-size: 13px;">View Ledger</button>
        </form>
      </div>

      <!-- ===== LEDGER CARD ===== -->
      <div class="ledger-card">
        <div class="ledger-card-header">
          <div class="ledger-card-title">
            <?php 
              if ($accountDetail) {
                echo htmlspecialchars($accountDetail['account_name'] . ' (' . $accountDetail['account_code'] . ')');
              } else {
                echo 'Select an account to view transactions';
              }
            ?>
          </div>
          <div style="font-size: 12.5px; color: var(--text2);">
            Balance Type: <strong><?php echo $isDebitNormal ? 'Debit-Normal (Asset/Expense)' : 'Credit-Normal (Liab/Eq/Rev)'; ?></strong>
          </div>
        </div>

        <div class="note-table-wrapper">
          <table class="ledger-table" id="ledgerTable">
            <thead>
              <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 15%;">Reference / J/E No</th>
                <th style="width: 38%;">Description / Narration</th>
                <th class="num-val" style="width: 11%;">Debit (Frw)</th>
                <th class="num-val" style="width: 11%;">Credit (Frw)</th>
                <th class="num-val" style="width: 13%;">Running Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($accountId <= 0): ?>
                <tr>
                  <td colspan="6" style="text-align: center; color: var(--text2); padding: 24px;">Please choose a ledger account to query.</td>
                </tr>
              <?php else: ?>
                <!-- Opening Balance Row -->
                <tr class="summary-row">
                  <td><?php echo htmlspecialchars($startDate); ?></td>
                  <td style="font-family: monospace; font-weight: 500;">OPENING</td>
                  <td style="color: var(--text2);">Accumulated ledger balance brought forward</td>
                  <td class="num-val">-</td>
                  <td class="num-val">-</td>
                  <td class="num-val"><?php echo number_format($openingBalance, 2); ?></td>
                </tr>

                <?php if (count($ledgerLines) === 0): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; color: var(--text2); padding: 18px;">No transaction activities in this date range.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($ledgerLines as $line): ?>
                    <tr>
                      <td><?php echo date('Y-m-d', strtotime($line['entry_date'])); ?></td>
                      <td style="font-family: monospace; font-weight: 500;">
                        <a href="../journal_entries/view?id=<?php echo $line['je_id']; ?>" style="color: var(--orange); text-decoration: none;"><?php echo htmlspecialchars($line['journal_no']); ?></a>
                      </td>
                      <td style="text-align: left; color: var(--text2);">
                        <?php 
                          $desc = !empty($line['line_desc']) ? $line['line_desc'] : $line['header_desc'];
                          echo htmlspecialchars($desc); 
                        ?>
                      </td>
                      <td class="num-val"><?php echo $line['debit'] > 0 ? number_format($line['debit'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo $line['credit'] > 0 ? number_format($line['credit'], 2) : '-'; ?></td>
                      <td class="num-val" style="font-weight: 500;"><?php echo number_format($line['running_balance'], 2); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>

                <!-- Closing Totals Row -->
                <tr class="total-row">
                  <td colspan="3" style="text-align: right; padding-right: 18px; color: var(--text);">Range Movement &amp; Ending Balance</td>
                  <td class="num-val"><?php echo number_format($totalDebit, 2); ?></td>
                  <td class="num-val"><?php echo number_format($totalCredit, 2); ?></td>
                  <td class="num-val" style="border-bottom: 4px double var(--text);"><?php echo number_format($closingBalance, 2); ?></td>
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
<?php if ($accountId > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV Exporter
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Account Ledger Report\r\n";
        csvContent += "Account: <?php echo htmlspecialchars($accountDetail['account_name'] . ' (' . $accountDetail['account_code'] . ')'); ?>\r\n";
        csvContent += "Reporting Range: <?php echo $startDate; ?> to <?php echo $endDate; ?>\r\n\r\n";
        csvContent += "Date,Reference/J/E,Description,Debit (Frw),Credit (Frw),Running Balance (Frw)\r\n";

        const tableRows = document.querySelectorAll('#ledgerTable tbody tr');
        tableRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (row.classList.contains('summary-row')) {
                csvContent += `"${cells[0].textContent}","OPENING","Opening Balance",0,0,${cells[5].textContent.replace(/,/g, '')}\r\n`;
            } else if (row.classList.contains('total-row')) {
                csvContent += `"Totals Movement",,,${cells[1].textContent.replace(/,/g, '')},${cells[2].textContent.replace(/,/g, '')},${cells[3].textContent.replace(/,/g, '')}\r\n`;
            } else {
                const date = cells[0].textContent.trim();
                const ref = cells[1].textContent.trim();
                const desc = cells[2].textContent.trim().replace(/,/g, '');
                
                const deb = cells[3].textContent.replace(/,/g, '').replace(/-/g, '0');
                const cred = cells[4].textContent.replace(/,/g, '').replace(/-/g, '0');
                const runBal = cells[5].textContent.replace(/,/g, '');

                csvContent += `"${date}","${ref}","${desc}",${deb},${cred},${runBal}\r\n`;
            }
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "account_ledger_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
<?php endif; ?>
</body>
</html>
