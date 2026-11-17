<?php
/**
 * YukSehat Chatbot Component
 * Modern Floating AI Chatbot powered by Node.js & Google Gemini AI
 */
$baseUrl = defined('BASEURL') ? BASEURL : 'http://localhost/YukSehat';
$currentUser = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$userData = null;
if ($currentUser) {
    $secret = defined('INTERNAL_API_SECRET') && !empty(INTERNAL_API_SECRET) ? INTERNAL_API_SECRET : null;
    $authTime = time();
    $userId = (int)($currentUser['id'] ?? 0);
    $userRole = (string)($currentUser['role'] ?? 'pengguna');
    $signaturePayload = $userId . ':' . $userRole . ':' . $authTime;
    $authToken = !empty($secret) ? hash_hmac('sha256', $signaturePayload, $secret) : '';
    $userData = [
        'id'         => $userId,
        'nama'       => $currentUser['nama'] ?? ($currentUser['username'] ?? 'Pengguna'),
        'username'   => $currentUser['username'] ?? '',
        'role'       => $userRole,
        'auth_token' => $authToken,
        'auth_time'  => $authTime
    ];
}
?>
<!-- ===== MODAL KONFIRMASI HAPUS CHAT ===== -->
<div class="modal fade" id="modalClearChat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-body px-4 pt-4 pb-3 text-center">
                <div class="d-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle mx-auto mb-3" style="width:60px;height:60px;">
                    <i class="bi bi-trash3-fill text-danger fs-3"></i>
                </div>
                <h6 class="fw-bold text-dark mb-1">Hapus Riwayat Percakapan?</h6>
                <p class="text-muted small mb-0">Seluruh riwayat obrolan dengan <strong>YukSehat AI</strong> akan dihapus dan tidak dapat dipulihkan.</p>
            </div>
            <div class="modal-footer border-0 pt-0 px-4 pb-4 d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-semibold" data-bs-dismiss="modal" style="min-width:110px;">Batal</button>
                <button type="button" class="btn btn-danger rounded-pill px-4 fw-semibold" id="btnConfirmClearChat" style="min-width:110px;">
                    <i class="bi bi-trash3 me-1"></i>Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===== YUKSEHAT AI CHATBOT WIDGET ===== -->
<div id="yuksehatChatWidget" class="yuksehat-chat-widget">
    <!-- Floating Trigger Button -->
    <div id="yuksehatChatTrigger" class="chat-trigger-btn shadow-lg" title="Konsultasi Kesehatan dengan YukSehat AI">
        <div class="chat-trigger-pulse"></div>
        <div class="chat-trigger-icon-wrap">
            <i class="bi bi-chat-dots-fill icon-open"></i>
            <i class="bi bi-x-lg icon-close"></i>
        </div>
        <div class="chat-trigger-badge">
            <span class="badge-dot"></span>
            <span class="badge-text">Tanya AI</span>
        </div>
    </div>

    <!-- Chat Dialog Window -->
    <div id="yuksehatChatBox" class="chat-box shadow-2xl">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-wrap">
                    <div class="avatar-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <span class="avatar-status-dot" id="yuksehatStatusDot" title="Node.js Server Online"></span>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold chat-title">YukSehat AI</h6>
                    <div class="chat-subtitle d-flex align-items-center gap-1">
                        <span class="server-badge" id="yuksehatStatusText">Gemini AI (Node)</span>
                        <span class="separator">•</span>
                        <span>Asisten Poskestren</span>
                    </div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn-chat-action" id="btnClearChat" title="Hapus Riwayat Chat">
                    <i class="bi bi-trash3"></i>
                </button>
                <button type="button" class="btn-chat-action" id="btnCloseChat" title="Tutup">
                    <i class="bi bi-dash-lg"></i>
                </button>
            </div>
        </div>

        <!-- Chat Body / Messages Area -->
        <div class="chat-body" id="chatMessages">
            <!-- Welcome Card -->
            <div class="chat-welcome-card" id="chatWelcomeCard">
                <div class="welcome-icon">
                    <i class="bi bi-heart-pulse-fill"></i>
                </div>
                <h6 class="fw-bold mb-1">Assalamu'alaikum! 👋</h6>
                <p class="welcome-desc mb-2">
                    Saya <strong>YukSehat AI</strong>, asisten cerdas kesehatan pesantren untuk konsultasi santri dan operasional Poskestren.
                </p>
                <div class="welcome-topics-title">Pilih topik cepat atau ketik pertanyaan:</div>
                <div class="quick-prompts-list">
                    <button type="button" class="quick-prompt-btn" data-prompt="Apa itu PHBS?">
                        <i class="bi bi-droplet-half text-info"></i> Apa itu PHBS?
                    </button>
                    <button type="button" class="quick-prompt-btn" data-prompt="Bagaimana cara melakukan pemeriksaan?">
                        <i class="bi bi-clipboard2-pulse text-primary"></i> Alur Pemeriksaan
                    </button>
                    <button type="button" class="quick-prompt-btn" data-prompt="Bagaimana cara mencegah scabies (gudikan) di asrama?">
                        <i class="bi bi-shield-check text-success"></i> Pencegahan Scabies
                    </button>
                    <button type="button" class="quick-prompt-btn" data-prompt="Apa pertolongan pertama demam tinggi pada santri?">
                        <i class="bi bi-bandaid text-danger"></i> Pertolongan Demam
                    </button>
                </div>
            </div>

            <!-- Typing Indicator (Hidden by default) -->
            <div class="typing-indicator-wrap d-none" id="typingIndicator">
                <div class="bot-avatar-mini">
                    <i class="bi bi-robot"></i>
                </div>
                <div class="typing-bubble">
                    <span class="typing-text">YukSehat AI sedang berpikir</span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
            </div>
        </div>

        <!-- Chat Footer / Input Area -->
        <div class="chat-footer">
            <form id="chatForm" class="chat-input-form" onsubmit="return false;">
                <div class="input-container">
                    <textarea 
                        id="chatInput" 
                        class="chat-textarea" 
                        rows="1" 
                        placeholder="Tanya seputar kesehatan santri..." 
                        aria-label="Pesan Anda"></textarea>
                    <button type="submit" id="btnSendChat" class="btn-send" title="Kirim Pesan">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </form>
            <div class="chat-disclaimer">
                <span>Didukung Node.js & Google Gemini AI • Bukan pengganti periksa dokter darurat</span>
            </div>
        </div>
    </div>
</div>

<!-- ===== CHATBOT STYLES ===== -->
<style>
/* CSS Variables */
:root {
    --ys-primary: #10b981;
    --ys-primary-dark: #059669;
    --ys-primary-light: #ecfdf5;
    --ys-primary-glow: rgba(16, 185, 129, 0.35);
    --ys-surface: #ffffff;
    --ys-bg-chat: #f8fafc;
    --ys-text-main: #1e293b;
    --ys-text-muted: #64748b;
    --ys-border: #e2e8f0;
    --ys-radius: 18px;
    --ys-shadow: 0 12px 36px -4px rgba(15, 23, 42, 0.18), 0 4px 12px -2px rgba(15, 23, 42, 0.08);
}

.yuksehat-chat-widget {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 1060;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

/* Floating Trigger Button */
.chat-trigger-btn {
    position: relative;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.25s ease;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.5), 0 6px 10px -4px rgba(16, 185, 129, 0.25);
}

.chat-trigger-btn:hover {
    transform: scale(1.08);
    box-shadow: 0 14px 30px -5px rgba(16, 185, 129, 0.6), 0 8px 14px -4px rgba(16, 185, 129, 0.3);
}

.chat-trigger-pulse {
    position: absolute;
    inset: -4px;
    border-radius: 50%;
    border: 2px solid #10b981;
    opacity: 0.6;
    animation: triggerPulse 2.4s infinite;
    pointer-events: none;
}

@keyframes triggerPulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.18); opacity: 0; }
    100% { transform: scale(1); opacity: 0; }
}

.chat-trigger-icon-wrap {
    font-size: 26px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.3s ease;
}

.chat-trigger-icon-wrap .icon-close {
    display: none;
    font-size: 22px;
}

.chat-trigger-btn.is-active .chat-trigger-pulse {
    display: none;
}

.chat-trigger-btn.is-active .icon-open {
    display: none;
}

.chat-trigger-btn.is-active .icon-close {
    display: block;
}

/* Badge Tag */
.chat-trigger-badge {
    position: absolute;
    right: 68px;
    background: #ffffff;
    color: #0f172a;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 20px;
    white-space: nowrap;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    display: flex;
    align-items: center;
    gap: 6px;
    pointer-events: none;
    animation: badgeFloat 3s ease-in-out infinite;
}

@keyframes badgeFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-3px); }
}

.chat-trigger-badge .badge-dot {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
}

.chat-trigger-btn.is-active .chat-trigger-badge {
    display: none;
}

/* Chat Box Container */
.chat-box {
    position: absolute;
    bottom: 74px;
    right: 0;
    width: 380px;
    height: 560px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: var(--ys-radius);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: var(--ys-shadow);
    border: 1px solid rgba(226, 232, 240, 0.9);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px) scale(0.95);
    transform-origin: bottom right;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.chat-box.is-open {
    opacity: 1;
    visibility: visible;
    transform: translateY(0) scale(1);
}

/* Chat Header */
.chat-header {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: #ffffff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.avatar-wrap {
    position: relative;
}

.avatar-icon {
    width: 38px;
    height: 38px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.18);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
}

.avatar-status-dot {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 10px;
    height: 10px;
    background: #22c55e;
    border: 2px solid #059669;
    border-radius: 50%;
}

.avatar-status-dot.offline {
    background: #eab308;
}

.chat-title {
    font-size: 14.5px;
    line-height: 1.2;
    color: #ffffff;
}

.chat-subtitle {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.85);
}

.server-badge {
    background: rgba(255, 255, 255, 0.2);
    padding: 1px 6px;
    border-radius: 4px;
    font-weight: 500;
}

.btn-chat-action {
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.85);
    width: 30px;
    height: 30px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.btn-chat-action:hover {
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
}

/* Chat Body */
.chat-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background-color: var(--ys-bg-chat);
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}

.chat-body::-webkit-scrollbar {
    width: 5px;
}
.chat-body::-webkit-scrollbar-track {
    background: transparent;
}
.chat-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

/* Welcome Card */
.chat-welcome-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 16px;
    border: 1px solid var(--ys-border);
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
    text-align: center;
}

.welcome-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: var(--ys-primary-light);
    color: var(--ys-primary);
    font-size: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px auto;
}

.welcome-desc {
    font-size: 12.5px;
    color: var(--ys-text-muted);
    line-height: 1.45;
}

.welcome-topics-title {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    text-align: left;
    margin-bottom: 8px;
}

.quick-prompts-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.quick-prompt-btn {
    text-align: left;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    padding: 7px 11px;
    border-radius: 8px;
    font-size: 12px;
    color: #334155;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.quick-prompt-btn:hover {
    background: var(--ys-primary-light);
    border-color: #a7f3d0;
    color: var(--ys-primary-dark);
    transform: translateX(2px);
}

/* Messages */
.message-row {
    display: flex;
    flex-direction: column;
    gap: 3px;
    max-width: 86%;
    animation: msgFadeIn 0.25s ease-out;
}

@keyframes msgFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.message-row.user {
    align-self: flex-end;
    align-items: flex-end;
}

.message-row.bot {
    align-self: flex-start;
    align-items: flex-start;
}

.msg-bubble {
    padding: 10px 14px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.5;
    word-break: break-word;
}

.message-row.user .msg-bubble {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    border-bottom-right-radius: 3px;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.2);
}

.message-row.bot .msg-bubble {
    background: #ffffff;
    color: var(--ys-text-main);
    border: 1px solid var(--ys-border);
    border-bottom-left-radius: 3px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.03);
}

.msg-meta {
    font-size: 10px;
    color: #94a3b8;
    padding: 0 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-copy-msg {
    background: transparent;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    padding: 0;
    font-size: 11px;
}
.btn-copy-msg:hover {
    color: var(--ys-primary);
}

/* Markdown formatting inside bot bubble */
.msg-bubble p { margin-bottom: 6px; }
.msg-bubble p:last-child { margin-bottom: 0; }
.msg-bubble ul, .msg-bubble ol { margin: 4px 0 6px 18px; padding: 0; }
.msg-bubble li { margin-bottom: 3px; }
.msg-bubble strong { font-weight: 600; color: #0f172a; }
.msg-bubble hr { margin: 8px 0; border: 0; border-top: 1px solid #e2e8f0; }

/* Typing Indicator */
.typing-indicator-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    align-self: flex-start;
}

.bot-avatar-mini {
    width: 26px;
    height: 26px;
    border-radius: 8px;
    background: #10b981;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
}

.typing-bubble {
    background: #ffffff;
    border: 1px solid var(--ys-border);
    padding: 7px 12px;
    border-radius: 12px;
    border-bottom-left-radius: 2px;
    display: flex;
    align-items: center;
    gap: 4px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}

.typing-text {
    font-size: 11px;
    color: var(--ys-text-muted);
    margin-right: 4px;
}

.typing-bubble .dot {
    width: 5px;
    height: 5px;
    background: #10b981;
    border-radius: 50%;
    animation: typingBlink 1.4s infinite ease-in-out;
}

.typing-bubble .dot:nth-child(2) { animation-delay: 0.2s; }
.typing-bubble .dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBlink {
    0%, 80%, 100% { opacity: 0.2; transform: scale(0.8); }
    40% { opacity: 1; transform: scale(1.15); }
}

/* Chat Footer */
.chat-footer {
    padding: 10px 14px 10px 14px;
    background: #ffffff;
    border-top: 1px solid var(--ys-border);
}

.input-container {
    position: relative;
    display: flex;
    align-items: center;
    background: #f1f5f9;
    border-radius: 24px;
    border: 1px solid #e2e8f0;
    padding: 4px 6px 4px 14px;
    transition: all 0.2s ease;
}

.input-container:focus-within {
    background: #ffffff;
    border-color: #10b981;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
}

.chat-textarea {
    flex: 1;
    border: none;
    background: transparent;
    outline: none;
    resize: none;
    font-size: 13px;
    line-height: 1.4;
    max-height: 80px;
    color: var(--ys-text-main);
    padding: 4px 0;
}

.chat-textarea::placeholder {
    color: #94a3b8;
}

.btn-send {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border: none;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: transform 0.15s ease, opacity 0.15s ease;
    flex-shrink: 0;
}

.btn-send:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-send:not(:disabled):hover {
    transform: scale(1.08);
}

.chat-disclaimer {
    text-align: center;
    font-size: 9.5px;
    color: #94a3b8;
    margin-top: 6px;
}

/* Responsive */
@media (max-width: 576px) {
    .yuksehat-chat-widget {
        bottom: 16px;
        right: 16px;
    }
    .chat-box {
        bottom: 70px;
        right: 0;
        width: calc(100vw - 32px);
        height: calc(100vh - 100px);
        max-height: 560px;
    }
    .chat-trigger-badge {
        display: none;
    }
}
</style>

<!-- ===== CHATBOT JAVASCRIPT ===== -->
<script>
(function() {
    const NODE_SERVER_URL = 'http://localhost:3005';
    const PHP_FALLBACK_URL = '<?= $baseUrl ?>/chatbot/send';
    const STATUS_CHECK_URL = '<?= $baseUrl ?>/chatbot/status';
    const CURRENT_USER = <?= json_encode($userData) ?>;

    const triggerBtn = document.getElementById('yuksehatChatTrigger');
    const chatBox = document.getElementById('yuksehatChatBox');
    const closeBtn = document.getElementById('btnCloseChat');
    const clearBtn = document.getElementById('btnClearChat');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const btnSend = document.getElementById('btnSendChat');
    const chatMessages = document.getElementById('chatMessages');
    const welcomeCard = document.getElementById('chatWelcomeCard');
    const typingIndicator = document.getElementById('typingIndicator');
    const statusDot = document.getElementById('yuksehatStatusDot');
    const statusText = document.getElementById('yuksehatStatusText');

    let chatHistory = [];
    let isProcessing = false;

    // Load history from localStorage
    function loadHistory() {
        try {
            const saved = localStorage.getItem('yuksehat_chat_history');
            if (saved) {
                chatHistory = JSON.parse(saved);
                if (Array.isArray(chatHistory) && chatHistory.length > 0) {
                    if (welcomeCard) welcomeCard.style.display = 'none';
                    chatHistory.forEach(item => {
                        renderMessageBubble(item.role, item.text, item.time, false);
                    });
                    scrollToBottom();
                }
            }
        } catch (e) {
            console.warn('[YukSehat AI] Gagal memuat riwayat chat:', e);
        }
    }

    // Save history to localStorage
    function saveHistory() {
        try {
            localStorage.setItem('yuksehat_chat_history', JSON.stringify(chatHistory.slice(-20)));
        } catch (e) {}
    }

    // Toggle Chat Window
    function toggleChat() {
        const isOpen = chatBox.classList.contains('is-open');
        if (isOpen) {
            chatBox.classList.remove('is-open');
            triggerBtn.classList.remove('is-active');
        } else {
            chatBox.classList.add('is-open');
            triggerBtn.classList.add('is-active');
            scrollToBottom();
            setTimeout(() => chatInput.focus(), 200);
        }
    }

    // Scroll to bottom
    function scrollToBottom() {
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 50);
    }

    // Parse Markdown sederhana ke HTML
    function parseMarkdown(text) {
        if (!text) return '';
        let escaped = text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        // Bold **text**
        escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Italic *text*
        escaped = escaped.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        // Lines & lists
        const lines = escaped.split('\n');
        let inList = false;
        let htmlLines = [];

        for (let line of lines) {
            const listMatch = line.match(/^(\*|-|\d+\.)\s+(.+)$/);
            if (listMatch) {
                if (!inList) {
                    htmlLines.push('<ul class="mb-2">');
                    inList = true;
                }
                htmlLines.push(`<li>${listMatch[2]}</li>`);
            } else {
                if (inList) {
                    htmlLines.push('</ul>');
                    inList = false;
                }
                if (line.trim().startsWith('---')) {
                    htmlLines.push('<hr>');
                } else if (line.trim()) {
                    htmlLines.push(`<p class="mb-1">${line}</p>`);
                }
            }
        }
        if (inList) htmlLines.push('</ul>');

        return htmlLines.join('');
    }

    // Render message bubble
    function renderMessageBubble(role, text, time, animate = true) {
        const row = document.createElement('div');
        row.className = `message-row ${role === 'user' ? 'user' : 'bot'}`;
        if (!animate) row.style.animation = 'none';

        const bubble = document.createElement('div');
        bubble.className = 'msg-bubble';

        if (role === 'user') {
            bubble.textContent = text;
        } else {
            bubble.innerHTML = parseMarkdown(text);
        }

        const meta = document.createElement('div');
        meta.className = 'msg-meta';
        const timeStr = time || new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        if (role === 'model' || role === 'assistant') {
            meta.innerHTML = `<span>${timeStr}</span> <button type="button" class="btn-copy-msg" title="Salin Jawaban"><i class="bi bi-clipboard"></i></button>`;
            const copyBtn = meta.querySelector('.btn-copy-msg');
            copyBtn.addEventListener('click', () => {
                navigator.clipboard.writeText(text).then(() => {
                    copyBtn.innerHTML = '<i class="bi bi-check2 text-success"></i>';
                    setTimeout(() => { copyBtn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
                });
            });
        } else {
            meta.textContent = timeStr;
        }

        row.appendChild(bubble);
        row.appendChild(meta);

        // Sisipkan sebelum typing indicator
        chatMessages.insertBefore(row, typingIndicator);
    }

    // Send Message
    async function sendMessage(text) {
        const cleanText = (text || chatInput.value || '').trim();
        if (!cleanText || isProcessing) return;

        isProcessing = true;
        chatInput.value = '';
        btnSend.disabled = true;

        if (welcomeCard) welcomeCard.style.display = 'none';

        const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        // Tampilkan pesan user
        renderMessageBubble('user', cleanText, nowTime);
        chatHistory.push({ role: 'user', text: cleanText, time: nowTime });
        saveHistory();
        scrollToBottom();

        // Tampilkan typing indicator
        typingIndicator.classList.remove('d-none');
        scrollToBottom();

        try {
            let replyText = null;

            // 1. Kirim pesan ke PHP backend (memiliki session terverifikasi dan otorisasi DB)
            try {
                const phpController = new AbortController();
                const timeoutId = setTimeout(() => phpController.abort(), 20000);

                const phpRes = await fetch(PHP_FALLBACK_URL, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: cleanText,
                        history: chatHistory.map(h => ({ role: h.role, text: h.text })),
                        user: CURRENT_USER
                    }),
                    signal: phpController.signal
                });
                clearTimeout(timeoutId);

                if (phpRes.ok) {
                    const phpData = await phpRes.json();
                    if (phpData && phpData.success && phpData.reply) {
                        replyText = phpData.reply;
                    }
                }
            } catch (phpErr) {
                console.warn('[YukSehat AI] PHP backend fetch kendala, mencoba Node.js server...', phpErr);
            }

            // 2. Jika PHP backend belum merespons, coba hubungi Node.js server langsung (port 3005)
            if (!replyText) {
                try {
                    const nodeController = new AbortController();
                    const timeoutId = setTimeout(() => nodeController.abort(), 18000);

                    const nodeRes = await fetch(`${NODE_SERVER_URL}/api/chat`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: cleanText,
                            history: chatHistory.map(h => ({ role: h.role, text: h.text })),
                            user: CURRENT_USER
                        }),
                        signal: nodeController.signal
                    });
                    clearTimeout(timeoutId);

                    if (nodeRes.ok) {
                        const nodeData = await nodeRes.json();
                        if (nodeData && nodeData.success && nodeData.reply) {
                            replyText = nodeData.reply;
                            if (statusDot) statusDot.classList.remove('offline');
                            if (statusText) statusText.textContent = 'Gemini AI (Node)';
                        }
                    }
                } catch (nodeErr) {
                    console.warn('[YukSehat AI] Node.js server direct fetch juga gagal:', nodeErr);
                }
            }

            if (!replyText) {
                throw new Error('Tidak menerima balasan dari AI. Silakan coba lagi.');
            }

            // Render bot reply
            const botTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            renderMessageBubble('model', replyText, botTime);
            chatHistory.push({ role: 'model', text: replyText, time: botTime });
            saveHistory();

        } catch (err) {
            console.error('[YukSehat AI Error]:', err);
            const errTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            const errorMsg = `⚠️ Maaf, terjadi kendala saat memproses jawaban: ${err.message || 'Layanan sedang sibuk'}. Silakan coba kirim ulang sesaat lagi.`;
            renderMessageBubble('model', errorMsg, errTime);
        } finally {
            typingIndicator.classList.add('d-none');
            isProcessing = false;
            btnSend.disabled = false;
            scrollToBottom();
            chatInput.focus();
        }
    }

    // Check status server
    async function checkServerStatus() {
        try {
            const res = await fetch(`${NODE_SERVER_URL}/health`, { method: 'GET' });
            if (res.ok) {
                if (statusDot) statusDot.classList.remove('offline');
                if (statusText) statusText.textContent = 'Gemini AI (Node)';
            } else {
                throw new Error();
            }
        } catch (e) {
            // Cek via PHP
            try {
                const phpStatus = await fetch(STATUS_CHECK_URL);
                const sData = await phpStatus.json();
                const provName = sData.provider === 'nvidia' ? 'NVIDIA NIM' : (sData.provider === 'gemini' ? 'Gemini AI' : 'AI Assistant');
                if (statusDot) statusDot.classList.remove('offline');
                if (statusText) statusText.textContent = provName + (sData.node_server === 'online' ? ' (Node)' : ' (Aktif)');
            } catch (err) {
                if (statusDot) statusDot.classList.add('offline');
                if (statusText) statusText.textContent = 'AI (Offline)';
            }
        }
    }

    // Event Listeners
    if (triggerBtn) triggerBtn.addEventListener('click', toggleChat);
    if (closeBtn) closeBtn.addEventListener('click', toggleChat);

    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            const bsModal = new bootstrap.Modal(document.getElementById('modalClearChat'));
            bsModal.show();

            const confirmBtn = document.getElementById('btnConfirmClearChat');
            const newBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
            newBtn.addEventListener('click', () => {
                bsModal.hide();
                chatHistory = [];
                localStorage.removeItem('yuksehat_chat_history');
                const rows = chatMessages.querySelectorAll('.message-row');
                rows.forEach(r => r.remove());
                if (welcomeCard) welcomeCard.style.display = 'block';
            });
        });
    }

    if (chatForm) {
        chatForm.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessage();
        });
    }

    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    // Quick prompts
    document.querySelectorAll('.quick-prompt-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const prompt = btn.getAttribute('data-prompt');
            if (prompt) {
                sendMessage(prompt);
            }
        });
    });

    // Init
    loadHistory();
    checkServerStatus();
})();
</script>
<!-- ===== END YUKSEHAT AI CHATBOT WIDGET ===== -->
