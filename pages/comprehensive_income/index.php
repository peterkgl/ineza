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
<title>INEZA African Mining — Statement of Profit or Loss and Other Comprehensive Income</title>
<meta name="description" content="Statement of profit or loss and Other Comprehensive Income for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/comprehensive_income.css">
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

  <?php $page_title = "Comprehensive Income"; include '../include/navbar.php'; ?>

  <div class="content" id="incomeContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Comprehensive Income Statement
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — Financial Years 2020 – 2022</div>
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
      <!-- Total Revenue -->
      <div class="stat-card" id="stat-revenue">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-orange">Revenue</span>
        </div>
        <div class="stat-val">Frw 1,995.0M</div>
        <div class="stat-label">Gross Revenue (2022)</div>
      </div>

      <!-- Gross Profit -->
      <div class="stat-card" id="stat-gross-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          </div>
          <span class="stat-trend trend-up">53.0% GP</span>
        </div>
        <div class="stat-val">Frw 1,057.3M</div>
        <div class="stat-label">Gross Profit (2022)</div>
      </div>

      <!-- Operating Profit -->
      <div class="stat-card" id="stat-operating-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-blue">EBIT</span>
        </div>
        <div class="stat-val">Frw 800.6M</div>
        <div class="stat-label">Operating Profit (2022)</div>
      </div>

      <!-- Net Profit -->
      <div class="stat-card" id="stat-net-profit">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">Net Profit</span>
        </div>
        <div class="stat-val">Frw 530.0M</div>
        <div class="stat-label">Comprehensive Income (2022)</div>
      </div>
    </div>

    <!-- ===== TABLE CONTAINER ===== -->
    <div class="equity-container" style="margin-top: 20px;">
      
      <div class="equity-year-card">
        <div class="equity-banner">
          <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 500; margin-bottom: 2px;">INEZA African Mining Ltd</div>
          <h3>Statement of profit or loss and Other Comprehensive Income</h3>
          <p>For the year ended 31 December 2022</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="comprehensive-income-table">
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
              <!-- Revenue -->
              <tr class="accent-row">
                <td class="row-label">Revenue</td>
                <td class="num-note">1</td>
                <td class="num-val">1,995,026,000</td>
                <td class="num-val">1,780,026,000</td>
                <td class="num-val">1,531,642,000</td>
              </tr>
              <!-- Cost of sales -->
              <tr>
                <td class="row-label">Cost of sales</td>
                <td class="num-note">2</td>
                <td class="num-val">(937,662,220)</td>
                <td class="num-val">(872,212,740)</td>
                <td class="num-val">(796,453,840)</td>
              </tr>
              <!-- Gross Profit -->
              <tr style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Gross Profit</td>
                <td class="num-note"></td>
                <td class="num-val" style="font-weight: 600;">1,057,363,780</td>
                <td class="num-val" style="font-weight: 600;">907,813,260</td>
                <td class="num-val" style="font-weight: 600;">735,188,160</td>
              </tr>
              <!-- Administrative expenses -->
              <tr>
                <td class="row-label">Administrative expenses</td>
                <td class="num-note">3</td>
                <td class="num-val">(256,723,747)</td>
                <td class="num-val">(151,562,434)</td>
                <td class="num-val">(152,145,000)</td>
              </tr>
              <!-- Operating Profit -->
              <tr style="border-top: 1px solid var(--border);">
                <td class="row-label" style="font-weight: 600;">Operating Profit(loss)</td>
                <td class="num-note"></td>
                <td class="num-val" style="font-weight: 600;">800,640,033</td>
                <td class="num-val" style="font-weight: 600;">756,250,826</td>
                <td class="num-val" style="font-weight: 600;">583,043,160</td>
              </tr>
              <!-- Finance costs -->
              <tr>
                <td class="row-label">Finance costs</td>
                <td class="num-note">4</td>
                <td class="num-val">(43,423,000)</td>
                <td class="num-val">(53,423,000)</td>
                <td class="num-val">(38,332,000)</td>
              </tr>
              <!-- Profit before tax -->
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label">Profit (Loss) before tax</td>
                <td class="num-note"></td>
                <td class="num-val">757,217,033</td>
                <td class="num-val">702,827,826</td>
                <td class="num-val">544,711,160</td>
              </tr>
              <!-- Income tax expenses -->
              <tr>
                <td class="row-label">Income tax expenses</td>
                <td class="num-note">11</td>
                <td class="num-val">(227,165,110)</td>
                <td class="num-val">(210,848,348)</td>
                <td class="num-val">(163,413,348)</td>
              </tr>
              <!-- Profit after tax -->
              <tr class="accent-row" style="border-top: 1px solid var(--border);">
                <td class="row-label">Profit (Loss) after tax</td>
                <td class="num-note"></td>
                <td class="num-val">530,051,923</td>
                <td class="num-val">491,979,478</td>
                <td class="num-val">381,297,812</td>
              </tr>
              <!-- Total comprehensive income -->
              <tr class="total-row">
                <td class="row-label">Total comprehensive income</td>
                <td class="num-note"></td>
                <td class="num-val">530,051,923</td>
                <td class="num-val">491,979,478</td>
                <td class="num-val">381,297,812</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>
</div>
<script src="../../src/js/navbar.js"></script>
<script src="../../src/js/sidebar.js"></script></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Comprehensive Income Statement\r\n\r\n";
        
        // Header Row
        csvContent += ",Notes,2022 (Frw),2021 (Frw),2020 (Frw)\r\n";

        const table = document.getElementById('comprehensive-income-table');
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            const label = row.querySelector('.row-label').textContent.trim().replace(/,/g, '');
            
            const noteCell = row.querySelector('.num-note');
            const note = noteCell ? noteCell.textContent.trim() : '';
            
            const cells = row.querySelectorAll('.num-val');
            
            let val2022 = cells[0].textContent.trim().replace(/,/g, '');
            let val2021 = cells[1].textContent.trim().replace(/,/g, '');
            let val2020 = cells[2].textContent.trim().replace(/,/g, '');

            csvContent += `"${label}",${note},${val2022},${val2021},${val2020}\r\n`;
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "ineza_comprehensive_income_statement.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
