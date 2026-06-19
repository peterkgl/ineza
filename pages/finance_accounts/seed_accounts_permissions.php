<?php
require_once __DIR__ . '/../../config/db.php';

$permissions = [
    ['name' => 'View Account Types', 'code' => 'view_account_types'],
    ['name' => 'Create Account Type', 'code' => 'create_account_type'],
    ['name' => 'Edit Account Type', 'code' => 'edit_account_type'],
    ['name' => 'Delete Account Type', 'code' => 'delete_account_type'],
    ['name' => 'View Accounts', 'code' => 'view_accounts'],
    ['name' => 'Create Account', 'code' => 'create_account'],
    ['name' => 'Edit Account', 'code' => 'edit_account'],
    ['name' => 'Delete Account', 'code' => 'delete_account']
];

foreach ($permissions as $p) {
    $name = mysqli_real_escape_string($conn, $p['name']);
    $code = mysqli_real_escape_string($conn, $p['code']);
    
    $check = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$code' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        echo "Permission '$code' already exists.\n";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO permissions (permition_name, permition_code) VALUES ('$name', '$code')");
        if ($insert) {
            echo "Seeded '$code'.\n";
        } else {
            echo "Failed '$code': " . mysqli_error($conn) . "\n";
        }
    }
}

mysqli_close($conn);
?>
