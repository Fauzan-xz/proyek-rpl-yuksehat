<!-- Landing Page Hero -->
<div class="bg-success text-white py-5 text-center position-relative overflow-hidden">
    <div class="container py-5 position-relative" style="z-index: 2;">
        <i class="bi bi-heart-pulse-fill display-1 mb-3 text-white"></i>
        <h1 class="display-4 fw-bold">Sistem Informasi Kesehatan Santri</h1>
        <p class="lead mb-4 opacity-90">Pantau dan kelola data kesehatan santri di pondok pesantren dengan mudah, modern, dan terpadu.</p>
        <div class="d-flex flex-wrap justify-content-center gap-3">
            <a href="<?= BASEURL; ?>/login" class="btn btn-light btn-lg px-4 rounded-pill fw-semibold shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Dashboard
            </a>
            <a href="<?= BASEURL; ?>/register" class="btn btn-outline-light btn-lg px-4 rounded-pill fw-semibold">
                <i class="bi bi-person-plus me-1"></i> Daftar Akun Santri
            </a>
            <button type="button" class="btn btn-outline-light btn-lg px-4 rounded-pill fw-semibold" onclick="document.getElementById('yuksehatChatTrigger')?.click()">
                <i class="bi bi-robot me-1"></i> Tanya Chatbot AI
            </button>
        </div>
    </div>
</div>

<div class="container my-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 py-4 rounded-4">
                <i class="bi bi-clipboard2-pulse fs-1 text-success mb-3"></i>
                <h4 class="fw-bold">Pemeriksaan Rutin</h4>
                <p class="text-muted px-3">Pencatatan riwayat pemeriksaan kesehatan santri terintegrasi.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 py-4 rounded-4">
                <i class="bi bi-hospital fs-1 text-success mb-3"></i>
                <h4 class="fw-bold">Poskestren Pesantren</h4>
                <p class="text-muted px-3">Pelayanan pos kesehatan pesantren secara terpadu dan tanggap.</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 shadow-sm border-0 py-4 rounded-4">
                <i class="bi bi-file-earmark-bar-graph fs-1 text-success mb-3"></i>
                <h4 class="fw-bold">Laporan Realtime</h4>
                <p class="text-muted px-3">Rekapitulasi data kesehatan untuk evaluasi pengurus pesantren.</p>
            </div>
        </div>
    </div>

    <!-- Banner Chatbot Interaktif -->
    <div class="card border-0 shadow-sm rounded-4 mt-5 overflow-hidden" style="background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-left: 6px solid #10b981 !important;">
        <div class="card-body p-4 p-md-5 d-flex flex-column flex-md-row align-items-center justify-content-between gap-4">
            <div class="d-flex align-items-start gap-4">
                <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 64px; height: 64px; font-size: 30px;">
                    <i class="bi bi-robot"></i>
                </div>
                <div>
                    <div class="badge bg-success bg-opacity-20 text-success fw-bold px-3 py-1 rounded-pill mb-2">Didukung Node.js & Google Gemini AI</div>
                    <h3 class="fw-bold mb-2 text-dark">Konsultasi Kesehatan Santri 24/7</h3>
                    <p class="text-secondary mb-0" style="max-width: 650px;">
                        Bingung cara penanganan scabies/gudikan, pertolongan pertama demam, atau menjaga pola hidup bersih di asrama? Klik ikon chat di pojok kanan bawah atau tombol di samping untuk mulai bertanya ke <strong>YukSehat AI</strong>.
                    </p>
                </div>
            </div>
            <div>
                <button type="button" class="btn btn-success btn-lg px-4 py-3 rounded-pill fw-bold shadow d-inline-flex align-items-center gap-2" onclick="document.getElementById('yuksehatChatTrigger')?.click();">
                    <i class="bi bi-chat-dots-fill"></i> Buka Chat Sekarang
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sertakan Komponen Chatbot -->
<?php require_once __DIR__ . '/../components/chatbot.php'; ?>

