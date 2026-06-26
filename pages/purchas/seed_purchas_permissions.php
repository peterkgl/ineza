<?php
require_once __DIR__ . '/../../config/db.php';

$permissions = [
    ['name' => 'View Purchases', 'code' => 'view_purchas'],
    ['name' => 'Create Purchases', 'code' => 'create_purchas'],
    ['name' => 'Edit Purchases', 'code' => 'edit_purchas'],
    ['name' => 'Delete Purchases', 'code' => 'delete_purchas']
];

foreach ($permissions as $p) {
    $name = mysqli_real_escape_string($conn, $p['name']);
    $code = mysqli_real_escape_string($conn, $p['code']);
    
    // Check if permission already exists
    $check = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$code' LIMIT 1");
    if ($check && mysqli_num_rows($check) > 0) {
        $row = mysqli_fetch_assoc($check);
        $permId = $row['id'];
        echo "Permission '$code' already exists.\n";
    } else {
        $insert = mysqli_query($conn, "INSERT INTO permissions (permition_name, permition_code) VALUES ('$name', '$code')");
        if ($insert) {
            $permId = mysqli_insert_id($conn);
            echo "Seeded permission '$code'.\n";
        } else {
            echo "Failed to seed permission '$code': " . mysqli_error($conn) . "\n";
            continue;
        }
    }

    // Attach to Admin role (id = 1) if not already attached
    $checkRolePerm = mysqli_query($conn, "SELECT * FROM role_permissions WHERE role_id = 1 AND permission_id = $permId LIMIT 1");
    if ($checkRolePerm && mysqli_num_rows($checkRolePerm) === 0) {
        $attach = mysqli_query($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (1, $permId)");
        if ($attach) {
            echo "Attached '$code' to Admin role.\n";
        } else {
            echo "Failed to attach '$code' to Admin: " . mysqli_error($conn) . "\n";
        }
    }
}

mysqli_close($conn);
?>
