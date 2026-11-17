<?php
/**
 * YukSehat Fonnte WhatsApp Gateway Service
 * Layanan integrasi notifikasi WhatsApp via Fonnte API.
 * 
 * Prinsip Keamanan:
 * - Zero Leakage: Token tidak pernah diekspos ke frontend, response API, atau pesan error.
 * - Non-Destructive: Tidak mengubah konfigurasi yang sudah ada.
 * - Safe Testing Guard: Mendukung mode dry-run/simulasi untuk mencegah pengiriman pesan sungguhan tanpa persetujuan.
 */
class FonnteService {

    const API_SEND_URL   = 'https://api.fonnte.com/send';
    const API_DEVICE_URL = 'https://api.fonnte.com/device';
    const TIMEOUT_SECONDS = 15;

    /**
     * Handler untuk pengujian unit otomatis tanpa koneksi jaringan eksternal
     */
    private static $mockHandler = null;

    /**
     * Set mock handler untuk testing
     * @param callable|null $handler function($action, $target, $message, $options)
     */
    public static function setMockHandler(?callable $handler) {
        self::$mockHandler = $handler;
    }

    /**
     * Dapatkan mock handler yang sedang aktif
     */
    public static function getMockHandler() {
        return self::$mockHandler;
    }

    /**
     * Mengambil token dari konstanta atau lingkungan secara aman
     */
    private static function getToken() {
        if (defined('FONNTE_TOKEN') && !empty(FONNTE_TOKEN)) {
            return trim((string)FONNTE_TOKEN);
        }
        $token = $_ENV['FONNTE_TOKEN'] ?? $_SERVER['FONNTE_TOKEN'] ?? getenv('FONNTE_TOKEN');
        return !empty($token) ? trim((string)$token) : null;
    }

    /**
     * Memeriksa apakah token sudah dikonfigurasi dan valid tanpa mengekspos nilainya
     */
    public static function isConfigured() {
        $token = self::getToken();
        return (!empty($token) && is_string($token) && strlen($token) > 0);
    }

    /**
     * Mendapatkan ringkasan status konfigurasi (Aman untuk log/laporan - tanpa token)
     */
    public static function getStatusSummary() {
        $isSet = self::isConfigured();
        $len = $isSet ? strlen(self::getToken()) : 0;

        return [
            'is_configured' => $isSet,
            'token_length'  => $len,
            'status'        => $isSet ? 'Terkonfigurasi (Siap Digunakan)' : 'Belum Dikonfigurasi di .env'
        ];
    }

    /**
     * Menstandarkan format nomor telepon Indonesia (misal: 0812... -> 62812...)
     */
    public static function formatPhoneNumber($phone) {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);

        if (empty($clean)) {
            return '';
        }

        // Awalan 08xxx -> 628xxx
        if (strpos($clean, '0') === 0) {
            $clean = '62' . substr($clean, 1);
        }
        // Awalan 8xxx -> 628xxx
        elseif (strpos($clean, '62') !== 0 && strpos($clean, '8') === 0) {
            $clean = '62' . $clean;
        }

        return $clean;
    }

    /**
     * Pengiriman pesan WhatsApp via Fonnte Gateway
     * 
     * @param string $target Nomor tujuan WhatsApp
     * @param string $message Teks pesan yang akan dikirim
     * @param array $options Opsi tambahan:
     *        - 'dry_run' (bool): jika true, hanya mensimulasikan tanpa HTTP request ke gateway
     *        - 'countryCode' (string): default '62'
     * @return array Status hasil pengiriman (bebas dari nilai token)
     */
    public static function send($target, $message, array $options = []) {
        $token = self::getToken();

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Konfigurasi FONNTE_TOKEN belum disetel pada file .env.'
            ];
        }

        $formattedTarget = self::formatPhoneNumber($target);
        if (empty($formattedTarget) || strlen($formattedTarget) < 9) {
            return [
                'success' => false,
                'message' => 'Format nomor WhatsApp tujuan tidak valid.'
            ];
        }

        if (empty(trim($message))) {
            return [
                'success' => false,
                'message' => 'Pesan WhatsApp tidak boleh kosong.'
            ];
        }

        // Safe Guard: Mode simulasi (dry-run)
        if (!empty($options['dry_run'])) {
            return [
                'success'         => true,
                'dry_run'         => true,
                'target'          => $formattedTarget,
                'message_length'  => mb_strlen($message),
                'message_preview' => mb_substr($message, 0, 60) . (mb_strlen($message) > 60 ? '...' : ''),
                'status'          => 'Simulasi berhasil. Pesan tidak dikirim ke gateway Fonnte (Dry-Run Mode).'
            ];
        }

        // Mock Handler untuk automated testing tanpa request HTTP keluar
        if (self::$mockHandler !== null) {
            return call_user_func(self::$mockHandler, 'send', $formattedTarget, $message, $options);
        }

        // Pengiriman nyata ke Fonnte API
        $payload = [
            'target'      => $formattedTarget,
            'message'     => $message,
            'countryCode' => $options['countryCode'] ?? '62'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_SEND_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success'         => false,
                'message'         => 'Koneksi ke gateway WhatsApp Fonnte gagal: ' . ($curlError ?: 'Network error'),
                'http_code'       => 0,
                'provider_status' => 'network_error'
            ];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && is_array($result) && !empty($result['status'])) {
            $msgId = null;
            if (!empty($result['id'])) {
                $msgId = is_array($result['id']) ? implode(',', $result['id']) : (string)$result['id'];
            }
            return [
                'success'             => true,
                'target'              => $formattedTarget,
                'detail'              => $result['detail'] ?? 'Pesan berhasil diteruskan ke antrean WhatsApp Fonnte.',
                'http_code'           => $httpCode,
                'provider_message_id' => $msgId,
                'provider_status'     => $result['process'] ?? 'gateway_accepted'
            ];
        }

        $errorReason = 'Gateway merespons kesalahan.';
        if (is_array($result) && isset($result['reason'])) {
            $errorReason = (string)$result['reason'];
        } elseif (is_array($result) && isset($result['detail'])) {
            $errorReason = (string)$result['detail'];
        }

        return [
            'success'         => false,
            'message'         => $errorReason,
            'http_code'       => $httpCode,
            'provider_status' => 'rejected'
        ];
    }

    /**
     * Memeriksa status perangkat terhubung di Fonnte API Gateway.
     * 
     * PERINGATAN KEAMANAN & ARSITEKTUR JARINGAN (L-03):
     * - Method ini melakukan HTTP request KELUAR secara aktif ke endpoint https://api.fonnte.com/device.
     * - Pemanggilan method ini HANYA boleh dilakukan melalui alur backend yang terotentikasi ketat
     *   khusus role Administrator dan untuk kebutuhan diagnostik operasional yang jelas.
     * - DILARANG memanggil method ini dari alur publik, polling background otomatis, cron job tanpa izin,
     *   atau pengujian simulasi Safe Mode.
     * - Method ini tidak membuat atau mengirimkan pesan WhatsApp, namun tetap mengonsumsi jaringan keluar.
     * 
     * @return array Status perangkat (device info aman tanpa token)
     */
    public static function checkDevice() {
        if (self::$mockHandler !== null) {
            return call_user_func(self::$mockHandler, 'checkDevice', null, null, []);
        }

        $token = self::getToken();

        if (empty($token)) {
            return [
                'success' => false,
                'message' => 'Konfigurasi FONNTE_TOKEN belum disetel pada file .env.'
            ];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::API_DEVICE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, []);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $token
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke server Fonnte: ' . ($curlError ?: 'Network error')
            ];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && is_array($result) && !empty($result['status'])) {
            return [
                'success'       => true,
                'device_status' => $result['device_status'] ?? 'unknown',
                'name'          => $result['name'] ?? '-',
                'package'       => $result['package'] ?? '-',
                'quota'         => $result['quota'] ?? 0,
                'expired'       => $result['expired'] ?? '-'
            ];
        }

        $reason = $result['reason'] ?? ($result['detail'] ?? 'Autentikasi token Fonnte gagal.');
        return [
            'success'   => false,
            'message'   => $reason,
            'http_code' => $httpCode
        ];
    }
}

