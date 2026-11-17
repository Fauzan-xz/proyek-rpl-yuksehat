<div class="row">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body text-center p-4">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($data['santri']['nama']) ?>&background=198754&color=fff&size=128" class="rounded-circle mb-3" alt="Avatar">
                <h4 class="fw-bold"><?= htmlspecialchars($data['santri']['nama']) ?></h4>
                <p class="text-muted mb-1">NIS: <?= htmlspecialchars($data['santri']['nis']) ?></p>
                <span class="badge bg-primary mb-3">Kamar <?= htmlspecialchars($data['santri']['kamar']) ?></span>
                
                <div class="d-grid gap-2">
                    <a href="<?= BASEURL; ?>/santri/edit/<?= $data['santri']['id'] ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil"></i> Edit Profil</a>
                </div>
            </div>
            <ul class="list-group list-group-flush border-top">
                <li class="list-group-item"><strong>Jenis Kelamin:</strong> <?= $data['santri']['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></li>
                <li class="list-group-item"><strong>Tanggal Lahir:</strong> <?= htmlspecialchars($data['santri']['tanggal_lahir']) ?></li>
                <li class="list-group-item"><strong>Kelas:</strong> <?= htmlspecialchars($data['santri']['kelas']) ?></li>
                <li class="list-group-item"><strong>No HP:</strong> <?= htmlspecialchars($data['santri']['no_hp']) ?></li>
                <li class="list-group-item"><strong>Golongan Darah:</strong> <?= htmlspecialchars($data['santri']['golongan_darah']) ?></li>
                <li class="list-group-item text-danger"><strong>Alergi:</strong> <?= htmlspecialchars($data['santri']['alergi'] ?: '-') ?></li>
            </ul>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold border-bottom pb-3 mb-3">Riwayat Penyakit Bawaan</h5>
                <p><?= nl2br(htmlspecialchars($data['santri']['riwayat_penyakit'])) ?: 'Tidak ada catatan.' ?></p>
            </div>
        </div>

        <!-- Placeholder for Riwayat Pemeriksaan -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between mb-4">
                    <h5 class="fw-bold">Riwayat Pemeriksaan</h5>
                    <?php if (AuthHelper::canInputPemeriksaan()): ?>
                    <a href="<?= BASEURL; ?>/pemeriksaan/create/<?= $data['santri']['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-plus"></i> Input Baru</a>
                    <?php endif; ?>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Keluhan</th>
                                <th>Hasil</th>
                                <th>Obat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Belum dihubungkan ke tabel riwayat_pemeriksaan secara relasional di View ini,
                                 ini merupakan pengembangan modul selanjutnya -->
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada riwayat pemeriksaan.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
