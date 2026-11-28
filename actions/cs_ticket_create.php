<?php
require_once '../includes/session.php';
require_role(array('konsumen', 'produsen'));
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../customer_service.php');
    exit();
}

$token_kiriman = (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_kiriman)) {
    header('Location: ../customer_service.php?error=csrf_invalid');
    exit();
}

if (!$conn) {
    header('Location: ../customer_service.php?error=db_error');
    exit();
}

$pengguna_id = (int) $_SESSION['user_id'];
$pengguna_cs = ambilPenggunaById($conn, $pengguna_id);
if (!$pengguna_cs) {
    hapusSesiPengguna();
    header('Location: ../login.php?error=login_required');
    exit();
}
sinkronSesiPengguna($pengguna_cs);
if (!in_array($pengguna_cs['peran'], array('konsumen', 'produsen'), true)) {
    require_role(array('konsumen', 'produsen'));
}
$kode_transaksi = (isset($_POST['trx_code']) && is_string($_POST['trx_code'])) ? trim($_POST['trx_code']) : '';
$topik = (isset($_POST['topic']) && is_string($_POST['topic'])) ? trim($_POST['topic']) : '';
$subjek = (isset($_POST['subject']) && is_string($_POST['subject'])) ? trim($_POST['subject']) : '';
$pesan_inti = (isset($_POST['message']) && is_string($_POST['message'])) ? trim($_POST['message']) : '';

if ($pesan_inti === '' || $subjek === '' || $topik === '') {
    header('Location: ../customer_service.php?error=empty_fields');
    exit();
}

$panjang_kode = function_exists('mb_strlen') ? mb_strlen($kode_transaksi, 'UTF-8') : strlen($kode_transaksi);
if ($kode_transaksi !== '' && $panjang_kode > 32) {
    header('Location: ../customer_service.php?error=trx_code_too_long');
    exit();
}

if ($kode_transaksi !== '') {
    $stmt_kode = mysqli_prepare($conn, "SELECT id FROM transaksi WHERE kode_transaksi = ? AND (konsumen_id = ? OR produsen_id = ?) LIMIT 1");
    if (!$stmt_kode) {
        header('Location: ../customer_service.php?error=db_error');
        exit();
    }
    mysqli_stmt_bind_param($stmt_kode, "sii", $kode_transaksi, $pengguna_id, $pengguna_id);
    mysqli_stmt_execute($stmt_kode);
    $res_kode = mysqli_stmt_get_result($stmt_kode);
    $ada_kode = mysqli_fetch_assoc($res_kode);
    mysqli_stmt_close($stmt_kode);
    if (!$ada_kode) {
        header('Location: ../customer_service.php?error=trx_code_invalid');
        exit();
    }
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
