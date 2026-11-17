<?php
class Dashboard_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Overall dashboard statistics for Admin, Pengurus, & Petugas Kesehatan
     */
    public function getDashboardStats() {
        $this->db->query("
            SELECT 
                (SELECT COUNT(*) FROM santri s JOIN users u ON s.user_id = u.id WHERE u.status = 'active') as total_santri,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE DATE(tanggal_pemeriksaan) = CURDATE()) as pemeriksaan_hari_ini,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE YEARWEEK(tanggal_pemeriksaan,1) = YEARWEEK(CURDATE(),1)) as pemeriksaan_minggu_ini,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE LOWER(tindakan) LIKE '%rawat%' OR LOWER(tindakan) LIKE '%uks%' OR LOWER(tindakan) LIKE '%inap%' OR LOWER(catatan) LIKE '%rawat%' OR LOWER(catatan) LIKE '%uks%') as rawat_inap,
                (SELECT COUNT(DISTINCT santri_id) FROM pemeriksaan_kesehatan WHERE LOWER(tindakan) LIKE '%rujuk%' OR LOWER(catatan) LIKE '%rujuk%' OR LOWER(hasil_pemeriksaan) LIKE '%rujuk%') as perlu_rujukan,
                (SELECT COUNT(*) FROM pengurus) as total_pengurus,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan) as total_pemeriksaan,
                (SELECT COUNT(DISTINCT santri_id) FROM pemeriksaan_kesehatan WHERE keluhan IS NOT NULL AND keluhan != '') as santri_sakit
        ");
        
        $result = $this->db->single();
        
        return [
            'total_santri' => (int)($result['total_santri'] ?? 0),
            'pemeriksaan_hari_ini' => (int)($result['pemeriksaan_hari_ini'] ?? 0),
            'pemeriksaan_minggu_ini' => (int)($result['pemeriksaan_minggu_ini'] ?? 0),
            'rawat_inap' => (int)($result['rawat_inap'] ?? 0),
            'perlu_rujukan' => (int)($result['perlu_rujukan'] ?? 0),
            'total_pengurus' => (int)($result['total_pengurus'] ?? 0),
            'total_pemeriksaan' => (int)($result['total_pemeriksaan'] ?? 0),
            'santri_sakit' => (int)($result['santri_sakit'] ?? 0)
        ];
    }

    public function getAdminStats() {
        return $this->getDashboardStats();
    }

    public function getPengurusStats() {
        return $this->getDashboardStats();
    }

    public function getRecentPemeriksaan($limit = 5) {
        $this->db->query("
            SELECT p.*, s.nama as nama_santri, s.kamar, s.kelas
            FROM pemeriksaan_kesehatan p
            JOIN santri s ON p.santri_id = s.id
            ORDER BY p.tanggal_pemeriksaan DESC
            LIMIT :limit
        ");
        $this->db->bind('limit', $limit, PDO::PARAM_INT);
        $result = $this->db->resultSet();
        return $result ? $result : [];
    }

    public function getAdminRecentPemeriksaan($limit = 5) {
        return $this->getRecentPemeriksaan($limit);
    }

    public function getPengurusRecentPemeriksaan($limit = 5) {
        return $this->getRecentPemeriksaan($limit);
    }

    public function getSantriProfile($user_id) {
        $this->db->query("
            SELECT s.* 
            FROM santri s 
            WHERE s.user_id = :user_id
        ");
        $this->db->bind('user_id', $user_id);
        $result = $this->db->single();
        return $result ? $result : null;
    }

    public function getSantriStats($santri_id) {
        $this->db->query("
            SELECT 
                COUNT(*) as total_pemeriksaan,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE santri_id = :s1 AND DATE(tanggal_pemeriksaan) = CURDATE()) as pemeriksaan_hari_ini,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE santri_id = :s2 AND (LOWER(tindakan) LIKE '%rawat%' OR LOWER(tindakan) LIKE '%uks%' OR LOWER(tindakan) LIKE '%inap%' OR LOWER(catatan) LIKE '%rawat%' OR LOWER(catatan) LIKE '%uks%')) as rawat_inap,
                (SELECT COUNT(*) FROM pemeriksaan_kesehatan WHERE santri_id = :s3 AND (LOWER(tindakan) LIKE '%rujuk%' OR LOWER(catatan) LIKE '%rujuk%' OR LOWER(hasil_pemeriksaan) LIKE '%rujuk%')) as perlu_rujukan
            FROM pemeriksaan_kesehatan 
            WHERE santri_id = :santri_id
        ");
        $this->db->bind('santri_id', $santri_id);
        $this->db->bind('s1', $santri_id);
        $this->db->bind('s2', $santri_id);
        $this->db->bind('s3', $santri_id);
        $result = $this->db->single();
        
        return [
            'total_pemeriksaan' => (int)($result['total_pemeriksaan'] ?? 0),
            'pemeriksaan_hari_ini' => (int)($result['pemeriksaan_hari_ini'] ?? 0),
            'pemeriksaan_minggu_ini' => (int)($result['pemeriksaan_minggu_ini'] ?? 0),
            'rawat_inap' => (int)($result['rawat_inap'] ?? 0),
            'perlu_rujukan' => (int)($result['perlu_rujukan'] ?? 0)
        ];
    }

    public function getSantriRecentPemeriksaan($santri_id, $limit = 3) {
        $this->db->query("
            SELECT p.*, pg.nama as nama_pemeriksa 
            FROM pemeriksaan_kesehatan p
            LEFT JOIN pengurus pg ON p.pemeriksa_id = pg.user_id
            WHERE p.santri_id = :santri_id
            ORDER BY p.tanggal_pemeriksaan DESC
            LIMIT :limit
        ");
        $this->db->bind('santri_id', $santri_id);
        $this->db->bind('limit', $limit, PDO::PARAM_INT);
        $result = $this->db->resultSet();
        return $result ? $result : [];
    }
}
