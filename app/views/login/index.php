<div class="login-bg min-vh-100 d-flex align-items-center justify-content-center py-5">
    <div class="row w-100 justify-content-center" style="max-width: 480px; margin: 0 auto; padding: 0 1rem;">
        <div class="col-12">
            <?php SessionHelper::flash('pesan'); ?>

            <!-- Header Branding -->
            <div class="text-center mb-4">
                <div class="login-logo-wrap mx-auto mb-3">
                    <div class="login-logo-icon">
                        <i class="bi bi-heart-pulse-fill"></i>
                    </div>
                    <div class="login-logo-pulse"></div>
                </div>
                <div class="login-brand-name mb-1">YukSehat</div>
                <p class="login-brand-sub">Sistem Kesehatan Santri Pesantren</p>
            </div>

            <!-- Login Card -->
            <div class="login-card">
                <div class="login-card-inner">
                    <h5 class="fw-bold mb-1 text-dark">Selamat Datang!</h5>
                    <p class="text-muted small mb-4">Masuk untuk mengakses sistem kesehatan santri.</p>

                    <form action="<?= BASEURL; ?>/login/auth" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">

                        <div class="mb-3">
                            <label class="login-label">Username / NIS</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon"><i class="bi bi-person-fill"></i></span>
                                <input type="text" name="username" class="login-input" placeholder="Masukkan username atau NIS" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="login-label">Password</label>
                            <div class="login-input-wrap">
                                <span class="login-input-icon"><i class="bi bi-lock-fill"></i></span>
                                <input type="password" name="password" class="login-input" placeholder="Masukkan password" required>
                            </div>
                        </div>

                        <button type="submit" class="login-btn">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Dashboard
                        </button>
                    </form>
                </div>
                <div class="login-card-footer">
                    <span class="text-muted small">Belum punya akun?</span>
                    <a href="<?= BASEURL; ?>/register" class="text-success fw-bold text-decoration-none small ms-1">Daftar Akun Santri</a>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= BASEURL; ?>" class="login-back-link"><i class="bi bi-arrow-left"></i> Kembali ke Beranda</a>
            </div>
        </div>
    </div>
</div>