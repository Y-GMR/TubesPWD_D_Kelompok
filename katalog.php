<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

$kata_kunci = isset($_GET['q']) ? bersihkanInput($_GET['q']) : '';
$kategori_id = isset($_GET['kategori_id']) ? (int) $_GET['kategori_id'] : 0;

$daftar_kategori = crud1AmbilDaftarKategori($conn);
$daftar_makanan = array();

if ($conn) {
    $sql_makanan = "SELECT m.id, m.produsen_id, m.kategori_id, m.nama_makanan, m.deskripsi, m.harga, m.stok, "
                 . "m.foto_makanan, m.kedaluwarsa_pada, m.alamat_ambil, m.latitude_ambil, m.longitude_ambil, "
                 . "k.nama_kategori, p.nama_lengkap AS nama_produsen, p.foto_profil AS foto_produsen "
                 . "FROM makanan m "
                 . "INNER JOIN kategori k ON m.kategori_id = k.id "
                 . "INNER JOIN pengguna p ON m.produsen_id = p.id "
                 . "WHERE m.stok > 0 AND (m.kedaluwarsa_pada IS NULL OR m.kedaluwarsa_pada > NOW())";

    $bind_types = "";
    $bind_params = array();

    if ($kata_kunci !== '') {
        $sql_makanan .= " AND (m.nama_makanan LIKE ? OR m.deskripsi LIKE ? OR m.alamat_ambil LIKE ? OR p.nama_lengkap LIKE ?)";
        $pola_cari = '%' . $kata_kunci . '%';
        $bind_types .= "ssss";
        $bind_params[] = $pola_cari;
        $bind_params[] = $pola_cari;
        $bind_params[] = $pola_cari;
        $bind_params[] = $pola_cari;
    }

    if ($kategori_id > 0) {
        $sql_makanan .= " AND m.kategori_id = ?";
        $bind_types .= "i";
        $bind_params[] = $kategori_id;
    }

    $sql_makanan .= " ORDER BY m.dibuat_pada DESC";

    $stmt_makanan = mysqli_prepare($conn, $sql_makanan);
    if ($stmt_makanan) {
        if ($bind_types !== '') {
            mysqli_stmt_bind_param($stmt_makanan, $bind_types, ...$bind_params);
        }
        mysqli_stmt_execute($stmt_makanan);
        $res_makanan = mysqli_stmt_get_result($stmt_makanan);
        while ($row_makanan = mysqli_fetch_assoc($res_makanan)) {
            $daftar_makanan[] = $row_makanan;
        }
        mysqli_stmt_close($stmt_makanan);
    }
}

$kelompok_produsen = array();
foreach ($daftar_makanan as $item) {
    $pid = (int) $item['produsen_id'];
    if (!isset($kelompok_produsen[$pid])) {
        $foto_prod = crud1FormatFotoProdusenUrl(isset($item['foto_produsen']) ? $item['foto_produsen'] : '');

        $alamat_pertama = (isset($item['alamat_ambil']) && trim((string) $item['alamat_ambil']) !== '')
            ? trim((string) $item['alamat_ambil'])
            : 'Lokasi gerai mitra';

        $kelompok_produsen[$pid] = array(
            'produsen_id'   => $pid,
            'nama_produsen' => $item['nama_produsen'],
            'foto_produsen' => $foto_prod,
            'alamat_ambil'  => $alamat_pertama,
            'total_stok'    => 0,
            'min_harga'     => null,
            'kategori_list' => array(),
            'items'         => array()
        );
    }

    $harga_item = (int) $item['harga'];
    $stok_item = (int) $item['stok'];
    $kelompok_produsen[$pid]['total_stok'] += $stok_item;

    if ($kelompok_produsen[$pid]['min_harga'] === null || $harga_item < $kelompok_produsen[$pid]['min_harga']) {
        $kelompok_produsen[$pid]['min_harga'] = $harga_item;
    }

    if (!in_array($item['nama_kategori'], $kelompok_produsen[$pid]['kategori_list'], true)) {
        $kelompok_produsen[$pid]['kategori_list'][] = $item['nama_kategori'];
    }

    $kelompok_produsen[$pid]['items'][] = $item;
}

$konsumen_terverifikasi = false;
if ($conn && is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'konsumen') {
    $pengguna_aktif = ambilPenggunaById($conn, (int) $_SESSION['user_id']);
    if ($pengguna_aktif && $pengguna_aktif['status_verifikasi'] === 'disetujui') {
        $konsumen_terverifikasi = true;
    }
}

$map_error = array(
    'item_tidak_valid'     => 'Parameter makanan yang Anda pilih tidak valid.',
    'item_tidak_tersedia'  => 'Makanan sudah habis, kedaluwarsa, atau tidak lagi tersedia.',
    'koneksi_gagal'        => 'Gagal terhubung ke basis data.'
);
$flash = crud1AmbilFlashPesan(array(), $map_error);

$page_title = 'Pangkalan Pangan - Katalog Makanan';
$body_attrs = 'data-auth="' . (is_logged_in() ? 'true' : 'false') . '"';
$extra_css = array('components.css?v=6', 'catalog.css?v=6');
$extra_js = array('crud1_navbar.js?v=1');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="hero-section">
            <div class="hero-left-content">
                <img src="assets/images/pangkalan_pangan_title.svg" alt="Pangkalan Pangan" class="hero-title-svg" />
                <p class="hero-subtitle">Menghubungkan pangan dengan mereka yang membutuhkan. Bersama, kita hadirkan lebih banyak senyum.</p>
                <div class="hero-cta-btn-wrap">
                    <a href="#katalog" class="btn-hero-claim">Mulai klaim pangan</a>
                </div>
            </div>
            <div class="hero-right-photo">
                <img src="assets/images/hero_volunteer_photo.png" alt="Relawan Berbagi Pangan" class="hero-volunteer-img" />
            </div>
        </div>

        <div class="catalog-section" id="katalog">

            <div class="catalog-mascot-block">
                <img src="assets/images/katalog_curved_text.svg" alt="Katalog" class="katalog-curved-img" />
                <img src="assets/images/database_mascot.svg" alt="Maskot Database Pangkalan Pangan" class="database-mascot-img" />
            </div>

            <?php if (!$conn): ?>
                <div class="alert alert-warning">
                    Koneksi basis data belum aktif. Data katalog belum dapat dimuat.
                </div>
            <?php endif; ?>

            <?php if ($flash['error'] !== ''): ?>
                <div class="alert alert-error"><?php echo escapeHtml($flash['error']); ?></div>
            <?php endif; ?>

            <div class="catalog-search-wrap">
                <form action="katalog.php#katalog" method="get" class="catalog-search-form">
                    <div style="display: flex; align-items: center; width: 100%;">
                        <img src="assets/images/icon_search.svg" alt="Ikon Cari" class="search-icon-img" />
                        <input type="text" id="inputSearch" name="q" class="catalog-search-input" placeholder="Cari makanan, resto, atau lokasi pengambilan" value="<?php echo escapeHtml($kata_kunci); ?>" />
                        <?php if ($kategori_id > 0): ?>
                            <input type="hidden" name="kategori_id" value="<?php echo escapeHtml((string) $kategori_id); ?>" />
                        <?php endif; ?>
                        <button type="submit" class="btn btn-sm btn-primary" style="margin-left: 12px; border-radius: 18px;">Cari</button>
                    </div>
                </form>
            </div>

            <div class="category-filter-wrap">
                <ul class="category-filter-list">
                    <?php
                    $url_semua = 'katalog.php' . ($kata_kunci !== '' ? ('?q=' . urlencode($kata_kunci)) : '') . '#katalog';
                    ?>
                    <li><a href="<?php echo escapeHtml($url_semua); ?>" class="category-pill<?php echo ($kategori_id === 0) ? ' active' : ''; ?>">Semua Makanan</a></li>
                    <?php foreach ($daftar_kategori as $kat): ?>
                        <?php
                        $kid = (int) $kat['id'];
                        $query_kat = '?kategori_id=' . $kid . ($kata_kunci !== '' ? ('&q=' . urlencode($kata_kunci)) : '');
                        $url_kat = 'katalog.php' . $query_kat . '#katalog';
                        ?>
                        <li>
                            <a href="<?php echo escapeHtml($url_kat); ?>" class="category-pill<?php echo ($kategori_id === $kid) ? ' active' : ''; ?>">
                                <?php echo escapeHtml($kat['nama_kategori']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="merchant-cards-grid">
                <?php if (empty($kelompok_produsen)): ?>
                    <div class="catalog-empty-state" id="catalogEmptyState">
                        <div class="empty-state-card">
                            <img src="assets/images/logo_sprout.svg" alt="Pangkalan Pangan" class="empty-state-icon" />
                            <h3 class="empty-state-title">Belum ada makanan tersedia yang sesuai</h3>
                            <p class="empty-state-desc">Pencarian atau kategori yang Anda pilih saat ini belum memiliki stok aktif yang belum kedaluwarsa.</p>
                            <a href="katalog.php#katalog" class="btn-reset-filter" id="btnResetFilter">Tampilkan Semua Makanan</a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($kelompok_produsen as $prod): ?>
                        <?php
                        $item_pertama = $prod['items'][0];
                        $item_pertama_id = (int) $item_pertama['id'];
                        $min_harga = (int) $prod['min_harga'];
                        $label_kategori = implode(', ', $prod['kategori_list']);
                        $chunks = array_chunk($prod['items'], 3);
                        ?>
                        <div class="merchant-main-card">
                            <div class="merchant-header-block">
                                <div class="merchant-thumb-wrap">
                                    <img src="<?php echo escapeHtml($prod['foto_produsen']); ?>" alt="<?php echo escapeHtml($prod['nama_produsen']); ?>" class="merchant-thumb-img" />
                                </div>
                                <div class="merchant-meta-block">
                                    <div class="merchant-heading-row">
                                        <h2 class="merchant-name-title"><?php echo escapeHtml($prod['nama_produsen']); ?></h2>
                                    </div>
                                    <div class="merchant-location-stock-row">
                                        <div class="location-meta-wrap">
                                            <img src="assets/images/icon_gps.svg" alt="Lokasi" class="pin-icon" />
                                            <span><?php echo escapeHtml($prod['alamat_ambil']); ?></span>
                                        </div>
                                        <span class="stock-tag-pill">Stok: <?php echo escapeHtml((string) $prod['total_stok']); ?></span>
                                    </div>
                                    <div class="merchant-price-category-row">
                                        <img src="assets/images/icon_price.svg" alt="Harga" class="price-tag-icon" />
                                        <span class="price-val-text"><?php echo escapeHtml(formatRupiah($min_harga)); ?></span>
                                        <span class="price-unit-text"><?php echo $min_harga === 0 ? '' : '/porsi'; ?></span>
                                        <span class="bullet-dot">&#8226;</span>
                                        <span class="category-label"><?php echo escapeHtml($label_kategori); ?></span>
                                    </div>
                                    <a href="item_detail.php?id=<?php echo escapeHtml((string) $item_pertama_id); ?>" class="btn-merchant-claim">Lihat &amp; Klaim Pangan</a>
                                </div>
                            </div>

                            <hr class="merchant-card-hr" />

                            <div class="today-menu-top-row">
                                <span class="today-menu-title">Menu tersedia (<?php echo escapeHtml((string) count($prod['items'])); ?> pilihan)</span>
                                <a href="item_detail.php?id=<?php echo escapeHtml((string) $item_pertama_id); ?>" class="today-menu-all-link">Rincian menu &gt;</a>
                            </div>

                            <?php foreach ($chunks as $row_idx => $row_items): ?>
                                <div class="mini-cards-row"<?php echo $row_idx > 0 ? ' style="margin-top: 12px;"' : ''; ?>>
                                    <?php foreach ($row_items as $m): ?>
                                        <?php
                                        $mid = (int) $m['id'];
                                        $mharga = (int) $m['harga'];
                                        $mstok = (int) $m['stok'];
                                        $mfoto = crud1FormatFotoUrl($m['foto_makanan']);
                                        $detail_url = 'item_detail.php?id=' . $mid;
                                        ?>
                                        <div class="mini-food-card">
                                            <a href="<?php echo escapeHtml($detail_url); ?>" class="mini-food-img-wrap">
                                                <img src="<?php echo escapeHtml($mfoto); ?>" alt="<?php echo escapeHtml($m['nama_makanan']); ?>" class="mini-food-img" />
                                            </a>
                                            <a href="<?php echo escapeHtml($detail_url); ?>" class="mini-food-title" title="<?php echo escapeHtml($m['nama_makanan']); ?>">
                                                <?php echo escapeHtml($m['nama_makanan']); ?>
                                            </a>
                                            <div class="mini-food-stock">Stok: <?php echo escapeHtml((string) $mstok); ?></div>
                                            <div class="mini-food-bottom-row">
                                                <span class="mini-food-price"><?php echo escapeHtml(formatRupiah($mharga)); ?></span>
                                                <?php if ($konsumen_terverifikasi): ?>
                                                    <form action="cart.php" method="post" style="display: inline; margin: 0; padding: 0;">
                                                        <div style="display: inline;">
                                                            <input type="hidden" name="aksi" value="tambah" />
                                                            <input type="hidden" name="item_id" value="<?php echo escapeHtml((string) $mid); ?>" />
                                                            <input type="hidden" name="jumlah" value="1" />
                                                            <button type="submit" class="btn-add-mini-item" title="Tambah ke Keranjang">
                                                                <img src="assets/images/button_add.svg" alt="Tambah" />
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="<?php echo escapeHtml($detail_url); ?>" class="btn-add-mini-item" title="Lihat Rincian Makanan">
                                                        <img src="assets/images/button_add.svg" alt="Rincian" />
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>

<?php
require_once 'includes/footer.php';
?>
