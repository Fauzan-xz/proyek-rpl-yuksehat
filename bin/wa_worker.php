<?php
/**
 * YukSehat Background WhatsApp Queue Worker (CLI)
 * Digunakan untuk pemrosesan antrean pesan WhatsApp via Cron Job atau Task Scheduler.
 * 
 * Penggunaan CLI:
 *   php bin/wa_worker.php
 *   php bin/wa_worker.php --limit=20
 *   php bin/wa_worker.php --status
 *   php bin/wa_worker.php --dry-run
 * 
 * Keamanan:
 * - Dilarang keras dipanggil melalui browser (HTTP). Hanya berjalan di CLI.
 * - Mengikuti flag WHATSAPP_ENABLED dari .env.
 * - Non-blocking File Lock (flock) untuk mencegah proses overlapping antar-cron (F-02).
 */

// 1. Keamanan: Batasi eksekusi HANYA melalui CLI
if (php_sapi_name() !== 'cli' && empty($_SERVER['argv'])) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "403 Forbidden: Worker script ini hanya dapat dieksekusi melalui PHP CLI.\n";
    exit(1);
}

// 2. Proteksi Overlapping Worker via File Lock (F-02)
$lockFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yuksehat_wa_worker.lock';
$lockFp = @fopen($lockFilePath, 'c+');

if ($lockFp) {
    if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
        $now = date('Y-m-d H:i:s');
        echo "[{$now}] [SKIP] Proses worker lain sedang berjalan (lockfile aktif: {$lockFilePath}). Worker ini keluar dengan aman untuk mencegah tumpang-tindih (F-02).\n";
        fclose($lockFp);
        exit(0);
    }
}

// Daftarkan fungsi shutdown agar lock selalu dilepas dan resource dibersihkan dengan aman
register_shutdown_function(function() use (&$lockFp) {
    if ($lockFp && is_resource($lockFp)) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        $lockFp = null;
    }
});

// 3. Muat dependensi aplikasi
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/app/core/Database.php';
require_once dirname(__DIR__) . '/app/services/FonnteService.php';
require_once dirname(__DIR__) . '/app/services/WaQueueService.php';

// 4. Parsing Argumen CLI
$options = getopt('', ['limit::', 'force', 'dry-run', 'status', 'recover-only', 'help']);

if (isset($options['help'])) {
    echo "========================================================\n";
    echo "YukSehat WhatsApp Queue Processor (CLI Worker)\n";
    echo "========================================================\n";
    echo "Penggunaan:\n";
    echo "  php bin/wa_worker.php [opsi]\n\n";
    echo "Opsi yang tersedia:\n";
    echo "  --limit=N       Batas jumlah antrean yang diproses (default: 10, maks: 50)\n";
    echo "  --status        Tampilkan ringkasan status antrean dan konfigurasi\n";
    echo "  --dry-run       Jalankan pemrosesan tanpa mengirim HTTP ke Fonnte\n";
    echo "  --recover-only  Hanya pulihkan antrean macet (stale locks)\n";
    echo "  --force         Paksa pemrosesan meskipun WHATSAPP_ENABLED=false (Khusus Staging)\n";
    echo "  --help          Tampilkan panduan bantuan ini\n";
    echo "========================================================\n";
    exit(0);
}

$now = date('Y-m-d H:i:s');
echo "[{$now}] YukSehat WhatsApp Worker dimulai...\n";

// 5. Perintah --status
if (isset($options['status'])) {
    $db = new Database();
    $db->query("SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) AS processing,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status = 'reconciliation_required' THEN 1 ELSE 0 END) AS reconciliation_required
                FROM notifikasi_wa_queue");
    $stats = $db->single();
    $workerSummary = WaQueueService::getWorkerStatusSummary();

    echo "--- Ringkasan Sistem Antrean ---\n";
    echo "Status Konfigurasi Fonnte : " . ($workerSummary['is_configured'] ? 'Terkonfigurasi' : 'Belum Dikonfigurasi') . "\n";
    echo "Mode Pengiriman WhatsApp  : " . $workerSummary['mode_label'] . "\n";
    echo "Total Antrean             : " . (int)$stats['total'] . "\n";
    echo "  - Pending               : " . (int)$stats['pending'] . "\n";
    echo "  - Sedang Diproses       : " . (int)$stats['processing'] . "\n";
    echo "  - Berhasil Terkirim     : " . (int)$stats['sent'] . "\n";
    echo "  - Gagal                 : " . (int)$stats['failed'] . "\n";
    echo "  - Perlu Rekonsiliasi    : " . (int)$stats['reconciliation_required'] . "\n";
    exit(0);
}

// 6. Perintah --recover-only
if (isset($options['recover-only'])) {
    $recovered = WaQueueService::recoverStaleLocks(WaQueueService::STALE_LOCK_MINUTES);
    echo "[{$now}] Pemulihan selesai: {$recovered} antrean macet berhasil dipulihkan.\n";
    exit(0);
}

// 7. Jalankan Pemrosesan Batch
$limit = isset($options['limit']) ? (int)$options['limit'] : 10;
$processOptions = [
    'force'   => isset($options['force']),
    'dry_run' => isset($options['dry-run'])
];

$result = WaQueueService::processBatch($limit, $processOptions);

$nowEnd = date('Y-m-d H:i:s');
echo "[{$nowEnd}] Hasil: [{$result['status']}] {$result['message']}\n";
exit(0);
