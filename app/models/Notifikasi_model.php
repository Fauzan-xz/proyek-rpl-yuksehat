<?php
/**
 * Model Notifikasi In-App dan Antrean WhatsApp (Safe Mode)
 * YukSehat - Native PHP MVC
 */

require_once dirname(__DIR__) . '/services/FonnteService.php';
require_once dirname(__DIR__) . '/services/WaQueueService.php';

class Notifikasi_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Buat notifikasi baru dengan idempotensi berdasarkan (user_id, jenis, referensi_tipe, referensi_id)
     * Hanya berlaku untuk user dengan status active dan role 'pengurus'.
     *
     * @param int $userId ID pengguna di tabel users
     * @param string $jenis Kategori peristiwa ('penugasan_baru', 'pengalihan_tugas', 'status_diperiksa', 'status_selesai')
     * @param string $judul Judul notifikasi in-app
     * @param string $pesan Isi teks notifikasi in-app
     * @param string $linkUrl URL relatif tujuan dalam aplikasi
     * @param string $refTipe Tipe referensi ('keluhan')
     * @param int|null $refId ID objek referensi
     * @param string|null $waGenericMessage Pesan generik tanpa data medis untuk queue WhatsApp
     * @return int|false ID notifikasi atau false jika gagal/tidak memenuhi syarat
     */
    public function createNotification($userId, $jenis, $judul, $pesan, $linkUrl, $refTipe = 'keluhan', $refId = null, $waGenericMessage = null) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        // 1. Validasi server-side: Akun aktif dengan role 'pengurus' atau 'santri'
        $this->db->query("SELECT id, role, status FROM users WHERE id = :uid LIMIT 1");
        $this->db->bind('uid', $userId);
        $user = $this->db->single();

        if (!$user || $user['status'] !== 'active' || !in_array($user['role'], ['pengurus', 'santri'])) {
            return false;
        }

        // 2. Pemeriksaan Idempotensi: Cegah notifikasi duplikat untuk peristiwa yang sama
        if ($refId !== null) {
            $this->db->query("SELECT id FROM notifikasi 
                              WHERE user_id = :uid 
                                AND jenis = :jenis 
                                AND referensi_tipe = :ref_tipe 
                                AND referensi_id = :ref_id 
                              LIMIT 1");
            $this->db->bind('uid', $userId);
            $this->db->bind('jenis', $jenis);
            $this->db->bind('ref_tipe', $refTipe);
            $this->db->bind('ref_id', (int)$refId);
            $existing = $this->db->single();

            if ($existing) {
                return (int)$existing['id']; // Idempotent: kembalikan ID yang sudah ada
            }
        }

        // 3. Simpan notifikasi in-app
        try {
            $this->db->query("INSERT INTO notifikasi (user_id, jenis, judul, pesan, link_url, referensi_tipe, referensi_id, created_at) 
                              VALUES (:uid, :jenis, :judul, :pesan, :link_url, :ref_tipe, :ref_id, NOW())");
            $this->db->bind('uid', $userId);
            $this->db->bind('jenis', $jenis);
            $this->db->bind('judul', $judul);
            $this->db->bind('pesan', $pesan);
            $this->db->bind('link_url', $linkUrl);
            $this->db->bind('ref_tipe', $refTipe);
            $this->db->bind('ref_id', $refId !== null ? (int)$refId : null);
            $this->db->execute();

            $notifId = (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            // HARDENING: Tangani duplicate-key error (SQLSTATE 23000) jika terjadi race-condition bersamaan
            if ($refId !== null && ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false)) {
                $this->db->query("SELECT id FROM notifikasi 
                                  WHERE user_id = :uid 
                                    AND jenis = :jenis 
                                    AND referensi_tipe = :ref_tipe 
                                    AND referensi_id = :ref_id 
                                  LIMIT 1");
                $this->db->bind('uid', $userId);
                $this->db->bind('jenis', $jenis);
                $this->db->bind('ref_tipe', $refTipe);
                $this->db->bind('ref_id', (int)$refId);
                $existing = $this->db->single();

                if ($existing) {
                    // Karena sudah tersimpan oleh proses paralel, kembalikan ID yang ada secara idempoten
                    return (int)$existing['id'];
                }
            }

            error_log('[YukSehat Notifikasi Error] ' . $e->getMessage());
            return false;
        }

        // 4. Persiapan WhatsApp Queue (Safe Mode / Dry-Run Preparation & Audit Trail)
        // KETENTUAN: Tidak memanggil endpoint /send, tidak ada worker otomatis, dan pesan WA wajib generik (Zero Medical Details)
        try {
            // Ambil nomor HP dari profil pengurus atau santri sesuai role
            if ($user['role'] === 'pengurus') {
                $this->db->query("SELECT no_hp FROM pengurus WHERE user_id = :uid LIMIT 1");
            } else {
                $this->db->query("SELECT no_hp FROM santri WHERE user_id = :uid LIMIT 1");
            }
            $this->db->bind('uid', $userId);
            $profile = $this->db->single();

            $safeWaText = !empty($waGenericMessage)
                ? $waGenericMessage
                : "Halo,\n\nTerdapat pemberitahuan keluhan santri di sistem YukSehat. Silakan login ke aplikasi untuk melihat detailnya.\n\nTerima kasih.";

            $rawPhone = ($profile && isset($profile['no_hp'])) ? trim((string)$profile['no_hp']) : '';
            $formattedPhone = !empty($rawPhone) ? FonnteService::formatPhoneNumber($rawPhone) : '';

            // Validasi kelayakan nomor WhatsApp: harus ada, hanya digit, dan panjang valid 9-16 digit
            $isValidPhone = (!empty($formattedPhone) && strlen($formattedPhone) >= 9 && strlen($formattedPhone) <= 16);

            if ($isValidPhone) {
                // Kasus A: Nomor Valid -> Rekam antrean berstatus 'pending' untuk diproses oleh background worker
                $this->db->query("INSERT INTO notifikasi_wa_queue (notifikasi_id, user_id, nomor_tujuan, pesan, status, provider_status, percobaan, max_percobaan, created_at) 
                                  VALUES (:nid, :uid, :nomor, :pesan, 'pending', 'queued', 0, 3, NOW())");
                $this->db->bind('nid', $notifId);
                $this->db->bind('uid', $userId);
                $this->db->bind('nomor', $formattedPhone);
                $this->db->bind('pesan', $safeWaText);
                $this->db->execute();

                // Auto-dispatch instan jika mode WhatsApp aktif
                if (WaQueueService::isWhatsAppEnabled()) {
                    WaQueueService::processBatch(5);
                }
            } else {
                // Kasus B: Nomor Kosong atau Format Tidak Valid -> Catat kegagalan ke antrean untuk jejak audit
                $destPlaceholder = !empty($rawPhone) ? 'invalid_format' : 'not_available';
                $roleLabel = ($user['role'] === 'pengurus') ? 'pengurus' : 'santri';
                $errorMessage = "Nomor WhatsApp {$roleLabel} tidak terdaftar atau format tidak valid";

                $this->db->query("INSERT INTO notifikasi_wa_queue (notifikasi_id, user_id, nomor_tujuan, pesan, status, provider_status, percobaan, max_percobaan, error_message, created_at) 
                                  VALUES (:nid, :uid, :nomor, :pesan, 'failed', 'validation_error', 0, 3, :err, NOW())");
                $this->db->bind('nid', $notifId);
                $this->db->bind('uid', $userId);
                $this->db->bind('nomor', $destPlaceholder);
                $this->db->bind('pesan', $safeWaText);
                $this->db->bind('err', $errorMessage);
                $this->db->execute();

                // Catat ke log server secara aman tanpa mencantumkan nomor telepon lengkap
                error_log('[YukSehat WA Audit] User ID #' . $userId . ' status antrean: failed - ' . $errorMessage);
            }
        } catch (Exception $e) {
            // Fail-safe & Concurrency Protection:
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                error_log('[YukSehat WA Audit] Antrean WA untuk notifikasi ID #' . $notifId . ' sudah dibuat oleh proses paralel (idempoten).');
            } else {
                // Kegagalan antrean WhatsApp tidak boleh membatalkan penyimpanan notifikasi in-app
                error_log('[YukSehat WA Queue Preparation Safe Warning] Terjadi kendala saat menyimpan antrean: ' . $e->getMessage());
            }
        }

        return $notifId;
    }

    /**
     * Menghitung total notifikasi yang belum dibaca untuk user pengurus
     */
    public function getUnreadCount($userId) {
        $this->db->query("SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = :uid AND dibaca_pada IS NULL");
        $this->db->bind('uid', (int)$userId);
        $res = $this->db->single();
        return $res ? (int)$res['total'] : 0;
    }

    /**
     * Mengambil daftar notifikasi terbaru untuk preview dropdown navbar
     */
    public function getRecentNotifications($userId, $limit = 5) {
        $limit = max(1, min(10, (int)$limit));
        $this->db->query("SELECT * FROM notifikasi WHERE user_id = :uid ORDER BY created_at DESC LIMIT $limit");
        $this->db->bind('uid', (int)$userId);
        return $this->db->resultSet();
    }

    /**
     * Mengambil daftar seluruh notifikasi dengan paginasi dan filter
     * Diperkaya dengan relasi data keluhan, santri, pengurus, dan hasil pemeriksaan
     */
    public function getAllNotifications($userId, $limit = 15, $offset = 0, $filter = '') {
        $limit = max(1, min(50, (int)$limit));
        $offset = max(0, (int)$offset);

        $sql = "SELECT n.*,
                       rk.keluhan AS keluhan_teks,
                       rk.status AS keluhan_status,
                       rk.tanggal_keluhan,
                       rk.santri_id AS keluhan_santri_id,
                       s.nama AS nama_santri,
                       s.nis AS nis_santri,
                       s.kelas AS kelas_santri,
                       s.kamar AS kamar_santri,
                       p_assigned.nama AS nama_petugas,
                       pk.id AS pemeriksaan_id,
                       pk.tanggal_pemeriksaan,
                       pk.suhu,
                       pk.tekanan_darah,
                       pk.berat_badan,
                       pk.tinggi_badan,
                       pk.hasil_pemeriksaan,
                       pk.tindakan,
                       pk.obat,
                       pk.catatan AS catatan_medis
                FROM notifikasi n
                LEFT JOIN riwayat_keluhan rk ON (n.referensi_tipe = 'keluhan' AND n.referensi_id = rk.id)
                LEFT JOIN santri s ON (rk.santri_id = s.id OR (n.referensi_tipe = 'santri' AND n.referensi_id = s.id))
                LEFT JOIN users u_assigned ON rk.assigned_to = u_assigned.id
                LEFT JOIN pengurus p_assigned ON u_assigned.id = p_assigned.user_id
                LEFT JOIN (
                    SELECT pk1.* FROM pemeriksaan_kesehatan pk1
                    INNER JOIN (SELECT keluhan_id, MAX(id) AS max_id FROM pemeriksaan_kesehatan WHERE keluhan_id IS NOT NULL GROUP BY keluhan_id) pk2
                    ON pk1.id = pk2.max_id
                ) pk ON (pk.keluhan_id = rk.id OR (n.referensi_tipe = 'pemeriksaan' AND n.referensi_id = pk.id))
                WHERE n.user_id = :uid";

        if ($filter === 'unread') {
            $sql .= " AND n.dibaca_pada IS NULL";
        }
        $sql .= " ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset";

        $this->db->query($sql);
        $this->db->bind('uid', (int)$userId);
        return $this->db->resultSet();
    }

    /**
     * Menghitung total notifikasi untuk paginasi
     */
    public function countNotifications($userId, $filter = '') {
        $sql = "SELECT COUNT(*) AS total FROM notifikasi WHERE user_id = :uid";
        if ($filter === 'unread') {
            $sql .= " AND dibaca_pada IS NULL";
        }
        $this->db->query($sql);
        $this->db->bind('uid', (int)$userId);
        $res = $this->db->single();
        return $res ? (int)$res['total'] : 0;
    }

    /**
     * Memeriksa notifikasi spesifik dengan validasi kepemilikan ketat
     */
    public function getNotificationByIdAndUser($id, $userId) {
        $this->db->query("SELECT * FROM notifikasi WHERE id = :id AND user_id = :uid LIMIT 1");
        $this->db->bind('id', (int)$id);
        $this->db->bind('uid', (int)$userId);
        return $this->db->single();
    }

    /**
     * Menandai satu notifikasi sebagai telah dibaca
     */
    public function markAsRead($id, $userId) {
        $this->db->query("UPDATE notifikasi SET dibaca_pada = NOW() 
                          WHERE id = :id AND user_id = :uid AND dibaca_pada IS NULL");
        $this->db->bind('id', (int)$id);
        $this->db->bind('uid', (int)$userId);
        return $this->db->execute();
    }

    /**
     * Menandai semua notifikasi milik pengurus tertentu sebagai telah dibaca
     */
    public function markAllAsRead($userId) {
        $this->db->query("UPDATE notifikasi SET dibaca_pada = NOW() 
                          WHERE user_id = :uid AND dibaca_pada IS NULL");
        $this->db->bind('uid', (int)$userId);
        return $this->db->execute();
    }

    // =========================================================================
    //  DASHBOARD MONITORING ANTREAN WHATSAPP (READ-ONLY)
    // =========================================================================

    /**
     * Mengambil ringkasan statistik antrean WhatsApp
     */
    public function getWaQueueSummary() {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = 'reconciliation_required' THEN 1 ELSE 0 END) AS reconciliation_required,
                    SUM(CASE WHEN status = 'failed' AND nomor_tujuan = 'not_available' THEN 1 ELSE 0 END) AS failed_no_number,
                    SUM(CASE WHEN status = 'failed' AND nomor_tujuan = 'invalid_format' THEN 1 ELSE 0 END) AS failed_invalid_format
                FROM notifikasi_wa_queue";
        $this->db->query($sql);
        $res = $this->db->single();
        return [
            'total'                    => (int)($res['total'] ?? 0),
            'pending'                  => (int)($res['pending'] ?? 0),
            'processing'               => (int)($res['processing'] ?? 0),
            'sent'                     => (int)($res['sent'] ?? 0),
            'failed'                   => (int)($res['failed'] ?? 0),
            'reconciliation_required'   => (int)($res['reconciliation_required'] ?? 0),
            'failed_no_number'         => (int)($res['failed_no_number'] ?? 0),
            'failed_invalid_format'    => (int)($res['failed_invalid_format'] ?? 0),
        ];
    }

    /**
     * Mengambil daftar antrean WhatsApp dengan filter dan paginasi (Read-only)
     */
    public function getWaQueueList($filters = [], $limit = 15, $offset = 0) {
        $limit = max(1, min(100, (int)$limit));
        $offset = max(0, (int)$offset);

        $sql = "SELECT 
                    q.id,
                    q.notifikasi_id,
                    q.user_id,
                    q.nomor_tujuan,
                    q.pesan,
                    q.status,
                    q.provider_status,
                    q.provider_message_id,
                    q.error_message,
                    q.percobaan,
                    q.max_percobaan,
                    q.sent_at,
                    q.claimed_at,
                    q.next_retry_at,
                    q.created_at,
                    q.updated_at,
                    n.jenis AS jenis_notifikasi,
                    n.judul AS judul_notifikasi,
                    p.nama AS nama_pengurus,
                    p.jabatan AS jabatan_pengurus
                FROM notifikasi_wa_queue q
                LEFT JOIN notifikasi n ON q.notifikasi_id = n.id
                LEFT JOIN users u ON q.user_id = u.id
                LEFT JOIN pengurus p ON u.id = p.user_id
                WHERE 1=1";

        $binds = [];

        // Filter status antrean (pending, processing, sent, failed, reconciliation_required)
        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'processing', 'sent', 'failed', 'reconciliation_required'])) {
            $sql .= " AND q.status = :status";
            $binds['status'] = $filters['status'];
        }

        // Filter jenis notifikasi
        if (!empty($filters['jenis'])) {
            $sql .= " AND n.jenis = :jenis";
            $binds['jenis'] = $filters['jenis'];
        }

        // Filter rentang tanggal dibuat
        if (!empty($filters['tanggal_dari'])) {
            $sql .= " AND q.created_at >= :tgl_dari";
            $binds['tgl_dari'] = $filters['tanggal_dari'] . ' 00:00:00';
        }
        if (!empty($filters['tanggal_sampai'])) {
            $sql .= " AND q.created_at <= :tgl_sampai";
            $binds['tgl_sampai'] = $filters['tanggal_sampai'] . ' 23:59:59';
        }

        $sql .= " ORDER BY q.created_at DESC, q.id DESC LIMIT $limit OFFSET $offset";

        $this->db->query($sql);
        foreach ($binds as $key => $val) {
            $this->db->bind($key, $val);
        }

        return $this->db->resultSet();
    }

    /**
     * Menghitung total data antrean WhatsApp berdasarkan filter untuk paginasi
     */
    public function countWaQueueList($filters = []) {
        $sql = "SELECT COUNT(*) AS total 
                FROM notifikasi_wa_queue q
                LEFT JOIN notifikasi n ON q.notifikasi_id = n.id
                WHERE 1=1";

        $binds = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['pending', 'processing', 'sent', 'failed', 'reconciliation_required'])) {
            $sql .= " AND q.status = :status";
            $binds['status'] = $filters['status'];
        }

        if (!empty($filters['jenis'])) {
            $sql .= " AND n.jenis = :jenis";
            $binds['jenis'] = $filters['jenis'];
        }

        if (!empty($filters['tanggal_dari'])) {
            $sql .= " AND q.created_at >= :tgl_dari";
            $binds['tgl_dari'] = $filters['tanggal_dari'] . ' 00:00:00';
        }
        if (!empty($filters['tanggal_sampai'])) {
            $sql .= " AND q.created_at <= :tgl_sampai";
            $binds['tgl_sampai'] = $filters['tanggal_sampai'] . ' 23:59:59';
        }

        $this->db->query($sql);
        foreach ($binds as $key => $val) {
            $this->db->bind($key, $val);
        }

        $res = $this->db->single();
        return $res ? (int)$res['total'] : 0;
    }

    /**
     * Masking nomor telepon untuk tampilan aman tanpa mengekspos nomor lengkap
     */
    public static function maskPhoneNumber($phone) {
        $clean = trim((string)$phone);
        if (empty($clean) || $clean === 'not_available' || $clean === '-') {
            return '<span class="badge bg-secondary-subtle text-secondary border">Nomor Tidak Tersedia</span>';
        }
        if ($clean === 'invalid_format') {
            return '<span class="badge bg-danger-subtle text-danger border">Format Tidak Valid</span>';
        }

        $digits = preg_replace('/[^0-9]/', '', $clean);
        $len = strlen($digits);
        if ($len >= 8) {
            $start = substr($digits, 0, 4);
            $end = substr($digits, -3);
            $mask = str_repeat('*', max(3, $len - 7));
            return htmlspecialchars($start . $mask . $end);
        }
        return '<span class="badge bg-warning-subtle text-warning border">Nomor Tersembunyi</span>';
    }

    // =========================================================================
    //  FITUR SINGLE WHATSAPP TEST (SIMULASI SAFE MODE ONLY)
    // =========================================================================

    /**
     * Mengambil daftar akun pengurus aktif untuk pemilihan target uji di antarmuka Admin.
     * PRIVACY HARDENING (H-02): Nomor telepon lengkap DILARANG dikirimkan ke view/presentasi.
     * Masking diterapkan langsung pada model layer dan field no_hp mentah dihapus.
     */
    public function getPengurusActiveList() {
        $this->db->query("SELECT u.id AS user_id, p.nama, p.jabatan, p.no_hp 
                          FROM users u 
                          JOIN pengurus p ON u.id = p.user_id 
                          WHERE u.role = 'pengurus' AND u.status = 'active' 
                          ORDER BY p.nama ASC");
        $results = $this->db->resultSet();
        foreach ($results as &$item) {
            $rawPhone = $item['no_hp'] ?? '';
            $item['no_hp_masked'] = self::maskPhoneNumber($rawPhone);
            unset($item['no_hp']); // Hapus nomor telepon lengkap mentah dari array presentasi
        }
        unset($item);
        return $results;
    }

    /**
     * Mengambil riwayat pengujian pesan tunggal dari tabel wa_test_audit
     */
    public function getRecentWaTestAudit($limit = 10) {
        $limit = max(1, min(50, (int)$limit));
        $sql = "SELECT 
                    t.*,
                    adm.username AS admin_username,
                    p.nama AS nama_pengurus,
                    p.jabatan AS jabatan_pengurus
                FROM wa_test_audit t
                LEFT JOIN users adm ON t.admin_user_id = adm.id
                LEFT JOIN users u_pgr ON t.pengurus_user_id = u_pgr.id
                LEFT JOIN pengurus p ON u_pgr.id = p.user_id
                ORDER BY t.created_at DESC, t.id DESC
                LIMIT $limit";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    /**
     * Memeriksa rate limit global: Apakah pengujian pernah dijalankan dalam kurun $minutes menit terakhir.
     * Bersifat global untuk seluruh Administrator sistem (kebijakan pengetatan keamanan audit).
     *
     * @param int $minutes Default 15 menit
     * @return bool True jika rate limit aktif (pengujian ditolak), false jika diizinkan
     */
    public function checkWaTestRateLimit($minutes = 15) {
        $minutes = max(1, (int)$minutes);
        $this->db->query("SELECT id, created_at FROM wa_test_audit 
                          WHERE created_at >= (NOW() - INTERVAL :min MINUTE) 
                          ORDER BY created_at DESC LIMIT 1");
        $this->db->bind('min', $minutes);
        $res = $this->db->single();
        return !empty($res);
    }

    /**
     * Menjalankan pengujian satu pesan dalam SIMULASI SAFE MODE ONLY.
     * LARANGAN MUTLAK: Tidak memanggil FonnteService::send(), tidak ada koneksi HTTP ke gateway,
     * dan tidak mengubah antrean operasional notifikasi_wa_queue.
     *
     * @param int $adminUserId ID Admin yang mengeksekusi
     * @param int $pengurusUserId ID Pengurus penerima izin
     * @param string $idempotencyToken Token unik per request untuk mencegah eksekusi paralel
     * @param string $simulationOutcome Pilihan hasil simulasi ('sent', 'failed', 'timeout')
     * @return array Hasil simulasi
     */
    public function executeSingleTestSimulated($adminUserId, $pengurusUserId, $idempotencyToken, $simulationOutcome = 'sent') {
        $adminUserId = (int)$adminUserId;
        $pengurusUserId = (int)$pengurusUserId;

        if ($adminUserId <= 0 || $pengurusUserId <= 0) {
            return ['success' => false, 'error' => 'Parameter ID Admin atau Pengurus tidak valid.'];
        }

        // 1. Verifikasi Penerima: Wajib Akun Pengurus Aktif (Santri ditolak mutlak)
        $this->db->query("SELECT u.id, u.role, u.status, p.nama, p.no_hp 
                          FROM users u 
                          JOIN pengurus p ON u.id = p.user_id 
                          WHERE u.id = :uid LIMIT 1");
        $this->db->bind('uid', $pengurusUserId);
        $targetUser = $this->db->single();

        if (!$targetUser || $targetUser['role'] !== 'pengurus' || $targetUser['status'] !== 'active') {
            return ['success' => false, 'error' => 'Penerima yang dipilih bukan akun Pengurus aktif.'];
        }

        // 2. Verifikasi Nomor Telepon Pengurus: Hanya untuk validasi internal
        $rawPhone = trim((string)($targetUser['no_hp'] ?? ''));
        $formattedPhone = !empty($rawPhone) ? FonnteService::formatPhoneNumber($rawPhone) : '';

        if (empty($formattedPhone) || strlen($formattedPhone) < 9 || strlen($formattedPhone) > 16) {
            return ['success' => false, 'error' => 'Nomor WhatsApp pengurus kosong atau tidak valid (minimal 9 digit numerik).'];
        }

        // Masking nomor telepon untuk audit log (nomor mentah lengkap DILARANG disimpan ke audit)
        $len = strlen($formattedPhone);
        $start = substr($formattedPhone, 0, 4);
        $end = substr($formattedPhone, -3);
        $maskedPhone = $start . '****' . $end;

        // 3. Template Pesan Generik Statis (Server-side, tidak menerima input bebas)
        $staticMessage = "[YukSehat Poskestren] Uji Coba Gateway WhatsApp (Simulasi Safe Mode).\nStatus: Sistem Notifikasi Penugasan Berfungsi Normal.\n(Pesan Uji Simulasi Terbatas)";

        // 4. Catat Info Jaringan Pengakses (L-02: Sanitasi Kontrol Karakter & Batas Panjang)
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $rawUa = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        // Normalisasi: Hapus karakter kontrol (ASCII 0-31 dan 127) yang tidak diperlukan
        $cleanUa = preg_replace('/[\x00-\x1F\x7F]/u', '', (string)$rawUa);
        $userAgent = mb_substr(trim($cleanUa), 0, 255, 'UTF-8');
        if (empty($userAgent)) {
            $userAgent = 'Unknown';
        }

        // 5. Atomic Insertion ke tabel wa_test_audit (Mekanisme Single-Shot Concurrency Lock)
        try {
            $this->db->query("INSERT INTO wa_test_audit (admin_user_id, pengurus_user_id, nomor_tujuan_masked, pesan, status, idempotency_token, ip_address, user_agent, created_at) 
                              VALUES (:admin_id, :pengurus_id, :masked, :pesan, 'in_progress', :idemp, :ip, :ua, NOW())");
            $this->db->bind('admin_id', $adminUserId);
            $this->db->bind('pengurus_id', $pengurusUserId);
            $this->db->bind('masked', $maskedPhone);
            $this->db->bind('pesan', $staticMessage);
            $this->db->bind('idemp', $idempotencyToken);
            $this->db->bind('ip', $ipAddress);
            $this->db->bind('ua', $userAgent);
            $this->db->execute();

            $auditId = (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            // Jika duplicate key pada uniq_wa_test_idempotency, gagalkan request paralel
            if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                return ['success' => false, 'error' => 'Request paralel atau token idempotensi ganda terdeteksi. Eksepsi dicegah.'];
            }
            error_log('[YukSehat Single Test Error] ' . $e->getMessage());
            return ['success' => false, 'error' => 'Gagal menginisialisasi audit uji coba: ' . $e->getMessage()];
        }

        // 6. SIMULASI SAFE MODE ONLY (Tanpa menghubungi Fonnte API)
        // Penandaan eksplisit bahwa hasil ini adalah simulasi internal
        if ($simulationOutcome === 'failed') {
            $simErrorMessage = 'Simulasi kegagalan: Gateway merespons error verifikasi perangkat.';
            $this->db->query("UPDATE wa_test_audit SET status = 'failed', provider_status = 'simulated_test_failed', error_message = :err, executed_at = NOW() WHERE id = :id");
            $this->db->bind('err', $simErrorMessage);
            $this->db->bind('id', $auditId);
            $this->db->execute();

            return [
                'success' => false,
                'status'  => 'failed',
                'simulated' => true,
                'audit_id' => $auditId,
                'message' => 'Simulasi status failed berhasil dicatat ke audit log.'
            ];
        } elseif ($simulationOutcome === 'timeout') {
            $simTimeoutMessage = 'Simulasi batas waktu terlampaui (15 detik). Status akhir ambigu di gateway, dilarang melakukan retry otomatis.';
            $this->db->query("UPDATE wa_test_audit SET status = 'timeout', provider_status = 'simulated_test_timeout', error_message = :err, executed_at = NOW() WHERE id = :id");
            $this->db->bind('err', $simTimeoutMessage);
            $this->db->bind('id', $auditId);
            $this->db->execute();

            return [
                'success' => false,
                'status'  => 'timeout',
                'simulated' => true,
                'audit_id' => $auditId,
                'message' => 'Simulasi status timeout berhasil dicatat. Status ditandai ambigu tanpa retry.'
            ];
        } else {
            // Default: Simulasi Berhasil (sent)
            $simMessageId = 'sim_msg_' . bin2hex(random_bytes(8));
            $this->db->query("UPDATE wa_test_audit SET status = 'sent', provider_status = 'simulated_test_sent', provider_message_id = :mid, executed_at = NOW() WHERE id = :id");
            $this->db->bind('mid', $simMessageId);
            $this->db->bind('id', $auditId);
            $this->db->execute();

            return [
                'success' => true,
                'status'  => 'sent',
                'simulated' => true,
                'audit_id' => $auditId,
                'message' => 'Simulasi pengujian 1 pesan berhasil dijalankan dan tercatat ke wa_test_audit. (Safe Mode Aktif - Tidak ada pesan WhatsApp sungguhan yang dikirim).'
            ];
        }
    }
}

