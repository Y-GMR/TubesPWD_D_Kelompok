<?php
function bersihkanInput($data) {
    return trim((string) $data);
}

function escapeHtml($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function formatRupiah($angka) {
    $nilai = (int) $angka;
    if ($nilai === 0) {
        return 'Gratis';
    }
    return 'Rp ' . number_format($nilai, 0, ',', '.');
}

function ambilPenggunaById($conn, $pengguna_id) {
    $id_bersih = (int) $pengguna_id;
    $sql = "SELECT id, username, email, nama_lengkap, foto_profil, peran, dokumen_kelayakan, status_verifikasi, catatan_verifikasi, dibuat_pada "
         . "FROM pengguna WHERE id = ? LIMIT 1";
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, "i", $id_bersih);
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $data_pengguna = mysqli_fetch_assoc($hasil);
    mysqli_stmt_close($stmt);

    return $data_pengguna ? $data_pengguna : null;
}

function simpanSesiPengguna($data_pengguna) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $data_pengguna['id'];
    $_SESSION['username'] = $data_pengguna['username'];
    $_SESSION['full_name'] = $data_pengguna['nama_lengkap'];
    $_SESSION['role'] = $data_pengguna['peran'];
    $_SESSION['profile_image'] = $data_pengguna['foto_profil'];
}
?>
