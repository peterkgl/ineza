<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_bank_reconciliation') && 
    !hasPermission($conn, $userId, 'manage_bank_reconciliation') &&
    !hasPermission($conn, $userId, 'view_report_bank_recon_rwf') &&
    !hasPermission($conn, $userId, 'view_report_bank_recon_usd')) {
    header("Location: ../dashboard");
    exit();
}

// 1. Fetch ONLY accounts whose Account Type is "Cash & Cash Equivalents"
$bank_accounts = [];
$accQuery = mysqli_query($conn, "
    SELECT a.id, a.account_code, a.account_name, at.name as type_name 
    FROM accounts a 
    JOIN account_types at ON a.account_type_id = at.id 
    WHERE (at.name = 'Cash & Cash Equivalents' OR a.account_type_id = 1) 
      AND a.is_active = 1 
    ORDER BY a.account_name ASC
");

if ($accQuery && mysqli_num_rows($accQuery) > 0) {
    while ($row = mysqli_fetch_assoc($accQuery)) {
        $acct_id = (int)$row['id'];
        $acct_code = $row['account_code'];
        $acct_name = $row['account_name'];

        // Determine matching statement slug for report storage
        $slug = 'acc_' . $acct_id;
        if ($acct_code === '1031' || $acct_code === '1010-03' || strpos(strtolower($acct_name), 'rwf') !== false) {
            $slug = 'bank_recon_rwf';
        } elseif ($acct_code === '1041' || $acct_code === '1010-01' || strpos(strtolower($acct_name), 'usd') !== false) {
            $slug = 'bank_recon_usd';
        } elseif ($acct_code === '1051' || $acct_code === '1010-04' || strpos(strtolower($acct_name), 'euro') !== false || strpos(strtolower($acct_name), 'eur') !== false) {
            $slug = 'bank_recon_euro';
        }

        // Currency symbol detection
        $curr_code = 'RWF';
        $curr_sym = 'RWF ';
        if (strpos($acct_name, '$') !== false || strpos($acct_name, 'USD') !== false || $acct_code === '1041' || $acct_code === '1010-01') {
            $curr_code = 'USD';
            $curr_sym = '$ ';
        } elseif (strpos($acct_name, '€') !== false || strpos($acct_name, 'EUR') !== false || $acct_code === '1051' || $acct_code === '1010-04') {
            $curr_code = 'EUR';
            $curr_sym = '€ ';
        }

        $bank_accounts[$slug] = [
            'id' => $acct_id,
            'slug' => $slug,
            'title' => 'Bank Reconciliation — ' . $acct_name,
            'short_name' => $acct_name,
            'account_name' => $acct_name,
            'account_code' => $acct_code,
            'currency_code' => $curr_code,
            'currency_symbol' => $curr_sym,
        ];
    }
}

// Active Bank Account Selection
$report_slug = $_GET['account'] ?? '';
if (empty($report_slug) || !array_key_exists($report_slug, $bank_accounts)) {
    $report_slug = !empty($bank_accounts) ? array_key_first($bank_accounts) : 'bank_recon_rwf';
}

$acct_info = $bank_accounts[$report_slug] ?? [
    'id' => 0,
    'slug' => 'bank_recon_rwf',
    'title' => 'Bank Reconciliation',
    'short_name' => 'Bank Account',
    'account_name' => 'Bank Account',
    'account_code' => '1010',
    'currency_code' => 'RWF',
    'currency_symbol' => 'RWF '
];

$current_acct_id = (int)$acct_info['id'];
$acct_code = $acct_info['account_code'];
$curr_symbol = $acct_info['currency_symbol'];
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Reconciled Status View Filter (all, reconciled, unreconciled)
$status_view = $_GET['status_view'] ?? 'all';

// Target As-Of Date Selection via Date Input (type="date")
$target_date = $_GET['as_of_date'] ?? date('Y-m-d');
$target_date_esc = mysqli_real_escape_string($conn, $target_date);

// 2. Fetch System Movements (journal_entry_lines + journal_entries) for selected account
$acct_code_esc = mysqli_real_escape_string($conn, $acct_code);
$num_code = (int)$acct_code;

$movements_query = "
    SELECT jel.id, jel.journal_entry_id, jel.debit, jel.credit, jel.description as line_desc, 
           jel.is_reconciled, jel.reconciled_at, 
           je.entry_date, je.journal_no as entry_number, je.description as je_desc, je.journal_no as reference, je.created_at
    FROM journal_entry_lines jel
    JOIN journal_entries je ON jel.journal_entry_id = je.id
    WHERE (jel.account_id = $current_acct_id OR jel.account_id = $num_code OR CAST(jel.account_id AS CHAR) = '$acct_code_esc')
      AND (je.statuss = 'POSTED' OR je.statuss IS NULL OR je.statuss = '')
      AND je.entry_date <= '{$target_date_esc}'
    ORDER BY je.entry_date DESC, jel.id DESC
";
$movements_res = mysqli_query($conn, $movements_query);

$combined_movements = [];

$total_debits = 0.0;
$total_credits = 0.0;

$reconciled_count = 0;
$reconciled_debits = 0.0;
$reconciled_credits = 0.0;

$unreconciled_count = 0;
$unreconciled_debits = 0.0;
$unreconciled_credits = 0.0;

if ($movements_res) {
    while ($row = mysqli_fetch_assoc($movements_res)) {
        $debit = (float)$row['debit'];
        $credit = (float)$row['credit'];
        $is_rec = (int)($row['is_reconciled'] ?? 0);

        $total_debits += $debit;
        $total_credits += $credit;

        if ($is_rec === 1) {
            $reconciled_count++;
            $reconciled_debits += $debit;
            $reconciled_credits += $credit;
        } else {
            $unreconciled_count++;
            $unreconciled_debits += $debit;
            $unreconciled_credits += $credit;
        }

        $row['origin'] = 'journal_line';
        $row['display_ref'] = $row['entry_number'] ?: ($row['reference'] ?: ('JE#' . $row['journal_entry_id']));
        $row['display_desc'] = $row['line_desc'] ?: $row['je_desc'];
        
        $combined_movements[] = $row;
    }
}

// 3. Fetch & Integrate Statement Adjustment Items from bank_recon_items Table
$items_query = "
    SELECT id, report_slug, as_of_date, item_type, item_date, reference, amount, description, is_reconciled, reconciled_at 
    FROM bank_recon_items 
    WHERE (report_slug = '{$report_slug_esc}' OR report_slug = 'acc_{$current_acct_id}')
      AND item_date <= '{$target_date_esc}'
    ORDER BY item_date DESC, id DESC
";
$items_res = mysqli_query($conn, $items_query);
$recon_items = [];
$total_transit = 0.0;
$total_outstanding = 0.0;
$total_unrecorded = 0.0;

if ($items_res) {
    while ($it = mysqli_fetch_assoc($items_res)) {
        $amt = (float)$it['amount'];
        $type = $it['item_type'];
        $is_rec = (int)($it['is_reconciled'] ?? 0);

        $debit = 0.0;
        $credit = 0.0;

        if ($type === 'deposit_in_transit' || $type === 'unrecorded_payment') {
            $debit = $amt;
            $total_transit += ($type === 'deposit_in_transit' ? $amt : 0.0);
            $total_unrecorded += ($type === 'unrecorded_payment' ? $amt : 0.0);
        } else {
            $credit = $amt;
            $total_outstanding += $amt;
        }

        if ($is_rec === 1) {
            $reconciled_count++;
            $reconciled_debits += $debit;
            $reconciled_credits += $credit;
        } else {
            $unreconciled_count++;
            $unreconciled_debits += $debit;
            $unreconciled_credits += $credit;
        }

        $type_label = ($type === 'deposit_in_transit') ? 'Deposit in Transit' : (($type === 'outstanding_check') ? 'Outstanding Check' : 'Unrecorded Payment');

        $recon_items[] = $it;
        $combined_movements[] = [
            'id' => (int)$it['id'],
            'origin' => 'recon_item',
            'journal_entry_id' => 0,
            'debit' => $debit,
            'credit' => $credit,
            'line_desc' => $it['description'] . ' (' . $type_label . ')',
            'is_reconciled' => $is_rec,
            'reconciled_at' => $it['reconciled_at'],
            'entry_date' => $it['item_date'],
            'entry_number' => $it['reference'] ?: ('RECON#' . $it['id']),
            'je_desc' => $it['description'],
            'reference' => $it['reference'],
            'display_ref' => $it['reference'] ?: ('RECON#' . $it['id']),
            'display_desc' => '[Adjustment] ' . $it['description'] . ' (' . $type_label . ')'
        ];
    }
}

// Sort combined movements by entry_date DESC
usort($combined_movements, function($a, $b) {
    return strcmp($b['entry_date'], $a['entry_date']);
});

// Query offset counterparty accounts for all journal movements
$jeIds = [];
foreach ($combined_movements as $cm) {
    if (($cm['origin'] ?? '') === 'journal_line' && !empty($cm['journal_entry_id'])) {
        $jeIds[] = (int)$cm['journal_entry_id'];
    }
}

$offsetMap = [];
if (!empty($jeIds)) {
    $jeIdsStr = implode(',', array_unique($jeIds));
    $offsetRes = mysqli_query($conn, "
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
          AND (jel.account_id != $current_acct_id AND jel.account_id != $num_code AND CAST(jel.account_id AS CHAR) != '$acct_code_esc')
    ");
    if ($offsetRes) {
        while ($offRow = mysqli_fetch_assoc($offsetRes)) {
            $jeId = (int)$offRow['journal_entry_id'];
            if (!isset($offsetMap[$jeId])) {
                $offsetMap[$jeId] = [];
            }
            $offsetName = !empty($offRow['account_name']) ? ($offRow['account_code'] . ' - ' . $offRow['account_name']) : ('Account #' . $offRow['account_id']);
            $offsetMap[$jeId][] = $offsetName;
        }
    }
}

// Enhance each movement with counterparty "Where Money Came From" and "Where Money Went" info
foreach ($combined_movements as &$cm) {
    $debit = (float)$cm['debit'];
    $credit = (float)$cm['credit'];
    $origin = $cm['origin'] ?? 'journal_line';
    $bankName = $acct_info['account_name'] . ' (' . $acct_info['account_code'] . ')';
    
    if ($origin === 'journal_line') {
        $jeId = (int)$cm['journal_entry_id'];
        $offsets = $offsetMap[$jeId] ?? [];
        $offsetText = !empty($offsets) ? implode(', ', array_unique($offsets)) : 'General Ledger Account';
        
        if ($credit > 0) {
            $cm['flow_type'] = 'Payment Made';
            $cm['counterparty_label'] = 'Paid To: ' . $offsetText;
            $cm['flow_badge_class'] = 'trend-down';
            $cm['money_came_from'] = $bankName;
            $cm['money_went_to'] = $offsetText;
        } else {
            $cm['flow_type'] = 'Amount Received';
            $cm['counterparty_label'] = 'Received From: ' . $offsetText;
            $cm['flow_badge_class'] = 'pill-green';
            $cm['money_came_from'] = $offsetText;
            $cm['money_went_to'] = $bankName;
        }
    } else {
        if ($credit > 0) {
            $cm['flow_type'] = 'Payment Made';
            $cm['counterparty_label'] = 'Paid To: Statement Adjustment / Bank Outflow';
            $cm['flow_badge_class'] = 'trend-down';
            $cm['money_came_from'] = $bankName;
            $cm['money_went_to'] = 'Bank Outflow Adjustment';
        } else {
            $cm['flow_type'] = 'Amount Received';
            $cm['counterparty_label'] = 'Received From: Statement Adjustment / Bank Inflow';
            $cm['flow_badge_class'] = 'pill-green';
            $cm['money_came_from'] = 'Bank Inflow Adjustment';
            $cm['money_went_to'] = $bankName;
        }
    }

    // Format Date and Time
    $datePart = $cm['entry_date'] ?? date('Y-m-d');
    $timePart = !empty($cm['created_at']) ? date('H:i:s', strtotime($cm['created_at'])) : '00:00:00';
    $cm['formatted_datetime'] = $datePart . ' ' . $timePart;
}
unset($cm);

// Filter combined movements according to status view
$filtered_movements = [];
foreach ($combined_movements as $cm) {
    $is_rec = (int)$cm['is_reconciled'];
    if ($status_view === 'reconciled' && $is_rec === 1) {
        $filtered_movements[] = $cm;
    } elseif ($status_view === 'unreconciled' && $is_rec === 0) {
        $filtered_movements[] = $cm;
    } elseif ($status_view === 'all') {
        $filtered_movements[] = $cm;
    }
}

$gl_book_balance = $total_debits - $total_credits;
$reconciled_net = $reconciled_debits - $reconciled_credits;
$unreconciled_net = $unreconciled_debits - $unreconciled_credits;

// 4. Fetch Bank Statement Ending Balance from Database (Matching account_id and report_slug)
$statement_query = "SELECT balance, as_of_date FROM bank_statement_balances 
                    WHERE (account_id = {$current_acct_id} OR report_slug = '{$report_slug_esc}') 
                      AND as_of_date <= '{$target_date_esc}' 
                    ORDER BY as_of_date DESC, id DESC LIMIT 1";
$statement_res = mysqli_query($conn, $statement_query);
$statement_row = $statement_res ? mysqli_fetch_assoc($statement_res) : null;
$statement_balance = $statement_row ? (float)$statement_row['balance'] : 0.0;
$statement_date = $statement_row ? $statement_row['as_of_date'] : $target_date;

$total_additions = $total_transit + $total_unrecorded;
$adjusted_bank = $statement_balance + $total_additions - $total_outstanding;
$difference = $adjusted_bank - $gl_book_balance;
$is_fully_reconciled = (abs($difference) < 0.001);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — <?php echo htmlspecialchars($acct_info['title']); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/reports.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
<style>
/* --- Bank Reconciliation Styling --- */
.recon-container {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 12px;
}

/* Dynamic Filter Card Panel */
.recon-filter-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 14px 18px;
}

.filter-form-grid {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 12px;
}

.filter-field-group {
  flex: 1;
  min-width: 200px;
}

.filter-field-group label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: var(--text3);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  margin-bottom: 4px;
}

.form-control-select {
  width: 100%;
  padding: 8px 12px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 12.5px;
  font-weight: 500;
  outline: none;
  font-family: inherit;
  cursor: pointer;
  transition: border-color 0.15s ease;
}

.form-control-select:focus {
  border-color: var(--orange);
}

.filter-actions-group {
  display: flex;
  align-items: center;
  gap: 6px;
}

/* Hero Banner Card */
.recon-hero-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 16px;
}

.hero-left-title h3 {
  font-size: 15px;
  font-weight: 600;
  color: var(--text);
  margin: 0 0 4px 0;
  display: flex;
  align-items: center;
  gap: 8px;
}

.hero-left-title p {
  margin: 0;
  color: var(--text3);
  font-size: 12px;
}

/* Metrics Summary Grid */
.recon-metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 10px;
}

.recon-metric-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 14px 16px;
  transition: border-color 0.15s ease;
}

.recon-metric-card:hover {
  border-color: var(--border-hover);
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.metric-title {
  font-size: 10.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text3);
}

.metric-icon-box {
  width: 28px;
  height: 28px;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
}

.recon-metric-card.card-bank .metric-icon-box { background: var(--blue-bg); color: var(--blue); }
.recon-metric-card.card-add .metric-icon-box { background: var(--green-bg); color: var(--green); }
.recon-metric-card.card-deduct .metric-icon-box { background: var(--amber-bg); color: var(--amber); }
.recon-metric-card.card-book .metric-icon-box { background: rgba(124, 58, 237, 0.1); color: #7c3aed; }

.metric-val-num {
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  letter-spacing: -0.01em;
}

.metric-sub-note {
  font-size: 11px;
  color: var(--text3);
  margin-top: 4px;
}

.status-badge-hero {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 11.5px;
}

.status-badge-hero.balanced {
  background: var(--green-bg);
  color: var(--green);
}

.status-badge-hero.unbalanced {
  background: var(--red-bg);
  color: var(--red);
}

/* Tab Bar for View Modes */
.view-tabs-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  background: var(--card);
  padding: 10px 14px;
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  flex-wrap: wrap;
}

.tab-buttons-group {
  display: flex;
  gap: 6px;
}

.tab-btn-item {
  padding: 6px 14px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  color: var(--text2);
  background: var(--bg);
  border: 1px solid var(--border);
  transition: all 0.15s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.tab-btn-item:hover {
  color: var(--text);
}

.tab-btn-item.active {
  background: var(--orange);
  color: #ffffff;
  border-color: var(--orange);
  font-weight: 600;
}

/* Master Table Section */
.recon-table-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.recon-table-header {
  padding: 14px 16px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.recon-table-header h4 {
  margin: 0;
  font-size: 13.5px;
  font-weight: 600;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 8px;
}

.recon-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12.5px;
}

.recon-table th {
  background: var(--bg);
  color: var(--text3);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 10.5px;
  letter-spacing: 0.5px;
  padding: 10px 14px;
  border-bottom: 1px solid var(--border);
  text-align: left;
}

.recon-table td {
  padding: 10px 14px;
  border-bottom: 1px solid var(--table-border);
  color: var(--text2);
  vertical-align: middle;
}

.recon-table tr:hover td {
  background-color: var(--table-hover);
}

.recon-table tr.row-checked {
  background-color: var(--green-bg) !important;
}

[data-theme="dark"] .recon-table tr.row-checked {
  background-color: rgba(12, 50, 39, 0.4) !important;
}

.status-pill-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 8px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.status-pill-badge.reconciled {
  background: var(--green);
  color: #ffffff;
}

.status-pill-badge.pending {
  background: var(--bg);
  color: var(--text3);
  border: 1px solid var(--border);
}

.recon-chk-box {
  width: 16px;
  height: 16px;
  cursor: pointer;
  accent-color: var(--green);
}

/* Modals */
.modal-backdrop {
  position: fixed;
  top: 0; left: 0; width: 100vw; height: 100vh;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(2px);
  display: none;
  justify-content: center;
  align-items: center;
  z-index: 9999;
}

.modal-backdrop.open { display: flex; }

.modal-box {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  width: 90%;
  max-width: 480px;
  padding: 20px 24px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  animation: modalSlide 0.2s ease-out;
}

@keyframes modalSlide {
  from { opacity: 0; transform: translateY(8px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal-head {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 16px; border-bottom: 1px solid var(--border); padding-bottom: 10px;
}

.modal-head h3 { margin: 0; font-size: 14px; font-weight: 600; color: var(--text); }

.modal-close-btn { background: none; border: none; font-size: 18px; color: var(--text3); cursor: pointer; }
.modal-close-btn:hover { color: var(--text); }

.form-group-field { margin-bottom: 14px; }
.form-group-field label { display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text3); margin-bottom: 4px; }
.form-group-field input, .form-group-field select {
  width: 100%; padding: 8px 10px; border: 1px solid var(--border); border-radius: var(--radius); font-size: 12.5px;
  box-sizing: border-box; outline: none; background: var(--card); color: var(--text); font-family: inherit;
  transition: border-color 0.15s;
}

.form-group-field input:focus, .form-group-field select:focus {
  border-color: var(--orange);
}

.modal-foot { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }

@media print {
  .sidebar, .navbar, .recon-filter-card, .view-tabs-bar, .no-print, .recon-chk-box { display: none !important; }
  .content { padding: 0 !important; }
  body, .main { background: #ffffff !important; }
}
</style>
</head>
<body>

<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="main">

  <?php $page_title = "Bank Reconciliation"; include __DIR__ . '/../include/navbar.php'; ?>

  <div class="content" id="bankReconContent">

    <!-- Page Header -->
    <div class="page-header no-print">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
          Bank Reconciliation
          <span class="code-badge" style="margin-left: 6px; font-size: 11px; font-weight: 600; color: var(--orange);"><?php echo htmlspecialchars($acct_info['currency_code']); ?></span>
        </h1>
        <div class="page-sub">Match System Movements vs Official Bank Statements for INEZA African Mining Ltd</div>
      </div>
      <div class="page-actions">
        <button onclick="openStatementModal()" class="btn-sm">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit Bank Balance
        </button>
        <button onclick="openReconItemModal()" class="btn-sm btn-primary">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Statement Adjustment
        </button>
        <button onclick="window.print()" class="btn-sm">
          <svg class="btn-icon" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print Statement
        </button>
      </div>
    </div>

    <div class="recon-container">

      <!-- Dynamic Filter Panel Dropdowns -->
      <div class="recon-filter-card no-print">
        <form method="GET" action="" class="filter-form-grid">
          
          <!-- Dropdown 1: Select Cash / Bank Account (Only Cash & Cash Equivalents accounts) -->
          <div class="filter-field-group">
            <label for="filter_account">Select Cash / Bank Account</label>
            <select name="account" id="filter_account" class="form-control-select" onchange="this.form.submit()">
              <?php foreach ($bank_accounts as $slug_key => $acc): ?>
                <option value="<?php echo htmlspecialchars($slug_key); ?>" <?php echo ($report_slug === $slug_key) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($acc['account_name'] . ' (' . $acc['account_code'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Field 2: Target Date (date input type="date") -->
          <div class="filter-field-group">
            <label for="filter_as_of_date">Target Date</label>
            <input type="date" 
                   name="as_of_date" 
                   id="filter_as_of_date" 
                   class="form-control-select" 
                   value="<?php echo htmlspecialchars($target_date); ?>" 
                   onchange="this.form.submit()">
          </div>

          <!-- Dropdown 3: Transaction Status Filter -->
          <div class="filter-field-group">
            <label for="filter_status_view">Movement Status View</label>
            <select name="status_view" id="filter_status_view" class="form-control-select" onchange="this.form.submit()">
              <option value="all" <?php echo ($status_view === 'all') ? 'selected' : ''; ?>>All Movements (<?php echo count($combined_movements); ?>)</option>
              <option value="reconciled" <?php echo ($status_view === 'reconciled') ? 'selected' : ''; ?>>✓ Checked / Reconciled (<?php echo $reconciled_count; ?>)</option>
              <option value="unreconciled" <?php echo ($status_view === 'unreconciled') ? 'selected' : ''; ?>>Unchecked / Pending (<?php echo $unreconciled_count; ?>)</option>
            </select>
          </div>

          <!-- Filter Actions -->
          <div class="filter-actions-group">
            <button type="submit" class="btn-sm btn-primary">
              <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
              Apply Filter
            </button>
            <a href="index.php?account=<?php echo urlencode($report_slug); ?>" class="btn-sm">Reset Date</a>
          </div>

        </form>
      </div>

      <!-- Hero Summary Card -->
      <div class="recon-hero-card">
        <div class="hero-left-title">
          <h3>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <?php echo htmlspecialchars($acct_info['account_name']); ?>
          </h3>
          <p>Reconciliation Period As Of: <strong><?php echo date('F d, Y', strtotime($target_date)); ?></strong> | GL Account: <code><?php echo htmlspecialchars($acct_code); ?></code></p>
        </div>
        <div class="no-print">
          <?php if ($is_fully_reconciled): ?>
            <span class="status-badge-hero balanced">✓ Statement & GL Books Reconciled ($0.00)</span>
          <?php else: ?>
            <span class="status-badge-hero unbalanced">Variance: <?php echo $curr_symbol . number_format($difference, 2); ?></span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Metrics Summary Grid -->
      <div class="recon-metrics-grid">
        
        <div class="recon-metric-card card-bank">
          <div class="metric-header">
            <span class="metric-title">Ending Bank Balance</span>
            <div class="metric-icon-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
            </div>
          </div>
          <div class="metric-val-num"><?php echo $curr_symbol . number_format($statement_balance, 2); ?></div>
          <div class="metric-sub-note">Bank Statement Ending (<?php echo htmlspecialchars($statement_date); ?>)</div>
        </div>

        <div class="recon-metric-card card-add">
          <div class="metric-header">
            <span class="metric-title">✓ Checked / Reconciled</span>
            <div class="metric-icon-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
          </div>
          <div class="metric-val-num" id="metric-reconciled-sum"><?php echo $curr_symbol . number_format($reconciled_net, 2); ?></div>
          <div class="metric-sub-note"><strong id="metric-reconciled-count"><?php echo $reconciled_count; ?></strong> transactions verified matching statement</div>
        </div>

        <div class="recon-metric-card card-deduct">
          <div class="metric-header">
            <span class="metric-title">Unchecked / Pending</span>
            <div class="metric-icon-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
          </div>
          <div class="metric-val-num" id="metric-unreconciled-sum"><?php echo $curr_symbol . number_format($unreconciled_net, 2); ?></div>
          <div class="metric-sub-note"><strong id="metric-unreconciled-count"><?php echo $unreconciled_count; ?></strong> transactions awaiting checkoff</div>
        </div>

        <div class="recon-metric-card card-book">
          <div class="metric-header">
            <span class="metric-title">GL System Book Balance</span>
            <div class="metric-icon-box">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            </div>
          </div>
          <div class="metric-val-num"><?php echo $curr_symbol . number_format($gl_book_balance, 2); ?></div>
          <div class="metric-sub-note">Live ledger balance (<?php echo count($combined_movements); ?> entries)</div>
        </div>

      </div>

      <!-- View Navigation Tabs Bar & Bulk Tools -->
      <div class="view-tabs-bar no-print">
        <div class="tab-buttons-group">
          <a href="?account=<?php echo urlencode($report_slug); ?>&as_of_date=<?php echo urlencode($target_date); ?>&status_view=all" 
             class="tab-btn-item <?php echo ($status_view === 'all') ? 'active' : ''; ?>">
             <span>All Movements</span>
             <span class="code-badge"><?php echo count($combined_movements); ?></span>
          </a>
          <a href="?account=<?php echo urlencode($report_slug); ?>&as_of_date=<?php echo urlencode($target_date); ?>&status_view=reconciled" 
             class="tab-btn-item <?php echo ($status_view === 'reconciled') ? 'active' : ''; ?>">
             <span>✓ Checked (Reconciled)</span>
             <span class="code-badge" style="background:var(--green-bg); color:var(--green);"><?php echo $reconciled_count; ?></span>
          </a>
          <a href="?account=<?php echo urlencode($report_slug); ?>&as_of_date=<?php echo urlencode($target_date); ?>&status_view=unreconciled" 
             class="tab-btn-item <?php echo ($status_view === 'unreconciled') ? 'active' : ''; ?>">
             <span>Unchecked (Pending)</span>
             <span class="code-badge" style="background:var(--amber-bg); color:var(--amber);"><?php echo $unreconciled_count; ?></span>
          </a>
        </div>
        <div style="display: flex; gap: 8px;">
          <button onclick="bulkReconcile(1)" class="btn-sm" style="background:var(--green-bg); color:var(--green); border-color:transparent;">
            ✓ Mark All Checked
          </button>
          <button onclick="bulkReconcile(0)" class="btn-sm" style="background:var(--bg); color:var(--text2);">
            Unmark All
          </button>
        </div>
      </div>

      <!-- System Movements & Bank Matching Log Section -->
      <div class="recon-table-card">
        <div class="recon-table-header">
          <h4>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            System Movements &amp; Bank Matching Log — <?php echo htmlspecialchars($acct_info['short_name']); ?>
          </h4>
          <span style="font-size: 11.5px; color: var(--text3); font-weight: 500;">
            Showing <strong><?php echo count($filtered_movements); ?></strong> of <strong><?php echo count($combined_movements); ?></strong> total transactions as of <?php echo htmlspecialchars($target_date); ?>
          </span>
        </div>

        <div style="overflow-x: auto;">
          <table class="recon-table">
            <thead>
              <tr>
                <th style="width: 4%; text-align: center;" class="no-print">Check</th>
                <th style="width: 10%;">Date</th>
                <th style="width: 12%;">Ref / Entry #</th>
                <th style="width: 12%;">Movement Flow</th>
                <th style="width: 32%;">Transaction Particulars &amp; Counterparty Details</th>
                <th style="width: 13%; text-align: right; color: var(--green);">Amount Received (+)</th>
                <th style="width: 13%; text-align: right; color: var(--red);">Payment Made (-)</th>
                <th style="width: 6%; text-align: center;">Status</th>
              </tr>
            </thead>
            <tbody>

              <?php if (empty($filtered_movements)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; color: var(--text3); font-style: italic; padding: 24px;">
                    No movements or statement items match the selected view filter (<?php echo htmlspecialchars($status_view); ?>) for <?php echo htmlspecialchars($acct_info['account_name']); ?> as of <?php echo htmlspecialchars($target_date); ?>.
                  </td>
                </tr>
              <?php endif; ?>

              <?php foreach ($filtered_movements as $m): ?>
                <?php 
                  $is_rec = (int)($m['is_reconciled'] ?? 0); 
                  $debit = (float)$m['debit'];
                  $credit = (float)$m['credit'];
                  $origin = $m['origin'] ?? 'journal_line';
                  $flow_type = $m['flow_type'] ?? ($debit > 0 ? 'Amount Received' : 'Payment Made');
                  $flow_badge = $m['flow_badge_class'] ?? ($debit > 0 ? 'pill-green' : 'trend-down');
                  $counterparty = $m['counterparty_label'] ?? '';
                  $came_from = $m['money_came_from'] ?? '';
                  $went_to = $m['money_went_to'] ?? '';
                  $formatted_dt = $m['formatted_datetime'] ?? $m['entry_date'];
                  $display_amount = ($debit > 0) ? ($curr_symbol . number_format($debit, 2)) : ($curr_symbol . number_format($credit, 2));
                ?>
                <tr class="recon-row <?php echo $is_rec ? 'row-checked' : 'row-unchecked'; ?>" 
                    id="row-<?php echo $origin; ?>-<?php echo $m['id']; ?>"
                    style="cursor: pointer;"
                    onclick="openMovementDetailModal(this)"
                    data-ref="<?php echo htmlspecialchars($m['display_ref']); ?>"
                    data-desc="<?php echo htmlspecialchars($m['display_desc']); ?>"
                    data-flow="<?php echo htmlspecialchars($flow_type); ?>"
                    data-flow-class="<?php echo $flow_badge; ?>"
                    data-came-from="<?php echo htmlspecialchars($came_from); ?>"
                    data-went-to="<?php echo htmlspecialchars($went_to); ?>"
                    data-datetime="<?php echo htmlspecialchars($formatted_dt); ?>"
                    data-amount="<?php echo htmlspecialchars($display_amount); ?>"
                    data-status="<?php echo $is_rec ? 'Checked (Reconciled)' : 'Unchecked (Pending)'; ?>"
                    title="Click to view full transaction details">
                  <td style="text-align: center;" class="no-print" onclick="event.stopPropagation()">
                    <input type="checkbox" 
                           class="recon-chk-box" 
                           data-type="<?php echo $origin; ?>" 
                           data-id="<?php echo $m['id']; ?>" 
                           <?php echo $is_rec ? 'checked' : ''; ?>
                           onchange="toggleReconcile(this)">
                  </td>
                  <td><?php echo htmlspecialchars($m['entry_date']); ?></td>
                  <td><code><?php echo htmlspecialchars($m['display_ref']); ?></code></td>
                  <td>
                    <span class="status-pill <?php echo $flow_badge; ?>">
                      <?php echo htmlspecialchars($flow_type); ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight: 600; color: var(--text);"><?php echo htmlspecialchars($m['display_desc']); ?></div>
                    <div style="font-size: 11px; color: var(--text2); margin-top: 2px;">
                      <span class="account-offset-tag"><?php echo htmlspecialchars($counterparty); ?></span>
                    </div>
                  </td>
                  <td style="text-align: right; font-weight: 600; color: var(--green);">
                    <?php echo ($debit > 0) ? number_format($debit, 2) : '-'; ?>
                  </td>
                  <td style="text-align: right; font-weight: 600; color: var(--red);">
                    <?php echo ($credit > 0) ? number_format($credit, 2) : '-'; ?>
                  </td>
                  <td style="text-align: center;">
                    <?php if ($is_rec): ?>
                      <span class="status-pill-badge reconciled" id="badge-<?php echo $origin; ?>-<?php echo $m['id']; ?>">✓ Checked</span>
                    <?php else: ?>
                      <span class="status-pill-badge pending" id="badge-<?php echo $origin; ?>-<?php echo $m['id']; ?>">Unchecked</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>

              <!-- Bank Statement Balance Comparison Row -->
              <tr class="row-category-header">
                <td colspan="5">
                  <strong>Bank Statement Ending Balance - as of <?php echo htmlspecialchars($statement_date); ?></strong>
                </td>
                <td colspan="2" style="text-align: right; font-weight: 700;">
                  <?php echo $curr_symbol . number_format($statement_balance, 2); ?>
                </td>
                <td style="text-align: center;" class="no-print">
                  <button onclick="openStatementModal()" class="tbl-action-btn tbl-btn-edit">Edit</button>
                </td>
              </tr>

              <tr class="row-subtotal">
                <td colspan="5">
                  <strong>Total Reconciled Movements (Checked)</strong>
                </td>
                <td colspan="2" style="text-align: right; font-weight: 700; color: var(--green);">
                  <?php echo $curr_symbol . number_format($reconciled_net, 2); ?>
                </td>
                <td class="no-print"></td>
              </tr>

              <tr class="row-grandtotal">
                <td colspan="5">
                  <strong>General Ledger Adjusted Book Balance</strong>
                </td>
                <td colspan="2" style="text-align: right; font-weight: 700;">
                  <?php echo $curr_symbol . number_format($gl_book_balance, 2); ?>
                </td>
                <td class="no-print"></td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>

<!-- Modal: Movement Full Details -->
<div id="modalMovementDetail" class="modal-backdrop">
  <div class="modal-box" style="max-width: 540px;">
    <div class="modal-head">
      <h3>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--orange)" stroke-width="2" style="vertical-align: -3px; margin-right: 6px;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
        Transaction Movement Details
      </h3>
      <button type="button" class="modal-close-btn" onclick="closeModal('modalMovementDetail')">&times;</button>
    </div>
    <div style="padding: 20px; display: flex; flex-direction: column; gap: 14px;">
      
      <div style="display: flex; justify-content: space-between; align-items: center; background: var(--bg); padding: 12px 16px; border-radius: var(--radius-lg); border: 1px solid var(--border);">
        <div>
          <span class="status-pill" id="detFlowBadge">Movement Flow</span>
          <div style="font-size: 11px; color: var(--text3); margin-top: 4px;" id="detRef">Ref: -</div>
        </div>
        <div style="text-align: right;">
          <div style="font-size: 18px; font-weight: 700;" id="detAmount">0.00</div>
          <div style="font-size: 11px; color: var(--text3);" id="detStatus">Status</div>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div style="background: var(--green-bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 12px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 4px;"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
            Where Money Came From
          </div>
          <div style="font-size: 12.5px; font-weight: 600; color: var(--text);" id="detCameFrom">-</div>
        </div>

        <div style="background: var(--red-bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 12px;">
          <div style="font-size: 10.5px; font-weight: 700; color: var(--red); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: -1px; margin-right: 4px;"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
            Where Money Went
          </div>
          <div style="font-size: 12.5px; font-weight: 600; color: var(--text);" id="detWentTo">-</div>
        </div>
      </div>

      <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 12px;">
        <div style="font-size: 11px; font-weight: 600; color: var(--text3); margin-bottom: 4px;">Date &amp; Time of Transaction</div>
        <div style="font-size: 13px; font-weight: 600; color: var(--text);" id="detDateTime">-</div>
      </div>

      <div style="background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 12px;">
        <div style="font-size: 11px; font-weight: 600; color: var(--text3); margin-bottom: 4px;">Transaction Particulars &amp; Description</div>
        <div style="font-size: 12.5px; color: var(--text2);" id="detDesc">-</div>
      </div>

    </div>
    <div class="modal-foot">
      <button type="button" class="btn-sm btn-primary" onclick="closeModal('modalMovementDetail')">Close Details</button>
    </div>
  </div>
</div>

<!-- Modal: Update Bank Statement Balance -->
<div id="modalStatementBalance" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-head">
      <h3>Update Bank Statement Ending Balance</h3>
      <button type="button" class="modal-close-btn" onclick="closeModal('modalStatementBalance')">&times;</button>
    </div>
    <form id="formStatementBalance" onsubmit="saveStatementBalance(event)">
      <input type="hidden" name="report_slug" value="<?php echo htmlspecialchars($report_slug); ?>">
      <div class="form-group-field">
        <label for="modal_as_of_date">Statement As-Of Date</label>
        <input type="date" id="modal_as_of_date" name="as_of_date" value="<?php echo htmlspecialchars($target_date); ?>" required>
      </div>
      <div class="form-group-field">
        <label for="modal_balance">Ending Bank Balance (<?php echo trim($curr_symbol); ?>)</label>
        <input type="number" step="0.01" id="modal_balance" name="balance" value="<?php echo $statement_balance; ?>" placeholder="0.00" required>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-sm" onclick="closeModal('modalStatementBalance')">Cancel</button>
        <button type="submit" class="btn-sm btn-primary">Save Statement Balance</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add Statement Adjustment -->
<div id="modalReconItem" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-head">
      <h3 id="modalReconItemTitle">Add Statement Adjustment Item</h3>
      <button type="button" class="modal-close-btn" onclick="closeModal('modalReconItem')">&times;</button>
    </div>
    <form id="formReconItem" onsubmit="saveReconItem(event)">
      <input type="hidden" id="item_id" name="id" value="0">
      <input type="hidden" name="report_slug" value="<?php echo htmlspecialchars($report_slug); ?>">
      <input type="hidden" name="as_of_date" value="<?php echo htmlspecialchars($target_date); ?>">

      <div class="form-group-field">
        <label for="item_type">Adjustment Category</label>
        <select id="item_type" name="item_type" required>
          <option value="deposit_in_transit">Deposit in Transit (+)</option>
          <option value="outstanding_check">Outstanding Check (-)</option>
          <option value="unrecorded_payment">Unrecorded Bank Payment (+)</option>
        </select>
      </div>
      <div class="form-group-field">
        <label for="item_date">Transaction Date</label>
        <input type="date" id="item_date" name="item_date" value="<?php echo htmlspecialchars($target_date); ?>" required>
      </div>
      <div class="form-group-field">
        <label for="description">Payee / Description</label>
        <input type="text" id="description" name="description" placeholder="e.g. Bank Fee / Supplier Check" required>
      </div>
      <div class="form-group-field">
        <label for="reference">Check # / Reference</label>
        <input type="text" id="reference" name="reference" placeholder="Reference or Check #">
      </div>
      <div class="form-group-field">
        <label for="amount">Amount (<?php echo trim($curr_symbol); ?>)</label>
        <input type="number" step="0.01" id="amount" name="amount" placeholder="0.00" required>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-sm" onclick="closeModal('modalReconItem')">Cancel</button>
        <button type="submit" class="btn-sm btn-primary">Save Item</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}

function openMovementDetailModal(trElem) {
    const ref = trElem.getAttribute('data-ref') || '-';
    const desc = trElem.getAttribute('data-desc') || '-';
    const flow = trElem.getAttribute('data-flow') || 'Movement';
    const flowClass = trElem.getAttribute('data-flow-class') || 'pill-green';
    const cameFrom = trElem.getAttribute('data-came-from') || '-';
    const wentTo = trElem.getAttribute('data-went-to') || '-';
    const dt = trElem.getAttribute('data-datetime') || '-';
    const amt = trElem.getAttribute('data-amount') || '0.00';
    const status = trElem.getAttribute('data-status') || 'Unchecked';

    document.getElementById('detRef').innerText = 'Ref / Entry #: ' + ref;
    document.getElementById('detDesc').innerText = desc;
    
    const badge = document.getElementById('detFlowBadge');
    badge.innerText = flow;
    badge.className = 'status-pill ' + flowClass;

    document.getElementById('detCameFrom').innerText = cameFrom;
    document.getElementById('detWentTo').innerText = wentTo;
    document.getElementById('detDateTime').innerText = dt;
    document.getElementById('detAmount').innerText = amt;
    document.getElementById('detStatus').innerText = 'Status: ' + status;

    openModal('modalMovementDetail');
}

function openStatementModal() {
    openModal('modalStatementBalance');
}

function openReconItemModal(defaultType) {
    document.getElementById('item_id').value = 0;
    document.getElementById('modalReconItemTitle').innerText = 'Add Statement Adjustment Item';
    if(defaultType) {
        document.getElementById('item_type').value = defaultType;
    }
    document.getElementById('description').value = '';
    document.getElementById('reference').value = '0';
    document.getElementById('amount').value = '';
    openModal('modalReconItem');
}

function toggleReconcile(elem) {
    const targetType = elem.getAttribute('data-type') || 'journal_line';
    const id = elem.getAttribute('data-id');
    const isChecked = elem.checked ? 1 : 0;

    const row = document.getElementById('row-' + targetType + '-' + id);
    const badge = document.getElementById('badge-' + targetType + '-' + id);

    const formData = new FormData();
    formData.append('action', 'toggle_reconcile');
    formData.append('target_type', targetType);
    formData.append('id', id);
    formData.append('is_reconciled', isChecked);

    fetch('actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            if(isChecked) {
                if(row) {
                    row.classList.add('row-checked');
                    row.setAttribute('data-status', 'Checked (Reconciled)');
                }
                if(badge) {
                    badge.className = 'status-pill-badge reconciled';
                    badge.innerText = '✓ Checked';
                }
            } else {
                if(row) {
                    row.classList.remove('row-checked');
                    row.setAttribute('data-status', 'Unchecked (Pending)');
                }
                if(badge) {
                    badge.className = 'status-pill-badge pending';
                    badge.innerText = 'Unchecked';
                }
            }
        } else {
            elem.checked = !isChecked;
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        elem.checked = !isChecked;
        alert('Network error: Could not update transaction status.');
    });
}

function bulkReconcile(isChecked) {
    const actionLabel = isChecked ? 'mark all transactions as Checked (Reconciled)' : 'unmark all transactions';
    if(!confirm('Are you sure you want to ' + actionLabel + '?')) return;

    const formData = new FormData();
    formData.append('action', 'bulk_reconcile');
    formData.append('account_id', '<?php echo $current_acct_id; ?>');
    formData.append('account_code', '<?php echo htmlspecialchars($acct_code); ?>');
    formData.append('target_date', '<?php echo htmlspecialchars($target_date); ?>');
    formData.append('is_reconciled', isChecked ? 1 : 0);

    fetch('actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function saveStatementBalance(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formStatementBalance'));
    formData.append('action', 'save_statement_balance');

    fetch('actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function saveReconItem(e) {
    e.preventDefault();
    const formData = new FormData(document.getElementById('formReconItem'));
    formData.append('action', 'save_recon_item');

    fetch('actions.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>
</body>
</html>
