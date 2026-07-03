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
<title>INEZA African Mining — Statement of Changes in Equity</title>
<meta name="description" content="Statement of Changes in Equity report for INEZA African Mining Ltd.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../src/css/dashboard.css">
<link rel="stylesheet" href="../../src/css/sidebar.css">
<link rel="stylesheet" href="../../src/css/navbar.css">
<link rel="stylesheet" href="../../src/css/equity_report.css">
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

  <?php $page_title = "Statement of Changes in Equity"; include '../include/navbar.php'; ?>

  <div class="content" id="equityContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Statement of Changes in Equity
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — As of December 31, 2022</div>
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
      <!-- Total Equity -->
      <div class="stat-card" id="stat-total-equity">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/><circle cx="12" cy="12" r="4"/></svg>
          </div>
          <span class="stat-trend trend-up">2022</span>
        </div>
        <div class="stat-val">Frw 1,418,329,213</div>
        <div class="stat-label">Total Equity Balance</div>
      </div>

      <!-- Share Capital -->
      <div class="stat-card" id="stat-share-capital">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--orange-light)">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
          </div>
          <span class="stat-trend trend-orange">Capital</span>
        </div>
        <div class="stat-val">Frw 15,000,000</div>
        <div class="stat-label">Paid-in Share Capital</div>
      </div>

      <!-- Retained Earnings -->
      <div class="stat-card" id="stat-retained-earnings">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">Earnings</span>
        </div>
        <div class="stat-val">Frw 1,403,329,213</div>
        <div class="stat-label">Accumulated Retained Earnings</div>
      </div>

      <!-- growth -->
      <div class="stat-card" id="stat-equity-growth">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polygon points="17 6 23 6 23 12"/></svg>
          </div>
          <span class="stat-trend trend-up">+59.7%</span>
        </div>
        <div class="stat-val">Frw 530,051,923</div>
        <div class="stat-label">Growth (YoY increase)</div>
      </div>
    </div>

    <!-- ===== YEAR FILTER SEARCHABLE DROPDOWN ===== -->
    <div class="year-filter-container">
      <span class="dropdown-label">Select Fiscal Year</span>
      <div class="custom-dropdown">
        <button type="button" class="dropdown-trigger" id="dropdownTriggerBtn">Fiscal Year 2022</button>
        <div class="dropdown-menu">
          <div class="dropdown-search-wrapper">
            <input type="text" class="dropdown-search" placeholder="Search year..." autocomplete="off">
          </div>
          <div class="dropdown-options-list">
            <div class="dropdown-option selected" data-value="2022">Fiscal Year 2022</div>
            <div class="dropdown-option" data-value="2021">Fiscal Year 2021</div>
            <div class="dropdown-option" data-value="2020">Fiscal Year 2020</div>
            <div class="no-results">No years found</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== TABLES CONTAINER ===== -->
    <div class="equity-container">
      
      <!-- FISCAL YEAR 2022 -->
      <div class="equity-year-card" data-year-section="2022">
        <div class="equity-banner">
          <h3>Statement of Changes in Equity</h3>
          <p>For the year ended 31 December 2022</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="table-2022">
            <thead>
              <tr>
                <th></th>
                <th>Share Capital</th>
                <th>Retained Earnings</th>
                <th>Total Equity</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr class="accent-row">
                <td class="row-label">Opening balance (1 January 2022)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">873,277,290</td>
                <td class="num-val">888,277,290</td>
              </tr>
              <tr>
                <td class="row-label">Profit (loss) for the Year</td>
                <td class="num-val">-</td>
                <td class="num-val">530,051,923</td>
                <td class="num-val">530,051,923</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Closing balance (31 December 2022)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">1,403,329,213</td>
                <td class="num-val">1,418,329,213</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FISCAL YEAR 2021 -->
      <div class="equity-year-card" data-year-section="2021">
        <div class="equity-banner">
          <h3>Statement of Changes in Equity</h3>
          <p>For the year ended 31 December 2021</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="table-2021">
            <thead>
              <tr>
                <th></th>
                <th>Share Capital</th>
                <th>Retained Earnings</th>
                <th>Total Equity</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr class="accent-row">
                <td class="row-label">Opening balance (1 January 2021)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">381,297,812</td>
                <td class="num-val">396,297,812</td>
              </tr>
              <tr>
                <td class="row-label">Profit (loss) for the Year</td>
                <td class="num-val">-</td>
                <td class="num-val">491,979,478</td>
                <td class="num-val">491,979,478</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Closing balance (31 December 2021)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">873,277,290</td>
                <td class="num-val">888,277,290</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- FISCAL YEAR 2020 -->
      <div class="equity-year-card" data-year-section="2020">
        <div class="equity-banner">
          <h3>Statement of Changes in Equity</h3>
          <p>For the year ended 31 December 2020</p>
        </div>
        <div class="equity-table-wrapper">
          <table class="equity-table" id="table-2020">
            <thead>
              <tr>
                <th></th>
                <th>Share Capital</th>
                <th>Retained Earnings</th>
                <th>Total Equity</th>
              </tr>
              <tr class="currency-row">
                <th></th>
                <th>Frw</th>
                <th>Frw</th>
                <th>Frw</th>
              </tr>
            </thead>
            <tbody>
              <tr class="accent-row">
                <td class="row-label">Opening balance (1 January 2020)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">-</td>
                <td class="num-val">15,000,000</td>
              </tr>
              <tr>
                <td class="row-label">Profit (loss) for the Year</td>
                <td class="num-val">-</td>
                <td class="num-val">381,297,812</td>
                <td class="num-val">381,297,812</td>
              </tr>
              <tr class="total-row">
                <td class="row-label">Closing balance (31 December 2020)</td>
                <td class="num-val">15,000,000</td>
                <td class="num-val">381,297,812</td>
                <td class="num-val">396,297,812</td>
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
    // Custom Searchable Dropdown Year Filter Logic
    const dropdown = document.querySelector('.custom-dropdown');
    const trigger = document.querySelector('.dropdown-trigger');
    const searchInput = document.querySelector('.dropdown-search');
    const options = document.querySelectorAll('.dropdown-option');
    const noResults = document.querySelector('.no-results');
    const yearSections = document.querySelectorAll('.equity-year-card');

    // Default load: show only the active year (2022) and hide others
    const initialYear = '2022';
    yearSections.forEach(section => {
        if (section.getAttribute('data-year-section') === initialYear) {
            section.style.display = 'block';
        } else {
            section.style.display = 'none';
        }
    });

    // Toggle Dropdown Panel
    trigger.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdown.classList.toggle('open');
        if (dropdown.classList.contains('open')) {
            searchInput.value = '';
            // Reset options visibility
            options.forEach(opt => opt.style.display = 'block');
            noResults.style.display = 'none';
            searchInput.focus();
        }
    });

    // Search Options Filter
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        let visibleCount = 0;

        options.forEach(opt => {
            const text = opt.textContent.toLowerCase();
            if (text.includes(query)) {
                opt.style.display = 'block';
                visibleCount++;
            } else {
                opt.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            noResults.style.display = 'block';
        } else {
            noResults.style.display = 'none';
        }
    });

    // Option Selection
    options.forEach(opt => {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Remove selection class and set on selected
            options.forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');

            // Update trigger text
            const selectedText = this.textContent.trim();
            const selectedYear = this.getAttribute('data-value');
            trigger.textContent = selectedText;

            // Close dropdown
            dropdown.classList.remove('open');

            // Show selected section, hide others
            yearSections.forEach(section => {
                if (section.getAttribute('data-year-section') === selectedYear) {
                    section.style.display = 'block';
                    section.style.opacity = '0';
                    setTimeout(() => {
                        section.style.opacity = '1';
                        section.style.transition = 'opacity 0.25s ease-in-out';
                    }, 50);
                } else {
                    section.style.display = 'none';
                }
            });
        });
    });

    // Click outside to close
    document.addEventListener('click', function() {
        if (dropdown) {
            dropdown.classList.remove('open');
        }
    });

    // Stop propagation inside dropdown menu so it doesn't close when typing or clicking inside
    const dropdownMenu = dropdown ? dropdown.querySelector('.dropdown-menu') : null;
    if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    // CSV Exporter logic
    document.getElementById('exportCsvBtn').addEventListener('click', function() {
        let csvContent = "data:text/csv;charset=utf-8,";
        csvContent += "INEZA African Mining Ltd - Statement of Changes in Equity\r\n\r\n";

        const sections = [
            { year: '2022', tableId: 'table-2022' },
            { year: '2021', tableId: 'table-2021' },
            { year: '2020', tableId: 'table-2020' }
        ];

        sections.forEach(sec => {
            const table = document.getElementById(sec.tableId);
            csvContent += `Statement of Changes in Equity - Year ended 31 December ${sec.year}\r\n`;
            
            // Header Row
            csvContent += ",Share Capital (Frw),Retained Earnings (Frw),Total Equity (Frw)\r\n";

            // Body Rows
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const label = row.querySelector('.row-label').textContent.trim().replace(/,/g, '');
                const cells = row.querySelectorAll('.num-val');
                
                let capital = cells[0].textContent.trim().replace(/,/g, '');
                let earnings = cells[1].textContent.trim().replace(/,/g, '');
                let total = cells[2].textContent.trim().replace(/,/g, '');

                csvContent += `"${label}",${capital},${earnings},${total}\r\n`;
            });
            csvContent += "\r\n"; // Blank line between years
        });

        // Trigger download
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "ineza_statement_of_changes_in_equity.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>

</body>
</html>
