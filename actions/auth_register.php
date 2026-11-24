<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.xhtml');
    exit();
}

$nama_lengkap     = isset($_POST['fullname']) ? bersihkanInput($_POST['fullname']) : '';
$username         = isset($_POST['username']) ? bersihkanInput($_POST['username']) : '';
$email            = isset($_POST['email']) ? bersihkanInput($_POST['email']) : '';
$password         = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$peran            = isset($_POST['role']) ? bersihkanInput($_POST['role']) : 'konsumen';

$target_kembali = file_exists('../register.php') ? '../register.php' : '../register.xhtml';

if ($nama_lengkap === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
    header('Location: ' . $target_kembali . '?error=data_tidak_lengkap');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $target_kembali . '?error=email_tidak_valid');
    exit();
}

if (strlen($username) < 4 || strlen($username) > 30) {
    header('Location: ' . $target_kembali . '?error=username_panjang_salah');
    exit();
}

if (strlen($password) < 8) {
    header('Location: ' . $target_kembali . '?error=password_terlalu_pendek');
    exit();
}

if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    header('Location: ' . $target_kembali . '?error=password_tidak_memenuhi_syarat');
    exit();
}

if ($password !== $confirm_password) {
    header('Location: ' . $target_kembali . '?error=password_tidak_cocok');
    exit();
}

if (!in_array($peran, array('konsumen', 'produsen'), true)) {
    $peran = 'konsumen';
}

if (!$conn) {
    header('Location: ' . $target_kembali . '?error=koneksi_gagal');
    exit();
}

$stmt_cek = mysqli_prepare($conn, "SELECT id, username, email FROM pengguna WHERE username = ? OR email = ? LIMIT 1");
if (!$stmt_cek) {
    header('Location: ' . $target_kembali . '?error=sistem_gagal');
    exit();
}

mysqli_stmt_bind_param($stmt_cek, "ss", $username, $email);
mysqli_stmt_execute($stmt_cek);
$hasil_cek = mysqli_stmt_get_result($stmt_cek);
$duplikat = mysqli_fetch_assoc($hasil_cek);
mysqli_stmt_close($stmt_cek);

if ($duplikat) {
    if (strtolower($duplikat['username']) === strtolower($username)) {
        header('Location: ' . $target_kembali . '?error=username_sudah_dipakai');
    } else {
        header('Location: ' . $target_kembali . '?error=email_sudah_dipakai');
    }
    exit();
}

$kata_sandi_hash = password_hash($password, PASSWORD_BCRYPT);
$foto_profil_default = 'default_avatar.png';
$status_verifikasi = 'menunggu';

$query_simpan = "INSERT INTO pengguna (username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, status_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt_simpan = mysqli_prepare($conn, $query_simpan);

if (!$stmt_simpan) {
    header('Location: ' . $target_kembali . '?error=sistem_gagal');
    exit();
}

mysqli_stmt_bind_param($stmt_simpan, "sssssss", $username, $email, $kata_sandi_hash, $nama_lengkap, $foto_profil_default, $peran, $status_verifikasi);

if (mysqli_stmt_execute($stmt_simpan)) {
    mysqli_stmt_close($stmt_simpan);
    $target_login = file_exists('../login.php') ? '../login.php' : '../login.xhtml';
    header('Location: ' . $target_login . '?pesan=registrasi_berhasil');
    exit();
} else {
    mysqli_stmt_close($stmt_simpan);
    header('Location: ' . $target_kembali . '?error=gagal_menyimpan');
    exit();
}
?>
