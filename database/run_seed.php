<?php
/**
 * Script untuk inisialisasi database dan seeding data dummy.
 * Script ini bisa dijalankan dari CLI: `php database/run_seed.php`
 */

require_once __DIR__ . '/../app/init.php'; // Memuat .env via init.php config

try {
    // 1. Buat koneksi ke MySQL server (tanpa spesifik database) untuk memastikan DB ada
    $dsn_no_db = "mysql:host=" . DB_HOST;
    $pdo_server = new PDO($dsn_no_db, DB_USER, DB_PASS);
    $pdo_server->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 2. Buat database jika belum ada
    $db_name = DB_NAME;
    $pdo_server->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$db_name' created or already exists.\n";
    
    // 3. Konek ke database spesifik
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 4. Eksekusi file schema.sql
    $schema_file = __DIR__ . '/schema.sql';
    if (file_exists($schema_file)) {
        $sql = file_get_contents($schema_file);
        $pdo->exec($sql);
        echo "Schema imported successfully.\n";
    } else {
        die("schema.sql not found!\n");
    }

    // 5. Seeding Data
    $pdo->beginTransaction();
    
    $password_admin = password_hash('admin123', PASSWORD_BCRYPT);
    $password_pengurus = password_hash('pengurus123', PASSWORD_BCRYPT);
    $password_santri = password_hash('santri123', PASSWORD_BCRYPT);

    // Insert Admin
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $stmt->execute(['admin', $password_admin]);
    $admin_id = $pdo->lastInsertId();

    // Insert Pengurus
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'pengurus')");
    $stmt->execute(['pengurus1', $password_pengurus]);
    $pengurus_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (?, ?, ?, ?)");
    $stmt->execute([$pengurus_id, 'Ahmad Pengurus', 'Kepala Kesehatan', '081234567890']);

    // Insert Dummy Santri
    $santri_data = [
        ['nis' => '1001', 'nama' => 'Budi Santoso', 'jk' => 'L', 'kelas' => '10A', 'kamar' => 'A1'],
        ['nis' => '1002', 'nama' => 'Siti Aminah', 'jk' => 'P', 'kelas' => '11B', 'kamar' => 'B2'],
        ['nis' => '1003', 'nama' => 'Andi Wijaya', 'jk' => 'L', 'kelas' => '12C', 'kamar' => 'C3']
    ];

    foreach ($santri_data as $s) {
        // Create user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'santri')");
        $stmt->execute([$s['nis'], $password_santri]);
        $user_id = $pdo->lastInsertId();

        // Create santri profile
        $stmt = $pdo->prepare("INSERT INTO santri (user_id, nis, nama, jenis_kelamin, kelas, kamar) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $s['nis'], $s['nama'], $s['jk'], $s['kelas'], $s['kamar']]);
    }
    
    $pdo->commit();
    echo "Seed data inserted successfully.\n";

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
