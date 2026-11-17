<?php $p = $data['item']; ?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-warning p-3 rounded-top-4">
                <h5 class="mb-0 text-dark"><i class="bi bi-pencil me-2"></i>Edit Pemeriksaan –
                    <?= htmlspecialchars($p['nama_santri']) ?></h5>
            </div>
            <div class="card-body p-4">
                <?php SessionHelper::flash('pesan'); ?>

                <form action="<?= BASEURL ?>/pemeriksaan/update/<?= $p['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">

                    <div class="alert alert-info py-2">
                        <i class="bi bi-info-circle me-1"></i>
                        Santri: <strong><?= htmlspecialchars($p['nama_santri']) ?></strong>
                        (NIS: <?= htmlspecialchars($p['nis']) ?>) – tidak dapat diubah.
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-3">Informasi Pemeriksaan</h6>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tanggal & Waktu Pemeriksaan *</label>
                        <input type="datetime-local" name="tanggal_pemeriksaan" class="form-control" required
                               value="<?= date('Y-m-d\TH:i', strtotime($p['tanggal_pemeriksaan'])) ?>">
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4">Data Vital</h6>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Suhu (°C)</label>
                            <input type="number" step="0.1" min="30" max="45"
                                   name="suhu" id="suhuInput" class="form-control" value="<?= htmlspecialchars($p['suhu']) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tekanan Darah</label>
                            <input type="text" name="tekanan_darah" id="tensiInput" class="form-control"
                                   value="<?= htmlspecialchars($p['tekanan_darah']) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Berat (kg)</label>
                            <input type="number" step="0.1" name="berat_badan" id="bbInput" class="form-control"
                                   value="<?= htmlspecialchars($p['berat_badan']) ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Tinggi (cm)</label>
                            <input type="number" step="0.1" name="tinggi_badan" id="tbInput" class="form-control"
                                   value="<?= htmlspecialchars($p['tinggi_badan']) ?>">
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0">Keluhan Santri *</label>
                            <button type="button" id="btnAnalyzeKeluhan" class="btn btn-outline-success btn-sm px-2 py-1 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm" style="font-size: 12px;">
                                <i class="bi bi-stars"></i> Analisis Keluhan dengan AI
                            </button>
                        </div>
                        <textarea name="keluhan" id="keluhanInput" class="form-control" rows="3" required><?= htmlspecialchars($p['keluhan']) ?></textarea>
                    </div>

                    <!-- AI Clinical Analysis Result Container (Initially Hidden) -->
                    <div id="aiAnalysisWrapper" class="d-none mb-4">
                        <div class="card border-0 shadow-sm rounded-3 overflow-hidden" style="border: 1px solid #a7f3d0 !important; background: #f8fafc;">
                            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success bg-opacity-15 text-success fw-bold d-inline-flex align-items-center gap-1" style="font-size: 11px;">
                                        <i class="bi bi-robot"></i> Draf Tinjauan AI Poskestren
                                    </span>
                                    <span class="text-secondary small fw-medium" style="font-size: 11.5px;">Bukan diagnosis final atau resep otomatis</span>
                                </div>
                                <button type="button" class="btn-close" id="btnCloseAnalysis" aria-label="Tutup" style="font-size: 10px;"></button>
                            </div>
                            <div class="card-body p-3">
                                <!-- Loading State -->
                                <div id="aiAnalysisLoading" class="text-center py-3 d-none">
                                    <div class="spinner-border text-success spinner-border-sm mb-2" role="status"></div>
                                    <div class="small text-muted">Sedang menganalisis keluhan dengan Google Gemini AI...</div>
                                </div>
                                <!-- Error Alert -->
                                <div id="aiAnalysisError" class="alert alert-danger py-2 px-3 small d-none mb-2"></div>
                                <!-- Draft Output Area -->
                                <div id="aiAnalysisOutput" class="small text-dark" style="line-height: 1.6;"></div>
                                <!-- Disclaimer Note -->
                                <div class="alert alert-warning py-2 px-3 mt-3 mb-2 small d-flex align-items-start gap-2" style="font-size: 11px; background-color: #fffbeb; border-color: #fde68a;">
                                    <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-1"></i>
                                    <div>
                                        <strong>Catatan Penting:</strong> Draf ini merupakan bahan pertimbangan awal untuk ditinjau petugas kesehatan. Pemeriksaan fisik dan keputusan medis mutlak berada di bawah wewenang pemeriksa. Data rekam medis tidak otomatis tersimpan sebelum Anda menekan tombol "Perbarui Data".
                                    </div>
                                </div>
                                <!-- Optional Action: Salin ke Form -->
                                <div class="d-flex flex-wrap justify-content-end gap-2 mt-2 pt-2 border-top">
                                    <button type="button" id="btnApplyDraft" class="btn btn-success btn-sm px-3 fw-semibold">
                                        <i class="bi bi-clipboard-check me-1"></i> Gunakan Saran ke Formulir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4">Catatan Medis</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Hasil / Diagnosa</label>
                            <textarea name="hasil_pemeriksaan" id="hasilPemeriksaanInput" class="form-control" rows="3"><?= htmlspecialchars($p['hasil_pemeriksaan']) ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Tindakan</label>
                            <textarea name="tindakan" id="tindakanInput" class="form-control" rows="3"><?= htmlspecialchars($p['tindakan']) ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Obat</label>
                            <textarea name="obat" id="obatInput" class="form-control" rows="2"><?= htmlspecialchars($p['obat']) ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Catatan Tambahan</label>
                            <textarea name="catatan" id="catatanInput" class="form-control" rows="2"><?= htmlspecialchars($p['catatan']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <a href="<?= BASEURL ?>/pemeriksaan/show/<?= $p['id'] ?>" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-save"></i> Perbarui Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Script Interaktif Analisis Keluhan AI -->
<script>
// Gunakan global utility dari dash_footer
function showAiToast(type, title, msg) { showYkToast(type, title, msg); }
function showConfirmModal(fields, onConfirm) {
    const listHtml = fields.map(f =>
        `<div class="d-flex align-items-center gap-2 bg-warning bg-opacity-10 rounded-3 px-3 py-2">
            <i class="bi bi-pencil-fill text-warning small"></i>
            <span class="small fw-semibold text-dark">${f}</span>
        </div>`
    ).join('');
    showYkConfirm({
        title: 'Timpa Data yang Sudah Ada?',
        msg: 'Kolom di atas akan digantikan saran AI. Anda tetap bisa mengedit setelah ditempelkan.',
        type: 'warning',
        confirmText: 'Ya, Tempelkan',
        icon: 'bi-clipboard-check'
    }).then(ok => { if (ok) onConfirm(); });
    setTimeout(() => {
        const msgEl = document.getElementById('ykConfirmMsg');
        if (msgEl) msgEl.insertAdjacentHTML('beforebegin',
            `<div class="d-flex flex-column gap-2 mb-2">${listHtml}</div>`);
    }, 50);
}

document.addEventListener('DOMContentLoaded', function() {
    const btnAnalyze = document.getElementById('btnAnalyzeKeluhan');
    const aiWrapper = document.getElementById('aiAnalysisWrapper');
    const aiLoading = document.getElementById('aiAnalysisLoading');
    const aiError = document.getElementById('aiAnalysisError');
    const aiOutput = document.getElementById('aiAnalysisOutput');
    const btnClose = document.getElementById('btnCloseAnalysis');
    const btnApply = document.getElementById('btnApplyDraft');
    
    const keluhanInput = document.getElementById('keluhanInput');
    const suhuInput = document.getElementById('suhuInput');
    const tensiInput = document.getElementById('tensiInput');
    const bbInput = document.getElementById('bbInput');
    const tbInput = document.getElementById('tbInput');
    const hasilInput    = document.getElementById('hasilPemeriksaanInput');
    const tindakanInput = document.getElementById('tindakanInput');
    const obatInput     = document.getElementById('obatInput');
    const catatanInput  = document.getElementById('catatanInput');

    let latestDraftText   = '';
    let latestDraftParsed = null;

    if (btnClose) {
        btnClose.addEventListener('click', function() {
            aiWrapper.classList.add('d-none');
        });
    }

    if (btnAnalyze) {
        btnAnalyze.addEventListener('click', async function() {
            const keluhan = (keluhanInput.value || '').trim();
            if (!keluhan) {
                showAiToast('warning', 'Keluhan Belum Diisi', 'Silakan tuliskan keluhan santri terlebih dahulu.');
                keluhanInput.focus();
                return;
            }

            aiWrapper.classList.remove('d-none');
            aiLoading.classList.remove('d-none');
            aiError.classList.add('d-none');
            aiOutput.innerHTML = '';
            btnAnalyze.disabled = true;

            const payload = {
                csrf_token: '<?= $data['csrf_token'] ?>',
                keluhan: keluhan,
                suhu: suhuInput ? suhuInput.value : '',
                tekanan_darah: tensiInput ? tensiInput.value : '',
                berat_badan: bbInput ? bbInput.value : '',
                tinggi_badan: tbInput ? tbInput.value : '',
                santri_id: '<?= $p['santri_id'] ?? '' ?>'
            };

            try {
                const formData = new FormData();
                for (const key in payload) {
                    formData.append(key, payload[key]);
                }

                const res = await fetch('<?= BASEURL ?>/pemeriksaan/analyze_keluhan', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': '<?= $data['csrf_token'] ?>'
                    },
                    body: formData
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    throw new Error(data.error || 'Terjadi kendala saat analisis AI.');
                }

                // Decode entitas jika AI mengembalikan &amp; atau &quot;
                const txtHelper = document.createElement('textarea');
                txtHelper.innerHTML = data.analysis || '';
                latestDraftText   = txtHelper.value;
                latestDraftParsed = data.parsed || null;
                
                let formatted = latestDraftText
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                    .replace(/### (.*?)\n/g, '<h6 class="fw-bold text-success mt-3 mb-1"><i class="bi bi-chevron-right"></i> $1</h6>')
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g, '<em>$1</em>')
                    .replace(/\n\* /g, '<br>• ')
                    .replace(/\n- /g, '<br>• ')
                    .replace(/\n/g, '<br>');

                aiOutput.innerHTML = formatted;
            } catch (err) {
                aiError.textContent = '⚠️ ' + (err.message || 'Gagal memproses analisis.');
                aiError.classList.remove('d-none');
            } finally {
                aiLoading.classList.add('d-none');
                btnAnalyze.disabled = false;
            }
        });
    }

    if (btnApply) {
        btnApply.addEventListener('click', function() {
            if (!latestDraftText && !latestDraftParsed) return;

            const parsed = latestDraftParsed || {};
            const hasilSaran    = parsed.hasil_pemeriksaan || '';
            const tindakanSaran = parsed.tindakan || '';
            const obatSaran     = parsed.obat || '';
            const catatanSaran  = parsed.catatan || '';

            const willOverwrite = [];
            if (hasilSaran    && hasilInput    && hasilInput.value.trim())    willOverwrite.push('Hasil / Diagnosa');
            if (tindakanSaran && tindakanInput && tindakanInput.value.trim()) willOverwrite.push('Tindakan');
            if (obatSaran     && obatInput     && obatInput.value.trim())     willOverwrite.push('Obat');
            if (catatanSaran  && catatanInput  && catatanInput.value.trim())  willOverwrite.push('Catatan Tambahan');

            function doApply() {
                let filled = [];
                if (hasilInput    && hasilSaran)    { hasilInput.value    = hasilSaran;    filled.push('Hasil / Diagnosa'); }
                if (tindakanInput && tindakanSaran) { tindakanInput.value = tindakanSaran; filled.push('Tindakan'); }
                if (obatInput     && obatSaran)     { obatInput.value     = obatSaran;     filled.push('Obat'); }
                if (catatanInput  && catatanSaran)  { catatanInput.value  = catatanSaran;  filled.push('Catatan Tambahan'); }

                if (filled.length === 0 && hasilInput) {
                    hasilInput.value = '[Draf Rekomendasi AI]:\n' + latestDraftText;
                    filled.push('Hasil / Diagnosa');
                }

                if (filled.length > 0) {
                    showAiToast('success', 'Saran AI Berhasil Ditempelkan!',
                        'Kolom: ' + filled.join(', ') + '. Silakan tinjau sebelum menyimpan.');
                    if (hasilInput) hasilInput.focus();
                }
            }

            if (willOverwrite.length > 0) {
                showConfirmModal(willOverwrite, doApply);
            } else {
                doApply();
            }
        });
    }
});
</script>
