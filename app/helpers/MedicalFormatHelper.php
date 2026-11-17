<?php

class MedicalFormatHelper {

    /**
     * Memeriksa apakah teks hasil pemeriksaan atau tindakan memuat draf dari AI
     */
    public static function isAiDraft($text) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') return false;
        return (
            preg_match('/\[(?:Draf|Saran|Catatan)[^\]]*AI[^\]]*\]/i', $text) ||
            stripos($text, 'Draf Rekomendasi') !== false ||
            stripos($text, 'Draf Tinjauan') !== false ||
            stripos($text, '### 1.') !== false ||
            stripos($text, '### 2.') !== false ||
            stripos($text, '### 3.') !== false ||
            stripos($text, '### 4.') !== false
        );
    }

    /**
     * Membersihkan entitas HTML & spasi berlebih
     */
    public static function cleanEntities($text) {
        if (empty($text)) return '';
        return trim(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    /**
     * Truncate teks secara aman pada batas kata, tanpa memotong kata sembarangan
     */
    public static function truncateWord($text, $maxLength = 200, $append = '...') {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        $sub = mb_substr($text, 0, $maxLength);
        $lastSpace = mb_strrpos($sub, ' ');
        if ($lastSpace !== false && $lastSpace > ($maxLength * 0.70)) {
            return rtrim(mb_substr($sub, 0, $lastSpace), ',.;- ') . $append;
        }
        return rtrim($sub, ',.;- ') . $append;
    }

    /**
     * Meringkas hasil pemeriksaan klinis untuk pratinjau tabel, laporan, dan ekspor:
     * - Target 1–3 kalimat, idealnya maksimal 200 karakter.
     * - Mengandung inti keluhan, dugaan awal klinis secara hati-hati, dan perlunya evaluasi.
     * - Mempertahankan catatan manual petugas tanpa diubah sembarangan.
     */
    public static function summarizeHasil($text, $maxLength = 200) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') return '—';

        $decoded = self::cleanEntities($text);

        // Jika catatan manual petugas (bukan draf AI)
        if (!self::isAiDraft($decoded)) {
            $clean = preg_replace('/\s+/', ' ', $decoded);
            return self::truncateWord($clean, $maxLength);
        }

        // 1. Bersihkan banner boilerplate AI
        $cleaned = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $cleaned = preg_replace('/Berikut adalah DRAF[^\n]*\n+/i', '', $cleaned);
        $cleaned = preg_replace('/###\s*1\.?[^\n]*\n+/i', '', $cleaned);

        // 2. Buang seksi 2, 3, 4 agar tidak memenuhi ringkasan tabel
        $cleaned = preg_replace('/(?:\n|\A)###\s*[234]\.[\s\S]*$/i', '', $cleaned);
        $cleaned = preg_replace('/(?:\n|\A)Hal yang Perlu Diklarifikasi[\s\S]*$/i', '', $cleaned);
        $cleaned = preg_replace('/(?:\n|\A)Tanda Bahaya[\s\S]*$/i', '', $cleaned);

        // Hapus penanda format markdown tebal / miring
        $plain = preg_replace('/[*_#]/', '', $cleaned);

        // 3. Ekstraksi inti keluhan
        $keluhan = '';
        if (preg_match('/(?:Intisari\s+Keluhan|Keluhan\s+Utama)[^\n:]*:\s*([^\n.]+)/i', $plain, $m)) {
            $k = trim($m[1]);
            // Hilangkan kata pembuka "Santri laki-laki kelas..." jika ada
            $k = preg_replace('/^Santri\s+(?:laki-laki|perempuan)?\s*(?:kelas\s+[^\s]+\s*)?(?:mengeluh(?:kan)?|mengalami)\s*/i', '', $k);
            $keluhan = trim($k);
        }

        // 4. Ekstraksi dugaan awal klinis
        $dugaan = '';
        if (preg_match('/(?:Kemungkinan\s+Kondisi|Dugaan\s+Awal)[^\n:]*:\s*(?:[\r\n]+\s*[-•\d.]+\s*)?([^\n:.]+)/i', $plain, $m)) {
            $d = trim($m[1]);
            $d = preg_replace('/^\d+\.\s*/', '', $d);
            $d = ltrim($d, "- •\t\n\r\0\x0B");
            $dugaan = trim($d);
        }

        // 5. Susun kalimat ringkas padat (1-3 kalimat)
        if ($keluhan && $dugaan) {
            $summary = "Keluhan {$keluhan}. Dugaan awal mengarah pada {$dugaan}, perlu pemeriksaan lanjutan.";
        } elseif ($keluhan) {
            $summary = "Keluhan {$keluhan}. Penyebab belum dapat dipastikan dan perlu pemeriksaan lanjutan.";
        } elseif ($dugaan) {
            $summary = "Dugaan awal mengarah pada {$dugaan}, perlu pemeriksaan lanjutan.";
        } else {
            $summary = preg_replace('/\s+/', ' ', trim($plain));
        }

        $summary = preg_replace('/\s+/', ' ', trim($summary));
        return self::truncateWord($summary, $maxLength);
    }

    /**
     * Meringkas saran tindakan klinis:
     * - Target 1–2 kalimat, idealnya maksimal 180 karakter.
     * - Menampilkan instruksi perawatan utama tanpa uraian panjang.
     */
    public static function summarizeTindakan($text, $maxLength = 180) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') return '—';

        $decoded = self::cleanEntities($text);

        // Jika tindakan manual petugas
        if (!self::isAiDraft($decoded)) {
            $clean = preg_replace('/\s+/', ' ', $decoded);
            return self::truncateWord($clean, $maxLength);
        }

        // Bersihkan banner saran AI
        $cleaned = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $cleaned = preg_replace('/###\s*4\.?[^\n]*\n+/i', '', $cleaned);

        // Ekstraksi 2-3 poin tindakan utama
        $actions = [];
        if (preg_match_all('/[-*]\s*([^:\n]+:\s*[^\n]+|[-*]\s*[^\n]+)/i', $cleaned, $m)) {
            foreach ($m[1] as $act) {
                $actClean = preg_replace('/\*\*(.*?)\*\*/', '$1', $act);
                $actClean = preg_replace('/^\s*[-*+]\s*/', '', $actClean);
                $actClean = preg_replace('/\s+/', ' ', trim($actClean));
                if (preg_match('/^([^:]+):\s*([^.]+)/i', $actClean, $p)) {
                    $actions[] = trim($p[1]) . ': ' . trim($p[2]);
                } else {
                    $actions[] = rtrim(mb_substr($actClean, 0, 50), '.');
                }
                if (count($actions) >= 3) break;
            }
        }

        if (!empty($actions)) {
            $summary = implode('; ', $actions) . '.';
        } else {
            $plain = preg_replace('/[*_#]/', '', $cleaned);
            $summary = preg_replace('/\s+/', ' ', trim($plain));
        }

        $summary = preg_replace('/\s+/', ' ', trim($summary));
        return self::truncateWord($summary, $maxLength);
    }

    /**
     * Meringkas catatan / tanda bahaya:
     * - Hanya informasi penting, terutama tanda bahaya (red flags) dan instruksi rujukan.
     */
    public static function summarizeCatatan($text, $maxLength = 180) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') return '—';

        $decoded = self::cleanEntities($text);

        if (!self::isAiDraft($decoded)) {
            $clean = preg_replace('/\s+/', ' ', $decoded);
            return self::truncateWord($clean, $maxLength);
        }

        $cleaned = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $cleaned = preg_replace('/###\s*3\.?[^\n]*\n+/i', '', $cleaned);

        $redFlags = [];
        if (preg_match_all('/[-*]\s*([^\n]+)/i', $cleaned, $m)) {
            foreach (array_slice($m[1], 0, 2) as $rf) {
                $rfClean = preg_replace('/[*_#]/', '', $rf);
                $rfClean = preg_replace('/\s+/', ' ', trim($rfClean));
                $redFlags[] = rtrim($rfClean, '.');
            }
        }

        if (!empty($redFlags)) {
            $summary = 'Waspadai tanda bahaya: ' . implode(', ', $redFlags) . '. Segera rujuk bila memberat.';
        } else {
            $plain = preg_replace('/[*_#]/', '', $cleaned);
            $summary = preg_replace('/\s+/', ' ', trim($plain));
        }

        $summary = preg_replace('/\s+/', ' ', trim($summary));
        return self::truncateWord($summary, $maxLength);
    }

    /**
     * General clean summary (backward compatibility)
     */
    public static function cleanSummary($text, $maxLength = 160) {
        return self::summarizeHasil($text, $maxLength);
    }

    /**
     * Badge visual "Draf AI — Perlu Verifikasi Petugas"
     */
    public static function renderBadge($text, $isCompact = true) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') return '';

        if (self::isAiDraft($text)) {
            $label = $isCompact ? 'Draf AI — Perlu Verifikasi' : 'Draf AI — Perlu Verifikasi Petugas';
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-flex align-items-center gap-1 mb-1" style="font-size: 10px; font-weight: 600;"><i class="bi bi-robot"></i> ' . $label . '</span>';
        }

        return '';
    }

    /**
     * Render sel tabel untuk Hasil / Diagnosa (dengan ringkasan rapi, line-clamp, dan tautan detail)
     */
    public static function renderTablePreview($text, $detailUrl = null, $maxLength = 200) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '<span class="text-muted">—</span>';
        }

        $badge = self::renderBadge($text, true);
        $summary = self::summarizeHasil($text, $maxLength);
        $safeSummary = htmlspecialchars($summary, ENT_QUOTES, 'UTF-8');

        $html = '<div class="hasil-pemeriksaan-cell">';
        if ($badge) {
            $html .= '<div class="mb-1">' . $badge . '</div>';
        }
        $html .= '<div class="line-clamp-3 small text-secondary" style="line-height: 1.45; word-break: break-word;">' . $safeSummary . '</div>';

        if ($detailUrl) {
            $html .= '<a href="' . htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') . '" class="text-success text-decoration-none fw-semibold small d-inline-flex align-items-center gap-1 mt-1" style="font-size: 11.5px;">';
            $html .= '<span>Lihat Detail</span> <i class="bi bi-arrow-right-short"></i>';
            $html .= '</a>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render sel tabel untuk Tindakan & Obat
     */
    public static function renderTindakanPreview($tindakan, $obat = '', $maxLength = 180) {
        $hasTindakan = !empty($tindakan) && trim($tindakan) !== '-' && trim($tindakan) !== '—';
        $hasObat = !empty($obat) && trim($obat) !== '-' && trim($obat) !== '—';

        if (!$hasTindakan && !$hasObat) {
            return '<span class="text-muted small">—</span>';
        }

        $html = '<div class="tindakan-obat-cell">';

        if ($hasTindakan) {
            $sumTindakan = self::summarizeTindakan($tindakan, $maxLength);
            $safeTindakan = htmlspecialchars($sumTindakan, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="small fw-semibold text-primary mb-1 line-clamp-2" title="' . htmlspecialchars(self::cleanEntities($tindakan), ENT_QUOTES, 'UTF-8') . '">';
            $html .= '<i class="bi bi-bandaid me-1"></i>' . $safeTindakan;
            $html .= '</div>';
        }

        if ($hasObat) {
            $cleanObat = self::cleanEntities($obat);
            $safeObat = htmlspecialchars($cleanObat, ENT_QUOTES, 'UTF-8');
            $html .= '<div class="small text-muted line-clamp-2" title="' . $safeObat . '">';
            $html .= '<i class="bi bi-capsule me-1 text-success"></i>' . $safeObat;
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render lengkap aman untuk halaman detail pemeriksaan (show.php)
     */
    public static function renderFull($text) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '<span class="text-muted">—</span>';
        }

        $decoded = self::cleanEntities($text);
        $isAi = self::isAiDraft($decoded);

        // Hapus token draf AI di awal teks
        $content = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $content = preg_replace('/Berikut adalah DRAF[^\n]*\n+/i', '', $content);

        // Escape aman terhadap XSS
        $safe = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        // Format Markdown Terkontrol
        $safe = preg_replace(
            '/###\s*(.*?)(?:\r?\n|$)/',
            '<h6 class="fw-bold text-success mt-3 mb-2"><i class="bi bi-chevron-right me-1"></i>$1</h6>',
            $safe
        );
        $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-dark">$1</strong>', $safe);
        $safe = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $safe);
        $safe = preg_replace('/^\s*[-*•]\s+(.*)$/m', '<div class="d-flex align-items-start ms-2 mb-1"><i class="bi bi-dot text-success fs-5 me-1 mt-n1"></i><div>$1</div></div>', $safe);
        $safe = nl2br($safe);
        $safe = preg_replace('/<\/h6><br \/>/', '</h6>', $safe);
        $safe = preg_replace('/<\/div><br \/>/', '</div>', $safe);

        $badgeHtml = '';
        if ($isAi) {
            $badgeHtml = '
            <div class="alert alert-warning border border-warning-subtle d-flex align-items-start gap-2 py-2 px-3 mb-3 rounded-3 shadow-sm" style="background-color: #fffbeb;">
                <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1"></i>
                <div class="small">
                    <strong>Draf Rekomendasi AI — Perlu Verifikasi Petugas:</strong><br>
                    <span class="text-muted">Hasil di bawah ini disusun sebagai bahan pertimbangan klinis awal oleh AI. Bukan merupakan diagnosis medis final atau resep obat otomatis. Seluruh tindakan dan keputusan klinis wajib diverifikasi oleh petugas kesehatan pemeriksa.</span>
                </div>
            </div>';
        }

        return $badgeHtml . '<div class="medical-notes-content" style="line-height: 1.6; font-size: 0.95rem;">' . $safe . '</div>';
    }

    /**
     * Render lengkap aman untuk tindakan (show.php)
     */
    public static function renderFullTindakan($text) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '<span class="text-muted">—</span>';
        }

        $decoded = self::cleanEntities($text);
        $isAi = self::isAiDraft($decoded);

        $content = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $safe = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-dark">$1</strong>', $safe);
        $safe = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $safe);
        $safe = preg_replace('/^\s*[-*•]\s+(.*)$/m', '<div class="d-flex align-items-start ms-2 mb-1"><i class="bi bi-check2-circle text-primary me-2 mt-1"></i><div>$1</div></div>', $safe);
        $safe = nl2br($safe);
        $safe = preg_replace('/<\/div><br \/>/', '</div>', $safe);

        $badgeHtml = '';
        if ($isAi) {
            $badgeHtml = '<div class="small text-muted mb-2 fst-italic"><i class="bi bi-info-circle me-1"></i>Saran tindakan awal AI (wajib dikonfirmasi oleh petugas Poskestren):</div>';
        }

        return $badgeHtml . '<div class="tindakan-content" style="line-height: 1.6;">' . $safe . '</div>';
    }

    /**
     * Render lengkap aman untuk catatan tambahan (show.php)
     */
    public static function renderFullCatatan($text) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '<span class="text-muted">—</span>';
        }

        $decoded = self::cleanEntities($text);
        $isAi = self::isAiDraft($decoded);

        $content = preg_replace('/\[(?:Draf|Saran|Catatan)[^\]]*\]:?\s*/i', '', $decoded);
        $safe = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

        $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong class="text-dark">$1</strong>', $safe);
        $safe = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $safe);
        $safe = preg_replace('/^\s*[-*•]\s+(.*)$/m', '<div class="d-flex align-items-start ms-2 mb-1"><i class="bi bi-exclamation-circle text-danger me-2 mt-1"></i><div>$1</div></div>', $safe);
        $safe = nl2br($safe);
        $safe = preg_replace('/<\/div><br \/>/', '</div>', $safe);

        $alertClass = $isAi ? 'alert-warning border-warning-subtle' : 'alert-secondary border-secondary-subtle';
        return '<div class="alert ' . $alertClass . ' mb-0 py-2">' . $safe . '</div>';
    }

    /**
     * Format teks ringkas untuk sel ekspor Excel:
     * - Bersih dari tag markdown dan entitas HTML
     * - Panjang terkontrol, tidak meluber atau memotong kata sembarangan
     */
    public static function formatExcelText($text, $type = 'hasil', $maxLength = 220) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '—';
        }

        if ($type === 'hasil') {
            return self::summarizeHasil($text, $maxLength);
        } elseif ($type === 'tindakan') {
            return self::summarizeTindakan($text, $maxLength);
        } elseif ($type === 'catatan') {
            return self::summarizeCatatan($text, $maxLength);
        }

        $decoded = self::cleanEntities($text);
        $plain = preg_replace('/[*_#]/', '', $decoded);
        $plain = preg_replace('/\s+/', ' ', trim($plain));
        return self::truncateWord($plain, $maxLength);
    }

    /**
     * Format teks lengkap untuk Sheet 2 (Detail Catatan Klinis) pada ekspor Excel:
     * - Bersih dari tag markdown mentah (###, **)
     * - Entitas HTML didecode
     * - Seluruh isi teks dipertahankan secara utuh tanpa pemotongan
     */
    public static function formatExcelFullText($text) {
        if (empty($text) || trim($text) === '-' || trim($text) === '—') {
            return '—';
        }

        $decoded = self::cleanEntities($text);
        
        // Bersihkan token markdown tapi pertahankan baris dan struktur
        $cleaned = preg_replace('/###\s*/', '', $decoded);
        $cleaned = preg_replace('/\*\*(.*?)\*\*/', '$1', $cleaned);
        $cleaned = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '$1', $cleaned);
        
        // Standarisasi baris baru
        $cleaned = str_replace(["\r\n", "\r"], "\n", $cleaned);
        $cleaned = preg_replace('/\n{3,}/', "\n\n", trim($cleaned));
        
        return $cleaned ?: '—';
    }

    /**
     * Helper formatting nilai kosong ke tanda '—'
     */
    public static function formatEmpty($val, $suffix = '') {
        if ($val === null || $val === '' || trim($val) === '-' || trim($val) === '—') {
            return '—';
        }
        return $val . $suffix;
    }

    /**
     * Memisahkan respons analisis AI ke dalam field formulir yang sesuai:
     * - hasil_pemeriksaan: ringkasan keluhan & dugaan awal (+ klarifikasi jika ada)
     * - tindakan: saran tindakan umum di poskestren (diberi label draf konfirmasi petugas)
     * - obat: kosong (petugas yang mencatat obat jika memang diberikan)
     * - catatan: tanda bahaya & kriteria rujukan
     */
    public static function parseClinicalSections($text) {
        if (empty($text)) {
            return [
                'hasil_pemeriksaan' => '',
                'tindakan' => '',
                'obat' => '',
                'catatan' => ''
            ];
        }

        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $sec1 = '';
        $sec2 = '';
        $sec3 = '';
        $sec4 = '';

        // Ekstraksi Section 1 (Ringkasan & Dugaan Awal)
        if (preg_match('/###\s*1\.?[^\n]*\n([\s\S]*?)(?=(?:###\s*2\.|\z))/i', $decoded, $m1)) {
            $sec1 = trim($m1[1]);
        }
        // Ekstraksi Section 2 (Informasi yang Perlu Diklarifikasi)
        if (preg_match('/###\s*2\.?[^\n]*\n([\s\S]*?)(?=(?:###\s*3\.|\z))/i', $decoded, $m2)) {
            $sec2 = trim($m2[1]);
        }
        // Ekstraksi Section 3 (Tanda Bahaya / Red Flags)
        if (preg_match('/###\s*3\.?[^\n]*\n([\s\S]*?)(?=(?:###\s*4\.|\z))/i', $decoded, $m3)) {
            $sec3 = trim($m3[1]);
        }
        // Ekstraksi Section 4 (Saran Tindak Lanjut Umum & Perawatan di Poskestren)
        if (preg_match('/###\s*4\.?[^\n]*\n([\s\S]*)$/i', $decoded, $m4)) {
            $sec4 = trim($m4[1]);
        }

        // Helper membersihkan markdown tebal/miring
        $cleanMd = function($str) {
            if (!$str) return '';
            $str = preg_replace('/\*\*(.*?)\*\*/', '$1', $str);
            $str = preg_replace('/__(.*?)__/', '$1', $str);
            $str = preg_replace('/(?<!\*)\*(?!\*)(.*?)(?<!\*)\*(?!\*)/', '$1', $str);
            return trim($str);
        };

        // 1. Diagnosa / Hasil
        $hasilPemeriksaan = '';
        if ($sec1) {
            $hasilPemeriksaan = "[Draf Tinjauan AI - Perlu Verifikasi]:\n" . $cleanMd($sec1);
            if ($sec2) {
                $hasilPemeriksaan .= "\n\nHal yang Perlu Diklarifikasi:\n" . $cleanMd($sec2);
            }
        } else {
            $hasilPemeriksaan = "[Draf Tinjauan AI - Perlu Verifikasi]:\n" . $cleanMd($decoded);
        }

        // 2. Tindakan: Saran tindakan perawatan umum (wajib berlabel saran konfirmasi)
        $tindakan = '';
        if ($sec4) {
            $tindakan = "[Saran Tindak Lanjut AI - Perlu Konfirmasi Petugas]:\n" . $cleanMd($sec4);
        }

        // 3. Obat: SELALU KOSONG (Petugas mencatat jika obat benar-benar diberikan)
        $obat = '';

        // 4. Catatan Tambahan: Tanda bahaya / kriteria rujukan
        $catatan = '';
        if ($sec3) {
            $catatan = "[Catatan Pemantauan AI]:\n" . $cleanMd($sec3);
        }

        return [
            'hasil_pemeriksaan' => $hasilPemeriksaan,
            'tindakan' => $tindakan,
            'obat' => $obat,
            'catatan' => $catatan
        ];
    }
}
