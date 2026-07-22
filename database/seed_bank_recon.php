<?php
require_once __DIR__ . '/../config/db.php';

echo "=== SEEDING BANK RECONCILIATION DATA & PERMISSIONS ===\n";

// 1. Ensure permissions exist
$perms = [
    ['code' => 'view_bank_reconciliation', 'name' => 'View Bank Reconciliation'],
    ['code' => 'manage_bank_reconciliation', 'name' => 'Manage Bank Reconciliation'],
];

foreach ($perms as $p) {
    $code = mysqli_real_escape_string($conn, $p['code']);
    $name = mysqli_real_escape_string($conn, $p['name']);
    $chk = mysqli_query($conn, "SELECT id FROM permissions WHERE permition_code = '$code'");
    if (mysqli_num_rows($chk) == 0) {
        mysqli_query($conn, "INSERT INTO permissions (permition_code, permition_name) VALUES ('$code', '$name')");
        echo "Inserted permission: $code\n";
    }
}

// 2. Clear old test bank statement balances and recon items for our report_slugs if needed
$slugs = ['bank_recon_rwf', 'bank_recon_usd', 'bank_recon_euro'];

// Sample statement balances from Excel reference sheets:
// Equity RWF: As of 2026-05-02 -> 1742.34
// Equity USD: As of 2026-05-05 -> 2270.69
// Equity EURO: As of 2026-05-28 -> 364.25

$balances = [
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-02', 'bal' => 1742.34],
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'bal' => 1742.34],
    ['slug' => 'bank_recon_usd', 'date' => '2026-05-05', 'bal' => 2270.69],
    ['slug' => 'bank_recon_usd', 'date' => '2026-05-08', 'bal' => 2270.69],
    ['slug' => 'bank_recon_euro', 'date' => '2026-05-28', 'bal' => 364.25],
    ['slug' => 'bank_recon_euro', 'date' => '2026-05-08', 'bal' => 364.25],
];

foreach ($balances as $b) {
    $slug = mysqli_real_escape_string($conn, $b['slug']);
    $date = $b['date'];
    $bal = $b['bal'];
    
    $check = mysqli_query($conn, "SELECT id FROM bank_statement_balances WHERE report_slug = '$slug' AND as_of_date = '$date'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO bank_statement_balances (report_slug, as_of_date, balance) VALUES ('$slug', '$date', $bal)");
        echo "Inserted statement balance: $slug | $date | $bal\n";
    }
}

// 3. Sample Deposits in Transit from Excel:
$items = [
    // RWF deposits in transit
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-30', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Equity Bank'],
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-31', 'ref' => '0', 'amount' => 0.00, 'desc' => 'NTAGWABIRA NSANA Jean'],
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-31', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Equity Bank'],
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-31', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Ineza African Mining Ltd Rwf'],
    ['slug' => 'bank_recon_rwf', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-31', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Equity Bank'],
    
    // USD deposits in transit
    ['slug' => 'bank_recon_usd', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-27', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Petty Cash Fund'],
    ['slug' => 'bank_recon_usd', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-27', 'ref' => '0', 'amount' => 0.00, 'desc' => 'FOREX. - USD to Rwf'],
    ['slug' => 'bank_recon_usd', 'date' => '2026-05-08', 'type' => 'deposit_in_transit', 'item_date' => '2026-03-27', 'ref' => '0', 'amount' => 0.00, 'desc' => 'Production fees - 115 tags from B&K'],
];

foreach ($items as $it) {
    $slug = mysqli_real_escape_string($conn, $it['slug']);
    $date = $it['date'];
    $type = $it['type'];
    $item_date = $it['item_date'];
    $ref = mysqli_real_escape_string($conn, $it['ref']);
    $amount = (float)$it['amount'];
    $desc = mysqli_real_escape_string($conn, $it['desc']);
    
    $check = mysqli_query($conn, "SELECT id FROM bank_recon_items WHERE report_slug = '$slug' AND as_of_date = '$date' AND description = '$desc' AND item_date = '$item_date'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO bank_recon_items (report_slug, as_of_date, item_type, item_date, reference, amount, description) VALUES ('$slug', '$date', '$type', '$item_date', '$ref', $amount, '$desc')");
        echo "Inserted recon item: $slug | $desc\n";
    }
}

echo "=== SEEDING COMPLETED SUCCESSFULLY ===\n";
