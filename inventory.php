<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

require_role('produsen');

$produsen_id = (int) $_SESSION['user_id'];
$produsen = ambilPenggunaById($conn, $produsen_id);
$nama_gerai = ($produsen && trim((string) $produsen['nama_lengkap']) !== '')
    ? $produsen['nama_lengkap']
    : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Mitra Produsen');

$daftar_kategori = crud1AmbilDaftarKategori($conn);
$daftar_makanan = array();
$total_tersalurkan = 0;

if ($conn) {
    $sql_items = "SELECT m.id, m.kategori_id, m.nama_makanan, m.deskripsi, m.harga, m.stok, "
               . "m.foto_makanan, m.kedaluwarsa_pada, m.alamat_ambil, m.dibuat_pada, "
               . "k.nama_kategori, "
               . "(SELECT COUNT(*) FROM detail_transaksi dt WHERE dt.makanan_id = m.id) AS total_klaim_ref "
               . "FROM makanan m "
               . "INNER JOIN kategori k ON m.kategori_id = k.id "
               . "WHERE m.produsen_id = ? "
               . "ORDER BY m.dibuat_pada DESC";
    $stmt_items = mysqli_prepare($conn, $sql_items);
    if ($stmt_items) {
        mysqli_stmt_bind_param($stmt_items, "i", $produsen_id);
        mysqli_stmt_execute($stmt_items);
        $res_items = mysqli_stmt_get_result($stmt_items);
        while ($row = mysqli_fetch_assoc($res_items)) {
            $daftar_makanan[] = $row;
        }
        mysqli_stmt_close($stmt_items);
    }

    $sql_terjual = "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_tersalurkan "
                 . "FROM detail_transaksi dt "
                 . "INNER JOIN transaksi t ON dt.transaksi_id = t.id "
                 . "WHERE t.produsen_id = ? AND t.status IN ('siap_diambil', 'selesai')";
    $stmt_terjual = mysqli_prepare($conn, $sql_terjual);
    if ($stmt_terjual) {
        mysqli_stmt_bind_param($stmt_terjual, "i", $produsen_id);
        mysqli_stmt_execute($stmt_terjual);
        $res_terjual = mysqli_stmt_get_result($stmt_terjual);
        $row_terjual = mysqli_fetch_assoc($res_terjual);
        if ($row_terjual) {
            $total_tersalurkan = (int) $row_terjual['total_tersalurkan'];
        }
        mysqli_stmt_close($stmt_terjual);
    }
}

$total_siap_diambil = 0;
$jumlah_stok_menipis = 0;
$count_semua = count($daftar_makanan);
$count_tersedia = 0;
$count_menipis = 0;
$count_ditutup = 0;

foreach ($daftar_makanan as $item) {
    $stok_item = (int) $item['stok'];
    if ($stok_item > 0) {
        $total_siap_diambil += $stok_item;
        if ($stok_item <= 8) {
            $jumlah_stok_menipis++;
            $count_menipis++;
        } else {
            $count_tersedia++;
        }
    } else {
        $count_ditutup++;
    }
}

$map_pesan = array(
    'makanan_ditambah'   => 'Makanan baru berhasil ditambahkan ke inventaris Anda.',
    'makanan_diperbarui' => 'Detail makanan berhasil diperbarui.',
    'stok_diperbarui'    => 'Jumlah stok porsi berhasil diperbarui.',
    'donasi_ditutup'     => 'Donasi makanan berhasil ditutup (stok disetel menjadi 0).',
    'makanan_dihapus'    => 'Makanan berhasil dihapus dari inventaris.'
);

$map_error = array(
    'koneksi_gagal'              => 'Gagal terhubung ke basis data.',
    'sistem_gagal'               => 'Terjadi kesalahan internal saat memproses inventaris.',
    'aksi_tidak_valid'           => 'Permintaan aksi inventaris tidak dikenali.',
    'id_tidak_valid'             => 'ID makanan tidak valid.',
    'akses_ditolak'              => 'Akses ditolak: Anda hanya dapat mengelola makanan milik gerai Anda sendiri.',
    'stok_tidak_valid'           => 'Nilai stok harus berupa bilangan bulat antara 0 hingga 9999.',
    'makanan_memiliki_transaksi' => 'Produk tidak dapat dihapus karena tercatat dalam riwayat klaim transaksi. Gunakan tombol Tutup Donasi untuk menonaktifkan.',
    'gagal_simpan'               => 'Gagal menyimpan perubahan data inventaris.',
    'gagal_hapus'                => 'Gagal menghapus item makanan dari basis data.'
);

$flash = crud1AmbilFlashPesan($map_pesan, $map_error);

$page_title = 'Manajemen Inventaris Pangan - Pangkalan Pangan';
$extra_css = array('components.css?v=8', 'inventory.css?v=1');
$extra_js = array('crud1_navbar.js?v=1', 'crud1_inventory.js?v=2');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="inventory-page-wrap">
            <div class="inventory-container">

                <div class="inventory-top-bar">
                    <a href="katalog.php" class="inventory-back-link">&#8592; Kembali ke Katalog</a>
                    <h1 class="inventory-title">Inventaris Produk</h1>
                    <a href="add_food.php" class="btn-add-food-top">+ Donasi Makanan Baru</a>
                </div>

                <?php if (!$conn): ?>
                    <div class="alert alert-warning">Koneksi basis data belum aktif. Data inventaris tidak dapat dimuat.</div>
                <?php endif; ?>

                <?php if ($flash['error'] !== ''): ?>
                    <div class="alert alert-error"><?php echo escapeHtml($flash['error']); ?></div>
                <?php endif; ?>

                <?php if ($flash['sukses'] !== ''): ?>
                    <div class="alert alert-success"><?php echo escapeHtml($flash['sukses']); ?></div>
                <?php endif; ?>

                <div class="inventory-metrics-grid">
                    <div class="inventory-metric-card">
                        <span class="metric-accent-bar accent-teal"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">MAKANAN SIAP DIAMBIL</span>
                            <span class="metric-val val-teal" id="metricReadyVal"><?php echo escapeHtml((string) $total_siap_diambil); ?> Porsi</span>
                        </div>
                    </div>

                    <div class="inventory-metric-card">
                        <span class="metric-accent-bar accent-orange"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">STOK MENIPIS</span>
                            <span class="metric-val val-orange" id="metricLowVal"><?php echo escapeHtml((string) $jumlah_stok_menipis); ?> Menu</span>
                        </div>
                    </div>

                    <div class="inventory-metric-card">
                        <span class="metric-accent-bar accent-red"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">TOTAL PANGAN TERSALURKAN</span>
                            <span class="metric-val val-red"><?php echo escapeHtml((string) $total_tersalurkan); ?> Porsi</span>
                        </div>
                    </div>
                </div>

                <div class="inventory-controls-row">
                    <div class="inv-controls-left">
                        <input type="text" id="invSearchInput" class="inventory-search-input" />
                        <select id="invCategorySelect" class="inventory-category-select">
                            <option value="all">Semua Kategori</option>
                            <?php foreach ($daftar_kategori as $kat): ?>
                                <option value="<?php echo escapeHtml($kat['nama_kategori']); ?>"><?php echo escapeHtml($kat['nama_kategori']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="inv-controls-right">
                        <button type="button" class="inv-pill active inv-filter-all">Semua (<?php echo escapeHtml((string) $count_semua); ?>)</button>
                        <button type="button" class="inv-pill inv-filter-available">Tersedia (<?php echo escapeHtml((string) $count_tersedia); ?>)</button>
                        <button type="button" class="inv-pill inv-filter-low">Stok Menipis (<?php echo escapeHtml((string) $count_menipis); ?>)</button>
                        <button type="button" class="inv-pill inv-filter-closed">Ditutup (<?php echo escapeHtml((string) $count_ditutup); ?>)</button>
                    </div>
                </div>

                <div class="inventory-grid" id="inventoryGrid">
                    <?php if (empty($daftar_makanan)): ?>
                        <div class="inventory-empty-state" id="invEmptyState" style="display: block;">
                            Anda belum menambahkan makanan donasi ke inventaris. Klik "+ Donasi Makanan Baru" untuk mulai berbagi.
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_makanan as $index => $item): ?>
                            <?php
                            $item_id = (int) $item['id'];
                            $stok_int = (int) $item['stok'];
                            $harga_int = (int) $item['harga'];
                            $total_klaim_ref = (int) $item['total_klaim_ref'];
                            $banner_idx = ($index % 6) + 1;
                            $foto_src = crud1FormatFotoUrl($item['foto_makanan']);
                            $batas_tampil = crud1FormatKedaluwarsaTampil($item['kedaluwarsa_pada']);

                            if ($stok_int <= 0) {
                                $status_kartu = 'closed';
                            } elseif ($stok_int <= 8) {
                                $status_kartu = 'low';
                            } else {
                                $status_kartu = 'available';
                            }
                            $card_classes = 'inventory-card inv-status-' . $status_kartu . ($stok_int <= 0 ? ' is-closed' : '');
                            ?>
                            <div class="<?php echo escapeHtml($card_classes); ?>"
                                 id="inv-card-<?php echo escapeHtml((string) $item_id); ?>">
                                <div class="inv-card-banner bg-banner-<?php echo escapeHtml((string) $banner_idx); ?>">
                                    <img src="<?php echo escapeHtml($foto_src); ?>" alt="<?php echo escapeHtml($item['nama_makanan']); ?>" class="inv-banner-img" />
                                    <span class="inv-stock-badge<?php echo ($stok_int <= 8) ? ' warning' : ''; ?> stock-badge-val">Stok: <?php echo escapeHtml((string) $stok_int); ?></span>
                                    <?php if ($stok_int > 0 && $stok_int <= 8): ?>
                                        <span class="inv-low-stock-badge">Stok Menipis</span>
                                    <?php elseif ($stok_int <= 0): ?>
                                        <span class="inv-low-stock-badge">Donasi Ditutup</span>
                                    <?php endif; ?>
                                </div>
                                <div class="inv-card-body">
                                    <span class="inv-cat-value" style="display: none;"><?php echo escapeHtml($item['nama_kategori']); ?></span>
                                    <h2 class="inv-item-title"><?php echo escapeHtml($item['nama_makanan']); ?></h2>
                                    <p class="inv-item-meta">Kategori: <?php echo escapeHtml($item['nama_kategori']); ?> &#8226; <?php echo escapeHtml($nama_gerai); ?></p>
                                    <p class="inv-item-price"><?php echo escapeHtml(formatRupiah($harga_int)); ?></p>
                                    <p class="inv-item-limit">Batas Ambil: <?php echo escapeHtml($batas_tampil); ?></p>

                                    <div class="inv-card-actions">
                                        <button type="button"
                                                class="btn-inv-manage btn-open-stock-modal">Kelola Stok</button>
                                        <a href="add_food.php?id=<?php echo escapeHtml((string) $item_id); ?>" class="btn-inv-manage">Ubah Detail</a>
                                    </div>

                                    <div class="inv-card-actions" style="margin-top: 8px;">
                                        <?php if ($stok_int > 0): ?>
                                            <form action="actions/crud1_item.php" method="post" class="form-inv-close" style="flex: 1; display: flex;">
                                                <div style="display: flex; width: 100%;">
                                                    <input type="hidden" name="action" value="tutup_donasi" />
                                                    <input type="hidden" name="id" value="<?php echo escapeHtml((string) $item_id); ?>" />
                                                    <button type="submit" class="btn-inv-close" style="width: 100%;">Tutup Donasi</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <button type="button" class="btn-inv-close closed" disabled="disabled" style="flex: 1;">Sudah Ditutup</button>
                                        <?php endif; ?>

                                        <?php if ($total_klaim_ref > 0): ?>
                                            <button type="button"
                                                    class="btn-inv-close closed btn-inv-delete-locked"
                                                    title="Tidak dapat dihapus karena makanan sudah memiliki riwayat klaim"
                                                    style="flex: 1;">Terkunci (Klaim)</button>
                                        <?php else: ?>
                                            <form action="actions/crud1_item.php" method="post" class="form-inv-delete" style="flex: 1; display: flex;">
                                                <div style="display: flex; width: 100%;">
                                                    <input type="hidden" name="action" value="delete" />
                                                    <input type="hidden" name="id" value="<?php echo escapeHtml((string) $item_id); ?>" />
                                                    <button type="submit" class="btn-inv-close" style="width: 100%;">Hapus</button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="inventory-empty-state" id="invEmptyState">
                            Tidak ada makanan dalam inventaris yang sesuai dengan filter atau pencarian Anda.
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <div class="inv-modal-overlay" id="stockModalOverlay">
            <div class="inv-modal">
                <form action="actions/crud1_item.php" method="post" id="stockModalForm">
                    <div>
                        <input type="hidden" name="action" value="update_stok" />
                        <input type="hidden" name="id" id="modalItemIdInput" value="" />
                    </div>
                    <h2 class="inv-modal-title" id="modalItemTitle">Kelola Stok Makanan</h2>
                    <p class="inv-modal-subtitle">Perbarui jumlah porsi siap ambil untuk donasi makanan ini (setel 0 untuk menutup sementara).</p>
                    <div class="inv-modal-stepper">
                        <button type="button" class="btn-stepper" id="btnStepMinus">&#8722;</button>
                        <input type="text" name="stok" id="inputStockVal" class="inv-stepper-val" value="0" />
                        <button type="button" class="btn-stepper" id="btnStepPlus">&#43;</button>
                    </div>
                    <div class="inv-modal-actions">
                        <button type="button" class="btn-modal-cancel" id="btnCancelModal">Batal</button>
                        <button type="submit" class="btn-modal-save" id="btnSaveStock">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
