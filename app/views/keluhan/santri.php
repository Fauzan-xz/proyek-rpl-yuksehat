<div class="row g-3 mb-4">
    <div class="col-12">
        <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-chat-heart text-danger me-2"></i>Keluhan Kesehatan Saya</h5>
        <p class="text-secondary small mb-0">Laporkan keluhan sakit atau gejala yang Anda alami ke petugas Poskestren agar dapat segera diperiksa.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Form Kirim Keluhan Baru -->
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-send-plus-fill text-success me-2"></i>Kirim Keluhan Baru</h6>
            </div>
            <div class="card-body p-4">
                <form action="<?= BASEURL ?>/keluhan/kirim" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Pilih Petugas / Pemeriksa <span class="text-danger">*</span></label>
                        <select name="pemeriksa_id" class="form-select form-select-sm" required>
                            <option value="">-- Pilih Petugas Poskestren --</option>
                            <?php if (!empty($data['pengurus_list'])): ?>
                                <?php foreach ($data['pengurus_list'] as $p): ?>
                                    <option value="<?= (int)$p['user_id'] ?>" <?= (strtolower($p['nama']) === 'ronal') ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nama']) ?> <?= !empty($p['jabatan']) ? '('.htmlspecialchars($p['jabatan']).')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <div class="form-text small" style="font-size: 11.5px;">Petugas yang Anda pilih akan langsung menerima notifikasi WhatsApp untuk memeriksa Anda.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Apa yang Anda rasakan? <span class="text-danger">*</span></label>
                        <textarea name="keluhan" class="form-control" rows="4" required placeholder="Contoh: Merasa demam sejak kemarin malam, kepala pusing dan tenggorokan sakit saat menelan..."></textarea>
                        <div class="form-text small" style="font-size: 11.5px;">Jelaskan gejala, sejak kapan terasa, dan apakah ada keluhan lain seperti gatal di kulit, mual, atau batuk.</div>
                    </div>

                    <div class="alert alert-info border-0 rounded-3 p-3 mb-3 d-flex align-items-start gap-2" style="font-size: 12px;">
                        <i class="bi bi-info-circle-fill text-info fs-6 mt-1 flex-shrink-0"></i>
                        <div>
                            <strong>Petunjuk:</strong> Setelah dikirim, keluhan Anda akan masuk antrean pemeriksaan Poskestren. Jika darurat (sesak napas / pingsan), segera hubungi pengurus asrama secara langsung.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-semibold shadow-sm d-flex align-items-center justify-content-center gap-2">
                        <i class="bi bi-send-fill"></i> Kirim ke Antrean Poskestren
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat Status Keluhan -->
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history text-primary me-2"></i>Status Keluhan Saya</h6>
                <span class="badge bg-light text-dark border"><?= count($data['keluhan_list'] ?? []) ?> Catatan</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;" class="text-center">#</th>
                            <th>Tanggal Lapor</th>
                            <th>Keluhan</th>
                            <th>Petugas Pemeriksa</th>
                            <th style="width: 120px;" class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data['keluhan_list'])): ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-circle fs-1 d-block mb-2 text-success opacity-50"></i>
                                Belum ada keluhan kesehatan yang dikirim. Alhamdulillah Anda sehat!
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($data['keluhan_list'] as $idx => $k): ?>
                        <tr>
                            <td class="text-center text-muted fw-bold"><?= $idx + 1 ?></td>
                            <td class="text-nowrap small text-muted">
                                <?= date('d M Y, H:i', strtotime($k['tanggal_keluhan'])) ?> WIB
                            </td>
                            <td>
                                <div class="text-dark fw-medium"><?= nl2br(htmlspecialchars($k['keluhan'])) ?></div>
                            </td>
                            <td>
                                <?php if (!empty($k['nama_pengurus'])): ?>
                                    <span class="badge bg-light text-primary border px-2 py-1 rounded-pill" style="font-size: 11.5px;">
                                        <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($k['nama_pengurus']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($k['status'] === 'menunggu'): ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 11.5px;"><i class="bi bi-hourglass-split me-1"></i>Menunggu</span>
                                <?php elseif ($k['status'] === 'diperiksa'): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 11.5px;"><i class="bi bi-clipboard-check me-1"></i>Diperiksa</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 rounded-pill fw-semibold" style="font-size: 11.5px;"><i class="bi bi-check-circle-fill me-1"></i>Selesai</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
