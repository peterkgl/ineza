<?php
require_once __DIR__ . '/../config/db.php';

$permissions = [
    ['name' => 'View Journal Entries', 'code' => 'view_journal_entries'],
    ['name' => 'Create Journal Entry', 'code' => 'create_journal_entry'],
    ['name' => 'Cancel Journal Entry', 'code' => 'cancel_journal_entry'],
    ['name' => 'View General Ledger', 'code' => 'view_general_ledger'],
    ['name' => 'View Trial Balance', 'code' => 'view_trial_balance'],
    ['name' => 'View Account Ledger', 'code' => 'view_account_ledger']
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

mysqli_close($conn);
echo "\n=== Seeding Complete ===\n";
?>
