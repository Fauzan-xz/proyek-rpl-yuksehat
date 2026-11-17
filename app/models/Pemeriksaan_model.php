<?php

class Pemeriksaan_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Ambil semua data pemeriksaan dengan filter (untuk admin/pengurus)
     */
    public function getAllPemeriksaan($limit, $offset, $keyword = '', $tanggal_dari = '', $tanggal_sampai = '', $filter = '') {
        $query = "SELECT p.*, 
                         s.nama AS nama_santri, s.nis, s.kelas, s.kamar,
                         u.nama AS nama_pemeriksa
                  FROM pemeriksaan_kesehatan p
                  JOIN santri s ON p.santri_id = s.id
                  LEFT JOIN (
                      SELECT u2.id AS uid, COALESCE(pg.nama, 'Admin') AS nama
                      FROM users u2
                      LEFT JOIN pengurus pg ON pg.user_id = u2.id
                  ) u ON p.pemeriksa_id = u.uid
                  WHERE 1=1";

        $binds = [];

        if (!empty($keyword)) {
            $query .= " AND (s.nama LIKE :keyword1 OR s.nis LIKE :keyword2 OR p.keluhan LIKE :keyword3 OR p.hasil_pemeriksaan LIKE :keyword4 OR p.tindakan LIKE :keyword5 OR p.catatan LIKE :keyword6)";
            $binds['keyword1'] = "%$keyword%";
            $binds['keyword2'] = "%$keyword%";
            $binds['keyword3'] = "%$keyword%";
            $binds['keyword4'] = "%$keyword%";
            $binds['keyword5'] = "%$keyword%";
            $binds['keyword6'] = "%$keyword%";
        }
        if (!empty($tanggal_dari)) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) >= :tanggal_dari";
            $binds['tanggal_dari'] = $tanggal_dari;
        }
        if (!empty($tanggal_sampai)) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) <= :tanggal_sampai";
            $binds['tanggal_sampai'] = $tanggal_sampai;
        }
        if ($filter === 'rujukan') {
            $query .= " AND (LOWER(p.tindakan) LIKE '%rujuk%' OR LOWER(p.catatan) LIKE '%rujuk%' OR LOWER(p.hasil_pemeriksaan) LIKE '%rujuk%')";
        } elseif ($filter === 'uks') {
            $query .= " AND (LOWER(p.tindakan) LIKE '%rawat%' OR LOWER(p.tindakan) LIKE '%uks%' OR LOWER(p.tindakan) LIKE '%inap%' OR LOWER(p.catatan) LIKE '%rawat%' OR LOWER(p.catatan) LIKE '%uks%')";
        }

        $query .= " ORDER BY p.tanggal_pemeriksaan DESC LIMIT :limit OFFSET :offset";

        $this->db->query($query);
        foreach ($binds as $k => $v) {
            $this->db->bind($k, $v);
        }
        $this->db->bind('limit', $limit, PDO::PARAM_INT);
        $this->db->bind('offset', $offset, PDO::PARAM_INT);

        return $this->db->resultSet();
    }

    public function countAllPemeriksaan($keyword = '', $tanggal_dari = '', $tanggal_sampai = '', $filter = '') {
        $query = "SELECT COUNT(p.id) AS total
                  FROM pemeriksaan_kesehatan p
                  JOIN santri s ON p.santri_id = s.id
                  WHERE 1=1";

        $binds = [];
        if (!empty($keyword)) {
            $query .= " AND (s.nama LIKE :keyword1 OR s.nis LIKE :keyword2 OR p.keluhan LIKE :keyword3 OR p.hasil_pemeriksaan LIKE :keyword4 OR p.tindakan LIKE :keyword5 OR p.catatan LIKE :keyword6)";
            $binds['keyword1'] = "%$keyword%";
            $binds['keyword2'] = "%$keyword%";
            $binds['keyword3'] = "%$keyword%";
            $binds['keyword4'] = "%$keyword%";
            $binds['keyword5'] = "%$keyword%";
            $binds['keyword6'] = "%$keyword%";
        }
        if (!empty($tanggal_dari)) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) >= :tanggal_dari";
            $binds['tanggal_dari'] = $tanggal_dari;
        }
        if (!empty($tanggal_sampai)) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) <= :tanggal_sampai";
            $binds['tanggal_sampai'] = $tanggal_sampai;
        }
        if ($filter === 'rujukan') {
            $query .= " AND (LOWER(p.tindakan) LIKE '%rujuk%' OR LOWER(p.catatan) LIKE '%rujuk%' OR LOWER(p.hasil_pemeriksaan) LIKE '%rujuk%')";
        } elseif ($filter === 'uks') {
            $query .= " AND (LOWER(p.tindakan) LIKE '%rawat%' OR LOWER(p.tindakan) LIKE '%uks%' OR LOWER(p.tindakan) LIKE '%inap%' OR LOWER(p.catatan) LIKE '%rawat%' OR LOWER(p.catatan) LIKE '%uks%')";
        }

        $this->db->query($query);
        foreach ($binds as $k => $v) {
            $this->db->bind($k, $v);
        }
        return $this->db->single()['total'];
    }

    /**
     * Ambil riwayat pemeriksaan milik santri tertentu saja (untuk role santri)
     */
    public function getPemeriksaanBySantriUserId($user_id, $limit, $offset) {
        $this->db->query("SELECT p.*,
                                 s.nama AS nama_santri, s.nis,
                                 COALESCE(pg.nama, 'Admin') AS nama_pemeriksa
                          FROM pemeriksaan_kesehatan p
                          JOIN santri s ON p.santri_id = s.id AND s.user_id = :user_id
                          LEFT JOIN pengurus pg ON pg.user_id = p.pemeriksa_id
                          ORDER BY p.tanggal_pemeriksaan DESC
                          LIMIT :limit OFFSET :offset");
        $this->db->bind('user_id', $user_id);
        $this->db->bind('limit', $limit, PDO::PARAM_INT);
        $this->db->bind('offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function countPemeriksaanBySantriUserId($user_id) {
        $this->db->query("SELECT COUNT(p.id) AS total
                          FROM pemeriksaan_kesehatan p
                          JOIN santri s ON p.santri_id = s.id AND s.user_id = :user_id");
        $this->db->bind('user_id', $user_id);
        return $this->db->single()['total'];
    }

    /**
     * Ambil satu detail pemeriksaan lengkap
     */
    public function getPemeriksaanById($id) {
        $this->db->query("SELECT p.*,
                                 s.nama AS nama_santri, s.nis, s.user_id AS santri_user_id,
                                 s.kamar, s.kelas, s.golongan_darah, s.alergi,
                                 COALESCE(pg.nama, 'Administrator') AS nama_pemeriksa,
                                 u.username AS username_pemeriksa
                          FROM pemeriksaan_kesehatan p
                          JOIN santri s ON p.santri_id = s.id
                          LEFT JOIN users u ON p.pemeriksa_id = u.id
                          LEFT JOIN pengurus pg ON pg.user_id = p.pemeriksa_id
                          WHERE p.id = :id");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function insertPemeriksaan($data) {
        $keluhan_id = !empty($data['keluhan_id']) ? (int)$data['keluhan_id'] : null;

        try {
            $this->db->beginTransaction();

            $this->db->query("INSERT INTO pemeriksaan_kesehatan 
                              (santri_id, pemeriksa_id, keluhan_id, tanggal_pemeriksaan, keluhan,
                               suhu, tekanan_darah, berat_badan, tinggi_badan,
                               hasil_pemeriksaan, tindakan, obat, catatan)
                              VALUES
                              (:santri_id, :pemeriksa_id, :keluhan_id, :tanggal_pemeriksaan, :keluhan,
                               :suhu, :tekanan_darah, :berat_badan, :tinggi_badan,
                               :hasil_pemeriksaan, :tindakan, :obat, :catatan)");

            $this->db->bind('santri_id', $data['santri_id']);
            $this->db->bind('pemeriksa_id', $data['pemeriksa_id']);
            $this->db->bind('keluhan_id', $keluhan_id);
            $this->db->bind('tanggal_pemeriksaan', $data['tanggal_pemeriksaan']);
            $this->db->bind('keluhan', $data['keluhan']);
            $this->db->bind('suhu', $data['suhu'] ?: null);
            $this->db->bind('tekanan_darah', $data['tekanan_darah']);
            $this->db->bind('berat_badan', $data['berat_badan'] ?: null);
            $this->db->bind('tinggi_badan', $data['tinggi_badan'] ?: null);
            $this->db->bind('hasil_pemeriksaan', $data['hasil_pemeriksaan']);
            $this->db->bind('tindakan', $data['tindakan']);
            $this->db->bind('obat', $data['obat']);
            $this->db->bind('catatan', $data['catatan']);

            $res = $this->db->execute();
            if ($res && $keluhan_id) {
                $this->db->query("UPDATE riwayat_keluhan SET status = 'selesai' WHERE id = :kid");
                $this->db->bind('kid', $keluhan_id);
                $this->db->execute();

                // Notifikasi penyelesaian keluhan dikirim ke SANTRI (bukan ke pemeriksa yang mencatat)
                try {
                    $this->db->query("SELECT rk.id, rk.santri_id, s.user_id AS santri_user_id, s.nama AS nama_santri, p.nama AS nama_pengurus 
                                      FROM riwayat_keluhan rk 
                                      JOIN santri s ON rk.santri_id = s.id 
                                      LEFT JOIN users u ON rk.assigned_to = u.id 
                                      LEFT JOIN pengurus p ON u.id = p.user_id 
                                      WHERE rk.id = :kid LIMIT 1");
                    $this->db->bind('kid', $keluhan_id);
                    $kRow = $this->db->single();
                    if ($kRow && !empty($kRow['santri_user_id'])) {
                        require_once __DIR__ . '/Notifikasi_model.php';
                        $notifMod = new Notifikasi_model();
                        $namaSantri = $kRow['nama_santri'] ?? 'Santri';
                        $namaPengurus = $kRow['nama_pengurus'] ?? 'Petugas Poskestren';
                        $notifMod->createNotification(
                            (int)$kRow['santri_user_id'],
                            'status_selesai',
                            'Pemeriksaan Keluhan Selesai',
                            "Pemeriksaan kesehatan untuk keluhan Anda telah selesai dicatat oleh petugas ({$namaPengurus}). Semoga lekas sembuh!",
                            '/keluhan',
                            'keluhan',
                            (int)$keluhan_id,
                            "Halo {$namaSantri},\n\nPemeriksaan keluhan kesehatan Anda di Poskestren YukSehat telah selesai dicatat oleh petugas ({$namaPengurus}). Silakan beristirahat dan patuhi anjuran petugas. Semoga lekas sembuh.\n\nTerima kasih."
                        );
                    }
                } catch (Exception $ne) {
                    error_log('[YukSehat Notif In Examination Warning] ' . $ne->getMessage());
                }
            }

            $this->db->commit();
            return $res;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('[insertPemeriksaan Error] ' . $e->getMessage());
            return false;
        }
    }

    public function updatePemeriksaan($data, $id) {
        $this->db->query("UPDATE pemeriksaan_kesehatan SET
                          tanggal_pemeriksaan = :tanggal_pemeriksaan,
                          keluhan = :keluhan,
                          suhu = :suhu,
                          tekanan_darah = :tekanan_darah,
                          berat_badan = :berat_badan,
                          tinggi_badan = :tinggi_badan,
                          hasil_pemeriksaan = :hasil_pemeriksaan,
                          tindakan = :tindakan,
                          obat = :obat,
                          catatan = :catatan
                          WHERE id = :id");

        $this->db->bind('tanggal_pemeriksaan', $data['tanggal_pemeriksaan']);
        $this->db->bind('keluhan', $data['keluhan']);
        $this->db->bind('suhu', $data['suhu'] ?: null);
        $this->db->bind('tekanan_darah', $data['tekanan_darah']);
        $this->db->bind('berat_badan', $data['berat_badan'] ?: null);
        $this->db->bind('tinggi_badan', $data['tinggi_badan'] ?: null);
        $this->db->bind('hasil_pemeriksaan', $data['hasil_pemeriksaan']);
        $this->db->bind('tindakan', $data['tindakan']);
        $this->db->bind('obat', $data['obat']);
        $this->db->bind('catatan', $data['catatan']);
        $this->db->bind('id', $id);

        return $this->db->execute();
    }

    public function deletePemeriksaan($id) {
        $this->db->query("DELETE FROM pemeriksaan_kesehatan WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->execute();
    }

    /**
     * Ambil semua santri aktif untuk dropdown
     */
    public function getAllSantriList() {
        $this->db->query("SELECT s.id, s.nis, s.nama, s.kamar, s.kelas
                          FROM santri s
                          JOIN users u ON s.user_id = u.id AND u.status = 'active'
                          ORDER BY s.nama ASC");
        return $this->db->resultSet();
    }
}
