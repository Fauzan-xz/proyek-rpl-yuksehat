<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="fw-bold mb-0"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Laporan Kesehatan Santri</h4>
            <a href="#" onclick="document.getElementById('exportForm').submit(); return false;" class="btn btn-success fw-bold">
                <i class="bi bi-file-earmark-excel"></i> Export Excel
            </a>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white pt-4 border-bottom-0">
        <h6 class="fw-bold"><i class="bi bi-funnel me-2"></i>Filter Laporan</h6>
    </div>
    <div class="card-body p-4">
        <form action="<?= BASEURL ?>/laporan" method="GET" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Mulai Tanggal</label>
                    <input type="date" name="tgl_mulai" class="form-control form-control-sm" value="<?= htmlspecialchars($data['filters']['tgl_mulai']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Sampai Tanggal</label>
                    <input type="date" name="tgl_akhir" class="form-control form-control-sm" value="<?= htmlspecialchars($data['filters']['tgl_akhir']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Nama Santri</label>
                    <input type="text" name="nama_santri" class="form-control form-control-sm" placeholder="Ketik nama..." value="<?= htmlspecialchars($data['filters']['nama_santri']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Keluhan</label>
                    <input type="text" name="keluhan" class="form-control form-control-sm" placeholder="Ketik keluhan..." value="<?= htmlspecialchars($data['filters']['keluhan']) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Kelas</label>
                    <select name="kelas" class="form-select form-select-sm">
                        <option value="">Semua Kelas</option>
                        <?php foreach($data['filter_options']['kelas'] as $k): ?>
                            <option value="<?= htmlspecialchars($k['kelas']) ?>" <?= $data['filters']['kelas'] == $k['kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($k['kelas']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Kamar</label>
                    <select name="kamar" class="form-select form-select-sm">
                        <option value="">Semua Kamar</option>
                        <?php foreach($data['filter_options']['kamar'] as $k): ?>
                            <option value="<?= htmlspecialchars($k['kamar']) ?>" <?= $data['filters']['kamar'] == $k['kamar'] ? 'selected' : '' ?>><?= htmlspecialchars($k['kamar']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="L" <?= $data['filters']['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki (L)</option>
                        <option value="P" <?= $data['filters']['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan (P)</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i class="bi bi-search me-1"></i>Tampilkan Data</button>
                    <a href="<?= BASEURL ?>/laporan" class="btn btn-light btn-sm ms-2 border"><i class="bi bi-arrow-clockwise"></i></a>
                </div>
            </div>
        </form>
        
        <!-- Hidden Form for Export -->
        <form action="<?= BASEURL ?>/laporan/export" method="GET" id="exportForm">
            <?php foreach($data['filters'] as $key => $val): ?>
                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($val) ?>">
            <?php endforeach; ?>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3 text-center" style="width: 50px;">No</th>
                        <th style="width: 120px; min-width: 110px;">Tanggal</th>
                        <th style="width: 180px; min-width: 150px;">Santri</th>
                        <th style="width: 180px; min-width: 140px;">Keluhan</th>
                        <th style="min-width: 220px; max-width: 280px;">Diagnosa / Hasil</th>
                        <th style="width: 180px; min-width: 140px;">Tindakan / Obat</th>
                        <th style="width: 140px; min-width: 110px;">Pemeriksa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($data['laporan_data'])): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Tidak ada data yang sesuai dengan filter.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($data['laporan_data'] as $row): ?>
                        <tr>
                            <td class="ps-3 text-center text-muted"><?= $no++ ?></td>
                            <td class="text-nowrap">
                                <span class="fw-semibold text-dark"><?= date('d M Y', strtotime($row['tanggal_pemeriksaan'])) ?></span><br>
                                <small class="text-muted"><?= date('H:i', strtotime($row['tanggal_pemeriksaan'])) ?> WIB</small>
                            </td>
                            <td>
                                <strong class="text-dark"><?= htmlspecialchars($row['nama_santri']) ?></strong><br>
                                <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($row['nis']) ?></span>
                                <span class="badge bg-secondary-subtle text-secondary border"><?= htmlspecialchars($row['kelas']) ?> - <?= htmlspecialchars($row['kamar']) ?></span>
                            </td>
                            <td>
                                <div class="line-clamp-3 small text-secondary" style="line-height: 1.45; word-break: break-word;" title="<?= htmlspecialchars($row['keluhan']) ?>">
                                    <?= htmlspecialchars($row['keluhan']) ?>
                                </div>
                            </td>
                            <td>
                                <?= MedicalFormatHelper::renderTablePreview(
                                    $row['hasil_pemeriksaan'],
                                    BASEURL . '/pemeriksaan/show/' . $row['id'],
                                    160
                                ) ?>
                            </td>
                            <td>
                                <?php if (!empty($row['tindakan']) && $row['tindakan'] !== '-'): ?>
                                    <div class="small fw-semibold text-primary mb-1 line-clamp-2" title="<?= htmlspecialchars($row['tindakan']) ?>">
                                        <i class="bi bi-bandaid me-1"></i><?= htmlspecialchars($row['tindakan']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($row['obat']) && $row['obat'] !== '-'): ?>
                                    <div class="small text-muted line-clamp-2" title="<?= htmlspecialchars($row['obat']) ?>">
                                        <i class="bi bi-capsule me-1 text-success"></i><?= htmlspecialchars($row['obat']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ((empty($row['tindakan']) || $row['tindakan'] === '-') && (empty($row['obat']) || $row['obat'] === '-')): ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?= htmlspecialchars($row['nama_pemeriksa'] ?: 'Admin') ?></div>
                                <small class="text-muted">Poskestren</small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="p-3 bg-light text-end rounded-bottom-4">
            <small class="text-muted">Total Data: <strong><?= count($data['laporan_data']) ?></strong> baris</small>
        </div>
    </div>
</div>
