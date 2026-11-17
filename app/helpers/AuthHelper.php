<?php

class AuthHelper {
    public static function isLoggedIn() {
        return isset($_SESSION['user']);
    }

    public static function guest() {
        return !self::isLoggedIn();
    }

    public static function user() {
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            SessionHelper::setFlash('pesan', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/login');
            exit;
        }
    }

    public static function requireGuest() {
        if (self::isLoggedIn()) {
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    public static function requireRole($roles = []) {
        self::requireLogin();
        $user = self::user();
        if (!in_array($user['role'], $roles)) {
            SessionHelper::setFlash('pesan', 'Anda tidak memiliki akses ke halaman tersebut.', 'alert alert-warning');
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
    }

    /**
     * Memeriksa apakah user yang sedang login adalah Ronal sebagai Pengurus
     * Hanya Ronal (pengurus) yang memiliki kewenangan melakukan input pemeriksaan kesehatan
     */
    public static function canInputPemeriksaan() {
        $user = self::user();
        if (!$user) return false;
        return ($user['role'] === 'pengurus' && strtolower(trim($user['username'])) === 'ronal');
    }

    /**
     * Proteksi Server-Side: Hanya Ronal (Pengurus) yang diizinkan mengakses form input dan menyimpan pemeriksaan
     */
    public static function requireCanInputPemeriksaan() {
        self::requireLogin();
        if (!self::canInputPemeriksaan()) {
            SessionHelper::setFlash('pesan', 'Akses ditolak: Hanya petugas Ronal (Pengurus) yang berwenang melakukan input pemeriksaan kesehatan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/pemeriksaan');
            exit;
        }
    }

    public static function generateCSRF() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Call this after session_regenerate_id to rotate the CSRF token
    public static function rotateCSRF() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function validateCSRF($token) {
        if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
            return true;
        }
        return false;
    }
}
