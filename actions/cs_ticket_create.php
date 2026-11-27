<?php
require_once '../includes/session.php';
require_login();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer_service.php');
    exit();
}

if (!$conn) {
    header('Location: ../customer_service.php?error=db_error');
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

$panjang_kode = function_exists('mb_strlen') ? mb_strlen($kode_transaksi, 'UTF-8') : strlen($kode_transaksi);
if ($kode_transaksi !== '' && $panjang_kode > 32) {
    header('Location: ../customer_service.php?error=trx_code_too_long');
    exit();
}

$pesan_lengkap = '[' . $topik . '] ' . $subjek . ' - ' . $pesan_inti;
if ((function_exists('mb_strlen') ? mb_strlen($pesan_lengkap, 'UTF-8') : strlen($pesan_lengkap)) > 5000) {
    $pesan_lengkap = function_exists('mb_substr') ? mb_substr($pesan_lengkap, 0, 5000, 'UTF-8') : substr($pesan_lengkap, 0, 5000);
}
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
