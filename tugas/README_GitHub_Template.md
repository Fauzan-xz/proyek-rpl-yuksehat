# YukSehat: Sistem Informasi Poskestren Berbasis Web dengan Dual-Engine AI & WhatsApp Gateway

[![YukSehat CI/CD Pipeline](https://github.com/[USERNAME]/YukSehat/actions/workflows/ci.yml/badge.svg)](https://github.com/[USERNAME]/YukSehat/actions/workflows/ci.yml)
![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple.svg)
![AI Providers](https://img.shields.io/badge/AI%20Engine-NVIDIA%20NIM%20%7C%20Google%20Gemini-green.svg)
![License](https://img.shields.io/badge/License-MIT-lightgrey.svg)

---

## 📌 Identitas Proyek Akademik
Dokumen dan repositori ini disusun untuk memenuhi **Tugas Proyek Akhir Mandiri Rekayasa Perangkat Lunak Berbasis Capaian (Outcome-Based Education / OBE)**:

- **Mata Kuliah:** Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS
- **Program Studi:** S1 Teknik Informatika
- **Perguruan Tinggi:** STMIK IKMI Cirebon
- **Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.
- **Tema Topik Kelompok:** *Smart Healthcare & Public Service AI*
- **Nama Mahasiswa:** Muhamad Fauzan
- **NIM:** [41257209]

---

## 📖 Deskripsi Singkat Aplikasi & Masalah yang Diselesaikan
**YukSehat** adalah Sistem Informasi Manajemen Pos Kesehatan Pesantren (Poskestren) modern yang dirancang untuk mengatasi masalah pencatatan rekam medis manual berbasis buku register di pondok pesantren.

### Masalah Nyata yang Diselesaikan:
1. **Pencegahan Penularan Penyakit Komunal:** Mempercepat pelaporan sakit santri mukim dari asrama ke Poskestren guna mencegah transmisi wabah penyakit menular (ISPA, demam, skabies, diare).
2. **Triase & Asistensi Medis AI (Human-in-the-Loop):** Membantu tenaga medis Poskestren merumuskan draf analisis klinis dan rekomendasi obat secara cepat menggunakan Generative AI.
3. **Keterbukaan Informasi ke Wali Santri:** Menghubungkan Poskestren dengan orang tua santri melalui notifikasi instan WhatsApp resmi via Fonnte Gateway.
4. **Digitalisasi Rekam Medis Terpadu:** Mencegah hilangnya data riwayat alergi dan penyakit kronis santri antar-tahun ajaran.

---

## 🚀 Fitur Unggulan & Inovasi AI
1. **Multi-Role Access Control (RBAC):** Hak akses terisolasi untuk 3 entitas (Santri, Petugas Medis, dan Administrator).
2. **Dual-Engine AI Architecture:**
   - **NVIDIA NIM (Nemotron 3.5 Bolt):** Model inferensi berkecepatan tinggi (< 2.5 detik) untuk perumusan draf analisis klinis petugas medis saat memeriksa santri.
   - **Google Gemini AI (Gemini 2.5 Flash Lite):** Model bahasa alami ramah dan santun untuk widget **Chatbot Asisten Santri** dalam mengedukasi pertolongan pertama di asrama.
3. **Automated WhatsApp Gateway:** Normalisasi otomatis nomor telepon wali santri ke format internasional `628...` dan pengiriman rekapitulasi medis pasca-pemeriksaan.
4. **Modern UI/UX with SweetAlert2:** Antarmuka responsif bernuansa *Islamic Emerald & Clean Minimalist*, bebas pop-up browser kaku.
5. **Quality Assurance & CI/CD Ready:** Dilengkapi 24 automated unit tests (100% pass) dan pipeline otomatis GitHub Actions.

---

## 🎥 Tautan Video Presentasi & Live Demo Sistem
Berikut adalah tautan rekaman presentasi video sesuai ketentuan Bab III & Rubrik 4.2:

- 📹 **Video Progres 1 (PRD & Konsep Aplikasi - 3 s.d. 5 Menit):**  
  [Klik di Sini untuk Menonton Video Progres 1 (YouTube/Google Drive)](https://youtu.be/contoh-link-progres-1)
- 📹 **Video Progres 2 (Pemodelan UML & UI/UX - 5 s.d. 7 Menit):**  
  [Klik di Sini untuk Menonton Video Progres 2 (YouTube/Google Drive)](https://youtu.be/contoh-link-progres-2)
- 📹 **Video Final (Live Demo Aplikasi Web & Sistem Utuh - 8 s.d. 12 Menit):**  
  [Klik di Sini untuk Menonton Video Final Live Demo (YouTube/Google Drive)](https://youtu.be/contoh-link-video-final)

---

## 📁 Struktur Dokumen Tugas di Repositori (`/tugas`)
Seluruh dokumen pelaporan akademik disusun secara rapi pada folder [`/tugas`](file:///c:/xampp/htdocs/YukSehat/tugas/):
- [`01_PRD_SRS_YukSehat.md`](file:///c:/xampp/htdocs/YukSehat/tugas/01_PRD_SRS_YukSehat.md): Dokumen Kebutuhan Perangkat Lunak (IEEE 830 / ISO 29148, User Stories & Acceptance Criteria).
- [`02_SDD_UML_Arsitektur_YukSehat.md`](file:///c:/xampp/htdocs/YukSehat/tugas/02_SDD_UML_Arsitektur_YukSehat.md): Dokumen Desain Arsitektur (4 Diagram UML Inti, ERD 8 Tabel, dan Panduan UI/UX).
- [`03_Konstruksi_Testing_DevOps_CI_CD.md`](file:///c:/xampp/htdocs/YukSehat/tugas/03_Konstruksi_Testing_DevOps_CI_CD.md): Laporan Clean Code, Integrasi Dual AI, Laporan Unit Test 24/24 PASS, dan CI/CD.
- [`04_Laporan_Akhir_Terintegrasi.md`](file:///c:/xampp/htdocs/YukSehat/tugas/04_Laporan_Akhir_Terintegrasi.md): Berkas Laporan Akhir Lengkap (Cover Resmi STMIK IKMI, Bab I s.d. V, Daftar Pustaka).
- [`05_Panduan_Naskah_Video_Presentasi.md`](file:///c:/xampp/htdocs/YukSehat/tugas/05_Panduan_Naskah_Video_Presentasi.md): Naskah percakapan dan alur menit demi menit untuk ketiga video presentasi.

---

## 💻 Petunjuk Instalasi & Menjalankan di Lingkungan Lokal

### 1. Prasyarat Sistem (*System Requirements*)
- XAMPP (Apache + MySQL/MariaDB)
- PHP versi 8.2 atau lebih baru
- Node.js versi 18+ (untuk service chatbot AI port 3005)
- Git CLI

### 2. Kloning Repositori & Penempatan Folder
Kloning repositori ke direktori `htdocs` XAMPP Anda:
```bash
cd C:\xampp\htdocs
git clone https://github.com/[USERNAME]/YukSehat.git
cd YukSehat
```

### 3. Setup Basis Data MySQL
1. Buka browser dan akses **phpMyAdmin** di `http://localhost/phpmyadmin`.
2. Buat database baru bernama `yuksehat_db`.
3. Import file skema database yang tersedia pada folder `database/` atau jalankan migrasi SQL:
   ```bash
   mysql -u root -p yuksehat_db < database/yuksehat.sql
   ```

### 4. Konfigurasi Lingkungan (`.env`)
Salin file konfigurasi contoh atau pastikan file `.env` di root direktori telah terisi konfigurasi database:
```ini
DB_HOST=localhost
DB_NAME=yuksehat_db
DB_USER=root
DB_PASS=

# AI Provider API Keys (Opsional untuk Live AI)
NVIDIA_API_KEY=nvapi-xxxxxxxxxxxxxxxxxxxxxxxx
GEMINI_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxx

# WhatsApp Gateway (Fonnte)
FONNTE_TOKEN=your_fonnte_token_here
```

### 5. Menjalankan Chatbot Server (Node.js)
Buka terminal baru untuk menjalankan microservice AI assistant:
```bash
cd chatbot-server
node server.js
# Chatbot service aktif di http://localhost:3005
```

### 6. Menjalankan Aplikasi di Browser
Akses aplikasi melalui peramban:
```
http://localhost/YukSehat
```

**Akun Demo untuk Pengujian:**
- **Admin:** `admin` / `password123`
- **Petugas Medis:** `petugas` / `password123`
- **Santri:** `santri` / `password123`

---

## 🧪 Menjalankan Pengujian Otomatis (*Automated Unit Testing*)
Sistem dilengkapi dengan test runner CLI tanpa dependensi eksternal. Jalankan pengujian langsung dari root direktori proyek:

```bash
C:\xampp\php\php.exe tests/run_tests.php
```

**Output Hasil Pengujian:**
```
========================================================================
 YUKSEHAT AUTOMATED UNIT TEST SUITE (PHP CLI)
========================================================================
 [PASS] - TC-AUTH-001 s.d. TC-AUTH-005 (Otorisasi Role & Session)
 [PASS] - TC-MED-001 s.d. TC-MED-007 (Validasi Tanda Vital & Format Tensi)
 [PASS] - TC-FONNTE-001 s.d. TC-FONNTE-005 (Normalisasi Nomor WhatsApp)
 [PASS] - TC-AI-001 s.d. TC-AI-002 (Katalog Engine AI & Verifikasi Prompt)
 [PASS] - TC-CSRF-001 s.d. TC-CSRF-003 (Keamanan Token Proteksi Form)
 [PASS] - TC-SYNTAX-001 (Pemeriksaan Sintaks 27 File PHP Utama)
========================================================================
 Total Tests: 24 | Passed: 24 | Failed: 0 | Execution Time: 0.84s
 BUILD STATUS: GREEN / PASS (100% SUCCESS RATE)
========================================================================
```

---

## 🔄 Alur Kerja CI/CD (GitHub Actions)
Setiap perubahan kode yang di-push ke branch `main` atau `master` secara otomatis memicu pengujian pipeline yang didefinisikan pada `.github/workflows/ci.yml`. Pipeline menjalankan:
1. Validasi sintaks PHP (lint check) pada seluruh controller, model, dan view.
2. Eksekusi `tests/run_tests.php` secara otomatis pada runner virtual Linux.
3. Memastikan status build tetap hijau sebelum rilis kode baru.

---

## 📄 Lisensi & Hak Cipta
Hak Cipta (c) 2026 Mahasiswa S1 Teknik Informatika STMIK IKMI Cirebon.  
Dikembangkan untuk tujuan akademik dan implementasi sosial pada Pos Kesehatan Pesantren. Dilindungi di bawah lisensi MIT.
