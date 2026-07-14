<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;
if (!hasPermission($conn, $userId, 'view_report_accounts_payable')) {
    header("Location: ../dashboard");
    exit();
}
$report_slug = 'accounts_payable';
$report_title = 'Account Payable';
$report_slug_esc = mysqli_real_escape_string($conn, $report_slug);

// Custom labels mapping is disabled

// Parse filters
$filter_year = $_GET['filter_year'] ?? '';
$filter_month = $_GET['filter_month'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';
$filter_start = $_GET['filter_start'] ?? '';
$filter_end = $_GET['filter_end'] ?? '';

function buildDateCondition($conn, $column, $filter_year, $filter_month, $filter_date, $filter_start, $filter_end) {
    if (!empty($filter_date)) {
        return " AND DATE({$column}) = '" . mysqli_real_escape_string($conn, $filter_date) . "' ";
    }

    if (!empty($filter_start) && !empty($filter_end)) {
        return " AND {$column} BETWEEN '" . mysqli_real_escape_string($conn, $filter_start) . "' AND '" . mysqli_real_escape_string($conn, $filter_end) . "' ";
    }

    if (!empty($filter_year) && !empty($filter_month)) {
        return " AND YEAR({$column}) = '" . mysqli_real_escape_string($conn, $filter_year) . "' AND MONTH({$column}) = '" . mysqli_real_escape_string($conn, $filter_month) . "' ";
    }

    if (!empty($filter_year)) {
        return " AND YEAR({$column}) = '" . mysqli_real_escape_string($conn, $filter_year) . "' ";
    }

    if (!empty($filter_month)) {
        return " AND MONTH({$column}) = '" . mysqli_real_escape_string($conn, $filter_month) . "' ";
    }

    return '';
}

$purchaseDateCond = buildDateCondition($conn, 'purchase_date', $filter_year, $filter_month, $filter_date, $filter_start, $filter_end);
$advanceDateCond = buildDateCondition($conn, 'advance_date', $filter_year, $filter_month, $filter_date, $filter_start, $filter_end);
$paymentDateCond = buildDateCondition($conn, 'payment_date', $filter_year, $filter_month, $filter_date, $filter_start, $filter_end);

// Fetch database records
$query = "SELECT s.name AS supplier_name, l.lots_code, p.id AS purchase_id,
            COALESCE(SUM(p.quantity_kg), 0) AS total_weight,
            COALESCE(SUM(p.net_paid_supplier_usd), 0) AS total_amount,
            COALESCE(sa.total_advances, 0) AS total_advances,
            COALESCE(sp.total_payments, 0) AS total_payments,
            (COALESCE(SUM(p.net_paid_supplier_usd), 0) - COALESCE(sp.total_payments, 0)) AS balance_due,
            p.purchase_date AS tx_date
          FROM purchasing p
          JOIN suppliers s ON p.supplier_id = s.id
          JOIN lots l ON p.lot_id = l.id
          LEFT JOIN (
              SELECT supplier_id, SUM(amount) AS total_advances
              FROM supplier_advances
              WHERE 1=1 {$advanceDateCond}
              GROUP BY supplier_id
          ) sa ON sa.supplier_id = s.id
          LEFT JOIN (
              SELECT spa.purchase_id, SUM(spa.amount_allocated) AS total_payments
              FROM supplier_payment_allocations spa
              JOIN supplier_payments sp ON sp.id = spa.supplier_payment_id
              WHERE 1=1 {$paymentDateCond}
              GROUP BY spa.purchase_id
          ) sp ON sp.purchase_id = p.id
          WHERE 1=1 {$purchaseDateCond}
          GROUP BY s.id, l.id, p.purchase_date, sa.total_advances, sp.total_payments
          ORDER BY p.purchase_date ASC, s.name ASC";
$db_res = mysqli_query($conn, $query);
$total_w = 0.0;
$total_a = 0.0;
$total_ad = 0.0;
$total_pay = 0.0;
$total_bal = 0.0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — <?php echo htmlspecialchars($report_title); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/reports.css">
<style>
  .payment-history-link {
    border: none;
    background: transparent;
    color: #2563eb;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
  }
  .payment-history-link:hover {
    color: #1d4ed8;
  }
  .payment-history-modal {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1200;
    padding: 16px;
  }
  .payment-history-modal-content {
    width: min(720px, 100%);
    max-height: 85vh;
    overflow: auto;
    background: var(--card-bg, #fff);
    border-radius: 12px;
    box-shadow: 0 16px 50px rgba(0,0,0,0.2);
    padding: 18px;
  }
  .payment-history-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
  }
  .modal-close-btn {
    border: none;
    background: transparent;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
  }
</style>
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include __DIR__ . '/../include/sidebar.php'; ?>

<div class="main">

  <?php include __DIR__ . '/../include/navbar.php'; ?>

  <div class="content">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          <?php echo htmlspecialchars($report_title); ?>
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Accounts Payable Ledger</div>
      </div>
      <div class="page-actions">
        <a class="btn-sm btn-primary" href="../include/export.php?sheet=<?php echo urlencode($report_slug); ?>&filter_year=<?php echo urlencode($filter_year); ?>&filter_month=<?php echo urlencode($filter_month); ?>&filter_date=<?php echo urlencode($filter_date); ?>&filter_start=<?php echo urlencode($filter_start); ?>&filter_end=<?php echo urlencode($filter_end); ?>" id="exportBtn">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export to Excel
        </a>
        <button class="btn-sm" onclick="window.print()">
          Print Report
        </button>
      </div>
    </div>

    <!-- Report Container -->
    <div class="report-container page-<?php echo htmlspecialchars($report_slug); ?>">
      <div class="report-card">
      <div class="report-toolbar">
        <div class="search-wrap">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          <input type="text" id="reportSearch" placeholder="Search values in table..." onkeyup="searchTable()">
        </div>
        <div class="toolbar-actions">
          <label class="toggle-label">
            <input type="checkbox" id="hideEmptyRows" onchange="toggleEmptyRows(this)">
            <span>Hide Empty Rows</span>
          </label>
        </div>
      </div>

      <!-- Date Filters -->
      <form method="GET" class="report-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; padding: 12px 16px; background: var(--card-bg, #fff); border-radius: 10px; margin-bottom: 12px; border: 1px solid var(--border, #e5e7eb); box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Year</label>
          <select name="filter_year" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; min-width: 90px; color: var(--text, #111);">
            <option value="">All</option>
            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
              <option value="<?php echo $y; ?>" <?php echo ($filter_year == $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Month</label>
          <select name="filter_month" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; min-width: 120px; color: var(--text, #111);">
            <option value="">All</option>
            <?php
            $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            for ($m = 1; $m <= 12; $m++):
            ?>
              <option value="<?php echo $m; ?>" <?php echo ($filter_month == $m) ? 'selected' : ''; ?>><?php echo $months[$m-1]; ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Specific Date</label>
          <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px;">
          <label style="font-size: 11px; font-weight: 600; color: var(--text-secondary, #6b7280); text-transform: uppercase; letter-spacing: 0.5px;">Date Range</label>
          <div style="display: flex; gap: 6px; align-items: center;">
            <input type="date" name="filter_start" value="<?php echo htmlspecialchars($filter_start); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
            <span style="font-size: 12px; color: var(--text-secondary, #9ca3af);">to</span>
            <input type="date" name="filter_end" value="<?php echo htmlspecialchars($filter_end); ?>" style="padding: 7px 12px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); font-size: 13px; color: var(--text, #111);">
          </div>
        </div>
        <div style="display: flex; gap: 6px;">
          <button type="submit" style="padding: 7px 18px; border-radius: 8px; border: none; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 2px 6px rgba(99,102,241,0.3);">
            <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: none; stroke: currentColor; stroke-width: 2; vertical-align: -2px; margin-right: 4px;"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
            Apply
          </button>
          <a href="?" style="padding: 7px 14px; border-radius: 8px; border: 1px solid var(--border, #d1d5db); background: var(--input-bg, #f9fafb); color: var(--text-secondary, #6b7280); font-size: 13px; font-weight: 500; text-decoration: none; cursor: pointer; transition: all 0.2s;">
            Clear
          </a>
        </div>
      </form>

      <div class="table-wrapper" style="padding: 16px;">
        <table class="excel-table" id="reportTable">
          <thead>
            <tr style="background: var(--bg); font-weight: 700; text-align: center;">
              <th colspan="9">ACCOUNTS PAYABLE - SUPPLIERS OF MINERALS</th>
            </tr>
            <tr style="background: var(--bg); font-weight: 600;">
              <th>Date</th>
              <th>Description</th>
              <th>Lot No.</th>
              <th>Code</th>
              <th>Weight (kgs)</th>
              <th>Est. Total Amount</th>
              <th>Payments</th>
              <th>Balance Due</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $has_data = false;
            if ($db_res && mysqli_num_rows($db_res) > 0):
                $has_data = true;
                while ($row = mysqli_fetch_assoc($db_res)):
                    $weight = (float)$row['total_weight'];
                    $amount = (float)$row['total_amount'];
                    $payments = (float)$row['total_payments'];
                    $bal = (float)$row['balance_due'];

                    $paymentHistory = [];
                    if (!empty($row['purchase_id'])) {
                        $historyQuery = "SELECT sp.id AS payment_id, sp.payment_date, spa.amount_allocated, sp.amount_currency, sp.amount_base, sp.exchange_rate, sp.status, cur.code AS currency_code, a.account_name, a.account_code
                                         FROM supplier_payment_allocations spa
                                         JOIN supplier_payments sp ON sp.id = spa.supplier_payment_id
                                         LEFT JOIN currencies cur ON cur.id = sp.currency_id
                                         LEFT JOIN accounts a ON a.id = sp.account_id
                                         WHERE spa.purchase_id = " . (int)$row['purchase_id'] . "
                                         ORDER BY sp.payment_date ASC, sp.id ASC";
                        $historyRes = mysqli_query($conn, $historyQuery);
                        if ($historyRes) {
                            while ($historyRow = mysqli_fetch_assoc($historyRes)) {
                                $paymentHistory[] = [
                                    'payment_id' => (int)$historyRow['payment_id'],
                                    'payment_date' => $historyRow['payment_date'],
                                    'amount_allocated' => (float)$historyRow['amount_allocated'],
                                    'amount_currency' => (float)$historyRow['amount_currency'],
                                    'amount_base' => (float)$historyRow['amount_base'],
                                    'exchange_rate' => (float)$historyRow['exchange_rate'],
                                    'status' => $historyRow['status'],
                                    'currency_code' => $historyRow['currency_code'] ?? 'USD',
                                    'account_name' => $historyRow['account_name'] ?? '—',
                                    'account_code' => $historyRow['account_code'] ?? '—'
                                ];
                            }
                        }
                    }

                    $paymentHistoryJson = htmlspecialchars(json_encode($paymentHistory), ENT_QUOTES, 'UTF-8');
                    $paymentCell = '<button type="button" class="payment-history-link" data-title="' . htmlspecialchars($row['supplier_name'] . ' — ' . $row['lots_code'], ENT_QUOTES, 'UTF-8') . '" data-history="' . $paymentHistoryJson . '" onclick="openPaymentHistoryModal(this)">$' . number_format($payments, 2) . '</button>';

                    $total_w += $weight;
                    $total_a += $amount;
                    $total_pay += $payments;
                    $total_bal += $bal;
            ?>
              <tr class="data-row">
                <td><?php echo htmlspecialchars($row['tx_date']); ?></td>
                <td><?php echo htmlspecialchars($row['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($row['lots_code']); ?></td>
                <td></td>
                <td style="text-align: right;"><?php echo number_format($weight, 1); ?></td>
                <td style="text-align: right;">$<?php echo number_format($amount, 2); ?></td>
                <td style="text-align: right;"><?php echo $paymentCell; ?></td>
                <td style="text-align: right; font-weight: 600;">$<?php echo number_format($bal, 2); ?></td>
              </tr>
            <?php
                endwhile;
            else:
            ?>
              <tr>
                <td colspan="9" style="text-align: center; padding: 24px; color: var(--text2);">No accounts payable records found.</td>
              </tr>
            <?php
            endif;
            ?>
            <tr style="font-weight: 700; background: var(--bg);">
              <td colspan="4" style="text-align: right;">Total:</td>
              <td style="text-align: right;"><?php echo number_format($total_w, 1); ?></td>
              <td style="text-align: right;">$<?php echo number_format($total_a, 2); ?></td>
              <td style="text-align: right;">$<?php echo number_format($total_pay, 2); ?></td>
              <td style="text-align: right;">$<?php echo number_format($total_bal, 2); ?></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  </div>
</div>

<div id="paymentHistoryModal" class="payment-history-modal" style="display: none;">
  <div class="payment-history-modal-content" role="dialog" aria-modal="true" aria-labelledby="paymentHistoryTitle">
    <div class="payment-history-modal-header">
      <h3 id="paymentHistoryTitle" style="margin: 0;">Payment History</h3>
      <button type="button" class="modal-close-btn" onclick="closePaymentHistoryModal()" aria-label="Close">×</button>
    </div>
    <div id="paymentHistoryBody"></div>
  </div>
</div>

<script>
  function searchTable() {
    var input = document.getElementById("reportSearch");
    if (!input) return;
    var filter = input.value.toLowerCase();
    var table = document.getElementById("reportTable");
    if (!table) return;
    var trs = table.getElementsByClassName("data-row");
    
    for (var i = 0; i < trs.length; i++) {
      var tr = trs[i];
      var tds = tr.getElementsByTagName("td");
      var rowMatches = false;
      
      for (var j = 0; j < tds.length; j++) {
        var td = tds[j];
        var text = td.textContent || td.innerText;
        
        td.innerHTML = text.replace(/<span class="highlight">(.*?)<\/span>/gi, '$1');
        
        if (filter !== "" && text.toLowerCase().indexOf(filter) > -1) {
          rowMatches = true;
          var regex = new RegExp("(" + escapeRegExp(filter) + ")", "gi");
          td.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
        }
      }
      
      if (filter === "") {
        tr.style.display = "";
      } else {
        if (rowMatches) {
          tr.style.display = "";
        } else {
          tr.style.display = "none";
        }
      }
    }
  }

  function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  }

  function toggleEmptyRows(checkbox) {
    var table = document.getElementById("reportTable");
    if (!table) return;
    var trs = table.getElementsByClassName("empty-row");
    for (var i = 0; i < trs.length; i++) {
      trs[i].style.display = checkbox.checked ? "none" : "";
    }
    searchTable();
  }

  function openPaymentHistoryModal(button) {
    var modal = document.getElementById('paymentHistoryModal');
    var title = document.getElementById('paymentHistoryTitle');
    var body = document.getElementById('paymentHistoryBody');
    if (!modal || !title || !body) return;

    var history = [];
    try {
      history = JSON.parse(button.getAttribute('data-history') || '[]');
    } catch (e) {
      history = [];
    }

    title.textContent = button.getAttribute('data-title') || 'Payment History';

    if (!history.length) {
      body.innerHTML = '<div style="padding: 8px 0; color: var(--text2);">No payment history recorded for this purchase.</div>';
    } else {
      var rows = '';
      history.forEach(function(item) {
        rows += '<tr>' +
          '<td>' + (item.payment_date || '—') + '</td>' +
          '<td>' + (item.status || '—') + '</td>' +
          '<td>' + (item.account_name || '—') + ' (' + (item.account_code || '—') + ')</td>' +
          '<td style="text-align: right;">' + (item.amount_currency ? '$' + Number(item.amount_currency).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—') + '</td>' +
          '<td style="text-align: right;">' + (item.exchange_rate ? Number(item.exchange_rate).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 6 }) : '—') + '</td>' +
          '<td style="text-align: right;">' + (item.amount_base ? '$' + Number(item.amount_base).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—') + '</td>' +
          '<td style="text-align: right;">' + (item.amount_allocated ? '$' + Number(item.amount_allocated).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—') + '</td>' +
          '</tr>';
      });

      body.innerHTML = '<table class="excel-table" style="width: 100%; font-size: 13px;">' +
        '<thead><tr><th style="text-align:left;">Date</th><th style="text-align:left;">Status</th><th style="text-align:left;">Account</th><th style="text-align:right;">Amount</th><th style="text-align:right;">Rate</th><th style="text-align:right;">Base</th><th style="text-align:right;">Applied</th></tr></thead>' +
        '<tbody>' + rows + '</tbody></table>';
    }

    modal.style.display = 'flex';
  }

  function closePaymentHistoryModal() {
    var modal = document.getElementById('paymentHistoryModal');
    if (modal) {
      modal.style.display = 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('paymentHistoryModal');
    if (modal) {
      modal.addEventListener('click', function(event) {
        if (event.target === modal) {
          closePaymentHistoryModal();
        }
      });
    }
  });
</script>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script>
</body>
</html>
