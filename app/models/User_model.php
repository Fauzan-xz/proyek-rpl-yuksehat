<?php

class User_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getUserByUsername($username) {
        $this->db->query("SELECT u.* FROM users u 
                          LEFT JOIN santri s ON u.id = s.user_id 
                          LEFT JOIN pengurus p ON u.id = p.user_id 
                          WHERE (u.username = :username OR s.nis = :nis OR (LOWER(p.nama) = LOWER(:p_nama) AND u.role IN ('admin', 'pengurus'))) 
                          AND u.status = 'active' LIMIT 1");
        $this->db->bind('username', $username);
        $this->db->bind('nis', $username);
        $this->db->bind('p_nama', $username);
        return $this->db->single();
    }

    public function checkUsernameExists($username) {
        $this->db->query("SELECT id FROM users WHERE username = :username");
        $this->db->bind('username', $username);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }


    public function checkNisExists($nis) {
        $this->db->query("SELECT id FROM santri WHERE nis = :nis");
        $this->db->bind('nis', $nis);
        $this->db->execute();
        return $this->db->rowCount() > 0;
    }

    public function getSantriByUserId($user_id) {
        $this->db->query("SELECT * FROM santri WHERE user_id = :user_id");
        $this->db->bind('user_id', $user_id);
        return $this->db->single();
    }

    public function getPengurusByUserId($user_id) {
        $this->db->query("SELECT * FROM pengurus WHERE user_id = :user_id");
        $this->db->bind('user_id', $user_id);
        return $this->db->single();
    }

    public function registerSantri($data) {
        try {
            // Begin Transaction
            $this->db->query("SET autocommit = 0;");
            $this->db->execute();
            $this->db->query("START TRANSACTION;");
            $this->db->execute();

            // Insert User
            $this->db->query("INSERT INTO users (username, password, role) VALUES (:username, :password, 'santri')");
            $this->db->bind('username', $data['username']);
            $this->db->bind('password', $data['password']);
            $this->db->execute();

            // Get Last Insert ID using a direct PDO call if possible, or another query
            $this->db->query("SELECT LAST_INSERT_ID() as id");
            $user_id = $this->db->single()['id'];

            // Insert Santri
            $this->db->query("INSERT INTO santri (user_id, nis, nama, jenis_kelamin, tanggal_lahir, kelas, kamar, no_hp) VALUES (:user_id, :nis, :nama, :jenis_kelamin, :tanggal_lahir, :kelas, :kamar, :no_hp)");
            $this->db->bind('user_id', $user_id);
            $this->db->bind('nis', $data['nis']);
            $this->db->bind('nama', $data['nama']);
            $this->db->bind('jenis_kelamin', $data['jenis_kelamin']);
            $this->db->bind('tanggal_lahir', $data['tanggal_lahir']);
            $this->db->bind('kelas', $data['kelas']);
            $this->db->bind('kamar', $data['kamar']);
            $this->db->bind('no_hp', $data['no_hp']);
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
}
