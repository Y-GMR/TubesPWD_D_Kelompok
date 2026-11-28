<?php
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '604800');
    session_set_cookie_params(array(
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    session_start();
}

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function cariPathRoot($target_file) {
    if (file_exists($target_file)) {
        return $target_file;
    }
    if (file_exists('../' . $target_file)) {
        return '../' . $target_file;
    }
    if (file_exists('../../' . $target_file)) {
        return '../../' . $target_file;
    }
    return $target_file;
}

function urlBerandaPeran($peran) {
    if ($peran === 'admin') {
        return cariPathRoot('views/admin/dashboard.php');
    }
    if ($peran === 'produsen') {
        return cariPathRoot('producer_dashboard.php');
    }
    return cariPathRoot('katalog.php');
}

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $jalur_login = cariPathRoot('login.php');
        header('Location: ' . $jalur_login . '?error=login_required');
        exit();
    }
}

function require_role($peran_diizinkan) {
    require_login();

    $peran_pengguna = isset($_SESSION['role']) ? $_SESSION['role'] : '';
    $cocok = false;

    if (is_array($peran_diizinkan)) {
        $cocok = in_array($peran_pengguna, $peran_diizinkan, true);
    } else {
        $cocok = ($peran_pengguna === $peran_diizinkan);
    }

    if (!$cocok) {
        $_SESSION['flash_error'] = 'Anda tidak memiliki hak akses untuk membuka halaman tersebut.';
        $tujuan = urlBerandaPeran($peran_pengguna);
        header('Location: ' . $tujuan . '?error=unauthorized');
        exit();
    }
}

function redirect_if_logged_in() {
    if (!is_logged_in()) {
        return;
    }

    $peran_pengguna = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';
    $tujuan = urlBerandaPeran($peran_pengguna);

    header('Location: ' . $tujuan);
    exit();
}
?>
