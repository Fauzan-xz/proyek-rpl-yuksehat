# SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
# SISTEM INFORMASI KESEHATAN SANTRI YUKSEHAT

- **Dokumen Identifikasi:** SRS-YUKSEHAT-2026-V1.0
- **Nama Sistem:** YukSehat
- **Versi Sistem:** 1.0.0
- **Tanggal Rilis:** 16 November 2026
- **Status Dokumen:** Baseline Rekayasa Perangkat Lunak Resmi
- **Standar Format:** IEEE Std 830-1998 / ISO/IEC/IEEE 29148:2018 (Disesuaikan untuk Standar Akademik & Industri)

---

## 1. Pendahuluan

### 1.1 Gambaran Umum
Sistem Informasi Kesehatan Santri **YukSehat** merupakan aplikasi berbasis web yang dikembangkan khusus untuk mengelola rekam medis fisik santri, memfasilitasi pelaporan keluhan kesehatan, mengelola antrean triase penanganan Posko Kesehatan Pesantren (Poskestren), menyediakan asisten klinis berbasis kecerdasan buatan (*AI Clinical Drafter*), melayani konsultasi interaktif melalui chatbot cerdas, serta menyediakan laporan rekapitulasi kesehatan multi-sheet terformat ke format Microsoft Excel (.xlsx).

### 1.2 Audiens Dokumen
Dokumen Spesifikasi Kebutuhan Perangkat Lunak (*Software Requirements Specification* - SRS) ini ditujukan kepada:
1. **Tim Pengembang Perangkat Lunak (Software Engineers)**: Sebagai pedoman arsitektur, batasan logika pemrograman, dan pengujian unit/integrasi.
2. **Quality Assurance & Security Auditors**: Sebagai acuan pengujian fungsionalitas (*Acceptance Testing*), verifikasi kontrol akses (RBAC), serta audit kepatuhan keamanan.
3. **Pimpinan Pondok Pesantren & Stakeholder Manajemen**: Sebagai dokumen acuan pemenuhan kebutuhan tata kelola operasional Poskestren.
4. **Instansi Akademik / Penilai Teknis**: Sebagai artefak rekayasa perangkat lunak formal yang membuktikan keandalan dan konsistensi rancang bangun sistem.

---

## 2. Tujuan Dokumen

Tujuan penyusunan dokumen SRS ini adalah:
1. Mendefinisikan secara menyeluruh kebutuhan fungsional (*Functional Requirements*) dan kebutuhan nonfungsional (*Non-Functional Requirements*) dari sistem YukSehat berdasarkan implementasi riil pada repositori kode sumber.
2. Menetapkan batasan desain antarmuka, spesifikasi struktur data, aturan bisnis (*business rules*), serta protokol penanganan kesalahan (*error handling*).
3. Menjamin keterlacakan (*traceability*) antara kebutuhan pengguna, dokumen PRD, kode pengontrol (*controller*), model data, dan antarmuka tampilan (*views*).

---

## 3. Ruang Lingkup Sistem

### Batasan Fungsional Sistem:
- **Autentikasi & Otorisasi**: Manajemen sesi terproteksi, autentikasi berbasis tiga pengenal (Username, NIS, atau Nama Petugas), registrasi mandiri santri, rotasi token anti-CSRF, dan pencegahan penguncian admin (*Anti-Lockout*).
- **Manajemen Santri**: Pengelolaan direktori santri (biodata lengkap, asrama, kamar, kelas, riwayat alergi, golongan darah, riwayat penyakit komorbid) beserta filter dan paginasi.
- **Manajemen Keluhan**: Pengajuan keluhan oleh santri dengan penunjukan petugas pemeriksa, antrean keluhan terfilter status, penugasan (*assignment*), dan pengalihan tugas antar-pengurus.
- **Pemeriksaan Kesehatan Fisik**: Pencatatan data vital (suhu, tensi, berat, tinggi), integrasi draf analisis AI, diagnosa, tindakan medis, resep obat, dan riwayat periksa.
- **Asisten AI Terpadu**:
  - Draf analisis klinis pendukung keputusan petugas kesehatan dengan etika non-diagnostik.
  - Chatbot asisten Poskestren (*dual-engine*: Google Gemini dan NVIDIA NIM) yang mendukung respons deterministik, penyaringan topik (*off-topic guard*), dan penarikan statistik basis data langsung untuk peran staf terautentikasi.
- **Pusat Notifikasi**: Notifikasi *in-app* real-time dengan modal peninjauan interaktif dan antrean pengiriman WhatsApp aman (*Safe-Mode Fonnte Gateway*).
- **Rekapitulasi & Ekspor**: Penyusunan laporan kesehatan terfilter dan unduhan berkas Microsoft Excel (.xlsx) dengan 2 lembar kerja terstruktur (*Summary* dan *Full Clinical Details*).
- **Konfigurasi Sistem**: Pemilihan model AI dinamis, pengujian latensi API via AJAX, pemantauan status gateway WhatsApp, dan pengelolaan akun pengguna.

---

## 4. Definisi dan Istilah

| Istilah / Akronim | Definisi Resmi dalam Konteks YukSehat |
|---|---|
| **Poskestren** | Pos Kesehatan Pesantren, unit pertolongan pertama dan layanan kesehatan komunal di pondok pesantren. |
| **RBAC** | *Role-Based Access Control*, mekanisme pembatasan hak akses sistem berdasarkan peran pengguna (Admin, Pengurus, Santri). |
| **CSRF** | *Cross-Site Request Forgery*, serangan siber yang memalsukan perintah dari pengguna sah; dicegah melalui token acak kriptografis. |
| **IDOR** | *Insecure Direct Object References*, kerentanan akses data langsung tanpa otorisasi kepemilikan; dicegah dengan validasi kepemilikan rekam medis. |
| **NIS** | Nomor Induk Santri, identitas unik numerik yang dimiliki oleh setiap santri di pesantren. |
| **PHBS** | Perilaku Hidup Bersih dan Sehat, parameter edukasi preventif kesehatan asrama. |
| **Dual-Engine AI** | Arsitektur integrasi AI pada YukSehat yang memadukan komputasi Google Gemini API dan NVIDIA NIM API untuk menjamin ketersediaan tinggi (*high availability*). |
| **Safe-Mode WhatsApp** | Pengoperasian antrean notifikasi WhatsApp di mana pesan tidak langsung dikirim ke gateway eksternal tanpa pengaktifan sakelar `WHATSAPP_ENABLED`, serta sterilisasi penuh data medis dari teks pesan. |
| **Fonnte** | Layanan pihak ketiga penyedia *Application Programming Interface* (API) WhatsApp Gateway. |
| **SimpleXLSXGen** | Pustaka generator berkas Microsoft Excel format ZIP/XML ringan berbasis PHP native tanpa dependensi ekstensi berat. |

---

## 5. Perspektif Produk

Aplikasi YukSehat beroperasi sebagai aplikasi monolitik web mandiri dengan arsitektur berorientasi layanan (*Service-Oriented Architecture*) untuk integrasi AI dan pesan eksternal:

```
[ Web Browser Client ]
       |
       | HTTP/HTTPS Request
       v
+--------------------------------------------------------------------------+
| Web Server (Apache with mod_rewrite & Security Headers)                  |
| Root Request Rewrite -> /public/index.php                                |
+--------------------------------------------------------------------------+
       |
       v
+--------------------------------------------------------------------------+
| YukSehat PHP MVC Engine (PHP 8.1+)                                       |
| - Core: App Router, Base Controller, PDO Database Wrapper                |
| - Helpers: AuthHelper, SessionHelper, MedicalFormatHelper, SimpleXLSXGen|
| - Models: User, Santri, Keluhan, Pemeriksaan, Notifikasi, Dashboard      |
| - Services: AiService, ChatbotDataService, FonnteService, WaQueueService |
+--------------------------------------------------------------------------+
       |                                      |                     |
       | SQL Queries (PDO Prepared)           | IPC / HTTP (cURL)   | CLI Worker
       v                                      v                     v
+--------------------+              +-------------------+ +----------------+
| MySQL Database     |              | Node.js Chatbot   | | Background     |
| (yuk_sehat)        |              | Server (Port 3005)| | Worker CLI     |
| 8 Tabel Relasional |              +-------------------+ | (wa_worker.php)|
+--------------------+                        |           +----------------+
                                              |                   |
                               +--------------+---------+         | HTTPS API
                               |                        |         v
                               v                        v   +--------------+
                       [ Google Gemini API ]  [ NVIDIA NIM ]| Fonnte WA    |
                       (Cloud LLM Studio)     (API Catalog) | Gateway      |
                                                            +--------------+
```

---

## 6. Karakteristik Pengguna

| Kelas Pengguna | Tingkat Keahlian Teknis | Hak Akses Utama | Tanggung Jawab Operasional |
|---|---|---|---|
| **Administrator** | Menengah | Seluruh modul sistem, manajemen pengguna, konfigurasi AI, monitoring WhatsApp. | Memastikan ketersediaan sistem, audit integritas data, dan manajemen staf. |
| **Pengurus** | Menengah ke Bawah | Antrean keluhan, rekam medis (Input khusus Ronal), direktori santri, dan laporan. | Memberikan penanganan medis fisik dan mencatat rekam medis harian santri. |
| **Santri** | Dasar | Pengajuan keluhan pribadi, riwayat kesehatan sendiri, dan chatbot konsultasi. | Melaporkan kondisi sakit secara mandiri dan mengakses edukasi kesehatan. |
| **Tamu (Guest)** | Dasar | Landing page, pendaftaran santri, dan formulir login. | Mengakses informasi awal pesantren dan melakukan autentikasi ke dalam sistem. |

---

## 7. Lingkungan Operasional

1. **Perangkat Keras Minimum Server**:
   - Prosesor: Dual-Core 2.0 GHz (x86_64).
   - Memori (RAM): Minimal 2 GB (Disarankan 4 GB jika menjalankan Node.js secara lokal).
   - Media Penyimpanan: 1 GB ruang kosong untuk sistem dan berkas basis data.
2. **Perangkat Lunak Server**:
   - Sistem Operasi: Microsoft Windows (XAMPP Environment) atau Linux Distribution (Ubuntu 20.04+ / Debian 11+).
   - Web Server: Apache 2.4+ dengan modul `mod_rewrite` dan `mod_headers` aktif.
   - Bahasa Pemrograman: PHP >= 8.1.0 (Ekstensi wajib: `pdo_mysql`, `curl`, `mbstring`, `openssl`).
   - Basis Data: MySQL >= 5.7 atau MariaDB >= 10.4.
   - Lingkungan Eksekusi AI: Node.js >= 18.0.0 (Tersedia runtime tersemat di `chatbot-server/node.exe`).
3. **Perangkat Lunak Klien**:
   - Peramban web modern dengan dukungan ECMAScript 6+ dan CSS3 (Google Chrome 90+, Mozilla Firefox 88+, Microsoft Edge, Safari Mobile).

---

## 8. Batasan Sistem (Design Constraints)

1. **Batasan Bahasa Pemrograman**: Arsitektur inti wajib MVC Native murni tanpa dependensi framework PHP besar (Laravel/Symfony) demi menjaga portabilitas tinggi di peladen pesantren.
2. **Batasan Keamanan Sesi**: Identitas dan peran pengguna wajib diverifikasi dari sesi backend server (`$_SESSION['user']`). Data role yang dikirimkan melalui payload klien JavaScript dilarang dipercaya.
3. **Batasan Otorisasi Input Medis (Aturan Ronal)**: Formulir tambah rekam medis baru (`/pemeriksaan/create`) dan penyimpanannya (`/pemeriksaan/store`) dibatasi secara ketat hanya dapat dieksekusi oleh akun pengurus ber-username `ronal` (`BR-002`).
4. **Batasan Kerahasiaan Notifikasi WhatsApp**: Pesan yang disalurkan ke gateway Fonnte dilarang memuat informasi keluhan medis, diagnosa, atau resep obat santri (`BR-006`).
5. **Kemandirian Berkas Ekspor**: Generator berkas Microsoft Excel wajib menggunakan pustaka ringan berbasis XML murni (`SimpleXLSXGen`), tanpa ketergantungan ekstensi COM atau pustaka PhpSpreadsheet yang membutuhkan Composer besar.

---

## 9. Kebutuhan Fungsional (Functional Requirements)

### 9.1 Modul Autentikasi dan Kontrol Akses (FR-AUTH)

#### FR-AUTH-001: Multi-Identifier Login
- **Deskripsi**: Sistem harus memfasilitasi pengguna untuk melakukan autentikasi menggunakan salah satu dari tiga pengenal: Username akun, Nomor Induk Santri (NIS), atau Nama Lengkap Petugas (khusus admin/pengurus).
- **Masukan**: Kredensial identitas (string), Kata Sandi (string), Token CSRF (string).
- **Proses**:
  1. Validasi keberadaan dan keabsahan token CSRF via `hash_equals()`.
  2. Cari catatan pengguna aktif pada tabel `users` yang berelasi dengan tabel `santri` (berdasarkan `nis`) atau tabel `pengurus` (berdasarkan `nama`).
  3. Verifikasi kesesuaian kata sandi menggunakan `password_verify($password, $user['password'])`.
  4. Regenerasi ID sesi dengan `session_regenerate_id(true)`.
  5. Rotasi token CSRF via `AuthHelper::rotateCSRF()`.
  6. Daftarkan informasi sesi pengguna: `id`, `username`, `role`, dan `nama` tampilan.
- **Keluaran**: Pengalihan (*redirect*) ke rute `/dashboard` dengan sesi aktif, atau pengembalian pesan kesalahan jika kredensial salah.

#### FR-AUTH-002: Registrasi Mandiri Santri
- **Deskripsi**: Sistem harus memfasilitasi calon pengguna santri untuk mendaftarkan akun baru secara mandiri.
- **Masukan**: NIS, Nama Lengkap, Username, Password, Konfirmasi Password, Jenis Kelamin (L/P), Tanggal Lahir, Kelas, Kamar, dan Nomor HP.
- **Proses**:
  1. Validasi token anti-CSRF.
  2. Periksa keunikan Username pada tabel `users`.
  3. Periksa keunikan NIS pada tabel `santri`.
  4. Verifikasi panjang kata sandi minimal 8 karakter dan kesesuaian konfirmasi password.
  5. Bungkus penyimpanan dalam transaksi basis data atomik (`START TRANSACTION`):
     - `INSERT INTO users` dengan role `'santri'` dan password ter-hash BCrypt.
     - `INSERT INTO santri` dengan `user_id` hasil *last insert id*.
  6. Eksekusi `COMMIT` jika kedua operasi berhasil, atau `ROLLBACK` jika terjadi kegagalan.
- **Keluaran**: Pesan flash sukses dan pengalihan ke `/login`.

#### FR-AUTH-003: Terminasi Sesi (Logout)
- **Deskripsi**: Sistem harus memfasilitasi pengguna untuk mengakhiri sesi aktif secara aman.
- **Proses**: Hapus seluruh variabel sesi (`session_unset()`), hancurkan data sesi server (`session_destroy()`), dan alihkan pengguna ke `/login`.

---

### 9.2 Modul Dashboard & Analitik (FR-DASH)

#### FR-DASH-001: Statistik Operasional Poskestren
- **Deskripsi**: Sistem harus menampilkan ringkasan metrik kesehatan real-time pada halaman utama dashboard sesuai peran yang sedang login.
- **Keluaran Berdasarkan Peran**:
  - **Admin / Pengurus**: Total santri aktif, jumlah pemeriksaan hari ini, pemeriksaan minggu ini, santri rawat inap/UKS, santri rujukan luar pesantren, total pemeriksaan kumulatif, total santri sakit, dan 5 baris pemeriksaan fisik terbaru.
  - **Santri**: Profil santri (NIS, Kelas, Kamar), total pemeriksaan pribadi, pemeriksaan hari ini, riwayat rawat inap pribadi, dan 3 riwayat pemeriksaan medis terakhir miliknya.

---

### 9.3 Modul Master Data Santri (FR-SAN)

#### FR-SAN-001: Direktori Santri Terfilter & Paginasi
- **Deskripsi**: Sistem harus menampilkan daftar santri aktif yang dapat difilter berdasarkan kata kunci pencarian (Nama atau NIS), filter kelas, dan filter kamar dengan paginasi 10 data per halaman.
- **Hak Akses**: Administrator dan Pengurus.

#### FR-SAN-002: Penambahan dan Pembaruan Data Santri
- **Deskripsi**: Sistem harus memfasilitasi penambahan santri baru dan pembaruan biodata (NIS, Nama, Jenis Kelamin, Tanggal Lahir, Kelas, Kamar, Nomor HP, Golongan Darah, Alergi, Riwayat Penyakit Komorbid) dengan sanitasi string ketat dan validasi keunikan NIS.

#### FR-SAN-003: Deaktivasi Akun Santri (Soft Delete)
- **Deskripsi**: Sistem harus memfasilitasi penonaktifan akun santri melalui metode POST terproteksi CSRF. Status akun pada tabel `users` diubah menjadi `'inactive'` tanpa menghapus fisik baris data di tabel `santri`, guna menjaga keutuhan riwayat pemeriksaan medis terdahulu.

---

### 9.4 Modul Keluhan & Antrean Poskestren (FR-KEL)

#### FR-KEL-001: Pengajuan Keluhan Mandiri Santri
- **Deskripsi**: Sistem harus menyediakan antarmuka bagi santri untuk mengajukan keluhan rasa sakit, memilih pengurus pemeriksa yang bertugas, dan mencatat waktu lapor secara otomatis.
- **Proses**:
  1. Validasi input keluhan (wajib diisi).
  2. Validasi petugas pemeriksa (wajib ada di tabel `users` berstatus `active` dengan role `pengurus`).
  3. Simpan data ke `riwayat_keluhan` dengan status default `'menunggu'`.
  4. Buat notifikasi in-app dan antrean pesan WhatsApp generik khusus kepada pengurus yang dipilih.

#### FR-KEL-002: Manajemen Antrean Keluhan Petugas
- **Deskripsi**: Sistem harus menampilkan tabel antrean keluhan santri kepada Admin dan Pengurus, dilengkapi tab filter cepat (*All, Menunggu, Sedang Diperiksa, Selesai*) beserta angka counter real-time.

#### FR-KEL-003: Penugasan dan Pengalihan Petugas (Assign/Reassign)
- **Deskripsi**: Sistem harus memungkinkan Admin atau Pengurus untuk menetapkan atau mengalihkan tanggung jawab penanganan santri kepada petugas pengurus lain. Sistem memicu pembuatan notifikasi in-app kepada pengurus baru.

#### FR-KEL-004: Pembaruan Status Keluhan Real-Time
- **Deskripsi**: Sistem harus memfasilitasi pengubahan status keluhan (`menunggu` -> `diperiksa` -> `selesai`). Pembaruan status memicu notifikasi in-app otomatis ke akun santri pelapor.

---

### 9.5 Modul Pemeriksaan Medis & Asisten AI (FR-MED)

#### FR-MED-001: Pencatatan Rekam Medis Fisik
- **Deskripsi**: Sistem harus menyediakan formulir pencatatan tanda vital (Suhu °C, Tekanan Darah mmHg, Berat Badan kg, Tinggi Badan cm), Diagnosa Hasil Pemeriksaan, Tindakan Medis, Resep Obat, dan Catatan Tambahan.
- **Aturan Bisnis (BR-002)**: Form tambah periksa baru (`/pemeriksaan/create`) dan penyimpanannya (`/pemeriksaan/store`) dibatasi secara ketat hanya untuk akun Pengurus bernama `ronal`.

#### FR-MED-002: Pre-fill Otomatis Data Antrean
- **Deskripsi**: Ketika petugas membuka form periksa melalui tombol "Periksa" pada antrean keluhan, sistem harus mengisi otomatis data santri terpilih dan teks keluhan santri ke dalam formulir pemeriksaan.

#### FR-MED-003: Draf Analisis Klinis Asisten AI
- **Deskripsi**: Sistem harus menyediakan tombol "Analisis Keluhan dengan AI" yang memproses keluhan santri dan data vital anonim ke model AI (Google Gemini / NVIDIA NIM).
- **Format Luaran AI**: Draf ringkas (maksimal 1–2 butir poin per bagian):
  1. Intisari Keluhan & Dugaan Klinis Awal.
  2. Tindakan Observasi Poskestren.
  3. Rekomendasi Terapi Gejala Ringan & Edukasi.
  4. Tanda Bahaya (*Red Flags*) yang Memerlukan Rujukan Rumah Sakit.

#### FR-MED-004: Penempelan Draf AI dengan Konfirmasi Overwrite
- **Deskripsi**: Sistem harus memungkinkan petugas menempelkan saran AI ke form periksa melalui tombol "Gunakan Saran ke Formulir". Jika kolom form sudah memuat ketikan manual, sistem wajib menampilkan modal konfirmasi kustom sebelum menimpa data.

#### FR-MED-005: Penutupan Status Keluhan Otomatis
- **Deskripsi**: Saat rekam medis pemeriksaan berhasil disimpan, sistem harus memperbarui status baris keluhan terkait di tabel `riwayat_keluhan` menjadi `'selesai'` secara otomatis.

---

### 9.6 Modul Rekapitulasi & Ekspor Excel (FR-REP)

#### FR-REP-001: Filter Riwayat Pemeriksaan Komprehensif
- **Deskripsi**: Sistem harus menyediakan filter rekapitulasi berdasarkan: Tanggal Mulai, Tanggal Akhir, Nama Santri, Kelas, Kamar, Jenis Kelamin, dan Kata Kunci Keluhan.

#### FR-REP-002: Ekspor Buku Kerja Excel Multi-Sheet
- **Deskripsi**: Sistem harus menghasilkan berkas spreadsheet Microsoft Excel (.xlsx) resmi dengan 2 lembar kerja (*sheets*):
  - **Sheet 1 ("Ringkasan Laporan")**: Kolom identitas, data vital, ringkasan hasil klinis (maksimal 200 karakter via `MedicalFormatHelper::summarizeHasil()`), ringkasan tindakan, obat, catatan, dan nama pemeriksa.
  - **Sheet 2 ("Detail Catatan Klinis")**: Catatan klinis lengkap tanpa pemotongan, membedakan tipe catatan antara "Catatan Petugas" dan "Draf Rekomendasi AI".

---

### 9.7 Modul Chatbot Kesehatan & Analitik Pesantren (FR-CHAT)

#### FR-CHAT-001: Konsultasi Interaktif Floating Widget
- **Deskripsi**: Sistem harus menyediakan antarmuka obrolan mengambang (*floating widget*) yang dapat diakses dari seluruh halaman dashboard untuk berkonsultasi seputar kesehatan santri.

#### FR-CHAT-002: Penjawab Kueri Data Statistik Real-Time
- **Deskripsi**: Chatbot harus mampu menjawab pertanyaan analitik basis data operasional secara deterministik langsung untuk Admin dan Pengurus (contoh: *"Berapa pemeriksaan hari ini?"*, *"Siapa saja yang sakit hari ini?"*, *"Berapa santri rawat inap?"*, *"Kamar apa yang paling sering sakit?"*).
- **Batasan**: Kueri agregat basis data diblokir jika penanya adalah akun peran Santri.

#### FR-CHAT-003: Penyaringan Pertanyaan Luar Topik (Off-Topic Guard)
- **Deskripsi**: Sistem harus menolak secara sopan pertanyaan di luar konteks kesehatan dan pesantren (seperti coding, politik, matematika rumit, game) menggunakan pesan standar (*canned rejection*).

#### FR-CHAT-004: Dual-Engine & Safe Offline Fallback
- **Deskripsi**: Chatbot harus memprioritaskan pemanggilan engine Node.js (Port 3005), dengan fallback ke engine internal PHP cURL. Jika seluruh layanan AI offline, sistem menampilkan pesan darurat yang menyarankan pengguna menemui petugas Poskestren secara langsung.

---

### 9.8 Modul Notifikasi & Integrasi WhatsApp (FR-NOTIF)

#### FR-NOTIF-001: Pencatatan Notifikasi In-App Idempoten
- **Deskripsi**: Sistem harus mencatat notifikasi internal saat terjadi keluhan baru, penugasan, atau perubahan status, dengan pencegahan duplikasi data berbasis kombinasi `(user_id, jenis, referensi_id)`.

#### FR-NOTIF-002: Antarmuka Riwayat Notifikasi & Modal Detail
- **Deskripsi**: Menyediakan halaman `/notifikasi` dengan paginasi, indikator jumlah belum dibaca, tombol "Buka Rincian" yang menampilkan modal Bootstrap detail keluhan & pemeriksaan, penandaan baca AJAX, serta tombol "Tandai Semua Dibaca".

#### FR-NOTIF-003: Antrean Pengiriman WhatsApp Aman (Safe-Mode)
- **Deskripsi**: Sistem harus menstandarkan nomor telepon tujuan (`628xxx`) dan menyimpan antrean pesan ke `notifikasi_wa_queue` dengan status `'pending'` tanpa mengirim data medis rahasia.

#### FR-NOTIF-004: Pemrosesan Worker Latar Belakang (CLI)
- **Deskripsi**: Menyediakan skrip CLI `bin/wa_worker.php` yang dapat dieksekusi via Cron Job untuk memproses antrean pesan WhatsApp dengan file lock (`flock`) anti-tumpang-tindih dan jeda percobaan bertingkat (*exponential backoff*).

#### FR-NOTIF-005: Dashboard Monitoring WhatsApp (Khusus Admin)
- **Deskripsi**: Menyediakan halaman `/monitoring` bagi Admin untuk memeriksa status koneksi perangkat Fonnte, ringkasan kuota paket, audit jejak pengiriman, tombol proses batch manual, dan tombol rekonsiliasi status antrean (`reconciliation_required`).

---

### 9.9 Modul Pengelolaan Sistem & Pengguna (FR-ADM)

#### FR-ADM-001: Manajemen Pengguna & Anti-Lockout
- **Deskripsi**: Admin dapat menambah akun admin/pengurus, mengubah role (admin/pengurus/santri), memperbarui status (active/inactive), dan memperbarui nomor WhatsApp. Sistem wajib menolak penurunan role atau penonaktifan terhadap akun Admin yang sedang login (*Anti-Lockout Rule*).

#### FR-ADM-002: Konfigurasi Model AI & Manajemen API Key
- **Deskripsi**: Admin dapat memilih model AI aktif untuk Chatbot dan Analisis Klinis (NVIDIA Nemotron 3.5 Bolt, Gemini Flash Lite, Gemini 3.5 Flash, Gemini 3.8 Flash, dll.), melakukan uji latensi respon model via AJAX, memasang API Key baru, serta menghapus API Key lama secara aman melalui modal dialog.

---

## 10. Kebutuhan Nonfungsional (Non-Functional Requirements)

### 10.1 Keamanan (Security - NFR-SEC)
- **NFR-SEC-001**: Seluruh query interaksi basis data wajib menggunakan teknik *PDO Prepared Statements* dengan *parameter binding*. Penggabungan string variabel mentah ke klausa SQL dilarang keras (Nilai toleransi: 0 insiden SQL Injection).
- **NFR-SEC-002**: Setiap form mutasi data berbasis metode POST wajib memvalidasi token anti-CSRF kriptografis 32-byte acak yang diverifikasi menggunakan perbandingan waktu konstan (`hash_equals()`).
- **NFR-SEC-003**: Seluruh luaran teks yang berasal dari masukan pengguna wajib disanitasi menggunakan `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` sebelum dirender ke DOM untuk mencegah XSS.
- **NFR-SEC-004**: Cookie sesi PHP wajib dikonfigurasi dengan atribut `httponly=true`, `samesite=Lax`, dan `secure=auto`.
- **NFR-SEC-005**: Pesan notifikasi yang dikirimkan ke pihak ketiga (WhatsApp Fonnte) wajib 100% bebas dari narasi keluhan medis, diagnosa penyakit, dan nama obat santri (*Zero Medical Leakage*).
- **NFR-SEC-006**: Berkas konfigurasi sensitif (`.env`, `.git`, `.sql`, berkas uji) dan direktori internal sistem wajib diblokir total dari akses langsung HTTP publik melalui aturan Apache `.htaccess`.
- **NFR-SEC-007**: Pada mode produksi (`APP_ENV=production`), direktif `display_errors` wajib dimatikan (`0`) untuk mencegah kebocoran *stack trace*, path direktori server, atau kredensial internal ke peramban pengguna.

### 10.2 Performa dan Efisiensi (Performance - NFR-PERF)
- **NFR-PERF-001**: Waktu muat halaman statis dan dashboard internal tanpa pemanggilan AI eksternal harus disajikan di bawah 1,5 detik pada kondisi jaringan lokal normal.
- **NFR-PERF-002**: Waktu respon asisten AI (analisis klinis dan chatbot) ditargetkan selesai dalam rentang 2 hingga 6 detik (bergantung pada latensi API cloud Google/NVIDIA).
- **NFR-PERF-003**: Mesin ekspor laporan spreadsheet via `SimpleXLSXGen` harus mampu menghasilkan berkas XLSX berisi 1.000 baris data rekam medis dalam waktu kurang dari 3 detik dengan konsumsi memori puncak di bawah 32 MB.

### 10.3 Keandalan dan Ketersediaan (Reliability - NFR-REL)
- **NFR-REL-001**: Kegagalan koneksi internet atau habisnya kuota gateway eksternal (Fonnte/Gemini/NVIDIA) tidak boleh menyebabkan kegagalan sistem utama atau pembatalan transaksi basis data lokal (*fail-safe non-blocking*).
- **NFR-REL-002**: Pemrosesan antrean WhatsApp worker latar belakang wajib dilindungi berkas kunci eksklusif non-blocking (`flock`) untuk mencegah tabrakan proses konkuren saat dipicu oleh cron job berkala (*Anti-Overlapping F-02*).
- **NFR-REL-003**: Antrean pengiriman pesan yang mengalami kegagalan transmisi jaringan wajib menerapkan skema penundaan bertingkat (*Exponential Backoff*): Percobaan 1 jeda 60 detik, Percobaan 2 jeda 300 detik, dan maksimal 3 kali percobaan sebelum dinyatakan `failed`.

### 10.4 Kegunaan dan Aksesibilitas (Usability - NFR-USE)
- **NFR-USE-001**: Seluruh komponen antarmuka harus responsif dan adaptif terhadap resolusi layar desktop (>= 1200px), tablet (768px - 1199px), dan ponsel cerdas (<= 767px).
- **NFR-USE-002**: Dialog interaksi penimpaan data dan konfirmasi tindakan destruktif wajib menggunakan modal kustom Bootstrap yang elegan dan tidak memblokir peramban seperti fungsi bawaan `alert()` atau `confirm()`.

### 10.5 Kemudahan Pemeliharaan dan Portabilitas (Maintainability & Portability - NFR-MAINT)
- **NFR-MAINT-001**: Kode sumber wajib mematuhi standar arsitektur MVC Native dengan pemisahan berkas yang konsisten, tanpa ketergantungan paket biner OS eksternal yang sulit dipindahkan.
- **NFR-MAINT-002**: Konfigurasi lingkungan sistem wajib terpusat pada satu berkas `.env` yang dapat dikonfigurasi ulang secara dinamis tanpa mengubah baris kode logika bisnis.

---

## 11. Spesifikasi Hak Akses (Role-Based Access Control)

Berikut adalah matriks hak akses formal yang ditegakkan pada tingkat controller:

| Kode FR | Modul & Aksi Fungsional | Tamu (Guest) | Santri | Pengurus | Administrator |
|---|---|:---:|:---:|:---:|:---:|
| **FR-AUTH-001** | Login Multi-Identifier | Diizinkan | Redirect | Redirect | Redirect |
| **FR-AUTH-002** | Registrasi Akun Santri | Diizinkan | Redirect | Redirect | Redirect |
| **FR-AUTH-003** | Logout Sistem | Ditolak | Diizinkan | Diizinkan | Diizinkan |
| **FR-DASH-001** | Akses Dashboard Statistik | Ditolak | Khusus Santri | Penuh Poskestren | Penuh Poskestren |
| **FR-SAN-001** | Lihat Direktori Master Santri | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-SAN-002** | Tambah & Edit Data Santri | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-SAN-003** | Deaktivasi Akun Santri | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-KEL-001** | Kirim Keluhan Mandiri | Ditolak | **Diizinkan** | Ditolak | Ditolak |
| **FR-KEL-002** | Tinjau Antrean Keluhan | Ditolak | Milik Sendiri | Diizinkan | Diizinkan |
| **FR-KEL-003** | Tugaskan / Alihkan Pemeriksa | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-KEL-004** | Perbarui Status Keluhan | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-MED-001** | Akses Form Tambah Periksa | Ditolak | Ditolak | **Hanya Ronal** | Ditolak (Aturan Ronal)|
| **FR-MED-001** | Simpan Pemeriksaan Medis | Ditolak | Ditolak | **Hanya Ronal** | Ditolak (Aturan Ronal)|
| **FR-MED-002** | Edit & Hapus Rekam Medis | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-MED-003** | Analisis Keluhan via AI | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-MED-004** | Lihat Rincian Rekam Medis | Ditolak | Milik Sendiri (IDOR Safe) | Diizinkan | Diizinkan |
| **FR-REP-001** | Akses Menu Laporan Terfilter | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-REP-002** | Ekspor Laporan Excel Multi-Sheet | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-CHAT-001** | Konsultasi Chatbot AI | Diizinkan | Diizinkan | Diizinkan | Diizinkan |
| **FR-CHAT-002** | Kueri Data Agregat via Chatbot | Ditolak | Ditolak | Diizinkan | Diizinkan |
| **FR-NOTIF-001**| Riwayat Notifikasi In-App | Ditolak | Milik Sendiri | Milik Sendiri | Ditolak (Khusus Petugas/Santri) |
| **FR-ADM-001** | Manajemen Pengguna & Role | Ditolak | Ditolak | Ditolak | **Diizinkan (Khusus Admin)** |
| **FR-ADM-002** | Konfigurasi Model AI & Kunci API | Ditolak | Ditolak | Ditolak | **Diizinkan (Khusus Admin)** |
| **FR-NOTIF-005**| Monitoring Gateway WhatsApp | Ditolak | Ditolak | Ditolak | **Diizinkan (Khusus Admin)** |

---

## 12. Kebutuhan Antarmuka (Interface Requirements)

### 12.1 Antarmuka Pengguna (User Interface)
- Menggunakan palet warna hijau Poskestren (Emerald/Success `#059669` - `#10b981`) dan biru profesional (`#1e40af`).
- Seluruh tabel menyajikan kontrol pencarian cepat, filter status berbentuk pills, dan pagination.
- Interaksi asinkron (AJAX) diterapkan pada pengujian model AI, analisis keluhan klinis, dan penandaan baca notifikasi.
- Notifikasi visual menggunakan `ykToast` bergradien di pojok kanan atas layar dan konfirmasi penghapusan/penimpaan menggunakan `ykConfirmModal` yang terpusat.

### 12.2 Antarmuka Perangkat Lunak (Software Interface)
- **Koneksi Basis Data**: Ekstensi PHP `PDO_MYSQL` menggunakan koneksi port default MySQL `3306`.
- **Mikrolayanan Chatbot**: Protokol HTTP REST JSON ke endpoint `http://127.0.0.1:3005` yang dilindungi header rahasia `X-Internal-Secret` dan verifikasi tanda tangan HMAC.
- **Layanan Cloud Google Gemini**: Endpoint REST API v1beta Google AI Studio (`https://generativelanguage.googleapis.com/v1beta/models/...:generateContent`).
- **Layanan Cloud NVIDIA NIM**: Endpoint REST API OpenAI-compatible (`https://integrate.api.nvidia.com/v1/chat/completions`).
- **Layanan Gateway WhatsApp Fonnte**: Endpoint HTTPS REST `https://api.fonnte.com/send` dan `https://api.fonnte.com/device` dengan autentikasi header token `Authorization: {FONNTE_TOKEN}`.

### 12.3 Antarmuka Komunikasi (Communication Interface)
- Protokol transfer web menggunakan HTTP/1.1 dan HTTPS (disarankan dengan sertifikat TLS 1.3).
- Format pertukaran data internal pengontrol dan API eksternal menggunakan format standar **JSON** (*JavaScript Object Notation*) dengan set karakter UTF-8.

---

## 13. Kebutuhan Basis Data (Database Requirements)

### 13.1 Kamus Data Ringkas (Data Dictionary)

#### 1. Tabel `users`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `username` | VARCHAR(255) | NO | Pengenal unik login (Username / NIS) |
| `password` | VARCHAR(255) | NO | Hash kata sandi algoritma BCrypt |
| `role` | ENUM('admin','pengurus','santri') | NO | Peran pengguna dalam sistem |
| `status` | ENUM('active','inactive') | YES | Status keaktifan akun (Default: 'active') |
| `created_at` | TIMESTAMP | NO | Waktu pendaftaran akun |
| `updated_at` | TIMESTAMP | NO | Waktu pembaruan akun terakhir |

#### 2. Tabel `santri`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `user_id` | INT(11) | NO | Foreign Key ke `users.id` (ON DELETE CASCADE) |
| `nis` | VARCHAR(50) | NO | Nomor Induk Santri (Unik) |
| `nama` | VARCHAR(255) | NO | Nama lengkap santri |
| `jenis_kelamin`| ENUM('L','P') | NO | Jenis kelamin santri |
| `tanggal_lahir`| DATE | YES | Tanggal lahir santri |
| `kelas` | VARCHAR(50) | YES | Tingkat/kelas santri |
| `kamar` | VARCHAR(100) | YES | Nama/nomor kamar asrama |
| `no_hp` | VARCHAR(20) | YES | Nomor telepon santri/wali |
| `golongan_darah`| VARCHAR(5) | YES | Golongan darah (A, B, AB, O) |
| `alergi` | TEXT | YES | Catatan alergi makanan/obat |
| `riwayat_penyakit`| TEXT | YES | Riwayat penyakit bawaan/kronis |

#### 3. Tabel `riwayat_keluhan`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `santri_id` | INT(11) | NO | Foreign Key ke `santri.id` (ON DELETE CASCADE) |
| `tanggal_keluhan`| DATETIME | NO | Waktu santri mengirim keluhan |
| `keluhan` | TEXT | NO | Deskripsi gejala rasa sakit |
| `assigned_to` | INT(11) | YES | Foreign Key ke `users.id` (Petugas penanganan) |
| `status` | ENUM('menunggu','diperiksa','selesai')| YES | Status alur penanganan |

#### 4. Tabel `pemeriksaan_kesehatan`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `santri_id` | INT(11) | NO | Foreign Key ke `santri.id` (ON DELETE CASCADE) |
| `pemeriksa_id` | INT(11) | YES | Foreign Key ke `users.id` (Petugas pemeriksa) |
| `keluhan_id` | INT(11) | YES | Foreign Key ke `riwayat_keluhan.id` |
| `tanggal_pemeriksaan`| DATETIME | NO | Waktu pemeriksaan fisik dilakukan |
| `keluhan` | TEXT | YES | Keluhan santri saat diperiksa |
| `suhu` | DECIMAL(4,2) | YES | Suhu tubuh (°C) |
| `tekanan_darah`| VARCHAR(20) | YES | Tekanan darah (mmHg, contoh: '120/80') |
| `berat_badan` | DECIMAL(5,2) | YES | Berat badan santri (kg) |
| `tinggi_badan` | DECIMAL(5,2) | YES | Tinggi badan santri (cm) |
| `hasil_pemeriksaan`| TEXT | YES | Diagnosa temuan klinis |
| `tindakan` | TEXT | YES | Tindakan medis/terapi yang diberikan |
| `obat` | TEXT | YES | Nama dan dosis obat yang diserahkan |
| `catatan` | TEXT | YES | Catatan tambahan & tanda bahaya rujukan |

#### 5. Tabel `notifikasi`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `user_id` | INT(11) | NO | Foreign Key ke `users.id` (Penerima notifikasi) |
| `jenis` | VARCHAR(50) | NO | Kategori notifikasi (keluhan_baru, dll.) |
| `judul` | VARCHAR(255) | NO | Judul ringkas notifikasi |
| `pesan` | TEXT | NO | Narasi penjelasan notifikasi |
| `link_url` | VARCHAR(255) | NO | Tautan navigasi internal aplikasi |
| `referensi_tipe`| VARCHAR(50) | YES | Tipe objek referensi (contoh: 'keluhan') |
| `referensi_id` | INT(11) | YES | ID objek referensi pada tabel terkait |
| `dibaca_pada` | DATETIME | YES | Stempel waktu notifikasi dibaca |
| `created_at` | DATETIME | NO | Waktu pembuatan notifikasi |

#### 6. Tabel `notifikasi_wa_queue`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `id` | INT(11) | NO | Primary Key, Auto Increment |
| `notifikasi_id`| INT(11) | NO | Foreign Key ke `notifikasi.id` |
| `user_id` | INT(11) | NO | ID pengguna penerima |
| `nomor_tujuan` | VARCHAR(30) | NO | Nomor WhatsApp terstandar (628xxx) |
| `pesan` | TEXT | NO | Teks pesan WhatsApp (Wajib generik) |
| `status` | ENUM('pending','processing','sent','failed','reconciliation_required') | YES | Status pemrosesan antrean |
| `provider_status`| VARCHAR(50) | YES | Status respon gateway Fonnte |
| `provider_message_id`| VARCHAR(100)| YES | ID unik pesan dari gateway Fonnte |
| `percobaan` | INT(11) | YES | Jumlah percobaan pengiriman |
| `max_percobaan`| INT(11) | YES | Batas maksimal percobaan (Default: 3) |
| `error_message`| TEXT | YES | Keterangan kegagalan pengiriman |
| `claimed_at` | DATETIME | YES | Kunci stempel waktu worker aktif |
| `next_retry_at`| DATETIME | YES | Jadwal percobaan berikutnya |

#### 7. Tabel `system_settings`
| Kolom | Tipe Data | Nullable | Keterangan |
|---|---|:---:|---|
| `setting_key` | VARCHAR(64) | NO | Primary Key (nama kunci konfigurasi) |
| `setting_value`| TEXT | YES | Nilai pengaturan (model, provider, key) |
| `updated_at` | TIMESTAMP | NO | Waktu pembaruan konfigurasi |

---

## 14. Kriteria Penerimaan (Acceptance Criteria per FR)

| Kode Kebutuhan | Skenario Pengujian | Hasil yang Diharapkan (*Pass Criteria*) |
|---|---|---|
| **FR-AUTH-001** | Login dengan Username, NIS, atau Nama Petugas | Sistem memverifikasi kredensial secara akurat, me-regenerasi session ID, dan me-redirect pengguna ke Dashboard yang sesuai perannya. |
| **FR-AUTH-002** | Registrasi dengan NIS yang sudah ada | Sistem menolak pendaftaran dan memunculkan pesan peringatan bahwa NIS sudah terdaftar. |
| **FR-SAN-001** | Filter pencarian santri berdasarkan nama & kelas | Hanya santri yang memenuhi kedua kriteria yang tampil di tabel; data terpaginasi dengan rapi. |
| **FR-SAN-003** | Deaktivasi akun santri | Kolom status di tabel `users` berubah menjadi `inactive`; data santri tidak muncul di pencarian aktif, namun riwayat pemeriksaan medis tetap utuh. |
| **FR-KEL-001** | Pengajuan keluhan oleh santri | Keluhan masuk ke tabel `riwayat_keluhan` berstatus `menunggu`, notifikasi in-app terkirim ke pengurus terpilih, pesan WhatsApp generik tercatat di queue. |
| **FR-KEL-003** | Pengalihan keluhan ke pengurus lain | Nilai `assigned_to` terbarui, notifikasi penugasan baru dibuat untuk pengurus target, dan pengurus lama tidak lagi mendapat notifikasi terkait. |
| **FR-MED-001** | Pengurus non-Ronal mengakses `/pemeriksaan/create` | Sistem memblokir akses dan mengalihkan pengguna kembali ke `/pemeriksaan` dengan pesan kesalahan hak akses khusus. |
| **FR-MED-003** | Eksekusi tombol "Analisis Keluhan dengan AI" | Kontainer hijau muncul dalam waktu < 6 detik menampilkan 4 bagian tinjauan klinis ringkas dengan disclaimer non-diagnostik. |
| **FR-MED-004** | Salin saran AI ke form yang sudah ada isinya | Sistem menampilkan Modal Konfirmasi Overwrite; data form hanya tergantikan jika tombol "Ya, Tempelkan" dikonfirmasi. |
| **FR-REP-002** | Unduh laporan kesehatan via tombol Export Excel | Berkas XLSX terunduh tanpa galat peramban, memuat Sheet 1 (Ringkasan) dengan teks klinis terkompresi dan Sheet 2 (Detail Catatan Medis) dengan teks utuh. |
| **FR-CHAT-002** | Pengurus bertanya data statistik kesehatan ke Chatbot | Chatbot mengeksekusi kueri agregasi SQL dan menyajikan angka aktual yang sinkron dengan data basis data Poskestren. |
| **FR-CHAT-003** | Pengguna menanyakan topik di luar kesehatan ke Chatbot | Chatbot menolak dengan sopan menggunakan pesan *Canned Rejection* tanpa memanggil model AI eksternal. |
| **FR-NOTIF-002**| Klik tombol "Buka Rincian" pada halaman Notifikasi | Modal Bootstrap muncul menampilkan rincian nama santri, waktu keluhan, isi keluhan, dan hasil periksa secara komprehensif. |
| **FR-ADM-001** | Admin mencoba menonaktifkan akunnya sendiri | Sistem menolak tindakan tersebut dan memunculkan notifikasi peringatan anti-lockout. |
| **FR-ADM-002** | Pengujian model AI via tombol Uji Model | Indikator spinner aktif, sistem mengembalikan latensi dalam milidetik (ms) dan menampilkan alert status koneksi berhasil/gagal. |

---

## 15. Ketergantungan Eksternal (External Dependencies)

| Komponen Dependensi | Jenis Dependensi | Dampak Jika Layanan Mengalami Gangguan |
|---|---|---|
| **Google Gemini API** | Layanan Eksternal (Cloud) | Analisis klinis otomatis dan chatbot generatif beralih ke engine sekunder NVIDIA NIM atau mengembalikan pesan fallback aman. |
| **NVIDIA NIM API** | Layanan Eksternal (Cloud) | Analisis klinis beralih ke Google Gemini atau fallback internal. |
| **Fonnte WhatsApp Gateway** | Layanan Eksternal (Gateway) | Pesan WhatsApp tertahan di antrean basis data (`notifikasi_wa_queue`) berstatus `pending` atau `failed`. Notifikasi in-app tetap berfungsi normal 100%. |
| **Node.js Local Server** | Layanan Internal (Port 3005) | Permintaan obrolan chatbot otomatis dialihkan (*fallback*) ke cURL internal PHP `AiService.php` tanpa memutus layanan pengguna. |
| **Apache mod_rewrite** | Modul Peladen Web | Seluruh rute *pretty-URL* tidak dapat diarahkan ke Front Controller jika modul ini tidak aktif. |
| **Ekstensi PHP PDO_MySQL** | Ekstensi Interpreter | Aplikasi tidak dapat menginisialisasi koneksi basis data. |
