<?php
require_once 'includes/session.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/crud1_helpers.php';

require_role('admin');

$daftar_kategori = crud1AmbilDaftarKategoriDenganJumlah($conn);

$edit_id = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : 0;
$kategori_edit = ($edit_id > 0) ? crud1CariKategoriById($conn, $edit_id) : null;

$form_action_mode = $kategori_edit ? 'update' : 'create';
$form_id_val = $kategori_edit ? (string) ((int) $kategori_edit['id']) : '';
$form_name_val = $kategori_edit ? (string) $kategori_edit['nama_kategori'] : '';
$form_desc_val = ($kategori_edit && isset($kategori_edit['deskripsi'])) ? (string) $kategori_edit['deskripsi'] : '';
$form_title_text = $kategori_edit ? ('Ubah Kategori (#' . (int) $kategori_edit['id'] . ')') : 'Tambah Kategori Baru';
$form_btn_text = $kategori_edit ? 'Perbarui Kategori' : 'Simpan Kategori';

$map_pesan = array(
    'kategori_ditambah'   => 'Kategori baru berhasil ditambahkan ke sistem.',
    'kategori_diperbarui' => 'Perubahan data kategori berhasil disimpan.',
    'kategori_dihapus'    => 'Kategori berhasil dihapus dari sistem.'
);

$map_error = array(
    'koneksi_gagal'             => 'Gagal terhubung ke basis data.',
    'sistem_gagal'              => 'Terjadi kesalahan internal saat memproses kategori.',
    'aksi_tidak_valid'          => 'Permintaan aksi kategori tidak dikenali.',
    'id_tidak_valid'            => 'ID kategori tidak valid.',
    'nama_kategori_tidak_valid' => 'Nama kategori wajib diisi antara 2 hingga 100 karakter.',
    'deskripsi_terlalu_panjang' => 'Deskripsi kategori maksimal 500 karakter.',
    'kategori_sudah_ada'        => 'Nama kategori sudah terdaftar, silakan gunakan nama lain.',
    'kategori_tidak_ditemukan'  => 'Data kategori yang dituju tidak ditemukan.',
    'kategori_masih_digunakan'  => 'Kategori tidak dapat dihapus karena masih memiliki produk makanan terkait.',
    'gagal_simpan'              => 'Gagal menyimpan data kategori ke basis data.',
    'gagal_hapus'               => 'Gagal menghapus data kategori dari basis data.'
);

$flash = crud1AmbilFlashPesan($map_pesan, $map_error);

$page_title = 'Kelola Kategori Makanan - Pangkalan Pangan';
$extra_css = array('components.css?v=9', 'manage_categories.css?v=1');
$extra_js = array('crud1_navbar.js?v=1', 'crud1_categories.js?v=2');

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

        <div class="category-page-wrap">
            <div class="category-container">

                <div class="category-top-nav">
                    <a href="views/admin/dashboard.php" class="category-back-link">&#8592; Kembali ke Dashboard Admin</a>
                </div>

                <div class="category-header-center">
                    <h1 class="category-page-title">Kelola Kategori<br />Makanan</h1>
                </div>

                <?php if (!$conn): ?>
                    <div class="alert alert-warning">Koneksi basis data belum aktif. Data kategori tidak dapat dimuat.</div>
                <?php endif; ?>

                <?php if ($flash['error'] !== ''): ?>
                    <div class="alert alert-error"><?php echo escapeHtml($flash['error']); ?></div>
                <?php endif; ?>

                <?php if ($flash['sukses'] !== ''): ?>
                    <div class="alert alert-success"><?php echo escapeHtml($flash['sukses']); ?></div>
                <?php endif; ?>

                <div class="category-grid-layout">

                    <div class="category-form-card">
                        <div class="category-card-header">
                            <h2 class="category-card-title" id="formCardTitle"><?php echo escapeHtml($form_title_text); ?></h2>
                        </div>
                        <div class="category-form-body">
                            <form id="categoryForm" action="actions/crud1_category.php" method="post">
                                <div>
                                    <input type="hidden" id="catActionInput" name="action" value="<?php echo escapeHtml($form_action_mode); ?>" />
                                    <input type="hidden" id="catIdInput" name="id" value="<?php echo escapeHtml($form_id_val); ?>" />
                                </div>

                                <div class="cat-form-group">
                                    <label for="catNameInput" class="cat-form-label">Nama Kategori *</label>
                                    <input type="text" id="catNameInput" name="nama_kategori" class="cat-form-input" value="<?php echo escapeHtml($form_name_val); ?>" />
                                </div>

                                <div class="cat-form-group">
                                    <label for="catDescInput" class="cat-form-label">Deskripsi Kategori</label>
                                    <textarea id="catDescInput" name="deskripsi" class="cat-form-textarea" rows="4" cols="40"><?php echo escapeHtml($form_desc_val); ?></textarea>
                                </div>

                                <div class="cat-form-actions">
                                    <button type="submit" class="btn-submit-category" id="btnSubmitCategory"><?php echo escapeHtml($form_btn_text); ?></button>
                                    <button type="button" class="btn-cancel-edit" id="btnCancelEdit"<?php echo $kategori_edit ? ' style="display: block;"' : ''; ?>>Batal Ubah</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="category-table-card">
                        <div class="category-table-responsive">
                            <table class="category-data-table" id="categoriesTable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">ID</th>
                                        <th style="width: 200px;">NAMA KATEGORI</th>
                                        <th>DESKRIPSI</th>
                                        <th style="width: 130px;">JUMLAH MENU</th>
                                        <th style="width: 140px;">AKSI</th>
                                    </tr>
                                </thead>
                                <tbody id="categoriesTableBody">
                                    <?php if (empty($daftar_kategori)): ?>
                                        <tr>
                                            <td colspan="5">Belum ada kategori makanan yang terdaftar.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($daftar_kategori as $kat): ?>
                                            <?php
                                            $kat_id = (int) $kat['id'];
                                            $jumlah_menu = (int) $kat['jumlah_menu'];
                                            $desc_teks = (isset($kat['deskripsi']) && trim((string) $kat['deskripsi']) !== '') ? (string) $kat['deskripsi'] : '-';
                                            $desc_asli = isset($kat['deskripsi']) ? (string) $kat['deskripsi'] : '';
                                            ?>
                                            <tr>
                                                <td class="col-cat-id">#<?php echo escapeHtml((string) $kat_id); ?></td>
                                                <td class="col-cat-name"><?php echo escapeHtml($kat['nama_kategori']); ?></td>
                                                <td class="col-cat-desc"><?php echo escapeHtml($desc_teks); ?></td>
                                                <td>
                                                    <?php if ($jumlah_menu > 0): ?>
                                                        <span class="badge-menu-count badge-has-menu"><?php echo escapeHtml((string) $jumlah_menu); ?> Menu</span>
                                                    <?php else: ?>
                                                        <span class="badge-menu-count badge-zero-menu">0 Menu</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="cat-actions-cell">
                                                        <button type="button" class="btn-cat-edit">Ubah</button>

                                                        <?php if ($jumlah_menu > 0): ?>
                                                            <button type="button" class="btn-cat-delete-disabled" title="Kategori tidak dapat dihapus karena masih memiliki makanan">Hapus</button>
                                                        <?php else: ?>
                                                            <form action="actions/crud1_category.php" method="post" class="form-cat-delete">
                                                                <div>
                                                                    <input type="hidden" name="action" value="delete" />
                                                                    <input type="hidden" name="id" value="<?php echo escapeHtml((string) $kat_id); ?>" />
                                                                    <button type="submit" class="btn-cat-delete-active">Hapus</button>
                                                                </div>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
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
        </div>

<?php
require_once 'includes/footer.php';
?>
