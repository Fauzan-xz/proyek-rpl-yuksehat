<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Database.php';

require_once __DIR__ . '/helpers/SessionHelper.php';
require_once __DIR__ . '/helpers/AuthHelper.php';
require_once __DIR__ . '/helpers/MedicalFormatHelper.php';
require_once __DIR__ . '/helpers/SimpleXLSXGen.php';
require_once __DIR__ . '/services/ChatbotDataService.php';
require_once __DIR__ . '/services/AiService.php';
require_once __DIR__ . '/services/FonnteService.php';

// SECURITY FIX VULN-03: Harden session cookie parameters before session_start()
// httponly: JS cannot read cookie (anti-XSS cookie theft)
// samesite=Lax: protects against CSRF via cross-site requests
// secure: only send over HTTPS (will be enforced in production)
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path'     => '/',
    'domain'   => $cookieParams['domain'],
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // true on HTTPS
    'httponly' => true,  // Block JS access to session cookie
    'samesite' => 'Lax', // Prevent CSRF via third-party sites
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
