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

function getTrialBalanceGroup($accountCode, $accountName, $parentType) {
    $code = preg_replace('/[^0-9]/', '', (string)$accountCode);
    $name = strtolower((string)$accountName);
    $parentType = (int)$parentType;

    if ($parentType === -4) {
        return ['section' => 'Income Statement', 'subsection' => 'Revenue', 'label' => 'Revenue'];
    }
    if ($parentType === -5) {
        return ['section' => 'Income Statement', 'subsection' => 'Cost of Sales', 'label' => 'Cost of Sales'];
    }
    if ($parentType === -6) {
        return ['section' => 'Income Statement', 'subsection' => 'Expenses', 'label' => 'Expenses'];
    }

    if ($parentType === -1) {
        if (preg_match('/cash|bank|petty/i', $name) || in_array($code, ['1001', '1021', '1031', '1041', '1051', '1061'], true)) {
            return ['section' => 'Assets', 'subsection' => 'Current Assets', 'label' => 'Cash & Cash Equivalents'];
        }
        if (preg_match('/receivable|debtor/i', $name) || $code === '1002') {
            return ['section' => 'Assets', 'subsection' => 'Current Assets', 'label' => 'Accounts Receivables'];
        }
        if (preg_match('/stock|inventory|goods/i', $name) || in_array($code, ['1004', '1024', '1034'], true)) {
            return ['section' => 'Assets', 'subsection' => 'Current Assets', 'label' => 'Inventory / Stock'];
        }
        if (preg_match('/prepay|advance/i', $name)) {
            return ['section' => 'Assets', 'subsection' => 'Current Assets', 'label' => 'Other Current Assets'];
        }
        if (preg_match('/property|plant|equipment|ppe|vehicle|building/i', $name) || $code === '1005') {
            return ['section' => 'Assets', 'subsection' => 'Non Current Assets', 'label' => 'Property, Plant & Equipment'];
        }
        if (preg_match('/intangible|deferred tax/i', $name)) {
            return ['section' => 'Assets', 'subsection' => 'Non Current Assets', 'label' => 'Other Non Current Assets'];
        }
        return ['section' => 'Assets', 'subsection' => 'Other Assets', 'label' => 'Other Assets'];
    }

    if ($parentType === -2) {
        if (preg_match('/payable/i', $name) || $code === '2001') {
            return ['section' => 'Liabilities', 'subsection' => 'Current Liabilities', 'label' => 'Accounts Payables'];
        }
        if (preg_match('/loan|borrow/i', $name) || in_array($code, ['2008', '2009', '2010', '2012'], true)) {
            return ['section' => 'Liabilities', 'subsection' => 'Non Current Liabilities', 'label' => 'Long Term Loans'];
        }
        if (preg_match('/tax/i', $name) || $code === '2011') {
            return ['section' => 'Liabilities', 'subsection' => 'Current Liabilities', 'label' => 'Current Tax Payable'];
        }
        if (preg_match('/accrued|payroll|provision|deferred revenue|deposit|intercompany/i', $name)) {
            return ['section' => 'Liabilities', 'subsection' => 'Current Liabilities', 'label' => 'Other Current Liabilities'];
        }
        return ['section' => 'Liabilities', 'subsection' => 'Current Liabilities', 'label' => 'Other Liabilities'];
    }

    if ($parentType === -3) {
        if (preg_match('/capital|share/i', $name) || $code === '3001') {
            return ['section' => 'Equity', 'subsection' => 'Capital & Reserves', 'label' => 'Share Capital'];
        }
        if (preg_match('/retained|earnings/i', $name) || $code === '3005') {
            return ['section' => 'Equity', 'subsection' => 'Capital & Reserves', 'label' => 'Retained Earnings'];
        }
        if (preg_match('/reserve/i', $name) || $code === '3007') {
            return ['section' => 'Equity', 'subsection' => 'Capital & Reserves', 'label' => 'Reserves'];
        }
        if (preg_match('/drawing/i', $name)) {
            return ['section' => 'Equity', 'subsection' => 'Capital & Reserves', 'label' => 'Owner Drawings'];
        }
        return ['section' => 'Equity', 'subsection' => 'Capital & Reserves', 'label' => 'Other Equity'];
    }

    return ['section' => 'Other', 'subsection' => 'Other', 'label' => 'Other Accounts'];
}

// Fetch ledger totals per account up to date using the same account-type logic as the balance sheet and income statement.
$query = "
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
       AND je.entry_date <= '$endDateEsc'
    WHERE a.is_active = 1
    GROUP BY a.id
    ORDER BY CAST(a.account_code AS UNSIGNED), a.account_code ASC";

$result = mysqli_query($conn, $query);
$trialData = [];
$groupedData = [];
$grandDebit = 0.0;
$grandCredit = 0.0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $parentType = (int)$row['parent_type_id'];
        $isDebitNormal = in_array($parentType, [-1, -5, -6], true);

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

        if ($row['total_debit'] > 0 || $row['total_credit'] > 0 || $debitVal > 0 || $creditVal > 0) {
            $trialData[] = [
                'account_code' => $row['account_code'],
                'account_name' => $row['account_name'],
                'debit' => $debitVal,
                'credit' => $creditVal,
                'parent_type_id' => $parentType
            ];

            $grandDebit += $debitVal;
            $grandCredit += $creditVal;
        }
    }

    foreach ($trialData as $row) {
        $groupInfo = getTrialBalanceGroup($row['account_code'], $row['account_name'], $row['parent_type_id']);
        $groupKey = $groupInfo['section'] . '|' . $groupInfo['subsection'] . '|' . $groupInfo['label'];

        if (!isset($groupedData[$groupKey])) {
            $groupedData[$groupKey] = [
                'section' => $groupInfo['section'],
                'subsection' => $groupInfo['subsection'],
                'label' => $groupInfo['label'],
                'rows' => [],
                'debit' => 0.0,
                'credit' => 0.0
            ];
        }

        $groupedData[$groupKey]['rows'][] = [
            'account_code' => $row['account_code'],
            'account_name' => $row['account_name'],
            'debit' => $row['debit'],
            'credit' => $row['credit']
        ];
        $groupedData[$groupKey]['debit'] += (float)$row['debit'];
        $groupedData[$groupKey]['credit'] += (float)$row['credit'];
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
              <?php if (count($groupedData) === 0): ?>
                <tr>
                  <td colspan="4" style="text-align: center; color: var(--text2); padding: 24px;">No active balances recorded.</td>
                </tr>
              <?php else: ?>
                <?php $currentSection = ''; $currentSubsection = ''; ?>
                <?php foreach ($groupedData as $group): ?>
                  <?php if ($currentSection !== $group['section']): ?>
                    <tr class="section-header-row">
                      <td colspan="4"><?php echo htmlspecialchars($group['section']); ?></td>
                    </tr>
                    <?php $currentSection = $group['section']; ?>
                  <?php endif; ?>

                  <?php if ($currentSubsection !== $group['subsection']): ?>
                    <tr class="subsection-header-row">
                      <td colspan="4"><?php echo htmlspecialchars($group['subsection']); ?></td>
                    </tr>
                    <?php $currentSubsection = $group['subsection']; ?>
                  <?php endif; ?>

                  <tr class="trial-group-row">
                    <td colspan="4" style="padding-left: 20px; font-weight: 600; color: var(--text);">
                      <?php echo htmlspecialchars($group['label']); ?>
                    </td>
                  </tr>

                  <?php foreach ($group['rows'] as $row): ?>
                    <tr class="trial-row">
                      <td style="font-family: monospace; font-weight: 500; padding-left: 28px;" class="acc-code"><?php echo htmlspecialchars($row['account_code']); ?></td>
                      <td style="font-weight: 500;" class="acc-name"><?php echo htmlspecialchars($row['account_name']); ?></td>
                      <td class="num-val"><?php echo $row['debit'] > 0 ? number_format($row['debit'], 2) : '-'; ?></td>
                      <td class="num-val"><?php echo $row['credit'] > 0 ? number_format($row['credit'], 2) : '-'; ?></td>
                    </tr>
                  <?php endforeach; ?>

                  <tr class="total-row">
                    <td colspan="2" style="text-align: right; padding-right: 18px; color: var(--text);">Total <?php echo htmlspecialchars($group['label']); ?></td>
                    <td class="num-val"><?php echo number_format($group['debit'], 2); ?></td>
                    <td class="num-val"><?php echo number_format($group['credit'], 2); ?></td>
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
            if (row.classList.contains('trial-row')) {
                const code = row.querySelector('.acc-code').textContent.trim();
                const name = row.querySelector('.acc-name').textContent.trim().replace(/,/g, '');
                const cells = row.querySelectorAll('.num-val');
                
                const debit = cells[0].textContent.replace(/,/g, '').replace(/-/g, '0');
                const credit = cells[1].textContent.replace(/,/g, '').replace(/-/g, '0');

                csvContent += `"${code}","${name}",${debit},${credit}\r\n`;
            } else if (row.classList.contains('total-row') && row.querySelector('td').textContent.includes('Reconciliation Totals')) {
                const cells = row.querySelectorAll('td');
                csvContent += `"Reconciliation Totals",,${cells[1].textContent.replace(/,/g, '')},${cells[2].textContent.replace(/,/g, '')}\r\n`;
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
