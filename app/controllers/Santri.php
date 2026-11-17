<?php
class Santri extends Controller {
    public function __construct() {
        AuthHelper::requireRole(['admin', 'pengurus']);
    }

    public function index() {
        $user = AuthHelper::user();
        
        // Setup pagination & filter
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $keyword = isset($_GET['search']) ? trim($_GET['search']) : '';
        $kelas = isset($_GET['kelas']) ? trim($_GET['kelas']) : '';
        $kamar = isset($_GET['kamar']) ? trim($_GET['kamar']) : '';

        $santriModel = $this->model('Santri_model');
        $total_data = $santriModel->countAllSantri($keyword, $kelas, $kamar);
        $total_pages = ceil($total_data / $limit);

        $data['santri'] = $santriModel->getAllSantri($limit, $offset, $keyword, $kelas, $kamar);
        $data['page'] = $page;
        $data['total_pages'] = $total_pages;
        $data['keyword'] = $keyword;
        $data['filter_kelas'] = $kelas;
        $data['filter_kamar'] = $kamar;
        
        $data['role'] = $user['role']; 
        $data['username'] = $user['nama'];
        $data['title'] = 'Data Santri';
        $data['current_page'] = 'santri';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Data Santri' => '#'];
        
        $this->view('templates/dash_header', $data);
        $this->view('santri/index', $data);
        $this->view('templates/dash_footer');
    }

    public function detail($id = null) {
        if (!$id) {
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        $santriModel = $this->model('Santri_model');
        $santri = $santriModel->getSantriById($id);

        if (!$santri) {
            SessionHelper::setFlash('pesan', 'Data santri tidak ditemukan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        $user = AuthHelper::user();
        $data['santri'] = $santri;
        $data['role'] = $user['role']; 
        $data['username'] = $user['nama'];
        $data['title'] = 'Detail Santri';
        $data['current_page'] = 'santri';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Data Santri' => BASEURL.'/santri', 'Detail' => '#'];
        
        $this->view('templates/dash_header', $data);
        $this->view('santri/detail', $data);
        $this->view('templates/dash_footer');
    }

    public function create() {
        $user = AuthHelper::user();
        
        $data['csrf_token'] = AuthHelper::generateCSRF();
        $data['role'] = $user['role']; 
        $data['username'] = $user['nama'];
        $data['title'] = 'Tambah Santri';
        $data['current_page'] = 'santri';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Data Santri' => BASEURL.'/santri', 'Tambah' => '#'];
        
        $this->view('templates/dash_header', $data);
        $this->view('santri/create', $data);
        $this->view('templates/dash_footer');
    }

    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
                SessionHelper::setFlash('pesan', 'CSRF Token Invalid.', 'alert alert-danger');
                header('Location: ' . BASEURL . '/santri/create');
                exit;
            }

            $santriModel = $this->model('Santri_model');

            // Sanitize input
            $dataInsert = [
                'nis' => htmlspecialchars(trim($_POST['nis'])),
                'nama' => htmlspecialchars(trim($_POST['nama'])),
                'jenis_kelamin' => in_array($_POST['jenis_kelamin'], ['L', 'P']) ? $_POST['jenis_kelamin'] : 'L',
                'tanggal_lahir' => htmlspecialchars(trim($_POST['tanggal_lahir'])),
                'kelas' => htmlspecialchars(trim($_POST['kelas'])),
                'kamar' => htmlspecialchars(trim($_POST['kamar'])),
                'no_hp' => htmlspecialchars(trim($_POST['no_hp'])),
                'golongan_darah' => htmlspecialchars(trim($_POST['golongan_darah'])),
                'alergi' => htmlspecialchars(trim($_POST['alergi'])),
                'riwayat_penyakit' => htmlspecialchars(trim($_POST['riwayat_penyakit']))
            ];

            if ($santriModel->checkNisExists($dataInsert['nis'])) {
                SessionHelper::setFlash('pesan', 'NIS sudah terdaftar!', 'alert alert-danger');
                header('Location: ' . BASEURL . '/santri/create');
                exit;
            }

            if ($santriModel->insertSantri($dataInsert)) {
                SessionHelper::setFlash('pesan', 'Data santri berhasil ditambahkan.', 'alert alert-success');
                header('Location: ' . BASEURL . '/santri');
            } else {
                SessionHelper::setFlash('pesan', 'Gagal menambahkan data santri.', 'alert alert-danger');
                header('Location: ' . BASEURL . '/santri/create');
            }
            exit;
        }
    }

    public function edit($id = null) {
        if (!$id) {
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        $santriModel = $this->model('Santri_model');
        $santri = $santriModel->getSantriById($id);

        if (!$santri) {
            SessionHelper::setFlash('pesan', 'Data santri tidak ditemukan.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        $user = AuthHelper::user();
        $data['santri'] = $santri;
        $data['csrf_token'] = AuthHelper::generateCSRF();
        $data['role'] = $user['role']; 
        $data['username'] = $user['nama'];
        $data['title'] = 'Edit Santri';
        $data['current_page'] = 'santri';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Data Santri' => BASEURL.'/santri', 'Edit' => '#'];
        
        $this->view('templates/dash_header', $data);
        $this->view('santri/edit', $data);
        $this->view('templates/dash_footer');
    }

    public function update($id = null) {
        if (!$id || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'CSRF Token Invalid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/santri/edit/' . $id);
            exit;
        }

        $santriModel = $this->model('Santri_model');

        $dataUpdate = [
            'nis' => htmlspecialchars(trim($_POST['nis'])),
            'nama' => htmlspecialchars(trim($_POST['nama'])),
            'jenis_kelamin' => in_array($_POST['jenis_kelamin'], ['L', 'P']) ? $_POST['jenis_kelamin'] : 'L',
            'tanggal_lahir' => htmlspecialchars(trim($_POST['tanggal_lahir'])),
            'kelas' => htmlspecialchars(trim($_POST['kelas'])),
            'kamar' => htmlspecialchars(trim($_POST['kamar'])),
            'no_hp' => htmlspecialchars(trim($_POST['no_hp'])),
            'golongan_darah' => htmlspecialchars(trim($_POST['golongan_darah'])),
            'alergi' => htmlspecialchars(trim($_POST['alergi'])),
            'riwayat_penyakit' => htmlspecialchars(trim($_POST['riwayat_penyakit']))
        ];

        if ($santriModel->updateSantri($dataUpdate, $id)) {
            SessionHelper::setFlash('pesan', 'Data santri berhasil diperbarui.', 'alert alert-success');
            header('Location: ' . BASEURL . '/santri');
        } else {
            SessionHelper::setFlash('pesan', 'Gagal memperbarui data.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/santri/edit/' . $id);
        }
        exit;
    }

    public function delete($id = null) {
        if (!$id || $_SERVER['REQUEST_METHOD'] != 'POST') {
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        if (!isset($_POST['csrf_token']) || !AuthHelper::validateCSRF($_POST['csrf_token'])) {
            SessionHelper::setFlash('pesan', 'CSRF Token Invalid.', 'alert alert-danger');
            header('Location: ' . BASEURL . '/santri');
            exit;
        }

        $santriModel = $this->model('Santri_model');
        if ($santriModel->deactivateSantri($id)) {
            SessionHelper::setFlash('pesan', 'Santri berhasil dinonaktifkan.', 'alert alert-success');
        } else {
            SessionHelper::setFlash('pesan', 'Gagal menonaktifkan santri.', 'alert alert-danger');
        }
        header('Location: ' . BASEURL . '/santri');
        exit;
    }
}
