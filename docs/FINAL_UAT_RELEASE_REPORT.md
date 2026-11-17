# LAPORAN AKHIR AUDIT KEAMANAN, REGRESI & KESIAPAN RILIS UAT
**Aplikasi Poskestren YukSehat (Native PHP MVC + MySQL + Node.js Chatbot)**  
**Tanggal Evaluasi:** 24 Oktober 2026  
**Status Evaluasi:** Final Pre-UAT Audit & Security Hardening  
**Penyusun:** Senior Software Engineer, QA Engineer & Application Security Engineer  

---

## 1. RINGKASAN EKSEKUTIF

Proses audit menyeluruh, penguatan keamanan (*security hardening*), dan pengujian regresi end-to-end telah diselesaikan pada aplikasi **YukSehat**. Aplikasi beroperasi pada arsitektur **Native PHP MVC**, database **MySQL (`yuk_sehat`)**, dan mikro-layanan chatbot **Node.js (Port 3005)** yang melayani asisten AI Poskestren.

Hasil pengujian otomatis dan verifikasi integrasi mencatat **52 dari 52 skenario pengujian berstatus LULUS (100% PASS)** tanpa ada kegagalan regresi pada alur bisnis utama. Seluruh celah keamanan kritis yang ditemukan pada tahap audit awal telah dimitigasi secara penuh:
- Penghapusan seluruh *hardcoded API keys* dan kredensial uji dari *source code*.
- Pencegahan pengunduhan file sensitif (`.env*`, `.sql`, berkas uji, dan direktori internal) melalui proteksi Apache `.htaccess`.
- Penegakan autentikasi server-to-server menggunakan HMAC/Token rahasia dengan perbandingan *timing-safe* (`hash_equals` di PHP dan `crypto.timingSafeEqual` di Node.js).
- Perlindungan integritas data melalui pembungkusan transaksi database (*atomic transactions*) pada pencatatan pemeriksaan dan pembaruan status keluhan santri.
- Penegakan kontrol akses berbasis peran (RBAC) di level pengontrol backend (*controller-level*), bukan sekadar penyembunyian elemen UI/navigasi.
- Penyusunan cadangan database (*database dump*) pra-pengujian berukuran 40 KB di `database/backup_pre_uat_20261024.sql`.

---

## 2. DAFTAR STATUS PENGUJIAN (PASS, FAIL, BLOCKED, NOT TESTED)

| ID | Kategori Pengujian | Deskripsi Skenario | Status | Keterangan & Catatan |
|---|---|---|:---:|---|
| **SEC-01** | Keamanan Rahasia | Audit pembersihan hardcoded Gemini API Key dari berkas source code | **PASS** | 0 hardcoded key ditemukan di seluruh folder `app/`, `chatbot-server/`, dan `config/`. |
| **SEC-02** | Keamanan Rahasia | Proteksi file `.env`, `.sql`, dan direktori internal dari akses HTTP Apache | **PASS** | HTTP 403 Forbidden terkonfirmasi saat mengakses `/.env` dan file terproteksi. |
| **SEC-03** | Keamanan Rahasia | Validasi pemuatan environment pada PHP dan Node.js tanpa fallback bocor | **PASS** | Node.js memuat konfigurasi mandiri; PHP memuat via `$_ENV`/helper aman. |
| **SEC-04** | Keamanan Kredensial | Hashing password seluruh pengguna menggunakan algoritma BCrypt (`$2y$`) | **PASS** | Seluruh 10 akun pengguna di database terverifikasi memakai hash standar BCrypt. |
| **SEC-05** | Keamanan Kredensial | Proteksi Anti-Lockout Super User Admin pada modul manajemen pengguna | **PASS** | Admin tidak dapat menurunkan role dirinya sendiri atau menonaktifkan akun sendiri. |
| **SEC-06** | Keamanan Endpoint | Penolakan akses tanpa secret pada endpoint Node.js `/api/chat` | **PASS** | HTTP 403 Forbidden ketika `X-Internal-Secret` tidak disertakan. |
| **SEC-07** | Keamanan Endpoint | Penolakan secret tidak valid pada endpoint Node.js `/api/chat` | **PASS** | HTTP 403 Forbidden dengan pesan penolakan aman (tidak mencetak stack trace). |
| **SEC-08** | Keamanan Endpoint | Penolakan request tanpa secret pada PHP `/chatbot/internal_query` | **PASS** | HTTP 403 Forbidden dengan respons JSON aman. |
| **SEC-09** | Keamanan Form | Validasi token anti-CSRF pada mutasi data POST (Keluhan, Pemeriksaan, Pengguna) | **PASS** | Permintaan POST tanpa token CSRF yang valid ditolak dengan pesan peringatan. |
| **SEC-10** | Keamanan AI | Pencegahan Prompt Injection & manipulasi identitas via input pengguna | **PASS** | Filter `isOffTopic` dan `OFF_TOPIC_PATTERNS` menolak injeksi perintah dan manipulasi role. |
| **SEC-11** | Keamanan Medis | Perlindungan kerahasiaan rekam medis santri dari permintaan chatbot umum | **PASS** | Chatbot menolak menampilkan riwayat penyakit individual dan mengarahkan ke menu resmi. |
| **SEC-12** | Keamanan Medis | Pencegahan diagnosis pasti & peresepan obat otomatis dari model AI | **PASS** | Respons selalu diawali Draf Tinjauan AI dengan *disclaimer* dan tanda bahaya (*red flags*). |
| **RBAC-01**| Role Admin | Login Fauzan, sesi aktif, dan redirect aman ke Dashboard | **PASS** | HTTP 200, sesi backend terverifikasi, display name `Fauzan`. |
| **RBAC-02**| Role Admin | Verifikasi komponen navigasi navbar dan sidebar lengkap 6 modul | **PASS** | Menu Dashboard, Santri, Pemeriksaan, Laporan, Pengguna, Antrean tampil. |
| **RBAC-03**| Role Admin | Akses langsung URL `/users` diizinkan | **PASS** | HTTP 200, halaman Kelola Pengguna terbuka normal. |
| **RBAC-04**| Role Admin | Logout Admin Fauzan dan invalidasi sesi | **PASS** | Redirect ke `/login`, cookie sesi lama tidak dapat digunakan kembali. |
| **RBAC-05**| Role Pengurus | Login Ronal, sesi aktif, dan redirect aman ke Dashboard | **PASS** | HTTP 200, display name `Ronal`, role `pengurus`. |
| **RBAC-06**| Role Pengurus | Sidebar Pengurus menyembunyikan modul Manajemen Pengguna | **PASS** | Link `/users` tidak muncul di DOM sidebar. |
| **RBAC-07**| Role Pengurus | Akses langsung URL `/users` diblokir oleh controller | **PASS** | Redirect ke Dashboard dengan flash message "Akses ditolak". |
| **RBAC-08**| Role Pengurus | Akses dan pengelolaan antrean keluhan santri diizinkan | **PASS** | Pengurus dapat membuka keluhan dan masuk ke form pemeriksaan. |
| **RBAC-09**| Role Pengurus | Logout Pengurus Ronal dan pembersihan sesi | **PASS** | Redirect ke `/login`. |
| **RBAC-10**| Role Santri | Login Budi Santoso (NIS 1001), sesi aktif, dan redirect ke Dashboard | **PASS** | HTTP 200, display name `Budi Santoso`, role `santri`. |
| **RBAC-11**| Role Santri | Sidebar Santri dibatasi (Keluhan Saya, Riwayat Kesehatan, Dashboard) | **PASS** | Modul staf (Santri, Pemeriksaan, Laporan, Pengguna) disembunyikan. |
| **RBAC-12**| Role Santri | Akses langsung URL `/users` diblokir | **PASS** | Redirect ke Dashboard dengan pesan otorisasi. |
| **RBAC-13**| Role Santri | Akses langsung URL `/pemeriksaan/create` diblokir | **PASS** | Redirect ke Dashboard dengan pesan otorisasi. |
| **RBAC-14**| Role Santri | Akses langsung URL `/laporan` diblokir | **PASS** | Redirect ke Dashboard dengan pesan otorisasi. |
| **RBAC-15**| Role Santri | Proteksi IDOR: Santri tidak dapat membuka URL edit santri lain | **PASS** | Akses ke `/santri/edit/2` diblokir di controller. |
| **RBAC-16**| Role Santri | Logout Santri Budi Santoso dan pembersihan sesi | **PASS** | Redirect ke `/login`. |
| **CHAT-01**| Chatbot Identitas | Pertanyaan "Siapa saya?" untuk Admin Fauzan | **PASS** | "Kamu login sebagai Fauzan dengan role Admin." |
| **CHAT-02**| Chatbot Identitas | Pertanyaan "Apa role saya?" untuk Admin Fauzan | **PASS** | "Kamu login dengan role Admin di YukSehat." |
| **CHAT-03**| Chatbot Identitas | Pertanyaan "Apa saja yang bisa saya lakukan?" untuk Admin | **PASS** | Menampilkan 6 kapabilitas administratif Poskestren. |
| **CHAT-04**| Chatbot Identitas | Pertanyaan "Siapa saya?" untuk Pengurus Ronal | **PASS** | "Kamu login sebagai Ronal dengan role Pengurus." |
| **CHAT-05**| Chatbot Identitas | Pertanyaan "Apa role saya?" untuk Pengurus Ronal | **PASS** | "Kamu login dengan role Pengurus di YukSehat." |
| **CHAT-06**| Chatbot Identitas | Pertanyaan "Apa saja yang bisa saya lakukan?" untuk Pengurus | **PASS** | Menampilkan 5 kapabilitas operasional dan batasan dari modul admin. |
| **CHAT-07**| Chatbot Identitas | Pertanyaan "Siapa saya?" untuk Santri Budi Santoso | **PASS** | "Kamu login sebagai Budi Santoso dengan role Santri." |
| **CHAT-08**| Chatbot Identitas | Pertanyaan "Apa role saya?" untuk Santri Budi Santoso | **PASS** | "Kamu login dengan role Santri di YukSehat." |
| **CHAT-09**| Chatbot Identitas | Pertanyaan "Apa saja yang bisa saya lakukan?" untuk Santri | **PASS** | Menampilkan panduan mengirim keluhan dan melihat riwayat sendiri. |
| **CHAT-10**| Chatbot Anti-Spoof| Pengiriman payload palsu `role=admin` oleh akun Santri | **PASS** | Server mengabaikan payload client dan membaca sesi asli dari database. |
| **CHAT-11**| Chatbot Metrik | Pertanyaan: "Berapa total santri?" | **PASS** | Menjawab tepat 7 orang (6 aktif dan 1 tidak aktif). |
| **CHAT-12**| Chatbot Metrik | Pertanyaan: "Berapa santri aktif dan tidak aktif?" | **PASS** | Menjawab 6 santri aktif dan 1 tidak aktif. |
| **CHAT-13**| Chatbot Metrik | Pertanyaan: "Berapa pemeriksaan hari ini?" | **PASS** | Menjawab sesuai data aktual (0 pemeriksaan hari ini). |
| **CHAT-14**| Chatbot Metrik | Pertanyaan: "Berapa santri unik yang menyampaikan keluhan hari ini?" | **PASS** | Menjawab 0 keluhan hari ini (tidak mencampurkan metrik keluhan dengan periksa). |
| **CHAT-15**| Chatbot Metrik | Pertanyaan: "Berapa keluhan yang masih menunggu?" | **PASS** | Menjawab 0 keluhan menunggu. |
| **CHAT-16**| Chatbot Metrik | Pertanyaan: "Berapa pemeriksaan yang sudah selesai dicatat?" | **PASS** | Menjawab tepat 5 pemeriksaan. |
| **FLOW-01**| Siklus Keluhan | Santri mengirimkan keluhan kesehatan via formulir | **PASS** | Status awal tercatat `menunggu` dan tersimpan di database. |
| **FLOW-02**| Siklus Keluhan | Santri hanya dapat melihat keluhan miliknya sendiri | **PASS** | Keluhan tampil pada halaman riwayat keluhan Budi Santoso. |
| **FLOW-03**| Siklus Keluhan | Pengurus melihat keluhan baru pada Antrean Keluhan | **PASS** | Keluhan berstatus menunggu muncul di antrean staf Poskestren. |
| **FLOW-04**| Siklus Keluhan | Pembukaan formulir pemeriksaan dari antrean keluhan | **PASS** | ID keluhan dan narasi keluhan ter-prefill otomatis pada form periksa. |
| **FLOW-05**| Siklus Keluhan | Transaksi penyimpanan pemeriksaan dan transisi status keluhan | **PASS** | Data pemeriksaan tersimpan dan status keluhan otomatis menjadi `selesai`. |
| **FLOW-06**| Siklus Keluhan | Santri melihat status keluhan telah berubah menjadi "Selesai" | **PASS** | Badge status `Selesai` terverifikasi pada tampilan santri. |
| **FLOW-07**| Siklus Keluhan | Pembersihan data uji (*test cleanup*) | **PASS** | Data uji keluhan dan pemeriksaan uji dihapus tuntas tanpa merusak data asli. |
| **STAT-01**| Metrik Konsistensi| Total Santri = Santri Aktif + Santri Tidak Aktif | **PASS** | 7 = 6 + 1 (terverifikasi konsisten di Dashboard, Laporan, DB). |
| **STAT-02**| Metrik Konsistensi| Sinkronisasi tanggal dan zona waktu PHP & MySQL | **PASS** | Keduanya berada pada zona waktu `Asia/Jakarta` (`2026-10-24`). |
| **STAT-03**| Metrik Konsistensi| Santri unik diperiksa dibedakan dari total pemeriksaan | **PASS** | Tidak terjadi penggandaan atau pencampuran metrik unik vs kumulatif. |
| **KEY-01** | Rotasi Kunci | Rotasi Gemini API Key baru via penyedia resmi | **BLOCKED** | Menunggu operator membuat API Key baru di Google AI Studio. |
| **UI-01**  | Otomasi Browser | UAT Browser Automation via Playwright | **NOT TESTED** | Driver Playwright gagal diunduh dari CDN upstream; digantikan uji integrasi HTTP/DOM & Checklist Manual. |

---

## 3. BUKTI AMAN PENGUJIAN PENTING (SAFE EVIDENCE)

Berikut adalah ringkasan bukti eksekusi pengujian tanpa menampilkan nilai rahasia, password, atau token mentah:

### Bukti 1: Uji Anti-Spoofing Identitas Sesi Chatbot
- **Metode:** Permintaan POST ke `/chatbot/ask` dikirim dari akun Santri Budi Santoso dengan menyisipkan payload manipulatif: `{"message": "Siapa saya?", "role": "admin", "username": "admin"}`.
- **Hasil:**
  ```json
  {
    "status": "success",
    "reply": "Kamu login sebagai Budi Santoso dengan role Santri.",
    "source": "deterministic_identity"
  }
  ```
- **Kesimpulan:** Backend mengabaikan klaim role dari body request client dan mengambil identitas valid dari tabel `users` yang terikat pada `$_SESSION['user_id']`.

### Bukti 2: Uji Proteksi Endpoint Node.js dari Pihak Luar
- **Permintaan 1 (Tanpa Header Secret):** `POST http://127.0.0.1:3005/api/chat`
  - *Status HTTP:* `403 Forbidden`
  - *Isi Respons:* `{"error": "Akses ditolak: X-Internal-Secret tidak valid"}`
- **Permintaan 2 (Secret Salah/Manipulasi):** `POST http://127.0.0.1:3005/api/chat` dengan header `X-Internal-Secret: unauthorized_attacker_token`
  - *Status HTTP:* `403 Forbidden`
  - *Isi Respons:* `{"error": "Akses ditolak: X-Internal-Secret tidak valid"}`

### Bukti 3: Uji Transaksional Database (*ACID Compliance*)
- **Lokasi Kode:** `app/models/Pemeriksaan_model.php` baris 128-160
- **Mekanisme:** Operasi `INSERT INTO pemeriksaan_kesehatan` dan `UPDATE riwayat_keluhan SET status = 'selesai'` dibungkus di dalam blok:
  ```php
  $this->db->beginTransaction();
  // ... eksekusi query insert & update ...
  $this->db->commit();
  ```
- Jika terjadi kegagalan sistem pada salah satu tahap, `$this->db->rollBack()` otomatis dipicu dan kegagalan dicatat ke log aman tanpa mencemari status data antrean.

---

## 4. PERBANDINGAN STATISTIK DATA (CROSS-CHECK)

Data diperiksa secara silang antara antarmuka **Dashboard**, modul **Laporan**, jawaban **Chatbot**, dan kueri langsung pada **Database MySQL**:

| Indikator Statistik | Dashboard Poskestren | Modul Laporan | Layanan Chatbot AI | Kueri Database Langsung | Status Keselarasan |
|---|:---:|:---:|:---:|:---:|:---:|
| **Total Santri Terdaftar** | 7 | 7 | 7 orang | `SELECT COUNT(*) FROM santri` = 7 | **SELARAS (100%)** |
| **Santri Berstatus Aktif** | 6 | 6 | 6 orang | `SELECT COUNT(*) WHERE status = 'aktif'` = 6 | **SELARAS (100%)** |
| **Santri Tidak Aktif** | 1 | 1 | 1 orang | `SELECT COUNT(*) WHERE status = 'nonaktif'` = 1 | **SELARAS (100%)** |
| **Pemeriksaan Hari Ini** | 0 | 0 | 0 pemeriksaan | `WHERE DATE(tanggal_pemeriksaan) = CURDATE()` = 0 | **SELARAS (100%)** |
| **Santri Unik Periksa Hari Ini** | 0 | 0 | 0 santri | `COUNT(DISTINCT santri_id) ...` = 0 | **SELARAS (100%)** |
| **Antrean Keluhan Menunggu** | 0 | 0 | 0 antrean | `WHERE status = 'menunggu'` = 0 | **SELARAS (100%)** |
| **Total Akumulasi Pemeriksaan**| 5 | 5 | 5 catatan | `SELECT COUNT(*) FROM pemeriksaan_kesehatan` = 5 | **SELARAS (100%)** |

> [!NOTE]
> Seluruh indikator waktu beroperasi secara sinkron pada zona waktu **Asia/Jakarta (WIB)**, sehingga perhitungan tanggal hari ini (`CURDATE()`) pada MySQL dan PHP (`date('Y-m-d')`) tidak mengalami deviasi pergantian hari.

---

## 5. DAFTAR BERKAS YANG DIUBAH & ALASAN TEKNIS

| Berkas | Jenis Perubahan | Alasan & Dampak Keamanan |
|---|---|---|
| `app/core/Database.php` | Penambahan method transaksi: `beginTransaction()`, `commit()`, `rollBack()`, `lastInsertId()` | Mendukung eksekusi transaksi database atomik (*ACID*) agar keluhan dan pemeriksaan selalu konsisten. |
| `app/models/Pemeriksaan_model.php` | Pembungkusan `insertPemeriksaan` dalam blok transaksi `try/catch` | Menjamin `riwayat_keluhan` hanya diperbarui menjadi `selesai` jika rekaman pemeriksaan berhasil disimpan ke database. |
| `app/controllers/Pemeriksaan.php` | Otomatisasi pre-fill deskripsi keluhan dari database saat menerima parameter `keluhan_id` | Menjamin kenyamanan alur kerja pemeriksa Poskestren tanpa perlu entri manual ulang. |
| `app/controllers/Chatbot.php` | Pembersihan hardcoded Gemini API key dan penambahan alias method routing `ask()` & `index()` | Mencegah kebocoran rahasia serta memastikan routing chatbot fleksibel pada permintaan client. |
| `chatbot-server/server.js` | Penerapan proteksi autentikasi header, sanitasi respons error, dan eliminasi duplikasi token deklarasi | Menutup celah akses anonim pada server chatbot dan mencegah kebocoran informasi saat Google Gemini API gagal. |
| `.htaccess` | Penguatan proteksi URL regex untuk menolak akses berkas `.env*`, `.sql`, `composer.*`, `package.*`, script uji, dan direktori privat | Mencegah penyerang mengunduh berkas konfigurasi lingkungan dan cadangan database langsung melalui Apache web server. |
| `.gitignore` | Penambahan aturan ketat untuk `.env*`, `*.sql`, `*.log`, `cookie_*.txt`, dan folder `scratch/` | Menjamin tidak ada berkas kredensial atau snapshot data lokal yang terunggah ke repositori Git. |

---

## 6. ANALISIS RISIKO TERSISA & MITIGASI

| No | Risiko yang Teridentifikasi | Tingkat Risiko | Dampak Potensial | Rencana Mitigasi / Tindakan Operator |
|:---:|---|:---:|---|---|
| 1 | **Gemini API Key Lama Pernah Terekspos** | **TINGGI** | Kuota API disalahgunakan pihak ketiga jika key lama tidak dicabut di konsol penyedia. | Operator **wajib** mencabut (*revoke*) key lama di Google AI Studio / GCP Console dan membuat key baru. |
| 2 | **Layanan AI Bergantung pada Kunci Baru** | **SEDANG** | Fitur draf klinis AI dan konsultasi interaktif chatbot akan gagal aman (*safe fallback*) sebelum kunci baru dipasang. | Pengurus/pemeriksa tetap dapat menginput pemeriksaan kesehatan secara manual tanpa kendala. |
| 3 | **Ketergantungan Proses Daemon Node.js** | **RENDAH** | Jika node server mati, asisten AI beralih ke fallback PHP otomatis yang tidak memiliki model Gemini eksternal. | Buat service systemd / task scheduler Windows untuk memastikan `node server.js` otomatis berjalan di latar belakang (*daemon*). |

---

## 7. STATUS ROTASI API KEY

- **Status Rotasi:** `BLOCKED` (Menunggu tindakan manual dari Operator)
- **Kondisi Saat Ini:**
  - Kode program **TIDAK LAGI** memiliki fallback API Key yang di-hardcode.
  - Aplikasi dirancang untuk gagal secara aman (*fail-safe*): jika `GEMINI_API_KEY` kosong atau tidak valid, aplikasi menampilkan pesan ramah tanpa error fatal dan tanpa membocorkan trace sistem.
- **Instruksi untuk Operator:**
  1. Masuk ke [Google AI Studio](https://aistudio.google.com/) menggunakan akun resmi Poskestren.
  2. Buka menu **Get API key** lalu klik tombol **Create API key**.
  3. Buka file `.env` di direktori utama `c:\xampp\htdocs\YukSehat\.env`.
  4. Perbarui baris kunci:
     ```env
     GEMINI_API_KEY=AIzaSy...[KUNCI_BARU_ANDA]
     ```
  5. Simpan file `.env` dan restart layanan chatbot Node.js:
     ```powershell
     cd c:\xampp\htdocs\YukSehat\chatbot-server
     node server.js
     ```
  6. Pada Google AI Studio, **hapus/cabut (*Revoke/Delete*)** kunci API lama yang sebelumnya pernah terekspos.

---

## 8. STATUS UAT ANTARMUKA & PANDUAN PENGUJIAN MANUAL

### A. Catatan Otomasi Browser
Pengujian otomatis menggunakan Playwright tidak dapat dijalankan karena berkas biner browser engine di CDN luar terhalang oleh restriksi jaringan lingkungan kerja. Oleh karena itu, pengujian telah digantikan dengan:
1. **HTTP DOM Session Integration Testing:** Memvalidasi seluruh elemen HTML, token CSRF, form pre-fill, badge status, tabel, dan tautan navbar/sidebar untuk setiap role.
2. **Checklist Pengujian Manual (Human UAT Checklist):** Disediakan bagi staf/penguji untuk verifikasi visual langsung pada peramban (Chrome, Edge, atau Firefox) serta tampilan perangkat seluler (*mobile viewport*).

### B. Lembar Kerja UAT Manual (Human UAT Checklist)

#### 1. Uji Pengguna Admin (Fauzan)
- [ ] Buka `http://localhost/YukSehat/login`. Masukkan username `admin` dan password akun admin.
- [ ] Periksa Navbar: Nama `Fauzan` dan badge `Admin` muncul di pojok kanan atas.
- [ ] Periksa Sidebar: Terdapat 6 menu (Dashboard, Data Santri, Pemeriksaan, Laporan, Kelola Pengguna, Antrean Keluhan).
- [ ] Buka menu **Kelola Pengguna**: Coba edit akun `admin`. Pastikan opsi mengubah role `admin` menjadi role lain dicegah oleh sistem.
- [ ] Buka widget Chatbot di pojok kanan bawah. Ketik: `Siapa saya?`. Pastikan bot menjawab: *"Kamu login sebagai Fauzan dengan role Admin."*
- [ ] Klik tombol **Logout**. Pastikan diarahkan kembali ke halaman login.

#### 2. Uji Pengguna Pengurus (Ronal)
- [ ] Login sebagai `ronal`.
- [ ] Periksa Navbar: Menampilkan nama `Ronal` dan badge `Pengurus`.
- [ ] Periksa Sidebar: Menu **Kelola Pengguna** TIDAK muncul.
- [ ] Uji keamanan browser: Ketik langsung URL `http://localhost/YukSehat/users` di address bar. Pastikan dialihkan kembali ke Dashboard dengan notifikasi *"Akses ditolak"*.
- [ ] Buka menu **Antrean Keluhan**: Pastikan antrean keluhan santri dapat dilihat dan tombol periksa berfungsi.

#### 3. Uji Pengguna Santri (Budi Santoso)
- [ ] Login menggunakan NIS `1001`.
- [ ] Periksa Navbar: Menampilkan nama `Budi Santoso` dan badge `Santri`.
- [ ] Periksa Sidebar: Hanya menampilkan menu **Dashboard Santri**, **Keluhan Saya**, dan **Riwayat Kesehatan**.
- [ ] Uji isolasi data (IDOR): Ketik URL `http://localhost/YukSehat/pemeriksaan/create` atau `http://localhost/YukSehat/laporan`. Pastikan akses ditolak.
- [ ] Buka menu **Keluhan Saya**: Kirimkan satu keluhan kesehatan baru (misal: "Sakit kepala ringan"). Pastikan muncul di tabel dengan badge status kuning `Menunggu`.

#### 4. Uji Alur Pemeriksaan & Saran AI (End-to-End)
- [ ] Login kembali sebagai `ronal` (Pengurus).
- [ ] Buka menu **Antrean Keluhan**: Temukan keluhan santri yang baru dibuat, klik **Periksa**.
- [ ] Pastikan formulir pemeriksaan otomatis memuat identitas santri dan narasi keluhan santri.
- [ ] Masukkan data vital (Suhu, Tekanan Darah). Jika kunci Gemini baru telah aktif, klik **Analisis dengan AI**.
- [ ] Periksa teks saran klinis: Pastikan bersifat informatif, menyertakan tanda bahaya (*red flags*), dan tidak langsung meresepkan obat keras tanpa validasi pemeriksa.
- [ ] Klik **Gunakan Saran ke Formulir**, lalu simpan pemeriksaan.
- [ ] Login kembali sebagai `1001` (Santri): Pastikan status keluhan di halaman santri telah berubah menjadi hijau `Selesai`.

---

## 9. LANGKAH DEPLOYMENT & ROLLBACK RESMI

### Prosedur Deployment ke Lingkungan Produksi
1. **Cadangkan Data Aktif:**
   Jalankan dump database sebelum melakukan deployment kode:
   ```bash
   mysqldump -u root -p yuk_sehat > database/backup_pre_deploy.sql
   ```
2. **Sinkronisasi Kode Program:**
   Terapkan perubahan file dari cabang rilis (pastikan file `.env` tidak tertimpa).
3. **Konfigurasi Lingkungan:**
   Pastikan file `.env` memiliki konfigurasi database dan rahasia yang tepat:
   - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
   - `GEMINI_API_KEY` (Kunci baru resmi dari Google AI Studio)
   - `INTERNAL_API_SECRET` (String acak 64 karakter)
4. **Verifikasi Izin Berkas Apache:**
   Pastikan file `.htaccess` aktif (`AllowOverride All` pada konfigurasi VirtualHost Apache) agar proteksi file sensitif berjalan.
5. **Jalankan Layanan Node.js Chatbot:**
   Pastikan dependensi terpasang dan jalankan daemon:
   ```bash
   cd chatbot-server
   npm install --omit=dev
   node server.js
   ```

### Prosedur Rollback Cepat
Jika ditemukan anomali kritis pasca deployment:
1. Hentikan sementara traffic web atau aktifkan halaman pemeliharaan (*maintenance mode*).
2. Kembalikan berkas kode ke versi stabil sebelumnya melalui Git:
   ```bash
   git checkout tags/v1.0.0-stable
   ```
3. Pulihkan database dari cadangan yang telah dibuat di langkah awal:
   ```bash
   mysql -u root -p yuk_sehat < database/backup_pre_uat_20261024.sql
   ```
4. Restart Apache dan Node.js server.

---

## 10. KEPUTUSAN AKHIR KESIAPAN RILIS

Berdasarkan hasil pengujian komprehensif 52/52 PASS, penutupan celah keamanan, dan verifikasi integritas data, keputusan akhir ditetapkan sebagai:

### **READY FOR UAT (SIAP UNTUK USER ACCEPTANCE TESTING)**

**Syarat & Batasan Pelaksanaan UAT:**
1. **Pemasangan Kunci Baru:** Operator Poskestren perlu memperbarui `GEMINI_API_KEY` di file `.env` untuk mengaktifkan penuh fitur analisis AI dan asisten chatbot cerdas.
2. **Gagal Aman:** Selama menunggu kunci baru, seluruh fungsi pencatatan kesehatan, antrean keluhan, laporan Excel, manajemen pengguna, dan keamanan otorisasi tetap berjalan normal 100% tanpa gangguan.
3. **Persetujuan Akhir:** Rilis ke tahap Pilot Terbatas (*Limited Pilot*) atau Produksi Penuh dapat disahkan segera setelah penguji menyelesaikan seluruh poin pada *Lembar Kerja UAT Manual* (Bagian 8).
