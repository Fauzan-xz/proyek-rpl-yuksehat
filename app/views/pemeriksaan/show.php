<?php $p = $data['item']; ?>
<div class="row">
    <!-- Sidebar Info Santri -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-success text-white rounded-top-4 p-3">
                <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Identitas Santri</h6>
            </div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item"><strong>Nama:</strong> <?= htmlspecialchars($p['nama_santri']) ?></li>
                <li class="list-group-item"><strong>NIS:</strong> <?= htmlspecialchars($p['nis']) ?></li>
                <li class="list-group-item"><strong>Kelas:</strong> <?= htmlspecialchars($p['kelas']) ?></li>
                <li class="list-group-item"><strong>Kamar:</strong> <?= htmlspecialchars($p['kamar']) ?></li>
                <li class="list-group-item"><strong>Gol. Darah:</strong>
                    <?php if($p['golongan_darah']): ?>
                        <span class="badge bg-danger"><?= htmlspecialchars($p['golongan_darah']) ?></span>
                    <?php else: ?>-<?php endif; ?>
                </li>
                <?php if($p['alergi']): ?>
                <li class="list-group-item text-danger"><strong>Alergi:</strong> <?= htmlspecialchars($p['alergi']) ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mt-3">
            <div class="card-body p-3">
                <p class="mb-1 small text-muted">Diperiksa oleh:</p>
                <strong><?= htmlspecialchars($p['nama_pemeriksa']) ?></strong><br>
                <small class="text-muted"><?= date('d M Y, H:i', strtotime($p['tanggal_pemeriksaan'])) ?> WIB</small>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <a href="<?= BASEURL ?>/pemeriksaan" class="btn btn-secondary btn-sm flex-fill">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <?php if ($data['role'] !== 'santri'): ?>
            <a href="<?= BASEURL ?>/pemeriksaan/edit/<?= $p['id'] ?>" class="btn btn-warning btn-sm flex-fill">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Pemeriksaan -->
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold border-bottom pb-3 mb-4">
                    <i class="bi bi-activity text-success me-2"></i>Data Vital
                </h5>
                <div class="row g-3">
                    <div class="col-6 col-md-3 text-center">
                        <div class="border rounded-3 p-3">
                            <i class="bi bi-thermometer-half fs-3 text-danger"></i>
                            <p class="mb-0 mt-2 fs-4 fw-bold"><?= $p['suhu'] ? $p['suhu'].'°C' : '-' ?></p>
                            <small class="text-muted">Suhu</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="border rounded-3 p-3">
                            <i class="bi bi-heart-pulse fs-3 text-danger"></i>
                            <p class="mb-0 mt-2 fs-5 fw-bold"><?= $p['tekanan_darah'] ?: '-' ?></p>
                            <small class="text-muted">Tensi</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="border rounded-3 p-3">
                            <i class="bi bi-speedometer fs-3 text-primary"></i>
                            <p class="mb-0 mt-2 fs-4 fw-bold"><?= $p['berat_badan'] ? $p['berat_badan'].' kg' : '-' ?></p>
                            <small class="text-muted">Berat</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 text-center">
                        <div class="border rounded-3 p-3">
                            <i class="bi bi-arrow-up-short fs-3 text-success"></i>
                            <p class="mb-0 mt-2 fs-4 fw-bold"><?= $p['tinggi_badan'] ? $p['tinggi_badan'].' cm' : '-' ?></p>
                            <small class="text-muted">Tinggi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h5 class="fw-bold border-bottom pb-3 mb-4">
                    <i class="bi bi-file-medical text-primary me-2"></i>Catatan Medis
                </h5>

                <div class="mb-3">
                    <label class="fw-bold text-muted small">Keluhan</label>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($p['keluhan'])) ?></p>
                </div>
                <hr>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="fw-bold text-muted small mb-0">Hasil Pemeriksaan / Diagnosa</label>
                        <?php if (MedicalFormatHelper::isAiDraft($p['hasil_pemeriksaan'])): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 11px;">
                                <i class="bi bi-robot me-1"></i>Draf Rekomendasi AI
                            </span>
                        <?php endif; ?>
                    </div>
                    <div><?= MedicalFormatHelper::renderFull($p['hasil_pemeriksaan']) ?></div>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Tindakan</label>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($p['tindakan'])) ?: '<span class="text-muted">-</span>' ?></p>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="fw-bold text-muted small">Obat yang Diberikan</label>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($p['obat'])) ?: '<span class="text-muted">-</span>' ?></p>
                </div>
                <?php if ($p['catatan']): ?>
                <hr>
                <div class="mb-0">
                    <label class="fw-bold text-muted small">Catatan Tambahan</label>
                    <div class="alert alert-secondary mb-0 py-2">
                        <?= nl2br(htmlspecialchars($p['catatan'])) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
