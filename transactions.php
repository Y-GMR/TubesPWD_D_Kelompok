<?php
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = null;
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
}

// 1. Session Guard: Wajib Login
if (!function_exists('is_logged_in') || !is_logged_in()) {
    header("Location: login.php?error=login_required");
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen';

// 2. Ambil data profil pengguna
$data_pengguna = ambilPenggunaById($conn, $user_id);
if ($conn && !$data_pengguna) {
    hapusSesiPengguna();
    header("Location: login.php?error=login_required");
    exit();
}
if ($data_pengguna) {
    sinkronSesiPengguna($data_pengguna);
    if (isset($data_pengguna['peran'])) {
        $user_role = $data_pengguna['peran'];
    }
}
$nama_user_tampil = ($data_pengguna && isset($data_pengguna['nama_lengkap']))
    ? $data_pengguna['nama_lengkap']
    : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Pengguna');
$status_sktm = ($data_pengguna && isset($data_pengguna['status_verifikasi'])) ? $data_pengguna['status_verifikasi'] : 'menunggu';

// 3. Hitung Kuota Harian Konsumen (Maksimal 25 porsi dan 5 kali checkout per hari)
$maks_porsi_harian = 25;
$maks_checkout_harian = 5;
$sudah_porsi_hari_ini = 0;
$sudah_checkout_hari_ini = 0;

if ($conn && $user_role === 'konsumen') {
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

    // Total checkout/transaksi hari ini
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

// 4. Query Riwayat Transaksi Sesuai Peran Pengguna
$daftar_transaksi = array();

if ($conn) {
    if ($user_role === 'admin') {
        $sql_trx = "SELECT t.id, t.kode_transaksi, t.total_harga, t.status, t.dibuat_pada,
                           pk.nama_lengkap AS nama_konsumen, pp.nama_lengkap AS nama_produsen,
                           pk.nama_lengkap AS pihak_terkait, pk.id AS pihak_id,
                           (SELECT m.alamat_ambil FROM detail_transaksi dt JOIN makanan m ON dt.makanan_id = m.id WHERE dt.transaksi_id = t.id LIMIT 1) AS alamat_ambil
                    FROM transaksi t
                    INNER JOIN pengguna pk ON t.konsumen_id = pk.id
                    INNER JOIN pengguna pp ON t.produsen_id = pp.id
                    ORDER BY t.dibuat_pada DESC";
        $stmt_trx = mysqli_prepare($conn, $sql_trx);
    } elseif ($user_role === 'produsen') {
        $sql_trx = "SELECT t.id, t.kode_transaksi, t.total_harga, t.status, t.dibuat_pada,
                           pk.nama_lengkap AS nama_konsumen, pp.nama_lengkap AS nama_produsen,
                           pk.nama_lengkap AS pihak_terkait, pk.id AS pihak_id,
                           NULL AS alamat_ambil
                    FROM transaksi t
                    INNER JOIN pengguna pk ON t.konsumen_id = pk.id
                    INNER JOIN pengguna pp ON t.produsen_id = pp.id
                    WHERE t.produsen_id = ?
                    ORDER BY t.dibuat_pada DESC";
        $stmt_trx = mysqli_prepare($conn, $sql_trx);
        if ($stmt_trx) {
            mysqli_stmt_bind_param($stmt_trx, "i", $user_id);
        }
    } else {
        $sql_trx = "SELECT t.id, t.kode_transaksi, t.total_harga, t.status, t.dibuat_pada,
                           pk.nama_lengkap AS nama_konsumen, pp.nama_lengkap AS nama_produsen,
                           pp.nama_lengkap AS pihak_terkait, pp.id AS pihak_id,
                           (SELECT m.alamat_ambil FROM detail_transaksi dt JOIN makanan m ON dt.makanan_id = m.id WHERE dt.transaksi_id = t.id LIMIT 1) AS alamat_ambil
                    FROM transaksi t
                    INNER JOIN pengguna pk ON t.konsumen_id = pk.id
                    INNER JOIN pengguna pp ON t.produsen_id = pp.id
                    WHERE t.konsumen_id = ?
                    ORDER BY t.dibuat_pada DESC";
        $stmt_trx = mysqli_prepare($conn, $sql_trx);
        if ($stmt_trx) {
            mysqli_stmt_bind_param($stmt_trx, "i", $user_id);
        }
    }

    if ($stmt_trx) {
        mysqli_stmt_execute($stmt_trx);
        $res_trx = mysqli_stmt_get_result($stmt_trx);

        while ($trx_row = mysqli_fetch_assoc($res_trx)) {
            $t_id = (int) $trx_row['id'];
            $trx_row['items'] = array();

            // Ambil rincian makanan untuk transaksi ini
            $sql_dt = "SELECT dt.makanan_id, dt.jumlah, dt.harga_satuan, dt.subtotal, m.nama_makanan
                       FROM detail_transaksi dt
                       INNER JOIN makanan m ON dt.makanan_id = m.id
                       WHERE dt.transaksi_id = ?";
            $stmt_dt = mysqli_prepare($conn, $sql_dt);
            if ($stmt_dt) {
                mysqli_stmt_bind_param($stmt_dt, "i", $t_id);
                mysqli_stmt_execute($stmt_dt);
                $res_dt = mysqli_stmt_get_result($stmt_dt);
                while ($item_row = mysqli_fetch_assoc($res_dt)) {
                    $trx_row['items'][] = $item_row;
                }
                mysqli_stmt_close($stmt_dt);
            }

            $daftar_transaksi[] = $trx_row;
        }
        mysqli_stmt_close($stmt_trx);
    }
}

// Cari transaksi aktif utama untuk tiket display (status 'menunggu' atau 'siap_diambil')
$tiket_aktif = null;
$kode_fokus = (isset($_GET['code']) && is_string($_GET['code'])) ? trim($_GET['code']) : '';

if ($kode_fokus !== '') {
    foreach ($daftar_transaksi as $t) {
        if ($t['kode_transaksi'] === $kode_fokus) {
            $tiket_aktif = $t;
            break;
        }
    }
}

if ($tiket_aktif === null) {
    foreach ($daftar_transaksi as $t) {
        if ($t['status'] === 'menunggu' || $t['status'] === 'siap_diambil') {
            $tiket_aktif = $t;
            break;
        }
    }
}

// Jika tidak ada yang aktif, ambil transaksi paling terakhir
if ($tiket_aktif === null && !empty($daftar_transaksi)) {
    $tiket_aktif = $daftar_transaksi[0];
}

if ($user_role === 'admin') {
    $judul_halaman_trx = 'Manajemen Semua Transaksi Klaim';
    $judul_tiket_kiri = 'Rincian Tiket Klaim';
    $judul_riwayat_kanan = 'Daftar Semua Transaksi';
    $back_home_url = 'views/admin/dashboard.php';
    $back_home_label = 'Kembali ke Dashboard Admin';
    $empty_title = 'Belum ada transaksi klaim tercatat';
    $empty_desc = 'Transaksi klaim pangan dari seluruh konsumen dan mitra produsen akan otomatis tercatat di sini.';
    $help_url = 'views/admin/manage_users.php#antrean-cs';
    $help_prefix = 'Kelola aduan pengguna di ';
    $help_link_text = 'Verifikasi & CS';
    $catatan_tiket_role = 'Pantau kode transaksi, pihak terkait, serta status pengambilan atau pembatalan pesanan.';
} elseif ($user_role === 'produsen') {
    $judul_halaman_trx = 'Kelola Klaim Masuk Gerai';
    $judul_tiket_kiri = 'Rincian Tiket Klaim';
    $judul_riwayat_kanan = 'Semua Klaim Masuk Gerai';
    $back_home_url = 'producer_dashboard.php';
    $back_home_label = 'Kembali ke Dashboard Produsen';
    $empty_title = 'Belum ada klaim masuk untuk gerai Anda';
    $empty_desc = 'Daftar pesanan klaim paket pangan yang masuk dari konsumen untuk gerai Anda akan otomatis tercatat di sini.';
    $help_url = 'customer_service.php';
    $help_prefix = 'Mengalami kendala klaim? ';
    $help_link_text = 'Bantuan';
    $catatan_tiket_role = 'Cocokkan kode transaksi ini dengan tiket yang ditunjukkan penerima manfaat saat pengambilan pangan di gerai Anda.';
} else {
    $judul_halaman_trx = 'Riwayat Klaim & Tiket Transaksi';
    $judul_tiket_kiri = 'Tiket Pengambilan Pangan';
    $judul_riwayat_kanan = 'Semua Riwayat Klaim';
    $back_home_url = 'katalog.php';
    $back_home_label = 'Kembali ke Katalog';
    $empty_title = 'Belum ada riwayat klaim';
    $empty_desc = 'Daftar makanan yang berhasil Anda klaim dan ambil di gerai produsen akan otomatis tercatat di sini.';
    $help_url = 'customer_service.php';
    $help_prefix = 'Mengalami kendala klaim? ';
    $help_link_text = 'Bantuan';
    $catatan_tiket_role = 'Tunjukkan kode transaksi kepada staf gerai produsen saat pengambilan makanan sebelum batas waktu operasional berakhir.';
}

$page_title = $judul_halaman_trx . ' - Pangkalan Pangan';
$extra_css = array('checkout.css?v=7', 'components.css?v=10');
$extra_js = array('crud1_navbar.js?v=1');
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="checkout-main-section">
    <div class="checkout-container">
<?php if ($user_role === 'admin' || $user_role === 'produsen'): ?>
        <div class="back-link-wrap" style="margin-bottom: 14px;">
            <a href="<?php echo escapeHtml($back_home_url); ?>" class="back-link-btn">&#8592; <?php echo escapeHtml($back_home_label); ?></a>
        </div>
<?php endif; ?>
        <h1 class="checkout-page-title"><?php echo escapeHtml($judul_halaman_trx); ?></h1>

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

        <div class="claim-history-grid">
            <!-- Kolom Kiri: Tiket Pengambilan Aktif -->
            <div class="checkout-card claim-ticket-card">
                <h2 class="summary-title" style="text-align: center;"><?php echo escapeHtml($judul_tiket_kiri); ?></h2>
                <hr class="summary-divider" />

                <?php if ($tiket_aktif): ?>
                    <?php
                    $status_badge_text = '';
                    $status_badge_color = '';
                    if ($tiket_aktif['status'] === 'menunggu') {
                        $status_badge_text = 'Menunggu Konfirmasi Gerai';
                        $status_badge_color = '#D69E2E';
                    } elseif ($tiket_aktif['status'] === 'siap_diambil') {
                        $status_badge_text = 'Siap Diambil di Gerai';
                        $status_badge_color = '#38A169';
                    } elseif ($tiket_aktif['status'] === 'selesai') {
                        $status_badge_text = 'Klaim Selesai Diambil';
                        $status_badge_color = '#3182CE';
                    } elseif ($tiket_aktif['status'] === 'dibatalkan') {
                        $status_badge_text = 'Klaim Dibatalkan';
                        $status_badge_color = '#E53E3E';
                    }
                    ?>

                    <ul class="summary-list">
<?php if ($user_role === 'konsumen'): ?>
                        <li class="summary-item">
                            <span class="summary-item-label">Penerima Manfaat</span>
                            <span class="summary-item-val val-teal"><?php echo escapeHtml($nama_user_tampil); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Status SKTM</span>
                            <span class="summary-item-val <?php echo ($status_sktm === 'disetujui') ? 'val-green' : 'val-amber'; ?>">
                                <?php echo escapeHtml(ucfirst($status_sktm)); ?>
                            </span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Porsi Hari Ini</span>
                            <span class="summary-item-val val-dark"><?php echo (int) $sudah_porsi_hari_ini; ?> dari <?php echo (int) $maks_porsi_harian; ?> porsi maks</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Sisa Kuota Porsi</span>
                            <span class="summary-item-val val-green"><?php echo (int) $sisa_porsi_harian; ?> porsi lagi</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Checkout Hari Ini</span>
                            <span class="summary-item-val val-dark"><?php echo (int) $sudah_checkout_hari_ini; ?> dari <?php echo (int) $maks_checkout_harian; ?> kali</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Sisa Kuota Checkout</span>
                            <span class="summary-item-val val-amber"><?php echo (int) $sisa_checkout_harian; ?> kali lagi</span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Tempat Pengambilan</span>
                            <span class="summary-item-val val-dark"><?php echo escapeHtml($tiket_aktif['pihak_terkait']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Total Pembayaran</span>
                            <span class="summary-item-val val-green"><?php echo formatRupiah($tiket_aktif['total_harga']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Waktu Pemesanan</span>
                            <span class="summary-item-val val-dark"><?php echo escapeHtml(date('d M Y, H:i', strtotime($tiket_aktif['dibuat_pada']))); ?> WIB</span>
                        </li>
<?php else: ?>
                        <li class="summary-item">
                            <span class="summary-item-label">Konsumen</span>
                            <span class="summary-item-val val-teal"><?php echo escapeHtml($tiket_aktif['nama_konsumen']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Gerai Produsen</span>
                            <span class="summary-item-val val-dark"><?php echo escapeHtml($tiket_aktif['nama_produsen']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Total Pembayaran</span>
                            <span class="summary-item-val val-green"><?php echo formatRupiah($tiket_aktif['total_harga']); ?></span>
                        </li>
                        <li class="summary-item">
                            <span class="summary-item-label">Waktu Pemesanan</span>
                            <span class="summary-item-val val-dark"><?php echo escapeHtml(date('d M Y, H:i', strtotime($tiket_aktif['dibuat_pada']))); ?> WIB</span>
                        </li>
<?php endif; ?>
                        <?php if (!empty($tiket_aktif['alamat_ambil'])): ?>
                            <li class="summary-item">
                                <span class="summary-item-label">Alamat Gerai</span>
                                <span class="summary-item-val val-dark" style="font-size: 12px; max-width: 200px; text-align: right;">
                                    <?php echo escapeHtml($tiket_aktif['alamat_ambil']); ?>
                                </span>
                            </li>
                        <?php endif; ?>
                        <li class="summary-item">
                            <span class="summary-item-label">Status Klaim</span>
                            <span class="summary-item-val" style="color: <?php echo $status_badge_color; ?>; font-weight: bold;">
                                <?php echo escapeHtml($status_badge_text); ?>
                            </span>
                        </li>
                    </ul>

                    <div class="ticket-box" style="margin: 20px 0; padding: 16px; background-color: #F7FAFC; border: 2px dashed #CBD5E0; border-radius: 8px; text-align: center;">
                        <div class="ticket-label" style="font-size: 12px; letter-spacing: 1px; color: #718096; margin-bottom: 4px;">KODE TIKET TRANSAKSI</div>
                        <div class="ticket-code" style="font-size: 24px; font-weight: bold; color: #2B6CB0; letter-spacing: 2px;">
                            <?php echo escapeHtml($tiket_aktif['kode_transaksi']); ?>
                        </div>
                    </div>

                    <!-- Rincian Item pada Tiket -->
                    <div style="margin-bottom: 16px; font-size: 13px; color: #4A5568;">
                        <strong>Menu yang Diklaim:</strong>
                        <ul style="margin: 6px 0 0 16px; padding: 0;">
                            <?php foreach ($tiket_aktif['items'] as $it): ?>
                                <li><?php echo escapeHtml($it['nama_makanan']); ?> x <?php echo (int) $it['jumlah']; ?> porsi (<?php echo formatRupiah($it['subtotal']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if ($tiket_aktif['status'] !== 'dibatalkan'): ?>
                        <p class="ticket-reminder-note" style="font-size: 12px; color: #718096; line-height: 1.4; margin-bottom: 16px;">
                            <?php echo escapeHtml($catatan_tiket_role); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Tombol Aksi Pembatalan (Jika Masih Status Menunggu) -->
                    <?php if (($user_role === 'konsumen' || $user_role === 'admin') && $tiket_aktif['status'] === 'menunggu'): ?>
                        <form action="actions/crud2_status_update.php" method="post" style="margin: 0 0 12px 0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan klaim pesanan ini? Stok porsi akan dikembalikan ke inventaris.');">
                            <div>
                                <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                <input type="hidden" name="transaksi_id" value="<?php echo (int) $tiket_aktif['id']; ?>" />
                                <input type="hidden" name="aksi" value="batal" />
                                <button type="submit" class="btn-cancel-checkout" style="width: 100%; border: 1px solid #E53E3E; background-color: #FFF5F5; color: #E53E3E; cursor: pointer; padding: 10px; border-radius: 6px; font-weight: bold;">
                                    Batalkan Klaim Ini
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Tombol Aksi untuk Produsen atau Admin (Ubah Status ke Siap Diambil / Selesai) -->
                    <?php if ($user_role === 'produsen' || $user_role === 'admin'): ?>
                        <?php if ($tiket_aktif['status'] === 'menunggu'): ?>
                            <form action="actions/crud2_status_update.php" method="post" style="margin-bottom: 10px;">
                                <div>
                                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                    <input type="hidden" name="transaksi_id" value="<?php echo (int) $tiket_aktif['id']; ?>" />
                                    <input type="hidden" name="status" value="siap_diambil" />
                                    <button type="submit" class="btn-confirm-claim" style="width: 100%; border: none; cursor: pointer;">
                                        Tandai Siap Diambil
                                    </button>
                                </div>
                            </form>
                        <?php elseif ($tiket_aktif['status'] === 'siap_diambil'): ?>
                            <form action="actions/crud2_status_update.php" method="post" style="margin-bottom: 10px;">
                                <div>
                                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                    <input type="hidden" name="transaksi_id" value="<?php echo (int) $tiket_aktif['id']; ?>" />
                                    <input type="hidden" name="status" value="selesai" />
                                    <button type="submit" class="btn-confirm-claim" style="width: 100%; border: none; cursor: pointer; background-color: #2B6CB0;">
                                        Tandai Telah Selesai Diambil
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>

                    <a href="<?php echo escapeHtml($back_home_url); ?>" class="btn-ticket-catalog" style="display: block; text-align: center; text-decoration: none;"><?php echo escapeHtml($back_home_label); ?></a>

                <?php else: ?>
                    <p style="text-align: center; color: #718096; padding: 24px 0;">Belum ada tiket aktif yang dipilih.</p>
                    <a href="<?php echo escapeHtml($back_home_url); ?>" class="btn-ticket-catalog" style="display: block; text-align: center; text-decoration: none;"><?php echo escapeHtml($back_home_label); ?></a>
                <?php endif; ?>

                <p class="checkout-trouble-note" style="margin-top: 16px; text-align: center;">
                    <?php echo escapeHtml($help_prefix); ?><a href="<?php echo escapeHtml($help_url); ?>" class="checkout-trouble-link"><?php echo escapeHtml($help_link_text); ?></a>
                </p>
            </div>

            <!-- Kolom Kanan: Riwayat Transaksi Lengkap -->
            <div class="checkout-card claim-past-card">
                <h2 class="past-history-title"><?php echo escapeHtml($judul_riwayat_kanan); ?></h2>
                <hr class="summary-divider" />

                <?php if (empty($daftar_transaksi)): ?>
                    <div class="past-history-empty" id="pastHistoryEmpty">
                        <div class="history-empty-icon-wrap">
                            <img src="assets/images/logo_sprout.svg" alt="Pangkalan Pangan" class="history-empty-icon" />
                        </div>
                        <h3 class="history-empty-title"><?php echo escapeHtml($empty_title); ?></h3>
                        <p class="history-empty-desc"><?php echo escapeHtml($empty_desc); ?></p>
                        <a href="<?php echo escapeHtml($back_home_url); ?>" class="btn-history-catalog" style="text-decoration: none;"><?php echo escapeHtml(($user_role === 'konsumen') ? 'Jelajahi Makanan' : $back_home_label); ?></a>
                    </div>
                <?php else: ?>
                    <ul class="past-history-list" style="display: block; list-style: none; padding: 0; margin: 0;">
                        <?php foreach ($daftar_transaksi as $tr): ?>
                            <?php
                            $st_color = '#718096';
                            $st_label = ucfirst($tr['status']);
                            if ($tr['status'] === 'menunggu') {
                                $st_color = '#D69E2E';
                                $st_label = 'Menunggu';
                            } elseif ($tr['status'] === 'siap_diambil') {
                                $st_color = '#38A169';
                                $st_label = 'Siap Diambil';
                            } elseif ($tr['status'] === 'selesai') {
                                $st_color = '#3182CE';
                                $st_label = 'Selesai';
                            } elseif ($tr['status'] === 'dibatalkan') {
                                $st_color = '#E53E3E';
                                $st_label = 'Dibatalkan';
                            }
                            ?>
                            <li style="padding: 16px; margin-bottom: 12px; background: #FFF; border: 1px solid #E2E8F0; border-radius: 8px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                    <div>
                                        <a href="transactions.php?code=<?php echo urlencode($tr['kode_transaksi']); ?>" style="font-weight: bold; color: #2B6CB0; text-decoration: none; font-size: 15px;">
                                            <?php echo escapeHtml($tr['kode_transaksi']); ?>
                                        </a>
                                        <div style="font-size: 12px; color: #A0AEC0; margin-top: 2px;">
                                            <?php echo escapeHtml(date('d M Y, H:i', strtotime($tr['dibuat_pada']))); ?> WIB
                                        </div>
                                    </div>
                                    <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; color: #FFF; background-color: <?php echo $st_color; ?>;">
                                        <?php echo escapeHtml($st_label); ?>
                                    </span>
                                </div>

                                <div style="font-size: 13px; color: #4A5568; margin-bottom: 8px;">
<?php if ($user_role === 'admin'): ?>
                                    <strong>Konsumen:</strong> <?php echo escapeHtml($tr['nama_konsumen']); ?> &#8226;
                                    <strong>Produsen:</strong> <?php echo escapeHtml($tr['nama_produsen']); ?>
<?php else: ?>
                                    <strong><?php echo ($user_role === 'produsen') ? 'Konsumen:' : 'Gerai Produsen:'; ?></strong>
                                    <?php echo escapeHtml($tr['pihak_terkait']); ?>
<?php endif; ?>
                                </div>

                                <div style="font-size: 13px; color: #718096; margin-bottom: 8px;">
                                    <?php foreach ($tr['items'] as $item): ?>
                                        <div>&#8226; <?php echo escapeHtml($item['nama_makanan']); ?> (<?php echo (int) $item['jumlah']; ?> porsi)</div>
                                    <?php endforeach; ?>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #EDF2F7; padding-top: 8px; margin-top: 8px;">
                                    <span style="font-size: 14px; font-weight: bold; color: #2F855A;">
                                        Total: <?php echo formatRupiah($tr['total_harga']); ?>
                                    </span>

                                    <div style="display: flex; gap: 8px;">
                                        <a href="transactions.php?code=<?php echo urlencode($tr['kode_transaksi']); ?>" style="font-size: 12px; color: #2B6CB0; text-decoration: underline;">
                                            Lihat Tiket
                                        </a>

                                        <?php if (($user_role === 'konsumen' || $user_role === 'admin') && $tr['status'] === 'menunggu'): ?>
                                            <form action="actions/crud2_status_update.php" method="post" style="display: inline; margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan klaim pesanan ini?');">
                                                <div style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                                    <input type="hidden" name="transaksi_id" value="<?php echo (int) $tr['id']; ?>" />
                                                    <input type="hidden" name="aksi" value="batal" />
                                                    <button type="submit" style="background: none; border: none; color: #E53E3E; font-size: 12px; cursor: pointer; text-decoration: underline; padding: 0;">
                                                        Batalkan
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>
