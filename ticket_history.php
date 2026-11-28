<?php
require_once 'includes/session.php';
require_role(array('konsumen', 'produsen'));
require_once 'config/database.php';
require_once 'includes/functions.php';

$pengguna_id = (int) $_SESSION['user_id'];
if ($conn) {
    $pengguna_th = ambilPenggunaById($conn, $pengguna_id);
    if (!$pengguna_th) {
        hapusSesiPengguna();
        header('Location: login.php?error=login_required');
        exit();
    }
    sinkronSesiPengguna($pengguna_th);
    if (!in_array($pengguna_th['peran'], array('konsumen', 'produsen'), true)) {
        require_role(array('konsumen', 'produsen'));
    }
}
$daftar_tiket = array();
$total_tiket = 0;
$tiket_menunggu = 0;
$tiket_selesai = 0;

$stmt = $conn ? mysqli_prepare(
    $conn,
    "SELECT id, kode_transaksi, pesan, balasan_admin, status, dibuat_pada FROM tiket_bantuan WHERE pengguna_id = ? ORDER BY id DESC"
) : false;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $pengguna_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $daftar_tiket[] = $row;
        $total_tiket++;
        if ($row['status'] === 'selesai') {
            $tiket_selesai++;
        } else {
            $tiket_menunggu++;
        }
    }
    mysqli_stmt_close($stmt);
}

$pesan_sukses = '';
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $pesan_sukses = 'Tiket bantuan Anda berhasil dikirim dan sedang menunggu tanggapan admin.';
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Riwayat Tiket - Bantuan &amp; Keluhan - Pangkalan Pangan</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css?v=8" />
    <link rel="stylesheet" type="text/css" href="assets/css/components.css?v=10" />
    <link rel="stylesheet" type="text/css" href="assets/css/customer_service.css?v=1" />
</head>
<body>
    <div class="page-wrapper">
        <?php include 'includes/navbar.php'; ?>

        <div class="cs-page-section">
            <div class="cs-tab-container">
                <a href="customer_service.php" class="cs-tab-item">Buka Tiket Baru</a>
                <a href="ticket_history.php" class="cs-tab-item active">Cek Status &amp; Riwayat Tiket</a>
            </div>

            <div class="cs-main-card">
                <div class="ticket-header-row">
                    <h1 class="cs-card-title">Cek Status &amp; Riwayat Tiket</h1>
                    <a href="customer_service.php" class="btn-cs-new-ticket">+ Buka Tiket Baru</a>
                </div>

                <hr class="cs-divider" />

                <?php if ($pesan_sukses !== ''): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($pesan_sukses, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <div class="ticket-filter-bar">
                    <div class="ticket-filter-pills">
                        <button type="button" class="ticket-filter-pill ticket-filter-all active">Semua Tiket (<?php echo htmlspecialchars((string) $total_tiket, ENT_QUOTES, 'UTF-8'); ?>)</button>
                        <button type="button" class="ticket-filter-pill ticket-filter-pending">Menunggu Balasan (<?php echo htmlspecialchars((string) $tiket_menunggu, ENT_QUOTES, 'UTF-8'); ?>)</button>
                        <button type="button" class="ticket-filter-pill ticket-filter-done">Sudah Selesai (<?php echo htmlspecialchars((string) $tiket_selesai, ENT_QUOTES, 'UTF-8'); ?>)</button>
                    </div>
                    <div class="ticket-search-box">
                        <input type="text" id="ticketSearchInput" class="ticket-search-input" />
                    </div>
                </div>

                <div class="ticket-list" id="ticketListContainer">
                    <?php if (empty($daftar_tiket)): ?>
                        <div class="ticket-empty-state" id="ticketEmptyState" style="display: block;">
                            Anda belum memiliki riwayat tiket bantuan.
                        </div>
                    <?php else: ?>
                        <?php foreach ($daftar_tiket as $tiket): ?>
                            <?php
                            $is_done = ($tiket['status'] === 'selesai');
                            $status_class = $is_done ? 'ticket-status-done' : 'ticket-status-pending';
                            $badge_class = $is_done ? 'ticket-badge-done' : 'ticket-badge-pending';
                            $status_label = $is_done ? 'Selesai' : 'Menunggu Balasan';
                            ?>
                            <div class="ticket-item-card <?php echo htmlspecialchars($status_class, ENT_QUOTES, 'UTF-8'); ?>" id="ticket-card-<?php echo htmlspecialchars((string) $tiket['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="ticket-top-line">
                                    <div class="ticket-meta-left">
                                        <span class="ticket-id">#CS-<?php echo htmlspecialchars((string) $tiket['id'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span class="ticket-date">Diajukan pada: <?php echo htmlspecialchars((string) $tiket['dibuat_pada'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <span class="ticket-badge <?php echo htmlspecialchars($badge_class, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>

                                <?php if (!empty($tiket['kode_transaksi'])): ?>
                                    <div class="ticket-topic">Kode Transaksi Terkait: <?php echo htmlspecialchars($tiket['kode_transaksi'], ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>

                                <div class="ticket-msg-box">
                                    <div class="ticket-msg-label">Pesan Anda:</div>
                                    <p class="ticket-msg-text"><?php echo htmlspecialchars($tiket['pesan'], ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>

                                <?php if ($is_done && !empty($tiket['balasan_admin'])): ?>
                                    <div class="ticket-reply-box">
                                        <div class="ticket-reply-header">
                                            <span class="ticket-reply-sender">Tanggapan Admin:</span>
                                        </div>
                                        <p class="ticket-reply-text"><?php echo htmlspecialchars($tiket['balasan_admin'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="ticket-notice-box">
                                        <p class="ticket-notice-text">Status: Tiket masih dalam antrean dan akan segera dibalas oleh Admin.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="ticket-empty-state" id="ticketEmptyState">
                            Tidak ada tiket yang sesuai dengan pencarian atau filter Anda.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

<?php
$extra_js = array('customer_service.js?v=1');
require_once 'includes/footer.php';
?>
