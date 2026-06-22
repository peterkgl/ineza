<?php require_once 'login/auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>INEZA African Mining — Financial Dashboard</title>
<meta name="description" content="INEZA African Mining Ltd financial management dashboard — cash lead schedule, bank accounts, purchase logs, and mineral stock tracking.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../src/css/dashboard.css">
<link rel="stylesheet" href="../src/css/sidebar.css">
<link rel="stylesheet" href="../src/css/navbar.css">
<script>
  (function() {
    var savedTheme = localStorage.getItem('theme');
    var currentTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', currentTheme);
  })();
</script>
</head>
<body>

<?php include './include/sidebar.php'; ?>

<!-- ========== MAIN ========== -->
<div class="main">

  <?php $page_title = "Cash Lead Schedule"; include './include/navbar.php'; ?>

  <!-- ========== CONTENT ========== -->
  <div class="content" id="dashboardContent">

    <!-- Page Header -->
    <div class="page-header">
      <div>
        <h1 class="page-title">
          <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
          Cash Lead Schedule
        </h1>
        <div class="page-sub">INEZA African Mining Ltd — As of January 5, 2026</div>
      </div>
      <div class="page-actions">
        <button class="btn-sm" id="exportBtn">
          <svg class="btn-icon" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
          Export
        </button>
        <button class="btn-sm btn-primary" id="addEntryBtn">
          <svg class="btn-icon btn-icon-white" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Entry
        </button>
      </div>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="stats-grid">
      <!-- Total Cash -->
      <div class="stat-card" id="stat-total-cash">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--green-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--green)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-up">Total</span>
        </div>
        <div class="stat-val">$234,608</div>
        <div class="stat-label">Total Cash Balance</div>
      </div>

      <!-- USD Equity -->
      <div class="stat-card" id="stat-usd">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--blue-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-blue">USD</span>
        </div>
        <div class="stat-val">$2,069</div>
        <div class="stat-label">US$ Equity Balance</div>
      </div>

      <!-- RWF Equity -->
      <div class="stat-card" id="stat-rwf">
        <div class="stat-top">
          <div class="stat-icon" style="background:#FBF0EB">
            <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">RWF</span>
        </div>
        <div class="stat-val">RWF 6.4M</div>
        <div class="stat-label">RWF Equity ($4,415)</div>
      </div>

      <!-- Euro Equity -->
      <div class="stat-card" id="stat-eur">
        <div class="stat-top">
          <div class="stat-icon" style="background:var(--amber-bg)">
            <svg viewBox="0 0 24 24" style="stroke:var(--amber)"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
          </div>
          <span class="stat-trend trend-warn">EUR</span>
        </div>
        <div class="stat-val">€227,950</div>
        <div class="stat-label">Euro Equity (RWF 195K)</div>
      </div>
    </div>

    <!-- ===== MAIN GRID ===== -->
    <div class="main-grid">

      <!-- LEFT COLUMN -->
      <div class="left-col">
        <!-- ACCOUNTS PAYABLE TABLE -->
        <div class="card">
          <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;">
            <div class="card-title">Accounts Payable — Mineral Suppliers</div>
            <div class="card-actions" style="display: flex; align-items: center; gap: 8px;">
              <input type="text" id="dashboardSearch" class="form-control" placeholder="Search..." style="max-width: 180px; padding: 6px 10px; font-size: 12px; margin: 0;">
              <button class="btn-sm">Filter</button>
              <button class="btn-sm">Export CSV</button>
            </div>
          </div>
          <div class="table-container">
            <table class="data-table">
            <thead>
              <tr>
                <th style="width: 50px;">#</th>
                <th>Supplier</th>
                <th>Lot</th>
                <th>Weight (kg)</th>
                <th>Est. Amount</th>
                <th>Advances</th>
                <th>Balance Due</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="payableList">
              <tr>
                <td>1</td>
                <td class="td-name">Paulin Murego</td>
                <td><span class="code-badge">Tin-Lot 01&02</span></td>
                <td>—</td>
                <td class="td-bold">$206,085</td>
                <td>$131,000</td>
                <td class="td-bold">$75,085</td>
                <td><span class="status-pill pill-amber">Partial</span></td>
              </tr>
              <tr>
                <td>2</td>
                <td class="td-name">Paulin Murego</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>3,241.9</td>
                <td class="td-bold">$65,053</td>
                <td>$0</td>
                <td class="td-bold">$65,053</td>
                <td><span class="status-pill pill-red">Unpaid</span></td>
              </tr>
              <tr>
                <td>3</td>
                <td class="td-name">Darius Bimenyimana</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>1,178.4</td>
                <td class="td-bold">$28,200</td>
                <td>$0</td>
                <td class="td-bold">$28,200</td>
                <td><span class="status-pill pill-red">Unpaid</span></td>
              </tr>
              <tr>
                <td>4</td>
                <td class="td-name">Richard Akayezu</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>354.0</td>
                <td class="td-bold">$7,558</td>
                <td>$0</td>
                <td class="td-bold">$7,558</td>
                <td><span class="status-pill pill-red">Unpaid</span></td>
              </tr>
              <tr>
                <td>5</td>
                <td class="td-name">Marc Nshimyumuremyi</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>1,843.0</td>
                <td class="td-bold">$40,617</td>
                <td>$0</td>
                <td class="td-bold">$40,617</td>
                <td><span class="status-pill pill-red">Unpaid</span></td>
              </tr>
              <tr>
                <td>6</td>
                <td class="td-name">Eprocomi</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>2,599.8</td>
                <td class="td-bold">$54,322</td>
                <td>$20,000</td>
                <td class="td-bold">$34,322</td>
                <td><span class="status-pill pill-amber">Partial</span></td>
              </tr>
              <tr>
                <td>7</td>
                <td class="td-name">Eprocomi</td>
                <td><span class="code-badge">Ta-Lot 04</span></td>
                <td>1,087.4</td>
                <td class="td-bold">$64,670</td>
                <td>$44,000</td>
                <td class="td-bold">$20,670</td>
                <td><span class="status-pill pill-amber">Partial</span></td>
              </tr>
              <tr>
                <td>8</td>
                <td class="td-name">Bosco</td>
                <td><span class="code-badge">Tin-Lot 05</span></td>
                <td>1,407.5</td>
                <td class="td-bold">$35,403</td>
                <td>$25,000</td>
                <td class="td-bold">$10,403</td>
                <td><span class="status-pill pill-amber">Partial</span></td>
              </tr>
              <tr>
                <td>9</td>
                <td class="td-name">Bosco</td>
                <td><span class="code-badge">Tin-Lot 03</span></td>
                <td>949.4</td>
                <td class="td-bold">$23,240</td>
                <td>$12,500</td>
                <td class="td-bold">$10,740</td>
                <td><span class="status-pill pill-amber">Partial</span></td>
              </tr>
            </tbody>
          </table>
          </div>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-col">
        <!-- FINANCIAL ALERTS -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Financial Alerts</div>
          </div>
          <div class="alert-list">
            <div class="alert-item red">
              <svg class="alert-icon" style="stroke:var(--red)" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              <div>
                <div class="alert-title">Total Payable — $304,241 outstanding</div>
                <div class="alert-sub">Estimated unpaid minerals to 9 suppliers</div>
              </div>
            </div>
            <div class="alert-item amber">
              <svg class="alert-icon" style="stroke:var(--amber)" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
              <div>
                <div class="alert-title">Bank Recon Diff — $(1,906) RWF Equity</div>
                <div class="alert-sub">Reconciliation gap in RWF Equity account</div>
              </div>
            </div>
            <div class="alert-item amber">
              <svg class="alert-icon" style="stroke:var(--amber)" viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0zM12 9v4M12 17h.01"/></svg>
              <div>
                <div class="alert-title">Bank Recon Diff — $34,408 USD Equity</div>
                <div class="alert-sub">Outstanding items in US$ bank reconciliation</div>
              </div>
            </div>
            <div class="alert-item blue">
              <svg class="alert-icon" style="stroke:var(--blue)" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
              <div>
                <div class="alert-title">Euro Recon Diff — $(179,918)</div>
                <div class="alert-sub">Reconciliation variance on Euro Equity</div>
              </div>
            </div>
          </div>
        </div>

        <!-- MINERAL STOCK SUMMARY -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Mineral Stock Summary</div>
          </div>
          <div class="cat-list">
            <!-- Tin -->
            <div class="cat-item">
              <div class="cat-icon" style="background:#FBF0EB">
                <svg viewBox="0 0 24 24" style="stroke:var(--orange)"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Tin (Sn) — Stock Available</div>
                <div class="cat-count">Under processing</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:83%"></div></div>
              <div class="cat-val">1,326.5 kg</div>
            </div>
            <!-- Tantalum -->
            <div class="cat-item">
              <div class="cat-icon" style="background:var(--blue-bg)">
                <svg viewBox="0 0 24 24" style="stroke:var(--blue)"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Tantalum (Ta) — Stock Available</div>
                <div class="cat-count">Under processing</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:17%;background:var(--blue)"></div></div>
              <div class="cat-val">265.7 kg</div>
            </div>
            <!-- Total -->
            <div class="cat-item">
              <div class="cat-icon" style="background:var(--green-bg)">
                <svg viewBox="0 0 24 24" style="stroke:var(--green)"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              </div>
              <div class="cat-info">
                <div class="cat-name">Total Mineral Stock</div>
                <div class="cat-count">Sn + Ta combined</div>
              </div>
              <div class="cat-bar-wrap"><div class="cat-bar" style="width:100%;background:var(--green)"></div></div>
              <div class="cat-val">1,592.2 kg</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <div class="bottom-spacer"></div>
  </div>
</div>

<script src="../src/js/navbar.js"></script>
<script src="../src/js/sidebar.js"></script>
<script src="../src/js/dashboard.js"></script>
</body>
</html>
