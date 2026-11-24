<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

redirect_if_logged_in();

$pesan_error = '';

if (isset($_GET['error'])) {
    $err = bersihkanInput($_GET['error']);
    if ($err === 'data_tidak_lengkap') {
        $pesan_error = 'Semua kolom formulir pendaftaran wajib diisi lengkap.';
    } elseif ($err === 'email_tidak_valid') {
        $pesan_error = 'Format alamat email yang Anda masukkan tidak valid.';
    } elseif ($err === 'username_panjang_salah') {
        $pesan_error = 'Username harus terdiri dari 4 hingga 30 karakter.';
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
    } elseif ($err === 'koneksi_gagal') {
        $pesan_error = 'Gagal terhubung ke basis data. Pastikan server MySQL berjalan.';
    } else {
        $pesan_error = 'Terjadi kesalahan sistem saat mendaftar. Silakan coba kembali.';
    }
}

$page_title = 'Registrasi Akun - Pangkalan Pangan';
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
                <li><a href="katalog.php#help">Bantuan</a></li>
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
                    <button type="button" class="btn-role-tab active" id="btnRoleConsumer">Penerima Manfaat (Konsumen)</button>
                    <button type="button" class="btn-role-tab" id="btnRoleDonor">Donatur Pangan (Produsen)</button>
                </div>

                <form action="actions/auth_register.php" method="post" class="auth-form" id="registerForm">
                    <input type="hidden" name="role" id="regRole" value="konsumen" />

                    <div class="auth-form-group">
                        <label for="regFullname" class="auth-label">Nama Lengkap</label>
                        <input type="text" id="regFullname" name="fullname" class="auth-input" placeholder="Nama Lengkap" />
                    </div>

                    <div class="auth-form-group">
                        <label for="regUsername" class="auth-label">Username</label>
                        <input type="text" id="regUsername" name="username" class="auth-input" placeholder="Username" />
                        <span class="auth-helper-text" id="usernameFeedback" style="display: none;">Username tersedia</span>
                    </div>

                    <div class="auth-form-group">
                        <label for="regEmail" class="auth-label">Alamat Email</label>
                        <input type="text" id="regEmail" name="email" class="auth-input" placeholder="nama@email.com" />
                        <span class="auth-helper-text" id="emailFeedback" style="display: none;">Email tersedia</span>
                    </div>

                    <div class="auth-input-row">
                        <div class="auth-form-group">
                            <label for="regPassword" class="auth-label">Kata Sandi</label>
                            <input type="password" id="regPassword" name="password" class="auth-input" placeholder="••••••••" />
                        </div>
                        <div class="auth-form-group">
                            <label for="regConfirmPassword" class="auth-label">Konfirmasi Sandi</label>
                            <input type="password" id="regConfirmPassword" name="confirm_password" class="auth-input" placeholder="••••••••" />
                        </div>
                    </div>

                    <button type="submit" class="btn-auth-submit btn-auth-teal">Daftar Akun Baru &#8594;</button>
                </form>

                <div class="auth-switch-footer">
                    Sudah memiliki akun? <a href="login.php" class="auth-switch-link link-terracotta">Masuk</a>
                </div>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
