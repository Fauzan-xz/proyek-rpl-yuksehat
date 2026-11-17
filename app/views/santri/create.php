<div class="row justify-content-center">
    <div class="col-md-10">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-success text-white p-3 rounded-top-4">
                <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i> Tambah Data Santri</h5>
            </div>
            <div class="card-body p-4">
                <?php SessionHelper::flash('pesan'); ?>
                
                <form action="<?= BASEURL; ?>/santri/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                    
                    <h6 class="fw-bold border-bottom pb-2 mb-3">Informasi Utama</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">NIS *</label>
                            <input type="text" name="nis" class="form-control" required placeholder="Nomor Induk Santri">
                            <small class="text-muted">Juga akan menjadi default Username dan Password.</small>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" required placeholder="Sesuai KTP/KK">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Jenis Kelamin *</label>
                            <select name="jenis_kelamin" class="form-select" required>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Tanggal Lahir *</label>
                            <input type="date" name="tanggal_lahir" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Nomor HP</label>
                            <input type="text" name="no_hp" class="form-control">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kelas</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Contoh: 10-IPA">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Kamar Asrama</label>
                            <input type="text" name="kamar" class="form-control" placeholder="Contoh: A1">
                        </div>
                    </div>

                    <h6 class="fw-bold border-bottom pb-2 mb-3 mt-4">Informasi Medis (Opsional)</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Golongan Darah</label>
                            <select name="golongan_darah" class="form-select">
                                <option value="">Tidak Diketahui</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="AB">AB</option>
                                <option value="O">O</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Alergi</label>
                            <input type="text" name="alergi" class="form-control" placeholder="Obat, makanan, atau debu">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">Riwayat Penyakit Bawaan</label>
                            <textarea name="riwayat_penyakit" class="form-control" rows="3" placeholder="Kosongkan jika tidak ada"></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="<?= BASEURL; ?>/santri" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Simpan Data</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
