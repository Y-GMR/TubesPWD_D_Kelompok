<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

$target_kembali = file_exists('../profile.php') ? '../profile.php' : '../profile.xhtml';

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

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ' . $target_kembali . '?error=email_tidak_valid');
    exit();
}

if (strlen($username) < 4 || strlen($username) > 30) {
    header('Location: ' . $target_kembali . '?error=username_panjang_salah');
    exit();
}

if (!$conn) {
    header('Location: ' . $target_kembali . '?error=koneksi_gagal');
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
if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
    $file_tmp  = $_FILES['foto_profil']['tmp_name'];
    $file_name = $_FILES['foto_profil']['name'];
    $file_size = $_FILES['foto_profil']['size'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    $ekstensi_diizinkan = array('jpg', 'jpeg', 'png');

    if (in_array($file_ext, $ekstensi_diizinkan, true) && $file_size <= 2097152) {
        $nama_file_acak = 'avatar_' . $user_id . '_' . time() . '.' . $file_ext;
        $target_direktori = '../assets/uploads/avatars/';

        if (!is_dir($target_direktori)) {
            mkdir($target_direktori, 0777, true);
        }

        if (move_uploaded_file($file_tmp, $target_direktori . $nama_file_acak)) {
            $foto_profil_baru = 'assets/uploads/avatars/' . $nama_file_acak;
        }
    }
}

$ada_ganti_password = false;
$kata_sandi_hash = '';
if ($password_baru !== '') {
    if (strlen($password_baru) < 8) {
        header('Location: ' . $target_kembali . '?error=password_terlalu_pendek');
        exit();
    }
    if (!preg_match('/[a-zA-Z]/', $password_baru) || !preg_match('/[0-9]/', $password_baru)) {
        header('Location: ' . $target_kembali . '?error=password_tidak_memenuhi_syarat');
        exit();
    }
    if ($password_baru !== $konfirmasi_sandi) {
        header('Location: ' . $target_kembali . '?error=password_tidak_cocok');
        exit();
    }
    $kata_sandi_hash = password_hash($password_baru, PASSWORD_BCRYPT);
    $ada_ganti_password = true;
}

if ($foto_profil_baru !== null && $ada_ganti_password) {
    $stmt_update = mysqli_prepare($conn, "UPDATE pengguna SET nama_lengkap = ?, username = ?, email = ?, kata_sandi_hash = ?, foto_profil = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "sssssi", $nama_lengkap, $username, $email, $kata_sandi_hash, $foto_profil_baru, $user_id);
} elseif ($foto_profil_baru !== null) {
    $stmt_update = mysqli_prepare($conn, "UPDATE pengguna SET nama_lengkap = ?, username = ?, email = ?, foto_profil = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "ssssi", $nama_lengkap, $username, $email, $foto_profil_baru, $user_id);
} elseif ($ada_ganti_password) {
    $stmt_update = mysqli_prepare($conn, "UPDATE pengguna SET nama_lengkap = ?, username = ?, email = ?, kata_sandi_hash = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "ssssi", $nama_lengkap, $username, $email, $kata_sandi_hash, $user_id);
} else {
    $stmt_update = mysqli_prepare($conn, "UPDATE pengguna SET nama_lengkap = ?, username = ?, email = ? WHERE id = ?");
    mysqli_stmt_bind_param($stmt_update, "sssi", $nama_lengkap, $username, $email, $user_id);
}

if (!$stmt_update) {
    header('Location: ' . $target_kembali . '?error=sistem_gagal');
    exit();
}

if (mysqli_stmt_execute($stmt_update)) {
    mysqli_stmt_close($stmt_update);

    $_SESSION['full_name'] = $nama_lengkap;
    $_SESSION['username']  = $username;
    if ($foto_profil_baru !== null) {
        $_SESSION['profile_image'] = $foto_profil_baru;
    }

    header('Location: ' . $target_kembali . '?pesan=profil_berhasil_diperbarui');
    exit();
} else {
    mysqli_stmt_close($stmt_update);
    header('Location: ' . $target_kembali . '?error=gagal_memperbarui');
    exit();
}
?>
