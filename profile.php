<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

require_login();

$user_id = (int) $_SESSION['user_id'];
$pengguna = ambilPenggunaById($conn, $user_id);

if (!$pengguna) {
    hapusSesiPengguna();
    header('Location: login.php?error=login_required');
    exit();
}
sinkronSesiPengguna($pengguna);

$peran_pengguna = isset($pengguna['peran']) ? $pengguna['peran'] : 'konsumen';
$status_verif = isset($pengguna['status_verifikasi']) ? $pengguna['status_verifikasi'] : 'menunggu';
$catatan_verif = isset($pengguna['catatan_verifikasi']) ? trim((string) $pengguna['catatan_verifikasi']) : '';
$dokumen_raw = isset($pengguna['dokumen_kelayakan']) ? trim((string) $pengguna['dokumen_kelayakan']) : '';
$dokumen_rel = '';
if ($dokumen_raw !== '' && strpos($dokumen_raw, '..') === false) {
    $kandidat_doc = (strpos($dokumen_raw, '/') === false) ? ('assets/uploads/documents/' . $dokumen_raw) : $dokumen_raw;
    if (file_exists($kandidat_doc)) {
        $dokumen_rel = $kandidat_doc;
    }
}

$foto_tampil = ($peran_pengguna === 'produsen')
    ? 'assets/images/producer/avatar_produsen.png'
    : 'assets/images/avatar_consumer.png';
if (!empty($pengguna['foto_profil']) && $pengguna['foto_profil'] !== 'default_avatar.png' && strpos($pengguna['foto_profil'], '..') === false) {
    $kandidat_foto = (strpos($pengguna['foto_profil'], '/') === false) ? ('assets/uploads/avatars/' . $pengguna['foto_profil']) : $pengguna['foto_profil'];
    if (file_exists($kandidat_foto)) {
        $foto_tampil = $kandidat_foto;
    }
}

$sisa_kuota_harian = 25;
$total_riwayat_klaim = 0;
if ($conn && $peran_pengguna === 'konsumen') {
    $stmt_kuota = mysqli_prepare($conn, "SELECT COALESCE(SUM(dt.jumlah), 0) AS total_porsi FROM transaksi t INNER JOIN detail_transaksi dt ON t.id = dt.transaksi_id WHERE t.konsumen_id = ? AND DATE(t.dibuat_pada) = CURDATE() AND t.status != 'dibatalkan'");
    if ($stmt_kuota) {
        mysqli_stmt_bind_param($stmt_kuota, "i", $user_id);
        mysqli_stmt_execute($stmt_kuota);
        $res_k = mysqli_stmt_get_result($stmt_kuota);
        if ($row_k = mysqli_fetch_assoc($res_k)) {
            $sisa_kuota_harian = max(0, 25 - (int) $row_k['total_porsi']);
        }
        mysqli_stmt_close($stmt_kuota);
    }

    $stmt_riw = mysqli_prepare($conn, "SELECT COUNT(*) AS total_klaim FROM transaksi WHERE konsumen_id = ?");
    if ($stmt_riw) {
        mysqli_stmt_bind_param($stmt_riw, "i", $user_id);
        mysqli_stmt_execute($stmt_riw);
        $res_r = mysqli_stmt_get_result($stmt_riw);
        if ($row_r = mysqli_fetch_assoc($res_r)) {
            $total_riwayat_klaim = (int) $row_r['total_klaim'];
        }
        mysqli_stmt_close($stmt_riw);
    }
}

$pesan_error = '';
$pesan_sukses = '';

if (isset($_GET['error'])) {
    $err = bersihkanInput($_GET['error']);
    if ($err === 'data_tidak_lengkap') {
        $pesan_error = 'Nama lengkap, username, dan email wajib diisi.';
    } elseif ($err === 'nama_tidak_valid') {
        $pesan_error = 'Nama lengkap harus terdiri dari 2 hingga 100 karakter.';
    } elseif ($err === 'email_tidak_valid') {
        $pesan_error = 'Format alamat email tidak valid.';
    } elseif ($err === 'username_panjang_salah') {
        $pesan_error = 'Username harus terdiri dari 4 hingga 30 karakter.';
    } elseif ($err === 'username_karakter_tidak_valid') {
        $pesan_error = 'Username hanya boleh berisi huruf, angka, dan garis bawah (_).';
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
    } elseif ($err === 'foto_format_ditolak') {
        $pesan_error = 'Format foto profil harus berupa JPG, PNG, atau WEBP.';
    } elseif ($err === 'foto_terlalu_besar') {
        $pesan_error = 'Ukuran berkas foto profil maksimal 2 MB.';
    } elseif ($err === 'foto_mime_ditolak') {
        $pesan_error = 'Berkas foto profil tidak valid atau bukan gambar asli.';
    } elseif ($err === 'foto_upload_gagal') {
        $pesan_error = 'Gagal mengunggah foto profil baru.';
    } elseif ($err === 'dokumen_format_ditolak') {
        $pesan_error = 'Format dokumen kelayakan harus berupa PDF, JPG, atau PNG.';
    } elseif ($err === 'dokumen_terlalu_besar') {
        $pesan_error = 'Ukuran berkas dokumen kelayakan maksimal 2 MB.';
    } elseif ($err === 'dokumen_mime_ditolak') {
        $pesan_error = 'Berkas dokumen kelayakan tidak valid atau bukan PDF/gambar asli.';
    } elseif ($err === 'dokumen_upload_gagal') {
        $pesan_error = 'Gagal mengunggah dokumen kelayakan.';
    } elseif ($err === 'koneksi_gagal') {
        $pesan_error = 'Gagal terhubung ke basis data.';
    } elseif ($err === 'csrf_tidak_valid') {
        $pesan_error = 'Sesi keamanan formulir tidak sah. Silakan muat ulang halaman dan coba lagi.';
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

$status_class = 'val-amber';
if ($status_verif === 'disetujui') {
    $status_class = 'val-green';
} elseif ($status_verif === 'ditolak') {
    $status_class = 'val-danger';
}

$beranda_kembali = 'katalog.php';
if ($peran_pengguna === 'admin') {
    $beranda_kembali = 'views/admin/dashboard.php';
} elseif ($peran_pengguna === 'produsen') {
    $beranda_kembali = 'producer_dashboard.php';
}

$page_title = 'Pengaturan Profil - Pangkalan Pangan';
$page_wrapper_class = 'profile-page-wrapper';
$extra_css = array('profile.css?v=9');
$extra_js = array('image_preview.js', 'profile.js?v=3', 'ajax_validator.js?v=2');

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
                <li><a href="<?php echo ($peran_pengguna === 'admin') ? 'views/admin/manage_users.php#antrean-cs' : 'customer_service.php'; ?>"><?php echo ($peran_pengguna === 'admin') ? 'Verifikasi &amp; CS' : 'Bantuan'; ?></a></li>
                <li><a href="actions/auth_logout.php">Keluar</a></li>
            </ul>
            <a href="<?php echo escapeHtml($beranda_kembali); ?>" class="btn-nav-back">Kembali ke Beranda</a>
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
                                    <img src="<?php echo escapeHtml($foto_tampil); ?>" alt="Foto Profil" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover; display: block;" />
                                </div>
                                <button type="button" class="btn-upload-photo">Unggah Foto Baru</button>
                                <span class="upload-photo-hint">Format: JPG, PNG, WEBP maks 2MB</span>
                            </div>

                            <hr class="profile-divider" />

                            <ul class="profile-status-list">
                                <li class="profile-status-item">
                                    <span class="status-item-label">Peran Akun</span>
                                    <span class="status-item-val val-dark"><?php echo escapeHtml(ucfirst($peran_pengguna)); ?></span>
                                </li>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Status Verifikasi</span>
                                    <span class="status-item-val <?php echo escapeHtml($status_class); ?>" id="profileSktmStatusVal"><?php echo escapeHtml(ucfirst($status_verif)); ?></span>
                                </li>
<?php if ($peran_pengguna === 'konsumen'): ?>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Sisa Kuota Hari Ini</span>
                                    <span class="status-item-val val-amber"><?php echo escapeHtml((string) $sisa_kuota_harian); ?> porsi</span>
                                </li>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Total Riwayat Klaim</span>
                                    <span class="status-item-val val-dark"><?php echo escapeHtml((string) $total_riwayat_klaim); ?> transaksi</span>
                                </li>
<?php endif; ?>
                                <li class="profile-status-item">
                                    <span class="status-item-label">Bergabung Sejak</span>
                                    <span class="status-item-val val-dark"><?php echo escapeHtml(substr((string) (isset($pengguna['dibuat_pada']) ? $pengguna['dibuat_pada'] : ''), 0, 10)); ?></span>
                                </li>
                            </ul>

                            <div class="profile-sktm-verified-box"<?php
                                if ($status_verif === 'ditolak') {
                                    echo ' style="background-color: #fee2e2; border-color: #fecaca; color: #991b1b;"';
                                } elseif ($status_verif === 'menunggu') {
                                    echo ' style="background-color: #fef3c7; border-color: #fde68a; color: #92400e;"';
                                }
                            ?>>
<?php if ($status_verif === 'disetujui'): ?>
                                Akun telah diverifikasi<?php echo $catatan_verif !== '' ? (': ' . escapeHtml($catatan_verif)) : '.'; ?>
<?php elseif ($status_verif === 'ditolak'): ?>
                                Verifikasi ditolak<?php echo $catatan_verif !== '' ? (': ' . escapeHtml($catatan_verif)) : ($peran_pengguna === 'konsumen' ? '. Silakan unggah ulang dokumen yang valid.' : '. Hubungi pusat bantuan untuk klarifikasi.'); ?>
<?php elseif ($peran_pengguna === 'konsumen'): ?>
                                Akun menunggu verifikasi admin<?php echo $dokumen_raw === '' ? '. Unggah dokumen SKTM/KKS di formulir.' : ' (dokumen sudah diunggah).'; ?>
<?php else: ?>
                                Akun <?php echo escapeHtml($peran_pengguna); ?> sedang menunggu peninjauan dan persetujuan admin.
<?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-right-col">
                        <div class="profile-card profile-form-card">
                            <h2 class="profile-card-title">Pengaturan Data &amp; Keamanan Akun</h2>
                            <hr class="profile-divider" />

                            <form action="actions/auth_profile_update.php" method="post" enctype="multipart/form-data" class="profile-edit-form" id="profileEditForm">
                                <div>
                                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                                </div>
                                <div class="form-group-full">
                                    <label for="inputNamaLengkap" class="profile-form-label">Nama Lengkap</label>
                                    <input type="text" id="inputNamaLengkap" name="nama_lengkap" class="profile-form-input" value="<?php echo escapeHtml($pengguna['nama_lengkap']); ?>" />
                                </div>

                                <div class="form-row-2col">
                                    <div class="form-group-col">
                                        <label for="inputUsername" class="profile-form-label">Nama Pengguna (Username)</label>
                                        <input type="text" id="inputUsername" name="username" class="profile-form-input" value="<?php echo escapeHtml($pengguna['username']); ?>" />
                                    </div>
                                    <div class="form-group-col">
                                        <label for="inputEmail" class="profile-form-label">Alamat Email</label>
                                        <input type="text" id="inputEmail" name="email" class="profile-form-input" value="<?php echo escapeHtml($pengguna['email']); ?>" />
                                    </div>
                                </div>

                                <div class="form-group-full" style="margin-top: 4px;">
                                    <label for="inputFotoProfil" class="profile-form-label">Unggah Foto Profil Baru</label>
                                    <input type="file" id="inputFotoProfil" name="foto_profil" accept="image/jpeg,image/png,image/webp" class="profile-form-input" style="height: auto; padding: 10px 14px;" />
                                </div>

<?php if ($peran_pengguna === 'konsumen'): ?>
                                <div class="form-group-full">
                                    <label for="inputDokumenKelayakan" class="profile-form-label">Dokumen Kelayakan Penerima Manfaat (SKTM / KKS)</label>
                                    <div class="profile-doc-upload-box">
                                        <input type="file" id="inputDokumenKelayakan" name="dokumen_kelayakan" accept="application/pdf,image/jpeg,image/png" class="profile-doc-file-input" />
                                        <span class="profile-doc-file-name" id="profileDocFileName">
<?php if ($dokumen_rel !== ''): ?>
                                            Berkas tersimpan: <a href="actions/view_document.php?id=<?php echo escapeHtml((string) ((int) $pengguna['id'])); ?>"><?php echo escapeHtml(basename($dokumen_rel)); ?></a>
<?php elseif ($dokumen_raw !== ''): ?>
                                            Berkas tercatat: <?php echo escapeHtml($dokumen_raw); ?>
<?php else: ?>
                                            Belum ada berkas dipilih (PDF, JPG, atau PNG maks. 2MB)
<?php endif; ?>
                                        </span>
                                        <div class="profile-doc-preview-wrap" id="profileDocPreviewWrap" style="display: none;">
                                            <img src="assets/images/avatar_consumer.png" alt="Pratinjau Dokumen Kelayakan" class="profile-doc-preview-img" id="profileDocPreviewImg" style="display: none;" />
                                        </div>
                                    </div>
                                    <span class="profile-doc-hint">Unggah foto atau pindai dokumen SKTM/KKS untuk proses verifikasi kelayakan akun oleh admin.</span>
                                </div>
<?php endif; ?>

                                <div class="form-row-2col">
                                    <div class="form-group-col">
                                        <label for="inputPasswordBaru" class="profile-form-label">Kata Sandi Baru (Opsional)</label>
                                        <input type="password" id="inputPasswordBaru" name="password_baru" class="profile-form-input" />
                                    </div>
                                    <div class="form-group-col">
                                        <label for="inputKonfirmasiSandi" class="profile-form-label">Konfirmasi Kata Sandi Baru</label>
                                        <input type="password" id="inputKonfirmasiSandi" name="konfirmasi_sandi" class="profile-form-input" />
                                    </div>
                                </div>

                                <div class="profile-form-bottom-bar">
                                    <div class="profile-btn-group">
                                        <button type="submit" class="btn-profile-submit">Simpan Perubahan</button>
                                        <a href="<?php echo escapeHtml($beranda_kembali); ?>" class="btn-profile-cancel">Batal</a>
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
