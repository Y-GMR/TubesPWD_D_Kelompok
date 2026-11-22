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

INSERT INTO pengguna (id, username, email, kata_sandi_hash, nama_lengkap, foto_profil, peran, dokumen_kelayakan, status_verifikasi, catatan_verifikasi) VALUES
(1, 'admin', 'admin@pangkalanpangan.id', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'Administrator Pangkalan Pangan', 'assets/images/avatar_consumer.png', 'admin', NULL, 'disetujui', 'Akun administrator utama'),
(2, 'dunkin_sudirman', 'dunkin@pangkalanpangan.id', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'Dunkin Donuts Sudirman', 'assets/images/merchant_dunkin.png', 'produsen', NULL, 'disetujui', 'Mitra produsen terverifikasi'),
(3, 'breadtalk_seturan', 'breadtalk@pangkalanpangan.id', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'BreadTalk Seturan', 'assets/images/merchant_breadtalk.png', 'produsen', NULL, 'disetujui', 'Mitra produsen terverifikasi'),
(4, 'pizzahut_gejayan', 'pizzahut@pangkalanpangan.id', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'Pizza Hut Gejayan', 'assets/images/merchant_pizzahut.png', 'produsen', NULL, 'disetujui', 'Mitra produsen terverifikasi'),
(5, 'kfc_babarsari', 'kfc@pangkalanpangan.id', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'KFC Babarsari', 'assets/images/merchant_kfc.png', 'produsen', NULL, 'disetujui', 'Mitra produsen terverifikasi'),
(6, 'iwang_petra', 'iwangpetra@gmail.com', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'Iwang Petra', 'assets/images/avatar_consumer.png', 'konsumen', 'sktm_petra.pdf', 'disetujui', 'Dokumen SKTM valid'),
(7, 'budi_santoso', 'budi@gmail.com', '$2y$10$Vq1euJ7.GWbpTs.80tRS3eJZO64QdcgGUazsdhNIiKyxt.HymnuC6', 'Budi Santoso', 'assets/images/avatar_consumer.png', 'konsumen', 'kks_budi.jpg', 'menunggu', NULL);

INSERT INTO kategori (id, nama_kategori, deskripsi) VALUES
(1, 'Roti & Kue', 'Berbagai produk roti, donat, dan kue layak konsumsi.'),
(2, 'Gorengan', 'Lauk pauk dan kudapan goreng siap santap.'),
(3, 'Tradisional', 'Hidangan nusantara dan jajanan pasar tradisional.'),
(4, 'Cepat Saji', 'Hidangan cepat saji dari gerai mitra terdaftar.'),
(5, 'Minuman', 'Minuman kemasan dan minuman segar layak konsumsi.');

INSERT INTO makanan (id, produsen_id, kategori_id, nama_makanan, deskripsi, harga, stok, foto_makanan, kedaluwarsa_pada, alamat_ambil, latitude_ambil, longitude_ambil) VALUES
(1, 2, 1, 'Donat Cokelat (Paket 3 Pcs)', 'Paket donat cokelat surplus harian dalam kemasan bersih.', 7000, 5, 'assets/images/menu_donat_cokelat.png', '2026-11-22 21:00:00', 'Jl. Jend. Sudirman No. 54, Gowongan, Kec. Jetis, Kota Yogyakarta, DIY 55233', -7.7829, 110.3671),
(2, 2, 1, 'Donat Glaze (Paket 3 Pcs)', 'Paket donat glaze lembut siap diambil hari ini.', 7000, 5, 'assets/images/menu_donat_glaze.png', '2026-11-22 21:00:00', 'Jl. Jend. Sudirman No. 54, Gowongan, Kec. Jetis, Kota Yogyakarta, DIY 55233', -7.7829, 110.3671),
(3, 2, 1, 'Donat Oreo (Paket 3 Pcs)', 'Paket donat taburan oreo layak konsumsi.', 6000, 2, 'assets/images/menu_donat_oreo.png', '2026-11-22 21:00:00', 'Jl. Jend. Sudirman No. 54, Gowongan, Kec. Jetis, Kota Yogyakarta, DIY 55233', -7.7829, 110.3671),
(4, 3, 1, 'Fire Flosss (Paket 2 Pcs)', 'Roti abon pedas manis surplus produksi sore.', 8000, 5, 'assets/images/menu_fire_flosss.png', '2026-11-22 21:00:00', 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7735, 110.4092),
(5, 3, 1, 'Choco Bun', 'Roti lembut isi cokelat siap konsumsi.', 7500, 5, 'assets/images/menu_choco_bun.png', '2026-11-22 21:00:00', 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7735, 110.4092),
(6, 3, 1, 'Cheese Boat', 'Roti keju panggang surplus etalase harian.', 9000, 5, 'assets/images/menu_cheese_boat.png', '2026-11-22 21:00:00', 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7735, 110.4092),
(7, 4, 4, 'Pepperoni Slice', 'Potongan pizza pepperoni hangat siap ambil.', 12000, 2, 'assets/images/menu_pepperoni.png', '2026-11-22 21:30:00', 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7654, 110.3891),
(8, 4, 4, 'Cheese Slice', 'Potongan pizza keju mozarella.', 11000, 3, 'assets/images/menu_cheese_slice.png', '2026-11-22 21:30:00', 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7654, 110.3891),
(9, 4, 4, 'Garlic Bread', 'Roti bawang panggang pendamping hidangan.', 9000, 3, 'assets/images/menu_garlic_bread.png', '2026-11-22 21:30:00', 'Jl. Affandi No. 25, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7654, 110.3891),
(10, 5, 2, 'Ayam Crispy (Paket 1 Porsi)', 'Ayam goreng renyah surplus operasional.', 11000, 3, 'assets/images/menu_ayam_crispy.png', '2026-11-22 22:00:00', 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7788, 110.4123),
(11, 5, 2, 'Ayam Original (Paket 1 Porsi)', 'Ayam goreng bumbu original layak santap.', 11000, 3, 'assets/images/menu_ayam_original.png', '2026-11-22 22:00:00', 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7788, 110.4123),
(12, 5, 2, 'Perkedel', 'Perkedel kentang gurih siap ambil.', 5000, 4, 'assets/images/menu_perkedel.png', '2026-11-22 22:00:00', 'Jl. Babarsari No. 8, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7788, 110.4123),
(13, 3, 3, 'Paket Kue Tradisional Harian', 'Aneka kue basah tradisional donasi gratis.', 0, 8, 'assets/images/menu_choco_bun.png', '2026-11-22 20:00:00', 'Jl. Seturan Raya No. 12, Caturtunggal, Kec. Depok, Kabupaten Sleman, DIY 55281', -7.7735, 110.4092),
(14, 2, 5, 'Paket Air Mineral dan Teh Hangat', 'Minuman pendamping gratis untuk penerima manfaat.', 0, 10, 'assets/images/merchant_dunkin.png', '2026-11-22 21:00:00', 'Jl. Jend. Sudirman No. 54, Gowongan, Kec. Jetis, Kota Yogyakarta, DIY 55233', -7.7829, 110.3671);

INSERT INTO transaksi (id, kode_transaksi, konsumen_id, produsen_id, total_harga, status) VALUES
(1, 'PP-20261122-001', 6, 2, 14000, 'siap_diambil'),
(2, 'PP-20261121-002', 6, 3, 8000, 'selesai');

INSERT INTO detail_transaksi (id, transaksi_id, makanan_id, jumlah, harga_satuan, subtotal) VALUES
(1, 1, 2, 2, 7000, 14000),
(2, 2, 4, 1, 8000, 8000);

INSERT INTO tiket_bantuan (id, pengguna_id, kode_transaksi, pesan, balasan_admin, status) VALUES
(1, 6, 'PP-20261122-001', 'Apakah pengambilan paket donat bisa diwakilkan anggota keluarga?', NULL, 'terbuka'),
(2, 7, NULL, 'Dokumen SKTM sudah diunggah, mohon bantuannya untuk verifikasi akun.', 'Terima kasih, dokumen Anda sedang masuk antrean pemeriksaan admin.', 'selesai');