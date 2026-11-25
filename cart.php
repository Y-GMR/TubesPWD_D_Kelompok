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

// Inisialisasi keranjang belanja pada sesi jika belum ada

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';

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

// Handler Tambah Cepat via URL GET (misal: cart.php?action=add&id=7 atau &slug=pepperoni-slice) -agy
if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $target_id = 0;
    if (isset($_GET['id'])) {
        $target_id = (int) $_GET['id'];
    } elseif (isset($_GET['slug']) && isset($slug_map[$_GET['slug']])) {
        $target_id = $slug_map[$_GET['slug']];
    } elseif (isset($_GET['item']) && isset($slug_map[$_GET['item']])) {
        $target_id = $slug_map[$_GET['item']];
    }

    if ($target_id > 0) {
        $qty = isset($_GET['qty']) ? (int) $_GET['qty'] : 1;
        if ($qty < 1) $qty = 1;
        if (!isset($_SESSION['cart'][$target_id])) {
            $_SESSION['cart'][$target_id] = array('jumlah' => $qty);
        } else {
            $_SESSION['cart'][$target_id]['jumlah'] += $qty;
        }
        header("Location: cart.php?status=added");
        exit();
    }
}

// 4. Ambil Data Profil Pengguna & Verifikasi (isVerified?)
$data_pengguna = null;
$status_verifikasi = 'menunggu';
if ($conn) {
    $data_pengguna = ambilPenggunaById($conn, $user_id);
    if ($data_pengguna && isset($data_pengguna['status_verifikasi'])) {
        $status_verifikasi = $data_pengguna['status_verifikasi'];
    }
}

// 5. Hitung Kuota Klaim Harian Konsumen (Maksimal 5 porsi per hari)
$kuota_harian_maks = 5;
$sudah_diklaim_hari_ini = 0;
if ($conn) {
    $sql_kuota = "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_hari_ini
                  FROM transaksi t
                  INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                  WHERE t.konsumen_id = ?
                    AND DATE(t.dibuat_pada) = CURDATE()
                    AND t.status != 'dibatalkan'";
    $stmt_kuota = mysqli_prepare($conn, $sql_kuota);
    if ($stmt_kuota) {
        mysqli_stmt_bind_param($stmt_kuota, "i", $user_id);
        mysqli_stmt_execute($stmt_kuota);
        $res_kuota = mysqli_stmt_get_result($stmt_kuota);
        $row_kuota = mysqli_fetch_assoc($res_kuota);
        if ($row_kuota) {
            $sudah_diklaim_hari_ini = (int) $row_kuota['total_hari_ini'];
        }
        mysqli_stmt_close($stmt_kuota);
    }
}
$sisa_kuota_hari_ini = max(0, $kuota_harian_maks - $sudah_diklaim_hari_ini);

// 6. Muat Rincian Item dari Basis Data untuk Isi Keranjang
$daftar_item_keranjang = array();
$total_porsi_keranjang = 0;
$total_harga_keranjang = 0;
$stok_bermasalah = false;

if ($conn && !empty($_SESSION['cart'])) {
    $ids = array();
    foreach ($_SESSION['cart'] as $id_k => $v_k) {
        $id_int = (int) $id_k;
        if ($id_int > 0) $ids[] = $id_int;
    }

    if (!empty($ids)) {
        $in_clause = implode(',', array_fill(0, count($ids), '?'));
        $tipe_param = str_repeat('i', count($ids));

        $sql_detail = "SELECT m.id, m.nama_makanan, m.harga, m.stok, m.foto_makanan, m.alamat_ambil,
                              k.nama_kategori, p.id AS produsen_id, p.nama_lengkap AS nama_produsen
                       FROM makanan m
                       INNER JOIN kategori k ON m.kategori_id = k.id
                       INNER JOIN pengguna p ON m.produsen_id = p.id
                       WHERE m.id IN ($in_clause)";

        $stmt_detail = mysqli_prepare($conn, $sql_detail);
        if ($stmt_detail) {
            mysqli_stmt_bind_param($stmt_detail, $tipe_param, ...$ids);
            mysqli_stmt_execute($stmt_detail);
            $res_detail = mysqli_stmt_get_result($stmt_detail);

            while ($row = mysqli_fetch_assoc($res_detail)) {
                $m_id = (int) $row['id'];
                $jumlah = isset($_SESSION['cart'][$m_id]['jumlah']) ? (int) $_SESSION['cart'][$m_id]['jumlah'] : 1;
                $harga = (int) $row['harga'];
                $subtotal = $harga * $jumlah;
                $stok_db = (int) $row['stok'];

                if ($jumlah > $stok_db) {
                    $stok_bermasalah = true;
                }

                $daftar_item_keranjang[] = array(
                    'id'            => $m_id,
                    'nama_makanan'  => $row['nama_makanan'],
                    'nama_kategori' => $row['nama_kategori'],
                    'nama_produsen' => $row['nama_produsen'],
                    'alamat_ambil'  => $row['alamat_ambil'],
                    'foto_makanan'  => $row['foto_makanan'],
                    'harga'         => $harga,
                    'stok'          => $stok_db,
                    'jumlah'        => $jumlah,
                    'subtotal'      => $subtotal
                );

                $total_porsi_keranjang += $jumlah;
                $total_harga_keranjang += $subtotal;
            }
            mysqli_stmt_close($stmt_detail);
        }
    }
}
?>