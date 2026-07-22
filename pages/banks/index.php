<?php
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_banks') && !hasPermission($conn, $userId, 'view_cash') && !hasPermission($conn, $userId, 'view_bank_reconciliation')) {
    header("Location: ../dashboard");
    exit();
}

// Fetch all bank accounts under "Cash & Cash Equivalents" account type along with latest recorded statement balances
$bankAccountsQuery = "
    SELECT 
        a.id, 
        a.account_code, 
        a.account_name, 
        a.description,
        a.is_active,
        at.name AS account_type_name,
        COALESCE(SUM(CASE WHEN je.statuss = 'POSTED' THEN jel.debit ELSE 0 END), 0) AS total_debits,
        COALESCE(SUM(CASE WHEN je.statuss = 'POSTED' THEN jel.credit ELSE 0 END), 0) AS total_credits,
        (COALESCE(SUM(CASE WHEN je.statuss = 'POSTED' THEN jel.debit ELSE 0 END), 0) - 
         COALESCE(SUM(CASE WHEN je.statuss = 'POSTED' THEN jel.credit ELSE 0 END), 0)) AS current_balance,
        COUNT(DISTINCT CASE WHEN je.statuss = 'POSTED' THEN jel.id END) AS total_transactions
    FROM accounts a
    JOIN account_types at ON a.account_type_id = at.id
    LEFT JOIN journal_entry_lines jel 
        ON (jel.account_id = a.id OR jel.account_id = a.account_code OR CAST(jel.account_id AS CHAR) = a.account_code)
    LEFT JOIN journal_entries je 
        ON jel.journal_entry_id = je.id
    WHERE (at.name = 'Cash & Cash Equivalents' OR a.account_type_id = 1)
    GROUP BY a.id, a.account_code, a.account_name, a.description, a.is_active, at.name
    ORDER BY a.account_code ASC
";

$result = mysqli_query($conn, $bankAccountsQuery);
$bankAccounts = [];
$totalPortfolioBalance = 0;
$totalDebitsAll = 0;
$totalCreditsAll = 0;
$totalRecordedAll = 0;
$activeCount = 0;

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $acctId = (int)$row['id'];
        $acctCode = mysqli_real_escape_string($conn, $row['account_code']);
        
        // Determine report_slug matching Bank Reconciliation
        $slug = 'acc_' . $acctId;
        if ($acctCode === '1031' || $acctCode === '1010-03' || strpos(strtolower($row['account_name']), 'rwf') !== false || $acctCode == '1010') {
            $slug = 'bank_recon_rwf';
        } elseif ($acctCode === '1041' || $acctCode === '1010-01' || strpos(strtolower($row['account_name']), 'usd') !== false || $acctCode == '1020') {
            $slug = 'bank_recon_usd';
        } elseif ($acctCode === '1051' || $acctCode === '1010-04' || strpos(strtolower($row['account_name']), 'euro') !== false || strpos(strtolower($row['account_name']), 'eur') !== false) {
            $slug = 'bank_recon_euro';
        }

        // Fetch latest recorded statement balance for this bank account
        $stmtQuery = mysqli_query($conn, "
            SELECT balance, as_of_date 
            FROM bank_statement_balances 
            WHERE account_id = $acctId OR report_slug = '$slug' 
            ORDER BY as_of_date DESC, id DESC 
            LIMIT 1
        ");
        
        $recBalance = null;
        $recDate = null;
        if ($stmtQuery && mysqli_num_rows($stmtQuery) > 0) {
            $stmtRow = mysqli_fetch_assoc($stmtQuery);
            $recBalance = (float)$stmtRow['balance'];
            $recDate = $stmtRow['as_of_date'];
            $totalRecordedAll += $recBalance;
        }

        $row['recorded_balance'] = $recBalance;
        $row['recorded_date'] = $recDate;
        $row['report_slug'] = $slug;
        
        $bankAccounts[] = $row;
        $totalPortfolioBalance += (float)$row['current_balance'];
        $totalDebitsAll += (float)$row['total_debits'];
        $totalCreditsAll += (float)$row['total_credits'];
        if ($row['is_active']) {
            $activeCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Banks Overview — INEZA African Mining</title>
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
    $page_title = "Bank Accounts"; 
    include '../include/navbar.php'; 
    ?>

    <div class="content">
      <div class="banks-container">
        
        <!-- TOP PAGE TITLE & ACTION BAR -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div>
            <div class="page-header-title">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
              Bank Accounts Overview
            </div>
            <div class="page-header-sub">
              Monitor general ledger balances, statement balances, and reconciliation variances.
            </div>
          </div>

          <div style="display: flex; gap: 8px;">
            <button class="btn-sm btn-primary" onclick="openRecordModal()">
              <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Record Bank Balance
            </button>
            <button class="btn-sm" onclick="window.print()">
              <svg class="btn-icon" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
              Print
            </button>
          </div>
        </div>

        <!-- HEADER STATS GRID (SYSTEM DESIGN MATCH) -->
        <div class="stats-grid">
          
          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--blue-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--blue);"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
              </div>
              <span class="stat-trend trend-blue"><?php echo $activeCount; ?> Active</span>
            </div>
            <div class="stat-val"><?php echo count($bankAccounts); ?></div>
            <div class="stat-label">Total Bank Accounts</div>
          </div>

          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--green-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--green);"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              </div>
              <span class="stat-trend trend-up">Ledger</span>
            </div>
            <div class="stat-val" style="<?php echo ($totalPortfolioBalance < 0) ? 'color: var(--red);' : ''; ?>">
              RWF <?php echo number_format($totalPortfolioBalance, 2); ?>
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
              RWF <?php echo number_format($totalRecordedAll, 2); ?>
            </div>
            <div class="stat-label">Recorded Statement Total</div>
          </div>

          <div class="stat-card">
            <div class="stat-top">
              <div class="stat-icon" style="background: var(--amber-bg);">
                <svg viewBox="0 0 24 24" style="stroke: var(--amber);"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
              </div>
              <span class="stat-trend trend-warn">Inflows</span>
            </div>
            <div class="stat-val" style="color: var(--green);">
              RWF <?php echo number_format($totalDebitsAll, 2); ?>
            </div>
            <div class="stat-label">Lifetime Total Inflows</div>
          </div>

        </div>

        <!-- MAIN TABLE CARD -->
        <div class="card">
          
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div class="card-title">Bank Accounts &amp; Balances</div>

            <div class="toolbar-left">
              <div class="search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="bankSearchInput" class="form-control" placeholder="Search accounts..." onkeyup="filterBankTable()">
              </div>

              <select id="balanceFilterSelect" class="form-select" onchange="filterBankTable()">
                <option value="all">All Balances</option>
                <option value="recorded">Has Recorded Statement</option>
                <option value="positive">Positive Balance (> 0)</option>
                <option value="zero">Zero Balance (= 0)</option>
                <option value="negative">Overdrawn (< 0)</option>
              </select>
            </div>
          </div>

          <!-- DATA TABLE (ACCOUNT TYPE COLUMN REMOVED) -->
          <div style="overflow-x: auto;">
            <table class="data-table" id="banksTable">
              <thead>
                <tr>
                  <th style="width: 90px;">Code</th>
                  <th>Bank Account Name</th>
                  <th style="text-align: right;">System Ledger Balance</th>
                  <th style="text-align: right;">Recorded Statement</th>
                  <th style="text-align: center;">Reconciliation Variance</th>
                  <th style="text-align: center; width: 140px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($bankAccounts)): ?>
                  <tr>
                    <td colspan="6" style="text-align: center; padding: 24px; color: var(--text3);">
                      No bank accounts found with Account Type "Cash &amp; Cash Equivalents".
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($bankAccounts as $acct): 
                    $sysBal = (float)$acct['current_balance'];
                    $recBal = $acct['recorded_balance'];
                    
                    $sysPill = 'pill-green';
                    if ($sysBal == 0) $sysPill = 'status-pill';
                    elseif ($sysBal < 0) $sysPill = 'trend-down';

                    $variance = 0;
                    $hasRecorded = ($recBal !== null);
                    if ($hasRecorded) {
                        $variance = $recBal - $sysBal;
                    }
                  ?>
                    <tr class="bank-row" data-name="<?php echo htmlspecialchars(strtolower($acct['account_name'] . ' ' . $acct['account_code'])); ?>" data-balance="<?php echo $sysBal; ?>" data-recorded="<?php echo $hasRecorded ? '1' : '0'; ?>">
                      <td>
                        <span class="code-badge"><?php echo htmlspecialchars($acct['account_code']); ?></span>
                      </td>
                      <td>
                        <a href="view.php?id=<?php echo $acct['id']; ?>" class="td-name" style="text-decoration: none;" title="View bank movements &amp; history">
                          <?php echo htmlspecialchars($acct['account_name']); ?>
                        </a>
                        <?php if (!empty($acct['description'])): ?>
                          <div style="font-size: 11px; color: var(--text3); margin-top: 1px;">
                            <?php echo htmlspecialchars($acct['description']); ?>
                          </div>
                        <?php endif; ?>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        <span class="status-pill <?php echo $sysPill; ?>">
                          RWF <?php echo number_format($sysBal, 2); ?>
                        </span>
                      </td>
                      <td style="text-align: right;" class="td-bold">
                        <?php if ($hasRecorded): ?>
                          <div style="color: var(--purple);">
                            RWF <?php echo number_format($recBal, 2); ?>
                          </div>
                          <div style="font-size: 10.5px; color: var(--text3);">
                            As of <?php echo htmlspecialchars($acct['recorded_date']); ?>
                          </div>
                        <?php else: ?>
                          <span style="color: var(--text3); font-style: italic; font-size: 11.5px;">Not recorded</span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align: center;">
                        <?php if (!$hasRecorded): ?>
                          <span class="variance-badge variance-none">No Statement</span>
                        <?php elseif (abs($variance) < 0.01): ?>
                          <span class="variance-badge variance-reconciled">
                            <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            Balanced (0.00)
                          </span>
                        <?php else: ?>
                          <span class="variance-badge variance-diff">
                            Diff: RWF <?php echo number_format($variance, 2); ?>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td style="text-align: center;">
                        <div style="display: inline-flex; gap: 4px;">
                          <button type="button" class="btn-sm" onclick="openRecordModal(<?php echo $acct['id']; ?>, '<?php echo htmlspecialchars(addslashes($acct['account_name'])); ?>', '<?php echo $hasRecorded ? $recBal : ''; ?>')" title="Record statement balance">
                            Record
                          </button>
                          <a href="view.php?id=<?php echo $acct['id']; ?>" class="btn-sm btn-primary" style="text-decoration: none;" title="View details">
                            View
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
              <?php if (!empty($bankAccounts)): ?>
                <tfoot>
                  <tr style="background: var(--bg); font-weight: 600; border-top: 1.5px solid var(--border);">
                    <td colspan="2" style="text-align: right; color: var(--text2);">TOTAL PORTFOLIO:</td>
                    <td style="text-align: right;" class="td-bold">
                      <span class="status-pill pill-green">
                        RWF <?php echo number_format($totalPortfolioBalance, 2); ?>
                      </span>
                    </td>
                    <td style="text-align: right;" class="td-bold" style="color: var(--purple);">
                      RWF <?php echo number_format($totalRecordedAll, 2); ?>
                    </td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              <?php endif; ?>
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
          Record Bank Account Balance
        </div>
        <button class="bank-modal-close" onclick="closeRecordModal()">&times;</button>
      </div>
      
      <form id="recordBalanceForm" onsubmit="saveBankBalance(event)">
        <div class="bank-modal-body">
          
          <div class="form-group">
            <label for="modalAccountId">Select Bank Account *</label>
            <select id="modalAccountId" name="account_id" class="form-select" required>
              <option value="">-- Choose a Bank Account --</option>
              <?php foreach ($bankAccounts as $acct): ?>
                <option value="<?php echo $acct['id']; ?>">
                  <?php echo htmlspecialchars($acct['account_code'] . ' — ' . $acct['account_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="modalAsOfDate">Statement As-Of Date *</label>
            <input type="date" id="modalAsOfDate" name="as_of_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
          </div>

          <div class="form-group">
            <label for="modalBalance">Recorded Statement Balance (RWF) *</label>
            <input type="number" step="0.01" id="modalBalance" name="balance" class="form-control" placeholder="0.00" required>
          </div>

          <div class="form-group">
            <label for="modalNotes">Reference / Notes (Optional)</label>
            <input type="text" id="modalNotes" name="notes" class="form-control" placeholder="e.g., Monthly bank confirmation balance...">
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
    function openRecordModal(acctId = '', acctName = '', currentRecBal = '') {
      document.getElementById('recordBalanceForm').reset();
      document.getElementById('modalAsOfDate').value = new Date().toISOString().split('T')[0];
      document.getElementById('modalAlert').style.display = 'none';

      if (acctId) {
        document.getElementById('modalAccountId').value = acctId;
      }
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

    function filterBankTable() {
      const searchVal = document.getElementById('bankSearchInput').value.toLowerCase().trim();
      const filterVal = document.getElementById('balanceFilterSelect').value;
      const rows = document.querySelectorAll('.bank-row');

      rows.forEach(row => {
        const nameData = row.getAttribute('data-name') || '';
        const balData = parseFloat(row.getAttribute('data-balance') || 0);
        const hasRec = row.getAttribute('data-recorded') === '1';

        let matchesSearch = nameData.includes(searchVal);
        let matchesFilter = true;

        if (filterVal === 'recorded' && !hasRec) matchesFilter = false;
        if (filterVal === 'positive' && balData <= 0) matchesFilter = false;
        if (filterVal === 'zero' && balData !== 0) matchesFilter = false;
        if (filterVal === 'negative' && balData >= 0) matchesFilter = false;

        if (matchesSearch && matchesFilter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>
