<?php
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "tubespwd_db";

$conn = @mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    if (defined('DB_NO_DIE') && DB_NO_DIE) {
        $conn = null;
        return;
    }
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
mysqli_query($conn, "SET time_zone = '+07:00'");
?>
