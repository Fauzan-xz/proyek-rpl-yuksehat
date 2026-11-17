# DOKUMEN ANALISIS FITUR DAN ARSITEKTUR SISTEM YUKSEHAT
**Sistem Informasi Manajemen Kesehatan Santri Berbasis Web (Poskestren)**

- **Versi Dokumen:** 1.0.0
- **Tanggal Analisis:** 16 November 2026
- **Status Verifikasi:** Diverifikasi Penuh terhadap *Source Code* Workspace
- **Penyusun:** Senior Business Analyst, Product Manager & Software Requirements Engineer
- **Metodologi Analisis:** *Code Inspection*, *Static Architecture Tracing*, *Schema Validation*, dan *Security Posture Audit*

---

## 1. Ringkasan Eksekutif Analisis

Aplikasi **YukSehat** merupakan sistem informasi data kesehatan santri yang dirancang khusus untuk digitalisasi operasional Pos Kesehatan Pesantren (Poskestren). Berdasarkan inspeksi langsung terhadap repositori kode sumber (*codebase*), YukSehat diimplementasikan menggunakan arsitektur **Model-View-Controller (MVC) murni berbasis Native PHP** (tanpa framework pihak ketiga seperti Laravel atau CodeIgniter), didukung antarmuka responsif **Bootstrap 5**, basis data relasional **MySQL (MariaDB)**, serta pelengkap mikrolayanan berbasis **Node.js** untuk orkestrasi asisten kecerdasan buatan (*AI Chatbot & Clinical Drafter*).

Sistem ini menerapkan pengamanan berlapis (*defense-in-depth*) mencakup:
1. **Pencegahan SQL Injection**: Penggunaan *Prepared Statements* dan parameter binding PDO terpusat melalui wrapper `app/core/Database.php`.
2. **Perlindungan Cross-Site Request Forgery (CSRF)**: Token anti-CSRF kriptografis (`bin2hex(random_bytes(32))`) yang divalidasi via `hash_equals()` pada seluruh rute mutasi data POST, dilengkapi rotasi token otomatis (`AuthHelper::rotateCSRF()`) saat regenerasi ID sesi.
3. **Pencegahan Cross-Site Scripting (XSS)**: Sanitasi masukan melalui `htmlspecialchars()`, isolasi manipulasi entitas, dan penetapan cookie sesi berparameter `httponly=true`, `samesite=Lax`, serta `secure=auto`.
4. **Isolasi Role Keras (Strict RBAC)**: Pengecekan peran dilakukan di tingkat konstruktor dan method *Controller*, bukan sekadar menyembunyikan tautan di antarmuka (*UI-level hiding*).
5. **Perlindungan Kerahasiaan Medis**: Pesan yang disalurkan melalui gateway WhatsApp pihak ketiga (*Fonnte*) dibatasi secara ketat hanya pada informasi generik tanpa memuat rincian keluhan, diagnosis, atau rekam obat (*Zero Medical Details Leakage*).
6. **Manajemen Kegagalan Gateway (Safe Fallback)**: Kegagalan gateway eksternal tidak pernah membatalkan transaksi basis data lokal (*non-blocking asynchronous queue*).

---

## 2. Struktur Direktori dan Komponen Perangkat Lunak

Struktur direktori aktual pada workspace `c:\xampp\htdocs\YukSehat` adalah sebagai berikut:

```text
YukSehat/
├── .env                              # Variabel lingkungan (Koneksi DB, API Key, Switch Integrasi)
├── .env.example                      # Template referensi konfigurasi lingkungan
├── .gitignore                        # Berkas pengecualian Git
├── .htaccess                         # Apache rewrite engine, security HTTP headers, blokir direktori internal
├── README.md                         # Dokumentasi umum awal proyek
├── index.php                         # Fallback root redirector ke public/
├── lint.php                          # Script utilitas CLI pengujian sintaksis PHP
├── setup_admin.php                   # Script CLI inisialisasi akun administrator dan pengurus
│
├── app/
│   ├── init.php                      # Bootstrap inisialisasi aplikasi, dependensi, dan sesi aman
│   ├── controllers/                  # 14 Berkas Pengontrol (Business Logic & Endpoint Routing)
│   │   ├── Chatbot.php               # Handler API Chatbot & integrasi model AI
│   │   ├── Dashboard.php             # Handler statistik dashboard per role
│   │   ├── Home.php                  # Handler landing page publik / redirection
│   │   ├── Keluhan.php               # Handler antrean, pengajuan, penugasan, dan status keluhan
│   │   ├── Kesehatan.php             # Handler stub/placeholder rute kesehatan umum
│   │   ├── Laporan.php               # Handler rekapitulasi medis & ekspor multi-sheet Excel
│   │   ├── Login.php                 # Handler autentikasi pengguna, session fixation guard
│   │   ├── Monitoring.php            # Handler dashboard monitoring gateway WhatsApp Fonnte (Admin)
│   │   ├── Notifikasi.php            # Handler riwayat notifikasi in-app pengurus & santri
│   │   ├── Pemeriksaan.php           # Handler rekam medis vital & asisten klinis AI
│   │   ├── Register.php              # Handler pendaftaran mandiri santri
│   │   ├── Santri.php                # Handler master data santri & riwayat medis
│   │   ├── Settings.php              # Handler konfigurasi model AI (NVIDIA NIM & Google Gemini)
│   │   └── Users.php                 # Handler manajemen pengguna, role, dan nomor WhatsApp
│   │
│   ├── core/                         # Kerangka Utama MVC Native
│   │   ├── App.php                   # URL Router & Dispatcher front-controller
│   │   ├── Controller.php            # Base controller (pemuat view dan model)
│   │   └── Database.php              # PDO Database Wrapper & prepared statement engine
│   │
│   ├── helpers/                      # Pustaka Bantuan
│   │   ├── AuthHelper.php            # Utilitas autentikasi, otorisasi RBAC, CSRF token, & aturan Ronal
│   │   ├── MedicalFormatHelper.php   # Parser & summarizer draf medis AI untuk tabel & ekspor
│   │   ├── SessionHelper.php         # Handler flash message berbasis sesi
│   │   └── SimpleXLSXGen.php         # Generator berkas Microsoft Excel (.xlsx) ringan tanpa ekstensi berat
│   │
│   ├── models/                       # 6 Berkas Model Data (Interaksi Basis Data via PDO)
│   │   ├── Dashboard_model.php       # Agregasi metrik statistik operasional Poskestren
│   │   ├── Laporan_model.php         # Pengambilan data riwayat kesehatan terfilter
│   │   ├── Notifikasi_model.php      # Penyimpanan notifikasi in-app & antrean WhatsApp aman
│   │   ├── Pemeriksaan_model.php     # CRUD rekam medis fisik & vital santri
│   │   ├── Santri_model.php          # CRUD profil santri & riwayat kesehatan
│   │   └── User_model.php            # Autentikasi kredensial, verifikasi status, pendaftaran santri
│   │
│   ├── services/                     # 4 Berkas Layanan Integrasi Eksternal & Khusus
│   │   ├── AiService.php             # Orkestrator model AI (NVIDIA NIM & Google Gemini API)
│   │   ├── ChatbotDataService.php    # Pemroses query statistik basis data real-time via chatbot
│   │   ├── FonnteService.php         # Klien HTTP Gateway WhatsApp Fonnte (Device Check, Send, Dry-Run)
│   │   └── WaQueueService.php        # Pemroses antrean WhatsApp (Atomic Claiming, Exponential Backoff, F-01)
│   │
│   └── views/                        # Tampilan Antarmuka Pengguna (Blade-less PHP + Bootstrap 5)
│       ├── components/               # Komponen Modular (Breadcrumb, Chatbot Floating, Navbar, Sidebar)
│       ├── dashboard/                # Antarmuka Dashboard (Admin, Pengurus, Santri)
│       ├── home/                     # Landing Page Publik
│       ├── keluhan/                  # Tampilan Antrean Keluhan (Admin) & Formulir Keluhan (Santri)
│       ├── laporan/                  # Tampilan Filter Laporan & Tombol Ekspor Excel
│       ├── login/                    # Antarmuka Masuk Sistem
│       ├── monitoring/               # Antarmuka Dashboard Monitoring Antrean WhatsApp (Admin)
│       ├── notifikasi/               # Antarmuka Riwayat Notifikasi In-App & Rincian Modal
│       ├── pemeriksaan/              # Antarmuka Indeks, Detail, Input (AI Drafter), Edit Pemeriksaan
│       ├── register/                 # Antarmuka Registrasi Akun Santri
│       ├── santri/                   # Antarmuka Indeks, Tambah, Edit, Detail Master Santri
│       ├── settings/                 # Antarmuka Konfigurasi Model AI & Manajemen API Key
│       ├── templates/                # Template Wrapper (Header publik, Footer publik, Dash Header, Dash Footer)
│       └── users/                    # Antarmuka Kelola Akun, Role, Status, dan Nomor WhatsApp
│
├── bin/
│   └── wa_worker.php                 # Worker CLI latar belakang untuk pemrosesan antrean WhatsApp (Cron/Scheduler)
│
├── chatbot-server/                   # Layanan Mikrolayanan AI Chatbot (Node.js)
│   ├── node.exe                      # Binary Node.js tersemat (Portable Runtime)
│   ├── package.json                  # Metadata paket Node.js
│   ├── server.js                     # Server HTTP Node.js (Endpoint /api/chat, /api/analyze-keluhan, port 3005)
│   ├── start.bat                     # Skrip peluncur server di lingkungan Windows
│   └── start.ps1                     # Skrip PowerShell peluncur server
│
├── config/
│   └── config.php                    # Pemuat berkas .env, konstanta global, konfigurasi zona waktu & error handling
│
├── database/                         # Berkas Skema, Migrasi, dan Cadangan Basis Data
│   ├── migrations/                   # Skrip DDL pembaruan skema antrean WhatsApp (F-01, worker lock)
│   ├── backup_pre_notifikasi_*.sql   # Cadangan basis data sebelum penambahan fitur notifikasi
│   ├── backup_pre_uat_*.sql          # Cadangan basis data evaluasi UAT
│   ├── run_seed.php                  # Skrip pembuat skema awal dan data benih (seed)
│   └── schema.sql                    # Definisi DDL tabel dasar (users, pengurus, santri, keluhan, pemeriksaan)
│
├── docs/                             # Berkas dokumentasi audit dan laporan UAT terdahulu
├── public/                           # Document Root Web Server (Apache/Nginx)
│   ├── index.php                     # Front Controller tunggal
│   ├── .htaccess                     # Rule penulisan ulang URL internal public
│   └── assets/                       # Aset statis (CSS, JS, Ikon)
│
└── scratch/                          # Direktori berkas pengujian skenario teknis internal
```

---

## 3. Analisis Modul & Arsitektur MVC

Aplikasi mengimplementasikan pola arsitektur **Model-View-Controller (MVC)** dengan pembagian peran yang tegas:

1. **Front Controller & Routing (`public/index.php` & `app/core/App.php`)**:
   - Seluruh permintaan HTTP diarahkan ke `public/index.php` melalui penulisan ulang modul Apache (`RewriteRule ^(.*)$ public/$1 [L]`).
   - `App.php` mem-parsing parameter URL dengan pola `BASEURL/{controller}/{method}/{params}`. Default controller adalah `Home` dan default method adalah `index`.
2. **Base Controller (`app/core/Controller.php`)**:
   - Menyediakan metode `view($view, $data = [])` untuk merender tampilan dan mengekstraksi variabel `$data`.
   - Menyediakan metode `model($model)` untuk menginisialisasi kelas model secara instan.
3. **Database Layer (`app/core/Database.php`)**:
   - Berbasis PDO (*PHP Data Objects*) dengan koneksi persisten ke MySQL.
   - Menyediakan metode terenkapsulasi: `query()`, `bind()`, `execute()`, `resultSet()`, `single()`, `rowCount()`, dan `lastInsertId()`.
4. **Presentation Layer (`app/views/`)**:
   - Dipisahkan antara halaman publik (`header.php` dan `footer.php`) dan dashboard internal pengguna (`dash_header.php` dan `dash_footer.php`).
   - Komponen global seperti `sidebar.php`, `navbar.php`, `breadcrumb.php`, dan `chatbot.php` dimuat secara modular.
   - Pustaka interaktif modern disuntikkan secara terpusat: Bootstrap 5.3.2, Bootstrap Icons, SweetAlert-free modal native, serta utilitas kustom `showYkToast()` dan `showYkConfirm()`.

---

## 4. Analisis Skema Database dan Integritas Relasi

Berdasarkan analisis berkas `database/schema.sql`, `database/backup_pre_notifikasi_20261031.sql`, dan skrip migrasi, basis data `yuk_sehat` memiliki 8 tabel utama yang saling terhubung:

```
+------------------+         1:1         +------------------+
|      users       |-------------------->|     pengurus     |
| (id, username,   |                     | (id, user_id,    |
|  password, role, |                     |  nama, jabatan,  |
|  status)         |                     |  no_hp)          |
+------------------+                     +------------------+
        |
        | 1:1
        v
+------------------+         1:N         +------------------+
|      santri      |-------------------->| riwayat_keluhan  |
| (id, user_id,    |                     | (id, santri_id,  |
|  nis, nama, jk,  |                     |  keluhan, status,|
|  kelas, kamar,   |                     |  assigned_to)    |
|  no_hp)          |                     +------------------+
+------------------+                               |
        |                                          | 1:1 (opsional)
        | 1:N                                      v
        +-------------------------------->+-----------------------+
                                          | pemeriksaan_kesehatan |
                                          | (id, santri_id,       |
                                          |  pemeriksa_id,        |
                                          |  keluhan_id, suhu,    |
                                          |  tekanan_darah, bb,tb,|
                                          |  hasil, tindakan,     |
                                          |  obat, catatan)       |
                                          +-----------------------+

+------------------+         1:N         +-----------------------+
|    notifikasi    |-------------------->|  notifikasi_wa_queue  |
| (id, user_id,    |                     | (id, notifikasi_id,   |
|  jenis, judul,   |                     |  user_id, nomor_tujuan|
|  pesan, link_url,|                     |  status, provider_id, |
|  referensi_id,   |                     |  claimed_at, retry_at)|
|  dibaca_pada)    |                     +-----------------------+
+------------------+

+------------------+
| system_settings  |
| (setting_key,    |
|  setting_value)  |
+------------------+
```

### Rincian Tabel dan Atribut:

1. **`users`**:
   - `id` (INT PK AI), `username` (VARCHAR 255 UNIQUE), `password` (VARCHAR 255 BCrypt hash), `role` (ENUM: `'admin'`, `'pengurus'`, `'santri'`), `status` (ENUM: `'active'`, `'inactive'`), `created_at`, `updated_at`.
2. **`pengurus`**:
   - `id` (INT PK AI), `user_id` (INT UNIQUE FK -> `users.id` ON DELETE CASCADE), `nama` (VARCHAR 255), `jabatan` (VARCHAR 100), `no_hp` (VARCHAR 20), timestamp.
3. **`santri`**:
   - `id` (INT PK AI), `user_id` (INT UNIQUE FK -> `users.id` ON DELETE CASCADE), `nis` (VARCHAR 50 UNIQUE), `nama` (VARCHAR 255), `jenis_kelamin` (ENUM: `'L'`, `'P'`), `tanggal_lahir` (DATE), `kelas` (VARCHAR 50), `kamar` (VARCHAR 100), `no_hp` (VARCHAR 20), `golongan_darah` (VARCHAR 5), `alergi` (TEXT), `riwayat_penyakit` (TEXT), timestamp.
4. **`riwayat_keluhan`**:
   - `id` (INT PK AI), `santri_id` (INT FK -> `santri.id` ON DELETE CASCADE), `tanggal_keluhan` (DATETIME), `keluhan` (TEXT), `assigned_to` (INT NULL FK -> `users.id`), `status` (ENUM: `'menunggu'`, `'diperiksa'`, `'selesai'`), timestamp.
5. **`pemeriksaan_kesehatan`**:
   - `id` (INT PK AI), `santri_id` (INT FK -> `santri.id` ON DELETE CASCADE), `pemeriksa_id` (INT NULL FK -> `users.id` ON DELETE SET NULL), `keluhan_id` (INT NULL FK -> `riwayat_keluhan.id` ON DELETE SET NULL), `tanggal_pemeriksaan` (DATETIME), `keluhan` (TEXT), `suhu` (DECIMAL 4,2), `tekanan_darah` (VARCHAR 20), `berat_badan` (DECIMAL 5,2), `tinggi_badan` (DECIMAL 5,2), `hasil_pemeriksaan` (TEXT), `tindakan` (TEXT), `obat` (TEXT), `catatan` (TEXT), timestamp.
6. **`notifikasi`**:
   - `id` (INT PK AI), `user_id` (INT FK -> `users.id`), `jenis` (VARCHAR 50: `'keluhan_baru'`, `'penugasan_baru'`, `'pengalihan_tugas'`, `'status_diperiksa'`, `'status_selesai'`), `judul` (VARCHAR 255), `pesan` (TEXT), `link_url` (VARCHAR 255), `referensi_tipe` (VARCHAR 50), `referensi_id` (INT NULL), `dibaca_pada` (DATETIME NULL), `created_at` (DATETIME).
7. **`notifikasi_wa_queue`**:
   - `id` (INT PK AI), `notifikasi_id` (INT FK -> `notifikasi.id` ON DELETE CASCADE), `user_id` (INT), `nomor_tujuan` (VARCHAR 30), `pesan` (TEXT), `status` (ENUM: `'pending'`, `'processing'`, `'sent'`, `'failed'`, `'reconciliation_required'`), `provider_status` (VARCHAR 50), `provider_message_id` (VARCHAR 100 NULL), `percobaan` (INT DEFAULT 0), `max_percobaan` (INT DEFAULT 3), `error_message` (TEXT NULL), `sent_at` (DATETIME NULL), `claimed_at` (DATETIME NULL), `next_retry_at` (DATETIME NULL), timestamp.
8. **`system_settings`**:
   - `setting_key` (VARCHAR 64 PK), `setting_value` (TEXT NULL), `updated_at` (TIMESTAMP). Menyimpan konfigurasi model AI aktif, provider, endpoint, dan API key dinamis.

---

## 5. Analisis Sistem Autentikasi, Sesi, dan Keamanan

Berdasarkan inspeksi berkas `app/init.php`, `app/helpers/AuthHelper.php`, `app/controllers/Login.php`, dan `.htaccess`:

1. **Penyimpanan Kredensial**:
   - Password disimpan menggunakan algoritma standar industri **BCrypt** (`PASSWORD_BCRYPT`) dengan *cost factor* default PHP.
   - Pengecekan login mendukung *multi-identifier login*: Pengguna dapat memasukkan **Username**, **NIS** (untuk santri), atau **Nama Petugas** (untuk admin/pengurus) pada form login yang sama.
2. **Session Hardening & Cookie Configuration**:
   - Konfigurasi cookie dilakukan sebelum `session_start()`:
     - `httponly = true` (mencegah pencurian session id via skrip peramban JavaScript / XSS).
     - `samesite = Lax` (mencegah session hijacking melalui skenario CSRF pihak ketiga).
     - `secure = true` (aktif otomatis saat aplikasi berjalan di bawah protokol HTTPS).
3. **Session Fixation Guard**:
   - Pemanggilan `session_regenerate_id(true)` diterapkan tepat setelah kredensial berhasil divalidasi pada `Login::auth()`.
   - Rotasi token CSRF (`AuthHelper::rotateCSRF()`) dipicu berbarengan untuk memastikan token lama hangus.
4. **Proteksi Anti-Lockout Super User Admin**:
   - Pada `Users::update()`, sistem memvalidasi: Jika pengguna yang sedang diedit adalah akun Admin yang sedang login, sistem secara otomatis menolak penurunan role atau penonaktifan status akun tersebut.
5. **Keamanan Jaringan & Server-Level Apache (`.htaccess`)**:
   - Pemasangan HTTP Security Headers:
     - `X-Frame-Options: SAMEORIGIN` (Anti-Clickjacking).
     - `X-Content-Type-Options: nosniff` (Anti-MIME Sniffing).
     - `Referrer-Policy: strict-origin-when-cross-origin`.
     - `X-XSS-Protection: 1; mode=block`.
   - Pemblokiran langsung terhadap file sensitif (`.env`, `.git`, berkas `.sql`, `composer.json`, `package.json`, berkas pengujian `test_*.php`) dan direktori internal (`app/`, `bin/`, `config/`, `database/`, `chatbot-server/`).

---

## 6. Analisis Kontrol Akses Berbasis Peran (RBAC Matrix)

Penerapan RBAC diuji pada level backend controller. Berikut adalah matriks hak akses riil:

| Modul / URL Handler | Aksi Spesifik | Administrator | Pengurus | Santri | Tamu (Guest) |
|---|---|:---:|:---:|:---:|:---:|
| **Publik / Landing** (`/home`, `/`) | Lihat Halaman Selamat Datang | Ya | Ya | Ya | Ya |
| **Login** (`/login`) | Akses Form & Autentikasi | Redirect Dashboard | Redirect Dashboard | Redirect Dashboard | Ya |
| **Register** (`/register`) | Pendaftaran Mandiri Santri | Redirect Dashboard | Redirect Dashboard | Redirect Dashboard | Ya |
| **Dashboard** (`/dashboard`) | Lihat Statistik Operasional | Ya (Statistik Penuh) | Ya (Statistik Penuh) | Ya (Statistik Mandiri) | Ditolak (Login) |
| **Keluhan** (`/keluhan`) | Akses Antrean Keluhan | Ya (Semua Santri) | Ya (Semua Santri) | Ya (Hanya Milik Sendiri)| Ditolak (Login) |
| **Keluhan: Kirim** (`/keluhan/kirim`) | Mengirim Laporan Keluhan Baru | Ditolak (Role) | Ditolak (Role) | Ya | Ditolak (Login) |
| **Keluhan: Tugaskan** (`/keluhan/assign`) | Menugaskan Pemeriksa | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Keluhan: Status** (`/keluhan/update_status`) | Mengubah Status Antrean | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Pemeriksaan: Indeks** (`/pemeriksaan`) | Daftar Riwayat Medis | Ya (Semua Santri) | Ya (Semua Santri) | Ya (Rekam Medis Sendiri)| Ditolak (Login) |
| **Pemeriksaan: Detail** (`/pemeriksaan/show`) | Lihat Detail Rekam Medis | Ya | Ya | Ya (Kepemilikan Sah) | Ditolak (Login) |
| **Pemeriksaan: Input** (`/pemeriksaan/create`) | Akses Form Tambah Periksa | Ditolak (Aturan Ronal) | **Ya (Hanya Ronal)** | Ditolak (Role) | Ditolak (Login) |
| **Pemeriksaan: Simpan** (`/pemeriksaan/store`) | Simpan Rekam Medis Baru | Ditolak (Aturan Ronal) | **Ya (Hanya Ronal)** | Ditolak (Role) | Ditolak (Login) |
| **Pemeriksaan: Edit** (`/pemeriksaan/edit`) | Edit Rekam Medis | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Pemeriksaan: Hapus** (`/pemeriksaan/delete`) | Hapus Permanen Rekam Medis | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Pemeriksaan: AI Assist** (`/pemeriksaan/analyze_keluhan`) | Analisis Klinis Gemini/NVIDIA | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Santri: Indeks** (`/santri`) | Direktori Data Santri | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Santri: Detail** (`/santri/detail`) | Detail Biodata & Medis Santri | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Santri: Tambah/Edit** (`/santri/create`, `/edit`) | CRUD Master Santri | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Santri: Deaktivasi** (`/santri/delete`) | Nonaktifkan Akun Santri | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Laporan: Indeks** (`/laporan`) | Rekapitulasi & Filter Medis | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Laporan: Export** (`/laporan/export`) | Unduh Buku Kerja XLSX 2 Sheet | Ya | Ya | Ditolak (Role) | Ditolak (Login) |
| **Notifikasi** (`/notifikasi`) | Riwayat Notifikasi In-App | Ditolak (Role) | Ya (Milik Sendiri) | Ya (Milik Sendiri) | Ditolak (Login) |
| **Pengguna** (`/users`) | Kelola Akun, Role, Status, No HP | **Ya** | Ditolak (Role) | Ditolak (Role) | Ditolak (Login) |
| **Pengaturan AI** (`/settings`) | Konfigurasi Model & Kunci API | **Ya** | Ditolak (Role) | Ditolak (Role) | Ditolak (Login) |
| **Monitoring WA** (`/monitoring`) | Pantau Antrean WhatsApp Real Gateway | **Ya** | Ditolak (Role) | Ditolak (Role) | Ditolak (Login) |
| **Chatbot Widget** | Konsultasi Teks & Data Poskestren | Ya (Mode Admin Data) | Ya (Mode Staf Data) | Ya (Konsultasi Sehat) | Ya (Guest Konsultasi) |

> **Catatan Implementasi Khusus (Aturan Bisnis Ronal):**
> Pada `AuthHelper::canInputPemeriksaan()`, hanya user yang memiliki `role === 'pengurus'` DAN `username === 'ronal'` yang diberikan wewenang membuka dan mengeksekusi form `/pemeriksaan/create` dan `/pemeriksaan/store`. Kebijakan ini merupakan batasan operasional riil di Poskestren agar pencatatan tindakan medis hanya dilakukan oleh penanggung jawab medis resmi yang ditunjuk.

---

## 7. Audit Implementasi Fitur Nyata

Berikut adalah klasifikasi status fitur hasil audit kode sumber (*source code verification*):

### A. Fitur Berstatus AKTIF (Fully Implemented & Production Ready)
1. **Autentikasi & Otorisasi RBAC**: Login multi-identifier (username/NIS/nama), logout, registrasi santri, proteksi CSRF form, dan regenerasi ID sesi.
2. **Manajemen Master Data Santri**: Pencatatan NIS unik, nama, kelas, kamar, kontak wali/santri, golongan darah, riwayat alergi, riwayat penyakit, paginasi data, filter pencarian kata kunci/kelas/kamar, dan deaktivasi akun santri (*soft-delete*).
3. **Pengajuan & Antrean Keluhan Santri**:
   - Pengajuan keluhan oleh santri dengan opsi memilih pengurus pemeriksa yang bertugas.
   - Antrean keluhan terfilter (*Pills Filter*: Semua, Menunggu, Sedang Diperiksa, Selesai Ditangani) dengan jumlah indikator real-time.
   - Fitur penugasan dan pengalihan tugas pemeriksa (*assign / reassign*).
   - Pembaruan status keluhan interaktif secara langsung (*instant status change*).
4. **Pencatatan Rekam Medis (Pemeriksaan Kesehatan)**:
   - Pencatatan tanda vital (suhu tubuh, tensi darah, berat badan, tinggi badan).
   - Pre-fill otomatis data santri dan narasi keluhan saat periksa dibuka langsung dari antrean keluhan.
   - Kolom diagnosis hasil pemeriksaan, tindakan medis, resep obat, dan catatan tambahan.
   - Validasi kepemilikan data (*IDOR Protection*) agar santri tidak dapat mengintip rekam medis santri lain.
5. **Draf Analisis Klinis Asisten AI (`analyze_keluhan`)**:
   - Analisis otomatis keluhan santri dan data vital berbasis Google Gemini / NVIDIA NIM.
   - Pengekstrakan draf ringkas ke format terstruktur (Hasil, Tindakan, Obat, Catatan Bahaya).
   - Tombol interaktif "Gunakan Saran ke Formulir" dengan modal konfirmasi penimpaan data (*modal overwrite confirm*) dan notifikasi toast sukses.
6. **Laporan & Ekspor Microsoft Excel Multi-Sheet**:
   - Filter rentang tanggal pemeriksaan, nama santri, kelas, kamar, jenis kelamin, dan kata kunci keluhan.
   - Ekspor workbook XLSX resmi via `SimpleXLSXGen`:
     - **Sheet 1 ("Ringkasan Laporan")**: Data terstruktur rapi dengan hasil dan tindakan yang diringkas profesional via `MedicalFormatHelper::summarizeHasil()`.
     - **Sheet 2 ("Detail Catatan Klinis")**: Catatan klinis lengkap tanpa pemotongan untuk kebutuhan audit medis dan rekam jejak.
7. **Sistem Notifikasi In-App**:
   - Notifikasi internal pengurus saat ada keluhan baru atau penugasan.
   - Notifikasi internal santri saat status keluhan ditindaklanjuti atau selesai.
   - Modal interaktif "Buka Rincian" yang menampilkan rincian keluhan, santri, dan hasil periksa secara langsung.
   - Penandaan telah dibaca per item via AJAX dan penandaan massal (*Mark All as Read*).
8. **Dashboard Statistik Khusus**:
   - Agregasi real-time total santri, santri sakit, pemeriksaan hari ini, rawat inap/UKS, dan rujukan luar pesantren.
   - Tampilan disesuaikan untuk Admin, Pengurus, dan Santri.
9. **Manajemen Pengguna & Konfigurasi AI**:
   - Penambahan akun admin/pengurus, pengubahan role, pengubahan status akun, dan pembaruan nomor WhatsApp.
   - Pilihan model AI instan (NVIDIA Nemotron 3.5 Bolt, Google Gemini Flash Lite, Gemini 3.5 Flash, Gemini 3.8 Flash, Poolside Laguna, Nemotron Ultra) dengan fitur pengujian latensi via AJAX (`/settings/test_model`).
   - Manajemen Kunci API (Pasang baru, hapus kunci, dan sembunyikan/tampilkan via modal).

### B. Fitur Berstatus PARSIAL (Partially Implemented / Specific Constraints)
1. **Input Rekam Medis Khusus Petugas Ronal**:
   - Form penambahan pemeriksaan baru (`/pemeriksaan/create`) sengaja dibatasi hanya untuk user `ronal` (pengurus). Pengurus lain atau admin diblokir oleh sistem dengan pesan penolakan formal.
2. **Chatbot Data Query untuk Santri**:
   - Fitur tanya jawab statistik basis data (contoh: "berapa santri sakit hari ini?") hanya diizinkan untuk Admin dan Pengurus. Jika santri menanyakannya, sistem secara otomatis mengalihkannya ke respons umum atau menolak menampilkan data agregat operasional.

### C. Fitur Berstatus SIMULASI / SAFE-MODE (Config-Gated & Controlled Delivery)
1. **Integrasi Gateway WhatsApp (Fonnte API)**:
   - Terimplementasi penuh secara teknis pada `app/services/FonnteService.php`, `app/services/WaQueueService.php`, dan `bin/wa_worker.php`.
   - Menggunakan sakelar lingkungan `WHATSAPP_ENABLED` (default: `false` di konfigurasi produksi aman).
   - Ketika `WHATSAPP_ENABLED=false`, antrean tetap tercatat dengan status `pending` di basis data dan dapat disimulasikan atau dipantau di menu **Monitoring WhatsApp** tanpa mengirimkan transmisi radio ke nomor WhatsApp fisik target (*Zero Unintended Blast*).
   - Dilengkapi penanganan status ketidakpastian pengiriman (*Hardening F-01: reconciliation_required*) dan pencegahan tabrakan worker (*F-02: lockfile `flock`*).

### D. Fitur Berstatus BELUM TERSEDIA / PLACEHOLDER
1. **Modul Kesehatan Mandiri (`app/controllers/Kesehatan.php`)**:
   - File ini hanya memuat kode percontohan sederhana (*echo "Ini adalah halaman Kesehatan"*). Seluruh fungsi riil kesehatan telah diwadahi secara lengkap oleh `Keluhan.php` dan `Pemeriksaan.php`.
2. **Unggah Foto / Berkas Rekam Medis (Attachment Fisik)**:
   - Form pemeriksaan saat ini hanya mendukung teks dan angka vital (suhu, tensi, berat, tinggi). Belum terdapat input tipe berkas (*file upload*) untuk foto rontgen, surat rujukan fisik, atau resep luar.

---

## 8. Analisis Alur Bisnis Utama

### 8.1 Alur Pengajuan dan Penanganan Keluhan Santri
1. **Pengajuan (Santri)**:
   - Santri masuk ke `/keluhan`, mengisi textarea keluhan, dan memilih pengurus pemeriksa yang aktif di Poskestren.
   - Data disimpan ke `riwayat_keluhan` dengan status `menunggu`.
   - Notifikasi in-app berjenis `keluhan_baru` dibuat untuk pengurus yang dipilih.
2. **Tinjauan & Penugasan (Pengurus/Admin)**:
   - Pengurus membuka antrean di `/keluhan`.
   - Pengurus dapat mengklik tombol **"Periksa"** untuk langsung membuka form input pemeriksaan medis.
   - Atau, pengurus dapat mengalihkan keluhan ke pengurus lain via tombol **"Tugaskan"** (`/keluhan/assign`). Notifikasi berjenis `penugasan_baru` atau `pengalihan_tugas` otomatis dikirimkan ke pengurus baru.
3. **Penyelesaian**:
   - Setelah pemeriksaan selesai diinput atau santri dinyatakan pulih, status keluhan diperbarui menjadi `diperiksa` atau `selesai`.
   - Notifikasi in-app otomatis dikirimkan ke akun santri yang bersangkutan memberitahukan bahwa penanganan telah tuntas.

### 8.2 Alur Pemeriksaan Medis dan Integrasi AI Clinical Analysis
1. Petugas yang berwenang (Ronal) membuka `/pemeriksaan/create`.
2. Petugas memilih nama santri (atau terisi otomatis dari antrean keluhan).
3. Petugas menginput data vital (suhu, tensi, berat, tinggi) dan keluhan.
4. Petugas dapat menekan tombol **"Analisis Keluhan dengan AI"**:
   - Request AJAX dikirim ke `/pemeriksaan/analyze_keluhan`.
   - Backend memanggil endpoint AI (Node.js `/api/analyze-keluhan` atau `AiService::callDirectAnalysis()`).
   - Respon draf klinis ditampilkan dalam kontainer hijau dengan peringatan non-diagnosis.
5. Petugas menekan **"Gunakan Saran ke Formulir"**:
   - Jika kolom formulir masih kosong, saran langsung ditempelkan.
   - Jika kolom sudah terisi catatan manual, muncul **Modal Konfirmasi Overwrite** agar data manual tidak hilang secara tidak sengaja.
6. Petugas menyempurnakan data dan menekan tombol **"Simpan Pemeriksaan"**.
7. Data tersimpan permanen di `pemeriksaan_kesehatan`, dan status keluhan di `riwayat_keluhan` otomatis berubah menjadi `selesai`.

### 8.3 Alur Notifikasi In-App dan Antrean WhatsApp
1. Pemicu peristiwa (*event trigger*) terjadi di sistem (contoh: keluhan baru, perubahan penugasan, keluhan tuntas).
2. Sistem mengeksekusi `Notifikasi_model::createNotification()`:
   - Validasi idempotensi mencegah notifikasi ganda untuk peristiwa dan referensi yang sama.
   - Rekord notifikasi in-app disimpan ke tabel `notifikasi`.
   - Nomor WhatsApp target divalidasi dan distandarkan (format `628xxx`).
   - Teks pesan WhatsApp dipastikan generik (tanpa data medis santri).
   - Antrean pesan disimpan ke tabel `notifikasi_wa_queue`.
3. Jika switch `WHATSAPP_ENABLED === true`, `WaQueueService::processBatch()` memproses antrean secara otomatis atau via background worker CLI `php bin/wa_worker.php`.

### 8.4 Alur Chatbot AI Dual-Engine (PHP & Node.js)
1. Pengguna membuka floating widget chatbot di pojok kanan bawah halaman.
2. Pengguna mengirimkan pesan pertanyaan.
3. Alur pemrosesan request:
   - **Tahap 1 (Query Data Aktual)**: Jika pesan menanyakan data statistik pesantren ("berapa santri sakit hari ini?"), `ChatbotDataService` mengeksekusi query agregasi SQL dan mengembalikan jawaban deterministik berbasis data real-time (khusus role admin/pengurus).
   - **Tahap 2 (Pertanyaan Identitas/Deterministik)**: Pertanyaan seputar "siapa saya?", "apa role saya?", dan "apa itu PHBS?" dijawab langsung melalui aturan deterministik lokal.
   - **Tahap 3 (Pre-screening Off-Topic)**: Permintaan di luar topik kesehatan/pesantren (coding, politik, game) langsung ditolak secara sopan via `CANNED_REJECTION`.
   - **Tahap 4 (Generatif AI)**: Pertanyaan konsultasi kesehatan umum diteruskan ke mikrolayanan Node.js (Port 3005) atau PHP fallback engine untuk diproses oleh Google Gemini atau NVIDIA Nemotron dengan *system instruction* medis berdisclaimer ketat.
   - **Tahap 5 (Safe Offline Fallback)**: Jika seluruh engine eksternal offline, chatbot mengembalikan pesan penolakan aman yang menyarankan santri untuk segera menemui petugas Poskestren secara langsung.

### 8.5 Alur Rekapitulasi dan Ekspor Excel Dua Sheet
1. Admin atau Pengurus membuka `/laporan`.
2. Pengguna memilih parameter filter (periode tanggal, kelas, kamar, jenis kelamin, keluhan).
3. Pengguna menekan tombol **"Export Excel"**:
   - Backend memanggil `Laporan::export()`.
   - Data rekam medis diambil dan diformat menggunakan `MedicalFormatHelper`.
   - `SimpleXLSXGen` menghasilkan dokumen spreadsheet berekstensi `.xlsx` dengan 2 lembar kerja (*sheets*):
     - **Sheet 1**: Ringkasan laporan yang terkompresi dan siap cetak/analisis cepat.
     - **Sheet 2**: Detail catatan klinis lengkap untuk dokumentasi audit medis mendalam.
   - File dikirimkan langsung ke peramban pengguna untuk diunduh tanpa menyimpan berkas sementara di server.

---

## 9. Analisis Penanganan Kesalahan, Sanitasi Input, dan Security Posture

| Vektor Keamanan | Implementasi di YukSehat | Status Audit |
|---|---|:---:|
| **SQL Injection** | Menggunakan PDO Prepared Statements di seluruh query `app/core/Database.php`. Tidak ada konkatenasi variabel langsung ke dalam klausa SQL. | **LULUS (100% Aman)** |
| **Cross-Site Scripting (XSS)** | Sanitasi masukan dan luaran dengan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Cookie sesi `HttpOnly` aktif. | **LULUS (100% Aman)** |
| **CSRF Attacks** | Validasi token anti-CSRF kriptografis pada setiap formulir POST. Rotasi token saat transisi sesi. | **LULUS (100% Aman)** |
| **Broken Access Control (IDOR)** | Validasi kepemilikan data rekam medis santri di `Pemeriksaan::show()`. Validasi role di setiap controller. | **LULUS (100% Aman)** |
| **Session Hijacking** | Regenerasi session ID saat login (`session_regenerate_id(true)`), atribut cookie `SameSite=Lax`. | **LULUS (100% Aman)** |
| **Information Leakage** | Penonaktifan `display_errors` saat `APP_ENV=production`. Blokir akses berkas `.env` dan direktori internal via Apache. | **LULUS (100% Aman)** |
| **Medical Privacy Leakage** | Pesan notifikasi WhatsApp disterilkan dari teks keluhan, diagnosis, dan obat santri. | **LULUS (100% Aman)** |

---

## 10. Temuan Teknis Khusus & Batasan Nyata Sistem

1. **Dependensi Eksternal**:
   - Fitur analisis AI membutuhkan koneksi internet aktif dan API Key yang valid (Google AI Studio atau NVIDIA NIM API).
   - Fitur pengiriman WhatsApp membutuhkan akun dan token aktif di platform Fonnte serta perangkat terhubung (*device connected*).
2. **Ketergantungan Port Mikrolayanan**:
   - Chatbot Node.js berjalan di host `127.0.0.1` port `3005`. Jika Node.js tidak dijalankan, chatbot tetap dapat berjalan melalui fallback internal PHP (`AiService.php`), namun latensi respon mungkin sedikit bertambah.
3. **Batasan Format Input Medis**:
   - Tidak ada fitur pengunggahan berkas digital (lampiran foto rontgen/lab).
4. **Kebijakan Petugas Input**:
   - Form input pemeriksaan baru dibatasi secara ketat oleh sistem khusus untuk akun dengan username `ronal` (Role: Pengurus). Akun lain hanya dapat melihat, mengedit, atau menghapus data yang telah ada sesuai batas hak aksesnya.
