<?php

class Santri_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllSantri($limit, $offset, $keyword = '', $kelas = '', $kamar = '') {
        $query = "SELECT s.*, u.status 
                  FROM santri s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE u.status = 'active'";
        
        $binds = [];
        if (!empty($keyword)) {
            $query .= " AND (s.nama LIKE :keyword1 OR s.nis LIKE :keyword2)";
            $binds['keyword1'] = "%$keyword%";
            $binds['keyword2'] = "%$keyword%";
        }
        if (!empty($kelas)) {
            $query .= " AND s.kelas = :kelas";
            $binds['kelas'] = $kelas;
        }
        if (!empty($kamar)) {
            $query .= " AND s.kamar = :kamar";
            $binds['kamar'] = $kamar;
        }

        $query .= " ORDER BY s.nama ASC LIMIT :limit OFFSET :offset";
        
        $this->db->query($query);
        
        foreach ($binds as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind('limit', $limit, PDO::PARAM_INT);
        $this->db->bind('offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    public function countAllSantri($keyword = '', $kelas = '', $kamar = '') {
        $query = "SELECT COUNT(s.id) as total 
                  FROM santri s 
                  JOIN users u ON s.user_id = u.id 
                  WHERE u.status = 'active'";
        
        $binds = [];
        if (!empty($keyword)) {
            $query .= " AND (s.nama LIKE :keyword1 OR s.nis LIKE :keyword2)";
            $binds['keyword1'] = "%$keyword%";
            $binds['keyword2'] = "%$keyword%";
        }
        if (!empty($kelas)) {
            $query .= " AND s.kelas = :kelas";
            $binds['kelas'] = $kelas;
        }
        if (!empty($kamar)) {
            $query .= " AND s.kamar = :kamar";
            $binds['kamar'] = $kamar;
        }

        $this->db->query($query);
        
        foreach ($binds as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->single()['total'];
    }

    public function getSantriById($id) {
        $this->db->query("SELECT s.*, u.username, u.status 
                          FROM santri s 
                          JOIN users u ON s.user_id = u.id 
                          WHERE s.id = :id");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function insertSantri($data) {
        try {
            $this->db->query("SET autocommit = 0;");
            $this->db->execute();
            $this->db->query("START TRANSACTION;");
            $this->db->execute();

            // Create User account first
            $this->db->query("INSERT INTO users (username, password, role, status) VALUES (:username, :password, 'santri', 'active')");
            $this->db->bind('username', $data['nis']); // Use NIS as username by default
            $this->db->bind('password', password_hash($data['nis'], PASSWORD_BCRYPT)); // Default password is NIS
            $this->db->execute();

            $this->db->query("SELECT LAST_INSERT_ID() as id");
            $user_id = $this->db->single()['id'];

            // Insert Santri data
            $query = "INSERT INTO santri 
                      (user_id, nis, nama, jenis_kelamin, tanggal_lahir, kelas, kamar, no_hp, golongan_darah, alergi, riwayat_penyakit) 
                      VALUES 
                      (:user_id, :nis, :nama, :jenis_kelamin, :tanggal_lahir, :kelas, :kamar, :no_hp, :golongan_darah, :alergi, :riwayat_penyakit)";
            
            $this->db->query($query);
            $this->db->bind('user_id', $user_id);
            $this->db->bind('nis', $data['nis']);
            $this->db->bind('nama', $data['nama']);
            $this->db->bind('jenis_kelamin', $data['jenis_kelamin']);
            $this->db->bind('tanggal_lahir', $data['tanggal_lahir']);
            $this->db->bind('kelas', $data['kelas']);
            $this->db->bind('kamar', $data['kamar']);
            $this->db->bind('no_hp', $data['no_hp']);
            $this->db->bind('golongan_darah', $data['golongan_darah']);
            $this->db->bind('alergi', $data['alergi']);
            $this->db->bind('riwayat_penyakit', $data['riwayat_penyakit']);
            $this->db->execute();

            $this->db->query("COMMIT;");
            $this->db->execute();
            return true;
        } catch (Exception $e) {
            $this->db->query("ROLLBACK;");
            $this->db->execute();
            return false;
        }
    }

    public function updateSantri($data, $id) {
        $query = "UPDATE santri SET 
                  nama = :nama,
                  jenis_kelamin = :jenis_kelamin,
                  tanggal_lahir = :tanggal_lahir,
                  kelas = :kelas,
                  kamar = :kamar,
                  no_hp = :no_hp,
                  golongan_darah = :golongan_darah,
                  alergi = :alergi,
                  riwayat_penyakit = :riwayat_penyakit
                  WHERE id = :id";
        
        $this->db->query($query);
        $this->db->bind('nama', $data['nama']);
        $this->db->bind('jenis_kelamin', $data['jenis_kelamin']);
        $this->db->bind('tanggal_lahir', $data['tanggal_lahir']);
        $this->db->bind('kelas', $data['kelas']);
        $this->db->bind('kamar', $data['kamar']);
        $this->db->bind('no_hp', $data['no_hp']);
        $this->db->bind('golongan_darah', $data['golongan_darah']);
        $this->db->bind('alergi', $data['alergi']);
        $this->db->bind('riwayat_penyakit', $data['riwayat_penyakit']);
        $this->db->bind('id', $id);
        
        return $this->db->execute();
    }

    public function deactivateSantri($id) {
        // Get user_id of santri
        $santri = $this->getSantriById($id);
        if ($santri) {
            $this->db->query("UPDATE users SET status = 'inactive' WHERE id = :user_id");
            $this->db->bind('user_id', $santri['user_id']);
            return $this->db->execute();
        }
        return false;
    }

    public function checkNisExists($nis, $exclude_id = null) {
        if ($exclude_id) {
            $this->db->query("SELECT id FROM santri WHERE nis = :nis AND id != :id");
            $this->db->bind('id', $exclude_id);
        } else {
            $this->db->query("SELECT id FROM santri WHERE nis = :nis");
        }
        $this->db->bind('nis', $nis);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }
}
