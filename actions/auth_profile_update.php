<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

$target_kembali = '../profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $target_kembali);
    exit();
}

$user_id          = (int) $_SESSION['user_id'];
$nama_lengkap     = isset($_POST['nama_lengkap']) ? bersihkanInput($_POST['nama_lengkap']) : '';
$username         = isset($_POST['username']) ? bersihkanInput($_POST['username']) : '';
$email            = isset($_POST['email']) ? bersihkanInput($_POST['email']) : '';
$password_baru    = isset($_POST['password_baru']) ? $_POST['password_baru'] : '';
$konfirmasi_sandi = isset($_POST['konfirmasi_sandi']) ? $_POST['konfirmasi_sandi'] : '';

if ($nama_lengkap === '' || $username === '' || $email === '') {
    header('Location: ' . $target_kembali . '?error=data_tidak_lengkap');
    exit();
}

$panjang_nama = function_exists('mb_strlen') ? mb_strlen($nama_lengkap, 'UTF-8') : strlen($nama_lengkap);
if ($panjang_nama < 2 || $panjang_nama > 100) {
    header('Location: ' . $target_kembali . '?error=nama_tidak_valid');
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

if (!preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username)) {
    header('Location: ' . $target_kembali . '?error=username_karakter_tidak_valid');
    exit();
}

if (!$conn) {
    header('Location: ' . $target_kembali . '?error=koneksi_gagal');
    exit();
}

$pengguna_lama = ambilPenggunaById($conn, $user_id);
if (!$pengguna_lama) {
    header('Location: ../login.php?error=login_required');
    exit();
}

$stmt_cek = mysqli_prepare($conn, "SELECT id, username, email FROM pengguna WHERE (username = ? OR email = ?) AND id != ? LIMIT 1");
if (!$stmt_cek) {
    header('Location: ' . $target_kembali . '?error=sistem_gagal');
    exit();
}

mysqli_stmt_bind_param($stmt_cek, "ssi", $username, $email, $user_id);
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

$foto_profil_baru = null;
if (isset($_FILES['foto_profil'])) {
    $upload_foto = prosesUploadFotoProfil($_FILES['foto_profil'], '../');
    if (!$upload_foto['ok']) {
        $peta_err_foto = array(
            'format_ditolak' => 'foto_format_ditolak',
            'ukuran_terlalu_besar' => 'foto_terlalu_besar',
            'mime_ditolak' => 'foto_mime_ditolak',
            'upload_gagal' => 'foto_upload_gagal'
        );
        $kode_err = isset($peta_err_foto[$upload_foto['error']]) ? $peta_err_foto[$upload_foto['error']] : 'foto_upload_gagal';
        header('Location: ' . $target_kembali . '?error=' . $kode_err);
        exit();
    }
    if ($upload_foto['uploaded']) {
        $foto_profil_baru = $upload_foto['path'];
    }
}

$dokumen_baru = null;
if ($pengguna_lama['peran'] === 'konsumen' && isset($_FILES['dokumen_kelayakan'])) {
    $upload_doc = prosesUploadDokumenKelayakan($_FILES['dokumen_kelayakan'], '../');
    if (!$upload_doc['ok']) {
        if ($foto_profil_baru !== null) {
            hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
        }
        $peta_err_doc = array(
            'format_ditolak' => 'dokumen_format_ditolak',
            'ukuran_terlalu_besar' => 'dokumen_terlalu_besar',
            'mime_ditolak' => 'dokumen_mime_ditolak',
            'upload_gagal' => 'dokumen_upload_gagal'
        );
        $kode_err = isset($peta_err_doc[$upload_doc['error']]) ? $peta_err_doc[$upload_doc['error']] : 'dokumen_upload_gagal';
        header('Location: ' . $target_kembali . '?error=' . $kode_err);
        exit();
    }
    if ($upload_doc['uploaded']) {
        $dokumen_baru = $upload_doc['path'];
    }
}

$ada_ganti_password = false;
$kata_sandi_hash = '';
if ($password_baru !== '') {
    if (strlen($password_baru) < 8) {
        if ($foto_profil_baru !== null) hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
        if ($dokumen_baru !== null) hapusBerkasAman($dokumen_baru, 'assets/uploads/documents/', '../');
        header('Location: ' . $target_kembali . '?error=password_terlalu_pendek');
        exit();
    }
    if (!preg_match('/[a-zA-Z]/', $password_baru) || !preg_match('/[0-9]/', $password_baru)) {
        if ($foto_profil_baru !== null) hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
        if ($dokumen_baru !== null) hapusBerkasAman($dokumen_baru, 'assets/uploads/documents/', '../');
        header('Location: ' . $target_kembali . '?error=password_tidak_memenuhi_syarat');
        exit();
    }
    if ($password_baru !== $konfirmasi_sandi) {
        if ($foto_profil_baru !== null) hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
        if ($dokumen_baru !== null) hapusBerkasAman($dokumen_baru, 'assets/uploads/documents/', '../');
        header('Location: ' . $target_kembali . '?error=password_tidak_cocok');
        exit();
    }
    $kata_sandi_hash = password_hash($password_baru, PASSWORD_BCRYPT);
    $ada_ganti_password = true;
}

$set_clauses = array("nama_lengkap = ?", "username = ?", "email = ?");
$bind_types = "sss";
$bind_params = array($nama_lengkap, $username, $email);

if ($ada_ganti_password) {
    $set_clauses[] = "kata_sandi_hash = ?";
    $bind_types .= "s";
    $bind_params[] = $kata_sandi_hash;
}
if ($foto_profil_baru !== null) {
    $set_clauses[] = "foto_profil = ?";
    $bind_types .= "s";
    $bind_params[] = $foto_profil_baru;
}
if ($dokumen_baru !== null) {
    $set_clauses[] = "dokumen_kelayakan = ?";
    $bind_types .= "s";
    $bind_params[] = $dokumen_baru;
    $set_clauses[] = "status_verifikasi = 'menunggu'";
    $set_clauses[] = "catatan_verifikasi = NULL";
}

$bind_types .= "i";
$bind_params[] = $user_id;

$sql_update = "UPDATE pengguna SET " . implode(", ", $set_clauses) . " WHERE id = ?";
$stmt_update = mysqli_prepare($conn, $sql_update);

if (!$stmt_update) {
    if ($foto_profil_baru !== null) hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
    if ($dokumen_baru !== null) hapusBerkasAman($dokumen_baru, 'assets/uploads/documents/', '../');
    header('Location: ' . $target_kembali . '?error=sistem_gagal');
    exit();
}

mysqli_stmt_bind_param($stmt_update, $bind_types, ...$bind_params);

if (mysqli_stmt_execute($stmt_update)) {
    mysqli_stmt_close($stmt_update);

    if ($foto_profil_baru !== null && !empty($pengguna_lama['foto_profil'])) {
        hapusBerkasAman($pengguna_lama['foto_profil'], 'assets/uploads/avatars/', '../');
    }
    if ($dokumen_baru !== null && !empty($pengguna_lama['dokumen_kelayakan'])) {
        hapusBerkasAman($pengguna_lama['dokumen_kelayakan'], 'assets/uploads/documents/', '../');
    }

    $_SESSION['full_name'] = $nama_lengkap;
    $_SESSION['username']  = $username;
    if ($foto_profil_baru !== null) {
        $_SESSION['profile_image'] = $foto_profil_baru;
    }

    header('Location: ' . $target_kembali . '?pesan=profil_berhasil_diperbarui');
    exit();
} else {
    mysqli_stmt_close($stmt_update);
    if ($foto_profil_baru !== null) hapusBerkasAman($foto_profil_baru, 'assets/uploads/avatars/', '../');
    if ($dokumen_baru !== null) hapusBerkasAman($dokumen_baru, 'assets/uploads/documents/', '../');
    header('Location: ' . $target_kembali . '?error=gagal_memperbarui');
    exit();
}
?>
