<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form action="<?= BASEURL; ?>/santri" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Cari Nama/NIS</label>
                <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($data['keyword']) ?>" placeholder="Pencarian...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kelas</label>
                <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($data['filter_kelas']) ?>" placeholder="Semua Kelas">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kamar</label>
                <input type="text" name="kamar" class="form-control" value="<?= htmlspecialchars($data['filter_kamar']) ?>" placeholder="Semua Kamar">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">Daftar Santri</h5>
            <a href="<?= BASEURL; ?>/santri/create" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Santri</a>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>NIS</th>
                        <th>Nama</th>
                        <th>L/P</th>
                        <th>Kelas</th>
                        <th>Kamar</th>
                        <th>Gol. Darah</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data['santri'])): ?>
                        <tr><td colspan="7" class="text-center">Data tidak ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach($data['santri'] as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['nis']) ?></td>
                            <td><?= htmlspecialchars($s['nama']) ?></td>
                            <td><?= $s['jenis_kelamin'] ?></td>
                            <td><?= htmlspecialchars($s['kelas']) ?></td>
                            <td><?= htmlspecialchars($s['kamar']) ?></td>
                            <td>
                                <?php if($s['golongan_darah']): ?>
                                    <span class="badge bg-danger"><?= htmlspecialchars($s['golongan_darah']) ?></span>
                                <?php else: ?>
                                    - 
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASEURL; ?>/santri/detail/<?= $s['id'] ?>" class="btn btn-sm btn-info text-white" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="<?= BASEURL; ?>/santri/edit/<?= $s['id'] ?>" class="btn btn-sm btn-warning text-white" title="Edit"><i class="bi bi-pencil"></i></a>
                                
                                <form action="<?= BASEURL; ?>/santri/delete/<?= $s['id'] ?>" method="POST" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= AuthHelper::generateCSRF(); ?>">
                                    <button type="button" class="btn btn-sm btn-danger"
                                        onclick="(async()=>{ if(await showYkConfirm({title:'Nonaktifkan Santri?',msg:'Akun santri ini akan dinonaktifkan. Data tetap tersimpan di sistem.',type:'danger',confirmText:'Ya, Nonaktifkan',icon:'bi-person-x-fill'})) this.closest('form').submit(); })();"
                                        title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Component -->
        <?php if ($data['total_pages'] > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-end mb-0">
                <?php 
                $prev = $data['page'] - 1;
                $next = $data['page'] + 1;
                $params = http_build_query([
                    'search' => $data['keyword'],
                    'kelas' => $data['filter_kelas'],
                    'kamar' => $data['filter_kamar']
                ]);
                ?>
                <li class="page-item <?= $data['page'] <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL; ?>/santri?page=<?= $prev ?>&<?= $params ?>">Sebelumnya</a>
                </li>
                
                <?php for($i = 1; $i <= $data['total_pages']; $i++): ?>
                    <li class="page-item <?= $i == $data['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="<?= BASEURL; ?>/santri?page=<?= $i ?>&<?= $params ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <li class="page-item <?= $data['page'] >= $data['total_pages'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL; ?>/santri?page=<?= $next ?>&<?= $params ?>">Selanjutnya</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
