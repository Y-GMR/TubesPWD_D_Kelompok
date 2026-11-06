# Rancangan Basis Data & Kamus Data Relasional

**Proyek:** Tugas Besar Pemrograman Web Dasar Gasal 2026/2027  
**Kelas / Kelompok:** Kelas D / TubesPWD_D_Kelompok  
**Institusi:** Program Studi Informatika, Fakultas Teknologi Industri, Universitas Atma Jaya Yogyakarta  
**Versi Dokumen:** 1.0.0 (Scaffolding Rilis)  
**Mesin Basis Data:** MySQL 8.0+ / MariaDB 10.4+ (Bawaan XAMPP)  
**Skema Bawaan:** `tubespwd_db`  

---

## 1. Tinjauan Arsitektur & Konteks Sistem

Skema basis data relasional ini dirancang secara terstruktur untuk memenuhi seluruh kriteria wajib dan rubrik bonus Tugas Besar PWD 2026/2027:
1. **Inti Otentikasi & Profil Pengguna:** Penyimpanan akun pengguna terenkripsi menggunakan algoritma bcrypt, pembagian peran akses (`admin` dan `customer`), serta dukungan unggahan foto profil avatar.
2. **CRUD 1 (Master Katalog Barang):** Struktur katalog hierarkis melalui relasi `categories` (induk) dan `items` (anak) untuk mendukung pengelolaan inventaris, harga satuan, dan pemilahan kategori.
3. **CRUD 2 (Siklus Transaksi & Geolokasi):** Pencatatan pesanan yang menghubungkan pembeli (`users`), rincian baris barang (`transaction_details`), stok barang (`items`), serta titik koordinat GPS pengiriman (`latitude`, `longitude`) untuk memenuhi kriteria Bonus 2 (Geolokasi).
4. **Integritas Referensial:** Menegakkan batasan kunci asing (foreign key) mesin InnoDB dengan aturan `CASCADE` dan `RESTRICT` terukur guna mencegah data yatim (orphan rows) sekaligus melindungi audit historis transaksi.

---

## 2. Diagram Hubungan Entitas (Entity Relationship Diagram - ERD)

```
+-----------------------------------------------------------------------------------+
|                            RELASI ENTITAS BASIS DATA                              |
+-----------------------------------------------------------------------------------+

     +-----------------------+                    +---------------------------+
     |      categories       |                    |           users           |
     +-----------------------+                    +---------------------------+
     | PK  id                |                    | PK  id                    |
     |     name (UQ)         |                    |     username (UQ)         |
     |     description       |                    |     email (UQ)            |
     |     created_at        |                    |     password_hash         |
     +-----------+-----------+                    |     full_name             |
                 | 1                              |     profile_image         |
                 |                                |     role (admin/customer) |
                 | N                              |     created_at            |
     +-----------v-----------+                    |     updated_at            |
     |         items         |                    +-------------+-------------+
     +-----------------------+                                  | 1
     | PK  id                |                                  |
     | FK  category_id       |                                  |
     |     name              |                                  |
     |     description       |                                  |
     |     price             |                                  |
     |     stock             |                                  |
     |     image_url         |                                  |
     |     created_at        |                                  |
     |     updated_at        |                                  |
     +-----------+-----------+                                  | N
                 | 1                               +------------v------------+
                 |                                 |       transactions      |
                 |                                 +-------------------------+
                 |                                 | PK  id                  |
                 |                                 |     transaction_code(UQ)|
                 |                                 | FK  user_id             |
                 |                                 |     total_amount        |
                 |                                 |     latitude            |
                 |                                 |     longitude           |
                 |                                 |     status              |
                 |                                 |     created_at          |
                 |                                 |     updated_at          |
                 |                                 +------------+------------+
                 |                                              | 1
                 | N                                            |
     +-----------v----------------------------------------------v------------+
     |                              transaction_details                      |
     +-----------------------------------------------------------------------+
     | PK  id                                                                |
     | FK  transaction_id                                                    |
     | FK  item_id                                                           |
     |     quantity                                                          |
     |     unit_price                                                        |
     |     subtotal                                                          |
     +-----------------------------------------------------------------------+
```

### Ringkasan Kardinalitas Relasi
- `categories` (1) ke `items` (N): Satu kategori dapat memayungi nol atau banyak item barang. Setiap item wajib terikat ke tepat satu kategori.
- `users` (1) ke `transactions` (N): Satu pengguna pelanggan dapat memiliki nol atau banyak riwayat transaksi. Setiap transaksi wajib dimiliki oleh tepat satu pengguna.
- `transactions` (1) ke `transaction_details` (N): Satu transaksi pesanan memuat satu atau banyak baris rincian barang belanjaan.
- `items` (1) ke `transaction_details` (N): Satu item barang dapat tercatat berulang kali dalam berbagai baris rincian transaksi yang berbeda.

---

## 3. Kamus Data (Data Dictionary)

### 3.1. Tabel: `users`
Menyimpan kredensial otentikasi akun, data pribadi profil, dan penentuan peran hak akses pengguna.

| Nama Kolom | Tipe Data | Nullable | Nilai Bawaan | Batasan / Kunci | Keterangan Fungsional |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Tidak | AUTO_INCREMENT | `PRIMARY KEY` | Kunci utama pengidentifikasi unik akun pengguna. |
| `username` | `VARCHAR(50)` | Tidak | Tanpa default | `UNIQUE` | Nama pengguna unik untuk login (alfanumerik + garis bawah). Diperiksa via AJAX. |
| `email` | `VARCHAR(100)` | Tidak | Tanpa default | `UNIQUE` | Alamat surat elektronik terverifikasi RFC untuk login dan komunikasi. |
| `password_hash` | `VARCHAR(255)` | Tidak | Tanpa default | Tidak ada | Hash password terenkripsi via fungsi PHP `password_hash(..., PASSWORD_BCRYPT)`. |
| `full_name` | `VARCHAR(100)` | Tidak | Tanpa default | Tidak ada | Nama lengkap pengguna untuk tampilan antarmuka. |
| `profile_image` | `VARCHAR(255)` | Tidak | `'default_avatar.png'` | Tidak ada | Nama berkas foto avatar yang tersimpan pada direktori `uploads/avatars/`. |
| `role` | `ENUM('admin','customer')` | Tidak | `'customer'` | Tidak ada | Peran otorisasi penentu hak akses sistem (administrator atau pelanggan). |
| `created_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu pembuatan akun registrasi. |
| `updated_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu pembaruan otomatis saat data profil disunting. |

---

### 3.2. Tabel: `categories`
Klasifikasi master pengelompokan barang untuk CRUD 1 (Katalog Barang).

| Nama Kolom | Tipe Data | Nullable | Nilai Bawaan | Batasan / Kunci | Keterangan Fungsional |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Tidak | AUTO_INCREMENT | `PRIMARY KEY` | Kunci utama pengidentifikasi unik kategori. |
| `name` | `VARCHAR(100)` | Tidak | Tanpa default | `UNIQUE` | Nama kategori produk (contoh: Elektronik, Buku Kuliah, Merchandise). |
| `description` | `TEXT` | Ya | `NULL` | Tidak ada | Penjelasan deskriptif mengenai cakupan barang dalam kategori ini. |
| `created_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu penambahan kategori ke sistem. |

---

### 3.3. Tabel: `items`
Daftar produk inventaris barang yang dikelola dalam modul CRUD 1.

| Nama Kolom | Tipe Data | Nullable | Nilai Bawaan | Batasan / Kunci | Keterangan Fungsional |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Tidak | AUTO_INCREMENT | `PRIMARY KEY` | Kunci utama pengidentifikasi unik barang. |
| `category_id` | `INT UNSIGNED` | Tidak | Tanpa default | `FOREIGN KEY` (Ref: `categories.id`) | Kunci asing relasi ke kategori induk. Berperilaku `ON DELETE RESTRICT`. |
| `name` | `VARCHAR(150)` | Tidak | Tanpa default | `INDEX` | Nama komersial barang. Diberi indeks untuk pencarian cepat. |
| `description` | `TEXT` | Ya | `NULL` | Tidak ada | Spesifikasi rincian teknis atau informasi produk. |
| `price` | `DECIMAL(12, 2)` | Tidak | `0.00` | `INDEX` | Harga jual per unit dalam mata uang Rupiah. Diberi indeks untuk pengurutan harga. |
| `stock` | `INT` | Tidak | `0` | Tidak ada | Jumlah stok fisik barang yang siap dipesan. |
| `image_url` | `VARCHAR(255)` | Tidak | `'default_item.png'` | Tidak ada | Nama berkas foto produk yang disimpan pada `uploads/items/`. |
| `created_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu barang ditambahkan ke katalog. |
| `updated_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu pembaruan otomatis saat data barang diubah. |

---

### 3.4. Tabel: `transactions`
Data induk transaksi pemesanan barang pada modul CRUD 2.

| Nama Kolom | Tipe Data | Nullable | Nilai Bawaan | Batasan / Kunci | Keterangan Fungsional |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Tidak | AUTO_INCREMENT | `PRIMARY KEY` | Kunci utama internal transaksi. |
| `transaction_code` | `VARCHAR(32)` | Tidak | Tanpa default | `UNIQUE` | Kode nota transaksi unik (contoh: `TRX-20261106-0001`). |
| `user_id` | `INT UNSIGNED` | Tidak | Tanpa default | `FOREIGN KEY` (Ref: `users.id`) | Kunci asing relasi ke pembeli. Berperilaku `ON DELETE RESTRICT`. |
| `total_amount` | `DECIMAL(12, 2)` | Tidak | `0.00` | Tidak ada | Total nominal kotor tagihan pesanan dalam Rupiah. |
| `latitude` | `DECIMAL(10, 8)` | Ya | `NULL` | Tidak ada | Titik koordinat latitude GPS pengiriman (Bonus 2: Geolokasi). |
| `longitude` | `DECIMAL(11, 8)` | Ya | `NULL` | Tidak ada | Titik koordinat longitude GPS pengiriman (Bonus 2: Geolokasi). |
| `status` | `ENUM(...)` | Tidak | `'pending'` | `INDEX` | Status pesanan: `'pending'`, `'paid'`, `'shipped'`, `'completed'`, `'cancelled'`. |
| `created_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP` | `INDEX` | Catatan waktu saat pesanan dibuat oleh pelanggan. |
| `updated_at` | `TIMESTAMP` | Tidak | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | Tidak ada | Catatan waktu pembaruan status operasional transaksi. |

---

### 3.5. Tabel: `transaction_details`
Baris rincian barang belanjaan untuk setiap transaksi pesanan pada CRUD 2.

| Nama Kolom | Tipe Data | Nullable | Nilai Bawaan | Batasan / Kunci | Keterangan Fungsional |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | `INT UNSIGNED` | Tidak | AUTO_INCREMENT | `PRIMARY KEY` | Kunci utama baris rincian item. |
| `transaction_id` | `INT UNSIGNED` | Tidak | Tanpa default | `FOREIGN KEY` (Ref: `transactions.id`) | Kunci asing ke induk transaksi. Berperilaku `ON DELETE CASCADE`. |
| `item_id` | `INT UNSIGNED` | Tidak | Tanpa default | `FOREIGN KEY` (Ref: `items.id`) | Kunci asing ke master produk. Berperilaku `ON DELETE RESTRICT`. |
| `quantity` | `INT UNSIGNED` | Tidak | `1` | Tidak ada | Jumlah unit barang yang dibeli (wajib >= 1). |
| `unit_price` | `DECIMAL(12, 2)` | Tidak | `0.00` | Tidak ada | Snapshot harga jual historis saat transaksi dieksekusi. |
| `subtotal` | `DECIMAL(12, 2)` | Tidak | `0.00` | Tidak ada | Nilai total perkalian unit (`quantity * unit_price`). |

---

## 4. Integritas Referensial & Strategi Penghapusan Data

1. **Penghapusan Kategori (`categories -> items`):**
   - Dikonfigurasi dengan `ON DELETE RESTRICT`. Sebuah kategori tidak dapat dihapus apabila masih terdapat barang aktif yang terikat padanya. Hal ini mencegah barang kehilangan kelompok induknya.
2. **Penghapusan Pengguna (`users -> transactions`):**
   - Dikonfigurasi dengan `ON DELETE RESTRICT`. Akun pengguna yang memiliki rekam jejak pesanan historis dilarang dihapus dari basis data demi melindungi integritas audit transaksi.
3. **Penghapusan Transaksi (`transactions -> transaction_details`):**
   - Dikonfigurasi dengan `ON DELETE CASCADE`. Jika sebuah data induk transaksi dihapus oleh admin, seluruh baris rincian barang di tabel `transaction_details` otomatis terhapus bersamaan untuk mencegah data yatim.
4. **Penghapusan Item Barang (`items -> transaction_details`):**
   - Dikonfigurasi dengan `ON DELETE RESTRICT`. Produk barang yang pernah dipesan dalam transaksi masa lalu dilarang dihapus dari tabel master. Jika barang tersebut sudah tidak dijual, pengelola cukup mengubah stoknya menjadi 0.

---

## 5. Strategi Pengindeksan & Optimasi Kueri

| Nama Tabel | Nama Indeks | Kolom yang Diindeks | Tipe Indeks | Sasaran Kinerja Kueri |
| :--- | :--- | :--- | :--- | :--- |
| `users` | `PRIMARY` | `id` | B-Tree Terklaster | Pencarian cepat ID sesi login dan profil pengguna |
| `users` | `uq_users_username` | `username` | B-Tree Unik | Verifikasi kredensial login & validasi instan ketersediaan username via AJAX |
| `users` | `uq_users_email` | `email` | B-Tree Unik | Validasi keunikan alamat email saat registrasi pengguna baru |
| `categories` | `uq_categories_name` | `name` | B-Tree Unik | Memastikan tidak ada duplikasi nama kategori |
| `items` | `idx_items_category_id`| `category_id` | B-Tree Standar | Mempercepat penyaringan daftar barang berdasarkan kategori |
| `items` | `idx_items_name` | `name` | B-Tree Standar | Mempercepat kueri pencarian kata kunci nama barang (`LIKE 'kata%'`) |
| `items` | `idx_items_price` | `price` | B-Tree Standar | Mempercepat pemfilteran rentang harga dan pengurutan barang |
| `transactions`| `uq_transactions_code` | `transaction_code` | B-Tree Unik | Pencarian nota faktur pesanan secara langsung |
| `transactions`| `idx_transactions_user_id`| `user_id` | B-Tree Standar | Mengoptimalkan kueri riwayat pesanan milik pelanggan tertentu |
| `transactions`| `idx_transactions_status` | `status` | B-Tree Standar | Mempercepat penyaringan transaksi berdasarkan status pada dashboard admin |
| `transactions`| `idx_transactions_created_at`| `created_at` | B-Tree Standar | Pengurutan kronologis transaksi dan rekap laporan berkala |
| `transaction_details`| `idx_tdetails_transaction_id`| `transaction_id`| B-Tree Standar | Mengambil seluruh baris rincian barang untuk satu pesanan spesifik |
| `transaction_details`| `idx_tdetails_item_id` | `item_id` | B-Tree Standar | Perhitungan agregasi volume penjualan tiap barang |

---

## 6. Prosedur Pemasangan & Migrasi Basis Data

Pengembang dapat menginisialisasi skema basis data secara lokal melalui antarmuka phpMyAdmin atau Terminal MySQL:

```bash
# Impor melalui baris perintah MySQL
mysql -u root -p < sql/schema.sql
```

Kueri verifikasi setelah proses impor selesai:
```sql
USE tubespwd_db;
SHOW TABLES;
SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema = 'tubespwd_db';
```
