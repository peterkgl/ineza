<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Access check
if (!hasPermission($conn, $userId, 'view_journal_entries')) {
    header("Location: ../dashboard");
    exit();
}

$canCancel = hasPermission($conn, $userId, 'cancel_journal_entry');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index");
    exit();
}

// Fetch Header
$query = "SELECT je.*, CONCAT(u.first_name, ' ', u.last_name) as creator_name
          FROM journal_entries je
          LEFT JOIN users u ON je.created_by = u.id
          WHERE je.id = $id LIMIT 1";
$result = mysqli_query($conn, $query);
if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: index");
    exit();
}
$entry = mysqli_fetch_assoc($result);

// Fetch Lines
$linesQuery = "SELECT jel.*, a.account_code, a.account_name, c.code as currency_code
               FROM journal_entry_lines jel
               LEFT JOIN accounts a ON (jel.account_id = a.id OR a.account_code = CAST(jel.account_id AS CHAR))
               JOIN currencies c ON jel.currency_id = c.id
               WHERE jel.journal_entry_id = $id
               ORDER BY jel.debit DESC, jel.id ASC";
$linesResult = mysqli_query($conn, $linesQuery);
$lines = [];
$totalDebit = 0.0;
$totalCredit = 0.0;
if ($linesResult) {
    while ($row = mysqli_fetch_assoc($linesResult)) {
        $lines[] = $row;
        $totalDebit += (float)$row['debit'];
        $totalCredit += (float)$row['credit'];
    }
}

if (empty($_SESSION['journal_entries_token'])) {
    $_SESSION['journal_entries_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Journal Entry <?php echo htmlspecialchars($entry['journal_no']); ?></title>
<meta name="description" content="View detail lines of manual journal entries.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

  <?php $page_title = "Journal Entry Detail View"; include '../include/navbar.php'; ?>

  <div class="content" id="journalViewContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Journal Entry Details
        </h1>
        <div class="page-sub">Reference: <?php echo htmlspecialchars($entry['journal_no']); ?></div>
      </div>
      <div class="page-actions">
        <a href="./index" class="btn-sm" style="text-decoration: none;">
          Back to List
        </a>
        <button class="btn-sm" onclick="window.print()">
          Print Voucher
        </button>
        <?php if ($entry['statuss'] === 'POSTED' && $canCancel): ?>
          <button class="btn-sm btn-danger-outline" id="cancelEntryBtn">
            Cancel &amp; Void
          </button>
        <?php endif; ?>
      </div>
    </div>

    <!-- ===== DETAILS CARD ===== -->
    <div class="journal-container">
      
      <div class="journal-card">
        <div class="journal-card-header">
          <div>
            <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">INEZA African Mining Ltd</div>
            <h3 style="font-size: 16px; font-weight: 600; margin: 0; color: var(--text);">Journal Entry Voucher</h3>
          </div>
          <div>
            <span class="status-badge <?php echo strtolower($entry['statuss']); ?>">
              <?php echo htmlspecialchars($entry['statuss']); ?>
            </span>
          </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px; font-size: 13px;">
          <div>
            <div style="color: var(--text2); margin-bottom: 4px;">Journal Number</div>
            <div style="font-weight: 600; font-family: monospace; font-size: 13.5px;"><?php echo htmlspecialchars($entry['journal_no']); ?></div>
          </div>
          <div>
            <div style="color: var(--text2); margin-bottom: 4px;">Posting Date</div>
            <div style="font-weight: 600;"><?php echo date('Y-m-d', strtotime($entry['entry_date'])); ?></div>
          </div>
          <div>
            <div style="color: var(--text2); margin-bottom: 4px;">Recorded By</div>
            <div style="font-weight: 600;"><?php echo htmlspecialchars($entry['creator_name'] ?? 'System'); ?></div>
          </div>
          <div>
            <div style="color: var(--text2); margin-bottom: 4px;">Created On</div>
            <div style="font-weight: 600;"><?php echo date('Y-m-d H:i:s', strtotime($entry['created_at'])); ?></div>
          </div>
        </div>

        <div style="font-size: 13px; margin-bottom: 24px; border-top: 1px solid var(--border); padding-top: 14px;">
          <div style="color: var(--text2); margin-bottom: 4px;">General Memo / Explanation</div>
          <div style="font-weight: 500; font-size: 13.5px; color: var(--text);"><?php echo htmlspecialchars($entry['description']); ?></div>
        </div>

        <!-- Ledger Lines Table -->
        <div class="note-table-wrapper">
          <table class="journal-table">
            <thead>
              <tr>
                <th style="width: 15%;">Account Code</th>
                <th style="width: 30%;">Account Name</th>
                <th style="width: 25%;">Narration / Line Description</th>
                <th class="num-val" style="width: 15%;">Debit (Frw)</th>
                <th class="num-val" style="width: 15%;">Credit (Frw)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($lines as $line): ?>
                <tr>
                  <td style="font-family: monospace; font-size: 12.5px; font-weight: 500;"><?php echo htmlspecialchars($line['account_code']); ?></td>
                  <td style="font-weight: 500;"><?php echo htmlspecialchars($line['account_name']); ?></td>
                  <td style="color: var(--text2); font-size: 12.5px;"><?php echo htmlspecialchars($line['description'] ?? ''); ?></td>
                  <td class="num-val"><?php echo $line['debit'] > 0 ? number_format($line['debit'], 2) : '-'; ?></td>
                  <td class="num-val"><?php echo $line['credit'] > 0 ? number_format($line['credit'], 2) : '-'; ?></td>
                </tr>
              <?php endforeach; ?>
              
              <!-- Totals row -->
              <tr style="font-weight: 600; background-color: var(--bg); border-top: 1.5px solid var(--border-hover);">
                <td colspan="3" style="text-align: right; padding-right: 18px; color: var(--text);">Totals</td>
                <td class="num-val" style="color: var(--green); border-bottom: 4px double var(--text);"><?php echo number_format($totalDebit, 2); ?></td>
                <td class="num-val" style="color: var(--green); border-bottom: 4px double var(--text);"><?php echo number_format($totalCredit, 2); ?></td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>
</div>

<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
<?php if ($entry['statuss'] === 'POSTED' && $canCancel): ?>
<script>
document.getElementById('cancelEntryBtn').addEventListener('click', function() {
    if (confirm("Are you sure you want to cancel and void this journal entry? This will flag the transaction as CANCELLED and update audit trails. This action is irreversible.")) {
        const formData = new FormData();
        formData.append('id', <?php echo $entry['id']; ?>);
        formData.append('token', '<?php echo $_SESSION['journal_entries_token']; ?>');

        fetch('api.php?action=cancel', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                alert(res.message);
                window.location.reload();
            } else {
                alert("Error: " + res.message);
            }
        })
        .catch(err => {
            alert("System error. Could not connect to API.");
        });
    }
});
</script>
<?php endif; ?>
</body>
</html>
