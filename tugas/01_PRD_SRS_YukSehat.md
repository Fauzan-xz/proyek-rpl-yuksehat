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
