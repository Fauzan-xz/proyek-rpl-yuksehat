<div class="row g-3 mb-4">
    <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark">
                <i class="bi bi-bell-fill text-success me-2"></i><?= ($data['role'] ?? '') === 'santri' ? 'Pusat Notifikasi Santri' : 'Pusat Notifikasi Penugasan' ?>
            </h5>
            <p class="text-secondary small mb-0">
                <?= ($data['role'] ?? '') === 'santri' 
                    ? 'Daftar pemberitahuan status keluhan dan hasil pemeriksaan kesehatan Anda.' 
                    : 'Daftar pemberitahuan penugasan antrean keluhan santri yang ditujukan khusus kepada Anda.' ?>
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if (($data['unread_count'] ?? 0) > 0): ?>
            <form action="<?= BASEURL ?>/notifikasi/read_all" method="POST" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                <button type="submit" class="btn btn-outline-success btn-sm px-3 shadow-sm rounded-pill">
                    <i class="bi bi-check2-all me-1"></i>Tandai Semua Dibaca
                </button>
            </form>
            <?php endif; ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill shadow-sm" style="font-size: 13px;">
                <i class="bi bi-envelope-open me-1"></i><?= (int)($data['unread_count'] ?? 0) ?> Belum Dibaca
            </span>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-2 d-flex gap-2">
        <a href="<?= BASEURL ?>/notifikasi" class="btn btn-sm <?= empty($data['filter']) ? 'btn-success text-white' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-inbox-fill me-1"></i>Semua Notifikasi (<?= (int)$data['total'] ?>)
        </a>
        <a href="<?= BASEURL ?>/notifikasi?filter=unread" class="btn btn-sm <?= ($data['filter'] ?? '') === 'unread' ? 'btn-success text-white' : 'btn-light border text-secondary' ?> px-3 rounded-pill fw-medium">
            <i class="bi bi-bell me-1"></i>Belum Dibaca (<?= (int)$data['unread_count'] ?>)
        </a>
    </div>
</div>

<!-- Daftar Notifikasi -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-stars text-primary me-2"></i>Riwayat Pemberitahuan</h6>
        <span class="badge bg-light text-dark border"><?= count($data['notifications'] ?? []) ?> Ditampilkan</span>
    </div>
    
    <div class="list-group list-group-flush">
        <?php if (empty($data['notifications'])): ?>
        <div class="p-5 text-center text-muted">
            <i class="bi bi-bell-slash fs-1 text-secondary opacity-50 d-block mb-3"></i>
            <h6 class="fw-bold text-dark">Belum Ada Notifikasi</h6>
            <p class="small text-muted mb-0">
                <?= ($data['role'] ?? '') === 'santri' 
                    ? 'Pemberitahuan saat keluhan Anda diperiksa atau selesai ditangani akan muncul di sini.' 
                    : 'Saat ada keluhan santri yang ditugaskan kepada Anda, pemberitahuan akan muncul di sini.' ?>
            </p>
        </div>
        <?php else: ?>
        <?php foreach ($data['notifications'] as $item): ?>
            <?php
                $isUnread = empty($item['dibaca_pada']);
                $iconClass = 'bi-bell-fill text-secondary';
                $badgeBg = 'bg-light text-secondary';
                
                if ($item['jenis'] === 'penugasan_baru') {
                    $iconClass = 'bi-person-plus-fill text-warning';
                    $badgeBg = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                } elseif ($item['jenis'] === 'keluhan_baru') {
                    $iconClass = 'bi-chat-heart-fill text-danger';
                    $badgeBg = 'bg-danger-subtle text-danger border border-danger-subtle';
                } elseif ($item['jenis'] === 'pengalihan_tugas') {
                    $iconClass = 'bi-arrow-left-right text-info';
                    $badgeBg = 'bg-info-subtle text-info-emphasis border border-info-subtle';
                } elseif ($item['jenis'] === 'status_diperiksa') {
                    $iconClass = 'bi-clipboard-pulse text-primary';
                    $badgeBg = 'bg-primary-subtle text-primary border border-primary-subtle';
                } elseif ($item['jenis'] === 'status_selesai') {
                    $iconClass = 'bi-check-circle-fill text-success';
                    $badgeBg = 'bg-success-subtle text-success border border-success-subtle';
                }
            ?>
            <div class="list-group-item p-3 border-bottom <?= $isUnread ? 'bg-light-subtle' : '' ?>" style="<?= $isUnread ? 'border-left: 4px solid #10b981 !important;' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                         style="width: 42px; height: 42px; background: #f1f5f9;">
                        <i class="bi <?= $iconClass ?> fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex flex-column flex-sm-row sm-align-items-center justify-content-between gap-1 mb-1">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="fw-bold mb-0 text-dark fs-6"><?= htmlspecialchars($item['judul'] ?? 'Notifikasi') ?></h6>
                                <?php if ($isUnread): ?>
                                <span class="badge bg-danger rounded-pill" style="font-size: 10px;">Baru</span>
                                <?php endif; ?>
                            </div>
                            <span class="text-muted small" style="font-size: 11.5px;">
                                <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($item['created_at'])) ?> WIB
                            </span>
                        </div>
                        <p class="text-secondary small mb-2 text-break" style="font-size: 13px; line-height: 1.5;">
                            <?= nl2br(htmlspecialchars($item['pesan'] ?? '')) ?>
                        </p>
                        <div class="d-flex align-items-center justify-content-between pt-1">
                            <span class="badge <?= $badgeBg ?> rounded-pill" style="font-size: 11px;">
                                <?= htmlspecialchars(ucwords(str_replace('_', ' ', $item['jenis'] ?? 'umum'))) ?>
                            </span>
                            <button type="button" 
                                    class="btn btn-sm btn-outline-success py-1 px-3 rounded-pill btn-detail-notif" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#modalDetail<?= $item['id'] ?>" 
                                    data-notif-id="<?= $item['id'] ?>"
                                    style="font-size: 12px;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka Rincian
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Detail Notifikasi & Keluhan -->
            <div class="modal fade" id="modalDetail<?= $item['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $item['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="modal-header border-bottom py-3 px-4 bg-light">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; background: #ecfdf5;">
                                    <i class="bi <?= $iconClass ?> fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="modal-title fw-bold text-dark mb-0" id="modalLabel<?= $item['id'] ?>"><?= htmlspecialchars($item['judul'] ?? 'Rincian Pemberitahuan') ?></h6>
                                    <small class="text-muted" style="font-size: 11px;">
                                        <i class="bi bi-clock me-1"></i><?= date('d M Y, H:i', strtotime($item['created_at'])) ?> WIB
                                    </small>
                                </div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Pesan Pemberitahuan -->
                            <div class="p-3 rounded-3 mb-3 bg-light border">
                                <div class="fw-semibold text-dark small mb-1"><i class="bi bi-info-circle text-primary me-1"></i>Isi Notifikasi:</div>
                                <div class="text-secondary small" style="font-size: 13px; line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($item['pesan'] ?? '')) ?>
                                </div>
                            </div>

                            <?php if (!empty($item['nama_santri']) || !empty($item['keluhan_teks'])): ?>
                            <!-- Rincian Keluhan Santri -->
                            <div class="card border border-light-subtle rounded-3 mb-3 shadow-none">
                                <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fw-bold text-dark small"><i class="bi bi-person-lines-fill text-success me-1.5"></i>Informasi Santri & Keluhan</span>
                                    <?php
                                        $statusBadge = 'bg-secondary-subtle text-secondary';
                                        $statusLabel = ucfirst($item['keluhan_status'] ?? 'Menunggu');
                                        if (($item['keluhan_status'] ?? '') === 'menunggu') {
                                            $statusBadge = 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
                                        } elseif (($item['keluhan_status'] ?? '') === 'diperiksa') {
                                            $statusBadge = 'bg-primary-subtle text-primary border border-primary-subtle';
                                        } elseif (($item['keluhan_status'] ?? '') === 'selesai') {
                                            $statusBadge = 'bg-success-subtle text-success border border-success-subtle';
                                            $statusLabel = 'Selesai Ditangani';
                                        }
                                    ?>
                                    <span class="badge <?= $statusBadge ?> rounded-pill" style="font-size: 11px;">
                                        <?= htmlspecialchars($statusLabel) ?>
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2 pb-2 border-bottom">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block" style="font-size: 11px;">Nama Santri:</span>
                                            <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($item['nama_santri'] ?? '-') ?></span>
                                            <span class="text-muted small d-block" style="font-size: 11.5px;">NIS: <?= htmlspecialchars($item['nis_santri'] ?? '-') ?></span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block" style="font-size: 11px;">Kelas & Kamar:</span>
                                            <span class="fw-medium text-dark">Kelas <?= htmlspecialchars($item['kelas_santri'] ?? '-') ?> • Kamar <?= htmlspecialchars($item['kamar_santri'] ?? '-') ?></span>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-2">
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block" style="font-size: 11px;">Waktu Lapor Keluhan:</span>
                                            <span class="small text-dark fw-medium">
                                                <?= !empty($item['tanggal_keluhan']) ? date('d M Y, H:i', strtotime($item['tanggal_keluhan'])) . ' WIB' : '-' ?>
                                            </span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted small d-block" style="font-size: 11px;">Petugas Pemeriksa:</span>
                                            <span class="small text-dark fw-medium">
                                                <i class="bi bi-person-badge text-success me-1"></i><?= htmlspecialchars($item['nama_petugas'] ?? 'Belum Ditugaskan') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <span class="text-muted small d-block mb-1" style="font-size: 11px;">Keluhan yang Dirasakan:</span>
                                        <div class="p-2.5 rounded-2 bg-light text-dark fw-medium small" style="font-size: 12.5px;">
                                            <?= nl2br(htmlspecialchars($item['keluhan_teks'] ?? '-')) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($item['pemeriksaan_id']) && (!empty($item['hasil_pemeriksaan']) || !empty($item['tindakan']) || !empty($item['obat']))): ?>
                            <!-- Rincian Catatan Medis Pemeriksaan -->
                            <div class="card border border-success-subtle rounded-3 shadow-none bg-light-subtle">
                                <div class="card-header bg-success-subtle text-success-emphasis py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fw-bold small"><i class="bi bi-clipboard2-pulse-fill me-1.5"></i>Hasil Pemeriksaan Poskestren</span>
                                    <span class="small" style="font-size: 11px;">
                                        <?= !empty($item['tanggal_pemeriksaan']) ? date('d M Y, H:i', strtotime($item['tanggal_pemeriksaan'])) . ' WIB' : '' ?>
                                    </span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2 pb-2 border-bottom">
                                        <div class="col-3">
                                            <span class="text-muted small d-block" style="font-size: 10.5px;">Suhu:</span>
                                            <span class="fw-bold text-dark small"><?= !empty($item['suhu']) ? htmlspecialchars($item['suhu']) . ' °C' : '-' ?></span>
                                        </div>
                                        <div class="col-3">
                                            <span class="text-muted small d-block" style="font-size: 10.5px;">Tekanan Darah:</span>
                                            <span class="fw-bold text-dark small"><?= !empty($item['tekanan_darah']) ? htmlspecialchars($item['tekanan_darah']) : '-' ?></span>
                                        </div>
                                        <div class="col-3">
                                            <span class="text-muted small d-block" style="font-size: 10.5px;">Berat Badan:</span>
                                            <span class="fw-bold text-dark small"><?= !empty($item['berat_badan']) ? htmlspecialchars($item['berat_badan']) . ' kg' : '-' ?></span>
                                        </div>
                                        <div class="col-3">
                                            <span class="text-muted small d-block" style="font-size: 10.5px;">Tinggi Badan:</span>
                                            <span class="fw-bold text-dark small"><?= !empty($item['tinggi_badan']) ? htmlspecialchars($item['tinggi_badan']) . ' cm' : '-' ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($item['hasil_pemeriksaan'])): ?>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block" style="font-size: 11px;">Hasil Pemeriksaan / Diagnosa:</span>
                                        <div class="small text-dark fw-medium mt-0.5"><?= nl2br(htmlspecialchars($item['hasil_pemeriksaan'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['tindakan'])): ?>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block" style="font-size: 11px;">Tindakan Medis:</span>
                                        <div class="small text-dark fw-medium mt-0.5"><?= nl2br(htmlspecialchars($item['tindakan'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['obat'])): ?>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block" style="font-size: 11px;">Obat yang Diberikan:</span>
                                        <div class="small text-success fw-bold mt-0.5"><i class="bi bi-capsule me-1"></i><?= htmlspecialchars($item['obat']) ?></div>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($item['catatan_medis'])): ?>
                                    <div>
                                        <span class="text-muted small d-block" style="font-size: 11px;">Catatan Tambahan:</span>
                                        <div class="small text-secondary fst-italic mt-0.5"><?= nl2br(htmlspecialchars($item['catatan_medis'])) ?></div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Tutup</button>
                            
                            <div>
                                <?php if (($data['role'] ?? '') === 'santri'): ?>
                                <a href="<?= BASEURL ?>/keluhan" class="btn btn-success btn-sm px-3 rounded-pill">
                                    <i class="bi bi-card-checklist me-1"></i>Lihat Keluhan Saya
                                </a>
                                <?php else: ?>
                                    <?php if (($item['keluhan_status'] ?? '') === 'menunggu' && !empty($item['referensi_id'])): ?>
                                    <a href="<?= BASEURL ?>/pemeriksaan/create?santri_id=<?= (int)$item['keluhan_santri_id'] ?>&keluhan_id=<?= (int)$item['referensi_id'] ?>&keluhan=<?= urlencode($item['keluhan_teks'] ?? '') ?>" class="btn btn-success btn-sm px-3 rounded-pill me-1">
                                        <i class="bi bi-heart-pulse-fill me-1"></i>Periksa Santri Sekarang
                                    </a>
                                    <a href="<?= BASEURL ?>/keluhan?status=menunggu" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                                        Ke Antrean
                                    </a>
                                    <?php elseif (($item['keluhan_status'] ?? '') === 'diperiksa'): ?>
                                    <a href="<?= BASEURL ?>/keluhan?status=diperiksa" class="btn btn-primary btn-sm px-3 rounded-pill">
                                        <i class="bi bi-inbox-fill me-1"></i>Buka Antrean Diperiksa
                                    </a>
                                    <?php elseif (($item['keluhan_status'] ?? '') === 'selesai'): ?>
                                    <a href="<?= BASEURL ?>/keluhan?status=selesai" class="btn btn-outline-success btn-sm px-3 rounded-pill">
                                        <i class="bi bi-check2-all me-1"></i>Buka Riwayat Selesai
                                    </a>
                                    <?php else: ?>
                                    <a href="<?= BASEURL ?>/keluhan" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                                        Buka Antrean Keluhan
                                    </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginasi jika lebih dari 1 halaman -->
    <?php if (($data['total_pages'] ?? 1) > 1): ?>
    <div class="card-footer bg-white py-3">
        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= ($data['page'] <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/notifikasi?page=<?= $data['page'] - 1 ?>&filter=<?= urlencode($data['filter']) ?>">Sebelumnya</a>
                </li>
                <?php for ($p = 1; $p <= $data['total_pages']; $p++): ?>
                <li class="page-item <?= ($p == $data['page']) ? 'active' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/notifikasi?page=<?= $p ?>&filter=<?= urlencode($data['filter']) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($data['page'] >= $data['total_pages']) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= BASEURL ?>/notifikasi?page=<?= $data['page'] + 1 ?>&filter=<?= urlencode($data['filter']) ?>">Berikutnya</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailButtons = document.querySelectorAll('.btn-detail-notif');
    detailButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const notifId = this.getAttribute('data-notif-id');
            const listItem = this.closest('.list-group-item');
            const unreadBadge = listItem ? listItem.querySelector('.badge.bg-danger') : null;
            
            // Tandai sudah dibaca via AJAX
            if (notifId && unreadBadge) {
                fetch('<?= BASEURL ?>/notifikasi/mark_read_ajax/' + notifId, {
                    method: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data && data.success) {
                        if (unreadBadge) unreadBadge.remove();
                        if (listItem) {
                            listItem.classList.remove('bg-light-subtle');
                            listItem.style.borderLeft = 'none';
                        }
                        // Update badge unread di header jika ada
                        const topBadge = document.querySelector('.badge.bg-success-subtle');
                        if (topBadge && data.unread_count !== undefined) {
                            topBadge.innerHTML = '<i class="bi bi-envelope-open me-1"></i>' + data.unread_count + ' Belum Dibaca';
                        }
                    }
                })
                .catch(err => console.error('Gagal memperbarui status notifikasi:', err));
            }
        });
    });
});
</script>

