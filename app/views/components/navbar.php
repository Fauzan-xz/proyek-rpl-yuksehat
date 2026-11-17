<?php
$role = $data['role'] ?? 'admin';
$username = $data['username'] ?? 'Administrator';

// Generate initials for avatar
$nameParts = preg_split('/\s+/', trim($username));
$initials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1 && !empty($nameParts[1])) {
    $initials .= strtoupper(substr($nameParts[1], 0, 1));
}

// Muat notifikasi in-app untuk Pengurus dan Santri
$unreadNotifCount = 0;
$recentNotifs = [];
if (in_array($role, ['pengurus', 'santri']) && AuthHelper::isLoggedIn()) {
    require_once __DIR__ . '/../../models/Notifikasi_model.php';
    $notifModel = new Notifikasi_model();
    $currUser = AuthHelper::user();
    if ($currUser && isset($currUser['id'])) {
        $unreadNotifCount = $notifModel->getUnreadCount($currUser['id']);
        $recentNotifs = $notifModel->getRecentNotifications($currUser['id'], 5);
    }
}
?>
<nav class="topbar d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
        <button type="button" id="sidebarCollapse" class="btn btn-sm btn-light border d-lg-none me-2" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-none d-md-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center gap-1 px-2.5 py-1 rounded-pill" style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; font-size: 11.5px; font-weight: 600;">
                <span style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; box-shadow: 0 0 8px #10b981; display: inline-block;"></span>
                <span>YukSehat Sistem</span>
            </span>
            <span class="text-secondary small d-flex align-items-center gap-1 text-muted ms-1" style="font-size: 12px;">
                <i class="bi bi-calendar3 text-muted"></i>
                <span><?= date('l, d M Y') ?></span>
            </span>
        </div>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <?php if (in_array($role, ['pengurus', 'santri'])): ?>
        <!-- Ikon Lonceng Notifikasi Pengurus & Santri -->
        <div class="dropdown">
            <button class="btn btn-light rounded-circle position-relative p-2 border-0 shadow-none d-flex align-items-center justify-content-center" 
                    type="button" 
                    id="dropdownNotif" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false" 
                    title="<?= $role === 'santri' ? 'Notifikasi Keluhan' : 'Notifikasi Penugasan' ?>"
                    style="width: 38px; height: 38px; background: #f1f5f9;">
                <i class="bi bi-bell-fill fs-6 <?= $unreadNotifCount > 0 ? 'text-warning' : 'text-secondary' ?>"></i>
                <?php if ($unreadNotifCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white" style="font-size: 9px; padding: 3px 5px;">
                    <?= $unreadNotifCount > 9 ? '9+' : $unreadNotifCount ?>
                    <span class="visually-hidden">notifikasi baru</span>
                </span>
                <?php endif; ?>
            </button>
            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2 p-0 overflow-hidden" 
                 aria-labelledby="dropdownNotif" 
                 style="width: 320px; font-size: 13px;">
                <div class="px-3 py-2.5 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <div class="fw-bold text-dark d-flex align-items-center gap-1.5">
                        <i class="bi bi-bell text-success"></i>
                        <span><?= $role === 'santri' ? 'Pemberitahuan Keluhan' : 'Notifikasi Penugasan' ?></span>
                    </div>
                    <?php if ($unreadNotifCount > 0): ?>
                    <span class="badge bg-danger rounded-pill" style="font-size: 10px;"><?= $unreadNotifCount ?> baru</span>
                    <?php endif; ?>
                </div>

                <div class="list-group list-group-flush" style="max-height: 280px; overflow-y: auto;">
                    <?php if (empty($recentNotifs)): ?>
                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-bell-slash fs-3 d-block text-secondary opacity-50 mb-1"></i>
                        <span class="small">Belum ada notifikasi baru</span>
                    </div>
                    <?php else: ?>
                    <?php foreach ($recentNotifs as $notif): ?>
                        <?php $isUnread = empty($notif['dibaca_pada']); ?>
                        <a href="<?= BASEURL ?>/notifikasi/read/<?= (int)$notif['id'] ?>" 
                           class="list-group-item list-group-item-action p-2.5 border-bottom text-dark <?= $isUnread ? 'bg-light-subtle' : '' ?>"
                           style="<?= $isUnread ? 'border-left: 3px solid #10b981 !important;' : '' ?>">
                            <div class="d-flex align-items-start gap-2">
                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 mt-0.5" 
                                      style="width: 24px; height: 24px; background: <?= $isUnread ? '#dcfce7' : '#f1f5f9' ?>; color: <?= $isUnread ? '#059669' : '#64748b' ?>;">
                                    <i class="bi bi-envelope-fill" style="font-size: 11px;"></i>
                                </span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center justify-content-between gap-1 mb-0.5">
                                        <div class="fw-bold text-truncate small <?= $isUnread ? 'text-dark' : 'text-secondary' ?>" style="font-size: 12px;">
                                            <?= htmlspecialchars($notif['judul']) ?>
                                        </div>
                                        <?php if ($isUnread): ?>
                                        <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-secondary small text-truncate" style="font-size: 11.5px;">
                                        <?= htmlspecialchars($notif['pesan']) ?>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size: 10px;">
                                        <?= date('d M, H:i', strtotime($notif['created_at'])) ?> WIB
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="p-2 bg-light border-top text-center">
                    <a href="<?= BASEURL ?>/notifikasi" class="text-decoration-none small text-success fw-semibold" style="font-size: 12px;">
                        <i class="bi bi-card-list me-1"></i>Buka Semua Notifikasi
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Profil Pengguna -->
        <div class="dropdown">
            <a class="nav-link dropdown-toggle text-dark d-flex align-items-center gap-2 py-1 ps-1 pe-3 rounded-pill topbar-user-chip" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="navbar-avatar" title="<?= htmlspecialchars($username) ?>">
                    <?= htmlspecialchars($initials) ?>
                </div>
                <div class="d-none d-sm-block text-start lh-1 me-1">
                    <div class="fw-bold text-dark small mb-1" style="font-size: 13px;"><?= htmlspecialchars($username) ?></div>
                    <div class="text-muted" style="font-size: 11px;"><?= $role == 'admin' ? 'Administrator' : ucfirst($role) ?></div>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2 py-2" style="font-size: 13px; min-width: 190px;">
                <li class="px-3 py-2 border-bottom mb-1">
                    <div class="fw-bold text-dark text-truncate"><?= htmlspecialchars($username) ?></div>
                    <div class="text-muted small"><?= $role == 'admin' ? 'Super User' : ucfirst($role) ?></div>
                </li>
                <?php if ($role === 'santri'): ?>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/dashboard"><i class="bi bi-speedometer2 me-2 text-secondary"></i>Dashboard</a></li>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/notifikasi"><i class="bi bi-bell me-2 text-secondary"></i>Pemberitahuan</a></li>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/keluhan"><i class="bi bi-chat-heart me-2 text-secondary"></i>Keluhan Saya</a></li>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/pemeriksaan"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Pemeriksaan</a></li>
                <?php elseif ($role === 'pengurus'): ?>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/dashboard"><i class="bi bi-speedometer2 me-2 text-secondary"></i>Dashboard</a></li>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/notifikasi"><i class="bi bi-bell me-2 text-secondary"></i>Pemberitahuan</a></li>
                <?php else: ?>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1" href="<?= BASEURL; ?>/dashboard"><i class="bi bi-speedometer2 me-2 text-secondary"></i>Dashboard</a></li>
                <?php endif; ?>
                <li><hr class="dropdown-divider my-1"></li>
                <li><a class="dropdown-item py-2 px-3 rounded-2 mx-1 text-danger" href="<?= BASEURL; ?>/login/logout"><i class="bi bi-box-arrow-right me-2"></i>Keluar</a></li>
            </ul>
        </div>
    </div>
</nav>
