<?php
/**
 * Controller Monitoring Antrean Notifikasi WhatsApp Fonnte
 * Khusus Role Administrator (Production Real Gateway Monitoring)
 * YukSehat - Native PHP MVC
 */

class Monitoring extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
        // OTORISASI KETAT: Hanya role Admin yang berwenang mengakses Dashboard Monitoring WhatsApp
        AuthHelper::requireRole(['admin']);
    }

    /**
     * Halaman Dashboard Monitoring Notifikasi WhatsApp Real Gateway
     */
    public function index() {
        // RESTRIKSI HTTP METHOD: Hanya request GET yang diizinkan untuk monitoring dashboard
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Allow: GET');
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed. Halaman monitoring hanya mengizinkan request GET.';
            exit;
        }

        $currentUser = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');
        require_once dirname(__DIR__) . '/services/WaQueueService.php';

        // Filter parameters (Sanitized)
        $status = trim($_GET['status'] ?? '');
        $jenis = trim($_GET['jenis'] ?? '');
        $tanggalDari = trim($_GET['tanggal_dari'] ?? '');
        $tanggalSampai = trim($_GET['tanggal_sampai'] ?? '');

        // Validasi format tanggal YYYY-MM-DD jika diisi
        if (!empty($tanggalDari) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalDari)) {
            $tanggalDari = '';
        }
        if (!empty($tanggalSampai) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalSampai)) {
            $tanggalSampai = '';
        }

        $allowedStatuses = ['pending', 'processing', 'sent', 'failed', 'reconciliation_required'];
        $filters = [
            'status'         => in_array($status, $allowedStatuses) ? $status : '',
            'jenis'          => in_array($jenis, ['keluhan_baru', 'penugasan_baru', 'pengalihan_tugas', 'status_diperiksa', 'status_selesai']) ? $jenis : '',
            'tanggal_dari'   => $tanggalDari,
            'tanggal_sampai' => $tanggalSampai,
        ];

        // Paginasi antrean operasional
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        // Ambil data statistik dan daftar antrean operasional (Read-Only)
        $summary = $notifModel->getWaQueueSummary();
        $total = $notifModel->countWaQueueList($filters);
        $queueList = $notifModel->getWaQueueList($filters, $limit, $offset);
        $totalPages = max(1, ceil($total / $limit));

        // Status Gateway & Worker
        $workerSummary = WaQueueService::getWorkerStatusSummary();
        $deviceStatus = $_SESSION['fonnte_device_status'] ?? [
            'success'       => null,
            'device_status' => 'unverified',
            'checked_at'    => null,
            'name'          => '-',
            'quota'         => '-'
        ];

        // Log audit historis (arsip wa_test_audit)
        $testAuditList = $notifModel->getRecentWaTestAudit(10);

        $data = [
            'title'           => 'Monitoring WhatsApp',
            'current_page'    => 'monitoring',
            'role'            => 'admin',
            'username'        => $currentUser['nama'] ?? ($currentUser['username'] ?? 'Administrator'),
            'summary'         => $summary,
            'list'            => $queueList,
            'total'           => $total,
            'page'            => $page,
            'total_pages'     => $totalPages,
            'filters'         => $filters,
            'worker_summary'  => $workerSummary,
            'device_status'   => $deviceStatus,
            'csrf_token'      => AuthHelper::generateCSRF(),
            'test_audit_list' => $testAuditList
        ];

        $this->view('templates/dash_header', $data);
        $this->view('monitoring/index', $data);
        $this->view('templates/dash_footer');
    }

    /**
     * Endpoint Cek Koneksi Gateway Fonnte (On-Demand Device Check)
     * POST only, dilindungi CSRF, khusus Admin.
     */
    public function check_device() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed. Endpoint ini hanya mengizinkan request POST.';
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan CSRF gagal. Silakan coba kembali.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/monitoring');
            exit;
        }

        $res = FonnteService::checkDevice();

        $_SESSION['fonnte_device_status'] = [
            'success'       => $res['success'] ?? false,
            'device_status' => $res['device_status'] ?? 'unknown',
            'name'          => $res['name'] ?? '-',
            'package'       => $res['package'] ?? '-',
            'quota'         => $res['quota'] ?? 0,
            'expired'       => $res['expired'] ?? '-',
            'message'       => $res['message'] ?? null,
            'checked_at'    => time()
        ];

        if (!empty($res['success'])) {
            $statusName = htmlspecialchars($res['device_status'] ?? 'terhubung');
            SessionHelper::setFlash('pesan', "Status perangkat Fonnte berhasil diverifikasi: [{$statusName}]. Nama akun: " . htmlspecialchars($res['name'] ?? '-'), 'alert alert-success');
        } else {
            $msg = htmlspecialchars($res['message'] ?? 'Gagal memeriksa koneksi perangkat ke gateway Fonnte.');
            SessionHelper::setFlash('pesan', "Pemeriksaan perangkat gagal: {$msg}", 'alert alert-warning');
        }

        AuthHelper::rotateCSRF();
        header('Location: ' . BASEURL . '/monitoring');
        exit;
    }

    /**
     * Endpoint Proses Antrean Manual oleh Administrator
     * POST only, dilindungi CSRF, khusus Admin.
     */
    public function process_queue() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed. Endpoint ini hanya mengizinkan request POST.';
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan CSRF gagal. Silakan coba kembali.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/monitoring');
            exit;
        }

        require_once dirname(__DIR__) . '/services/WaQueueService.php';

        $limit = isset($_POST['limit']) ? max(1, min(30, (int)$_POST['limit'])) : 10;
        $result = WaQueueService::processBatch($limit);

        if ($result['status'] === 'success') {
            SessionHelper::setFlash('pesan', $result['message'], 'alert alert-success');
        } elseif ($result['status'] === 'idle') {
            SessionHelper::setFlash('pesan', $result['message'], 'alert alert-info');
        } elseif ($result['status'] === 'disabled') {
            SessionHelper::setFlash('pesan', $result['message'], 'alert alert-warning');
        } else {
            SessionHelper::setFlash('pesan', $result['message'], 'alert alert-danger');
        }

        AuthHelper::rotateCSRF();
        header('Location: ' . BASEURL . '/monitoring');
        exit;
    }

    /**
     * Endpoint Rekonsiliasi Manual Antrean oleh Administrator (F-01)
     * POST only, dilindungi CSRF, khusus Admin.
     */
    public function reconcile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed. Endpoint ini hanya mengizinkan request POST.';
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan CSRF gagal. Silakan coba kembali.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/monitoring');
            exit;
        }

        require_once dirname(__DIR__) . '/services/WaQueueService.php';

        $queueId = (int)($_POST['queue_id'] ?? 0);
        $action = trim($_POST['reconcile_action'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $res = WaQueueService::reconcileItem($queueId, $action, $notes);

        if (!empty($res['success'])) {
            SessionHelper::setFlash('pesan', $res['message'], 'alert alert-success');
        } else {
            SessionHelper::setFlash('pesan', $res['message'], 'alert alert-danger');
        }

        AuthHelper::rotateCSRF();
        header('Location: ' . BASEURL . '/monitoring');
        exit;
    }

    /**
     * Endpoint Simulasi Uji Coba Terisolasi (Legacy/Arsip Kompatibilitas)
     * Tetap ada untuk menjaga kompatibilitas route dan audit trail.
     */
    public function execute_single_test() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Allow: POST');
            http_response_code(405);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Method Not Allowed. Endpoint ini hanya mengizinkan request POST.';
            exit;
        }

        $currentUser = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi formulir keamanan (CSRF) gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/monitoring');
            exit;
        }

        $nonce = trim($_POST['test_nonce'] ?? '');
        $savedNonce = $_SESSION['wa_test_nonce'] ?? '';
        unset($_SESSION['wa_test_nonce'], $_SESSION['wa_test_nonce_time']);

        if (empty($nonce) || empty($savedNonce) || !hash_equals($savedNonce, $nonce)) {
            SessionHelper::setFlash('pesan', 'Token uji coba (Nonce) tidak valid atau telah digunakan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/monitoring');
            exit;
        }

        $pengurusId = (int)($_POST['pengurus_id'] ?? 0);
        $idempotencyToken = bin2hex(random_bytes(32));
        $simOutcome = trim($_POST['sim_outcome'] ?? 'sent');

        $res = $notifModel->executeSingleTestSimulated((int)$currentUser['id'], $pengurusId, $idempotencyToken, $simOutcome);

        if (!empty($res['success'])) {
            SessionHelper::setFlash('pesan', $res['message'], 'alert alert-success');
        } else {
            SessionHelper::setFlash('pesan', $res['error'] ?? 'Simulasi gagal.', 'alert alert-danger');
        }

        AuthHelper::rotateCSRF();
        header('Location: ' . BASEURL . '/monitoring');
        exit;
    }
}
