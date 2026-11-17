<?php
/**
 * Controller Notifikasi Khusus Pengurus
 * YukSehat - Native PHP MVC
 */

class Notifikasi extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
        // Otorisasi: Pengurus dan Santri berwenang mengakses notifikasi miliknya
        AuthHelper::requireRole(['pengurus', 'santri']);
    }

    /**
     * Halaman Riwayat Notifikasi
     */
    public function index() {
        $user = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');

        $filter = trim($_GET['filter'] ?? '');
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $total = $notifModel->countNotifications($user['id'], $filter);
        $list = $notifModel->getAllNotifications($user['id'], $limit, $offset, $filter);
        $unreadCount = $notifModel->getUnreadCount($user['id']);

        $isSantri = ($user['role'] === 'santri');

        $data = [
            'title'        => $isSantri ? 'Pemberitahuan Keluhan' : 'Notifikasi Penugasan',
            'current_page' => 'notifikasi',
            'role'         => $user['role'],
            'username'     => $user['nama'],
            'notifications'=> $list,
            'unread_count' => $unreadCount,
            'total'        => $total,
            'page'         => $page,
            'total_pages'  => max(1, ceil($total / $limit)),
            'filter'       => $filter,
            'csrf_token'   => AuthHelper::generateCSRF()
        ];

        $this->view('templates/dash_header', $data);
        $this->view('notifikasi/index', $data);
        $this->view('templates/dash_footer');
    }

    /**
     * Tandai satu notifikasi sebagai dibaca dan redirect ke link internal yang sah
     */
    public function read($id = null) {
        $id = (int)$id;
        if ($id <= 0) {
            header('Location: ' . BASEURL . '/notifikasi');
            exit;
        }

        $user = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');

        // Validasi kepemilikan ketat: pengurus hanya bisa membaca notifikasi miliknya sendiri
        $notif = $notifModel->getNotificationByIdAndUser($id, $user['id']);

        if (!$notif) {
            SessionHelper::setFlash('pesan', 'Notifikasi tidak ditemukan atau Anda tidak memiliki hak akses.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/notifikasi');
            exit;
        }

        // Tandai sebagai telah dibaca
        $notifModel->markAsRead($id, $user['id']);

        // KEAMANAN: Cegah Open Redirect. URL tujuan wajib diverifikasi ke whitelist rute lokal aplikasi
        $rawTarget = trim($notif['link_url'] ?? '');
        $safeRedirect = BASEURL . '/notifikasi';

        if (!empty($rawTarget) && str_starts_with($rawTarget, '/') && !str_starts_with($rawTarget, '//')) {
            // Hanya izinkan path yang valid dalam aplikasi
            if (preg_match('#^/(?:keluhan|pemeriksaan|dashboard)(?:[/?#]|$)#', $rawTarget)) {
                $safeRedirect = BASEURL . $rawTarget;
                
                // Jika pengurus membuka notifikasi keluhan, sesuaikan filter status agar keluhan tampil di antrean
                if ($user['role'] !== 'santri' && $rawTarget === '/keluhan' && !empty($notif['referensi_id'])) {
                    $db = new Database();
                    $db->query("SELECT status FROM riwayat_keluhan WHERE id = :id LIMIT 1");
                    $db->bind('id', (int)$notif['referensi_id']);
                    $keluhan = $db->single();
                    if ($keluhan && !empty($keluhan['status'])) {
                        $safeRedirect = BASEURL . '/keluhan?status=' . urlencode($keluhan['status']);
                    }
                }
            }
        }

        header('Location: ' . $safeRedirect);
        exit;
    }

    /**
     * Tandai notifikasi sebagai telah dibaca via AJAX tanpa reload halaman
     */
    public function mark_read_ajax($id = null) {
        header('Content-Type: application/json');
        $id = (int)$id;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
            exit;
        }

        $user = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');
        $notif = $notifModel->getNotificationByIdAndUser($id, $user['id']);

        if (!$notif) {
            echo json_encode(['success' => false, 'message' => 'Notifikasi tidak ditemukan']);
            exit;
        }

        $notifModel->markAsRead($id, $user['id']);
        $unreadCount = $notifModel->getUnreadCount($user['id']);

        echo json_encode(['success' => true, 'unread_count' => $unreadCount]);
        exit;
    }

    /**
     * Tandai semua notifikasi milik pengurus aktif sebagai telah dibaca
     */
    public function read_all() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/notifikasi');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan gagal. Silakan coba kembali.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/notifikasi');
            exit;
        }

        $user = AuthHelper::user();
        $notifModel = $this->model('Notifikasi_model');
        $notifModel->markAllAsRead($user['id']);

        SessionHelper::setFlash('pesan', 'Semua notifikasi berhasil ditandai telah dibaca.', 'alert alert-success');
        header('Location: ' . BASEURL . '/notifikasi');
        exit;
    }
}

