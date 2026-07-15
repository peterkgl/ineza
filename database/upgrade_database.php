<?php
require_once __DIR__ . '/../config/db.php';

echo "Starting database upgrade...\n";

// 1. Create settings table if not exists
$settingsTableSql = "
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_query($conn, $settingsTableSql)) {
    echo "Table 'settings' verified/created successfully.\n";
} else {
    echo "Error creating table 'settings': " . mysqli_error($conn) . "\n";
}

// 2. Seed default configurations
$defaultSettings = [
    [
        'key' => 'tax_rate_rra',
        'value' => '3.0',
        'desc' => 'Rwanda Revenue Authority withheld tax rate percentage (e.g. 3.0 for 3%)'
    ],
    [
        'key' => 'tax_rate_rma_tin',
        'value' => '50.0',
        'desc' => 'RMA tax rate per kg in RWF for Tin (Sn) products'
    ],
    [
        'key' => 'tax_rate_rma_coltan',
        'value' => '125.0',
        'desc' => 'RMA tax rate per kg in RWF for Tantalum/Coltan (Ta) products'
    ],
    [
        'key' => 'tax_rate_rma_wolframite',
        'value' => '50.0',
        'desc' => 'RMA tax rate per kg in RWF for Wolframite (W03) products'
    ],
    [
        'key' => 'tax_rate_inkomane_tin',
        'value' => '20.0',
        'desc' => 'Inkomane tax rate per kg in RWF for Tin (Sn) products'
    ],
    [
        'key' => 'tax_rate_inkomane_coltan',
        'value' => '40.0',
        'desc' => 'Inkomane tax rate per kg in RWF for Tantalum/Coltan (Ta) products'
    ],
    [
        'key' => 'tax_rate_inkomane_wolframite',
        'value' => '20.0',
        'desc' => 'Inkomane tax rate per kg in RWF for Wolframite (W03) products'
    ]
];

foreach ($defaultSettings as $setting) {
    $key = mysqli_real_escape_string($conn, $setting['key']);
    $val = mysqli_real_escape_string($conn, $setting['value']);
    $desc = mysqli_real_escape_string($conn, $setting['desc']);
    
    $checkSetting = mysqli_query($conn, "SELECT id FROM settings WHERE setting_key = '$key' LIMIT 1");
    if ($checkSetting && mysqli_num_rows($checkSetting) === 0) {
        $insert = mysqli_query($conn, "INSERT INTO settings (setting_key, setting_value, description) VALUES ('$key', '$val', '$desc')");
        if ($insert) {
            echo "Seeded default setting '$key' = '$val'.\n";
        } else {
            echo "Failed to seed setting '$key': " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Setting '$key' already exists.\n";
    }
}

// 3. Alter purchasing table to add fluc and lme_paid columns
$columnsToAdd = [
    'fluc' => "DECIMAL(14,4) DEFAULT NULL AFTER lme_price",
    'lme_paid' => "DECIMAL(14,4) DEFAULT NULL AFTER fluc"
];

foreach ($columnsToAdd as $colName => $colDef) {
    $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM `purchasing` LIKE '$colName'");
    if ($checkCol && mysqli_num_rows($checkCol) === 0) {
        $alterSql = "ALTER TABLE `purchasing` ADD `$colName` $colDef";
        if (mysqli_query($conn, $alterSql)) {
            echo "Added column '$colName' to table 'purchasing'.\n";
        } else {
            echo "Error adding column '$colName': " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Column '$colName' already exists in table 'purchasing'.\n";
    }
}

// 4. Seed permission 'manage_settings'
$permName = 'Manage System Settings';
$permCode = 'manage_settings';

$checkPerm = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$permCode' LIMIT 1");
if ($checkPerm && mysqli_num_rows($checkPerm) > 0) {
    $row = mysqli_fetch_assoc($checkPerm);
    $permId = $row['id'];
    echo "Permission '$permCode' already exists.\n";
} else {
    $insertPerm = mysqli_query($conn, "INSERT INTO permissions (permition_name, permition_code) VALUES ('$permName', '$permCode')");
    if ($insertPerm) {
        $permId = mysqli_insert_id($conn);
        echo "Seeded permission '$permCode'.\n";
    } else {
        echo "Failed to seed permission '$permCode': " . mysqli_error($conn) . "\n";
        $permId = null;
    }
}

if ($permId) {
    // Attach to Admin role (id = 1) if not already attached
    $checkRolePerm = mysqli_query($conn, "SELECT * FROM role_permissions WHERE role_id = 1 AND permission_id = $permId LIMIT 1");
    if ($checkRolePerm && mysqli_num_rows($checkRolePerm) === 0) {
        $attach = mysqli_query($conn, "INSERT INTO role_permissions (role_id, permission_id) VALUES (1, $permId)");
        if ($attach) {
            echo "Attached '$permCode' to Admin role.\n";
        } else {
            echo "Failed to attach '$permCode' to Admin role: " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "Permission '$permCode' is already attached to Admin role.\n";
    }
}

echo "Database upgrade completed.\n";
?>
