# Spesifikasi Kontrak API & Pengendali Aksi Backend

**Proyek:** Tugas Besar Pemrograman Web Dasar Gasal 2026/2027  
**Kelas / Kelompok:** Kelas D / TubesPWD_D_Kelompok  
**Institusi:** Program Studi Informatika, Fakultas Teknologi Industri, Universitas Atma Jaya Yogyakarta  
**Versi Dokumen:** 1.0.0 (Scaffolding Rilis)  
**Format Komunikasi:** Pengiriman Formulir Standar (`application/x-www-form-urlencoded`), Unggah Berkas Multipart (`multipart/form-data`), Respons Asinkron JSON (`application/json`)  

---

## 1. Standar Perancangan Antarmuka Umum

1. **Struktur Pembungkus Respons JSON yang Konsisten:**
   Seluruh endpoint berbasis JSON mengembalikan struktur pembungkus standar:
   ```json
   {
     "status": "success | error",
     "message": "Pesan deskriptif hasil eksekusi aksi",
     "data": {}
   }
   ```
2. **Pemetaan Kode Status HTTP:**
   - `200 OK`: Permintaan berhasil diproses secara normal.
   - `201 Created`: Sumber daya entitas baru berhasil disimpan ke basis data.
   - `400 Bad Request`: Format muatan tidak valid, parameter wajib tidak lengkap, atau gagal validasi semantik.
   - `401 Unauthorized`: Kredensial tidak valid atau pengguna belum melakukan otentikasi login.
   - `403 Forbidden`: Pengguna terotentikasi tidak memiliki hak akses (misal: pelanggan mengakses aksi admin).
   - `404 Not Found`: Data entitas yang ditargetkan tidak ditemukan di basis data.
   - `409 Conflict`: Terjadi pelanggaran keunikan data (misal: username atau email sudah terdaftar).
   - `500 Internal Server Error`: Terjadi kegagalan server atau basis data yang tidak terduga (pesan teknis disanitasi).
3. **Pengiriman Formulir Biasa vs Permintaan AJAX:**
   - Pengiriman formulir XHTML biasa akan memproses data, mengatur pesan flash pada sesi (`$_SESSION['flash_success']` atau `$_SESSION['flash_error']`), lalu melakukan pengalihan HTTP 302 kembali ke halaman tampilan.
   - Permintaan asinkron AJAX akan selalu mengembalikan kode status HTTP eksplisit dan header `Content-Type: application/json`.

---

## 2. Endpoint Otentikasi & Manajemen Pengguna

### 2.1. Pemeriksaan Real-Time Ketersediaan Username & Email (Bonus 3)
Memungkinkan formulir pendaftaran akun memeriksa ketersediaan username atau email secara asinkron tanpa memuat ulang halaman sebelum tombol kirim ditekan.

- **Kanal Discord:** `#role-frontend-interactivity` (Peran 5: Interaktivitas Frontend & Paket Bonus)
- **Rute URL:** `GET /actions/check_user_ajax.php`
- **Tingkat Akses:** Terbuka untuk Publik
- **Parameter Kueri (Query Parameters):**
  | Parameter | Tipe Data | Wajib | Keterangan & Aturan Validasi |
  | :--- | :--- | :--- | :--- |
  | `field` | `string` | Ya | Wajib bernilai `'username'` atau `'email'`. |
  | `value` | `string` | Ya | Nilai teks yang ingin diperiksa keberadaannya di tabel basis data. |

- **Contoh Respons Sukses (Data Tersedia) [HTTP 200 OK]:**
  ```json
  {
    "status": "success",
    "available": true,
    "message": "Username tersedia."
  }
  ```

- **Contoh Respons Konflik (Data Sudah Terpakai) [HTTP 200 OK atau 409 Conflict]:**
  ```json
  {
    "status": "error",
    "available": false,
    "message": "Username sudah digunakan, silakan pilih yang lain."
  }
  ```

- **Contoh Respons Kesalahan Validasi [HTTP 400 Bad Request]:**
  ```json
  {
    "status": "error",
    "message": "Parameter tidak valid. Field harus bernilai 'username' atau 'email'."
  }
  ```

---

### 2.2. Aksi Registrasi Pengguna Baru
Memproses pendaftaran akun baru, memvalidasi aturan format regex, mengenkripsi password dengan algoritma bcrypt, dan menyimpan data akun ke tabel `users`.

- **Kanal Discord:** `#role-auth-core` (Peran 1: Inti Otentikasi & Akun)
- **Rute URL:** `POST /actions/auth_register.php`
- **Tingkat Akses:** Publik (Pengguna yang sudah login akan dialihkan ke beranda)
- **Content-Type:** `application/x-www-form-urlencoded`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `username` | `string` | Ya | 4-30 karakter, alfanumerik + garis bawah (`^[a-zA-Z0-9_]{4,30}$`) | Username unik pilihan pengguna |
  | `email` | `string` | Ya | Format email valid (`filter_var(..., FILTER_VALIDATE_EMAIL)`) | Alamat email unik pengguna |
  | `full_name` | `string` | Ya | 2-100 karakter, alfabet, spasi, tanda baca standar | Nama lengkap pengguna |
  | `password` | `string` | Ya | Minimal 8 karakter, wajib kombinasi huruf dan angka | Kata sandi akun |
  | `confirm_password` | `string` | Ya | Wajib sama persis dengan kolom `password` | Konfirmasi pengulangan kata sandi |

- **Perilaku Saat Berhasil:**
  - Password dienkripsi menggunakan `password_hash($password, PASSWORD_BCRYPT)`.
  - Data pengguna baru disimpan ke basis data dengan peran bawaan `role = 'customer'`.
  - Mengatur `$_SESSION['flash_success'] = 'Registrasi berhasil! Silakan login.'`.
  - Melakukan pengalihan HTTP 302 ke `login.php`.

- **Perilaku Saat Gagal Validasi:**
  - Menyimpan input lama (kecuali password) ke `$_SESSION['old_input']`.
  - Mengatur `$_SESSION['flash_error'] = 'Pesan error spesifik.'`.
  - Melakukan pengalihan HTTP 302 kembali ke `register.php`.

---

### 2.3. Aksi Login Pengguna
Memverifikasi kredensial login, menetapkan variabel sesi otentikasi, dan meregenerasi ID sesi pengguna.

- **Kanal Discord:** `#role-auth-core` (Peran 1: Inti Otentikasi & Akun)
- **Rute URL:** `POST /actions/auth_login.php`
- **Tingkat Akses:** Terbuka untuk Publik
- **Content-Type:** `application/x-www-form-urlencoded`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `username_or_email` | `string` | Ya | Teks tidak boleh kosong | Username atau alamat email terdaftar |
  | `password` | `string` | Ya | Teks tidak boleh kosong | Kata sandi akun yang akan diuji |

- **Perilaku Saat Berhasil:**
  - Memverifikasi kecocokan hash menggunakan `password_verify($password, $user['password_hash'])`.
  - Memanggil `session_regenerate_id(true)` untuk pengamanan sesi.
  - Mengisi variabel sesi: `$_SESSION['user_id']`, `$_SESSION['username']`, `$_SESSION['role']`, `$_SESSION['full_name']`, `$_SESSION['profile_image']`.
  - Jika `role === 'admin'`, dialihkan ke `admin/dashboard.php`; jika tidak, dialihkan ke `index.php`.

- **Perilaku Saat Gagal:**
  - Mengatur `$_SESSION['flash_error'] = 'Kombinasi username/email dan password tidak sesuai.'`.
  - Melakukan pengalihan HTTP 302 kembali ke `login.php`.

---

### 2.4. Aksi Logout Pengguna
Menutup dan mengakhiri sesi aktif pengguna secara aman.

- **Kanal Discord:** `#role-auth-core` (Peran 1: Inti Otentikasi & Akun)
- **Rute URL:** `POST /actions/auth_logout.php` (atau `GET /actions/auth_logout.php`)
- **Tingkat Akses:** Wajib Login Terotentikasi
- **Perilaku:**
  - Mengosongkan seluruh isi larik `$_SESSION`.
  - Menghancurkan sesi server dengan `session_destroy()`.
  - Menghapus cookie sesi pada peramban klien.
  - Melakukan pengalihan HTTP 302 ke `login.php?logout=success`.

---

### 2.5. Aksi Pembaruan Profil & Foto Avatar (Bonus 4)
Memperbarui informasi pribadi akun serta memproses penggantian berkas foto profil pengguna.

- **Kanal Discord:** `#role-auth-core` (Peran 1: Inti Otentikasi & Akun)
- **Rute URL:** `POST /actions/auth_profile_update.php`
- **Tingkat Akses:** Wajib Login (`require_login()`)
- **Content-Type:** `multipart/form-data`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `full_name` | `string` | Ya | 2-100 karakter alfabet dan spasi | Nama lengkap terbaru |
  | `email` | `string` | Ya | Format email valid, unik terhadap pengguna lain | Alamat email terbaru |
  | `current_password` | `string` | Opsional | Diisi jika ingin mengganti kata sandi | Kata sandi saat ini untuk verifikasi |
  | `new_password` | `string` | Opsional | Minimal 8 karakter huruf dan angka | Kata sandi baru yang diinginkan |
  | `profile_image` | `file` | Opsional | Tipe MIME gambar (`image/jpeg`, `image/png`, `image/webp`), maks 2MB | Berkas gambar foto avatar |

- **Perilaku Saat Berhasil:**
  - Jika berkas foto disertakan, memvalidasi magic bytes MIME, menyimpan ke `uploads/avatars/<hash>.<ext>`, memperbarui basis data, dan menyinkronkan `$_SESSION['profile_image']`.
  - Jika kolom kata sandi diisi, memverifikasi kata sandi lama terlebih dahulu sebelum memperbarui hash bcrypt baru.
  - Mengatur `$_SESSION['flash_success'] = 'Profil berhasil diperbarui.'`.
  - Melakukan pengalihan HTTP 302 ke `profile.php`.

---

## 3. CRUD 1: Manajemen Master Katalog (Kategori & Item)

### 3.1. Endpoint Aksi Kategori
Mengelola taksonomi dan pengelompokan produk katalog.

- **Kanal Discord:** `#role-crud1-catalog` (Peran 2: Master Data & Katalog)
- **Rute URL:** `POST /actions/crud1_category.php`
- **Tingkat Akses:** Administrator Saja (`require_admin()`)
- **Content-Type:** `application/x-www-form-urlencoded`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `action` | `string` | Ya | Pilihan nilai: `'create'`, `'update'`, `'delete'` | Jenis operasi CRUD |
  | `id` | `integer` | Wajib untuk `'update'`, `'delete'` | Bilangan bulat positif | ID kategori yang dikelola |
  | `name` | `string` | Wajib untuk `'create'`, `'update'` | 2-100 karakter, unik | Nama kategori produk |
  | `description` | `string` | Opsional | Maksimal 500 karakter | Keterangan deskripsi kategori |

- **Aturan Operasi & Logika Bisnis:**
  - `create`: Menyimpan data baru ke tabel `categories`. Pengalihan ke `admin/manage_categories.php`.
  - `update`: Memperbarui `name` dan `description` kategori yang telah ada.
  - `delete`: Memeriksa apakah masih ada item aktif yang terkait dengan kategori ini. Jika ada, batalkan penghapusan dengan pesan error: `"Kategori tidak dapat dihapus karena masih memiliki produk terkait."`. Jika kosong, baris data dihapus.

---

### 3.2. Endpoint Aksi Katalog Item
Mengelola data produk inventaris, harga satuan, ketersediaan stok, dan foto barang.

- **Kanal Discord:** `#role-crud1-catalog` (Peran 2: Master Data & Katalog)
- **Rute URL:** `POST /actions/crud1_item.php`
- **Tingkat Akses:** Administrator Saja (`require_admin()`)
- **Content-Type:** `multipart/form-data`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `action` | `string` | Ya | Pilihan nilai: `'create'`, `'update'`, `'delete'` | Jenis operasi CRUD |
  | `id` | `integer` | Wajib untuk `'update'`, `'delete'` | Bilangan bulat positif | ID barang inventaris |
  | `category_id` | `integer` | Wajib untuk `'create'`, `'update'` | Harus terdaftar pada `categories.id` | Relasi ID kategori induk |
  | `name` | `string` | Wajib untuk `'create'`, `'update'` | 2-150 karakter | Nama barang / produk |
  | `description` | `string` | Opsional | Teks uraian barang | Rincian spesifikasi barang |
  | `price` | `decimal` | Wajib untuk `'create'`, `'update'` | Angka numerik, nilai >= 0.00 | Harga satuan dalam rupiah |
  | `stock` | `integer` | Wajib untuk `'create'`, `'update'` | Bilangan bulat >= 0 | Jumlah stok fisik barang |
  | `item_image` | `file` | Opsional | Tipe berkas JPG/PNG/WEBP, maks 2MB | Berkas gambar foto produk |

- **Aturan Operasi & Logika Bisnis:**
  - `create`: Mengunggah gambar (atau menggunakan default `default_item.png`), lalu menyimpan data item ke tabel `items`.
  - `update`: Memperbarui informasi item; jika gambar baru diunggah, hapus berkas gambar fisik lama (kecuali default) dan simpan jalur berkas baru.
  - `delete`: Memeriksa apakah item tercatat dalam detail transaksi riwayat (`transaction_details`). Jika tercatat, tolak penghapusan dengan pesan `"Produk tidak dapat dihapus karena tercatat dalam transaksi historis. Nonaktifkan dengan menyetel stok ke 0."`.

---

## 4. CRUD 2: Transaksi & Checkout Geolokasi

### 4.1. Aksi Checkout Pesanan (Bonus 2: Geolokasi)
Memproses pembelian barang dari keranjang belanja pengguna, memvalidasi ketersediaan stok, menghitung total pembayaran, mencatat koordinat pengiriman GPS, dan menyimpan seluruh item transaksi secara atomik dalam satu transaksi basis data.

- **Kanal Discord:** `#role-crud2-transactions` (Peran 3: Transaksi & Geolokasi)
- **Rute URL:** `POST /actions/crud2_transaction.php`
- **Tingkat Akses:** Pelanggan Terotentikasi (`require_login()`)
- **Content-Type:** `application/x-www-form-urlencoded`
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `action` | `string` | Ya | Nilai tetap: `'checkout'` | Menginisiasi transaksi order |
  | `latitude` | `float` | Opsional | Nilai koordinat lintang antara -90.0 hingga 90.0 | Titik koordinat latitude GPS (Bonus 2) |
  | `longitude` | `float` | Opsional | Nilai koordinat bujur antara -180.0 hingga 180.0 | Titik koordinat longitude GPS (Bonus 2) |
  | `notes` | `string` | Opsional | Teks maksimal 255 karakter | Catatan instruksi pengantaran |

- **Alur Eksekusi Transaksi Atomik:**
  1. Menjalankan perintah `PDO->beginTransaction()`.
  2. Membaca isi keranjang belanja dari sesi pengguna. Memastikan keranjang tidak kosong.
  3. Untuk setiap barang di keranjang, mengunci baris data (`SELECT ... FOR UPDATE`), memeriksa stok fisik (`stock >= jumlah_diminta`), dan memotong jumlah stok barang.
  4. Menerbitkan kode pesanan unik dengan pola: `TRX-{YYYYMMDD}-{4_DIGIT_ACAK}`.
  5. Menyimpan data induk transaksi ke tabel `transactions` dengan menyertakan `user_id`, `total_amount`, `latitude`, `longitude`, dan status awal `'pending'`.
  6. Menyimpan rincian tiap barang ke tabel `transaction_details` (`transaction_id`, `item_id`, `quantity`, `unit_price`, `subtotal`).
  7. Mengosongkan keranjang belanja pada sesi pengguna.
  8. Menjalankan perintah `PDO->commit()`.
  9. Melakukan pengalihan HTTP 302 ke `transactions.php?code=...` dengan pesan konfirmasi pesanan.

- **Pembatalan Transaksi Saat Terjadi Kesalahan (Rollback):**
  - Jika ada stok barang yang tidak mencukupi atau kueri basis data mengalami kegagalan, jalankan `PDO->rollBack()`, setel pesan peringatan error pada sesi, dan alihkan pengguna kembali ke `cart.php`.

---

### 4.2. Aksi Pembaruan Status Transaksi oleh Admin
Memungkinkan pengelola sistem memperbarui status transaksi pesanan (misal dari `'pending'` menjadi `'paid'`, `'shipped'`, `'completed'`, atau `'cancelled'`).

- **Kanal Discord:** `#role-crud2-transactions` & `#role-admin-dashboard` (Aksi Bersama)
- **Rute URL:** `POST /actions/crud2_status_update.php`
- **Tingkat Akses:** Administrator Saja (`require_admin()`)
- **Bidang Muatan Formulir (Request Body):**
  | Bidang | Tipe Data | Wajib | Aturan Validasi | Deskripsi |
  | :--- | :--- | :--- | :--- | :--- |
  | `transaction_id`| `integer` | Ya | Wajib terdaftar pada `transactions.id` | ID transaksi yang diperbarui |
  | `status` | `string` | Ya | Nilai: `'pending'`, `'paid'`, `'shipped'`, `'completed'`, `'cancelled'` | Status operasional baru |

- **Logika Bisnis & Perilaku:**
  - Memperbarui kolom `status` dan `updated_at` pada tabel `transactions`.
  - Jika status diubah menjadi `'cancelled'` (dibatalkan), sistem secara otomatis mengembalikan jumlah barang yang dipesan ke stok fisik tabel `items.stock`.
  - Melakukan pengalihan HTTP 302 kembali ke halaman kelola `admin/manage_transactions.php`.
