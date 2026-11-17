<?php
/**
 * Setup & Verifikasi Akun Admin Fauzan dan Pengurus Ronal
 * Jalankan via CLI: php setup_admin.php
 */

require_once __DIR__ . '/app/init.php';

try {
    $db = new Database();

    echo "=== MEMULAI SETUP AKUN YUKSEHAT ===\n";

    // ─────────────────────────────────────────────
    // 1. Akun Admin Fauzan
    // ─────────────────────────────────────────────
    $db->query("SELECT id, username, password, role FROM users WHERE role = 'admin' OR username = 'admin' LIMIT 1");
    $admin = $db->single();

    if (!$admin) {
        // Jika belum ada admin sama sekali
        $adminPass = getenv('ADMIN_PASSWORD') ?: 'admin123';
        $hashedAdmin = password_hash($adminPass, PASSWORD_BCRYPT);
        $db->query("INSERT INTO users (username, password, role, status) VALUES ('admin', :pass, 'admin', 'active')");
        $db->bind('pass', $hashedAdmin);
        $db->execute();

        $db->query("SELECT LAST_INSERT_ID() as id");
        $adminId = $db->single()['id'];
        echo "[Admin] Akun user admin berhasil dibuat (ID: {$adminId}).\n";
    } else {
        $adminId = $admin['id'];
        echo "[Admin] Akun user admin ditemukan (ID: {$adminId}, Username: {$admin['username']}).\n";
    }

    // Pastikan profil Admin memiliki nama tampilan Fauzan di tabel pengurus
    $db->query("SELECT id, nama FROM pengurus WHERE user_id = :uid LIMIT 1");
    $db->bind('uid', $adminId);
    $adminProfile = $db->single();

    if (!$adminProfile) {
        $db->query("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (:uid, 'Fauzan', 'Administrator', '081234567891')");
        $db->bind('uid', $adminId);
        $db->execute();
        echo "[Admin] Profil Fauzan berhasil dibuat untuk user ID {$adminId}.\n";
    } else {
        if ($adminProfile['nama'] !== 'Fauzan') {
            $db->query("UPDATE pengurus SET nama = 'Fauzan' WHERE id = :pid");
            $db->bind('pid', $adminProfile['id']);
            $db->execute();
            echo "[Admin] Profil admin diperbarui menjadi nama Fauzan.\n";
        } else {
            echo "[Admin] Profil Fauzan sudah aktif dan sesuai.\n";
        }
    }

    // ─────────────────────────────────────────────
    // 2. Akun Pengurus Ronal
    // ─────────────────────────────────────────────
    $db->query("SELECT u.id, u.username, u.role, p.id as pengurus_id, p.nama 
                FROM users u 
                LEFT JOIN pengurus p ON u.id = p.user_id 
                WHERE u.username = 'ronal' OR LOWER(p.nama) = 'ronal' 
                LIMIT 1");
    $existingRonal = $db->single();

    if ($existingRonal) {
        echo "[Pengurus] Akun Pengurus Ronal sudah ada (User ID: {$existingRonal['id']}, Username: {$existingRonal['username']}). Tidak membuat akun duplikat.\n";
        // Pastikan role dan nama sesuai
        if ($existingRonal['role'] !== 'pengurus') {
            $db->query("UPDATE users SET role = 'pengurus' WHERE id = :id");
            $db->bind('id', $existingRonal['id']);
            $db->execute();
            echo "[Pengurus] Role akun Ronal dipastikan sebagai 'pengurus'.\n";
        }
        if (!$existingRonal['pengurus_id']) {
            $db->query("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (:uid, 'Ronal', 'Pengurus Poskestren', '081234567892')");
            $db->bind('uid', $existingRonal['id']);
            $db->execute();
            echo "[Pengurus] Profil Pengurus Ronal dibuat.\n";
        } elseif ($existingRonal['nama'] !== 'Ronal') {
            $db->query("UPDATE pengurus SET nama = 'Ronal' WHERE id = :pid");
            $db->bind('pid', $existingRonal['pengurus_id']);
            $db->execute();
            echo "[Pengurus] Nama profil Pengurus diperbarui menjadi Ronal.\n";
        }

        // Sinkronisasi password dari konfigurasi lokal yang aman jika tersedia
        $ronalPass = $_ENV['RONAL_PASSWORD'] ?? (getenv('RONAL_PASSWORD') ?: '');
        if (!empty($ronalPass)) {
            $db->query("SELECT password FROM users WHERE id = :id");
            $db->bind('id', $existingRonal['id']);
            $currentHash = $db->single()['password'];
            if (!password_verify($ronalPass, $currentHash)) {
                $newHash = password_hash($ronalPass, PASSWORD_BCRYPT);
                $db->query("UPDATE users SET password = :p WHERE id = :id");
                $db->bind('p', $newHash);
                $db->bind('id', $existingRonal['id']);
                $db->execute();
                echo "[Pengurus] Password akun Ronal disinkronkan dengan konfigurasi lokal.\n";
            }
        }
    } else {
        // Ambil password dari environment lokal yang aman
        $ronalPass = getenv('RONAL_PASSWORD');
        if (empty($ronalPass)) {
            $ronalPass = bin2hex(random_bytes(6));
        }
        $hashedRonal = password_hash($ronalPass, PASSWORD_BCRYPT);

        $db->query("INSERT INTO users (username, password, role, status) VALUES ('ronal', :pass, 'pengurus', 'active')");
        $db->bind('pass', $hashedRonal);
        $db->execute();

        $db->query("SELECT LAST_INSERT_ID() as id");
        $ronalUserId = $db->single()['id'];

        $db->query("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (:uid, 'Ronal', 'Pengurus Poskestren', '081234567892')");
        $db->bind('uid', $ronalUserId);
        $db->execute();

        echo "[Pengurus] Akun Pengurus Ronal berhasil dibuat (User ID: {$ronalUserId}, Username: ronal, Role: pengurus).\n";
    }

    echo "=== SETUP SELESAI DENGAN SUKSES ===\n";

} catch (Exception $e) {
    echo "Terjadi kesalahan saat setup: " . $e->getMessage() . "\n";
    exit(1);
}
