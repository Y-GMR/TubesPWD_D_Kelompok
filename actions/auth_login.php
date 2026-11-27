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
$remember = isset($_POST['remember']);

if (($username === '' && $email === '') || $password === '') {
    header('Location: ../login.php?error=data_tidak_lengkap');
    exit();
}

if (!$conn) {
    header('Location: ../login.php?error=koneksi_gagal');
    exit();
}

if ($username !== '' && $email !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id, username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, status_verifikasi FROM pengguna WHERE username = ? AND email = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
    }
} else {
    $pengenal = ($username !== '') ? $username : $email;
    $stmt = mysqli_prepare($conn, "SELECT id, username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, status_verifikasi FROM pengguna WHERE username = ? OR email = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $pengenal, $pengenal);
    }
}

if (!$stmt) {
    header('Location: ../login.php?error=sistem_gagal');
    exit();
}

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

if ($remember && ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), array(
        'expires' => time() + (7 * 86400),
        'path' => $params['path'],
        'domain' => $params['domain'],
        'secure' => $params['secure'],
        'httponly' => true,
        'samesite' => 'Lax'
    ));
}

if ($pengguna['peran'] === 'admin') {
    header('Location: ../views/admin/dashboard.php');
} elseif ($pengguna['peran'] === 'produsen') {
    header('Location: ../producer_dashboard.php');
} else {
    header('Location: ../katalog.php');
}
exit();
?>
