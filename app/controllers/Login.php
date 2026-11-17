<?php
class Login extends Controller {
    public function index() {
        AuthHelper::requireGuest(); // Jika sudah login, lempar ke dashboard

        $data['title'] = 'Login';
        $data['csrf_token'] = AuthHelper::generateCSRF();
        
        $this->view('templates/header', $data);
        $this->view('login/index', $data);
        $this->view('templates/footer');
    }

    public function auth() {
        AuthHelper::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // CSRF Validation
            if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
                SessionHelper::setFlash('pesan', 'Validasi form gagal. Silakan coba lagi.', 'alert alert-danger');
                header('Location: ' . BASEURL . '/login');
                exit;
            }

            $username = htmlspecialchars($_POST['username']);
            $password = $_POST['password'];

            $userModel = $this->model('User_model');
            $user = $userModel->getUserByUsername($username);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Session Fixation Protection
                    session_regenerate_id(true);
                    // SECURITY FIX VULN-04: Rotate CSRF token after new session is issued
                    AuthHelper::rotateCSRF();
                    
                    // Set Session Data
                    $_SESSION['user'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'role' => $user['role']
                    ];
                    
                    // Load specific profile name if possible
                    if ($user['role'] == 'santri') {
                        $profile = $userModel->getSantriByUserId($user['id']);
                        $_SESSION['user']['nama'] = $profile ? $profile['nama'] : $user['username'];
                    } elseif ($user['role'] == 'pengurus') {
                        $profile = $userModel->getPengurusByUserId($user['id']);
                        $_SESSION['user']['nama'] = $profile ? $profile['nama'] : $user['username'];
                    } elseif ($user['role'] == 'admin') {
                        $profile = $userModel->getPengurusByUserId($user['id']);
                        $_SESSION['user']['nama'] = ($profile && !empty($profile['nama'])) ? $profile['nama'] : 'Fauzan';
                    } else {
                        $_SESSION['user']['nama'] = $user['username'];
                    }

                    header('Location: ' . BASEURL . '/dashboard');
                    exit;
                } else {
                    SessionHelper::setFlash('pesan', 'Password salah!', 'alert alert-danger');
                    header('Location: ' . BASEURL . '/login');
                    exit;
                }
            } else {
                SessionHelper::setFlash('pesan', 'Username tidak ditemukan atau akun tidak aktif!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/login');
                exit;
            }
        }
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: ' . BASEURL . '/login');
        exit;
    }
}
