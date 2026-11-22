<?php
if (file_exists('../../includes/session.php')) {
    require_once '../../includes/session.php';
}
if (function_exists('require_role')) {
    require_role('admin');
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
        header('HTTP/1.1 403 Forbidden');
        header('Location: ../../login.php?error=unauthorized');
        exit();
    }
}

$conn = null;
if (file_exists('../../config/database.php')) {
    require_once '../../config/database.php';
}

$total_admin = 0;
$total_produsen = 0;
$total_konsumen = 0;
$konsumen_menunggu = 0;
$tiket_terbuka = 0;
$makanan_stok_tipis = 0;
$total_transaksi = 0;
$daftar_stok_tipis = array();

if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT peran, COUNT(*) AS jumlah FROM pengguna GROUP BY peran");
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            if ($row['peran'] === 'admin') {
                $total_admin = (int) $row['jumlah'];
            } elseif ($row['peran'] === 'produsen') {
                $total_produsen = (int) $row['jumlah'];
            } elseif ($row['peran'] === 'konsumen') {
                $total_konsumen = (int) $row['jumlah'];
            }
        }
        mysqli_stmt_close($stmt);
    }

    $status_menunggu = 'menunggu';
    $peran_konsumen = 'konsumen';
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jumlah FROM pengguna WHERE peran = ? AND status_verifikasi = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ss", $peran_konsumen, $status_menunggu);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $konsumen_menunggu = (int) $row['jumlah'];
        }
        mysqli_stmt_close($stmt);
    }

    $status_terbuka = 'terbuka';
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jumlah FROM tiket_bantuan WHERE status = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $status_terbuka);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $tiket_terbuka = (int) $row['jumlah'];
        }
        mysqli_stmt_close($stmt);
    }

    $batas_stok = 5;
    $stmt = mysqli_prepare($conn, "SELECT id, nama_makanan, stok FROM makanan WHERE stok <= ? ORDER BY stok ASC LIMIT 5");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $batas_stok);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_stok_tipis[] = $row;
        }
        $makanan_stok_tipis = count($daftar_stok_tipis);
        mysqli_stmt_close($stmt);
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS jumlah FROM transaksi");
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $total_transaksi = (int) $row['jumlah'];
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Dashboard Admin - Tubes PWD</title>
    <link rel="stylesheet" type="text/css" href="../../assets/css/admin.css" />
</head>
<body>
    <div id="adminWrapper" class="admin-wrapper">
        <div id="adminSidebar" class="admin-sidebar">
            <h2 class="sidebar-title">Panel Admin</h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="manage_users.php">Verifikasi &amp; CS</a></li>
            </ul>
        </div>

        <div id="adminMain" class="admin-main">
            <div class="page-header">
                <h1>Dashboard Analitik Admin</h1>
                <p class="page-subtitle">Ringkasan data pengguna, verifikasi dokumen, tiket bantuan, dan stok makanan.</p>
            </div>

            <?php if (!$conn): ?>
                <div class="alert alert-warning">
                    Koneksi database belum aktif (menunggu config/database.php dari Role 1). Menampilkan data placeholder.
                </div>
            <?php endif; ?>

            <div class="kpi-grid">
                <div class="kpi-card">
                    <span class="kpi-label">Total Konsumen</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $total_konsumen, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Total Produsen</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $total_produsen, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Total Admin</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $total_admin, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Menunggu Verifikasi</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $konsumen_menunggu, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Tiket CS Terbuka</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $tiket_terbuka, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <div class="kpi-card">
                    <span class="kpi-label">Total Transaksi</span>
                    <strong class="kpi-value"><?php echo htmlspecialchars((string) $total_transaksi, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            </div>

            <div class="section-box">
                <h2>Peringatan Stok Makanan Menipis (Stok &lt;= 5)</h2>
                <table class="table-modern table-striped">
                    <thead>
                        <tr class="table-header">
                            <th>ID</th>
                            <th>Nama Makanan</th>
                            <th>Sisa Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_stok_tipis)): ?>
                            <tr>
                                <td colspan="3">Belum ada data makanan dengan stok menipis.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_stok_tipis as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $item['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($item['nama_makanan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars((string) $item['stok'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
