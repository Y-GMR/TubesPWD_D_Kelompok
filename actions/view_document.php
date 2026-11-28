<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

require_login();

if (!$conn) {
    http_response_code(503);
    exit('Layanan tidak tersedia.');
}

$pengguna_login_id = (int) $_SESSION['user_id'];
$pengguna_login = ambilPenggunaById($conn, $pengguna_login_id);
if (!$pengguna_login) {
    hapusSesiPengguna();
    header('Location: ../login.php?error=login_required');
    exit();
}
sinkronSesiPengguna($pengguna_login);

$target_id = isset($_GET['id']) ? (int) $_GET['id'] : $pengguna_login_id;
if ($target_id <= 0) {
    http_response_code(400);
    exit('Permintaan tidak valid.');
}

$is_admin = ($pengguna_login['peran'] === 'admin');
$is_pemilik = ($target_id === $pengguna_login_id);
if (!$is_admin && !$is_pemilik) {
    http_response_code(403);
    exit('Akses dokumen ditolak.');
}

$target_pengguna = ambilPenggunaById($conn, $target_id);
if (!$target_pengguna) {
    http_response_code(404);
    exit('Pengguna tidak ditemukan.');
}

$doc_val = trim((string) (isset($target_pengguna['dokumen_kelayakan']) ? $target_pengguna['dokumen_kelayakan'] : ''));
if ($doc_val === '' || strpos($doc_val, '..') !== false) {
    http_response_code(404);
    exit('Dokumen tidak tersedia.');
}

$doc_rel = (strpos($doc_val, 'assets/') === 0) ? $doc_val : ('assets/uploads/documents/' . $doc_val);
$base_dir = realpath(__DIR__ . '/../assets/uploads/documents');
$file_real = realpath(__DIR__ . '/../' . $doc_rel);

if ($base_dir === false || $file_real === false || !is_file($file_real)) {
    http_response_code(404);
    exit('Berkas dokumen tidak ditemukan.');
}

$base_prefix = rtrim(str_replace('\\', '/', $base_dir), '/') . '/';
$file_norm = str_replace('\\', '/', $file_real);
if (strpos($file_norm, $base_prefix) !== 0) {
    http_response_code(403);
    exit('Lokasi berkas tidak sah.');
}

$peta_mime = array(
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp'
);
$ext = strtolower(pathinfo($file_real, PATHINFO_EXTENSION));
$content_type = isset($peta_mime[$ext]) ? $peta_mime[$ext] : 'application/octet-stream';

if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo) {
        $detected = finfo_file($finfo, $file_real);
        $finfo = null;
        if (in_array($detected, $peta_mime, true)) {
            $content_type = $detected;
        }
    }
}

$nama_berkas = basename($file_real);
$nama_berkas = preg_replace('/[^a-zA-Z0-9._-]/', '_', $nama_berkas);

header('Content-Type: ' . $content_type);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: inline; filename="' . $nama_berkas . '"');
header('Content-Length: ' . filesize($file_real));
header('Cache-Control: private, no-store, max-age=0');

readfile($file_real);
exit();
?>
