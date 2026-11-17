<?php
/**
 * Controller Konfigurasi Model AI & Chatbot (Khusus Admin)
 * Sederhana: Cukup pilih model untuk Chatbot dan Analisis Klinis tanpa perlu salin-tempel API Key.
 */
class Settings extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
        AuthHelper::requireRole(['admin']);
    }

    public function index() {
        $currentUser = AuthHelper::user();
        $config = AiService::getConfig(true);
        $catalog = AiService::getModelCatalog();

        $data = [
            'title'        => 'Pilihan Model AI & Chatbot',
            'current_page' => 'settings',
            'role'         => $currentUser['role'] ?? 'admin',
            'username'     => $currentUser['nama'] ?? ($currentUser['username'] ?? 'Administrator'),
            'breadcrumb'   => [
                'Home' => BASEURL,
                'Konfigurasi Model AI' => '#'
            ],
            'config'       => $config,
            'catalog'      => $catalog,
            'csrf_token'   => AuthHelper::generateCSRF()
        ];

        $this->view('templates/dash_header', $data);
        $this->view('settings/index', $data);
        $this->view('templates/dash_footer');
    }

    /**
     * Simpan perubahan model AI
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        $chatbotModel = trim($_POST['chatbot_model'] ?? '');
        $clinicalModel = trim($_POST['clinical_model'] ?? '');

        // Dukungan jika memilih opsi model kustom
        if ($chatbotModel === 'custom' && !empty($_POST['custom_chatbot_model'])) {
            $chatbotModel = trim($_POST['custom_chatbot_model']);
        }
        if ($clinicalModel === 'custom' && !empty($_POST['custom_clinical_model'])) {
            $clinicalModel = trim($_POST['custom_clinical_model']);
        }

        AiService::saveModels($chatbotModel, $clinicalModel);

        // Jika admin juga memperbarui API key di accordion lanjutan (opsional)
        $nvidiaKey = trim($_POST['nvidia_api_key'] ?? '');
        $geminiKey = trim($_POST['gemini_api_key'] ?? '');
        if (!empty($nvidiaKey) || !empty($geminiKey)) {
            AiService::updateApiKeys($nvidiaKey, $geminiKey);
        }

        SessionHelper::setFlash('pesan', 'Pengaturan model AI berhasil disimpan! Chatbot dan Analisis Klinis otomatis aktif menggunakan model terpilih.', 'alert alert-success');
        header('Location: ' . BASEURL . '/settings');
        exit;
    }

    /**
     * Ganti / Pasang API Key Baru untuk Provider tertentu (NVIDIA atau Gemini)
     */
    public function update_key() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan CSRF gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        $provider = trim($_POST['provider'] ?? '');
        $apiKey = trim($_POST['api_key'] ?? '');

        if (!in_array($provider, ['nvidia', 'gemini'])) {
            SessionHelper::setFlash('pesan', 'Provider AI tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        if (empty($apiKey)) {
            SessionHelper::setFlash('pesan', 'Kunci API baru tidak boleh kosong.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        $providerLabel = ($provider === 'nvidia') ? 'NVIDIA NIM' : 'Google Gemini';
        $success = AiService::saveApiKey($provider, $apiKey);

        if ($success) {
            SessionHelper::setFlash('pesan', "Kunci API {$providerLabel} berhasil diperbarui dan aktif!", 'alert alert-success');
        } else {
            SessionHelper::setFlash('pesan', "Gagal menyimpan Kunci API {$providerLabel}.", 'alert alert-danger');
        }

        header('Location: ' . BASEURL . '/settings');
        exit;
    }

    /**
     * Hapus API Key lama untuk Provider tertentu (NVIDIA atau Gemini)
     */
    public function delete_key() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan CSRF gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        $provider = trim($_POST['provider'] ?? '');
        if (!in_array($provider, ['nvidia', 'gemini'])) {
            SessionHelper::setFlash('pesan', 'Provider AI tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/settings');
            exit;
        }

        $providerLabel = ($provider === 'nvidia') ? 'NVIDIA NIM' : 'Google Gemini';
        AiService::deleteApiKey($provider);

        SessionHelper::setFlash('pesan', "Kunci API {$providerLabel} berhasil dihapus dari sistem.", 'alert alert-success');
        header('Location: ' . BASEURL . '/settings');
        exit;
    }

    /**
     * Endpoint AJAX pengujian model secara langsung: POST /settings/test_model
     */
    public function test_model() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Metode request harus POST']);
            exit;
        }

        $inputRaw = file_get_contents('php://input');
        $data = json_decode($inputRaw, true);
        if (!$data && !empty($_POST)) {
            $data = $_POST;
        }

        $model = trim($data['model'] ?? '');
        $result = AiService::testModel($model);
        echo json_encode($result);
        exit;
    }

    /**
     * Alias kompatibilitas mundur untuk test_connection
     */
    public function test_connection() {
        return $this->test_model();
    }
}
