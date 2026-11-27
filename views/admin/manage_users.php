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

$pesan_sukses = '';
$pesan_error = '';
if (isset($_SESSION['flash_success'])) {
    $pesan_sukses = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $pesan_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
} elseif (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $pesan_error = 'Anda tidak memiliki izin untuk mengakses halaman tersebut.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $aksi = isset($_POST['aksi']) ? $_POST['aksi'] : '';

    if ($aksi === 'verifikasi_pengguna') {
        $pengguna_id = isset($_POST['pengguna_id']) ? (int) $_POST['pengguna_id'] : 0;
        $status_baru = isset($_POST['status_verifikasi']) ? $_POST['status_verifikasi'] : '';
        $catatan = isset($_POST['catatan_verifikasi']) ? trim($_POST['catatan_verifikasi']) : '';

        if ($pengguna_id === (int) $_SESSION['user_id']) {
            $_SESSION['flash_error'] = 'Anda tidak dapat mengubah status verifikasi akun Anda sendiri.';
        } elseif ($pengguna_id > 0 && ($status_baru === 'disetujui' || $status_baru === 'ditolak' || $status_baru === 'menunggu')) {
            $stmt = mysqli_prepare($conn, "UPDATE pengguna SET status_verifikasi = ?, catatan_verifikasi = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssi", $status_baru, $catatan, $pengguna_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_success'] = 'Status verifikasi pengguna berhasil diperbarui.';
                } else {
                    $_SESSION['flash_error'] = 'Gagal memperbarui status verifikasi.';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['flash_error'] = 'Data verifikasi tidak valid.';
        }
    } elseif ($aksi === 'balas_tiket') {
        $tiket_id = isset($_POST['tiket_id']) ? (int) $_POST['tiket_id'] : 0;
        $balasan = isset($_POST['balasan_admin']) ? trim($_POST['balasan_admin']) : '';
        $status_selesai = 'selesai';

        if ($tiket_id > 0 && $balasan !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE tiket_bantuan SET balasan_admin = ?, status = ? WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ssi", $balasan, $status_selesai, $tiket_id);
                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['flash_success'] = 'Balasan tiket bantuan berhasil disimpan.';
                } else {
                    $_SESSION['flash_error'] = 'Gagal menyimpan balasan tiket.';
                }
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['flash_error'] = 'Balasan tiket tidak boleh kosong.';
        }
    } else {
        $_SESSION['flash_error'] = 'Aksi tidak dikenali.';
    }

    header('Location: manage_users.php');
    exit();
}

$daftar_pengguna = array();
$daftar_tiket = array();

if ($conn) {
    $stmt = mysqli_prepare($conn, "SELECT id, username, nama_lengkap, peran, dokumen_kelayakan, status_verifikasi, catatan_verifikasi FROM pengguna ORDER BY id DESC");
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_pengguna[] = $row;
        }
        mysqli_stmt_close($stmt);
    }

    $sql_tiket = "SELECT t.id, t.kode_transaksi, t.pesan, t.balasan_admin, t.status, p.username "
               . "FROM tiket_bantuan t "
               . "INNER JOIN pengguna p ON t.pengguna_id = p.id "
               . "ORDER BY t.id DESC";
    $stmt = mysqli_prepare($conn, $sql_tiket);
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $daftar_tiket[] = $row;
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
    <title>Kelola Pengguna &amp; CS - Tubes PWD</title>
    <link rel="stylesheet" type="text/css" href="../../assets/css/admin.css" />
</head>
<body>
    <div id="adminWrapper" class="admin-wrapper">
        <div id="adminSidebar" class="admin-sidebar">
            <h2 class="sidebar-title">Panel Admin</h2>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php" class="active">Verifikasi &amp; CS</a></li>
                <li><a href="../../manage_categories.php">Kelola Kategori</a></li>
                <li><a href="../../transactions.php">Kelola Transaksi</a></li>
                <li><a href="../../katalog.php">Lihat Katalog</a></li>
                <li><a href="../../profile.php">Profil Saya</a></li>
                <li><a href="../../actions/auth_logout.php">Keluar</a></li>
            </ul>
        </div>

        <div id="adminMain" class="admin-main">
            <div class="page-header">
                <h1>Verifikasi Dokumen Pengguna &amp; Layanan Konsumen</h1>
                <p class="page-subtitle">Peninjauan dokumen kelayakan (SKTM/KKS/DTSEN/Slip Gaji) dan pengelolaan tiket bantuan.</p>
            </div>

            <?php if (!$conn): ?>
                <div class="alert alert-warning">
                    Koneksi database belum aktif. Menampilkan tabel kosong.
                </div>
            <?php endif; ?>

            <?php if ($pesan_sukses !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($pesan_sukses, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($pesan_error !== ''): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($pesan_error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="section-box">
                <h2>Daftar Pengguna &amp; Verifikasi Dokumen Kelayakan</h2>
                <div style="overflow-x: auto;">
                <table class="table-modern table-striped">
                    <thead>
                        <tr class="table-header">
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Peran</th>
                            <th>Dokumen</th>
                            <th>Status</th>
                            <th>Tinjauan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_pengguna)): ?>
                            <tr>
                                <td colspan="6">Belum ada data pengguna.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_pengguna as $user): ?>
                                <?php
                                $doc_val = trim((string) $user['dokumen_kelayakan']);
                                $doc_href = '';
                                if ($doc_val !== '') {
                                    $doc_href = (strpos($doc_val, 'assets/') === 0) ? ('../../' . $doc_val) : ('../../assets/uploads/documents/' . $doc_val);
                                }
                                $st_ver = isset($user['status_verifikasi']) ? $user['status_verifikasi'] : 'menunggu';
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($user['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($user['peran'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($doc_href !== ''): ?>
                                            <a href="<?php echo htmlspecialchars($doc_href, ENT_QUOTES, 'UTF-8'); ?>">Lihat Dokumen</a>
                                        <?php else: ?>
                                            <span>Belum ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge"><?php echo htmlspecialchars($st_ver, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <?php if ((int) $user['id'] === (int) $_SESSION['user_id']): ?>
                                            <span>Akun Admin Aktif</span>
                                        <?php else: ?>
                                        <form method="post" action="manage_users.php" class="inline-form">
                                            <div>
                                                <input type="hidden" name="aksi" value="verifikasi_pengguna" />
                                                <input type="hidden" name="pengguna_id" value="<?php echo htmlspecialchars((string) $user['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                                                <input type="text" name="catatan_verifikasi" class="form-control" value="<?php echo htmlspecialchars((string) $user['catatan_verifikasi'], ENT_QUOTES, 'UTF-8'); ?>" />
                                                <select name="status_verifikasi" class="form-control">
                                                    <option value="menunggu"<?php if ($st_ver === 'menunggu') echo ' selected="selected"'; ?>>Menunggu</option>
                                                    <option value="disetujui"<?php if ($st_ver === 'disetujui') echo ' selected="selected"'; ?>>Setujui</option>
                                                    <option value="ditolak"<?php if ($st_ver === 'ditolak') echo ' selected="selected"'; ?>>Tolak</option>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="section-box">
                <h2>Antrean Tiket Bantuan (Customer Service)</h2>
                <div style="overflow-x: auto;">
                <table class="table-modern table-striped">
                    <thead>
                        <tr class="table-header">
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Kode Transaksi</th>
                            <th>Pesan</th>
                            <th>Status</th>
                            <th>Balasan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($daftar_tiket)): ?>
                            <tr>
                                <td colspan="6">Belum ada tiket bantuan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($daftar_tiket as $tiket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string) $tiket['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($tiket['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) $tiket['kode_transaksi'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($tiket['pesan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="badge"><?php echo htmlspecialchars($tiket['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td>
                                        <form method="post" action="manage_users.php" class="inline-form">
                                            <div>
                                                <input type="hidden" name="aksi" value="balas_tiket" />
                                                <input type="hidden" name="tiket_id" value="<?php echo htmlspecialchars((string) $tiket['id'], ENT_QUOTES, 'UTF-8'); ?>" />
                                                <input type="text" name="balasan_admin" class="form-control" value="<?php echo htmlspecialchars((string) $tiket['balasan_admin'], ENT_QUOTES, 'UTF-8'); ?>" />
                                                <button type="submit" class="btn btn-success">Kirim &amp; Selesai</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
