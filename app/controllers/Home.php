<?php
class Home extends Controller {
    public function index() {
        // Jika sudah login, langsung ke dashboard
        if (AuthHelper::isLoggedIn()) {
            header('Location: ' . BASEURL . '/dashboard');
            exit;
        }
        // Jika belum login, tampilkan landing page dengan link ke /login
        $data['title'] = 'Selamat Datang - Yuk Sehat';
        $this->view('templates/header', $data);
        $this->view('home/index', $data);
        $this->view('templates/footer');
    }
}
