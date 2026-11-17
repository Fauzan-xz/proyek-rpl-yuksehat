<?php

// Function to load .env variables
function loadEnv($path) {
    if(!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

loadEnv(dirname(__DIR__) . '/.env');

date_default_timezone_set('Asia/Jakarta');

define('BASEURL', 'http://localhost/YukSehat');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'local'); // 'local' or 'production'

// ============================================================
// ENVIRONMENT-BASED ERROR HANDLING (L-DEP-01)
// ============================================================
if (APP_ENV === 'production') {
    // Production Mode: Do not expose stack traces, paths, or credentials in responses
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    // Keep error reporting active so all errors are safely logged to server logs
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Local / Development Mode: Preserve developer diagnostics
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? (getenv('GEMINI_API_KEY') ?: null));
define('NVIDIA_API_KEY', $_ENV['NVIDIA_API_KEY'] ?? $_SERVER['NVIDIA_API_KEY'] ?? (getenv('NVIDIA_API_KEY') ?: null));
define('FONNTE_TOKEN', $_ENV['FONNTE_TOKEN'] ?? $_SERVER['FONNTE_TOKEN'] ?? (getenv('FONNTE_TOKEN') ?: null));
define('WHATSAPP_ENABLED', filter_var($_ENV['WHATSAPP_ENABLED'] ?? $_SERVER['WHATSAPP_ENABLED'] ?? (getenv('WHATSAPP_ENABLED') ?: 'false'), FILTER_VALIDATE_BOOLEAN));

define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'yuk_sehat');
define('INTERNAL_API_SECRET', $_ENV['INTERNAL_API_SECRET'] ?? $_SERVER['INTERNAL_API_SECRET'] ?? (getenv('INTERNAL_API_SECRET') ?: null));
