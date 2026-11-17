<?php
/**
 * Controller Antrean Keluhan & Pengajuan Keluhan Santri
 * Dilengkapi Sistem Penugasan dan Notifikasi Khusus Pengurus
 * YukSehat - Native PHP MVC
 */
class Keluhan extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
    }

    public function index() {
        $user = AuthHelper::user();
        $db = new Database();

        if ($user['role'] === 'santri') {
            // Tampilan untuk Santri: Kirim keluhan & lihat riwayat status keluhan pribadi
            $db->query("SELECT id FROM santri WHERE user_id = :uid LIMIT 1");
            $db->bind('uid', $user['id']);
            $profil = $db->single();

            $santriId = $profil ? (int)$profil['id'] : 0;
            $keluhanList = [];

            if ($santriId > 0) {
                $db->query("SELECT rk.*, p.nama AS nama_pengurus 
                            FROM riwayat_keluhan rk 
                            LEFT JOIN users u ON rk.assigned_to = u.id 
                            LEFT JOIN pengurus p ON u.id = p.user_id 
                            WHERE rk.santri_id = :sid 
                            ORDER BY rk.tanggal_keluhan DESC");
                $db->bind('sid', $santriId);
                $keluhanList = $db->resultSet();
            }

            // Ambil daftar pengurus aktif agar santri dapat memilih pemeriksa
            $db->query("SELECT u.id AS user_id, p.nama, p.jabatan 
                        FROM users u 
                        JOIN pengurus p ON u.id = p.user_id 
                        WHERE u.role = 'pengurus' AND u.status = 'active' 
                        ORDER BY p.nama ASC");
            $pengurusList = $db->resultSet();

            $data = [
                'title'         => 'Keluhan Kesehatan Saya',
                'current_page'  => 'keluhan',
                'role'          => 'santri',
                'username'      => $user['nama'],
                'keluhan_list'  => $keluhanList,
                'pengurus_list' => $pengurusList,
                'santri_id'     => $santriId,
                'csrf_token'    => AuthHelper::generateCSRF()
            ];

            $this->view('templates/dash_header', $data);
            $this->view('keluhan/santri', $data);
            $this->view('templates/dash_footer');
        } else {
            // Tampilan untuk Admin & Pengurus: Kelola antrean keluhan santri
            AuthHelper::requireRole(['admin', 'pengurus']);

            // Hitung statistik keluhan per status untuk tombol filter cepat
            $db->query("SELECT status, COUNT(*) AS total FROM riwayat_keluhan GROUP BY status");
            $statusCounts = [
                'all' => 0,
                'menunggu' => 0,
                'diperiksa' => 0,
                'selesai' => 0
            ];
            foreach ($db->resultSet() as $row) {
                if (isset($statusCounts[$row['status']])) {
                    $statusCounts[$row['status']] = (int)$row['total'];
                }
                $statusCounts['all'] += (int)$row['total'];
            }

            // Tentukan status filter:
            // Jika ada parameter status di URL (termasuk kosong ''), gunakan nilai tersebut
            // Jika tidak ada di URL: jika ada yang 'menunggu' utamakan 'menunggu', jika 0 tampilkan semua ('')
            if (isset($_GET['status'])) {
                $statusFilter = trim($_GET['status']);
            } else {
                $statusFilter = ($statusCounts['menunggu'] > 0) ? 'menunggu' : '';
            }

            $search = trim($_GET['search'] ?? '');

            $sql = "SELECT rk.*, s.nama AS nama_santri, s.nis, s.kelas, s.kamar,
                           p.nama AS nama_petugas_assigned, u.username AS username_petugas_assigned
                    FROM riwayat_keluhan rk
                    JOIN santri s ON rk.santri_id = s.id
                    LEFT JOIN users u ON rk.assigned_to = u.id
                    LEFT JOIN pengurus p ON u.id = p.user_id
                    WHERE 1=1";

            $binds = [];
            if (!empty($statusFilter) && in_array($statusFilter, ['menunggu', 'diperiksa', 'selesai'])) {
                $sql .= " AND rk.status = :status";
                $binds['status'] = $statusFilter;
            }

            if (!empty($search)) {
                $sql .= " AND (s.nama LIKE :q1 OR s.nis LIKE :q2 OR rk.keluhan LIKE :q3)";
                $binds['q1'] = "%$search%";
                $binds['q2'] = "%$search%";
                $binds['q3'] = "%$search%";
            }

            $sql .= " ORDER BY rk.tanggal_keluhan DESC";

            $db->query($sql);
            foreach ($binds as $key => $val) {
                $db->bind($key, $val);
            }
            $antrean = $db->resultSet();

            $countMenunggu = $statusCounts['menunggu'];

            // Daftar Pengurus Aktif untuk Dropdown Penugasan
            $db->query("SELECT u.id AS user_id, p.nama, p.jabatan 
                        FROM users u 
                        JOIN pengurus p ON u.id = p.user_id 
                        WHERE u.role = 'pengurus' AND u.status = 'active' 
                        ORDER BY p.nama ASC");
            $pengurusList = $db->resultSet();

            $data = [
                'title'          => 'Antrean Keluhan Santri',
                'current_page'   => 'keluhan',
                'role'           => $user['role'],
                'user_id'        => $user['id'],
                'username'       => $user['nama'],
                'antrean'        => $antrean,
                'status_filter'  => $statusFilter,
                'status_counts'  => $statusCounts,
                'search'         => $search,
                'count_menunggu' => $countMenunggu,
                'pengurus_list'  => $pengurusList,
                'csrf_token'     => AuthHelper::generateCSRF()
            ];

            $this->view('templates/dash_header', $data);
            $this->view('keluhan/admin', $data);
            $this->view('templates/dash_footer');
        }
    }

    public function kirim() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        AuthHelper::requireRole(['santri']);

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi formulir keamanan gagal. Silakan kirim ulang.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $user = AuthHelper::user();
        $db = new Database();

        $db->query("SELECT id, nama FROM santri WHERE user_id = :uid LIMIT 1");
        $db->bind('uid', $user['id']);
        $profil = $db->single();

        if (!$profil) {
            SessionHelper::setFlash('pesan', 'Profil santri Anda belum terdaftar di sistem. Hubungi pengurus.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $keluhan = trim($_POST['keluhan'] ?? '');
        $pemeriksaId = (int)($_POST['pemeriksa_id'] ?? 0);

        if (empty($keluhan)) {
            SessionHelper::setFlash('pesan', 'Keluhan kesehatan tidak boleh kosong.', 'alert alert-warning');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        // Validasi pengurus pemeriksa yang dipilih santri
        $selectedPengurus = null;
        if ($pemeriksaId > 0) {
            $db->query("SELECT u.id AS user_id, p.nama, p.no_hp 
                        FROM users u 
                        JOIN pengurus p ON u.id = p.user_id 
                        WHERE u.id = :uid AND u.role = 'pengurus' AND u.status = 'active' 
                        LIMIT 1");
            $db->bind('uid', $pemeriksaId);
            $selectedPengurus = $db->single();
        }

        if (!$selectedPengurus) {
            SessionHelper::setFlash('pesan', 'Silakan pilih petugas / pemeriksa Poskestren yang valid.', 'alert alert-warning');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        try {
            $db->query("INSERT INTO riwayat_keluhan (santri_id, keluhan, assigned_to, status, tanggal_keluhan) 
                        VALUES (:sid, :k, :assigned_to, 'menunggu', NOW())");
            $db->bind('sid', $profil['id']);
            $db->bind('k', htmlspecialchars($keluhan));
            $db->bind('assigned_to', $pemeriksaId);
            $db->execute();
            $keluhanId = (int)$db->lastInsertId();

            // Pemicu Notifikasi WhatsApp & In-App HANYA ke Pengurus yang Dipilih Santri
            $namaSantri = $profil['nama'] ?? 'Santri';
            $namaPemeriksa = $selectedPengurus['nama'] ?? 'Petugas';
            $notifModel = $this->model('Notifikasi_model');

            $waGenericMsg = "Halo {$namaPemeriksa} (Poskestren YukSehat),\n\nTerdapat keluhan santri baru atas nama {$namaSantri} yang memilih Anda sebagai pemeriksa di Poskestren YukSehat. Silakan login ke aplikasi untuk meninjau dan melakukan pemeriksaan.\n\nTerima kasih.";

            $notifModel->createNotification(
                (int)$pemeriksaId,
                'keluhan_baru',
                'Keluhan Santri Baru Masuk',
                "Santri atas nama " . htmlspecialchars($namaSantri) . " telah mengirimkan keluhan kesehatan dan memilih Anda sebagai pemeriksa.",
                '/keluhan',
                'keluhan',
                $keluhanId,
                $waGenericMsg
            );

            SessionHelper::setFlash('pesan', "Keluhan kesehatan Anda berhasil dikirim dan ditugaskan kepada {$namaPemeriksa}. Petugas telah diberi tahu.", 'alert alert-success');
        } catch (Exception $e) {
            SessionHelper::setFlash('pesan', 'Terjadi kendala saat mengirim keluhan: ' . $e->getMessage(), 'alert alert-danger');
        }

        header('Location: ' . BASEURL . '/keluhan');
        exit;
    }

    /**
     * Penugasan Pengurus untuk menangani keluhan santri
     * Mengirimkan notifikasi HANYA kepada pengurus yang ditugaskan secara eksplisit
     */
    public function assign() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        AuthHelper::requireRole(['admin', 'pengurus']);

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi formulir keamanan gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $keluhanId = (int)($_POST['keluhan_id'] ?? 0);
        $assignedTo = (int)($_POST['assigned_to'] ?? 0);

        if ($keluhanId <= 0) {
            SessionHelper::setFlash('pesan', 'ID keluhan tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $db = new Database();

        // 1. Ambil data keluhan saat ini
        $db->query("SELECT rk.*, s.nama AS nama_santri 
                    FROM riwayat_keluhan rk 
                    JOIN santri s ON rk.santri_id = s.id 
                    WHERE rk.id = :kid LIMIT 1");
        $db->bind('kid', $keluhanId);
        $keluhan = $db->single();

        if (!$keluhan) {
            SessionHelper::setFlash('pesan', 'Data keluhan tidak ditemukan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $oldAssignedTo = !empty($keluhan['assigned_to']) ? (int)$keluhan['assigned_to'] : null;

        // Opsi batalkan penugasan
        if ($assignedTo === 0) {
            $db->query("UPDATE riwayat_keluhan SET assigned_to = NULL WHERE id = :kid");
            $db->bind('kid', $keluhanId);
            $db->execute();

            SessionHelper::setFlash('pesan', 'Penugasan keluhan berhasil dibatalkan.', 'alert alert-info');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        // 2. Validasi target user: wajib ada di tabel users, role 'pengurus', status 'active'
        $db->query("SELECT u.id, u.username, u.role, u.status, p.nama AS nama_pengurus 
                    FROM users u 
                    JOIN pengurus p ON u.id = p.user_id 
                    WHERE u.id = :uid LIMIT 1");
        $db->bind('uid', $assignedTo);
        $targetUser = $db->single();

        if (!$targetUser || $targetUser['role'] !== 'pengurus' || $targetUser['status'] !== 'active') {
            SessionHelper::setFlash('pesan', 'Petugas pengurus yang dipilih tidak valid atau tidak aktif.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        // 3. Jika ditugaskan ke orang yang sama, tidak perlu notifikasi ulang
        if ($oldAssignedTo === $assignedTo) {
            SessionHelper::setFlash('pesan', 'Keluhan ini sudah ditugaskan kepada ' . htmlspecialchars($targetUser['nama_pengurus']) . '.', 'alert alert-info');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        // 4. Update data penugasan di database
        $db->query("UPDATE riwayat_keluhan SET assigned_to = :assigned_to WHERE id = :kid");
        $db->bind('assigned_to', $assignedTo);
        $db->bind('kid', $keluhanId);
        $db->execute();

        // 5. Pisahkan peristiwa penugasan baru vs pengalihan penugasan (Reassignment)
        $namaPengurus = $targetUser['nama_pengurus'] ?? 'Pengurus';
        $notifModel = $this->model('Notifikasi_model');

        if ($oldAssignedTo === null) {
            // Peristiwa: Penugasan Baru
            $jenis = 'penugasan_baru';
            $judul = 'Penugasan Keluhan Baru';
            $pesan = "Keluhan kesehatan santri di antrean telah ditugaskan kepada Anda untuk ditindaklanjuti.";
            $waMsg = "Halo Pengurus Poskestren YukSehat,\n\nTerdapat penugasan keluhan antrean santri baru di sistem YukSehat. Silakan login ke aplikasi untuk menindaklanjuti.\n\nTerima kasih.";
        } else {
            // Peristiwa: Pengalihan Tugas (Reassignment)
            // Catatan: Pengurus lama ($oldAssignedTo) tidak akan lagi menerima notifikasi peristiwa keluhan ini
            $jenis = 'pengalihan_tugas';
            $judul = 'Pengalihan Penugasan Keluhan';
            $pesan = "Penugasan keluhan kesehatan santri telah dialihkan kepada Anda untuk ditindaklanjuti.";
            $waMsg = "Halo Pengurus Poskestren YukSehat,\n\nTerdapat pengalihan penugasan keluhan santri kepada Anda di sistem YukSehat. Silakan login ke aplikasi untuk menindaklanjuti.\n\nTerima kasih.";
        }

        $linkUrl = '/keluhan';

        // Trigger notifikasi HANYA kepada pengurus yang ditugaskan secara eksplisit ($assignedTo)
        $notifModel->createNotification(
            $assignedTo,
            $jenis,
            $judul,
            $pesan,
            $linkUrl,
            'keluhan',
            $keluhanId,
            $waMsg
        );

        SessionHelper::setFlash('pesan', 'Keluhan santri berhasil ditugaskan kepada ' . htmlspecialchars($namaPengurus) . '.', 'alert alert-success');
        header('Location: ' . BASEURL . '/keluhan');
        exit;
    }

    public function update_status() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        AuthHelper::requireRole(['admin', 'pengurus']);

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi keamanan gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $id = (int)($_POST['keluhan_id'] ?? 0);
        $status = trim($_POST['status'] ?? '');

        if ($id <= 0 || !in_array($status, ['menunggu', 'diperiksa', 'selesai'])) {
            SessionHelper::setFlash('pesan', 'Data status keluhan tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        $db = new Database();

        // 1. Ambil data keluhan saat ini beserta data santri dan pengurus
        $db->query("SELECT rk.id, rk.santri_id, rk.assigned_to, rk.status,
                           s.user_id AS santri_user_id, s.nama AS nama_santri,
                           p.nama AS nama_pengurus
                    FROM riwayat_keluhan rk 
                    JOIN santri s ON rk.santri_id = s.id 
                    LEFT JOIN users u ON rk.assigned_to = u.id 
                    LEFT JOIN pengurus p ON u.id = p.user_id 
                    WHERE rk.id = :id LIMIT 1");
        $db->bind('id', $id);
        $keluhan = $db->single();

        if (!$keluhan) {
            SessionHelper::setFlash('pesan', 'Data keluhan tidak ditemukan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/keluhan');
            exit;
        }

        // 2. Perbarui status antrean
        $db->query("UPDATE riwayat_keluhan SET status = :s WHERE id = :id");
        $db->bind('s', $status);
        $db->bind('id', $id);
        $db->execute();

        // 3. Notifikasi perubahan status dikirimkan ke SANTRI (bukan ke pemeriksa yang menangani)
        $santriUserId = !empty($keluhan['santri_user_id']) ? (int)$keluhan['santri_user_id'] : 0;
        $namaSantri   = $keluhan['nama_santri'] ?? 'Santri';
        $namaPengurus = $keluhan['nama_pengurus'] ?? 'Petugas Poskestren';

        if ($santriUserId > 0) {
            $notifModel = $this->model('Notifikasi_model');
            $jenis = 'status_' . $status;

            if ($status === 'selesai') {
                $judul = 'Keluhan Selesai Ditangani';
                $pesan = "Keluhan kesehatan Anda telah selesai ditangani oleh {$namaPengurus}. Semoga lekas pulih!";
                $waMsg = "Halo {$namaSantri},\n\nKeluhan kesehatan Anda di Poskestren YukSehat telah selesai ditangani oleh petugas pemeriksa ({$namaPengurus}). Silakan beristirahat dan patuhi anjuran petugas. Semoga lekas sembuh.\n\nTerima kasih.";
            } elseif ($status === 'diperiksa') {
                $judul = 'Keluhan Sedang Diperiksa';
                $pesan = "Keluhan kesehatan Anda sedang ditindaklanjuti/diperiksa oleh {$namaPengurus}.";
                $waMsg = "Halo {$namaSantri},\n\nKeluhan kesehatan Anda sedang dalam proses pemeriksaan oleh petugas Poskestren ({$namaPengurus}). Silakan menuju Poskestren bila diminta.\n\nTerima kasih.";
            } else {
                $judul = 'Pembaruan Status Keluhan';
                $pesan = "Status keluhan kesehatan Anda telah diperbarui menjadi 'Menunggu'.";
                $waMsg = "Halo {$namaSantri},\n\nStatus keluhan kesehatan Anda di Poskestren YukSehat telah diperbarui. Silakan login ke aplikasi untuk melihat detailnya.\n\nTerima kasih.";
            }

            $notifModel->createNotification(
                $santriUserId,
                $jenis,
                $judul,
                $pesan,
                '/keluhan',
                'keluhan',
                $id,
                $waMsg
            );
        }

        SessionHelper::setFlash('pesan', 'Status antrean keluhan berhasil diperbarui menjadi ' . ucfirst($status) . '.', 'alert alert-success');
        header('Location: ' . BASEURL . '/keluhan?status=' . urlencode($status));
        exit;
    }
}
