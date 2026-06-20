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

// Check permissions
$showSuppliers = hasPermission($conn, $sidebarUserId, 'view_suppliers');
$showProducts = hasPermission($conn, $sidebarUserId, 'view_products');
$showProductElements = hasPermission($conn, $sidebarUserId, 'view_product_elements');
$showCurrencies = hasPermission($conn, $sidebarUserId, 'view_currencies');
$showAccountTypes = hasPermission($conn, $sidebarUserId, 'view_account_types');
$showAccounts = hasPermission($conn, $sidebarUserId, 'view_accounts');
$showUsers = hasPermission($conn, $sidebarUserId, 'view_users');
$showRoles = hasPermission($conn, $sidebarUserId, 'view_roles');
$showPermissions = hasPermission($conn, $sidebarUserId, 'view_permissions');
$showAuditLogs = hasPermission($conn, $sidebarUserId, 'view_audit_logs');
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-badge">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <div>
      <div class="logo-text">INEZA Mining</div>
      <div class="logo-sub">Financial Suite</div>
    </div>
  </div>

  <div class="sidebar-section">Main</div>
  <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'dashboard') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>dashboard" id="nav-dashboard">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </a>

  <?php if ($showSuppliers): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Suppliers</div>
    <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'suppliers') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>suppliers/index" id="nav-suppliers">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
      Suppliers
    </a>
    <a class="nav-item" href="#" id="nav-supplier-advances">
      <svg viewBox="0 0 24 24"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
      Supplier Advances
    </a>
  <?php endif; ?>

  <?php if ($showProducts || $showProductElements): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Products &amp; Stock</div>
    <?php if ($showProducts): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'products') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>products/index" id="nav-products">
        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Products
        <span class="nav-badge green">Active</span>
      </a>
    <?php endif; ?>
    <?php if ($showProductElements): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'product_elements') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>product_elements/index" id="nav-product-elements">
        <svg viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Product Elements
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showCurrencies): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Finance</div>
    <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'currencies') !== false || strpos($_SERVER['SCRIPT_NAME'], 'Currencies') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>Currencies/index" id="nav-currencies">
      <svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
      Currencies
      <span class="nav-badge">Base</span>
    </a>
  <?php endif; ?>

  <?php if ($showAccountTypes || $showAccounts): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">Accounts</div>
    <?php if ($showAccountTypes): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'account_types') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>finance_accounts/account_types" id="nav-account-types">
        <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Account Types
      </a>
    <?php endif; ?>
    <?php if ($showAccounts): ?>
      <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'accounts') !== false && strpos($_SERVER['SCRIPT_NAME'], 'account_types') === false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>finance_accounts/accounts" id="nav-accounts">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
        Accounts
      </a>
    <?php endif; ?>
  <?php endif; ?>

  <?php if ($showUsers || $showRoles || $showPermissions): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">User Management</div>
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
  <?php endif; ?>

  <?php if ($showAuditLogs): ?>
    <div class="sidebar-divider"></div>
    <div class="sidebar-section">System Logs</div>
    <a class="nav-item <?php echo (strpos($_SERVER['SCRIPT_NAME'], 'audit_logs') !== false) ? 'active' : ''; ?>" href="<?php echo $prefix_to_pages; ?>audit_logs/index" id="nav-audit-logs">
      <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Audit Logs
    </a>
    <a class="nav-item" href="#" id="nav-login-history">
      <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
      Login History
    </a>
  <?php endif; ?>
</aside>
