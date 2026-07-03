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
<title>INEZA African Mining — Statement of Financial Position (Balance Sheet)</title>
<meta name="description" content="Statement of Financial Position for DETROIT CROWNED GOLDEN BUSINESS 'CGB' Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/balance_sheet.css">
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

  <?php $page_title = "Balance Sheet"; include '../include/navbar.php'; ?>

  <div class="content" id="balanceSheetContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M12 22V2M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          Statement of Financial Position
        </h1>
        <div class="page-sub">DETROIT CROWNED GOLDEN BUSINESS "CGB" Ltd — Financial Years 2020 – 2022</div>
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
      <!-- Total Assets -->
      <div class="stat-card" id="stat-total-assets">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-up">Assets</span>
        </div>
        <div class="stat-val">Frw 2,011.6M</div>
        <div class="stat-label">Total Assets (2022)</div>
      </div>

      <!-- Total Equity -->
      <div class="stat-card" id="stat-total-equity">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-blue">Equity</span>
        </div>
        <div class="stat-val">Frw 1,418.3M</div>
        <div class="stat-label">Capital and Reserves</div>
      </div>

      <!-- Total Liabilities -->
      <div class="stat-card" id="stat-total-liabilities">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Liabilities</span>
        </div>
        <div class="stat-val">Frw 593.3M</div>
        <div class="stat-label">Total Liabilities (2022)</div>
      </div>

      <!-- Cash Position -->
      <div class="stat-card" id="stat-cash-position">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-up">Cash</span>
        </div>
        <div class="stat-val">Frw 75.8M</div>
        <div class="stat-label">Cash &amp; cash equivalents</div>
      </div>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">DETROIT CROWNED GOLDEN BUSINESS "CGB" Ltd</div>
          <h3>Statement of financial position</h3>
          <p>As at 31 December 2022</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="balance-sheet-table">
            <thead>
              <tr>
                <th></th>
                <th class="num-note">Notes</th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <!-- Assets Section -->
              <tr class="section-header-row">
                <td colspan="5">Assets</td>
              </tr>
              
              <!-- Non Current Assets -->
              <tr class="subsection-header-row">
                <td colspan="5">Non Current Assets</td>
              </tr>
              <tr>
                <td class="row-label">Property, Plant and Equipment</td>
                <td class="num-note"></td>
                <td class="num-val">702,151,200</td>
                <td class="num-val">430,655,062</td>
                <td class="num-val">51,175,000</td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current assets</td>
                <td class="num-note"></td>
                <td class="num-val">702,151,200</td>
                <td class="num-val">430,655,062</td>
                <td class="num-val">51,175,000</td>
              </tr>
              
              <!-- Current Assets -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Assets</td>
              </tr>
              <tr>
                <td class="row-label">Inventory</td>
                <td class="num-note">7</td>
                <td class="num-val">791,623,921</td>
                <td class="num-val">773,198,870</td>
                <td class="num-val">464,009,712</td>
              </tr>
              <tr>
                <td class="row-label">Accounts receivables</td>
                <td class="num-note">6</td>
                <td class="num-val">441,999,685</td>
                <td class="num-val">370,565,172</td>
                <td class="num-val">185,174,448</td>
              </tr>
              <tr>
                <td class="row-label">Cash and cash equivalents</td>
                <td class="num-note">5</td>
                <td class="num-val">75,806,100</td>
                <td class="num-val">95,986,330</td>
                <td class="num-val">44,507,100</td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Current Assets</td>
                <td class="num-note"></td>
                <td class="num-val">1,309,429,706</td>
                <td class="num-val">1,239,750,372</td>
                <td class="num-val">693,691,260</td>
              </tr>
              
              <!-- Total Assets -->
              <tr class="grand-total-row">
                <td class="row-label">Total Assets</td>
                <td class="num-note"></td>
                <td class="num-val">2,011,580,906</td>
                <td class="num-val">1,670,405,434</td>
                <td class="num-val">744,866,260</td>
              </tr>
              
              <!-- Equity and Liabilities Section -->
              <tr class="section-header-row">
                <td colspan="5">Equity and liabilities</td>
              </tr>
              
              <!-- Capital and reserves -->
              <tr class="subsection-header-row">
                <td colspan="5">Capital and reserves</td>
              </tr>
              <tr>
                <td class="row-label">Share Capital</td>
                <td class="num-note"></td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">15,000,000</td>
              </tr>
              <tr>
                <td class="row-label">Retained earnings</td>
                <td class="num-note"></td>
                <td class="num-val">1,403,329,213</td>
                <td class="num-val">873,277,290</td>
                <td class="num-val">381,297,812</td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Equity</td>
                <td class="num-note"></td>
                <td class="num-val">1,418,329,213</td>
                <td class="num-val">888,277,290</td>
                <td class="num-val">396,297,812</td>
              </tr>
              
              <!-- Non Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Non Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Long term loans and other LT liabilities</td>
                <td class="num-note">10</td>
                <td class="num-val">203,338,777</td>
                <td class="num-val">247,055,474</td>
                <td class="num-val">-</td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total Non current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val">203,338,777</td>
                <td class="num-val">247,055,474</td>
                <td class="num-val">-</td>
              </tr>
              
              <!-- Current Liabilities -->
              <tr class="subsection-header-row">
                <td colspan="5">Current Liabilities</td>
              </tr>
              <tr>
                <td class="row-label">Accounts payables</td>
                <td class="num-note">9</td>
                <td class="num-val">112,748,306</td>
                <td class="num-val">257,194,092</td>
                <td class="num-val">149,525,000</td>
              </tr>
              <tr>
                <td class="row-label">Other current liabilities</td>
                <td class="num-note">9</td>
                <td class="num-val">50,000,000</td>
                <td class="num-val">67,030,230</td>
                <td class="num-val">35,630,100</td>
              </tr>
              <tr>
                <td class="row-label">Current Tax Payable</td>
                <td class="num-note">11</td>
                <td class="num-val">227,165,110</td>
                <td class="num-val">210,848,348</td>
                <td class="num-val">163,413,348</td>
              </tr>
              <tr class="subtotal-row">
                <td class="row-label">Total current liabilities</td>
                <td class="num-note"></td>
                <td class="num-val">389,913,416</td>
                <td class="num-val">535,072,670</td>
                <td class="num-val">348,568,448</td>
              </tr>
              
              <!-- Total Liabilities -->
              <tr class="subtotal-row">
                <td class="row-label" style="padding-left: 18px;">Total liabilities</td>
                <td class="num-note"></td>
                <td class="num-val">593,251,693</td>
                <td class="num-val">782,128,144</td>
                <td class="num-val">348,568,448</td>
              </tr>
              
              <!-- Total equity and liabilities -->
              <tr class="grand-total-row">
                <td class="row-label">Total equity and liabilities</td>
                <td class="num-note"></td>
                <td class="num-val">2,011,580,906</td>
                <td class="num-val">1,670,405,434</td>
                <td class="num-val">744,866,260</td>
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
        csvContent += "DETROIT CROWNED GOLDEN BUSINESS \"CGB\" Ltd - Statement of financial position\r\n\r\n";
        
        // Header Row
        csvContent += ",Notes,2022 (Frw),2021 (Frw),2020 (Frw)\r\n";

        const table = document.getElementById('balance-sheet-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            // Determine the type of row
            if (row.classList.contains('section-header-row')) {
                const headerText = row.textContent.trim().replace(/,/g, '');
                csvContent += `\r\n"${headerText}",,,\r\n`;
            } else if (row.classList.contains('subsection-header-row')) {
                const subHeaderText = row.textContent.trim().replace(/,/g, '');
                csvContent += `"${subHeaderText}",,,\r\n`;
            } else {
                const labelCell = row.querySelector('.row-label');
                if (!labelCell) return;
                const label = labelCell.textContent.trim().replace(/,/g, '');
                
                const noteCell = row.querySelector('.num-note');
                const note = noteCell ? noteCell.textContent.trim() : '';
                
                const cells = row.querySelectorAll('.num-val');
                
                let val2022 = cells[0] ? cells[0].textContent.trim().replace(/,/g, '') : '';
                let val2021 = cells[1] ? cells[1].textContent.trim().replace(/,/g, '') : '';
                let val2020 = cells[2] ? cells[2].textContent.trim().replace(/,/g, '') : '';

                csvContent += `"${label}",${note},${val2022},${val2021},${val2020}\r\n`;
            }
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "cgb_statement_of_financial_position.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
