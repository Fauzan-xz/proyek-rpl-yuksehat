<?php SessionHelper::flash('pesan'); ?>

<?php if ($data['role'] !== 'santri'): ?>
<!-- Filter Bar (hanya admin & pengurus) -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3">
        <form action="<?= BASEURL; ?>/pemeriksaan" method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">Cari Santri / Keluhan</label>
                <input type="text" name="search" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($data['keyword'] ?? '') ?>" placeholder="Nama, NIS, atau keluhan...">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">Tanggal Dari</label>
                <input type="date" name="tanggal_dari" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($data['tanggal_dari'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">Tanggal Sampai</label>
                <input type="date" name="tanggal_sampai" class="form-control form-control-sm"
                       value="<?= htmlspecialchars($data['tanggal_sampai'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($data['filter']) && $data['filter'] === 'rujukan'): ?>
    <div class="alert alert-danger d-flex justify-content-between align-items-center mb-4 py-2 px-3 rounded-4 shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-hospital-fill fs-5"></i>
            <span>Menampilkan data pemeriksaan dengan <strong>rekomendasi rujukan ke faskes / rumah sakit</strong>.</span>
        </div>
        <a href="<?= BASEURL; ?>/pemeriksaan" class="btn btn-outline-danger btn-sm rounded-pill px-3">Tampilkan Semua</a>
    </div>
<?php elseif (!empty($data['filter']) && $data['filter'] === 'uks'): ?>
    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-4 py-2 px-3 rounded-4 shadow-sm" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-bandaid-fill fs-5"></i>
            <span>Menampilkan data santri yang <strong>sedang dirawat / diobservasi di UKS</strong>.</span>
        </div>
        <a href="<?= BASEURL; ?>/pemeriksaan" class="btn btn-outline-warning btn-sm rounded-pill px-3">Tampilkan Semua</a>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-clipboard2-pulse text-success me-2"></i>
                <?= $data['role'] === 'santri' ? 'Riwayat Pemeriksaan Saya' : 'Daftar Pemeriksaan' ?>
            </h5>
            <?php if (AuthHelper::canInputPemeriksaan()): ?>
                <a href="<?= BASEURL; ?>/pemeriksaan/create" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-lg"></i> Input Pemeriksaan
                </a>
            <?php endif; ?>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 120px; min-width: 105px;">Tanggal</th>
                        <?php if ($data['role'] !== 'santri'): ?>
                        <th style="width: 170px; min-width: 140px;">Santri</th>
                        <?php endif; ?>
                        <th style="width: 170px; min-width: 130px;">Keluhan</th>
                        <th style="min-width: 220px; max-width: 280px;">Diagnosa / Hasil</th>
                        <th style="width: 85px;">Suhu</th>
                        <th style="width: 130px; min-width: 110px;">Pemeriksa</th>
                        <th class="text-center" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['list'])): ?>
                        <tr><td colspan="<?= $data['role'] !== 'santri' ? 7 : 6 ?>" class="text-center py-4 text-muted">Belum ada data pemeriksaan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data['list'] as $p): ?>
                        <tr>
                            <td class="text-nowrap">
                                <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($p['tanggal_pemeriksaan'])) ?></span><br>
                                <small class="text-muted"><?= date('H:i', strtotime($p['tanggal_pemeriksaan'])) ?> WIB</small>
                            </td>
                            <?php if ($data['role'] !== 'santri'): ?>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($p['nama_santri']) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($p['nis']) ?></small>
                                <span class="badge bg-secondary-subtle text-secondary border ms-1"><?= htmlspecialchars($p['kelas'] ?? '-') ?></span>
                            </td>
                            <?php endif; ?>
                            <td>
                                <div class="line-clamp-3 small text-secondary" style="line-height: 1.45; word-break: break-word;" title="<?= htmlspecialchars($p['keluhan']) ?>">
                                    <?= htmlspecialchars($p['keluhan']) ?>
                                </div>
                            </td>
                            <td>
                                <?= MedicalFormatHelper::renderTablePreview(
                                    $p['hasil_pemeriksaan'] ?? '',
                                    BASEURL . '/pemeriksaan/show/' . $p['id'],
                                    160
                                ) ?>
                            </td>
                            <td>
                                <?php if ($p['suhu']): ?>
                                    <span class="badge <?= $p['suhu'] >= 38 ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $p['suhu'] ?>&deg;C
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($p['nama_pemeriksa']) ?></div>
                                <small class="text-muted">Poskestren</small>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="<?= BASEURL; ?>/pemeriksaan/show/<?= $p['id'] ?>"
                                   class="btn btn-sm btn-info text-white" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if ($data['role'] !== 'santri'): ?>
                                <a href="<?= BASEURL; ?>/pemeriksaan/edit/<?= $p['id'] ?>"
                                   class="btn btn-sm btn-warning text-white" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= BASEURL; ?>/pemeriksaan/delete/<?= $p['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= AuthHelper::generateCSRF() ?>">
                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="(async()=>{ if(await showYkConfirm({title:'Hapus Pemeriksaan?',msg:'Data pemeriksaan ini akan dihapus secara permanen dan tidak dapat dipulihkan.',type:'danger',confirmText:'Ya, Hapus'})) this.closest('form').submit(); })();" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($data['total_pages'] > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-end mb-0">
                <?php
                $prev = $data['page'] - 1;
                $next = $data['page'] + 1;
                $role = $data['role'];
                $params = $role !== 'santri' ? http_build_query(array_filter([
                    'search'         => $data['keyword'] ?? '',
                    'tanggal_dari'   => $data['tanggal_dari'] ?? '',
                    'tanggal_sampai' => $data['tanggal_sampai'] ?? '',
                    'filter'         => $data['filter'] ?? '',
                ])) : '';
                ?>
                <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/pemeriksaan?page=<?= $prev ?>&<?= $params ?>">Sebelumnya</a>
                </li>
                <?php for ($i = 1; $i <= $data['total_pages']; $i++): ?>
                <li class="page-item <?= $i == $data['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/pemeriksaan?page=<?= $i ?>&<?= $params ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $data['page'] >= $data['total_pages'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/pemeriksaan?page=<?= $next ?>&<?= $params ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
