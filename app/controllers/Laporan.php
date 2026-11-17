<?php

class Laporan extends Controller {

    public function __construct() {
        AuthHelper::requireLogin();
        // Santri tidak diizinkan mengakses laporan
        AuthHelper::requireRole(['admin', 'pengurus']);
    }

    public function index() {
        $user = AuthHelper::user();
        $laporanModel = $this->model('Laporan_model');
        
        // Ambil Opsi Filter (Kelas, Kamar)
        $data['filter_options'] = $laporanModel->getFilterOptions();

        // Tangkap parameter filter GET
        $filters = [
            'tgl_mulai' => isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01'), // Default 1st of month
            'tgl_akhir' => isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t'),  // Default end of month
            'nama_santri' => isset($_GET['nama_santri']) ? $_GET['nama_santri'] : '',
            'kelas' => isset($_GET['kelas']) ? $_GET['kelas'] : '',
            'kamar' => isset($_GET['kamar']) ? $_GET['kamar'] : '',
            'jenis_kelamin' => isset($_GET['jenis_kelamin']) ? $_GET['jenis_kelamin'] : '',
            'keluhan' => isset($_GET['keluhan']) ? $_GET['keluhan'] : ''
        ];

        $data['filters'] = $filters;
        $data['laporan_data'] = $laporanModel->getLaporanData($filters);

        $data['role'] = $user['role'];
        $data['username'] = $user['nama'];
        $data['title'] = 'Laporan Kesehatan';
        $data['current_page'] = 'laporan';
        $data['breadcrumb'] = ['Home' => BASEURL, 'Laporan Kesehatan' => '#'];

        $this->view('templates/dash_header', $data);
        $this->view('laporan/index', $data);
        $this->view('templates/dash_footer');
    }

    public function export() {
        // Tangkap parameter filter GET (sama persis dengan index)
        $filters = [
            'tgl_mulai' => isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : date('Y-m-01'),
            'tgl_akhir' => isset($_GET['tgl_akhir']) ? $_GET['tgl_akhir'] : date('Y-m-t'),
            'nama_santri' => isset($_GET['nama_santri']) ? $_GET['nama_santri'] : '',
            'kelas' => isset($_GET['kelas']) ? $_GET['kelas'] : '',
            'kamar' => isset($_GET['kamar']) ? $_GET['kamar'] : '',
            'jenis_kelamin' => isset($_GET['jenis_kelamin']) ? $_GET['jenis_kelamin'] : '',
            'keluhan' => isset($_GET['keluhan']) ? $_GET['keluhan'] : ''
        ];

        $laporanModel = $this->model('Laporan_model');
        $data = $laporanModel->getLaporanData($filters);

        $tglMulaiFormatted = date('d/m/Y', strtotime($filters['tgl_mulai']));
        $tglAkhirFormatted = date('d/m/Y', strtotime($filters['tgl_akhir']));
        $tglUnduh = date('d/m/Y H:i');
        $totalData = count($data);

        // ==========================================
        // SHEET 1: RINGKASAN LAPORAN PEMERIKSAAN
        // ==========================================
        $summaryData = [
            ['<style font-size="14" color="#1E3A8A"><b>LAPORAN PEMERIKSAAN KESEHATAN SANTRI — YUKSEHAT</b></style>'],
            ['<b>Periode Pemeriksaan:</b> ' . $tglMulaiFormatted . ' s.d. ' . $tglAkhirFormatted . '  |  <b>Tanggal Unduh:</b> ' . $tglUnduh . ' WIB  |  <b>Total Pemeriksaan:</b> ' . $totalData],
            ['<i>Catatan: Kolom hasil pemeriksaan dan tindakan diringkas profesional. Detail teks klinis lengkap tersedia pada sheet "Detail Catatan Klinis".</i>'],
            [], // Baris kosong pemisah
            [
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>NO</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>NIS</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>NAMA SANTRI</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>KELAS</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>KAMAR</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>TGL PERIKSA</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>KELUHAN</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>SUHU</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>TEKANAN DARAH</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>BERAT</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>TINGGI</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>RINGKASAN HASIL &amp; TINJAUAN KLINIS</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>RINGKASAN TINDAKAN</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>OBAT</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>CATATAN / TANDA BAHAYA</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>PEMERIKSA</left></b></style>'
            ]
        ];

        $no = 1;
        foreach ($data as $row) {
            $summaryData[] = [
                '<center><top>' . $no++ . '</top></center>',
                '<center><top>' . $row['nis'] . '</top></center>',
                '<top>' . $row['nama_santri'] . '</top>',
                '<center><top>' . ($row['kelas'] ?: '—') . '</top></center>',
                '<center><top>' . ($row['kamar'] ?: '—') . '</top></center>',
                '<center><top>' . date('d/m/Y H:i', strtotime($row['tanggal_pemeriksaan'])) . '</top></center>',
                '<wraptext><top>' . ($row['keluhan'] ?: '—') . '</top></wraptext>',
                '<center><top>' . ($row['suhu'] ? $row['suhu'] . ' °C' : '—') . '</top></center>',
                '<center><top>' . ($row['tekanan_darah'] ?: '—') . '</top></center>',
                '<center><top>' . ($row['berat_badan'] ? $row['berat_badan'] . ' kg' : '—') . '</top></center>',
                '<center><top>' . ($row['tinggi_badan'] ? $row['tinggi_badan'] . ' cm' : '—') . '</top></center>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelText($row['hasil_pemeriksaan'], 'hasil', 200) . '</top></wraptext>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelText($row['tindakan'], 'tindakan', 180) . '</top></wraptext>',
                '<wraptext><top>' . ($row['obat'] ?: '—') . '</top></wraptext>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelText($row['catatan'] ?? '', 'catatan', 180) . '</top></wraptext>',
                '<top>' . ($row['nama_pemeriksa'] ?: 'Admin') . '</top>'
            ];
        }

        // Buat instance SimpleXLSXGen dengan Sheet 1
        $xlsx = Shuchkin\SimpleXLSXGen::fromArray($summaryData, 'Ringkasan Laporan');
        $xlsx->setColWidth('A', 6);
        $xlsx->setColWidth('B', 12);
        $xlsx->setColWidth('C', 24);
        $xlsx->setColWidth('D', 10);
        $xlsx->setColWidth('E', 10);
        $xlsx->setColWidth('F', 18);
        $xlsx->setColWidth('G', 22);
        $xlsx->setColWidth('H', 10);
        $xlsx->setColWidth('I', 15);
        $xlsx->setColWidth('J', 10);
        $xlsx->setColWidth('K', 10);
        $xlsx->setColWidth('L', 38);
        $xlsx->setColWidth('M', 32);
        $xlsx->setColWidth('N', 18);
        $xlsx->setColWidth('O', 25);
        $xlsx->setColWidth('P', 18);
        $xlsx->freezePanes('A6');
        $xlsx->autoFilter('A5:P5');

        // ==========================================
        // SHEET 2: DETAIL CATATAN KLINIS LENGKAP
        // ==========================================
        $detailData = [
            ['<style font-size="14" color="#1E3A8A"><b>DETAIL REKAM MEDIS &amp; CATATAN KLINIS LENGKAP — YUKSEHAT</b></style>'],
            ['<b>Periode:</b> ' . $tglMulaiFormatted . ' s.d. ' . $tglAkhirFormatted . '  |  <i>Menyimpan teks klinis lengkap (catatan petugas & draf analisis AI) tanpa pemotongan untuk audit medis.</i>'],
            [], // Baris kosong pemisah
            [
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>NO</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>NIS</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>NAMA SANTRI</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><center>TGL PERIKSA</center></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>KELUHAN LENGKAP</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>HASIL PEMERIKSAAN / TINJAUAN KLINIS LENGKAP</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>TINDAKAN MEDIS LENGKAP</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>OBAT</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>CATATAN &amp; TANDA BAHAYA LENGKAP</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>TIPE CATATAN</left></b></style>',
                '<style bgcolor="#1E40AF" color="#FFFFFF"><b><left>PEMERIKSA</left></b></style>'
            ]
        ];

        $noDetail = 1;
        foreach ($data as $row) {
            $isAi = MedicalFormatHelper::isAiDraft($row['hasil_pemeriksaan']);
            $detailData[] = [
                '<center><top>' . $noDetail++ . '</top></center>',
                '<center><top>' . $row['nis'] . '</top></center>',
                '<top>' . $row['nama_santri'] . '</top>',
                '<center><top>' . date('d/m/Y H:i', strtotime($row['tanggal_pemeriksaan'])) . '</top></center>',
                '<wraptext><top>' . ($row['keluhan'] ?: '—') . '</top></wraptext>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelFullText($row['hasil_pemeriksaan']) . '</top></wraptext>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelFullText($row['tindakan']) . '</top></wraptext>',
                '<wraptext><top>' . ($row['obat'] ?: '—') . '</top></wraptext>',
                '<wraptext><top>' . MedicalFormatHelper::formatExcelFullText($row['catatan'] ?? '') . '</top></wraptext>',
                '<top>' . ($isAi ? 'Draf Rekomendasi AI' : 'Catatan Petugas') . '</top>',
                '<top>' . ($row['nama_pemeriksa'] ?: 'Admin') . '</top>'
            ];
        }

        $xlsx->addSheet($detailData, 'Detail Catatan Klinis');
        $xlsx->setColWidth('A', 6);
        $xlsx->setColWidth('B', 12);
        $xlsx->setColWidth('C', 24);
        $xlsx->setColWidth('D', 18);
        $xlsx->setColWidth('E', 24);
        $xlsx->setColWidth('F', 48);
        $xlsx->setColWidth('G', 42);
        $xlsx->setColWidth('H', 18);
        $xlsx->setColWidth('I', 32);
        $xlsx->setColWidth('J', 22);
        $xlsx->setColWidth('K', 18);
        $xlsx->freezePanes('A5');
        $xlsx->autoFilter('A4:K4');

        $filename = 'laporan-kesehatan-' . date('Y-m-d') . '.xlsx';
        
        // Memicu unduhan file excel
        $xlsx->downloadAs($filename);
        exit;
    }
}
