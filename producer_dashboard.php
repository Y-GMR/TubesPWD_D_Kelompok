<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

require_role('produsen');

$produsen_id = (int) $_SESSION['user_id'];
$produsen = ambilPenggunaById($conn, $produsen_id);
if ($conn && !$produsen) {
    hapusSesiPengguna();
    header('Location: login.php?error=login_required');
    exit();
}
$status_verif_produsen = ($produsen && isset($produsen['status_verifikasi'])) ? $produsen['status_verifikasi'] : 'menunggu';
$catatan_verif_produsen = ($produsen && isset($produsen['catatan_verifikasi'])) ? trim((string) $produsen['catatan_verifikasi']) : '';
$produsen_terverifikasi = ($status_verif_produsen === 'disetujui');
$nama_gerai = ($produsen && trim((string) $produsen['nama_lengkap']) !== '')
    ? $produsen['nama_lengkap']
    : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Mitra Produsen');

$total_siap_ambil = 0;
$klaim_masuk = 0;
$total_tersalurkan = 0;
$daftar_transaksi = array();
$daftar_makanan = array();

if ($conn) {
    $stmt_stok = mysqli_prepare($conn, "SELECT COALESCE(SUM(stok), 0) AS total_stok FROM makanan WHERE produsen_id = ? AND stok > 0 AND (kedaluwarsa_pada IS NULL OR kedaluwarsa_pada > NOW())");
    if ($stmt_stok) {
        mysqli_stmt_bind_param($stmt_stok, "i", $produsen_id);
        mysqli_stmt_execute($stmt_stok);
        $res_s = mysqli_stmt_get_result($stmt_stok);
        if ($row_s = mysqli_fetch_assoc($res_s)) {
            $total_siap_ambil = (int) $row_s['total_stok'];
        }
        mysqli_stmt_close($stmt_stok);
    }

    $stmt_masuk = mysqli_prepare($conn, "SELECT COUNT(*) AS total_masuk FROM transaksi WHERE produsen_id = ? AND status IN ('menunggu', 'siap_diambil')");
    if ($stmt_masuk) {
        mysqli_stmt_bind_param($stmt_masuk, "i", $produsen_id);
        mysqli_stmt_execute($stmt_masuk);
        $res_m = mysqli_stmt_get_result($stmt_masuk);
        if ($row_m = mysqli_fetch_assoc($res_m)) {
            $klaim_masuk = (int) $row_m['total_masuk'];
        }
        mysqli_stmt_close($stmt_masuk);
    }

    $stmt_salur = mysqli_prepare($conn, "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_salur FROM detail_transaksi dt INNER JOIN transaksi t ON dt.transaksi_id = t.id WHERE t.produsen_id = ? AND t.status IN ('siap_diambil', 'selesai')");
    if ($stmt_salur) {
        mysqli_stmt_bind_param($stmt_salur, "i", $produsen_id);
        mysqli_stmt_execute($stmt_salur);
        $res_sal = mysqli_stmt_get_result($stmt_salur);
        if ($row_sal = mysqli_fetch_assoc($res_sal)) {
            $total_tersalurkan = (int) $row_sal['total_salur'];
        }
        mysqli_stmt_close($stmt_salur);
    }

    $sql_trx = "SELECT t.id, t.kode_transaksi, t.total_harga, t.status, t.dibuat_pada, "
             . "pk.nama_lengkap AS nama_konsumen, pk.status_verifikasi AS status_konsumen "
             . "FROM transaksi t "
             . "INNER JOIN pengguna pk ON t.konsumen_id = pk.id "
             . "WHERE t.produsen_id = ? "
             . "ORDER BY FIELD(t.status, 'menunggu', 'siap_diambil', 'selesai', 'dibatalkan'), t.dibuat_pada DESC "
             . "LIMIT 10";
    $stmt_trx = mysqli_prepare($conn, $sql_trx);
    if ($stmt_trx) {
        mysqli_stmt_bind_param($stmt_trx, "i", $produsen_id);
        mysqli_stmt_execute($stmt_trx);
        $res_trx = mysqli_stmt_get_result($stmt_trx);
        while ($row_t = mysqli_fetch_assoc($res_trx)) {
            $tid = (int) $row_t['id'];
            $items_label = array();
            $total_porsi_trx = 0;

            $stmt_dt = mysqli_prepare($conn, "SELECT m.nama_makanan, dt.jumlah FROM detail_transaksi dt INNER JOIN makanan m ON dt.makanan_id = m.id WHERE dt.transaksi_id = ?");
            if ($stmt_dt) {
                mysqli_stmt_bind_param($stmt_dt, "i", $tid);
                mysqli_stmt_execute($stmt_dt);
                $res_dt = mysqli_stmt_get_result($stmt_dt);
                while ($row_d = mysqli_fetch_assoc($res_dt)) {
                    $items_label[] = $row_d['nama_makanan'] . ' (' . (int) $row_d['jumlah'] . ')';
                    $total_porsi_trx += (int) $row_d['jumlah'];
                }
                mysqli_stmt_close($stmt_dt);
            }
            $row_t['menu_ringkas'] = !empty($items_label) ? implode(', ', $items_label) : '-';
            $row_t['total_porsi'] = $total_porsi_trx;
            $daftar_transaksi[] = $row_t;
        }
        mysqli_stmt_close($stmt_trx);
    }

    $sql_food = "SELECT m.id, m.nama_makanan, m.harga, m.stok, m.foto_makanan, m.kedaluwarsa_pada, k.nama_kategori "
              . "FROM makanan m INNER JOIN kategori k ON m.kategori_id = k.id "
              . "WHERE m.produsen_id = ? AND m.stok > 0 AND (m.kedaluwarsa_pada IS NULL OR m.kedaluwarsa_pada > NOW()) "
              . "ORDER BY m.dibuat_pada DESC LIMIT 8";
    $stmt_food = mysqli_prepare($conn, $sql_food);
    if ($stmt_food) {
        mysqli_stmt_bind_param($stmt_food, "i", $produsen_id);
        mysqli_stmt_execute($stmt_food);
        $res_f = mysqli_stmt_get_result($stmt_food);
        while ($row_f = mysqli_fetch_assoc($res_f)) {
            $daftar_makanan[] = $row_f;
        }
        mysqli_stmt_close($stmt_food);
    }
}

$flash_error = '';
$flash_success = '';
if (isset($_SESSION['flash_error'])) {
    $flash_error = trim((string) $_SESSION['flash_error']);
    unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
    $flash_success = trim((string) $_SESSION['flash_success']);
    unset($_SESSION['flash_success']);
}
if ($flash_error === '' && isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $flash_error = 'Anda tidak memiliki hak akses untuk membuka halaman tersebut.';
    } elseif ($_GET['error'] === 'belum_diverifikasi') {
        $flash_error = 'Akun Anda belum diverifikasi oleh admin. Anda belum dapat mengelola makanan.';
    }
}

$page_title = 'Dashboard Produsen - Pangkalan Pangan';
$extra_css = array('components.css?v=9', 'catalog.css?v=7');
$extra_js = array('crud1_navbar.js?v=1');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="main-content">

            <div class="producer-welcome-card">
                <div class="welcome-text-group">
                    <h1 class="welcome-title">Selamat Datang, <?php echo escapeHtml($nama_gerai); ?>!</h1>
                    <p class="welcome-subtitle">Kelola ketersediaan makanan berlebih, pantau klaim masuk dari konsumen, dan pastikan pangan tersalurkan tepat waktu.</p>
                </div>
                <div class="welcome-actions">
<?php if ($produsen_terverifikasi): ?>
                    <a href="add_food.php" class="btn btn-primary">+ Donasi Makanan Baru</a>
                    <a href="inventory.php" class="btn btn-secondary">Kelola Inventaris</a>
<?php else: ?>
                    <a href="profile.php" class="btn btn-secondary">Lihat Status Profil</a>
<?php endif; ?>
                </div>
            </div>

            <?php if (!$produsen_terverifikasi): ?>
                <?php if ($status_verif_produsen === 'ditolak'): ?>
                    <div class="alert alert-error">
                        Akun produsen Anda saat ini berstatus <strong>Ditolak</strong><?php echo $catatan_verif_produsen !== '' ? (' (' . escapeHtml($catatan_verif_produsen) . ')') : ''; ?>. Anda belum dapat menambahkan atau mengelola donasi makanan.
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        Akun produsen Anda sedang menunggu verifikasi admin. Setelah disetujui, Anda dapat menambahkan dan mengelola donasi makanan.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($flash_error !== ''): ?>
                <div class="alert alert-error"><?php echo escapeHtml($flash_error); ?></div>
            <?php endif; ?>

            <?php if ($flash_success !== ''): ?>
                <div class="alert alert-success"><?php echo escapeHtml($flash_success); ?></div>
            <?php endif; ?>

            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-inner-wrap">
                        <span class="metric-accent-bar metric-accent-teal"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">SIAP AMBIL</span>
                            <span class="metric-value metric-value-teal"><?php echo escapeHtml((string) $total_siap_ambil); ?> Porsi</span>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-inner-wrap">
                        <span class="metric-accent-bar metric-accent-orange"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">KLAIM MASUK</span>
                            <span class="metric-value metric-value-orange"><?php echo escapeHtml((string) $klaim_masuk); ?> Pesanan</span>
                        </div>
                    </div>
                </div>
                <div class="metric-card">
                    <div class="metric-inner-wrap">
                        <span class="metric-accent-bar metric-accent-red"></span>
                        <div class="metric-text-group">
                            <span class="metric-label">TOTAL PANGAN TERSALURKAN</span>
                            <span class="metric-value metric-value-red"><?php echo escapeHtml((string) $total_tersalurkan); ?> Porsi</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-header">
                <h2>Perlu Diproses</h2>
                <a href="transactions.php" class="section-link">Lihat Semua Riwayat Klaim &#8594;</a>
            </div>

            <div class="table-scroll-wrapper">
                <table class="table-modern table-striped">
                    <thead>
                        <tr class="table-header">
                            <th class="table-col-code">KODE KLAIM</th>
                            <th class="table-col-user">PENERIMA / KONSUMEN</th>
                            <th class="table-col-menu">MENU MAKANAN</th>
                            <th class="table-col-portion">PORSI</th>
                            <th class="table-col-time">WAKTU KLAIM</th>
                            <th class="table-col-status">STATUS</th>
                            <th class="table-col-action">AKSI PESANAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_transaksi)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: #6b7280;">Belum ada pesanan klaim masuk untuk gerai Anda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_transaksi as $trx): ?>
                                <?php
                                $badge_cls = 'badge-waiting';
                                $badge_lbl = 'Menunggu';
                                if ($trx['status'] === 'siap_diambil') {
                                    $badge_cls = 'badge-ready';
                                    $badge_lbl = 'Siap Diambil';
                                } elseif ($trx['status'] === 'selesai') {
                                    $badge_cls = 'badge-done';
                                    $badge_lbl = 'Selesai';
                                } elseif ($trx['status'] === 'dibatalkan') {
                                    $badge_cls = 'badge-danger';
                                    $badge_lbl = 'Dibatalkan';
                                }
                                ?>
                                <tr>
                                    <td class="table-col-code">
                                        <a href="transactions.php?code=<?php echo urlencode($trx['kode_transaksi']); ?>" class="table-claim-code" style="text-decoration: none;">
                                            <?php echo escapeHtml($trx['kode_transaksi']); ?>
                                        </a>
                                    </td>
                                    <td class="table-col-user">
                                        <div class="table-user-name"><?php echo escapeHtml($trx['nama_konsumen']); ?></div>
                                        <div class="table-user-sub">Verifikasi: <?php echo escapeHtml(ucfirst($trx['status_konsumen'])); ?></div>
                                    </td>
                                    <td class="table-col-menu"><span class="table-menu-name"><?php echo escapeHtml($trx['menu_ringkas']); ?></span></td>
                                    <td class="table-col-portion"><span class="table-portion"><?php echo escapeHtml((string) $trx['total_porsi']); ?> Porsi</span></td>
                                    <td class="table-col-time"><span class="table-time"><?php echo escapeHtml(date('d M Y, H:i', strtotime($trx['dibuat_pada']))); ?> WIB</span></td>
                                    <td class="table-col-status"><span class="badge <?php echo escapeHtml($badge_cls); ?>"><?php echo escapeHtml($badge_lbl); ?></span></td>
                                    <td class="table-col-action">
                                        <?php if ($trx['status'] === 'menunggu'): ?>
                                            <form action="actions/crud2_status_update.php" method="post" style="margin: 0;">
                                                <div>
                                                    <input type="hidden" name="transaksi_id" value="<?php echo escapeHtml((string) ((int) $trx['id'])); ?>" />
                                                    <input type="hidden" name="status" value="siap_diambil" />
                                                    <input type="hidden" name="redirect_to" value="producer_dashboard" />
                                                    <button type="submit" class="btn-table-action btn-action-teal">Siapkan Porsi</button>
                                                </div>
                                            </form>
                                        <?php elseif ($trx['status'] === 'siap_diambil'): ?>
                                            <form action="actions/crud2_status_update.php" method="post" style="margin: 0;">
                                                <div>
                                                    <input type="hidden" name="transaksi_id" value="<?php echo escapeHtml((string) ((int) $trx['id'])); ?>" />
                                                    <input type="hidden" name="status" value="selesai" />
                                                    <input type="hidden" name="redirect_to" value="producer_dashboard" />
                                                    <button type="submit" class="btn-table-action btn-action-orange">Tandai Selesai</button>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <a href="transactions.php?code=<?php echo urlencode($trx['kode_transaksi']); ?>" class="btn-table-action btn-action-gray">Rincian</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="section-header">
                <h2>Inventaris Makanan Aktif</h2>
                <a href="inventory.php" class="section-link">Kelola Semua Inventaris &#8594;</a>
            </div>

            <div class="inventory-scroll-container">
                <?php if (empty($daftar_makanan) && !$produsen_terverifikasi): ?>
                    <div class="alert alert-warning" style="width: 100%; margin-bottom: 0;">
                        Belum ada makanan aktif dalam inventaris gerai Anda.
                    </div>
                <?php endif; ?>
                <?php foreach ($daftar_makanan as $item): ?>
                    <?php
                    $foto_url = crud1FormatFotoUrl($item['foto_makanan']);
                    $batas_ambil = crud1FormatKedaluwarsaTampil($item['kedaluwarsa_pada']);
                    $sudah_kedaluwarsa = (!empty($item['kedaluwarsa_pada']) && strtotime($item['kedaluwarsa_pada']) <= time());
                    ?>
                    <div class="food-card">
                        <div class="food-card-thumb-wrap">
                            <img src="<?php echo escapeHtml($foto_url); ?>" alt="<?php echo escapeHtml($item['nama_makanan']); ?>" class="food-card-img" />
                            <span class="food-card-stock-badge">Stok: <?php echo escapeHtml((string) ((int) $item['stok'])); ?></span>
                        </div>
                        <div class="food-card-body">
                            <h3 class="food-card-title"><?php echo escapeHtml($item['nama_makanan']); ?></h3>
                            <p class="food-card-category">Kategori: <?php echo escapeHtml($item['nama_kategori']); ?> &#8226; <?php echo escapeHtml($nama_gerai); ?></p>
                            <p class="food-card-price"><?php echo escapeHtml(formatRupiah((int) $item['harga'])); ?></p>
                            <p class="food-card-time">Batas Ambil: <?php echo escapeHtml($batas_ambil); ?><?php echo $sudah_kedaluwarsa ? ' (Kedaluwarsa)' : ''; ?></p>
                            <?php if ($produsen_terverifikasi): ?>
                            <div class="food-card-actions">
                                <a href="add_food.php?id=<?php echo escapeHtml((string) ((int) $item['id'])); ?>" class="btn btn-sm btn-secondary">Ubah Detail</a>
                                <form action="actions/crud1_item.php" method="post" style="flex: 1; display: flex; margin: 0;" onsubmit="return confirm('Tutup donasi untuk makanan ini (stok akan disetel menjadi 0)?');">
                                    <div style="width: 100%; display: flex;">
                                        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                        <input type="hidden" name="action" value="tutup_donasi" />
                                        <input type="hidden" name="id" value="<?php echo escapeHtml((string) ((int) $item['id'])); ?>" />
                                        <button type="submit" class="btn btn-sm btn-danger" style="width: 100%;">Tutup Donasi</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($produsen_terverifikasi): ?>
                <a href="add_food.php" class="add-food-card-box" title="Tambah Donasi Makanan Baru">
                    <span class="add-food-card-icon">+</span>
                    <span class="add-food-card-text">Tambah Makanan Baru</span>
                </a>
                <?php endif; ?>
            </div>

        </div>

<?php
require_once 'includes/footer.php';
?>
