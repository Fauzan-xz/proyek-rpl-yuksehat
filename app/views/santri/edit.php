<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-warning p-3 rounded-top-4">
                <h5 class="mb-0 text-dark"><i class="bi bi-pencil me-2"></i> Edit Data Santri</h5>
            </div>
            <div class="card-body p-4">
                <?php SessionHelper::flash('pesan'); ?>
                
                <form action="<?= BASEURL; ?>/santri/update/<?= $data['santri']['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                    
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Informasi Utama</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">NIS *</label>
                            <input type="text" name="nis" class="form-control bg-light" value="<?= htmlspecialchars($data['santri']['nis']) ?>" required readonly>
                            <small class="text-muted">NIS tidak dapat diubah.</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['santri']['nama']) ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L" <?= $data['santri']['jenis_kelamin'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="P" <?= $data['santri']['jenis_kelamin'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tanggal Lahir *</label>
                            <input type="date" name="tanggal_lahir" class="form-control" value="<?= htmlspecialchars($data['santri']['tanggal_lahir']) ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Nomor HP</label>
                            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data['santri']['no_hp']) ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kelas</label>
                            <input type="text" name="kelas" class="form-control" value="<?= htmlspecialchars($data['santri']['kelas']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kamar Asrama</label>
                            <input type="text" name="kamar" class="form-control" value="<?= htmlspecialchars($data['santri']['kamar']) ?>">
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4">Informasi Medis</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Golongan Darah</label>
                            <select name="golongan_darah" class="form-select">
                                <option value="">Tidak Diketahui</option>
                                <option value="A" <?= $data['santri']['golongan_darah'] == 'A' ? 'selected' : '' ?>>A</option>
                                <option value="B" <?= $data['santri']['golongan_darah'] == 'B' ? 'selected' : '' ?>>B</option>
                                <option value="AB" <?= $data['santri']['golongan_darah'] == 'AB' ? 'selected' : '' ?>>AB</option>
                                <option value="O" <?= $data['santri']['golongan_darah'] == 'O' ? 'selected' : '' ?>>O</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Alergi</label>
                            <input type="text" name="alergi" class="form-control" value="<?= htmlspecialchars($data['santri']['alergi']) ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Riwayat Penyakit Bawaan</label>
                            <textarea name="riwayat_penyakit" class="form-control" rows="3"><?= htmlspecialchars($data['santri']['riwayat_penyakit']) ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?= BASEURL; ?>/santri" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Perbarui Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
