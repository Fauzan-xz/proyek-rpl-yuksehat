<div class="row g-3 mb-4">
    <div class="col-12 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-people-gear text-success me-2"></i>Pengelolaan Pengguna & Role</h5>
            <p class="text-secondary small mb-0">Kelola akun pengguna sistem, pembagian hak akses (Admin, Pengurus, Santri), dan status keaktifan akun.</p>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm px-3 py-2 rounded-pill fw-semibold shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalTambahUser">
                <i class="bi bi-person-plus-fill"></i> Tambah Pengguna
            </button>
        </div>
    </div>
</div>

<!-- Card Filter & Search -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="<?= BASEURL ?>/users" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Cari username, nama, atau NIS..." value="<?= htmlspecialchars($data['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-12 col-md-4">
                <select name="role" class="form-select form-select-sm">
                    <option value="">Semua Role (Admin, Pengurus, Santri)</option>
                    <option value="admin" <?= ($data['role_filter'] ?? '') === 'admin' ? 'selected' : '' ?>>Role: Admin</option>
                    <option value="pengurus" <?= ($data['role_filter'] ?? '') === 'pengurus' ? 'selected' : '' ?>>Role: Pengurus</option>
                    <option value="santri" <?= ($data['role_filter'] ?? '') === 'santri' ? 'selected' : '' ?>>Role: Santri</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-3 flex-grow-1"><i class="bi bi-filter"></i> Filter</button>
                <a href="<?= BASEURL ?>/users" class="btn btn-outline-secondary btn-sm px-3" title="Reset filter"><i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>
</div>

<!-- Table Users -->
<div class="card border-0 shadow-sm rounded-3 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-lines-fill text-primary me-2"></i>Daftar Pengguna Terdaftar</h6>
        <span class="badge bg-light text-dark border"><?= count($data['users'] ?? []) ?> Pengguna</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th style="width: 50px;" class="text-center">#</th>
                    <th>Nama & Profil</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Keterangan</th>
                    <th style="width: 100px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data['users'])): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        Tidak ada pengguna ditemukan.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($data['users'] as $idx => $u): ?>
                <tr>
                    <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                                 style="width: 32px; height: 32px; font-size: 13px; background: <?= $u['role'] === 'admin' ? '#10b981' : ($u['role'] === 'pengurus' ? '#3b82f6' : '#8b5cf6') ?>;">
                                <?= strtoupper(substr($u['nama_lengkap'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold text-dark"><?= htmlspecialchars($u['nama_lengkap'] ?? $u['username']) ?></div>
                                <?php if (!empty($u['jabatan'])): ?>
                                    <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($u['jabatan']) ?></div>
                                <?php elseif (!empty($u['nis'])): ?>
                                    <div class="text-muted" style="font-size: 11px;">NIS: <?= htmlspecialchars($u['nis']) ?> • <?= htmlspecialchars($u['kelas'] ?? '-') ?></div>
                                <?php endif; ?>
                                <?php if (!empty($u['no_hp'])): ?>
                                    <div class="text-muted" style="font-size: 11px;"><i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($u['no_hp']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><code><?= htmlspecialchars($u['username']) ?></code></td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-shield-lock-fill me-1"></i>Admin</span>
                        <?php elseif ($u['role'] === 'pengurus'): ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-person-badge-fill me-1"></i>Pengurus</span>
                        <?php else: ?>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-mortarboard-fill me-1"></i>Santri</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['status'] === 'active'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11.5px;">
                                <i class="bi bi-check-circle-fill me-1"></i>Aktif
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1 rounded-pill fw-semibold" style="font-size: 11.5px;">
                                <i class="bi bi-x-circle-fill me-1"></i>Nonaktif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small">
                        Dibuat: <?= !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '-' ?>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 btn-edit-user" 
                                data-id="<?= $u['id'] ?>"
                                data-username="<?= htmlspecialchars($u['username']) ?>"
                                data-nama="<?= htmlspecialchars($u['nama_lengkap'] ?? '') ?>"
                                data-no_hp="<?= htmlspecialchars($u['no_hp'] ?? '') ?>"
                                data-role="<?= $u['role'] ?>"
                                data-status="<?= $u['status'] ?>"
                                title="Ubah Role / Status">
                            <i class="bi bi-pencil-square"></i> Ubah
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pengguna -->
<div class="modal fade" id="modalTambahUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= BASEURL ?>/users/store" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-person-plus text-success me-2"></i>Tambah Pengguna Baru</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control form-control-sm" required placeholder="Contoh: Ronal">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control form-control-sm" required placeholder="username_login">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select form-select-sm" required>
                                <option value="pengurus">Pengurus</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control form-control-sm" required placeholder="Masukkan kata sandi aman">
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Jabatan</label>
                            <input type="text" name="jabatan" class="form-control form-control-sm" placeholder="Contoh: Staf Poskestren">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">No. HP</label>
                            <input type="text" name="no_hp" class="form-control form-control-sm" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-circle me-1"></i>Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ubah Role / Status -->
<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= BASEURL ?>/users/update" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                <input type="hidden" name="user_id" id="editUserId" value="">
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Ubah Role & Status Pengguna</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Username</label>
                        <input type="text" id="editUsername" class="form-control form-control-sm bg-light" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nama Tampilan</label>
                        <input type="text" name="nama" id="editNama" class="form-control form-control-sm">
                    </div>
                    <div class="mb-3" id="editNoHpGroup">
                        <label class="form-label small fw-semibold">No. HP (WhatsApp)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="bi bi-whatsapp text-success"></i></span>
                            <input type="text" name="no_hp" id="editNoHp" class="form-control" placeholder="Contoh: 081234567890">
                        </div>
                        <div class="form-text" style="font-size: 11px;">Digunakan untuk menerima notifikasi penugasan & keluhan santri via WhatsApp.</div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Role Akun</label>
                            <select name="role" id="editRole" class="form-select form-select-sm" required>
                                <option value="admin">Admin</option>
                                <option value="pengurus">Pengurus</option>
                                <option value="santri">Santri</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Status Akun</label>
                            <select name="status" id="editStatus" class="form-select form-select-sm" required>
                                <option value="active">Active (Aktif)</option>
                                <option value="inactive">Inactive (Nonaktif)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-save me-1"></i>Perbarui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editButtons = document.querySelectorAll('.btn-edit-user');
    const modalEdit = new bootstrap.Modal(document.getElementById('modalEditUser'));
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('editUserId').value = this.dataset.id;
            document.getElementById('editUsername').value = this.dataset.username;
            document.getElementById('editNama').value = this.dataset.nama;
            document.getElementById('editNoHp').value = this.dataset.no_hp || '';
            document.getElementById('editRole').value = this.dataset.role;
            document.getElementById('editStatus').value = this.dataset.status;
            modalEdit.show();
        });
    });
});
</script>
