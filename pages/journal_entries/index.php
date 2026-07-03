<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Access permission check (view_accounts fits journal entries view)
if (!hasPermission($conn, $userId, 'view_accounts')) {
    header("Location: ../dashboard");
    exit();
}

$canCreate = hasPermission($conn, $userId, 'create_account');

// Fetch Stats
$statsQuery = "SELECT 
                COUNT(*) as total_count,
                SUM(CASE WHEN statuss = 'DRAFT' THEN 1 ELSE 0 END) as draft_count,
                SUM(CASE WHEN statuss = 'POSTED' THEN 1 ELSE 0 END) as posted_count,
                SUM(CASE WHEN statuss = 'CANCELLED' THEN 1 ELSE 0 END) as cancelled_count
               FROM journal_entries";
$statsRes = mysqli_query($conn, $statsQuery);
$stats = mysqli_fetch_assoc($statsRes) ?? ['total_count' => 0, 'draft_count' => 0, 'posted_count' => 0, 'cancelled_count' => 0];

$debitsQuery = "SELECT SUM(debit) as total_debits 
                FROM journal_entry_lines jel
                JOIN journal_entries je ON jel.journal_entry_id = je.id
                WHERE je.statuss = 'POSTED'";
$debitsRes = mysqli_query($conn, $debitsQuery);
$debitsRow = mysqli_fetch_assoc($debitsRes);
$totalDebitsVal = $debitsRow['total_debits'] ?? 0.0;

// Fetch Journal Entries List
$listQuery = "SELECT je.*, 
                     COALESCE(SUM(jel.debit), 0.00) as total_amount,
                     CONCAT(u.first_name, ' ', u.last_name) as creator_name
              FROM journal_entries je
              LEFT JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
              LEFT JOIN users u ON je.created_by = u.id
              GROUP BY je.id
              ORDER BY je.entry_date DESC, je.id DESC";
$listResult = mysqli_query($conn, $listQuery);
$entries = [];
if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $entries[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Journal Entries</title>
<meta name="description" content="View, filter, and manage journal entries in the double entry ledger.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/journal_entries.css">
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

  <?php $page_title = "Journal Entries Ledger"; include '../include/navbar.php'; ?>

  <div class="content" id="journalListContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Journal Entries
        </h1>
        <div class="page-sub">View and manage double-entry accounting transactions</div>
      </div>
      <div class="page-actions">
        <?php if ($canCreate): ?>
          <a href="create" class="btn-sm btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            New Journal Entry
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Total Entries -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
          </div>
          <span class="stat-trend trend-blue">Total</span>
        </div>
        <div class="stat-val"><?php echo (int)$stats['total_count']; ?></div>
        <div class="stat-label">Total Journal Entries</div>
      </div>

      <!-- Total Debited Value -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">Posted</span>
        </div>
        <div class="stat-val">Frw <?php echo number_format($totalDebitsVal, 0); ?></div>
        <div class="stat-label">Total Debits Volume</div>
      </div>

      <!-- Posted Count -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Active</span>
        </div>
        <div class="stat-val"><?php echo (int)($stats['posted_count'] ?? 0); ?></div>
        <div class="stat-label">Posted Transactions</div>
      </div>

      <!-- Cancelled Count -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--red-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--red)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          </div>
          <span class="stat-trend trend-down">Void</span>
        </div>
        <div class="stat-val"><?php echo (int)($stats['cancelled_count'] ?? 0); ?></div>
        <div class="stat-label">Cancelled Entries</div>
      </div>
    </div>

    <!-- ===== TABLE CARD ===== -->
    <div class="journal-container">
      <div class="journal-card">
        <div class="journal-card-header" style="border-bottom: none; margin-bottom: 0;">
          <div class="journal-card-title">Transaction History Log</div>
          <div style="display: flex; gap: 8px;">
            <input type="text" id="searchInput" class="form-control" placeholder="Search J/E No or Desc..." style="max-width: 240px; font-size: 12.5px;">
            <select id="statusFilter" class="form-control" style="max-width: 140px; font-size: 12.5px;">
              <option value="all">All Statuses</option>
              <option value="POSTED">Posted</option>
              <option value="DRAFT">Draft</option>
              <option value="CANCELLED">Cancelled</option>
            </select>
          </div>
        </div>

        <div class="note-table-wrapper" style="margin-top: 14px;">
          <table class="journal-table" id="journalTable">
            <thead>
              <tr>
                <th>Date</th>
                <th>Journal Number</th>
                <th>Description</th>
                <th>Created By</th>
                <th class="num-val">Total Amount (Frw)</th>
                <th style="text-align: center;">Status</th>
                <th style="text-align: center;">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (count($entries) === 0): ?>
                <tr>
                  <td colspan="7" style="text-align: center; color: var(--text2); padding: 24px;">No journal entries found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($entries as $entry): ?>
                  <tr class="journal-row" data-status="<?php echo $entry['statuss']; ?>">
                    <td><?php echo date('Y-m-d', strtotime($entry['entry_date'])); ?></td>
                    <td style="font-weight: 500; font-family: 'Inter', monospace;" class="je-no"><?php echo htmlspecialchars($entry['journal_no']); ?></td>
                    <td class="je-desc"><?php echo htmlspecialchars($entry['description']); ?></td>
                    <td><?php echo htmlspecialchars($entry['creator_name'] ?? 'System'); ?></td>
                    <td class="num-val"><?php echo number_format($entry['total_amount'], 2); ?></td>
                    <td style="text-align: center;">
                      <span class="status-badge <?php echo strtolower($entry['statuss']); ?>">
                        <?php echo htmlspecialchars($entry['statuss']); ?>
                      </span>
                    </td>
                    <td style="text-align: center;">
                      <a href="view?id=<?php echo $entry['id']; ?>" class="btn-danger-outline" style="padding: 4px 8px; font-size: 11px; text-decoration: none;">View</a>
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

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const rows = document.querySelectorAll('.journal-row');

    function filterTable() {
        const query = searchInput.value.toLowerCase().trim();
        const status = statusFilter.value;

        rows.forEach(row => {
            const rowStatus = row.getAttribute('data-status');
            const jeNo = row.querySelector('.je-no').textContent.toLowerCase();
            const jeDesc = row.querySelector('.je-desc').textContent.toLowerCase();

            const statusMatch = (status === 'all' || rowStatus === status);
            const queryMatch = (query === '' || jeNo.includes(query) || jeDesc.includes(query));

            if (statusMatch && queryMatch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterTable);
    statusFilter.addEventListener('change', filterTable);
});
</script>
</body>
</html>
