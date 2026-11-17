<?php

class Kesehatan extends Controller {
    public function __construct() {
        AuthHelper::requireLogin();
    }

    public function index() {
        $data['title'] = 'Kesehatan';
        $this->view('templates/header', $data);
        echo '<div class="container mt-5"><h1>Kesehatan</h1><p>Ini adalah halaman Kesehatan.</p></div>';
        $this->view('templates/footer');
    }
}

