<!-- Header Halaman Ringkas -->
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h5 class="fw-bold text-dark mb-1">
            <i class="bi bi-whatsapp text-success me-2"></i>Monitoring WhatsApp
        </h5>
        <p class="text-muted small mb-0">Pantau status pengiriman notifikasi WhatsApp dan koneksi gateway Fonnte.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <?php if (!empty($data['worker_summary']['whatsapp_enabled'])): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill fw-medium" style="font-size: 12px;">
                <i class="bi bi-broadcast me-1"></i>Live Mode Aktif
            </span>
        <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill fw-medium" style="font-size: 12px;">
                <i class="bi bi-pause-circle me-1"></i>Safe Mode (Nonaktif)
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($data['summary']['reconciliation_required'])): ?>
<!-- Alert Peringatan Status Perlu Rekonsiliasi (F-01) -->
<div class="alert alert-warning border border-warning shadow-sm rounded-3 mb-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 p-3">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 bg-warning text-dark" style="width: 36px; height: 36px;">
            <i class="bi bi-exclamation-triangle-fill fs-6"></i>
        </div>
        <div>
            <strong class="text-dark">Terdapat <?= (int)$data['summary']['reconciliation_required'] ?> antrean perlu rekonsiliasi manual.</strong>
            <div class="small text-muted">Gateway telah menerima pesan namun pembaruan database lokal tertunda.</div>
        </div>
    </div>
    <a href="<?= BASEURL ?>/monitoring?status=reconciliation_required" class="btn btn-warning btn-sm text-nowrap fw-semibold px-3 py-1.5">
        <i class="bi bi-funnel me-1"></i>Tinjau Antrean
    </a>
</div>
<?php endif; ?>

<!-- Bar Status Koneksi Fonnte & Aksi Cepat -->
<?php 
    $devStatus = $data['device_status']['device_status'] ?? 'unverified';
    $isChecked = !empty($data['device_status']['checked_at']);
    $isOnline = ($devStatus === 'connect');
?>
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3 d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <!-- Status Device -->
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $isOnline ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' ?>" style="width: 42px; height: 42px;">
                <i class="bi <?= $isOnline ? 'bi-router-fill' : 'bi-router' ?> fs-5"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-semibold text-dark small">Gateway Fonnte:</span>
                    <?php if ($isOnline): ?>
                        <span class="badge bg-success text-white px-2 py-0.5 rounded-pill" style="font-size: 11px;">
                            <i class="bi bi-circle-fill me-1" style="font-size: 7px;"></i>Terhubung
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary text-white px-2 py-0.5 rounded-pill" style="font-size: 11px;">
                            <?= htmlspecialchars(ucfirst($devStatus)) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-muted small mt-0.5" style="font-size: 11.5px;">
                    <?php if ($isChecked): ?>
                        Akun: <strong><?= htmlspecialchars($data['device_status']['name'] ?? '-') ?></strong> &bull; Sisa Kuota: <strong><?= (int)($data['device_status']['quota'] ?? 0) ?> pesan</strong> &bull; Diperiksa: <?= date('H:i', (int)$data['device_status']['checked_at']) ?> WIB
                    <?php else: ?>
                        Klik tombol periksa untuk memverifikasi kuota dan koneksi nomor WhatsApp gateway.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi -->
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <form method="POST" action="<?= BASEURL ?>/monitoring/check_device" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-outline-secondary btn-sm px-3" style="font-size: 12px;">
                    <i class="bi bi-arrow-repeat me-1"></i>Cek Koneksi
                </button>
            </form>
            <form method="POST" action="<?= BASEURL ?>/monitoring/process_queue" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">
                <input type="hidden" name="limit" value="15">
                <button type="submit" class="btn btn-primary btn-sm px-3" style="font-size: 12px;">
                    <i class="bi bi-play-fill me-1"></i>Proses Antrean
                </button>
            </form>
        </div>
    </div>
</div>

<!-- 4 Kartu Statistik Ringkas -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Total Notifikasi</div>
                    <h4 class="fw-bold text-dark mb-0 mt-1"><?= number_format($data['summary']['total'] ?? 0) ?></h4>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-light text-primary" style="width: 40px; height: 40px;">
                    <i class="bi bi-chat-left-text-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Terkirim</div>
                    <h4 class="fw-bold text-success mb-0 mt-1"><?= number_format($data['summary']['sent'] ?? 0) ?></h4>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 40px; height: 40px;">
                    <i class="bi bi-check2-all fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Menunggu (Pending)</div>
                    <h4 class="fw-bold text-warning mb-0 mt-1"><?= number_format($data['summary']['pending'] ?? 0) ?></h4>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 40px; height: 40px;">
                    <i class="bi bi-hourglass-split fs-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-muted small fw-medium">Gagal</div>
                    <h4 class="fw-bold text-danger mb-0 mt-1"><?= number_format($data['summary']['failed'] ?? 0) ?></h4>
                </div>
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-danger-subtle text-danger" style="width: 40px; height: 40px;">
                    <i class="bi bi-x-circle-fill fs-5"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Card Utama: Tabel Antrean WhatsApp & Filter Terpadu -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <!-- Header Tabel + Filter Terintegrasi -->
    <div class="card-header bg-white border-bottom p-3">
        <form method="GET" action="<?= BASEURL ?>/monitoring" class="row g-2 align-items-center">
            <div class="col-12 col-md-auto d-flex align-items-center gap-2 me-md-auto">
                <span class="fw-bold text-dark small"><i class="bi bi-list-check text-primary me-1"></i>Riwayat Pesan</span>
                <span class="badge bg-light text-secondary border" style="font-size: 11px;"><?= (int)$data['total'] ?> data</span>
            </div>

            <!-- Filter Status -->
            <div class="col-6 col-md-auto">
                <select name="status" class="form-select form-select-sm" style="font-size: 12px;" onchange="this.form.submit()">
                    <option value="" <?= empty($data['filters']['status']) ? 'selected' : '' ?>>Semua Status</option>
                    <option value="sent" <?= ($data['filters']['status'] === 'sent') ? 'selected' : '' ?>>Terkirim (Sent)</option>
                    <option value="pending" <?= ($data['filters']['status'] === 'pending') ? 'selected' : '' ?>>Menunggu (Pending)</option>
                    <option value="processing" <?= ($data['filters']['status'] === 'processing') ? 'selected' : '' ?>>Sedang Diproses</option>
                    <option value="failed" <?= ($data['filters']['status'] === 'failed') ? 'selected' : '' ?>>Gagal (Failed)</option>
                    <option value="reconciliation_required" <?= ($data['filters']['status'] === 'reconciliation_required') ? 'selected' : '' ?>>Perlu Rekonsiliasi</option>
                </select>
            </div>

            <!-- Filter Peristiwa -->
            <div class="col-6 col-md-auto">
                <select name="jenis" class="form-select form-select-sm" style="font-size: 12px;" onchange="this.form.submit()">
                    <option value="" <?= empty($data['filters']['jenis']) ? 'selected' : '' ?>>Semua Peristiwa</option>
                    <option value="keluhan_baru" <?= ($data['filters']['jenis'] === 'keluhan_baru') ? 'selected' : '' ?>>Keluhan Baru</option>
                    <option value="penugasan_baru" <?= ($data['filters']['jenis'] === 'penugasan_baru') ? 'selected' : '' ?>>Penugasan Baru</option>
                    <option value="pengalihan_tugas" <?= ($data['filters']['jenis'] === 'pengalihan_tugas') ? 'selected' : '' ?>>Pengalihan Tugas</option>
                    <option value="status_diperiksa" <?= ($data['filters']['jenis'] === 'status_diperiksa') ? 'selected' : '' ?>>Status Diperiksa</option>
                    <option value="status_selesai" <?= ($data['filters']['jenis'] === 'status_selesai') ? 'selected' : '' ?>>Status Selesai</option>
                </select>
            </div>

            <!-- Reset Filter jika aktif -->
            <?php if (!empty($data['filters']['status']) || !empty($data['filters']['jenis']) || !empty($data['filters']['tanggal_dari']) || !empty($data['filters']['tanggal_sampai'])): ?>
            <div class="col-auto">
                <a href="<?= BASEURL ?>/monitoring" class="btn btn-outline-secondary btn-sm py-1 px-2" title="Reset Filter" style="font-size: 11px;">
                    <i class="bi bi-x-circle me-1"></i>Reset
                </a>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabel Data Bersih -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 12.5px;">
            <thead class="table-light text-secondary">
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Waktu</th>
                    <th>Penerima (Pengurus)</th>
                    <th>Peristiwa</th>
                    <th class="text-center">Status</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['list'])): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-chat-square-dots fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Belum ada notifikasi WhatsApp yang tercatat.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($data['list'] as $idx => $item): ?>
                <tr>
                    <td class="text-center text-muted"><?= ($data['page'] - 1) * 15 + $idx + 1 ?></td>
                    <td class="text-nowrap text-secondary">
                        <div class="fw-medium text-dark"><?= date('d M Y', strtotime($item['created_at'])) ?></div>
                        <div style="font-size: 11px;"><?= date('H:i', strtotime($item['created_at'])) ?> WIB</div>
                    </td>
                    <td>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($item['nama_pengurus'] ?? 'Pengurus') ?></div>
                        <div class="text-muted" style="font-size: 11px;">
                            <i class="bi bi-whatsapp text-success me-1"></i><?= Notifikasi_model::maskPhoneNumber($item['nomor_tujuan']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($item['jenis_notifikasi'])): ?>
                            <span class="badge bg-light text-dark border px-2 py-1 rounded-pill" style="font-size: 11px;">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $item['jenis_notifikasi']))) ?>
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill" style="font-size: 11px;">Pemberitahuan</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <?php if ($item['status'] === 'sent'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-medium" style="font-size: 11px;">
                                <i class="bi bi-check2 me-1"></i>Terkirim
                            </span>
                        <?php elseif ($item['status'] === 'pending'): ?>
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-medium" style="font-size: 11px;">
                                <i class="bi bi-hourglass-split me-1"></i>Menunggu
                            </span>
                        <?php elseif ($item['status'] === 'processing'): ?>
                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1 rounded-pill fw-medium" style="font-size: 11px;">
                                <i class="bi bi-arrow-repeat me-1"></i>Diproses
                            </span>
                        <?php elseif ($item['status'] === 'reconciliation_required'): ?>
                            <span class="badge bg-warning text-dark border border-warning px-2 py-1 rounded-pill fw-medium" style="font-size: 11px;">
                                Perlu Review
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 rounded-pill fw-medium" style="font-size: 11px;">
                                <i class="bi bi-x-circle me-1"></i>Gagal
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($item['status'] === 'sent'): ?>
                            <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Diterima gateway Fonnte</span>
                        <?php elseif ($item['status'] === 'failed'): ?>
                            <span class="text-danger small"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= htmlspecialchars($item['error_message'] ?? 'Pengiriman gagal') ?></span>
                        <?php elseif ($item['status'] === 'reconciliation_required'): ?>
                            <div class="d-flex flex-wrap gap-1">
                                <form method="POST" action="<?= BASEURL ?>/monitoring/reconcile" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="reconcile_action" value="mark_sent">
                                    <button type="button" class="btn btn-success btn-sm py-0 px-2 fw-medium" style="font-size: 11px;"
                                        onclick="(async()=>{ if(await showYkConfirm({title:'Tandai Terkirim?',msg:'Antrean #WA-<?= (int)$item['id'] ?> akan ditandai sebagai terkirim.',type:'success',confirmText:'Ya, Tandai',icon:'bi-check2-all'})) this.closest('form').submit(); })();">Tandai Terkirim</button>
                                </form>
                                <form method="POST" action="<?= BASEURL ?>/monitoring/reconcile" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="queue_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="reconcile_action" value="retry">
                                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 fw-medium" style="font-size: 11px;"
                                        onclick="(async()=>{ if(await showYkConfirm({title:'Kirim Ulang?',msg:'Antrean #WA-<?= (int)$item['id'] ?> akan dikirim ulang ke WhatsApp.',type:'info',confirmText:'Ya, Kirim Ulang',icon:'bi-send-fill'})) this.closest('form').submit(); })();">Kirim Ulang</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span class="text-muted small">Menunggu eksekusi</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginasi jika lebih dari 1 halaman -->
    <?php if (($data['total_pages'] ?? 1) > 1): ?>
    <div class="card-footer bg-white py-2 border-top">
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm justify-content-center mb-0" style="font-size: 11.5px;">
                <li class="page-item <?= ($data['page'] <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/monitoring?page=<?= $data['page'] - 1 ?>&status=<?= urlencode($data['filters']['status']) ?>&jenis=<?= urlencode($data['filters']['jenis']) ?>">Sebelumnya</a>
                </li>
                <?php for ($p = 1; $p <= $data['total_pages']; $p++): ?>
                <li class="page-item <?= ($p == $data['page']) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/monitoring?page=<?= $p ?>&status=<?= urlencode($data['filters']['status']) ?>&jenis=<?= urlencode($data['filters']['jenis']) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($data['page'] >= $data['total_pages']) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/monitoring?page=<?= $data['page'] + 1 ?>&status=<?= urlencode($data['filters']['status']) ?>&jenis=<?= urlencode($data['filters']['jenis']) ?>">Berikutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- Bagian Accordion Arsip Jejak Audit (Tertutup / Minimalis) -->
<?php if (!empty($data['test_audit_list'])): ?>
<div class="accordion mt-3 mb-4" id="accordionAuditHistory">
    <div class="accordion-item border-0 shadow-sm rounded-3 overflow-hidden">
        <h2 class="accordion-header" id="headingAudit">
            <button class="accordion-button collapsed bg-white text-secondary py-2.5 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAudit" aria-expanded="false" aria-controls="collapseAudit" style="font-size: 12px;">
                <i class="bi bi-archive text-secondary me-2"></i>
                <span class="fw-medium text-dark">Log Audit Pengujian Historis</span>
                <span class="badge bg-light text-muted border ms-2" style="font-size: 10.5px;"><?= count($data['test_audit_list']) ?> data</span>
            </button>
        </h2>
        <div id="collapseAudit" class="accordion-collapse collapse" aria-labelledby="headingAudit" data-bs-parent="#accordionAuditHistory">
            <div class="accordion-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 11.5px;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35px;" class="text-center">#</th>
                                <th>Waktu</th>
                                <th>Admin</th>
                                <th>Target Pengurus</th>
                                <th>Nomor WhatsApp</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['test_audit_list'] as $idx => $testItem): ?>
                            <tr>
                                <td class="text-center text-muted"><?= $idx + 1 ?></td>
                                <td class="text-nowrap text-muted"><?= date('d M Y, H:i', strtotime($testItem['executed_at'] ?? $testItem['created_at'])) ?> WIB</td>
                                <td><?= htmlspecialchars($testItem['admin_username'] ?? ('User #' . $testItem['admin_user_id'])) ?></td>
                                <td><?= htmlspecialchars($testItem['nama_pengurus'] ?? ('Pengurus #' . $testItem['pengurus_user_id'])) ?></td>
                                <td><?= htmlspecialchars(strip_tags($testItem['nomor_tujuan_masked'])) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($testItem['status']) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
