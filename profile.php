<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$user_id = (int) $_SESSION['user_id'];
$pengguna = ambilPenggunaById($conn, $user_id);

if (!$pengguna) {
    $pengguna = array(
        'nama_lengkap' => isset($_SESSION['full_name']) ? $_SESSION['full_name'] : '',
        'username' => isset($_SESSION['username']) ? $_SESSION['username'] : '',
        'email' => '',
        'foto_profil' => isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default_avatar.png',
        'peran' => isset($_SESSION['role']) ? $_SESSION['role'] : 'konsumen',
        'status_verifikasi' => 'menunggu',
        'dibuat_pada' => '-'
    );
}

$pesan_error = '';
$pesan_sukses = '';

if (isset($_GET['error'])) {
    $err = bersihkanInput($_GET['error']);
    if ($err === 'data_tidak_lengkap') {
        $pesan_error = 'Nama lengkap, username, dan email wajib diisi.';
    } elseif ($err === 'email_tidak_valid') {
        $pesan_error = 'Format alamat email tidak valid.';
    } elseif ($err === 'username_panjang_salah') {
        $pesan_error = 'Username harus terdiri dari 4 hingga 30 karakter.';
    } elseif ($err === 'password_terlalu_pendek') {
        $pesan_error = 'Kata sandi baru minimal harus 8 karakter.';
    } elseif ($err === 'password_tidak_memenuhi_syarat') {
        $pesan_error = 'Kata sandi baru harus mengandung kombinasi huruf dan angka.';
    } elseif ($err === 'password_tidak_cocok') {
        $pesan_error = 'Konfirmasi kata sandi baru tidak cocok.';
    } elseif ($err === 'username_sudah_dipakai') {
        $pesan_error = 'Username tersebut sudah digunakan oleh akun lain.';
    } elseif ($err === 'email_sudah_dipakai') {
        $pesan_error = 'Email tersebut sudah digunakan oleh akun lain.';
    } elseif ($err === 'koneksi_gagal') {
        $pesan_error = 'Gagal terhubung ke basis data.';
    } else {
        $pesan_error = 'Terjadi kesalahan sistem saat memperbarui profil.';
    }
}

if (isset($_GET['pesan'])) {
    $psn = bersihkanInput($_GET['pesan']);
    if ($psn === 'profil_berhasil_diperbarui') {
        $pesan_sukses = 'Perubahan data profil dan akun berhasil disimpan.';
    }
}

$page_title = 'Pengaturan Profil - Pangkalan Pangan';
$page_wrapper_class = 'profile-page-wrapper';
$extra_css = array('profile.css?v=6');
$extra_js = array('profile.js');

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
            <a href="katalog.php" class="btn-nav-back">Kembali ke Katalog</a>
        </div>

        <div class="profile-main-section">
            <div class="profile-container">
                <h1 class="profile-page-title">Pengaturan<br />Profil</h1>

                <?php if ($pesan_error !== ''): ?>
                    <div class="alert alert-error" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; line-height: 1.4; background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">
                        <?php echo escapeHtml($pesan_error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($pesan_sukses !== ''): ?>
                    <div class="alert alert-success" style="padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; line-height: 1.4; background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
                        <?php echo escapeHtml($pesan_sukses); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-content-grid">

                    <div class="profile-left-col">
                        <div class="profile-card profile-photo-card">
                            <h2 class="profile-card-title text-center">Foto &amp; Status Akun</h2>

                            <div class="profile-avatar-wrapper">
                                <div class="profile-large-avatar-circle">
                                    <?php if (!empty($pengguna['foto_profil']) && file_exists($pengguna['foto_profil'])): ?>
                                        <img src="<?php echo escapeHtml($pengguna['foto_profil']); ?>" alt="Foto Profil" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;" />
                                    <?php else: ?>
                                        <svg class="profile-large-avatar-svg" viewBox="0 0 80 80" width="80" height="80" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="40" cy="28" r="16" fill="#D1D5DB" />
                                            <path d="M16 66C16 52.7452 26.7452 42 40 42C53.2548 42 64 52.7452 64 66V70H16V66Z" fill="#D1D5DB" />
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <span class="upload-photo-hint">Format: JPG, PNG maks 2MB</span>
                            </div>

                            <hr class="profile-divider" />

                            <ul class="profile-status-list">
                                <li class="profile-status-item">
                                    <span class="status-item-label">Peran Akun</span>
                                    <span class="status-item-val val-dark"><?php echo ucfirst(escapeHtml($pengguna['peran'])); ?></span>
                                </li>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Status Verifikasi</span>
                                    <span class="status-item-val val-green"><?php echo ucfirst(escapeHtml($pengguna['status_verifikasi'])); ?></span>
                                </li>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Bergabung Sejak</span>
                                    <span class="status-item-val val-dark"><?php echo escapeHtml(substr($pengguna['dibuat_pada'], 0, 10)); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="profile-right-col">
                        <div class="profile-card profile-form-card">
                            <h2 class="profile-card-title">Pengaturan Data &amp; Keamanan Akun</h2>
                            <hr class="profile-divider" />

                            <form action="actions/auth_profile_update.php" method="post" enctype="multipart/form-data" class="profile-edit-form" id="profileEditForm">
                                <div class="form-group-full">
                                    <label for="inputNamaLengkap" class="profile-form-label">Nama Lengkap</label>
                                    <input type="text" id="inputNamaLengkap" name="nama_lengkap" class="profile-form-input" value="<?php echo escapeHtml($pengguna['nama_lengkap']); ?>" placeholder="Nama Lengkap" />
                                </div>

                                <div class="form-row-2col">
                                    <div class="form-group-col">
                                        <label for="inputUsername" class="profile-form-label">Nama Pengguna (Username)</label>
                                        <input type="text" id="inputUsername" name="username" class="profile-form-input" value="<?php echo escapeHtml($pengguna['username']); ?>" placeholder="Nama Pengguna (Username)" />
                                    </div>
                                    <div class="form-group-col">
                                        <label for="inputEmail" class="profile-form-label">Alamat Email</label>
                                        <input type="email" id="inputEmail" name="email" class="profile-form-input" value="<?php echo escapeHtml($pengguna['email']); ?>" placeholder="Alamat Email" />
                                    </div>
                                </div>

                                <div class="form-group-full" style="margin-top: 10px;">
                                    <label for="inputFotoProfil" class="profile-form-label">Unggah Foto Profil Baru</label>
                                    <input type="file" id="inputFotoProfil" name="foto_profil" accept="image/jpeg,image/png" class="profile-form-input" />
                                </div>

                                <div class="form-row-2col">
                                    <div class="form-group-col">
                                        <label for="inputPasswordBaru" class="profile-form-label">Kata Sandi Baru (Opsional)</label>
                                        <input type="password" id="inputPasswordBaru" name="password_baru" class="profile-form-input" placeholder="Kosongkan jika tidak ingin mengubah sandi" />
                                    </div>
                                    <div class="form-group-col">
                                        <label for="inputKonfirmasiSandi" class="profile-form-label">Konfirmasi Kata Sandi Baru</label>
                                        <input type="password" id="inputKonfirmasiSandi" name="konfirmasi_sandi" class="profile-form-input" placeholder="Ulangi kata sandi baru" />
                                    </div>
                                </div>

                                <div class="profile-form-bottom-bar">
                                    <div class="profile-btn-group">
                                        <button type="submit" class="btn-profile-submit">Simpan Perubahan</button>
                                        <a href="katalog.php" class="btn-profile-cancel">Batal</a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>

<?php
require_once 'includes/footer.php';
?>
