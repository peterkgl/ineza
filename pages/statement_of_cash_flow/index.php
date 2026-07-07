<?php
require_once __DIR__ . '/../login/auth.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$userId = $_SESSION['user_id'] ?? 0;

// Security access check using standard view_accounts permission
if (!hasPermission($conn, $userId, 'view_accounts')) {
    header("Location: ../dashboard");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Statement of Cash Flows</title>
<meta name="description" content="Statement of Cash Flows report for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/statement_of_cash_flow.css">
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

  <?php $page_title = "Statement of Cash Flows"; include '../include/navbar.php'; ?>

  <div class="content" id="cashFlowContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Statement of Cash Flows
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Financial Years 2021 – 2022</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="exportCsvBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export CSV
        </button>
        <button class="btn-sm btn-primary" onclick="window.print()">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><path d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v8H6z"/></svg>
          Print Report
        </button>
      </div>
    </div>

    <!-- ===== STAT CARDS SUMMARY ===== -->
    <div class="stats-grid">
      <!-- Operating Cash Flows -->
      <div class="stat-card" id="stat-operating-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-up">Operating</span>
        </div>
        <div class="stat-val">Frw 295,033,105</div>
        <div class="stat-label">Net Operating Cash Flow (2022)</div>
      </div>

      <!-- Investing Cash Flows -->
      <div class="stat-card" id="stat-investing-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-orange">Investing</span>
        </div>
        <div class="stat-val">Frw (271,496,138)</div>
        <div class="stat-label">Net Investing Cash Flow (2022)</div>
      </div>

      <!-- Financing Cash Flows -->
      <div class="stat-card" id="stat-financing-cf">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Financing</span>
        </div>
        <div class="stat-val">Frw (43,717,197)</div>
        <div class="stat-label">Net Financing Cash Flow (2022)</div>
      </div>

      <!-- Cash at Year End -->
      <div class="stat-card" id="stat-cash-end">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">Cash Position</span>
        </div>
        <div class="stat-val">Frw 75,806,100</div>
        <div class="stat-label">Cash equivalents at Year End</div>
      </div>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">INEZA African Mining Ltd</div>
          <h3>Statement of cash flows</h3>
          <p>For the year ended 31 December 2022</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="cash-flow-table">
            <thead>
              <tr>
                <th></th>
                <th class="num-note">Notes</th>
                <th>2022</th>
                <th>2021</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <!-- Section: Operating Activities -->
              <tr class="accent-row">
                <td class="row-label" style="font-weight: 600;">Cash flow from operating activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Profit before tax</td>
                <td class="num-note"></td>
                <td class="num-val">757,217,033</td>
                <td class="num-val">702,827,826</td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="padding-left: 20px; font-weight: 500;">Profit(loss) before working capital changes</td>
                <td class="num-note"></td>
                <td class="num-val">757,217,033</td>
                <td class="num-val">702,827,826</td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="padding-left: 20px; font-weight: 600;">Working capital changes</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">(Increase)/decrease in inventory</td>
                <td class="num-note"></td>
                <td class="num-val">(18,425,051)</td>
                <td class="num-val">(309,189,158)</td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">(Increase)/decrease in account receivables</td>
                <td class="num-note"></td>
                <td class="num-val">(71,434,513)</td>
                <td class="num-val">(185,390,724)</td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">Increase/(decrease) in account payables</td>
                <td class="num-note"></td>
                <td class="num-val">(144,445,786)</td>
                <td class="num-val">107,669,092</td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 40px;">Increase/(decrease) in other current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val">(17,030,230)</td>
                <td class="num-val">31,400,130</td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="padding-left: 20px; font-weight: 500;">Cash flows from operations</td>
                <td class="num-note"></td>
                <td class="num-val">505,881,453</td>
                <td class="num-val">347,317,166</td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Tax paid</td>
                <td class="num-note"></td>
                <td class="num-val">(210,848,348)</td>
                <td class="num-val">(163,413,348)</td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net Cash flow from operating activities</td>
                <td class="num-note"></td>
                <td class="num-val">295,033,105</td>
                <td class="num-val">183,903,818</td>
              </tr>

              <!-- Section: Investing Activities -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Cash flows from Investing activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Acquisition of Non-current assets</td>
                <td class="num-note"></td>
                <td class="num-val">(271,496,138)</td>
                <td class="num-val">(379,480,062)</td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net cash flow from investing activities</td>
                <td class="num-note"></td>
                <td class="num-val">(271,496,138)</td>
                <td class="num-val">(379,480,062)</td>
              </tr>

              <!-- Section: Financing Activities -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Cash flow from financing activities</td>
                <td class="num-note"></td>
                <td class="num-val"></td>
                <td class="num-val"></td>
              </tr>
              <tr>
                <td class="row-label" style="padding-left: 20px;">Acquisition/ (payment of loan)</td>
                <td class="num-note"></td>
                <td class="num-val">(43,717,197)</td>
                <td class="num-val">247,055,474</td>
              </tr>
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net Cash flow from financing activities</td>
                <td class="num-note"></td>
                <td class="num-val">(43,717,197)</td>
                <td class="num-val">247,055,474</td>
              </tr>

              <!-- Summary Totals -->
              <tr class="accent-row" style="border-top: 1.5px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Net cash inflow (Outflow) for the period</td>
                <td class="num-note"></td>
                <td class="num-val">(20,180,230)</td>
                <td class="num-val">51,479,230</td>
              </tr>
              <tr class="accent-row">
                <td class="row-label" style="font-weight: 600;">Cash and cash equivalents at the beginning</td>
                <td class="num-note"></td>
                <td class="num-val">95,986,330</td>
                <td class="num-val">44,507,100</td>
              </tr>
              <tr class="total-row">
                <td class="row-label" style="font-weight: 600;">Cash and cash equivalents at the end</td>
                <td class="num-note"></td>
                <td class="num-val">75,806,100</td>
                <td class="num-val">95,986,330</td>
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Statement of Cash Flows\r\n\r\n";
        
        // Header Row
        csvContent += ",Notes,2022 (Frw),2021 (Frw)\r\n";

        const table = document.getElementById('cash-flow-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const label = row.querySelector('.row-label').textContent.trim().replace(/,/g, '');
            
            const noteCell = row.querySelector('.num-note');
            const note = noteCell ? noteCell.textContent.trim() : '';
            
            const cells = row.querySelectorAll('.num-val');
            
            let val2022 = cells[0] ? cells[0].textContent.trim().replace(/,/g, '') : '';
            let val2021 = cells[1] ? cells[1].textContent.trim().replace(/,/g, '') : '';

            // Filter out column title row if it gets caught
            if (val2022 || val2021) {
                csvContent += `"${label}",${note},${val2022},${val2021}\r\n`;
            } else {
                csvContent += `"${label}",${note},,\r\n`;
            }
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "ineza_statement_of_cash_flows.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
