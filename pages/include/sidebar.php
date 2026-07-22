<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$sidebarUserId = $_SESSION['user_id'] ?? 0;

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/permissions.php';

$script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
$pages_pos = strpos($script, '/pages/');
$depth = 1;
if ($pages_pos !== false) {
    $sub_path = substr($script, $pages_pos + 7);
    $segments = explode('/', $sub_path);
    $depth = count($segments);
}
$prefix_to_pages = ($depth === 2) ? '../' : './';
$prefix_to_root = ($depth === 2) ? '../../' : '../';

// Check permissions
$showDashboard = hasPermission($conn, $sidebarUserId, 'view_dashboard');

$showSuppliers = hasPermission($conn, $sidebarUserId, 'view_suppliers') || 
                 hasPermission($conn, $sidebarUserId, 'create_supplier') || 
                 hasPermission($conn, $sidebarUserId, 'edit_supplier') || 
                 hasPermission($conn, $sidebarUserId, 'delete_supplier');

$showSupplierAdvances = hasPermission($conn, $sidebarUserId, 'view_supplier_advances');

$showProducts = hasPermission($conn, $sidebarUserId, 'view_products') || 
                hasPermission($conn, $sidebarUserId, 'create_product') || 
                hasPermission($conn, $sidebarUserId, 'edit_product') || 
                hasPermission($conn, $sidebarUserId, 'delete_product');

$showProductCategories = hasPermission($conn, $sidebarUserId, 'view_product_categories') || 
                         hasPermission($conn, $sidebarUserId, 'create_product_category') || 
                         hasPermission($conn, $sidebarUserId, 'edit_product_category') || 
                         hasPermission($conn, $sidebarUserId, 'delete_product_category');

$showProductElements = hasPermission($conn, $sidebarUserId, 'view_product_elements') || 
                       hasPermission($conn, $sidebarUserId, 'create_product_element') || 
                       hasPermission($conn, $sidebarUserId, 'edit_product_element') || 
                       hasPermission($conn, $sidebarUserId, 'delete_product_element');

$showCurrencies = hasPermission($conn, $sidebarUserId, 'view_currencies') || 
                  hasPermission($conn, $sidebarUserId, 'create_currency') || 
                  hasPermission($conn, $sidebarUserId, 'edit_currency') || 
                  hasPermission($conn, $sidebarUserId, 'delete_currency');

$showAccountTypes = hasPermission($conn, $sidebarUserId, 'view_account_types') || 
                    hasPermission($conn, $sidebarUserId, 'create_account_type') || 
                    hasPermission($conn, $sidebarUserId, 'edit_account_type') || 
                    hasPermission($conn, $sidebarUserId, 'delete_account_type');

$showAccounts = hasPermission($conn, $sidebarUserId, 'view_accounts') || 
                hasPermission($conn, $sidebarUserId, 'create_account') || 
                hasPermission($conn, $sidebarUserId, 'edit_account') || 
                hasPermission($conn, $sidebarUserId, 'delete_account');

$showUsers = hasPermission($conn, $sidebarUserId, 'view_users') || 
             hasPermission($conn, $sidebarUserId, 'create_user') || 
             hasPermission($conn, $sidebarUserId, 'edit_user') || 
             hasPermission($conn, $sidebarUserId, 'delete_user');

$showRoles = hasPermission($conn, $sidebarUserId, 'view_roles') || 
             hasPermission($conn, $sidebarUserId, 'create_role') || 
             hasPermission($conn, $sidebarUserId, 'edit_role') || 
             hasPermission($conn, $sidebarUserId, 'delete_role');

$showPermissions = hasPermission($conn, $sidebarUserId, 'view_permissions') || 
                   hasPermission($conn, $sidebarUserId, 'create_permission') || 
                   hasPermission($conn, $sidebarUserId, 'edit_permission') || 
                   hasPermission($conn, $sidebarUserId, 'delete_permission');

$showAuditLogs = hasPermission($conn, $sidebarUserId, 'view_audit_logs');

$showWarehouses = hasPermission($conn, $sidebarUserId, 'view_warehouses') || 
                  hasPermission($conn, $sidebarUserId, 'create_warehouse') || 
                  hasPermission($conn, $sidebarUserId, 'edit_warehouse') || 
                  hasPermission($conn, $sidebarUserId, 'delete_warehouse');

$showLots = hasPermission($conn, $sidebarUserId, 'view_lots') || 
            hasPermission($conn, $sidebarUserId, 'create_lot') || 
            hasPermission($conn, $sidebarUserId, 'edit_lot') || 
            hasPermission($conn, $sidebarUserId, 'delete_lot') || 
            hasPermission($conn, $sidebarUserId, 'close_lot');

$showUnitOfMeasure = hasPermission($conn, $sidebarUserId, 'view_unit_of_measure') || 
                     hasPermission($conn, $sidebarUserId, 'create_unit_of_measure') || 
                     hasPermission($conn, $sidebarUserId, 'edit_unit_of_measure') || 
                     hasPermission($conn, $sidebarUserId, 'delete_unit_of_measure');

$showPurchases = hasPermission($conn, $sidebarUserId, 'view_purchas') || 
                 hasPermission($conn, $sidebarUserId, 'create_purchas') || 
                 hasPermission($conn, $sidebarUserId, 'edit_purchas') || 
                 hasPermission($conn, $sidebarUserId, 'delete_purchas');

$showStock = hasPermission($conn, $sidebarUserId, 'view_stock') || 
             hasPermission($conn, $sidebarUserId, 'view_stock_movement');

$showSales = hasPermission($conn, $sidebarUserId, 'view_sales') || 
             hasPermission($conn, $sidebarUserId, 'create_sale') || 
             hasPermission($conn, $sidebarUserId, 'edit_sale') || 
             hasPermission($conn, $sidebarUserId, 'delete_sale');

$showJournalEntries = hasPermission($conn, $sidebarUserId, 'view_journal_entries') || 
                      hasPermission($conn, $sidebarUserId, 'create_journal_entry') || 
                      hasPermission($conn, $sidebarUserId, 'cancel_journal_entry');

$showGeneralLedger = hasPermission($conn, $sidebarUserId, 'view_general_ledger');
$showGeneralJournal = hasPermission($conn, $sidebarUserId, 'view_general_journal');
$showTrialBalance = hasPermission($conn, $sidebarUserId, 'view_trial_balance');
$showAccountLedger = hasPermission($conn, $sidebarUserId, 'view_account_ledger');

// Individual Finance Reports
$showBanks = hasPermission($conn, $sidebarUserId, 'view_banks') || hasPermission($conn, $sidebarUserId, 'view_cash') || hasPermission($conn, $sidebarUserId, 'view_bank_reconciliation');
$showCash = hasPermission($conn, $sidebarUserId, 'view_cash');
$showBalanceSheet = hasPermission($conn, $sidebarUserId, 'view_balance_sheet');
$showEquityReport = hasPermission($conn, $sidebarUserId, 'view_equity_report');
$showComprehensiveIncome = hasPermission($conn, $sidebarUserId, 'view_comprehensive_income');
$showStatementOfCashFlow = hasPermission($conn, $sidebarUserId, 'view_statement_of_cash_flow');
$showNote = hasPermission($conn, $sidebarUserId, 'view_note');
$showBankReconciliation = hasPermission($conn, $sidebarUserId, 'view_bank_reconciliation') || 
                          hasPermission($conn, $sidebarUserId, 'manage_bank_reconciliation') ||
                          hasPermission($conn, $sidebarUserId, 'view_report_bank_recon_rwf') ||
                          hasPermission($conn, $sidebarUserId, 'view_report_bank_recon_usd');

// Individual Mining Reports
$showRepBankReconUsd = hasPermission($conn, $sidebarUserId, 'view_report_bank_recon_usd');
$showRepBankReconRwf = hasPermission($conn, $sidebarUserId, 'view_report_bank_recon_rwf');
$showRepMonthlyTx = hasPermission($conn, $sidebarUserId, 'view_report_monthly_transactions');
$showRepCashCountHq = hasPermission($conn, $sidebarUserId, 'view_report_cash_count_hq');
$showRepPettyCashRub = hasPermission($conn, $sidebarUserId, 'view_report_petty_cash_rub');
$showRepCashCountRub = hasPermission($conn, $sidebarUserId, 'view_report_cash_count_rub');
$showRepPurchaseLogsTa = hasPermission($conn, $sidebarUserId, 'view_report_purchase_logs_ta');
$showRepAccountsPayable = hasPermission($conn, $sidebarUserId, 'view_report_accounts_payable');
$showRepTinSummary = hasPermission($conn, $sidebarUserId, 'view_report_tin_summary');
$showRepTaSummary = hasPermission($conn, $sidebarUserId, 'view_report_ta_summary');

// Login History
$showLoginHistory = hasPermission($conn, $sidebarUserId, 'view_login_history');

// System Settings
$showSettings = hasPermission($conn, $sidebarUserId, 'manage_settings');
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <img src="<?php echo $prefix_to_root; ?>src/logo/ineza_logo.png" alt="INEZA African Mining Logo" class="sidebar-logo-image">
  </div>
  <div class="sidebar-nav">

  <?php if ($showDashboard): ?>
    <div class="sidebar-section">Main</div>
    <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'dashboard') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>dashboard" id="nav-dashboard">
      <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
  <?php endif; ?>

  <?php if ($showSuppliers || $showSupplierAdvances): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Suppliers</div>
    <?php if ($showSuppliers): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'suppliers') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>suppliers/index" id="nav-suppliers">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        Suppliers
      </a>
    <?php endif; ?>
    <?php if ($showSupplierAdvances): ?>
      <a class="nav-item" href="#" id="nav-supplier-advances">
        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Supplier Advances
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showProducts || $showProductElements || $showProductCategories || $showWarehouses || $showLots || $showUnitOfMeasure || $showPurchases || $showStock || $showSales): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Products &amp; Stock</div>
    <?php if ($showProducts): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'products') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>products/index" id="nav-products">
        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Products
        <span class="nav-badge green">Active</span>
      </a>
    <?php endif; ?>
    <?php if ($showPurchases): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'purchas') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>purchas/index" id="nav-purchases">
        <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/><line x1="9" y1="20" x2="9" y2="10"/></svg>
        Purchases
      </a>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'pay_supplier') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>purchas/pay_supplier" id="nav-pay-supplier">
        <svg viewBox="0 0 24 24"><path d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z"/><path d="M9 12l2 2 4-4"/></svg>
        Pay Supplier
      </a>
    <?php endif; ?>
    <?php if ($showSales): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'sales') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>sales/index" id="nav-sales">
        <svg viewBox="0 0 24 24"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
        Sales
      </a>
    <?php endif; ?>
    <?php if ($showStock): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'stock') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>stock/index" id="nav-stock">
        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
        Stock
      </a>
    <?php endif; ?>
    <?php if ($showLots): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'lots') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>lots/index" id="nav-lots">
        <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
        Lots
      </a>
    <?php endif; ?>
    <?php if ($showWarehouses): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'warehouses') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>warehouses/index" id="nav-warehouses">
        <svg viewBox="0 0 24 24"><path d="M20 21v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8M2 7l10-5 10 5-10 5z"/></svg>
        Warehouses
      </a>
    <?php endif; ?>
    <?php if ($showProductElements): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'product_elements') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>product_elements/index" id="nav-product-elements">
        <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Product Elements
      </a>
    <?php endif; ?>
    <?php if ($showUnitOfMeasure): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'unit_of_measure') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>unit_of_measure/index" id="nav-unit-of-measure">
        <svg viewBox="0 0 24 24"><path d="M3 3h18v18H3V3zm2 2v14h14V5H5z"/><path d="M7 9h10v2H7zM7 13h10v2H7z"/></svg>
        Units of Measure
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showBanks || $showCash || $showCurrencies || $showBalanceSheet || $showEquityReport || $showComprehensiveIncome || $showStatementOfCashFlow || $showNote || $showBankReconciliation): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Finance</div>
    <?php if ($showBanks): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], '/banks/') !== false || strpos($_SERVER['SCRIPT_NAME'], 'banks') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>banks/index" id="nav-banks">
        <svg viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
        Banks
        <span class="nav-badge green">New</span>
      </a>
    <?php endif; ?>
    <?php if ($showBankReconciliation): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'bank_reconciliation') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>bank_reconciliation/index" id="nav-bank-reconciliation">
        <svg viewBox="0 0 24 24"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
        Bank Reconciliation
        <span class="nav-badge blue">Module</span>
      </a>
    <?php endif; ?>
    <?php if ($showCash): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'cash') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>cash/index" id="nav-cash">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/></svg>
        Cash
      </a>
    <?php endif; ?>
    <?php if ($showCurrencies): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'currencies') !== false || strpos($_SERVER['SCRIPT_NAME'], 'Currencies') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>Currencies/index" id="nav-currencies">
        <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Currencies
        <span class="nav-badge">Base</span>
      </a>
    <?php endif; ?>
    <?php if ($showBalanceSheet): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'balance_sheet') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>balance_sheet/index" id="nav-balance-sheet">
        <svg viewBox="0 0 24 24"><path d="M12 22V2M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Balance Sheet
      </a>
    <?php endif; ?>
    <?php if ($showEquityReport): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'equity_report') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>equity_report/index" id="nav-equity-report">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Changes in Equity
      </a>
    <?php endif; ?>
    <?php if ($showComprehensiveIncome): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'comprehensive_income') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>comprehensive_income/index" id="nav-comprehensive-income">
        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Income Statement
      </a>
    <?php endif; ?>
    <?php if ($showStatementOfCashFlow): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'statement_of_cash_flow') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>statement_of_cash_flow/index" id="nav-statement-of-cash-flow">
        <svg viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        Cash Flow Statement
      </a>
    <?php endif; ?>
    <?php if ($showNote): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'note') !== false && strpos($_SERVER['SCRIPT_NAME'], 'finance_accounts') === false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>note/index" id="nav-note">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Note
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showAccountTypes || $showAccounts || $showJournalEntries || $showGeneralLedger || $showTrialBalance || $showAccountLedger || $showGeneralJournal): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Accounts</div>
    <?php if ($showAccountTypes): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'account_types') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>finance_accounts/account_types" id="nav-account-types">
        <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Account Types
      </a>
    <?php endif; ?>
    <?php if ($showAccounts): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'accounts') !== false && strpos($_SERVER['SCRIPT_NAME'], 'account_types') === false && strpos($_SERVER['SCRIPT_NAME'], 'journal_entries') === false && strpos($_SERVER['SCRIPT_NAME'], 'general_ledger') === false && strpos($_SERVER['SCRIPT_NAME'], 'trial_balance') === false && strpos($_SERVER['SCRIPT_NAME'], 'account_ledger') === false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>finance_accounts/accounts" id="nav-accounts">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
        Accounts
      </a>
    <?php endif; ?>
    <?php if ($showJournalEntries): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'journal_entries') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>journal_entries/index" id="nav-journal-entries">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Journal Entries
      </a>
    <?php endif; ?>
    <?php if ($showGeneralLedger): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'general_ledger') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>general_ledger/index" id="nav-general-ledger">
        <svg viewBox="0 0 24 24"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5v-15z"/></svg>
        General Ledger
      </a>
    <?php endif; ?>
    <?php if ($showTrialBalance): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'trial_balance') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>trial_balance/index" id="nav-trial-balance">
        <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
        Trial Balance
      </a>
    <?php endif; ?>
    <?php if ($showAccountLedger): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'account_ledger') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>account_ledger/index" id="nav-account-ledger">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
        Account Ledger
      </a>
    <?php endif; ?>
    <?php if ($showGeneralJournal): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'general_journal') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>general_journal/index" id="nav-general-journal">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        General Journal
      </a>
      <?php endif; ?>
  <?php endif; ?>

  <?php if ($showRepBankReconUsd || $showRepBankReconRwf || $showRepMonthlyTx || $showRepCashCountHq || $showRepPettyCashRub || $showRepCashCountRub || $showRepPurchaseLogsTa || $showRepAccountsPayable || $showRepTinSummary || $showRepTaSummary): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Mining Reports</div>
    <details class="nav-group" <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_') !== false) ? 'open' : ''; ?> style="outline: none;">
      <summary class="nav-item" style="list-style: none; display: flex; justify-content: space-between; align-items: center; cursor: pointer; outline: none;">
        <span style="display: flex; align-items: center; gap: 9px;">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
          System All reports
        </span>
        <svg class="chevron" viewBox="0 0 24 24" style="width: 12px; height: 12px; transition: transform 0.2s;"><polyline points="6 9 12 15 18 9"/></svg>
      </summary>
      <div class="nav-group-items" style="padding-left: 10px; margin-top: 2px; max-height: 300px; overflow-y: auto;">
        <?php if ($showRepBankReconUsd): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_bank_recon_usd') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_bank_recon_usd/index">Bank Recon USD</a>
        <?php endif; ?>
        <?php if ($showRepBankReconRwf): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_bank_recon_rwf') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_bank_recon_rwf/index">Bank Recon RWF</a>
        <?php endif; ?>
        <?php if ($showRepMonthlyTx): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_monthly_transactions') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_monthly_transactions/index">Monthly Transactions</a>
        <?php endif; ?>
        <?php if ($showRepCashCountHq): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_cash_count_hq') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_cash_count_hq/index">Cash Count HQ</a>
        <?php endif; ?>
        <?php if ($showRepPettyCashRub): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_petty_cash_rub') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_petty_cash_rub/index">Petty Cash Rubaya</a>
        <?php endif; ?>
        <?php if ($showRepCashCountRub): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_cash_count_rub') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_cash_count_rub/index">Cash Count Rubaya</a>
        <?php endif; ?>
        <?php if ($showRepPurchaseLogsTa): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_purchase_logs_ta') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_purchase_logs_ta/index">Tantalum Purchases</a>
        <?php endif; ?>
        <?php if ($showRepAccountsPayable): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_accounts_payable') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_accounts_payable/index">Accounts Payable</a>
        <?php endif; ?>
        <?php if ($showRepTinSummary): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_tin_summary') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_tin_summary/index">Tin Summary</a>
        <?php endif; ?>
        <?php if ($showRepTaSummary): ?>
          <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'report_ta_summary') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>report_ta_summary/index">Ta Summary</a>
        <?php endif; ?>
      </div>
    </details>
  <?php endif; ?>

  <style>
    details.nav-group summary::-webkit-details-marker {
      display: none;
    }
    details.nav-group[open] .chevron {
      transform: rotate(180deg);
    }
    .nav-group-items::-webkit-scrollbar {
      width: 3px;
    }
    .nav-group-items::-webkit-scrollbar-track {
      background: transparent;
    }
    .nav-group-items::-webkit-scrollbar-thumb {
      background: rgba(0,0,0,0.1);
      border-radius: 4px;
    }
  </style>

  <?php if ($showUsers || $showRoles || $showPermissions || $showSettings): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">User &amp; Settings</div>
    <?php if ($showUsers): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'users') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>users/index" id="nav-users">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Users
      </a>
    <?php endif; ?>
    <?php if ($showRoles): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'role') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>role/index" id="nav-roles">
        <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        Roles
      </a>
    <?php endif; ?>
    <?php if ($showPermissions): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'permissions') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>permissions/index" id="nav-permissions">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Permissions
      </a>
    <?php endif; ?>
    <?php if ($showSettings): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'settings') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>settings/index" id="nav-settings">
        <svg viewBox="0 0 24 24" style="fill: none; stroke: currentColor; stroke-width: 2;"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        System Settings
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showAuditLogs || $showLoginHistory): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">System Logs</div>
    <?php if ($showAuditLogs): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'audit_logs') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>audit_logs/index" id="nav-audit-logs">
        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Audit Logs
      </a>
    <?php endif; ?>
    <?php if ($showLoginHistory): ?>
      <a class="nav-item" href="#" id="nav-login-history">
        <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
        Login History
      </a>
    <?php endif; ?>
  <?php endif; ?>
  </div>
</aside>
