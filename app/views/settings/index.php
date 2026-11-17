<?php
$config = $data['config'] ?? [];
$catalog = $data['catalog'] ?? [];
$csrfToken = $data['csrf_token'] ?? '';

$chatbotModel = $config['chatbot_model'] ?? 'nvidia/nemotron-3.5-bolt-e4b';
$clinicalModel = $config['clinical_model'] ?? 'nvidia/nemotron-3.5-bolt-e4b';

$hasNvidiaKey = !empty($config['nvidia_api_key']);
$hasGeminiKey = !empty($config['gemini_api_key']);

$nvidiaMasked = $config['nvidia_masked'] ?? '';
$geminiMasked = $config['gemini_masked'] ?? '';
?>

<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center text-white flex-shrink-0" style="width: 46px; height: 46px; border-radius: 14px; background: linear-gradient(135deg, #059669 0%, #047857 100%); box-shadow: 0 4px 14px rgba(5, 150, 105, 0.35);">
                <i class="bi bi-cpu-fill fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1 text-dark" style="font-family: 'Outfit', sans-serif;">Pilihan Model AI (Chatbot & Analisis Klinis)</h5>
                <p class="text-secondary small mb-0">
                    Kelola model kecerdasan buatan dan kunci API provider (NVIDIA NIM & Google Gemini) dengan mudah.
                </p>
            </div>
        </div>
    </div>
</div>

<?php SessionHelper::flash(); ?>

<!-- Status Ringkas Kunci API yang Tersimpan -->
<div class="row g-3 mb-4">
    <!-- NVIDIA NIM Provider Card -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border: 1px solid #e2e8f0 !important; border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border: 1px solid #a7f3d0; color: #059669; font-size: 1.25rem; box-shadow: 0 2px 8px rgba(5, 150, 105, 0.12);">
                        <i class="bi bi-gpu-card"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-0.5">
                            <span class="fw-bold text-dark" style="font-size: 13.5px;">API Key NVIDIA NIM</span>
                            <?php if ($hasNvidiaKey): ?>
                            <span class="badge text-uppercase fw-bold" style="font-size: 9px; background: #d1fae5; color: #065f46; border-radius: 4px; padding: 2px 6px;">Aktif</span>
                            <?php else: ?>
                            <span class="badge text-uppercase fw-bold bg-secondary bg-opacity-25 text-secondary" style="font-size: 9px; border-radius: 4px; padding: 2px 6px;">Belum Terpasang</span>
                            <?php endif; ?>
                        </div>
                        <div class="font-monospace small <?= $hasNvidiaKey ? 'text-muted' : 'text-danger fst-italic' ?>" style="font-size: 11.5px;">
                            <?= $hasNvidiaKey ? htmlspecialchars($nvidiaMasked) : 'Belum ada API Key' ?>
                        </div>
                    </div>
                </div>
                <?php if ($hasNvidiaKey): ?>
                <span class="badge rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11.5px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                    <span>Tersedia</span>
                </span>
                <?php else: ?>
                <span class="badge rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-secondary border bg-light" style="font-size: 11.5px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                    <span>Kosong</span>
                </span>
                <?php endif; ?>
            </div>
            
            <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top mt-2">
                <button type="button" class="btn btn-sm btn-outline-success py-1 px-3 rounded-pill d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalKeyNvidia" style="font-size: 12px;">
                    <i class="bi <?= $hasNvidiaKey ? 'bi-pencil-square' : 'bi-plus-circle' ?>"></i>
                    <span><?= $hasNvidiaKey ? 'Ganti API Key' : 'Pasang API Key' ?></span>
                </button>
                <?php if ($hasNvidiaKey): ?>
                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalDeleteNvidia" style="font-size: 12px;">
                    <i class="bi bi-trash3"></i>
                    <span>Hapus Key</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Google Gemini Provider Card -->
    <div class="col-12 col-md-6">
        <div class="card border-0 shadow-sm p-3 bg-white h-100" style="border: 1px solid #e2e8f0 !important; border-radius: 16px; transition: transform 0.2s ease, box-shadow 0.2s ease;">
            <div class="d-flex align-items-start justify-content-between mb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe; color: #2563eb; font-size: 1.25rem; box-shadow: 0 2px 8px rgba(37, 99, 235, 0.12);">
                        <i class="bi bi-stars"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-0.5">
                            <span class="fw-bold text-dark" style="font-size: 13.5px;">API Key Google Gemini</span>
                            <?php if ($hasGeminiKey): ?>
                            <span class="badge text-uppercase fw-bold" style="font-size: 9px; background: #dbeafe; color: #1e40af; border-radius: 4px; padding: 2px 6px;">Aktif</span>
                            <?php else: ?>
                            <span class="badge text-uppercase fw-bold bg-secondary bg-opacity-25 text-secondary" style="font-size: 9px; border-radius: 4px; padding: 2px 6px;">Belum Terpasang</span>
                            <?php endif; ?>
                        </div>
                        <div class="font-monospace small <?= $hasGeminiKey ? 'text-muted' : 'text-danger fst-italic' ?>" style="font-size: 11.5px;">
                            <?= $hasGeminiKey ? htmlspecialchars($geminiMasked) : 'Belum ada API Key' ?>
                        </div>
                    </div>
                </div>
                <?php if ($hasGeminiKey): ?>
                <span class="badge rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 11.5px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #3b82f6; display: inline-block;"></span>
                    <span>Tersedia</span>
                </span>
                <?php else: ?>
                <span class="badge rounded-pill px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5 text-secondary border bg-light" style="font-size: 11.5px;">
                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; display: inline-block;"></span>
                    <span>Kosong</span>
                </span>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center justify-content-end gap-2 pt-2 border-top mt-2">
                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 rounded-pill d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalKeyGemini" style="font-size: 12px;">
                    <i class="bi <?= $hasGeminiKey ? 'bi-pencil-square' : 'bi-plus-circle' ?>"></i>
                    <span><?= $hasGeminiKey ? 'Ganti API Key' : 'Pasang API Key' ?></span>
                </button>
                <?php if ($hasGeminiKey): ?>
                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-3 rounded-pill d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalDeleteGemini" style="font-size: 12px;">
                    <i class="bi bi-trash3"></i>
                    <span>Hapus Key</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Form Pemilihan Model Sederhana -->
<div class="card border-0 shadow-sm mb-4" style="border: 1px solid #e2e8f0 !important; border-radius: 16px; overflow: hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9;">
        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center" style="font-family: 'Outfit', sans-serif;">
            <div class="d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; border-radius: 8px; background: #ecfdf5; color: #059669;">
                <i class="bi bi-toggles2 fs-6"></i>
            </div>
            <span>Pilih Model yang Digunakan</span>
        </h6>
        <span class="badge border px-2.5 py-1 rounded-pill" style="background: #fffbeb; color: #b45309; border-color: #fde68a !important; font-size: 11.5px;">
            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>Otomatis Terhubung
        </span>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="<?= BASEURL ?>/settings/save" id="simpleModelForm">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <!-- 1. Model untuk Chatbot -->
            <div class="card border p-3 rounded-3 mb-4 bg-light bg-opacity-50">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-2">
                    <label for="chatbotModelSelect" class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-chat-dots-fill text-success fs-5"></i>
                        <span>1. Model untuk Chatbot Asisten Santri</span>
                    </label>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnTestChatbotModel">
                        <i class="bi bi-broadcast"></i> Uji Model Ini
                    </button>
                </div>
                <p class="text-secondary small mb-2">
                    Digunakan saat santri atau pengurus bertanya kepada YukSehat AI di widget konsultasi.
                </p>
                <select name="chatbot_model" id="chatbotModelSelect" class="form-select form-select-lg fs-6 fw-semibold">
                    <?php 
                    $isCustomChatbot = true;
                    foreach ($catalog as $id => $item): 
                        $selected = ($chatbotModel === $id);
                        if ($selected) $isCustomChatbot = false;
                    ?>
                        <option value="<?= htmlspecialchars($id) ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['name']) ?> &mdash; [<?= $item['provider_label'] ?>: <?= $item['badge'] ?>]
                        </option>
                    <?php endforeach; ?>
                    <option value="custom" <?= $isCustomChatbot ? 'selected' : '' ?>>✏️ Kustom / Ketik Model Lain...</option>
                </select>

                <!-- Input Kustom Chatbot (hanya muncul jika memilih custom) -->
                <div id="customChatbotWrap" class="mt-2 <?= $isCustomChatbot ? '' : 'd-none' ?>">
                    <input type="text" name="custom_chatbot_model" id="customChatbotInput" class="form-control form-control-sm font-monospace" 
                           placeholder="Contoh: nvidia/nemotron-3.5-bolt-e4b atau gemini-3.8-flash" 
                           value="<?= $isCustomChatbot ? htmlspecialchars($chatbotModel) : '' ?>">
                </div>
            </div>

            <!-- 2. Model untuk Analisis Klinis -->
            <div class="card border p-3 rounded-3 mb-4 bg-light bg-opacity-50">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mb-2">
                    <label for="clinicalModelSelect" class="form-label fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                        <i class="bi bi-clipboard2-pulse-fill text-primary fs-5"></i>
                        <span>2. Model untuk Analisis Klinis Poskestren</span>
                    </label>
                    <button type="button" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" id="btnTestClinicalModel">
                        <i class="bi bi-broadcast"></i> Uji Model Ini
                    </button>
                </div>
                <p class="text-secondary small mb-2">
                    Digunakan pada menu Pemeriksaan saat petugas menekan tombol <em>"Analisis Keluhan dengan AI"</em> untuk membuat draf tinjauan medis awal.
                </p>
                <select name="clinical_model" id="clinicalModelSelect" class="form-select form-select-lg fs-6 fw-semibold">
                    <?php 
                    $isCustomClinical = true;
                    foreach ($catalog as $id => $item): 
                        $selected = ($clinicalModel === $id);
                        if ($selected) $isCustomClinical = false;
                    ?>
                        <option value="<?= htmlspecialchars($id) ?>" <?= $selected ? 'selected' : '' ?>>
                            <?= htmlspecialchars($item['name']) ?> &mdash; [<?= $item['provider_label'] ?>: <?= $item['badge'] ?>]
                        </option>
                    <?php endforeach; ?>
                    <option value="custom" <?= $isCustomClinical ? 'selected' : '' ?>>✏️ Kustom / Ketik Model Lain...</option>
                </select>

                <!-- Input Kustom Klinis -->
                <div id="customClinicalWrap" class="mt-2 <?= $isCustomClinical ? '' : 'd-none' ?>">
                    <input type="text" name="custom_clinical_model" id="customClinicalInput" class="form-control form-control-sm font-monospace" 
                           placeholder="Contoh: nvidia/nemotron-3.5-bolt-e4b atau gemini-3.8-flash" 
                           value="<?= $isCustomClinical ? htmlspecialchars($clinicalModel) : '' ?>">
                </div>
            </div>

            <!-- Tombol Samakan Model -->
            <div class="d-flex justify-content-end mb-4">
                <button type="button" class="btn btn-sm btn-link text-decoration-none text-muted" id="btnSyncModels">
                    <i class="bi bi-arrow-left-right me-1"></i>Samakan Model Chatbot & Analisis Klinis
                </button>
            </div>

            <!-- Live Test Alert Box (Hidden by default) -->
            <div id="testResultBox" class="alert d-none mb-4 fade show" role="alert">
                <div class="d-flex align-items-center gap-2">
                    <span id="testResultIcon"></span>
                    <strong id="testResultTitle">Hasil Uji Model</strong>
                </div>
                <div id="testResultMessage" class="small mt-1"></div>
            </div>

            <!-- Tombol Simpan -->
            <div class="pt-3 border-top d-flex align-items-center justify-content-between">
                <button type="submit" class="btn btn-success px-4 py-2 fw-semibold shadow-sm d-inline-flex align-items-center gap-2">
                    <i class="bi bi-check2-circle fs-5"></i>
                    <span>Simpan Perubahan Model</span>
                </button>
                <div class="text-muted small d-none d-sm-block">
                    <i class="bi bi-magic me-1"></i>Perubahan langsung aktif seketika
                </div>
            </div>

        </form>
    </div>
</div>

<!-- Modal Ganti / Pasang API Key NVIDIA NIM -->
<div class="modal fade" id="modalKeyNvidia" tabindex="-1" aria-labelledby="modalKeyNvidiaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-success" style="width: 36px; height: 36px; background: #ecfdf5;">
                        <i class="bi bi-gpu-card fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="modalKeyNvidiaLabel">
                            <?= $hasNvidiaKey ? 'Ganti API Key NVIDIA NIM' : 'Pasang API Key NVIDIA NIM' ?>
                        </h6>
                        <small class="text-muted" style="font-size: 11px;">build.nvidia.com</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASEURL ?>/settings/update_key" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="provider" value="nvidia">
                    
                    <label class="form-label fw-bold text-dark small mb-1">Kunci API Baru (nvapi-...)</label>
                    <div class="input-group mb-2">
                        <input type="password" name="api_key" id="inputNvidiaKey" class="form-control font-monospace" placeholder="Tempel API Key NVIDIA baru di sini..." required autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleKeyVisibility('inputNvidiaKey', this)" title="Lihat/Sembunyikan">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="small text-muted" style="font-size: 11.5px; line-height: 1.4;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Kunci API baru akan disimpan aman ke sistem dan langsung aktif menggantikan kunci lama.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-check2 me-1"></i>Simpan API Key Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus API Key NVIDIA NIM -->
<div class="modal fade" id="modalDeleteNvidia" tabindex="-1" aria-labelledby="modalDeleteNvidiaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger bg-opacity-10 py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-danger" style="width: 36px; height: 36px; background: #fee2e2;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-danger mb-0" id="modalDeleteNvidiaLabel">Konfirmasi Hapus API Key</h6>
                        <small class="text-muted" style="font-size: 11px;">Provider: NVIDIA NIM</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASEURL ?>/settings/delete_key" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="provider" value="nvidia">
                    
                    <p class="text-dark small mb-2">
                        Apakah Anda yakin ingin menghapus <strong>API Key NVIDIA NIM</strong> dari sistem?
                    </p>
                    <div class="p-2.5 rounded-3 bg-light border font-monospace small text-muted mb-3" style="font-size: 12px;">
                        Kunci saat ini: <?= htmlspecialchars($nvidiaMasked) ?>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mb-0" style="font-size: 11.5px;">
                        <i class="bi bi-exclamation-circle me-1"></i>Model AI yang menggunakan provider NVIDIA tidak akan dapat beroperasi sampai Anda memasang API Key baru.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-trash3 me-1"></i>Ya, Hapus API Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ganti / Pasang API Key Google Gemini -->
<div class="modal fade" id="modalKeyGemini" tabindex="-1" aria-labelledby="modalKeyGeminiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-primary" style="width: 36px; height: 36px; background: #eff6ff;">
                        <i class="bi bi-stars fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-dark mb-0" id="modalKeyGeminiLabel">
                            <?= $hasGeminiKey ? 'Ganti API Key Google Gemini' : 'Pasang API Key Google Gemini' ?>
                        </h6>
                        <small class="text-muted" style="font-size: 11px;">aistudio.google.com</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASEURL ?>/settings/update_key" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="provider" value="gemini">
                    
                    <label class="form-label fw-bold text-dark small mb-1">Kunci API Baru (AIza...)</label>
                    <div class="input-group mb-2">
                        <input type="password" name="api_key" id="inputGeminiKey" class="form-control font-monospace" placeholder="Tempel API Key Google Gemini baru di sini..." required autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleKeyVisibility('inputGeminiKey', this)" title="Lihat/Sembunyikan">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="small text-muted" style="font-size: 11.5px; line-height: 1.4;">
                        <i class="bi bi-info-circle me-1 text-primary"></i>Kunci API baru akan disimpan aman ke sistem dan langsung aktif menggantikan kunci lama.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-check2 me-1"></i>Simpan API Key Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Konfirmasi Hapus API Key Google Gemini -->
<div class="modal fade" id="modalDeleteGemini" tabindex="-1" aria-labelledby="modalDeleteGeminiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger bg-opacity-10 py-3 px-4 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-danger" style="width: 36px; height: 36px; background: #fee2e2;">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold text-danger mb-0" id="modalDeleteGeminiLabel">Konfirmasi Hapus API Key</h6>
                        <small class="text-muted" style="font-size: 11px;">Provider: Google Gemini</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASEURL ?>/settings/delete_key" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="provider" value="gemini">
                    
                    <p class="text-dark small mb-2">
                        Apakah Anda yakin ingin menghapus <strong>API Key Google Gemini</strong> dari sistem?
                    </p>
                    <div class="p-2.5 rounded-3 bg-light border font-monospace small text-muted mb-3" style="font-size: 12px;">
                        Kunci saat ini: <?= htmlspecialchars($geminiMasked) ?>
                    </div>
                    <div class="alert alert-warning py-2 px-3 small mb-0" style="font-size: 11.5px;">
                        <i class="bi bi-exclamation-circle me-1"></i>Model AI yang menggunakan provider Gemini tidak akan dapat beroperasi sampai Anda memasang API Key baru.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2.5 px-4 border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 rounded-pill" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3 rounded-pill fw-semibold">
                        <i class="bi bi-trash3 me-1"></i>Ya, Hapus API Key
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Deskripsi Model Siap Pakai -->
<div class="card border-0 shadow-sm rounded-3 bg-white">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle-fill text-info me-2"></i>Tentang Pilihan Model</h6>
    </div>
    <div class="card-body p-4">
        <div class="row g-3">
            <?php foreach ($catalog as $id => $item): ?>
            <div class="col-12 col-md-4">
                <div class="border rounded-3 p-3 h-100">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge <?= $item['badge_class'] ?? 'bg-secondary' ?> rounded-pill px-2 py-1" style="font-size: 10.5px;">
                            <?= $item['badge'] ?? 'AI Model' ?>
                        </span>
                        <span class="text-muted small" style="font-size: 11px;"><?= $item['provider_label'] ?? '' ?></span>
                    </div>
                    <div class="fw-bold text-dark small mb-1"><?= htmlspecialchars($item['name'] ?? $id) ?></div>
                    <div class="text-muted small lh-sm" style="font-size: 12px;"><?= htmlspecialchars($item['description'] ?? '') ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function toggleKeyVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const chatbotSelect = document.getElementById('chatbotModelSelect');
    const clinicalSelect = document.getElementById('clinicalModelSelect');
    const customChatbotWrap = document.getElementById('customChatbotWrap');
    const customClinicalWrap = document.getElementById('customClinicalWrap');
    const customChatbotInput = document.getElementById('customChatbotInput');
    const customClinicalInput = document.getElementById('customClinicalInput');
    const btnSync = document.getElementById('btnSyncModels');

    const btnTestChatbot = document.getElementById('btnTestChatbotModel');
    const btnTestClinical = document.getElementById('btnTestClinicalModel');
    const testResultBox = document.getElementById('testResultBox');
    const testResultIcon = document.getElementById('testResultIcon');
    const testResultTitle = document.getElementById('testResultTitle');
    const testResultMessage = document.getElementById('testResultMessage');

    chatbotSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customChatbotWrap.classList.remove('d-none');
            customChatbotInput.focus();
        } else {
            customChatbotWrap.classList.add('d-none');
        }
    });

    clinicalSelect.addEventListener('change', function() {
        if (this.value === 'custom') {
            customClinicalWrap.classList.remove('d-none');
            customClinicalInput.focus();
        } else {
            customClinicalWrap.classList.add('d-none');
        }
    });

    // Tombol Samakan Model
    btnSync.addEventListener('click', function() {
        clinicalSelect.value = chatbotSelect.value;
        clinicalSelect.dispatchEvent(new Event('change'));
        if (chatbotSelect.value === 'custom') {
            customClinicalInput.value = customChatbotInput.value;
        }
    });

    // Helper Fungsi Uji Model via AJAX
    async function runTest(modelVal, btnElement, targetLabel) {
        if (modelVal === 'custom') {
            modelVal = (btnElement === btnTestChatbot ? customChatbotInput.value : customClinicalInput.value).trim();
        }
        if (!modelVal) {
            showYkToast('warning', 'Model Belum Dipilih', 'Silakan pilih atau ketik nama model terlebih dahulu.');
            return;
        }

        const origHtml = btnElement.innerHTML;
        btnElement.disabled = true;
        btnElement.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menguji...';
        testResultBox.className = 'alert d-none';

        try {
            const res = await fetch('<?= BASEURL ?>/settings/test_model', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ model: modelVal })
            });
            const data = await res.json();
            testResultBox.classList.remove('d-none', 'alert-success', 'alert-danger');

            if (data.success) {
                testResultBox.classList.add('alert-success');
                testResultIcon.className = 'bi bi-check-circle-fill text-success fs-5';
                testResultTitle.textContent = `Uji Model untuk ${targetLabel} Berhasil!`;
                testResultMessage.innerHTML = `
                    <div>${data.message}</div>
                    <div class="mt-1"><strong>Model:</strong> <code>${data.model}</code> | <strong>Penyedia:</strong> ${data.provider} | <strong>Respon:</strong> ${data.latency_ms} ms</div>
                `;
            } else {
                testResultBox.classList.add('alert-danger');
                testResultIcon.className = 'bi bi-exclamation-triangle-fill text-danger fs-5';
                testResultTitle.textContent = `Uji Model Gagal`;
                testResultMessage.textContent = data.message || 'Tidak dapat terhubung ke server AI.';
            }
        } catch (e) {
            testResultBox.classList.remove('d-none', 'alert-success');
            testResultBox.classList.add('alert-danger');
            testResultIcon.className = 'bi bi-x-circle-fill text-danger fs-5';
            testResultTitle.textContent = `Kesalahan Jaringan`;
            testResultMessage.textContent = e.message;
        } finally {
            btnElement.disabled = false;
            btnElement.innerHTML = origHtml;
        }
    }

    btnTestChatbot.addEventListener('click', function() {
        runTest(chatbotSelect.value, btnTestChatbot, 'Chatbot');
    });

    btnTestClinical.addEventListener('click', function() {
        runTest(clinicalSelect.value, btnTestClinical, 'Analisis Klinis');
    });
});
</script>
