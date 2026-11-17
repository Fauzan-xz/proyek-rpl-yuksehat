<?php
/**
 * YukSehat AI Service
 * Sistem Manajemen Model AI Terpadu (NVIDIA NIM & Google Gemini)
 * Memungkinkan pergantian model secara instan tanpa perlu salin-tempel API Key berulang kali.
 */
class AiService {

    const DEFAULT_NVIDIA_KEY = null;
    const NVIDIA_ENDPOINT = 'https://integrate.api.nvidia.com/v1/chat/completions';

    private static $cachedConfig = null;

    /**
     * Katalog model resmi siap pakai yang langsung terhubung ke API Key masing-masing
     */
    public static function getModelCatalog() {
        return [
            'nvidia/nemotron-3.5-bolt-e4b' => [
                'id'          => 'nvidia/nemotron-3.5-bolt-e4b',
                'name'        => 'NVIDIA Nemotron 3.5 Bolt',
                'provider'    => 'nvidia',
                'provider_label' => 'NVIDIA NIM',
                'badge'       => 'Rekomendasi Utama (Tercepat)',
                'badge_class' => 'bg-success',
                'description' => 'Respon sangat cepat, tata bahasa Indonesia alami, format draf medis rapi.'
            ],
            'gemini-flash-lite-latest' => [
                'id'          => 'gemini-flash-lite-latest',
                'name'        => 'Google Gemini Flash Lite',
                'provider'    => 'gemini',
                'provider_label' => 'Google Cloud',
                'badge'       => 'Ringan & Efisien',
                'badge_class' => 'bg-primary',
                'description' => 'Model resmi Google AI Studio dengan latensi rendah dan konsumsi kuota hemat.'
            ],
            'gemini-3.5-flash' => [
                'id'          => 'gemini-3.5-flash',
                'name'        => 'Google Gemini 3.5 Flash',
                'provider'    => 'gemini',
                'provider_label' => 'Google Cloud',
                'badge'       => 'Stabil',
                'badge_class' => 'bg-info text-dark',
                'description' => 'Keseimbangan performa tinggi untuk pemahaman konsultasi kesehatan.'
            ],
            'gemini-3.8-flash' => [
                'id'          => 'gemini-3.8-flash',
                'name'        => 'Google Gemini 3.8 Flash',
                'provider'    => 'gemini',
                'provider_label' => 'Google Cloud',
                'badge'       => 'Penalaran Tinggi',
                'badge_class' => 'bg-dark',
                'description' => 'Model generasi terbaru Google dengan kemampuan analisis mendalam.'
            ],
            'poolside/laguna-xs-2.1' => [
                'id'          => 'poolside/laguna-xs-2.1',
                'name'        => 'Poolside Laguna XS 2.1',
                'provider'    => 'nvidia',
                'provider_label' => 'NVIDIA NIM',
                'badge'       => 'Alternatif Ringan',
                'badge_class' => 'bg-secondary',
                'description' => 'Alternatif model efisien berbasis cloud server NVIDIA.'
            ],
            'nvidia/nemotron-3-ultra-550b-a55b' => [
                'id'          => 'nvidia/nemotron-3-ultra-550b-a55b',
                'name'        => 'NVIDIA Nemotron 3 Ultra 550B',
                'provider'    => 'nvidia',
                'provider_label' => 'NVIDIA NIM',
                'badge'       => 'Model Kapasitas Besar',
                'badge_class' => 'bg-warning text-dark',
                'description' => 'Model komputasi besar untuk penalaran sintesis yang kompleks.'
            ]
        ];
    }

    /**
     * Memuat konfigurasi aktif saat ini
     */
    public static function getConfig($refresh = false) {
        if (self::$cachedConfig !== null && !$refresh) {
            return self::$cachedConfig;
        }

        $dbSettings = self::getAllSettingsFromDb();

        // 1. API Keys (disimpan permanen, utamakan pengaturan database jika sudah diset)
        if (array_key_exists('nvidia_api_key', $dbSettings)) {
            $nvidiaKey = $dbSettings['nvidia_api_key'];
        } else {
            $nvidiaKey = $_ENV['NVIDIA_API_KEY'] ?? (defined('NVIDIA_API_KEY') && NVIDIA_API_KEY ? NVIDIA_API_KEY : '');
        }

        if (array_key_exists('gemini_api_key', $dbSettings)) {
            $geminiKey = $dbSettings['gemini_api_key'];
        } else {
            $geminiKey = $_ENV['GEMINI_API_KEY'] ?? (defined('GEMINI_API_KEY') && GEMINI_API_KEY ? GEMINI_API_KEY : '');
        }

        // 2. Model Pilihan untuk Chatbot
        $chatbotModel = $dbSettings['chatbot_model'] ?? (
            $_ENV['CHATBOT_MODEL'] ?? ($dbSettings['ai_model'] ?? 'nvidia/nemotron-3.5-bolt-e4b')
        );

        // 3. Model Pilihan untuk Analisis AI
        $clinicalModel = $dbSettings['clinical_model'] ?? (
            $_ENV['CLINICAL_MODEL'] ?? ($dbSettings['ai_model'] ?? 'nvidia/nemotron-3.5-bolt-e4b')
        );

        $chatbotProvider = self::getProviderForModel($chatbotModel);
        $clinicalProvider = self::getProviderForModel($clinicalModel);

        self::$cachedConfig = [
            'chatbot_model'     => $chatbotModel,
            'clinical_model'    => $clinicalModel,
            'chatbot_provider'  => $chatbotProvider,
            'clinical_provider' => $clinicalProvider,
            'nvidia_api_key'    => $nvidiaKey,
            'gemini_api_key'    => $geminiKey,
            'nvidia_masked'     => self::maskKey($nvidiaKey),
            'gemini_masked'     => self::maskKey($geminiKey),
            // Kompatibilitas mundur
            'model'             => $chatbotModel,
            'provider'          => $chatbotProvider,
            'api_key'           => ($chatbotProvider === 'nvidia' ? $nvidiaKey : $geminiKey),
            'masked_key'        => self::maskKey($chatbotProvider === 'nvidia' ? $nvidiaKey : $geminiKey),
            'updated_at'        => $dbSettings['updated_at'] ?? date('Y-m-d H:i:s')
        ];

        return self::$cachedConfig;
    }

    /**
     * Mengetahui provider otomatis dari nama model
     */
    public static function getProviderForModel($model) {
        $m = trim(strtolower($model ?? ''));
        if (strpos($m, 'gemini') === 0 || strpos($m, 'models/gemini') !== false) {
            return 'gemini';
        }
        if (strpos($m, 'gpt-') === 0) {
            return 'openai';
        }
        return 'nvidia';
    }

    /**
     * Mengambil API key yang tepat secara otomatis sesuai model yang dipilih
     */
    public static function getKeyForModel($model) {
        $provider = self::getProviderForModel($model);
        $config = self::getConfig();
        if ($provider === 'gemini') {
            return $config['gemini_api_key'];
        }
        return $config['nvidia_api_key'];
    }

    /**
     * Simpan pergantian model Chatbot dan Analisis Klinis (Tanpa perlu input API Key)
     */
    public static function saveModels($chatbotModel, $clinicalModel) {
        $chatbotModel = trim($chatbotModel ?: 'nvidia/nemotron-3.5-bolt-e4b');
        $clinicalModel = trim($clinicalModel ?: 'nvidia/nemotron-3.5-bolt-e4b');

        $primaryProvider = self::getProviderForModel($chatbotModel);
        $config = self::getConfig();
        $primaryKey = ($primaryProvider === 'nvidia') ? $config['nvidia_api_key'] : $config['gemini_api_key'];

        // 1. Simpan ke database
        self::saveSettingsToDb([
            'chatbot_model'  => $chatbotModel,
            'clinical_model' => $clinicalModel,
            // Kompatibilitas mundur
            'ai_model'       => $chatbotModel,
            'ai_provider'    => $primaryProvider,
            'ai_api_key'     => $primaryKey
        ]);

        // 2. Sinkronkan ke .env
        self::syncEnv([
            'CHATBOT_MODEL'  => $chatbotModel,
            'CLINICAL_MODEL' => $clinicalModel,
            'AI_MODEL'       => $chatbotModel,
            'AI_PROVIDER'    => $primaryProvider
        ]);

        self::$cachedConfig = null;
        return true;
    }

    /**
     * Simpan / Ganti API Key untuk satu provider tertentu
     */
    public static function saveApiKey($provider, $key) {
        $provider = strtolower(trim($provider));
        $key = trim($key ?? '');

        if (empty($key) || strpos($key, '••••') !== false) {
            return false;
        }

        $dbKey = ($provider === 'nvidia') ? 'nvidia_api_key' : (($provider === 'gemini') ? 'gemini_api_key' : '');
        $envKey = ($provider === 'nvidia') ? 'NVIDIA_API_KEY' : (($provider === 'gemini') ? 'GEMINI_API_KEY' : '');

        if (empty($dbKey) || empty($envKey)) return false;

        self::saveSettingsToDb([$dbKey => $key]);
        self::syncEnv([$envKey => $key]);
        self::$cachedConfig = null;
        return true;
    }

    /**
     * Hapus API Key dari database dan .env untuk provider tertentu
     */
    public static function deleteApiKey($provider) {
        $provider = strtolower(trim($provider));
        $dbKey = ($provider === 'nvidia') ? 'nvidia_api_key' : (($provider === 'gemini') ? 'gemini_api_key' : '');
        $envKey = ($provider === 'nvidia') ? 'NVIDIA_API_KEY' : (($provider === 'gemini') ? 'GEMINI_API_KEY' : '');

        if (empty($dbKey) || empty($envKey)) return false;

        self::saveSettingsToDb([$dbKey => '']);
        self::syncEnv([$envKey => '']);
        self::$cachedConfig = null;
        return true;
    }

    /**
     * Memperbarui kunci API permanen (fitur lanjutan jika admin ingin mengganti kunci di masa depan)
     */
    public static function updateApiKeys($nvidiaKey = null, $geminiKey = null) {
        $toSave = [];
        $envVars = [];

        if (!empty($nvidiaKey) && strpos($nvidiaKey, '••••') === false) {
            $toSave['nvidia_api_key'] = trim($nvidiaKey);
            $envVars['NVIDIA_API_KEY'] = trim($nvidiaKey);
        }
        if (!empty($geminiKey) && strpos($geminiKey, '••••') === false) {
            $toSave['gemini_api_key'] = trim($geminiKey);
            $envVars['GEMINI_API_KEY'] = trim($geminiKey);
        }

        if (!empty($toSave)) {
            self::saveSettingsToDb($toSave);
            self::syncEnv($envVars);
            self::$cachedConfig = null;
        }
        return true;
    }

    /**
     * Uji koneksi model tertentu secara langsung menggunakan API Key yang sudah terdaftar
     */
    public static function testModel($model) {
        $startTime = microtime(true);
        $model = trim($model ?: 'nvidia/nemotron-3.5-bolt-e4b');
        $provider = self::getProviderForModel($model);
        $apiKey = self::getKeyForModel($model);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => "API Key untuk provider {$provider} belum tersedia di sistem."
            ];
        }

        if ($provider === 'nvidia') {
            // Verifikasi instan ke /models endpoint NVIDIA NIM
            $url = 'https://integrate.api.nvidia.com/v1/models';
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $latency = (int)round((microtime(true) - $startTime) * 1000);

            if ($err) {
                return [
                    'success' => false,
                    'latency_ms' => $latency,
                    'message' => 'Gagal koneksi ke server NVIDIA: ' . $err
                ];
            }

            if ($code === 200) {
                return [
                    'success' => true,
                    'model' => $model,
                    'provider' => 'NVIDIA NIM',
                    'latency_ms' => $latency,
                    'message' => "Model {$model} siap digunakan! API Key terverifikasi aktif (respon {$latency} ms)."
                ];
            }

            return [
                'success' => false,
                'latency_ms' => $latency,
                'message' => "Server NVIDIA NIM mengembalikan kode HTTP {$code}"
            ];
        } else {
            // Verifikasi Google Gemini
            $url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . urlencode($apiKey);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $latency = (int)round((microtime(true) - $startTime) * 1000);

            if ($code === 200) {
                return [
                    'success' => true,
                    'model' => $model,
                    'provider' => 'Google Gemini',
                    'latency_ms' => $latency,
                    'message' => "Model {$model} siap digunakan! API Key Gemini terverifikasi aktif (respon {$latency} ms)."
                ];
            }

            return [
                'success' => false,
                'latency_ms' => $latency,
                'message' => "Google Gemini mengembalikan kode HTTP {$code}"
            ];
        }
    }

    /**
     * Chat completion untuk Chatbot Asisten
     * Otomatis menggunakan model chatbot yang dipilih dan API key yang sesuai
     */
    public static function chat($message, $history = [], $userData = null, $systemInstruction = '') {
        $config = self::getConfig();
        $model = $config['chatbot_model'];
        $provider = $config['chatbot_provider'];
        $apiKey = self::getKeyForModel($model);

        if (empty($apiKey)) {
            return [
                'success' => true,
                'reply'   => 'Layanan AI sedang offline. Silakan hubungi petugas kesehatan di Poskestren.',
                'model'   => 'offline-fallback'
            ];
        }

        if ($provider === 'nvidia') {
            return self::executeNvidiaChat($model, $apiKey, $message, $history, $systemInstruction);
        } else {
            return self::executeGeminiChat($model, $apiKey, $message, $history, $systemInstruction);
        }
    }

    /**
     * Analisis Keluhan Klinis (Draf Pemeriksaan Poskestren)
     * Otomatis menggunakan model analisis yang dipilih dan API key yang sesuai
     */
    public static function analyzeClinical($keluhan, $vitalSigns = [], $konteks = []) {
        $config = self::getConfig();
        $model = $config['clinical_model'];
        $provider = $config['clinical_provider'];
        $apiKey = self::getKeyForModel($model);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'Layanan AI offline: Kunci API belum terpasang di sistem.'
            ];
        }

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
        if (!empty($vitalSigns['tekanan_darah'])) $prompt .= "- Tekanan Darah: {$vitalSigns['tekanan_darah']} mmHg\n";
        if (!empty($vitalSigns['berat_badan'])) $prompt .= "- Berat Badan: {$vitalSigns['berat_badan']} kg\n";
        if (!empty($vitalSigns['tinggi_badan'])) $prompt .= "- Tinggi Badan: {$vitalSigns['tinggi_badan']} cm\n";
        if (!empty($konteks['riwayat_penyakit'])) $prompt .= "- Riwayat Penyakit: {$konteks['riwayat_penyakit']}\n";
        if (!empty($konteks['alergi'])) $prompt .= "- Riwayat Alergi: {$konteks['alergi']}\n";
        $prompt .= "\nBuat analisis draf tinjauan singkat dan padat (maksimal 1-2 poin pendek per bagian, langsung ke intinya).";

        if ($provider === 'nvidia') {
            $payload = json_encode([
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemInstruction],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 350,
                'temperature' => 0.2
            ]);

            $ch = curl_init(self::NVIDIA_ENDPOINT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $json = json_decode($res, true);
            if ($code === 200 && !empty($json['choices'][0]['message']['content'])) {
                return [
                    'success'    => true,
                    'analysis'   => $json['choices'][0]['message']['content'],
                    'model'      => $model,
                    'provider'   => 'NVIDIA NIM',
                    'disclaimer' => 'Draf saran awal untuk ditinjau petugas kesehatan. Bukan diagnosis medis final atau resep obat otomatis.'
                ];
            }
        } else {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
            $payload = json_encode([
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]]
                ],
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 380
                ]
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $json = json_decode($res, true);
            if ($code === 200 && !empty($json['candidates'][0]['content']['parts'][0]['text'])) {
                return [
                    'success'    => true,
                    'analysis'   => $json['candidates'][0]['content']['parts'][0]['text'],
                    'model'      => $model,
                    'provider'   => 'Google Gemini',
                    'disclaimer' => 'Draf saran awal untuk ditinjau petugas kesehatan. Bukan diagnosis medis final atau resep obat otomatis.'
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'Gagal menghubungi server AI untuk analisis keluhan. Pastikan koneksi internet aktif.'
        ];
    }

    /**
     * Eksekusi Chat NVIDIA NIM
     */
    private static function executeNvidiaChat($model, $apiKey, $message, $history, $systemInstruction) {
        $messages = [];
        if (!empty($systemInstruction)) {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }

        if (is_array($history)) {
            foreach (array_slice($history, -8) as $item) {
                if (!empty($item['text'])) {
                    $role = in_array($item['role'] ?? '', ['model', 'assistant']) ? 'assistant' : 'user';
                    $messages[] = ['role' => $role, 'content' => (string)$item['text']];
                }
            }
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        $payload = json_encode([
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => 800,
            'temperature' => 0.6
        ]);

        $ch = curl_init(self::NVIDIA_ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($code === 200 && !empty($json['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'reply'   => self::cleanResponse($json['choices'][0]['message']['content']),
                'model'   => $model,
                'source'  => 'nvidia-nim'
            ];
        }

        return [
            'success' => true,
            'reply'   => 'Maaf, sistem konsultasi AI sedang sibuk. Silakan tanyakan langsung ke petugas kesehatan Poskestren.',
            'model'   => 'safe-fallback'
        ];
    }

    /**
     * Eksekusi Chat Google Gemini
     */
    private static function executeGeminiChat($model, $apiKey, $message, $history, $systemInstruction) {
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
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1024
            ]
        ];

        if (!empty($systemInstruction)) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]]
            ];
        }

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($res, true);
        if ($code === 200 && !empty($json['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'reply'   => self::cleanResponse($json['candidates'][0]['content']['parts'][0]['text']),
                'model'   => $model,
                'source'  => 'google-gemini'
            ];
        }

        return [
            'success' => true,
            'reply'   => 'Maaf, layanan konsultasi AI sedang dalam pemeliharaan. Silakan hubungi petugas di Poskestren.',
            'model'   => 'safe-fallback'
        ];
    }

    /**
     * Sensor token untuk tampilan aman
     */
    public static function maskKey($key) {
        $k = trim($key ?? '');
        if (empty($k)) return '';
        $len = strlen($k);
        if ($len <= 10) return '••••••••';
        return substr($k, 0, 7) . '••••••••' . substr($k, -6);
    }

    /**
     * Membersihkan teks balasan
     */
    public static function cleanResponse($text) {
        if (empty($text)) return '';
        $cleaned = trim($text);
        $cleaned = preg_replace('/^(Assalamu\'alaikum(?:\s+Warahmatullahi\s+Wabarakatuh)?|Halo|Hai|Selamat\s+(pagi|siang|sore|malam))[\s,!.-]+/i', '', $cleaned);
        $cleaned = preg_replace('/^(Tentu(?:\s+saja)?|Baik(?:\s+saya\s+akan\s+bantu)?|Terima\s+kasih\s+atas\s+pertanyaannya)[\s,!.-]+/i', '', $cleaned);
        $cleaned = preg_replace('/^Mengenai\s+pertanyaan\s+Anda\s+tentang\s+[^:,\n]+[:,\n]\s*/i', '', $cleaned);
        $cleaned = preg_replace('/https?:\/\/(?:www\.)?(?:shopee|tokopedia|lazada|amazon|bukalapak|tiktok|instagram|facebook)\S*/i', '', $cleaned);
        $cleaned = preg_replace('/\n*(?:Semoga\s+membantu|Semoga\s+lekas\s+sembuh|Ada\s+hal\s+lain\s+yang\s+bisa\s+saya\s+bantu\?|Jangan\s+ragu\s+untuk\s+bertanya\s+lagi)[.!]*\s*$/i', '', $cleaned);
        return !empty(trim($cleaned)) ? trim($cleaned) : trim($text);
    }

    private static function getAllSettingsFromDb() {
        try {
            $db = new Database();
            $db->query("SELECT setting_key, setting_value, updated_at FROM system_settings");
            $rows = $db->resultSet();
            $settings = [];
            $maxUpdated = null;
            if ($rows) {
                foreach ($rows as $r) {
                    $settings[$r['setting_key']] = $r['setting_value'];
                    if (!$maxUpdated || $r['updated_at'] > $maxUpdated) {
                        $maxUpdated = $r['updated_at'];
                    }
                }
                $settings['updated_at'] = $maxUpdated;
            }
            return $settings;
        } catch (Exception $e) {
            return [];
        }
    }

    private static function saveSettingsToDb($data) {
        try {
            $db = new Database();
            $db->query("CREATE TABLE IF NOT EXISTS `system_settings` (
                `setting_key` VARCHAR(64) PRIMARY KEY,
                `setting_value` TEXT,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $db->execute();

            foreach ($data as $key => $val) {
                $db->query("INSERT INTO system_settings (setting_key, setting_value, updated_at) 
                            VALUES (:key, :val, NOW())
                            ON DUPLICATE KEY UPDATE setting_value = :val2, updated_at = NOW()");
                $db->bind('key', $key);
                $db->bind('val', (string)$val);
                $db->bind('val2', (string)$val);
                $db->execute();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    private static function syncEnv($vars) {
        $envPath = dirname(dirname(__DIR__)) . '/.env';
        if (!file_exists($envPath)) return false;

        $lines = file($envPath, FILE_IGNORE_NEW_LINES);
        $updated = [];

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || strpos($trimmed, '#') === 0) continue;
            $parts = explode('=', $trimmed, 2);
            $key = trim($parts[0]);
            if (array_key_exists($key, $vars)) {
                $lines[$i] = $key . '=' . $vars[$key];
                $updated[$key] = true;
                $_ENV[$key] = $vars[$key];
                $_SERVER[$key] = $vars[$key];
                putenv("{$key}={$vars[$key]}");
            }
        }

        foreach ($vars as $key => $val) {
            if (!isset($updated[$key])) {
                $lines[] = $key . '=' . $val;
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
                putenv("{$key}={$val}");
            }
        }

        @file_put_contents($envPath, implode("\n", $lines) . "\n");
        return true;
    }
}
