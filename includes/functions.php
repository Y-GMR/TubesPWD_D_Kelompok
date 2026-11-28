<?php
function bersihkanInput($data) {
    if (is_string($data) || is_int($data) || is_float($data)) {
        return trim((string) $data);
    }
    return '';
}

function escapeHtml($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    $nilai = (int) $angka;
    if ($nilai === 0) {
        return 'Gratis';
    }
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

function ambilPenggunaById($conn, $pengguna_id) {
    if (!$conn) {
        return null;
    }
    $id_bersih = (int) $pengguna_id;
    $sql = "SELECT id, username, email, nama_lengkap, foto_profil, peran, dokumen_kelayakan, status_verifikasi, catatan_verifikasi, dibuat_pada "
         . "FROM pengguna WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_bersih);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $data_pengguna = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    return $data_pengguna ? $data_pengguna : null;
}

function simpanSesiPengguna($data_pengguna) {
    session_regenerate_id(true);
    unset($_SESSION['cart']);
    $_SESSION['user_id'] = (int) $data_pengguna['id'];
    $_SESSION['username'] = $data_pengguna['username'];
    $_SESSION['full_name'] = $data_pengguna['nama_lengkap'];
    $_SESSION['role'] = $data_pengguna['peran'];
    $_SESSION['profile_image'] = $data_pengguna['foto_profil'];
    $_SESSION['status_verifikasi'] = isset($data_pengguna['status_verifikasi']) ? $data_pengguna['status_verifikasi'] : 'menunggu';
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function sinkronSesiPengguna($data_pengguna) {
    if (!is_array($data_pengguna) || !isset($data_pengguna['id'])) {
        return;
    }
    $_SESSION['user_id'] = (int) $data_pengguna['id'];
    $_SESSION['username'] = isset($data_pengguna['username']) ? $data_pengguna['username'] : '';
    $_SESSION['full_name'] = isset($data_pengguna['nama_lengkap']) ? $data_pengguna['nama_lengkap'] : '';
    $_SESSION['role'] = isset($data_pengguna['peran']) ? $data_pengguna['peran'] : 'konsumen';
    $_SESSION['profile_image'] = isset($data_pengguna['foto_profil']) ? $data_pengguna['foto_profil'] : 'default_avatar.png';
    if (isset($data_pengguna['status_verifikasi'])) {
        $_SESSION['status_verifikasi'] = $data_pengguna['status_verifikasi'];
    }
}

function hapusSesiPengguna() {
    $_SESSION = array();

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', array(
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => isset($params['samesite']) ? $params['samesite'] : 'Lax'
        ));
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function prosesUploadBerkasPengguna($file, $ekstensi_diizinkan, $peta_mime_ke_ext, $direktori_relatif, $awalan_nama, $prefix_relatif = '../', $kembalikan_path_penuh = true) {
    if (!is_array($file) || !isset($file['error'])) {
        return array('ok' => true, 'uploaded' => false, 'path' => null, 'error' => '');
    }
    if (is_array($file['error'])) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    $kode_error = (int) $file['error'];
    if ($kode_error === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'uploaded' => false, 'path' => null, 'error' => '');
    }

    if ($kode_error === UPLOAD_ERR_INI_SIZE || $kode_error === UPLOAD_ERR_FORM_SIZE) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'ukuran_terlalu_besar');
    }

    if ($kode_error !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    $tmp_name = (isset($file['tmp_name']) && is_string($file['tmp_name'])) ? $file['tmp_name'] : '';
    $orig_name = (isset($file['name']) && is_string($file['name'])) ? $file['name'] : '';
    $file_size = (isset($file['size']) && !is_array($file['size'])) ? (int) $file['size'] : 0;

    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    if ($file_size <= 0 || $file_size > 2097152) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'ukuran_terlalu_besar');
    }

    $ext_asli = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    if (!in_array($ext_asli, $ekstensi_diizinkan, true)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'format_ditolak');
    }

    if (!function_exists('finfo_open') || !function_exists('finfo_file')) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_riil = $finfo ? finfo_file($finfo, $tmp_name) : false;
    $finfo = null;

    if ($mime_riil === false || !isset($peta_mime_ke_ext[$mime_riil])) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'mime_ditolak');
    }

    $ext_aman = $peta_mime_ke_ext[$mime_riil];
    $nama_file_unik = $awalan_nama . '_' . bin2hex(random_bytes(12)) . '.' . $ext_aman;
    $dir_fisik = $prefix_relatif . $direktori_relatif;

    if (!is_dir($dir_fisik)) {
        if (!mkdir($dir_fisik, 0755, true) && !is_dir($dir_fisik)) {
            return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
        }
    }

    if (!move_uploaded_file($tmp_name, $dir_fisik . $nama_file_unik)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    return array(
        'ok' => true,
        'uploaded' => true,
        'path' => $kembalikan_path_penuh ? ($direktori_relatif . $nama_file_unik) : $nama_file_unik,
        'error' => ''
    );
}

function prosesUploadFotoProfil($file, $prefix_relatif = '../') {
    return prosesUploadBerkasPengguna(
        $file,
        array('jpg', 'jpeg', 'png', 'webp'),
        array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'),
        'assets/uploads/avatars/',
        'avatar',
        $prefix_relatif,
        true
    );
}

function prosesUploadDokumenKelayakan($file, $prefix_relatif = '../') {
    return prosesUploadBerkasPengguna(
        $file,
        array('pdf', 'jpg', 'jpeg', 'png'),
        array('application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'),
        'assets/uploads/documents/',
        'doc',
        $prefix_relatif,
        false
    );
}

function hapusBerkasAman($path_relatif, $dir_diizinkan, $prefix_relatif = '../') {
    $raw = trim((string) $path_relatif);
    if ($raw === '' || strpos($raw, '..') !== false) {
        return false;
    }
    $kandidat = (strpos($raw, '/') === false) ? ($dir_diizinkan . $raw) : $raw;
    if (strncmp($kandidat, $dir_diizinkan, strlen($dir_diizinkan)) !== 0) {
        return false;
    }
    $base_dir = realpath($prefix_relatif . $dir_diizinkan);
    $target = realpath($prefix_relatif . $kandidat);
    if ($base_dir === false || $target === false) {
        return false;
    }
    $base_prefix = rtrim($base_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (strncmp($target, $base_prefix, strlen($base_prefix)) !== 0) {
        return false;
    }
    if (is_file($target)) {
        return @unlink($target);
    }
    return false;
}
?>
