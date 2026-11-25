<?php
require_once '../includes/session.php';
require_login();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer_service.php');
    exit();
}

$pengguna_id = (int) $_SESSION['user_id'];
$kode_transaksi = isset($_POST['trx_code']) ? trim($_POST['trx_code']) : '';
$topik = isset($_POST['topic']) ? trim($_POST['topic']) : '';
$subjek = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$pesan_inti = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($pesan_inti === '' || $subjek === '' || $topik === '') {
    header('Location: ../customer_service.php?error=empty_fields');
    exit();
}

$pesan_lengkap = '[' . $topik . '] ' . $subjek . ' - ' . $pesan_inti;
$kode_param = ($kode_transaksi !== '') ? $kode_transaksi : null;
$status_awal = 'terbuka';

$stmt = mysqli_prepare(
    $conn,
    "INSERT INTO tiket_bantuan (pengguna_id, kode_transaksi, pesan, status) VALUES (?, ?, ?, ?)"
);

if (!$stmt) {
    header('Location: ../customer_service.php?error=db_error');
    exit();
}

mysqli_stmt_bind_param($stmt, "isss", $pengguna_id, $kode_param, $pesan_lengkap, $status_awal);
$berhasil = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if ($berhasil) {
    header('Location: ../ticket_history.php?success=created');
    exit();
}

header('Location: ../customer_service.php?error=save_failed');
exit();
?>
