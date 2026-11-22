<?php
if (!isset($base_url)) {
    $base_url = '';
}
$is_logged_in = false;
$user_display_name = 'Konsumen';
$user_role_label = 'Konsumen';
$user_avatar = $base_url . 'assets/images/avatar_consumer.png';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) || isset($_SESSION['username'])) {
    $is_logged_in = true;
    if (isset($_SESSION['nama_lengkap']) && trim($_SESSION['nama_lengkap']) !== '') {
        $user_display_name = $_SESSION['nama_lengkap'];
    } elseif (isset($_SESSION['username'])) {
        $user_display_name = $_SESSION['username'];
    }
    if (isset($_SESSION['peran'])) {
        $role_raw = strtolower(trim($_SESSION['peran']));
        if ($role_raw === 'admin') {
            $user_role_label = 'Admin';
        } elseif ($role_raw === 'produsen') {
            $user_role_label = 'Produsen';
        } else {
            $user_role_label = 'Konsumen';
        }
    }
    if (isset($_SESSION['foto_profil']) && trim($_SESSION['foto_profil']) !== '' && $_SESSION['foto_profil'] !== 'default_avatar.png') {
        $user_avatar = $base_url . 'assets/uploads/' . htmlspecialchars($_SESSION['foto_profil'], ENT_QUOTES, 'UTF-8');
    }
}
?>
    <div class="header-navigation">
        <div class="nav-container">
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>index.xhtml" class="nav-logo-link" title="Pangkalan Pangan">
                <img src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/images/logo_sprout.svg" alt="Logo Pangkalan Pangan" class="nav-logo-img" />
            </a>
            <ul class="nav-menu-list">
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>food_catalogue.xhtml#about">Tentang kami</a></li>
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>profile.xhtml">Pengaturan</a></li>
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>food_catalogue.xhtml#help">Bantuan</a></li>
            </ul>
            <div class="nav-cta-wrap">
<?php if ($is_logged_in): ?>
                <div class="profile-consumer-dropdown">
                    <button type="button" class="profile-consumer-pill" id="profilePillBtn" aria-haspopup="true" aria-expanded="false" title="Profil - <?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($user_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Profil" class="profile-consumer-avatar" />
                        <span class="profile-consumer-info">
                            <span class="profile-consumer-name"><?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="profile-consumer-role"><?php echo htmlspecialchars($user_role_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                        <span class="profile-consumer-caret">&#9662;</span>
                    </button>
                    <div class="profile-dropdown-content" id="profileDropdownMenu">
<?php if (isset($_SESSION['peran']) && strtolower($_SESSION['peran']) === 'admin'): ?>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>views/admin/dashboard.php" class="profile-dropdown-item">Panel Admin</a>
<?php endif; ?>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>profile.xhtml" class="profile-dropdown-item">Pengaturan Profil</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>claim_history.xhtml" class="profile-dropdown-item">Riwayat Klaim</a>
                        <div class="profile-dropdown-divider"></div>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>index.xhtml" class="profile-dropdown-item profile-dropdown-logout" id="btnLogout">Keluar</a>
                    </div>
                </div>
<?php else: ?>
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>register.xhtml?role=donor" class="btn-share-header">Saya ingin berbagi</a>
<?php endif; ?>
            </div>
        </div>
    </div>
