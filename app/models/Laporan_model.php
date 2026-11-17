<?php

class Laporan_model {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getFilterOptions() {
        // Ambil opsi dinamis untuk kelas dan kamar dari tabel santri
        $this->db->query("SELECT DISTINCT kelas FROM santri WHERE kelas IS NOT NULL AND kelas != '' ORDER BY kelas ASC");
        $kelas = $this->db->resultSet();
        
        $this->db->query("SELECT DISTINCT kamar FROM santri WHERE kamar IS NOT NULL AND kamar != '' ORDER BY kamar ASC");
        $kamar = $this->db->resultSet();

        return [
            'kelas' => $kelas,
            'kamar' => $kamar
        ];
    }

    public function getLaporanData($filters = []) {
        $query = "
            SELECT 
                p.id, 
                p.tanggal_pemeriksaan, 
                p.keluhan, 
                p.suhu, 
                p.tekanan_darah, 
                p.berat_badan, 
                p.tinggi_badan, 
                p.hasil_pemeriksaan, 
                p.tindakan, 
                p.obat, 
                p.catatan, 
                s.nis, 
                s.nama as nama_santri, 
                s.kelas, 
                s.kamar, 
                s.jenis_kelamin,
                pg.nama as nama_pemeriksa
            FROM pemeriksaan_kesehatan p
            JOIN santri s ON p.santri_id = s.id
            LEFT JOIN pengurus pg ON p.pemeriksa_id = pg.user_id
            WHERE 1=1
        ";

        $binds = [];

        // Dynamic Filtering
        if (!empty($filters['tgl_mulai'])) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) >= :tgl_mulai";
            $binds['tgl_mulai'] = $filters['tgl_mulai'];
        }

        if (!empty($filters['tgl_akhir'])) {
            $query .= " AND DATE(p.tanggal_pemeriksaan) <= :tgl_akhir";
            $binds['tgl_akhir'] = $filters['tgl_akhir'];
        }

        if (!empty($filters['santri_id'])) {
            $query .= " AND p.santri_id = :santri_id";
            $binds['santri_id'] = $filters['santri_id'];
        } elseif (!empty($filters['nama_santri'])) {
            $query .= " AND s.nama LIKE :nama_santri";
            $binds['nama_santri'] = '%' . $filters['nama_santri'] . '%';
        }

        if (!empty($filters['kelas'])) {
            $query .= " AND s.kelas = :kelas";
            $binds['kelas'] = $filters['kelas'];
        }

        if (!empty($filters['kamar'])) {
            $query .= " AND s.kamar = :kamar";
            $binds['kamar'] = $filters['kamar'];
        }

        if (!empty($filters['jenis_kelamin'])) {
            $query .= " AND s.jenis_kelamin = :jenis_kelamin";
            $binds['jenis_kelamin'] = $filters['jenis_kelamin'];
        }

        if (!empty($filters['keluhan'])) {
            $query .= " AND p.keluhan LIKE :keluhan";
            $binds['keluhan'] = '%' . $filters['keluhan'] . '%';
        }

        $query .= " ORDER BY p.tanggal_pemeriksaan DESC";

        $this->db->query($query);
        
        foreach ($binds as $key => $value) {
            $this->db->bind($key, $value);
        }

        return $this->db->resultSet();
    }
}
