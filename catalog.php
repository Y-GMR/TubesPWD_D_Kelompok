<?php
// Katalog bersifat publik (tamu dapat melihat tanpa login; login wajib saat klaim item)
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Koneksi database (placeholder jika Role 1 belum membuat config/database.php)
$conn = null;
if (file_exists('config/database.php')) {
    require_once 'config/database.php';
}

// Ambil parameter GET untuk pencarian dan filter kategori
$kata_kunci = isset($_GET['q']) ? trim($_GET['q']) : '';
$kategori_id = isset($_GET['kategori_id']) ? (int) $_GET['kategori_id'] : 0;

$daftar_kategori = array();
$daftar_makanan = array();

if ($conn) {
    // 1. Ambil daftar kategori untuk opsi dropdown filter
    $stmt_kategori = mysqli_prepare($conn, "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC");
    if ($stmt_kategori) {
        mysqli_stmt_execute($stmt_kategori);
        $res_kategori = mysqli_stmt_get_result($stmt_kategori);
        while ($row_kategori = mysqli_fetch_assoc($res_kategori)) {
            $daftar_kategori[] = $row_kategori;
        }
        mysqli_stmt_close($stmt_kategori);
    }

    // 2. Kueri katalog makanan tersedia dengan filter pencarian & kategori
    $sql_makanan = "SELECT m.id, m.nama_makanan, m.deskripsi, m.harga, m.stok, m.foto_makanan, m.alamat_ambil,
                           k.nama_kategori, p.nama_lengkap AS nama_produsen
                    FROM makanan m
                    INNER JOIN kategori k ON m.kategori_id = k.id
                    INNER JOIN pengguna p ON m.produsen_id = p.id
                    WHERE m.stok > 0";

    $bind_types = "";
    $bind_params = array();

    if ($kata_kunci !== '') {
        $sql_makanan .= " AND (m.nama_makanan LIKE ? OR m.deskripsi LIKE ? OR m.alamat_ambil LIKE ?)";
        $pola_cari = '%' . $kata_kunci . '%';
        $bind_types .= "sss";
        $bind_params[] = $pola_cari;
        $bind_params[] = $pola_cari;
        $bind_params[] = $pola_cari;
    }

    if ($kategori_id > 0) {
        $sql_makanan .= " AND m.kategori_id = ?";
        $bind_types .= "i";
        $bind_params[] = $kategori_id;
    }

    $sql_makanan .= " ORDER BY m.dibuat_pada DESC";

    $stmt_makanan = mysqli_prepare($conn, $sql_makanan);
    if ($stmt_makanan) {
        if ($bind_types !== '') {
            mysqli_stmt_bind_param($stmt_makanan, $bind_types, ...$bind_params);
        }
        mysqli_stmt_execute($stmt_makanan);
        $res_makanan = mysqli_stmt_get_result($stmt_makanan);
        while ($row_makanan = mysqli_fetch_assoc($res_makanan)) {
            $daftar_makanan[] = $row_makanan;
        }
        mysqli_stmt_close($stmt_makanan);
    }
}
?>
<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Katalog Makanan - Food Bank Tubes PWD</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css" />
    <link rel="stylesheet" type="text/css" href="assets/css/components.css" />
</head>
<body>
    <div id="mainContainer" class="container">
        <div class="page-header">
            <h1>Katalog Makanan Tersedia</h1>
            <p class="page-subtitle">Temukan makanan layak konsumsi yang tersedia untuk diklaim dari produsen terdaftar.</p>
        </div>

        <?php if (!$conn): ?>
            <div class="alert alert-warning">
                Koneksi database belum aktif (menunggu config/database.php dari Role 1). Data katalog belum dapat dimuat.
            </div>
        <?php endif; ?>

        <div class="filter-section">
            <form action="catalog.php" method="get" class="filter-form">
                <div class="form-group">
                    <label for="inputSearch" class="form-label">Cari Makanan</label>
                    <input type="text" id="inputSearch" name="q" class="form-control" value="<?php echo htmlspecialchars($kata_kunci, ENT_QUOTES, 'UTF-8'); ?>" />
                </div>

                <div class="form-group">
                    <label for="selectKategori" class="form-label">Kategori</label>
                    <select id="selectKategori" name="kategori_id" class="form-control">
                        <option value="0">Semua Kategori</option>
                        <?php foreach ($daftar_kategori as $kategori): ?>
                            <?php $kat_id = (int) $kategori['id']; ?>
                            <option value="<?php echo htmlspecialchars((string) $kat_id, ENT_QUOTES, 'UTF-8'); ?>"<?php if ($kategori_id === $kat_id) { echo ' selected="selected"'; } ?>>
                                <?php echo htmlspecialchars($kategori['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" id="btnFilter" class="btn btn-primary">Terapkan Filter</button>
                    <a href="catalog.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div id="catalogGrid" class="catalog-grid">
            <?php if (empty($daftar_makanan)): ?>
                <div class="alert alert-warning">
                    Belum ada makanan tersedia yang sesuai dengan kriteria pencarian.
                </div>
            <?php else: ?>
                <?php foreach ($daftar_makanan as $item): ?>
                    <?php
                    $harga_int = (int) $item['harga'];
                    $stok_int = (int) $item['stok'];
                    $item_id = (int) $item['id'];
                    $alamat_ambil = isset($item['alamat_ambil']) && trim((string) $item['alamat_ambil']) !== ''
                        ? $item['alamat_ambil']
                        : 'Alamat belum dicantumkan';
                    $foto_file = isset($item['foto_makanan']) && trim((string) $item['foto_makanan']) !== ''
                        ? trim((string) $item['foto_makanan'])
                        : 'default_makanan.png';
                    if (strpos($foto_file, '/') === false) {
                        $foto_src = 'assets/uploads/' . $foto_file;
                    } else {
                        $foto_src = $foto_file;
                    }
                    ?>
                    <div class="card catalog-card">
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($foto_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['nama_makanan'], ENT_QUOTES, 'UTF-8'); ?>" class="card-img" />
                        </div>
                        <div class="card-body">
                            <h2 class="card-title"><?php echo htmlspecialchars($item['nama_makanan'], ENT_QUOTES, 'UTF-8'); ?></h2>

                            <p class="card-price">
                                <?php if ($harga_int === 0): ?>
                                    <span class="badge badge-success">Gratis</span>
                                <?php else: ?>
                                    <strong>Rp <?php echo htmlspecialchars(number_format($harga_int, 0, ',', '.'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <?php endif; ?>
                            </p>

                            <p class="card-meta">
                                <strong>Kategori:</strong> <?php echo htmlspecialchars($item['nama_kategori'], ENT_QUOTES, 'UTF-8'); ?><br />
                                <strong>Produsen:</strong> <?php echo htmlspecialchars($item['nama_produsen'], ENT_QUOTES, 'UTF-8'); ?><br />
                                <strong>Alamat Ambil:</strong> <?php echo htmlspecialchars($alamat_ambil, ENT_QUOTES, 'UTF-8'); ?><br />
                                <strong>Sisa Stok:</strong> <?php echo htmlspecialchars((string) $stok_int, ENT_QUOTES, 'UTF-8'); ?> porsi
                            </p>

                            <div class="card-actions">
                                <a href="item_detail.php?id=<?php echo htmlspecialchars((string) $item_id, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Lihat Rincian</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
