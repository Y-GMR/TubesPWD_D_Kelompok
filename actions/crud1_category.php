<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/crud1_helpers.php';

require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../manage_categories.php');
    exit();
}

if (!$conn) {
    header('Location: ../manage_categories.php?error=koneksi_gagal');
    exit();
}

$action = isset($_POST['action']) ? bersihkanInput($_POST['action']) : '';
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nama_kategori = isset($_POST['nama_kategori']) ? bersihkanInput($_POST['nama_kategori']) : '';
$deskripsi = isset($_POST['deskripsi']) ? bersihkanInput($_POST['deskripsi']) : '';

$panjang_nama = crud1Len($nama_kategori);
$panjang_desc = crud1Len($deskripsi);

if ($action === 'create') {
    if ($nama_kategori === '' || $panjang_nama < 2 || $panjang_nama > 100) {
        header('Location: ../manage_categories.php?error=nama_kategori_tidak_valid');
        exit();
    }
    if ($panjang_desc > 500) {
        header('Location: ../manage_categories.php?error=deskripsi_terlalu_panjang');
        exit();
    }

    $stmt_cek = mysqli_prepare($conn, "SELECT id FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) LIMIT 1");
    if (!$stmt_cek) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_cek, "s", $nama_kategori);
    mysqli_stmt_execute($stmt_cek);
    $res_cek = mysqli_stmt_get_result($stmt_cek);
    $sudah_ada = mysqli_fetch_assoc($res_cek);
    mysqli_stmt_close($stmt_cek);

    if ($sudah_ada) {
        header('Location: ../manage_categories.php?error=kategori_sudah_ada');
        exit();
    }

    $deskripsi_db = ($deskripsi === '') ? null : $deskripsi;
    $stmt_ins = mysqli_prepare($conn, "INSERT INTO kategori (nama_kategori, deskripsi) VALUES (?, ?)");
    if (!$stmt_ins) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_ins, "ss", $nama_kategori, $deskripsi_db);
    $ok = mysqli_stmt_execute($stmt_ins);
    mysqli_stmt_close($stmt_ins);

    if ($ok) {
        header('Location: ../manage_categories.php?pesan=kategori_ditambah');
    } else {
        header('Location: ../manage_categories.php?error=gagal_simpan');
    }
    exit();
}

if ($action === 'update') {
    if ($id <= 0) {
        header('Location: ../manage_categories.php?error=id_tidak_valid');
        exit();
    }
    if ($nama_kategori === '' || $panjang_nama < 2 || $panjang_nama > 100) {
        header('Location: ../manage_categories.php?error=nama_kategori_tidak_valid&edit_id=' . $id);
        exit();
    }
    if ($panjang_desc > 500) {
        header('Location: ../manage_categories.php?error=deskripsi_terlalu_panjang&edit_id=' . $id);
        exit();
    }

    $kategori_lama = crud1CariKategoriById($conn, $id);
    if (!$kategori_lama) {
        header('Location: ../manage_categories.php?error=kategori_tidak_ditemukan');
        exit();
    }

    $stmt_cek = mysqli_prepare($conn, "SELECT id FROM kategori WHERE LOWER(nama_kategori) = LOWER(?) AND id != ? LIMIT 1");
    if (!$stmt_cek) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_cek, "si", $nama_kategori, $id);
    mysqli_stmt_execute($stmt_cek);
    $res_cek = mysqli_stmt_get_result($stmt_cek);
    $duplikat = mysqli_fetch_assoc($res_cek);
    mysqli_stmt_close($stmt_cek);

    if ($duplikat) {
        header('Location: ../manage_categories.php?error=kategori_sudah_ada&edit_id=' . $id);
        exit();
    }

    $deskripsi_db = ($deskripsi === '') ? null : $deskripsi;
    $stmt_upd = mysqli_prepare($conn, "UPDATE kategori SET nama_kategori = ?, deskripsi = ? WHERE id = ?");
    if (!$stmt_upd) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_upd, "ssi", $nama_kategori, $deskripsi_db, $id);
    $ok = mysqli_stmt_execute($stmt_upd);
    mysqli_stmt_close($stmt_upd);

    if ($ok) {
        header('Location: ../manage_categories.php?pesan=kategori_diperbarui');
    } else {
        header('Location: ../manage_categories.php?error=gagal_simpan');
    }
    exit();
}

if ($action === 'delete') {
    if ($id <= 0) {
        header('Location: ../manage_categories.php?error=id_tidak_valid');
        exit();
    }

    $kategori_lama = crud1CariKategoriById($conn, $id);
    if (!$kategori_lama) {
        header('Location: ../manage_categories.php?error=kategori_tidak_ditemukan');
        exit();
    }

    // Cek relasi hasItemsRef? sebelum menghapus kategori
    $stmt_ref = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM makanan WHERE kategori_id = ?");
    if (!$stmt_ref) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_ref, "i", $id);
    mysqli_stmt_execute($stmt_ref);
    $res_ref = mysqli_stmt_get_result($stmt_ref);
    $row_ref = mysqli_fetch_assoc($res_ref);
    mysqli_stmt_close($stmt_ref);

    $jumlah_terkait = $row_ref ? (int) $row_ref['total'] : 0;
    if ($jumlah_terkait > 0) {
        header('Location: ../manage_categories.php?error=kategori_masih_digunakan');
        exit();
    }

    $stmt_del = mysqli_prepare($conn, "DELETE FROM kategori WHERE id = ?");
    if (!$stmt_del) {
        header('Location: ../manage_categories.php?error=sistem_gagal');
        exit();
    }
    mysqli_stmt_bind_param($stmt_del, "i", $id);
    $ok = mysqli_stmt_execute($stmt_del);
    mysqli_stmt_close($stmt_del);

    if ($ok) {
        header('Location: ../manage_categories.php?pesan=kategori_dihapus');
    } else {
        header('Location: ../manage_categories.php?error=gagal_hapus');
    }
    exit();
}

header('Location: ../manage_categories.php?error=aksi_tidak_valid');
exit();
?>
