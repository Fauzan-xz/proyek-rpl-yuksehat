# LAPORAN UAT LANJUTAN — YUKSEHAT
**Tanggal:** 24 Oktober 2026 | **Waktu:** 01:34 WIB  
**Dasar:** Kelanjutan dari `docs/FINAL_UAT_RELEASE_REPORT.md` (52/52 PASS)  
**Tujuan:** Verifikasi konfigurasi Gemini API, keamanan, backup, dan UAT manual lengkap

---

## RINGKASAN CEPAT

| Area | Status | Detail |
|---|:---:|---|
| Gemini API Key (PHP) | ✅ PASS | Tersedia, length=53, response HTTP 200 dari Gemini |
| Gemini API Key (Node.js) | ✅ PASS | Dimuat dari `../.env`, digunakan oleh model utama |
| Internal API Secret (PHP) | ✅ PASS | Tersedia, length=33 |
| Internal API Secret (Node.js) | ✅ PASS | Dimuat dari `.env`, dipakai untuk autentikasi server-to-server |
| Konsistensi Secret PHP↔Node.js | ✅ PASS | Keduanya membaca dari sumber `.env` yang sama |
| Proteksi HTTP `.env` | ✅ PASS | `GET /.env` → HTTP 403 Forbidden |
| Proteksi HTTP `.sql` backup | ✅ PASS | `GET /database/backup_*.sql` → HTTP 403 Forbidden |
| Proteksi HTTP `scratch/` | ✅ PASS | `GET /scratch/*.php` → HTTP 403 Forbidden |
| `.gitignore` entri `.env` | ✅ PASS | `/.env`, `/.env.*`, `/chatbot-server/.env`, `*.sql` dikecualikan |
| Backup Database | ✅ PASS | `backup_pre_uat_20261024.sql` (40.020 bytes, 24 Okt 2026 01:19) |
| Regresi 52 Skenario | ✅ PASS | 52/52 LULUS pada audit ulang 24 Okt 2026 01:33 |
| Gemini AI Fungsional (live) | ✅ PASS | API aktif, model `gemini-flash-lite-latest` berhasil merespons |
| Rotasi Key Lama | 🔴 BLOCKED | Menunggu konfirmasi operator. Key lama belum dicabut. |
| UAT Visual Browser | ⬜ NOT TESTED | Playwright tidak tersedia; checklist manual tersedia di bawah |
| `RONAL_PASSWORD` di `.env` | ⚠️ CATATAN | Plaintext credential di `.env` hanya untuk seeder; sebaiknya dihapus setelah seed |

---

## 1. VERIFIKASI GEMINI API KEY

### 1.1 PHP — Konfigurasi
- **File sumber:** `config/config.php` → `loadEnv('../.env')` → `define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'])`
- **Status:** `GEMINI_API_KEY` terbaca oleh PHP, length=53

### 1.2 Node.js — Konfigurasi
- **File sumber:** `chatbot-server/server.js` baris 39 → `loadEnvFile(path.resolve(__dirname, '../.env'))`
- **Status:** Key dimuat secara mandiri dari file yang sama, model utama `gemini-flash-lite-latest` aktif

### 1.3 Uji API Langsung (Sanitasi Aman)
Tes menggunakan skrip `scratch/test_gemini_safe.php` yang tidak pernah mencetak nilai key:

```
[INFO] GEMINI_API_KEY tersedia. Length=53, Prefix=AQ.Ab8RN*****...
[HTTP] Status Code: 200
[PASS] Gemini API response OK.
[INFO] Model Response Preview: Jakarta
```

**Kesimpulan:** API key aktif dan berfungsi. Model merespons query sederhana dengan benar.

### 1.4 Verifikasi dari Log Node.js (task-706)
Log daemon Node.js menunjukkan permintaan nyata dari sesi pengguna aktif (Ronal/Fauzan):
```
[Gemini API] Model gemini-flash-lite-latest -> HTTP 200 (1216ms)
[Gemini API] Model gemini-flash-lite-latest -> HTTP 200 (1280ms)
```

---

## 2. KEAMANAN KONFIGURASI

### 2.1 Proteksi HTTP Apache
| Berkas/Direktori | Uji HTTP | Hasil |
|---|---|:---:|
| `/.env` | `GET http://localhost/YukSehat/.env` | **403 Forbidden** ✅ |
| `/database/backup_pre_uat_20261024.sql` | `GET http://localhost/YukSehat/database/*.sql` | **403 Forbidden** ✅ |
| `/scratch/test_gemini_safe.php` | `GET http://localhost/YukSehat/scratch/*.php` | **403 Forbidden** ✅ |

### 2.2 .gitignore
Entri yang dikonfirmasi hadir:
- `/.env` ✅
- `/.env.*` ✅
- `/chatbot-server/.env` ✅
- `*.sql` ✅
- `*.log` ✅
- `cookie_*.txt` ✅
- `scratch/` ✅

### 2.3 Hardcoded API Key di Source Code
Pencarian ripgrep di seluruh `*.php` dan `*.js` untuk pola `AIzaSy` dan `AQ.Ab8RN`:
- **Hasil:** 0 kejadian di luar pattern regex dalam test script (yang merupakan string pencarian, bukan key aktual)
- **Status:** BERSIH ✅

### 2.4 Temuan: `RONAL_PASSWORD` di `.env`
> ⚠️ **Catatan Keamanan (Non-Blocker)**  
> File `.env` mengandung baris `RONAL_PASSWORD=...` yang digunakan oleh skrip seeder `setup_admin.php`. Ini bukan blocker keamanan karena:
> - `.env` tidak dapat diakses via HTTP (HTTP 403 dikonfirmasi)
> - `.env` dikecualikan dari Git
> - Nilai ini adalah password aplikasi, bukan kredensial sistem kritis
>
> **Rekomendasi:** Setelah proses seeding selesai dan tidak ada skrip yang merujuk nilai ini di lingkungan produksi, hapus baris `RONAL_PASSWORD` dari `.env`.

---

## 3. STATUS BACKUP DATABASE

| Item | Nilai |
|---|---|
| Nama File | `backup_pre_uat_20261024.sql` |
| Lokasi | `c:\xampp\htdocs\YukSehat\database\` |
| Ukuran | 40.020 bytes (~40 KB) — **tidak kosong** |
| Waktu Dibuat | 24 Oktober 2026, 01:19 WIB |
| Aksesibilitas via HTTP | HTTP 403 Forbidden ✅ |

**Catatan:** Backup ini TIDAK akan dipulihkan ke database aktif tanpa persetujuan eksplisit operator.

---

## 4. PANDUAN ROTASI KEY LAMA (Menunggu Operator)

**Status:** `BLOCKED` — Sistem mencatat bahwa key yang lama pernah tersimpan dalam riwayat Git. Sistem tidak dapat mengkonfirmasi pencabutan key tanpa tindakan manual dari operator.

### Langkah Manual yang Perlu Dilakukan Operator:

**A. Mencabut Key Lama di Google AI Studio:**
1. Buka [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) menggunakan akun Google resmi Poskestren.
2. Temukan key dengan prefix yang sama dengan key di `.env` saat ini (`AQ.Ab8RN...`).
3. Klik ikon hapus/cabut (🗑️ Delete/Revoke) pada key tersebut.
4. Konfirmasikan pencabutan.

**B. Membuat Key Baru:**
1. Di halaman yang sama, klik **"Create API key"**.
2. Pilih project Google Cloud yang sesuai.
3. Salin key baru yang dihasilkan.

**C. Memperbarui `.env`:**
1. Buka `c:\xampp\htdocs\YukSehat\.env`
2. Ganti baris: `GEMINI_API_KEY=AQ.Ab8RN...` dengan key baru
3. Simpan file.

**D. Restart Node.js:**
```powershell
# Hentikan daemon yang berjalan, lalu:
cd c:\xampp\htdocs\YukSehat\chatbot-server
node server.js
```

**E. Verifikasi:**
```powershell
C:\xampp\php\php.exe scratch/test_gemini_safe.php
```
Output yang diharapkan: `[PASS] Gemini API response OK.`

> [!CAUTION]
> **Jangan klaim key lama telah dicabut** sebelum operator mengkonfirmasi secara eksplisit langkah A di atas telah dilakukan melalui konsol Google resmi.

---

## 5. HASIL UAT OTOMATIS — AUDIT ULANG (24 Okt 2026, 01:33 WIB)

Semua 52 skenario dieksekusi ulang menggunakan `scratch/run_comprehensive_audit.php`:

### 5.1 Section 1: Role, Navbar, Sidebar & Akses Langsung URL

| Skenario | Role | Status |
|---|---|:---:|
| Login dan redirect ke Dashboard | Admin (Fauzan) | ✅ PASS |
| Navbar menampilkan nama & role | Admin (Fauzan) | ✅ PASS |
| Sidebar lengkap 6 modul | Admin (Fauzan) | ✅ PASS |
| Akses langsung `/users` diizinkan | Admin (Fauzan) | ✅ PASS |
| Logout | Admin (Fauzan) | ✅ PASS |
| Login dan redirect ke Dashboard | Pengurus (Ronal) | ✅ PASS |
| Navbar menampilkan nama & role | Pengurus (Ronal) | ✅ PASS |
| Sidebar: menu `/users` tersembunyi | Pengurus (Ronal) | ✅ PASS |
| Akses langsung `/users` ditolak (redirect + flash) | Pengurus (Ronal) | ✅ PASS |
| Logout | Pengurus (Ronal) | ✅ PASS |
| Login dan redirect ke Dashboard | Santri (Budi Santoso) | ✅ PASS |
| Navbar menampilkan nama & role | Santri (Budi Santoso) | ✅ PASS |
| Sidebar: hanya 3 menu santri | Santri (Budi Santoso) | ✅ PASS |
| Akses langsung `/users` diblokir | Santri (Budi Santoso) | ✅ PASS |
| Akses langsung `/pemeriksaan/create` diblokir | Santri (Budi Santoso) | ✅ PASS |
| Akses langsung `/laporan` diblokir | Santri (Budi Santoso) | ✅ PASS |
| IDOR: akses `/santri/edit/{id_lain}` diblokir | Santri (Budi Santoso) | ✅ PASS |
| Logout | Santri (Budi Santoso) | ✅ PASS |

### 5.2 Section 2: Chatbot Identitas & Data Real-Time

| Pertanyaan | Role | Jawaban Bot | Status |
|---|---|---|:---:|
| "Siapa saya?" | Admin | "Kamu login sebagai Fauzan dengan role Admin." | ✅ PASS |
| "Apa role saya?" | Admin | "Kamu login dengan role Admin di YukSehat." | ✅ PASS |
| "Apa saja yang bisa saya lakukan?" | Admin | Daftar 6 kapabilitas administratif | ✅ PASS |
| "Siapa saya?" | Pengurus | "Kamu login sebagai Ronal dengan role Pengurus." | ✅ PASS |
| "Apa role saya?" | Pengurus | "Kamu login dengan role Pengurus di YukSehat." | ✅ PASS |
| "Apa saja yang bisa saya lakukan?" | Pengurus | Daftar kapabilitas operasional + batasan | ✅ PASS |
| "Siapa saya?" | Santri | "Kamu login sebagai Budi Santoso dengan role Santri." | ✅ PASS |
| "Apa role saya?" | Santri | "Kamu login dengan role Santri di YukSehat." | ✅ PASS |
| "Apa saja yang bisa saya lakukan?" | Santri | Panduan keluhan & riwayat kesehatan pribadi | ✅ PASS |
| "Berapa total santri?" | Admin | "7 orang (6 aktif, 1 tidak aktif)" | ✅ PASS |
| "Berapa santri aktif dan tidak aktif?" | Admin | "6 aktif, 1 tidak aktif" | ✅ PASS |
| "Berapa pemeriksaan hari ini?" | Admin | "0 pemeriksaan" (konsisten dengan DB) | ✅ PASS |
| "Berapa santri unik yang menyampaikan keluhan hari ini?" | Admin | "0 keluhan" | ✅ PASS |
| "Berapa keluhan yang masih menunggu?" | Admin | "0 antrean menunggu" | ✅ PASS |
| "Berapa pemeriksaan yang sudah selesai dicatat?" | Admin | "5 pemeriksaan" | ✅ PASS |

### 5.3 Section 3: Siklus Hidup Keluhan (End-to-End)

| Langkah | Deskripsi | Status |
|---|---|:---:|
| 1 | Santri submit keluhan (ID: 6, status: menunggu) | ✅ PASS |
| 2 | Santri pemilik melihat keluhan sendiri | ✅ PASS |
| 3 | Pengurus melihat keluhan di antrean | ✅ PASS |
| 4 | Form pemeriksaan pre-fill `keluhan_id` & narasi keluhan | ✅ PASS |
| 5 | Status keluhan otomatis berubah menjadi `selesai` | ✅ PASS |
| 6 | Santri melihat badge `Selesai` pada riwayat keluhan | ✅ PASS |
| 7 | Data uji dihapus bersih tanpa merusak data asli | ✅ PASS |

### 5.4 Section 4: Keamanan

| Skenario Keamanan | Status |
|---|:---:|
| Anti-Spoofing: payload palsu `role=admin` diabaikan oleh sesi server | ✅ PASS |
| Node.js menolak tanpa `X-Internal-Secret` → HTTP 403 | ✅ PASS |
| Node.js menolak secret palsu → HTTP 403 | ✅ PASS |
| PHP `/chatbot/internal_query` menolak tanpa secret → HTTP 403 | ✅ PASS |
| Validasi token CSRF pada form POST | ✅ PASS |
| Semua 10 akun password = BCrypt `$2y$` | ✅ PASS |
| 0 API key hardcoded di source code PHP/JS | ✅ PASS |
| `GEMINI_API_KEY` aktif di PHP & Node.js | ✅ PASS |

### 5.5 Section 5: Konsistensi Statistik

| Metrik | Dashboard | Laporan | Chatbot | DB | Status |
|---|:---:|:---:|:---:|:---:|:---:|
| Total Santri | 7 | 7 | 7 | 7 | ✅ PASS |
| Santri Aktif | 6 | 6 | 6 | 6 | ✅ PASS |
| Santri Tidak Aktif | 1 | 1 | 1 | 1 | ✅ PASS |
| Pemeriksaan Hari Ini | 0 | 0 | 0 | 0 | ✅ PASS |
| Santri Unik Periksa Hari Ini | 0 | — | 0 | 0 | ✅ PASS |
| Timezone PHP vs MySQL | 2026-10-24 | — | — | 2026-10-24 | ✅ PASS |

---

## 6. CHECKLIST UAT MANUAL (UNTUK OPERATOR)

> ⚠️ Pengujian berikut memerlukan verifikasi visual langsung di peramban (Chrome/Edge/Firefox).  
> Item yang belum diverifikasi visual ditandai `⬜ NOT TESTED`.

### 6.1 Admin (Fauzan)
- `⬜ NOT TESTED` Login via peramban, verifikasi halaman Dashboard muncul
- `⬜ NOT TESTED` Navbar: nama "Fauzan" dan badge "Admin" terlihat di pojok kanan atas
- `⬜ NOT TESTED` Sidebar: 6 menu terlihat (Dashboard, Data Santri, Pemeriksaan, Laporan, Kelola Pengguna, Antrean Keluhan)
- `⬜ NOT TESTED` Buka **Kelola Pengguna** → coba ubah role akun admin sendiri → pastikan ditolak
- `⬜ NOT TESTED` Buka **Laporan** → atur filter periode → verifikasi tabel terisi
- `⬜ NOT TESTED` Klik **Ekspor Excel** → verifikasi file `.xlsx` terunduh
- `⬜ NOT TESTED` Buka widget Chatbot → ketik "Siapa saya?" → verifikasi respons menyebut "Fauzan" dan "Admin"
- `⬜ NOT TESTED` Ketik "Berapa total santri?" → verifikasi angka konsisten dengan dashboard
- `⬜ NOT TESTED` Logout → verifikasi kembali ke halaman login

### 6.2 Pengurus (Ronal)
- `⬜ NOT TESTED` Login, verifikasi nama "Ronal" dan badge "Pengurus" di navbar
- `⬜ NOT TESTED` Sidebar: pastikan menu **Kelola Pengguna** tidak muncul
- `⬜ NOT TESTED` Ketik langsung URL `http://localhost/YukSehat/users` → verifikasi redirect + pesan "Akses ditolak"
- `⬜ NOT TESTED` Buka **Antrean Keluhan** → verifikasi keluhan santri tampil dengan status menunggu
- `⬜ NOT TESTED` Klik **Periksa** pada keluhan → verifikasi form pre-fill dengan data santri & keluhan
- `⬜ NOT TESTED` Isi data vital → klik **Analisis dengan AI** (jika key baru sudah aktif) → verifikasi saran klinis muncul dengan disclaimer
- `⬜ NOT TESTED` Klik **Gunakan Saran ke Formulir** → verifikasi kolom form terisi otomatis
- `⬜ NOT TESTED` Simpan pemeriksaan → verifikasi redirect ke halaman pemeriksaan/berhasil
- `⬜ NOT TESTED` Logout

### 6.3 Santri (Budi Santoso / NIS 1001)
- `⬜ NOT TESTED` Login, verifikasi nama "Budi Santoso" dan badge "Santri" di navbar
- `⬜ NOT TESTED` Sidebar: hanya 3 menu (Dashboard Santri, Keluhan Saya, Riwayat Kesehatan)
- `⬜ NOT TESTED` Ketik langsung URL `/pemeriksaan/create` → verifikasi diblokir
- `⬜ NOT TESTED` Ketik langsung URL `/laporan` → verifikasi diblokir
- `⬜ NOT TESTED` Buka **Keluhan Saya** → kirim keluhan baru → verifikasi muncul badge "Menunggu"
- `⬜ NOT TESTED` Buka **Riwayat Kesehatan** → verifikasi hanya menampilkan data Budi Santoso
- `⬜ NOT TESTED` Buka Chatbot → ketik "Siapa saya?" → verifikasi menyebut "Budi Santoso" dan "Santri"
- `⬜ NOT TESTED` Ketik "Berapa total santri?" → verifikasi chatbot menolak (hanya boleh admin/pengurus)
- `⬜ NOT TESTED` Logout → verifikasi kembali ke login

### 6.4 Tampilan Mobile/Responsif
- `⬜ NOT TESTED` Buka DevTools (F12) → pilih ukuran layar mobile (375×667px) → verifikasi sidebar tidak overlap konten

---

## 7. RINGKASAN STATUS AKHIR

| Kategori | Status |
|---|:---:|
| Gemini API berfungsi (PHP & Node.js) | ✅ PASS |
| Konfigurasi keamanan `INTERNAL_API_SECRET` | ✅ PASS |
| Proteksi HTTP file sensitif | ✅ PASS |
| `.gitignore` lengkap | ✅ PASS |
| Backup database tersedia & tidak dapat diakses via HTTP | ✅ PASS |
| 0 API key hardcoded di source code | ✅ PASS |
| Regresi otomatis 52/52 PASS | ✅ PASS |
| Rotasi key lama | 🔴 BLOCKED (menunggu operator) |
| UAT visual browser (admin, pengurus, santri) | ⬜ NOT TESTED |

---

## 8. KEPUTUSAN AKHIR

### KONDISI SAAT INI: **SIAP UAT — DENGAN 1 BLOCKER AKTIF**

Aplikasi berfungsi penuh termasuk AI chatbot aktif (API HTTP 200 terkonfirmasi). Semua pengujian otomatis lulus. Satu-satunya blocker aktif adalah:

> 🔴 **BLOCKER: Key Gemini lama perlu dicabut secara manual oleh operator di konsol Google.**  
> Selama key lama belum dicabut, risiko penyalahgunaan kuota API oleh pihak yang menyimpan riwayat Git tetap ada. Namun, key saat ini berfungsi dan aman dari sisi kode program.

**Rekomendasi:** Lakukan pencabutan key lama (Bagian 4 laporan ini), kemudian operator menyelesaikan Checklist UAT Manual (Bagian 6) sebelum deklarasi siap produksi penuh.
