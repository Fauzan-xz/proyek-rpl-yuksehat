# Yuk Sehat 🩺
Sistem Informasi Data Kesehatan Santri Berbasis Web

## 📖 Deskripsi
**Yuk Sehat** adalah aplikasi berbasis web yang dirancang khusus untuk mengelola data kesehatan santri di lingkungan pondok pesantren. Aplikasi ini dibangun dengan menggunakan arsitektur MVC (Model-View-Controller) murni menggunakan PHP native (tanpa framework berat), Bootstrap 5, dan MySQL. 

Aplikasi ini mendukung tiga peran (role) pengguna:
1. **Admin**: Memiliki kontrol penuh terhadap semua modul, pengelolaan pengguna, dan melihat seluruh laporan.
2. **Pengurus**: Bertugas mencatat data pemeriksaan kesehatan santri harian.
3. **Santri**: Dapat login untuk melihat rekam medis pribadinya dan berkonsultasi mengenai keluhan awal melalui fitur asisten AI pintar.

## 🚀 Fitur Utama
- **Role-Based Access Control (RBAC)**
- **Manajemen Santri**: CRUD data santri lengkap.
- **Pencatatan Pemeriksaan Medis**: Rekam medis harian santri, keluhan, hasil pemeriksaan vital, dan tindakan/obat.
- **Asisten AI Terintegrasi**: Memanfaatkan Gemini AI untuk memberikan informasi awal terkait keluhan penyakit secara aman (dilengkapi disclaimer).
- **Laporan Kesehatan & Export Excel**: Kemudahan filter berdasarkan tanggal, kelas, asrama, dan ekspor langsung ke format `.xlsx`.
- **Dashboard Interaktif**: Statistik real-time, disesuaikan per role.
- **Keamanan Kuat**: Proteksi SQL Injection (PDO Prepared Statement), XSS (Data Escaping), CSRF Protection (Rotated Token), dan Security HTTP Headers.

## 📋 Requirements
- **PHP** >= 8.1
- **MySQL** / MariaDB (Disarankan menggunakan XAMPP)
- **Composer** (opsional, autoloader sederhana telah disertakan jika tidak menggunakan autoloader composer, namun ekstensi `PhpSpreadsheet` ter-bundled via `Shuchkin/SimpleXLSXGen`)
- Ekstensi PHP wajib: `pdo_mysql`, `curl`, `mbstring`.

## 🛠️ Instalasi & Persiapan

1. **Clone atau pindahkan** direktori project ini ke dalam folder `htdocs` Anda (untuk XAMPP).
   ```bash
   cd c:\xampp\htdocs
   git clone <repo-url> YukSehat
   ```

2. **Setup Database**:
   - Buka phpMyAdmin (http://localhost/phpmyadmin)
   - Buat database baru dengan nama `yuk_sehat`
   - Import file konfigurasi awal jika ada, atau jalankan setup tabel yang ada di dalam instruksi database.

3. **Konfigurasi Lingkungan (`.env`)**:
   - Salin file `.env.example` (jika ada) menjadi `.env`.
   - Atau buat file `.env` di root project dan atur seperti berikut:
     ```env
     APP_ENV=local
     DB_HOST=localhost
     DB_USER=root
     DB_PASS=
     DB_NAME=yuk_sehat
     GEMINI_API_KEY=masukkan_api_key_google_gemini_anda_di_sini
     ```

4. **Konfigurasi Root URL**:
   Pastikan URL base Anda benar. Anda dapat menyesuaikannya di file `config/config.php` (Secara default `http://localhost/YukSehat`).

## 🏃 Cara Menjalankan Aplikasi

Aplikasi dapat dijalankan melalui dua cara:

**A. Menggunakan XAMPP Apache**
1. Nyalakan modul Apache dan MySQL dari XAMPP Control Panel.
2. Buka browser dan arahkan ke: `http://localhost/YukSehat`

**B. Menggunakan PHP Built-in Server (Development Mode)**
1. Buka Terminal (PowerShell / Command Prompt).
2. Arahkan ke folder public proyek:
   ```bash
   cd c:\xampp\htdocs\YukSehat\public
   ```
3. Jalankan server lokal:
   ```bash
   php -S localhost:8000
   ```
4. Buka browser pada `http://localhost:8000`

## 🔑 Mengelola Akun Admin Pertama

Sistem tidak mengizinkan Admin mendaftar melalui halaman publik demi keamanan. Admin pertama harus di-insert langsung ke database.
Buka phpMyAdmin, jalankan query berikut untuk membuat akun Admin dengan username `admin` dan password `password123`:

```sql
INSERT INTO users (username, password, role, status, created_at)
VALUES ('admin', '$2y$10$YourGeneratedBcryptHashHere...', 'admin', 'active', NOW());
```
*(Catatan: Anda dapat menggunakan `password_hash('password123', PASSWORD_BCRYPT)` via script PHP ringan untuk menghasilkan hash yang valid terlebih dahulu).*

## 🤖 Konfigurasi Fitur AI (Gemini)

Aplikasi memiliki fitur konsultasi AI menggunakan **Google Gemini**.
1. Dapatkan API Key Anda dari Google AI Studio (https://aistudio.google.com/).
2. Masukkan API Key tersebut ke dalam file `.env`:
   `GEMINI_API_KEY=AIzaSy...`
3. Fitur AI siap digunakan dan hanya dapat diakses melalui modul **Konsultasi AI**. Asisten AI telah diinstruksikan untuk **tidak memberikan diagnosis pasti**, melainkan triase dan informasi aman.

## 📊 Export Laporan Excel

Fitur Export hanya dapat digunakan oleh **Admin** dan **Pengurus**.
1. Masuk ke menu **Laporan**.
2. Gunakan kolom *Filter* (Tanggal, Kelas, Keluhan, dsb.) untuk mempersempit data.
3. Klik tombol **Export Excel**.
4. Browser akan mengunduh file `.xlsx` menggunakan library ringan bawaan (`SimpleXLSXGen`), tanpa memerlukan instalasi library berat (PhpSpreadsheet).

## 📁 Struktur Direktori

```text
YukSehat/
├── .env                # File Konfigurasi (Database, API Keys)
├── .htaccess           # Rule Rewrite & Security HTTP Headers (Apache)
├── app/
│   ├── controllers/    # Business Logic dan Routing Handlers
│   ├── core/           # Core MVC (App.php, Controller.php, Database.php)
│   ├── helpers/        # Session, Auth, dan helper export Excel
│   ├── models/         # Model interaksi Database (PDO)
│   ├── services/       # Service Eksternal (contoh: AiHealthService)
│   ├── views/          # Presentasi Data (HTML, Bootstrap)
│   └── init.php        # Bootstrap awal framework, secure session config
├── config/
│   └── config.php      # Definisi Constant dan pemuatan .env
├── public/             # Document Root untuk web server
│   ├── index.php       # Front Controller
│   └── assets/         # (CSS, JS, Images tĩnh)
└── lint.php            # (Development tool) Script PHP Syntax Check
```

## 🛡️ Informasi Keamanan (Security Posture)

Seluruh sistem telah diaudit dan diperkuat dengan standar berikut:
1. **Pencegahan SQL Injection**: Semua query menggunakan *Prepared Statements* melalui Wrapper PDO khusus. Tidak ada penggabungan string secara langsung di SQL.
2. **XSS Protection**: Fitur Flashing Session di-escape menggunakan `htmlspecialchars()`.
3. **Session Hijacking & Fixation**: 
   - Konfigurasi `session_set_cookie_params()` secara terpusat.
   - Penggunaan flag `HttpOnly`, `SameSite=Lax`, dan rotasi ID sesi (`session_regenerate_id()`) diaktifkan saat transisi autentikasi.
4. **CSRF Protection**: Form mutasi state (Create, Edit, Delete) menggunakan validasi Token CSRF yang digenerate dan diverifikasi secara kriptografis menggunakan `hash_equals()`.
5. **Authorization (IDOR)**: Role checking dilakukan di konstruktor setiap Controller. Pada data sensitif (mis. Rekam Medis), terdapat validasi Ownership di mana santri A tidak dapat melihat ID pemeriksaan milik santri B.
6. **Hardening Tambahan**: `X-Frame-Options` dan pencegahan *Directory Traversal* telah didefinisikan pada `.htaccess`. Direktori internal (`app/`, `config/`) otomatis di-blok dari request langsung via Apache rules.

---
*Dikembangkan untuk digitalisasi kesehatan pondok pesantren yang modern, aman, dan responsif.*
