<?php
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = null;
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

// 1. Session Guard: Pengguna wajib login untuk mengelola keranjang klaim
if (!function_exists('is_logged_in') || !is_logged_in()) {
    header("Location: login.php?error=login_required");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';

// Inisialisasi keranjang belanja pada sesi jika belum ada
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// 2. Pemetaan slug nama makanan ke database (jika dikirim dari tautan katalog/detail JS)
$slug_map = array(
    'donat-cokelat'   => 1,
    'donat-glaze'     => 2,
    'donat-oreo'      => 3,
    'fire-flosss'     => 4,
    'choco-bun'       => 5,
    'cheese-boat'     => 6,
    'pepperoni-slice' => 7,
    'cheese-slice'    => 8,
    'garlic-bread'    => 9,
    'ayam-crispy'     => 10,
    'ayam-original'   => 11,
    'perkedel'        => 12
);

// 3. Handler Aksi Keranjang (Tambah via GET/POST, Ubah Jumlah, Hapus Item, Kosongkan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {
    $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

    if ($_POST['aksi'] === 'tambah' && $item_id > 0) {
        $tambah_qty = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
        if ($tambah_qty < 1) $tambah_qty = 1;

        if (!isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id] = array('jumlah' => $tambah_qty);
        } else {
            $_SESSION['cart'][$item_id]['jumlah'] += $tambah_qty;
        }
        header("Location: cart.php?status=added");
        exit();
    } elseif ($_POST['aksi'] === 'ubah' && isset($_SESSION['cart'][$item_id])) {
        $jumlah_baru = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
        if ($jumlah_baru > 0) {
            $_SESSION['cart'][$item_id]['jumlah'] = $jumlah_baru;
        } else {
            unset($_SESSION['cart'][$item_id]);
        }
        header("Location: cart.php?status=updated");
        exit();
    } elseif ($_POST['aksi'] === 'hapus' && isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        header("Location: cart.php?status=deleted");
        exit();
    } elseif ($_POST['aksi'] === 'kosongkan') {
        $_SESSION['cart'] = array();
        header("Location: cart.php?status=cleared");
        exit();
    }
}
?>