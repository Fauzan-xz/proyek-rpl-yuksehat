            </div> <!-- End Main Wrapper -->
        </div> <!-- End Content -->
    </div> <!-- End Wrapper -->
    
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarOverlay" class="sidebar-overlay"></div>
    
    <!-- YukSehat AI Chatbot Component -->
    <?php require_once __DIR__ . '/../components/chatbot.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        
        function toggleSidebar() {
            if (!sidebar) return;
            sidebar.classList.toggle('active');
            if (overlay) {
                overlay.classList.toggle('active');
            }
            if (sidebar.classList.contains('active')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        if (sidebarCollapse) {
            sidebarCollapse.addEventListener('click', toggleSidebar);
        }
        if (overlay) {
            overlay.addEventListener('click', toggleSidebar);
        }

        // Lightweight Click Ripple Micro-Interaction (Menghilangkan kesan kaku saat klik)
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn, .btn-detail-notif, .dropdown-item');
            if (!btn) return;
            
            const circle = document.createElement('span');
            const diameter = Math.max(btn.clientWidth, btn.clientHeight);
            const radius = diameter / 2;
            const rect = btn.getBoundingClientRect();
            
            circle.style.width = circle.style.height = `${diameter}px`;
            circle.style.left = `${e.clientX - rect.left - radius}px`;
            circle.style.top = `${e.clientY - rect.top - radius}px`;
            circle.classList.add('ui-click-ripple');
            
            const existingRipple = btn.querySelector('.ui-click-ripple');
            if (existingRipple) existingRipple.remove();
            
            btn.appendChild(circle);
            setTimeout(() => {
                if (circle.parentElement) circle.remove();
            }, 600);
        });
    </script>

    <!-- ===== GLOBAL UI UTILITIES: Toast & Confirm Modal ===== -->

    <!-- Toast Container -->
    <div class="position-fixed p-3" style="top:70px;right:0;z-index:19999;pointer-events:none;">
        <div id="ykToast" class="toast align-items-center border-0 shadow-lg" role="alert" aria-atomic="true" data-bs-delay="4500"
             style="min-width:300px;max-width:380px;border-radius:16px;overflow:hidden;pointer-events:auto;">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-start gap-3 p-3">
                    <div id="ykToastIcon" class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle" style="width:40px;height:40px;min-width:40px;">
                        <i id="ykToastIconInner" class="bi fs-5"></i>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div id="ykToastTitle" class="fw-bold small mb-1 lh-sm"></div>
                        <div id="ykToastMsg" class="small lh-sm" style="opacity:.85;word-break:break-word;"></div>
                    </div>
                </div>
                <button type="button" class="btn-close me-2 mt-2 flex-shrink-0" data-bs-dismiss="toast" aria-label="Tutup" style="filter:brightness(10);opacity:.7;"></button>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div class="modal fade" id="ykConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-body px-4 pt-4 pb-3 text-center">
                    <div id="ykConfirmIconWrap" class="d-flex align-items-center justify-content-center rounded-circle mx-auto mb-3"
                         style="width:60px;height:60px;">
                        <i id="ykConfirmIcon" class="bi fs-2"></i>
                    </div>
                    <h6 id="ykConfirmTitle" class="fw-bold text-dark mb-1"></h6>
                    <p id="ykConfirmMsg" class="text-muted small mb-0 lh-sm"></p>
                </div>
                <div class="modal-footer border-0 pt-2 px-4 pb-4 d-flex gap-2 justify-content-center">
                    <button type="button" id="ykConfirmCancel" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="min-width:110px;">Batal</button>
                    <button type="button" id="ykConfirmOk" class="btn rounded-pill px-4 fw-semibold" style="min-width:110px;"></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    /* =====================================================
       YukSehat Global UI Utilities
       showYkToast(type, title, msg)
       showYkConfirm(opts) → Promise<boolean>
       Tipe toast: 'success' | 'danger' | 'warning' | 'info'
    ===================================================== */
    function showYkToast(type, title, msg) {
        const cfg = {
            success : { bg:'linear-gradient(135deg,#059669,#10b981)', icon:'bi-check-circle-fill',  btnFilter:'brightness(10)' },
            danger  : { bg:'linear-gradient(135deg,#dc2626,#ef4444)', icon:'bi-x-circle-fill',      btnFilter:'brightness(10)' },
            warning : { bg:'linear-gradient(135deg,#d97706,#f59e0b)', icon:'bi-exclamation-triangle-fill', btnFilter:'none' },
            info    : { bg:'linear-gradient(135deg,#0284c7,#38bdf8)', icon:'bi-info-circle-fill',   btnFilter:'brightness(10)' },
        };
        const c = cfg[type] || cfg.info;
        const toast   = document.getElementById('ykToast');
        const iconW   = document.getElementById('ykToastIcon');
        const iconI   = document.getElementById('ykToastIconInner');
        const titleEl = document.getElementById('ykToastTitle');
        const msgEl   = document.getElementById('ykToastMsg');
        const closeBtn = toast.querySelector('.btn-close');
        if (!toast) return;

        toast.style.background = c.bg;
        toast.className = 'toast align-items-center border-0 shadow-lg';
        if (type === 'warning') toast.classList.add('text-dark'); else toast.classList.add('text-white');

        iconW.style.background = 'rgba(255,255,255,0.2)';
        iconI.className = 'bi fs-5 ' + c.icon;
        if (closeBtn) closeBtn.style.filter = c.btnFilter;
        titleEl.textContent = title;
        msgEl.textContent   = msg;

        bootstrap.Toast.getOrCreateInstance(toast).show();
    }

    function showYkConfirm({ title, msg, type = 'danger', confirmText = 'Ya, Lanjutkan', icon = null }) {
        return new Promise(resolve => {
            const cfg = {
                danger  : { bg:'rgba(220,38,38,.1)',  iconCls:'text-danger',  btnCls:'btn-danger',         defaultIcon:'bi-trash3-fill' },
                warning : { bg:'rgba(217,119,6,.1)',  iconCls:'text-warning', btnCls:'btn-warning',        defaultIcon:'bi-exclamation-triangle-fill' },
                info    : { bg:'rgba(2,132,199,.1)',  iconCls:'text-info',    btnCls:'btn-info text-white', defaultIcon:'bi-question-circle-fill' },
                success : { bg:'rgba(5,150,105,.1)',  iconCls:'text-success', btnCls:'btn-success',        defaultIcon:'bi-check-circle-fill' },
            };
            const c = cfg[type] || cfg.danger;

            const iconWrap = document.getElementById('ykConfirmIconWrap');
            const iconEl   = document.getElementById('ykConfirmIcon');
            const titleEl  = document.getElementById('ykConfirmTitle');
            const msgEl    = document.getElementById('ykConfirmMsg');
            const okBtn    = document.getElementById('ykConfirmOk');

            iconWrap.style.background = c.bg;
            iconEl.className = 'bi fs-2 ' + c.iconCls + ' ' + (icon || c.defaultIcon);
            titleEl.textContent = title;
            msgEl.textContent   = msg;
            okBtn.className     = 'btn rounded-pill px-4 fw-semibold ' + c.btnCls;
            okBtn.textContent   = confirmText;

            const modalEl = document.getElementById('ykConfirmModal');
            const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);

            // Clone ok button to clear old listeners
            const newOk = okBtn.cloneNode(true);
            okBtn.parentNode.replaceChild(newOk, okBtn);

            newOk.addEventListener('click', () => { bsModal.hide(); resolve(true); }, { once: true });
            modalEl.addEventListener('hidden.bs.modal', () => resolve(false), { once: true });

            bsModal.show();
        });
    }
    </script>
</body>
</html>