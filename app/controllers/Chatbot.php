<?php

class Chatbot extends Controller {

    private $nodeServerUrl = 'http://127.0.0.1:3005';
    private $candidateModels = [
        'gemini-flash-lite-latest',
        'gemini-3.5-flash',
        'gemini-3.8-flash'
    ];

    private $cannedRejection = 'Maaf, saya hanya membantu terkait penggunaan YukSehat dan informasi kesehatan yang relevan.';

    /**
     * Endpoint status chatbot: GET/POST /chatbot/status
     */
    public function status() {
        header('Content-Type: application/json; charset=utf-8');

        $config = AiService::getConfig();
        $nodeStatus = $this->checkNodeStatus();

        echo json_encode([
            'status' => 'ok',
            'node_server' => $nodeStatus ? 'online' : 'offline',
            'provider' => $config['chatbot_provider'] ?? 'nvidia',
            'model' => $config['chatbot_model'] ?? 'nvidia/nemotron-3.5-bolt-e4b',
            'node_url' => $this->nodeServerUrl
        ]);
        exit;
    }

    /**
     * Endpoint kirim pesan: POST /chatbot/send (dengan alias /chatbot/ask dan /chatbot)
     */
    public function index() {
        return $this->send();
    }

    public function ask() {
        return $this->send();
    }

    public function send() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metode request harus POST']);
            exit;
        }

        $inputRaw = file_get_contents('php://input');
        $data = json_decode($inputRaw, true);
        if (!$data && !empty($_POST)) {
            $data = $_POST;
        }

        $message = trim($data['message'] ?? '');
        $history = $data['history'] ?? [];

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Pesan tidak boleh kosong']);
            exit;
        }

        // Ambil info pengguna terverifikasi dari session akun PHP (JANGAN pernah percaya role dari input client)
        $sessionUser = AuthHelper::user();
        $userData = null;
        if ($sessionUser) {
            $secret = defined('INTERNAL_API_SECRET') && !empty(INTERNAL_API_SECRET) ? INTERNAL_API_SECRET : null;
            $authTime = time();
            $userId = (int)($sessionUser['id'] ?? 0);
            $userRole = (string)($sessionUser['role'] ?? 'pengguna');
            $signaturePayload = $userId . ':' . $userRole . ':' . $authTime;
            $authToken = !empty($secret) ? hash_hmac('sha256', $signaturePayload, $secret) : '';

            $userData = [
                'id'         => $userId,
                'nama'       => $sessionUser['nama'] ?? ($sessionUser['username'] ?? 'Pengguna'),
                'role'       => $userRole,
                'auth_token' => $authToken,
                'auth_time'  => $authTime
            ];
        }

        // 1. Cek query data database aktual (hanya role admin/pengurus dengan otorisasi ketat)
        if (ChatbotDataService::isDataQuery($message, $userData)) {
            $dataReply = ChatbotDataService::handleDataQuery($message, $userData);
            echo json_encode([
                'success' => true,
                'reply'   => $dataReply,
                'model'   => 'database-query'
            ]);
            exit;
        }

        // 2. Cek query deterministik langsung (Siapa saya, Siapa Anda, Apa itu PHBS)
        $directAnswer = $this->checkDirectDeterministicQuery($message, $userData);
        if ($directAnswer) {
            echo json_encode([
                'success' => true,
                'reply'   => $directAnswer,
                'model'   => 'direct-rule'
            ]);
            exit;
        }

        // 3. Pre-screening permintaan di luar topik
        if ($this->isOffTopic($message)) {
            echo json_encode([
                'success' => true,
                'reply'   => $this->cannedRejection,
                'model'   => 'pre-screen'
            ]);
            exit;
        }

        // 4. Coba kirim via Node.js server terlebih dahulu jika service aktif
        $nodeResponse = $this->callNodeServer($message, $history, $userData);
        if ($nodeResponse && !empty($nodeResponse['success'])) {
            echo json_encode($nodeResponse);
            exit;
        }

        // 5. Eksekusi via AiService terpadu (NVIDIA NIM / Google Gemini sesuai pengaturan aktif)
        $systemInstruction = $this->buildSystemInstruction($userData);
        $directResponse = AiService::chat($message, $history, $userData, $systemInstruction);
        if (!empty($directResponse['success']) && ($directResponse['model'] ?? '') !== 'safe-fallback') {
            echo json_encode($directResponse);
            exit;
        }

        // 6. Cadangan langsung jika AiService mengalami safe fallback
        $geminiResponse = $this->callGeminiDirect($message, $history, $userData);
        if (!empty($geminiResponse['success']) && ($geminiResponse['model'] ?? '') !== 'safe-fallback') {
            echo json_encode($geminiResponse);
            exit;
        }

        echo json_encode($directResponse);
        exit;
    }

    /**
     * Endpoint internal server-to-server untuk query database: POST /chatbot/internal_query
     * Autentikasi ketat menggunakan X-Internal-Secret (Header only) dan HMAC signature dengan replay protection
     */
    public function internal_query() {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Metode request harus POST']);
            exit;
        }

        // 1. Verifikasi rahasia antarlayanan (HANYA dari HTTP Header, dilarang dari query URL atau JSON body)
        $incomingSecret = $_SERVER['HTTP_X_INTERNAL_SECRET'] ?? '';
        $expectedSecret = defined('INTERNAL_API_SECRET') && !empty(INTERNAL_API_SECRET) ? INTERNAL_API_SECRET : null;

        // FAIL CLOSED: Jika secret internal tidak disetel di environment, tolak dengan HTTP 503
        if (empty($expectedSecret)) {
            http_response_code(503);
            echo json_encode(['success' => false, 'error' => 'Layanan internal tidak tersedia (Konfigurasi keamanan belum disetel)']);
            exit;
        }

        if (empty($incomingSecret) || !hash_equals($expectedSecret, (string)$incomingSecret)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Akses ditolak: Secret token internal tidak valid']);
            exit;
        }

        $inputRaw = file_get_contents('php://input');
        $data = json_decode($inputRaw, true) ?: [];

        $message = trim($data['message'] ?? '');
        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Pesan query tidak boleh kosong']);
            exit;
        }

        // 2. Verifikasi pengguna dari session PHP atau HMAC signature terverifikasi
        $sessionUser = AuthHelper::user();
        $userData = null;

        if ($sessionUser) {
            $userData = [
                'id'   => (int)($sessionUser['id'] ?? 0),
                'nama' => $sessionUser['nama'] ?? ($sessionUser['username'] ?? 'Pengguna'),
                'role' => $sessionUser['role'] ?? 'pengguna'
            ];
        } elseif (!empty($data['user']) && is_array($data['user'])) {
            $reqUser = $data['user'];
            $userId = isset($reqUser['id']) ? (int)$reqUser['id'] : 0;
            $userRole = trim($reqUser['role'] ?? '');
            $userToken = trim($reqUser['auth_token'] ?? '');
            $authTime = isset($reqUser['auth_time']) ? (int)$reqUser['auth_time'] : 0;

            if ($userId > 0 && !empty($userRole)) {
                // Jika token dikirimkan, validasi integritas, keaslian, dan masa berlaku (replay protection)
                if (!empty($userToken)) {
                    $currentTime = time();
                    // Batas kedaluwarsa request: 900 detik (15 menit)
                    if ($authTime <= 0 || abs($currentTime - $authTime) > 900) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Request autentikasi telah kedaluwarsa']);
                        exit;
                    }

                    $expectedToken = hash_hmac('sha256', $userId . ':' . $userRole . ':' . $authTime, $expectedSecret);
                    if (!hash_equals($expectedToken, $userToken)) {
                        http_response_code(401);
                        echo json_encode(['success' => false, 'error' => 'Signature autentikasi pengguna tidak valid']);
                        exit;
                    }
                }

                // Verifikasi keberadaan dan status aktif user di database aktual
                $db = new Database();
                $db->query("SELECT id, username, role, status FROM users WHERE id = :id LIMIT 1");
                $db->bind('id', $userId);
                $dbUser = $db->single();

                if (!$dbUser || $dbUser['status'] !== 'active' || $dbUser['role'] !== $userRole) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Pengguna tidak aktif atau role tidak sesuai di database']);
                    exit;
                }

                $userData = [
                    'id'   => (int)$dbUser['id'],
                    'nama' => $reqUser['nama'] ?? $dbUser['username'],
                    'role' => $dbUser['role']
                ];
            } else {
                $userData = null;
            }
        }

        $reply = ChatbotDataService::handleDataQuery($message, $userData);

        echo json_encode([
            'success' => true,
            'reply'   => $reply,
            'model'   => 'database-query'
        ]);
        exit;
    }

    private function checkNodeStatus() {
        $ch = curl_init($this->nodeServerUrl . '/health');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($httpCode === 200);
    }

    private function callNodeServer($message, $history, $userData) {
        $payload = json_encode([
            'message' => $message,
            'history' => $history,
            'user'    => $userData
        ]);

        $expectedSecret = defined('INTERNAL_API_SECRET') && !empty(INTERNAL_API_SECRET) ? INTERNAL_API_SECRET : null;
        if (empty($expectedSecret)) {
            return null; // Fail closed: tidak memanggil node server jika secret belum disetel
        }

        $ch = curl_init($this->nodeServerUrl . '/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload),
            'X-Internal-Secret: ' . $expectedSecret
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err || !$result) {
            return null;
        }

        return json_decode($result, true);
    }

    private function formatRoleName($role) {
        $r = strtolower(trim($role ?? ''));
        if ($r === 'admin') return 'Admin';
        if ($r === 'pengurus') return 'Pengurus';
        if ($r === 'santri') return 'Santri';
        return !empty($r) ? ucfirst($r) : 'Pengguna';
    }

    private function checkDirectDeterministicQuery($message, $userData) {
        $clean = strtolower(trim(preg_replace('/[?!.,]/', '', $message)));
        $clean = preg_replace('/\s+/', ' ', $clean);

        $isLoggedIn = !empty($userData) && !empty($userData['role']) && strtolower(trim($userData['role'])) !== 'guest';
        $role = $isLoggedIn ? strtolower(trim($userData['role'])) : 'guest';
        $roleFormatted = $this->formatRoleName($role);
        $nama = trim($userData['nama'] ?? '');
        $username = trim($userData['username'] ?? '');

        // 1. "Siapa saya?" / "Saya login sebagai siapa?" / "Siapa aku?"
        if (
            preg_match('/^(siapa\s+(saya|aku)|identitas\s+saya|siapa\s+saya\s+di\s+sini|siapa\s+nama\s+saya)$/i', $clean) ||
            preg_match('/^(saya\s+login\s+sebagai\s+(?:siapa|apa)|login\s+sebagai\s+siapa(?:\s+saya)?|sebagai\s+siapa\s+saya\s+login|akun\s+saya\s+login\s+sebagai\s+siapa)$/i', $clean)
        ) {
            if (!$isLoggedIn) {
                return "Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.";
            }
            if (empty($nama) || $nama === 'Pengguna') {
                return "Kamu login sebagai {$username} dengan role {$roleFormatted}, namun data profil lengkap belum tersedia.";
            }
            return "Kamu login sebagai {$nama} dengan role {$roleFormatted}.";
        }

        // 2. "Apa nama akun saya?"
        if (preg_match('/^(apa\s+nama\s+akun\s+saya|nama\s+akun\s+saya(?:\s+apa|\s+siapa)?|siapa\s+nama\s+akun\s+saya|apa\s+nama\s+saya|nama\s+saya\s+siapa)$/i', $clean)) {
            if (!$isLoggedIn) {
                return "Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.";
            }
            if (empty($nama) || $nama === 'Pengguna') {
                return "Akun kamu terdaftar dengan username {$username}, namun data profil lengkap belum tersedia.";
            }
            if (!empty($username) && strcasecmp($username, $nama) !== 0) {
                return "Nama akun kamu adalah {$nama} (username: {$username}).";
            }
            return "Nama akun kamu adalah {$nama}.";
        }

        // 3. "Apa role saya?" / "Apa peran saya?"
        if (preg_match('/^(apa\s+(?:role|peran)\s+saya|(?:role|peran)\s+saya\s+apa|apa\s+(?:role|peran)\s+akun\s+saya|(?:role|peran)\s+akun\s+saya\s+apa)$/i', $clean)) {
            if (!$isLoggedIn) {
                return "Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.";
            }
            return "Kamu login dengan role {$roleFormatted} di YukSehat.";
        }

        // 4. "Siapa Anda?"
        if (preg_match('/^(siapa\s+(anda|kamu|yuksehat\s*ai)|kamu\s+siapa|anda\s+siapa)$/i', $clean)) {
            return "Saya YukSehat AI, asisten digital untuk membantu penggunaan YukSehat dan memberikan informasi kesehatan umum.";
        }

        // 5. "Apa itu PHBS?"
        if (preg_match('/^(apa\s+itu\s+phbs|pengertian\s+phbs|definisi\s+phbs|jelaskan\s+phbs)$/i', $clean)) {
            return "PHBS adalah Pola Hidup Bersih dan Sehat, seperti mencuci tangan dengan sabun, menjaga kebersihan kamar, dan menggunakan air bersih.";
        }

        // 6. "Apa saja yang bisa saya lakukan di YukSehat?" / kewenangan / hak akses
        if (
            preg_match('/^(apa\s+(?:saja\s+)?yang\s+(?:bisa|dapat)\s+saya\s+lakukan(?:\s+di\s+yuksehat|\s+di\s+akun\s+ini)?|kewenangan\s+(?:akun\s+)?saya|hak\s+akses\s+(?:akun\s+)?saya|apa\s+saja\s+fitur\s+(?:akun\s+)?saya|fitur\s+apa\s+saja\s+yang\s+bisa\s+saya\s+gunakan)$/i', $clean) ||
            preg_match('/apa\s+saja\s+yang\s+bisa\s+saya\s+lakukan/i', $clean)
        ) {
            if ($role === 'admin') {
                return "Sebagai Admin di YukSehat, kamu dapat:
1. Memantau dashboard dan statistik keseluruhan sistem kesehatan.
2. Mengelola akun pengguna dan pembagian role (Admin, Pengurus, Santri).
3. Mengelola master data santri (tambah, edit, dan nonaktifkan akun).
4. Memantau antrean keluhan santri dan mencatat pemeriksaan kesehatan (termasuk draf klinis AI).
5. Mengakses laporan rekapitulasi kesehatan dan mengekspor data ke Excel (.xlsx).
6. Memantau audit log dan pengaturan sistem.";
            } elseif ($role === 'pengurus') {
                return "Sebagai Pengurus di YukSehat, kamu dapat:
1. Melihat dan mengelola data santri sesuai izin Poskestren.
2. Mengelola antrean keluhan santri yang menunggu pemeriksaan.
3. Mencatat pemeriksaan kesehatan santri, tindakan medis, serta memanfaatkan saran analisis AI.
4. Melihat rekapitulasi pemeriksaan kesehatan santri.
5. Mengakses dan mengekspor laporan pemeriksaan ke Excel (.xlsx).
Catatan: Pengurus tidak berwenang mengelola akun pengguna, mengubah role, atau mengubah pengaturan sistem khusus Admin.";
            } elseif ($role === 'santri') {
                return "Sebagai Santri di YukSehat, kamu dapat:
1. Mengirim keluhan kesehatan saat membutuhkan penanganan Poskestren.
2. Memantau status antrean keluhan kesehatan milikmu sendiri.
3. Melihat informasi dan riwayat pemeriksaan kesehatan pribadi di Dashboard Santri.
4. Mengakses edukasi Pola Hidup Bersih dan Sehat (PHBS) serta berkonsultasi dengan YukSehat AI.
Catatan: Demi menjaga kerahasiaan medis, kamu tidak dapat melihat data pribadi atau rekam medis santri lain.";
            } else {
                return "Kamu belum login, jadi saya belum dapat memastikan identitas akunmu. Silakan login sebagai Admin atau Pengurus untuk mengelola data dan pemeriksaan, atau sebagai Santri untuk mengirim keluhan dan melihat riwayat kesehatan pribadi.";
            }
        }

        // 5. "Mengapa saya tidak bisa membuka menu ini?" / akses ditolak
        if (
            preg_match('/^(mengapa|kenapa)\s+(?:saya\s+)?tidak\s+bisa\s+(?:membuka|buka|akses)\s+menu/i', $clean) ||
            preg_match('/kenapa\s+menu\s+(?:ini\s+)?tidak\s+bisa\s+dibuka/i', $clean) ||
            preg_match('/akses\s+ditolak\s+di\s+menu/i', $clean)
        ) {
            return "Akses menu di YukSehat dibatasi oleh peran akun Anda. Menu Data Santri, Input Pemeriksaan, dan Laporan hanya dapat diakses oleh role Admin dan Pengurus. Role Santri hanya memiliki akses ke Dashboard Santri dan Riwayat Kesehatan pribadi. Jika Anda pengguna tamu, silakan login terlebih dahulu.";
        }

        // 6. "Apa fungsi tombol Gunakan Saran?"
        if (
            preg_match('/(?:apa\s+)?fungsi\s+(?:tombol\s+)?gunakan\s+saran/i', $clean) ||
            preg_match('/tombol\s+gunakan\s+saran(?:\s+fungsinya\s+apa|\s+untuk\s+apa)?/i', $clean)
        ) {
            return "Tombol 'Gunakan Saran ke Formulir' berfungsi menyalin otomatis hasil analisis klinis AI (dugaan diagnosa, rekomendasi tindakan, obat, dan catatan pemantauan) langsung ke kolom formulir pemeriksaan, agar pemeriksa dapat meninjau dan mengeditnya sebelum disimpan.";
        }

        // 7. "Bagaimana cara menambahkan data santri?"
        if (preg_match('/(?:bagaimana\s+)?cara\s+(?:menambah(?:kan)?|tambah|input)\s+(?:data\s+)?santri/i', $clean)) {
            return "Langkah menambahkan santri: buka menu Data Santri -> klik tombol 'Tambah Santri' -> lengkapi formulir (NIS, nama, jenis kelamin, kelas, kamar, kontak, dan riwayat kesehatan) -> klik 'Simpan Data'. Akun login santri otomatis dibuat dengan NIS sebagai username dan kata sandi default.";
        }

        // 8. "Bagaimana cara melakukan pemeriksaan?"
        if (
            preg_match('/(?:bagaimana\s+)?cara\s+(?:melakukan|input|tambah|buat)\s+pemeriksaan/i', $clean) ||
            preg_match('/alur\s+pemeriksaan/i', $clean)
        ) {
            return "Langkah pemeriksaan: buka menu Pemeriksaan -> klik tombol 'Tambah Pemeriksaan' -> pilih santri dan isi keluhan -> klik 'Analisis Keluhan AI' jika ingin draf saran -> lengkapi tanda vital, diagnosa, tindakan, dan obat -> klik 'Simpan Pemeriksaan'.";
        }

        // 9. "Bagaimana cara melihat laporan bulanan?"
        if (
            preg_match('/(?:bagaimana\s+)?cara\s+(?:melihat|lihat|buka)\s+laporan(?:\s+bulanan)?/i', $clean) ||
            preg_match('/cara\s+lihat\s+rekapitulasi/i', $clean)
        ) {
            return "Langkah melihat laporan bulanan: buka menu Laporan -> tentukan rentang tanggal (default awal hingga akhir bulan berjalan) -> pilih filter kelas atau kamar jika diperlukan -> klik 'Terapkan Filter'. Rekapitulasi pemeriksaan periode tersebut akan tampil pada tabel.";
        }

        // 10. "Bagaimana cara mengekspor laporan ke Excel?"
        if (
            preg_match('/(?:bagaimana\s+)?cara\s+(?:ekspor|mengekspor|export|mengunduh|unduh|download|cetak)\s+laporan(?:\s+ke\s+excel)?/i', $clean) ||
            preg_match('/cara\s+(?:ekspor|mengekspor|export|download|unduh)\s+excel/i', $clean)
        ) {
            return "Langkah ekspor Excel: buka menu Laporan -> atur filter periode dan kriteria yang diinginkan -> klik tombol hijau 'Ekspor Excel'. Berkas .xlsx otomatis terunduh berisi 2 sheet: Ringkasan Laporan dan Detail Catatan Klinis Lengkap.";
        }

        // 11. "Apa perbedaan peran admin dan pengurus?"
        if (
            preg_match('/perbedaan\s+(?:peran\s+|role\s+)?admin\s+dan\s+pengurus/i', $clean) ||
            preg_match('/beda\s+admin\s+(?:sama|dan|dengan)\s+pengurus/i', $clean)
        ) {
            return "Admin dan Pengurus sama-sama berwenang mengelola Data Santri, Pemeriksaan (termasuk analisis AI), dan Laporan (termasuk ekspor Excel). Perbedaannya: Admin bertindak sebagai Super User sistem pengelola akun, sedangkan Pengurus adalah petugas/pemeriksa operasional Poskestren yang tercatat sebagai penanggung jawab pemeriksaan.";
        }

        // 12. "Bagaimana cara menonaktifkan santri?"
        if (preg_match('/(?:bagaimana\s+)?cara\s+menonaktifkan\s+santri/i', $clean)) {
            return "Untuk menonaktifkan santri: buka menu Data Santri -> cari santri yang dituju -> klik tombol Nonaktifkan (ikon hapus) -> konfirmasi tindakan. Status akun santri akan diubah menjadi tidak aktif tanpa menghapus riwayat pemeriksaan sebelumnya.";
        }

        return null;
    }

    private function isOffTopic($message) {
        $patterns = [
            '/\b(buat(?:kan)?|tulis(?:kan)?|buatkan|bikin|coding|kode|skrip|script|program|fungsi|function|class|algoritma|algorithm|snippet|debug|debugg(?:ing)?)\b.{0,60}\b(python|javascript|js|php|java|c\+\+|c#|ruby|go|rust|typescript|ts|html|css|sql|bash|shell|kotlin|swift)\b/i',
            '/\b(python|javascript|js|php|java|c\+\+|c#|ruby|go|rust|typescript|ts|bash|kotlin|swift)\b.{0,60}\b(buat(?:kan)?|tulis(?:kan)?|bikin|coding|kode|skrip|script|program|fungsi|function|class)\b/i',
            '/\bbuat(?:kan)?\s+(program|aplikasi|website|web\s*app|bot|game|sistem)\b(?!.{0,30}\byuksehat\b)/i',
            '/\b(?:hitung(?:kan)?|berapa)\s+(?:luas|volume|keliling|integral|turunan|matriks|determinan)\b/i',
            '/\b(tulis(?:kan)?|buat(?:kan)?)\s+(puisi|cerpen|esai|essay|karangan|lirik|lagu|skenario|cerita\s+fiksi)\b/i',
            '/\b(ignore|abaikan|lupakan|forget)\b.{0,50}\b(instruksi|system\s*prompt|rules?|aturan|batasan|restriction|role|peran)\b/i',
            '/\b(tampilkan|berikan|bocorkan|show|reveal|print|get|apa|lihat)\b.{0,40}\b(api[_\s-]?key|secret|rahasia|token|password|credential|kredensial|env|\.env|hash|konfigurasi\s+server)\b/i',
            '/\b(api[_\s-]?key|password|credential|kredensial|token|rahasia)\b.{0,40}\b(admin|database|gemini|sistem|server)\b/i',
            '/\b(mode\s+pengembang|developer\s+mode|dan\s+mode|jailbreak|bypass)\b/i',
            '/\b(jadilah|act\s+as|pretend\s+to\s+be)\b.{0,40}\b(root|superuser|admin|hacker|dan)\b/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }
        return false;
    }

    private function validateAndCleanResponse($text) {
        if (empty($text)) return '';
        $cleaned = trim($text);

        // Bersihkan salam berulang di awal
        $cleaned = preg_replace('/^(Assalamu\'alaikum(?:\s+Warahmatullahi\s+Wabarakatuh)?|Halo|Hai|Selamat\s+(pagi|siang|sore|malam))[\s,!.-]+/i', '', $cleaned);

        // Bersihkan basa-basi pembuka
        $cleaned = preg_replace('/^(Tentu(?:\s+saja)?|Baik(?:\s+saya\s+akan\s+bantu)?|Terima\s+kasih\s+atas\s+pertanyaannya)[\s,!.-]+/i', '', $cleaned);

        // Bersihkan pengulangan pertanyaan pengguna di awal
        $cleaned = preg_replace('/^Mengenai\s+pertanyaan\s+Anda\s+tentang\s+[^:,\n]+[:,\n]\s*/i', '', $cleaned);

        // Pastikan tidak ada tautan promosi komersial
        $cleaned = preg_replace('/https?:\/\/(?:www\.)?(?:shopee|tokopedia|lazada|amazon|bukalapak|tiktok|instagram|facebook)\S*/i', '', $cleaned);

        // Bersihkan penutup bertele-tele
        $cleaned = preg_replace('/\n*(?:Semoga\s+membantu|Semoga\s+lekas\s+sembuh|Ada\s+hal\s+lain\s+yang\s+bisa\s+saya\s+bantu\?|Jangan\s+ragu\s+untuk\s+bertanya\s+lagi)[.!]*\s*$/i', '', $cleaned);

        return !empty(trim($cleaned)) ? trim($cleaned) : trim($text);
    }

    private function buildSystemInstruction($userData) {
        $isLoggedIn = !empty($userData) && !empty($userData['role']) && strtolower(trim($userData['role'])) !== 'guest';
        $roleFormatted = $isLoggedIn ? $this->formatRoleName($userData['role']) : 'Tamu';
        $nama = trim($userData['nama'] ?? 'Pengguna');

        if ($isLoggedIn) {
            $userContext = "Konteks sesi akun pengguna saat ini:
- Nama: {$nama}
- Role: {$roleFormatted}
Aturan Identitas:
Jika pengguna bertanya siapa dirinya (seperti 'Siapa saya?' atau 'Saya login sebagai siapa?'), jawab persis:
\"Kamu login sebagai {$nama} dengan role {$roleFormatted}.\"
Jika pengguna bertanya apa role-nya, jawab:
\"Kamu login dengan role {$roleFormatted} di YukSehat.\"";

            if (strtolower(trim($userData['role'])) === 'admin') {
                $dbSnapshot = ChatbotDataService::getLiveDatabaseSnapshot();
                $userContext .= "\n\n═══════════════════════════════════════════════\nDATA RIIL DATABASE TERKINI (HAK AKSES PENUH ADMIN):\nSebagai Admin (Super User), akun ini berwenang penuh membaca seluruh data database YukSehat.\nGunakan data aktual di bawah ini untuk menjawab pertanyaan admin secara langsung, faktual, dan presisi:\n{$dbSnapshot}\nAturan Khusus Admin: JANGAN PERNAH menyuruh Admin mengecek menu Dashboard jika data sudah tersedia di snapshot di atas. Jawablah langsung secara faktual.";
            }
        } else {
            $userContext = "Konteks sesi akun pengguna saat ini: Pengguna belum login (Tamu / Guest).
Aturan Identitas:
Jika pengguna bertanya siapa dirinya, jawab persis:
\"Kamu belum login, jadi saya belum dapat memastikan identitas akunmu.\"";
        }

        return "Kamu adalah \"YukSehat AI\", asisten digital dan asisten kerja resmi untuk aplikasi Sistem Informasi Kesehatan Pesantren \"YukSehat\" serta informasi kesehatan umum santri.

═══════════════════════════════════════════════
PENGETAHUAN SISTEM YUKSEHAT (SUMBER KEBENARAN APLIKASI)
═══════════════════════════════════════════════
Aplikasi YukSehat dibangun menggunakan Native PHP MVC dengan database MySQL dan microservice Node.js.
Fitur-fitur yang benar-benar tersedia di YukSehat:
1. DASHBOARD:
   - Menampilkan ringkasan metrik statistik: Total Santri Aktif, Pemeriksaan Hari Ini, Pemeriksaan Minggu Ini, Observasi/Rawat UKS, Perlu Rujukan, Total Pengurus, Total Pemeriksaan, dan Santri Sakit.
   - Menampilkan tabel riwayat pemeriksaan kesehatan terbaru.
2. MASTER DATA SANTRI:
   - Menampilkan daftar santri aktif dengan pencarian nama/NIS dan filter kelas/kamar.
   - Form Tambah Santri: NIS, nama, jenis kelamin (L/P), tanggal lahir, kelas, kamar, no HP, golongan darah, alergi, dan riwayat penyakit.
   - Saat santri baru ditambahkan, akun login users otomatis dibuatkan dengan NIS sebagai username dan kata sandi default.
   - Fitur Edit Data Santri dan Deaktivasi/Nonaktifkan Santri (status akun diubah menjadi inactive tanpa menghapus riwayat pemeriksaan).
3. PEMERIKSAAN KESEHATAN (POSKESTREN):
   - Menampilkan daftar pemeriksaan kesehatan dengan filter kata kunci pencarian dan filter rentang tanggal periksa.
   - Input Pemeriksaan Baru: Pilih santri, isi keluhan santri, tanggal periksa, data vital (suhu tubuh, tensi, berat badan, tinggi badan), hasil diagnosa, tindakan, obat, dan catatan.
   - Analisis Keluhan AI (Draf Klinis): Tombol di form pemeriksaan untuk menganalisis keluhan santri secara anonim menjadi draf tinjauan awal bagi petugas Poskestren.
   - Tombol 'Gunakan Saran ke Formulir': Menyalin draf AI (hasil diagnosa, rekomendasi tindakan, obat, catatan) langsung ke input form pemeriksaan agar pemeriksa dapat meninjau dan mengeditnya sebelum menyimpan.
4. RIWAYAT KELUHAN SANTRI:
   - Mencatat keluhan santri dengan status: 'menunggu', 'diperiksa', dan 'selesai'.
5. LAPORAN & REKAPITULASI:
   - Filter laporan berdasarkan rentang tanggal mulai s.d. tanggal akhir (default bulan berjalan), nama santri, kelas, kamar, jenis kelamin, dan keluhan.
   - Tabel ringkasan laporan di layar.
   - Ekspor Excel (.xlsx): Menghasilkan berkas Excel dengan 2 Sheet resmi (Sheet 1: Ringkasan Laporan, Sheet 2: Detail Catatan Klinis Lengkap).
6. PERBEDAAN ROLE & KEWENANGAN:
   - Role 'admin': Super User pengelola sistem. Mengelola data santri (CRUD & nonaktifkan), mencatat pemeriksaan kesehatan santri, menggunakan analisis AI, melihat laporan bulanan, dan mengekspor Excel.
   - Role 'pengurus': Petugas/pemeriksa kesehatan Poskestren operasional. Mengelola data santri, mencatat dan memperbarui pemeriksaan kesehatan, memanfaatkan draf AI dan tombol Gunakan Saran, serta meninjau dan mengekspor laporan pemeriksaan santri.
   - Role 'santri': Hanya dapat mengakses Dashboard Santri dan Riwayat Kesehatan pribadi. Dilarang mengakses data santri lain, form input pemeriksaan santri, atau menu laporan.
   - Pengguna Tamu (Guest): Belum login, harus login ke akun YukSehat untuk mengakses menu aplikasi.

═══════════════════════════════════════════════
ATURAN GAYA JAWABAN: RINGKAS, RELEVAN, TANPA IKLAN
═══════════════════════════════════════════════
1. Utamakan jawaban singkat, jelas, akurat, dan mudah dipahami:
   - Pertanyaan sederhana: 1–3 kalimat (target sekitar 100 kata atau kurang).
   - Pertanyaan cara penggunaan: langkah-langkah singkat berurutan.
   - Pertanyaan rekapitulasi: angka penting dan poin-poin terstruktur.
2. DILARANG mengulang salam pembuka (seperti 'Halo', 'Assalamu\'alaikum') pada setiap balasan.
3. DILARANG mengulang pertanyaan pengguna sebelum menjawab.
4. Hindari paragraf panjang, basa-basi pembuka, pengulangan isi, dan penutup yang tidak diperlukan.
5. DILARANG menampilkan iklan, promosi komersial, tautan sponsor, atau rekomendasi produk lain.
6. JANGAN PERNAH mengarang atau menebak data angka/statistik database santri atau rekap pemeriksaan.
7. Kerahasiaan Medis Santri: Informasi rekam medis sensitif (diagnosa mendalam santri tertentu) tidak boleh dibocorkan ke akun santri atau tamu. Namun untuk Admin atau Pengurus resmi, Anda berwenang menyebutkan daftar nama santri, status santri, dan statistik pemeriksaan sesuai data riil database.
8. Keselamatan Medis: Untuk tanda bahaya klinis (sesak napas, pingsan, demam sangat tinggi berkepanjangan), arahkan agar segera mencari bantuan petugas Poskestren atau faskes darurat.

{$userContext}

═══════════════════════════════════════════════
TOPIK YANG WAJIB DITOLAK
═══════════════════════════════════════════════
Tolak dengan tegas dan ringkas apabila pengguna meminta coding, script, program (Python, PHP, JS, dll.), website/aplikasi luar, puisi/cerita, atau hal di luar kesehatan dan aplikasi YukSehat:
Jawaban: \"Maaf, saya hanya membantu terkait penggunaan YukSehat dan informasi kesehatan yang relevan.\"";
    }

    private function callGeminiDirect($message, $history, $userData) {
        $apiKey = defined('GEMINI_API_KEY') && !empty(GEMINI_API_KEY) && GEMINI_API_KEY !== 'your_gemini_api_key_here'
            ? GEMINI_API_KEY
            : null;

        if (empty($apiKey)) {
            return "Maaf, sistem AI sedang offline dan API key belum dikonfigurasi.";
        }

        $systemInstruction = $this->buildSystemInstruction($userData);

        $contents = [];
        if (is_array($history)) {
            foreach (array_slice($history, -8) as $item) {
                if (!empty($item['text'])) {
                    $role = in_array($item['role'] ?? '', ['model', 'assistant']) ? 'model' : 'user';
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => (string)$item['text']]]
                    ];
                }
            }
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]]
        ];

        $body = [
            'contents' => $contents,
            'systemInstruction' => [
                'parts' => [['text' => $systemInstruction]]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'topP' => 0.95,
                'maxOutputTokens' => 1024
            ]
        ];

        $payload = json_encode($body);
        $lastError = 'Gagal memproses AI';

        // Coba setiap model kandidat secara berurutan
        foreach ($this->candidateModels as $model) {
            $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

            $ch = curl_init($endpoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $resJson = json_decode($result, true);
            if ($httpCode === 200 && !empty($resJson['candidates'][0]['content']['parts'][0]['text'])) {
                $rawText = $resJson['candidates'][0]['content']['parts'][0]['text'];
                $cleanText = $this->validateAndCleanResponse($rawText);

                return [
                    'success' => true,
                    'reply' => $cleanText,
                    'model' => $model,
                    'source' => 'direct-gemini'
                ];
            }

            if (!empty($resJson['error']['message'])) {
                $lastError = $resJson['error']['message'];
            }
        }

        return [
            'success' => true,
            'reply' => 'Maaf, layanan konsultasi AI saat ini sedang dalam rotasi kunci atau pemeliharaan. Untuk konsultasi kesehatan atau bantuan medis, silakan temui petugas kesehatan di Poskestren.',
            'model' => 'safe-fallback'
        ];
    }
}
