<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

redirect_if_logged_in();

$pesan_error = '';
$pesan_sukses = '';

if (isset($_GET['error'])) {
    $err = bersihkanInput($_GET['error']);
    if ($err === 'data_tidak_lengkap') {
        $pesan_error = 'Harap masukkan username atau email beserta kata sandi.';
    } elseif ($err === 'kredensial_salah') {
        $pesan_error = 'Username, email, atau kata sandi tidak valid.';
    } elseif ($err === 'login_required') {
        $pesan_error = 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.';
    } elseif ($err === 'unauthorized') {
        $pesan_error = 'Anda tidak memiliki hak akses untuk halaman tersebut.';
    } elseif ($err === 'koneksi_gagal') {
        $pesan_error = 'Gagal terhubung ke basis data. Pastikan server MySQL berjalan.';
    } else {
        $pesan_error = 'Terjadi kesalahan sistem. Silakan coba kembali.';
    }
}

if (isset($_GET['pesan'])) {
    $psn = bersihkanInput($_GET['pesan']);
    if ($psn === 'logout_berhasil') {
        $pesan_sukses = 'Anda telah berhasil keluar dari sistem.';
    } elseif ($psn === 'registrasi_berhasil') {
        $pesan_sukses = 'Pendaftaran akun berhasil! Silakan masuk dengan akun Anda.';
    }
}

$page_title = 'Login Akun - Pangkalan Pangan';
$page_wrapper_class = 'auth-page-wrapper';
$extra_css = array('auth.css?v=6');
$extra_js = array('auth.js');

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
                <li><a href="customer_service.xhtml">Bantuan</a></li>
            </ul>
            <a href="katalog.php" class="btn-nav-back">Kembali ke Beranda</a>
        </div>

        <div class="auth-main-section">
            <div class="auth-card">
                <h1 class="auth-card-title">Login Akun</h1>

                <?php if ($pesan_error !== ''): ?>
                    <div class="alert alert-error" style="padding: 12px 16px; border-radius: 8px; 
                    margin-bottom: 20px; font-size: 13px; line-height: 1.4; background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; text-align: center;">
                        <?php echo escapeHtml($pesan_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_sukses !== ''): ?>
                    <div class="alert alert-success" style="padding: 12px 16px; border-radius: 8px; 
                    margin-bottom: 20px; font-size: 13px; line-height: 1.4; background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; text-align: center;">
                        <?php echo escapeHtml($pesan_sukses); ?>
                    </div>
                <?php endif; ?>

                <form action="actions/auth_login.php" method="post" class="auth-form" id="loginForm">
                    <div class="auth-form-group">
                        <label for="loginUsername" class="auth-label">Username</label>
                        <input type="text" id="loginUsername" name="username" class="auth-input" placeholder="Username" />
                    </div>

                    <div class="auth-form-group">
                        <label for="loginEmail" class="auth-label">Alamat Email</label>
                        <input type="text" id="loginEmail" name="email" class="auth-input" placeholder="Email" />
                    </div>

                    <div class="auth-form-group">
                        <label for="loginPassword" class="auth-label">Kata Sandi</label>
                        <div class="auth-password-wrap">
                            <input type="password" id="loginPassword" name="password" class="auth-input" placeholder="Kata Sandi" />
                            <button type="button" class="btn-toggle-eye" id="btnTogglePassword" title="Lihat kata sandi">
                                <img src="assets/images/icon_eye.svg" alt="Lihat kata sandi" />
                            </button>
                        </div>
                    </div>

                    <div class="auth-options-row">
                        <label class="auth-checkbox-label">
                            <input type="checkbox" name="remember" />
                            <span>Ingat saya di perangkat ini</span>
                        </label>
                        <a href="customer_service.xhtml" class="auth-forgot-link">Lupa kata sandi?</a>
                    </div>

                    <button type="submit" class="btn-auth-submit btn-auth-terracotta">Masuk Sekarang &#8594;</button>
                </form>

                <div class="auth-switch-footer">
                    Belum memiliki akun? <a href="register.php" class="auth-switch-link link-teal">Daftar</a>
                </div>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
