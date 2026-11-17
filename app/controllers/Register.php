<?php
class Register extends Controller {
    public function index() {
        AuthHelper::requireGuest();

        $data['title'] = 'Register Santri';
        $data['csrf_token'] = AuthHelper::generateCSRF();
        
        $this->view('templates/header', $data);
        $this->view('register/index', $data);
        $this->view('templates/footer');
    }

    public function store() {
        AuthHelper::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
                SessionHelper::setFlash('pesan', 'Validasi form gagal.', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
                exit;
            }

            $userModel = $this->model('User_model');

            // Sanitize & Validate
            $nis = htmlspecialchars(trim($_POST['nis']));
            $nama = htmlspecialchars(trim($_POST['nama']));
            $username = htmlspecialchars(trim($_POST['username']));
            $password = $_POST['password'];
            $password_confirm = $_POST['password_confirm'];
            
            $jenis_kelamin = in_array($_POST['jenis_kelamin'], ['L', 'P']) ? $_POST['jenis_kelamin'] : 'L';
            $tanggal_lahir = htmlspecialchars(trim($_POST['tanggal_lahir']));
            $kelas = htmlspecialchars(trim($_POST['kelas']));
            $kamar = htmlspecialchars(trim($_POST['kamar']));
            $no_hp = htmlspecialchars(trim($_POST['no_hp']));

            // Validate Unique
            if ($userModel->checkUsernameExists($username)) {
                SessionHelper::setFlash('pesan', 'Email/Username sudah terdaftar!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
                exit;
            }

            if ($userModel->checkNisExists($nis)) {
                SessionHelper::setFlash('pesan', 'NIS sudah terdaftar!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
                exit;
            }

            // Validate Password – NIST SP 800-63B: minimum 8 characters
            if (strlen($password) < 8) {
                SessionHelper::setFlash('pesan', 'Password minimal 8 karakter!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
                exit;
            }


            if ($password !== $password_confirm) {
                SessionHelper::setFlash('pesan', 'Konfirmasi password tidak cocok!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
                exit;
            }

            $dataInsert = [
                'username' => $username,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'nis' => $nis,
                'nama' => $nama,
                'jenis_kelamin' => $jenis_kelamin,
                'tanggal_lahir' => $tanggal_lahir,
                'kelas' => $kelas,
                'kamar' => $kamar,
                'no_hp' => $no_hp
            ];

            if ($userModel->registerSantri($dataInsert)) {
                SessionHelper::setFlash('pesan', 'Pendaftaran berhasil! Silakan login.', 'alert alert-success');
                header('Location: ' . BASEURL . '/login');
            } else {
                SessionHelper::setFlash('pesan', 'Terjadi kesalahan sistem saat mendaftar.', 'alert alert-danger');
                header('Location: ' . BASEURL . '/register');
            }
            exit;
        }
    }
}
