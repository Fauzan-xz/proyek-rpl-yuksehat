<?php
/**
 * Controller Pengelolaan Akun & Role (Khusus Admin)
 */
class Users extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
        // Hanya Admin yang memiliki akses ke manajemen akun dan role
        AuthHelper::requireRole(['admin']);
    }

    public function index() {
        $currentUser = AuthHelper::user();
        $db = new Database();

        $search = trim($_GET['search'] ?? '');
        $roleFilter = trim($_GET['role'] ?? '');

        $sql = "SELECT u.id, u.username, u.role, u.status, u.created_at,
                       COALESCE(p.nama, s.nama, u.username) AS nama_lengkap,
                       p.jabatan,
                       p.no_hp,
                       s.nis, s.kelas, s.kamar
                FROM users u
                LEFT JOIN pengurus p ON u.id = p.user_id
                LEFT JOIN santri s ON u.id = s.user_id
                WHERE 1=1";

        $binds = [];
        if (!empty($search)) {
            $sql .= " AND (u.username LIKE :q1 OR p.nama LIKE :q2 OR s.nama LIKE :q3 OR s.nis LIKE :q4)";
            $binds['q1'] = "%$search%";
            $binds['q2'] = "%$search%";
            $binds['q3'] = "%$search%";
            $binds['q4'] = "%$search%";
        }

        if (!empty($roleFilter) && in_array($roleFilter, ['admin', 'pengurus', 'santri'])) {
            $sql .= " AND u.role = :role";
            $binds['role'] = $roleFilter;
        }

        $sql .= " ORDER BY u.id ASC";

        $db->query($sql);
        foreach ($binds as $key => $val) {
            $db->bind($key, $val);
        }
        $usersList = $db->resultSet();

        $data = [
            'title'        => 'Pengelolaan Pengguna & Role',
            'current_page' => 'users',
            'role'         => $currentUser['role'],
            'username'     => $currentUser['nama'],
            'users'        => $usersList,
            'search'       => $search,
            'role_filter'  => $roleFilter,
            'csrf_token'   => AuthHelper::generateCSRF()
        ];

        $this->view('templates/dash_header', $data);
        $this->view('users/index', $data);
        $this->view('templates/dash_footer');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi formulir keamanan gagal. Silakan coba lagi.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $role     = trim($_POST['role'] ?? 'pengurus');
        $jabatan  = trim($_POST['jabatan'] ?? '');
        $no_hp    = trim($_POST['no_hp'] ?? '');

        if (empty($username) || empty($password) || empty($nama)) {
            SessionHelper::setFlash('pesan', 'Username, password, dan nama wajib diisi.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        if (!in_array($role, ['admin', 'pengurus'])) {
            SessionHelper::setFlash('pesan', 'Role hanya dapat dibuat untuk Admin atau Pengurus melalui menu ini.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        $db = new Database();
        $db->query("SELECT id FROM users WHERE username = :u LIMIT 1");
        $db->bind('u', $username);
        if ($db->single()) {
            SessionHelper::setFlash('pesan', "Username '{$username}' sudah digunakan. Pilih username lain.", 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        try {
            $hashedPass = password_hash($password, PASSWORD_BCRYPT);
            $db->query("INSERT INTO users (username, password, role, status) VALUES (:u, :p, :r, 'active')");
            $db->bind('u', $username);
            $db->bind('p', $hashedPass);
            $db->bind('r', $role);
            $db->execute();

            $db->query("SELECT LAST_INSERT_ID() AS id");
            $newUserId = $db->single()['id'];

            $db->query("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (:uid, :n, :j, :hp)");
            $db->bind('uid', $newUserId);
            $db->bind('n', $nama);
            $db->bind('j', !empty($jabatan) ? $jabatan : ($role === 'admin' ? 'Administrator' : 'Pengurus Poskestren'));
            $db->bind('hp', $no_hp);
            $db->execute();

            SessionHelper::setFlash('pesan', "Akun {$role} '{$nama}' berhasil ditambahkan.", 'alert alert-success');
        } catch (Exception $e) {
            SessionHelper::setFlash('pesan', "Gagal membuat akun: " . $e->getMessage(), 'alert alert-danger');
        }

        header('Location: ' . BASEURL . '/users');
        exit;
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'Validasi form keamanan gagal.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        $userId   = (int)($_POST['user_id'] ?? 0);
        $newRole  = trim($_POST['role'] ?? '');
        $status   = trim($_POST['status'] ?? 'active');
        $nama     = trim($_POST['nama'] ?? '');
        $no_hp    = trim($_POST['no_hp'] ?? '');
        $currentUser = AuthHelper::user();

        if ($userId <= 0) {
            SessionHelper::setFlash('pesan', 'ID pengguna tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        // Proteksi lockout: Jangan izinkan admin menonaktifkan atau mengubah role akunnya sendiri
        if ($userId === (int)$currentUser['id']) {
            if ($status !== 'active' || $newRole !== 'admin') {
                SessionHelper::setFlash('pesan', 'Anda tidak dapat menurunkan role atau menonaktifkan akun Anda sendiri yang sedang aktif.', 'alert alert-warning');
                header('Location: ' . BASEURL . '/users');
                exit;
            }
        }

        if (!in_array($newRole, ['admin', 'pengurus', 'santri']) || !in_array($status, ['active', 'inactive'])) {
            SessionHelper::setFlash('pesan', 'Role atau status yang dipilih tidak valid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/users');
            exit;
        }

        $db = new Database();
        $db->query("UPDATE users SET role = :r, status = :s WHERE id = :id");
        $db->bind('r', $newRole);
        $db->bind('s', $status);
        $db->bind('id', $userId);
        $db->execute();

        // Update nama dan nomor HP jika user adalah pengurus atau admin
        $db->query("SELECT id FROM pengurus WHERE user_id = :uid LIMIT 1");
        $db->bind('uid', $userId);
        $pRow = $db->single();
        if ($pRow) {
            $db->query("UPDATE pengurus SET nama = :n, no_hp = :hp WHERE id = :pid");
            $db->bind('n', $nama);
            $db->bind('hp', $no_hp);
            $db->bind('pid', $pRow['id']);
            $db->execute();
        } elseif (in_array($newRole, ['pengurus', 'admin']) && !empty($nama)) {
            $db->query("INSERT INTO pengurus (user_id, nama, jabatan, no_hp) VALUES (:uid, :n, 'Pengurus Poskestren', :hp)");
            $db->bind('uid', $userId);
            $db->bind('n', $nama);
            $db->bind('hp', $no_hp);
            $db->execute();
        }

        SessionHelper::setFlash('pesan', 'Perubahan data akun dan nomor HP WhatsApp berhasil disimpan.', 'alert alert-success');
        header('Location: ' . BASEURL . '/users');
        exit;
    }
}
