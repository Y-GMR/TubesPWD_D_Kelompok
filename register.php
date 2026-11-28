<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

if (is_logged_in()) {
    if (!defined('DB_NO_DIE')) {
        define('DB_NO_DIE', true);
    }
    $conn = null;
    require_once 'config/database.php';
    if ($conn) {
        $pengguna_cek = ambilPenggunaById($conn, (int) $_SESSION['user_id']);
        if (!$pengguna_cek) {
            unset(
                $_SESSION['user_id'],
                $_SESSION['username'],
                $_SESSION['full_name'],
                $_SESSION['role'],
                $_SESSION['profile_image'],
                $_SESSION['status_verifikasi'],
                $_SESSION['cart']
            );
            session_regenerate_id(true);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            sinkronSesiPengguna($pengguna_cek);
        }
    }
}

redirect_if_logged_in();

$pesan_error = '';
$session_flash_error = '';
if (isset($_SESSION['flash_error'])) {
    $session_flash_error = trim((string) $_SESSION['flash_error']);
    unset($_SESSION['flash_error']);
}
if (isset($_SESSION['flash_success'])) {
    unset($_SESSION['flash_success']);
}

$role_query_param = isset($_GET['role']) ? strtolower(bersihkanInput($_GET['role'])) : '';
$is_donor_mode = ($role_query_param === 'donor' || $role_query_param === 'produsen');

if (isset($_GET['error'])) {
    $err = bersihkanInput($_GET['error']);
    if ($err === 'data_tidak_lengkap') {
        $pesan_error = 'Semua kolom formulir pendaftaran wajib diisi lengkap.';
    } elseif ($err === 'nama_tidak_valid') {
        $pesan_error = 'Nama lengkap harus terdiri dari 2 hingga 100 karakter.';
    } elseif ($err === 'email_tidak_valid') {
        $pesan_error = 'Format alamat email yang Anda masukkan tidak valid.';
    } elseif ($err === 'username_panjang_salah') {
        $pesan_error = 'Username harus terdiri dari 4 hingga 30 karakter.';
    } elseif ($err === 'username_karakter_tidak_valid') {
        $pesan_error = 'Username hanya boleh berisi huruf, angka, dan garis bawah (_).';
    } elseif ($err === 'password_terlalu_pendek') {
        $pesan_error = 'Kata sandi minimal harus 8 karakter.';
    } elseif ($err === 'password_tidak_memenuhi_syarat') {
        $pesan_error = 'Kata sandi harus mengandung kombinasi huruf dan angka.';
    } elseif ($err === 'password_tidak_cocok') {
        $pesan_error = 'Kata sandi dan konfirmasi kata sandi tidak cocok.';
    } elseif ($err === 'username_sudah_dipakai') {
        $pesan_error = 'Username tersebut sudah terdaftar. Silakan pilih username lain.';
    } elseif ($err === 'email_sudah_dipakai') {
        $pesan_error = 'Alamat email tersebut sudah terdaftar. Silakan gunakan email lain atau masuk.';
    } elseif ($err === 'dokumen_format_ditolak') {
        $pesan_error = 'Format dokumen kelayakan harus berupa PDF, JPG, atau PNG.';
    } elseif ($err === 'dokumen_terlalu_besar') {
        $pesan_error = 'Ukuran dokumen kelayakan maksimal 2 MB.';
    } elseif ($err === 'dokumen_mime_ditolak') {
        $pesan_error = 'Berkas dokumen kelayakan tidak valid atau bukan PDF/gambar asli.';
    } elseif ($err === 'dokumen_upload_gagal') {
        $pesan_error = 'Gagal mengunggah dokumen kelayakan. Silakan coba lagi.';
    } elseif ($err === 'csrf_tidak_valid') {
        $pesan_error = 'Sesi keamanan formulir tidak sah. Silakan muat ulang halaman dan coba lagi.';
    } elseif ($err === 'koneksi_gagal') {
        $pesan_error = 'Gagal terhubung ke basis data. Pastikan server MySQL berjalan.';
    } else {
        $pesan_error = 'Terjadi kesalahan sistem saat mendaftar. Silakan coba kembali.';
    }
} elseif ($session_flash_error !== '') {
    $pesan_error = $session_flash_error;
}

$page_title = 'Registrasi Akun - Pangkalan Pangan';
$page_wrapper_class = 'auth-page-wrapper';
$extra_css = array('auth.css?v=8');
$extra_js = array('auth.js?v=4', 'ajax_validator.js?v=2');

require_once 'includes/header.php';
?>

        <div class="auth-nav-bar">
            <a href="katalog.php" class="auth-nav-left" title="Pangkalan Pangan">
                <img src="assets/images/logo_sprout.svg" alt="Logo Pangkalan Pangan" class="auth-nav-logo-sprout" />
                <img src="assets/images/pangkalan_pangan_title.svg" alt="Pangkalan Pangan" class="auth-nav-logo-text" />
            </a>
            <ul class="auth-nav-menu">
                <li><a href="katalog.php#about">Tentang kami</a></li>
                <li><a href="katalog.php">Katalog Pangan</a></li>
                <li><a href="#help">Bantuan</a></li>
            </ul>
            <a href="katalog.php" class="btn-nav-back">Kembali ke Beranda</a>
        </div>

        <div class="auth-main-section">
            <div class="auth-card">
                <h1 class="auth-card-title">Registrasi Akun</h1>

                <?php if ($pesan_error !== ''): ?>
                    <div class="alert alert-error" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; line-height: 1.4; background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                        <?php echo escapeHtml($pesan_error); ?>
                    </div>
                <?php endif; ?>

                <div class="auth-role-toggle">
                    <button type="button" class="btn-role-tab<?php echo !$is_donor_mode ? ' active' : ''; ?>" id="btnRoleConsumer">Penerima Manfaat (Konsumen)</button>
                    <button type="button" class="btn-role-tab<?php echo $is_donor_mode ? ' active' : ''; ?>" id="btnRoleDonor">Donatur Pangan (Produsen)</button>
                </div>

                <form action="actions/auth_register.php" method="post" enctype="multipart/form-data" class="auth-form" id="registerForm">
                    <div>
                        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                        <input type="hidden" name="role" id="regRole" value="<?php echo $is_donor_mode ? 'produsen' : 'konsumen'; ?>" />
                    </div>

                    <div class="auth-form-group">
                        <label for="regFullname" class="auth-label">Nama Lengkap</label>
                        <input type="text" id="regFullname" name="fullname" class="auth-input" />
                    </div>

                    <div class="auth-form-group">
                        <label for="regUsername" class="auth-label">Username</label>
                        <input type="text" id="regUsername" name="username" class="auth-input" />
                        <span class="auth-helper-text" id="usernameFeedback" style="display: none;">Username tersedia</span>
                    </div>

                    <div class="auth-form-group">
                        <label for="regEmail" class="auth-label">Alamat Email</label>
                        <input type="text" id="regEmail" name="email" class="auth-input" />
                        <span class="auth-helper-text" id="emailFeedback" style="display: none;">Email tersedia</span>
                    </div>

                    <div class="auth-input-row">
                        <div class="auth-form-group">
                            <label for="regPassword" class="auth-label">Kata Sandi</label>
                            <input type="password" id="regPassword" name="password" class="auth-input" />
                        </div>
                        <div class="auth-form-group">
                            <label for="regConfirmPassword" class="auth-label">Konfirmasi Sandi</label>
                            <input type="password" id="regConfirmPassword" name="confirm_password" class="auth-input" />
                        </div>
                    </div>

                    <div class="auth-form-group" id="sktmUploadGroup"<?php echo $is_donor_mode ? ' style="display: none;"' : ''; ?>>
                        <label for="regDocument" class="auth-label">Dokumen Kelayakan SKTM / KKS (Opsional)</label>
                        <div class="auth-file-box">
                            <input type="file" id="regDocument" name="dokumen_kelayakan" accept="application/pdf,image/jpeg,image/png" class="auth-file-input" />
                            <span class="auth-file-name" id="regDocumentName">Format: PDF, JPG, atau PNG (Maks. 2MB)</span>
                        </div>
                        <span class="auth-doc-note">Khusus penerima manfaat untuk mempercepat verifikasi oleh admin.</span>
                    </div>

                    <div>
                        <button type="submit" class="btn-auth-submit btn-auth-teal">Daftar Akun Baru &#8594;</button>
                    </div>
                </form>

                <div class="auth-switch-footer">
                    Sudah memiliki akun? <a href="login.php" class="auth-switch-link link-terracotta">Masuk</a>
                </div>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
