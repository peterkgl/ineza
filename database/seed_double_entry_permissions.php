<?php
require_once __DIR__ . '/../config/db.php';

$permissions = [
    ['name' => 'View Journal Entries', 'code' => 'view_journal_entries'],
    ['name' => 'Create Journal Entry', 'code' => 'create_journal_entry'],
    ['name' => 'Cancel Journal Entry', 'code' => 'cancel_journal_entry'],
    ['name' => 'View General Ledger', 'code' => 'view_general_ledger'],
    ['name' => 'View Trial Balance', 'code' => 'view_trial_balance'],
    ['name' => 'View Account Ledger', 'code' => 'view_account_ledger'],
    
    // Dashboard & Currencies
    ['name' => 'View Dashboard', 'code' => 'view_dashboard'],
    ['name' => 'View Currencies', 'code' => 'view_currencies'],
    ['name' => 'Create Currency', 'code' => 'create_currency'],
    ['name' => 'Edit Currency', 'code' => 'edit_currency'],
    ['name' => 'Delete Currency', 'code' => 'delete_currency'],
    
    // Finance Report pages
    ['name' => 'View Balance Sheet', 'code' => 'view_balance_sheet'],
    ['name' => 'View Changes in Equity', 'code' => 'view_equity_report'],
    ['name' => 'View Income Statement', 'code' => 'view_comprehensive_income'],
    ['name' => 'View Cash Flow Statement', 'code' => 'view_statement_of_cash_flow'],
    ['name' => 'View Note', 'code' => 'view_note'],
    
    // Mining Reports
    ['name' => 'View Report Bank Recon USD', 'code' => 'view_report_bank_recon_usd'],
    ['name' => 'View Report Bank Recon RWF', 'code' => 'view_report_bank_recon_rwf'],
    ['name' => 'View Report Monthly Transactions', 'code' => 'view_report_monthly_transactions'],
    ['name' => 'View Report Cash Count HQ', 'code' => 'view_report_cash_count_hq'],
    ['name' => 'View Report Petty Cash Rubaya', 'code' => 'view_report_petty_cash_rub'],
    ['name' => 'View Report Cash Count Rubaya', 'code' => 'view_report_cash_count_rub'],
    ['name' => 'View Report Tantalum Purchases', 'code' => 'view_report_purchase_logs_ta'],
    ['name' => 'View Report Accounts Payable', 'code' => 'view_report_accounts_payable'],
    ['name' => 'View Report Tin Summary', 'code' => 'view_report_tin_summary'],
    ['name' => 'View Report Ta Summary', 'code' => 'view_report_ta_summary'],
    
    // Placeholder links
    ['name' => 'View Supplier Advances', 'code' => 'view_supplier_advances'],
    ['name' => 'View Login History', 'code' => 'view_login_history']
];

$roleMapping = [
    'admin' => ['view_journal_entries', 'create_journal_entry', 'cancel_journal_entry', 'view_general_ledger', 'view_trial_balance', 'view_account_ledger'],
    'secretary' => ['view_journal_entries', 'create_journal_entry', 'cancel_journal_entry', 'view_general_ledger', 'view_trial_balance', 'view_account_ledger'],
    'view only' => ['view_journal_entries', 'view_general_ledger', 'view_trial_balance', 'view_account_ledger']
];

$permissionIds = [];

echo "=== Seeding New Permissions ===\n";
foreach ($permissions as $p) {
    $name = mysqli_real_escape_string($conn, $p['name']);
    $code = mysqli_real_escape_string($conn, $p['code']);
    
    $check = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$code' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $permId = (int)$row['id'];
        $permissionIds[$code] = $permId;
        echo "Permission '$code' already exists (ID: $permId).\n";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO permissions (permition_name, permition_code) VALUES ('$name', '$code')");
        if ($insert) {
            $permId = mysqli_insert_id($conn);
            $permissionIds[$code] = $permId;
            echo "Seeded Permission '$code' successfully (ID: $permId).\n";
        } else {
            echo "Failed seeding Permission '$code': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\n=== Seeding Role Mappings ===\n";
foreach ($roleMapping as $roleName => $mappedCodes) {
    $roleNameEsc = mysqli_real_escape_string($conn, $roleName);
    $roleCheck = mysqli_query($conn, "SELECT id FROM roles WHERE LOWER(name) = LOWER('$roleNameEsc') LIMIT 1");
    
    if ($roleCheck && mysqli_num_rows($roleCheck) > 0) {
        $roleRow = mysqli_fetch_assoc($roleCheck);
        $roleId = (int)$roleRow['id'];
        echo "Mapping permissions for Role '$roleName' (ID: $roleId):\n";
        
        foreach ($mappedCodes as $code) {
            if (isset($permissionIds[$code])) {
                $permId = $permissionIds[$code];
                
                $checkMapping = mysqli_query($conn, "SELECT 1 FROM role_permissions WHERE role_id = $roleId AND permission_id = $permId LIMIT 1");
                if ($checkMapping && mysqli_num_rows($checkMapping) > 0) {
                    echo "  - Permission '$code' already mapped to Role '$roleName'.\n";
                } else {
                    $insertMapping = mysqli_query($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES ($roleId, $permId)");
                    if ($insertMapping) {
                        echo "  - Mapped Permission '$code' to Role '$roleName'.\n";
                    } else {
                        echo "  - Failed mapping '$code' to '$roleName': " . mysqli_error($conn) . "\n";
                    }
                }
            } else {
                echo "  - Warning: Permission ID not found for code '$code'.\n";
            }
        }
    } else {
        echo "Role '$roleName' not found in database.\n";
    }
}

echo "\n=== Mapping ALL Permissions to Admin Role (ID = 1) ===\n";
$allPermsRes = mysqli_query($conn, "SELECT id, permition_code FROM permissions");
$adminMapCount = 0;
while ($permRow = mysqli_fetch_assoc($allPermsRes)) {
    $permId = (int)$permRow['id'];
    $code = $permRow['permition_code'];
    
    $checkAdminMapping = mysqli_query($conn, "SELECT 1 FROM role_permissions WHERE role_id = 1 AND permission_id = $permId LIMIT 1");
    if ($checkAdminMapping && mysqli_num_rows($checkAdminMapping) === 0) {
        $insertAdminMapping = mysqli_query($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (1, $permId)");
        if ($insertAdminMapping) {
            $adminMapCount++;
        }
    }
}
echo "Mapped $adminMapCount new permissions to Admin role.\n";

mysqli_close($conn);
echo "\n=== Seeding Complete ===\n";
?>
