# Arsitektur Sistem & Spesifikasi Teknis

**Proyek:** Tugas Besar Pemrograman Web Dasar Gasal 2026/2027  
**Kelas / Kelompok:** Kelas D / TubesPWD_D_Kelompok  
**Institusi:** Program Studi Informatika, Fakultas Teknologi Industri, Universitas Atma Jaya Yogyakarta  
**Versi Dokumen:** 1.0.0 (Scaffolding Rilis)  
**Lingkungan Target:** Apache 2.4+ / PHP 8.0+ / MySQL 8.0+ (Lingkungan Standar XAMPP di Windows/macOS/Linux)  

---

## 1. Arsitektur Sistem Tingkat Tinggi

Aplikasi ini menerapkan arsitektur modular prosedural berbasis Model-View-Controller (MVC) ringan yang diimplementasikan menggunakan PHP murni (native/vanilla) tanpa framework atau pustaka eksternal pihak ketiga, sepenuhnya mematuhi batasan akademik mata kuliah.

```
+-----------------------------------------------------------------------------+
|                         LAPISAN KLIEN (PERAMBAN WEB)                        |
|  - Dokumen markup XHTML 1.0 Strict (Tervalidasi W3C)                        |
|  - CSS3 Murni (Tata letak responsif kustom, tanpa framework seperti Tailwind)|
|  - Vanilla JavaScript ES6 (Event DOM, Fetch API / AJAX, Geolocation API)    |
+--------------------------------------+--------------------------------------+
                                       |
                     HTTP / HTTPS      | Formulir XHTML (POST / GET)
                     Asinkron          | Fetch / XMLHttpRequest (JSON)
                                       v
+-----------------------------------------------------------------------------+
|                     LAPISAN SERVER WEB (APACHE / XAMPP)                     |
|  - DocumentRoot: /TubesPWD_D_Kelompok/ atau direktori htdocs XAMPP          |
|  - Distribusi Aset Statis: /assets/css/, /assets/js/, /uploads/             |
|  - Eksekusi Modul PHP (mod_php atau php-fpm)                                |
+--------------------------------------+--------------------------------------+
                                       |
                                       v
+-----------------------------------------------------------------------------+
|                         LAPISAN APLIKASI (PHP MURNI)                        |
|                                                                             |
|  [Pintu Masuk & Tampilan]                                                   |
|  Halaman Utama: index.php, login.php, register.php, catalog.php, cart.php,  |
|                 transactions.php, profile.php, admin/dashboard.php          |
|                                                                             |
|  [Middleware / Penjaga Akses]                                               |
|  includes/session.php       -> Validasi otentikasi & ACL peran pengguna     |
|  includes/functions.php     -> Utilitas sanitasi, format & keamanan         |
|                                                                             |
|  [Pengendali Aksi Backend]                                                  |
|  actions/auth_*.php         -> Login, registrasi, pembaruan profil          |
|  actions/crud1_*.php        -> Operasi kategori & katalog item              |
|  actions/crud2_*.php        -> Checkout, keranjang, geolokasi, status pesanan|
|  actions/check_user_ajax.php-> Pemeriksaan ketersediaan data real-time AJAX |
|                                                                             |
|  [Lapisan Akses Data]                                                       |
|  config/database.php        -> Koneksi PDO tunggal dengan prepared statement|
+--------------------------------------+--------------------------------------+
                                       |
                                       | PDO (PHP Data Objects)
                                       | Prepared Statement dengan Parameter Terikat
                                       v
+-----------------------------------------------------------------------------+
|                      LAPISAN BASIS DATA (MYSQL / INNODB)                    |
|  Skema: tubespwd_db                                                         |
|  Tabel: users, categories, items, transactions, transaction_details         |
+-----------------------------------------------------------------------------+
```

---

## 2. Struktur Direktori & Pembagian Tanggung Jawab

Basis kode disusun dengan pemisahan tanggung jawab yang rapi dan terisolasi:

```
p:\College\PWD\Tugas Besar\
│
├── actions/                     # Berkas pemrosesan backend untuk permintaan POST/GET
│   ├── auth_login.php           # Memvalidasi kredensial, memulai sesi, meregenerasi session ID
│   ├── auth_logout.php          # Menghancurkan sesi dan menghapus cookie otentikasi
│   ├── auth_register.php        # Memvalidasi input registrasi, hash password, simpan pengguna
│   ├── auth_profile_update.php  # Memproses pembaruan data profil & unggahan foto avatar
│   ├── check_user_ajax.php      # Endpoint AJAX real-time untuk cek ketersediaan username/email
│   ├── crud1_category.php       # Manajemen kategori (Tambah, Ubah, Hapus)
│   ├── crud1_item.php           # Manajemen katalog item (Tambah, Ubah, Hapus, Unggah gambar)
│   ├── crud2_transaction.php    # Pemrosesan pesanan, simpan koordinat geolokasi, potong stok
│   └── crud2_status_update.php  # Pembaruan status transaksi & pengembalian stok jika batal
│
├── admin/                       # Halaman administratif terproteksi (Khusus peran admin)
│   ├── dashboard.php            # Ringkasan analitik, indikator metrik, riwayat aktivitas
│   ├── manage_items.php         # Tampilan admin untuk inventaris item CRUD 1
│   ├── manage_categories.php    # Tampilan admin untuk kategori CRUD 1
│   ├── manage_transactions.php  # Tampilan admin untuk daftar transaksi & kontrol status CRUD 2
│   └── manage_users.php         # Daftar akun pengguna dan penyesuaian hak akses
│
├── assets/                      # Aset statis di sisi klien
│   ├── css/                     # Berkas stylesheet CSS murni
│   │   ├── style.css            # Stylesheet dasar (tipografi, tata letak, reset)
│   │   ├── admin.css            # Tata letak dan tabel khusus panel admin
│   │   └── components.css       # Komponen antarmuka (formulir, tombol, alert, kartu)
│   ├── js/                      # Modul script JavaScript murni
│   │   ├── main.js              # Interaksi antarmuka umum & penutupan pesan flash
│   │   ├── ajax_validator.js    # Validasi debounce real-time untuk username/email (Bonus 3)
│   │   ├── geolocation.js       # Penangkapan koordinat browser via Geolocation API (Bonus 2)
│   │   └── image_preview.js     # Pratinjau gambar foto profil sebelum dikirim (Bonus 4)
│   └── images/                  # Berkas gambar statis, logo, dan ikon antarmuka
│
├── config/                      # Konfigurasi sistem
│   ├── database.php             # Kredensial koneksi basis data & instansiasi PDO
│   └── config.php               # Konstanta global (BASE_URL, UPLOAD_DIR, MAX_FILE_SIZE)
│
├── docs/                        # Dokumentasi teknis & panduan rekayasa sistem
│   ├── ARCHITECTURE.md          # Dokumen arsitektur sistem dan siklus hidup (berkas ini)
│   ├── DATABASE_DESIGN.md       # Rancangan relasional, ERD, dan kamus data
│   ├── API_SPECS.md             # Spesifikasi kontrak aksi dan muatan request/response
│   └── diagrams/                # Diagram alur arsitektur visual berstandar akademik (PNG & HTML)
│
├── includes/                    # Template modular sisi server & fungsi utilitas
│   ├── header.php               # Header DOCTYPE XHTML 1.0 Strict & deklarasi metadata
│   ├── footer.php               # Penutup dokumen XHTML & penyertaan script JavaScript
│   ├── navbar.php               # Navigasi responsif berbasis status peran sesi aktif
│   ├── session.php              # Inisialisasi sesi, regenerasi ID, dan penjaga akses (guard)
│   └── functions.php            # Utilitas keamanan, sanitasi output, dan penanganan CSRF
│
├── sql/                         # Skrip basis data dan data awal
│   └── schema.sql               # Definisi DDL tabel basis data & data benih (seed) pengujian
│
├── uploads/                     # Direktori penyimpanan berkas yang diunggah pengguna
│   ├── avatars/                 # Foto profil avatar pengguna (Bonus 4)
│   └── items/                   # Foto produk katalog barang
│
├── index.php                    # Halaman pembuka & etalase katalog unggulan
├── login.php                    # Portal otentikasi masuk pengguna
├── register.php                 # Halaman pendaftaran akun baru dengan validasi instan
├── profile.php                  # Halaman tampilan profil & formulir edit data akun (Bonus 4)
├── catalog.php                  # Halaman eksplorasi publik, pencarian & filter barang (CRUD 1)
├── item_detail.php              # Halaman rincian produk tunggal dengan opsi tambah ke keranjang
├── cart.php                     # Halaman ringkasan keranjang belanja pengguna
├── checkout.php                 # Halaman finalisasi order dengan capture Geolocation (CRUD 2 + Bonus 2)
├── transactions.php             # Halaman riwayat order transaksi & rincian pesanan pembeli
└── README.md                    # Dokumentasi utama proyek & panduan instalasi lokal
```

### 2.1. Matriks Kanal Discord & Batasan Hak Kelola Berkas

Untuk mencegah terjadinya konflik penggabungan kode (merge conflict) di Git, seluruh pekerjaan tim dipetakan ke dalam 5 kanal kerja Discord khusus yang dilengkapi diagram alur arsitektur visual:

| Kanal Kerja Discord | Peran & Deskripsi | Diagram Alur Arsitektur | Lingkup Berkas Resmi |
| :--- | :--- | :--- | :--- |
| `#role-auth-core` | Peran 1: Inti Otentikasi & Akun<br />*(Klaim di #task-board)* | `docs/diagrams/flowchart_auth_core.png` | `config/database.php`, `config/config.php`<br />`includes/session.php`, `includes/functions.php`<br />`actions/auth_register.php`, `actions/auth_login.php`<br />`actions/auth_logout.php`, `actions/auth_profile_update.php`<br />`login.php`, `register.php`, `profile.php`<br />`sql/schema.sql` |
| `#role-crud1-catalog` | Peran 2: Master Katalog & Kategori<br />*(Klaim di #task-board)* | `docs/diagrams/flowchart_crud1_catalog.png` | `actions/crud1_category.php`<br />`actions/crud1_item.php`<br />`catalog.php`, `item_detail.php`<br />`admin/manage_categories.php`, `admin/manage_items.php` |
| `#role-crud2-transactions` | Peran 3: Transaksi & Geolokasi<br />*(Klaim di #task-board)* | `docs/diagrams/flowchart_crud2_transactions.png` | `actions/crud2_transaction.php`<br />`actions/crud2_status_update.php`<br />`cart.php`, `checkout.php`, `transactions.php`<br />`admin/manage_transactions.php` |
| `#role-admin-dashboard` | Peran 4: Panel Dashboard Admin<br />*(Klaim di #task-board)* | `docs/diagrams/flowchart_admin_dashboard.png` | `admin/dashboard.php`<br />`admin/manage_users.php`<br />`assets/css/admin.css` |
| `#role-frontend-interactivity` | Peran 5: Interaktivitas Frontend<br />*(Klaim di #task-board)* | `docs/diagrams/flowchart_frontend_interactivity.png` | `includes/header.php`, `includes/footer.php`, `includes/navbar.php`<br />`assets/css/style.css`, `assets/css/components.css`<br />`assets/js/ajax_validator.js` (Bonus 3)<br />`actions/check_user_ajax.php` (Bonus 3)<br />`assets/js/geolocation.js` (Bonus 2)<br />`assets/js/image_preview.js` (Bonus 4) |

---

## 3. Siklus Hidup Sesi & Penjaga Akses Kontrol (Guard)

### 3.1. Inisialisasi Sesi
Setiap halaman PHP yang berinteraksi dengan pengguna wajib menyertakan `includes/session.php` pada baris paling atas sebelum menghasilkan keluaran teks apapun. Berkas ini menerapkan konfigurasi pengerasan keamanan sesi:
- Menerapkan parameter sesi aman sebelum menjalankan `session_start()`:
  - `cookie_httponly = 1` (Mencegah pencurian cookie sesi melalui serangan XSS pada skrip JavaScript)
  - `cookie_samesite = 'Lax'` (Melindungi sesi dari serangan Cross-Site Request Forgery / CSRF)
  - `use_strict_mode = 1` (Mencegah serangan fiksasi sesi dengan menolak ID sesi yang belum diinisialisasi server)
- Meregenerasi ID sesi pada transisi hak akses:
  - Saat proses login berhasil (`actions/auth_login.php`), fungsi `session_regenerate_id(true)` wajib dipanggil untuk menghapus ID sesi anonim lama dan menerbitkan ID baru yang terotentikasi.

### 3.2. Matriks Kontrol Akses Berbasis Peran (RBAC)

| Rute / Kemampuan | Publik / Tamu | Pelanggan (`role='customer'`) | Administrator (`role='admin'`) | Mekanisme Penjaga Akses |
| :--- | :---: | :---: | :---: | :--- |
| `index.php`, `catalog.php`, `item_detail.php` | Diizinkan | Diizinkan | Diizinkan | Akses terbuka untuk publik |
| `login.php`, `register.php` | Diizinkan | Dialihkan ke `index.php` | Dialihkan ke `admin/dashboard.php` | `redirect_if_logged_in()` |
| `profile.php`, `actions/auth_profile_update.php` | Ditolak (Pengalihan ke login) | Diizinkan | Diizinkan | `require_login()` |
| `cart.php`, `checkout.php`, `transactions.php` | Ditolak (Pengalihan ke login) | Diizinkan | Diizinkan | `require_login()` |
| Direktori `admin/*` | Ditolak (HTTP 403 Dilarang) | Ditolak (HTTP 403 Dilarang) | Diizinkan | `require_admin()` |
| `actions/crud1_*.php` (Tambah/Ubah/Hapus) | Ditolak | Ditolak | Diizinkan | `require_admin()` |
| `actions/crud2_transaction.php` (Checkout Pesanan) | Ditolak | Diizinkan | Diizinkan | `require_login()` |

### 3.3. Pola Implementasi Penjaga Sesi (`includes/session.php`)
```php
function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function is_admin(): bool {
    return is_logged_in() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman ini.';
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'Akses ditolak: Anda tidak memiliki wewenang administrator.';
        header('Location: ' . BASE_URL . 'index.php');
        exit;
    }
}
```

---

## 4. Protokol Keamanan & Pertahanan Berlapis

### 4.1. Pencegahan Serangan SQL Injection
- **Larangan Keras Kueri Mentah:** Menggabungkan string variabel input pengguna langsung ke dalam sintaks SQL (`"SELECT * FROM users WHERE username = '$user'"`) dilarang keras tanpa pengecualian.
- **Wajib Prepared Statement:** Semua operasi manipulasi data wajib menggunakan prepared statement PDO dengan parameter terikat (`?` posisional atau `:nama` bernama).
```php
// Pola standar yang wajib diterapkan
$stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE username = :username LIMIT 1');
$stmt->execute([':username' => $username]);
$user = $stmt->fetch();
```

### 4.2. Pencegahan Serangan Cross-Site Scripting (XSS)
- Mengikuti standar XHTML 1.0 Strict, setiap pencetakan data dinamis ke dalam kode HTML wajib di-escape menggunakan:
  `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Proyek menyediakan fungsi pembungkus ringkas `e($string)` pada `includes/functions.php`:
```php
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

### 4.3. Standar Enkripsi Password
- Password pengguna tidak boleh disimpan dalam bentuk plain text maupun algoritma lawas yang rentan (MD5 atau SHA1).
- Enkripsi password wajib memanfaatkan fungsi native PHP `password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])`.
- Verifikasi saat proses otentikasi login wajib menggunakan `password_verify($password, $user['password_hash'])`.

### 4.4. Saluran Unggah Berkas yang Aman (Bonus 4 & Gambar Item)
1. **Daftar Putih Ekstensi (Whitelisting):** Hanya ekstensi tertentu yang diizinkan secara ketat: `['jpg', 'jpeg', 'png', 'webp']`.
2. **Validasi Tipe MIME Riil:** Memeriksa magic bytes berkas menggunakan `finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])` alih-alih mempercayai nama berkas kiriman klien.
3. **Penamaan Berkas Acak Unik:** Menamai ulang berkas yang diunggah menggunakan kode acak kriptografis (`bin2hex(random_bytes(16)) . '.' . $ext`) untuk mencegah serangan path traversal maupun penimpaan berkas lain.
4. **Batas Ukuran Berkas:** Menetapkan batas ukuran maksimum berkas sebesar 2MB (`2 * 1024 * 1024` bytes).
5. **Pencegahan Eksekusi Skrip:** Direktori unggahan (`uploads/`) dilengkapi berkas konfigurasi `.htaccess` yang menonaktifkan eksekusi skrip PHP (`php_flag engine off` / `SetHandler none`).

---

## 5. Kepatuhan Standar W3C XHTML 1.0 Strict

Antarmuka web wajib lolos pengujian validator resmi W3C XHTML 1.0 Strict dengan mematuhi aturan berikut:
1. **Deklarasi DOCTYPE Wajib:**
   ```xml
   <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
   <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="id" lang="id">
   ```
2. **Penulisan Tag & Atribut Huruf Kecil:** Seluruh nama elemen HTML dan atribut wajib ditulis dengan huruf kecil (contoh: `<div class="container">`, bukan `<DIV CLASS="container">`).
3. **Penutupan Elemen Kosong (Void Tags):** Tag tanpa pasangan wajib ditutup secara self-closing dengan spasi sebelum garis miring:
   - `<br />`
   - `<hr />`
   - `<img src="jalur.jpg" alt="Deskripsi gambar" />`
   - `<input type="text" name="kolom" />`
4. **Nilai Atribut Wajib Diberi Tanda Kutip Ganda:** Seluruh nilai atribut wajib diapit tanda kutip ganda (contoh: `width="100"` dan bukan `width=100`).
5. **Larangan Minimasi Atribut Boolean:** Atribut kondisi boolean harus ditulis berpasangan secara eksplisit:
   - `<input type="checkbox" checked="checked" />`
   - `<select><option selected="selected">Pilihan</option></select>`
   - `<button disabled="disabled">Tombol</button>`
6. **Larangan Tag Presentasional Usang:** Dilarang menggunakan tag usang HTML4 seperti `<font>`, `<center>`, `<b>`, `<u>`, `<marquee>`. Seluruh pengaturan tata letak, warna, dan dekorasi visual wajib ditangani menggunakan CSS murni.

---

## 6. Higienitas Basis Kode & Kebijakan Nol Emoji

Seluruh lapisan arsitektur perangkat lunak ini diatur oleh kebijakan mutlak tanpa emoji atau simbol ikon grafis unicode:
1. **Kode Backend:** Variabel PHP, nama fungsi, komentar kode, pesan eksepsi, dan log wajib murni berupa teks profesional.
2. **Basis Data:** Nilai default kolom, data benih (seed), dan data enumerasi status tidak boleh memuat emoji.
3. **Tampilan Antarmuka:** Tombol XHTML, judul, badge indikator, dan pesan peringatan alert harus menggunakan teks terstruktur (misal `[Ubah]`, `[Hapus]`, `Status: Menunggu`) tanpa emoji.
4. **CSS & Tata Letak:** Dilarang menyisipkan karakter emoji melalui konten pseudo-element CSS. Gunakan warna, border, dan tata letak semantik jika memerlukan penanda visual.
5. **Git & Dokumentasi:** Seluruh pesan commit, deskripsi pull request, dan dokumentasi rekayasa sistem wajib 100% berupa teks murni yang profesional.
