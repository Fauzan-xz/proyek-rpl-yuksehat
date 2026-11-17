<?php
$role = $data['role'] ?? 'admin'; 
$current_page = $data['current_page'] ?? 'dashboard';
?>
<nav id="sidebar">
    <!-- Brand Header -->
    <div class="sidebar-header px-4 py-3 d-flex align-items-center gap-3">
        <div class="sidebar-brand-icon">
            <i class="bi bi-heart-pulse-fill"></i>
        </div>
        <div class="sidebar-brand-text">
            <div class="sidebar-brand-title">YukSehat</div>
            <div class="sidebar-brand-subtitle">Kesehatan Santri</div>
        </div>
    </div>
    
    <!-- User Role Badge -->
    <div class="sidebar-user-badge mx-3 my-2 p-2 px-3 d-flex align-items-center justify-content-between">
        <div>
            <div class="sidebar-user-role"><?= $role == 'admin' ? 'Administrator' : ucfirst($role) ?></div>
            <div class="sidebar-user-desc"><?= $role == 'admin' ? 'Super User System' : ($role == 'pengurus' ? 'Petugas Pengurus' : 'Akun Santri') ?></div>
        </div>
        <span class="sidebar-status-dot" title="Sistem Aktif"></span>
    </div>
    
    <div class="sidebar-menu-wrapper flex-grow-1 overflow-y-auto">
        <ul class="nav flex-column py-2">
            <li class="sidebar-heading">MENU UTAMA</li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/dashboard" class="nav-link <?= $current_page == 'dashboard' ? 'active' : '' ?>">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php if ($role == 'pengurus' || $role == 'santri'): ?>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/notifikasi" class="nav-link <?= $current_page == 'notifikasi' ? 'active' : '' ?>">
                    <i class="bi bi-bell-fill"></i>
                    <span>Notifikasi</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role == 'admin' || $role == 'pengurus' || $role == 'petugas_kesehatan'): ?>
            <li class="sidebar-heading mt-2">LAYANAN KESEHATAN</li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/keluhan" class="nav-link <?= $current_page == 'keluhan' ? 'active' : '' ?>">
                    <i class="bi bi-inbox-fill"></i>
                    <span>Antrean Keluhan</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/pemeriksaan" class="nav-link <?= $current_page == 'pemeriksaan' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                    <span>Pemeriksaan</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/santri" class="nav-link <?= $current_page == 'santri' ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Data Santri</span>
                </a>
            </li>
            
            <li class="sidebar-heading mt-2">REKAPITULASI</li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/laporan" class="nav-link <?= $current_page == 'laporan' ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-bar-graph-fill"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role == 'admin'): ?>
            <li class="sidebar-heading mt-2">PENGATURAN SISTEM</li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/users" class="nav-link <?= $current_page == 'users' ? 'active' : '' ?>">
                    <i class="bi bi-people-gear"></i>
                    <span>Kelola Pengguna & Role</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/settings" class="nav-link <?= $current_page == 'settings' ? 'active' : '' ?>">
                    <i class="bi bi-cpu-fill"></i>
                    <span>Konfigurasi AI & Chatbot</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/monitoring" class="nav-link <?= $current_page == 'monitoring' ? 'active' : '' ?>">
                    <i class="bi bi-whatsapp"></i>
                    <span>Monitoring WhatsApp</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if ($role == 'santri'): ?>
            <li class="sidebar-heading mt-2">LAYANAN SAYA</li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/keluhan" class="nav-link <?= $current_page == 'keluhan' ? 'active' : '' ?>">
                    <i class="bi bi-chat-heart-fill"></i>
                    <span>Keluhan Saya</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?= BASEURL; ?>/pemeriksaan" class="nav-link <?= $current_page == 'pemeriksaan' ? 'active' : '' ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Riwayat Kesehatan</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>