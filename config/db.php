<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "ineza_african_mining";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

function ensureDailyRollover($conn) {
    $today = date('Y-m-d');
    $query = "UPDATE stock s
              JOIN lots l ON s.lot_id = l.id
              SET s.opening = s.closing,
                  s.last_rolled_over_at = '$today'
              WHERE l.closing_date IS NULL
                AND (s.last_rolled_over_at < '$today' OR s.last_rolled_over_at IS NULL)";
    mysqli_query($conn, $query);
}

ensureDailyRollover($conn);

// create rra,rma,inkomane and production charges accounts if they don't exist
$accountsToCheck = [
    ['account_name' => 'RRA', 'account_type_id' => 18,'account_code' => '2024'],
    ['account_name' => 'RMA', 'account_type_id' => 18,'account_code' => '2034'],
    ['account_name' => 'Inkomane', 'account_type_id' => 18,'account_code' => '2044'],
    ['account_name' => 'Production Charges', 'account_type_id' => 62,'account_code' => '2054'],
];

// check if they exists
foreach ($accountsToCheck as $account) {
    $account_name = $account['account_name'];
    $account_type_id = $account['account_type_id'];
    $account_code = $account['account_code'];

    $checkQuery = "SELECT id FROM accounts WHERE account_name = '$account_name' AND account_type_id = $account_type_id";
    $result = mysqli_query($conn, $checkQuery);

    if (mysqli_num_rows($result) == 0) {
        // Account doesn't exist, insert it
        $insertQuery = "INSERT INTO accounts (account_name, account_type_id, account_code) VALUES ('$account_name', $account_type_id, '$account_code')";
        mysqli_query($conn, $insertQuery);
    }
}
?>
