<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <?php SessionHelper::flash('pesan'); ?>
            <div class="card shadow border-0 rounded-4 p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus display-4 text-success"></i>
                    <h2 class="mt-2 fw-bold">Pendaftaran Santri Baru</h2>
                    <p class="text-muted">Lengkapi data diri untuk membuat akun santri.</p>
                </div>
                <form action="<?= BASEURL; ?>/register/store" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                    
                    <h5 class="fw-bold mb-3 border-bottom pb-2">Informasi Akun</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Username / Email *</label>
                            <input type="text" name="username" class="form-control" placeholder="username" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Password *</label>
                            <input type="password" name="password" class="form-control" placeholder="Min. 8 Karakter" minlength="8" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label fw-bold">Konfirmasi Pass *</label>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi password" minlength="8" required>
                        </div>
                    </div>

                    <h5 class="fw-bold mt-4 mb-3 border-bottom pb-2">Data Diri Santri</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">NIS *</label>
                            <input type="text" name="nis" class="form-control" placeholder="Nomor Induk" required>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Nama Lengkap *</label>
                            <input type="text" name="nama" class="form-control" placeholder="Nama sesuai KK" required>
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
                            <input type="text" name="no_hp" class="form-control" placeholder="08xx">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Kelas</label>
                            <input type="text" name="kelas" class="form-control" placeholder="Misal: 10A">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold">Kamar Asrama</label>
                            <input type="text" name="kamar" class="form-control" placeholder="Misal: A1">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill shadow-sm fw-bold">
                        Daftar Sekarang <i class="bi bi-check-circle ms-1"></i>
                    </button>
                    
                    <div class="text-center mt-3 pt-2 border-top">
                        <span class="text-muted small">Sudah punya akun?</span>
                        <a href="<?= BASEURL; ?>/login" class="text-success fw-bold text-decoration-none small ms-1">Masuk di sini</a>
                    </div>
                </form>
            </div>
            
            <div class="text-center mt-3">
                <a href="<?= BASEURL; ?>" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>
