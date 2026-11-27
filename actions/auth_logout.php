<?php
require_once '../includes/session.php';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', array(
        'expires' => time() - 42000,
        'path' => $params["path"],
        'domain' => $params["domain"],
        'secure' => $params["secure"],
        'httponly' => $params["httponly"],
        'samesite' => 'Lax'
    ));
}

session_destroy();

header('Location: ../login.php?pesan=logout_berhasil');
exit();
?>
