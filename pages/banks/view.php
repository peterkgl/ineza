<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_banks') && !hasPermission($conn, $userId, 'view_cash') && !hasPermission($conn, $userId, 'view_bank_reconciliation')) {
    header("Location: ../dashboard");
    exit();
}

$accountId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$accountCode = isset($_GET['code']) ? trim($_GET['code']) : '';

// Resolve bank account
$account = null;
if ($accountId > 0) {
    $acctStmt = mysqli_query($conn, "SELECT a.*, at.name as account_type_name FROM accounts a JOIN account_types at ON a.account_type_id = at.id WHERE a.id = $accountId LIMIT 1");
    if ($acctStmt && mysqli_num_rows($acctStmt) > 0) {
        $account = mysqli_fetch_assoc($acctStmt);
    }
} elseif (!empty($accountCode)) {
    $codeEsc = mysqli_real_escape_string($conn, $accountCode);
    $acctStmt = mysqli_query($conn, "SELECT a.*, at.name as account_type_name FROM accounts a JOIN account_types at ON a.account_type_id = at.id WHERE a.account_code = '$codeEsc' LIMIT 1");
    if ($acctStmt && mysqli_num_rows($acctStmt) > 0) {
        $account = mysqli_fetch_assoc($acctStmt);
    }
}

if (!$account) {
    // Fallback: pick first Cash & Cash Equivalents account
    $fallbackStmt = mysqli_query($conn, "SELECT a.*, at.name as account_type_name FROM accounts a JOIN account_types at ON a.account_type_id = at.id WHERE (at.name = 'Cash & Cash Equivalents' OR a.account_type_id = 1) ORDER BY a.account_code ASC LIMIT 1");
    if ($fallbackStmt && mysqli_num_rows($fallbackStmt) > 0) {
        $account = mysqli_fetch_assoc($fallbackStmt);
    }
}

if (!$account) {
    die("No bank account found.");
}

$curAccountId = (int)$account['id'];
$curAccountCode = mysqli_real_escape_string($conn, $account['account_code']);
$curAccountCodeNum = intval($account['account_code']);

// Determine report_slug matching Bank Reconciliation
$reportSlug = 'acc_' . $curAccountId;
if ($curAccountCode === '1031' || $curAccountCode === '1010-03' || strpos(strtolower($account['account_name']), 'rwf') !== false || $curAccountCode == '1010') {
    $reportSlug = 'bank_recon_rwf';
} elseif ($curAccountCode === '1041' || $curAccountCode === '1010-01' || strpos(strtolower($account['account_name']), 'usd') !== false || $curAccountCode == '1020') {
    $reportSlug = 'bank_recon_usd';
} elseif ($curAccountCode === '1051' || $curAccountCode === '1010-04' || strpos(strtolower($account['account_name']), 'euro') !== false || strpos(strtolower($account['account_name']), 'eur') !== false) {
    $reportSlug = 'bank_recon_euro';
}

// Fetch latest recorded statement balance
$stmtQuery = mysqli_query($conn, "
    SELECT balance, as_of_date 
    FROM bank_statement_balances 
    WHERE account_id = $curAccountId OR report_slug = '$reportSlug' 
    ORDER BY as_of_date DESC, id DESC 
    LIMIT 1
");

$recBalance = null;
$recDate = null;
if ($stmtQuery && mysqli_num_rows($stmtQuery) > 0) {
    $stmtRow = mysqli_fetch_assoc($stmtQuery);
    $recBalance = (float)$stmtRow['balance'];
    $recDate = $stmtRow['as_of_date'];
}

// Fetch Balance Movements History from bank_balance_history
$historyQuery = "
    SELECT 
        h.*,
        CONCAT(u.first_name, ' ', u.last_name) AS created_by_name
    FROM bank_balance_history h
    LEFT JOIN users u ON h.created_by = u.id
    WHERE h.account_id = $curAccountId OR h.report_slug = '$reportSlug'
    ORDER BY h.as_of_date DESC, h.id DESC
";
$historyRes = mysqli_query($conn, $historyQuery);
$balanceHistory = [];
if ($historyRes) {
    while ($hRow = mysqli_fetch_assoc($historyRes)) {
        $balanceHistory[] = $hRow;
    }
}

// Date and Filter Parameters
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$movementFilter = $_GET['type'] ?? 'all';

$dateCond = "";
if (!empty($startDate)) {
    $sEsc = mysqli_real_escape_string($conn, $startDate);
    $dateCond .= " AND je.entry_date >= '$sEsc'";
}
if (!empty($endDate)) {
    $eEsc = mysqli_real_escape_string($conn, $endDate);
    $dateCond .= " AND je.entry_date <= '$eEsc'";
}

// Fetch all transactions for this account along with offset counterparty accounts
$txQuery = "
    SELECT 
        jel.id AS line_id,
        jel.journal_entry_id,
        jel.debit,
        jel.credit,
        jel.description AS line_desc,
        jel.is_reconciled,
        je.entry_date,
        je.journal_no AS entry_number,
        je.journal_no AS reference,
        je.description AS journal_desc,
        je.created_at
    FROM journal_entry_lines jel
    JOIN journal_entries je ON jel.journal_entry_id = je.id
    WHERE (jel.account_id = $curAccountId OR jel.account_id = $curAccountCodeNum OR CAST(jel.account_id AS CHAR) = '$curAccountCode')
      AND je.statuss = 'POSTED'
      {$dateCond}
    ORDER BY je.entry_date ASC, je.id ASC, jel.id ASC
";

$txResult = mysqli_query($conn, $txQuery);

$rawMovements = [];
$journalEntryIds = [];

if ($txResult) {
    while ($row = mysqli_fetch_assoc($txResult)) {
        $rawMovements[] = $row;
        $journalEntryIds[] = (int)$row['journal_entry_id'];
    }
}

// Fetch counterpart / offset accounts
$offsetMap = [];
if (!empty($journalEntryIds)) {
    $jeIdsStr = implode(',', array_unique($journalEntryIds));
    $offsetQuery = "
        SELECT 
            jel.journal_entry_id,
            jel.account_id,
            jel.debit,
            jel.credit,
            jel.description,
            a.account_code,
            a.account_name
        FROM journal_entry_lines jel
        LEFT JOIN accounts a ON (jel.account_id = a.id OR CAST(jel.account_id AS CHAR) = a.account_code)
        WHERE jel.journal_entry_id IN ($jeIdsStr)
          AND (jel.account_id != $curAccountId AND jel.account_id != $curAccountCodeNum AND CAST(jel.account_id AS CHAR) != '$curAccountCode')
    ";
    $offsetRes = mysqli_query($conn, $offsetQuery);
    if ($offsetRes) {
        while ($offRow = mysqli_fetch_assoc($offsetRes)) {
            $jeId = (int)$offRow['journal_entry_id'];
            if (!isset($offsetMap[$jeId])) {
                $offsetMap[$jeId] = [];
            }
            $offsetName = !empty($offRow['account_name']) ? ($offRow['account_code'] . ' - ' . $offRow['account_name']) : ('Account #' . $offRow['account_id']);
            $offsetMap[$jeId][] = [
                'name' => $offsetName,
                'debit' => (float)$offRow['debit'],
                'credit' => (float)$offRow['credit'],
                'desc' => $offRow['description']
            ];
        }
    }
}

// Process movements & calculate running balance
$movements = [];
$runningBalance = 0;
$totalInflow = 0;
$totalOutflow = 0;

foreach ($rawMovements as $mv) {
    $debit = (float)$mv['debit'];
    $credit = (float)$mv['credit'];
    
    $totalInflow += $debit;
    $totalOutflow += $credit;
    $runningBalance += ($debit - $credit);
    
    $jeId = (int)$mv['journal_entry_id'];
    $offsets = $offsetMap[$jeId] ?? [];
    
    $offsetSummary = [];
    foreach ($offsets as $off) {
        $offsetSummary[] = $off['name'];
    }
    $offsetText = !empty($offsetSummary) ? implode(', ', array_unique($offsetSummary)) : 'General Ledger Adjustment';
    
    if ($debit > 0) {
        $direction = 'INFLOW';
        $flowLabel = 'Money In (From: ' . $offsetText . ')';
    } else {
        $direction = 'OUTFLOW';
        $flowLabel = 'Money Out (To: ' . $offsetText . ')';
    }
    
    $movements[] = [
        'line_id' => $mv['line_id'],
        'journal_entry_id' => $mv['journal_entry_id'],
        'entry_date' => $mv['entry_date'],
        'entry_number' => $mv['entry_number'],
        'reference' => $mv['reference'],
        'description' => !empty($mv['line_desc']) ? $mv['line_desc'] : $mv['journal_desc'],
        'debit' => $debit,
        'credit' => $credit,
        'running_balance' => $runningBalance,
        'direction' => $direction,
        'offset_text' => $offsetText,
        'flow_label' => $flowLabel,
        'is_reconciled' => $mv['is_reconciled']
    ];
}

// Variance calculation
$variance = ($recBalance !== null) ? ($recBalance - $runningBalance) : 0;

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=bank_movements_' . $account['account_code'] . '_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Entry #', 'Reference', 'Description', 'Movement Type', 'Where Money Came From / Went To', 'Money In (Debit)', 'Money Out (Credit)', 'Running Balance (RWF)']);
    foreach ($movements as $m) {
        fputcsv($output, [
            $m['entry_date'],
            $m['entry_number'],
            $m['reference'],
            $m['description'],
            $m['direction'] === 'INFLOW' ? 'Money In' : 'Money Out',
            $m['offset_text'],
            $m['debit'],
            $m['credit'],
            $m['running_balance']
        ]);
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bank Statement — <?php echo htmlspecialchars($account['account_name']); ?> — INEZA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../src/css/dashboard.css">
  <link rel="stylesheet" href="../../src/css/sidebar.css">
  <link rel="stylesheet" href="../../src/css/navbar.css">
  <link rel="stylesheet" href="../../src/css/banks.css">
</head>
<body>

  <!-- SIDEBAR -->
  <?php include '../include/sidebar.php'; ?>

  <div class="main">
    <!-- NAVBAR -->
    <?php 
    $page_title = "Bank Account Movements: " . htmlspecialchars($account['account_name']); 
    include '../include/navbar.php'; 
    ?>

    <div class="content">
      <div class="banks-container">
        
        <!-- TOP PAGE TITLE & ACTION BAR -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div>
            <a href="index.php" style="font-size: 12px; color: var(--text3); text-decoration: none; display: flex; align-items: center; gap: 4px; margin-bottom: 4px;">
              <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
              Back to Banks Overview
            </a>
            <div class="page-header-title">
              <span class="code-badge"><?php echo htmlspecialchars($account['account_code']); ?></span>
              <?php echo htmlspecialchars($account['account_name']); ?>
            </div>
            <?php if (!empty($account['description'])): ?>
              <div class="page-header-sub"><?php echo htmlspecialchars($account['description']); ?></div>
            <?php endif; ?>
          </div>

          <div style="display: flex; gap: 8px;">
            <button class="btn-sm btn-primary" onclick="openRecordModal(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars(addslashes($account['account_name'])); ?>', '<?php echo ($recBalance !== null) ? $recBalance : ''; ?>')">
              <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Record Statement Balance
            </button>
            <a href="?id=<?php echo $account['id']; ?>&export=csv<?php echo !empty($startDate)?'&start_date='.$startDate:''; ?><?php echo !empty($endDate)?'&end_date='.$endDate:''; ?>" class="btn-sm" style="text-decoration: none;">
              <svg class="btn-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              Export CSV
            </a>
            <button class="btn-sm" onclick="window.print()">
              <svg class="btn-icon" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Print
            </button>
          </div>
        </div>

        <!-- HERO STATS GRID -->
        <div class="stats-grid">
          
          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--green-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--green);"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </div>
              <span class="stat-trend trend-up">Ledger</span>
            </div>
            <div class="stat-val" style="<?php echo ($runningBalance < 0) ? 'color: var(--red);' : ''; ?>">
              RWF <?php echo number_format($runningBalance, 2); ?>
            </div>
            <div class="stat-label">System Ledger Balance</div>
          </div>

          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--purple-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--purple);"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
              </div>
              <span class="stat-trend" style="background: var(--purple-bg); color: var(--purple);">Statement</span>
            </div>
            <div class="stat-val" style="color: var(--purple);">
              <?php echo ($recBalance !== null) ? 'RWF ' . number_format($recBalance, 2) : 'N/A'; ?>
            </div>
            <div class="stat-label">
              <?php echo $recDate ? ('As of ' . htmlspecialchars($recDate)) : 'Recorded Statement'; ?>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--blue-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--blue);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              </div>
              <span class="stat-trend trend-blue">Inflows</span>
            </div>
            <div class="stat-val" style="color: var(--green);">
              RWF <?php echo number_format($totalInflow, 2); ?>
            </div>
            <div class="stat-label">Total Inflows (Money In)</div>
          </div>

          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--amber-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--amber);"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg>
              </div>
              <span class="stat-trend trend-warn">Outflows</span>
            </div>
            <div class="stat-val" style="color: var(--red);">
              RWF <?php echo number_format($totalOutflow, 2); ?>
            </div>
            <div class="stat-label">Total Outflows (Money Out)</div>
          </div>

        </div>

        <!-- TAB NAVIGATION BUTTONS -->
        <div style="display: flex; gap: 8px; border-bottom: 1.5px solid var(--border); padding-bottom: 2px;">
          <button type="button" class="btn-sm btn-primary" id="tabMovementsBtn" onclick="switchTab('movements')">
            Transaction Movements (<?php echo count($movements); ?>)
          </button>
          <button type="button" class="btn-sm" id="tabHistoryBtn" onclick="switchTab('history')">
            Recorded Balance History (<?php echo count($balanceHistory); ?>)
          </button>
        </div>

        <!-- TAB 1: TRANSACTION MOVEMENTS -->
        <div id="tabMovements" class="card">
          
          <form method="GET" action="view.php" class="card-header" style="margin-bottom: 12px; gap: 10px; flex-wrap: wrap;">
            <input type="hidden" name="id" value="<?php echo $account['id']; ?>">
            
            <div class="toolbar-left">
              <div class="search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="txSearchInput" class="form-control" placeholder="Search reference, offset account..." onkeyup="filterMovementsTable()">
              </div>

              <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
              <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">

              <select id="directionFilterSelect" class="form-select" onchange="filterMovementsTable()">
                <option value="all">All Movements</option>
                <option value="INFLOW">Money In (Debits)</option>
                <option value="OUTFLOW">Money Out (Credits)</option>
              </select>

              <button type="submit" class="btn-sm btn-primary">Filter</button>
            </div>
          </form>

          <!-- TRANSACTION MOVEMENTS TABLE (NO ACCOUNT TYPE COLUMN) -->
          <div style="overflow-x: auto;">
            <table class="data-table" id="movementsTable">
              <thead>
                <tr>
                  <th style="width: 85px;">Date</th>
                  <th style="width: 90px;">Ref / Entry #</th>
                  <th style="width: 95px;">Movement</th>
                  <th>Description / Particulars</th>
                  <th>Where Money Came From / Went To</th>
                  <th style="text-align: right; width: 120px;">Money In (Debit)</th>
                  <th style="text-align: right; width: 120px;">Money Out (Credit)</th>
                  <th style="text-align: right; width: 140px;">Running Balance</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($movements)): ?>
                  <tr>
                    <td colspan="8" style="text-align: center; padding: 24px; color: var(--text3);">
                      No transaction movements recorded for this bank account in the selected period.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($movements as $mv): 
                    $isInflow = ($mv['direction'] === 'INFLOW');
                    $flowPill = $isInflow ? 'pill-green' : 'trend-down';
                    $flowIcon = $isInflow ? '+' : '-';
                    $searchData = strtolower($mv['entry_number'] . ' ' . $mv['reference'] . ' ' . $mv['description'] . ' ' . $mv['offset_text']);
                  ?>
                    <tr class="tx-row" data-search="<?php echo htmlspecialchars($searchData); ?>" data-direction="<?php echo $mv['direction']; ?>">
                      <td>
                        <span style="font-weight: 500; color: var(--text2);">
                          <?php echo htmlspecialchars($mv['entry_date']); ?>
                        </span>
                      </td>
                      <td>
                        <span class="code-badge">
                          <?php echo htmlspecialchars(!empty($mv['reference']) ? $mv['reference'] : '#' . $mv['entry_number']); ?>
                        </span>
                      </td>
                      <td>
                        <span class="status-pill <?php echo $flowPill; ?>">
                          <?php echo $flowIcon; ?> <?php echo $isInflow ? 'Money In' : 'Money Out'; ?>
                        </span>
                      </td>
                      <td>
                        <div class="td-name">
                          <?php echo htmlspecialchars($mv['description']); ?>
                        </div>
                      </td>
                      <td>
                        <div class="account-offset-tag" title="<?php echo htmlspecialchars($mv['offset_text']); ?>">
                          <strong><?php echo $isInflow ? 'From:' : 'To:'; ?></strong> <?php echo htmlspecialchars($mv['offset_text']); ?>
                        </div>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        <?php echo ($mv['debit'] > 0) ? '<span style="color: var(--green);">RWF ' . number_format($mv['debit'], 2) . '</span>' : '-'; ?>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        <?php echo ($mv['credit'] > 0) ? '<span style="color: var(--red);">RWF ' . number_format($mv['credit'], 2) . '</span>' : '-'; ?>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        RWF <?php echo number_format($mv['running_balance'], 2); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
              <?php if (!empty($movements)): ?>
                <tfoot>
                  <tr style="background: var(--bg); font-weight: 600; border-top: 1.5px solid var(--border);">
                    <td colspan="5" style="text-align: right; color: var(--text2);">PERIOD TOTALS:</td>
                    <td style="text-align: right;" class="td-bold" style="color: var(--green);">RWF <?php echo number_format($totalInflow, 2); ?></td>
                    <td style="text-align: right;" class="td-bold" style="color: var(--red);">RWF <?php echo number_format($totalOutflow, 2); ?></td>
                    <td style="text-align: right;" class="td-bold">RWF <?php echo number_format($runningBalance, 2); ?></td>
                  </tr>
                </tfoot>
              <?php endif; ?>
            </table>
          </div>

        </div>

        <!-- TAB 2: RECORDED BALANCE HISTORY -->
        <div id="tabHistory" class="card" style="display: none;">
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px;">
            <div class="card-title">Recorded Balance Movement &amp; Audit History</div>

            <button type="button" class="btn-sm btn-primary" onclick="openRecordModal(<?php echo $account['id']; ?>, '<?php echo htmlspecialchars(addslashes($account['account_name'])); ?>')">
              + New Balance Entry
            </button>
          </div>

          <div style="overflow-x: auto;">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Statement As-Of Date</th>
                  <th style="text-align: right;">Previous Balance</th>
                  <th style="text-align: right;">New Recorded Balance</th>
                  <th style="text-align: right;">Net Change</th>
                  <th>Notes / Reference</th>
                  <th>Recorded By</th>
                  <th>Timestamp</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($balanceHistory)): ?>
                  <tr>
                    <td colspan="7" style="text-align: center; padding: 24px; color: var(--text3);">
                      No balance recording history found for this bank account yet.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($balanceHistory as $h): 
                    $chg = (float)$h['change_amount'];
                    $chgPill = ($chg >= 0) ? 'pill-green' : 'trend-down';
                    $chgSign = ($chg >= 0) ? '+' : '';
                  ?>
                    <tr>
                      <td class="td-bold">
                        <?php echo htmlspecialchars($h['as_of_date']); ?>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        RWF <?php echo number_format($h['old_balance'], 2); ?>
                      </td>
                      <td style="text-align: right;" class="td-bold" style="color: var(--purple);">
                        RWF <?php echo number_format($h['new_balance'], 2); ?>
                      </td>
                      <td style="text-align: right;">
                        <span class="status-pill <?php echo $chgPill; ?>">
                          <?php echo $chgSign; ?>RWF <?php echo number_format($chg, 2); ?>
                        </span>
                      </td>
                      <td>
                        <?php echo !empty($h['notes']) ? htmlspecialchars($h['notes']) : '<span style="color:var(--text3); font-style:italic;">No notes</span>'; ?>
                      </td>
                      <td>
                        <span class="td-name">
                          <?php echo htmlspecialchars(!empty($h['created_by_name']) ? $h['created_by_name'] : 'System Administrator'); ?>
                        </span>
                      </td>
                      <td style="font-size: 11px; color: var(--text3);">
                        <?php echo htmlspecialchars($h['created_at']); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </div>

      </div>
    </div>
  </div>

  <!-- MODAL FOR RECORDING BANK STATEMENT BALANCE -->
  <div class="bank-modal-backdrop" id="recordModalBackdrop">
    <div class="bank-modal">
      <div class="bank-modal-header">
        <div class="bank-modal-title">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="5" width="20" height="14" rx="2"/>
            <line x1="2" y1="10" x2="22" y2="10"/>
          </svg>
          Record Statement Balance
        </div>
        <button class="bank-modal-close" onclick="closeRecordModal()">&times;</button>
      </div>
      
      <form id="recordBalanceForm" onsubmit="saveBankBalance(event)">
        <input type="hidden" name="account_id" value="<?php echo $account['id']; ?>">

        <div class="bank-modal-body">
          
          <div class="form-group">
            <label>Bank Account</label>
            <input type="text" class="form-control" value="<?php echo htmlspecialchars($account['account_code'] . ' — ' . $account['account_name']); ?>" readonly style="background: var(--bg);">
          </div>

          <div class="form-group">
            <label for="modalAsOfDate">Statement As-Of Date *</label>
            <input type="date" id="modalAsOfDate" name="as_of_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label for="modalBalance">Recorded Statement Balance (RWF) *</label>
            <input type="number" step="0.01" id="modalBalance" name="balance" class="form-control" placeholder="0.00" value="<?php echo ($recBalance !== null) ? $recBalance : ''; ?>" required>
          </div>

          <div class="form-group">
            <label for="modalNotes">Reference / Notes (Optional)</label>
            <input type="text" id="modalNotes" name="notes" class="form-control" placeholder="e.g., Monthly bank confirmation statement balance...">
          </div>

          <div id="modalAlert" style="display: none; padding: 8px 12px; border-radius: var(--radius); font-size: 12px;"></div>

        </div>

        <div class="bank-modal-footer">
          <button type="button" class="btn-sm" onclick="closeRecordModal()">Cancel</button>
          <button type="submit" class="btn-sm btn-primary" id="saveBalanceBtn">Save Balance</button>
        </div>
      </form>

    </div>
  </div>

  <script>
    function switchTab(tabName) {
      const tabMovements = document.getElementById('tabMovements');
      const tabHistory = document.getElementById('tabHistory');
      const btnMovements = document.getElementById('tabMovementsBtn');
      const btnHistory = document.getElementById('tabHistoryBtn');

      if (tabName === 'history') {
        tabMovements.style.display = 'none';
        tabHistory.style.display = 'block';
        btnMovements.className = 'btn-sm';
        btnHistory.className = 'btn-sm btn-primary';
      } else {
        tabMovements.style.display = 'block';
        tabHistory.style.display = 'none';
        btnMovements.className = 'btn-sm btn-primary';
        btnHistory.className = 'btn-sm';
      }
    }

    function openRecordModal(acctId = '', acctName = '', currentRecBal = '') {
      document.getElementById('modalAlert').style.display = 'none';
      if (currentRecBal !== '') {
        document.getElementById('modalBalance').value = currentRecBal;
      }
      document.getElementById('recordModalBackdrop').classList.add('active');
    }

    function closeRecordModal() {
      document.getElementById('recordModalBackdrop').classList.remove('active');
    }

    function saveBankBalance(e) {
      e.preventDefault();
      const saveBtn = document.getElementById('saveBalanceBtn');
      const alertBox = document.getElementById('modalAlert');

      saveBtn.disabled = true;
      saveBtn.innerText = 'Saving...';
      alertBox.style.display = 'none';

      const formData = new FormData(document.getElementById('recordBalanceForm'));
      formData.append('action', 'save_bank_balance');

      fetch('actions.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save Balance';

        if (data.success) {
          alertBox.style.display = 'block';
          alertBox.style.background = 'var(--green-bg)';
          alertBox.style.color = 'var(--green)';
          alertBox.innerText = data.message || 'Bank balance recorded successfully.';

          setTimeout(() => {
            closeRecordModal();
            window.location.reload();
          }, 800);
        } else {
          alertBox.style.display = 'block';
          alertBox.style.background = 'var(--red-bg)';
          alertBox.style.color = 'var(--red)';
          alertBox.innerText = data.message || 'An error occurred while saving balance.';
        }
      })
      .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save Balance';
        alertBox.style.display = 'block';
        alertBox.style.background = 'var(--red-bg)';
        alertBox.style.color = 'var(--red)';
        alertBox.innerText = 'Network or server error.';
      });
    }

    function filterMovementsTable() {
      const searchVal = document.getElementById('txSearchInput').value.toLowerCase().trim();
      const dirVal = document.getElementById('directionFilterSelect').value;
      const rows = document.querySelectorAll('.tx-row');

      rows.forEach(row => {
        const searchData = row.getAttribute('data-search') || '';
        const dirData = row.getAttribute('data-direction') || '';

        let matchesSearch = searchData.includes(searchVal);
        let matchesDir = (dirVal === 'all' || dirData === dirVal);

        if (matchesSearch && matchesDir) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
