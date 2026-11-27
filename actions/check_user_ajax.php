<?php
header('Content-Type: application/json; charset=UTF-8');

if (file_exists('../includes/session.php')) {
    require_once '../includes/session.php';
} elseif (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$field = (isset($_GET['field']) && is_string($_GET['field'])) ? trim($_GET['field']) : '';
$value = (isset($_GET['value']) && is_string($_GET['value'])) ? trim($_GET['value']) : '';

if ($field === '' && isset($_POST['field']) && is_string($_POST['field'])) {
    $field = trim($_POST['field']);
}
if ($value === '' && isset($_POST['value']) && is_string($_POST['value'])) {
    $value = trim($_POST['value']);
}

if ($field !== 'username' && $field !== 'email') {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => "Parameter tidak valid. Field harus bernilai 'username' atau 'email'.",
        'data' => null
    ));
    exit();
}

if ($value === '') {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Nilai isian tidak boleh kosong.',
        'data' => null
    ));
    exit();
}

if (!defined('DB_NO_DIE')) {
    define('DB_NO_DIE', true);
}
$conn = null;
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

$current_user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

if ($conn && $conn instanceof mysqli) {
    if ($field === 'username') {
        if ($current_user_id > 0) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE username = ? AND id != ? LIMIT 1");
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE username = ? LIMIT 1");
        }
    } else {
        if ($current_user_id > 0) {
            $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE email = ? AND id != ? LIMIT 1");
        } else {
            $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE email = ? LIMIT 1");
        }
    }

    if ($stmt) {
        if ($current_user_id > 0) {
            mysqli_stmt_bind_param($stmt, "si", $value, $current_user_id);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $value);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $num_rows = mysqli_stmt_num_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($num_rows > 0) {
            echo json_encode(array(
                'status' => 'error',
                'message' => ($field === 'username' ? 'Username sudah digunakan, silakan pilih yang lain.' : 'Email sudah terdaftar, silakan gunakan email lain.'),
                'data' => array('available' => false)
            ));
            exit();
        } else {
            echo json_encode(array(
                'status' => 'success',
                'message' => ($field === 'username' ? 'Username tersedia.' : 'Email tersedia.'),
                'data' => array('available' => true)
            ));
            exit();
        }
    }
}

$reserved_usernames = array('admin', 'administrator', 'root', 'iwang_petra', 'superadmin');
$reserved_emails = array('admin@pangkalanpangan.id', 'iwangpetra@gmail.com', 'admin@mail.com');

$lower_value = strtolower($value);
$is_taken = false;

if ($field === 'username') {
    $is_taken = in_array($lower_value, $reserved_usernames, true);
} else {
    $is_taken = in_array($lower_value, $reserved_emails, true);
}

if ($is_taken) {
    echo json_encode(array(
        'status' => 'error',
        'message' => ($field === 'username' ? 'Username sudah digunakan, silakan pilih yang lain.' : 'Email sudah terdaftar, silakan gunakan email lain.'),
        'data' => array('available' => false)
    ));
} else {
    echo json_encode(array(
        'status' => 'success',
        'message' => ($field === 'username' ? 'Username tersedia.' : 'Email tersedia.'),
        'data' => array('available' => true)
    ));
}
exit();
