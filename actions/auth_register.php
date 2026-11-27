<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

$nama_lengkap     = isset($_POST['fullname']) ? bersihkanInput($_POST['fullname']) : '';
$username         = isset($_POST['username']) ? bersihkanInput($_POST['username']) : '';
$email            = isset($_POST['email']) ? bersihkanInput($_POST['email']) : '';
$password         = isset($_POST['password']) ? $_POST['password'] : '';
$confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
$peran            = isset($_POST['role']) ? bersihkanInput($_POST['role']) : 'konsumen';

if (!in_array($peran, array('konsumen', 'produsen'), true)) {
    $peran = 'konsumen';
}

$target_kembali = '../register.php';
$role_query = ($peran === 'produsen') ? '&role=donor' : '';

if ($nama_lengkap === '' || $username === '' || $email === '' || $password === '' || $confirm_password === '') {
    header('Location: ' . $target_kembali . '?error=data_tidak_lengkap' . $role_query);
    exit();
}

$panjang_nama = function_exists('mb_strlen') ? mb_strlen($nama_lengkap, 'UTF-8') : strlen($nama_lengkap);
if ($panjang_nama < 2 || $panjang_nama > 100) {
    header('Location: ' . $target_kembali . '?error=nama_tidak_valid' . $role_query);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $target_kembali . '?error=email_tidak_valid' . $role_query);
    exit();
}

if (strlen($username) < 4 || strlen($username) > 30) {
    header('Location: ' . $target_kembali . '?error=username_panjang_salah' . $role_query);
    exit();
}

if (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
    header('Location: ' . $target_kembali . '?error=username_karakter_tidak_valid' . $role_query);
    exit();
}

if (strlen($password) < 8) {
    header('Location: ' . $target_kembali . '?error=password_terlalu_pendek' . $role_query);
    exit();
}

if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
    header('Location: ' . $target_kembali . '?error=password_tidak_memenuhi_syarat' . $role_query);
    exit();
}

if ($password !== $confirm_password) {
    header('Location: ' . $target_kembali . '?error=password_tidak_cocok' . $role_query);
    exit();
}

if (!$conn) {
    header('Location: ' . $target_kembali . '?error=koneksi_gagal' . $role_query);
    exit();
}

$stmt_cek = mysqli_prepare($conn, "SELECT id, username, email FROM pengguna WHERE username = ? OR email = ? LIMIT 1");
if (!$stmt_cek) {
    header('Location: ' . $target_kembali . '?error=sistem_gagal' . $role_query);
    exit();
}

mysqli_stmt_bind_param($stmt_cek, "ss", $username, $email);
mysqli_stmt_execute($stmt_cek);
$hasil_cek = mysqli_stmt_get_result($stmt_cek);
$duplikat = mysqli_fetch_assoc($hasil_cek);
mysqli_stmt_close($stmt_cek);

if ($duplikat) {
    if (strtolower($duplikat['username']) === strtolower($username)) {
        header('Location: ' . $target_kembali . '?error=username_sudah_dipakai' . $role_query);
    } else {
        header('Location: ' . $target_kembali . '?error=email_sudah_dipakai' . $role_query);
    }
    exit();
}

$dokumen_kelayakan_db = null;
if ($peran === 'konsumen' && isset($_FILES['dokumen_kelayakan'])) {
    $upload_doc = prosesUploadDokumenKelayakan($_FILES['dokumen_kelayakan'], '../');
    if (!$upload_doc['ok']) {
        $peta_err_doc = array(
            'format_ditolak' => 'dokumen_format_ditolak',
            'ukuran_terlalu_besar' => 'dokumen_terlalu_besar',
            'mime_ditolak' => 'dokumen_mime_ditolak',
            'upload_gagal' => 'dokumen_upload_gagal'
        );
        $kode_err = isset($peta_err_doc[$upload_doc['error']]) ? $peta_err_doc[$upload_doc['error']] : 'dokumen_upload_gagal';
        header('Location: ' . $target_kembali . '?error=' . $kode_err . $role_query);
        exit();
    }
    if ($upload_doc['uploaded']) {
        $dokumen_kelayakan_db = $upload_doc['path'];
    }
}

$kata_sandi_hash = password_hash($password, PASSWORD_BCRYPT);
$foto_profil_default = 'default_avatar.png';
$status_verifikasi = 'menunggu';

$query_simpan = "INSERT INTO pengguna (username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, dokumen_kelayakan, status_verifikasi) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt_simpan = mysqli_prepare($conn, $query_simpan);

if (!$stmt_simpan) {
    if ($dokumen_kelayakan_db !== null) {
        hapusBerkasAman($dokumen_kelayakan_db, 'assets/uploads/documents/', '../');
    }
    header('Location: ' . $target_kembali . '?error=sistem_gagal' . $role_query);
    exit();
}

mysqli_stmt_bind_param($stmt_simpan, "ssssssss", $username, $email, $kata_sandi_hash, $nama_lengkap, $foto_profil_default, $peran, $dokumen_kelayakan_db, $status_verifikasi);

if (mysqli_stmt_execute($stmt_simpan)) {
    mysqli_stmt_close($stmt_simpan);
    header('Location: ../login.php?pesan=registrasi_berhasil');
    exit();
} else {
    mysqli_stmt_close($stmt_simpan);
    if ($dokumen_kelayakan_db !== null) {
        hapusBerkasAman($dokumen_kelayakan_db, 'assets/uploads/documents/', '../');
    }
    header('Location: ' . $target_kembali . '?error=gagal_menyimpan' . $role_query);
    exit();
}
?>
