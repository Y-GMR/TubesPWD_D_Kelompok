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
    $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk melakukan klaim pangan.';
    header('Location: ../login.php?error=login_required');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../cart.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : '';

// 1. Verifikasi peran pengguna (Konsumen atau Admin)
if ($user_role !== 'konsumen' && $user_role !== 'admin') {
    $_SESSION['flash_error'] = 'Hanya akun konsumen yang memiliki akses untuk mengklaim paket pangan.';
    header('Location: ../katalog.php');
    exit();
}

// 2. Validasi Token CSRF
$token_kiriman = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_kiriman)) {
    $_SESSION['flash_error'] = 'Permintaan tidak valid (Token CSRF kedaluwarsa atau tidak sah). Silakan coba lagi.';
    header('Location: ../checkout.php');
    exit();
}

if (!$conn) {
    $_SESSION['flash_error'] = 'Koneksi basis data terputus. Mohon hubungi administrator.';
    header('Location: ../checkout.php');
    exit();
}

// 3. Validasi Status Verifikasi Akun Pengguna (isVerified? pada Flowchart)
$data_pengguna = ambilPenggunaById($conn, $user_id);
if (!$data_pengguna) {
    $_SESSION['flash_error'] = 'Data profil pengguna tidak ditemukan.';
    header('Location: ../login.php');
    exit();
}

if ($data_pengguna['status_verifikasi'] !== 'disetujui') {
    $_SESSION['flash_error'] = 'Akun Anda belum disetujui oleh admin. Klaim paket pangan hanya berlaku untuk penerima manfaat terverifikasi (dokumen SKTM/KKS disetujui).';
    header('Location: ../katalog.php');
    exit();
}

// 4. Validasi Isi Keranjang & Batas Porsi per Keranjang (Maksimal 5 porsi per keranjang)
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['flash_error'] = 'Keranjang klaim pangan Anda masih kosong. Silakan pilih makanan terlebih dahulu.';
    header('Location: ../katalog.php');
    exit();
}

$total_porsi_baru = 0;
foreach ($_SESSION['cart'] as $item) {
    $qty = isset($item['jumlah']) ? (int) $item['jumlah'] : 0;
    if ($qty > 0) {
        $total_porsi_baru += $qty;
    }
}

if ($total_porsi_baru <= 0) {
    $_SESSION['flash_error'] = 'Jumlah porsi dalam keranjang tidak valid.';
    header('Location: ../cart.php');
    exit();
}

$maks_porsi_per_keranjang = 5;
if ($total_porsi_baru > $maks_porsi_per_keranjang) {
    $_SESSION['flash_error'] = "Batas porsi per keranjang terlampaui. Maksimal {$maks_porsi_per_keranjang} porsi per transaksi (Anda memilih {$total_porsi_baru} porsi).";
    header('Location: ../checkout.php');
    exit();
}

// 5. Validasi Batas Harian:
//    - Maksimal 5 kali checkout per hari
//    - Maksimal 25 porsi total per hari
$maks_checkout_harian = 5;
$maks_porsi_harian = 25;

// Cek total transaksi dan total porsi hari ini
$sql_cek_kuota = "SELECT COUNT(DISTINCT t.id) AS total_trx_hari_ini,
                         COALESCE(SUM(dt.jumlah), 0) AS total_porsi_hari_ini
                  FROM transaksi t
                  LEFT JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                  WHERE t.konsumen_id = ?
                    AND DATE(t.dibuat_pada) = CURDATE()
                    AND t.status != 'dibatalkan'";

$stmt_kuota = mysqli_prepare($conn, $sql_cek_kuota);
if (!$stmt_kuota) {
    $_SESSION['flash_error'] = 'Terjadi kesalahan sistem saat memeriksa kuota harian.';
    header('Location: ../checkout.php');
    exit();
}

mysqli_stmt_bind_param($stmt_kuota, "i", $user_id);
mysqli_stmt_execute($stmt_kuota);
$res_kuota = mysqli_stmt_get_result($stmt_kuota);
$row_kuota = mysqli_fetch_assoc($res_kuota);
mysqli_stmt_close($stmt_kuota);

$sudah_checkout = isset($row_kuota['total_trx_hari_ini']) ? (int) $row_kuota['total_trx_hari_ini'] : 0;
$sudah_porsi = isset($row_kuota['total_porsi_hari_ini']) ? (int) $row_kuota['total_porsi_hari_ini'] : 0;

if ($sudah_checkout >= $maks_checkout_harian) {
    $_SESSION['flash_error'] = "Batas transaksi harian terlampaui. Anda telah mencapai batas maksimal {$maks_checkout_harian} kali checkout per hari.";
    header('Location: ../checkout.php');
    exit();
}

if ($sudah_porsi + $total_porsi_baru > $maks_porsi_harian) {
    $sisa_kuota = max(0, $maks_porsi_harian - $sudah_porsi);
    $_SESSION['flash_error'] = "Batas kuota porsi harian terlampaui. Maksimal {$maks_porsi_harian} porsi per hari (Anda sudah mengklaim {$sudah_porsi} porsi, sisa kuota: {$sisa_kuota} porsi).";
    header('Location: ../checkout.php');
    exit();
}

// 6. Validasi Ketersediaan Stok Terkini dari Basis Data (Do NOT trust client)
$item_ids = array();
foreach ($_SESSION['cart'] as $makanan_id => $c_item) {
    $m_id = (int) $makanan_id;
    if ($m_id > 0 && isset($c_item['jumlah']) && (int) $c_item['jumlah'] > 0) {
        $item_ids[] = $m_id;
    }
}

if (empty($item_ids)) {
    $_SESSION['flash_error'] = 'Tidak ada item makanan yang valid untuk diproses.';
    header('Location: ../cart.php');
    exit();
}

// Ambil data segar dari tabel makanan untuk setiap item di keranjang
$makanan_db = array();
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
$types = str_repeat('i', count($item_ids));

$sql_makanan = "SELECT id, produsen_id, nama_makanan, harga, stok FROM makanan WHERE id IN ($placeholders)";
$stmt_makanan = mysqli_prepare($conn, $sql_makanan);
if (!$stmt_makanan) {
    $_SESSION['flash_error'] = 'Gagal memverifikasi stok inventaris.';
    header('Location: ../checkout.php');
    exit();
}

mysqli_stmt_bind_param($stmt_makanan, $types, ...$item_ids);
mysqli_stmt_execute($stmt_makanan);
$res_makanan = mysqli_stmt_get_result($stmt_makanan);
while ($row = mysqli_fetch_assoc($res_makanan)) {
    $makanan_db[(int) $row['id']] = $row;
}
mysqli_stmt_close($stmt_makanan);

// Periksa kelengkapan dan kecukupan stok
foreach ($_SESSION['cart'] as $makanan_id => $c_item) {
    $m_id = (int) $makanan_id;
    $qty_diminta = (int) $c_item['jumlah'];
    if ($qty_diminta <= 0) continue;

    if (!isset($makanan_db[$m_id])) {
        $_SESSION['flash_error'] = "Salah satu menu makanan tidak ditemukan di basis data.";
        header('Location: ../cart.php');
        exit();
    }

    $stok_tersedia = (int) $makanan_db[$m_id]['stok'];
    if ($stok_tersedia < $qty_diminta) {
        $nama_m = $makanan_db[$m_id]['nama_makanan'];
        $_SESSION['flash_error'] = "Stok untuk menu '{$nama_m}' tidak mencukupi (Tersedia: {$stok_tersedia}, Diminta: {$qty_diminta}).";
        header('Location: ../cart.php');
        exit();
    }
}

// 7. Pengelompokan Item per Produsen (Auto-split per produsen_id)
$pesanan_per_produsen = array();
foreach ($_SESSION['cart'] as $makanan_id => $c_item) {
    $m_id = (int) $makanan_id;
    $qty = (int) $c_item['jumlah'];
    if ($qty <= 0) continue;

    $item_info = $makanan_db[$m_id];
    $produsen_id = (int) $item_info['produsen_id'];

    if (!isset($pesanan_per_produsen[$produsen_id])) {
        $pesanan_per_produsen[$produsen_id] = array(
            'items' => array(),
            'total_harga' => 0
        );
    }

    $harga_satuan = (int) $item_info['harga'];
    $subtotal = $harga_satuan * $qty;

    $pesanan_per_produsen[$produsen_id]['items'][] = array(
        'makanan_id' => $m_id,
        'jumlah' => $qty,
        'harga_satuan' => $harga_satuan,
        'subtotal' => $subtotal
    );
    $pesanan_per_produsen[$produsen_id]['total_harga'] += $subtotal;
}

// 8. Eksekusi Transaksi Atomik MySQLi (ACID Transaction)
mysqli_begin_transaction($conn);

try {
    $kode_transaksi_list = array();

    $sql_insert_trx = "INSERT INTO transaksi (kode_transaksi, konsumen_id, produsen_id, total_harga, status, dibuat_pada)
                       VALUES (?, ?, ?, ?, 'menunggu', NOW())";
    $stmt_insert_trx = mysqli_prepare($conn, $sql_insert_trx);
    if (!$stmt_insert_trx) {
        throw new Exception("Gagal mempersiapkan kueri transaksi utama: " . mysqli_error($conn));
    }

    $sql_insert_detail = "INSERT INTO detail_transaksi (transaksi_id, makanan_id, jumlah, harga_satuan, subtotal)
                          VALUES (?, ?, ?, ?, ?)";
    $stmt_insert_detail = mysqli_prepare($conn, $sql_insert_detail);
    if (!$stmt_insert_detail) {
        throw new Exception("Gagal mempersiapkan kueri rincian transaksi: " . mysqli_error($conn));
    }

    $sql_potong_stok = "UPDATE makanan SET stok = stok - ? WHERE id = ? AND stok >= ?";
    $stmt_potong_stok = mysqli_prepare($conn, $sql_potong_stok);
    if (!$stmt_potong_stok) {
        throw new Exception("Gagal mempersiapkan kueri pembaruan stok: " . mysqli_error($conn));
    }

    foreach ($pesanan_per_produsen as $prod_id => $data_trx) {
        // Hasilkan Kode Transaksi Unik: PP-YYYYMMDD-XXXX
        $acak = strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $kode_trx = 'PP-' . date('Ymd') . '-' . $acak;

        $total_harga = (int) $data_trx['total_harga'];

        // Simpan baris induk transaksi
        mysqli_stmt_bind_param($stmt_insert_trx, "siii", $kode_trx, $user_id, $prod_id, $total_harga);
        if (!mysqli_stmt_execute($stmt_insert_trx)) {
            throw new Exception("Gagal menyimpan transaksi produsen #{$prod_id}: " . mysqli_stmt_error($stmt_insert_trx));
        }

        $transaksi_id = mysqli_insert_id($conn);
        if ($transaksi_id <= 0) {
            throw new Exception("ID transaksi tidak valid yang dihasilkan oleh basis data.");
        }

        $kode_transaksi_list[] = $kode_trx;

        // Simpan setiap item ke detail_transaksi dan kurangi stok di makanan
        foreach ($data_trx['items'] as $it) {
            $m_id = (int) $it['makanan_id'];
            $qty = (int) $it['jumlah'];
            $harga = (int) $it['harga_satuan'];
            $sub = (int) $it['subtotal'];

            mysqli_stmt_bind_param($stmt_insert_detail, "iiiii", $transaksi_id, $m_id, $qty, $harga, $sub);
            if (!mysqli_stmt_execute($stmt_insert_detail)) {
                throw new Exception("Gagal mencatat detail transaksi untuk makanan ID #{$m_id}: " . mysqli_stmt_error($stmt_insert_detail));
            }

            // Pemotongan stok dengan kondisi pengaman atomik stok >= qty
            mysqli_stmt_bind_param($stmt_potong_stok, "iii", $qty, $m_id, $qty);
            if (!mysqli_stmt_execute($stmt_potong_stok)) {
                throw new Exception("Gagal memperbarui stok makanan ID #{$m_id}: " . mysqli_stmt_error($stmt_potong_stok));
            }

            if (mysqli_stmt_affected_rows($stmt_potong_stok) !== 1) {
                throw new Exception("Stok makanan ID #{$m_id} berubah selama proses checkout. Transaksi dibatalkan untuk mencegah kelebihan pemesanan.");
            }
        }
    }

    mysqli_stmt_close($stmt_insert_trx);
    mysqli_stmt_close($stmt_insert_detail);
    mysqli_stmt_close($stmt_potong_stok);

    // Komit transaksi basis data jika seluruh tahapan berhasil tanpa kendala
    mysqli_commit($conn);

    // Kosongkan keranjang belanja pada sesi pengguna
    $_SESSION['cart'] = array();

    $pesan_kode = implode(', ', $kode_transaksi_list);
    $_SESSION['flash_success'] = "Klaim pangan berhasil diproses! Kode Transaksi: {$pesan_kode}. Silakan ambil pesanan Anda di gerai produsen sebelum batas waktu berakhir.";
    header('Location: ../transactions.php?status=success&code=' . urlencode($kode_transaksi_list[0]));
    exit();

} catch (Exception $e) {
    // Batalkan seluruh kueri jika terjadi kesalahan (Rollback)
    mysqli_rollback($conn);
    $_SESSION['flash_error'] = 'Transaksi klaim pangan gagal: ' . $e->getMessage();
    header('Location: ../checkout.php?error=tx_failed');
    exit();
}
