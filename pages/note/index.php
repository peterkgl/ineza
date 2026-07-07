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
<title>INEZA African Mining — Notes to the Financial Statements</title>
<meta name="description" content="Notes to the Financial Statements for DETROIT CROWNED GOLDEN BUSINESS 'CGB' Ltd.">
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

  <?php $page_title = "Notes to Statements"; include '../include/navbar.php'; ?>

  <div class="content" id="noteContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24" style="width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; margin-right: 8px;"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          Notes to the Financial Statements
        </h1>
        <div class="page-sub">DETROIT CROWNED GOLDEN BUSINESS "CGB" Ltd — Year Ended 31st December 2022</div>
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
      <!-- Total Revenue (Note 1) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">Note 1</span>
        </div>
        <div class="stat-val">Frw 1,995.0M</div>
        <div class="stat-label">Sales Revenue (2022)</div>
      </div>

      <!-- Cost of Sales (Note 2) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
          </div>
          <span class="stat-trend trend-orange">Note 2</span>
        </div>
        <div class="stat-val">Frw 937.7M</div>
        <div class="stat-label">Cost of Goods Sold (2022)</div>
      </div>

      <!-- Administrative Expenses (Note 3) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          </div>
          <span class="stat-trend trend-orange">Note 3</span>
        </div>
        <div class="stat-val">Frw 256.7M</div>
        <div class="stat-label">Total Admin Expenses</div>
      </div>

      <!-- Finance Costs (Note 4) -->
      <div class="stat-card">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-blue">Note 4</span>
        </div>
        <div class="stat-val">Frw 43.4M</div>
        <div class="stat-label">Total Finance Costs (2022)</div>
      </div>
    </div>

    <!-- ===== NOTES STACK ===== -->
    <div class="note-container">

      <!-- Note 1: Revenue -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">1</span> Revenue</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Sales</td>
                <td class="num-val">1,995,026,000</td>
                <td class="num-val">1,780,026,000</td>
                <td class="num-val">1,531,642,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">1,995,026,000</td>
                <td class="num-val">1,780,026,000</td>
                <td class="num-val">1,531,642,000</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 2: Cost of sales -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">2</span> Cost of sales</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Cost of Goods Sold</td>
                <td class="num-val">937,662,220</td>
                <td class="num-val">872,212,740</td>
                <td class="num-val">796,453,840</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">937,662,220</td>
                <td class="num-val">872,212,740</td>
                <td class="num-val">796,453,840</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 3: Administrative expenses -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">3</span> Administrative expenses</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Others</td>
                <td class="num-val">82,660,000</td>
                <td class="num-val">72,740,434</td>
                <td class="num-val">90,589,000</td>
              </tr>
              <tr>
                <td class="row-label">CEPGL Fees</td>
                <td class="num-val">55,188,000</td>
                <td class="num-val">47,188,000</td>
                <td class="num-val">33,738,000</td>
              </tr>
              <tr>
                <td class="row-label">Salaries and wages</td>
                <td class="num-val">18,584,000</td>
                <td class="num-val">15,214,000</td>
                <td class="num-val">12,391,000</td>
              </tr>
              <tr>
                <td class="row-label">Rent</td>
                <td class="num-val">7,200,000</td>
                <td class="num-val">7,200,000</td>
                <td class="num-val">7,200,000</td>
              </tr>
              <tr>
                <td class="row-label">Annual and District Taxes</td>
                <td class="num-val">180,000</td>
                <td class="num-val">180,000</td>
                <td class="num-val">180,000</td>
              </tr>
              <tr>
                <td class="row-label">Depreciation</td>
                <td class="num-val">92,911,747</td>
                <td class="num-val">9,040,000</td>
                <td class="num-val">8,047,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">256,723,747</td>
                <td class="num-val">151,562,434</td>
                <td class="num-val">152,145,000</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 4: Finance costs -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">4</span> Finance costs</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Insurance fees</td>
                <td class="num-val">4,891,000</td>
                <td class="num-val">4,891,000</td>
                <td class="num-val">2,601,000</td>
              </tr>
              <tr>
                <td class="row-label">Bank interests and charges</td>
                <td class="num-val">38,532,000</td>
                <td class="num-val">48,532,000</td>
                <td class="num-val">35,731,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">43,423,000</td>
                <td class="num-val">53,423,000</td>
                <td class="num-val">38,332,000</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 5: Cash and cash equivalents -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">5</span> Cash and cash equivalents</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Cash at hand</td>
                <td class="num-val">15,806,100</td>
                <td class="num-val">25,986,330</td>
                <td class="num-val">14,507,100</td>
              </tr>
              <tr>
                <td class="row-label">Cash at bank (Local/RWF Account)</td>
                <td class="num-val">40,000,000</td>
                <td class="num-val">50,000,000</td>
                <td class="num-val">20,000,000</td>
              </tr>
              <tr>
                <td class="row-label">Cash at bank (Foreign/USD Account)</td>
                <td class="num-val">20,000,000</td>
                <td class="num-val">20,000,000</td>
                <td class="num-val">10,000,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">75,806,100</td>
                <td class="num-val">95,986,330</td>
                <td class="num-val">44,507,100</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 6: Accounts receivables -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">6</span> Accounts receivables</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Trade Receivables</td>
                <td class="num-val">421,999,685</td>
                <td class="num-val">350,565,172</td>
                <td class="num-val">175,174,448</td>
              </tr>
              <tr>
                <td class="row-label">Other Receivables</td>
                <td class="num-val">20,000,000</td>
                <td class="num-val">20,000,000</td>
                <td class="num-val">10,000,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">441,999,685</td>
                <td class="num-val">370,565,172</td>
                <td class="num-val">185,174,448</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 7: Inventory -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">7</span> Inventory</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Mineral Stocks</td>
                <td class="num-val">541,623,921</td>
                <td class="num-val">523,198,870</td>
                <td class="num-val">314,009,712</td>
              </tr>
              <tr>
                <td class="row-label">Consumables and Spares</td>
                <td class="num-val">250,000,000</td>
                <td class="num-val">250,000,000</td>
                <td class="num-val">150,000,000</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">791,623,921</td>
                <td class="num-val">773,198,870</td>
                <td class="num-val">464,009,712</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 9: Accounts payables / Other current liabilities -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">9</span> Accounts payables / Other current liabilities</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Accounts payables</td>
                <td class="num-val">112,748,306</td>
                <td class="num-val">257,194,092</td>
                <td class="num-val">149,525,000</td>
              </tr>
              <tr>
                <td class="row-label">Other current liabilities</td>
                <td class="num-val">50,000,000</td>
                <td class="num-val">67,030,230</td>
                <td class="num-val">35,630,100</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">162,748,306</td>
                <td class="num-val">324,224,322</td>
                <td class="num-val">185,155,100</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 10: Long term loans and other LT liabilities -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">10</span> Long term loans and other LT liabilities</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Long term Bank Loan</td>
                <td class="num-val">203,338,777</td>
                <td class="num-val">247,055,474</td>
                <td class="num-val">-</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">203,338,777</td>
                <td class="num-val">247,055,474</td>
                <td class="num-val">-</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Note 11: Current Tax Payable / Tax Expenses -->
      <div class="note-card">
        <div class="note-table-wrapper">
          <table class="note-table">
            <thead>
              <tr class="note-header-row">
                <td colspan="5"><span class="note-num">11</span> Current Tax Payable</td>
              </tr>
              <tr class="year-header-row">
                <th></th>
                <th>2022</th>
                <th>2021</th>
                <th>2020</th>
              </tr>
              <tr class="currency-header-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="row-label">Current Tax Payable</td>
                <td class="num-val">227,165,110</td>
                <td class="num-val">210,848,348</td>
                <td class="num-val">163,413,348</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Total</td>
                <td class="num-val">227,165,110</td>
                <td class="num-val">210,848,348</td>
                <td class="num-val">163,413,348</td>
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
        csvContent += "DETROIT CROWNED GOLDEN BUSINESS \"CGB\" Ltd - Notes to the Financial Statements\r\n\r\n";
        
        const tables = document.querySelectorAll('.note-table');
        tables.forEach(table => {
            const noteHeader = table.querySelector('.note-header-row td').textContent.trim().replace(/,/g, '');
            csvContent += `"${noteHeader}",,,\r\n`;
            csvContent += ",2022 (Frw),2021 (Frw),2020 (Frw)\r\n";
            
            const rows = table.querySelectorAll('tbody tr:not(.note-header-row):not(.year-header-row):not(.currency-header-row)');
            rows.forEach(row => {
                const labelCell = row.querySelector('.row-label');
                if (!labelCell) return;
                const label = labelCell.textContent.trim().replace(/,/g, '');
                
                const cells = row.querySelectorAll('.num-val');
                let val2022 = cells[0] ? cells[0].textContent.trim().replace(/,/g, '') : '';
                let val2021 = cells[1] ? cells[1].textContent.trim().replace(/,/g, '') : '';
                let val2020 = cells[2] ? cells[2].textContent.trim().replace(/,/g, '') : '';
                
                csvContent += `"${label}",${val2022},${val2021},${val2020}\r\n`;
            });
            csvContent += "\r\n"; // Blank line between notes
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "cgb_financial_notes_report.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
