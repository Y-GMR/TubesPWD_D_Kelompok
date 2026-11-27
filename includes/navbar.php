<?php
if (!isset($base_url)) {
    $base_url = '';
}
$root_dir = dirname(__DIR__);
$is_logged_in = false;
$user_display_name = 'Konsumen';
$user_role_label = 'Konsumen';
$role_raw = '';
$user_avatar = $base_url . 'assets/images/avatar_consumer.png';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $is_logged_in = true;
    if (isset($_SESSION['full_name']) && trim($_SESSION['full_name']) !== '') {
        $user_display_name = $_SESSION['full_name'];
    } elseif (isset($_SESSION['username'])) {
        $user_display_name = $_SESSION['username'];
    }
    if (isset($_SESSION['role'])) {
        $role_raw = strtolower(trim($_SESSION['role']));
        if ($role_raw === 'admin') {
            $user_role_label = 'Admin';
        } elseif ($role_raw === 'produsen') {
            $user_role_label = 'Produsen';
            $user_avatar = $base_url . 'assets/images/producer/avatar_produsen.png';
        } else {
            $user_role_label = 'Konsumen';
        }
    }
    if (isset($_SESSION['profile_image']) && trim($_SESSION['profile_image']) !== '' && $_SESSION['profile_image'] !== 'default_avatar.png') {
        $img_rel = ltrim(trim($_SESSION['profile_image']), '/');
        if (strpos($img_rel, '..') === false && file_exists($root_dir . '/' . $img_rel)) {
            $user_avatar = $base_url . $img_rel;
        }
    }
}
$help_target = 'customer_service.php';
$producer_target = file_exists($root_dir . '/producer_dashboard.php') ? 'producer_dashboard.php' : 'inventory.php';
$transactions_target = 'transactions.php';
$cart_total_qty = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cart_item) {
        if (isset($cart_item['jumlah'])) {
            $cart_total_qty += max(0, (int) $cart_item['jumlah']);
        }
    }
}
?>
    <div class="header-navigation">
        <div class="nav-container">
            <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>katalog.php" class="nav-logo-link" title="Pangkalan Pangan">
                <img src="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>assets/images/logo_sprout.svg" alt="Logo Pangkalan Pangan" class="nav-logo-img" />
            </a>
            <ul class="nav-menu-list">
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>katalog.php">Katalog</a></li>
<?php if ($is_logged_in): ?>
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>profile.php">Pengaturan</a></li>
<?php else: ?>
                <li><a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>login.php">Masuk</a></li>
<?php endif; ?>
                <li><a href="<?php echo htmlspecialchars($base_url . $help_target, ENT_QUOTES, 'UTF-8'); ?>">Bantuan</a></li>
            </ul>
            <div class="nav-cta-wrap">
<?php if ($is_logged_in): ?>
                <div class="profile-consumer-dropdown">
                    <button type="button" class="profile-consumer-pill" id="profilePillBtn" title="Profil - <?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <img src="<?php echo htmlspecialchars($user_avatar, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar Profil" class="profile-consumer-avatar" />
                        <span class="profile-consumer-info">
                            <span class="profile-consumer-name"><?php echo htmlspecialchars($user_display_name, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="profile-consumer-role"><?php echo htmlspecialchars($user_role_label, ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                        <span class="profile-consumer-caret">&#9662;</span>
                    </button>
                    <div class="profile-dropdown-content" id="profileDropdownMenu">
<?php if ($role_raw === 'admin'): ?>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>views/admin/dashboard.php" class="profile-dropdown-item">Panel Admin</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>manage_categories.php" class="profile-dropdown-item">Kelola Kategori</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>views/admin/manage_users.php" class="profile-dropdown-item">Kelola Pengguna</a>
                        <a href="<?php echo htmlspecialchars($base_url . $transactions_target, ENT_QUOTES, 'UTF-8'); ?>" class="profile-dropdown-item">Kelola Transaksi</a>
<?php elseif ($role_raw === 'produsen'): ?>
                        <a href="<?php echo htmlspecialchars($base_url . $producer_target, ENT_QUOTES, 'UTF-8'); ?>" class="profile-dropdown-item">Dashboard Produsen</a>
                        <a href="<?php echo htmlspecialchars($base_url . $transactions_target, ENT_QUOTES, 'UTF-8'); ?>" class="profile-dropdown-item">Kelola Klaim</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>inventory.php" class="profile-dropdown-item">Kelola Inventaris</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>add_food.php" class="profile-dropdown-item">Donasi Makanan Baru</a>
<?php else: ?>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>cart.php" class="profile-dropdown-item">Keranjang Klaim<?php echo $cart_total_qty > 0 ? (' (' . htmlspecialchars((string) $cart_total_qty, ENT_QUOTES, 'UTF-8') . ')') : ''; ?></a>
                        <a href="<?php echo htmlspecialchars($base_url . $transactions_target, ENT_QUOTES, 'UTF-8'); ?>" class="profile-dropdown-item">Riwayat Klaim</a>
<?php endif; ?>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>profile.php" class="profile-dropdown-item">Pengaturan Profil</a>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>customer_service.php" class="profile-dropdown-item">Pusat Bantuan</a>
                        <div class="profile-dropdown-divider"></div>
                        <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>actions/auth_logout.php" class="profile-dropdown-item profile-dropdown-logout" id="btnLogout">Keluar</a>
                    </div>
                </div>
<?php else: ?>
                <a href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>register.php?role=donor" class="btn-share-header">Saya ingin berbagi</a>
<?php endif; ?>
            </div>
        </div>
    </div>
