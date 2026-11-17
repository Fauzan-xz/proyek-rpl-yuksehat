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
