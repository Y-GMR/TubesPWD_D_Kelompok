<?php
if (!defined('CRUD1_MAX_FILE_SIZE')) {
    define('CRUD1_MAX_FILE_SIZE', 2097152);
}

if (!defined('CRUD1_UPLOAD_REL_DIR')) {
    define('CRUD1_UPLOAD_REL_DIR', 'assets/uploads/items/');
}

/**
 * Menghitung panjang string aman UTF-8 tanpa bergantung pada ekstensi mbstring.
 */
function crud1Len($teks) {
    $str = (string) $teks;
    return function_exists('mb_strlen') ? mb_strlen($str, 'UTF-8') : strlen($str);
}

/**
 * Mengambil seluruh kategori makanan diurutkan alfabetis.
 */
function crud1AmbilDaftarKategori($conn) {
    $daftar = array();
    if (!$conn) {
        return $daftar;
    }

    $sql = "SELECT id, nama_kategori, deskripsi, dibuat_pada FROM kategori ORDER BY nama_kategori ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $daftar;
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $daftar[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $daftar;
}

/**
 * Mengambil daftar kategori beserta jumlah menu makanan yang terikat (untuk admin).
 */
function crud1AmbilDaftarKategoriDenganJumlah($conn) {
    $daftar = array();
    if (!$conn) {
        return $daftar;
    }

    $sql = "SELECT k.id, k.nama_kategori, k.deskripsi, k.dibuat_pada, COUNT(m.id) AS jumlah_menu "
         . "FROM kategori k "
         . "LEFT JOIN makanan m ON m.kategori_id = k.id "
         . "GROUP BY k.id, k.nama_kategori, k.deskripsi, k.dibuat_pada "
         . "ORDER BY k.id ASC";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return $daftar;
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $daftar[] = $row;
    }
    mysqli_stmt_close($stmt);

    return $daftar;
}

/**
 * Mencari satu data kategori berdasarkan ID.
 */
function crud1CariKategoriById($conn, $kategori_id) {
    if (!$conn) {
        return null;
    }
    $id_bersih = (int) $kategori_id;
    if ($id_bersih <= 0) {
        return null;
    }

    $stmt = mysqli_prepare($conn, "SELECT id, nama_kategori, deskripsi FROM kategori WHERE id = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_bersih);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return $row ? $row : null;
}

/**
 * Mengambil satu data makanan yang dimiliki oleh produsen tertentu (anti-IDOR).
 */
function crud1AmbilMakananMilikProdusen($conn, $makanan_id, $produsen_id) {
    if (!$conn) {
        return null;
    }
    $id_bersih = (int) $makanan_id;
    $produsen_bersih = (int) $produsen_id;
    if ($id_bersih <= 0 || $produsen_bersih <= 0) {
        return null;
    }

    $sql = "SELECT id, produsen_id, kategori_id, nama_makanan, deskripsi, harga, stok, foto_makanan, "
         . "kedaluwarsa_pada, alamat_ambil, latitude_ambil, longitude_ambil, dibuat_pada "
         . "FROM makanan WHERE id = ? AND produsen_id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "ii", $id_bersih, $produsen_bersih);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    return $row ? $row : null;
}

/**
 * Memvalidasi nilai koordinat latitude/longitude.
 * Mengembalikan null jika kosong, float jika valid, atau false jika tidak valid.
 */
function crud1ValidasiKoordinat($nilai, $min, $max) {
    $teks = trim((string) $nilai);
    if ($teks === '') {
        return null;
    }
    if (!is_numeric($teks)) {
        return false;
    }
    $angka = (float) $teks;
    if (!is_finite($angka) || $angka < $min || $angka > $max) {
        return false;
    }
    return $angka;
}

/**
 * Memvalidasi dan mengonversi input tanggal kedaluwarsa ke format DATETIME MySQL.
 * Mengembalikan null jika kosong, string Y-m-d H:i:s jika valid, atau false jika format salah.
 */
function crud1ParseKedaluwarsa($input) {
    $teks = trim((string) $input);
    if ($teks === '') {
        return null;
    }

    $format_diizinkan = array('Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s', 'Y-m-d H:i');
    foreach ($format_diizinkan as $fmt) {
        $dt = DateTime::createFromFormat($fmt, $teks);
        $errors = DateTime::getLastErrors();
        $ada_error = is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0);
        if ($dt !== false && !$ada_error) {
            return $dt->format('Y-m-d H:i:s');
        }
    }

    return false;
}

/**
 * Memformat nilai DATETIME MySQL ke format input HTML datetime-local.
 */
function crud1FormatKedaluwarsaInput($datetime_str) {
    $teks = trim((string) $datetime_str);
    if ($teks === '') {
        return '';
    }
    $ts = strtotime($teks);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $ts);
}

/**
 * Memformat nilai DATETIME MySQL menjadi label waktu bacaan antarmuka.
 */
function crud1FormatKedaluwarsaTampil($datetime_str) {
    $teks = trim((string) $datetime_str);
    if ($teks === '') {
        return 'Tidak dibatasi';
    }
    $ts = strtotime($teks);
    if ($ts === false) {
        return $teks;
    }
    $bulan = array(
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
    );
    $idx_bulan = (int) date('n', $ts);
    $nama_bulan = isset($bulan[$idx_bulan]) ? $bulan[$idx_bulan] : date('M', $ts);
    return date('d', $ts) . ' ' . $nama_bulan . ' ' . date('Y, H:i', $ts) . ' WIB';
}

/**
 * Menormalisasi path foto makanan agar selalu mengarah ke berkas yang valid.
 */
function crud1FormatFotoUrl($foto_makanan, $base_prefix = '') {
    $fallback = 'assets/images/menu_donat_glaze.png';
    $raw = trim((string) $foto_makanan);

    if ($raw === '' || $raw === 'default_makanan.png' || $raw === 'default_item.png') {
        return $base_prefix . $fallback;
    }

    if (strpos($raw, '/') === false) {
        $kandidat_items = CRUD1_UPLOAD_REL_DIR . $raw;
        $kandidat_uploads = 'assets/uploads/' . $raw;
        if (file_exists(__DIR__ . '/../' . $kandidat_items)) {
            return $base_prefix . $kandidat_items;
        }
        if (file_exists(__DIR__ . '/../' . $kandidat_uploads)) {
            return $base_prefix . $kandidat_uploads;
        }
        return $base_prefix . $fallback;
    }

    if (strpos($raw, '..') !== false) {
        return $base_prefix . $fallback;
    }

    if (file_exists(__DIR__ . '/../' . $raw)) {
        return $base_prefix . $raw;
    }

    return $base_prefix . $fallback;
}

/**
 * Menormalisasi path foto profil produsen dengan fallback aman.
 */
function crud1FormatFotoProdusenUrl($foto_profil, $base_prefix = '') {
    $fallback = 'assets/images/producer/avatar_produsen.png';
    $raw = trim((string) $foto_profil);

    if ($raw === '' || $raw === 'default_avatar.png' || strpos($raw, '..') !== false) {
        return $base_prefix . $fallback;
    }

    $kandidat = (strpos($raw, '/') === false) ? ('assets/uploads/avatars/' . $raw) : $raw;
    if (file_exists(__DIR__ . '/../' . $kandidat)) {
        return $base_prefix . $kandidat;
    }

    return $base_prefix . $fallback;
}

/**
 * Memproses unggahan foto makanan dengan validasi ekstensi, batas 2MB,
 * deteksi MIME riil via finfo_file(), dan penamaan acak kriptografis.
 */
function crud1ProsesUploadFoto($file, $prefix_relatif = '../') {
    if (!is_array($file) || !isset($file['error'])) {
        return array('ok' => true, 'uploaded' => false, 'path' => null, 'error' => '');
    }

    $kode_error = (int) $file['error'];
    if ($kode_error === UPLOAD_ERR_NO_FILE) {
        return array('ok' => true, 'uploaded' => false, 'path' => null, 'error' => '');
    }

    if ($kode_error === UPLOAD_ERR_INI_SIZE || $kode_error === UPLOAD_ERR_FORM_SIZE) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'ukuran_terlalu_besar');
    }

    if ($kode_error !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    $tmp_name = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    $orig_name = isset($file['name']) ? (string) $file['name'] : '';
    $file_size = isset($file['size']) ? (int) $file['size'] : 0;

    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_tidak_valid');
    }

    if ($file_size <= 0 || $file_size > CRUD1_MAX_FILE_SIZE) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'ukuran_terlalu_besar');
    }

    $ext_asli = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    $ekstensi_diizinkan = array('jpg', 'jpeg', 'png', 'webp');
    if (!in_array($ext_asli, $ekstensi_diizinkan, true)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'format_file_ditolak');
    }

    if (!function_exists('finfo_open') || !function_exists('finfo_file')) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_riil = $finfo ? finfo_file($finfo, $tmp_name) : false;
    $finfo = null;

    $peta_mime_ke_ext = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    );

    if ($mime_riil === false || !isset($peta_mime_ke_ext[$mime_riil])) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'mime_file_ditolak');
    }

    $ext_aman = $peta_mime_ke_ext[$mime_riil];
    $nama_file_unik = bin2hex(random_bytes(16)) . '.' . $ext_aman;
    $dir_fisik = $prefix_relatif . CRUD1_UPLOAD_REL_DIR;

    if (!is_dir($dir_fisik)) {
        if (!mkdir($dir_fisik, 0755, true) && !is_dir($dir_fisik)) {
            return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'direktori_gagal');
        }
    }

    $tujuan_fisik = $dir_fisik . $nama_file_unik;
    if (!move_uploaded_file($tmp_name, $tujuan_fisik)) {
        return array('ok' => false, 'uploaded' => false, 'path' => null, 'error' => 'upload_gagal');
    }

    return array(
        'ok'       => true,
        'uploaded' => true,
        'path'     => CRUD1_UPLOAD_REL_DIR . $nama_file_unik,
        'error'    => ''
    );
}

/**
 * Menghapus berkas foto makanan lama secara aman hanya jika berada
 * di dalam direktori unggahan assets/uploads/items/.
 */
function crud1HapusFotoFisik($path_foto, $prefix_relatif = '../') {
    $raw = trim((string) $path_foto);
    if ($raw === '' || strpos($raw, '..') !== false) {
        return false;
    }

    $prefix_upload = CRUD1_UPLOAD_REL_DIR;
    if (strncmp($raw, $prefix_upload, strlen($prefix_upload)) !== 0) {
        return false;
    }

    $base_dir = realpath($prefix_relatif . $prefix_upload);
    $target_file = realpath($prefix_relatif . $raw);

    if ($base_dir === false || $target_file === false) {
        return false;
    }

    if (strncmp($target_file, $base_dir, strlen($base_dir)) !== 0) {
        return false;
    }

    if (is_file($target_file)) {
        return @unlink($target_file);
    }

    return false;
}

/**
 * Memetakan kode pesan sukses dan error dari parameter GET.
 */
function crud1AmbilFlashPesan($map_pesan, $map_error) {
    $hasil = array('sukses' => '', 'error' => '');

    if (isset($_GET['pesan'])) {
        $kode = trim((string) $_GET['pesan']);
        if (isset($map_pesan[$kode])) {
            $hasil['sukses'] = $map_pesan[$kode];
        }
    }

    if (isset($_GET['error'])) {
        $kode = trim((string) $_GET['error']);
        if (isset($map_error[$kode])) {
            $hasil['error'] = $map_error[$kode];
        } else {
            $hasil['error'] = 'Terjadi kesalahan saat memproses permintaan.';
        }
    }

    return $hasil;
}
?>
