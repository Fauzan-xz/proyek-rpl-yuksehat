<?php
$role  = $data['role']  ?? 'admin';
$stats = $data['stats'] ?? [];
?>

<?php if (in_array($role, ['admin', 'pengurus', 'petugas_kesehatan'])): ?>
<!-- ================================================================ -->
<!--  DASHBOARD — ADMIN / PENGURUS / PETUGAS KESEHATAN               -->
<!-- ================================================================ -->

<!-- AI Chatbot Assistant Banner -->
<div class="card border-0 mb-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 45%, #eff6ff 100%); border: 1px solid rgba(16, 185, 129, 0.3) !important; border-radius: 16px; box-shadow: 0 4px 20px rgba(5, 150, 105, 0.07);">
    <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 52px; height: 52px; font-size: 24px; border-radius: 14px; background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0 text-dark" style="font-family: 'Outfit', sans-serif; font-size: 1.05rem;">YukSehat AI Assistant</h6>
                    <span class="badge text-white small px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 10px; background: linear-gradient(90deg, #059669, #047857); border-radius: 6px; letter-spacing: 0.3px;">
                        <span style="width: 6px; height: 6px; background: #6ee7b7; border-radius: 50%; display: inline-block;"></span> AI Aktif
                    </span>
                </div>
                <p class="text-secondary small mb-0" style="font-size: 13px; line-height: 1.4;">Konsultasikan keluhan santri, panduan scabies, pertolongan pertama, atau edukasi PHBS asrama langsung dengan AI.</p>
            </div>
        </div>
        <div>
            <button type="button" class="btn text-white btn-sm px-4 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); font-size: 13px;" onclick="document.getElementById('yuksehatChatTrigger')?.click();">
                <i class="bi bi-chat-dots-fill"></i> Tanya AI Sekarang
            </button>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Card 1: Total Santri Aktif -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-icon bg-primary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-label">Total Santri Aktif</div>
                <div class="stat-value"><?= (int)($stats['total_santri'] ?? 0) ?></div>
                <div class="stat-desc">Santri aktif terdaftar</div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= BASEURL ?>/santri" class="text-primary text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                    <span>Lihat Data Santri</span> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 2: Pemeriksaan Hari Ini -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-icon bg-success">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>
                <div class="stat-label">Pemeriksaan Hari Ini</div>
                <div class="stat-value"><?= (int)($stats['pemeriksaan_hari_ini'] ?? 0) ?></div>
                <div class="stat-desc">Pemeriksaan tercatat hari ini</div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= BASEURL ?>/pemeriksaan" class="text-success text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                    <span>Lihat Pemeriksaan</span> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 3: Sedang di UKS -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-icon bg-warning">
                    <i class="bi bi-bandaid-fill"></i>
                </div>
                <div class="stat-label">Sedang di UKS</div>
                <div class="stat-value"><?= (int)($stats['rawat_inap'] ?? 0) ?></div>
                <div class="stat-desc">Perlu pemantauan</div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= BASEURL ?>/pemeriksaan?filter=uks" class="text-warning text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                    <span>Lihat Detail</span> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Card 4: Perlu Rujukan -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-card-body">
                <div class="stat-icon bg-danger">
                    <i class="bi bi-hospital-fill"></i>
                </div>
                <div class="stat-label">Perlu Rujukan</div>
                <div class="stat-value"><?= (int)($stats['perlu_rujukan'] ?? 0) ?></div>
                <div class="stat-desc">Perlu tindak lanjut</div>
            </div>
            <div class="stat-card-footer">
                <a href="<?= BASEURL ?>/pemeriksaan?filter=rujukan" class="text-danger text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                    <span>Lihat Detail</span> <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- ===== RECENT PEMERIKSAAN TABLE ===== -->
<div class="card border-0 shadow-sm" style="border: 1px solid #e2e8f0 !important; border-radius: 16px;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center border-bottom" style="border-top-left-radius: 16px !important; border-top-right-radius: 16px !important; border-color: #f1f5f9 !important;">
        <div>
            <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-family: 'Outfit', sans-serif; font-size: 16px;">
                <div class="d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; color: #059669;">
                    <i class="bi bi-journal-medical fs-6"></i>
                </div>
                <span>Aktivitas Pemeriksaan Terakhir</span>
            </h6>
            <div class="small text-muted mt-1" style="font-size: 12px; padding-left: 36px;">5 pemeriksaan terbaru</div>
        </div>
        <?php if (AuthHelper::canInputPemeriksaan()): ?>
        <a href="<?= BASEURL; ?>/pemeriksaan/create" class="btn text-white btn-sm px-3 py-2 d-inline-flex align-items-center" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border-radius: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.35); font-size: 12.5px;">
            <i class="bi bi-plus-lg me-1"></i> Input Pemeriksaan
        </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background-color: #f8fafc;">
                    <tr>
                        <th class="ps-4 py-3 text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; border-bottom: 1px solid #e2e8f0;">WAKTU</th>
                        <th class="py-3 text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; border-bottom: 1px solid #e2e8f0;">SANTRI</th>
                        <th class="py-3 text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; border-bottom: 1px solid #e2e8f0;">KAMAR</th>
                        <th class="py-3 text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; border-bottom: 1px solid #e2e8f0;">KELUHAN</th>
                        <th class="pe-4 py-3 text-end text-secondary small fw-bold" style="font-size: 11px; letter-spacing: 0.6px; border-bottom: 1px solid #e2e8f0;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['recent_pemeriksaan'])): ?>
                        <tr>
                            <td colspan="5" class="py-5 text-center text-muted">
                                <i class="bi bi-clipboard-x fs-1 opacity-50 d-block mb-2"></i>
                                <span class="small">Belum ada data pemeriksaan terbaru</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($data['recent_pemeriksaan'] as $p): ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="fw-semibold small text-dark"><?= date('d M Y', strtotime($p['tanggal_pemeriksaan'])) ?></div>
                                <div class="text-muted" style="font-size: 11.5px;"><?= date('H:i', strtotime($p['tanggal_pemeriksaan'])) ?> WIB</div>
                            </td>
                            <td class="py-3">
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($p['nama_santri'] ?? '-') ?></div>
                                <div class="text-muted" style="font-size: 11.5px;">Kelas <?= htmlspecialchars($p['kelas'] ?? '-') ?></div>
                            </td>
                            <td class="py-3">
                                <span class="badge bg-light text-dark border px-2 py-1 fw-normal" style="font-size: 11.5px; border-radius: 6px;">
                                    <?= htmlspecialchars($p['kamar'] ?? '-') ?>
                                </span>
                            </td>
                            <td class="py-3">
                                <div class="text-truncate text-secondary small" style="max-width: 250px;"><?= htmlspecialchars($p['keluhan'] ?? '-') ?></div>
                            </td>
                            <td class="pe-4 py-3 text-end">
                                <a href="<?= BASEURL; ?>/pemeriksaan/show/<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary px-3 py-1" style="font-size: 12px; border-radius: 6px;">
                                    Detail
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php elseif ($role == 'santri'): ?>
<!-- ================================================================ -->
<!--  DASHBOARD SANTRI                                                 -->
<!-- ================================================================ -->

<!-- AI Chatbot Assistant Banner Santri -->
<div class="card border-0 mb-4 overflow-hidden position-relative" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 45%, #eff6ff 100%); border: 1px solid rgba(16, 185, 129, 0.3) !important; border-radius: 16px; box-shadow: 0 4px 20px rgba(5, 150, 105, 0.07);">
    <div class="card-body p-3 p-md-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 52px; height: 52px; font-size: 24px; border-radius: 14px; background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 6px 16px rgba(5, 150, 105, 0.35);">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h6 class="fw-bold mb-0 text-dark" style="font-family: 'Outfit', sans-serif; font-size: 1.05rem;">YukSehat AI - Teman Konsultasi Santri</h6>
                    <span class="badge text-white small px-2 py-1 d-inline-flex align-items-center gap-1" style="font-size: 10px; background: linear-gradient(90deg, #059669, #047857); border-radius: 6px; letter-spacing: 0.3px;">
                        <span style="width: 6px; height: 6px; background: #6ee7b7; border-radius: 50%; display: inline-block;"></span> Aktif 24 Jam
                    </span>
                </div>
                <p class="text-secondary small mb-0" style="font-size: 13px; line-height: 1.4;">Sedang merasa tidak enak badan, gatal-gatal di asrama, atau butuh tips sehat? Tanyakan langsung ke asisten AI.</p>
            </div>
        </div>
        <div>
            <button type="button" class="btn text-white btn-sm px-4 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none; box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35); font-size: 13px;" onclick="document.getElementById('yuksehatChatTrigger')?.click();">
                <i class="bi bi-chat-dots-fill"></i> Tanya AI Sekarang
            </button>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100" style="border: 1px solid #e2e8f0 !important; border-radius: 16px;">
            <div class="card-body text-center p-4">
                <div class="mb-3">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($data['username'] ?? 'Santri') ?>&background=059669&color=fff&size=100" class="rounded-circle shadow-sm" alt="Avatar" style="width: 84px; height: 84px; border: 3px solid #ecfdf5;">
                </div>
                <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;"><?= htmlspecialchars($data['profil']['nama'] ?? $data['username'] ?? 'Santri') ?></h5>
                <p class="text-muted small mb-3">NIS: <?= htmlspecialchars($data['profil']['nis'] ?? '-') ?></p>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-semibold">Kelas <?= htmlspecialchars($data['profil']['kelas'] ?? '-') ?></span>
                    <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-3 py-1 fw-semibold">Kamar <?= htmlspecialchars($data['profil']['kamar'] ?? '-') ?></span>
                </div>
                <hr style="border-color: #f1f5f9;">
                <div class="text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Gol. Darah</span>
                        <strong><?= htmlspecialchars($data['profil']['golongan_darah'] ?? '-') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Alergi</span>
                        <?php $alergi = $data['profil']['alergi'] ?? ''; ?>
                        <?php if(empty($alergi) || strtolower($alergi) == 'tidak ada'): ?>
                            <span class="text-success small fw-bold"><i class="bi bi-check-circle me-1"></i>Tidak ada</span>
                        <?php else: ?>
                            <span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($alergi) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="stat-card">
                    <div class="stat-card-body">
                        <div class="stat-icon bg-success">
                            <i class="bi bi-clipboard2-pulse-fill"></i>
                        </div>
                        <div class="stat-label">Total Pemeriksaan</div>
                        <div class="stat-value"><?= (int)($stats['total_pemeriksaan'] ?? 0) ?></div>
                        <div class="stat-desc">Kali berobat ke Poskestren</div>
                    </div>
                    <div class="stat-card-footer">
                        <a href="<?= BASEURL ?>/pemeriksaan" class="text-success text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                            <span>Lihat Riwayat Lengkap</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="stat-card text-white" style="background: linear-gradient(135deg, #059669 0%, #047857 100%); border: none;">
                    <div class="stat-card-body">
                        <div class="stat-icon" style="background: rgba(255,255,255,0.18); color: #ffffff;">
                            <i class="bi bi-hospital"></i>
                        </div>
                        <h5 class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif;">Layanan Poskestren</h5>
                        <p class="small mb-0" style="color: rgba(255,255,255,0.85); font-size: 12.5px; line-height: 1.4;">Fasilitas kesehatan santri siap siaga untuk konsultasi dan pemeriksaan berkala.</p>
                    </div>
                    <div class="stat-card-footer border-0" style="background: rgba(0,0,0,0.12);">
                        <a href="<?= BASEURL ?>/keluhan" class="text-white text-decoration-none fw-semibold d-flex align-items-center justify-content-between">
                            <span>Ajukan Keluhan Baru</span> <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm" style="border: 1px solid #e2e8f0 !important; border-radius: 16px;">
            <div class="card-header bg-white pt-3 pb-2 border-bottom" style="border-color: #f1f5f9 !important; border-top-left-radius: 16px !important; border-top-right-radius: 16px !important;">
                <h6 class="fw-bold mb-0 d-flex align-items-center" style="font-family: 'Outfit', sans-serif;">
                    <div class="d-inline-flex align-items-center justify-content-center me-2" style="width: 26px; height: 26px; border-radius: 7px; background: #ecfdf5; color: #059669;">
                        <i class="bi bi-clock-history fs-6"></i>
                    </div>
                    <span>Riwayat Pemeriksaan Terakhir</span>
                </h6>
            </div>
            <div class="card-body p-0">
                <?php if(empty($data['recent_pemeriksaan'])): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-shield-check fs-1 text-success opacity-50 d-block mb-2"></i>
                        <div class="fw-semibold small text-dark mb-1">Kondisi Sehat</div>
                        <span class="small text-muted">Belum ada riwayat keluhan atau pemeriksaan kesehatan.</span>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($data['recent_pemeriksaan'] as $p): ?>
                        <div class="list-group-item px-4 py-3" style="border-color: #f1f5f9;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 14px;"><?= htmlspecialchars($p['keluhan'] ?? '-') ?></h6>
                                <span class="small text-muted" style="font-size: 12px;"><?= date('d M Y', strtotime($p['tanggal_pemeriksaan'])) ?></span>
                            </div>
                            <div class="mb-1 small">
                                <span class="text-muted">Hasil:</span> 
                                <?php if (MedicalFormatHelper::isAiDraft($p['hasil_pemeriksaan'] ?? '')): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle me-1" style="font-size: 10px;">Draf AI</span>
                                <?php endif; ?>
                                <span class="fw-semibold text-secondary"><?= MedicalFormatHelper::cleanSummary($p['hasil_pemeriksaan'] ?? '-', 90) ?></span>
                            </div>
                            <span class="badge bg-light text-secondary border fw-normal" style="font-size: 11px;">Petugas: <?= htmlspecialchars($p['nama_pemeriksa'] ?? 'Petugas') ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>