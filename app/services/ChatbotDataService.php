<?php

class ChatbotDataService {

    private static $bulanIndo = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember'
    ];

    /**
     * Cek apakah pesan pengguna merupakan pertanyaan data aktual database atau pencarian santri
     */
    public static function isDataQuery($message, $user = null) {
        $clean = strtolower(trim($message));
        $clean = preg_replace('/[?!.,]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        $isAdmin = ($user && isset($user['role']) && strtolower(trim($user['role'])) === 'admin');

        // Pattern matching untuk pertanyaan data aplikasi
        $patterns = [
            // 1. Konsultasi / pemeriksaan hari ini (santri unik vs total pemeriksaan)
            '/(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?(?:berkonsultasi|konsultasi|periksa|diperiksa)/i',
            '/(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i',
            '/(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:yang\s+)?periksa/i',
            '/(?:santri\s+)?(?:berkonsultasi|konsultasi)\s+hari\s+ini/i',
            '/santri\s+(?:yang\s+)?periksa\s+hari\s+ini/i',
            '/(?:siapa|daftar)\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i',
            '/(?:ada\s+)?(?:yang\s+)?periksa\s+hari\s+ini/i',
            '/pemeriksaan\s+hari\s+ini/i',
            '/pemeriksaan\s+minggu\s+ini/i',
            '/(?:ada\s+)?berapa\s+pemeriksaan\s+hari\s+ini/i',
            '/(?:ada\s+)?berapa\s+pemeriksaan\s+minggu\s+ini/i',
            '/berapa\s+(?:yang\s+)?periksa\s+minggu\s+ini/i',
            '/berapa\s+(?:yang\s+)?periksa\s+bulan\s+ini/i',

            // 2. Kelas / kamar dengan santri terbanyak
            '/kelas\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i',
            '/santri\s+terbanyak\s+(?:ada\s+)?di\s+kelas/i',
            '/kamar\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i',
            '/santri\s+terbanyak\s+(?:ada\s+)?di\s+kamar/i',

            // 3. Kelengkapan data santri
            '/(?:data\s+santri\s+)?(?:apa\s+(?:saja\s+)?)?(?:data\s+santri\s+)?(?:yang\s+)?(?:belum|tidak)\s+lengkap/i',
            '/data\s+(?:santri\s+)?(?:yang\s+)?belum\s+lengkap/i',
            '/kelengkapan\s+data\s+santri/i',
            '/(?:cek|periksa)\s+kelengkapan\s+data/i',
            '/santri\s+(?:data\s+)?(?:yang\s+)?belum\s+lengkap/i',

            // 4. Keluhan menunggu / antrean / santri keluhan hari ini
            '/(?:keluhan|antrean|antrian)\s+(?:santri\s+)?(?:yang\s+)?(?:masih\s+)?(?:menunggu|belum\s+diperiksa)/i',
            '/status\s+(?:antrean|antrian|keluhan)/i',
            '/(?:ada\s+)?berapa\s+keluhan(?:\s+yang)?(?:\s+masih)?\s+menunggu/i',
            '/(?:santri\s+unik\s+)?(?:yang\s+)?(?:menyampaikan\s+)?keluhan\s+hari\s+ini/i',
            '/(?:ada\s+)?berapa\s+santri(?:\s+unik)?(?:\s+yang)?\s+(?:menyampaikan|mengirim|punya)\s+keluhan\s+hari\s+ini/i',
            '/keluhan\s+hari\s+ini/i',

            // 5. Total / status santri / gender santri
            '/(?:ada\s+)?berapa\s+(?:jumlah\s+|total\s+)?santri/i',
            '/total\s+santri/i',
            '/jumlah\s+santri/i',
            '/santri\s+terdaftar/i',
            '/santri\s+(?:yang\s+)?aktif/i',
            '/santri\s+(?:nonaktif|tidak\s+aktif)/i',
            // Daftar nama santri / siapa saja santri
            '/(?:siapa|daftar|sebutkan|tampilkan)\s+(?:saja\s+|aja\s+)?(?:nama(?:nya)?|santri(?:nya)?|orang(?:nya)?)/i',
            '/(?:nama(?:nya)?|santri(?:nya)?)\s+siapa\s+(?:saja|aja)/i',
            '/daftar\s+(?:nama\s+)?santri/i',
            '/nama(?:\s*-\s*nama|\s+nama)?\s+santri/i',
            '/siapa\s+(?:saja\s+|aja\s+)?(?:santri\s+)?(?:yang\s+)?terdaftar/i',
            '/siapa\s+santrinya/i',
            '/siapa\s+namanya/i',
            '/santri\s+(?:laki-laki|perempuan|putra|putri|gender|jenis\s+kelamin)/i',
            '/berapa\s+(?:santri\s+)?(?:laki-laki|perempuan|putra|putri)/i',
            '/santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?alergi/i',
            '/santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?riwayat\s+penyakit/i',
            '/daftar\s+(?:alergi|riwayat\s+penyakit)/i',

            // 6. Santri per kelas / kamar
            '/santri\s+(?:berdasarkan|per)\s+kelas/i',
            '/santri\s+(?:di\s+)?(?:setiap|tiap)\s+kelas/i',
            '/santri\s+kelas\s+[a-z0-9\s-]+/i',
            '/kelas\s+[a-z0-9\s-]+\s+(?:ada\s+)?berapa/i',
            '/santri\s+(?:berdasarkan|per)\s+kamar/i',
            '/santri\s+(?:di\s+)?(?:setiap|tiap)\s+kamar/i',
            '/santri\s+kamar\s+[a-z0-9\s-]+/i',
            '/rekap\s+(?:data\s+)?santri/i',
            '/statistik\s+(?:data\s+)?santri/i',
            '/rekapitulasi\s+santri/i',

            // 7. Pemeriksaan bulan ini / terakhir / total / rawat inap / rujukan
            '/(?:ada\s+)?berapa\s+pemeriksaan\s+bulan\s+ini/i',
            '/pemeriksaan\s+(?:kesehatan\s+)?bulan\s+ini/i',
            '/kapan\s+pemeriksaan\s+(?:kesehatan\s+)?terakhir/i',
            '/pemeriksaan\s+(?:kesehatan\s+)?terakhir/i',
            '/tanggal\s+pemeriksaan\s+terakhir/i',
            '/rekap\s+(?:data\s+)?pemeriksaan/i',
            '/ringkasan\s+pemeriksaan/i',
            '/statistik\s+(?:data\s+)?pemeriksaan/i',
            '/rekapitulasi\s+pemeriksaan/i',
            '/total\s+pemeriksaan/i',
            '/jumlah\s+(?:seluruh\s+)?pemeriksaan/i',
            '/(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?(?:selesai\s+)?(?:di)?catat/i',
            '/(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?selesai/i',
            '/santri\s+(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks)/i',
            '/santri\s+(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i',
            '/(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i',
            '/(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks|sedang\s+di\s+uks)/i',
            '/(?:siapa|daftar|sebutkan)\s+(?:saja\s+)?(?:nama\s+)?(?:santri\s+)?(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i',
            '/(?:siapa|daftar|sebutkan)\s+(?:saja\s+)?(?:nama\s+)?(?:santri\s+)?(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks)/i',
            '/rujukan(?:\s+ada|\s+berapa|\s+siapa)/i',
            '/berapa\s+santri\s+(?:sakit|perlu\s+rujukan|rawat\s+inap)/i',
            '/siapa\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?sakit/i',

            // 8. Pengurus & Users
            '/(?:daftar|siapa|berapa)\s+(?:pengurus|petugas\s+poskestren)/i',
            '/(?:daftar|berapa|total)\s+(?:user|users|pengguna|akun)/i',

            // 9. Obat
            '/(?:daftar|apa\s+saja)\s+obat/i',
            '/obat\s+(?:yang\s+)?(?:pernah\s+)?(?:diberikan|digunakan|dipakai)/i',

            // 10. Database summary / rekap database
            '/(?:baca|rekap|ringkasan|seluruh|status|isi)\s+database/i',
            '/database\s+(?:yuksehat|aplikasi)/i',

            // 11. Pencarian santri yang diizinkan (directory lookup)
            '/cari\s+santri\s+[a-z0-9]/i',
            '/informasi\s+santri\s+[a-z0-9]/i',
            '/profil\s+santri\s+[a-z0-9]/i',

            // 12. Rekam medis santri spesifik
            '/(?:rekam\s+medis|riwayat\s+(?:penyakit|medis|kesehatan|pemeriksaan)|catatan\s+medis|data\s+medis|keluhan|diagnosa)\s+/i',
            '/(?:apa\s+(?:sakit|penyakit|keluhan|diagnosa|obat)|resep\s+obat|riwayat\s+sakit)\s+/i',
            '/\bobat\s+(?:untuk\s+|si\s+)?([a-z0-9]+)/i',
            '/(?:rekam\s+medis|riwayat\s+penyakit|keluhan|diagnosa)\s+(?:santri|anak|pasien|individu)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $clean)) {
                return true;
            }
        }

        // Khusus role admin: jika query menanyakan data/database
        if ($isAdmin) {
            if (
                preg_match('/\b(santri|pemeriksaan|periksa|keluhan|pengurus|users?|pengguna|akun|obat|diagnosa|penyakit|alergi|kamar|kelas|database|data|rekam\s*medis|nama(?:nya)?|orang(?:nya)?|rujuk(?:an)?|uks|rawat)\b/i', $clean) &&
                preg_match('/\b(berapa|siapa|apa|mana|kapan|daftar|tampilkan|cek|rekap|ringkasan|status|lihat|cari|ada|sebutkan|kalo)\b/i', $clean)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Memproses pertanyaan data dan mengembalikan jawaban terkontrol
     * 
     * @param string $message Pesan pengguna
     * @param array|null $user Info pengguna dari session backend terverifikasi ['id', 'nama', 'role']
     * @return string Jawaban langsung atau penolakan akses
     */
    public static function handleDataQuery($message, $user = null) {
        // 1. Verifikasi Login
        if (!$user || empty($user['role'])) {
            return 'Maaf, informasi data santri dan pemeriksaan hanya dapat diakses oleh admin atau pengurus yang telah login ke akun YukSehat.';
        }

        $role = strtolower(trim($user['role']));

        // 2. Verifikasi Role (Hanya admin dan pengurus)
        if ($role !== 'admin' && $role !== 'pengurus') {
            return 'Maaf, data rekapitulasi dan statistik santri atau pemeriksaan hanya dapat diakses oleh role admin atau pengurus.';
        }

        $clean = strtolower(trim($message));
        $clean = preg_replace('/[?!.,]/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        // 3. Batasan Kerahasiaan Medis Individu
        // Jika non-admin atau secara eksplisit meminta "apa rekam medis..."
        $isQueueQuery = preg_match('/(?:keluhan|antrean|antrian)\s+(?:santri\s+)?(?:yang\s+)?(?:masih\s+)?(?:menunggu|belum)/i', $clean) ||
                        preg_match('/status\s+(?:antrean|antrian|keluhan)/i', $clean) ||
                        preg_match('/(?:ada\s+)?berapa\s+keluhan(?:\s+yang)?(?:\s+masih)?\s+menunggu/i', $clean) ||
                        preg_match('/berapa\s+keluhan/i', $clean) ||
                        preg_match('/keluhan\s+hari\s+ini/i', $clean) ||
                        preg_match('/(?:santri\s+unik\s+)?(?:yang\s+)?(?:menyampaikan\s+)?keluhan/i', $clean);

        if (!$isQueueQuery) {
            if (
                preg_match('/^(?:apa\s+)?rekam\s+medis\s+(?:santri\s+)?([a-z0-9\s]+)/i', $clean) ||
                ($role !== 'admin' && (
                    preg_match('/(?:rekam\s+medis|riwayat\s+(?:penyakit|medis|kesehatan|pemeriksaan)|catatan\s+medis|data\s+medis|diagnosa)\s+(?:santri\s+)?([a-z0-9\s]+)/i', $clean) ||
                    preg_match('/(?:apa\s+(?:sakit|penyakit|diagnosa|obat)|resep\s+obat|riwayat\s+sakit|obat)\s+(?:dari\s+|si\s+|santri\s+|untuk\s+)?([a-z0-9\s]+)/i', $clean) ||
                    preg_match('/(?:rekam\s+medis|riwayat\s+penyakit|diagnosa|hasil\s+pemeriksaan)\s+(?:santri|anak|pasien|individu)/i', $clean)
                ))
            ) {
                return 'Untuk menjaga kerahasiaan medis santri, rincian rekam medis individual hanya dapat diakses melalui menu Pemeriksaan resmi sesuai hak akses Anda.';
            }
        }

        try {
            $db = new Database();

            // ── Intent 0: Siapa Saja Santri yang Periksa Hari Ini (Daftar Rinci Pemeriksaan Hari Ini) ──
            if (
                preg_match('/(?:siapa|daftar)\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i', $clean) ||
                preg_match('/santri\s+(?:yang\s+)?periksa\s+hari\s+ini\s+siapa/i', $clean)
            ) {
                $db->query("
                    SELECT p.id, p.tanggal_pemeriksaan, s.nama, s.nis, s.kelas, s.kamar, p.keluhan, p.hasil_pemeriksaan, p.tindakan, p.obat
                    FROM pemeriksaan_kesehatan p
                    JOIN santri s ON p.santri_id = s.id
                    WHERE DATE(p.tanggal_pemeriksaan) = CURDATE()
                    ORDER BY p.tanggal_pemeriksaan DESC
                ");
                $todayRows = $db->resultSet();
                if (empty($todayRows)) {
                    return "Hari ini belum ada santri yang melakukan pemeriksaan kesehatan (0 pemeriksaan).";
                }
                $lines = ["Daftar santri yang periksa hari ini (" . date('d') . " " . self::$bulanIndo[(int)date('n')] . " " . date('Y') . "):"];
                foreach ($todayRows as $row) {
                    $jam = date('H:i', strtotime($row['tanggal_pemeriksaan']));
                    $kls = !empty($row['kelas']) ? "Kelas {$row['kelas']}" : "Tanpa Kelas";
                    $kmr = !empty($row['kamar']) ? "Kamar {$row['kamar']}" : "";
                    $kmrStr = !empty($kmr) ? ", {$kmr}" : "";
                    $diag = !empty($row['hasil_pemeriksaan']) ? mb_substr(strip_tags($row['hasil_pemeriksaan']), 0, 80) : "Dalam observasi";
                    $obt = !empty($row['obat']) ? " | Obat: {$row['obat']}" : "";
                    $lines[] = "- {$row['nama']} ({$kls}{$kmrStr}) pukul {$jam} WIB: Keluhan \"{$row['keluhan']}\", Diagnosa: \"{$diag}\"{$obt}";
                }
                return implode("\n", $lines);
            }

            // ── Intent 1: Konsultasi / Pemeriksaan Hari Ini (Berapa yang periksa hari ini) ──
            if (
                preg_match('/(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?(?:berkonsultasi|konsultasi|periksa|diperiksa)/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+(?:orang\s+|santri\s+)?(?:yang\s+)?periksa(?:\s+hari\s+ini)?/i', $clean) ||
                preg_match('/(?:hari\s+ini\s+)?(?:ada\s+)?berapa\s+(?:yang\s+)?periksa/i', $clean) ||
                preg_match('/(?:santri\s+)?(?:berkonsultasi|konsultasi)\s+hari\s+ini/i', $clean) ||
                preg_match('/santri\s+(?:yang\s+)?periksa\s+hari\s+ini/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+pemeriksaan\s+hari\s+ini/i', $clean) ||
                preg_match('/pemeriksaan\s+hari\s+ini/i', $clean) ||
                preg_match('/(?:ada\s+)?(?:yang\s+)?periksa\s+hari\s+ini/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COUNT(DISTINCT santri_id) AS santri_unik,
                        COUNT(*) AS total_pemeriksaan
                    FROM pemeriksaan_kesehatan
                    WHERE DATE(tanggal_pemeriksaan) = CURDATE()
                ");
                $todayRes = $db->single();
                $unik = (int)($todayRes['santri_unik'] ?? 0);
                $totalPem = (int)($todayRes['total_pemeriksaan'] ?? 0);

                if ($unik > 0) {
                    // Ambil nama santri yang periksa
                    $db->query("
                        SELECT DISTINCT s.nama, s.kelas
                        FROM pemeriksaan_kesehatan p
                        JOIN santri s ON p.santri_id = s.id
                        WHERE DATE(p.tanggal_pemeriksaan) = CURDATE()
                        LIMIT 5
                    ");
                    $santriNames = [];
                    foreach ($db->resultSet() as $sr) {
                        $santriNames[] = $sr['nama'] . (!empty($sr['kelas']) ? " ({$sr['kelas']})" : "");
                    }
                    $namesStr = !empty($santriNames) ? ": " . implode(', ', $santriNames) : "";

                    if (preg_match('/berapa\s+santri/i', $clean) || preg_match('/berkonsultasi/i', $clean)) {
                        return "Hari ini terdapat {$unik} orang santri yang berkonsultasi (dengan total {$totalPem} catatan pemeriksaan kesehatan).";
                    }
                    return "Hari ini terdapat {$totalPem} pemeriksaan kesehatan (melibatkan {$unik} orang santri{$namesStr}).";
                } else {
                    $db->query("SELECT tanggal_pemeriksaan FROM pemeriksaan_kesehatan ORDER BY tanggal_pemeriksaan DESC LIMIT 1");
                    $lastRow = $db->single();
                    if ($lastRow && !empty($lastRow['tanggal_pemeriksaan'])) {
                        $lastTime = self::formatTanggalWaktu($lastRow['tanggal_pemeriksaan']);
                        return "Hari ini belum ada santri yang berkonsultasi atau melakukan pemeriksaan kesehatan (0 pemeriksaan). Pemeriksaan terakhir tercatat pada {$lastTime} WIB.";
                    }
                    return "Hari ini belum ada santri yang berkonsultasi atau melakukan pemeriksaan kesehatan (0 pemeriksaan).";
                }
            }

            // ── Intent 1B: Santri Unik yang Menyampaikan Keluhan Hari Ini ──
            if (
                preg_match('/(?:santri\s+unik\s+)?(?:yang\s+)?(?:menyampaikan\s+)?keluhan\s+hari\s+ini/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+santri(?:\s+unik)?(?:\s+yang)?\s+(?:menyampaikan|mengirim|punya)\s+keluhan\s+hari\s+ini/i', $clean) ||
                preg_match('/keluhan\s+hari\s+ini/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COUNT(DISTINCT santri_id) AS santri_unik,
                        COUNT(*) AS total_keluhan
                    FROM riwayat_keluhan
                    WHERE DATE(tanggal_keluhan) = CURDATE()
                ");
                $kRes = $db->single();
                $unik = (int)($kRes['santri_unik'] ?? 0);
                $tot = (int)($kRes['total_keluhan'] ?? 0);

                if ($unik > 0) {
                    return "Hari ini terdapat {$unik} orang santri yang menyampaikan keluhan (dengan total {$tot} keluhan tercatat).";
                } else {
                    return "Hari ini belum ada santri yang menyampaikan keluhan (0 keluhan).";
                }
            }

            // ── Intent 2: Pemeriksaan Minggu Ini ──
            if (
                preg_match('/(?:ada\s+)?berapa\s+pemeriksaan\s+minggu\s+ini/i', $clean) ||
                preg_match('/pemeriksaan\s+minggu\s+ini/i', $clean) ||
                preg_match('/berapa\s+(?:yang\s+)?periksa\s+minggu\s+ini/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COUNT(*) AS total_pemeriksaan,
                        COUNT(DISTINCT santri_id) AS santri_unik
                    FROM pemeriksaan_kesehatan 
                    WHERE YEARWEEK(tanggal_pemeriksaan, 1) = YEARWEEK(CURDATE(), 1)
                ");
                $r = $db->single();
                $tot = (int)($r['total_pemeriksaan'] ?? 0);
                $unik = (int)($r['santri_unik'] ?? 0);
                return "Minggu ini tercatat {$tot} pemeriksaan kesehatan untuk {$unik} santri.";
            }

            // ── Intent 3: Santri Berdasarkan Jenis Kelamin (Gender L/P) ──
            if (
                preg_match('/santri\s+(?:laki-laki|perempuan|putra|putri|gender|jenis\s+kelamin)/i', $clean) ||
                preg_match('/berapa\s+(?:santri\s+)?(?:laki-laki|perempuan|putra|putri)/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        SUM(CASE WHEN jenis_kelamin = 'L' THEN 1 ELSE 0 END) AS total_l,
                        SUM(CASE WHEN jenis_kelamin = 'P' THEN 1 ELSE 0 END) AS total_p,
                        COUNT(*) AS total_semua
                    FROM santri
                ");
                $g = $db->single();
                $l = (int)($g['total_l'] ?? 0);
                $p = (int)($g['total_p'] ?? 0);
                $tot = (int)($g['total_semua'] ?? 0);
                return "Jumlah santri berdasarkan jenis kelamin: Laki-laki (putra): {$l} orang, Perempuan (putri): {$p} orang (Total: {$tot} santri terdaftar).";
            }

            // ── Intent 4: Kelas Mana yang Memiliki Santri Terbanyak ──
            if (
                preg_match('/kelas\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i', $clean) ||
                preg_match('/santri\s+terbanyak\s+(?:ada\s+)?di\s+kelas/i' , $clean)
            ) {
                $db->query("
                    SELECT 
                        COALESCE(NULLIF(TRIM(kelas), ''), 'Tanpa Kelas') AS kelas,
                        COUNT(*) AS total
                    FROM santri
                    GROUP BY COALESCE(NULLIF(TRIM(kelas), ''), 'Tanpa Kelas')
                    ORDER BY total DESC, kelas ASC
                ");
                $rows = $db->resultSet();
                if (empty($rows)) {
                    return "Belum ada data kelas santri di sistem.";
                }

                $maxCount = (int)$rows[0]['total'];
                $top = [];
                foreach ($rows as $row) {
                    if ((int)$row['total'] === $maxCount) {
                        $top[] = "kelas {$row['kelas']} ({$row['total']} santri)";
                    }
                }
                return "Kelas dengan santri terbanyak saat ini adalah " . implode(' dan ', $top) . ".";
            }

            // ── Intent 5: Kamar Mana yang Memiliki Santri Terbanyak ──
            if (
                preg_match('/kamar\s+(?:mana\s+yang\s+memiliki|mana\s+yang|mana|terbanyak|paling\s+banyak)/i', $clean) ||
                preg_match('/santri\s+terbanyak\s+(?:ada\s+)?di\s+kamar/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COALESCE(NULLIF(TRIM(kamar), ''), 'Tanpa Kamar') AS kamar,
                        COUNT(*) AS total
                    FROM santri
                    GROUP BY COALESCE(NULLIF(TRIM(kamar), ''), 'Tanpa Kamar')
                    ORDER BY total DESC, kamar ASC
                ");
                $rows = $db->resultSet();
                if (empty($rows)) {
                    return "Belum ada data kamar santri di sistem.";
                }

                $maxCount = (int)$rows[0]['total'];
                $top = [];
                foreach ($rows as $row) {
                    if ((int)$row['total'] === $maxCount) {
                        $top[] = "kamar {$row['kamar']} ({$row['total']} santri)";
                    }
                }
                return "Kamar dengan santri terbanyak saat ini adalah " . implode(' dan ', $top) . ".";
            }

            // ── Intent 6: Data Santri yang Belum Lengkap (Audit Kelengkapan Data) ──
            if (
                preg_match('/(?:data\s+santri\s+)?(?:apa\s+(?:saja\s+)?)?(?:data\s+santri\s+)?(?:yang\s+)?(?:belum|tidak)\s+lengkap/i', $clean) ||
                preg_match('/data\s+(?:santri\s+)?(?:yang\s+)?belum\s+lengkap/i', $clean) ||
                preg_match('/kelengkapan\s+data\s+santri/i', $clean) ||
                preg_match('/(?:cek|periksa)\s+kelengkapan\s+data/i', $clean) ||
                preg_match('/santri\s+(?:data\s+)?(?:yang\s+)?belum\s+lengkap/i', $clean)
            ) {
                $db->query("
                    SELECT s.nis, s.nama, s.kelas, s.kamar, s.no_hp, s.golongan_darah
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                    WHERE u.status = 'active'
                      AND (
                        s.kelas IS NULL OR TRIM(s.kelas) = '' OR
                        s.kamar IS NULL OR TRIM(s.kamar) = '' OR
                        s.no_hp IS NULL OR TRIM(s.no_hp) = '' OR
                        s.golongan_darah IS NULL OR TRIM(s.golongan_darah) = ''
                      )
                    ORDER BY s.nama ASC
                ");
                $incompletes = $db->resultSet();
                $totalInc = count($incompletes);

                if ($totalInc === 0) {
                    return "Seluruh data santri aktif saat ini sudah terisi lengkap (kelas, kamar, kontak, dan golongan darah).";
                }

                $lines = ["Ditemukan {$totalInc} santri aktif dengan data belum lengkap:"];
                foreach (array_slice($incompletes, 0, 5) as $item) {
                    $miss = [];
                    if (empty(trim($item['kelas'] ?? ''))) $miss[] = 'kelas';
                    if (empty(trim($item['kamar'] ?? ''))) $miss[] = 'kamar';
                    if (empty(trim($item['no_hp'] ?? ''))) $miss[] = 'no. HP';
                    if (empty(trim($item['golongan_darah'] ?? ''))) $miss[] = 'golongan darah';
                    $lines[] = "- {$item['nama']} (NIS {$item['nis']}): belum ada " . implode(', ', $miss);
                }
                if ($totalInc > 5) {
                    $sisa = $totalInc - 5;
                    $lines[] = "... dan {$sisa} santri lainnya. Silakan lengkapi melalui menu Data Santri.";
                } else {
                    $lines[] = "Silakan lengkapi data tersebut melalui menu Data Santri.";
                }
                return implode("\n", $lines);
            }

            // ── Intent 7: Keluhan Santri yang Menunggu / Status Antrean ──
            if (
                preg_match('/(?:keluhan|antrean|antrian)\s+(?:santri\s+)?(?:yang\s+)?(?:masih\s+)?(?:menunggu|belum\s+diperiksa)/i', $clean) ||
                preg_match('/status\s+(?:antrean|antrian|keluhan)/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+keluhan(?:\s+yang)?(?:\s+masih)?\s+menunggu/i', $clean) ||
                preg_match('/berapa\s+keluhan\s+menunggu/i', $clean)
            ) {
                $db->query("
                    SELECT rk.id, rk.tanggal_keluhan, rk.keluhan, s.nama AS nama_santri, s.kelas
                    FROM riwayat_keluhan rk
                    JOIN santri s ON rk.santri_id = s.id
                    WHERE rk.status = 'menunggu'
                    ORDER BY rk.tanggal_keluhan ASC
                ");
                $pending = $db->resultSet();
                $cnt = count($pending);

                if ($cnt === 0) {
                    return "Saat ini tidak ada antrean keluhan santri yang berstatus menunggu pemeriksaan (0 keluhan menunggu).";
                }

                $lines = ["Saat ini ada {$cnt} keluhan santri yang masih berstatus menunggu pemeriksaan:"];
                foreach (array_slice($pending, 0, 3) as $p) {
                    $lines[] = "- {$p['nama_santri']} (Kelas {$p['kelas']}): \"{$p['keluhan']}\"";
                }
                if ($cnt > 3) {
                    $lines[] = "... dan " . ($cnt - 3) . " keluhan lainnya di menu Antrean Keluhan.";
                }
                return implode("\n", $lines);
            }

            // ── Intent 8: Santri Rawat Inap / Observasi UKS / Rujukan Faskes ──
            if (
                preg_match('/santri\s+(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks)/i', $clean) ||
                preg_match('/santri\s+(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i', $clean) ||
                preg_match('/(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:perlu\s+rujukan|dirujuk|rujukan)/i', $clean) ||
                preg_match('/(?:siapa|daftar|sebutkan|berapa|kalo)?\s*(?:yang\s+)?(?:rawat\s+inap|observasi|di\s+uks|sedang\s+di\s+uks)/i', $clean) ||
                preg_match('/berapa\s+santri\s+(?:sakit|perlu\s+rujukan|rawat\s+inap)/i', $clean) ||
                preg_match('/rujukan(?:\s+ada|\s+berapa|\s+siapa)/i', $clean)
            ) {
                $isAskNames = preg_match('/(?:siapa|nama|daftar|sebutkan)/i', $clean);

                if (preg_match('/rujuk/i', $clean)) {
                    $db->query("
                        SELECT p.id, p.tanggal_pemeriksaan, s.nis, s.nama, s.kelas, s.kamar, p.keluhan, p.hasil_pemeriksaan, p.tindakan, p.catatan
                        FROM pemeriksaan_kesehatan p
                        JOIN santri s ON p.santri_id = s.id
                        WHERE LOWER(p.tindakan) LIKE '%rujuk%' OR LOWER(p.catatan) LIKE '%rujuk%' OR LOWER(p.hasil_pemeriksaan) LIKE '%rujuk%'
                        ORDER BY p.tanggal_pemeriksaan DESC
                    ");
                    $rujukRows = $db->resultSet();
                    $totalCatatan = count($rujukRows);

                    if ($totalCatatan === 0) {
                        return "Saat ini tidak ada santri yang memiliki catatan rekomendasi rujukan ke faskes/rumah sakit (0 rujukan).";
                    }

                    // Kelompokkan per santri unik
                    $santriUnik = [];
                    foreach ($rujukRows as $r) {
                        if (!isset($santriUnik[$r['nama']])) {
                            $santriUnik[$r['nama']] = [
                                'nama' => $r['nama'],
                                'nis' => $r['nis'],
                                'kelas' => $r['kelas'],
                                'kamar' => $r['kamar'],
                                'catatan_list' => []
                            ];
                        }
                        $santriUnik[$r['nama']]['catatan_list'][] = $r;
                    }
                    $totalSantriUnik = count($santriUnik);

                    // Helper: ekstrak catatan rujukan yang bersih (hindari teks AI panjang)
                    $extractNote = function($c) {
                        $tindakan = trim(html_entity_decode(strip_tags($c['tindakan'] ?? ''), ENT_QUOTES, 'UTF-8'));
                        $isAiText = (stripos($tindakan, 'Saran Tindak Lanjut AI') !== false ||
                                     stripos($tindakan, '[Draf') !== false ||
                                     mb_strlen($tindakan) > 200);
                        if (!empty($tindakan) && !$isAiText) {
                            return ucfirst(preg_replace('/\s+/', ' ', mb_strtolower($tindakan)));
                        }
                        foreach ([$c['tindakan'] ?? '', $c['catatan'] ?? '', $c['hasil_pemeriksaan'] ?? ''] as $src) {
                            $txt = html_entity_decode(strip_tags($src), ENT_QUOTES, 'UTF-8');
                            $txt = preg_replace('/[*#_`\[\]]/u', '', $txt);
                            foreach (preg_split('/(?<=[.!?\n])\s*/u', $txt, -1, PREG_SPLIT_NO_EMPTY) as $s) {
                                $s = trim($s, " \t\n\r\0\x0B-\xe2\x80\xa2*");
                                if (stripos($s, 'rujuk') !== false && mb_strlen($s) > 10) {
                                    $s = preg_replace('/\s+/', ' ', $s);
                                    return mb_strlen($s) > 110 ? mb_substr($s, 0, 107) . '...' : $s;
                                }
                            }
                        }
                        return 'Rekomendasi rujukan ke faskes / RS';
                    };

                    $namaList = array_keys($santriUnik);

                    if ($isAskNames) {
                        // Jawaban detail: siapa / daftar / nama
                        $lines = ["Daftar {$totalSantriUnik} santri yang perlu rujukan:"];
                        $no = 1;
                        foreach ($santriUnik as $nama => $info) {
                            $kls = !empty($info['kelas']) ? $info['kelas'] : '-';
                            $kmr = !empty($info['kamar']) ? ", Kamar {$info['kamar']}" : '';
                            $lines[] = "{$no}. {$nama} (Kelas {$kls}{$kmr})";
                            foreach ($info['catatan_list'] as $c) {
                                $tgl = date('d/m/Y', strtotime($c['tanggal_pemeriksaan']));
                                $note = $extractNote($c);
                                $lines[] = "   \xe2\x80\xa2 {$tgl}: Keluhan \"{$c['keluhan']}\" \xe2\x86\x92 {$note}";
                            }
                            $no++;
                        }
                        $lines[] = "Detail lengkap tersedia di menu Pemeriksaan \xe2\x86\x92 filter Rujukan.";
                        return implode("\n", $lines);
                    } else {
                        // Jawaban ringkas: berapa / ada berapa
                        return "Ada {$totalSantriUnik} santri yang perlu rujukan ke faskes.";
                    }
                }

                if (preg_match('/rawat|observasi|uks/i', $clean)) {
                    $db->query("
                        SELECT p.id, p.tanggal_pemeriksaan, s.nis, s.nama, s.kelas, s.kamar, p.keluhan, p.hasil_pemeriksaan, p.tindakan, p.catatan
                        FROM pemeriksaan_kesehatan p
                        JOIN santri s ON p.santri_id = s.id
                        WHERE LOWER(p.tindakan) LIKE '%rawat%' OR LOWER(p.tindakan) LIKE '%uks%' OR LOWER(p.tindakan) LIKE '%inap%' OR LOWER(p.catatan) LIKE '%rawat%' OR LOWER(p.catatan) LIKE '%uks%'
                        ORDER BY p.tanggal_pemeriksaan DESC
                    ");
                    $rawatRows = $db->resultSet();
                    $totalRawat = count($rawatRows);

                    if ($totalRawat === 0) {
                        return "Saat ini tidak ada santri yang tercatat sedang diobservasi atau dirawat inap di UKS (0 santri).";
                    }

                    $santriUnik = [];
                    foreach ($rawatRows as $r) {
                        $santriUnik[$r['nama']] = $r;
                    }
                    $totalSantriUnik = count($santriUnik);

                    if ($isAskNames) {
                        $lines = ["Daftar santri yang diobservasi / dirawat di UKS ({$totalRawat} catatan, {$totalSantriUnik} santri):"];
                        $no = 1;
                        foreach ($santriUnik as $nama => $r) {
                            $kls = !empty($r['kelas']) ? "Kelas {$r['kelas']}" : "Tanpa Kelas";
                            $kmr = !empty($r['kamar']) ? ", Kamar {$r['kamar']}" : "";
                            $tgl = date('d/m/Y', strtotime($r['tanggal_pemeriksaan']));
                            $lines[] = "{$no}. {$nama} ({$kls}{$kmr}) [Tgl {$tgl}]: Keluhan \"{$r['keluhan']}\"";
                            $no++;
                        }
                        return implode("\n", $lines);
                    } else {
                        $namaList = array_keys($santriUnik);
                        return "Terdapat {$totalRawat} catatan pemeriksaan dengan tindakan observasi / rawat di UKS (melibatkan {$totalSantriUnik} santri: " . implode(', ', $namaList) . ").";
                    }
                }

                $db->query("
                    SELECT 
                        SUM(CASE WHEN LOWER(tindakan) LIKE '%rawat%' OR LOWER(tindakan) LIKE '%uks%' OR LOWER(tindakan) LIKE '%inap%' OR LOWER(catatan) LIKE '%rawat%' OR LOWER(catatan) LIKE '%uks%' THEN 1 ELSE 0 END) AS rawat_inap,
                        SUM(CASE WHEN LOWER(tindakan) LIKE '%rujuk%' OR LOWER(catatan) LIKE '%rujuk%' OR LOWER(hasil_pemeriksaan) LIKE '%rujuk%' THEN 1 ELSE 0 END) AS rujukan,
                        COUNT(DISTINCT santri_id) AS santri_sakit
                    FROM pemeriksaan_kesehatan
                ");
                $stat = $db->single();
                $rawat = (int)($stat['rawat_inap'] ?? 0);
                $rujuk = (int)($stat['rujukan'] ?? 0);
                $sakit = (int)($stat['santri_sakit'] ?? 0);
                return "Berdasarkan data pemeriksaan: {$sakit} santri pernah tercatat sakit, dengan {$rawat} observasi UKS dan {$rujuk} rujukan faskes.";
            }

            // ── Intent 8B: Santri Sedang Sakit / Siapa Saja yang Sakit ──
            if (
                preg_match('/siapa\s+(?:saja\s+)?(?:santri\s+)?(?:yang\s+)?sakit/i', $clean) ||
                preg_match('/santri\s+(?:yang\s+)?sedang\s+sakit/i', $clean)
            ) {
                $db->query("
                    SELECT DISTINCT s.nama, s.kelas, p.keluhan, p.hasil_pemeriksaan, p.tanggal_pemeriksaan
                    FROM pemeriksaan_kesehatan p
                    JOIN santri s ON p.santri_id = s.id
                    WHERE DATE(p.tanggal_pemeriksaan) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                    ORDER BY p.tanggal_pemeriksaan DESC
                    LIMIT 5
                ");
                $sakitRows = $db->resultSet();
                if (empty($sakitRows)) {
                    return "Dalam 7 hari terakhir, tidak ada santri yang tercatat sedang sakit atau melakukan pemeriksaan.";
                }
                $lines = ["Santri dengan catatan pemeriksaan/sakit dalam 7 hari terakhir:"];
                foreach ($sakitRows as $sr) {
                    $tgl = date('d/m', strtotime($sr['tanggal_pemeriksaan']));
                    $diag = !empty($sr['hasil_pemeriksaan']) ? mb_substr(strip_tags($sr['hasil_pemeriksaan']), 0, 60) : '-';
                    $lines[] = "- {$sr['nama']} ({$sr['kelas']}) [{$tgl}]: Keluhan \"{$sr['keluhan']}\", Diagnosa: \"{$diag}\"";
                }
                return implode("\n", $lines);
            }

            // ── Intent 9: Santri Aktif / Nonaktif ──
            if (preg_match('/(?:berapa\s+)?santri\s+(?:yang\s+)?aktif/i', $clean)) {
                $db->query("
                    SELECT 
                        SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS aktif,
                        SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) AS nonaktif
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                ");
                $res = $db->single();
                $aktif = (int)($res['aktif'] ?? 0);
                $nonaktif = (int)($res['nonaktif'] ?? 0);
                return "Saat ini terdapat {$aktif} santri dengan status aktif di YukSehat (dan {$nonaktif} santri tidak aktif).";
            }

            if (preg_match('/santri\s+(?:nonaktif|tidak\s+aktif)/i', $clean) || preg_match('/berapa\s+santri\s+tidak\s+aktif/i', $clean)) {
                $db->query("
                    SELECT COUNT(*) AS nonaktif 
                    FROM santri s 
                    JOIN users u ON s.user_id = u.id 
                    WHERE u.status = 'inactive'
                ");
                $nonaktif = (int)($db->single()['nonaktif'] ?? 0);
                return "Saat ini terdapat {$nonaktif} santri dengan status tidak aktif.";
            }

            // ── Intent 9B: Santri dengan Alergi & Riwayat Penyakit ──
            if (preg_match('/santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?alergi/i', $clean) || preg_match('/daftar\s+alergi/i', $clean)) {
                $db->query("SELECT nis, nama, kelas, alergi FROM santri WHERE alergi IS NOT NULL AND TRIM(alergi) != '' AND alergi != '-'");
                $alRows = $db->resultSet();
                if (empty($alRows)) {
                    return "Saat ini tidak ada data santri yang tercatat memiliki riwayat alergi di sistem.";
                }
                $lines = ["Daftar santri dengan riwayat alergi:"];
                foreach ($alRows as $al) {
                    $lines[] = "- {$al['nama']} ({$al['kelas']}): {$al['alergi']}";
                }
                return implode("\n", $lines);
            }

            if (preg_match('/santri\s+(?:yang\s+)?(?:punya\s+|memiliki\s+)?riwayat\s+penyakit/i', $clean) || preg_match('/daftar\s+riwayat\s+penyakit/i', $clean)) {
                $db->query("SELECT nis, nama, kelas, riwayat_penyakit FROM santri WHERE riwayat_penyakit IS NOT NULL AND TRIM(riwayat_penyakit) != '' AND riwayat_penyakit != '-'");
                $rpRows = $db->resultSet();
                if (empty($rpRows)) {
                    return "Saat ini tidak ada data santri yang tercatat memiliki riwayat penyakit khusus di sistem.";
                }
                $lines = ["Daftar santri dengan riwayat penyakit:"];
                foreach ($rpRows as $rp) {
                    $lines[] = "- {$rp['nama']} ({$rp['kelas']}): {$rp['riwayat_penyakit']}";
                }
                return implode("\n", $lines);
            }

            // ── Intent 9C: Daftar Seluruh Nama Santri Terdaftar (Khusus Admin & Pengurus) ──
            if (
                preg_match('/(?:siapa|daftar|sebutkan|tampilkan)\s+(?:saja\s+|aja\s+)?(?:nama(?:nya)?|santri(?:nya)?|orang(?:nya)?)/i', $clean) ||
                preg_match('/(?:nama(?:nya)?|santri(?:nya)?)\s+siapa\s+(?:saja|aja)/i', $clean) ||
                preg_match('/daftar\s+(?:nama\s+)?santri/i', $clean) ||
                preg_match('/nama(?:\s*-\s*nama|\s+nama)?\s+santri/i', $clean) ||
                preg_match('/siapa\s+(?:saja\s+|aja\s+)?(?:santri\s+)?(?:yang\s+)?terdaftar/i', $clean) ||
                preg_match('/siapa\s+santrinya/i', $clean) ||
                preg_match('/siapa\s+namanya/i', $clean)
            ) {
                $db->query("
                    SELECT s.nis, s.nama, s.jenis_kelamin, s.kelas, s.kamar, u.status
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                    ORDER BY s.nama ASC
                ");
                $santriRows = $db->resultSet();
                $totalSantri = count($santriRows);
                if ($totalSantri === 0) {
                    return "Belum ada data santri yang terdaftar di YukSehat.";
                }

                $lines = ["Daftar santri terdaftar di YukSehat ({$totalSantri} santri):"];
                $no = 1;
                foreach ($santriRows as $sr) {
                    $kls = !empty($sr['kelas']) ? "Kelas {$sr['kelas']}" : "Tanpa Kelas";
                    $kmr = !empty($sr['kamar']) ? ", Kamar {$sr['kamar']}" : "";
                    $status = ($sr['status'] === 'active') ? 'Aktif' : 'Nonaktif';
                    $lines[] = "{$no}. {$sr['nama']} (NIS: {$sr['nis']}) - {$kls}{$kmr} [{$status}]";
                    $no++;
                }
                return implode("\n", $lines);
            }

            // ── Intent 10: Santri Kelas Tertentu ──
            if (
                preg_match('/kelas\s+([a-z0-9\s-]+?)(?:\s+ada\s+berapa|\s+berapa|$)/i', $clean, $m) ||
                preg_match('/(?:berapa\s+(?:jumlah\s+)?santri\s+kelas\s+)([a-z0-9\s-]+)/i', $clean, $m)
            ) {
                $kelasTarget = trim($m[1]);
                $db->query("SELECT DISTINCT kelas FROM santri WHERE kelas IS NOT NULL AND kelas != ''");
                $classList = $db->resultSet();
                
                $matchedClass = null;
                foreach ($classList as $c) {
                    if (strcasecmp($c['kelas'], $kelasTarget) === 0 || 
                        strcasecmp(str_replace(' ', '', $c['kelas']), str_replace(' ', '', $kelasTarget)) === 0) {
                        $matchedClass = $c['kelas'];
                        break;
                    }
                }

                if ($matchedClass) {
                    $db->query("
                        SELECT 
                            COUNT(*) AS total,
                            SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS aktif,
                            SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) AS inaktif
                        FROM santri s
                        JOIN users u ON s.user_id = u.id
                        WHERE s.kelas = :kelas
                    ");
                    $db->bind('kelas', $matchedClass);
                    $cRes = $db->single();
                    $tot = (int)($cRes['total'] ?? 0);
                    $ak = (int)($cRes['aktif'] ?? 0);
                    $in = (int)($cRes['inaktif'] ?? 0);

                    if ($in > 0) {
                        return "Jumlah santri di kelas {$matchedClass} adalah {$tot} orang ({$ak} aktif, {$in} tidak aktif).";
                    }
                    return "Jumlah santri di kelas {$matchedClass} adalah {$tot} orang.";
                } else {
                    return "Tidak ditemukan santri yang terdaftar di kelas " . htmlspecialchars($kelasTarget) . ".";
                }
            }

            // ── Intent 11: Santri Berdasarkan Seluruh Kelas (Rekap Kelas) ──
            if (
                preg_match('/santri\s+(?:berdasarkan|per)\s+kelas/i', $clean) || 
                preg_match('/jumlah\s+santri\s+(?:di\s+)?(?:setiap|tiap)\s+kelas/i', $clean) ||
                preg_match('/(?:rekap|statistik|rincian)\s+(?:santri\s+)?kelas/i', $clean) ||
                preg_match('/rekap\s+(?:data\s+)?santri/i', $clean) ||
                preg_match('/statistik\s+(?:data\s+)?santri/i', $clean) ||
                preg_match('/rekapitulasi\s+santri/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COALESCE(NULLIF(TRIM(s.kelas), ''), 'Tanpa Kelas') AS kelas,
                        COUNT(s.id) AS total
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                    GROUP BY COALESCE(NULLIF(TRIM(s.kelas), ''), 'Tanpa Kelas')
                    ORDER BY kelas ASC
                ");
                $rows = $db->resultSet();
                if (empty($rows)) {
                    return "Belum ada data pembagian kelas santri.";
                }

                $lines = ["Jumlah santri berdasarkan kelas:"];
                $grandTotal = 0;
                foreach ($rows as $r) {
                    $lines[] = "- Kelas {$r['kelas']}: {$r['total']} santri";
                    $grandTotal += (int)$r['total'];
                }
                $lines[] = "Total: {$grandTotal} santri terdaftar.";
                return implode("\n", $lines);
            }

            // ── Intent 12: Santri Berdasarkan Kamar ──
            if (
                preg_match('/santri\s+(?:berdasarkan|per)\s+kamar/i', $clean) ||
                preg_match('/jumlah\s+santri\s+(?:di\s+)?(?:setiap|tiap)\s+kamar/i', $clean) ||
                preg_match('/(?:rekap|statistik|rincian)\s+(?:santri\s+)?kamar/i', $clean) ||
                preg_match('/santri\s+(?:di\s+)?(?:setiap|tiap)\s+kamar/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COALESCE(NULLIF(TRIM(s.kamar), ''), 'Tanpa Kamar') AS kamar,
                        COUNT(s.id) AS total
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                    GROUP BY COALESCE(NULLIF(TRIM(s.kamar), ''), 'Tanpa Kamar')
                    ORDER BY kamar ASC
                ");
                $rows = $db->resultSet();
                if (empty($rows)) {
                    return "Belum ada data pembagian kamar santri.";
                }

                $lines = ["Jumlah santri berdasarkan kamar:"];
                $grandTotal = 0;
                foreach ($rows as $r) {
                    $lines[] = "- Kamar {$r['kamar']}: {$r['total']} santri";
                    $grandTotal += (int)$r['total'];
                }
                $lines[] = "Total: {$grandTotal} santri terdaftar.";
                return implode("\n", $lines);
            }

            // ── Intent 13: Total Santri (Konsisten dengan Metrik Dashboard) ──
            if (
                preg_match('/(?:total|jumlah)\s+santri/i', $clean) || 
                preg_match('/(?:ada\s+)?berapa\s+santri(?:\s+terdaftar)?/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COUNT(*) AS total_semua,
                        SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) AS total_aktif,
                        SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) AS total_nonaktif
                    FROM santri s
                    JOIN users u ON s.user_id = u.id
                ");
                $sRes = $db->single();
                $semua = (int)($sRes['total_semua'] ?? 0);
                $aktif = (int)($sRes['total_aktif'] ?? 0);
                $nonaktif = (int)($sRes['total_nonaktif'] ?? 0);

                if ($nonaktif > 0) {
                    return "Total santri terdaftar saat ini adalah {$semua} orang ({$aktif} santri aktif dan {$nonaktif} tidak aktif).";
                }
                return "Total santri terdaftar saat ini adalah {$semua} orang (seluruhnya aktif).";
            }

            // ── Intent 14: Rekap Pemeriksaan Bulan Ini & Statistik Lengkap ──
            if (
                preg_match('/rekap\s+(?:data\s+)?pemeriksaan/i', $clean) ||
                preg_match('/ringkasan\s+pemeriksaan/i', $clean) ||
                preg_match('/statistik\s+(?:data\s+)?pemeriksaan/i', $clean) ||
                preg_match('/rekapitulasi\s+pemeriksaan/i', $clean)
            ) {
                $currentMonth = date('Y-m');
                $namaBulan = self::formatBulanTahun(date('Y'), date('n'));

                $db->query("
                    SELECT 
                        COUNT(*) AS total,
                        SUM(CASE WHEN LOWER(tindakan) LIKE '%rawat%' OR LOWER(tindakan) LIKE '%uks%' OR LOWER(tindakan) LIKE '%inap%' OR LOWER(catatan) LIKE '%rawat%' OR LOWER(catatan) LIKE '%uks%' THEN 1 ELSE 0 END) AS rawat_inap,
                        SUM(CASE WHEN LOWER(tindakan) LIKE '%rujuk%' OR LOWER(catatan) LIKE '%rujuk%' OR LOWER(hasil_pemeriksaan) LIKE '%rujuk%' THEN 1 ELSE 0 END) AS rujukan
                    FROM pemeriksaan_kesehatan 
                    WHERE DATE_FORMAT(tanggal_pemeriksaan, '%Y-%m') = :bulan
                ");
                $db->bind('bulan', $currentMonth);
                $res = $db->single();

                $db->query("SELECT COUNT(*) AS grand_total FROM pemeriksaan_kesehatan");
                $grandTotal = (int)($db->single()['grand_total'] ?? 0);

                $totalBulanIni = (int)($res['total'] ?? 0);
                $rawat = (int)($res['rawat_inap'] ?? 0);
                $rujuk = (int)($res['rujukan'] ?? 0);

                $lines = [
                    "Rekap pemeriksaan kesehatan bulan ini ({$namaBulan}):",
                    "- Pemeriksaan bulan ini: {$totalBulanIni} pemeriksaan",
                    "- Observasi / rawat UKS: {$rawat} santri",
                    "- Rekomendasi rujukan faskes: {$rujuk} santri",
                    "Total: {$grandTotal} riwayat pemeriksaan tercatat di sistem."
                ];
                return implode("\n", $lines);
            }

            // ── Intent 15: Pemeriksaan Bulan Ini ──
            if (
                preg_match('/(?:berapa\s+)?pemeriksaan\s+bulan\s+ini/i', $clean) ||
                preg_match('/jumlah\s+pemeriksaan\s+bulan\s+ini/i', $clean) ||
                preg_match('/berapa\s+(?:yang\s+)?periksa\s+bulan\s+ini/i', $clean)
            ) {
                $currentMonth = date('Y-m');
                $namaBulan = self::formatBulanTahun(date('Y'), date('n'));

                $db->query("
                    SELECT COUNT(*) AS total 
                    FROM pemeriksaan_kesehatan 
                    WHERE DATE_FORMAT(tanggal_pemeriksaan, '%Y-%m') = :bulan
                ");
                $db->bind('bulan', $currentMonth);
                $total = (int)($db->single()['total'] ?? 0);
                return "Bulan ini ({$namaBulan}) terdapat {$total} pemeriksaan kesehatan.";
            }

            // ── Intent 16: Kapan Pemeriksaan Terakhir Dilakukan ──
            if (
                preg_match('/kapan\s+pemeriksaan\s+(?:kesehatan\s+)?terakhir/i', $clean) ||
                preg_match('/pemeriksaan\s+(?:kesehatan\s+)?terakhir/i', $clean) ||
                preg_match('/tanggal\s+pemeriksaan\s+terakhir/i', $clean) ||
                preg_match('/siapa\s+(?:santri\s+)?(?:yang\s+)?terakhir\s+(?:di)?periksa/i', $clean)
            ) {
                $db->query("
                    SELECT p.tanggal_pemeriksaan, s.nama, s.kelas, p.keluhan, p.hasil_pemeriksaan, p.obat
                    FROM pemeriksaan_kesehatan p
                    JOIN santri s ON p.santri_id = s.id
                    ORDER BY p.tanggal_pemeriksaan DESC 
                    LIMIT 1
                ");
                $row = $db->single();
                if ($row && !empty($row['tanggal_pemeriksaan'])) {
                    $formattedDate = self::formatTanggalWaktu($row['tanggal_pemeriksaan']);
                    $diag = !empty($row['hasil_pemeriksaan']) ? mb_substr(strip_tags($row['hasil_pemeriksaan']), 0, 70) : '-';
                    $obt = !empty($row['obat']) ? " | Obat: {$row['obat']}" : "";
                    return "Pemeriksaan kesehatan terakhir dilakukan pada {$formattedDate} WIB (Santri: {$row['nama']} kelas {$row['kelas']}, keluhan: \"{$row['keluhan']}\", diagnosa: \"{$diag}\"{$obt}).";
                } else {
                    return "Belum ada riwayat pemeriksaan kesehatan yang tercatat di sistem.";
                }
            }

            // ── Intent 17: Total Pemeriksaan Keseluruhan / Selesai Dicatat ──
            if (
                preg_match('/total\s+pemeriksaan/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+(?:jumlah\s+|total\s+)?seluruh\s+pemeriksaan/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?(?:selesai\s+)?(?:di)?catat/i', $clean) ||
                preg_match('/(?:ada\s+)?berapa\s+pemeriksaan\s+(?:yang\s+)?(?:sudah\s+)?selesai/i', $clean)
            ) {
                $db->query("SELECT COUNT(*) AS total FROM pemeriksaan_kesehatan");
                $total = (int)($db->single()['total'] ?? 0);
                return "Total pemeriksaan kesehatan yang sudah selesai dicatat di sistem adalah {$total} pemeriksaan.";
            }

            // ── Intent 18: Pengurus Poskestren ──
            if (
                preg_match('/(?:daftar|siapa|berapa)\s+(?:pengurus|petugas\s+poskestren)/i', $clean) ||
                preg_match('/pengurus\s+poskestren/i', $clean)
            ) {
                $db->query("
                    SELECT p.nama, p.jabatan, p.no_hp, u.status 
                    FROM pengurus p 
                    JOIN users u ON p.user_id = u.id 
                    ORDER BY p.nama ASC
                ");
                $pengurusRows = $db->resultSet();
                $cnt = count($pengurusRows);
                if ($cnt === 0) {
                    return "Belum ada data pengurus Poskestren yang terdaftar di sistem.";
                }
                $lines = ["Daftar pengurus Poskestren YukSehat ({$cnt} pengurus terdaftar):"];
                foreach ($pengurusRows as $p) {
                    $st = $p['status'] === 'active' ? 'Aktif' : 'Nonaktif';
                    $hp = !empty($p['no_hp']) ? " (No. HP: {$p['no_hp']})" : "";
                    $lines[] = "- {$p['nama']} - Jabatan: {$p['jabatan']}{$hp} [Status: {$st}]";
                }
                return implode("\n", $lines);
            }

            // ── Intent 19: Users / Akun Pengguna ──
            if (
                preg_match('/(?:daftar|berapa|total)\s+(?:user|users|pengguna|akun)/i', $clean) ||
                preg_match('/akun\s+(?:admin|pengurus|santri)/i', $clean)
            ) {
                $db->query("
                    SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_cnt,
                        SUM(CASE WHEN role = 'pengurus' THEN 1 ELSE 0 END) as pengurus_cnt,
                        SUM(CASE WHEN role = 'santri' THEN 1 ELSE 0 END) as santri_cnt,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_cnt,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_cnt
                    FROM users
                ");
                $u = $db->single();
                return "Data akun pengguna (Users) YukSehat:
- Total akun: {$u['total_users']} pengguna ({$u['active_cnt']} aktif, {$u['inactive_cnt']} nonaktif)
- Rincian role: {$u['admin_cnt']} Admin, {$u['pengurus_cnt']} Pengurus, {$u['santri_cnt']} Santri.";
            }

            // ── Intent 20: Daftar Obat yang Pernah Diberikan ──
            if (
                preg_match('/(?:daftar|apa\s+saja)\s+obat/i', $clean) ||
                preg_match('/obat\s+(?:yang\s+)?(?:pernah\s+)?(?:diberikan|digunakan|dipakai)/i', $clean)
            ) {
                $db->query("
                    SELECT DISTINCT obat 
                    FROM pemeriksaan_kesehatan 
                    WHERE obat IS NOT NULL AND TRIM(obat) != ''
                ");
                $obatRows = $db->resultSet();
                $obatList = [];
                foreach ($obatRows as $or) {
                    $items = explode(',', $or['obat']);
                    foreach ($items as $item) {
                        $trimItem = trim($item);
                        if (!empty($trimItem) && !in_array($trimItem, $obatList)) {
                            $obatList[] = $trimItem;
                        }
                    }
                }
                if (empty($obatList)) {
                    return "Belum ada catatan riwayat pemberian obat pada pemeriksaan santri.";
                }
                return "Daftar obat yang tercatat pernah diberikan dalam pemeriksaan kesehatan santri: " . implode(', ', $obatList) . ".";
            }

            // ── Intent 21: Ringkasan / Rekap Seluruh Database ──
            if (
                preg_match('/(?:baca|rekap|ringkasan|seluruh|status|isi)\s+database/i', $clean) ||
                preg_match('/database\s+(?:yuksehat|aplikasi)/i', $clean)
            ) {
                return self::getLiveDatabaseSnapshot();
            }

            // ── Intent 22: Pencarian Santri yang Diizinkan (Directory Lookup) ──
            if (
                preg_match('/(?:cari|informasi|profil)\s+santri\s+([a-z0-9\s]+)/i', $clean, $m)
            ) {
                $searchTarget = trim($m[1]);
                // Abaikan keyword umum
                if (in_array($searchTarget, ['aktif', 'nonaktif', 'terbanyak', 'kelas', 'kamar', 'semua', 'total'])) {
                    // Teruskan ke query umum
                } else {
                    $db->query("
                        SELECT s.id, s.nis, s.nama, s.jenis_kelamin, s.kelas, s.kamar, s.no_hp, u.status
                        FROM santri s
                        JOIN users u ON s.user_id = u.id
                        WHERE s.nama LIKE :kw OR s.nis = :nis
                        ORDER BY s.nama ASC
                        LIMIT 3
                    ");
                    $db->bind('kw', '%' . $searchTarget . '%');
                    $db->bind('nis', $searchTarget);
                    $found = $db->resultSet();

                    if (empty($found)) {
                        return "Santri dengan nama atau NIS \"" . htmlspecialchars($searchTarget) . "\" tidak ditemukan.";
                    }

                    $lines = ["Hasil pencarian santri:"];
                    foreach ($found as $s) {
                        $jk = $s['jenis_kelamin'] === 'P' ? 'Perempuan' : 'Laki-laki';
                        $kls = !empty($s['kelas']) ? $s['kelas'] : 'Belum diatur';
                        $kmr = !empty($s['kamar']) ? $s['kamar'] : 'Belum diatur';
                        $st = $s['status'] === 'active' ? 'Aktif' : 'Tidak Aktif';
                        $lines[] = "- {$s['nama']} (NIS: {$s['nis']}) | {$jk} | Kelas: {$kls} | Kamar: {$kmr} | Status: {$st}";
                    }
                    $lines[] = "Catatan: Rincian catatan medis santri dapat dilihat pada menu Pemeriksaan resmi.";
                    return implode("\n", $lines);
                }
            }

            // Fallback khusus role Admin: Berikan snapshot database aktual
            if ($role === 'admin') {
                return self::getLiveDatabaseSnapshot();
            }

            // Fallback terstruktur jika query data belum dikenali
            return "Data tersebut belum dapat ditampilkan secara otomatis. Anda dapat menanyakan: total santri, santri berkonsultasi hari ini, pemeriksaan bulan ini, kelas/kamar terbanyak, pemeriksaan terakhir, atau data santri yang belum lengkap.";

        } catch (Exception $e) {
            error_log('[ChatbotDataService Error] ' . $e->getMessage());
            return "Maaf, data santri belum dapat diakses saat ini. Silakan coba lagi nanti.";
        }
    }

    /**
     * Menghasilkan ringkasan komprehensif data aktual dari seluruh database YukSehat
     * Digunakan untuk role Admin dan injeksi konteks ke AI
     */
    public static function getLiveDatabaseSnapshot() {
        try {
            $db = new Database();

            // 1. Data Users
            $db->query("SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_cnt,
                SUM(CASE WHEN role = 'pengurus' THEN 1 ELSE 0 END) as pengurus_cnt,
                SUM(CASE WHEN role = 'santri' THEN 1 ELSE 0 END) as santri_cnt,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_cnt,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_cnt
            FROM users");
            $u = $db->single();

            // 2. Data Santri
            $db->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN u.status = 'active' THEN 1 ELSE 0 END) as aktif,
                SUM(CASE WHEN u.status = 'inactive' THEN 1 ELSE 0 END) as inaktif,
                SUM(CASE WHEN s.jenis_kelamin = 'L' THEN 1 ELSE 0 END) as jml_l,
                SUM(CASE WHEN s.jenis_kelamin = 'P' THEN 1 ELSE 0 END) as jml_p
            FROM santri s
            JOIN users u ON s.user_id = u.id");
            $santriStat = $db->single();

            // Santri per kelas
            $db->query("SELECT COALESCE(NULLIF(TRIM(kelas), ''), 'Tanpa Kelas') as kls, COUNT(*) as c FROM santri GROUP BY kls ORDER BY c DESC, kls ASC");
            $kelasRows = $db->resultSet();
            $kelasSummary = [];
            foreach ($kelasRows as $kr) {
                $kelasSummary[] = "{$kr['kls']}: {$kr['c']}";
            }

            // Santri per kamar
            $db->query("SELECT COALESCE(NULLIF(TRIM(kamar), ''), 'Tanpa Kamar') as kmr, COUNT(*) as c FROM santri GROUP BY kmr ORDER BY c DESC, kmr ASC");
            $kamarRows = $db->resultSet();
            $kamarSummary = [];
            foreach ($kamarRows as $km) {
                $kamarSummary[] = "{$km['kmr']}: {$km['c']}";
            }

            // 3. Pengurus
            $db->query("SELECT p.nama, p.jabatan, u.status FROM pengurus p JOIN users u ON p.user_id = u.id ORDER BY p.nama ASC");
            $pengurusRows = $db->resultSet();
            $pengurusSummary = [];
            foreach ($pengurusRows as $pr) {
                $pengurusSummary[] = "{$pr['nama']} ({$pr['jabatan']})";
            }

            // 4. Pemeriksaan Hari Ini
            $db->query("SELECT p.id, p.tanggal_pemeriksaan, s.nama, s.kelas, s.kamar, p.keluhan, p.hasil_pemeriksaan, p.tindakan, p.obat
                FROM pemeriksaan_kesehatan p
                JOIN santri s ON p.santri_id = s.id
                WHERE DATE(p.tanggal_pemeriksaan) = CURDATE()
                ORDER BY p.tanggal_pemeriksaan DESC");
            $todayRows = $db->resultSet();
            $todayCount = count($todayRows);
            $todayDetails = [];
            foreach ($todayRows as $tr) {
                $jam = date('H:i', strtotime($tr['tanggal_pemeriksaan']));
                $diag = !empty($tr['hasil_pemeriksaan']) ? mb_substr(strip_tags($tr['hasil_pemeriksaan']), 0, 80) : '-';
                $obt = !empty($tr['obat']) ? " | Obat: {$tr['obat']}" : "";
                $todayDetails[] = "  * [ID {$tr['id']}] {$tr['nama']} ({$tr['kelas']}, Kamar {$tr['kamar']}) jam {$jam} WIB: Keluhan \"{$tr['keluhan']}\", Diagnosa: \"{$diag}\"{$obt}";
            }

            // 5. Total Pemeriksaan & Statistik
            $db->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN DATE_FORMAT(tanggal_pemeriksaan, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN 1 ELSE 0 END) as bulan_ini,
                SUM(CASE WHEN YEARWEEK(tanggal_pemeriksaan, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END) as minggu_ini,
                SUM(CASE WHEN LOWER(tindakan) LIKE '%rawat%' OR LOWER(tindakan) LIKE '%uks%' OR LOWER(tindakan) LIKE '%inap%' OR LOWER(catatan) LIKE '%rawat%' OR LOWER(catatan) LIKE '%uks%' THEN 1 ELSE 0 END) as rawat_inap,
                SUM(CASE WHEN LOWER(tindakan) LIKE '%rujuk%' OR LOWER(catatan) LIKE '%rujuk%' OR LOWER(hasil_pemeriksaan) LIKE '%rujuk%' THEN 1 ELSE 0 END) as rujukan
            FROM pemeriksaan_kesehatan");
            $pemStat = $db->single();

            // Pemeriksaan terakhir
            $db->query("SELECT p.tanggal_pemeriksaan, s.nama, s.kelas, p.keluhan, p.hasil_pemeriksaan, p.obat
                FROM pemeriksaan_kesehatan p
                JOIN santri s ON p.santri_id = s.id
                ORDER BY p.tanggal_pemeriksaan DESC LIMIT 1");
            $lastPem = $db->single();
            $lastPemStr = $lastPem ? self::formatTanggalWaktu($lastPem['tanggal_pemeriksaan']) . " WIB oleh santri {$lastPem['nama']} ({$lastPem['kelas']}), keluhan: \"{$lastPem['keluhan']}\"" : "Belum ada";

            // 6. Keluhan
            $db->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN DATE(tanggal_keluhan) = CURDATE() THEN 1 ELSE 0 END) as hari_ini
            FROM riwayat_keluhan");
            $kelStat = $db->single();

            $todayStr = date('d') . ' ' . self::$bulanIndo[(int)date('n')] . ' ' . date('Y');

            $lines = [];
            $lines[] = "RINGKASAN DATABASE AKTUAL YUKSEHAT (Hak Akses Penuh Role Admin):";
            $lines[] = "• Tanggal Hari Ini: {$todayStr}";
            $lines[] = "• Pemeriksaan Hari Ini: {$todayCount} pemeriksaan";
            if ($todayCount > 0) {
                foreach ($todayDetails as $td) {
                    $lines[] = $td;
                }
            }
            $lines[] = "• Rekap Pemeriksaan: Minggu Ini: " . (int)$pemStat['minggu_ini'] . " | Bulan Ini: " . (int)$pemStat['bulan_ini'] . " | Total Seluruh: " . (int)$pemStat['total'] . " (Rawat UKS: " . (int)$pemStat['rawat_inap'] . ", Rujukan: " . (int)$pemStat['rujukan'] . ")";

            // Santri perlu rujukan
            $db->query("SELECT DISTINCT s.nama, s.kelas, p.keluhan FROM pemeriksaan_kesehatan p JOIN santri s ON p.santri_id = s.id WHERE LOWER(p.tindakan) LIKE '%rujuk%' OR LOWER(p.catatan) LIKE '%rujuk%' OR LOWER(p.hasil_pemeriksaan) LIKE '%rujuk%' ORDER BY p.tanggal_pemeriksaan DESC");
            $rujukRowsSnapshot = $db->resultSet();
            if (!empty($rujukRowsSnapshot)) {
                $rList = [];
                foreach ($rujukRowsSnapshot as $rr) {
                    $rList[] = "{$rr['nama']} ({$rr['kelas']})";
                }
                $lines[] = "• Santri Perlu Rujukan: " . implode(', ', array_unique($rList));
            }
            $lines[] = "• Pemeriksaan Terakhir: {$lastPemStr}";
            $lines[] = "• Data Santri: Total {$santriStat['total']} santri ({$santriStat['aktif']} aktif, {$santriStat['inaktif']} nonaktif, {$santriStat['jml_l']} Laki-laki, {$santriStat['jml_p']} Perempuan)";
            $lines[] = "  - Kelas: " . implode(', ', $kelasSummary);
            $lines[] = "  - Kamar: " . implode(', ', $kamarSummary);

            // Santri terdaftar (nama, kelas, status)
            $db->query("SELECT s.nama, s.kelas, u.status FROM santri s JOIN users u ON s.user_id = u.id ORDER BY s.nama ASC");
            $allSantriRows = $db->resultSet();
            $santriListShort = [];
            foreach ($allSantriRows as $as) {
                $st = ($as['status'] === 'active') ? 'aktif' : 'nonaktif';
                $kls = !empty($as['kelas']) ? " ({$as['kelas']})" : "";
                $santriListShort[] = "{$as['nama']}{$kls} [{$st}]";
            }
            if (!empty($santriListShort)) {
                $lines[] = "• Daftar Santri Terdaftar: " . implode(', ', $santriListShort);
            }
            $lines[] = "• Antrean Keluhan: Total {$kelStat['total']} (" . (int)$kelStat['menunggu'] . " menunggu, " . (int)$kelStat['hari_ini'] . " keluhan hari ini)";
            $lines[] = "• Data Pengguna (Users): Total " . (int)$u['total_users'] . " akun (" . (int)$u['admin_cnt'] . " Admin, " . (int)$u['pengurus_cnt'] . " Pengurus, " . (int)$u['santri_cnt'] . " Santri)";
            $lines[] = "• Pengurus Poskestren: " . implode(', ', $pengurusSummary);

            return implode("\n", $lines);

        } catch (Exception $e) {
            return "Gagal memuat snapshot database: " . $e->getMessage();
        }
    }

    private static function formatBulanTahun($tahun, $bulanAngka) {
        $namaBulan = self::$bulanIndo[(int)$bulanAngka] ?? 'Bulan ' . $bulanAngka;
        return "{$namaBulan} {$tahun}";
    }

    private static function formatTanggalWaktu($datetimeStr) {
        $ts = strtotime($datetimeStr);
        if (!$ts) return $datetimeStr;

        $hari = date('j', $ts);
        $bulanNum = (int)date('n', $ts);
        $namaBulan = self::$bulanIndo[$bulanNum] ?? date('M', $ts);
        $tahun = date('Y', $ts);
        $jam = date('H:i', $ts);

        return "{$hari} {$namaBulan} {$tahun} pukul {$jam}";
    }
}
