# PANDUAN DAN NASKAH VIDEO PRESENTASI & LIVE DEMO SISTEM
## PROYEK AKHIR REKAYASA PERANGKAT LUNAK (OBE) — YUKSEHAT

**Mata Kuliah:** Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS  
**Program Studi:** S1 Teknik Informatika, STMIK IKMI Cirebon  
**Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.  
**Tema:** *Smart Healthcare & Public Service AI*  

---

## I. STANDAR KUALITAS DAN KETENTUAN REKAMAN VIDEO

Sesuai **Bab III & Rubrik 4.2 Panduan Proyek Mandiri**:
1. **Penampilan Pemateri:** Wajib berbusana sopan/rapi (kemeja/almamater). Webcam wajib aktif memperlihatkan wajah mahasiswa secara jelas di pojok layar (*picture-in-picture*) sepanjang video.
2. **Kualitas Audio & Visual:**
   - Resolusi rekaman minimal Full HD (1080p, 1920x1080).
   - Audio bersih, artikulasi jelas, menggunakan mikrofon eksternal/headset (bebas *background noise*).
   - Teks pada terminal/browser cukup besar (Zoom 110% - 125%) agar dapat terbaca oleh dosen penilai.
3. **Struktur Pengumpulan:** Video diunggah ke YouTube (Unlisted/Public) atau Google Drive institusi dengan hak akses *"Anyone with the link can view"*. Tautan video wajib dicantumkan pada berkas `README.md` repositori GitHub.

---

## II. NASKAH VIDEO PROGRES 1: PRD & KONSEP APLIKASI
- **Target Durasi:** 3 – 5 Menit  
- **Bobot Penilaian:** 20% Nilai Video  
- **Fokus:** Problem Statement, Target Pengguna (Pesantren), Solusi Produk, dan Scope AI.

### Rincian *Slide & Talking Points* (Menit demi Menit):

| Menit | Tampilan Layar | Naskah Percakapan (Spoken Script) |
| :--- | :--- | :--- |
| **00:00 - 00:45** | **Slide 1:** Judul Proyek, Logo IKMI, Nama & NIM Mahasiswa, Kamera aktif di sudut kanan atas. | *"Assalamu’alaikum Warahmatullahi Wabarakatuh. Selamat pagi/siang kepada Bapak Yudhistira Arie Wijaya, M.Kom., CDS. dan rekan-rekan sekalian. Perkenalkan nama saya [Nama Anda], NIM [NIM Anda], mahasiswa S1 Teknik Informatika STMIK IKMI Cirebon. Pada kesempatan video Progres Pertama ini, saya mempresentasikan rancangan Product Requirements Document (PRD) dan konsep proyek akhir mandiri saya yang berjudul **YukSehat: Sistem Informasi Poskestren Terpadu Berbasis Web dengan Integrasi Dual-Engine AI dan WhatsApp Gateway** pada tema Smart Healthcare."* |
| **00:45 - 01:45** | **Slide 2:** Latar Belakang & *Problem Statement* (Foto Pesantren & Buku Register Fisik). | *"Latar belakang proyek ini berangkat dari fenomena nyata di lingkungan pondok pesantren komunal. Di mana ratusan santri berinteraksi intensif, namun manajemen kesehatan santri di Poskestren masih mengandalkan buku catatan manual. Akibatnya terjadi risiko rekam medis hilang, antrean santri menumpuk tanpa triase prioritas, wali santri di rumah cemas karena tidak mendapat kabar kondisi anak mereka, serta petugas kesehatan kewalahan mencatat keluhan berulang."* |
| **01:45 - 02:45** | **Slide 3:** Solusi & *User Personas* (Ahmad Santri, dr. Siti Nakes, Haji Rahmat Wali). | *"Sebagai solusinya, YukSehat hadir melayani 3 aktor utama: Pertama, Santri yang dapat melaporkan keluhan sakit secara mandiri dan berkonsultasi edukatif dengan Chatbot AI. Kedua, Tenaga Medis Poskestren yang dibantu oleh AI Clinical Assistant untuk merumuskan draf analisis klinis dan mengelola antrean. Ketiga, Wali Santri yang otomatis menerima laporan notifikasi resmi via WhatsApp saat putra-putrinya selesai diperiksa."* |
| **02:45 - 03:30** | **Slide 4:** Ruang Lingkup Sistem & Rencana Teknologi (Stack: PHP MVC, MySQL, Dual AI). | *"Ruang lingkup sistem berfokus pada arsitektur MVC PHP Native 8.2 yang ringan, database MySQL 8.0, microservice Node.js untuk chatbot, serta orkestrasi Dual-Engine AI berbasis NVIDIA NIM dan Google Gemini. Pada Progres 2, saya akan menyajikan pemodelan UML lengkap dan purwarupa antarmukanya. Sekian progres pertama ini, terima kasih. Wassalamu’alaikum Warahmatullahi Wabarakatuh."* |

---

## III. NASKAH VIDEO PROGRES 2: PEMODELAN UML & UI/UX
- **Target Durasi:** 5 – 7 Menit  
- **Bobot Penilaian:** 30% Nilai Video  
- **Fokus:** 4 Diagram UML Inti, ERD Basis Data Relasional, dan Wireframe UI/UX.

### Rincian *Slide & Talking Points*:

| Menit | Tampilan Layar | Naskah Percakapan (Spoken Script) |
| :--- | :--- | :--- |
| **00:00 - 00:45** | **Slide 1:** Judul Progres 2, Kamera Mahasiswa aktif. | *"Assalamu’alaikum Warahmatullahi Wabarakatuh. Melanjutkan tahapan sebelumnya, pada video Progres Kedua ini saya akan memaparkan rancangan Software Design Document (SDD) sistem YukSehat, yang meliputi 4 Diagram UML utama, Entity Relationship Diagram (ERD), dan perancangan antarmuka UI/UX."* |
| **00:45 - 02:00** | **Slide 2:** Use Case Diagram & Activity Diagram. | *"Pertama, pada Use Case Diagram terlihat pemisahan wewenang ketat antara Santri, Nakes, dan Administrator. Santri mengakses pelaporan keluhan dan konsultasi chatbot. Nakes mengelola antrean dan melakukan input rekam medis dengan dependensi 'include' ke Analisis Klinis AI dan 'extend' ke Notifikasi WhatsApp. Pada Activity Diagram, digambarkan bagaimana proses triase AI berjalan secara asinkron: nakes menekan tombol AI, server backend meminta inferensi ke LLM, dan hasilnya disajikan sebagai draf tinjauan sebelum disimpan ke database."* |
| **02:00 - 03:30** | **Slide 3:** Class Diagram Berorientasi Objek (SOLID) & Sequence Diagram. | *"Kedua, Class Diagram dirancang mengadopsi prinsip SOLID. Database terisolasi dalam kelas singleton, setiap Model mewarisi BaseModel, dan layanan pihak ketiga didelegasikan ke AiService dan FonnteService. Pada Sequence Diagram, tampak jelas orkestrasi 4-tier: dimulai dari form peramban nakes, diproses oleh PemeriksaanController, memicu panggilan aman ke AI Engine, menyimpan persistensi ke MySQL via PDO, dan memancarkan webhook ke gateway WhatsApp."* |
| **03:30 - 04:45** | **Slide 4:** Entity Relationship Diagram (ERD 8 Tabel). | *"Ketiga, desain basis data relasional terdiri dari 8 entitas yang telah ternormalisasi hingga 3NF: tabel `users`, `santri`, `kamar`, `kelas`, `keluhan`, `pemeriksaan`, `riwayat_obat`, dan `chatbot_log`. Relasi foreign key dirancang dengan referential integrity yang mencegah duplikasi data."* |
| **04:45 - 06:00** | **Layar Web:** Walkthrough Prototipe UI/UX di Localhost. | *"Keempat, inilah implementasi antarmuka pengguna sistem YukSehat. Mengusung konsep 'Islamic Emerald & Clean Minimalist' berbasis Bootstrap 5. Terdapat floating widget Chatbot AI, modal analitika klinis yang responsif, dan dashboard antrean real-time. Pada video final berikutnya, saya akan mendemonstrasikan sistem yang bekerja penuh beserta pengujian otomatisnya. Terima kasih. Wassalamu’alaikum Warahmatullahi Wabarakatuh."* |

---

## IV. NASKAH VIDEO FINAL: LIVE DEMO APLIKASI WEB & SISTEM UTUH
- **Target Durasi:** 8 – 12 Menit  
- **Bobot Penilaian:** 50% Nilai Video  
- **Fokus:** Elevator Pitch, Live Demo Sistem Nyata, Pengujian Unit Otomatis di Terminal, Verifikasi CI/CD GitHub Actions, dan Refleksi Penutup.

### Rincian Skenario Demo Live (Menit demi Menit):

#### 1. Pembukaan & Elevator Pitch (00:00 - 01:15)
- **Tampilan:** Wajah mahasiswa jelas, memperlihatkan landing page login YukSehat.
- **Naskah:**
  > *"Assalamu’alaikum Warahmatullahi Wabarakatuh. Selamat pagi/siang Bapak Yudhistira Arie Wijaya, M.Kom., CDS. dan seluruh audiens. Saya [Nama Mahasiswa], NIM [NIM Mahasiswa] dari STMIK IKMI Cirebon.*  
  > *Hari ini saya dengan bangga mendemonstrasikan hasil akhir proyek mandiri Rekayasa Perangkat Lunak saya, yaitu **YukSehat: Sistem Informasi Pos Kesehatan Pesantren Terpadu Berbasis Web dengan Integrasi Dual-Engine AI dan WhatsApp Gateway**.*  
  > *Aplikasi ini bukan sekadar prototype statis, melainkan sistem web full-stack fungsional yang siap pakai, dilengkapi 24 automated unit tests, pipeline CI/CD GitHub Actions, dan kecerdasan buatan nyata. Mari kita mulai demonstrasi langsungnya."*

#### 2. Live Demo Peran Santri: Input Keluhan & Chatbot Edukasi AI (01:15 - 03:30)
- **Aksi Layar:**
  1. Login sebagai santri (username: `santri`, password: `password123`).
  2. Buka menu **Lapor Keluhan Kesehatan**.
  3. Ketik keluhan: *"Demam meriang sejak semalam, tenggorokan sakit menelan, badan lemas"*. Klik **Kirim Keluhan**.
  4. Tunjukkan dialog konfirmasi SweetAlert2: *"Keluhan berhasil dikirim dan masuk antrean Poskestren"*.
  5. Buka widget mengambang **Tanya AI** di pojok kanan bawah.
  6. Tanyakan: *"Saya demam dan radang tenggorokan, apa pertolongan pertama yang bisa saya lakukan di asrama sebelum dipanggil ke poskestren?"*.
  7. Tunjukkan respon cepat dan santun dari YukSehat AI (anjuran minum air hangat, istirahat, kompres, dan peringatan bahwa ini bukan obat keras).
  8. Logout dari akun santri.

#### 3. Live Demo Peran Nakes: Antrean & AI Clinical Assistant (03:30 - 06:30)
- **Aksi Layar:**
  1. Login sebagai Petugas Medis (username: `petugas`, password: `password123`).
  2. Masuk ke menu **Antrean Keluhan Santri**. Perlihatkan keluhan santri yang baru saja dikirim muncul di tabel antrean berstatus *"Menunggu Pemeriksaan"*.
  3. Klik aksi **Periksa Santri**. Sistem membuka form input pemeriksaan dengan data santri terisi otomatis.
  4. Input Tanda Vital:
     - Suhu Tubuh: `38.2` °C (Demam)
     - Tekanan Darah: `115/75` mmHg
     - Berat Badan: `52` kg
  5. Klik tombol hijau berikon kecerdasan: **"Analisis Keluhan dengan AI"**.
  6. **Sorot Keunggulan:** Tunjukkan spinner loading cepat (< 3 detik) dan hasil sintesis AI muncul di card khusus:
     - *Dugaan Awal:* Faringitis Akut / ISPA Ringan didukung suhu febris 38.2°C.
     - *Rekomendasi Tindakan:* Istirahat tirah baring 2 hari, hidrasi cukup, observasi berkala.
     - *Anjuran Medis:* Paracetamol tablet 500mg bila demam bertahan.
  7. Klik tombol **"Terapkan ke Form"**. Tunjukkan isi form Hasil Pemeriksaan, Tindakan, dan Resep langsung terisi otomatis dari draf AI tanpa nakes harus mengetik manual dari nol.
  8. Edit sedikit teks (bukti *Human-in-the-Loop*).
  9. Klik **"Simpan Pemeriksaan & Kirim Notifikasi WA"**.
  10. Tunjukkan SweetAlert2 sukses dan status keluhan otomatis berubah menjadi *"Selesai Ditangani"*.

#### 4. Live Demo Peran Admin: Orkestrasi Dual-Engine AI & Monitoring WA (06:30 - 08:00)
- **Aksi Layar:**
  1. Login sebagai Administrator (username: `admin`).
  2. Masuk ke menu **Konfigurasi AI & Chatbot**.
  3. Jelaskan fitur switcher Dual-Engine:
     - Pilihan Model 1 (Chatbot): Google Gemini Flash Lite / Nemotron.
     - Pilihan Model 2 (Analisis Klinis): NVIDIA Nemotron 3.5 Bolt (Rekomendasi Utama Tercepat).
  4. Klik tombol **"Uji Model Ini"**. Tunjukkan alert hijau instan: *"Koneksi API Berhasil! Model NVIDIA Nemotron merespon dalam 2400 ms"*.
  5. Buka menu **Monitoring WhatsApp**. Perlihatkan log pengiriman pesan notifikasi rekam medis yang baru saja tersimpan.

#### 5. Live Execution: Automated Unit Testing di Terminal (08:00 - 09:30)
- **Aksi Layar:**
  1. Buka PowerShell / Terminal di root direktori proyek `c:\xampp\htdocs\YukSehat`.
  2. Jalankan perintah:
     ```powershell
     C:\xampp\php\php.exe tests/run_tests.php
     ```
  3. Sorot hasil output di layar:
     > *"Dapat dilihat di layar terminal, test runner mengeksekusi 24 test cases secara otomatis mencakup otorisasi role, validasi format tekanan darah dan suhu, normalisasi nomor WhatsApp, katalog model AI, verifikasi token CSRF, dan sintaks 27 file core PHP. Seluruhnya berstatus **PASS 24/24 (100% Success Rate) dengan BUILD STATUS: GREEN**."*

#### 6. Verifikasi CI/CD GitHub Actions & Kualitas Repositori (09:30 - 10:30)
- **Aksi Layar:**
  1. Buka browser pada repositori GitHub proyek: `https://github.com/[username]/YukSehat`.
  2. Tunjukkan badge hijau **CI/CD Passing** pada header `README.md`.
  3. Buka tab **Actions** di GitHub, perlihatkan commit terakhir memicu workflow `.github/workflows/ci.yml` dan menghasilkan ceklis hijau (*All checks have passed*).
  4. Tunjukkan kerapian struktur commit dan kelengkapan berkas dokumentasi di folder `tugas/`.

#### 7. Refleksi & Penutup (10:30 - 11:30)
- **Tampilan:** Kamera wajah mahasiswa fullscreen atau slide penutup.
- **Naskah:**
  > *"Sebagai penutup dan refleksi pembelajaran OBE Rekayasa Perangkat Lunak ini: Proyek YukSehat telah membuktikan bagaimana integrasi kecerdasan buatan generatif dan otomasi komunikasi WhatsApp dapat memecahkan masalah riil di pesantren secara etis, terstruktur, dan akuntabel melalui pendekatan human-in-the-loop.*  
  > *Seluruh artefak perangkat lunak mulai dari PRD, SDD, diagram UML, pengujian unit, hingga pipeline CI/CD telah disusun secara komprehensif.*  
  > *Terima kasih yang sebesar-besarnya kepada Bapak Yudhistira Arie Wijaya, M.Kom., CDS. atas bimbingannya selama perkuliahan semester ini. Semoga karya ini bermanfaat bagi kemajuan teknologi kesehatan pesantren di Indonesia.*  
  > *Wabillahi taufiq wal hidayah, Wassalamu’alaikum Warahmatullahi Wabarakatuh."*

---

## V. CHECKLIST PERSIAPAN SEBELUM REKAMAN VIDEO

Pastikan mencentang seluruh item berikut sebelum menekan tombol *Record*:
- [ ] XAMPP Apache dan MySQL dalam status running hijau.
- [ ] Microservice chatbot Node.js berjalan (opsional jika menggunakan endpoint fallback).
- [ ] Terminal PowerShell sudah terbuka di direktori `c:\xampp\htdocs\YukSehat` dan perintah `php tests/run_tests.php` sudah diuji berjalan sukses.
- [ ] Browser dalam keadaan login terpisah (bisa gunakan incognito atau multi-profile) agar transisi login Santri -> Nakes -> Admin berjalan mulus tanpa jeda lama.
- [ ] Webcam berada pada pencahayaan yang terang, resolusi OBS / Screen Recorder diset ke 1920x1080 pada 30/60 fps.
- [ ] Mikrofon diuji terlebih dahulu (*mic check*) agar suara terdengar jernih tanpa dengung.
