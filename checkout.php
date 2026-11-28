<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

// 1. Session Guard: Wajib Login sebagai Konsumen
require_role('konsumen');

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';

// 2. Validasi Akun Pengguna & Status Verifikasi (isVerified?)
$data_pengguna = ambilPenggunaById($conn, $user_id);
if (!$data_pengguna) {
    hapusSesiPengguna();
    header("Location: login.php?error=login_required");
    exit();
}
sinkronSesiPengguna($data_pengguna);
if ($data_pengguna['peran'] !== 'konsumen') {
    require_role('konsumen');
}
$user_role = $data_pengguna['peran'];

$status_verifikasi = $data_pengguna['status_verifikasi'];
if ($status_verifikasi !== 'disetujui') {
    $_SESSION['flash_error'] = 'Akun Anda belum disetujui oleh admin. Klaim paket pangan hanya berlaku untuk akun yang telah diverifikasi kelayakannya (SKTM/KKS).';
    header("Location: cart.php?status=unverified");
    exit();
}

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Jika keranjang tetap kosong, arahkan kembali ke katalog
if (empty($_SESSION['cart'])) {
    $_SESSION['flash_error'] = 'Keranjang belanja Anda masih kosong. Silakan pilih menu makanan terlebih dahulu.';
    header("Location: katalog.php");
    exit();
}


$maks_porsi_per_keranjang = 5;
$maks_porsi_harian = 25;
$maks_checkout_harian = 5;

$sudah_porsi_hari_ini = 0;
$sudah_checkout_hari_ini = 0;

if ($conn) {
    // Total porsi hari ini
    $sql_kp = "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_porsi_hari_ini
               FROM transaksi t
               INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id
               WHERE t.konsumen_id = ?
                 AND DATE(t.dibuat_pada) = CURDATE()
                 AND t.status != 'dibatalkan'";
    $stmt_kp = mysqli_prepare($conn, $sql_kp);
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

    // Total transaksi hari ini
    $sql_kt = "SELECT COUNT(*) AS total_trx_hari_ini
               FROM transaksi
               WHERE konsumen_id = ?
                 AND DATE(dibuat_pada) = CURDATE()
                 AND status != 'dibatalkan'";
    $stmt_kt = mysqli_prepare($conn, $sql_kt);
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

if ($sudah_checkout_hari_ini >= $maks_checkout_harian) {
    $_SESSION['flash_error'] = "Batas checkout harian terlampaui. Anda telah mencapai batas maksimal {$maks_checkout_harian} kali checkout per hari.";
    header("Location: cart.php");
    exit();
}

// 4. Query Data Makanan Segar dari Basis Data
$ids = array();
foreach ($_SESSION['cart'] as $m_id => $val) {
    $id_int = (int) $m_id;
    if ($id_int > 0) $ids[] = $id_int;
}

$makanan_list = array();
$produsen_groups = array();
$total_porsi = 0;
$total_harga = 0;
$stok_habis = false;
$ada_kedaluwarsa = false;
$ditemukan_ids = array();

if ($conn && !empty($ids)) {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));

    $sql_items = "SELECT m.id, m.nama_makanan, m.harga, m.stok, m.foto_makanan, m.alamat_ambil,
                         m.latitude_ambil, m.longitude_ambil, m.kedaluwarsa_pada,
                         k.nama_kategori,
                         p.id AS produsen_id, p.nama_lengkap AS nama_produsen, p.foto_profil AS logo_produsen,
                         p.peran AS peran_produsen, p.status_verifikasi AS status_produsen
                  FROM makanan m
                  INNER JOIN kategori k ON m.kategori_id = k.id
                  INNER JOIN pengguna p ON m.produsen_id = p.id
                  WHERE m.id IN ($placeholders)";

    $stmt_items = mysqli_prepare($conn, $sql_items);
    if ($stmt_items) {
        mysqli_stmt_bind_param($stmt_items, $types, ...$ids);
        mysqli_stmt_execute($stmt_items);
        $res_items = mysqli_stmt_get_result($stmt_items);

        $ada_produsen_nonaktif = false;
        while ($row = mysqli_fetch_assoc($res_items)) {
            $item_id = (int) $row['id'];
            $ditemukan_ids[$item_id] = true;
            if (!isset($row['peran_produsen'], $row['status_produsen']) || $row['peran_produsen'] !== 'produsen' || $row['status_produsen'] !== 'disetujui') {
                $ada_produsen_nonaktif = true;
            }
            $qty = isset($_SESSION['cart'][$item_id]['jumlah']) ? (int) $_SESSION['cart'][$item_id]['jumlah'] : 1;
            $harga = (int) $row['harga'];
            $subtotal = $harga * $qty;
            $stok = (int) $row['stok'];
            $ked_raw = isset($row['kedaluwarsa_pada']) ? $row['kedaluwarsa_pada'] : null;

            if ($qty > $stok) {
                $stok_habis = true;
            }
            if ($ked_raw !== null && trim((string) $ked_raw) !== '' && strtotime($ked_raw) <= time()) {
                $ada_kedaluwarsa = true;
            }

            $prod_id = (int) $row['produsen_id'];
            if (!isset($produsen_groups[$prod_id])) {
                $produsen_groups[$prod_id] = array(
                    'produsen_id'       => $prod_id,
                    'nama_produsen'     => $row['nama_produsen'],
                    'logo_produsen'     => $row['logo_produsen'],
                    'alamat_ambil'      => $row['alamat_ambil'],
                    'latitude_ambil'    => $row['latitude_ambil'],
                    'longitude_ambil'   => $row['longitude_ambil'],
                    'earliest_expiry'   => $ked_raw,
                    'items'             => array(),
                    'subtotal_grup'     => 0,
                    'porsi_grup'        => 0
                );
            } elseif ($ked_raw !== null && trim((string) $ked_raw) !== '') {
                $cur_exp = $produsen_groups[$prod_id]['earliest_expiry'];
                if ($cur_exp === null || trim((string) $cur_exp) === '' || strtotime($ked_raw) < strtotime($cur_exp)) {
                    $produsen_groups[$prod_id]['earliest_expiry'] = $ked_raw;
                }
            }

            $row['jumlah'] = $qty;
            $row['subtotal'] = $subtotal;
            $produsen_groups[$prod_id]['items'][] = $row;
            $produsen_groups[$prod_id]['subtotal_grup'] += $subtotal;
            $produsen_groups[$prod_id]['porsi_grup'] += $qty;

            $total_porsi += $qty;
            $total_harga += $subtotal;
        }
        mysqli_stmt_close($stmt_items);
    }
}

foreach ($ids as $id_cek) {
    if (!isset($ditemukan_ids[$id_cek])) {
        unset($_SESSION['cart'][$id_cek]);
    }
}

if (empty($produsen_groups) || $total_porsi <= 0) {
    $_SESSION['flash_error'] = 'Item pada keranjang Anda sudah tidak tersedia. Silakan pilih menu lain.';
    header("Location: cart.php");
    exit();
}

if (!empty($ada_produsen_nonaktif)) {
    $_SESSION['flash_error'] = 'Sebagian menu berasal dari produsen yang belum terverifikasi. Silakan hapus item tersebut dari keranjang.';
    header("Location: cart.php");
    exit();
}

if ($ada_kedaluwarsa) {
    $_SESSION['flash_error'] = 'Sebagian item pada keranjang sudah melewati batas waktu pengambilan / kedaluwarsa. Silakan sesuaikan keranjang Anda.';
    header("Location: cart.php");
    exit();
}

if ($sudah_checkout_hari_ini + count($produsen_groups) > $maks_checkout_harian) {
    $_SESSION['flash_error'] = "Batas transaksi harian terlampaui (maksimal {$maks_checkout_harian} transaksi per hari; pesanan lintas " . count($produsen_groups) . " produsen melebihi sisa kuota checkout Anda).";
    header("Location: cart.php");
    exit();
}

// Validasi batas item porsi per keranjang
if ($total_porsi > $maks_porsi_per_keranjang) {
    $_SESSION['flash_error'] = "Total pesanan melebihi batas maksimal {$maks_porsi_per_keranjang} porsi per keranjang. Silakan sesuaikan jumlah porsi di keranjang.";
    header("Location: cart.php");
    exit();
}

// Validasi akumulasi kuota harian (maks 25 porsi)
if ($sudah_porsi_hari_ini + $total_porsi > $maks_porsi_harian) {
    $_SESSION['flash_error'] = "Total pesanan melebihi batas maksimal {$maks_porsi_harian} porsi per hari. Sisa kuota porsi Anda hari ini: {$sisa_porsi_harian} porsi.";
    header("Location: cart.php");
    exit();
}

if ($stok_habis) {
    $_SESSION['flash_error'] = "Stok untuk sebagian makanan tidak mencukupi di basis data. Silakan sesuaikan jumlah porsi di keranjang.";
    header("Location: cart.php");
    exit();
}

$page_title = 'Konfirmasi Klaim Pangan - Pangkalan Pangan';
$extra_css = array('checkout.css?v=7', 'components.css?v=10');
$extra_js = array('crud1_navbar.js?v=1');
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="checkout-main-section">
    <div class="checkout-container">
        <h1 class="checkout-page-title">Konfirmasi Klaim Pangan</h1>

        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; padding: 12px 16px; background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; border-radius: 6px;">
                <?php echo escapeHtml($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-content-grid">
            <!-- Kolom Kiri: Rincian Produsen & Item yang Diklaim -->
            <div class="checkout-left-col">
                <?php foreach ($produsen_groups as $prod_id => $group): ?>
                    <?php
                    $logo_src = crud1FormatFotoProdusenUrl($group['logo_produsen']);
                    $batas_ambil_grup = crud1FormatKedaluwarsaTampil($group['earliest_expiry']);
                    ?>
                    <div class="checkout-card food-detail-card" style="margin-bottom: 24px;">
                        <div class="checkout-merchant-header">
                            <div class="checkout-merchant-brand">
                                <img src="<?php echo escapeHtml($logo_src); ?>" alt="<?php echo escapeHtml($group['nama_produsen']); ?>" class="checkout-merchant-logo" />
                                <div>
                                    <h2 class="checkout-merchant-title"><?php echo escapeHtml($group['nama_produsen']); ?></h2>
                                    <p class="checkout-merchant-subtitle">Gerai Mitra Pangan Resmi &#8226; Pick-up Langsung</p>
                                </div>
                            </div>
                            <div class="food-deadline-wrap">
                                <div class="food-deadline-label">Batas Ambil:</div>
                                <div class="food-deadline-time"><?php echo escapeHtml($batas_ambil_grup); ?></div>
                            </div>
                        </div>

                        <hr class="checkout-hr" />

                        <div class="checkout-items-list">
                            <?php foreach ($group['items'] as $item): ?>
                                <?php
                                $foto_src = crud1FormatFotoUrl($item['foto_makanan']);
                                ?>
                                <div class="checkout-item-row">
                                    <div class="checkout-item-thumb">
                                        <img src="<?php echo escapeHtml($foto_src); ?>" alt="<?php echo escapeHtml($item['nama_makanan']); ?>" />
                                    </div>
                                    <div class="checkout-item-info">
                                        <h3 class="checkout-item-name"><?php echo escapeHtml($item['nama_makanan']); ?></h3>
                                        <p class="checkout-item-meta">Kategori: <?php echo escapeHtml($item['nama_kategori']); ?></p>
                                        <p class="checkout-item-price-tag">
                                            <?php echo formatRupiah($item['harga']); ?>
                                            <span class="checkout-item-qty-meta">x <?php echo (int) $item['jumlah']; ?> porsi</span>
                                        </p>
                                    </div>
                                    <div style="font-weight: bold; color: #2F855A; font-size: 15px;">
                                        <?php echo formatRupiah($item['subtotal']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <hr class="checkout-hr" />

                        <div class="food-detail-bottom">
                            <span class="food-claim-qty">Total: <?php echo (int) $group['porsi_grup']; ?> Porsi Pangan</span>
                            <span class="food-claim-price"><?php echo formatRupiah($group['subtotal_grup']); ?></span>
                        </div>
                    </div>

                    <!-- Kartu Lokasi Pengambilan Gerai Produsen -->
                    <div class="checkout-card posko-detail-card" style="margin-bottom: 24px;">
                        <h3 class="posko-card-title">Lokasi Pengambilan di Gerai Produsen</h3>
                        <div class="posko-name"><?php echo escapeHtml($group['nama_produsen']); ?></div>
                        <div class="posko-address"><?php echo escapeHtml($group['alamat_ambil']); ?></div>
                        <?php if ($group['latitude_ambil'] !== null && $group['longitude_ambil'] !== null): ?>
                            <div class="posko-meta">
                                Koordinat GPS: <span class="posko-meta-orange"><?php echo escapeHtml($group['latitude_ambil']); ?>, <?php echo escapeHtml($group['longitude_ambil']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="posko-alert-box">
                            Tunjukkan kode transaksi kepada kasir produsen saat pengambilan makanan. Harap datang sebelum batas waktu operasional berakhir.
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Kolom Kanan: Ringkasan Klaim & Tombol Finalisasi -->
            <div class="checkout-right-col">
                <div class="checkout-card summary-card">
                    <h3 class="summary-title">Ringkasan Klaim</h3>
                    <hr class="summary-divider" />

                    <ul class="summary-list">
                        <li class="summary-item">
                            <span class="summary-item-label">Penerima Manfaat</span>
                            <span class="summary-item-val val-teal"><?php echo escapeHtml($data_pengguna['nama_lengkap']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Peran Akun</span>
                            <span class="summary-item-val val-dark"><?php echo escapeHtml(ucfirst($user_role)); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Status Kelayakan SKTM</span>
                            <span class="summary-item-val val-green">Disetujui / Valid</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Porsi di Keranjang</span>
                            <span class="summary-item-val val-dark"><?php echo count($ditemukan_ids); ?> Menu (<?php echo (int) $total_porsi; ?> dari <?php echo (int) $maks_porsi_per_keranjang; ?> porsi maks)</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Total Porsi Hari Ini</span>
                            <span class="summary-item-val val-dark"><?php echo (int) ($sudah_porsi_hari_ini + $total_porsi); ?> dari <?php echo (int) $maks_porsi_harian; ?> porsi maks</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Checkout Hari Ini</span>
                            <span class="summary-item-val val-dark"><?php echo (int) $sudah_checkout_hari_ini; ?> dari <?php echo (int) $maks_checkout_harian; ?> kali</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Sisa Kuota Checkout</span>
                            <span class="summary-item-val val-amber"><?php echo (int) $sisa_checkout_harian; ?> kali transaksi lagi</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Total Pembayaran</span>
                            <span class="summary-item-val val-green"><?php echo formatRupiah($total_harga); ?></span>
                        </li>
                    </ul>

                    <hr class="summary-divider" />

                    <p class="summary-terms">Dengan mengonfirmasi klaim, Anda setuju untuk mengambil paket makanan di gerai mitra sebelum batas waktu berakhir hari ini.</p>

                    <!-- Formulir POST ke Backend Transaksi CRUD 2 -->
                    <form action="actions/crud2_transaction.php" method="post" style="margin: 0;">
                        <div>
                            <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                        </div>
                        <div>
                            <button type="submit" class="btn-confirm-claim" style="width: 100%; border: none; cursor: pointer; text-align: center;">Konfirmasi Klaim Sekarang</button>
                        </div>
                    </form>

                    <a href="cart.php" class="btn-cancel-checkout" style="display: block; text-align: center; text-decoration: none; margin-top: 10px;">Kembali ke Keranjang</a>

                    <p class="checkout-trouble-note">Mengalami kendala klaim? <a href="customer_service.php" class="checkout-trouble-link">Bantuan</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
