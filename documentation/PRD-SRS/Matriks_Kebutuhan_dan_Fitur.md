# MATRIKS PENELUSURAN KEBUTUHAN DAN FITUR (TRACEABILITY MATRIX)
# APLIKASI YUKSEHAT POSKESTREN

- **Dokumen Identifikasi:** TRACE-YUKSEHAT-2026-V1.0
- **Versi Dokumen:** 1.0.0
- **Tanggal Evaluasi:** 16 November 2026
- **Status:** Baseline Penelusuran Lengkap (PRD <-> SRS <-> Implementasi Kode <-> Basis Data)

---

## 1. Matriks Penelusuran Kebutuhan Fungsional (Functional Traceability Matrix)

Tabel berikut menghubungkan kebutuhan pengguna (*User Needs*), fitur pada PRD (*Feature ID*), spesifikasi fungsional SRS (*Functional Requirement*), komponen pengendali (*Controller & Method*), layanan pendukung (*Services/Helpers*), berkas tampilan (*Views*), tabel basis data, dan status implementasi aktual:

| Kebutuhan Pengguna (UN) | Fitur PRD (FEAT) | Spesifikasi SRS (FR) | Pengontrol & Metode (Controller@Method) | Model / Service Terkait | Tampilan (Views) | Tabel Basis Data | Status Implementasi |
|---|---|---|---|---|---|---|:---:|
| **UN-01**, **UN-02** | **FEAT-05** | **FR-KEL-001** | `Keluhan::kirim()` | `Notifikasi_model` | `app/views/keluhan/santri.php` | `riwayat_keluhan`, `notifikasi`, `notifikasi_wa_queue` | **Aktif** |
| **UN-05** | **FEAT-06** | **FR-KEL-002** | `Keluhan::index()` | Database Query Langsung | `app/views/keluhan/admin.php` | `riwayat_keluhan`, `santri`, `pengurus` | **Aktif** |
| **UN-05** | **FEAT-06** | **FR-KEL-003** | `Keluhan::assign()` | `Notifikasi_model` | `app/views/keluhan/admin.php` | `riwayat_keluhan`, `notifikasi` | **Aktif** |
| **UN-02**, **UN-05** | **FEAT-06** | **FR-KEL-004** | `Keluhan::update_status()` | `Notifikasi_model` | `app/views/keluhan/admin.php` | `riwayat_keluhan`, `notifikasi` | **Aktif** |
| **UN-06** | **FEAT-07**, **FEAT-18** | **FR-MED-001** | `Pemeriksaan::create()`, `Pemeriksaan::store()` | `Pemeriksaan_model`, `AuthHelper::requireCanInputPemeriksaan` | `app/views/pemeriksaan/create.php` | `pemeriksaan_kesehatan`, `riwayat_keluhan` | **Parsial (Aturan Ronal Aktif)** |
| **UN-03** | **FEAT-07** | **FR-MED-004** | `Pemeriksaan::index()`, `Pemeriksaan::show()` | `Pemeriksaan_model` | `app/views/pemeriksaan/index.php`, `show.php` | `pemeriksaan_kesehatan`, `santri` | **Aktif** |
| **UN-07** | **FEAT-08** | **FR-MED-003** | `Pemeriksaan::analyze_keluhan()` | `AiService`, `chatbot-server/server.js` | `app/views/pemeriksaan/create.php`, `edit.php` | `system_settings` | **Aktif** |
| **UN-07** | **FEAT-09** | **FR-MED-004** | Client-Side JS (`showConfirmModal`) | `dash_footer.php` (`showYkConfirm`) | `app/views/pemeriksaan/create.php`, `edit.php` | N/A (Client DOM State) | **Aktif** |
| **UN-08** | **FEAT-12** | **FR-NOTIF-001**, **FR-NOTIF-002** | `Notifikasi::index()`, `Notifikasi::read()`, `Notifikasi::mark_read_ajax()` | `Notifikasi_model` | `app/views/notifikasi/index.php` | `notifikasi`, `riwayat_keluhan`, `pemeriksaan_kesehatan` | **Aktif** |
| **UN-09** | **FEAT-13** | **FR-REP-001** | `Laporan::index()` | `Laporan_model` | `app/views/laporan/index.php` | `pemeriksaan_kesehatan`, `santri` | **Aktif** |
| **UN-09** | **FEAT-13** | **FR-REP-002** | `Laporan::export()` | `Laporan_model`, `MedicalFormatHelper`, `SimpleXLSXGen` | N/A (Binary Stream Download) | `pemeriksaan_kesehatan`, `santri` | **Aktif** |
| **UN-04** | **FEAT-10** | **FR-CHAT-001**, **FR-CHAT-003**, **FR-CHAT-004** | `Chatbot::send()`, `Chatbot::status()` | `AiService`, `chatbot-server/server.js` | `app/views/components/chatbot.php` | `system_settings` | **Aktif** |
| **UN-10** | **FEAT-11** | **FR-CHAT-002** | `Chatbot::send() -> ChatbotDataService` | `ChatbotDataService` | `app/views/components/chatbot.php` | `pemeriksaan_kesehatan`, `santri`, `riwayat_keluhan`, `notifikasi_wa_queue` | **Aktif** |
| **UN-10** | **FEAT-03** | **FR-DASH-001** | `Dashboard::index()` | `Dashboard_model` | `app/views/dashboard/index.php` | `pemeriksaan_kesehatan`, `santri`, `pengurus` | **Aktif** |
| **UN-11** | **FEAT-14** | **FR-ADM-001** | `Users::index()`, `Users::store()`, `Users::update()` | Database Query Langsung | `app/views/users/index.php` | `users`, `pengurus` | **Aktif** |
| **UN-12** | **FEAT-15** | **FR-ADM-002** | `Settings::index()`, `Settings::save()`, `Settings::update_key()`, `Settings::test_model()` | `AiService` | `app/views/settings/index.php` | `system_settings` | **Aktif** |
| **UN-13** | **FEAT-16**, **FEAT-17** | **FR-NOTIF-003**, **FR-NOTIF-004**, **FR-NOTIF-005** | `Monitoring::index()`, `Monitoring::check_device()`, `Monitoring::reconcile()`, `bin/wa_worker.php` | `FonnteService`, `WaQueueService`, `Notifikasi_model` | `app/views/monitoring/index.php` | `notifikasi_wa_queue` | **Simulasi / Safe-Mode (Aktif di Monitoring)** |
| **N/A** | **FEAT-01** | **FR-AUTH-001**, **FR-AUTH-003** | `Login::index()`, `Login::auth()`, `Login::logout()` | `User_model`, `AuthHelper`, `SessionHelper` | `app/views/login/index.php` | `users`, `santri`, `pengurus` | **Aktif** |
| **N/A** | **FEAT-02** | **FR-AUTH-002** | `Register::index()`, `Register::store()` | `User_model`, `AuthHelper`, `SessionHelper` | `app/views/register/index.php` | `users`, `santri` | **Aktif** |
| **N/A** | **FEAT-04** | **FR-SAN-001**, **FR-SAN-002**, **FR-SAN-003** | `Santri::index()`, `Santri::create()`, `Santri::store()`, `Santri::edit()`, `Santri::update()`, `Santri::delete()` | `Santri_model` | `app/views/santri/index.php`, `create.php`, `edit.php`, `detail.php` | `santri`, `users` | **Aktif** |
| **N/A** | **FEAT-19** | N/A (Stub) | `Kesehatan::index()` | N/A | Inline View Echo | N/A | **Placeholder / Belum Tersedia** |

---

## 2. Matriks Pemetaan Kebutuhan Nonfungsional (NFR Mapping)

| Kode NFR | Parameter | Persyaratan Spesifik | Lokasi Enforce Kode / Mekanisme Teknis | Status Audit |
|---|---|---|---|:---:|
| **NFR-SEC-001** | SQL Injection | Prepared Statements mutlak tanpa penggabungan string | `app/core/Database.php` (PDO Wrapper `bind()`, `prepare()`) | **LULUS (100%)** |
| **NFR-SEC-002** | CSRF Attack | Validasi token anti-CSRF kriptografis pada setiap POST | `AuthHelper::validateCSRF()` & `AuthHelper::rotateCSRF()` | **LULUS (100%)** |
| **NFR-SEC-003** | XSS Protection | Sanitasi string masukan dan keluaran DOM | `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` di template views | **LULUS (100%)** |
| **NFR-SEC-004** | Session Hardening | Flag cookie `HttpOnly`, `SameSite=Lax`, regenerasi ID sesi | `app/init.php` (`session_set_cookie_params`) & `Login::auth()` | **LULUS (100%)** |
| **NFR-SEC-005** | Privasi Medis WA | Pensterilan pesan WA dari narasi keluhan/diagnosa/obat | `Notifikasi_model::createNotification()` & `Keluhan::update_status()` | **LULUS (100%)** |
| **NFR-SEC-006** | Server Hardening | Blokir berkas rahasia `.env`, `.sql`, dan direktori internal | Root `.htaccess` (RewriteRule direktori & FilesMatch) | **LULUS (100%)** |
| **NFR-SEC-007** | Error Leakage | Pematian display_errors pada mode produksi | `config/config.php` (`ini_set('display_errors', '0')`) | **LULUS (100%)** |
| **NFR-PERF-001** | Latensi Halaman | Render halaman lokal di bawah 1,5 detik | Arsitektur MVC Native ringan tanpa overhead framework pihak ketiga | **LULUS (100%)** |
| **NFR-PERF-003** | Efisiensi Ekspor | Ekspor XLSX multi-sheet hemat memori | `SimpleXLSXGen.php` (Streaming ZIP/XML tanpa PhpSpreadsheet) | **LULUS (100%)** |
| **NFR-REL-001** | Ketahanan Gateway | Gangguan API eksternal tidak membatalkan transaksi DB | Blok `try-catch` terisolasi pada `Notifikasi_model` dan `AiService` | **LULUS (100%)** |
| **NFR-REL-002** | Anti-Overlapping | Proteksi tabrakan antrean worker via kunci file eksklusif | `bin/wa_worker.php` (`flock(..., LOCK_EX \| LOCK_NB)`) | **LULUS (100%)** |
| **NFR-REL-003** | Bounded Retry | Jeda bertingkat (Exponential Backoff) maks 3 kali | `WaQueueService::RETRY_BACKOFF_SECONDS` (60s, 300s, 900s) | **LULUS (100%)** |
| **NFR-USE-002** | Modal Interaktif | Menghilangkan alert/confirm kaku bawaan peramban | `dash_footer.php` (`showYkToast` & `showYkConfirm`) | **LULUS (100%)** |

---

## 3. Matriks Penegakan Aturan Bisnis (Business Rules Enforcement Matrix)

| Aturan Bisnis (BR) | Inti Aturan | Komponen Penegak Kode | Bukti Implementasi pada Source Code |
|---|---|---|---|
| **BR-001** | Kerahasiaan Rekam Medis (Santri hanya melihat data sendiri) | `Pemeriksaan::show()` | Validasi: `if ($user['role'] === 'santri' && $item['santri_user_id'] != $user['id']) { SessionHelper::setFlash('pesan', 'Akses ditolak...', 'alert alert-danger'); header('Location: ...'); exit; }` |
| **BR-002** | Otorisasi Khusus Input Periksa (Hanya akun Pengurus `ronal`) | `AuthHelper::canInputPemeriksaan()`, `Pemeriksaan::create()`, `Pemeriksaan::store()` | `return ($user['role'] === 'pengurus' && strtolower(trim($user['username'])) === 'ronal');` |
| **BR-003** | Siklus Status Keluhan (Menunggu -> Diperiksa -> Selesai) | `Keluhan::kirim()`, `Pemeriksaan::store()`, `Keluhan::update_status()` | Status awal di-insert `'menunggu'`. Saat periksa disimpan, status di-update menjadi `'selesai'`. |
| **BR-004** | Idempotensi Notifikasi (Mencegah notifikasi ganda) | `Notifikasi_model::createNotification()` | `SELECT id FROM notifikasi WHERE user_id=:uid AND jenis=:jenis AND referensi_tipe=:ref_tipe AND referensi_id=:ref_id LIMIT 1` |
| **BR-005** | Notifikasi Khusus Penugasan (Bukan broadcast umum) | `Keluhan::kirim()`, `Keluhan::assign()`, `Keluhan::update_status()` | Parameter `user_id` ditargetkan eksplisit ke ID pemeriksa yang dipilih atau ID santri pelapor. |
| **BR-006** | Privasi Mutlak Kanal WhatsApp (Tanpa data medis) | `Notifikasi_model`, `Keluhan.php` | Variabel `$waGenericMsg` diformat tanpa variabel `$keluhan`, `$hasil_pemeriksaan`, atau `$obat`. |
| **BR-007** | Ketahanan Kegagalan Gateway (Non-blocking DB) | `Notifikasi_model`, `WaQueueService` | Operasi HTTP ditaruh di luar blok transaksi penyimpanan notifikasi in-app. |
| **BR-008** | Anti-Lockout Super User Administrator | `Users::update()` | `if ($userId === (int)$currentUser['id']) { if ($status !== 'active' \|\| $newRole !== 'admin') { ... tolak tindakan ... } }` |
| **BR-009** | Etika AI Non-Diagnostik | `Pemeriksaan::analyze_keluhan()`, `chatbot-server/server.js` | Teks sistem selalu menyertakan label "Draf Saran Klinis Awal. Bukan diagnosa medis final atau resep obat otomatis." |
| **BR-010** | Integritas Deaktivasi Santri (Soft Delete) | `Santri::delete()`, `Santri_model::deactivateSantri()` | Query: `UPDATE users SET status = 'inactive' WHERE id = :user_id`. Baris di tabel `santri` tidak dihapus. |

---

## 4. Analisis Kesenjangan & Fitur Belum Terverifikasi (Gap Analysis)

Berdasarkan perbandingan antara ekspektasi umum sistem informasi kesehatan pesantren dengan implementasi nyata pada repositori:

| Area Analisis | Implementasi Nyata yang Ditemukan | Status Verifikasi | Keterangan & Kesenjangan |
|---|---|:---:|---|
| **Pengunggahan Berkas Medis** | Tidak ditemukan form bertipe `multipart/form-data` untuk dokumen rekam medis pada `app/views/pemeriksaan/`. | **Belum Tersedia** | Input medis saat ini 100% berbasis teks dan angka tanda vital. |
| **Modul Kesehatan Mandiri** | Berkas `app/controllers/Kesehatan.php` hanya berisi placeholder statis. | **Belum Tersedia** | Seluruh operasional kesehatan riil dilayani oleh `Keluhan.php` dan `Pemeriksaan.php`. |
| **Live WhatsApp Blast** | Gateway WhatsApp terpasang secara komprehensif, namun switch `WHATSAPP_ENABLED` diset `false` secara default di `.env`. | **Simulasi / Safe-Mode** | Layanan siap aktif (*ready for live delivery*) segera setelah token Fonnte diisi dan `WHATSAPP_ENABLED=true`. |
| **Pengujian Beban & Skalabilitas** | Sistem lulus 52/52 uji UAT fungsional, namun belum ditemukan berkas log uji stres multi-user (> 10.000 konkurensi). | **Belum Terverifikasi** | Sesuai karakteristik Poskestren lokal (skala mikro hingga menengah ratusan santri). |
| **Integrasi Eksternal SATUSEHAT/BPJS** | Tidak ditemukan dependensi API SATUSEHAT atau BPJS Kesehatan di dalam source code. | **Belum Tersedia** | Di luar ruang lingkup versi 1.0.0. |

---

## 5. Ringkasan Bagian yang Memerlukan Konfirmasi Manusia (Human Confirmation Items)

Untuk penyelarasan implementasi dengan kebijakan operasional jangka panjang pesantren, beberapa butir teknis berikut memerlukan keputusan manajemen pemangku kepentingan (*stakeholders decision*):

1. **Kebijakan Penetapan Petugas Input Pemeriksaan (`BR-002`)**:
   - *Kondisi Saat Ini*: Kode sumber secara eksplisit mengunci form penambahan pemeriksaan (`/pemeriksaan/create`) hanya untuk pengguna dengan username `ronal` (`AuthHelper::canInputPemeriksaan()`).
   - *Poin Keputusan Manusia*: Apakah pembatasan nama pengguna tunggal (`ronal`) ini akan dipertahankan sebagai aturan tetap Poskestren, atau diubah menjadi berbasis *Permission/Role* dinamis di masa mendatang jika ada pergantian petugas kesehatan jaga?
2. **Pengaktifan Live Delivery Gateway WhatsApp (`WHATSAPP_ENABLED`)**:
   - *Kondisi Saat Ini*: Mode WhatsApp beroperasi dalam *Safe-Mode* / simulasi antrean.
   - *Poin Keputusan Manusia*: Kapan pimpinan pondok menghendaki aktivasi pengiriman pesan WhatsApp riil ke nomor ponsel fisik pengurus/wali santri (dengan konsekuensi pemakaian kuota gateway Fonnte)?
3. **Penyederhanaan atau Penghapusan File Stub `Kesehatan.php`**:
   - *Kondisi Saat Ini*: File `app/controllers/Kesehatan.php` tidak digunakan oleh alur navigasi manapun.
   - *Poin Keputusan Manusia*: Apakah file stub ini disetujui untuk diarsipkan (*cleanup*) pada rilis pemeliharaan berikutnya demi kerapian arsitektur?
4. **Pendaftaran Mandiri Santri via `/register`**:
   - *Kondisi Saat Ini*: Santri dapat mendaftar sendiri melalui halaman registrasi publik asalkan NIS belum terdaftar.
   - *Poin Keputusan Manusia*: Apakah pendaftaran mandiri tetap dibuka untuk santri baru, atau seluruh akun santri harus di-impor secara terpusat oleh Admin/Pengurus dari data sekretariat pesantren?
