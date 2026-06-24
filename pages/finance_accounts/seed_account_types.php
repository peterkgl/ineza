<?php
require_once __DIR__ . '/../../config/db.php';

// Hard-coded account groups to insert into the database
$accountGroups = [
    [
        'id' => -1,
        'code' => '1000',
        'name' => 'Assets',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ],
    [
        'id' => -2,
        'code' => '2000',
        'name' => 'Liabilities',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ],
    [
        'id' => -3,
        'code' => '3000',
        'name' => 'Equity',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ],
    [
        'id' => -4,
        'code' => '4000',
        'name' => 'Revenue',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ],
    [
        'id' => -5,
        'code' => '5000',
        'name' => 'Cost of Sales',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ],
    [
        'id' => -6,
        'code' => '6000',
        'name' => 'Operating Expenses',
        'parent_id' => null,
        'is_editable' => 0,
        'is_deletable' => 0
    ]
];

foreach ($accountGroups as $group) {
    $id = (int)$group['id'];
    $code = mysqli_real_escape_string($conn, $group['code']);
    $name = mysqli_real_escape_string($conn, $group['name']);
    $is_editable = (int)$group['is_editable'];
    $is_deletable = (int)$group['is_deletable'];

    // Check if the group already exists
    $checkQuery = "SELECT id FROM account_types WHERE id = $id LIMIT 1";
    $checkResult = mysqli_query($conn, $checkQuery);
    
    if ($checkResult && mysqli_num_rows($checkResult) > 0) {
        echo "Account group '$name' (code: $code) already exists.\n";
    } else {
        // Insert the group
        $insertQuery = "INSERT INTO account_types (id, code, name, parent_id, is_editable, is_deletable) 
                        VALUES ($id, '$code', '$name', NULL, $is_editable, $is_deletable)";
        $insertResult = mysqli_query($conn, $insertQuery);
        
        if ($insertResult) {
            echo "Successfully seeded account group '$name' (code: $code).\n";
        } else {
            echo "Failed to seed account group '$name': " . mysqli_error($conn) . "\n";
        }
    }
}

echo "\nAccount type seeding complete!\n";
mysqli_close($conn);
?>