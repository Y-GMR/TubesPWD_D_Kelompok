<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/crud1_helpers.php';

require_role('produsen');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../inventory.php');
    exit();
}

// Verifikasi CSRF token
$csrf_dikirim = (isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_dikirim)) {
    header('Location: ../inventory.php?error=csrf_tidak_valid');
    exit();
}

if (!$conn) {
    header('Location: ../inventory.php?error=koneksi_gagal');
    exit();
}

$produsen_id = (int) $_SESSION['user_id'];
$action = isset($_POST['action']) ? bersihkanInput($_POST['action']) : '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

// Pastikan produsen sudah diverifikasi admin sebelum boleh mengubah data makanan
$data_produsen_cek = ambilPenggunaById($conn, $produsen_id);
if (!$data_produsen_cek) {
    hapusSesiPengguna();
    header('Location: ../login.php?error=login_required');
    exit();
}
sinkronSesiPengguna($data_produsen_cek);
if ($data_produsen_cek['peran'] !== 'produsen') {
    require_role('produsen');
}
if ($data_produsen_cek['status_verifikasi'] !== 'disetujui') {
    if ($data_produsen_cek['status_verifikasi'] === 'ditolak') {
        $_SESSION['flash_error'] = 'Akun produsen Anda ditolak oleh admin sehingga belum dapat mengelola makanan.';
    } else {
        $_SESSION['flash_error'] = 'Akun Anda belum diverifikasi oleh admin. Anda belum dapat mengelola makanan.';
    }
    header('Location: ../producer_dashboard.php?error=belum_diverifikasi');
    exit();
}

/**
 * Memvalidasi seluruh masukan formulir item makanan untuk operasi create/update.
 */
function crud1ValidasiPayloadMakanan($conn, $post, $wajib_higienis, $stok_minimal) {
    $nama_makanan = isset($post['nama_makanan']) ? bersihkanInput($post['nama_makanan']) : '';
    $kategori_id = isset($post['kategori_id']) ? (int) $post['kategori_id'] : 0;
    $harga_raw = isset($post['harga']) ? bersihkanInput($post['harga']) : '0';
    $stok_raw = isset($post['stok']) ? bersihkanInput($post['stok']) : '';
    $kedaluwarsa_raw = isset($post['kedaluwarsa_pada']) ? bersihkanInput($post['kedaluwarsa_pada']) : '';
    $deskripsi = isset($post['deskripsi']) ? bersihkanInput($post['deskripsi']) : '';
    $alamat_ambil = isset($post['alamat_ambil']) ? bersihkanInput($post['alamat_ambil']) : '';
    $lat_raw = isset($post['latitude']) ? $post['latitude'] : '';
    $lng_raw = isset($post['longitude']) ? $post['longitude'] : '';

    $panjang_nama = crud1Len($nama_makanan);
    if ($nama_makanan === '' || $panjang_nama < 2 || $panjang_nama > 150) {
        return array('ok' => false, 'error' => 'nama_makanan_tidak_valid');
    }

    if ($kategori_id <= 0 || !crud1CariKategoriById($conn, $kategori_id)) {
        return array('ok' => false, 'error' => 'kategori_tidak_valid');
    }

    if ($harga_raw === '') {
        $harga_raw = '0';
    }
    if (!preg_match('/^\d+$/', $harga_raw)) {
        return array('ok' => false, 'error' => 'harga_tidak_valid');
    }
    $harga = (int) $harga_raw;
    if ($harga < 0 || $harga > 100000000) {
        return array('ok' => false, 'error' => 'harga_tidak_valid');
    }

    if ($stok_raw === '' || !preg_match('/^\d+$/', $stok_raw)) {
        return array('ok' => false, 'error' => 'stok_tidak_valid');
    }
    $stok = (int) $stok_raw;
    if ($stok < $stok_minimal || $stok > 9999) {
        return array('ok' => false, 'error' => 'stok_tidak_valid');
    }

    $kedaluwarsa_db = crud1ParseKedaluwarsa($kedaluwarsa_raw);
    if ($kedaluwarsa_db === false) {
        return array('ok' => false, 'error' => 'kedaluwarsa_tidak_valid');
    }
    if ($stok > 0 && $kedaluwarsa_db !== null && strtotime($kedaluwarsa_db) <= time()) {
        return array('ok' => false, 'error' => 'kedaluwarsa_sudah_lewat');
    }

    $panjang_desc = crud1Len($deskripsi);
    if ($panjang_desc > 2000) {
        return array('ok' => false, 'error' => 'deskripsi_terlalu_panjang');
    }
    $deskripsi_db = ($deskripsi === '') ? null : $deskripsi;

    $panjang_alamat = crud1Len($alamat_ambil);
    if ($alamat_ambil === '' || $panjang_alamat < 3 || $panjang_alamat > 500) {
        return array('ok' => false, 'error' => 'alamat_tidak_valid');
    }

    $latitude = crud1ValidasiKoordinat($lat_raw, -90.0, 90.0);
    if ($latitude === false) {
        return array('ok' => false, 'error' => 'koordinat_tidak_valid');
    }
    $longitude = crud1ValidasiKoordinat($lng_raw, -180.0, 180.0);
    if ($longitude === false) {
        return array('ok' => false, 'error' => 'koordinat_tidak_valid');
    }

    if ($wajib_higienis && !isset($post['konfirmasi_higienis'])) {
        return array('ok' => false, 'error' => 'konfirmasi_higienis_wajib');
    }

    return array(
        'ok' => true,
        'data' => array(
            'kategori_id'      => $kategori_id,
            'nama_makanan'     => $nama_makanan,
            'deskripsi'        => $deskripsi_db,
            'harga'            => $harga,
            'stok'             => $stok,
            'kedaluwarsa_pada' => $kedaluwarsa_db,
            'alamat_ambil'     => $alamat_ambil,
            'latitude_ambil'   => $latitude,
            'longitude_ambil'  => $longitude
        )
    );
}

if ($action === 'create') {
    $validasi = crud1ValidasiPayloadMakanan($conn, $_POST, true, 1);
    if (!$validasi['ok']) {
        header('Location: ../add_food.php?error=' . $validasi['error']);
        exit();
    }

    $file_upload = isset($_FILES['foto_makanan']) ? $_FILES['foto_makanan'] : null;
    $upload = crud1ProsesUploadFoto($file_upload, '../');
    if (!$upload['ok']) {
        header('Location: ../add_food.php?error=' . $upload['error']);
        exit();
    }

    $foto_makanan = $upload['uploaded'] ? $upload['path'] : 'default_makanan.png';
    $data = $validasi['data'];

    $sql = "INSERT INTO makanan (produsen_id, kategori_id, nama_makanan, deskripsi, harga, stok, foto_makanan, kedaluwarsa_pada, alamat_ambil, latitude_ambil, longitude_ambil) "
         . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        if ($upload['uploaded']) {
            crud1HapusFotoFisik($foto_makanan, '../');
        }
        header('Location: ../add_food.php?error=sistem_gagal');
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        "iissiisssdd",
        $produsen_id,
        $data['kategori_id'],
        $data['nama_makanan'],
        $data['deskripsi'],
        $data['harga'],
        $data['stok'],
        $foto_makanan,
        $data['kedaluwarsa_pada'],
        $data['alamat_ambil'],
        $data['latitude_ambil'],
        $data['longitude_ambil']
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        header('Location: ../inventory.php?pesan=makanan_ditambah');
    } else {
        if ($upload['uploaded']) {
            crud1HapusFotoFisik($foto_makanan, '../');
        }
        header('Location: ../add_food.php?error=gagal_simpan');
    }
    exit();
}

if ($action === 'update') {
    if ($id <= 0) {
        header('Location: ../inventory.php?error=id_tidak_valid');
        exit();
    }

    $item_lama = crud1AmbilMakananMilikProdusen($conn, $id, $produsen_id);
    if (!$item_lama) {
        header('Location: ../inventory.php?error=akses_ditolak');
        exit();
    }

    $validasi = crud1ValidasiPayloadMakanan($conn, $_POST, false, 0);
    if (!$validasi['ok']) {
        header('Location: ../add_food.php?id=' . $id . '&error=' . $validasi['error']);
        exit();
    }

    $file_upload = isset($_FILES['foto_makanan']) ? $_FILES['foto_makanan'] : null;
    $upload = crud1ProsesUploadFoto($file_upload, '../');
    if (!$upload['ok']) {
        header('Location: ../add_food.php?id=' . $id . '&error=' . $upload['error']);
        exit();
    }

    $foto_baru = $upload['uploaded'] ? $upload['path'] : $item_lama['foto_makanan'];
    $data = $validasi['data'];

    $sql = "UPDATE makanan SET kategori_id = ?, nama_makanan = ?, deskripsi = ?, harga = ?, stok = ?, "
         . "foto_makanan = ?, kedaluwarsa_pada = ?, alamat_ambil = ?, latitude_ambil = ?, longitude_ambil = ? "
         . "WHERE id = ? AND produsen_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        if ($upload['uploaded']) {
            crud1HapusFotoFisik($foto_baru, '../');
        }
        header('Location: ../add_food.php?id=' . $id . '&error=sistem_gagal');
        exit();
    }

    mysqli_stmt_bind_param(
        $stmt,
        "issiisssddii",
        $data['kategori_id'],
        $data['nama_makanan'],
        $data['deskripsi'],
        $data['harga'],
        $data['stok'],
        $foto_baru,
        $data['kedaluwarsa_pada'],
        $data['alamat_ambil'],
        $data['latitude_ambil'],
        $data['longitude_ambil'],
        $id,
        $produsen_id
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if ($ok) {
        if ($upload['uploaded']) {
            crud1HapusFotoFisik($item_lama['foto_makanan'], '../');
        }
        header('Location: ../inventory.php?pesan=makanan_diperbarui');
    } else {
        if ($upload['uploaded']) {
            crud1HapusFotoFisik($foto_baru, '../');
        }
        header('Location: ../add_food.php?id=' . $id . '&error=gagal_simpan');
    }
    exit();
}

/**
 * Memperbarui stok item makanan milik produsen dengan prepared statement.
 */
function crud1UpdateStokMakanan($conn, $id, $produsen_id, $stok_baru) {
    $stmt = mysqli_prepare($conn, "UPDATE makanan SET stok = ? WHERE id = ? AND produsen_id = ?");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_bind_param($stmt, "iii", $stok_baru, $id, $produsen_id);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

if ($action === 'update_stok') {
    if ($id <= 0) {
        header('Location: ../inventory.php?error=id_tidak_valid');
        exit();
    }

    $item_lama = crud1AmbilMakananMilikProdusen($conn, $id, $produsen_id);
    if (!$item_lama) {
        header('Location: ../inventory.php?error=akses_ditolak');
        exit();
    }

    $stok_raw = isset($_POST['stok']) ? bersihkanInput($_POST['stok']) : '';
    if ($stok_raw === '' || !preg_match('/^\d+$/', $stok_raw)) {
        header('Location: ../inventory.php?error=stok_tidak_valid');
        exit();
    }
    $stok_baru = (int) $stok_raw;
    if ($stok_baru < 0 || $stok_baru > 9999) {
        header('Location: ../inventory.php?error=stok_tidak_valid');
        exit();
    }

    $ok = crud1UpdateStokMakanan($conn, $id, $produsen_id, $stok_baru);
    if ($ok === null) {
        header('Location: ../inventory.php?error=sistem_gagal');
    } elseif ($ok) {
        $batas = $item_lama['kedaluwarsa_pada'];
        $lewat = (!empty($batas) && strtotime($batas) <= time());
        $kode_pesan = ($stok_baru > 0 && $lewat) ? 'stok_diperbarui_kedaluwarsa' : 'stok_diperbarui';
        header('Location: ../inventory.php?pesan=' . $kode_pesan);
    } else {
        header('Location: ../inventory.php?error=gagal_simpan');
    }
    exit();
}

if ($action === 'tutup_donasi') {
    if ($id <= 0) {
        header('Location: ../inventory.php?error=id_tidak_valid');
        exit();
    }

    $item_lama = crud1AmbilMakananMilikProdusen($conn, $id, $produsen_id);
    if (!$item_lama) {
        header('Location: ../inventory.php?error=akses_ditolak');
        exit();
    }

    // Kedaluwarsa disetel ke waktu sekarang agar donasi tidak tampil lagi di katalog
    // apabila stok terpulihkan akibat pembatalan transaksi yang masih menunggu
    $ok = null;
    $stmt_tutup = mysqli_prepare($conn, "UPDATE makanan SET stok = 0, kedaluwarsa_pada = NOW() WHERE id = ? AND produsen_id = ?");
    if ($stmt_tutup) {
        mysqli_stmt_bind_param($stmt_tutup, "ii", $id, $produsen_id);
        $ok = mysqli_stmt_execute($stmt_tutup);
        mysqli_stmt_close($stmt_tutup);
    }
    if ($ok === null) {
        header('Location: ../inventory.php?error=sistem_gagal');
    } elseif ($ok) {
        header('Location: ../inventory.php?pesan=donasi_ditutup');
    } else {
        header('Location: ../inventory.php?error=gagal_simpan');
    }
    exit();
}

if ($action === 'delete') {
    if ($id <= 0) {
        header('Location: ../inventory.php?error=id_tidak_valid');
        exit();
    }

    $item_lama = crud1AmbilMakananMilikProdusen($conn, $id, $produsen_id);
    if (!$item_lama) {
        header('Location: ../inventory.php?error=akses_ditolak');
        exit();
    }

    // Cek relasi hasClaimRef? sebelum menghapus makanan
    $stmt_ref = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM detail_transaksi WHERE makanan_id = ?");
    if (!$stmt_ref) {
        header('Location: ../inventory.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_ref, "i", $id);
    mysqli_stmt_execute($stmt_ref);
    $res_ref = mysqli_stmt_get_result($stmt_ref);
    $row_ref = mysqli_fetch_assoc($res_ref);
    mysqli_stmt_close($stmt_ref);

    $jumlah_klaim = $row_ref ? (int) $row_ref['total'] : 0;
    if ($jumlah_klaim > 0) {
        header('Location: ../inventory.php?error=makanan_memiliki_transaksi');
        exit();
    }

    $stmt_del = mysqli_prepare($conn, "DELETE FROM makanan WHERE id = ? AND produsen_id = ?");
    if (!$stmt_del) {
        header('Location: ../inventory.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_del, "ii", $id, $produsen_id);
    $ok = mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);

    if ($ok) {
        crud1HapusFotoFisik($item_lama['foto_makanan'], '../');
        header('Location: ../inventory.php?pesan=makanan_dihapus');
    } else {
        header('Location: ../inventory.php?error=gagal_hapus');
    }
    exit();
}

header('Location: ../inventory.php?error=aksi_tidak_valid');
exit();
?>
