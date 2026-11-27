<?php
require_once 'includes/session.php';
require_login();
require_once 'config/database.php';

$pengguna_id = (int) $_SESSION['user_id'];
$email_user = '';
$nama_user = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '';

$stmt = $conn ? mysqli_prepare($conn, "SELECT email, nama_lengkap FROM pengguna WHERE id = ?") : false;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $pengguna_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $email_user = $row['email'];
        $nama_user = $row['nama_lengkap'];
    }
    mysqli_stmt_close($stmt);
}

$pesan_error = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'empty_fields') {
        $pesan_error = 'Topik, subjek, dan rincian pesan wajib diisi.';
    } elseif ($_GET['error'] === 'trx_code_too_long') {
        $pesan_error = 'Kode transaksi maksimal 32 karakter.';
    } elseif ($_GET['error'] === 'csrf_invalid') {
        $pesan_error = 'Sesi formulir tidak valid. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($_GET['error'] === 'db_error') {
        $pesan_error = 'Terjadi gangguan pada basis data. Silakan coba beberapa saat lagi.';
    } else {
        $pesan_error = 'Terjadi kesalahan saat menyimpan tiket bantuan. Silakan coba lagi.';
    }
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pusat Bantuan - Formulir Tiket Pengaduan - Pangkalan Pangan</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css?v=7" />
    <link rel="stylesheet" type="text/css" href="assets/css/components.css?v=7" />
    <link rel="stylesheet" type="text/css" href="assets/css/customer_service.css?v=1" />
</head>
<body data-auth="true">
    <div class="page-wrapper">
        <?php include 'includes/navbar.php'; ?>

        <div class="cs-page-section">
            <div class="cs-tab-container">
                <a href="customer_service.php" class="cs-tab-item active">Buka Tiket Baru</a>
                <a href="ticket_history.php" class="cs-tab-item">Cek Status &amp; Riwayat Tiket</a>
            </div>

            <div class="cs-main-card">
                <h1 class="cs-card-title">Buka Tiket Baru</h1>
                <hr class="cs-divider" />

                <?php if ($pesan_error !== ''): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($pesan_error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form action="actions/cs_ticket_create.php" method="post" id="csTicketForm">
                    <div>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '', ENT_QUOTES, 'UTF-8'); ?>" />
                    </div>
                    <h2 class="cs-section-title">Informasi Kontak Pengadu</h2>
                    <div class="cs-form-row">
                        <div class="cs-form-col">
                            <label for="emailInput" class="cs-label">Alamat Surel Aktif *</label>
                            <input type="text" id="emailInput" name="email" class="cs-input" value="<?php echo htmlspecialchars($email_user, ENT_QUOTES, 'UTF-8'); ?>" readonly="readonly" />
                        </div>
                        <div class="cs-form-col">
                            <label for="nameInput" class="cs-label">Nama Lengkap *</label>
                            <input type="text" id="nameInput" name="full_name" class="cs-input" value="<?php echo htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8'); ?>" readonly="readonly" />
                        </div>
                    </div>

                    <div class="cs-form-row">
                        <div class="cs-form-col">
                            <label for="trxCodeInput" class="cs-label">Kode Transaksi Terkait (Opsional)</label>
                            <input type="text" id="trxCodeInput" name="trx_code" class="cs-input" maxlength="32" />
                        </div>
                    </div>

                    <hr class="cs-divider" />

                    <h2 class="cs-section-title">Topik Bantuan</h2>
                    <div class="cs-form-row">
                        <div class="cs-form-col">
                            <label for="topicSelect" class="cs-label">Pilih Topik Bantuan *</label>
                            <select id="topicSelect" name="topic" class="cs-select">
                                <option value="">-- Pilih Topik Bantuan --</option>
                                <option value="Kendala Pengambilan di Posko">Kendala Pengambilan di Posko</option>
                                <option value="Mutu &amp; Kualitas Pangan">Mutu &amp; Kualitas Pangan</option>
                                <option value="Verifikasi Dokumen SKTM / KKS">Verifikasi Dokumen SKTM / KKS</option>
                                <option value="Akun &amp; Keamanan">Akun &amp; Keamanan</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>

                    <hr class="cs-divider" />

                    <h2 class="cs-section-title">Rincian Kendala &amp; Pesan</h2>
                    <div class="cs-form-row">
                        <div class="cs-form-col">
                            <label for="subjectInput" class="cs-label">Judul / Subjek Tiket *</label>
                            <input type="text" id="subjectInput" name="subject" class="cs-input" />
                        </div>
                    </div>

                    <div class="cs-form-row">
                        <div class="cs-form-col">
                            <label for="messageTextarea" class="cs-label">Rincian Masalah / Pesan Pengaduan *</label>
                            <textarea id="messageTextarea" name="message" class="cs-textarea" rows="4" cols="50"></textarea>
                        </div>
                    </div>

                    <hr class="cs-divider" />

                    <div class="cs-btn-wrap">
                        <button type="submit" class="btn-cs-submit" id="btnSubmitTicket">Buat Tiket</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="site-footer" id="about">
            <div class="footer-inner">
                <div class="footer-top-columns">
                    <div class="footer-col-left" id="help">
                        <div class="footer-heading">Tetap Terhubung</div>
                        <div class="footer-contact-p">Nomor Kontak: +6281229266070</div>
                        <div class="footer-contact-p">Instagram: @PP</div>
                        <div class="footer-contact-p">Surel: PangkalanPangan@gmail.com</div>
                    </div>
                    <div class="footer-col-center">
                        <img src="assets/images/footer_logo_sprout.svg" alt="Logo Pangkalan Pangan Footer" class="footer-center-logo" />
                    </div>
                    <div class="footer-col-right">
                        <div class="footer-heading">Ambil peran</div>
                        <ul class="footer-link-list">
                            <li><a href="register.php?role=donor">Donatur pangan</a></li>
                            <li><a href="register.php?role=donor">Mitra penyaluran</a></li>
                        </ul>
                    </div>
                </div>
                <hr class="footer-divider-line" />
                <div class="footer-bottom-copy">
                    <img src="assets/images/footer_copyright.svg" alt="Pangkalan Pangan. Bersama merawat kebaikan." class="footer-copy-img" />
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="assets/js/customer_service.js?v=1"></script>
</body>
</html>
