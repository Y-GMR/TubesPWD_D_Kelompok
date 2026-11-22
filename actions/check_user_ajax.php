<?php
header('Content-Type: application/json; charset=UTF-8');

$field = isset($_GET['field']) ? trim($_GET['field']) : '';
$value = isset($_GET['value']) ? trim($_GET['value']) : '';

if ($field === '' && isset($_POST['field'])) {
    $field = trim($_POST['field']);
}
if ($value === '' && isset($_POST['value'])) {
    $value = trim($_POST['value']);
}

if ($field !== 'username' && $field !== 'email') {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => "Parameter tidak valid. Field harus bernilai 'username' atau 'email'."
    ));
    exit();
}

if ($value === '') {
    http_response_code(400);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Nilai isian tidak boleh kosong.'
    ));
    exit();
}

$conn = null;
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

if ($conn && $conn instanceof mysqli) {
    if ($field === 'username') {
        $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE username = ? LIMIT 1");
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id FROM pengguna WHERE email = ? LIMIT 1");
    }

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $value);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $num_rows = mysqli_stmt_num_rows($stmt);
        mysqli_stmt_close($stmt);

        if ($num_rows > 0) {
            echo json_encode(array(
                'status' => 'error',
                'available' => false,
                'message' => ($field === 'username' ? 'Username sudah digunakan, silakan pilih yang lain.' : 'Email sudah terdaftar, silakan gunakan email lain.')
            ));
            exit();
        } else {
            echo json_encode(array(
                'status' => 'success',
                'available' => true,
                'message' => ($field === 'username' ? 'Username tersedia.' : 'Email tersedia.')
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
        'available' => false,
        'message' => ($field === 'username' ? 'Username sudah digunakan, silakan pilih yang lain.' : 'Email sudah terdaftar, silakan gunakan email lain.')
    ));
} else {
    echo json_encode(array(
        'status' => 'success',
        'available' => true,
        'message' => ($field === 'username' ? 'Username tersedia.' : 'Email tersedia.')
    ));
}
exit();
