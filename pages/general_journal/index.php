<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_journal_entries')) {
    header("Location: ../dashboard");
    exit();
}

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$startDateEsc = mysqli_real_escape_string($conn, $startDate);
$endDateEsc = mysqli_real_escape_string($conn, $endDate);

$accountCode = isset($_GET['account_code']) ? trim($_GET['account_code']) : '';
$accountCodeEsc = mysqli_real_escape_string($conn, $accountCode);
$accountFilterSql = '';
if ($accountCode !== '') {
    $accountFilterSql = " AND (a.account_code = '$accountCodeEsc' OR a.account_name LIKE '%$accountCodeEsc%')";
}

$query = "SELECT jel.*, je.entry_date, je.journal_no, je.description AS transaction_description,
                 a.account_code, a.account_name,
                 COALESCE(c.code, 'RWF') AS currency_code,
                 COALESCE(jel.amount_currency, 0.00) AS amount_currency,
                 COALESCE(jel.amount_base, 0.00) AS amount_base,
                 COALESCE(jel.exchange_rate, 1.00) AS exchange_rate,
                 COALESCE(jel.description, '') AS line_description
          FROM journal_entry_lines jel
          JOIN journal_entries je ON jel.journal_entry_id = je.id
          LEFT JOIN accounts a ON (a.id = jel.account_id OR a.account_code = CAST(jel.account_id AS CHAR))
          LEFT JOIN currencies c ON jel.currency_id = c.id
          WHERE je.entry_date BETWEEN '$startDateEsc' AND '$endDateEsc'$accountFilterSql
          ORDER BY je.entry_date ASC, je.id ASC, jel.id ASC";

$result = mysqli_query($conn, $query);
$journalGroups = [];
$totals = [
    'deposit_usd' => 0.0,
    'deposit_rwf' => 0.0,
    'withdraw_usd' => 0.0,
    'withdraw_rwf' => 0.0,
];
$runningBalanceRwf = 0.0;
$runningBalanceUsd = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $currency = strtoupper(trim($row['currency_code']));
        $debit = (float)$row['debit'];
        $credit = (float)$row['credit'];
        $amountBase = (float)$row['amount_base'];
        $exchangeRate = (float)$row['exchange_rate'];
        if ($exchangeRate <= 0) {
            $exchangeRate = 1.0;
        }

        $depositUsd = 0.0;
        $depositRwf = 0.0;
        $withdrawUsd = 0.0;
        $withdrawRwf = 0.0;
        $lineRwfDelta = 0.0;
        $lineUsdDelta = 0.0;

        if ($currency === 'USD') {
            if ($debit > 0) {
                $depositUsd = $debit;
                $depositRwf = $amountBase;
                $lineRwfDelta = $amountBase;
                $lineUsdDelta = $debit;
            } elseif ($credit > 0) {
                $withdrawUsd = $credit;
                $withdrawRwf = $amountBase;
                $lineRwfDelta = -$amountBase;
                $lineUsdDelta = -$credit;
            }
        } else {
            if ($debit > 0) {
                $depositRwf = $amountBase;
                $depositUsd = $amountBase / $exchangeRate;
                $lineRwfDelta = $amountBase;
                $lineUsdDelta = $amountBase / $exchangeRate;
            } elseif ($credit > 0) {
                $withdrawRwf = $amountBase;
                $withdrawUsd = $amountBase / $exchangeRate;
                $lineRwfDelta = -$amountBase;
                $lineUsdDelta = -$amountBase / $exchangeRate;
            }
        }

        $runningBalanceRwf += $lineRwfDelta;
        $runningBalanceUsd += $lineUsdDelta;

        $journalLine = [
            'line_description' => trim($row['line_description']),
            'account' => trim($row['account_code'] . ' - ' . $row['account_name']),
            'deposit_usd' => $depositUsd,
            'deposit_rwf' => $depositRwf,
            'withdraw_usd' => $withdrawUsd,
            'withdraw_rwf' => $withdrawRwf,
            'balance_rwf' => $runningBalanceRwf,
            'balance_usd' => $runningBalanceUsd,
            'currency_code' => $currency,
        ];

        $journalNo = $row['journal_no'];
        if (!isset($journalGroups[$journalNo])) {
            $journalGroups[$journalNo] = [
                'entry_date' => $row['entry_date'],
                'journal_no' => $journalNo,
                'transaction_description' => trim($row['transaction_description']),
                'lines' => [],
                'deposit_usd' => 0.0,
                'deposit_rwf' => 0.0,
                'withdraw_usd' => 0.0,
                'withdraw_rwf' => 0.0,
                'ending_balance_rwf' => 0.0,
                'ending_balance_usd' => 0.0,
            ];
        }

        $journalGroups[$journalNo]['lines'][] = $journalLine;
        $journalGroups[$journalNo]['deposit_usd'] += $depositUsd;
        $journalGroups[$journalNo]['deposit_rwf'] += $depositRwf;
        $journalGroups[$journalNo]['withdraw_usd'] += $withdrawUsd;
        $journalGroups[$journalNo]['withdraw_rwf'] += $withdrawRwf;
        $journalGroups[$journalNo]['ending_balance_rwf'] = $runningBalanceRwf;
        $journalGroups[$journalNo]['ending_balance_usd'] = $runningBalanceUsd;

        $totals['deposit_usd'] += $depositUsd;
        $totals['deposit_rwf'] += $depositRwf;
        $totals['withdraw_usd'] += $withdrawUsd;
        $totals['withdraw_rwf'] += $withdrawRwf;
    }
}

$journalGroups = array_values($journalGroups);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — General Journal</title>
<meta name="description" content="General journal report with USD and RWF deposits, withdrawals, and running balances.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/journal_entries.css">
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

  <?php $page_title = "General Journal"; include '../include/navbar.php'; ?>

  <div class="content" id="generalJournalContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          General Journal Report
        </h1>
        <div class="page-sub">Posted journal transactions with USD/RWF deposit, withdrawal, and running balances</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm btn-primary" onclick="window.print()">Print</button>
      </div>
    </div>

    <div class="journal-container">
      <div class="ledger-filters" style="margin-bottom: 16px;">
        <form method="GET" style="display: flex; flex-wrap: wrap; gap: 12px; width: 100%; align-items: flex-end;">
          <div class="form-group" style="flex: 1; min-width: 180px;">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
          </div>
          <div class="form-group" style="flex: 1; min-width: 180px;">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
          </div>
          <div class="form-group" style="flex: 1.5; min-width: 220px;">
            <label for="searchInput">Search</label>
            <input type="text" id="searchInput" class="form-control" placeholder="Search details or account...">
          </div>
          <button type="submit" class="btn-sm btn-primary" style="padding: 8px 16px; font-size: 13px;">Filter</button>
        </form>
      </div>

      <div class="ledger-card">
        <div class="ledger-card-header">
          <div class="ledger-card-title">General Journal Activity</div>
          <div style="font-size: 12.5px; color: var(--text2);">Showing posted transactions from <strong><?php echo htmlspecialchars($startDate); ?></strong> to <strong><?php echo htmlspecialchars($endDate); ?></strong></div>
        </div>

        <div class="note-table-wrapper">
          <table class="ledger-table" id="journalTable">
            <thead>
              <tr>
                <th style="min-width: 110px;">Date</th>
                <th style="min-width: 260px;">Transaction Details</th>
                <th style="min-width: 220px;">Account</th>
                <th class="num-val">Deposits USD</th>
                <th class="num-val">Deposits RWF</th>
                <th class="num-val">Withdrawals USD</th>
                <th class="num-val">Withdrawals RWF</th>
                <th class="num-val">Balance RWF</th>
                <th class="num-val">Balance USD</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($journalGroups) === 0): ?>
                <tr>
                  <td colspan="9" style="text-align: center; color: var(--text2); padding: 24px;">No journal line transactions found for the selected date range.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($journalGroups as $group): ?>
                  <tr class="journal-group-header">
                    <td><?php echo htmlspecialchars($group['entry_date']); ?></td>
                    <td colspan="2" style="font-weight: 700;">
                      <?php echo htmlspecialchars($group['journal_no']); ?>
                      <div style="font-size: 13px; color: var(--text2); margin-top: 4px;"><?php echo htmlspecialchars($group['transaction_description']); ?></div>
                    </td>
                    <td class="num-val"><?php echo $group['deposit_usd'] > 0 ? '$' . number_format($group['deposit_usd'], 2) : '-'; ?></td>
                    <td class="num-val"><?php echo $group['deposit_rwf'] > 0 ? number_format($group['deposit_rwf'], 2) : '-'; ?></td>
                    <td class="num-val"><?php echo $group['withdraw_usd'] > 0 ? '$' . number_format($group['withdraw_usd'], 2) : '-'; ?></td>
                    <td class="num-val"><?php echo $group['withdraw_rwf'] > 0 ? number_format($group['withdraw_rwf'], 2) : '-'; ?></td>
                    <td class="num-val"><?php echo number_format($group['ending_balance_rwf'], 2); ?></td>
                    <td class="num-val"><?php echo '$' . number_format($group['ending_balance_usd'], 2); ?></td>
                  </tr>

                  <?php foreach ($group['lines'] as $line): ?>
                    <tr class="journal-line-row">
                      <td></td>
                      <td style="padding-left: 28px; color: var(--text2);">
                        <?php echo htmlspecialchars($line['line_description']); ?>
                      </td>
                      <td style="font-family: monospace; font-size: 12px; color: var(--text);">
                        <?php echo htmlspecialchars($line['account']); ?>
                      </td>
                      <td class="num-val"><?php echo $line['deposit_usd'] > 0 ? '$' . number_format($line['deposit_usd'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo $line['deposit_rwf'] > 0 ? number_format($line['deposit_rwf'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo $line['withdraw_usd'] > 0 ? '$' . number_format($line['withdraw_usd'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo $line['withdraw_rwf'] > 0 ? number_format($line['withdraw_rwf'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo number_format($line['balance_rwf'], 2); ?></td>
                      <td class="num-val"><?php echo '$' . number_format($line['balance_usd'], 2); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endforeach; ?>
                <tr class="total-row">
                  <td colspan="3" style="text-align: right; padding-right: 18px; color: var(--text);">Totals</td>
                  <td class="num-val">$<?php echo number_format($totals['deposit_usd'], 2); ?></td>
                  <td class="num-val"><?php echo number_format($totals['deposit_rwf'], 2); ?></td>
                  <td class="num-val">$<?php echo number_format($totals['withdraw_usd'], 2); ?></td>
                  <td class="num-val"><?php echo number_format($totals['withdraw_rwf'], 2); ?></td>
                  <td class="num-val">-</td>
                  <td class="num-val">-</td>
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
    const rows = document.querySelectorAll('#journalTable tbody tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (query === '' || text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
</body>
</html>
