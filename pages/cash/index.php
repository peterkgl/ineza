<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

if (!hasPermission($conn, $userId, 'view_balance_sheet')) {
    header("Location: ../dashboard");
    exit();
}

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
        $currentYear = (int)date('Y');
        $availableYears = [$currentYear, $currentYear - 1, $currentYear - 2];
    }
    return $availableYears;
}

function getCashAccounts($conn, $year) {
    $date = "$year-12-31";
    $sql = "
        SELECT a.id, a.account_code, a.account_name,
               COALESCE(SUM(CASE WHEN je.entry_date <= '$date' THEN jel.debit ELSE 0.00 END), 0.00) AS total_debit,
               COALESCE(SUM(CASE WHEN je.entry_date <= '$date' THEN jel.credit ELSE 0.00 END), 0.00) AS total_credit
        FROM accounts a
        JOIN account_types t ON a.account_type_id = t.id
        LEFT JOIN journal_entry_lines jel ON a.account_code = CAST(jel.account_id AS CHAR)
                                       AND a.account_type_id = jel.parent_account_id
        LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.statuss = 'POSTED'
        WHERE a.is_active = 1
          AND a.account_type_id = 1
        GROUP BY a.id
        ORDER BY a.account_code ASC";

    $result = mysqli_query($conn, $sql);
    $accounts = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $debit = (float)$row['total_debit'];
            $credit = (float)$row['total_credit'];
            $balance = $debit - $credit;
            $code = $row['account_code'];
            $accounts[] = [
                'code' => $code,
                'name' => $row['account_name'],
                'balance' => $balance,
                'link' => "../general_journal/index.php?start_date={$year}-01-01&end_date={$year}-12-31&account_code=" . urlencode($code)
            ];
        }
    }
    return $accounts;
}

$availableYears = getAvailableYears($conn);
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $availableYears[0];
if (!in_array($selectedYear, $availableYears, true)) {
    $availableYears[] = $selectedYear;
    rsort($availableYears);
}

$cashAccounts = getCashAccounts($conn, $selectedYear);
$totalCashBalance = 0.0;
foreach ($cashAccounts as $account) {
    $totalCashBalance += $account['balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Cash &amp; Cash Equivalents</title>
<meta name="description" content="Cash and cash equivalents account balances with links to general journal details.">
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

  <?php $page_title = "Cash & Cash Equivalents"; include '../include/navbar.php'; ?>

  <div class="content" id="cashContent">

    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          Cash &amp; Cash Equivalents
        </h1>
        <div class="page-sub">Accounts under Cash &amp; Cash Equivalents as at 31 December <?php echo $selectedYear; ?></div>
      </div>
      <div class="page-actions">
        <button class="btn-sm btn-primary" onclick="window.print()">Print</button>
      </div>
    </div>

    <div class="year-filter-container" style="max-width: 320px; margin-bottom: 20px;">
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

    <div class="stats-grid" style="grid-template-columns: minmax(220px, 1fr); margin-bottom: 24px;">
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/></svg>
          </div>
          <span class="stat-trend trend-up">Cash Total</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalCashBalance, 0); ?></div>
        <div class="stat-label">Cash &amp; cash equivalents balance</div>
      </div>
    </div>

    <div class="note-card">
      <div class="note-table-wrapper">
        <table class="note-table">
          <thead>
            <tr class="note-header-row">
              <td colspan="3">Cash &amp; Cash Equivalents Accounts</td>
            </tr>
            <tr class="year-header-row">
              <th>Account</th>
              <th>Balance (Frw)</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($cashAccounts)): ?>
              <tr>
                <td class="row-label" colspan="3" style="text-align: center;">No Cash &amp; Cash Equivalents accounts found for the selected year.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($cashAccounts as $account): ?>
                <tr>
                  <td class="row-label"><?php echo htmlspecialchars($account['code'] . ' - ' . $account['name']); ?></td>
                  <td class="num-val">
                    <a href="<?php echo htmlspecialchars($account['link']); ?>" style="color: inherit; text-decoration: none;">
                      <?php echo number_format($account['balance'], 0); ?>
                    </a>
                  </td>
                  <td style="text-align: center;"><a href="<?php echo htmlspecialchars($account['link']); ?>">View journal</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
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

    options.forEach(opt => {
        opt.addEventListener('click', function() {
            const yearVal = this.getAttribute('data-value');
            window.location.href = `?year=${yearVal}`;
        });
    });

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

    document.addEventListener('click', function() {
        dropdown.classList.remove('open');
    });

    dropdown.querySelector('.dropdown-menu').addEventListener('click', function(e) {
        e.stopPropagation();
    });
});
</script>

</body>
</html>
