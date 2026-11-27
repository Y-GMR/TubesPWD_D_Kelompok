<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

require_role('produsen');

$produsen_id = (int) $_SESSION['user_id'];
$produsen = ambilPenggunaById($conn, $produsen_id);

// Blokir produsen yang belum diverifikasi admin
if (!$produsen || $produsen['status_verifikasi'] !== 'disetujui') {
    $_SESSION['flash_error'] = 'Akun Anda sedang menunggu verifikasi admin. Anda belum dapat mengelola makanan.';
    header('Location: producer_dashboard.php?error=belum_diverifikasi');
    exit();
}

$nama_gerai = trim((string) $produsen['nama_lengkap']) !== ''
    ? $produsen['nama_lengkap']
    : (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Mitra Produsen');

$daftar_kategori = crud1AmbilDaftarKategori($conn);

$edit_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = false;
$item_edit = null;

if ($edit_id > 0) {
    $item_edit = crud1AmbilMakananMilikProdusen($conn, $edit_id, $produsen_id);
    if (!$item_edit) {
        header('Location: inventory.php?error=akses_ditolak');
        exit();
    }
    $is_edit = true;
}

$val_id = $is_edit ? (string) ((int) $item_edit['id']) : '';
$val_action = $is_edit ? 'update' : 'create';
$val_nama = $is_edit ? (string) $item_edit['nama_makanan'] : '';
$val_kategori_id = $is_edit ? (int) $item_edit['kategori_id'] : (!empty($daftar_kategori) ? (int) $daftar_kategori[0]['id'] : 0);
$val_harga = $is_edit ? (string) ((int) $item_edit['harga']) : '0';
$val_stok = $is_edit ? (string) ((int) $item_edit['stok']) : '10';
$val_deskripsi = ($is_edit && isset($item_edit['deskripsi'])) ? (string) $item_edit['deskripsi'] : '';
$val_alamat = ($is_edit && isset($item_edit['alamat_ambil'])) ? (string) $item_edit['alamat_ambil'] : '';
$val_lat = ($is_edit && $item_edit['latitude_ambil'] !== null) ? (string) $item_edit['latitude_ambil'] : '';
$val_lng = ($is_edit && $item_edit['longitude_ambil'] !== null) ? (string) $item_edit['longitude_ambil'] : '';

$default_kedaluwarsa = date('Y-m-d H:i', time() + 4 * 3600);
$val_kedaluwarsa = $is_edit
    ? crud1FormatKedaluwarsaInput($item_edit['kedaluwarsa_pada'])
    : $default_kedaluwarsa;

$preview_foto_src = $is_edit
    ? crud1FormatFotoUrl($item_edit['foto_makanan'])
    : 'assets/images/menu_ayam_crispy.png';

$map_error = array(
    'koneksi_gagal'             => 'Gagal terhubung ke basis data.',
    'sistem_gagal'              => 'Terjadi kesalahan sistem saat menyimpan data makanan.',
    'nama_makanan_tidak_valid'  => 'Nama makanan wajib diisi antara 2 hingga 150 karakter.',
    'kategori_tidak_valid'      => 'Kategori makanan yang dipilih tidak valid.',
    'harga_tidak_valid'         => 'Harga harus berupa bilangan bulat 0 atau lebih (0 berarti Gratis).',
    'stok_tidak_valid'          => 'Jumlah porsi stok tidak valid.',
    'kedaluwarsa_tidak_valid'   => 'Format batas waktu pengambilan / kedaluwarsa tidak valid.',
    'kedaluwarsa_sudah_lewat'   => 'Batas waktu kedaluwarsa harus lebih besar dari waktu saat ini.',
    'deskripsi_terlalu_panjang' => 'Catatan makanan maksimal 2000 karakter.',
    'alamat_tidak_valid'        => 'Alamat pengambilan wajib diisi (3-500 karakter).',
    'koordinat_tidak_valid'     => 'Koordinat GPS latitude/longitude tidak valid.',
    'konfirmasi_higienis_wajib' => 'Anda wajib menyetujui pernyataan makanan higienis dan layak konsumsi.',
    'ukuran_terlalu_besar'      => 'Ukuran berkas foto melebihi batas maksimal 2 MB.',
    'format_file_ditolak'       => 'Ekstensi berkas foto harus JPG, JPEG, PNG, atau WEBP.',
    'mime_file_ditolak'         => 'Tipe MIME berkas foto tidak valid atau bukan gambar asli.',
    'upload_tidak_valid'        => 'Berkas unggahan tidak valid.',
    'direktori_gagal'           => 'Direktori penyimpanan foto gagal disiapkan.',
    'upload_gagal'              => 'Gagal mengunggah berkas foto makanan.',
    'gagal_simpan'              => 'Gagal menyimpan data makanan ke basis data.'
);

$flash = crud1AmbilFlashPesan(array(), $map_error);

$page_title = ($is_edit ? 'Ubah Data Makanan' : 'Donasi Makanan Baru') . ' - Pangkalan Pangan';
$extra_css = array('components.css', 'catalog.css');
$extra_js = array('geolocation.js', 'image_preview.js', 'crud1_navbar.js?v=1', 'crud1_add_food.js?v=2');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="main-content">

            <div class="back-link-wrap">
                <a href="inventory.php" class="back-link-btn">&#8592; Kembali ke Inventaris</a>
            </div>

            <h1 class="page-title-main"><?php echo $is_edit ? 'Ubah Detail Makanan' : 'Donasi Makanan Baru'; ?></h1>
            <p class="page-subtitle-main"><?php echo $is_edit ? 'Perbarui detail, jumlah stok, waktu batas ambil, atau foto produk Anda.' : 'Daftarkan makanan untuk disalurkan kepada mereka yang membutuhkan.'; ?></p>

            <?php if (!$conn): ?>
                <div class="alert alert-warning">Koneksi basis data belum aktif. Formulir belum dapat disimpan.</div>
            <?php endif; ?>

            <?php if ($flash['error'] !== ''): ?>
                <div class="alert alert-error"><?php echo escapeHtml($flash['error']); ?></div>
            <?php endif; ?>

            <form action="actions/crud1_item.php" method="post" enctype="multipart/form-data" id="formAddFood" class="add-food-grid">
                <div>
                    <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($_SESSION['csrf_token']); ?>" />
                    <input type="hidden" name="action" value="<?php echo escapeHtml($val_action); ?>" />
                    <?php if ($is_edit): ?>
                        <input type="hidden" name="id" value="<?php echo escapeHtml($val_id); ?>" />
                    <?php endif; ?>
                </div>

                <div class="add-food-col-left">

                    <div class="form-card">
                        <div class="form-card-header">
                            <span class="card-accent-bar"></span>
                            <h2 class="card-title-text">Detail Makanan</h2>
                        </div>

                        <div class="form-group">
                            <label for="inputFoodName" class="form-label">Nama Makanan *</label>
                            <input type="text" id="inputFoodName" name="nama_makanan" class="form-control" value="<?php echo escapeHtml($val_nama); ?>" />
                        </div>

                        <div class="form-row-2col">
                            <div class="form-group">
                                <label for="selectCategory" class="form-label">Kategori *</label>
                                <select id="selectCategory" name="kategori_id" class="form-control">
                                    <?php if (empty($daftar_kategori)): ?>
                                        <option value="0">Belum ada kategori</option>
                                    <?php else: ?>
                                        <?php foreach ($daftar_kategori as $kat): ?>
                                            <?php $kid = (int) $kat['id']; ?>
                                            <option value="<?php echo escapeHtml((string) $kid); ?>"<?php echo ($val_kategori_id === $kid) ? ' selected="selected"' : ''; ?>>
                                                <?php echo escapeHtml($kat['nama_kategori']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="inputPortion" class="form-label">Jumlah Porsi *</label>
                                <input type="text" id="inputPortion" name="stok" class="form-control" value="<?php echo escapeHtml($val_stok); ?>" />
                            </div>
                        </div>

                        <div class="form-row-2col">
                            <div class="form-group">
                                <label for="inputPrice" class="form-label">Harga per Porsi (Rp, 0 = Gratis) *</label>
                                <input type="text" id="inputPrice" name="harga" class="form-control" value="<?php echo escapeHtml($val_harga); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="inputExpiryTime" class="form-label">Batas Kedaluwarsa (YYYY-MM-DD HH:MM)</label>
                                <input type="text" id="inputExpiryTime" name="kedaluwarsa_pada" class="form-control" value="<?php echo escapeHtml($val_kedaluwarsa); ?>" />
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="textareaNotes" class="form-label">Catatan, Deskripsi &amp; Alergen</label>
                            <textarea id="textareaNotes" name="deskripsi" class="form-control form-control-textarea" rows="4" cols="50"><?php echo escapeHtml($val_deskripsi); ?></textarea>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="form-card-header">
                            <span class="card-accent-bar"></span>
                            <h2 class="card-title-text">Lokasi Pengambilan</h2>
                        </div>

                        <div class="form-group">
                            <label for="inputAddress" class="form-label">Alamat Gerai *</label>
                            <input type="text" id="inputAddress" name="alamat_ambil" class="form-control" value="<?php echo escapeHtml($val_alamat); ?>" />
                        </div>

                        <button type="button" id="btnDetectLocation" class="btn-detect-gps-full btn-detect-gps">Perbarui Lokasi GPS</button>

                        <div class="form-row-2col">
                            <div class="form-group">
                                <label for="inputLatitude" class="form-label">Latitude</label>
                                <input type="text" id="inputLatitude" name="latitude" class="form-control" readonly="readonly" value="<?php echo escapeHtml($val_lat); ?>" />
                            </div>
                            <div class="form-group">
                                <label for="inputLongitude" class="form-label">Longitude</label>
                                <input type="text" id="inputLongitude" name="longitude" class="form-control" readonly="readonly" value="<?php echo escapeHtml($val_lng); ?>" />
                            </div>
                        </div>

                        <div class="geo-status-box">
                            <span id="geoStatusText"><?php echo ($val_lat !== '' && $val_lng !== '') ? 'Koordinat lokasi tersimpan (' . escapeHtml($val_lat) . ', ' . escapeHtml($val_lng) . ')' : 'Klik tombol di atas untuk mendeteksi koordinat GPS gerai'; ?></span>
                        </div>
                        <div id="geoCoordinatesPreview" class="geo-preview-text" style="display: none;"></div>
                    </div>

                </div>

                <div class="add-food-col-right">

                    <div class="form-card">
                        <div class="form-card-header">
                            <span class="card-accent-bar"></span>
                            <h2 class="card-title-text">Foto Makanan</h2>
                        </div>

                        <div class="photo-dropzone" id="dropzoneUpload">
                            <div class="photo-dropzone-title">Pilih atau seret foto ke sini</div>
                            <button type="button" class="btn-browse-photo" id="btnBrowsePhoto">Pilih Foto</button>
                            <div class="photo-dropzone-hint">JPG, PNG, atau WEBP (Maks. 2MB)</div>
                        </div>

                        <input type="file" id="foodPhotoInput" name="foto_makanan" accept="image/jpeg,image/png,image/webp" class="form-file-hidden" />

                        <div class="photo-preview-wrap">
                            <div class="photo-preview-box">
                                <img src="<?php echo escapeHtml($preview_foto_src); ?>" alt="Pratinjau Foto Makanan" class="photo-preview-img" id="foodImagePreview" />
                            </div>
                            <div class="photo-preview-footer">
                                <span class="photo-preview-badge"><?php echo $is_edit ? 'Foto Saat Ini / Pratinjau' : 'Pratinjau Foto'; ?></span>
                                <button type="button" class="btn-change-photo" id="btnChangePhoto">Ganti Foto</button>
                            </div>
                        </div>
                    </div>

                    <div class="form-card">
                        <div class="form-card-header">
                            <span class="card-accent-bar"></span>
                            <h2 class="card-title-text">Ringkasan Donasi</h2>
                        </div>

                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Gerai</span>
                                <span class="summary-val" id="summaryStore"><?php echo escapeHtml($nama_gerai); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Jumlah Porsi</span>
                                <span class="summary-val" id="summaryPortion"><?php echo escapeHtml($val_stok); ?> Porsi</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Biaya</span>
                                <span class="summary-val-free" id="summaryPrice"><?php echo escapeHtml(formatRupiah((int) $val_harga)); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Batas Pengambilan</span>
                                <span class="summary-val" id="summaryTime"><?php echo $val_kedaluwarsa !== '' ? escapeHtml(str_replace('T', ' ', $val_kedaluwarsa)) : 'Tidak dibatasi'; ?></span>
                            </div>
                        </div>

                        <hr class="summary-divider" />

                        <label class="summary-checkbox-wrap">
                            <input type="checkbox" id="chkConfirmHygienic" name="konfirmasi_higienis" checked="checked" />
                            <span>Saya menjamin makanan dalam kondisi higienis dan layak konsumsi.</span>
                        </label>

                        <button type="submit" class="btn-submit-donation" id="btnSubmitDonation"><?php echo $is_edit ? 'Simpan Perubahan Makanan' : 'Donasikan Makanan'; ?></button>
                        <a href="inventory.php" class="btn-cancel-donation">&#8592; Batal &amp; Kembali ke Inventaris</a>
                    </div>

                </div>

            </form>

        </div>

<?php
require_once 'includes/footer.php';
?>
