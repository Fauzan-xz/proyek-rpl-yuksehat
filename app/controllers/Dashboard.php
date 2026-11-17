<?php
class Dashboard extends Controller {
    public function __construct() {
        AuthHelper::requireLogin();
    }

    public function index() {
        $user = AuthHelper::user();
        $model = $this->model('Dashboard_model');
        
        $data['role'] = $user['role'] ?? 'admin'; 
        $data['username'] = $user['nama'] ?? 'User';
        $data['title'] = 'Dashboard';
        $data['current_page'] = 'dashboard';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Dashboard' => '#'];
        
        if (in_array($data['role'], ['admin', 'pengurus', 'petugas_kesehatan'])) {
            $data['stats'] = $model->getDashboardStats();
            $data['recent_pemeriksaan'] = $model->getRecentPemeriksaan(5);
        } elseif ($data['role'] == 'santri') {
            $profil = $model->getSantriProfile($user['id']);
            if ($profil) {
                $data['profil'] = $profil;
                $data['stats'] = $model->getSantriStats($profil['id']);
                $data['recent_pemeriksaan'] = $model->getSantriRecentPemeriksaan($profil['id'], 3);
            } else {
                $data['profil'] = null;
                $data['stats'] = [
                    'total_pemeriksaan' => 0,
                    'pemeriksaan_hari_ini' => 0,
                    'rawat_inap' => 0,
                    'perlu_rujukan' => 0
                ];
                $data['recent_pemeriksaan'] = [];
            }
        } else {
            $data['stats'] = [
                'total_santri' => 0,
                'pemeriksaan_hari_ini' => 0,
                'rawat_inap' => 0,
                'perlu_rujukan' => 0,
                'total_pengurus' => 0,
                'total_pemeriksaan' => 0,
                'santri_sakit' => 0
            ];
            $data['recent_pemeriksaan'] = [];
        }
        
        $this->view('templates/dash_header', $data);
        $this->view('dashboard/index', $data);
        $this->view('templates/dash_footer');
    }
}
