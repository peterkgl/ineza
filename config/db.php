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
?>
