<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

// 1. Session Guard: Hanya konsumen yang dapat mengelola keranjang klaim
require_role('konsumen');

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';

// Inisialisasi keranjang belanja pada sesi jika belum ada
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Batas aturan bisnis:
// Maksimal 5 item/porsi per keranjang
// Maksimal 25 item/porsi total per hari (ekuivalen dengan 5 keranjang penuh x 5 porsi per hari)
// Maksimal 5 kali checkout per hari
$maks_porsi_per_keranjang = 5;
$maks_porsi_harian = 25;
$maks_checkout_harian = 5;

function crud2AmbilMakananValidUntukKeranjang($conn, $makanan_id) {
    if (!$conn || $makanan_id <= 0) {
        return null;
    }
    $sql = "SELECT m.id, m.nama_makanan, m.stok, m.kedaluwarsa_pada, p.status_verifikasi AS status_produsen "
         . "FROM makanan m "
         . "INNER JOIN pengguna p ON m.produsen_id = p.id "
         . "WHERE m.id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, "i", $makanan_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    return $row ? $row : null;
}

// Ambil Data Profil Pengguna & Verifikasi Kelayakan (isVerified?)
$data_pengguna = null;
$status_verifikasi = 'menunggu';
if ($conn) {
    $data_pengguna = ambilPenggunaById($conn, $user_id);
    if (!$data_pengguna) {
        hapusSesiPengguna();
        header("Location: login.php?error=login_required");
        exit();
    }
    if (isset($data_pengguna['status_verifikasi'])) {
        $status_verifikasi = $data_pengguna['status_verifikasi'];
    }
}

// 3. Handler Aksi Keranjang via POST (Tambah, Ubah Jumlah, Hapus Item, Kosongkan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && is_string($_POST['aksi'])) {
    $token_kiriman = (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) ? $_POST['csrf_token'] : '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_kiriman)) {
        $_SESSION['flash_error'] = 'Sesi keamanan formulir tidak sah (Token CSRF invalid). Silakan coba lagi.';
        header("Location: cart.php?status=csrf_invalid");
        exit();
    }

    $aksi_cart = $_POST['aksi'];
    $item_id = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

    if ($aksi_cart === 'tambah' && $item_id > 0) {
        if ($status_verifikasi !== 'disetujui') {
            $_SESSION['flash_error'] = 'Akun Anda belum diverifikasi admin sehingga belum dapat menambahkan makanan ke keranjang.';
            header("Location: cart.php?status=unverified");
            exit();
        }
        $tambah_qty = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
        if ($tambah_qty < 1) $tambah_qty = 1;

        $item_db = crud2AmbilMakananValidUntukKeranjang($conn, $item_id);
        if (!$item_db) {
            $_SESSION['flash_error'] = 'Menu makanan tidak ditemukan atau sudah dihapus.';
            header("Location: cart.php?status=unavailable");
            exit();
        }
        $stok_db = (int) $item_db['stok'];
        $kedaluwarsa_db = isset($item_db['kedaluwarsa_pada']) ? $item_db['kedaluwarsa_pada'] : null;
        $produsen_ok = (isset($item_db['status_produsen']) && $item_db['status_produsen'] === 'disetujui');
        if (!$produsen_ok || $stok_db <= 0 || ($kedaluwarsa_db !== null && trim((string) $kedaluwarsa_db) !== '' && strtotime($kedaluwarsa_db) <= time())) {
            $_SESSION['flash_error'] = 'Maaf, "' . $item_db['nama_makanan'] . '" sudah habis, melewati batas kedaluwarsa, atau tidak lagi tersedia.';
            header("Location: cart.php?status=unavailable");
            exit();
        }

        $total_sekarang = 0;
        foreach ($_SESSION['cart'] as $id_cek => $it_cek) {
            $total_sekarang += (int) $it_cek['jumlah'];
        }

        $qty_item_saat_ini = isset($_SESSION['cart'][$item_id]['jumlah']) ? (int) $_SESSION['cart'][$item_id]['jumlah'] : 0;
        if ($qty_item_saat_ini + $tambah_qty > $stok_db) {
            $_SESSION['flash_error'] = 'Stok "' . $item_db['nama_makanan'] . '" hanya tersisa ' . $stok_db . ' porsi.';
            header("Location: cart.php?status=stock_limit");
            exit();
        }

        if ($total_sekarang + $tambah_qty > $maks_porsi_per_keranjang) {
            $_SESSION['flash_error'] = "Batas porsi tercapai. Maksimal {$maks_porsi_per_keranjang} porsi dalam satu keranjang belanja.";
            header("Location: cart.php?status=limit_reached");
            exit();
        }

        if (!isset($_SESSION['cart'][$item_id])) {
            $_SESSION['cart'][$item_id] = array('jumlah' => $tambah_qty);
        } else {
            $_SESSION['cart'][$item_id]['jumlah'] += $tambah_qty;
        }
        header("Location: cart.php?status=added");
        exit();
    } elseif ($aksi_cart === 'ubah' && isset($_SESSION['cart'][$item_id])) {
        $jumlah_baru = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1;
        if ($jumlah_baru > 0) {
            if ($status_verifikasi !== 'disetujui') {
                $_SESSION['flash_error'] = 'Akun Anda belum diverifikasi admin sehingga belum dapat mengubah jumlah porsi klaim.';
                header("Location: cart.php?status=unverified");
                exit();
            }
            $item_db = crud2AmbilMakananValidUntukKeranjang($conn, $item_id);
            if (!$item_db) {
                unset($_SESSION['cart'][$item_id]);
                $_SESSION['flash_error'] = 'Menu makanan tidak lagi tersedia.';
                header("Location: cart.php?status=unavailable");
                exit();
            }
            $stok_db = (int) $item_db['stok'];
            $kedaluwarsa_db = isset($item_db['kedaluwarsa_pada']) ? $item_db['kedaluwarsa_pada'] : null;
            $produsen_ok = (isset($item_db['status_produsen']) && $item_db['status_produsen'] === 'disetujui');
            if (!$produsen_ok) {
                unset($_SESSION['cart'][$item_id]);
                $_SESSION['flash_error'] = 'Menu "' . $item_db['nama_makanan'] . '" tidak lagi tersedia.';
                header("Location: cart.php?status=unavailable");
                exit();
            }
            if ($kedaluwarsa_db !== null && trim((string) $kedaluwarsa_db) !== '' && strtotime($kedaluwarsa_db) <= time()) {
                $_SESSION['flash_error'] = 'Menu "' . $item_db['nama_makanan'] . '" sudah kedaluwarsa. Silakan hapus dari keranjang.';
                header("Location: cart.php?status=expired");
                exit();
            }
            if ($jumlah_baru > $stok_db) {
                $_SESSION['flash_error'] = 'Stok "' . $item_db['nama_makanan'] . '" hanya tersisa ' . $stok_db . ' porsi.';
                header("Location: cart.php?status=stock_limit");
                exit();
            }

            $total_lain = 0;
            foreach ($_SESSION['cart'] as $id_cek => $it_cek) {
                if ((int) $id_cek !== $item_id) {
                    $total_lain += (int) $it_cek['jumlah'];
                }
            }

            if ($total_lain + $jumlah_baru > $maks_porsi_per_keranjang) {
                $_SESSION['flash_error'] = "Batas porsi tercapai. Maksimal {$maks_porsi_per_keranjang} porsi dalam satu keranjang belanja.";
                header("Location: cart.php?status=limit_reached");
                exit();
            }

            $_SESSION['cart'][$item_id]['jumlah'] = $jumlah_baru;
        } else {
            unset($_SESSION['cart'][$item_id]);
        }
        header("Location: cart.php?status=updated");
        exit();
    } elseif ($aksi_cart === 'hapus' && isset($_SESSION['cart'][$item_id])) {
        unset($_SESSION['cart'][$item_id]);
        header("Location: cart.php?status=deleted");
        exit();
    } elseif ($aksi_cart === 'kosongkan') {
        $_SESSION['cart'] = array();
        header("Location: cart.php?status=cleared");
        exit();
    }
}

// 5. Hitung Batas Harian Konsumen dari Basis Data:
//    - Total porsi yang sudah diklaim hari ini (maks 25 porsi)
//    - Total transaksi/checkout yang sudah dilakukan hari ini (maks 5 kali)
$sudah_porsi_hari_ini = 0;
$sudah_checkout_hari_ini = 0;

if ($conn) {
    // Total porsi hari ini
    $sql_kuota_porsi = "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_porsi_hari_ini
                        FROM transaksi t
                        INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id
                        WHERE t.konsumen_id = ?
                          AND DATE(t.dibuat_pada) = CURDATE()
                          AND t.status != 'dibatalkan'";
    $stmt_kp = mysqli_prepare($conn, $sql_kuota_porsi);
    if ($stmt_kp) {
        mysqli_stmt_bind_param($stmt_kp, "i", $user_id);
        mysqli_stmt_execute($stmt_kp);
        $res_kp = mysqli_stmt_get_result($stmt_kp);
        $row_kp = mysqli_fetch_assoc($res_kp);
        if ($row_kp) {
            $sudah_porsi_hari_ini = (int) $row_kp['total_porsi_hari_ini'];
        }
        mysqli_stmt_close($stmt_kp);
    }

    // Total checkout/transaksi hari ini
    $sql_kuota_trx = "SELECT COUNT(*) AS total_trx_hari_ini
                      FROM transaksi
                      WHERE konsumen_id = ?
                        AND DATE(dibuat_pada) = CURDATE()
                        AND status != 'dibatalkan'";
    $stmt_kt = mysqli_prepare($conn, $sql_kuota_trx);
    if ($stmt_kt) {
        mysqli_stmt_bind_param($stmt_kt, "i", $user_id);
        mysqli_stmt_execute($stmt_kt);
        $res_kt = mysqli_stmt_get_result($stmt_kt);
        $row_kt = mysqli_fetch_assoc($res_kt);
        if ($row_kt) {
            $sudah_checkout_hari_ini = (int) $row_kt['total_trx_hari_ini'];
        }
        mysqli_stmt_close($stmt_kt);
    }
}

$sisa_porsi_harian = max(0, $maks_porsi_harian - $sudah_porsi_hari_ini);
$sisa_checkout_harian = max(0, $maks_checkout_harian - $sudah_checkout_hari_ini);

// 6. Muat Rincian Item dari Basis Data untuk Isi Keranjang
$daftar_item_keranjang = array();
$total_porsi_keranjang = 0;
$total_harga_keranjang = 0;
$stok_bermasalah = false;
$ada_item_kedaluwarsa = false;
$ada_produsen_belum_verif = false;
$produsen_di_keranjang = array();

if ($conn && !empty($_SESSION['cart'])) {
    $ids = array();
    foreach ($_SESSION['cart'] as $id_k => $v_k) {
        $id_int = (int) $id_k;
        if ($id_int > 0) $ids[] = $id_int;
    }

    if (!empty($ids)) {
        $in_clause = implode(',', array_fill(0, count($ids), '?'));
        $tipe_param = str_repeat('i', count($ids));

        $sql_detail = "SELECT m.id, m.nama_makanan, m.harga, m.stok, m.foto_makanan, m.alamat_ambil, m.kedaluwarsa_pada,
                              k.nama_kategori, p.id AS produsen_id, p.nama_lengkap AS nama_produsen,
                              p.status_verifikasi AS status_produsen
                       FROM makanan m
                       INNER JOIN kategori k ON m.kategori_id = k.id
                       INNER JOIN pengguna p ON m.produsen_id = p.id
                       WHERE m.id IN ($in_clause)";

        $stmt_detail = mysqli_prepare($conn, $sql_detail);
        if ($stmt_detail) {
            mysqli_stmt_bind_param($stmt_detail, $tipe_param, ...$ids);
            mysqli_stmt_execute($stmt_detail);
            $res_detail = mysqli_stmt_get_result($stmt_detail);
            $ditemukan_ids = array();

            while ($row = mysqli_fetch_assoc($res_detail)) {
                $m_id = (int) $row['id'];
                $ditemukan_ids[$m_id] = true;
                $jumlah = isset($_SESSION['cart'][$m_id]['jumlah']) ? (int) $_SESSION['cart'][$m_id]['jumlah'] : 1;
                $harga = (int) $row['harga'];
                $subtotal = $harga * $jumlah;
                $stok_db = (int) $row['stok'];
                $kedaluwarsa_raw = isset($row['kedaluwarsa_pada']) ? $row['kedaluwarsa_pada'] : null;
                $is_expired = ($kedaluwarsa_raw !== null && trim((string) $kedaluwarsa_raw) !== '' && strtotime($kedaluwarsa_raw) <= time());
                $is_prod_ok = (isset($row['status_produsen']) && $row['status_produsen'] === 'disetujui');

                if ($jumlah > $stok_db) {
                    $stok_bermasalah = true;
                }
                if ($is_expired) {
                    $ada_item_kedaluwarsa = true;
                }
                if (!$is_prod_ok) {
                    $ada_produsen_belum_verif = true;
                }

                $produsen_di_keranjang[(int) $row['produsen_id']] = true;

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
                    'subtotal'      => $subtotal,
                    'is_expired'    => $is_expired,
                    'is_prod_ok'    => $is_prod_ok
                );

                $total_porsi_keranjang += $jumlah;
                $total_harga_keranjang += $subtotal;
            }
            mysqli_stmt_close($stmt_detail);

            foreach ($ids as $id_cek_ghost) {
                if (!isset($ditemukan_ids[$id_cek_ghost])) {
                    unset($_SESSION['cart'][$id_cek_ghost]);
                }
            }
        }
    }
}

// Periksa kelayakan tombol checkout
$jumlah_trx_baru = count($produsen_di_keranjang);
$melebihi_porsi_keranjang = ($total_porsi_keranjang > $maks_porsi_per_keranjang);
$melebihi_porsi_harian = ($sudah_porsi_hari_ini + $total_porsi_keranjang > $maks_porsi_harian);
$melebihi_checkout_harian = ($sudah_checkout_hari_ini >= $maks_checkout_harian) || ($jumlah_trx_baru > 0 && ($sudah_checkout_hari_ini + $jumlah_trx_baru > $maks_checkout_harian));

$bisa_checkout = (!empty($daftar_item_keranjang) && !$stok_bermasalah && !$ada_item_kedaluwarsa && !$ada_produsen_belum_verif && !$melebihi_porsi_keranjang && !$melebihi_porsi_harian && !$melebihi_checkout_harian && $status_verifikasi === 'disetujui');

$page_title = 'Keranjang Klaim Pangan - Pangkalan Pangan';
$extra_css = array('checkout.css?v=6', 'components.css?v=6');
$extra_js = array('crud1_navbar.js?v=1');
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="checkout-main-section">
    <div class="checkout-container">
        <h1 class="checkout-page-title">Keranjang Klaim Pangan</h1>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; padding: 12px 16px; background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; border-radius: 6px;">
                <?php echo escapeHtml($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px;">
                <?php echo escapeHtml($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($status_verifikasi !== 'disetujui'): ?>
            <div class="posko-alert-box" style="margin-bottom: 24px; background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; padding: 14px 18px;">
                <strong>Perhatian Akun:</strong> Status verifikasi akun Anda saat ini adalah <em><?php echo escapeHtml(ucfirst($status_verifikasi)); ?></em>.
                Klaim paket pangan hanya dapat diselesaikan setelah dokumen kelayakan (SKTM/KKS) disetujui oleh admin.
            </div>
        <?php endif; ?>

        <?php if (empty($daftar_item_keranjang)): ?>
            <div class="checkout-card" style="text-align: center; padding: 48px 24px;">
                <img src="assets/images/logo_sprout.svg" alt="Pangkalan Pangan" style="width: 64px; height: 64px; margin-bottom: 16px;" />
                <h2 style="font-size: 20px; color: #2D3748; margin-bottom: 8px;">Keranjang Klaim Anda Sedang Kosong</h2>
                <p style="color: #718096; margin-bottom: 24px;">Jelajahi berbagai pilihan makanan bergizi yang tersedia untuk Anda klaim dari produsen mitra kami.</p>
                <a href="katalog.php" class="btn-confirm-claim" style="display: inline-block; text-decoration: none; max-width: 280px; margin: 0 auto;">Lihat Katalog Makanan</a>
            </div>
        <?php else: ?>

            <div class="checkout-content-grid">
                <div class="checkout-left-col">
                    <div class="checkout-card food-detail-card">
                        <div class="checkout-merchant-header">
                            <div>
                                <h2 class="checkout-merchant-title">Daftar Porsi Makanan Dipilih</h2>
                                <p class="checkout-merchant-subtitle">Metode: Ambil Sendiri di Gerai Produsen (Pick-up Only) &#8226; Maksimal 5 Porsi per Keranjang</p>
                            </div>
                            <form action="cart.php" method="post" style="margin: 0;">
                                <div>
                                    <input type="hidden" name="aksi" value="kosongkan" />
                                    <button type="submit" style="background: none; border: none; color: #E53E3E; font-size: 13px; cursor: pointer; text-decoration: underline;" onclick="return confirm('Kosongkan seluruh isi keranjang?');">Kosongkan Keranjang</button>
                                </div>
                            </form>
                        </div>

                        <hr class="checkout-hr" />

                        <div class="checkout-items-list">
                            <?php foreach ($daftar_item_keranjang as $item): ?>
                                <?php
                                $foto_src = crud1FormatFotoUrl($item['foto_makanan']);
                                $is_overstock = ($item['jumlah'] > $item['stok']);
                                $is_expired = !empty($item['is_expired']);
                                $is_prod_ok = !empty($item['is_prod_ok']);
                                ?>
                                <div class="checkout-item-row" style="align-items: center; padding: 14px 0; border-bottom: 1px solid #EDF2F7;">
                                    <div class="checkout-item-thumb">
                                        <img src="<?php echo escapeHtml($foto_src); ?>" alt="<?php echo escapeHtml($item['nama_makanan']); ?>" />
                                    </div>
                                    <div class="checkout-item-info" style="flex: 1; padding: 0 16px;">
                                        <h3 class="checkout-item-name" style="margin: 0 0 4px 0; font-size: 16px; color: #2D3748;">
                                            <?php echo escapeHtml($item['nama_makanan']); ?>
                                        </h3>
                                        <p class="checkout-item-meta" style="margin: 0 0 4px 0; font-size: 13px; color: #718096;">
                                            Kategori: <?php echo escapeHtml($item['nama_kategori']); ?> &#8226; Produsen: <strong><?php echo escapeHtml($item['nama_produsen']); ?></strong>
                                        </p>
                                        <p class="checkout-item-meta" style="margin: 0 0 6px 0; font-size: 12px; color: #A0AEC0;">
                                            Lokasi Pick-up: <?php echo escapeHtml($item['alamat_ambil']); ?>
                                        </p>
                                        <p class="checkout-item-price-tag" style="margin: 0; font-size: 14px; font-weight: bold; color: #2F855A;">
                                            <?php echo formatRupiah($item['harga']); ?> / porsi
                                            <?php if (!$is_prod_ok): ?>
                                                <span style="color: #E53E3E; font-size: 12px; font-weight: normal; margin-left: 8px;">(Produsen Belum Terverifikasi)</span>
                                            <?php elseif ($is_expired): ?>
                                                <span style="color: #E53E3E; font-size: 12px; font-weight: normal; margin-left: 8px;">(Sudah Kedaluwarsa!)</span>
                                            <?php elseif ($is_overstock): ?>
                                                <span style="color: #E53E3E; font-size: 12px; font-weight: normal; margin-left: 8px;">(Stok hanya tersisa: <?php echo (int) $item['stok']; ?>!)</span>
                                            <?php else: ?>
                                                <span style="color: #718096; font-size: 12px; font-weight: normal; margin-left: 8px;">(Sisa stok: <?php echo (int) $item['stok']; ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                                        <form action="cart.php" method="post" style="margin: 0;">
                                            <div class="checkout-stepper-wrap">
                                                <input type="hidden" name="aksi" value="ubah" />
                                                <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>" />

                                                <button type="submit" name="jumlah" value="<?php echo (int) ($item['jumlah'] - 1); ?>" class="btn-step" title="Kurangi 1">-</button>
                                                <span class="step-qty-val"><?php echo (int) $item['jumlah']; ?></span>
                                                <button type="submit" name="jumlah" value="<?php echo (int) ($item['jumlah'] + 1); ?>" class="btn-step" title="Tambah 1" <?php if ($status_verifikasi !== 'disetujui' || !$is_prod_ok || $is_expired || $item['jumlah'] >= $item['stok'] || $total_porsi_keranjang >= $maks_porsi_per_keranjang) echo 'disabled="disabled"'; ?>>+</button>
                                            </div>
                                        </form>

                                        <form action="cart.php" method="post" style="margin: 0;">
                                            <div>
                                                <input type="hidden" name="aksi" value="hapus" />
                                                <input type="hidden" name="item_id" value="<?php echo (int) $item['id']; ?>" />
                                                <button type="submit" style="background: none; border: none; color: #A0AEC0; font-size: 12px; cursor: pointer; text-decoration: underline;" title="Hapus dari keranjang">Hapus</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="checkout-hr" />

                        <div class="food-detail-bottom" style="display: flex; justify-content: space-between; align-items: center;">
                            <span class="food-claim-qty" style="font-weight: bold; color: #2D3748;">
                                Total Klaim: <?php echo (int) $total_porsi_keranjang; ?> / <?php echo (int) $maks_porsi_per_keranjang; ?> Porsi
                            </span>
                            <span class="food-claim-price" style="font-size: 18px; font-weight: bold; color: #2F855A;">
                                Total: <?php echo formatRupiah($total_harga_keranjang); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="checkout-right-col">
                    <div class="checkout-card summary-card">
                        <h3 class="summary-title">Ringkasan Validasi</h3>
                        <hr class="summary-divider" />

                        <ul class="summary-list">
                            <li class="summary-item">
                                <span class="summary-item-label">Penerima Manfaat</span>
                                <span class="summary-item-val val-teal"><?php echo escapeHtml(($data_pengguna && isset($data_pengguna['nama_lengkap'])) ? $data_pengguna['nama_lengkap'] : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : (isset($_SESSION['username']) ? $_SESSION['username'] : 'Pengguna'))); ?></span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Status SKTM</span>
                                <span class="summary-item-val <?php echo ($status_verifikasi === 'disetujui') ? 'val-green' : 'val-amber'; ?>">
                                    <?php echo escapeHtml(ucfirst($status_verifikasi)); ?>
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Porsi di Keranjang</span>
                                <span class="summary-item-val <?php echo ($melebihi_porsi_keranjang ? 'val-amber' : 'val-dark'); ?>">
                                    <?php echo (int) $total_porsi_keranjang; ?> dari <?php echo (int) $maks_porsi_per_keranjang; ?> porsi maks
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Total Porsi Hari Ini</span>
                                <span class="summary-item-val val-dark">
                                    <?php echo (int) $sudah_porsi_hari_ini; ?> dari <?php echo (int) $maks_porsi_harian; ?> porsi
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Sisa Kuota Porsi</span>
                                <span class="summary-item-val <?php echo ($melebihi_porsi_harian ? 'val-amber' : 'val-green'); ?>">
                                    <?php echo (int) $sisa_porsi_harian; ?> porsi lagi
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Checkout Hari Ini</span>
                                <span class="summary-item-val val-dark">
                                    <?php echo (int) $sudah_checkout_hari_ini; ?> dari <?php echo (int) $maks_checkout_harian; ?> kali
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Sisa Checkout</span>
                                <span class="summary-item-val <?php echo ($melebihi_checkout_harian ? 'val-amber' : 'val-green'); ?>">
                                    <?php echo (int) $sisa_checkout_harian; ?> kali lagi
                                </span>
                            </li>
                            <li class="summary-item">
                                <span class="summary-item-label">Total Tagihan</span>
                                <span class="summary-item-val val-green"><?php echo formatRupiah($total_harga_keranjang); ?></span>
                            </li>
                        </ul>

                        <hr class="summary-divider" />

                        <?php if ($melebihi_porsi_keranjang): ?>
                            <div class="alert alert-warning" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 4px;">
                                Total porsi di keranjang melebihi batas maksimal <?php echo (int) $maks_porsi_per_keranjang; ?> porsi per keranjang. Silakan kurangi porsi untuk melanjutkan.
                            </div>
                        <?php endif; ?>

                        <?php if ($melebihi_porsi_harian): ?>
                            <div class="alert alert-warning" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 4px;">
                                Total klaim porsi hari ini melebihi batas maksimal <?php echo (int) $maks_porsi_harian; ?> porsi per hari. Sisa kuota porsi Anda hari ini adalah <?php echo (int) $sisa_porsi_harian; ?> porsi.
                            </div>
                        <?php endif; ?>

                        <?php if ($melebihi_checkout_harian): ?>
                            <div class="alert alert-warning" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 4px;">
                                Anda telah mencapai batas maksimal <?php echo (int) $maks_checkout_harian; ?> kali checkout per hari. Silakan kembali lagi besok.
                            </div>
                        <?php endif; ?>

                        <?php if ($ada_produsen_belum_verif): ?>
                            <div class="alert alert-error" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                                Sebagian makanan berasal dari produsen yang belum terverifikasi admin. Silakan hapus item tersebut sebelum checkout.
                            </div>
                        <?php endif; ?>

                        <?php if ($ada_item_kedaluwarsa): ?>
                            <div class="alert alert-error" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                                Sebagian makanan di keranjang sudah melewati batas kedaluwarsa. Silakan hapus item tersebut sebelum checkout.
                            </div>
                        <?php endif; ?>

                        <?php if ($stok_bermasalah): ?>
                            <div class="alert alert-warning" style="font-size: 13px; margin-bottom: 16px; padding: 10px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 4px;">
                                Sebagian item melebihi stok yang tersedia saat ini di basis data produsen. Silakan sesuaikan jumlah porsi.
                            </div>
                        <?php endif; ?>

                        <?php if ($bisa_checkout): ?>
                            <a href="checkout.php" class="btn-confirm-claim" style="display: block; text-align: center; text-decoration: none; margin-bottom: 12px;">Lanjut ke Konfirmasi Klaim &#8594;</a>
                        <?php else: ?>
                            <button type="button" class="btn-confirm-claim" style="opacity: 0.5; cursor: not-allowed; width: 100%; margin-bottom: 12px;" disabled="disabled">
                                <?php
                                if ($status_verifikasi !== 'disetujui') {
                                    echo 'Akun Belum Diverifikasi';
                                } elseif ($melebihi_checkout_harian) {
                                    echo 'Batas 5 Checkout Harian Tercapai';
                                } elseif ($melebihi_porsi_keranjang) {
                                    echo 'Maksimal 5 Porsi per Keranjang';
                                } elseif ($melebihi_porsi_harian) {
                                    echo 'Batas 25 Porsi Harian Tercapai';
                                } elseif ($ada_produsen_belum_verif) {
                                    echo 'Produsen Belum Terverifikasi';
                                } elseif ($ada_item_kedaluwarsa) {
                                    echo 'Ada Makanan Kedaluwarsa';
                                } elseif ($stok_bermasalah) {
                                    echo 'Stok Tidak Cukup';
                                } else {
                                    echo 'Keranjang Kosong';
                                }
                                ?>
                            </button>
                        <?php endif; ?>

                        <a href="katalog.php" class="btn-cancel-checkout" style="display: block; text-align: center; text-decoration: none;">+ Tambah Menu Lain</a>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
