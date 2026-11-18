CREATE DATABASE IF NOT EXISTS tubespwd_db;
USE tubespwd_db;

DROP TABLE IF EXISTS tiket_bantuan;
DROP TABLE IF EXISTS detail_transaksi;
DROP TABLE IF EXISTS transaksi;
DROP TABLE IF EXISTS makanan;
DROP TABLE IF EXISTS kategori;
DROP TABLE IF EXISTS pengguna;

-- tabel pengguna (admin, produsen, konsumen)
CREATE TABLE pengguna (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  kata_sandi_hash VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(100) NOT NULL,
  foto_profil VARCHAR(255) NOT NULL DEFAULT 'default_avatar.png',
  peran ENUM('admin', 'produsen', 'konsumen') NOT NULL DEFAULT 'konsumen',
  dokumen_kelayakan VARCHAR(255) NULL,
  status_verifikasi ENUM('menunggu', 'disetujui', 'ditolak') NOT NULL DEFAULT 'menunggu',
  catatan_verifikasi TEXT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- tabel kategori makanan
CREATE TABLE kategori (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_kategori VARCHAR(100) NOT NULL UNIQUE,
  deskripsi TEXT NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- tabel makanan (si produsen)
CREATE TABLE makanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produsen_id INT NOT NULL,
  kategori_id INT NOT NULL,
  nama_makanan VARCHAR(150) NOT NULL,
  deskripsi TEXT NULL,
  harga INT NOT NULL DEFAULT 0,
  stok INT NOT NULL DEFAULT 0,
  foto_makanan VARCHAR(255) NOT NULL DEFAULT 'default_makanan.png',
  kedaluwarsa_pada DATETIME NULL,
  alamat_ambil TEXT NULL,
  latitude_ambil DOUBLE NULL, -- idk how this works research later
  longitude_ambil DOUBLE NULL,
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (produsen_id) REFERENCES pengguna(id),
  FOREIGN KEY (kategori_id) REFERENCES kategori(id)
);

-- tabel transaksi klaim makanan (pick-up per produsen)
CREATE TABLE transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode_transaksi VARCHAR(32) NOT NULL UNIQUE,
  konsumen_id INT NOT NULL,
  produsen_id INT NOT NULL,
  total_harga INT NOT NULL DEFAULT 0,
  status ENUM('menunggu', 'siap_diambil', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (konsumen_id) REFERENCES pengguna(id),
  FOREIGN KEY (produsen_id) REFERENCES pengguna(id)
);

-- tabel detail_transaksi (rincian porsi per transaksi)
CREATE TABLE detail_transaksi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaksi_id INT NOT NULL,
  makanan_id INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  harga_satuan INT NOT NULL DEFAULT 0,
  subtotal INT NOT NULL DEFAULT 0,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id),
  FOREIGN KEY (makanan_id) REFERENCES makanan(id)
);

-- tabel tiket_bantuan (Customer Service oleh Admin)
CREATE TABLE tiket_bantuan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pengguna_id INT NOT NULL,
  kode_transaksi VARCHAR(32) NULL,
  pesan TEXT NOT NULL,
  balasan_admin TEXT NULL,
  status ENUM('terbuka', 'selesai') NOT NULL DEFAULT 'terbuka',
  dibuat_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pengguna_id) REFERENCES pengguna(id)
);