<?php
require_once __DIR__ . '/../../config/db.php';

$permissions = [
    ['name' => 'View Product Categories', 'code' => 'view_product_categories'],
    ['name' => 'Create Product Category', 'code' => 'create_product_category'],
    ['name' => 'Edit Product Category', 'code' => 'edit_product_category'],
    ['name' => 'Delete Product Category', 'code' => 'delete_product_category']
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
