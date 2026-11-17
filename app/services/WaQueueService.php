<?php
/**
 * YukSehat WhatsApp Queue Processor Service
 * Native PHP MVC + MySQL
 * 
 * Prinsip Desain & Keamanan:
 * - Atomic Claiming: Mencegah race condition antar-worker konkuren tanpa mengunci tabel lama.
 * - Non-Blocking HTTP: Tidak menahan transaksi database terbuka saat request gateway berlangsung.
 * - Controlled Activation: Mengikuti switch WHATSAPP_ENABLED (default: false).
 * - Bounded Retry & Exponential Backoff: Menghindari infinite loop dan spamming gateway.
 * - Strict Privacy: Tidak mencatat token API, pesan keluhan medis, atau nomor HP lengkap di log.
 * - Idempotency & In-App Preservation: Notifikasi in-app tetap utuh jika WA gagal.
 * - Hardening F-01: Uncertain Delivery State (reconciliation_required) untuk mencegah pesan duplikat saat worker crash.
 */

class WaQueueService {

    const MAX_PERCOBAAN_DEFAULT = 3;
    const STALE_LOCK_MINUTES = 5;

    // Skema jeda percobaan ulang (Exponential Backoff dalam satuan detik)
    // Percobaan ke-1 gagal -> tunggu 60 detik (1 menit)
    // Percobaan ke-2 gagal -> tunggu 300 detik (5 menit)
    // Percobaan ke-3 gagal -> berhenti (status: failed)
    const RETRY_BACKOFF_SECONDS = [
        1 => 60,
        2 => 300,
        3 => 900
    ];

    /**
     * Memeriksa apakah mode pengiriman WhatsApp diizinkan di konfigurasi server
     */
    public static function isWhatsAppEnabled() {
        if (defined('WHATSAPP_ENABLED')) {
            return (bool)WHATSAPP_ENABLED;
        }
        $val = $_ENV['WHATSAPP_ENABLED'] ?? $_SERVER['WHATSAPP_ENABLED'] ?? getenv('WHATSAPP_ENABLED');
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Mengambil ringkasan status konfigurasi dan worker untuk dashboard
     */
    public static function getWorkerStatusSummary() {
        $isEnabled = self::isWhatsAppEnabled();
        $isConfigured = FonnteService::isConfigured();

        return [
            'whatsapp_enabled' => $isEnabled,
            'is_configured'    => $isConfigured,
            'mode_label'       => $isEnabled ? 'Live Delivery Aktif' : 'Nonaktif (WHATSAPP_ENABLED=false)',
            'mode_badge_class' => $isEnabled ? 'bg-success text-white' : 'bg-secondary text-white',
            'worker_label'     => $isEnabled ? 'Siap Memproses (CLI / Cron)' : 'Dinonaktifkan Secara Aman'
        ];
    }

    /**
     * Memulihkan antrean yang macet (Stale Locks) akibat worker crash atau timeout tak terduga.
     * Mengembalikan status antrean 'processing' yang telah melewati batas waktu kembali ke 'pending'
     * atau menandainya 'reconciliation_required' / 'failed' secara aman.
     *
     * HARDENING F-01: Jika antrean sudah memiliki provider_message_id, maka gateway
     * telah menerima pesan tersebut. JANGAN reset ke pending secara buta karena akan
     * menyebabkan pengiriman duplikat! Tandai 'reconciliation_required'.
     *
     * @param int $timeoutMinutes Batas waktu kadaluarsa claiming (default 5 menit)
     * @return int Jumlah antrean yang dipulihkan
     */
    public static function recoverStaleLocks($timeoutMinutes = self::STALE_LOCK_MINUTES) {
        $db = new Database();
        $timeoutMinutes = max(1, (int)$timeoutMinutes);

        // 1. Temukan item yang macet
        $db->query("SELECT id, percobaan, max_percobaan, provider_message_id FROM notifikasi_wa_queue 
                    WHERE status = 'processing' 
                      AND claimed_at < DATE_SUB(NOW(), INTERVAL :mins MINUTE)");
        $db->bind('mins', $timeoutMinutes);
        $staleItems = $db->resultSet();

        $recovered = 0;
        foreach ($staleItems as $item) {
            $id = (int)$item['id'];
            $newPercobaan = (int)$item['percobaan'] + 1;
            $maxPercobaan = (int)($item['max_percobaan'] ?? self::MAX_PERCOBAAN_DEFAULT);
            $hasProviderId = !empty($item['provider_message_id']);

            // HARDENING F-01: Cegah pengiriman duplikat jika provider ID sudah ada
            if ($hasProviderId) {
                $db->query("UPDATE notifikasi_wa_queue 
                            SET status = 'reconciliation_required', 
                                error_message = 'Worker timeout saat pemrosesan. Gateway telah menerima pesan (ID terlampir), status lokal belum terkonfirmasi. Memerlukan rekonsiliasi administratif.', 
                                provider_status = 'lock_recovered_uncertain', 
                                claimed_at = NULL, 
                                updated_at = NOW() 
                            WHERE id = :id AND status = 'processing'");
                $db->bind('id', $id);
                $db->execute();
                $recovered++;
                continue;
            }

            if ($newPercobaan >= $maxPercobaan) {
                // Lewati batas -> tandai failed
                $db->query("UPDATE notifikasi_wa_queue 
                            SET status = 'failed', 
                                percobaan = :percobaan, 
                                error_message = 'Worker timeout: proses terputus saat mengeksekusi antrean', 
                                provider_status = 'worker_timeout', 
                                claimed_at = NULL, 
                                updated_at = NOW() 
                            WHERE id = :id AND status = 'processing'");
                $db->bind('percobaan', $newPercobaan);
                $db->bind('id', $id);
                $db->execute();
            } else {
                // Kembalikan ke pending dengan backoff
                $backoff = self::RETRY_BACKOFF_SECONDS[$newPercobaan] ?? 300;
                $db->query("UPDATE notifikasi_wa_queue 
                            SET status = 'pending', 
                                percobaan = :percobaan, 
                                error_message = 'Worker lock kadaluarsa, antrean dijadwalkan ulang secara otomatis', 
                                provider_status = 'lock_recovered', 
                                next_retry_at = DATE_ADD(NOW(), INTERVAL :backoff SECOND), 
                                claimed_at = NULL, 
                                updated_at = NOW() 
                            WHERE id = :id AND status = 'processing'");
                $db->bind('backoff', $backoff);
                $db->bind('percobaan', $newPercobaan);
                $db->bind('id', $id);
                $db->execute();
            }
            $recovered++;
        }

        if ($recovered > 0) {
            error_log("[YukSehat WA Worker] Berhasil memulihkan {$recovered} antrean WhatsApp yang macet.");
        }

        return $recovered;
    }

    /**
     * Memproses batch antrean notifikasi WhatsApp yang berstatus 'pending'.
     *
     * @param int $limit Jumlah maksimum pesan per batch
     * @param array $options Opsi tambahan:
     *        - 'force' (bool): jika true, abaikan cek WHATSAPP_ENABLED (hanya untuk pengujian terkontrol)
     *        - 'dry_run' (bool): jika true, teruskan ke FonnteService sebagai dry-run
     * @return array Rekapitulasi hasil pemrosesan batch
     */
    public static function processBatch($limit = 10, array $options = []) {
        $limit = max(1, min(50, (int)$limit));
        $isEnabled = self::isWhatsAppEnabled();
        $isForce = !empty($options['force']);

        // SAFE GUARD: Tolak pengiriman nyata jika fitur belum diaktifkan di konfigurasi
        if (!$isEnabled && !$isForce) {
            return [
                'status'    => 'disabled',
                'message'   => 'Pengiriman WhatsApp sedang dinonaktifkan dalam konfigurasi (WHATSAPP_ENABLED=false). Pengiriman hanya dapat diaktifkan setelah persetujuan Administrator.',
                'processed' => 0,
                'sent'      => 0,
                'failed'    => 0,
                'retried'   => 0
            ];
        }

        // Cek token Fonnte
        if (!FonnteService::isConfigured()) {
            return [
                'status'    => 'unconfigured',
                'message'   => 'Konfigurasi FONNTE_TOKEN belum disetel pada file .env.',
                'processed' => 0,
                'sent'      => 0,
                'failed'    => 0,
                'retried'   => 0
            ];
        }

        // 1. Pulihkan antrean macet sebelum memproses
        self::recoverStaleLocks();

        $db = new Database();

        // 2. Ambil kandidat antrean yang berhak dieksekusi:
        // - status = 'pending'
        // - next_retry_at IS NULL atau sudah terlewati (<= NOW())
        // - percobaan < max_percobaan
        // CATATAN: status 'reconciliation_required' TIDAK AKAN PERNAH diambil oleh worker ini secara otomatis!
        $db->query("SELECT id FROM notifikasi_wa_queue 
                    WHERE status = 'pending' 
                      AND (next_retry_at IS NULL OR next_retry_at <= NOW()) 
                      AND percobaan < max_percobaan 
                    ORDER BY id ASC 
                    LIMIT :limit");
        $db->bind('limit', $limit);
        $candidates = $db->resultSet();

        if (empty($candidates)) {
            return [
                'status'    => 'idle',
                'message'   => 'Tidak ada antrean pending yang siap diproses saat ini.',
                'processed' => 0,
                'sent'      => 0,
                'failed'    => 0,
                'retried'   => 0
            ];
        }

        $sentCount = 0;
        $failedCount = 0;
        $retriedCount = 0;
        $processedCount = 0;

        foreach ($candidates as $candidate) {
            $queueId = (int)$candidate['id'];

            // 3. ATOMIC CLAIMING (Database Lock tanpa menahan transaksi panjang)
            // Update status menjadi 'processing' secara atomik. Jika ada worker paralel yang
            // telah mendahului mengklaim, affected rows akan bernilai 0 sehingga worker ini melewatinya.
            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'processing', 
                            claimed_at = NOW(), 
                            updated_at = NOW() 
                        WHERE id = :id AND status = 'pending'");
            $db->bind('id', $queueId);
            $db->execute();

            if ($db->rowCount() === 0) {
                // Sudah diklaim oleh worker paralel, lewati
                continue;
            }

            // 4. Ambil data antrean yang berhasil diklaim
            $db->query("SELECT * FROM notifikasi_wa_queue WHERE id = :id LIMIT 1");
            $db->bind('id', $queueId);
            $item = $db->single();

            if (!$item) {
                continue;
            }

            $processedCount++;

            // 5. Eksekusi pengiriman satu antrean (NON-BLOCKING DB: transaksi DB sudah selesai)
            $result = self::processSingleQueueItem($item, $options);

            if ($result['status'] === 'sent') {
                $sentCount++;
            } elseif ($result['status'] === 'retried') {
                $retriedCount++;
            } else {
                $failedCount++;
            }
        }

        return [
            'status'    => 'success',
            'message'   => "Batch selesai: {$processedCount} diproses, {$sentCount} terkirim, {$retriedCount} dijadwal-ulang, {$failedCount} gagal.",
            'processed' => $processedCount,
            'sent'      => $sentCount,
            'failed'    => $failedCount,
            'retried'   => $retriedCount
        ];
    }

    /**
     * Memproses satu antrean notifikasi WhatsApp yang telah diklaim ('processing')
     * 
     * @param array $item Baris data dari notifikasi_wa_queue
     * @param array $options Opsi tambahan (dry_run, dll)
     * @return array Ringkasan hasil pemrosesan ('status' => 'sent'|'retried'|'failed'|'reconciliation_required')
     */
    public static function processSingleQueueItem(array $item, array $options = []) {
        $db = new Database();
        $id = (int)$item['id'];
        $nomor = trim((string)$item['nomor_tujuan']);
        $pesan = trim((string)$item['pesan']);
        $currentPercobaan = (int)($item['percobaan'] ?? 0);
        $maxPercobaan = (int)($item['max_percobaan'] ?? self::MAX_PERCOBAAN_DEFAULT);
        $nextPercobaan = $currentPercobaan + 1;

        // 1. Validasi Permanen: Nomor Tujuan
        $cleanPhone = FonnteService::formatPhoneNumber($nomor);
        if (empty($nomor) || in_array($nomor, ['not_available', 'invalid_format']) || strlen($cleanPhone) < 9) {
            $errorMessage = 'Nomor WhatsApp pengurus tidak valid atau tidak tersedia';
            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'failed', 
                            percobaan = :percobaan, 
                            error_message = :err, 
                            provider_status = 'validation_error', 
                            claimed_at = NULL, 
                            updated_at = NOW() 
                        WHERE id = :id");
            $db->bind('percobaan', $nextPercobaan);
            $db->bind('err', $errorMessage);
            $db->bind('id', $id);
            $db->execute();

            return ['status' => 'failed', 'error' => $errorMessage];
        }

        // 2. Kirim pesan melalui FonnteService
        // Perhatian: Ini adalah panggilan HTTP keluar (External Gateway Request).
        // Transaksi database TIDAK SEDANG BERJALAN di sini untuk mencegah pool starvation.
        try {
            $sendRes = FonnteService::send($cleanPhone, $pesan, $options);
        } catch (Exception $e) {
            $sendRes = [
                'success'         => false,
                'message'         => 'Pengecualian saat memanggil gateway: ' . $e->getMessage(),
                'provider_status' => 'exception_error'
            ];
        }

        // 3. Evaluasi Hasil Gateway
        if (!empty($sendRes['success'])) {
            // Berhasil Terkirim / Gateway Menerima
            $providerStatus = $sendRes['provider_status'] ?? 'accepted';
            $msgId = $sendRes['provider_message_id'] ?? null;

            try {
                $db->query("UPDATE notifikasi_wa_queue 
                            SET status = 'sent', 
                                percobaan = :percobaan, 
                                sent_at = NOW(), 
                                provider_status = :prov, 
                                provider_message_id = :p_id, 
                                error_message = NULL, 
                                claimed_at = NULL, 
                                updated_at = NOW() 
                            WHERE id = :id");
                $db->bind('percobaan', $nextPercobaan);
                $db->bind('prov', $providerStatus);
                $db->bind('p_id', $msgId);
                $db->bind('id', $id);
                $db->execute();

                return ['status' => 'sent', 'provider_message_id' => $msgId];
            } catch (Exception $dbEx) {
                // HARDENING F-01: Gateway menerima pesan, namun update DB lokal gagal.
                // JANGAN biarkan status menggantung di 'processing' untuk di-reset ke 'pending'!
                // Simpan provider_message_id dan tandai 'reconciliation_required'.
                error_log("[YukSehat WA Worker CRITICAL F-01] Gateway menerima antrean #{$id} (Provider ID: {$msgId}), tetapi update database lokal gagal: " . $dbEx->getMessage());

                try {
                    $emergencyDb = new Database();
                    $emergencyDb->query("UPDATE notifikasi_wa_queue 
                                         SET status = 'reconciliation_required', 
                                             percobaan = :percobaan, 
                                             provider_status = 'gateway_accepted_db_error', 
                                             provider_message_id = :p_id, 
                                             error_message = :err, 
                                             claimed_at = NULL, 
                                             updated_at = NOW() 
                                         WHERE id = :id");
                    $emergencyDb->bind('percobaan', $nextPercobaan);
                    $emergencyDb->bind('p_id', $msgId);
                    $emergencyDb->bind('err', 'Pesan telah diterima gateway Fonnte (ID: ' . ($msgId ?: '-') . '), namun terjadi kendala saat pembaruan database lokal. Memerlukan rekonsiliasi administratif sebelum tindakan lanjutan.');
                    $emergencyDb->bind('id', $id);
                    $emergencyDb->execute();
                } catch (Exception $emEx) {
                    error_log("[YukSehat WA Worker CRITICAL F-01] Pembaruan darurat ke reconciliation_required gagal: " . $emEx->getMessage());
                }

                return [
                    'status'              => 'reconciliation_required',
                    'error'               => 'Pesan diterima gateway namun pembaruan DB lokal gagal: ' . $dbEx->getMessage(),
                    'provider_message_id' => $msgId
                ];
            }
        }

        // 4. Penanganan Kegagalan: Bedakan Error Permanen vs Error Sementara (Retryable)
        $errorMessage = $sendRes['message'] ?? 'Pengiriman gateway gagal';
        $httpCode = (int)($sendRes['http_code'] ?? 0);
        $providerStatus = $sendRes['provider_status'] ?? 'gateway_error';

        $isPermanent = self::isPermanentError($httpCode, $errorMessage);

        if (!$isPermanent && $nextPercobaan < $maxPercobaan) {
            // Error Sementara & Masih Memiliki Kuota Percobaan -> Jadwalkan Ulang dengan Backoff
            $backoffSeconds = self::RETRY_BACKOFF_SECONDS[$nextPercobaan] ?? 300;

            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'pending', 
                            percobaan = :percobaan, 
                            error_message = :err, 
                            provider_status = :prov, 
                            next_retry_at = DATE_ADD(NOW(), INTERVAL :backoff SECOND), 
                            claimed_at = NULL, 
                            updated_at = NOW() 
                        WHERE id = :id");
            $db->bind('percobaan', $nextPercobaan);
            $db->bind('err', $errorMessage);
            $db->bind('prov', $providerStatus);
            $db->bind('backoff', $backoffSeconds);
            $db->bind('id', $id);
            $db->execute();

            return ['status' => 'retried', 'error' => $errorMessage, 'next_attempt' => $nextPercobaan];
        }

        // Error Permanen atau Batas Maksimum Percobaan Tercapai -> Tandai Gagal
        $finalError = ($nextPercobaan >= $maxPercobaan && !$isPermanent)
            ? "Mencapai batas maksimum percobaan ({$maxPercobaan}x). Terakhir: {$errorMessage}"
            : $errorMessage;

        $db->query("UPDATE notifikasi_wa_queue 
                    SET status = 'failed', 
                        percobaan = :percobaan, 
                        error_message = :err, 
                        provider_status = :prov, 
                        claimed_at = NULL, 
                        updated_at = NOW() 
                    WHERE id = :id");
        $db->bind('percobaan', $nextPercobaan);
        $db->bind('err', $finalError);
        $db->bind('prov', $providerStatus);
        $db->bind('id', $id);
        $db->execute();

        return ['status' => 'failed', 'error' => $finalError];
    }

    /**
     * Memeriksa apakah suatu respon kesalahan bersifat permanen (tidak ada gunanya di-retry)
     */
    public static function isPermanentError($httpCode, $errorMessage) {
        $msg = strtolower((string)$errorMessage);

        // Kategori permanen:
        // - 401 / 403 (Autentikasi token salah)
        // - Format nomor salah / tidak terdaftar
        // - Kuota akun habis
        if ($httpCode === 401 || $httpCode === 403) {
            return true;
        }

        $permanentKeywords = [
            'invalid token',
            'token not registered',
            'unauthorized',
            'format nomor',
            'nomor whatsapp tidak valid',
            'target invalid',
            'country code',
            'quota expired',
            'package expired'
        ];

        foreach ($permanentKeywords as $kw) {
            if (strpos($msg, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rekonsiliasi manual oleh Administrator untuk antrean berstatus 'reconciliation_required' (F-01)
     *
     * @param int $id ID antrean di notifikasi_wa_queue
     * @param string $action 'mark_sent' | 'retry' | 'mark_failed'
     * @param string $notes Catatan administratif opsional
     * @return array Hasil rekonsiliasi
     */
    public static function reconcileItem($id, $action, $notes = '') {
        $id = (int)$id;
        $allowedActions = ['mark_sent', 'retry', 'mark_failed'];
        if (!in_array($action, $allowedActions)) {
            return ['success' => false, 'message' => 'Aksi rekonsiliasi tidak valid.'];
        }

        $db = new Database();
        $db->query("SELECT * FROM notifikasi_wa_queue WHERE id = :id LIMIT 1");
        $db->bind('id', $id);
        $item = $db->single();

        if (!$item) {
            return ['success' => false, 'message' => 'Data antrean tidak ditemukan.'];
        }

        if ($item['status'] !== 'reconciliation_required') {
            return ['success' => false, 'message' => 'Hanya antrean berstatus "reconciliation_required" yang dapat direkonsiliasi.'];
        }

        $noteSuffix = !empty($notes) ? " [Catatan: " . trim($notes) . "]" : "";

        if ($action === 'mark_sent') {
            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'sent', 
                            sent_at = NOW(), 
                            error_message = CONCAT(COALESCE(error_message, ''), ' [Rekonsiliasi Manual: Ditandai Terkirim', :note, ']'), 
                            claimed_at = NULL, 
                            updated_at = NOW() 
                        WHERE id = :id");
            $db->bind('note', $noteSuffix);
            $db->bind('id', $id);
            $db->execute();

            return ['success' => true, 'message' => "Antrean #WA-{$id} berhasil ditandai sebagai Terkirim (Sent) via rekonsiliasi manual."];
        } elseif ($action === 'retry') {
            // Administrator mengonfirmasi pesan belum sampai, diizinkan untuk dikirim ulang
            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'pending', 
                            next_retry_at = NOW(), 
                            error_message = CONCAT(COALESCE(error_message, ''), ' [Rekonsiliasi Manual: Dijadwalkan Ulang', :note, ']'), 
                            claimed_at = NULL, 
                            updated_at = NOW() 
                        WHERE id = :id");
            $db->bind('note', $noteSuffix);
            $db->bind('id', $id);
            $db->execute();

            return ['success' => true, 'message' => "Antrean #WA-{$id} berhasil dijadwalkan ulang ke status Pending via rekonsiliasi manual."];
        } else {
            // mark_failed
            $db->query("UPDATE notifikasi_wa_queue 
                        SET status = 'failed', 
                            error_message = CONCAT(COALESCE(error_message, ''), ' [Rekonsiliasi Manual: Dibatalkan/Gagal', :note, ']'), 
                            claimed_at = NULL, 
                            updated_at = NOW() 
                        WHERE id = :id");
            $db->bind('note', $noteSuffix);
            $db->bind('id', $id);
            $db->execute();

            return ['success' => true, 'message' => "Antrean #WA-{$id} berhasil ditandai sebagai Gagal (Failed) via rekonsiliasi manual."];
        }
    }
}
