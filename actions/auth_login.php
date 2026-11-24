<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit();
}

$username = isset($_POST['username']) ? bersihkanInput($_POST['username']) : '';
$email    = isset($_POST['email']) ? bersihkanInput($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

$pengenal = '';
if ($username !== '') {
    $pengenal = $username;
} elseif ($email !== '') {
    $pengenal = $email;
}

if ($pengenal === '' || $password === '') {
    header('Location: ../login.php?error=data_tidak_lengkap');
    exit();
}

if (!$conn) {
    header('Location: ../login.php?error=koneksi_gagal');
    exit();
}

$stmt = mysqli_prepare($conn, "SELECT id, username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, status_verifikasi FROM pengguna WHERE username = ? OR email = ? LIMIT 1");
if (!$stmt) {
    header('Location: ../login.php?error=sistem_gagal');
    exit();
}

mysqli_stmt_bind_param($stmt, "ss", $pengenal, $pengenal);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$pengguna = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$pengguna) {
    header('Location: ../login.php?error=kredensial_salah');
    exit();
}

if (!password_verify($password, $pengguna['kata_sandi_hash'])) {
    header('Location: ../login.php?error=kredensial_salah');
    exit();
}

simpanSesiPengguna($pengguna);

if ($pengguna['peran'] === 'admin') {
    header('Location: ../views/admin/dashboard.php');
} elseif ($pengguna['peran'] === 'produsen') {
    header('Location: ../producer_dashboard.xhtml');
} else {
    header('Location: ../katalog.php');
}
exit();
?>
