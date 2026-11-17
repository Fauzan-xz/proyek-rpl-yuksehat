<?php
class Pemeriksaan extends Controller {

    public function __construct() {
        // Jika request ditujukan ke endpoint AJAX analyze_keluhan, biarkan method mengembalikan respons JSON 401
        $url = $_GET['url'] ?? '';
        if (strpos($url, 'analyze_keluhan') !== false) {
            return;
        }
        AuthHelper::requireLogin();
    }

    // ─────────────────────────────────────────────
    //  INDEX – Daftar pemeriksaan
    // ─────────────────────────────────────────────
    public function index() {
        $user   = AuthHelper::user();
        $model  = $this->model('Pemeriksaan_model');

        $page   = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit  = 10;
        $offset = ($page - 1) * $limit;

        if ($user['role'] === 'santri') {
            // Santri hanya melihat miliknya sendiri
            $total  = $model->countPemeriksaanBySantriUserId($user['id']);
            $list   = $model->getPemeriksaanBySantriUserId($user['id'], $limit, $offset);
        } else {
            // Admin / Pengurus: filter bebas
            $keyword       = isset($_GET['search'])        ? trim($_GET['search'])        : '';
            $tanggal_dari  = isset($_GET['tanggal_dari'])  ? trim($_GET['tanggal_dari'])  : '';
            $tanggal_sampai= isset($_GET['tanggal_sampai'])? trim($_GET['tanggal_sampai']): '';
            $filter        = isset($_GET['filter'])        ? trim($_GET['filter'])        : '';

            $total = $model->countAllPemeriksaan($keyword, $tanggal_dari, $tanggal_sampai, $filter);
            $list  = $model->getAllPemeriksaan($limit, $offset, $keyword, $tanggal_dari, $tanggal_sampai, $filter);

            $data['keyword']        = $keyword;
            $data['tanggal_dari']   = $tanggal_dari;
            $data['tanggal_sampai'] = $tanggal_sampai;
            $data['filter']         = $filter;
        }

        $data['list']        = $list;
        $data['page']        = $page;
        $data['total_pages'] = ceil($total / $limit);
        $data['total']       = $total;

        $data['role']         = $user['role'];
        $data['username']     = $user['nama'];
        $data['title']        = 'Pemeriksaan Kesehatan';
        $data['current_page'] = 'pemeriksaan';
        $data['breadcrumb']   = ['Home' => BASEURL, 'Pemeriksaan' => '#'];

        $this->view('templates/dash_header', $data);
        $this->view('pemeriksaan/index', $data);
        $this->view('templates/dash_footer');
    }

    // ─────────────────────────────────────────────
    //  SHOW – Detail satu pemeriksaan
    // ─────────────────────────────────────────────
    public function show($id = null) {
        if (!$id) { $this->_redirect('/pemeriksaan'); }

        $user  = AuthHelper::user();
        $model = $this->model('Pemeriksaan_model');
        $item  = $model->getPemeriksaanById($id);

        if (!$item) {
            SessionHelper::setFlash('pesan', 'Data pemeriksaan tidak ditemukan.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan');
        }

        // AUTHORIZATION: santri hanya boleh lihat miliknya sendiri
        if ($user['role'] === 'santri' && $item['santri_user_id'] != $user['id']) {
            SessionHelper::setFlash('pesan', 'Anda tidak memiliki akses ke data tersebut.', 'alert alert-warning');
            $this->_redirect('/pemeriksaan');
        }

        $data['item']        = $item;
        $data['role']        = $user['role'];
        $data['username']    = $user['nama'];
        $data['title']       = 'Detail Pemeriksaan';
        $data['current_page']= 'pemeriksaan';
        $data['breadcrumb']  = ['Home' => BASEURL, 'Pemeriksaan' => BASEURL.'/pemeriksaan', 'Detail' => '#'];

        $this->view('templates/dash_header', $data);
        $this->view('pemeriksaan/show', $data);
        $this->view('templates/dash_footer');
    }

    // ─────────────────────────────────────────────
    //  CREATE – Form tambah pemeriksaan
    //  Khusus: Hanya petugas Ronal (Pengurus) yang berwenang melakukan input pemeriksaan
    // ─────────────────────────────────────────────
    public function create($santri_id = null) {
        AuthHelper::requireCanInputPemeriksaan();

        $user  = AuthHelper::user();
        $model = $this->model('Pemeriksaan_model');

        $selected_santri = $santri_id ?: ($_GET['santri_id'] ?? null);
        $keluhan_id      = $_GET['keluhan_id'] ?? null;
        $prefill_keluhan = $_GET['keluhan'] ?? '';

        if (!empty($keluhan_id)) {
            $db = new Database();
            $db->query("SELECT santri_id, keluhan FROM riwayat_keluhan WHERE id = :kid LIMIT 1");
            $db->bind('kid', $keluhan_id);
            $rk = $db->single();
            if ($rk) {
                if (empty($selected_santri)) {
                    $selected_santri = $rk['santri_id'];
                }
                if (empty($prefill_keluhan)) {
                    $prefill_keluhan = $rk['keluhan'];
                }
            }
        }

        $data['santri_list']     = $model->getAllSantriList();
        $data['selected_santri'] = $selected_santri;
        $data['keluhan_id']      = $keluhan_id;
        $data['prefill_keluhan'] = $prefill_keluhan;
        $data['csrf_token']      = AuthHelper::generateCSRF();
        $data['role']            = $user['role'];
        $data['username']        = $user['nama'];
        $data['title']           = 'Input Pemeriksaan';
        $data['current_page']    = 'pemeriksaan';
        $data['breadcrumb']      = ['Home' => BASEURL, 'Pemeriksaan' => BASEURL.'/pemeriksaan', 'Tambah' => '#'];

        $this->view('templates/dash_header', $data);
        $this->view('pemeriksaan/create', $data);
        $this->view('templates/dash_footer');
    }

    // ─────────────────────────────────────────────
    //  STORE – Simpan pemeriksaan baru
    //  Khusus: Hanya petugas Ronal (Pengurus) yang berwenang menyimpan input pemeriksaan
    // ─────────────────────────────────────────────
    public function store() {
        AuthHelper::requireCanInputPemeriksaan();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->_redirect('/pemeriksaan');
        }

        if (!AuthHelper::validateCSRF($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('pesan', 'CSRF Token tidak valid.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/create');
        }

        $user = AuthHelper::user();

        // Validasi wajib
        $santri_id  = filter_input(INPUT_POST, 'santri_id', FILTER_VALIDATE_INT);
        $keluhan    = trim($_POST['keluhan'] ?? '');
        $keluhan_id = filter_input(INPUT_POST, 'keluhan_id', FILTER_VALIDATE_INT);

        if (!$santri_id || empty($keluhan)) {
            SessionHelper::setFlash('pesan', 'Santri dan keluhan wajib diisi.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/create');
        }

        $data = [
            'santri_id'          => $santri_id,
            'pemeriksa_id'       => $user['id'],
            'keluhan_id'         => $keluhan_id ?: null,
            'tanggal_pemeriksaan'=> htmlspecialchars(trim($_POST['tanggal_pemeriksaan'])),
            'keluhan'            => htmlspecialchars($keluhan),
            'suhu'               => htmlspecialchars(trim($_POST['suhu'])),
            'tekanan_darah'      => htmlspecialchars(trim($_POST['tekanan_darah'])),
            'berat_badan'        => htmlspecialchars(trim($_POST['berat_badan'])),
            'tinggi_badan'       => htmlspecialchars(trim($_POST['tinggi_badan'])),
            'hasil_pemeriksaan'  => htmlspecialchars(trim($_POST['hasil_pemeriksaan'])),
            'tindakan'           => htmlspecialchars(trim($_POST['tindakan'])),
            'obat'               => htmlspecialchars(trim($_POST['obat'])),
            'catatan'            => htmlspecialchars(trim($_POST['catatan'])),
        ];

        $model = $this->model('Pemeriksaan_model');
        if ($model->insertPemeriksaan($data)) {
            SessionHelper::setFlash('pesan', 'Data pemeriksaan berhasil disimpan.', 'alert alert-success');
            $this->_redirect('/pemeriksaan');
        } else {
            SessionHelper::setFlash('pesan', 'Gagal menyimpan data.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/create');
        }
    }

    // ─────────────────────────────────────────────
    //  EDIT – Form edit pemeriksaan
    // ─────────────────────────────────────────────
    public function edit($id = null) {
        AuthHelper::requireRole(['admin', 'pengurus']);
        if (!$id) { $this->_redirect('/pemeriksaan'); }

        $user  = AuthHelper::user();
        $model = $this->model('Pemeriksaan_model');
        $item  = $model->getPemeriksaanById($id);

        if (!$item) {
            SessionHelper::setFlash('pesan', 'Data tidak ditemukan.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan');
        }

        $data['item']        = $item;
        $data['csrf_token']  = AuthHelper::generateCSRF();
        $data['role']        = $user['role'];
        $data['username']    = $user['nama'];
        $data['title']       = 'Edit Pemeriksaan';
        $data['current_page']= 'pemeriksaan';
        $data['breadcrumb']  = ['Home' => BASEURL, 'Pemeriksaan' => BASEURL.'/pemeriksaan', 'Edit' => '#'];

        $this->view('templates/dash_header', $data);
        $this->view('pemeriksaan/edit', $data);
        $this->view('templates/dash_footer');
    }

    // ─────────────────────────────────────────────
    //  UPDATE – Proses update pemeriksaan
    // ─────────────────────────────────────────────
    public function update($id = null) {
        AuthHelper::requireRole(['admin', 'pengurus']);

        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->_redirect('/pemeriksaan');
        }

        if (!AuthHelper::validateCSRF($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('pesan', 'CSRF Token tidak valid.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/edit/' . $id);
        }

        $keluhan = trim($_POST['keluhan'] ?? '');
        if (empty($keluhan)) {
            SessionHelper::setFlash('pesan', 'Keluhan wajib diisi.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/edit/' . $id);
        }

        $data = [
            'tanggal_pemeriksaan'=> htmlspecialchars(trim($_POST['tanggal_pemeriksaan'])),
            'keluhan'            => htmlspecialchars($keluhan),
            'suhu'               => htmlspecialchars(trim($_POST['suhu'])),
            'tekanan_darah'      => htmlspecialchars(trim($_POST['tekanan_darah'])),
            'berat_badan'        => htmlspecialchars(trim($_POST['berat_badan'])),
            'tinggi_badan'       => htmlspecialchars(trim($_POST['tinggi_badan'])),
            'hasil_pemeriksaan'  => htmlspecialchars(trim($_POST['hasil_pemeriksaan'])),
            'tindakan'           => htmlspecialchars(trim($_POST['tindakan'])),
            'obat'               => htmlspecialchars(trim($_POST['obat'])),
            'catatan'            => htmlspecialchars(trim($_POST['catatan'])),
        ];

        $model = $this->model('Pemeriksaan_model');
        if ($model->updatePemeriksaan($data, $id)) {
            SessionHelper::setFlash('pesan', 'Data pemeriksaan berhasil diperbarui.', 'alert alert-success');
            $this->_redirect('/pemeriksaan/show/' . $id);
        } else {
            SessionHelper::setFlash('pesan', 'Gagal memperbarui data.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan/edit/' . $id);
        }
    }

    // ─────────────────────────────────────────────
    //  DELETE – Hapus pemeriksaan
    // ─────────────────────────────────────────────
    public function delete($id = null) {
        AuthHelper::requireRole(['admin', 'pengurus']);

        if (!$id || $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->_redirect('/pemeriksaan');
        }

        if (!AuthHelper::validateCSRF($_POST['csrf_token'] ?? '')) {
            SessionHelper::setFlash('pesan', 'CSRF Token tidak valid.', 'alert alert-danger');
            $this->_redirect('/pemeriksaan');
        }

        $model = $this->model('Pemeriksaan_model');
        if ($model->deletePemeriksaan($id)) {
            SessionHelper::setFlash('pesan', 'Data pemeriksaan berhasil dihapus.', 'alert alert-success');
        } else {
            SessionHelper::setFlash('pesan', 'Gagal menghapus data.', 'alert alert-danger');
        }
        $this->_redirect('/pemeriksaan');
    }

    // ─────────────────────────────────────────────
    //  Helper redirect
    // ─────────────────────────────────────────────
    private function _redirect($path) {
        header('Location: ' . BASEURL . $path);
        exit;
    }

    // ─────────────────────────────────────────────
    //  AI CLINICAL ANALYSIS – Draf Keluhan Petugas
    // ─────────────────────────────────────────────
    public function analyze_keluhan() {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Proteksi Autentikasi & Role Server-Side
        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Akses ditolak: Silakan login terlebih dahulu.'
            ]);
            exit;
        }

        $user = AuthHelper::user();
        if (!in_array($user['role'] ?? '', ['admin', 'pengurus', 'petugas_kesehatan'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Akses ditolak: Anda tidak memiliki izin untuk fitur analisis klinis.'
            ]);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metode request tidak diizinkan.']);
            exit;
        }

        // 2. Proteksi CSRF Token
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $csrfToken = $_POST['csrf_token'] ?? $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? '';
        if (!AuthHelper::validateCSRF($csrfToken)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Validasi CSRF Token gagal. Silakan muat ulang halaman.']);
            exit;
        }

        // 3. Validasi Keluhan
        $keluhan = trim($_POST['keluhan'] ?? '');
        if (empty($keluhan)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Keluhan santri tidak boleh kosong untuk dianalisis.']);
            exit;
        }

        // 4. Data Klinis Tambahan yang Dianonimkan (Tanpa Nama & Tanpa NIS)
        $suhu = trim($_POST['suhu'] ?? '');
        $tekanan_darah = trim($_POST['tekanan_darah'] ?? '');
        $berat_badan = trim($_POST['berat_badan'] ?? '');
        $tinggi_badan = trim($_POST['tinggi_badan'] ?? '');

        // Informasi non-identitas santri (misal: kelas/kamar jika relevan untuk konteks asrama)
        $konteks = '';
        $santri_id = filter_input(INPUT_POST, 'santri_id', FILTER_VALIDATE_INT);
        if ($santri_id) {
            $santriModel = $this->model('Santri_model');
            $santri = $santriModel->getSantriById($santri_id);
            if ($santri) {
                $jk = ($santri['jenis_kelamin'] ?? '') === 'P' ? 'Perempuan' : 'Laki-laki';
                $kelas = htmlspecialchars($santri['kelas'] ?? '-');
                $konteks = "Santri {$jk}, Kelas {$kelas}";
                if (!empty($santri['alergi']) && strtolower($santri['alergi']) !== 'tidak ada') {
                    $konteks .= ", Riwayat Alergi: " . htmlspecialchars($santri['alergi']);
                }
            }
        }

        $vitalSigns = [
            'suhu' => $suhu,
            'tekanan_darah' => $tekanan_darah,
            'berat_badan' => $berat_badan,
            'tinggi_badan' => $tinggi_badan
        ];

        // 5. Eksekusi Analisis via Service AI yang ada
        $result = $this->_callAIAnalysis($keluhan, $vitalSigns, $konteks);
        if (!empty($result['success']) && !empty($result['analysis'])) {
            $result['parsed'] = MedicalFormatHelper::parseClinicalSections($result['analysis']);
        }
        echo json_encode($result);
        exit;
    }

    /**
     * Memanggil service AI (Node.js microservice dengan fallback cURL PHP)
     */
    private function _callAIAnalysis($keluhan, $vitalSigns, $konteks) {
        $nodeUrl = 'http://127.0.0.1:3005/api/analyze-keluhan';
        $payload = json_encode([
            'keluhan' => $keluhan,
            'vital_signs' => $vitalSigns,
            'konteks' => $konteks
        ]);

        // Coba via Node.js Server terlebih dahulu
        $ch = curl_init($nodeUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 18);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$err && $httpCode === 200 && $response) {
            $json = json_decode($response, true);
            if (!empty($json['success'])) {
                return $json;
            }
        }

        // Eksekusi via AiService terpadu (NVIDIA NIM / Google Gemini sesuai konfigurasi aktif)
        $aiResult = AiService::analyzeClinical($keluhan, $vitalSigns, $konteks);
        if (!empty($aiResult['success'])) {
            return $aiResult;
        }

        // Fallback langsung ke Google Gemini jika terjadi kendala
        return $this->_callGeminiDirectAnalysis($keluhan, $vitalSigns, $konteks);
    }

    private function _callGeminiDirectAnalysis($keluhan, $vitalSigns, $konteks) {
        $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'your_gemini_api_key_here'
            ? GEMINI_API_KEY
            : null;

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Layanan AI offline dan API key belum dikonfigurasi.'
            ];
        }

        $candidateModels = [
            'gemini-flash-lite-latest',
            'gemini-3.5-flash',
            'gemini-3.8-flash'
        ];

        $systemInstruction = "Kamu adalah Asisten Analisis Klinis untuk Petugas Poskestren YukSehat. Tugasmu menyusun DRAF TINJAUAN AWAL yang SANGAT RINGKAS, PADAT, dan TO THE POINT (tanpa basa-basi atau penjelasan bertele-tele). PRINSIP: 1. Draf pembantu untuk ditinjau petugas, BUKAN diagnosis medis final. 2. JANGAN meresepkan obat keras/antibiotik otomatis. 3. Batasi setiap bagian maksimal 1-2 butir poin singkat. FORMAT OUTPUT WAJIB:
### 1. Ringkasan Keluhan & Dugaan Awal
- (1-2 kemungkinan penyebab/kondisi utama secara singkat)

### 2. Informasi yang Perlu Diklarifikasi
- (1-2 pertanyaan penting untuk ditanyakan ke santri)

### 3. Hal yang Perlu Diperhatikan / Tanda Bahaya (Red Flags)
- (1-2 gejala bahaya jika kondisi memburuk)

### 4. Saran Tindak Lanjut Umum & Perawatan di Poskestren
- (1-2 langkah perawatan/istirahat/P3K praktis)";

        $prompt = "Informasi Klinis Santri:\n- Keluhan Utama: {$keluhan}\n";
        if (!empty($vitalSigns['suhu'])) $prompt .= "- Suhu Tubuh: {$vitalSigns['suhu']} °C\n";
        if (!empty($vitalSigns['tekanan_darah'])) $prompt .= "- Tekanan Darah: {$vitalSigns['tekanan_darah']}\n";
        if (!empty($vitalSigns['berat_badan'])) $prompt .= "- Berat Badan: {$vitalSigns['berat_badan']} kg\n";
        if (!empty($vitalSigns['tinggi_badan'])) $prompt .= "- Tinggi Badan: {$vitalSigns['tinggi_badan']} cm\n";
        if (!empty($konteks)) $prompt .= "- Konteks Santri: {$konteks}\n";
        $prompt .= "\nMohon susun draf tinjauan klinis awal secara ringkas dan to the point (maksimal 1-2 butir poin per bagian).";

        $body = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.95,
                'maxOutputTokens' => 380
            ]
        ];

        $payload = json_encode($body);
        $lastError = 'Gagal menghubungi layanan AI.';

        foreach ($candidateModels as $model) {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 14);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $resJson = json_decode($result, true);
            if ($httpCode === 200 && !empty($resJson['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success' => true,
                    'analysis' => $resJson['candidates'][0]['content']['parts'][0]['text'],
                    'model' => $model,
                    'disclaimer' => 'Draf saran awal untuk ditinjau petugas kesehatan. Bukan diagnosis medis final atau resep obat otomatis.'
                ];
            }

            if (!empty($resJson['error']['message'])) {
                $lastError = $resJson['error']['message'];
            }
        }

        return [
            'success' => false,
            'error' => $lastError
        ];
    }
}
