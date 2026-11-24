<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

$item_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($item_id <= 0) {
    header('Location: katalog.php?error=item_tidak_valid');
    exit();
}

if (!$conn) {
    header('Location: katalog.php?error=koneksi_gagal');
    exit();
}

$sql = "SELECT m.*, k.nama_kategori, p.nama_lengkap AS nama_produsen, p.foto_profil AS foto_produsen "
     . "FROM makanan m "
     . "INNER JOIN kategori k ON m.kategori_id = k.id "
     . "INNER JOIN pengguna p ON m.produsen_id = p.id "
     . "WHERE m.id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    header('Location: katalog.php?error=item_tidak_tersedia');
    exit();
}

mysqli_stmt_bind_param($stmt, "i", $item_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$item = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$item) {
    header('Location: katalog.php?error=item_tidak_tersedia');
    exit();
}

$stok_int = (int) $item['stok'];
$kedaluwarsa_raw = isset($item['kedaluwarsa_pada']) ? $item['kedaluwarsa_pada'] : null;
$sudah_kedaluwarsa = ($kedaluwarsa_raw !== null && trim((string) $kedaluwarsa_raw) !== '' && strtotime($kedaluwarsa_raw) <= time());

if ($stok_int <= 0 || $sudah_kedaluwarsa) {
    header('Location: katalog.php?error=item_tidak_tersedia');
    exit();
}

$foto_makanan_url = crud1FormatFotoUrl($item['foto_makanan']);
$foto_produsen_url = crud1FormatFotoProdusenUrl(isset($item['foto_produsen']) ? $item['foto_produsen'] : '');

$harga_int = (int) $item['harga'];
$batas_ambil_label = crud1FormatKedaluwarsaTampil($kedaluwarsa_raw);
$alamat_ambil = (isset($item['alamat_ambil']) && trim((string) $item['alamat_ambil']) !== '')
    ? trim((string) $item['alamat_ambil'])
    : 'Alamat gerai mitra';
$deskripsi_teks = (isset($item['deskripsi']) && trim((string) $item['deskripsi']) !== '')
    ? trim((string) $item['deskripsi'])
    : 'Tidak ada catatan tambahan untuk makanan ini.';

$is_login = is_logged_in();
$peran_aktif = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$status_verifikasi = '';
$catatan_verifikasi = '';

if ($is_login && $peran_aktif === 'konsumen') {
    $pengguna = ambilPenggunaById($conn, (int) $_SESSION['user_id']);
    if ($pengguna) {
        $status_verifikasi = $pengguna['status_verifikasi'];
        $catatan_verifikasi = isset($pengguna['catatan_verifikasi']) ? (string) $pengguna['catatan_verifikasi'] : '';
    }
}

$qty_di_keranjang = 0;
if (isset($_SESSION['cart'][$item_id]['jumlah'])) {
    $qty_di_keranjang = (int) $_SESSION['cart'][$item_id]['jumlah'];
}
$sisa_boleh_klaim = max(0, $stok_int - $qty_di_keranjang);

$page_title = $item['nama_makanan'] . ' - Rincian Makanan';
$body_attrs = 'data-auth="' . ($is_login ? 'true' : 'false') . '"';
$extra_css = array('components.css?v=6', 'catalog.css?v=6');
$extra_js = array('crud1_navbar.js?v=1');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="main-content" style="padding-top: 24px; padding-bottom: 60px;">
            <div class="back-link-wrap">
                <a href="katalog.php#katalog" class="back-link-btn">&#8592; Kembali ke Katalog Makanan</a>
            </div>

            <div class="food-modal-card" style="margin: 0 auto; transform: none;">
                <div class="food-modal-header">
                    <div class="food-modal-merchant">
                        <img src="<?php echo escapeHtml($foto_produsen_url); ?>" alt="<?php echo escapeHtml($item['nama_produsen']); ?>" class="food-modal-merchant-logo" />
                        <div>
                            <div class="food-modal-merchant-name"><?php echo escapeHtml($item['nama_produsen']); ?></div>
                            <div class="food-modal-merchant-sub">Gerai Resmi Donatur &#8226; <?php echo escapeHtml($alamat_ambil); ?></div>
                        </div>
                    </div>
                    <a href="katalog.php#katalog" class="food-modal-close-btn" title="Tutup">&#215;</a>
                </div>

                <div class="food-modal-img-wrap">
                    <img src="<?php echo escapeHtml($foto_makanan_url); ?>" alt="<?php echo escapeHtml($item['nama_makanan']); ?>" class="food-modal-img" />
                </div>

                <div class="food-modal-info-body">
                    <div class="food-modal-title-row">
                        <h1 class="food-modal-title"><?php echo escapeHtml($item['nama_makanan']); ?></h1>
                        <div class="food-modal-price"><?php echo escapeHtml(formatRupiah($harga_int)); ?></div>
                    </div>

                    <div class="food-modal-meta-row">
                        <span><?php echo escapeHtml($item['nama_kategori']); ?></span>
                        <span class="meta-dot">&#8226;</span>
                        <span>Stok Tersedia: <?php echo escapeHtml((string) $stok_int); ?> porsi</span>
                    </div>

                    <p style="font-size: 13.5px; color: #374151; line-height: 1.5; margin: 10px 0 14px 0;">
                        <?php echo escapeHtml($deskripsi_teks); ?>
                    </p>

                    <div class="food-modal-quick-info">
                        <div class="food-modal-info-col">
                            <span class="food-modal-info-label">Batas Pengambilan / Kedaluwarsa:</span>
                            <span class="food-modal-info-val amber"><?php echo escapeHtml($batas_ambil_label); ?></span>
                        </div>
                        <div class="food-modal-info-col">
                            <span class="food-modal-info-label">Lokasi Pengambilan:</span>
                            <span class="food-modal-info-val"><?php echo escapeHtml($alamat_ambil); ?></span>
                        </div>
                    </div>

                    <?php if ($item['latitude_ambil'] !== null && $item['longitude_ambil'] !== null): ?>
                        <p style="font-size: 12px; color: #6b7280; margin: 0 0 14px 0;">
                            Koordinat Titik Ambil: <?php echo escapeHtml((string) $item['latitude_ambil']); ?>, <?php echo escapeHtml((string) $item['longitude_ambil']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!$is_login): ?>
                        <a href="login.php?error=login_required" class="food-modal-claim-btn">Masuk Akun untuk Mengklaim</a>
                    <?php elseif ($peran_aktif !== 'konsumen'): ?>
                        <div class="alert alert-warning">
                            Fitur klaim makanan hanya dapat digunakan oleh akun Konsumen yang terverifikasi.
                        </div>
                    <?php elseif ($status_verifikasi === 'menunggu'): ?>
                        <div class="alert alert-warning">
                            Akun Anda berstatus Menunggu Verifikasi oleh Admin. Silakan tunggu persetujuan dokumen kelayakan sebelum melakukan klaim pangan.
                        </div>
                    <?php elseif ($status_verifikasi !== 'disetujui'): ?>
                        <div class="alert alert-error">
                            Status verifikasi akun Anda saat ini ditolak.<?php echo $catatan_verifikasi !== '' ? (' Catatan: ' . escapeHtml($catatan_verifikasi)) : ''; ?>
                        </div>
                    <?php elseif ($sisa_boleh_klaim <= 0): ?>
                        <div class="alert alert-warning">
                            Seluruh sisa stok makanan ini (<?php echo escapeHtml((string) $qty_di_keranjang); ?> porsi) sudah ada di keranjang Anda.
                        </div>
                        <a href="cart.php" class="food-modal-claim-btn">Lihat Keranjang Klaim</a>
                    <?php else: ?>
                        <?php if ($qty_di_keranjang > 0): ?>
                            <div class="alert alert-warning">
                                Anda sudah memiliki <?php echo escapeHtml((string) $qty_di_keranjang); ?> porsi item ini di keranjang. Maksimal tambahan: <?php echo escapeHtml((string) $sisa_boleh_klaim); ?> porsi.
                            </div>
                        <?php endif; ?>
                        <form action="cart.php" method="post">
                            <div class="form-group">
                                <input type="hidden" name="aksi" value="tambah" />
                                <input type="hidden" name="item_id" value="<?php echo escapeHtml((string) $item_id); ?>" />
                                <label for="inputClaimQty" class="form-label">Jumlah Porsi Diklaim (Maks. <?php echo escapeHtml((string) $sisa_boleh_klaim); ?>)</label>
                                <input type="number" id="inputClaimQty" name="jumlah" class="form-control" min="1" max="<?php echo escapeHtml((string) $sisa_boleh_klaim); ?>" value="1" required="required" />
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <button type="submit" class="food-modal-claim-btn">Tambah ke Keranjang</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <a href="katalog.php#katalog" class="food-modal-cancel-link">Kembali ke Katalog</a>
                </div>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
