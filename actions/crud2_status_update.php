<?php
if (file_exists('../includes/session.php')) {
    require_once '../includes/session.php';
} elseif (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = null;
if (file_exists('../config/database.php')) {
    require_once '../config/database.php';
} elseif (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

if (file_exists('../includes/functions.php')) {
    require_once '../includes/functions.php';
} elseif (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

if (!function_exists('is_logged_in') || !is_logged_in()) {
    $_SESSION['flash_error'] = 'Silakan login terlebih dahulu.';
    header('Location: ../login.php?error=login_required');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../transactions.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

$redirect_to = (isset($_POST['redirect_to']) && is_string($_POST['redirect_to'])) ? trim($_POST['redirect_to']) : '';
$target_error = ($user_role === 'produsen' && $redirect_to === 'producer_dashboard') ? '../producer_dashboard.php' : '../transactions.php';

// 1. Validasi Token CSRF
$token_kiriman = (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_kiriman)) {
    $_SESSION['flash_error'] = 'Sesi keamanan formulir tidak sah (CSRF token invalid).';
    header('Location: ' . $target_error);
    exit();
}

$transaksi_id = isset($_POST['transaksi_id']) ? (int) $_POST['transaksi_id'] : 0;
$aksi = (isset($_POST['aksi']) && is_string($_POST['aksi'])) ? trim($_POST['aksi']) : '';
$status_tujuan = (isset($_POST['status']) && is_string($_POST['status'])) ? trim($_POST['status']) : '';

if ($transaksi_id <= 0) {
    $_SESSION['flash_error'] = 'ID Transaksi tidak valid.';
    header('Location: ' . $target_error);
    exit();
}

if (!$conn) {
    $_SESSION['flash_error'] = 'Koneksi basis data terputus.';
    header('Location: ' . $target_error);
    exit();
}

$pengguna_aktif = ambilPenggunaById($conn, $user_id);
if (!$pengguna_aktif) {
    hapusSesiPengguna();
    header('Location: ../login.php?error=login_required');
    exit();
}
sinkronSesiPengguna($pengguna_aktif);
$user_role = isset($pengguna_aktif['peran']) ? $pengguna_aktif['peran'] : $user_role;
$target_error = ($user_role === 'produsen' && $redirect_to === 'producer_dashboard') ? '../producer_dashboard.php' : '../transactions.php';

// 2. Ambil Data Transaksi Saat Ini dari Basis Data
$sql_cek = "SELECT id, kode_transaksi, konsumen_id, produsen_id, total_harga, status, dibuat_pada
            FROM transaksi WHERE id = ? LIMIT 1";
$stmt_cek = mysqli_prepare($conn, $sql_cek);
if (!$stmt_cek) {
    $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat memeriksa data transaksi.';
    header('Location: ' . $target_error);
    exit();
}

mysqli_stmt_bind_param($stmt_cek, "i", $transaksi_id);
mysqli_stmt_execute($stmt_cek);
$res_cek = mysqli_stmt_get_result($stmt_cek);
$trx = mysqli_fetch_assoc($res_cek);
mysqli_stmt_close($stmt_cek);

if (!$trx) {
    $_SESSION['flash_error'] = 'Transaksi tidak ditemukan dalam sistem.';
    header('Location: ' . $target_error);
    exit();
}

$status_sekarang = $trx['status'];
$konsumen_id = (int) $trx['konsumen_id'];
$produsen_id = (int) $trx['produsen_id'];

// 3. Kasus A: Pembatalan oleh Konsumen (cancelOrder? pada Flowchart)
if ($aksi === 'batal' || $status_tujuan === 'dibatalkan') {
    // Konsumen hanya boleh membatalkan transaksinya sendiri saat status masih 'menunggu'
    $is_pemilik = ($user_role === 'konsumen' && $user_id === $konsumen_id);
    $is_admin = ($user_role === 'admin');

    if (!$is_pemilik && !$is_admin) {
        $_SESSION['flash_error'] = 'Anda tidak memiliki hak untuk membatalkan pesanan ini.';
        header('Location: ' . $target_error);
        exit();
    }

    if ($status_sekarang !== 'menunggu') {
        $_SESSION['flash_error'] = 'Pesanan tidak dapat dibatalkan karena status sudah diproses (' . htmlspecialchars($status_sekarang, ENT_QUOTES, 'UTF-8') . ').';
        header('Location: ' . $target_error);
        exit();
    }

    // Eksekusi Pembatalan dan Pengembalian Stok secara Atomik (ACID)
    mysqli_begin_transaction($conn);

    try {
        // Kunci status dengan transisi atomik 'menunggu' -> 'dibatalkan' agar tidak terjadi double-refund stok
        $sql_update = "UPDATE transaksi SET status = 'dibatalkan' WHERE id = ? AND status = 'menunggu'";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if (!$stmt_update) {
            throw new Exception("Gagal memperbarui status transaksi.");
        }
        mysqli_stmt_bind_param($stmt_update, "i", $transaksi_id);
        if (!mysqli_stmt_execute($stmt_update)) {
            throw new Exception("Gagal mengeksekusi pembatalan transaksi.");
        }
        $affected = mysqli_stmt_affected_rows($stmt_update);
        mysqli_stmt_close($stmt_update);

        if ($affected !== 1) {
            throw new Exception("Transaksi sudah diproses atau dibatalkan sebelumnya.");
        }

        // Ambil semua rincian makanan yang ada di transaksi ini
        $sql_items = "SELECT makanan_id, jumlah FROM detail_transaksi WHERE transaksi_id = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);
        if (!$stmt_items) {
            throw new Exception("Gagal membaca rincian pesanan untuk pemulihan stok.");
        }
        mysqli_stmt_bind_param($stmt_items, "i", $transaksi_id);
        if (!mysqli_stmt_execute($stmt_items)) {
            mysqli_stmt_close($stmt_items);
            throw new Exception("Gagal membaca rincian pesanan untuk pemulihan stok.");
        }
        $res_items = mysqli_stmt_get_result($stmt_items);

        $daftar_kembali = array();
        while ($row_item = mysqli_fetch_assoc($res_items)) {
            $daftar_kembali[] = $row_item;
        }
        mysqli_stmt_close($stmt_items);

        // Pulihkan stok masing-masing makanan
        $sql_restore = "UPDATE makanan SET stok = stok + ? WHERE id = ?";
        $stmt_restore = mysqli_prepare($conn, $sql_restore);
        if (!$stmt_restore) {
            throw new Exception("Gagal mempersiapkan pemulihan stok inventaris.");
        }

        foreach ($daftar_kembali as $item_batal) {
            $m_id = (int) $item_batal['makanan_id'];
            $qty = (int) $item_batal['jumlah'];
            mysqli_stmt_bind_param($stmt_restore, "ii", $qty, $m_id);
            if (!mysqli_stmt_execute($stmt_restore)) {
                throw new Exception("Gagal mengembalikan stok makanan ID #{$m_id}.");
            }
        }
        mysqli_stmt_close($stmt_restore);

        mysqli_commit($conn);

        if ($is_admin && !$is_pemilik) {
            $_SESSION['flash_success'] = 'Klaim pangan (' . $trx['kode_transaksi'] . ') berhasil dibatalkan oleh admin. Stok porsi dan kuota harian konsumen telah dikembalikan.';
        } else {
            $_SESSION['flash_success'] = 'Klaim pangan (' . $trx['kode_transaksi'] . ') berhasil dibatalkan. Kuota harian Anda telah dikembalikan.';
        }
        header('Location: ../transactions.php?status=cancelled');
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['flash_error'] = 'Gagal membatalkan transaksi: ' . $e->getMessage();
        header('Location: ../transactions.php?error=cancel_failed');
        exit();
    }
}

// 4. Kasus B: Pembaruan Status Operasional oleh Produsen atau Admin
// Status yang diperbolehkan: 'siap_diambil', 'selesai'
$status_valid = array('siap_diambil', 'selesai');
if (!in_array($status_tujuan, $status_valid, true)) {
    $_SESSION['flash_error'] = 'Nilai status operasional tidak valid.';
    header('Location: ' . $target_error);
    exit();
}

$is_produsen = ($user_role === 'produsen' && $user_id === $produsen_id);
$is_admin = ($user_role === 'admin');

if (!$is_produsen && !$is_admin) {
    $_SESSION['flash_error'] = 'Anda tidak memiliki wewenang untuk memperbarui status klaim pada gerai ini.';
    header('Location: ' . $target_error);
    exit();
}

if ($status_sekarang === 'dibatalkan' || $status_sekarang === 'selesai') {
    $_SESSION['flash_error'] = 'Status transaksi yang sudah selesai atau dibatalkan tidak dapat diubah kembali.';
    header('Location: ' . $target_error);
    exit();
}

if ($status_tujuan === 'siap_diambil' && $status_sekarang !== 'menunggu') {
    $_SESSION['flash_error'] = 'Hanya transaksi berstatus menunggu yang dapat ditandai siap diambil.';
    header('Location: ' . $target_error);
    exit();
}

if ($status_tujuan === 'selesai' && $status_sekarang !== 'menunggu' && $status_sekarang !== 'siap_diambil') {
    $_SESSION['flash_error'] = 'Status transaksi saat ini tidak dapat langsung diselesaikan.';
    header('Location: ' . $target_error);
    exit();
}

$sql_update_status = ($status_tujuan === 'siap_diambil')
    ? "UPDATE transaksi SET status = ? WHERE id = ? AND status = 'menunggu'"
    : "UPDATE transaksi SET status = ? WHERE id = ? AND status IN ('menunggu', 'siap_diambil')";

$stmt_update_status = mysqli_prepare($conn, $sql_update_status);
if (!$stmt_update_status) {
    $_SESSION['flash_error'] = 'Gagal mempersiapkan kueri pembaruan status.';
    header('Location: ' . $target_error);
    exit();
}

mysqli_stmt_bind_param($stmt_update_status, "si", $status_tujuan, $transaksi_id);
if (mysqli_stmt_execute($stmt_update_status)) {
    $affected_rows = mysqli_stmt_affected_rows($stmt_update_status);
    mysqli_stmt_close($stmt_update_status);

    if ($affected_rows !== 1) {
        $_SESSION['flash_error'] = 'Status transaksi telah berubah oleh proses lain. Silakan muat ulang halaman.';
        header('Location: ' . $target_error);
        exit();
    }

    $label_status = ($status_tujuan === 'siap_diambil') ? 'Siap Diambil' : 'Selesai';
    $_SESSION['flash_success'] = "Status transaksi {$trx['kode_transaksi']} berhasil diperbarui menjadi: {$label_status}.";

    if ($user_role === 'produsen' && $redirect_to === 'producer_dashboard') {
        header('Location: ../producer_dashboard.php?status=updated');
    } else {
        header('Location: ../transactions.php?status=updated&code=' . urlencode($trx['kode_transaksi']));
    }
    exit();
} else {
    mysqli_stmt_close($stmt_update_status);
    $_SESSION['flash_error'] = 'Gagal memperbarui status transaksi. Silakan coba kembali.';
    header('Location: ' . $target_error);
    exit();
}
