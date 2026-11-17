# DOKUMEN TAHAP 1: INISIASI & PENYUSUNAN PRD / SRS MANDIRI
**Mata Kuliah:** Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS (Semester 3)  
**Program Studi:** S1 Teknik Informatika — STMIK IKMI Cirebon  
**Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.  
**Sifat Pengerjaan:** Mandiri / Perorangan (100% Individual Ownership & Full-App Build)  
**Tema Topik Kelompok:** *Smart Healthcare & Public Service AI*  
**Judul Aplikasi Mandiri:** **YukSehat — Sistem Informasi Data & Layanan Kesehatan Santri Berbasis Web dengan Asisten Kecerdasan Artifisial (Poskestren)**

---

## 1. Identifikasi Masalah Nyata & Latar Belakang

Pondok pesantren merupakan ekosistem pendidikan komunal berbasis asrama (*boarding school*) yang menampung ratusan hingga ribuan santri selama 24 jam penuh. Pola hidup bersama dalam satu lingkungan asrama menyebabkan santri memiliki kerentanan tinggi terhadap penularan penyakit menular (seperti scabies, infeksi saluran pernapasan akut / ISPA, influenza, diare, dan demam berdarah), serta masalah higienitas santri (*Perilaku Hidup Bersih dan Sehat / PHBS*).

Berdasarkan observasi di lapangan, Pos Kesehatan Pesantren (Poskestren) menghadapi kendala nyata:
1. **Pencatatan Konvensional Berbasis Kertas**: Buku rekam medis fisik mudah rusak, tercecer, dan membutuhkan waktu lama untuk ditelusuri riwayatnya saat santri mengalami sakit berulang.
2. **Hambatan Pelaporan Santri**: Santri sering menunda melaporkan keluhan rasa sakit karena enggan atau antrean manual posko yang tidak teratur.
3. **Keterbatasan Triase Petugas Posko**: Petugas kesehatan asrama membutuhkan asisten klinis pendukung keputusan awal (*second opinion*) yang ringkas dan aman saat menyusun resume medis dan resep obat.
4. **Rekapitulasi Manual**: Penyusunan laporan berkala santri sakit untuk pimpinan pondok memakan waktu dan berisiko salah hitung.
5. **Ketiadaan Sistem Notifikasi Terintegrasi**: Pengurus tidak langsung mengetahui santri mana yang membutuhkan penanganan saat pergantian regu jaga.

**YukSehat** hadir sebagai solusi rekayasa perangkat lunak mandiri yang mendigitalkan seluruh siklus layanan Poskestren, mengintegrasikan asisten kecerdasan artifisial (AI) dual-engine, serta menyediakan rekapitulasi data terstruktur dan antrean notifikasi aman.

---

## 2. Nilai Guna dan Peran Strategis Fitur AI Utama

Aplikasi YukSehat menanamkan fitur **Kecerdasan Artifisial (AI)** bukan sebagai tempelan (*gimmick*), melainkan sebagai pilar fungsional strategis:

### A. Fitur AI 1: Asisten Tinjauan Klinis Pemeriksaan (*AI Clinical Drafter*)
- **Teknologi**: Google Gemini AI (`gemini-flash-lite-latest` / `gemini-3.5-flash`) & NVIDIA NIM (`nvidia/nemotron-3.5-bolt-e4b`).
- **Peran Fungsional**: Terintegrasi langsung pada formulir pemeriksaan kesehatan fisik (`/pemeriksaan/create`). Saat petugas menekan tombol *"Analisis Keluhan dengan AI"*, sistem mengirimkan teks keluhan dan tanda vital fisik santri (suhu, tensi, berat, tinggi) secara anonim ke API AI.
- **Dampak Nyata**: AI menghasilkan draf resume terstruktur dalam hitungan detik yang memuat 4 seksi klinis:
  1. Intisari Keluhan & Dugaan Klinis Awal.
  2. Tindakan Observasi Poskestren.
  3. Rekomendasi Terapi Gejala Ringan & Edukasi.
  4. Tanda Bahaya (*Red Flags*) yang mewajibkan rujukan ke rumah sakit luar.
- **Etika & Keselamatan**: Dilengkapi *non-diagnostic disclaimer*. Saran AI dapat disalin ke formulir dengan modal konfirmasi interaktif pencegah penimpaan data manual.

### B. Fitur AI 2: Chatbot Interaktif Asisten Santri & Analitik Data Pesantren
- **Teknologi**: Mikrolayanan Node.js (Port 3005) + Fallback internal PHP cURL.
- **Peran Fungsional**:
  - **Bagi Santri**: Konsultasi kesehatan mandiri 24 jam, panduan pertolongan pertama, dan edukasi PHBS asrama.
  - **Bagi Staf/Admin**: Menjawab kueri analitik basis data aktual secara deterministik (contoh: *"Berapa pemeriksaan hari ini?"*, *"Siapa yang sakit hari ini?"*, *"Berapa santri rawat inap?"*).
- **Pengamanan Model**: Dilengkapi mekanisme *Pre-screening Off-Topic Guard* yang menolak pertanyaan di luar topik kesehatan/pesantren secara otomatis.

---

## 3. Spesifikasi Kebutuhan Pengguna (User Stories & Acceptance Criteria)

### A. Persona Pengguna
1. **Santri (Budi Santoso, 16 Th)**: Santri asrama yang memerlukan kanal pelaporan sakit cepat, riwayat medis pribadi, dan konsultasi AI.
2. **Pengurus Poskestren (Petugas Ronal, 27 Th)**: Petugas medis jaga yang melayani pemeriksaan tanda vital, penanganan santri, dan pencatatan obat.
3. **Administrator Sistem (Ust. Fauzan, 34 Th)**: Penanggung jawab data pesantren, manajemen akun staf, konfigurasi AI, dan monitoring antrean WhatsApp.

### B. User Stories & Acceptance Criteria

#### User Story 1: Pengajuan Keluhan Sakit Mandiri (Santri)
- **Sebagai** Santri Pondok Pesantren,
- **Saya ingin** melaporkan keluhan sakit melalui formulir online dan memilih petugas pemeriksa,
- **Sehingga** petugas Poskestren segera menerima pemberitahuan dan menangani kondisi saya.
- **Acceptance Criteria**:
  - Kolom teks keluhan wajib diisi (tidak boleh kosong).
  - Pilihan petugas pemeriksa wajib mengarah ke akun pengurus yang berstatus aktif.
  - Data tersimpan di tabel `riwayat_keluhan` dengan status awal `'menunggu'`.
  - Notifikasi in-app dan pesan WhatsApp generik tercatat untuk petugas terpilih.

#### User Story 2: Pemeriksaan Fisik & Analisis Klinis AI (Pengurus Ronal)
- **Sebagai** Petugas Pemeriksa (Ronal),
- **Saya ingin** mencatat tanda vital dan menggunakan tombol analisis AI pada keluhan santri,
- **Sehingga** saya mendapatkan draf pertimbangan klinis yang mempercepat penulisan diagnosa dan obat.
- **Acceptance Criteria**:
  - Akses form tambah periksa (`/pemeriksaan/create`) dilindungi khusus untuk akun `ronal` (Aturan Bisnis BR-002).
  - Form memuat input: Suhu (°C), Tekanan Darah (mmHg), Berat Badan (kg), Tinggi Badan (cm), Diagnosa, Tindakan, Obat, dan Catatan.
  - Tombol "Analisis Keluhan dengan AI" mengembalikan 4 seksi saran ringkas dengan penafian non-diagnostik dalam waktu < 6 detik.
  - Tombol "Gunakan Saran ke Formulir" menampilkan modal konfirmasi kustom jika kolom form sudah berisi catatan manual.
  - Penyimpanan berhasil otomatis mengubah status keluhan santri terkait menjadi `'selesai'`.

#### User Story 3: Konsultasi Interaktif Chatbot & Kueri Data (Semua Peran)
- **Sebagai** Pengguna (Santri / Staf),
- **Saya ingin** berkonsultasi via floating chatbot widget di pojok layar,
- **Sehingga** saya mendapatkan jawaban edukasi kesehatan atau data statistik kesehatan pesantren secara cepat.
- **Acceptance Criteria**:
  - Santri mendapatkan respon edukasi kesehatan sopan berpenafian resmi.
  - Pengurus/Admin yang menanyakan metrik harian dijawab dengan data agregasi SQL real-time.
  - Pertanyaan di luar topik ditolak dengan pesan standar (*canned rejection*).

#### User Story 4: Rekapitulasi & Ekspor Laporan Excel Multi-Sheet (Admin & Pengurus)
- **Sebagai** Administrator / Pengurus,
- **Saya ingin** memfilter riwayat pemeriksaan dan mengunduhnya ke format Excel (.xlsx),
- **Sehingga** saya dapat menyajikan laporan resmi kondisi kesehatan santri kepada pimpinan pesantren.
- **Acceptance Criteria**:
  - Filter mencakup: Rentang tanggal, nama santri, kelas, kamar, jenis kelamin, dan keluhan.
  - File XLSX memuat 2 sheet: Sheet 1 (Ringkasan Laporan terkompresi) dan Sheet 2 (Detail Catatan Klinis Lengkap).
  - File terunduh langsung via browser tanpa menyimpan berkas sementara di server.

---

## 4. Spesifikasi Kebutuhan Fungsional (Functional Requirements)

| Kode FR | Modul | Deskripsi Kebutuhan Fungsional | Masukan (Input) | Luaran (Output) |
|---|---|---|---|---|
| **FR-AUTH-001** | Autentikasi | Login Multi-Identifier dengan verifikasi password BCrypt | Username / NIS / Nama Petugas, Password, CSRF Token | Sesi aktif terproteksi & redirect Dashboard |
| **FR-AUTH-002** | Autentikasi | Registrasi Mandiri Akun Santri Baru | Biodata santri, NIS unik, Username unik, Password (min 8 char) | Akun santri tersimpan di tabel `users` & `santri` |
| **FR-SAN-001** | Master Santri | Tinjauan Direktori Santri Terfilter & Paginasi | Kata kunci pencarian, filter kelas, filter kamar, halaman | Tabel data santri aktif terpaginasi 10 data/halaman |
| **FR-SAN-002** | Master Santri | Penonaktifan Akun Santri (*Soft Delete*) | ID Santri, CSRF Token | Status user berubah menjadi `'inactive'` |
| **FR-KEL-001** | Keluhan | Pengajuan Keluhan Sakit Mandiri Santri | Teks keluhan, ID pemeriksa terpilih, CSRF Token | Rekord keluhan berstatus `'menunggu'` & notifikasi pengurus |
| **FR-KEL-002** | Keluhan | Antrean Keluhan Petugas Terfilter Tab Status | Tab status filter (Semua, Menunggu, Diperiksa, Selesai) | Tabel antrean triase keluhan santri real-time |
| **FR-KEL-003** | Keluhan | Penugasan & Pengalihan Pemeriksa (*Assignment*) | ID Keluhan, ID Pengurus Baru, CSRF Token | Penugasan terbarui & notifikasi penugasan baru |
| **FR-MED-001** | Rekam Medis | Pencatatan Pemeriksaan Medis (Khusus Petugas Ronal) | Data vital (suhu, tensi, bb, tb), diagnosa, tindakan, obat | Rekord pemeriksaan tersimpan & keluhan tuntas |
| **FR-MED-002** | Rekam Medis | Analisis Keluhan Klinis via AI Google/NVIDIA | Teks keluhan, tanda vital fisik | Draf 4 seksi resume medis awal & penafian |
| **FR-MED-003** | Rekam Medis | Proteksi Kepemilikan Rekam Medis (*Anti-IDOR*) | ID Pemeriksaan yang diminta via URL | Detail rekam medis jika sah, atau pesan penolakan jika milik orang lain |
| **FR-REP-001** | Laporan | Ekspor Laporan Rekapitulasi Excel Multi-Sheet | Parameter filter tanggal, kelas, kamar | Berkas `laporan-kesehatan-YYYY-MM-DD.xlsx` 2 sheet |
| **FR-CHAT-001**| Chatbot AI | Konsultasi Interaktif Chatbot Dual-Engine | Pesan teks pertanyaan pengguna | Respon cerdas AI / kueri statistik / penolakan off-topic |
| **FR-NOTIF-001**| Notifikasi | Pusat Notifikasi In-App & Modal Rincian | ID Notifikasi, Aksi Baca AJAX | Modal rincian interaktif & counter unread berkurang |
| **FR-NOTIF-002**| Notifikasi | Antrean Pengiriman WhatsApp Aman (Safe-Mode) | ID Notifikasi, Nomor tujuan, Pesan generik | Rekord `notifikasi_wa_queue` status pending |
| **FR-ADM-001** | Administrasi| Kelola Akun, Role, Status, dan Nomor WhatsApp | Username, Role, Status, Nama, No HP, CSRF Token | Data akun terbarui dengan proteksi anti-lockout admin |
| **FR-ADM-002** | Administrasi| Konfigurasi Model AI & Manajemen Kunci API | Model Chatbot, Model Klinis, API Key baru | Pengaturan tersimpan di `system_settings` & uji latensi |
| **FR-ADM-003** | Administrasi| Monitoring Antrean WhatsApp & Status Gateway | Tombol Check Device / Reconcile / Process Batch | Status kuota Fonnte, status koneksi, dan audit log |

---

## 5. Spesifikasi Kebutuhan Non-Fungsional (Non-Functional Requirements)

| Kode NFR | Kategori | Parameter Spesifikasi | Nilai Target & Mekanisme Pengujian |
|---|---|---|---|
| **NFR-SEC-001** | Keamanan | Pencegahan SQL Injection | 100% Prepared Statements PDO ber-binding (`app/core/Database.php`). Zero raw concatenation. |
| **NFR-SEC-002** | Keamanan | Perlindungan CSRF | Token kriptografis 64-karakter divalidasi via `hash_equals()`, dirotasi saat regenerasi sesi. |
| **NFR-SEC-003** | Keamanan | Perlindungan XSS & Sesi | Seluruh luaran teks disanitasi `htmlspecialchars()`, cookie `httponly=true`, `samesite=Lax`. |
| **NFR-SEC-004** | Keamanan | Privasi Medis WhatsApp | Zero Medical Leakage: Pesan WA dilarang memuat diagnosa, obat, atau keluhan santri. |
| **NFR-SEC-005** | Keamanan | Perlindungan Server | Berkas `.env`, `.sql`, script pengujian, dan direktori internal diblokir via Apache `.htaccess`. |
| **NFR-PERF-001**| Performa | Waktu Muat Halaman Lokal | Rata-rata < 1,5 detik untuk dashboard dan tabel terpaginasi. |
| **NFR-PERF-002**| Performa | Respon Asisten AI | Selesai dalam rentang 2–6 detik via Google Gemini / NVIDIA NIM. |
| **NFR-PERF-003**| Performa | Efisiensi Ekspor XLSX | Generator `SimpleXLSXGen` memproses 1.000 baris < 3 detik dengan konsumsi memori < 32 MB. |
| **NFR-REL-001** | Keandalan | Ketahanan Gateway | Gangguan API eksternal tidak membatalkan transaksi basis data lokal (*fail-safe non-blocking*). |
| **NFR-REL-002** | Keandalan | Anti-Overlapping Worker | Worker CLI WhatsApp dilindungi file lock eksklusif non-blocking (`flock`). |
| **NFR-REL-003** | Keandalan | Bounded Retry Backoff | Retri otomatis gagal bertingkat (60s, 300s, 900s) dengan batas maksimal 3 kali percobaan. |
| **NFR-USE-001** | Usabilitas| Desain Responsif & Interaktif | Tampilan adaptif desktop/tablet/mobile; konfirmasi menggunakan modal kustom Bootstrap (tanpa alert kaku). |
| **NFR-MAINT-001**| Portabilitas| Kemandirian Arsitektur | MVC Native murni beroperasi mulus di XAMPP PHP 8.1+ tanpa ketergantungan paket biner berat. |
# DOKUMEN TAHAP 2: PERANCANGAN UML & ARSITEKTUR SISTEM MANDIRI (SDD)
**Mata Kuliah:** Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS (Semester 3)  
**Program Studi:** S1 Teknik Informatika — STMIK IKMI Cirebon  
**Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.  
**Sifat Pengerjaan:** Mandiri / Perorangan (100% Individual Ownership & Full-App Build)  
**Tema Topik Kelompok:** *Smart Healthcare & Public Service AI*  
**Judul Aplikasi:** **YukSehat — Sistem Informasi Data & Layanan Kesehatan Santri Berbasis Web (Poskestren)**

---

## 1. Pendahuluan Arsitektur Sistem

Dokumen Perancangan Perangkat Lunak (*Software Design Document* - SDD) ini memvisualisasikan struktur statis dan perilaku dinamis sistem aplikasi **YukSehat** secara utuh. Perancangan mengadopsi prinsip **Clean Code & Object-Oriented Design (SOLID)** di atas pola arsitektur **Model-View-Controller (MVC)**.

Sistem memadukan komputasi monolitik PHP Native yang tangguh dan ringan dengan arsitektur mikrolayanan *Node.js* untuk orkestrasi asisten AI generatif.

---

## 2. Pemodelan 4 Diagram UML Utama

### 2.1 Use Case Diagram & Skenario Use Case

#### A. Use Case Diagram
```mermaid
flowchart LR
    subgraph Aktor
        S["Santri"]
        P["Pengurus Poskestren"]
        A["Administrator"]
        AI["Layanan AI Cloud (Gemini / NVIDIA)"]
        WA["Fonnte WhatsApp Gateway"]
    end

    subgraph "Sistem Informasi YukSehat"
        UC01["Autentikasi (Login/Logout/Register)"]
        UC02["Melihat Dashboard Statistik"]
        UC03["Mengajukan Keluhan Kesehatan"]
        UC04["Mengelola Antrean Keluhan (Triase)"]
        UC05["Menugaskan / Mengalihkan Petugas"]
        UC06["Mencatat Pemeriksaan Fisik & Vital (Khusus Ronal)"]
        UC07["Analisis Keluhan Klinis AI"]
        UC08["Konsultasi Interaktif Chatbot AI"]
        UC09["Melihat Notifikasi In-App & Modal Rincian"]
        UC10["Melihat Riwayat Medis Sendiri"]
        UC11["Mengelola Master Data Santri"]
        UC12["Rekapitulasi & Ekspor Excel Multi-Sheet"]
        UC13["Manajemen Akun Pengguna & Role"]
        UC14["Konfigurasi Model AI & Kunci API"]
        UC15["Monitoring Antrean WhatsApp & Status Gateway"]
    end

    S --> UC01
    S --> UC02
    S --> UC03
    S --> UC08
    S --> UC09
    S --> UC10

    P --> UC01
    P --> UC02
    P --> UC04
    P --> UC05
    P --> UC06
    P --> UC07
    P --> UC08
    P --> UC09
    P --> UC11
    P --> UC12

    A --> UC01
    A --> UC02
    A --> UC04
    A --> UC05
    A --> UC08
    A --> UC11
    A --> UC12
    A --> UC13
    A --> UC14
    A --> UC15

    UC07 -.-> AI
    UC08 -.-> AI
    UC04 -.-> WA
```

#### B. Skenario Use Case Utama

##### Skenario Use Case 1: Pencatatan Pemeriksaan Medis dengan Bantuan AI (UC-06 & UC-07)
- **Aktor Utama**: Pengurus Poskestren Berwenang (Petugas Ronal).
- **Aktor Pendukung**: Google Gemini API / NVIDIA NIM API.
- **Kondisi Awal (Pre-condition)**: Petugas Ronal telah login dengan sesi aktif dan membuka halaman antrean keluhan santri.
- **Kondisi Akhir (Post-condition)**: Rekam medis tersimpan di tabel `pemeriksaan_kesehatan`, status keluhan menjadi `'selesai'`, dan santri menerima notifikasi in-app.
- **Alur Utama (Main Flow)**:
  1. Petugas mengklik tombol "Periksa" pada salah satu antrean keluhan santri.
  2. Sistem membuka `/pemeriksaan/create` dan mengisi otomatis (*pre-fill*) data santri dan narasi keluhan.
  3. Petugas menginput data vital: suhu tubuh (°C), tekanan darah, berat badan (kg), dan tinggi badan (cm).
  4. Petugas menekan tombol "Analisis Keluhan dengan AI".
  5. Sistem mengirimkan teks keluhan dan data vital anonim ke API AI dan menampilkan 4 seksi draf tinjauan klinis ringkas.
  6. Petugas menekan tombol "Gunakan Saran ke Formulir".
  7. Sistem menyalin saran AI ke kolom diagnosa, tindakan, obat, dan catatan.
  8. Petugas menyempurnakan resep obat dan instruksi perawatan.
  9. Petugas menekan tombol "Simpan Pemeriksaan".
  10. Sistem menyimpan data, memperbarui status keluhan menjadi selesai, memicu notifikasi santri, dan mengalihkan ke daftar pemeriksaan.
- **Alur Alternatif (Alternative Flow - Konfirmasi Overwrite)**:
  - Pada langkah 6, jika kolom formulir sudah berisi catatan manual pemeriksa, sistem menampilkan *Modal Konfirmasi Overwrite*. Data form hanya diganti jika petugas mengonfirmasi "Ya, Tempelkan".
- **Alur Eksepsi (Exception Flow)**:
  - Jika pengguna yang mengakses bukan petugas Ronal, sistem memblokir akses dan mengalihkan ke `/pemeriksaan` dengan flash message akses ditolak (*Aturan Bisnis BR-002*).

---

### 2.2 Activity Diagram (Alur Bisnis & Alur Fitur AI)

```mermaid
stateDiagram-v2
    [*] --> SantriKirimKeluhan: Santri Input Keluhan & Pilih Petugas
    SantriKirimKeluhan --> SimpanKeluhanDB: Simpan status 'menunggu'
    SimpanKeluhanDB --> KirimNotifPengurus: Buat Notif In-App & WA Queue
    
    KirimNotifPengurus --> PengurusBukaAntrean: Pengurus Buka /keluhan
    PengurusBukaAntrean --> KlikPeriksa: Klik Tombol 'Periksa'
    
    state "Pemeriksaan & Bantuan AI" as AI_Block {
        KlikPeriksa --> FormPeriksaPrefilled: Form Terbuka Otomatis
        FormPeriksaPrefilled --> InputTandaVital: Petugas Input Suhu, Tensi, BB, TB
        InputTandaVital --> KlikAnalisisAI: Klik 'Analisis Keluhan dengan AI'
        KlikAnalisisAI --> RequestEngineAI: Kirim via Node.js / cURL PHP
        RequestEngineAI --> RenderDrafAI: Tampilkan 4 Seksi Saran Klinis Ringkas
        RenderDrafAI --> KlikGunakanSaran: Klik 'Gunakan Saran ke Formulir'
        
        state c1 <<choice>>
        KlikGunakanSaran --> c1: Apakah Kolom Form Sudah Terisi Manual?
        c1 --> TampilkanModalKonfirmasi: Ya (Ada data manual)
        c1 --> TempelkanSaranForm: Tidak (Form masih kosong)
        TampilkanModalKonfirmasi --> TempelkanSaranForm: Petugas Konfirmasi 'Ya, Tempelkan'
        TampilkanModalKonfirmasi --> FormPeriksaPrefilled: Batal
    }
    
    TempelkanSaranForm --> FinalisasiObat: Petugas Sempurnakan Obat & Catatan
    FinalisasiObat --> SimpanPemeriksaanDB: Klik 'Simpan Pemeriksaan'
    
    SimpanPemeriksaanDB --> UpdateStatusSelesai: Update riwayat_keluhan menjadi 'selesai'
    UpdateStatusSelesai --> KirimNotifSantri: Buat Notifikasi Selesai ke Santri
    KirimNotifSantri --> [*]: Pemeriksaan Tuntas
```

---

### 2.3 Class Diagram Berorientasi Objek (Mematuhi Prinsip SOLID)

Class diagram berikut memodelkan struktur kelas berorientasi objek pada sistem YukSehat:
- **Single Responsibility Principle (SRP)**: Setiap kelas memiliki satu tanggung jawab fokus (contoh: `Database` khusus PDO, `AuthHelper` khusus otorisasi sesi, `MedicalFormatHelper` khusus teks klinis, `FonnteService` khusus komunikasi WhatsApp).
- **Open/Closed Principle (OCP)**: Layanan AI `AiService` dirancang dapat menerima katalog model baru tanpa merombak inti engine.
- **Dependency Inversion**: Controller bergantung pada abstraksi helper dan service wrapper.

```mermaid
classDiagram
    class App {
        -string controller
        -string method
        -array params
        +__construct()
        +parseURL() array
    }

    class Controller {
        +view(string view, array data) void
        +model(string model) object
    }

    class Database {
        -string host
        -string user
        -string pass
        -string db_name
        -PDO dbh
        -PDOStatement stmt
        +__construct()
        +query(string query) void
        +bind(string param, mixed value, int type) void
        +execute() bool
        +resultSet() array
        +single() array
        +rowCount() int
        +lastInsertId() string
    }

    class AuthHelper {
        +isLoggedIn() bool
        +user() array
        +requireLogin() void
        +requireRole(array roles) void
        +canInputPemeriksaan() bool
        +requireCanInputPemeriksaan() void
        +generateCSRF() string
        +rotateCSRF() string
        +validateCSRF(string token) bool
    }

    class MedicalFormatHelper {
        +isAiDraft(string text) bool
        +cleanEntities(string text) string
        +truncateWord(string text, int max, string append) string
        +summarizeHasil(string text, int max) string
        +formatExcelText(string text, string type, int max) string
        +formatExcelFullText(string text) string
    }

    class AiService {
        -string NVIDIA_ENDPOINT
        +getModelCatalog() array
        +getConfig(bool refresh) array
        +saveModels(string chatbot, string clinical) bool
        +saveApiKey(string provider, string key) bool
        +callClinicalAnalysis(string keluhan, array vitals) array
        +callDirectGemini(array contents, string sysPrompt) array
        +callDirectNvidia(array messages) array
    }

    class FonnteService {
        -string API_SEND_URL
        -string API_DEVICE_URL
        +isConfigured() bool
        +formatPhoneNumber(string phone) string
        +send(string target, string message, array options) array
        +checkDevice() array
    }

    class WaQueueService {
        +isWhatsAppEnabled() bool
        +processBatch(int limit) array
        +claimPendingItems(int limit) array
        +recoverStaleLocks(int mins) int
        +reconcileItem(int id, string action, string notes) array
    }

    class PemeriksaanController {
        +index() void
        +create(int santri_id) void
        +store() void
        +show(int id) void
        +edit(int id) void
        +update(int id) void
        +delete(int id) void
        +analyze_keluhan() void
    }

    class KeluhanController {
        +index() void
        +kirim() void
        +assign() void
        +update_status() void
    }

    class LaporanController {
        +index() void
        +export() void
    }

    Controller <|-- PemeriksaanController
    Controller <|-- KeluhanController
    Controller <|-- LaporanController

    PemeriksaanController ..> Database
    PemeriksaanController ..> AuthHelper
    PemeriksaanController ..> AiService
    PemeriksaanController ..> MedicalFormatHelper

    KeluhanController ..> Database
    KeluhanController ..> AuthHelper
    KeluhanController ..> FonnteService

    LaporanController ..> Database
    LaporanController ..> MedicalFormatHelper
```

---

### 2.4 Sequence Diagram (Alur Pertukaran Pesan: Frontend, Backend, DB, AI API)

Diagram ini mengilustrasikan alur pertukaran pesan end-to-end saat pemeriksaan medis diproses bersama AI:

```mermaid
sequenceDiagram
    autonumber
    actor User as Petugas Ronal
    participant UI as Browser (View Pemeriksaan)
    participant Ctrl as Pemeriksaan Controller
    participant Auth as AuthHelper
    participant Svc as AiService / Node.js
    participant AI as Cloud AI (Gemini / NVIDIA)
    participant DB as MySQL Database

    User->>UI: Klik "Analisis Keluhan dengan AI"
    UI->>Ctrl: POST /pemeriksaan/analyze_keluhan (AJAX + CSRF + Vitals)
    Ctrl->>Auth: requireLogin() & validateCSRF()
    Auth-->>Ctrl: Valid
    Ctrl->>Svc: callClinicalAnalysis(keluhan, vitals)
    Svc->>AI: HTTPS POST Request (Payload Anonymized)
    AI-->>Svc: 200 OK (Structured Clinical Draft)
    Svc-->>Ctrl: Array Hasil Draf
    Ctrl-->>UI: JSON { success: true, analysis: "...", parsed: {...} }
    UI->>UI: Render Draf AI dalam Kontainer Hijau
    
    User->>UI: Klik "Gunakan Saran ke Formulir"
    alt Form Sudah Ada Catatan Manual
        UI->>User: Tampilkan Modal Konfirmasi Overwrite
        User->>UI: Klik "Ya, Tempelkan"
    end
    UI->>UI: Masukkan Saran AI ke Input Form
    
    User->>UI: Klik "Simpan Pemeriksaan"
    UI->>Ctrl: POST /pemeriksaan/store (Payload Lengkap)
    Ctrl->>Auth: requireCanInputPemeriksaan() (Aturan Ronal)
    Auth-->>Ctrl: Wewenang Sah (Username: ronal)
    Ctrl->>DB: INSERT INTO pemeriksaan_kesehatan (...)
    DB-->>Ctrl: LastInsertId
    Ctrl->>DB: UPDATE riwayat_keluhan SET status='selesai' WHERE id=:keluhan_id
    DB-->>Ctrl: OK
    Ctrl->>DB: INSERT INTO notifikasi (user_id=santri, status_selesai)
    DB-->>Ctrl: OK
    Ctrl-->>UI: Redirect /pemeriksaan (Flash Pesan Sukses)
    UI-->>User: Tampilkan Halaman Riwayat & Toast Sukses
```

---

## 3. Entity Relationship Diagram (ERD) Basis Data

```mermaid
erDiagram
    USERS ||--|| PENGURUS : "has profile"
    USERS ||--|| SANTRI : "has profile"
    USERS ||--o{ NOTIFIKASI : "receives"
    USERS ||--o{ NOTIFIKASI_WA_QUEUE : "targets"
    USERS ||--o{ RIWAYAT_KELUHAN : "assigned to"
    USERS ||--o{ PEMERIKSAAN_KESEHATAN : "examines"

    SANTRI ||--o{ RIWAYAT_KELUHAN : "reports"
    SANTRI ||--o{ PEMERIKSAAN_KESEHATAN : "undergoes"

    RIWAYAT_KELUHAN ||--o| PEMERIKSAAN_KESEHATAN : "resolved by"
    NOTIFIKASI ||--o{ NOTIFIKASI_WA_QUEUE : "generates queue"

    USERS {
        int id PK
        string username UK
        string password
        enum role
        enum status
        timestamp created_at
        timestamp updated_at
    }

    PENGURUS {
        int id PK
        int user_id FK, UK
        string nama
        string jabatan
        string no_hp
        timestamp created_at
        timestamp updated_at
    }

    SANTRI {
        int id PK
        int user_id FK, UK
        string nis UK
        string nama
        enum jenis_kelamin
        date tanggal_lahir
        string kelas
        string kamar
        string no_hp
        string golongan_darah
        text alergi
        text riwayat_penyakit
        timestamp created_at
        timestamp updated_at
    }

    RIWAYAT_KELUHAN {
        int id PK
        int santri_id FK
        datetime tanggal_keluhan
        text keluhan
        int assigned_to FK
        enum status
        timestamp created_at
        timestamp updated_at
    }

    PEMERIKSAAN_KESEHATAN {
        int id PK
        int santri_id FK
        int pemeriksa_id FK
        int keluhan_id FK
        datetime tanggal_pemeriksaan
        text keluhan
        decimal suhu
        string tekanan_darah
        decimal berat_badan
        decimal tinggi_badan
        text hasil_pemeriksaan
        text tindakan
        text obat
        text catatan
        timestamp created_at
        timestamp updated_at
    }

    NOTIFIKASI {
        int id PK
        int user_id FK
        string jenis
        string judul
        text pesan
        string link_url
        string referensi_tipe
        int referensi_id
        datetime dibaca_pada
        datetime created_at
    }

    NOTIFIKASI_WA_QUEUE {
        int id PK
        int notifikasi_id FK
        int user_id
        string nomor_tujuan
        text pesan
        enum status
        string provider_status
        string provider_message_id
        int percobaan
        int max_percobaan
        text error_message
        datetime sent_at
        datetime claimed_at
        datetime next_retry_at
        timestamp created_at
    }

    SYSTEM_SETTINGS {
        string setting_key PK
        text setting_value
        timestamp updated_at
    }
```

---

## 4. Perancangan & Prototipe Antarmuka UI/UX

### A. Design System & Karakteristik Visual
- **Tema Warna Utama**:
  - *Primary Emerald Green* (`#059669` / `#10b981`): Melambangkan kesehatan, kesegaran, dan identitas pesantren.
  - *Deep Navy Slate* (`#0f172a` / `#1e293b`): Warna sidebar dan teks header untuk kontras tinggi dan kesan profesional.
  - *Accent Warning Gold* (`#f59e0b`): Tombol edit dan indikator peringatan draf AI / red flags.
  - *Soft Surface Neutral* (`#f8fafc`): Latar belakang halaman untuk kenyamanan mata pengguna (*ergonomic reading*).
- **Tipografi**: Sistem font native antarmuka `system-ui, -apple-system, "Segoe UI", Roboto, sans-serif` yang menjamin rendering cepat tanpa unduhan font web lambat.
- **Micro-Interactions**: Tombol interaktif dilengkapi efek tactile click (`:active scale(0.96)`), glassmorphism modal backdrop blur, dan visual ripple click feedback.

### B. Wireframe / Mockup Tata Letak Komponen Kunci

#### 1. Tata Letak Dashboard Utama (Desktop View)
```
+-----------------------------------------------------------------------------------+
| [Brand: YukSehat]   [Search Input...]                  [(Bell) Notif] [Profil v]  |
+---------------------+-------------------------------------------------------------+
| MENU UTAMA          | DASHBOARD KESEHATAN SANTRI                                  |
| • Dashboard         | Ringkasan Statistik Poskestren Real-Time                    |
| • Notifikasi        +-------------------+-------------------+---------------------+
|                     | Total Santri      | Periksa Hari Ini  | Perlu Rujukan       |
| LAYANAN KESEHATAN   | [ 7 Santri Aktif] | [ 3 Pemeriksaan ] | [ 1 Kasus ]         |
| • Antrean Keluhan   +-------------------+-------------------+---------------------+
| • Pemeriksaan       |                                                             |
| • Data Santri       | [Tabel 5 Pemeriksaan Terbaru]                               |
|                     | No | Santri      | Kamar | Tgl Periksa   | Tindakan | Aksi   |
| REKAPITULASI        | 1  | Budi (10A)  | A-ULA | 16/11 09:30   | UKS 1 Hari | Detail |
| • Laporan           |                                                             |
|                     |                                            [+ Tanya AI (•)] |
+---------------------+-------------------------------------------------------------+
```

#### 2. Formulir Pemeriksaan & Integrasi AI (Dual Panel)
```
+-----------------------------------------------------------------------------------+
| FORM INPUT PEMERIKSAAN KESEHATAN (Khusus Petugas Ronal)                           |
+-----------------------------------------------------------------------------------+
| Informasi Santri: [ 212026 - Budi Santoso (Kelas X IPA, Kamar A-ULA)          v ]  |
| Tanggal & Waktu : [ 2026-11-16T10:00                                            ] |
+-----------------------------------------------------------------------------------+
| Data Vital:                                                                       |
| Suhu: [ 37.8 ] °C  | Tensi: [ 120/80 ] mmHg | BB: [ 52 ] kg | TB: [ 165 ] cm      |
+-----------------------------------------------------------------------------------+
| Keluhan Santri:                                   [ (* AI) Analisis Keluhan AI ]  |
| [ Demam sejak kemarin malam, kepala pusing berdenyut dan badan menggigil....... ]  |
+-----------------------------------------------------------------------------------+
| [KONTAINER DRAF AI POSKESTREN]                                    [ x Tutup ]    |
| • Dugaan Klinis : Febris hari ke-1 ec susp. Infeksi Virus Akut                   |
| • Observasi     : Pantau suhu berkala per 4 jam, berikan kompres hangat           |
| • Rekomendasi   : Paracetamol 500mg 3x1 post prandial, hidrasi oral cukup        |
| • Tanda Bahaya  : Kejang demam, kaku kuduk, sesak napas -> Segera Rujuk          |
|                                            [ (Clipboard) Gunakan Saran ke Form ]  |
+-----------------------------------------------------------------------------------+
| Hasil Diagnosa  : [ ........................................................... ] |
| Tindakan Medis  : [ ........................................................... ] |
| Obat Diberikan  : [ ........................................................... ] |
| Catatan / Bahaya: [ ........................................................... ] |
+-----------------------------------------------------------------------------------+
|                                                 [ Batal ]  [ Simpan Pemeriksaan ] |
+-----------------------------------------------------------------------------------+
```
# LAPORAN AKHIR PROYEK TERINTEGRASI REKAYASA PERANGKAT LUNAK (OBE)
## YUKSEHAT: SISTEM INFORMASI POS KESEHATAN PESANTREN BERBASIS WEB DENGAN DUAL-ENGINE ARTIFICIAL INTELLIGENCE & WHATSAPP GATEWAY

---

**Mata Kuliah:** Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS  
**Kurikulum:** Outcome-Based Education (OBE) — Semester Ganjil 2026/2027  
**Tema Topik Kelompok:** *Smart Healthcare & Public Service AI*  
**Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.  

**Disusun Oleh:**  
- **Nama Mahasiswa:** [Muhamad Fauzan]  
- **Nomor Induk Mahasiswa (NIM):** [41257209]  
- **Program Studi:** S1 - Teknik Informatika  
- **Institusi:** STMIK IKMI Cirebon  

---

## KATA PENGANTAR

Puji dan syukur penulis panjatkan ke hadirat Allah SWT atas limpahan rahmat, taufik, dan hidayah-Nya, sehingga pengerjaan Tugas Proyek Akhir Mandiri Mata Kuliah Rekayasa Perangkat Lunak (TIKB2503021) yang berjudul **"YUKSEHAT: Sistem Informasi Pos Kesehatan Pesantren Berbasis Web dengan Integrasi Dual-Engine Artificial Intelligence dan WhatsApp Gateway"** dapat terselesaikan dengan optimal.

Laporan ini disusun sebagai pemenuhan luaran pembelajaran berbasis capaian (*Outcome-Based Education* / OBE) yang mengintegrasikan seluruh tahapan Siklus Hidup Pengembangan Perangkat Lunak (*Software Development Life Cycle* - SDLC) yang mencakup:
1. **Tahap 1:** Analisis Kebutuhan Sistem & Penyusunan Dokumen PRD/SRS (Standar IEEE 830 / ISO 29148).
2. **Tahap 2:** Perancangan Arsitektur & Desain Berorientasi Objek SDD (4 Diagram UML Inti, ERD Basis Data 8 Tabel, Desain UI/UX).
3. **Tahap 3:** Konstruksi Kode (*Clean Code & SOLID*), Integrasi Dual-Engine AI (NVIDIA NIM & Google Gemini), Pengujian Otomatis (*Unit Testing* 24/24 PASS *Green Build*), serta Implementasi Pipeline CI/CD GitHub Actions.
4. **Tahap 4 & 5:** Integrasi Dokumen Akademik dan Perancangan Naskah Video Presentasi & Demonstrasi Sistem.

Penulis menyampaikan ucapan terima kasih yang sebesar-besarnya kepada Bapak **Yudhistira Arie Wijaya, M.Kom., CDS.** selaku Dosen Pengampu mata kuliah Rekayasa Perangkat Lunak yang senantiasa membimbing, mengarahkan, dan memberikan standar industri tinggi terhadap luaran proyek ini. Penulis menyadari bahwa sistem ini masih dapat terus disempurnakan. Oleh karena itu, masukan, kritik, dan saran konstruktif sangat diharapkan guna penyempurnaan di masa mendatang.

Cirebon, November 2026  
**Penulis (Mahasiswa Peneliti)**

---

## DAFTAR ISI

- [KATA PENGANTAR](#kata-pengantar)
- [DAFTAR ISI](#daftar-isi)
- [BAB I: PENDAHULUAN](#bab-i-pendahuluan)
  - [1.1 Latar Belakang Masalah](#11-latar-belakang-masalah)
  - [1.2 Identifikasi dan Rumusan Masalah](#12-identifikasi-dan-rumusan-masalah)
  - [1.3 Tujuan Pengembangan Sistem](#13-tujuan-pengembangan-sistem)
  - [1.4 Batasan dan Ruang Lingkup Sistem](#14-batasan-dan-ruang-lingkup-sistem)
- [BAB II: SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK (PRD & SRS)](#bab-ii-spesifikasi-kebutuhan-perangkat-lunak-prd--srs)
  - [2.1 Profil Pengguna dan Personas](#21-profil-pengguna-dan-personas)
  - [2.2 User Stories & Acceptance Criteria (Format Gherkin)](#22-user-stories--acceptance-criteria-format-gherkin)
  - [2.3 Spesifikasi Kebutuhan Fungsional (Functional Requirements)](#23-spesifikasi-kebutuhan-fungsional-functional-requirements)
  - [2.4 Spesifikasi Kebutuhan Non-Fungsional (Non-Functional Requirements)](#24-spesifikasi-kebutuhan-non-fungsional-non-functional-requirements)
  - [2.5 Aturan Bisnis Inti (Business Rules)](#25-aturan-bisnis-inti-business-rules)
- [BAB III: PERANCANGAN SISTEM DAN ARSITEKTUR (SDD & UML)](#bab-iii-perancangan-sistem-dan-arsitektur-sdd--uml)
  - [3.1 Arsitektur Tingkat Tinggi Perangkat Lunak](#31-arsitektur-tingkat-tinggi-perangkat-lunak)
  - [3.2 Pemodelan 4 Diagram UML Inti](#32-pemodelan-4-diagram-uml-inti)
    - [3.2.1 Use Case Diagram dan Skenario](#321-use-case-diagram-dan-skenario)
    - [3.2.2 Activity Diagram Bisnis & Alur AI](#322-activity-diagram-bisnis--alur-ai)
    - [3.2.3 Class Diagram Berorientasi Objek & SOLID](#323-class-diagram-berorientasi-objek--solid)
    - [3.2.4 Sequence Diagram Multi-Tier (Frontend-Backend-Database-AI)](#324-sequence-diagram-multi-tier-frontend-backend-database-ai)
  - [3.3 Desain Basis Data Relasional (ERD)](#33-desain-basis-data-relasional-erd)
  - [3.4 Desain Antarmuka Pengguna (UI/UX) dan Aksesibilitas](#34-desain-antarmuka-pengguna-uiux-dan-aksesibilitas)
- [BAB IV: IMPLEMENTASI, INTEGRASI AI, TESTING, DAN CI/CD](#bab-iv-implementasi-integrasi-ai-testing-dan-cicd)
  - [4.1 Implementasi Kode Bersih (*Clean Code* & SOLID)](#41-implementasi-kode-bersih-clean-code--solid)
  - [4.2 Integrasi Mesin Kecerdasan Buatan Ganda (Dual-Engine AI)](#42-integrasi-mesin-kecerdasan-buatan-ganda-dual-engine-ai)
  - [4.3 Hasil Pengujian Otomatis (*Automated Unit Testing*)](#43-hasil-pengujian-otomatis-automated-unit-testing)
  - [4.4 Otomasi Deployment dan Pengujian Berkelanjutan (CI/CD Pipeline)](#44-otomasi-deployment-dan-pengujian-berkelanjutan-cicd-pipeline)
- [BAB V: KESIMPULAN DAN REKOMENDASI PENGEMBANGAN](#bab-v-kesimpulan-dan-rekomendasi-pengembangan)
  - [5.1 Kesimpulan Capaian Proyek](#51-kesimpulan-capaian-proyek)
  - [5.2 Keterbatasan Sistem](#52-keterbatasan-sistem)
  - [5.3 Rencana Pengembangan Lanjutan](#53-rencana-pengembangan-lanjutan)
- [DAFTAR PUSTAKA](#daftar-pustaka)
- [LAMPIRAN](#lampiran)

---

## BAB I: PENDAHULUAN

### 1.1 Latar Belakang Masalah
Pondok Pesantren merupakan institusi pendidikan Islam berasrama yang menampung ratusan hingga ribuan santri dalam satu ekosistem komunal yang padat. Tingginya interaksi fisik harian di asrama, ruang kelas, dan aula makan menuntut pengawasan sanitasi serta kesehatan yang ketat. Penyakit menular seperti infeksi saluran pernapasan atas (ISPA), demam berdarah, skabies (*gudikan*), diare, dan konjungtivitis memiliki risiko transmisi yang sangat cepat di lingkungan pesantren.

Meskipun sebagian besar pesantren telah memiliki unit Pos Kesehatan Pesantren (Poskestren), operasional hariannya mayoritas masih bertumpu pada pencatatan manual berbasis buku register fisik kertas. Kondisi ini memicu sejumlah persoalan genting:
1. **Risiko Kehilangan dan Kerusakan Data Rekam Medis:** Buku fisik rentan robek, basah, dan hilang, sehingga riwayat alergi obat dan penyakit kronis santri tidak terdokumentasi secara berkelanjutan.
2. **Keterlambatan Penanganan Santri Sakit:** Ketika santri merasa sakit di asrama, mereka harus mencari pengurus kamar atau wali santri secara manual untuk melapor, memicu penumpukan antrean tanpa triase prioritas.
3. **Keterisolasian Informasi bagi Wali Santri:** Orang tua/wali santri sering kali tidak menerima informasi real-time mengenai kondisi kesehatan putra-putrinya, menimbulkan kecemasan dan miskomunikasi.
4. **Beban Kerja Tenaga Medis:** Tenaga medis Poskestren yang terbatas harus mencatat keluhan berulang dan merumuskan diagnosa awal tanpa asisten komputasi yang cerdas.

Berangkat dari urgensi tersebut, proyek **YukSehat** dirancang dan dibangun sebagai Sistem Informasi Manajemen Poskestren Terpadu yang mengintegrasikan teknologi web responsif modern, *Dual-Engine Artificial Intelligence* (Generative AI untuk triase dan asisten kesehatan), serta *Automated WhatsApp Gateway* untuk menjembatani komunikasi instan kepada wali santri.

### 1.2 Identifikasi dan Rumusan Masalah
Berdasarkan latar belakang di atas, rumusan masalah proyek ini adalah:
1. Bagaimana merancang arsitektur sistem informasi kesehatan pesantren yang aman, modular, dan memisahkan hak akses multi-role (Santri, Pengurus/Nakes, Admin)?
2. Bagaimana mengintegrasikan kecerdasan buatan (*Large Language Models*) untuk membantu tenaga medis melakukan triase keluhan dan memberikan draf saran medis secara cepat dan presisi?
3. Bagaimana mengotomatisasi penyebaran notifikasi status kesehatan santri langsung ke nomor WhatsApp orang tua/wali santri secara real-time?
4. Bagaimana memastikan keandalan, keamanan (*data privacy & sanitization*), serta kualitas perangkat lunak melalui pengujian otomatis (*Unit Testing*) dan *Continuous Integration* (CI/CD)?

### 1.3 Tujuan Pengembangan Sistem
Tujuan yang dicapai melalui proyek ini meliputi:
- **Tujuan Teknis:** Membangun aplikasi web full-stack menggunakan arsitektur MVC (Model-View-Controller) berbasis PHP Native 8.2 dan MySQL 8.0, terintegrasi microservice Node.js untuk AI Assistant.
- **Tujuan Klinis & Operasional:** Mengotomatiskan alur pencatatan keluhan, antrean Poskestren, pemeriksaan vital sign, rekam medis terpadu, dan rekapitulasi pelaporan berkala.
- **Tujuan Komunikasi:** Menyediakan jembatan informasi transparan kepada wali santri melalui integrasi webhook/API Fonnte WhatsApp Gateway.
- **Tujuan Akademik:** Memenuhi standar kompetensi Outcome-Based Education (OBE) pada mata kuliah Rekayasa Perangkat Lunak STMIK IKMI Cirebon dengan predikat istimewa.

### 1.4 Batasan dan Ruang Lingkup Sistem
Ruang lingkup dan batasan operasional aplikasi YukSehat:
1. Sistem beroperasi pada lingkungan jaringan intranet pesantren maupun cloud publik terenkripsi (HTTPS).
2. Tiga level peran pengguna (*Role-Based Access Control*):
   - **Santri:** Input keluhan mandiri, melihat riwayat berobat, konsultasi edukasi dengan Chatbot AI.
   - **Pengurus / Tenaga Medis:** Manajemen antrean, pemeriksaan tanda vital & diagnosis, utilisasi AI Clinical Assistant, update status rujukan.
   - **Administrator:** Tata kelola pengguna/santri, manajemen master data kamar & kelas, audit log WhatsApp, dan orkestrasi API Model AI.
3. Fitur AI berfungsi sebagai *Decision Support System* (alat bantu draf klinis dan edukasi santri), bukan pengganti legitimasi diagnosis dokter resmi (*human-in-the-loop validation*).

---

## BAB II: SPESIFIKASI KEBUTUHAN PERANGKAT LUNAK (PRD & SRS)

### 2.1 Profil Pengguna dan Personas

| Profil Persona | Deskripsi & Karakteristik | Kebutuhan Utama (*User Needs*) |
| :--- | :--- | :--- |
| **Ahmad (16 Th) — Persona Santri** | Santri mukim kelas XI yang aktif berkegiatan, memiliki keterbatasan akses smartphone (hanya di lab/kiosk pesantren). | Akses cepat pelaporan sakit tanpa birokrasi rumit; dapat berkonsultasi privasi dengan bot kesehatan mengenai keluhan awal. |
| **dr. Siti / Ustadz Wildan — Persona Tenaga Medis & Pengurus** | Petugas Poskestren yang menangani pemeriksaan harian 20–50 santri, mencatat tensi, suhu, dan obat. | Draf analisis klinis cepat berbasis AI untuk mempercepat input diagnosa; dashboard antrean terurut berdasarkan kegawatan. |
| **Haji Rahmat — Persona Wali Santri** | Orang tua santri yang tinggal di luar kota, membutuhkan kabar pasti mengenai kesehatan anaknya. | Notifikasi pesan WhatsApp resmi dari sistem Poskestren saat anaknya diperiksa atau butuh rujukan RS. |
| **Fahmi, S.Kom. — Persona Administrator** | Pengelola TI pesantren yang bertanggung jawab atas ketersediaan server dan keamanan data medis. | Konfigurasi fleksibel kuota API AI, pemantauan log WhatsApp, manajemen akun terpusat, dan backup database. |

### 2.2 User Stories & Acceptance Criteria (Format Gherkin)

#### US-01: Pelaporan Keluhan Santri
- **As a** Santri Mukim,
- **I want to** melaporkan gejala demam dan flu secara online melalui portal YukSehat,
- **So that** pengurus Poskestren segera mengetahui kondisi saya dan memanggil saya untuk diperiksa.
```gherkin
Scenario: Santri berhasil mengirim laporan keluhan kesehatan
  Given Santri telah login ke portal YukSehat
  And Santri berada pada halaman form "Lapor Keluhan"
  When Santri mengisi keluhan "Demam tinggi sejak semalam dan pusing berputar"
  And Santri menekan tombol "Kirim Keluhan"
  Then Sistem memvalidasi input keluhan tidak boleh kosong
  And Sistem menyimpan keluhan dengan status "Menunggu Pemeriksaan"
  And Sistem menambahkan data ke antrean Poskestren
  And Sistem menampilkan notifikasi sukses berbasis SweetAlert2
```

#### US-02: Asistensi Klinis AI untuk Pemeriksaan Medis
- **As a** Petugas Medis Poskestren,
- **I want to** mengklik tombol "Analisis Keluhan dengan AI" saat memeriksa tanda vital santri,
- **So that** saya memperoleh rekomendasi diferensial diagnosa, tindakan darurat, dan anjuran obat secara instan.
```gherkin
Scenario: Analisis klinis berbasis AI menghasilkan draf medis
  Given Petugas Medis membuka form input pemeriksaan untuk santri terpilih
  And Field keluhan telah terisi "Mata perih, merah, dan berair setelah begadang"
  When Petugas menekan tombol "Analisis Keluhan dengan AI"
  Then Sistem mengirim request terenkripsi ke engine AI aktif (NVIDIA NIM / Gemini)
  And AI mengembalikan respon terstruktur (Kemungkinan Diagnosa, Tindakan, Catatan)
  And Petugas dapat mengklik "Terapkan ke Form" untuk menyalin draf ke form input
  And Petugas tetap berwenang mengubah isi teks sebelum menyimpan rekam medis
```

### 2.3 Spesifikasi Kebutuhan Fungsional (Functional Requirements)

| Kode ID | Modul / Fitur | Deskripsi Kebutuhan Fungsional | Kriteria Penerimaan (*Acceptance Criteria*) |
| :--- | :--- | :--- | :--- |
| **FR-AUTH-001** | Autentikasi | Sistem menyediakan antarmuka login terenkripsi menggunakan password hashing `PASSWORD_BCRYPT`. | Login sukses me-regenerasi session id; login gagal menampilkan pesan error tanpa mengekspos eksistensi username. |
| **FR-AUTH-002** | Otorisasi Multi-Role | Sistem membatasi menu dan endpoint berdasarkan role: `admin`, `petugas`, dan `santri`. | Pengguna yang mencoba mengakses controller di luar hak aksesnya dialihkan dengan flash message error. |
| **FR-SAN-001** | Master Data Santri | Sistem memfasilitasi penambahan, pembaruan, dan pencarian santri (NIS, Nama, Kamar, Kelas, No. HP Wali). | NIS bersifat unik; nomor HP wali dinormalisasi otomatis ke format internasional `628xxxxxxxxxx`. |
| **FR-KEL-001** | Manajemen Keluhan | Santri dapat mengajukan keluhan dan melihat riwayat status penanganannya secara realtime. | Keluhan baru otomatis berstatus `Menunggu Pemeriksaan` dan tercatat pada tabel `keluhan`. |
| **FR-ANT-001** | Antrean Poskestren | Pengurus dapat memantau antrean, memfilter berdasarkan status, dan memanggil santri. | Badge jumlah antrean terupdate otomatis; aksi ganti status terekam dengan timestamp akurat. |
| **FR-PEM-001** | Rekam Pemeriksaan | Tenaga medis dapat menginput tanda vital (suhu, tensi, berat badan) dan diagnosa medis. | Data tersimpan pada tabel `pemeriksaan` dan merelasikan `keluhan_id` ke status `Selesai Ditangani`. |
| **FR-AI-001** | Chatbot Asisten Santri | Santri dapat berkonsultasi edukasi kesehatan interaktif melalui widget chatbot mengambang. | Bot merespon santun, menolak peresepan obat keras bebas, dan menyarankan periksa langsung jika kondisi darurat. |
| **FR-AI-002** | AI Clinical Assistant | Petugas Poskestren dapat menghasilkan draf diagnosa medis otomatis dari data keluhan dan tanda vital. | Draf terbagi menjadi temuan fisik, tindakan observasi, dan anjuran istirahat; dapat disalin ke form dalam 1-klik. |
| **FR-AI-003** | Switcher Model AI | Administrator dapat memilih engine aktif antara NVIDIA NIM (Nemotron) dan Google Gemini AI. | Pengujian koneksi API key langsung memunculkan respon milidetik (latency check) dan status aktif. |
| **FR-WA-001** | WhatsApp Gateway | Sistem mengirim notifikasi otomatis ke nomor WhatsApp wali santri pasca pemeriksaan selesai. | Notifikasi berisi nama santri, hasil diagnosa, saran istirahat, dan nama nakes pemeriksa. |
| **FR-LAP-001** | Rekapitulasi Laporan | Sistem menyajikan grafik tren penyakit terbanyak, distribusi santri sakit per asrama, serta ekspor data. | Grafik interaktif (Chart.js), laporan dapat difilter per rentang tanggal dan diekspor ke PDF/Excel. |

### 2.4 Spesifikasi Kebutuhan Non-Fungsional (Non-Functional Requirements)

| Kode ID | Kategori Parameter | Nilai Target & Spesifikasi Teknis |
| :--- | :--- | :--- |
| **NFR-PERF-001** | Kecepatan Respon Web | Waktu muat halaman statis & dashboard `<= 1.5 detik` pada koneksi broadband lokal. |
| **NFR-PERF-002** | Kecepatan Respon AI | Respon inferensi AI Clinical Assistant `<= 5.0 detik` melalui NVIDIA NIM Bolt / Gemini Flash Lite. |
| **NFR-SEC-001** | Proteksi CSRF | Seluruh form HTTP POST dilindungi oleh token unik `csrf_token` tersimpan di session. |
| **NFR-SEC-002** | Keamanan Injeksi SQL | Seluruh query ke database MySQL wajib menggunakan PDO Prepared Statements tanpa exception. |
| **NFR-SEC-003** | Sanitasi Input (XSS) | Seluruh output data pengguna pada tampilan View disaring melalui fungsi `htmlspecialchars()`. |
| **NFR-AVAIL-001** | Ketersediaan Sistem | Sistem dirancang memiliki uptime minimum `99.5%` pada jam operasional Poskestren (06:00 - 22:00). |
| **NFR-REL-001** | Graceful Degradation | Kegagalan layanan eksternal (AI down atau kuota WA habis) tidak boleh menghentikan proses simpan data medis. |

### 2.5 Aturan Bisnis Inti (Business Rules)
1. **BR-001 (Validasi Akses Data Rekam Medis):** Santri hanya diizinkan melihat data rekam medis miliknya sendiri berdasarkan relasi `santri_id` pada session aktif.
2. **BR-002 (Integritas Data Petugas Pemeriksa):** Pengurus yang login dapat menginput pemeriksaan; field petugas pemeriksa otomatis mengunci identitas pengurus yang sedang login guna mencegah pemalsuan identitas (*non-repudiation*).
3. **BR-003 (Human-in-the-Loop AI Medical Ethics):** Saran dari AI berstatus draf mentah dan wajib ditinjau serta disetujui secara manual oleh tenaga medis berwenang sebelum difinalisasi ke rekam medis resmi.
4. **BR-004 (Format Baku WhatsApp Gateway):** Nomor telepon tujuan wajib diawali kode negara `62` tanpa tanda hubung, spasi, atau karakter non-numerik.

---

## BAB III: PERANCANGAN SISTEM DAN ARSITEKTUR (SDD & UML)

### 3.1 Arsitektur Tingkat Tinggi Perangkat Lunak
Sistem Informasi YukSehat dibangun menggunakan pola arsitektur **3-Tier Model-View-Controller (MVC)** yang terpisah secara tegas antara lapis presentasi, lapis logika bisnis, dan lapis persistensi data:

```
+--------------------------------------------------------------------------+
|                       PRESENTATION LAYER (CLIENT)                        |
|  Bootstrap 5.3 + Custom CSS (YukSehat Theme) + SweetAlert2 + Chart.js    |
+------------------------------------+-------------------------------------+
                                     | (HTTPS Requests / JSON API)
+------------------------------------v-------------------------------------+
|                     APPLICATION & BUSINESS LOGIC LAYER                   |
|  - Router & Front Controller (index.php)                                 |
|  - Middleware: CSRF Token Validator, Role Permission Check               |
|  - MVC Controllers (KeluhanController, PemeriksaanController, dll.)      |
|  - Services: AiService (Dual-Engine), FonnteService (WA Gateway)         |
|  - Microservice AI Assistant: Node.js Express Runtime (Port 3005)        |
+------------------------------------+-------------------------------------+
                                     | (PDO MySQL Driver)
+------------------------------------v-------------------------------------+
|                          DATA PERSISTENCE LAYER                          |
|  MySQL 8.0 Relational Database (InnoDB Engine, utf8mb4, Foreign Keys)    |
+--------------------------------------------------------------------------+
```

### 3.2 Pemodelan 4 Diagram UML Inti

#### 3.2.1 Use Case Diagram dan Skenario
Diagram Use Case memetakan interaksi fungsional antara tiga aktor sistem (Santri, Petugas Poskestren, dan Administrator):

```mermaid
usecaseDiagram
    actor "Santri" as U_Santri
    actor "Petugas Medis" as U_Nakes
    actor "Administrator" as U_Admin

    package "Sistem Informasi YukSehat" {
        usecase "Autentikasi Login & Logout" as UC_Auth
        usecase "Kelola Profil & Ganti Password" as UC_Profil
        usecase "Input Keluhan Kesehatan" as UC_InputKeluhan
        usecase "Pantau Status Keluhan & Antrean" as UC_PantauAntrean
        usecase "Konsultasi Edukasi Chatbot AI" as UC_Chatbot
        usecase "Kelola Antrean Pemeriksaan" as UC_KelolaAntrean
        usecase "Input Rekam Medis & Tanda Vital" as UC_InputPeriksa
        usecase "Gunakan Analisis Klinis AI" as UC_AIKlinis
        usecase "Kirim Notifikasi WA ke Wali Santri" as UC_NotifWA
        usecase "Kelola Master Data Santri & Kamar" as UC_MasterSantri
        usecase "Konfigurasi Model AI & API Keys" as UC_ConfigAI
        usecase "Monitoring Gateway WhatsApp" as UC_MonitorWA
        usecase "Lihat Dashboard & Cetak Rekap Laporan" as UC_Laporan
    }

    U_Santri --> UC_Auth
    U_Santri --> UC_Profil
    U_Santri --> UC_InputKeluhan
    U_Santri --> UC_PantauAntrean
    U_Santri --> UC_Chatbot

    U_Nakes --> UC_Auth
    U_Nakes --> UC_Profil
    U_Nakes --> UC_KelolaAntrean
    U_Nakes --> UC_InputPeriksa
    U_Nakes --> UC_Laporan
    UC_InputPeriksa ..> UC_AIKlinis : <<include>>
    UC_InputPeriksa ..> UC_NotifWA : <<extend>>

    U_Admin --> UC_Auth
    U_Admin --> UC_MasterSantri
    U_Admin --> UC_ConfigAI
    U_Admin --> UC_MonitorWA
    U_Admin --> UC_Laporan
```

#### 3.2.2 Activity Diagram Bisnis & Alur AI
Diagram aktivitas berikut memodelkan alur bisnis end-to-end mulai dari pelaporan keluhan oleh santri, proses triase AI, pemeriksaan fisik, hingga pengiriman notifikasi WhatsApp:

```mermaid
flowchart TD
    startNode([Mulai Alur]) --> A1[Santri Login ke Portal YukSehat]
    A1 --> A2[Santri Mengisi Form Keluhan Gejala Sakit]
    A2 --> A3[Sistem Menyimpan Keluhan: Status Menunggu]
    A3 --> B1[Petugas Poskestren Membuka Antrean Masuk]
    B1 --> B2[Petugas Memanggil Santri & Mengukur Tanda Vital]
    B2 --> B3{Gunakan AI Assistant?}
    
    B3 -- Ya --> C1[Klik Tombol 'Analisis Keluhan dengan AI']
    C1 --> C2[Backend Memanggil AiService: Model NVIDIA/Gemini]
    C2 --> C3[Engine AI Melakukan Sintesis Draf Medis]
    C3 --> C4[Respon AI Ditampilkan pada Modal/Box Klinis]
    C4 --> C5[Petugas Meninjau, Mengedit, & Mengadopsi Draf]
    C5 --> B4[Finalisasi Input Diagnosa & Obat]

    B3 -- Tidak --> B4
    
    B4 --> D1[Petugas Menyimpan Data Rekam Pemeriksaan]
    D1 --> D2[Sistem Memperbarui Status Keluhan: Selesai]
    D2 --> E1{Kirim Notifikasi WA?}
    
    E1 -- Ya --> E2[FonnteService Menormalisasi Nomor HP Wali]
    E2 --> E3[Sistem Mengirim Pesan WhatsApp Ringkasan Medis]
    E3 --> E4[Wali Santri Menerima Pesan Realtime]
    E4 --> endNode([Selesai])
    
    E1 -- Tidak --> endNode
```

#### 3.2.3 Class Diagram Berorientasi Objek & SOLID
Class diagram mencerminkan kepatuhan terhadap prinsip *Single Responsibility* dan *Dependency Inversion*:

```mermaid
classDiagram
    class Database {
        -PDO $pdo
        +getInstance() PDO
        +disconnect() void
    }

    class BaseModel {
        #PDO $db
        +findAll() array
        +findById(int $id) array
    }

    class KeluhanModel {
        +create(array $data) int
        +updateStatus(int $id, string $status) bool
        +getAntreanAktif() array
        +getBySantriId(int $santriId) array
    }

    class PemeriksaanModel {
        +create(array $data) int
        +getHistoryBySantri(int $santriId) array
        +getStatistikBulanan() array
    }

    class AiService {
        -string $provider
        -string $apiKey
        +analyzeSymptom(string $symptom, array $vitalSign) array
        +chatConsultation(string $message, array $history) string
        +testConnection(string $provider) array
    }

    class FonnteService {
        -string $token
        -string $apiUrl
        +send(string $targetPhone, string $message) array
        +normalizePhone(string $phone) string
    }

    BaseModel <|-- KeluhanModel
    BaseModel <|-- PemeriksaanModel
    BaseModel --> Database : uses
    PemeriksaanController --> KeluhanModel : calls
    PemeriksaanController --> PemeriksaanModel : calls
    PemeriksaanController --> AiService : delegates AI
    PemeriksaanController --> FonnteService : delegates WA
```

#### 3.2.4 Sequence Diagram Multi-Tier (Frontend-Backend-Database-AI)
Alur interaksi berurutan saat pemeriksaan medis berlangsung:

```mermaid
sequenceDiagram
    autonumber
    actor Nakes as Petugas Poskestren
    participant View as Web UI (Pemeriksaan)
    participant Ctrl as PemeriksaanController
    participant AI as AiService (NVIDIA/Gemini)
    participant Model as PemeriksaanModel
    participant DB as MySQL Database
    participant WA as FonnteService (WhatsApp API)

    Nakes->>View: Buka Form & Input Vital Sign (Tensi/Suhu)
    Nakes->>View: Klik 'Analisis Keluhan dengan AI'
    View->>Ctrl: POST /pemeriksaan/analisis-ai (CSRF Token, Keluhan, Vital)
    Ctrl->>AI: analyzeSymptom(keluhan, vitalSign)
    AI-->>Ctrl: Draf Analisis (Diagnosa Awal, Saran Tindakan)
    Ctrl-->>View: JSON Response (Status 200 OK)
    View-->>Nakes: Tampilkan Draf di Box Rekomendasi
    Nakes->>View: Tinjau, Edit, & Klik 'Simpan Pemeriksaan'
    View->>Ctrl: POST /pemeriksaan/simpan (Form Data Medis)
    Ctrl->>Model: savePemeriksaan(data)
    Model->>DB: INSERT INTO pemeriksaan VALUES (...)
    DB-->>Model: Success (ID Generated)
    Ctrl->>WA: sendNotification(hpWali, pesanMedis)
    WA-->>Ctrl: WA Dispatched (HTTP 200)
    Ctrl-->>View: Redirect with Flash Success (SweetAlert2)
    View-->>Nakes: Dialog Sukses Pemeriksaan Tersimpan
```

### 3.3 Desain Basis Data Relasional (ERD)
Struktur basis data YukSehat terdiri dari 8 entitas tabel relasional ternormalisasi:

```mermaid
erDiagram
    USERS ||--o{ SANTRI : "dimiliki_oleh"
    KAMAR ||--o{ SANTRI : "dihuni_oleh"
    KELAS ||--o{ SANTRI : "diikuti_oleh"
    SANTRI ||--o{ KELUHAN : "mengajukan"
    USERS ||--o{ KELUHAN : "ditangani_oleh"
    KELUHAN ||--o| PEMERIKSAAN : "menghasilkan"
    USERS ||--o{ PEMERIKSAAN : "diperiksa_oleh"
    PEMERIKSAAN ||--o{ RIWAYAT_OBAT : "meresepkan"
    USERS ||--o{ CHATBOT_LOG : "berinteraksi"

    USERS {
        int id PK
        string username
        string password
        enum role "admin, petugas, santri"
        timestamp created_at
    }

    SANTRI {
        int id PK
        int user_id FK
        string nis UK
        string nama
        enum jenis_kelamin "L, P"
        int kamar_id FK
        int kelas_id FK
        string no_hp_wali
        text riwayat_alergi
    }

    KELUHAN {
        int id PK
        int santri_id FK
        text keluhan
        datetime tgl_keluhan
        enum status "menunggu, diperiksa, selesai"
        int ditangani_oleh FK
    }

    PEMERIKSAAN {
        int id PK
        int keluhan_id FK
        int santri_id FK
        int petugas_id FK
        datetime tgl_periksa
        decimal suhu_tubuh
        string tekanan_darah
        decimal berat_badan
        decimal tinggi_badan
        text hasil_pemeriksaan
        text tindakan
        text resep_obat
        text catatan
        boolean notif_wa_sent
    }
```

### 3.4 Desain Antarmuka Pengguna (UI/UX) dan Aksesibilitas
Antarmuka sistem mengusung estetika visual modern dengan tema **Islamic Emerald & White Minimalist**:
- **Warna Primer:** Hijau Poskestren (`#0f766e` / `#059669`) melambangkan kesehatan, kesegaran, dan tradisi pesantren yang teduh.
- **Warna Aksen:** Kuning Keemasan (`#d97706`) untuk penanda badge antrean prioritas.
- **Tipografi:** Plus Jakarta Sans & Inter font family yang memiliki keterbacaan tinggi di berbagai resolusi layar (*fluid typography*).
- **Aksesibilitas & Feedback Visual:** Menggunakan library SweetAlert2 untuk dialog konfirmasi destruktif dan notifikasi toast mengambang non-intrusif, menghilangkan pop-up default peramban (*browser native alert*) yang kaku.

---

## BAB IV: IMPLEMENTASI, INTEGRASI AI, TESTING, DAN CI/CD

### 4.1 Implementasi Kode Bersih (*Clean Code* & SOLID)
Arsitektur kode YukSehat mengimplementasikan prinsip-prinsip rekayasa perangkat lunak modern:
1. **Single Responsibility Principle (SRP):** Setiap kelas controller hanya bertanggung jawab mengelola alur HTTP, sedangkan pemrosesan database didelegasikan ke Model, dan komunikasi pihak ketiga diisolasi ke dalam folder `app/Services/` (`AiService.php`, `FonnteService.php`).
2. **Open/Closed Principle (OCP):** Arsitektur AI Engine dirancang berbasis adaptor provider. Penambahan model AI baru (seperti Claude atau Mistral) dapat dilakukan dengan menambah konfigurasi pada registry tanpa membongkar core controller.
3. **Robust Sanitization Helper:** Penerapan kelas bantuan terpusat seperti `AuthHelper::hasRole()`, `MedicalFormatHelper::formatBloodPressure()`, dan `CSRFToken::validate()` untuk meminimalisasi duplikasi kode (*Don't Repeat Yourself* - DRY).

### 4.2 Integrasi Mesin Kecerdasan Buatan Ganda (Dual-Engine AI)
YukSehat mempelopori implementasi arsitektur AI Ganda (*Dual-Engine Architecture*) yang dapat diorkestrasi secara independen oleh Administrator melalui GUI:
1. **Engine 1: NVIDIA NIM (Nemotron 3.5 Bolt)**
   - *Karakteristik:* Latensi inferensi ultra-rendah (< 2.5 detik), kemampuan penalaran logika klinis tinggi, dan format draf rekam medis yang sangat terstruktur.
   - *Penggunaan:* Direkomendasikan sebagai mesin utama untuk **AI Clinical Assistant** di ruang pemeriksaan.
2. **Engine 2: Google Gemini AI (Gemini 2.5 Flash Lite / 3.8 Flash)**
   - *Karakteristik:* Pemahaman bahasa alami bahasa Indonesia yang santun, luwes, dan ramah untuk santri usia remaja.
   - *Penggunaan:* Dikonfigurasi optimal untuk **Chatbot Edukasi Asisten Santri** berbasis Node.js microservice.

### 4.3 Hasil Pengujian Otomatis (*Automated Unit Testing*)
Sebagai bukti kepatuhan terhadap rubrik pengujian perangkat lunak mandiri, sistem dilengkapi dengan test runner otomatis berbasis PHP CLI (`tests/run_tests.php`). Pengujian mencakup 24 assertion kasus uji komprehensif:

```
========================================================================
 YUKSEHAT AUTOMATED UNIT TEST SUITE (PHP CLI)
 Environment: PHP 8.2.12 (CLI) | OS: WINNT
========================================================================
 [PASS] - TC-AUTH-001: AuthHelper::isLoggedIn returns false when no session
 [PASS] - TC-AUTH-002: AuthHelper::user returns null when unauthenticated
 [PASS] - TC-AUTH-003: AuthHelper::hasRole allows matching role
 [PASS] - TC-AUTH-004: AuthHelper::hasRole denies non-matching role
 [PASS] - TC-AUTH-005: AuthHelper::hasRole allows admin full-access
 [PASS] - TC-MED-001: Blood pressure format 120/80 is valid
 [PASS] - TC-MED-002: Blood pressure format 140/90 is valid
 [PASS] - TC-MED-003: Blood pressure format invalid rejected
 [PASS] - TC-MED-004: Body temperature 36.5 is normal
 [PASS] - TC-MED-005: Body temperature 39.0 is fever
 [PASS] - TC-MED-006: Body temperature 34.0 is hypothermia
 [PASS] - TC-MED-007: Truncate word limits characters safely
 [PASS] - TC-FONNTE-001: Phone 08123456789 converted to 628123456789
 [PASS] - TC-FONNTE-002: Phone with dashes converted correctly
 [PASS] - TC-FONNTE-003: Phone with leading plus converted correctly
 [PASS] - TC-FONNTE-004: Empty phone returns empty string
 [PASS] - TC-FONNTE-005: Mock mode returns simulated success without network
 [PASS] - TC-AI-001: AiService contains valid provider catalogue
 [PASS] - TC-AI-002: Prompt contains required clinical markers
 [PASS] - TC-CSRF-001: CSRF token generation returns 64-char hex string
 [PASS] - TC-CSRF-002: CSRF validation succeeds with correct token
 [PASS] - TC-CSRF-003: CSRF validation fails with invalid token
 [PASS] - TC-SYNTAX-001: All 27 core PHP files passed linting without syntax errors
 [PASS] - TC-ENV-001: Environment config contains mandatory DB & Service keys

========================================================================
 TEST RESULTS SUMMARY:
 Total Tests: 24 | Passed: 24 | Failed: 0 | Execution Time: 0.84s
 BUILD STATUS: GREEN / PASS (100% SUCCESS RATE)
========================================================================
```

### 4.4 Otomasi Deployment dan Pengujian Berkelanjutan (CI/CD Pipeline)
Untuk memastikan setiap commit kode baru memenuhi standar kualitas, repositori dikonfigurasikan dengan alur kerja **GitHub Actions** (`.github/workflows/ci.yml`):
- **Triggers:** Setiap event `push` dan `pull_request` pada branch `main` dan `master`.
- **Matrix OS:** Ubuntu Latest dengan PHP versi 8.2.
- **Pipeline Steps:**
  1. *Checkout Source Code* dari repository.
  2. *Setup PHP Environment* lengkap dengan ekstensi `pdo`, `pdo_mysql`, `mbstring`, dan `curl`.
  3. *PHP Syntax Linting Check* pada seluruh direktori `app/`, `config/`, dan `public/`.
  4. *Automated Unit Testing Execution* menjalankan `php tests/run_tests.php`.
  5. *Status Badge Deployment* memancarkan status build hijau (*Passing*) pada file README repositori.

---

## BAB V: KESIMPULAN DAN REKOMENDASI PENGEMBANGAN

### 5.1 Kesimpulan Capaian Proyek
Berdasarkan seluruh tahapan perancangan, implementasi, dan pengujian yang telah diselesaikan, dapat disimpulkan bahwa:
1. Sistem Informasi YukSehat berhasil dibangun secara penuh (*fully-functional*) sesuai standar kurikulum Outcome-Based Education (OBE) STMIK IKMI Cirebon.
2. Arsitektur MVC Native PHP 8.2 terbukti tangguh, terstruktur, dan memiliki performa tinggi tanpa overhead framework berlebih.
3. Inovasi **Dual-Engine AI** berhasil diintegrasikan dengan mulus, di mana NVIDIA NIM Nemotron mempercepat perumusan draf medis klinis nakes, dan Google Gemini melayani edukasi santri interaktif.
4. Kualitas perangkat lunak terverifikasi secara objektif melalui 24 pengujian unit terotomatisasi (*100% pass*) dan terintegrasi pipeline CI/CD GitHub Actions.

### 5.2 Keterbatasan Sistem
1. Koneksi layanan AI dan WhatsApp Gateway membutuhkan stabilitas koneksi internet aktif. Jika koneksi terputus, sistem akan beralih ke mode offline fallback.
2. Pengujian integrasi WhatsApp Gateway saat ini menggunakan mock simulator di lingkungan lokal untuk menghindari konsumsi saldo kuota pesan yang tidak perlu.

### 5.3 Rencana Pengembangan Lanjutan
1. **Pengembangan Mobile App (Flutter/React Native):** Menghadirkan aplikasi khusus wali santri dengan notifikasi push langsung ke smartphone.
2. **Integrasi Rekam Medis Standar SatuSehat Kemenkes:** Menyelaraskan format pertukaran data medis berbasis standar HL7 FHIR untuk integrasi dengan fasilitas kesehatan rujukan resmi.
3. **Modul Smart Medicine Inventory:** Menambahkan pelacakan stok obat Poskestren berbasis peringatan otomatis tanggal kadaluarsa (*expired date tracking*).

---

## DAFTAR PUSTAKA

1. Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill Education.
2. Sommerville, I. (2016). *Software Engineering* (10th ed.). Pearson Education.
3. Martin, R. C. (2017). *Clean Architecture: A Craftsman's Guide to Software Structure and Design*. Prentice Hall.
4. Fowler, M. (2018). *Refactoring: Improving the Design of Existing Code* (2nd ed.). Addison-Wesley Professional.
5. IEEE Computer Society. (1998). *IEEE Recommended Practice for Software Requirements Specifications (IEEE Std 830-1998)*. IEEE.
6. International Organization for Standardization. (2018). *Systems and software engineering — Life cycle processes — Requirements engineering (ISO/IEC/IEEE 29148:2018)*. ISO.
7. World Health Organization (WHO). (2021). *Ethics and governance of artificial intelligence for health: WHO guidance*. World Health Organization.

---

## LAMPIRAN

### Lampiran 1: Struktur Direktori Proyek Bersih
```
YukSehat/
|-- .github/workflows/ci.yml       <- Pipeline CI/CD GitHub Actions
|-- app/
|   |-- Controllers/               <- Auth, Keluhan, Pemeriksaan, Admin, Chatbot
|   |-- Models/                    <- User, Santri, Keluhan, Pemeriksaan, Kamar
|   |-- Services/                  <- AiService.php, FonnteService.php
|   |-- Views/                     <- Antarmuka Blade/PHP Responsif
|-- chatbot-server/                <- Microservice Node.js Express Port 3005
|-- config/                        <- Database & App Environment Variables
|-- database/                      <- Skema SQL Relasional DDL & DML
|-- tests/run_tests.php            <- Automated Unit Test Suite (24 Test Cases)
|-- tugas/                         <- Berkas Dokumen Luaran Proyek Akhir OBE
|   |-- 01_PRD_SRS_YukSehat.md
|   |-- 02_SDD_UML_Arsitektur_YukSehat.md
|   |-- 03_Konstruksi_Testing_DevOps_CI_CD.md
|   |-- 04_Laporan_Akhir_Terintegrasi.md
|   |-- 05_Panduan_Naskah_Video_Presentasi.md
|   |-- README_GitHub_Template.md
`-- index.php                      <- Front Controller & Routing Gateway
```
