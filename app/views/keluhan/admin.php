<div class="row g-3 mb-4">
    <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-inbox-fill text-warning me-2"></i>Antrean Keluhan Kesehatan Santri</h5>
            <p class="text-secondary small mb-0">Pantau, tugaskan pengurus penanganan, dan tindak lanjuti laporan keluhan santri yang masuk ke Poskestren.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill shadow-sm" style="font-size: 13px;">
                <i class="bi bi-hourglass-split me-1"></i><?= (int)($data['count_menunggu'] ?? 0) ?> Keluhan Menunggu
            </span>
        </div>
    </div>
</div>

<!-- Quick Status Filter Pills -->
<div class="card border-0 shadow-sm rounded-3 mb-3">
    <div class="card-body p-2 d-flex flex-wrap gap-2">
        <a href="<?= BASEURL ?>/keluhan?status=" class="btn btn-sm <?= ($data['status_filter'] ?? '') === '' ? 'btn-success text-white' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-inbox-fill me-1"></i>Semua Keluhan (<?= (int)($data['status_counts']['all'] ?? 0) ?>)
        </a>
        <a href="<?= BASEURL ?>/keluhan?status=menunggu" class="btn btn-sm <?= ($data['status_filter'] ?? '') === 'menunggu' ? 'btn-warning text-dark' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-hourglass-split me-1"></i>Menunggu (<?= (int)($data['status_counts']['menunggu'] ?? 0) ?>)
        </a>
        <a href="<?= BASEURL ?>/keluhan?status=diperiksa" class="btn btn-sm <?= ($data['status_filter'] ?? '') === 'diperiksa' ? 'btn-primary text-white' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-clipboard-pulse me-1"></i>Sedang Diperiksa (<?= (int)($data['status_counts']['diperiksa'] ?? 0) ?>)
        </a>
        <a href="<?= BASEURL ?>/keluhan?status=selesai" class="btn btn-sm <?= ($data['status_filter'] ?? '') === 'selesai' ? 'btn-success text-white' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-check-circle-fill me-1"></i>Selesai Ditangani (<?= (int)($data['status_counts']['selesai'] ?? 0) ?>)
        </a>
    </div>
</div>

<!-- Card Filter & Search -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASEURL ?>/keluhan" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari santri, NIS, atau keluhan..." value="<?= htmlspecialchars($data['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="" <?= ($data['status_filter'] ?? '') === '' ? 'selected' : '' ?>>Semua Status Keluhan (<?= (int)($data['status_counts']['all'] ?? 0) ?>)</option>
                    <option value="menunggu" <?= ($data['status_filter'] ?? '') === 'menunggu' ? 'selected' : '' ?>>Status: Menunggu Pemeriksaan (<?= (int)($data['status_counts']['menunggu'] ?? 0) ?>)</option>
                    <option value="diperiksa" <?= ($data['status_filter'] ?? '') === 'diperiksa' ? 'selected' : '' ?>>Status: Sedang Diperiksa (<?= (int)($data['status_counts']['diperiksa'] ?? 0) ?>)</option>
                    <option value="selesai" <?= ($data['status_filter'] ?? '') === 'selesai' ? 'selected' : '' ?>>Status: Selesai Ditangani (<?= (int)($data['status_counts']['selesai'] ?? 0) ?>)</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 flex-grow-1"><i class="bi bi-filter"></i> Terapkan</button>
                <a href="<?= BASEURL ?>/keluhan?status=" class="btn btn-outline-secondary btn-sm px-3" title="Tampilkan Semua"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Antrean -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-task text-primary me-2"></i>Daftar Keluhan Santri</h6>
        <span class="badge bg-light text-dark border"><?= count($data['antrean'] ?? []) ?> Laporan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 40px;" class="text-center">#</th>
                    <th>Santri</th>
                    <th>Waktu Lapor</th>
                    <th>Keluhan yang Dirasakan</th>
                    <th style="width: 170px;" class="text-center">Petugas Penanganan</th>
                    <th style="width: 110px;" class="text-center">Status</th>
                    <th style="width: 180px;" class="text-center">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['antrean'])): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-clipboard2-check fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Tidak ada data antrean keluhan pada kriteria ini.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($data['antrean'] as $idx => $item): ?>
                <tr>
                    <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($item['nama_santri'] ?? '-') ?></div>
                        <div class="text-muted" style="font-size: 11px;">
                            NIS: <?= htmlspecialchars($item['nis'] ?? '-') ?> • Kelas <?= htmlspecialchars($item['kelas'] ?? '-') ?> • Kamar <?= htmlspecialchars($item['kamar'] ?? '-') ?>
                        </div>
                    </td>
                    <td class="text-nowrap small text-muted">
                        <?= date('d M Y, H:i', strtotime($item['tanggal_keluhan'])) ?> WIB
                    </td>
                    <td>
                        <div class="text-dark fw-medium"><?= nl2br(htmlspecialchars($item['keluhan'])) ?></div>
                    </td>
                    <td class="text-center">
                        <!-- Petugas Penanganan & Form Penugasan -->
                        <div class="dropdown d-inline-block">
                            <?php if (!empty($item['assigned_to'])): ?>
                                <button class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 shadow-none" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false" 
                                        title="Klik untuk ubah atau alihkan penugasan"
                                        style="font-size: 11.5px;">
                                    <i class="bi bi-person-check-fill text-success"></i>
                                    <span class="text-truncate" style="max-width: 110px;"><?= htmlspecialchars($item['nama_petugas_assigned'] ?? 'Pengurus') ?></span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-warning text-dark py-1 px-2 rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 shadow-none" 
                                        type="button" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false" 
                                        title="Belum ada pengurus yang ditugaskan. Klik untuk menugaskan."
                                        style="font-size: 11.5px;">
                                    <i class="bi bi-person-plus text-warning"></i>
                                    <span>Tugaskan</span>
                                </button>
                            <?php endif; ?>

                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 rounded-3" style="min-width: 220px; font-size: 12px;">
                                <div class="px-2 py-1 text-muted small fw-semibold border-bottom mb-1">
                                    <i class="bi bi-arrow-right-circle me-1"></i>Tugaskan ke Pengurus:
                                </div>
                                <?php if (!empty($data['pengurus_list'])): ?>
                                    <?php foreach ($data['pengurus_list'] as $pengurus): ?>
                                        <form action="<?= BASEURL ?>/keluhan/assign" method="POST" class="m-0 p-0">
                                            <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                            <input type="hidden" name="keluhan_id" value="<?= (int)$item['id'] ?>">
                                            <input type="hidden" name="assigned_to" value="<?= (int)$pengurus['user_id'] ?>">
                                            <button type="submit" class="dropdown-item py-1.5 px-2 rounded-2 d-flex align-items-center justify-content-between <?= ((int)$item['assigned_to'] === (int)$pengurus['user_id']) ? 'active' : '' ?>">
                                                <span><i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($pengurus['nama']) ?></span>
                                                <?php if ((int)$item['assigned_to'] === (int)$pengurus['user_id']): ?>
                                                <i class="bi bi-check2"></i>
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="px-2 py-1 text-muted small">Tidak ada akun pengurus aktif.</div>
                                <?php endif; ?>

                                <?php if (!empty($item['assigned_to'])): ?>
                                <div class="border-top my-1"></div>
                                <form action="<?= BASEURL ?>/keluhan/assign" method="POST" class="m-0 p-0">
                                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                    <input type="hidden" name="keluhan_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="assigned_to" value="0">
                                    <button type="submit" class="dropdown-item py-1.5 px-2 rounded-2 text-danger">
                                        <i class="bi bi-x-circle me-1"></i>Batalkan Penugasan
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="dropdown d-inline-block">
                            <?php if ($item['status'] === 'menunggu'): ?>
                                <button class="btn btn-sm btn-outline-warning text-dark py-1 px-2 rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Klik untuk ubah status penanganan" style="font-size: 11.5px;">
                                    <i class="bi bi-hourglass-split text-warning"></i>
                                    <span>Menunggu</span>
                                </button>
                            <?php elseif ($item['status'] === 'diperiksa'): ?>
                                <button class="btn btn-sm btn-outline-primary py-1 px-2 rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Klik untuk ubah status penanganan" style="font-size: 11.5px;">
                                    <i class="bi bi-clipboard-pulse text-primary"></i>
                                    <span>Diperiksa</span>
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill dropdown-toggle d-inline-flex align-items-center gap-1 shadow-none" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Klik untuk ubah status penanganan" style="font-size: 11.5px;">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Selesai</span>
                                </button>
                            <?php endif; ?>
                            
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2 rounded-3" style="min-width: 180px; font-size: 12px;">
                                <div class="px-2 py-1 text-muted small fw-semibold border-bottom mb-1">
                                    <i class="bi bi-arrow-repeat me-1"></i>Ubah Status:
                                </div>
                                <form action="<?= BASEURL ?>/keluhan/update_status" method="POST" class="m-0 p-0">
                                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                    <input type="hidden" name="keluhan_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="status" value="menunggu">
                                    <button type="submit" class="dropdown-item py-1.5 px-2 rounded-2 d-flex align-items-center justify-content-between <?= ($item['status'] === 'menunggu') ? 'active' : '' ?>">
                                        <span><i class="bi bi-hourglass-split me-1 text-warning"></i>Menunggu</span>
                                        <?php if ($item['status'] === 'menunggu'): ?><i class="bi bi-check2"></i><?php endif; ?>
                                    </button>
                                </form>
                                <form action="<?= BASEURL ?>/keluhan/update_status" method="POST" class="m-0 p-0">
                                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                    <input type="hidden" name="keluhan_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="status" value="diperiksa">
                                    <button type="submit" class="dropdown-item py-1.5 px-2 rounded-2 d-flex align-items-center justify-content-between <?= ($item['status'] === 'diperiksa') ? 'active' : '' ?>">
                                        <span><i class="bi bi-clipboard-pulse me-1 text-primary"></i>Sedang Diperiksa</span>
                                        <?php if ($item['status'] === 'diperiksa'): ?><i class="bi bi-check2"></i><?php endif; ?>
                                    </button>
                                </form>
                                <form action="<?= BASEURL ?>/keluhan/update_status" method="POST" class="m-0 p-0">
                                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                    <input type="hidden" name="keluhan_id" value="<?= (int)$item['id'] ?>">
                                    <input type="hidden" name="status" value="selesai">
                                    <button type="submit" class="dropdown-item py-1.5 px-2 rounded-2 d-flex align-items-center justify-content-between <?= ($item['status'] === 'selesai') ? 'active' : '' ?>">
                                        <span><i class="bi bi-check-circle-fill me-1 text-success"></i>Selesai Ditangani</span>
                                        <?php if ($item['status'] === 'selesai'): ?><i class="bi bi-check2"></i><?php endif; ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center gap-1">
                            <?php if ($item['status'] !== 'selesai'): ?>
                            <?php if (AuthHelper::canInputPemeriksaan()): ?>
                            <a href="<?= BASEURL ?>/pemeriksaan/create?santri_id=<?= $item['santri_id'] ?>&keluhan_id=<?= $item['id'] ?>&keluhan=<?= urlencode($item['keluhan']) ?>" 
                               class="btn btn-sm btn-success py-1 px-2 d-inline-flex align-items-center gap-1 rounded-pill"
                               style="font-size: 11.5px;"
                               title="Lakukan pemeriksaan kesehatan untuk keluhan ini">
                                <i class="bi bi-heart-pulse-fill"></i> Periksa
                            </a>
                            <?php endif; ?>
                            <form action="<?= BASEURL ?>/keluhan/update_status" method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                                <input type="hidden" name="keluhan_id" value="<?= $item['id'] ?>">
                                <input type="hidden" name="status" value="selesai">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 rounded-pill" style="font-size: 11.5px;" title="Tandai Selesai"
                                    onclick="(async()=>{ if(await showYkConfirm({title:'Tandai Selesai?',msg:'Keluhan ini akan ditandai telah selesai ditangani.',type:'success',confirmText:'Ya, Selesaikan',icon:'bi-check2-circle'})) this.closest('form').submit(); })();">
                                    <i class="bi bi-check2"></i> Selesai
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted small" style="font-size: 11.5px;"><i class="bi bi-check2-all text-success me-1"></i>Tuntas</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
