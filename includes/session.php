<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(array(
        'httponly' => true,
        'samesite' => 'Lax'
    ));
    session_start();
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
        header('HTTP/1.1 403 Forbidden');
        $jalur_login = cariPathRoot('login.php');
        header('Location: ' . $jalur_login . '?error=unauthorized');
        exit();
    }
}

function redirect_if_logged_in() {
    if (!is_logged_in()) {
        return;
    }

    $peran_pengguna = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';
    if ($peran_pengguna === 'admin') {
        $tujuan = cariPathRoot('views/admin/dashboard.php');
    } else {
        $tujuan = cariPathRoot('katalog.php');
    }

    header('Location: ' . $tujuan);
    exit();
}
?>
