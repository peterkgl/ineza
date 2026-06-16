<?php
require_once __DIR__ . '/../../config/db.php';

$permissions = [
    ['name' => 'View Exchange Rates', 'code' => 'view_exchange_rates'],
    ['name' => 'Create Exchange Rate', 'code' => 'create_exchange_rate'],
    ['name' => 'Edit Exchange Rate', 'code' => 'edit_exchange_rate'],
    ['name' => 'Delete Exchange Rate', 'code' => 'delete_exchange_rate']
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
