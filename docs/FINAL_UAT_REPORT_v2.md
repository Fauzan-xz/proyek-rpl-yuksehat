# LAPORAN UAT AKHIR — YUKSEHAT
**Aplikasi:** Poskestren YukSehat (Native PHP MVC + MySQL + Node.js)  
**Tanggal Laporan:** 24 Oktober 2026, 01:43 WIB  
**Basis:** Kelanjutan `docs/FINAL_UAT_RELEASE_REPORT.md` & `docs/UAT_CONTINUATION_REPORT.md`  

---

## RINGKASAN EKSEKUTIF

| Item | Status |
|---|:---:|
| Regresi Otomatis (52 skenario) | ✅ **52/52 PASS** |
| Gemini API fungsional (PHP & Node.js) | ✅ **PASS** |
| Proteksi file sensitif via HTTP | ✅ **PASS** |
| Konsistensi `INTERNAL_API_SECRET` | ✅ **PASS** |
| Integritas & uji restorasi backup | ✅ **PASS** |
| `RONAL_PASSWORD` di `.env` | ⚠️ **PERLU TINDAKAN** (seeder aktif — jangan hapus dulu) |
| Rotasi key Gemini lama | 🔴 **BLOCKED** (menunggu operator) |
| UAT visual peramban | ⬜ **NOT TESTED** (Playwright CDN tidak tersedia) |

**Keputusan:** Aplikasi **BELUM dapat dideklarasikan siap produksi penuh** hingga blocker keamanan diselesaikan dan operator melengkapi UAT visual manual.

---

## 1. VERIFIKASI GEMINI API (Diperbarui 24 Okt 2026, 01:42 WIB)

### PHP — Status
- `GEMINI_API_KEY` dibaca dari `.env` via `config/config.php` → `loadEnv()`
- Panjang key: 53 karakter — ✅ SET
- Uji live (sanitized, tanpa mencetak key): **HTTP 200**, model merespons `"Jakarta"` ✅

### Node.js — Status
- Key dibaca dari `loadEnvFile(path.resolve(__dirname, '../.env'))` (baris 39, `server.js`)
- Log daemon `task-706` menunjukkan permintaan nyata berhasil diproses: `HTTP 200` ✅
- Status endpoint `/api/status`: `{"status":"online","model":"gemini-flash-lite-latest"}` ✅

**Kesimpulan:** Kedua layanan membaca key dari sumber yang sama (`.env` di root proyek) dan berfungsi normal. Nilai key tidak dicetak sesuai instruksi.

---

## 2. KEAMANAN KONFIGURASI (Diperbarui 24 Okt 2026, 01:33 WIB)

| Item | Hasil | Bukti |
|---|:---:|---|
| `GET /.env` via HTTP | ✅ **403 Forbidden** | Apache `.htaccess` aktif |
| `GET /database/*.sql` via HTTP | ✅ **403 Forbidden** | Apache `.htaccess` aktif |
| `GET /scratch/*.php` via HTTP | ✅ **403 Forbidden** | Apache `.htaccess` aktif |
| `.gitignore` mencakup `.env`, `*.sql`, `scratch/` | ✅ **PASS** | 7 entri dikonfirmasi |
| 0 hardcoded API key di source code PHP/JS | ✅ **PASS** | ripgrep: 0 hasil di luar regex test |
| `INTERNAL_API_SECRET` tersedia PHP & Node.js | ✅ **PASS** | PHP length=33, Node.js dari `.env` sama |
| Node.js `/api/chat` tolak tanpa header secret | ✅ **403** | Diuji via HTTP request |
| PHP `/chatbot/internal_query` tolak tanpa secret | ✅ **403** | Diuji via HTTP request |

---

## 3. STATUS `RONAL_PASSWORD` DI `.ENV`

**Temuan:** Baris `RONAL_PASSWORD=...` tersedia di `.env`.

**Analisis penggunaan:**
- `database/run_seed.php` → **TIDAK menggunakan** `RONAL_PASSWORD`. Menggunakan `password_hash('pengurus123', ...)` secara langsung.
- `setup_admin.php` → **AKTIF menggunakan** `RONAL_PASSWORD` dari `$_ENV` untuk:
  - Sinkronisasi password akun Ronal saat setup dijalankan ulang
  - Inisialisasi password akun Ronal jika akun belum ada

**Kesimpulan:**
- `RONAL_PASSWORD` **masih diperlukan** oleh `setup_admin.php` jika skrip tersebut akan dijalankan kembali (misal saat reinstall atau reset akun).
- **Jangan hapus** dari `.env` sampai ada keputusan dari operator bahwa `setup_admin.php` tidak akan dijalankan lagi.
- File `.env` tidak dapat diakses via HTTP (HTTP 403) dan dikecualikan dari Git — risiko eksposur rendah.

> **Rekomendasi untuk Operator:** Jika siklus setup selesai dan `setup_admin.php` tidak akan dijalankan lagi di lingkungan produksi, hapus baris `RONAL_PASSWORD` dari `.env` dan blokir akses publik ke `setup_admin.php` melalui `.htaccess` atau hapus file tersebut.

---

## 4. INTEGRITAS & UJI RESTORASI BACKUP

### Informasi Backup
| Item | Nilai |
|---|---|
| Nama file | `database/backup_pre_uat_20261024.sql` |
| Ukuran | 40.020 bytes (tidak kosong) |
| Dibuat | 24 Oktober 2026, 01:19 WIB |
| Proteksi HTTP | HTTP 403 Forbidden ✅ |

### Uji Restorasi ke Database Terisolasi
Database `yuk_sehat_restore_test` dibuat secara terpisah, backup dipulihkan ke dalamnya, lalu dihapus setelah verifikasi:

| Pemeriksaan | Hasil DB Aktif (`yuk_sehat`) | Hasil DB Restore Test |
|---|:---:|:---:|
| Jumlah Tabel | 5 | 5 ✅ |
| Jumlah users | 10 | 10 ✅ |
| Jumlah santri | 7 | 7 ✅ |
| Jumlah pemeriksaan_kesehatan | 5 | 5 ✅ |

**Kesimpulan:** Backup valid, dapat dipulihkan, dan konsisten dengan database aktif saat backup dibuat. DB aktif (`yuk_sehat`) **tidak disentuh** — hanya DB test terisolasi yang digunakan, kemudian dihapus bersih.

---

## 5. REGRESI OTOMATIS (Dijalankan Ulang 24 Okt 2026, 01:42 WIB)

**Hasil:** ✅ **52/52 LULUS — 100% PASS** (ketiga kali berturut-turut)

| Section | Skenario | Status |
|---|---|:---:|
| **§1 Role & Akses** | Login Admin, Navbar, Sidebar 6 menu, Direct URL Admin | ✅ 5 PASS |
| **§1 Role & Akses** | Login Pengurus, Navbar, Sidebar tanpa `/users`, Block `/users` | ✅ 5 PASS |
| **§1 Role & Akses** | Login Santri, Navbar, Sidebar 3 menu, Block `/users`, `/pemeriksaan/create`, `/laporan`, IDOR | ✅ 8 PASS |
| **§2 Chatbot Identitas** | "Siapa saya?", "Apa role saya?", "Apa saja..." (Admin, Pengurus, Santri) | ✅ 9 PASS |
| **§2 Chatbot Data** | Statistik real-time: Total/Aktif/Tidak Aktif Santri, Pemeriksaan, Keluhan | ✅ 6 PASS |
| **§3 Lifecycle Keluhan** | Submit → Antrean → Form prefill → Simpan → Selesai → Santri lihat → Cleanup | ✅ 7 PASS |
| **§4 Keamanan** | Anti-spoof, Node 403, PHP 403, CSRF, BCrypt, Hardcoded key, ENV load | ✅ 8 PASS |
| **§5 Statistik** | Total=Aktif+Nonaktif, Periksa hari ini, Santri unik, Timezone sync | ✅ 4 PASS |

---

## 6. UAT VISUAL PERAMBAN (CHECKLIST UNTUK OPERATOR)

> ⬜ Seluruh item di bawah ini berstatus **NOT TESTED** karena Playwright browser driver tidak dapat diunduh dari CDN (HTTP 404). Operator perlu menyelesaikan checklist ini secara manual di peramban fisik.

### 6.1 Role Admin (Fauzan)

| # | Skenario | Status | Langkah Manual |
|:---:|---|:---:|---|
| A1 | Buka `http://localhost/YukSehat/login` — form login tampil | ⬜ NOT TESTED | Buka URL di Chrome/Edge/Firefox |
| A2 | Login sebagai admin → redirect ke `/dashboard` | ⬜ NOT TESTED | Masukkan username dan password admin |
| A3 | Navbar: nama "Fauzan" dan badge "Admin" terlihat | ⬜ NOT TESTED | Periksa pojok kanan atas halaman |
| A4 | Sidebar: 6 menu tampil (Dashboard, Santri, Pemeriksaan, Laporan, Kelola Pengguna, Antrean) | ⬜ NOT TESTED | Periksa panel navigasi kiri |
| A5 | Akses `/users` via URL langsung → halaman terbuka (bukan diblokir) | ⬜ NOT TESTED | Ketik URL langsung di address bar |
| A6 | Akses `/laporan` → halaman laporan dengan filter terbuka | ⬜ NOT TESTED | Klik menu Laporan di sidebar |
| A7 | Ekspor Excel → file `.xlsx` terunduh | ⬜ NOT TESTED | Klik tombol Ekspor Excel |
| A8 | Chatbot: ketik "Siapa saya?" → jawab "Fauzan" dan "Admin" | ⬜ NOT TESTED | Klik icon chat pojok kanan bawah |
| A9 | Chatbot: ketik "Berapa total santri?" → jawab angka konsisten dashboard | ⬜ NOT TESTED | Kirim pesan di widget chatbot |
| A10 | Logout → redirect ke `/login` | ⬜ NOT TESTED | Klik tombol Logout |

### 6.2 Role Pengurus (Ronal)

| # | Skenario | Status | Langkah Manual |
|:---:|---|:---:|---|
| P1 | Login sebagai ronal → redirect ke `/dashboard` | ⬜ NOT TESTED | Login dengan akun pengurus |
| P2 | Navbar: nama "Ronal" dan badge "Pengurus" | ⬜ NOT TESTED | Periksa pojok kanan atas |
| P3 | Sidebar: menu "Kelola Pengguna" **tidak muncul** | ⬜ NOT TESTED | Periksa panel navigasi kiri |
| P4 | Akses `/users` langsung via URL → halaman ditolak dengan pesan akses | ⬜ NOT TESTED | Ketik URL di address bar |
| P5 | Buka Antrean Keluhan → keluhan santri tampil di tabel | ⬜ NOT TESTED | Klik menu Antrean Keluhan |
| P6 | Klik Periksa → form pemeriksaan prefill nama santri & keluhan | ⬜ NOT TESTED | Klik tombol Periksa pada baris keluhan |
| P7 | Analisis AI → saran klinis muncul dengan disclaimer medis | ⬜ NOT TESTED | Klik Analisis dengan AI di form |
| P8 | Gunakan Saran → kolom form terisi otomatis dari saran AI | ⬜ NOT TESTED | Klik tombol Gunakan Saran ke Formulir |
| P9 | Simpan pemeriksaan → status keluhan santri berubah "Selesai" | ⬜ NOT TESTED | Klik Simpan di form pemeriksaan |
| P10 | Logout | ⬜ NOT TESTED | Klik tombol Logout |

### 6.3 Role Santri (Budi Santoso / NIS 1001)

| # | Skenario | Status | Langkah Manual |
|:---:|---|:---:|---|
| S1 | Login sebagai `1001` → redirect ke `/dashboard` | ⬜ NOT TESTED | Login dengan NIS santri |
| S2 | Navbar: nama "Budi Santoso" dan badge "Santri" | ⬜ NOT TESTED | Periksa pojok kanan atas |
| S3 | Sidebar: hanya 3 menu (Dashboard, Keluhan Saya, Riwayat Kesehatan) | ⬜ NOT TESTED | Periksa panel navigasi kiri |
| S4 | Akses `/pemeriksaan/create` via URL → diblokir + pesan akses | ⬜ NOT TESTED | Ketik URL di address bar |
| S5 | Akses `/laporan` via URL → diblokir + pesan akses | ⬜ NOT TESTED | Ketik URL di address bar |
| S6 | Akses `/users` via URL → diblokir + pesan akses | ⬜ NOT TESTED | Ketik URL di address bar |
| S7 | Kirim keluhan baru → muncul badge "Menunggu" di riwayat | ⬜ NOT TESTED | Buka Keluhan Saya → isi form keluhan |
| S8 | Riwayat Kesehatan → hanya menampilkan data Budi Santoso | ⬜ NOT TESTED | Klik menu Riwayat Kesehatan |
| S9 | Chatbot: "Siapa saya?" → sebut "Budi Santoso" dan "Santri" | ⬜ NOT TESTED | Buka widget chatbot |
| S10 | Chatbot: "Berapa total santri?" → ditolak (data staf) | ⬜ NOT TESTED | Kirim di widget chatbot |
| S11 | Logout | ⬜ NOT TESTED | Klik tombol Logout |

### 6.4 Tampilan Mobile / Responsif

| # | Skenario | Status | Langkah Manual |
|:---:|---|:---:|---|
| M1 | Dashboard tidak overflow di layar 375px (mobile) | ⬜ NOT TESTED | F12 DevTools → Toggle Device Toolbar |
| M2 | Sidebar lipat (collapsible) berfungsi di mobile | ⬜ NOT TESTED | Periksa hamburger menu |

---

## 7. INSTRUKSI ROTASI KEY GOOGLE GEMINI (Menunggu Konfirmasi Operator)

> 🔴 **BLOCKER KEAMANAN:** Key Gemini yang saat ini aktif pernah tersimpan di riwayat Git. Risiko penyalahgunaan kuota oleh pihak yang memiliki akses ke riwayat repositori tetap ada hingga key dicabut.

**Sistem tidak akan:**
- Mencabut key secara otomatis
- Mengklaim key telah dicabut tanpa konfirmasi operator
- Membuat atau mengarang key baru

**Langkah yang perlu dilakukan operator:**

1. **Buka** [https://aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) menggunakan akun Google resmi Poskestren.

2. **Identifikasi** key dengan prefix `AQ.Ab8RN...` (prefix ini tidak rahasia — nilai lengkap tidak dicetak di laporan ini).

3. **Klik ikon hapus** (🗑️ Delete/Revoke) pada key tersebut → konfirmasi penghapusan.

4. **Buat key baru:** Klik "Create API key" → pilih project → salin key baru.

5. **Perbarui `.env`:**
   ```
   GEMINI_API_KEY=[KEY_BARU_DARI_GOOGLE]
   ```

6. **Restart Node.js** (hentikan daemon lama, jalankan ulang):
   ```powershell
   cd c:\xampp\htdocs\YukSehat\chatbot-server
   node server.js
   ```

7. **Verifikasi** (jalankan skrip aman tanpa mencetak key):
   ```powershell
   C:\xampp\php\php.exe scratch/test_gemini_safe.php
   ```
   Output yang diharapkan: `[PASS] Gemini API response OK.`

8. **Konfirmasikan** ke tim engineering bahwa langkah 3 (revoke) telah selesai dilakukan.

---

## 8. TABEL STATUS AKHIR LENGKAP

| ID | Kategori | Skenario | Status | Catatan |
|---|---|---|:---:|---|
| SEC-01 | Keamanan | 0 hardcoded API key di source code | ✅ PASS | ripgrep bersih |
| SEC-02 | Keamanan | Proteksi HTTP file sensitif (`.env`, `.sql`, `scratch/`) | ✅ PASS | HTTP 403 terkonfirmasi |
| SEC-03 | Keamanan | ENV loading PHP & Node.js dari `.env` | ✅ PASS | Kedua layanan aktif |
| SEC-04 | Keamanan | BCrypt hash semua password | ✅ PASS | 10/10 akun `$2y$` |
| SEC-05 | Keamanan | CSRF validation pada form POST | ✅ PASS | Request tanpa token ditolak |
| SEC-06 | Keamanan | Node.js endpoint tolak tanpa secret | ✅ PASS | HTTP 403 |
| SEC-07 | Keamanan | PHP endpoint tolak tanpa secret | ✅ PASS | HTTP 403 |
| SEC-08 | Keamanan | Anti-spoofing chatbot identitas | ✅ PASS | Payload palsu diabaikan |
| SEC-09 | Keamanan | Anti-lockout admin | ✅ PASS | Role self-demotion diblokir |
| SEC-10 | Keamanan | IDOR protection (santri/edit/{id_lain}) | ✅ PASS | Akses diblokir |
| API-01 | API | Gemini API PHP live test | ✅ PASS | HTTP 200, respons valid |
| API-02 | API | Gemini API Node.js live test | ✅ PASS | Log: HTTP 200, ms < 2000 |
| API-03 | API | Rotasi key lama | 🔴 BLOCKED | Menunggu operator revoke di Google Console |
| DB-01 | Database | Backup tidak kosong | ✅ PASS | 40.020 bytes |
| DB-02 | Database | Backup tidak dapat diakses via HTTP | ✅ PASS | HTTP 403 |
| DB-03 | Database | Uji restorasi backup (DB terisolasi) | ✅ PASS | 5 tabel, 10 users, 7 santri, 5 pemeriksaan |
| ENV-01 | Konfigurasi | `RONAL_PASSWORD` masih diperlukan seeder | ⚠️ CATATAN | `setup_admin.php` masih aktif menggunakannya |
| REG-01~52 | Regresi | 52 skenario end-to-end | ✅ PASS | 52/52 LULUS (3× berturut-turut) |
| UAT-A1~A10 | UAT Visual | Admin (Fauzan) — 10 skenario | ⬜ NOT TESTED | Playwright CDN 404; checklist manual tersedia |
| UAT-P1~P10 | UAT Visual | Pengurus (Ronal) — 10 skenario | ⬜ NOT TESTED | Checklist manual tersedia di §6.2 |
| UAT-S1~S11 | UAT Visual | Santri (Budi Santoso) — 11 skenario | ⬜ NOT TESTED | Checklist manual tersedia di §6.3 |
| UAT-M1~M2 | UAT Visual | Tampilan mobile responsif — 2 skenario | ⬜ NOT TESTED | Checklist manual tersedia di §6.4 |

---

## 9. KEPUTUSAN KESIAPAN RILIS

### 🔴 BELUM SIAP PRODUKSI PENUH

**Dua kondisi masih harus dipenuhi sebelum deklarasi siap produksi:**

#### Blocker 1 — Wajib: Rotasi Key Gemini (Tindakan Operator)
> Ikuti langkah di §7. Setelah operator mengkonfirmasi pencabutan key lama dan memasukkan key baru, jalankan `scratch/test_gemini_safe.php` untuk verifikasi otomatis. Status akan berubah dari `BLOCKED` → `PASS`.

#### Blocker 2 — Disarankan: UAT Visual Manual (Tindakan Operator)
> Selesaikan checklist di §6 menggunakan peramban fisik (Chrome/Edge/Firefox). Berikan tanda centang (✅) pada setiap item yang diverifikasi secara langsung. Tanpa ini, pengujian antarmuka belum pernah dilakukan oleh manusia.

### Setelah kedua kondisi terpenuhi:
Aplikasi YukSehat dapat dideklarasikan **SIAP PRODUKSI** dengan basis:
- 52/52 regresi otomatis LULUS
- API Gemini aktif dan terverifikasi aman
- Backup database valid dan dapat dipulihkan
- Key lama telah dicabut
- UAT visual operator telah selesai
