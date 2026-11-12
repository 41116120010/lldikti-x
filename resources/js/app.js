/**
 * SIPERAPAT - Enterprise Gov-Tech Frontend Engine
 * Features:
 * 1. Seamless Navigation Engine (View Transitions API + Fast AJAX Morphing)
 * 2. Flat Solid Blue Top Loading Progress Bar with Natural Cadence
 * 3. Universal Modal Dialog & Toast System
 * 4. Client-side Form Validation & Security Helpers
 * 5. Persistent Layout & Mobile Responsive Controls
 * 6. WebRTC Camera & Signature Pad Lifecycle Handling
 */

/**
 * Escape a string for safe interpolation into an HTML template literal.
 * Only used for the handful of call sites that deliberately opt into isHtml;
 * every other value is written with textContent instead.
 */
const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (ch) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;',
}[ch]));

// --- 1. PROGRESS BAR CONTROLLER ---
const ProgressBar = {
    el: null,
    timer: null,
    val: 0,

    init() {
        this.el = document.getElementById('app-progress-bar');
    },

    start() {
        if (!this.el) this.init();
        if (!this.el) return;

        clearInterval(this.timer);
        this.val = 25;
        this.el.classList.remove('done');
        this.el.classList.add('loading');
        this.el.style.width = `${this.val}%`;
        this.el.style.opacity = '1';

        this.timer = setInterval(() => {
            if (this.val < 85) {
                this.val += (85 - this.val) * 0.18;
                if (this.el) this.el.style.width = `${this.val}%`;
            }
        }, 100);
    },

    finish() {
        if (!this.el) return;
        clearInterval(this.timer);
        this.val = 100;
        this.el.style.width = '100%';

        setTimeout(() => {
            if (this.el) {
                this.el.classList.add('done');
                setTimeout(() => {
                    if (this.el) {
                        this.el.classList.remove('loading', 'done');
                        this.el.style.width = '0%';
                        this.el.style.opacity = '0';
                    }
                }, 250);
            }
        }, 160);
    },

    reset() {
        if (!this.el) return;
        clearInterval(this.timer);
        this.el.classList.remove('loading', 'done');
        this.el.style.width = '0%';
        this.el.style.opacity = '0';
    }
};

// --- 2. GLOBAL INTERACTION LISTENERS & INITIALIZATION ---
function initGlobalListeners() {
    // Password Toggle Utility
    const eyeIcon = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOffIcon = '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-3.11 4.24M6.11 6.11A13.2 13.2 0 0 0 2 11s3.5 7 10 7a10.9 10.9 0 0 0 4.11-.8"/>';
    
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        if (button.dataset.hasListener === 'true') return;
        button.dataset.hasListener = 'true';
        if (!button.hasAttribute('aria-pressed')) {
            button.setAttribute('aria-pressed', 'false');
        }

        button.addEventListener('click', () => {
            const input = document.querySelector('#' + button.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const svg = button.querySelector('svg');
            if (svg) svg.innerHTML = show ? eyeOffIcon : eyeIcon;
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            button.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    });

    // Form Loading State on Submit
    document.querySelectorAll('form.auth-form').forEach(form => {
        if (form.dataset.hasListener === 'true') return;
        form.dataset.hasListener = 'true';

        form.addEventListener('submit', () => {
            const submit = form.querySelector('.auth-submit');
            if (!submit || submit.disabled) return;
            submit.disabled = true;
            submit.dataset.originalLabel = submit.textContent;
            submit.textContent = submit.dataset.loadingLabel || 'Memproses...';
        });
    });

    // Universal Modal & Toast Definitions
    const modalBackdrop = document.querySelector('#app-modal');
    const modalContent = document.querySelector('#modal-content');
    const modalTimerTrack = document.querySelector('#modal-timer-track');
    const modalTimerBar = document.querySelector('#modal-timer-bar');
    const toast = document.querySelector('#app-toast');
    let toastTimer;

    // Modal Auto-Dismiss Timer State Machine
    let modalTimerInterval = null;
    let modalRemainingMs = 0;
    let modalTotalMs = 0;
    let modalIsPaused = false;
    let modalActiveConfirmBtn = null;
    let modalBaseConfirmText = '';
    let modalHintEl = null;

    window.clearModalTimer = () => {
        if (modalTimerInterval) {
            clearInterval(modalTimerInterval);
            modalTimerInterval = null;
        }
        modalRemainingMs = 0;
        modalTotalMs = 0;
        modalIsPaused = false;
        if (modalTimerTrack) {
            modalTimerTrack.classList.add('hidden');
        }
        if (modalTimerBar) {
            modalTimerBar.style.width = '100%';
            modalTimerBar.className = 'modal-timer-bar';
        }
        if (modalActiveConfirmBtn && modalBaseConfirmText) {
            modalActiveConfirmBtn.textContent = modalBaseConfirmText;
        }
        modalActiveConfirmBtn = null;
        modalBaseConfirmText = '';
        modalHintEl = null;
    };

    window.closeModal = () => {
        window.clearModalTimer();
        if (!modalBackdrop) return;
        modalBackdrop.classList.remove('open');
        modalBackdrop.setAttribute('aria-hidden', 'true');
    };

    window.showModal = ({
        title = 'Pemberitahuan',
        message = '',
        errors = null,
        type = 'info',
        confirmText = 'Tutup',
        cancelText = null,
        onConfirm = null,
        onCancel = null,
        isHtml = false,
        autoClose = null,
        autoCloseDelay = null
    }) => {
        if (!modalBackdrop || !modalContent) return;
        window.clearModalTimer();

        // Determine if this modal should auto-close
        // Default: true for alert/message modals (when cancelText is null and type is not 'confirm')
        const shouldAutoClose = autoClose !== null ? Boolean(autoClose) : (!cancelText && type !== 'confirm');

        // Determine delay duration
        let delayMs = 4000;
        if (typeof autoCloseDelay === 'number' && autoCloseDelay > 0) {
            delayMs = autoCloseDelay;
        } else {
            switch (type) {
                case 'success': delayMs = 3500; break;
                case 'info': delayMs = 4000; break;
                case 'warning': delayMs = 5000; break;
                case 'error': delayMs = 6000; break;
                default: delayMs = 4000; break;
            }
        }

        let iconSvg = '';
        let headerColor = 'text-blue-600';
        let btnColor = 'bg-blue-600 hover:bg-blue-700 text-white';
        let timerBarColor = 'bg-blue-500';

        if (type === 'success') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>';
            headerColor = 'text-emerald-800';
            btnColor = 'bg-emerald-600 hover:bg-emerald-700 text-white';
            timerBarColor = 'bg-emerald-500';
        } else if (type === 'error') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>';
            headerColor = 'text-rose-800';
            btnColor = 'bg-rose-600 hover:bg-rose-700 text-white';
            timerBarColor = 'bg-rose-500';
        } else if (type === 'warning') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>';
            headerColor = 'text-amber-800';
            btnColor = 'bg-amber-600 hover:bg-amber-700 text-white';
            timerBarColor = 'bg-amber-500';
        } else if (type === 'confirm') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>';
            headerColor = 'text-slate-900';
            btnColor = 'bg-blue-600 hover:bg-blue-700 text-white';
            timerBarColor = 'bg-blue-500';
        }

        const initialSeconds = Math.ceil(delayMs / 1000);
        const buttonDisplay = shouldAutoClose ? `${confirmText} (${initialSeconds}s)` : confirmText;

        let timerHintHtml = '';
        if (shouldAutoClose) {
            timerHintHtml = `
                <div class="modal-timer-hint" id="modal-timer-hint">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-slate-400"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span id="modal-timer-status">Menutup otomatis dalam <strong class="text-slate-700 font-bold" id="modal-timer-sec">${initialSeconds}</strong> detik</span>
                </div>
            `;
        }

        let actionsHtml = '';
        if (cancelText) {
            actionsHtml = `
                <div class="modal-actions mt-5 flex items-center justify-end gap-2.5">
                    <button type="button" class="button secondary text-xs px-4 py-2" id="modal-cancel-btn"></button>
                    <button type="button" class="button ${btnColor} text-xs px-4 py-2 font-semibold" id="modal-confirm-btn"></button>
                </div>
                ${timerHintHtml}
            `;
        } else {
            actionsHtml = `
                <div class="modal-actions mt-5 flex flex-col sm:flex-row items-center justify-end gap-2">
                    <button type="button" class="button ${btnColor} text-xs px-5 py-2.5 w-full sm:w-auto font-semibold" id="modal-confirm-btn"></button>
                </div>
                ${timerHintHtml}
            `;
        }

        // Only our own markup is written through innerHTML. Every value that may
        // carry user-supplied text (title, message, validation errors, button
        // labels) is assigned afterwards through textContent, so it can never be
        // parsed as HTML — including flash messages that echo back a value the
        // user typed, e.g. an agenda title.
        modalContent.innerHTML = `
            <div class="text-center sm:text-left">
                ${iconSvg}
                <h3 class="text-base font-bold ${headerColor} mb-2 text-center" id="modal-title-text"></h3>
                <div id="modal-message" class="text-xs text-slate-600 leading-relaxed text-center"></div>
                ${actionsHtml}
            </div>
        `;

        modalContent.querySelector('#modal-title-text').textContent = title;

        const messageEl = modalContent.querySelector('#modal-message');
        if (isHtml) {
            messageEl.innerHTML = message;
        } else if (Array.isArray(errors) && errors.length) {
            const list = document.createElement('ul');
            list.className = 'list-disc list-inside text-left space-y-0.5';
            errors.forEach((line) => {
                const item = document.createElement('li');
                item.textContent = line;
                list.appendChild(item);
            });
            messageEl.appendChild(list);
        } else {
            messageEl.textContent = message;
        }

        const confirmBtn = modalContent.querySelector('#modal-confirm-btn');
        const cancelBtn = modalContent.querySelector('#modal-cancel-btn');
        if (confirmBtn) confirmBtn.textContent = buttonDisplay;
        if (cancelBtn) cancelBtn.textContent = cancelText;
        modalHintEl = modalContent.querySelector('#modal-timer-hint');
        const statusSecEl = modalContent.querySelector('#modal-timer-sec');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                window.closeModal();
                if (typeof onConfirm === 'function') onConfirm();
            });
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                window.closeModal();
                if (typeof onCancel === 'function') onCancel();
            });
        }

        modalBackdrop.classList.add('open');
        modalBackdrop.setAttribute('aria-hidden', 'false');

        // Setup Auto-Close Timer if enabled
        if (shouldAutoClose) {
            modalTotalMs = delayMs;
            modalRemainingMs = delayMs;
            modalIsPaused = false;
            modalActiveConfirmBtn = confirmBtn;
            modalBaseConfirmText = confirmText;

            if (modalTimerTrack && modalTimerBar) {
                modalTimerTrack.classList.remove('hidden');
                modalTimerBar.className = `modal-timer-bar ${timerBarColor}`;
                modalTimerBar.style.width = '100%';
            }

            const stepMs = 50;
            modalTimerInterval = setInterval(() => {
                if (!modalIsPaused) {
                    modalRemainingMs -= stepMs;
                    if (modalTimerBar) {
                        const pct = Math.max(0, (modalRemainingMs / modalTotalMs) * 100);
                        modalTimerBar.style.width = `${pct}%`;
                    }

                    const curSec = Math.max(1, Math.ceil(modalRemainingMs / 1000));
                    if (modalActiveConfirmBtn) {
                        modalActiveConfirmBtn.textContent = `${modalBaseConfirmText} (${curSec}s)`;
                    }
                    if (statusSecEl) {
                        statusSecEl.textContent = curSec;
                    }

                    if (modalRemainingMs <= 0) {
                        window.closeModal();
                        if (typeof onConfirm === 'function') onConfirm();
                    }
                }
            }, stepMs);
        }
    };

    if (modalBackdrop && !modalBackdrop.dataset.hasListener) {
        modalBackdrop.dataset.hasListener = 'true';
        document.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', window.closeModal));
        modalBackdrop.addEventListener('click', (event) => {
            if (event.target === modalBackdrop) window.closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') window.closeModal();
        });

        // Pause timer on hover and touch over the modal card
        const modalCard = modalBackdrop.querySelector('.modal');
        if (modalCard) {
            const pauseTimer = () => {
                modalIsPaused = true;
                if (modalHintEl) {
                    modalHintEl.classList.add('paused');
                    const statusTextEl = modalHintEl.querySelector('#modal-timer-status');
                    if (statusTextEl) {
                        statusTextEl.innerHTML = '<strong class="text-amber-700">Otomatis menutup dijeda</strong> (geser kursor untuk melanjutkan)';
                    }
                }
            };

            const resumeTimer = () => {
                modalIsPaused = false;
                if (modalHintEl) {
                    modalHintEl.classList.remove('paused');
                    const statusTextEl = modalHintEl.querySelector('#modal-timer-status');
                    const curSec = Math.max(1, Math.ceil(modalRemainingMs / 1000));
                    if (statusTextEl) {
                        statusTextEl.innerHTML = `Menutup otomatis dalam <strong class="text-slate-700 font-bold" id="modal-timer-sec">${curSec}</strong> detik`;
                    }
                }
            };

            modalCard.addEventListener('mouseenter', pauseTimer);
            modalCard.addEventListener('mouseleave', resumeTimer);
            modalCard.addEventListener('touchstart', pauseTimer, { passive: true });
            modalCard.addEventListener('touchend', resumeTimer);
        }
    }

    // Process Server Flash Data on Load
    const flashData = document.querySelector('#flash-modal-data');
    if (flashData && !flashData.dataset.processed) {
        flashData.dataset.processed = 'true';
        const type = flashData.dataset.type || 'info';
        const title = flashData.dataset.title || 'Pemberitahuan';
        const message = flashData.dataset.message || '';
        const autoClose = flashData.dataset.autoClose !== 'false';
        const autoCloseDelay = flashData.dataset.delay ? parseInt(flashData.dataset.delay, 10) : null;

        // Validation errors arrive as a JSON array so the layout never has to
        // assemble them into an HTML string.
        let errors = null;
        if (flashData.dataset.errors) {
            try {
                const parsed = JSON.parse(flashData.dataset.errors);
                if (Array.isArray(parsed)) errors = parsed;
            } catch (err) {
                console.warn('[Flash] Unable to parse validation errors payload.', err);
            }
        }

        if (message || (Array.isArray(errors) && errors.length)) {
            window.showModal({
                title: title,
                message: message,
                errors: errors,
                type: type,
                confirmText: 'Mengerti & Tutup',
                autoClose: autoClose,
                autoCloseDelay: autoCloseDelay
            });
        }
    }

    // Intercept Forms with Custom Confirmation Modals
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        if (form.dataset.hasConfirmListener === 'true') return;
        form.dataset.hasConfirmListener = 'true';

        form.addEventListener('submit', (e) => {
            if (form.dataset.confirmed === 'true') return;
            e.preventDefault();

            const message = form.dataset.confirm || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            const title = form.dataset.confirmTitle || 'Konfirmasi Tindakan';
            const type = form.dataset.confirmType || 'confirm';
            const confirmBtnText = form.dataset.confirmBtn || 'Ya, Lanjutkan';

            window.showModal({
                title: title,
                message: message,
                type: type,
                confirmText: confirmBtnText,
                cancelText: 'Batal',
                onConfirm: () => {
                    form.dataset.confirmed = 'true';
                    if (typeof window.__onSuratEdaranPreSubmit === 'function') {
                        window.__onSuratEdaranPreSubmit();
                    }
                    form.submit();
                }
            });
        });
    });

    // Form Client-side Validation Interceptor
    document.querySelectorAll('form').forEach(form => {
        if (form.dataset.hasValListener === 'true') return;
        form.dataset.hasValListener = 'true';

        form.addEventListener('invalid', (e) => {
            const firstInvalid = form.querySelector(':invalid');
            if (e.target === firstInvalid) {
                let label = form.querySelector(`label[for="${e.target.id}"]`)?.textContent || e.target.getAttribute('placeholder') || e.target.name || 'Kolom isian';
                label = label.replace(/[\*•]/g, '').trim();

                window.showModal({
                    title: 'Kondisi Belum Terpenuhi',
                    message: `Mohon lengkapi data wajib pada formulir: <strong>${escapeHtml(label)}</strong> sebelum menyimpan data.`,
                    isHtml: true,
                    type: 'warning',
                    confirmText: 'Periksa Kembali'
                });
            }
        }, true);
    });

    // Mobile Sidebar Drawer Controller
    const sidebar = document.querySelector('#app-sidebar');
    const sidebarToggle = document.querySelector('#mobile-sidebar-toggle');
    const sidebarClose = document.querySelector('#mobile-sidebar-close');
    const sidebarOverlay = document.querySelector('#sidebar-overlay');

    const openMobileSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('open');
        if (sidebarOverlay) sidebarOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    const closeMobileSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('open');
        if (sidebarOverlay) sidebarOverlay.classList.remove('open');
        document.body.style.overflow = '';
    };

    if (sidebarToggle && !sidebarToggle.dataset.hasListener) {
        sidebarToggle.dataset.hasListener = 'true';
        sidebarToggle.addEventListener('click', openMobileSidebar);
    }
    if (sidebarClose && !sidebarClose.dataset.hasListener) {
        sidebarClose.dataset.hasListener = 'true';
        sidebarClose.addEventListener('click', closeMobileSidebar);
    }
    if (sidebarOverlay && !sidebarOverlay.dataset.hasListener) {
        sidebarOverlay.dataset.hasListener = 'true';
        sidebarOverlay.addEventListener('click', closeMobileSidebar);
    }

    // User Profile Dropdown Controller
    const userDropdownBtn = document.querySelector('#user-profile-dropdown-btn');
    const userDropdownMenu = document.querySelector('#user-profile-dropdown-menu');
    const userDropdownChevron = document.querySelector('#user-profile-chevron');

    if (userDropdownBtn && userDropdownMenu && !userDropdownBtn.dataset.hasListener) {
        userDropdownBtn.dataset.hasListener = 'true';

        const toggleUserDropdown = (forceState) => {
            const isCurrentlyOpen = userDropdownBtn.getAttribute('aria-expanded') === 'true';
            const willOpen = forceState !== undefined ? forceState : !isCurrentlyOpen;

            if (willOpen) {
                userDropdownMenu.classList.remove('hidden');
                userDropdownBtn.setAttribute('aria-expanded', 'true');
                if (userDropdownChevron) userDropdownChevron.style.transform = 'rotate(180deg)';
            } else {
                userDropdownMenu.classList.add('hidden');
                userDropdownBtn.setAttribute('aria-expanded', 'false');
                if (userDropdownChevron) userDropdownChevron.style.transform = 'rotate(0deg)';
            }
        };

        userDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleUserDropdown();
        });

        document.addEventListener('click', (e) => {
            if (!userDropdownMenu.contains(e.target) && !userDropdownBtn.contains(e.target)) {
                toggleUserDropdown(false);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                toggleUserDropdown(false);
            }
        });
    }

    // Initialize Microsoft Word Ribbon Rich Text Editors
    WordEditor.init();

    // Initialize Interactive Photo Preview Uploader
    PhotoPreviewUploader.init();

    // Initialize Office Document WYSIWYG Workstation
    OfficeWorkstation.init();

    // Initialize Surat Edaran / Undangan Document WYSIWYG Workstation
    SuratEdaranWorkstation.init();
}

// --- 2.5. MICROSOFT WORD RIBBON RICH TEXT EDITOR CONTROLLER ---
const WordEditor = {
    init() {
        document.querySelectorAll('.word-editor-wrapper').forEach(wrapper => {
            if (wrapper.dataset.initialized === 'true') return;
            wrapper.dataset.initialized = 'true';

            const contentEl = wrapper.querySelector('.word-editor-content');
            const hiddenInput = wrapper.querySelector('textarea[name], input[name]');
            const ribbon = wrapper.querySelector('.word-editor-ribbon');
            const formatSelect = ribbon ? ribbon.querySelector('select[data-command="formatBlock"]') : null;
            const fontSizeSelect = ribbon ? ribbon.querySelector('select[data-action="fontSize"]') : null;
            const wordsCountEl = wrapper.querySelector('.word-counter-words');
            const charsCountEl = wrapper.querySelector('.word-counter-chars');
            const tablePicker = ribbon ? ribbon.querySelector('.word-table-picker') : null;

            if (!contentEl || !hiddenInput) return;

            const updateCounters = () => {
                const text = contentEl.innerText.trim();
                const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
                const chars = text.length;
                if (wordsCountEl) wordsCountEl.textContent = `${words} Kata`;
                if (charsCountEl) charsCountEl.textContent = `${chars} Karakter`;
            };

            const syncContent = () => {
                hiddenInput.value = contentEl.innerHTML;
                updateCounters();
            };

            const updateActiveStates = () => {
                if (!ribbon) return;
                const commands = [
                    'bold', 'italic', 'underline', 'strikeThrough',
                    'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull',
                    'insertUnorderedList', 'insertOrderedList',
                    'superscript', 'subscript'
                ];
                commands.forEach(cmd => {
                    const btn = ribbon.querySelector(`button[data-command="${cmd}"]`);
                    if (btn) {
                        try {
                            if (document.queryCommandState(cmd)) {
                                btn.classList.add('is-active');
                            } else {
                                btn.classList.remove('is-active');
                            }
                        } catch (e) {}
                    }
                });

                if (formatSelect) {
                    try {
                        const block = (document.queryCommandValue('formatBlock') || '').toLowerCase();
                        if (['h2', 'h3', 'h4', 'p', 'blockquote'].includes(block)) {
                            formatSelect.value = block;
                        } else {
                            formatSelect.value = 'p';
                        }
                    } catch (e) {}
                }
            };

            if (ribbon) {
                // 1. Standard formatting commands
                ribbon.querySelectorAll('button[data-command]').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const cmd = btn.dataset.command;
                        contentEl.focus();

                        if (cmd === 'blockquote') {
                            document.execCommand('formatBlock', false, 'blockquote');
                        } else if (cmd === 'insertHorizontalRule') {
                            document.execCommand('insertHorizontalRule', false, null);
                        } else {
                            document.execCommand(cmd, false, null);
                        }

                        syncContent();
                        updateActiveStates();
                    });
                });

                // 2. Paragraph style dropdown
                if (formatSelect) {
                    formatSelect.addEventListener('change', (e) => {
                        contentEl.focus();
                        const val = e.target.value;
                        if (val) {
                            document.execCommand('formatBlock', false, val);
                            syncContent();
                            updateActiveStates();
                        }
                    });
                }

                // 3. Font Size dropdown
                if (fontSizeSelect) {
                    fontSizeSelect.addEventListener('change', (e) => {
                        contentEl.focus();
                        const val = e.target.value;
                        if (val) {
                            document.execCommand('fontSize', false, val);
                            syncContent();
                            updateActiveStates();
                        }
                    });
                }

                // 4. Text Color & Highlight Pickers
                ribbon.querySelectorAll('.word-color-btn').forEach(colorBtn => {
                    const action = colorBtn.dataset.action;
                    const colorInput = ribbon.querySelector(`input[data-color-for="${action}"]`);
                    const indicator = colorBtn.querySelector('.color-indicator');

                    if (colorInput) {
                        colorBtn.addEventListener('click', (e) => {
                            e.preventDefault();
                            colorInput.click();
                        });

                        colorInput.addEventListener('input', (e) => {
                            const selectedColor = e.target.value;
                            if (indicator) indicator.style.background = selectedColor;
                            contentEl.focus();

                            if (action === 'textColor') {
                                document.execCommand('foreColor', false, selectedColor);
                            } else if (action === 'highlight') {
                                try {
                                    if (!document.execCommand('hiliteColor', false, selectedColor)) {
                                        document.execCommand('backColor', false, selectedColor);
                                    }
                                } catch (err) {
                                    document.execCommand('backColor', false, selectedColor);
                                }
                            }

                            syncContent();
                            updateActiveStates();
                        });
                    }
                });

                // 5. Table Grid Picker (6x6)
                const tableBtn = ribbon.querySelector('button[data-action="insertTable"]');
                if (tableBtn && tablePicker) {
                    const maxRows = 6;
                    const maxCols = 6;
                    let gridHtml = '<div class="word-table-grid">';
                    for (let r = 1; r <= maxRows; r++) {
                        for (let c = 1; c <= maxCols; c++) {
                            gridHtml += `<div class="word-table-cell" data-row="${r}" data-col="${c}"></div>`;
                        }
                    }
                    gridHtml += '</div><div class="word-table-picker-label">Pilih Ukuran (0 × 0)</div>';
                    tablePicker.innerHTML = gridHtml;

                    const cells = tablePicker.querySelectorAll('.word-table-cell');
                    const labelEl = tablePicker.querySelector('.word-table-picker-label');

                    const updateGridSelection = (targetRow, targetCol) => {
                        cells.forEach(cell => {
                            const r = parseInt(cell.dataset.row, 10);
                            const c = parseInt(cell.dataset.col, 10);
                            if (r <= targetRow && c <= targetCol) {
                                cell.classList.add('selected');
                            } else {
                                cell.classList.remove('selected');
                            }
                        });
                        if (labelEl) {
                            labelEl.textContent = targetRow > 0 && targetCol > 0 
                                ? `${targetRow} × ${targetCol} Tabel` 
                                : 'Pilih Ukuran (0 × 0)';
                        }
                    };

                    cells.forEach(cell => {
                        cell.addEventListener('mouseenter', () => {
                            const r = parseInt(cell.dataset.row, 10);
                            const c = parseInt(cell.dataset.col, 10);
                            updateGridSelection(r, c);
                        });

                        cell.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            const r = parseInt(cell.dataset.row, 10);
                            const c = parseInt(cell.dataset.col, 10);

                            let tableHtml = '<table class="w-full"><thead><tr>';
                            for (let colIdx = 1; colIdx <= c; colIdx++) {
                                tableHtml += `<th>Kolom ${colIdx}</th>`;
                            }
                            tableHtml += '</tr></thead><tbody>';
                            for (let rowIdx = 1; rowIdx < r; rowIdx++) {
                                tableHtml += '<tr>';
                                for (let colIdx = 1; colIdx <= c; colIdx++) {
                                    tableHtml += '<td>&nbsp;</td>';
                                }
                                tableHtml += '</tr>';
                            }
                            tableHtml += '</tbody></table><p><br></p>';

                            tablePicker.classList.remove('open');
                            contentEl.focus();
                            document.execCommand('insertHTML', false, tableHtml);
                            syncContent();
                            updateActiveStates();
                        });
                    });

                    tablePicker.addEventListener('mouseleave', () => {
                        updateGridSelection(0, 0);
                    });

                    tableBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        tablePicker.classList.toggle('open');
                        updateGridSelection(0, 0);
                    });

                    document.addEventListener('click', (e) => {
                        if (!tablePicker.contains(e.target) && e.target !== tableBtn) {
                            tablePicker.classList.remove('open');
                        }
                    });
                }

                // 6. Clear All Button with confirmation
                const clearAllBtn = ribbon.querySelector('button[data-action="clearAll"]');
                if (clearAllBtn) {
                    clearAllBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        if (!contentEl.innerText.trim() && !contentEl.querySelector('table, hr, img')) return;

                        if (window.showModal) {
                            window.showModal({
                                title: 'Hapus Semua Isi Catatan',
                                message: 'Apakah Anda yakin ingin mengosongkan seluruh isi catatan ini? Tindakan ini tidak dapat dibatalkan.',
                                type: 'warning',
                                confirmText: 'Ya, Kosongkan',
                                cancelText: 'Batal',
                                onConfirm: () => {
                                    contentEl.innerHTML = '';
                                    syncContent();
                                    updateActiveStates();
                                    contentEl.focus();
                                }
                            });
                        } else {
                            if (confirm('Kosongkan seluruh teks editor?')) {
                                contentEl.innerHTML = '';
                                syncContent();
                                updateActiveStates();
                                contentEl.focus();
                            }
                        }
                    });
                }
            }

            // Keyboard shortcuts (Ctrl+B, Ctrl+I, Ctrl+U)
            contentEl.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    if (e.key === 'b' || e.key === 'B') {
                        e.preventDefault();
                        document.execCommand('bold', false, null);
                        syncContent();
                        updateActiveStates();
                    } else if (e.key === 'i' || e.key === 'I') {
                        e.preventDefault();
                        document.execCommand('italic', false, null);
                        syncContent();
                        updateActiveStates();
                    } else if (e.key === 'u' || e.key === 'U') {
                        e.preventDefault();
                        document.execCommand('underline', false, null);
                        syncContent();
                        updateActiveStates();
                    }
                }
            });

            // Clean Paste Sanitizer (Strip harmful inline styles, scripts, Word cruft)
            contentEl.addEventListener('paste', (e) => {
                e.preventDefault();
                const clipboardData = e.clipboardData || window.clipboardData;
                const htmlData = clipboardData ? clipboardData.getData('text/html') : '';
                const textData = clipboardData ? clipboardData.getData('text/plain') : '';

                if (htmlData) {
                    try {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlData, 'text/html');

                        const disallowed = doc.querySelectorAll('script, style, meta, link, object, embed, iframe');
                        disallowed.forEach(el => el.remove());

                        const allowedTags = [
                            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
                            'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'hr',
                            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'sup', 'sub', 'span'
                        ];

                        const allElements = Array.from(doc.body.querySelectorAll('*'));
                        allElements.forEach(el => {
                            const tag = el.tagName.toLowerCase();
                            if (!allowedTags.includes(tag)) {
                                el.replaceWith(...el.childNodes);
                            } else {
                                const attrs = Array.from(el.attributes);
                                attrs.forEach(attr => {
                                    if (['colspan', 'rowspan'].includes(attr.name)) return;
                                    el.removeAttribute(attr.name);
                                });
                            }
                        });

                        const cleanHtml = doc.body.innerHTML.trim();
                        if (cleanHtml) {
                            document.execCommand('insertHTML', false, cleanHtml);
                        } else {
                            document.execCommand('insertText', false, textData);
                        }
                    } catch (err) {
                        document.execCommand('insertText', false, textData);
                    }
                } else if (textData) {
                    const paragraphs = textData
                        .split(/\r?\n\r?\n/)
                        .map(p => p.trim())
                        .filter(Boolean)
                        .map(p => `<p>${p.replace(/\r?\n/g, '<br>')}</p>`)
                        .join('');

                    if (paragraphs) {
                        document.execCommand('insertHTML', false, paragraphs);
                    } else {
                        document.execCommand('insertText', false, textData);
                    }
                }

                setTimeout(() => {
                    syncContent();
                    updateActiveStates();
                }, 10);
            });

            contentEl.addEventListener('input', syncContent);
            contentEl.addEventListener('keyup', updateActiveStates);
            contentEl.addEventListener('mouseup', updateActiveStates);

            const form = contentEl.closest('form');
            if (form) {
                form.addEventListener('submit', () => {
                    syncContent();
                });
            }

            updateCounters();
        });
    }
};

window.WordEditor = WordEditor;

// --- 2.6. INTERACTIVE PHOTO PREVIEW & QUEUE UPLOADER ---
const PhotoPreviewUploader = {
    init() {
        const container = document.querySelector('#photo-uploader-container');
        if (!container || container.dataset.initialized === 'true') return;
        container.dataset.initialized = 'true';

        const fileInput = container.querySelector('#photos');
        const dropzone = container.querySelector('#photo-dropzone');
        const previewGrid = container.querySelector('#photo-preview-grid');
        const counterBadge = container.querySelector('#photo-counter-badge');

        if (!fileInput || !previewGrid) return;

        let currentFiles = [];
        let objectUrls = [];

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        const formatSize = (bytes) => {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        };

        const revokeAllUrls = () => {
            objectUrls.forEach(url => URL.revokeObjectURL(url));
            objectUrls = [];
        };

        const syncDataTransfer = () => {
            try {
                const dt = new DataTransfer();
                currentFiles.forEach(item => dt.items.add(item.file));
                fileInput.files = dt.files;
            } catch (err) {
                console.warn('DataTransfer sync warning:', err);
            }
        };

        const syncWorkstationAnnex = () => {
            window.__queuedNotulenPhotos = currentFiles.map((item, idx) => ({
                name: item.file.name,
                size: item.file.size,
                caption: item.caption || '',
                previewUrl: objectUrls[idx] || '',
            }));
            if (typeof window.rebuildSheetDocumentationAnnex === 'function') {
                window.rebuildSheetDocumentationAnnex();
            }
            window.dispatchEvent(new CustomEvent('office:reflow'));
        };

        const render = () => {
            revokeAllUrls();
            previewGrid.innerHTML = '';

            if (currentFiles.length === 0) {
                previewGrid.classList.add('hidden');
                if (counterBadge) counterBadge.classList.add('hidden');
                const navBadge = document.querySelector('#nav-photo-count');
                if (navBadge) {
                    const stored = parseInt(navBadge.dataset.storedCount || '0', 10);
                    navBadge.textContent = stored;
                }
                syncWorkstationAnnex();
                return;
            }

            previewGrid.classList.remove('hidden');
            if (counterBadge) {
                counterBadge.textContent = `${currentFiles.length} Foto Baru Terpilih`;
                counterBadge.classList.remove('hidden');
            }
            const navBadge = document.querySelector('#nav-photo-count');
            if (navBadge) {
                const stored = parseInt(navBadge.dataset.storedCount || '0', 10);
                navBadge.textContent = stored + currentFiles.length;
            }

            currentFiles.forEach((item, index) => {
                const file = item.file;
                const url = URL.createObjectURL(file);
                objectUrls.push(url);

                const card = document.createElement('div');
                card.className = 'relative group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:border-slate-300 transition flex flex-col justify-between';
                card.innerHTML = `
                    <div class="aspect-4/3 relative bg-slate-100 overflow-hidden flex items-center justify-center cursor-pointer group/thumb" title="Klik untuk pratinjau perbesar">
                        <img src="${url}" alt="${escapeHtml(file.name)}" class="w-full h-24 sm:h-28 object-cover group-hover/thumb:scale-105 transition duration-300">
                        <div class="absolute inset-0 bg-slate-950/0 group-hover/thumb:bg-slate-950/25 transition flex items-center justify-center">
                            <div class="w-7 h-7 rounded-full bg-slate-900/80 text-white flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition shadow-md">
                                <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            class="photo-preview-remove absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-md transition cursor-pointer z-10"
                            title="Hapus foto ini dari antrean upload"
                            aria-label="Hapus foto ${escapeHtml(file.name)}"
                            data-index="${index}"
                        >
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="p-2 border-t border-slate-100 bg-white space-y-1.5">
                        <div class="flex items-center justify-between gap-1">
                            <div class="text-[11px] font-bold text-slate-800 truncate" title="${escapeHtml(file.name)}">${escapeHtml(file.name)}</div>
                            <div class="text-[10px] text-slate-500 font-mono font-medium shrink-0">${formatSize(file.size)}</div>
                        </div>
                        <input
                            type="text"
                            name="captions[]"
                            value="${escapeHtml(item.caption || '')}"
                            maxlength="255"
                            placeholder="Ketik keterangan foto (opsional)..."
                            aria-label="Keterangan foto ${escapeHtml(file.name)}"
                            class="photo-caption-input w-full text-[11px] rounded-lg border border-slate-200 focus:border-slate-900 focus:ring-slate-900 py-1.5 px-2 text-slate-800 bg-slate-50 focus:bg-white transition font-medium"
                        >
                    </div>
                `;

                const thumbArea = card.querySelector('.group\\/thumb');
                if (thumbArea) {
                    thumbArea.addEventListener('click', (e) => {
                        if (e.target.closest('.photo-preview-remove')) return;
                        if (window.documentationPreviewModal) {
                            window.documentationPreviewModal.open({
                                items: [{
                                    url: url,
                                    caption: item.caption || file.name,
                                    agendaTitle: 'Pratinjau Antrean Berkas',
                                    date: formatSize(file.size),
                                    downloadUrl: url,
                                    fileType: 'image'
                                }],
                                currentIndex: 0
                            });
                        }
                    });
                }

                const captionInput = card.querySelector('.photo-caption-input');
                if (captionInput) {
                    captionInput.addEventListener('input', (e) => {
                        item.caption = e.target.value;
                        window.__officeDirtyState = true;
                        window.dispatchEvent(new CustomEvent('office:dirty-change', { detail: { dirty: true } }));
                        syncWorkstationAnnex();
                    });
                }

                const removeBtn = card.querySelector('.photo-preview-remove');
                removeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    removeFile(index);
                });

                previewGrid.appendChild(card);
            });

            syncWorkstationAnnex();
        };

        const removeFile = (index) => {
            currentFiles.splice(index, 1);
            syncDataTransfer();
            render();
        };

        const addFiles = (newFiles) => {
            const allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxBytes = 3 * 1024 * 1024; // 3MB
            const maxTotal = 10;

            let oversizedNames = [];

            Array.from(newFiles).forEach(file => {
                if (currentFiles.length >= maxTotal) return;

                if (!allowedMimes.includes(file.type) && !/\.(jpe?g|png|webp)$/i.test(file.name)) {
                    return;
                }

                if (file.size > maxBytes) {
                    oversizedNames.push(file.name);
                    return;
                }

                // Avoid exact duplicates in current list
                const isDuplicate = currentFiles.some(item => item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified);
                if (!isDuplicate) {
                    currentFiles.push({ file, caption: '' });
                }
            });

            if (oversizedNames.length > 0 && window.showModal) {
                window.showModal({
                    title: 'Ukuran Foto Terlalu Besar',
                    message: `Berkas (${oversizedNames.join(', ')}) melebihi batas maksimal 3 MB.`,
                    type: 'warning',
                    confirmText: 'Mengerti'
                });
            }

            if (currentFiles.length > 0) {
                window.__officeDirtyState = true;
                window.dispatchEvent(new CustomEvent('office:dirty-change', { detail: { dirty: true } }));
            }

            syncDataTransfer();
            render();
        };

        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                addFiles(e.target.files);
            }
        });

        if (dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('border-slate-500', 'bg-slate-100');
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('border-slate-500', 'bg-slate-100');
                });
            });

            dropzone.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    addFiles(dt.files);
                }
            });
        }

        const form = container.closest('form');
        if (form) {
            form.addEventListener('reset', () => {
                currentFiles = [];
                syncDataTransfer();
                render();
            });
        }
    }
};

window.PhotoPreviewUploader = PhotoPreviewUploader;

// --- 2.7. OFFICE DOCUMENT WYSIWYG WORKSTATION CONTROLLER ---
const OfficeWorkstation = {
    init() {
        const workstation = document.querySelector('#office-workstation');
        if (!workstation || workstation.dataset.initialized === 'true') return;
        workstation.dataset.initialized = 'true';

        const ribbon = workstation.querySelector('.word-editor-ribbon');
        const zoomContainer = workstation.querySelector('#office-zoom-container');
        const deskCanvas = workstation.querySelector('.office-desk-canvas');
        const s1Container = workstation.querySelector('#sheet-seksi-1-container');
        const s1NWrap = workstation.querySelector('#sheet-section-notulensi-wrapper');
        const s1KWrap = workstation.querySelector('#sheet-section-kesimpulan-wrapper');
        const seksi2Wrapper = workstation.querySelector('#sheet-seksi-2-wrapper');
        const continuationContainer = workstation.querySelector('#dynamic-continuation-sheets');
        const sigWrapper = workstation.querySelector('#sheet-pengesahan-final');
        const docAnnex = workstation.querySelector('#sheet-documentation-annex');
        const separator1 = workstation.querySelector('#page-separator-1');
        
        const notulensiBox = workstation.querySelector('#notulensi-content');
        const kesimpulanBox = workstation.querySelector('#kesimpulan-content');
        const notulensiHidden = workstation.querySelector('#notulensi-hidden');
        const kesimpulanHidden = workstation.querySelector('#kesimpulan-hidden');

        let activeBox = notulensiBox;
        let activeHidden = notulensiHidden;
        let savedRange = null;
        let isSubmittingForm = false;
        window.__officeDirtyState = false;

        const statusWordsEl = workstation.querySelector('#office-status-words');
        const statusSaveStateEl = workstation.querySelector('#office-status-save-state');
        const statusSaveDotEl = workstation.querySelector('#office-status-save-dot');
        const statusSaveLabelEl = workstation.querySelector('#office-status-save-label');

        const updateSaveStateUI = (dirty) => {
            window.__officeDirtyState = Boolean(dirty);
            if (!statusSaveStateEl || !statusSaveDotEl || !statusSaveLabelEl) return;
            if (window.__officeDirtyState) {
                statusSaveStateEl.className = 'inline-flex items-center gap-1.5 text-amber-400 font-semibold';
                statusSaveDotEl.className = 'w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse';
                statusSaveLabelEl.textContent = 'Perubahan Belum Disimpan';
            } else {
                statusSaveStateEl.className = 'inline-flex items-center gap-1.5 text-emerald-400 font-semibold';
                statusSaveDotEl.className = 'w-1.5 h-1.5 rounded-full bg-emerald-400';
                statusSaveLabelEl.textContent = 'Tersimpan';
            }
        };

        window.addEventListener('office:dirty-change', (e) => {
            updateSaveStateUI(e.detail?.dirty ?? true);
        });

        const updateWordCounter = () => {
            if (!statusWordsEl) return;
            let combinedText = '';
            workstation.querySelectorAll('.office-editable-box').forEach(b => {
                combinedText += ' ' + (b.innerText || '');
            });
            const words = combinedText.trim() ? combinedText.trim().split(/\s+/).filter(Boolean).length : 0;
            statusWordsEl.textContent = `${words} Kata`;
        };

        const syncContent = (markDirty = false) => {
            const nBoxes = workstation.querySelectorAll('[data-editor="notulensi"]');
            let joinedN = '';
            nBoxes.forEach(box => {
                const html = box.innerHTML.trim();
                if (html && html !== '<p><br></p>' && html !== '<br>') {
                    joinedN += html;
                }
            });
            if (notulensiHidden) notulensiHidden.value = joinedN;

            const kBoxes = workstation.querySelectorAll('[data-editor="kesimpulan"]');
            let joinedK = '';
            kBoxes.forEach(box => {
                const html = box.innerHTML.trim();
                if (html && html !== '<p><br></p>' && html !== '<br>') {
                    joinedK += html;
                }
            });
            if (kesimpulanHidden) kesimpulanHidden.value = joinedK;

            updateWordCounter();
            if (markDirty) {
                updateSaveStateUI(true);
            }
        };

        const updateActiveStates = () => {
            if (!ribbon) return;
            const commands = [
                'bold', 'italic', 'underline', 'strikeThrough',
                'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull',
                'insertUnorderedList', 'insertOrderedList',
                'superscript', 'subscript'
            ];
            commands.forEach(cmd => {
                const btn = ribbon.querySelector(`button[data-command="${cmd}"]`);
                if (btn) {
                    try {
                        if (document.queryCommandState(cmd)) {
                            btn.classList.add('is-active');
                        } else {
                            btn.classList.remove('is-active');
                        }
                    } catch (e) {}
                }
            });

            const formatSelect = ribbon.querySelector('select[data-command="formatBlock"]');
            if (formatSelect) {
                try {
                    const block = (document.queryCommandValue('formatBlock') || '').toLowerCase();
                    if (['h2', 'h3', 'h4', 'p', 'blockquote'].includes(block)) {
                        formatSelect.value = block;
                    } else {
                        formatSelect.value = 'p';
                    }
                } catch (e) {}
            }
        };

        const setActiveEditor = (box, hidden) => {
            activeBox = box;
            activeHidden = hidden;
            workstation.querySelectorAll('.office-editable-box').forEach(b => {
                b.classList.remove('is-active-editor');
            });
            if (box) box.classList.add('is-active-editor');
            updateActiveStates();
        };

        const rulerEl = workstation.querySelector('#office-document-ruler');
        const statusBarEl = workstation.querySelector('#office-status-bar');
        const statusSizeEl = workstation.querySelector('#office-status-size');
        const statusPageEl = workstation.querySelector('#office-status-page');
        const statusZoomEl = workstation.querySelector('#office-status-zoom');

        const titleEl = workstation.querySelector('.doc-running-header strong');
        const agendaTitle = workstation.dataset.agendaTitle || (titleEl ? titleEl.textContent : 'Agenda Rapat');

        // 1. Fixed A4 Paper Format Enforcement (210 × 297 mm)
        const setPaperSize = (triggerReflow = true) => {
            const allSheets = workstation.querySelectorAll('.office-paper-sheet');
            allSheets.forEach(sheet => {
                sheet.classList.remove('paper-f4');
                sheet.classList.add('paper-a4');
            });
            const allGaps = workstation.querySelectorAll('.office-page-gap');
            allGaps.forEach(gap => {
                gap.classList.remove('gap-f4');
            });
            if (rulerEl) {
                rulerEl.classList.remove('paper-f4', 'ruler-f4');
                rulerEl.classList.add('paper-a4');
            }
            if (statusBarEl) {
                statusBarEl.classList.remove('paper-f4', 'status-f4');
                statusBarEl.classList.add('paper-a4');
            }
            if (statusSizeEl) {
                statusSizeEl.textContent = 'A4 (210 × 297 mm)';
            }
            try {
                localStorage.setItem('siperapat_paper_format', 'a4');
            } catch (e) {}

            if (triggerReflow && paginationEngine) {
                paginationEngine.reflow();
            }
        };

        // 2. Zoom & Scaling Controls (with Layout Height Compensation & Ruler Sync)
        const zoomButtons = workstation.querySelectorAll('[data-zoom]');
        let currentZoomMode = window.innerWidth < 768 ? 'fit' : '100';
        let currentZoomScale = 1;

        const updateZoomCompensation = () => {
            if (!zoomContainer) return;
            zoomContainer.style.marginBottom = '0px';
            const unscaledHeight = zoomContainer.offsetHeight;
            if (unscaledHeight > 0 && currentZoomScale !== 1) {
                const heightDiff = Math.round(unscaledHeight * (currentZoomScale - 1));
                zoomContainer.style.marginBottom = `${heightDiff}px`;
            }
            if (rulerEl) {
                rulerEl.style.transformOrigin = 'top center';
                rulerEl.style.transform = currentZoomScale === 1 ? 'none' : `scaleX(${currentZoomScale})`;
            }
        };

        const setZoom = (zoomVal) => {
            if (!zoomContainer) return;
            currentZoomMode = zoomVal;
            zoomButtons.forEach(b => b.classList.remove('active-zoom', 'bg-slate-900', 'text-white'));

            const activeBtn = workstation.querySelector(`[data-zoom="${zoomVal}"]`);
            if (activeBtn) activeBtn.classList.add('active-zoom', 'bg-slate-900', 'text-white');

            let currentZoomText = `Zoom: ${zoomVal}%`;
            if (zoomVal === 'fit') {
                const canvasWidth = deskCanvas ? deskCanvas.clientWidth - 48 : window.innerWidth;
                const firstSheet = workstation.querySelector('.office-paper-sheet');
                const sheetWidthPx = firstSheet ? firstSheet.offsetWidth : 794;
                currentZoomScale = Math.min(1, Math.max(0.4, canvasWidth / sheetWidthPx));
                zoomContainer.style.transform = `scale(${currentZoomScale})`;
                currentZoomText = `Zoom: Sesuaikan (${Math.round(currentZoomScale * 100)}%)`;
            } else {
                currentZoomScale = parseFloat(zoomVal) / 100;
                zoomContainer.style.transform = currentZoomScale === 1 ? 'none' : `scale(${currentZoomScale})`;
            }
            if (statusZoomEl) {
                statusZoomEl.textContent = currentZoomText;
            }
            updateZoomCompensation();
        };

        zoomButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setZoom(btn.dataset.zoom);
            });
        });

        setZoom(currentZoomMode);

        // 2.1. Viewport Scroll Detection for Active Sheet Indicator
        const handleScroll = () => {
            const curSheets = workstation.querySelectorAll('.office-paper-sheet');
            if (curSheets.length === 0 || !statusPageEl) return;
            const midY = window.innerHeight / 2;
            let activePage = 1;
            curSheets.forEach((sheet, idx) => {
                const rect = sheet.getBoundingClientRect();
                if (rect.top <= midY && rect.bottom >= midY) {
                    activePage = idx + 1;
                }
            });
            statusPageEl.textContent = `Halaman ${activePage} dari ${curSheets.length}`;
        };
        window.addEventListener('scroll', handleScroll, { passive: true });
        if (deskCanvas) {
            deskCanvas.addEventListener('scroll', handleScroll, { passive: true });
        }

        // 2.2 Caret Preservation & Navigation Utilities
        const saveCaretInfo = () => {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || !activeBox) return null;
            const editorType = activeBox.dataset.editor;
            const range = sel.getRangeAt(0);

            let boxOffset = 0;
            try {
                const preCaretRange = range.cloneRange();
                preCaretRange.selectNodeContents(activeBox);
                preCaretRange.setEnd(range.endContainer, range.endOffset);
                boxOffset = preCaretRange.toString().length;
            } catch (e) {
                boxOffset = activeBox.textContent.length;
            }

            let globalOffset = 0;
            const boxes = Array.from(workstation.querySelectorAll(`[data-editor="${editorType}"]`));
            for (const b of boxes) {
                if (b === activeBox) {
                    globalOffset += boxOffset;
                    break;
                }
                globalOffset += b.textContent.length;
            }

            return { editorType, globalOffset };
        };

        const restoreCaretInfo = (caretInfo) => {
            if (!caretInfo || !caretInfo.editorType) return;
            const boxes = Array.from(workstation.querySelectorAll(`[data-editor="${caretInfo.editorType}"]`));
            if (boxes.length === 0) return;

            let remainingOffset = caretInfo.globalOffset;
            let targetBox = boxes[0];

            for (const b of boxes) {
                const textLen = b.textContent.length;
                if (remainingOffset <= textLen) {
                    targetBox = b;
                    break;
                }
                remainingOffset -= textLen;
                targetBox = b;
            }

            targetBox.focus();
            setActiveEditor(targetBox, targetBox.dataset.editor === 'kesimpulan' ? kesimpulanHidden : notulensiHidden);

            try {
                const sel = window.getSelection();
                const range = document.createRange();
                let currentOffset = 0;
                let found = false;

                function traverse(node) {
                    if (found) return;
                    if (node.nodeType === Node.TEXT_NODE) {
                        const len = node.textContent.length;
                        if (currentOffset + len >= remainingOffset) {
                            range.setStart(node, Math.min(Math.max(0, remainingOffset - currentOffset), len));
                            range.collapse(true);
                            found = true;
                            return;
                        }
                        currentOffset += len;
                    } else {
                        for (let i = 0; i < node.childNodes.length; i++) {
                            traverse(node.childNodes[i]);
                            if (found) return;
                        }
                    }
                }

                traverse(targetBox);
                if (!found) {
                    range.selectNodeContents(targetBox);
                    range.collapse(false);
                }
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (e) {
                targetBox.focus();
            }
        };

        const setCaretAtEnd = (el) => {
            el.focus();
            try {
                const range = document.createRange();
                range.selectNodeContents(el);
                range.collapse(false);
                const sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
            } catch (e) {}
        };

        // 2.3 Strict Page Margin Enforcement Helper (Zoom-Independent Geometry)
        const getBottomInSheet = (sheet, element) => {
            if (!sheet || !element) return 0;
            const sheetRect = sheet.getBoundingClientRect();
            const elRect = element.getBoundingClientRect();
            const scale = sheet.offsetWidth > 0 ? (sheetRect.width / sheet.offsetWidth) : 1;
            return (elRect.bottom - sheetRect.top) / (scale || 1);
        };

        const isExceedingMargin = (sheet, element) => {
            if (!sheet || !element) return false;
            const pageMaxHeight = sheet.offsetWidth > 0
                ? sheet.offsetWidth * (297 / 210)
                : 1122;
            const footerReserve = sheet.offsetWidth > 0
                ? sheet.offsetWidth * (24 / 210)
                : 90;
            const maxContentBottom = pageMaxHeight - footerReserve;
            return getBottomInSheet(sheet, element) > maxContentBottom;
        };

        // 2.4 Dynamic Multi-Page Reflow Engine (Non-Destructive Whole-Section Flow)
        let reflowTimer = null;
        const debouncedReflow = (delay = 300) => {
            clearTimeout(reflowTimer);
            reflowTimer = setTimeout(() => {
                if (paginationEngine) {
                    paginationEngine.reflow();
                }
            }, delay);
        };

        if (s1KWrap) {
            s1KWrap.addEventListener('pointerdown', () => clearTimeout(reflowTimer));
            s1KWrap.addEventListener('focusin', () => clearTimeout(reflowTimer));
        }

        const attachBoxEvents = (box) => {
            if (!box || box.dataset.eventsBound === 'true') return;
            box.dataset.eventsBound = 'true';

            const editorType = box.dataset.editor;
            const hiddenInput = editorType === 'kesimpulan' ? kesimpulanHidden : notulensiHidden;

            box.addEventListener('focus', () => setActiveEditor(box, hiddenInput));
            box.addEventListener('click', () => setActiveEditor(box, hiddenInput));
            box.addEventListener('keyup', updateActiveStates);
            box.addEventListener('mouseup', updateActiveStates);

            box.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    document.execCommand(e.shiftKey ? 'outdent' : 'indent', false, null);
                    syncContent(true);
                    updateActiveStates();
                    debouncedReflow(250);
                    return;
                }
                if (e.ctrlKey || e.metaKey) {
                    const key = e.key.toLowerCase();
                    const shortcutMap = {
                        b: 'bold',
                        i: 'italic',
                        u: 'underline',
                        l: 'justifyLeft',
                        e: 'justifyCenter',
                        r: 'justifyRight',
                        j: 'justifyFull',
                    };
                    if (shortcutMap[key]) {
                        e.preventDefault();
                        document.execCommand(shortcutMap[key], false, null);
                        syncContent(true);
                        updateActiveStates();
                        debouncedReflow(250);
                    } else if (key === 'z' || key === 'y') {
                        setTimeout(() => {
                            syncContent(true);
                            updateActiveStates();
                            debouncedReflow(250);
                        }, 10);
                    }
                }
            });

            box.addEventListener('input', () => {
                syncContent(true);
                debouncedReflow(250);
            });

            box.addEventListener('blur', () => {
                syncContent(false);
            });

            // Clean Paste Sanitizer (Strip MS Word & external inline style cruft)
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const clipboardData = e.clipboardData || window.clipboardData;
                const htmlData = clipboardData ? clipboardData.getData('text/html') : '';
                const textData = clipboardData ? clipboardData.getData('text/plain') : '';

                if (htmlData) {
                    try {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlData, 'text/html');

                        doc.querySelectorAll('script, style, meta, link, object, embed, iframe').forEach(el => el.remove());

                        const allowedTags = [
                            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
                            'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'hr',
                            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'sup', 'sub'
                        ];

                        Array.from(doc.body.querySelectorAll('*')).forEach(el => {
                            const tag = el.tagName.toLowerCase();
                            if (!allowedTags.includes(tag)) {
                                el.replaceWith(...el.childNodes);
                            } else {
                                Array.from(el.attributes).forEach(attr => {
                                    if (['colspan', 'rowspan'].includes(attr.name)) return;
                                    el.removeAttribute(attr.name);
                                });
                            }
                        });

                        const cleanHtml = doc.body.innerHTML.trim();
                        if (cleanHtml) {
                            document.execCommand('insertHTML', false, cleanHtml);
                        } else {
                            document.execCommand('insertText', false, textData);
                        }
                    } catch (err) {
                        document.execCommand('insertText', false, textData);
                    }
                } else if (textData) {
                    const paragraphs = textData
                        .split(/\r?\n\r?\n/)
                        .map(p => p.trim())
                        .filter(Boolean)
                        .map(p => `<p>${p.replace(/\r?\n/g, '<br>')}</p>`)
                        .join('');

                    if (paragraphs) {
                        document.execCommand('insertHTML', false, paragraphs);
                    } else {
                        document.execCommand('insertText', false, textData);
                    }
                }

                setTimeout(() => {
                    syncContent(true);
                    updateActiveStates();
                    paginationEngine.reflow();
                }, 30);
            });
        };

        const paginationEngine = {
            isReflowing: false,

            reflow() {
                if (this.isReflowing) return;
                this.isReflowing = true;

                try {
                    const sheet1 = workstation.querySelector('.office-paper-sheet[data-page="1"]');
                    if (!sheet1) return;

                    const kesimpulanIsFocused = !!(s1KWrap && s1KWrap.contains(document.activeElement));
                    const savedCaret = kesimpulanIsFocused ? saveCaretInfo() : null;

                    // 1. Reclaim modular sections before rebuilding continuation sheets
                    if (s1KWrap && s1Container && s1KWrap.parentNode !== s1Container) {
                        s1Container.appendChild(s1KWrap);
                    }
                    if (seksi2Wrapper && seksi2Wrapper.parentNode) {
                        seksi2Wrapper.parentNode.removeChild(seksi2Wrapper);
                    }
                    if (sigWrapper && sigWrapper.parentNode) {
                        sigWrapper.parentNode.removeChild(sigWrapper);
                    }
                    if (docAnnex && docAnnex.parentNode) {
                        docAnnex.parentNode.removeChild(docAnnex);
                    }

                    // 2. Clear continuation container
                    if (continuationContainer) {
                        continuationContainer.innerHTML = '';
                    }

                    // Sync visibility with Document Config checkboxes
                    const isConfigChecked = (name) => {
                        const modal = document.getElementById('modal-notulen-document-config');
                        if (!modal) return true;
                        const input = modal.querySelector(`input[type="checkbox"][name="${name}"]`);
                        return input ? input.checked : true;
                    };

                    const showNotulensi = isConfigChecked('show_notulensi');
                    const showKesimpulan = isConfigChecked('show_kesimpulan');

                    if (s1NWrap) {
                        s1NWrap.style.display = showNotulensi ? '' : 'none';
                    }
                    if (s1KWrap) {
                        s1KWrap.style.display = showKesimpulan ? '' : 'none';
                    }
                    if (s1Container) {
                        s1Container.style.display = (showNotulensi || showKesimpulan) ? '' : 'none';
                    }

                    const createContinuationSheet = (pageNum) => {
                        const sheet = document.createElement('div');
                        sheet.className = 'office-paper-sheet paper-a4';
                        sheet.dataset.page = String(pageNum);
                        sheet.innerHTML = `
                            <div class="doc-running-header">
                                <span class="truncate max-w-sm">Berita Acara Rapat: <strong>${agendaTitle}</strong></span>
                                <span class="doc-page-number font-bold shrink-0">Halaman ${pageNum}</span>
                            </div>
                            <div class="continuation-content-area" style="flex: 1 1 auto; display: flex; flex-direction: column; gap: 8pt;">
                            </div>
                            <div class="doc-running-footer">
                                <span>SIPERAPAT &bull; LLDIKTI Wilayah X</span>
                                <span class="doc-page-number font-bold">Halaman ${pageNum}</span>
                            </div>
                        `;
                        return sheet;
                    };

                    const createPageSeparator = (targetPageName) => {
                        const sep = document.createElement('div');
                        sep.className = 'office-page-separator';
                        sep.setAttribute('aria-hidden', 'true');
                        sep.innerHTML = `
                            <div class="office-page-gap">
                                <div class="gap-line"></div>
                                <div class="gap-indicator">
                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    <span class="gap-indicator-text">Pemisah Halaman &bull; Menuju ${targetPageName}</span>
                                </div>
                                <div class="gap-line"></div>
                            </div>
                        `;
                        return sep;
                    };

                    const appendToSheet = (sheet, el) => {
                        if (sheet === sheet1) {
                            sheet1.insertBefore(el, sheet1.querySelector('.doc-running-footer'));
                        } else {
                            const ca = sheet.querySelector('.continuation-content-area');
                            if (ca) {
                                ca.appendChild(el);
                            } else {
                                sheet.insertBefore(el, sheet.querySelector('.doc-running-footer'));
                            }
                        }
                    };

                    const sheetHasPriorContent = (sheet) => {
                        if (sheet === sheet1) return true;
                        const ca = sheet.querySelector('.continuation-content-area');
                        return ca ? ca.children.length > 1 : false;
                    };

                    const getLastSheet = () => {
                        if (!continuationContainer) return sheet1;
                        const contSheets = continuationContainer.querySelectorAll('.office-paper-sheet');
                        return contSheets.length > 0 ? contSheets[contSheets.length - 1] : sheet1;
                    };

                    let curPage = 2;

                    // 3. Flow Section I.B (#sheet-section-kesimpulan-wrapper) to Sheet 2 if Sheet 1 exceeds margin
                    if (s1KWrap && showKesimpulan && continuationContainer) {
                        if (showNotulensi && isExceedingMargin(sheet1, s1KWrap)) {
                            if (s1KWrap.parentNode) {
                                s1KWrap.parentNode.removeChild(s1KWrap);
                            }
                            const kSheet = createContinuationSheet(curPage);
                            continuationContainer.appendChild(kSheet);
                            appendToSheet(kSheet, s1KWrap);
                            if (kesimpulanBox) kesimpulanBox.dataset.page = String(curPage);
                            curPage++;
                        } else if (kesimpulanBox) {
                            kesimpulanBox.dataset.page = '1';
                        }
                    }

                    // 4. Flow Signature Block (#sheet-pengesahan-final) immediately after Notulensi & Kesimpulan
                    if (sigWrapper) {
                        const targetSheet = getLastSheet();
                        const standaloneHeader = sigWrapper.querySelector('#sheet-signature-standalone-header');
                        if (standaloneHeader) standaloneHeader.style.display = 'none';

                        appendToSheet(targetSheet, sigWrapper);

                        if (isExceedingMargin(targetSheet, sigWrapper) && sheetHasPriorContent(targetSheet) && continuationContainer) {
                            if (sigWrapper.parentNode) sigWrapper.parentNode.removeChild(sigWrapper);

                            if (continuationContainer.querySelectorAll('.office-paper-sheet').length > 0) {
                                continuationContainer.appendChild(createPageSeparator('Lembar Pengesahan'));
                            }

                            const sigSheet = createContinuationSheet(curPage);
                            continuationContainer.appendChild(sigSheet);
                            appendToSheet(sigSheet, sigWrapper);
                            if (standaloneHeader) standaloneHeader.style.display = 'block';
                            curPage++;
                        }
                    }

                    // 5. Flow Section II (#sheet-seksi-2-wrapper: Daftar Kehadiran Peserta) after Signature Block
                    if (seksi2Wrapper) {
                        const targetSheet = getLastSheet();
                        appendToSheet(targetSheet, seksi2Wrapper);

                        if (seksi2Wrapper.style.display !== 'none' && isExceedingMargin(targetSheet, seksi2Wrapper) && sheetHasPriorContent(targetSheet) && continuationContainer) {
                            if (seksi2Wrapper.parentNode) seksi2Wrapper.parentNode.removeChild(seksi2Wrapper);

                            if (continuationContainer.querySelectorAll('.office-paper-sheet').length > 0) {
                                continuationContainer.appendChild(createPageSeparator(`Halaman ${curPage}`));
                            }

                            const s2Sheet = createContinuationSheet(curPage);
                            continuationContainer.appendChild(s2Sheet);
                            appendToSheet(s2Sheet, seksi2Wrapper);
                            curPage++;
                        }
                    }

                    // 6. Flow Documentation Annex (#sheet-documentation-annex)
                    if (docAnnex) {
                        const targetSheet = getLastSheet();
                        appendToSheet(targetSheet, docAnnex);

                        if (docAnnex.style.display !== 'none' && isExceedingMargin(targetSheet, docAnnex) && sheetHasPriorContent(targetSheet) && continuationContainer) {
                            if (docAnnex.parentNode) docAnnex.parentNode.removeChild(docAnnex);

                            if (continuationContainer.querySelectorAll('.office-paper-sheet').length > 0) {
                                continuationContainer.appendChild(createPageSeparator('Lampiran Dokumentasi'));
                            }

                            const annexSheet = createContinuationSheet(curPage);
                            continuationContainer.appendChild(annexSheet);
                            appendToSheet(annexSheet, docAnnex);
                            curPage++;
                        }
                    }

                    // 7. Update all sheets page numbers and running headers/footers
                    const allSheets = workstation.querySelectorAll('.office-paper-sheet');
                    const totPages = allSheets.length;

                    allSheets.forEach((sh, idx) => {
                        const pNum = idx + 1;
                        sh.dataset.page = String(pNum);
                        const rh = sh.querySelector('.doc-running-header .doc-page-number');
                        if (rh) rh.textContent = `Halaman ${pNum} dari ${totPages}`;
                        const rf = sh.querySelector('.doc-running-footer .doc-page-number');
                        if (rf) rf.textContent = `Halaman ${pNum} dari ${totPages}`;
                    });

                    // 8. Update Separator 1 (between Sheet 1 and dynamic sheets)
                    if (separator1) {
                        if (totPages <= 1) {
                            separator1.style.display = 'none';
                        } else {
                            separator1.style.display = 'block';
                            const indText = separator1.querySelector('.gap-indicator-text');
                            if (indText) {
                                indText.textContent = 'Pemisah Halaman • Menuju Halaman 2';
                            }
                        }
                    }

                    // Re-bind events to all editable boxes
                    workstation.querySelectorAll('.office-editable-box').forEach(b => {
                        attachBoxEvents(b);
                    });

                    // Restore caret if Kesimpulan was focused during reflow
                    if (savedCaret) {
                        restoreCaretInfo(savedCaret);
                    }

                    // Sync content (without marking dirty on initial reflow)
                    syncContent(false);

                    // Update zoom compensation & status bar
                    updateZoomCompensation();
                    if (statusPageEl) {
                        handleScroll();
                    }
                } finally {
                    this.isReflowing = false;
                }
            }
        };

        window.addEventListener('office:reflow', () => {
            if (paginationEngine) paginationEngine.reflow();
        });

        // Initialize active editor to notulensi by default
        setActiveEditor(notulensiBox, notulensiHidden);
        workstation.querySelectorAll('.office-editable-box').forEach(b => {
            attachBoxEvents(b);
        });

        // Enforce fixed A4 paper format (210 × 297 mm)
        setPaperSize(false);

        // Initial reflow
        paginationEngine.reflow();

        // 3. Ribbon Toolbar Commands for Active Box
        if (ribbon) {
            const formatSelect = ribbon.querySelector('select[data-command="formatBlock"]');
            const fontSizeSelect = ribbon.querySelector('select[data-action="fontSize"]');
            const tablePicker = ribbon.querySelector('.word-table-picker');

            const saveSelection = () => {
                const sel = window.getSelection();
                if (sel.rangeCount > 0) savedRange = sel.getRangeAt(0);
            };

            const restoreSelection = () => {
                if (activeBox) activeBox.focus();
                if (savedRange) {
                    const sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(savedRange);
                }
            };

            // Formatting Buttons
            ribbon.querySelectorAll('button[data-command]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    restoreSelection();
                    const cmd = btn.dataset.command;
                    if (cmd === 'blockquote') {
                        document.execCommand('formatBlock', false, 'blockquote');
                    } else if (cmd === 'insertHorizontalRule') {
                        document.execCommand('insertHorizontalRule', false, null);
                    } else {
                        document.execCommand(cmd, false, null);
                    }
                    syncContent(true);
                    updateActiveStates();
                    debouncedReflow(200);
                });
            });

            // Paragraph Block Select
            if (formatSelect) {
                formatSelect.addEventListener('change', (e) => {
                    restoreSelection();
                    const val = e.target.value;
                    if (val) {
                        document.execCommand('formatBlock', false, val);
                        syncContent(true);
                        updateActiveStates();
                        debouncedReflow(200);
                    }
                });
            }

            // Font Size Select
            if (fontSizeSelect) {
                fontSizeSelect.addEventListener('change', (e) => {
                    restoreSelection();
                    const val = e.target.value;
                    if (val) {
                        document.execCommand('fontSize', false, val);
                        syncContent(true);
                        debouncedReflow(200);
                    }
                });
            }

            // Text Color & Highlight Pickers
            ribbon.querySelectorAll('.word-color-btn').forEach(colorBtn => {
                const action = colorBtn.dataset.action;
                const colorInput = ribbon.querySelector(`input[data-color-for="${action}"]`);
                const indicator = colorBtn.querySelector('.color-indicator');

                if (colorInput) {
                    colorBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        saveSelection();
                        colorInput.click();
                    });

                    colorInput.addEventListener('input', (e) => {
                        const selectedColor = e.target.value;
                        if (indicator) indicator.style.background = selectedColor;
                        restoreSelection();

                        if (action === 'textColor') {
                            document.execCommand('foreColor', false, selectedColor);
                        } else if (action === 'highlight') {
                            try {
                                if (!document.execCommand('hiliteColor', false, selectedColor)) {
                                    document.execCommand('backColor', false, selectedColor);
                                }
                            } catch (err) {
                                document.execCommand('backColor', false, selectedColor);
                            }
                        }
                        syncContent(true);
                    });
                }
            });

            // Table Grid Picker
            const tableBtn = ribbon.querySelector('button[data-action="insertTable"]');
            if (tableBtn && tablePicker) {
                const maxRows = 6;
                const maxCols = 6;
                let gridHtml = '<div class="word-table-grid">';
                for (let r = 1; r <= maxRows; r++) {
                    for (let c = 1; c <= maxCols; c++) {
                        gridHtml += `<div class="word-table-cell" data-row="${r}" data-col="${c}"></div>`;
                    }
                }
                gridHtml += '</div><div class="word-table-picker-label">Pilih Ukuran (0 × 0)</div>';
                tablePicker.innerHTML = gridHtml;

                const cells = tablePicker.querySelectorAll('.word-table-cell');
                const labelEl = tablePicker.querySelector('.word-table-picker-label');

                const updateGridSelection = (targetRow, targetCol) => {
                    cells.forEach(cell => {
                        const r = parseInt(cell.dataset.row, 10);
                        const c = parseInt(cell.dataset.col, 10);
                        if (r <= targetRow && c <= targetCol) {
                            cell.classList.add('selected');
                        } else {
                            cell.classList.remove('selected');
                        }
                    });
                    if (labelEl) {
                        labelEl.textContent = targetRow > 0 && targetCol > 0 
                            ? `${targetRow} × ${targetCol} Tabel` 
                            : 'Pilih Ukuran (0 × 0)';
                    }
                };

                cells.forEach(cell => {
                    cell.addEventListener('mouseenter', () => {
                        const r = parseInt(cell.dataset.row, 10);
                        const c = parseInt(cell.dataset.col, 10);
                        updateGridSelection(r, c);
                    });

                    cell.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        const r = parseInt(cell.dataset.row, 10);
                        const c = parseInt(cell.dataset.col, 10);

                        let tableHtml = '<table class="w-full my-2 border-collapse border border-black" border="1"><thead><tr>';
                        for (let colIdx = 1; colIdx <= c; colIdx++) {
                            tableHtml += `<th class="border border-black p-1 bg-slate-100 text-xs">Kolom ${colIdx}</th>`;
                        }
                        tableHtml += '</tr></thead><tbody>';
                        for (let rowIdx = 1; rowIdx < r; rowIdx++) {
                            tableHtml += '<tr>';
                            for (let colIdx = 1; colIdx <= c; colIdx++) {
                                tableHtml += '<td class="border border-black p-1 text-xs">&nbsp;</td>';
                            }
                            tableHtml += '</tr>';
                        }
                        tableHtml += '</tbody></table><p><br></p>';

                        tablePicker.classList.remove('open');
                        restoreSelection();
                        document.execCommand('insertHTML', false, tableHtml);
                        syncContent(true);
                        paginationEngine.reflow();
                    });
                });

                tablePicker.addEventListener('mouseleave', () => updateGridSelection(0, 0));

                tableBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    saveSelection();
                    tablePicker.classList.toggle('open');
                    updateGridSelection(0, 0);
                });

                document.addEventListener('click', (e) => {
                    if (!tablePicker.contains(e.target) && e.target !== tableBtn) {
                        tablePicker.classList.remove('open');
                    }
                });
            }

            // Clear All Button (Consistent System Modal + Reflow)
            const clearAllBtn = ribbon.querySelector('button[data-action="clearAll"]');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!activeBox || !activeBox.innerText.trim()) return;
                    const sectionLabel = activeBox.dataset.editor === 'kesimpulan'
                        ? 'Kesimpulan & Rencana Tindak Lanjut (RTL)'
                        : 'Catatan Jalannya Rapat (Notulensi)';

                    const executeClear = () => {
                        activeBox.innerHTML = '';
                        syncContent(true);
                        updateActiveStates();
                        paginationEngine.reflow();
                        activeBox.focus();
                    };

                    if (typeof window.showModal === 'function') {
                        window.showModal({
                            title: 'Kosongkan Isi Bagian Ini?',
                            message: `Seluruh teks pada bagian "${sectionLabel}" akan dihapus dari editor. Anda masih dapat membatalkannya sebelum menyimpan ke server.`,
                            type: 'danger',
                            confirmText: 'Ya, Kosongkan',
                            cancelText: 'Batal',
                            onConfirm: executeClear,
                        });
                    } else if (confirm('Kosongkan teks pada bagian yang sedang aktif?')) {
                        executeClear();
                    }
                });
            }
        }

        // 4. Keyboard Shortcuts: Ctrl+S / Cmd+S to Save
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                const saveForm = workstation.querySelector('#notulen-form') || workstation.querySelector('form');
                if (saveForm) {
                    e.preventDefault();
                    isSubmittingForm = true;
                    updateSaveStateUI(false);
                    syncContent(false);
                    saveForm.requestSubmit ? saveForm.requestSubmit() : saveForm.submit();
                }
            }
        });

        // 5. Beforeunload Guard for Unsaved Changes
        window.addEventListener('beforeunload', (e) => {
            if (window.__officeDirtyState && !isSubmittingForm) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Form Submit: Ensure latest sync before submission
        const form = workstation.querySelector('#notulen-form') || workstation.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => {
                isSubmittingForm = true;
                updateSaveStateUI(false);
                syncContent(false);
            });
        }
        workstation.querySelectorAll('button[form="notulen-form"]').forEach(btn => {
            btn.addEventListener('click', () => {
                isSubmittingForm = true;
                updateSaveStateUI(false);
                syncContent(false);
            });
        });
    }
};

window.OfficeWorkstation = OfficeWorkstation;

// --- 3. SEAMLESS SPA-FEEL NAVIGATION ENGINE ---
const SeamlessNavigation = {
    abortController: null,
    isNavigating: false,

    init() {
        ProgressBar.init();
        initGlobalListeners();

        // Delegate all link clicks
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link) return;

            // Check if link should be handled seamlessly
            if (this.shouldIntercept(link, e)) {
                e.preventDefault();
                this.navigate(link.href, true);
            }
        });

        // Browser Back / Forward History Navigation
        window.addEventListener('popstate', () => {
            this.navigate(window.location.href, false);
        });
    },

    shouldIntercept(link, e) {
        // Allow user modifiers for opening in new tab/window
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return false;

        const href = link.getAttribute('href');
        if (!href) return false;

        // Skip non-HTTP links, hash links, external links, downloads, and new tabs
        if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
        if (link.target && link.target !== '_self') return false;
        if (link.hasAttribute('download')) return false;
        if (link.dataset.noSeamless === 'true' || link.dataset.seamless === 'false') return false;

        // Verify same origin
        try {
            const targetUrl = new URL(link.href, window.location.origin);
            if (targetUrl.origin !== window.location.origin) return false;

            // Exclude binary export routes & storage files from AJAX interception
            const path = targetUrl.pathname.toLowerCase();
            if (path.includes('/export') || path.includes('/csv') || path.endsWith('.doc') || path.endsWith('.pdf') || path.startsWith('/storage/')) return false;

            return true;
        } catch {
            return false;
        }
    },

    async navigate(url, pushState = true) {
        if (this.isNavigating && this.abortController) {
            this.abortController.abort();
        }

        // Release any active camera streams before navigating away
        if (window.__siperapatCameraStream) {
            try {
                window.__siperapatCameraStream.getTracks().forEach(track => track.stop());
            } catch (e) {
                console.warn('Error releasing camera stream:', e);
            }
            window.__siperapatCameraStream = null;
        }

        this.isNavigating = true;
        this.abortController = new AbortController();
        const startTime = Date.now();
        ProgressBar.start();

        try {
            const response = await fetch(url, {
                signal: this.abortController.signal,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-Seamless-Navigation': '1',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                }
            });

            // Ensure natural visual cadence (~280ms) for smooth feedback
            const elapsed = Date.now() - startTime;
            const minDelay = 280;
            if (elapsed < minDelay) {
                await new Promise(resolve => setTimeout(resolve, minDelay - elapsed));
            }

            // If response redirected to an external domain or login page, perform standard redirect
            if (response.redirected && response.url) {
                const redirectedUrl = new URL(response.url);
                if (redirectedUrl.pathname.includes('/login')) {
                    window.location.href = response.url;
                    return;
                }
            }

            if (!response.ok) {
                // If 401 Unauthorized or 419 CSRF Expired, redirect to login
                if (response.status === 401 || response.status === 419) {
                    window.location.href = '/login';
                    return;
                }
                // Fallback to full page load for server errors
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const parser = new DOMParser();
            const newDoc = parser.parseFromString(html, 'text/html');

            const newMain = newDoc.querySelector('#main-content');
            if (!newMain) {
                // Not an app layout page (e.g., login or full standalone page)
                window.location.href = url;
                return;
            }

            // Perform View Transition if supported by browser
            if (document.startViewTransition) {
                await document.startViewTransition(() => {
                    this.applyDomSwap(newDoc, url, pushState);
                }).ready;
            } else {
                this.applyDomSwap(newDoc, url, pushState);
            }

            ProgressBar.finish();
            this.isNavigating = false;
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.warn('Seamless navigation fallback:', err);
            ProgressBar.reset();
            this.isNavigating = false;
            window.location.href = url;
        }
    },

    applyDomSwap(newDoc, url, pushState) {
        // 1. Update Document Title
        document.title = newDoc.title;

        // 2. Swap Topbar Heading & Subtitle
        const currentHeading = document.querySelector('#topbar-heading-container');
        const newHeading = newDoc.querySelector('#topbar-heading-container');
        if (currentHeading && newHeading) {
            currentHeading.innerHTML = newHeading.innerHTML;
        }

        // 3. Swap Main Content
        const currentMain = document.querySelector('#main-content');
        const newMain = newDoc.querySelector('#main-content');
        if (currentMain && newMain) {
            currentMain.innerHTML = newMain.innerHTML;
            currentMain.className = newMain.className;
        }

        // 4. Update Sidebar Active Links
        const currentSidebar = document.querySelector('#app-sidebar');
        const newSidebar = newDoc.querySelector('#app-sidebar');
        if (currentSidebar && newSidebar) {
            const currentLinks = currentSidebar.querySelectorAll('.nav-link');
            const newLinks = newSidebar.querySelectorAll('.nav-link');
            newLinks.forEach((newLink, index) => {
                if (currentLinks[index]) {
                    currentLinks[index].className = newLink.className;
                    if (newLink.hasAttribute('aria-current')) {
                        currentLinks[index].setAttribute('aria-current', 'page');
                    } else {
                        currentLinks[index].removeAttribute('aria-current');
                    }
                }
            });
        }

        // 5. Sync CSRF Token
        const newCsrf = newDoc.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (newCsrf) {
            const currentCsrf = document.querySelector('meta[name="csrf-token"]');
            if (currentCsrf) currentCsrf.setAttribute('content', newCsrf);
            document.querySelectorAll('input[name="_token"]').forEach(input => input.value = newCsrf);
        }

        // 6. Update URL History
        if (pushState) {
            window.history.pushState({ url }, '', url);
        }

        // 7. Scroll to Top
        window.scrollTo({ top: 0, behavior: 'instant' });

        // 8. Re-initialize Global Listeners & Modals
        initGlobalListeners();

        // 9. Re-execute Page-specific Scripts inside active DOM
        if (currentMain) {
            currentMain.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }

        // 10. Fire custom lifecycle event for dynamic views (WebRTC Camera, Signature Pad, etc.)
        window.dispatchEvent(new CustomEvent('page:loaded', { detail: { url } }));

        // 11. Process Flash Messages from newDoc
        const newFlashData = newDoc.querySelector('#flash-modal-data');
        if (newFlashData) {
            const type = newFlashData.dataset.type || 'info';
            const title = newFlashData.dataset.title || 'Pemberitahuan';
            const message = newFlashData.dataset.message || '';
            const autoClose = newFlashData.dataset.autoClose !== 'false';
            const autoCloseDelay = newFlashData.dataset.delay ? parseInt(newFlashData.dataset.delay, 10) : null;

            let errors = null;
            if (newFlashData.dataset.errors) {
                try {
                    const parsed = JSON.parse(newFlashData.dataset.errors);
                    if (Array.isArray(parsed)) errors = parsed;
                } catch (err) {
                    console.warn('[Flash] Unable to parse validation errors payload.', err);
                }
            }

            if ((message || (Array.isArray(errors) && errors.length)) && window.showModal) {
                window.showModal({
                    title: title,
                    message: message,
                    errors: errors,
                    type: type,
                    confirmText: 'Mengerti & Tutup',
                    autoClose: autoClose,
                    autoCloseDelay: autoCloseDelay
                });
            }
        }

        // 12. Close mobile drawer if open
        const sidebarEl = document.querySelector('#app-sidebar');
        const overlayEl = document.querySelector('#sidebar-overlay');
        if (sidebarEl && sidebarEl.classList.contains('open')) {
            sidebarEl.classList.remove('open');
            if (overlayEl) overlayEl.classList.remove('open');
            document.body.style.overflow = '';
        }
    }
};

// --- 2.8. SURAT EDARAN & UNDANGAN RESMI WYSIWYG WORKSTATION CONTROLLER ---
const SuratEdaranWorkstation = {
    init() {
        const workstation = document.querySelector('#surat-edaran-workstation');
        if (!workstation || workstation.dataset.initialized === 'true') return;
        workstation.dataset.initialized = 'true';

        const ribbon = workstation.querySelector('.word-editor-ribbon');
        const zoomContainer = workstation.querySelector('#office-zoom-container');
        const deskCanvas = workstation.querySelector('.office-desk-canvas');
        const rulerEl = workstation.querySelector('#office-document-ruler');
        const statusBarEl = workstation.querySelector('#office-status-bar');
        const statusSizeEl = workstation.querySelector('#office-status-size');
        const statusPageEl = workstation.querySelector('#office-status-page');
        const statusZoomEl = workstation.querySelector('#office-status-zoom');
        const sheet = workstation.querySelector('#surat-sheet-page-1');
        const form = workstation.querySelector('#surat-edaran-form');

        const editableBoxes = workstation.querySelectorAll('.office-editable-box');
        const pembukaBox = workstation.querySelector('#surat-pembuka-content');
        const bodyBox = workstation.querySelector('#surat-body-content');
        const catatanBox = workstation.querySelector('#surat-catatan-content');
        const penutupBox = workstation.querySelector('#surat-penutup-content');
        const altEdaranBox = workstation.querySelector('#surat-edaran-alt-content');
        const lampiranBox = workstation.querySelector('#surat-lampiran-content');

        const formPembuka = workstation.querySelector('#form-pembuka');
        const formContent = workstation.querySelector('#form-surat-content');
        const formCatatan = workstation.querySelector('#form-catatan');
        const formPenutup = workstation.querySelector('#form-penutup');
        const formLampiranContent = workstation.querySelector('#form-lampiran-content');

        const statusWordsEl = workstation.querySelector('#office-status-words');
        const statusSaveStateEl = workstation.querySelector('#office-status-save-state');
        const statusSaveDotEl = workstation.querySelector('#office-status-save-dot');
        const statusSaveLabelEl = workstation.querySelector('#office-status-save-label');

        const initialTemplate = document.querySelector('#form-template-type')?.value || 'undangan';
        let activeBox = initialTemplate === 'edaran'
            ? (altEdaranBox || bodyBox || pembukaBox || editableBoxes[0])
            : (bodyBox || pembukaBox || editableBoxes[0]);
        let savedRange = null;
        let isSubmittingForm = false;
        let currentZoomVal = 100;
        let updateActiveStates = () => {};
        let syncModalConfigToFormAndSheet = null;
        window.__suratDirtyState = false;

        const updateSaveStateUI = (dirty) => {
            window.__suratDirtyState = Boolean(dirty);
            if (!statusSaveStateEl || !statusSaveDotEl || !statusSaveLabelEl) return;
            if (window.__suratDirtyState) {
                statusSaveStateEl.className = 'inline-flex items-center gap-1.5 text-amber-400 font-semibold';
                statusSaveDotEl.className = 'w-1.5 h-1.5 rounded-full bg-amber-400';
                statusSaveLabelEl.textContent = 'Perubahan Belum Disimpan';
            } else {
                statusSaveStateEl.className = 'inline-flex items-center gap-1.5 text-emerald-400 font-semibold';
                statusSaveDotEl.className = 'w-1.5 h-1.5 rounded-full bg-emerald-400';
                statusSaveLabelEl.textContent = 'Tersimpan';
            }
        };

        window.__suratEdaranMarkDirty = () => {
            updateSaveStateUI(true);
        };

        window.__onSuratEdaranPreSubmit = () => {
            isSubmittingForm = true;
            window.__suratDirtyState = false;
            syncToHiddenInputs(true);
        };

        window.addEventListener('beforeunload', (e) => {
            if (window.__suratDirtyState && !isSubmittingForm) {
                e.preventDefault();
                e.returnValue = 'Perubahan naskah surat belum disimpan.';
                return e.returnValue;
            }
        });

        const updateWordCounter = () => {
            if (!statusWordsEl) return;
            let fullText = '';
            editableBoxes.forEach(box => {
                fullText += ' ' + (box.innerText || '');
            });
            const words = fullText.trim().split(/\s+/).filter(Boolean);
            statusWordsEl.textContent = `${words.length} kata`;
        };
        updateWordCounter();

        const syncToHiddenInputs = (includeModalConfig = false) => {
            if (includeModalConfig && typeof syncModalConfigToFormAndSheet === 'function') {
                syncModalConfigToFormAndSheet();
            }
            const templateType = document.querySelector('#form-template-type')?.value || 'undangan';

            if (formPembuka && pembukaBox) {
                formPembuka.value = pembukaBox.innerHTML.trim();
            }
            if (formCatatan && catatanBox) {
                formCatatan.value = catatanBox.innerHTML.trim();
            }
            if (formPenutup && penutupBox) {
                formPenutup.value = penutupBox.innerHTML.trim();
            }
            if (formLampiranContent && lampiranBox) {
                formLampiranContent.value = lampiranBox.innerHTML.trim();
            }

            if (formContent) {
                if (templateType === 'edaran' && altEdaranBox) {
                    formContent.value = altEdaranBox.innerHTML.trim();
                } else if (bodyBox) {
                    formContent.value = bodyBox.innerHTML.trim();
                }
            }
        };

        const isNodeInsideEditableBox = (node) => {
            if (!node) return null;
            const el = node.nodeType === Node.ELEMENT_NODE ? node : node.parentElement;
            if (!el) return null;
            const box = el.closest('.office-editable-box');
            return (box && workstation.contains(box)) ? box : null;
        };

        const saveSelection = () => {
            const sel = window.getSelection();
            if (sel && sel.rangeCount > 0) {
                const range = sel.getRangeAt(0);
                const ownerBox = isNodeInsideEditableBox(range.commonAncestorContainer);
                if (ownerBox) {
                    activeBox = ownerBox;
                    savedRange = range.cloneRange();
                }
            }
        };

        const restoreSelection = () => {
            const targetBox = activeBox;
            if (targetBox && document.activeElement !== targetBox) {
                targetBox.focus({ preventScroll: true });
            }
            if (!savedRange) return;
            const ownerBox = isNodeInsideEditableBox(savedRange.commonAncestorContainer);
            if (!ownerBox) return;
            const sel = window.getSelection();
            if (sel) {
                try {
                    sel.removeAllRanges();
                    sel.addRange(savedRange);
                } catch (e) {}
            }
        };

        const setActiveEditor = (box) => {
            if (!box) return;
            activeBox = box;
            editableBoxes.forEach(b => {
                b.classList.remove('is-active-editor');
            });
            box.classList.add('is-active-editor');
            if (typeof updateActiveStates === 'function') {
                updateActiveStates();
            }
        };

        editableBoxes.forEach(box => {
            box.addEventListener('focus', () => {
                setActiveEditor(box);
            });
            box.addEventListener('click', () => {
                setActiveEditor(box);
                saveSelection();
            });
            box.addEventListener('keyup', () => {
                saveSelection();
                if (typeof updateActiveStates === 'function') {
                    updateActiveStates();
                }
            });
            box.addEventListener('mouseup', () => {
                saveSelection();
                if (typeof updateActiveStates === 'function') {
                    updateActiveStates();
                }
            });
            box.addEventListener('input', () => {
                saveSelection();
                syncToHiddenInputs();
                updateWordCounter();
                updateSaveStateUI(true);
            });
            box.addEventListener('blur', () => {
                syncToHiddenInputs();
            });

            // Local formatting & alignment keyboard shortcuts
            box.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    e.preventDefault();
                    document.execCommand(e.shiftKey ? 'outdent' : 'indent', false, null);
                    saveSelection();
                    syncToHiddenInputs();
                    updateWordCounter();
                    updateSaveStateUI(true);
                    if (typeof updateActiveStates === 'function') {
                        updateActiveStates();
                    }
                    return;
                }

                if (e.ctrlKey || e.metaKey) {
                    const key = e.key.toLowerCase();
                    const shortcutMap = {
                        b: 'bold',
                        i: 'italic',
                        u: 'underline',
                        l: 'justifyLeft',
                        e: 'justifyCenter',
                        r: 'justifyRight',
                        j: 'justifyFull',
                    };

                    if (shortcutMap[key]) {
                        e.preventDefault();
                        document.execCommand(shortcutMap[key], false, null);
                        saveSelection();
                        syncToHiddenInputs();
                        updateWordCounter();
                        updateSaveStateUI(true);
                        if (typeof updateActiveStates === 'function') {
                            updateActiveStates();
                        }
                    } else if (key === 'z' || key === 'y') {
                        setTimeout(() => {
                            saveSelection();
                            syncToHiddenInputs();
                            updateWordCounter();
                            updateSaveStateUI(true);
                            if (typeof updateActiveStates === 'function') {
                                updateActiveStates();
                            }
                        }, 10);
                    }
                }
            });

            // Clean Smart HTML Paste Sanitizer (Consistent with Notulensi OfficeWorkstation)
            box.addEventListener('paste', (e) => {
                e.preventDefault();
                const clipboardData = e.clipboardData || window.clipboardData;
                const htmlData = clipboardData ? clipboardData.getData('text/html') : '';
                const textData = clipboardData ? clipboardData.getData('text/plain') : '';

                if (htmlData) {
                    try {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlData, 'text/html');

                        doc.querySelectorAll('script, style, meta, link, object, embed, iframe').forEach(el => el.remove());

                        const allowedTags = [
                            'p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
                            'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'hr',
                            'table', 'thead', 'tbody', 'tr', 'th', 'td', 'sup', 'sub'
                        ];

                        Array.from(doc.body.querySelectorAll('*')).forEach(el => {
                            const tag = el.tagName.toLowerCase();
                            if (!allowedTags.includes(tag)) {
                                el.replaceWith(...el.childNodes);
                            } else {
                                Array.from(el.attributes).forEach(attr => {
                                    if (['colspan', 'rowspan'].includes(attr.name)) return;
                                    el.removeAttribute(attr.name);
                                });
                            }
                        });

                        const cleanHtml = doc.body.innerHTML.trim();
                        if (cleanHtml) {
                            document.execCommand('insertHTML', false, cleanHtml);
                        } else if (textData) {
                            document.execCommand('insertText', false, textData);
                        }
                    } catch (err) {
                        if (textData) document.execCommand('insertText', false, textData);
                    }
                } else if (textData) {
                    const paragraphs = textData
                        .split(/\r?\n\r?\n/)
                        .map(p => p.trim())
                        .filter(Boolean)
                        .map(p => `<p>${escapeHtml(p).replace(/\r?\n/g, '<br>')}</p>`)
                        .join('');

                    if (paragraphs && textData.includes('\n\n')) {
                        document.execCommand('insertHTML', false, paragraphs);
                    } else {
                        document.execCommand('insertText', false, textData);
                    }
                }

                setTimeout(() => {
                    saveSelection();
                    syncToHiddenInputs();
                    updateWordCounter();
                    updateSaveStateUI(true);
                    if (typeof updateActiveStates === 'function') {
                        updateActiveStates();
                    }
                }, 20);
            });
        });

        // Initialize active editor class without forcing focus or selection highlight
        setActiveEditor(activeBox);

        // Ribbon Commands
        if (ribbon) {
            const fontNameSelect = ribbon.querySelector('select[data-action="fontName"]');
            const formatSelect = ribbon.querySelector('select[data-command="formatBlock"]');
            const fontSizeSelect = ribbon.querySelector('select[data-action="fontSize"]');

            const ensureVisibleActiveBox = () => {
                const templateType = document.querySelector('#form-template-type')?.value || 'undangan';
                const showLampiran = document.querySelector('#form-show-lampiran')?.value === '1';

                if (activeBox === lampiranBox && showLampiran) {
                    return lampiranBox;
                }
                if (templateType === 'edaran' && altEdaranBox) {
                    activeBox = altEdaranBox;
                } else if (!activeBox || activeBox === altEdaranBox || (activeBox === lampiranBox && !showLampiran)) {
                    activeBox = bodyBox || pembukaBox || editableBoxes[0];
                }
                return activeBox;
            };

            const exec = (cmd, val = null) => {
                const targetBox = ensureVisibleActiveBox();
                restoreSelection();

                if (targetBox && document.activeElement !== targetBox) {
                    targetBox.focus({ preventScroll: true });
                    restoreSelection();
                }

                let autoSelectedAll = false;
                if ((cmd === 'fontSize' || cmd === 'fontName') && targetBox) {
                    const sel = window.getSelection();
                    if (!sel || sel.rangeCount === 0 || sel.isCollapsed || !targetBox.contains(sel.anchorNode)) {
                        const fullRange = document.createRange();
                        fullRange.selectNodeContents(targetBox);
                        if (sel) {
                            sel.removeAllRanges();
                            sel.addRange(fullRange);
                            autoSelectedAll = true;
                        }
                    }
                }

                if (cmd === 'blockquote') {
                    document.execCommand('formatBlock', false, 'blockquote');
                } else {
                    document.execCommand(cmd, false, val);
                }

                if (autoSelectedAll) {
                    const sel = window.getSelection();
                    if (sel && sel.rangeCount > 0) {
                        sel.collapseToEnd();
                    }
                }

                saveSelection();
                syncToHiddenInputs();
                updateWordCounter();
                updateSaveStateUI(true);
                updateActiveStates();
            };

            updateActiveStates = () => {
                const commands = [
                    'bold', 'italic', 'underline', 'strikeThrough',
                    'justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull',
                    'insertUnorderedList', 'insertOrderedList',
                    'superscript', 'subscript'
                ];
                commands.forEach(cmd => {
                    const btn = ribbon.querySelector(`.word-btn[data-command="${cmd}"]`);
                    if (btn) {
                        try {
                            if (document.queryCommandState(cmd)) {
                                btn.classList.add('is-active');
                            } else {
                                btn.classList.remove('is-active');
                            }
                        } catch (err) {
                            btn.classList.remove('is-active');
                        }
                    }
                });

                if (formatSelect) {
                    try {
                        const block = (document.queryCommandValue('formatBlock') || '').toLowerCase().replace(/[<>]/g, '');
                        const quoteBtn = ribbon.querySelector('.word-btn[data-command="blockquote"]');
                        if (['h2', 'h3', 'h4', 'p', 'blockquote'].includes(block)) {
                            formatSelect.value = block;
                        } else {
                            formatSelect.value = 'p';
                        }
                        if (quoteBtn) {
                            if (block === 'blockquote') {
                                quoteBtn.classList.add('is-active');
                            } else {
                                quoteBtn.classList.remove('is-active');
                            }
                        }
                    } catch (e) {}
                }

                if (fontSizeSelect) {
                    try {
                        const sizeVal = String(document.queryCommandValue('fontSize') || '').trim();
                        if (['1', '2', '3', '4', '5', '6'].includes(sizeVal)) {
                            fontSizeSelect.value = sizeVal;
                        } else {
                            fontSizeSelect.value = '3';
                        }
                    } catch (e) {}
                }

                if (fontNameSelect) {
                    try {
                        const rawFont = String(document.queryCommandValue('fontName') || '').replace(/['"]/g, '').toLowerCase();
                        if (rawFont.includes('times')) {
                            fontNameSelect.value = 'Times New Roman';
                        } else if (rawFont.includes('georgia')) {
                            fontNameSelect.value = 'Georgia';
                        } else if (rawFont.includes('courier')) {
                            fontNameSelect.value = 'Courier New';
                        } else if (rawFont.includes('arial') || rawFont.includes('helvetica')) {
                            fontNameSelect.value = 'Arial';
                        }
                    } catch (e) {}
                }
            };

            // Prevent focus loss and collapse of selection on ribbon button click
            ribbon.querySelectorAll('.word-btn').forEach(btn => {
                btn.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    saveSelection();
                });
            });

            const editorSectionNames = {
                pembuka: 'Alinea Pembuka Surat Undangan',
                body: 'Rincian Batang Tubuh Surat Undangan',
                catatan: 'Catatan Tambahan',
                penutup: 'Alinea Penutup Surat Undangan',
                'edaran-body': 'Batang Tubuh Surat Edaran',
                lampiran: 'Lampiran Daftar Undangan (Halaman 2)',
            };

            ribbon.addEventListener('click', (e) => {
                const btn = e.target.closest('.word-btn');
                if (!btn) return;
                e.preventDefault();

                if (btn.dataset.command) {
                    exec(btn.dataset.command);
                } else if (btn.dataset.action === 'textColor' || btn.dataset.action === 'highlight') {
                    saveSelection();
                    const input = ribbon.querySelector(`input[data-color-for="${btn.dataset.action}"]`);
                    if (input) input.click();
                } else if (btn.dataset.action === 'openTablePicker' || btn.dataset.action === 'insertTable') {
                    saveSelection();
                    const picker = workstation.querySelector('#surat-table-picker');
                    if (picker) picker.classList.toggle('open');
                } else if (btn.dataset.action === 'clearAll') {
                    const targetBox = ensureVisibleActiveBox();
                    if (!targetBox || !targetBox.innerText.trim()) return;
                    const sectionLabel = editorSectionNames[targetBox.dataset.editor] || 'Bagian Naskah Aktif';

                    const executeClear = () => {
                        targetBox.innerHTML = '';
                        syncToHiddenInputs();
                        updateWordCounter();
                        updateSaveStateUI(true);
                        updateActiveStates();
                        targetBox.focus({ preventScroll: true });
                    };

                    if (typeof window.showModal === 'function') {
                        window.showModal({
                            title: 'Kosongkan Isi Bagian Ini?',
                            message: `Seluruh teks pada bagian "${sectionLabel}" akan dihapus dari editor. Anda masih dapat membatalkannya sebelum menyimpan ke server.`,
                            type: 'danger',
                            confirmText: 'Ya, Kosongkan',
                            cancelText: 'Batal',
                            onConfirm: executeClear,
                        });
                    } else if (confirm(`Kosongkan teks pada bagian "${sectionLabel}"?`)) {
                        executeClear();
                    }
                }
            });

            // Color inputs
            ribbon.querySelectorAll('.word-color-input').forEach(input => {
                input.addEventListener('input', (e) => {
                    const action = input.dataset.colorFor;
                    const val = e.target.value;
                    const indicator = ribbon.querySelector(`.color-indicator[data-indicator="${action}"]`);
                    if (indicator) indicator.style.background = val;

                    if (action === 'textColor') {
                        exec('foreColor', val);
                    } else if (action === 'highlight') {
                        try {
                            restoreSelection();
                            if (!document.execCommand('hiliteColor', false, val)) {
                                exec('backColor', val);
                            } else {
                                saveSelection();
                                syncToHiddenInputs();
                                updateWordCounter();
                                updateSaveStateUI(true);
                            }
                        } catch (err) {
                            exec('backColor', val);
                        }
                    }
                });
            });

            if (fontNameSelect) {
                fontNameSelect.addEventListener('mousedown', saveSelection);
                fontNameSelect.addEventListener('focus', saveSelection);
                fontNameSelect.addEventListener('change', () => {
                    if (fontNameSelect.value) {
                        exec('fontName', fontNameSelect.value);
                    }
                });
            }

            if (formatSelect) {
                formatSelect.addEventListener('mousedown', saveSelection);
                formatSelect.addEventListener('focus', saveSelection);
                formatSelect.addEventListener('change', () => {
                    if (formatSelect.value) {
                        exec('formatBlock', formatSelect.value);
                    }
                });
            }

            if (fontSizeSelect) {
                fontSizeSelect.addEventListener('mousedown', saveSelection);
                fontSizeSelect.addEventListener('focus', saveSelection);
                fontSizeSelect.addEventListener('change', () => {
                    if (fontSizeSelect.value) {
                        exec('fontSize', fontSizeSelect.value);
                    }
                });
            }

            // Table Picker Grid (6x6)
            const tableGrid = workstation.querySelector('#surat-table-grid');
            const tableLabel = workstation.querySelector('#surat-table-label');
            const tablePicker = workstation.querySelector('#surat-table-picker');
            if (tableGrid) {
                tableGrid.innerHTML = '';
                for (let r = 1; r <= 6; r++) {
                    for (let c = 1; c <= 6; c++) {
                        const cell = document.createElement('div');
                        cell.className = 'word-table-cell';
                        cell.dataset.row = r;
                        cell.dataset.col = c;
                        tableGrid.appendChild(cell);
                    }
                }

                tableGrid.addEventListener('mouseover', (e) => {
                    const cell = e.target.closest('.word-table-cell');
                    if (!cell) return;
                    const targetRow = parseInt(cell.dataset.row, 10);
                    const targetCol = parseInt(cell.dataset.col, 10);
                    if (tableLabel) tableLabel.textContent = `${targetRow} × ${targetCol} Tabel`;

                    tableGrid.querySelectorAll('.word-table-cell').forEach(c => {
                        const row = parseInt(c.dataset.row, 10);
                        const col = parseInt(c.dataset.col, 10);
                        if (row <= targetRow && col <= targetCol) {
                            c.classList.add('selected');
                        } else {
                            c.classList.remove('selected');
                        }
                    });
                });

                tableGrid.addEventListener('click', (e) => {
                    const cell = e.target.closest('.word-table-cell');
                    if (!cell) return;
                    const rows = parseInt(cell.dataset.row, 10);
                    const cols = parseInt(cell.dataset.col, 10);

                    let tableHtml = '<table border="1" cellpadding="6" cellspacing="0" style="width:100%; border-collapse:collapse; margin:10px 0;"><tbody>';
                    for (let r = 0; r < rows; r++) {
                        tableHtml += '<tr>';
                        for (let c = 0; c < cols; c++) {
                            tableHtml += '<td style="border:1px solid #cbd5e1; padding:6px 10px;">&nbsp;</td>';
                        }
                        tableHtml += '</tr>';
                    }
                    tableHtml += '</tbody></table><p>&nbsp;</p>';

                    exec('insertHTML', tableHtml);
                    if (tablePicker) tablePicker.classList.remove('open');
                });

                document.addEventListener('click', (e) => {
                    if (tablePicker && !tablePicker.contains(e.target) && !e.target.closest('[data-action="openTablePicker"], [data-action="insertTable"]')) {
                        tablePicker.classList.remove('open');
                    }
                });
            }
        }

        // Zoom Controls & Scaling
        const updateZoomCompensation = (zoomVal = currentZoomVal) => {
            if (!zoomContainer || !sheet) return;
            zoomContainer.style.marginBottom = '0px';
            const naturalHeight = zoomContainer.offsetHeight || sheet.offsetHeight || 1122;
            const scale = zoomVal / 100;
            if (scale !== 1) {
                const difference = Math.round(naturalHeight * (scale - 1));
                zoomContainer.style.marginBottom = `${difference}px`;
            }

            if (rulerEl) {
                rulerEl.style.transform = scale === 1 ? 'none' : `scaleX(${scale})`;
                rulerEl.style.transformOrigin = 'center top';
            }
        };

        const applyZoom = (val) => {
            if (!zoomContainer) return;
            let numericVal = 100;
            let zoomLabel = 'Zoom: 100%';
            if (val === 'fit') {
                const availableWidth = (deskCanvas ? deskCanvas.clientWidth : window.innerWidth) - 48;
                const sheetWidth = sheet ? (sheet.offsetWidth || 794) : 794;
                numericVal = Math.max(50, Math.min(125, Math.floor((availableWidth / sheetWidth) * 100)));
                zoomLabel = `Zoom: Sesuaikan (${numericVal}%)`;
            } else {
                numericVal = parseInt(val, 10) || 100;
                zoomLabel = `Zoom: ${numericVal}%`;
            }

            currentZoomVal = numericVal;
            zoomContainer.dataset.zoom = numericVal;
            zoomContainer.style.transform = numericVal === 100 ? 'none' : `scale(${numericVal / 100})`;
            zoomContainer.style.transformOrigin = 'center top';
            if (statusZoomEl) {
                statusZoomEl.textContent = zoomLabel;
            }
            updateZoomCompensation(numericVal);

            workstation.querySelectorAll('[data-zoom]').forEach(b => {
                if (b.dataset.zoom === String(val)) {
                    b.className = 'px-2 py-1 text-[11px] font-bold rounded-md active-zoom bg-slate-900 text-white transition cursor-pointer';
                } else {
                    b.className = 'px-2 py-1 text-[11px] font-bold rounded-md text-slate-700 hover:bg-slate-200 transition cursor-pointer';
                }
            });
        };

        // Fixed A4 Paper Format Enforcement (210 × 297 mm)
        const applyPaperSize = () => {
            const sheet2 = workstation.querySelector('#surat-sheet-page-2');
            [sheet, sheet2].forEach(s => {
                if (s) {
                    s.dataset.paperFormat = 'a4';
                    s.classList.remove('paper-f4');
                    s.classList.add('paper-a4');
                    s.style.width = '210mm';
                    s.style.minHeight = '297mm';
                }
            });

            if (rulerEl) {
                rulerEl.classList.remove('paper-f4', 'ruler-f4');
                rulerEl.classList.add('paper-a4');
            }

            if (statusBarEl) {
                statusBarEl.classList.remove('paper-f4', 'status-f4');
                statusBarEl.classList.add('paper-a4');
            }

            if (statusSizeEl) {
                statusSizeEl.textContent = 'Kertas A4 (Margin Kiri 3cm, Kanan 2cm, Bawah 2.5cm) • Kop: Times New Roman • Isi: Arial 12pt';
            }

            try {
                localStorage.setItem('siperapat_paper_format', 'a4');
            } catch (e) {}

            updateZoomCompensation(currentZoomVal);
        };

        const updateActivePageStatus = () => {
            if (!statusPageEl) return;
            const lampiranWrapper = document.getElementById('surat-lampiran-page-wrapper');
            const isLampiranVisible = lampiranWrapper && lampiranWrapper.style.display !== 'none';
            const totalPages = isLampiranVisible ? 2 : 1;
            let activePage = 1;
            if (isLampiranVisible) {
                const sheet2 = workstation.querySelector('#surat-sheet-page-2');
                if (sheet2) {
                    const rect = sheet2.getBoundingClientRect();
                    const midY = window.innerHeight / 2;
                    if (rect.top <= midY && rect.bottom >= midY) {
                        activePage = 2;
                    }
                }
            }
            statusPageEl.textContent = `Halaman ${activePage} dari ${totalPages}`;
        };

        window.addEventListener('scroll', updateActivePageStatus, { passive: true });
        if (deskCanvas) {
            deskCanvas.addEventListener('scroll', updateActivePageStatus, { passive: true });
        }

        workstation.querySelectorAll('[data-zoom]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                applyZoom(btn.dataset.zoom);
            });
        });

        applyPaperSize();
        if (window.innerWidth < 768) {
            applyZoom('fit');
        }

        // Modal Penyesuaian Naskah Surat Dinas Controllers
        const configModal = document.getElementById('modal-surat-config');
        const btnChooseCustomLogo = document.getElementById('btn-choose-custom-logo');
        const btnResetCustomLogo = document.getElementById('btn-reset-custom-logo');
        const formCustomLogoInput = document.getElementById('form-custom-logo');
        const formResetCustomLogoInput = document.getElementById('form-reset-custom-logo');
        const modalLogoPreviewImg = document.getElementById('modal-logo-preview-img');
        const modalCustomLogoFilename = document.getElementById('modal-custom-logo-filename');
        const defaultLogoSrc = workstation.dataset.defaultLogoSrc || '/images/tut-wuri-handayani.png';

        if (btnChooseCustomLogo && formCustomLogoInput) {
            btnChooseCustomLogo.addEventListener('click', (e) => {
                e.preventDefault();
                formCustomLogoInput.click();
            });
        }

        if (formCustomLogoInput) {
            formCustomLogoInput.addEventListener('change', () => {
                const file = formCustomLogoInput.files && formCustomLogoInput.files[0];
                if (!file) return;

                if (file.size > 512 * 1024) {
                    if (typeof ModalManager !== 'undefined' && typeof ModalManager.alert === 'function') {
                        ModalManager.alert('Ukuran berkas logo kop surat maksimal 512 KB.', 'Ukuran Berkas Terlalu Besar', 'error');
                    } else {
                        alert('Ukuran berkas logo kop surat maksimal 512 KB.');
                    }
                    formCustomLogoInput.value = '';
                    return;
                }

                if (formResetCustomLogoInput) {
                    formResetCustomLogoInput.value = '0';
                }

                const modalShowLogoCheckbox = document.getElementById('modal_show_logo');
                if (modalShowLogoCheckbox) {
                    modalShowLogoCheckbox.checked = true;
                }

                if (modalCustomLogoFilename) {
                    modalCustomLogoFilename.textContent = `Berkas dipilih: ${file.name} (${Math.round(file.size / 1024)} KB)`;
                }

                if (btnResetCustomLogo) {
                    btnResetCustomLogo.classList.remove('hidden');
                }

                const reader = new FileReader();
                reader.onload = (ev) => {
                    const dataUrl = ev.target?.result;
                    if (typeof dataUrl === 'string') {
                        if (modalLogoPreviewImg) modalLogoPreviewImg.src = dataUrl;
                        const sheetLogoImg = document.getElementById('sheet-logo-img');
                        if (sheetLogoImg) sheetLogoImg.src = dataUrl;
                    }
                };
                reader.readAsDataURL(file);
                updateSaveStateUI(true);
            });
        }

        if (btnResetCustomLogo) {
            btnResetCustomLogo.addEventListener('click', (e) => {
                e.preventDefault();
                if (formCustomLogoInput) {
                    formCustomLogoInput.value = '';
                }
                if (formResetCustomLogoInput) {
                    formResetCustomLogoInput.value = '1';
                }
                if (modalLogoPreviewImg) {
                    modalLogoPreviewImg.src = defaultLogoSrc;
                }
                const sheetLogoImg = document.getElementById('sheet-logo-img');
                if (sheetLogoImg) {
                    sheetLogoImg.src = defaultLogoSrc;
                }
                if (modalCustomLogoFilename) {
                    modalCustomLogoFilename.textContent = 'Logo standar Tut Wuri Handayani (Kemdiktisaintek)';
                }
                btnResetCustomLogo.classList.add('hidden');
                updateSaveStateUI(true);
            });
        }

        window.openSuratConfigModal = () => {
            const modal = document.getElementById('modal-surat-config');
            if (modal) {
                modal.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        };

        window.closeSuratConfigModal = () => {
            const modal = document.getElementById('modal-surat-config');
            if (modal) {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
            }
        };

        syncModalConfigToFormAndSheet = () => {
            // 1. Template Type
            const selectedTemplate = document.querySelector('input[name="modal_template_type"]:checked')?.value || 'undangan';
            const formTemplateInput = document.getElementById('form-template-type');
            if (formTemplateInput) formTemplateInput.value = selectedTemplate;

            const blockUndangan = document.getElementById('sheet-undangan-block');
            const blockEdaran = document.getElementById('sheet-edaran-block');
            const statusLabel = document.getElementById('status-format-label');
            if (statusLabel) statusLabel.textContent = selectedTemplate;

            if (selectedTemplate === 'edaran') {
                if (blockUndangan) blockUndangan.style.display = 'none';
                if (blockEdaran) blockEdaran.style.display = 'block';
            } else {
                if (blockEdaran) blockEdaran.style.display = 'none';
                if (blockUndangan) blockUndangan.style.display = 'block';
            }

            // 2. Kop Settings
            const showKop = document.getElementById('modal_show_kop')?.checked ?? true;
            const showLogo = document.getElementById('modal_show_logo')?.checked ?? true;
            const instansiInduk = document.getElementById('modal_instansi_induk')?.value || '';
            const instansiPelaksana = document.getElementById('modal_instansi_pelaksana')?.value || '';
            const alamatKontak = document.getElementById('modal_alamat_kontak')?.value || '';

            const formShowKop = document.getElementById('form-show-kop');
            const formShowLogo = document.getElementById('form-show-logo');
            const formInstansiInduk = document.getElementById('form-instansi-induk');
            const formInstansiPelaksana = document.getElementById('form-instansi-pelaksana');
            const formAlamatKontak = document.getElementById('form-alamat-kontak');

            if (formShowKop) formShowKop.value = showKop ? '1' : '0';
            if (formShowLogo) formShowLogo.value = showLogo ? '1' : '0';
            if (formInstansiInduk) formInstansiInduk.value = instansiInduk;
            if (formInstansiPelaksana) formInstansiPelaksana.value = instansiPelaksana;
            if (formAlamatKontak) formAlamatKontak.value = alamatKontak;

            const kopContainer = document.getElementById('sheet-kop-container');
            const headerKop = document.getElementById('sheet-header-kop');
            if (kopContainer) kopContainer.style.display = showKop ? 'block' : 'none';
            if (headerKop) headerKop.style.display = showKop ? 'block' : 'none';

            const logoCell = document.getElementById('sheet-logo-cell');
            const logoImg = document.getElementById('sheet-logo-img');
            const textCell = document.getElementById('sheet-kop-text-cell') || (logoCell ? logoCell.nextElementSibling : null);
            if (logoCell) {
                logoCell.style.width = showLogo ? '19.7%' : '0%';
                logoCell.style.display = showLogo ? 'table-cell' : 'none';
            }
            if (textCell) {
                textCell.style.width = showLogo ? '80.3%' : '100%';
            }
            if (logoImg) {
                logoImg.style.display = showLogo ? 'block' : 'none';
            }

            const sheetInstansiInduk = document.getElementById('sheet-instansi-induk');
            const sheetInstansiPelaksana = document.getElementById('sheet-instansi-pelaksana');
            const sheetAlamatKontak = document.getElementById('sheet-alamat-kontak');
            if (sheetInstansiInduk) sheetInstansiInduk.innerHTML = escapeHtml(instansiInduk).replace(/\n/g, '<br>');
            if (sheetInstansiPelaksana) sheetInstansiPelaksana.textContent = instansiPelaksana;
            if (sheetAlamatKontak) sheetAlamatKontak.innerHTML = escapeHtml(alamatKontak).replace(/\n/g, '<br>');

            // 3. Metadata fields
            const nomor = document.getElementById('modal_nomor_surat')?.value || '';
            const sifat = document.getElementById('modal_sifat')?.value || 'Biasa';
            const lampiran = document.getElementById('modal_lampiran')?.value || '-';
            const perihal = document.getElementById('modal_perihal')?.value || '';
            const tempat = document.getElementById('modal_tempat_surat')?.value || 'Padang';
            const tanggal = document.getElementById('modal_tanggal_surat')?.value || '';
            const tujuanRaw = document.getElementById('modal_tujuan_surat')?.value || '';
            const tujuanClean = tujuanRaw.replace(/^Yth\.?\s*/i, '');
            const showLampiranModal = document.getElementById('modal_show_lampiran')?.checked ?? false;
            const effectiveShowLampiran = showLampiranModal || (lampiran.trim() !== '' && lampiran.trim() !== '-');

            const fNomor = document.getElementById('form-nomor-surat');
            const fSifat = document.getElementById('form-sifat');
            const fLampiran = document.getElementById('form-lampiran');
            const fPerihal = document.getElementById('form-perihal');
            const fTempat = document.getElementById('form-tempat-surat');
            const fTanggal = document.getElementById('form-tanggal-surat');
            const fTujuan = document.getElementById('form-tujuan-surat');
            const fShowLampiran = document.getElementById('form-show-lampiran');

            if (fNomor) fNomor.value = nomor;
            if (fSifat) fSifat.value = sifat;
            if (fLampiran) fLampiran.value = lampiran;
            if (fPerihal) fPerihal.value = perihal;
            if (fTempat) fTempat.value = tempat;
            if (fTanggal) fTanggal.value = tanggal;
            if (fTujuan) fTujuan.value = tujuanClean;
            if (fShowLampiran) fShowLampiran.value = effectiveShowLampiran ? '1' : '0';

            const sheetNomor = document.getElementById('sheet-nomor-surat');
            const sheetSifat = document.getElementById('sheet-sifat');
            const sheetLampiran = document.getElementById('sheet-lampiran');
            const sheetPerihal = document.getElementById('sheet-perihal');
            const sheetTempat = document.getElementById('sheet-tempat-surat');
            const sheetTanggal = document.getElementById('sheet-tanggal-surat');
            const sheetTujuan = document.getElementById('sheet-tujuan-surat');

            if (sheetNomor) sheetNomor.textContent = nomor || '-';
            if (sheetSifat) sheetSifat.textContent = sifat || 'Biasa';
            if (sheetLampiran) sheetLampiran.textContent = lampiran || '-';
            if (sheetPerihal) sheetPerihal.textContent = perihal;
            if (sheetTempat) sheetTempat.textContent = tempat;
            if (sheetTanggal) sheetTanggal.textContent = tanggal;
            if (sheetTujuan) sheetTujuan.innerHTML = escapeHtml(tujuanClean).replace(/\n/g, '<br>');

            const sheetEdaranNomor = document.getElementById('sheet-edaran-nomor');
            const sheetEdaranPerihal = document.getElementById('sheet-edaran-perihal');
            const sheetEdaranTujuan = document.getElementById('sheet-edaran-tujuan');
            if (sheetEdaranNomor) sheetEdaranNomor.textContent = nomor || '-';
            if (sheetEdaranPerihal) sheetEdaranPerihal.textContent = perihal;
            if (sheetEdaranTujuan) sheetEdaranTujuan.innerHTML = escapeHtml(tujuanClean).replace(/\n/g, '<br>');

            // Sync Halaman 2 Lampiran Daftar Undangan
            const lampiranPageWrapper = document.getElementById('surat-lampiran-page-wrapper');
            const sheetLampiranNomor = document.getElementById('sheet-lampiran-nomor');
            const sheetLampiranTanggal = document.getElementById('sheet-lampiran-tanggal');
            if (lampiranPageWrapper) lampiranPageWrapper.style.display = effectiveShowLampiran ? 'flex' : 'none';
            if (sheetLampiranNomor) sheetLampiranNomor.textContent = nomor || '-';
            if (sheetLampiranTanggal) sheetLampiranTanggal.textContent = tanggal;

            // 4. Penandatangan & Tembusan
            const roleRaw = document.getElementById('modal_signer_role')?.value || 'Kepala';
            const roleClean = roleRaw.replace(/,+\s*$/, '');
            const name = document.getElementById('modal_signer_name')?.value || 'Afdalisma';
            const nip = document.getElementById('modal_signer_nip')?.value || '197012051992032002';
            const showSig = document.getElementById('modal_show_signature')?.checked ?? true;
            const tembusan = document.getElementById('modal_tembusan')?.value || '';

            const fRole = document.getElementById('form-signer-role');
            const fName = document.getElementById('form-signer-name');
            const fNip = document.getElementById('form-signer-nip');
            const fShowSig = document.getElementById('form-show-signature');
            const fTembusan = document.getElementById('form-tembusan');

            if (fRole) fRole.value = roleClean;
            if (fName) fName.value = name;
            if (fNip) fNip.value = nip;
            if (fShowSig) fShowSig.value = showSig ? '1' : '0';
            if (fTembusan) fTembusan.value = tembusan;

            const sheetRole = document.getElementById('sheet-signer-role');
            const sheetName = document.getElementById('sheet-signer-name');
            const sheetNip = document.getElementById('sheet-signer-nip');
            const sheetBadge = document.getElementById('sheet-signature-badge');
            const sheetTembusanLabel = document.getElementById('sheet-tembusan-label');
            const sheetTembusanBlock = document.getElementById('sheet-tembusan-block');
            const sheetTembusanText = document.getElementById('sheet-tembusan-text');

            if (sheetRole) sheetRole.textContent = roleClean;
            if (sheetName) sheetName.textContent = name;
            if (sheetNip) sheetNip.textContent = nip || '-';
            if (sheetBadge) sheetBadge.style.display = showSig ? 'inline-block' : 'none';
            if (sheetTembusanLabel) sheetTembusanLabel.style.display = tembusan.trim() ? 'block' : 'none';
            if (sheetTembusanBlock) sheetTembusanBlock.style.display = tembusan.trim() ? 'table-row' : 'none';
            if (sheetTembusanText) sheetTembusanText.innerHTML = escapeHtml(tembusan).replace(/\n/g, '<br>');

            return selectedTemplate;
        };

        window.applySuratConfigToSheet = () => {
            const selectedTemplate = syncModalConfigToFormAndSheet();
            if (selectedTemplate === 'edaran') {
                if (altEdaranBox) setActiveEditor(altEdaranBox);
            } else {
                if (bodyBox) setActiveEditor(bodyBox);
            }
            syncToHiddenInputs();
            updateWordCounter();
            updateSaveStateUI(true);
            updateActivePageStatus();
            updateZoomCompensation(currentZoomVal);
            window.closeSuratConfigModal();
        };

        if (configModal) {
            configModal.addEventListener('click', (e) => {
                if (e.target === configModal) {
                    window.closeSuratConfigModal();
                }
            });
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modal-surat-config');
                if (modal && !modal.classList.contains('hidden')) {
                    window.closeSuratConfigModal();
                }
            }
        });

        // Save button pre-sync click handler
        const saveBtn = workstation.querySelector('button[form="surat-edaran-form"]');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                syncToHiddenInputs(true);
            });
        }

        // Guard saat klik tombol Ekspor (PDF / Word) jika ada perubahan naskah yang belum disimpan
        workstation.querySelectorAll('[data-export-link]').forEach(link => {
            link.addEventListener('click', (e) => {
                if (!window.__suratDirtyState) return;
                e.preventDefault();
                const exportUrl = link.getAttribute('href');
                const formatName = link.dataset.exportLink === 'pdf' ? 'PDF Resmi (.pdf)' : 'Dokumen Word (.docx)';

                if (typeof window.showModal === 'function') {
                    window.showModal({
                        title: 'Perubahan Belum Disimpan',
                        message: `Terdapat ketikan naskah surat atau pengaturan yang belum disimpan ke server. Jika Anda mengunduh ${formatName} sekarang, berkas hanya akan memuat versi terakhir yang tersimpan. Lanjutkan mengunduh versi tersimpan?`,
                        type: 'warning',
                        confirmText: 'Tetap Unduh Versi Tersimpan',
                        cancelText: 'Batal (Simpan Dulu)',
                        onConfirm: () => {
                            window.location.href = exportUrl;
                        },
                    });
                }
            });
        });

        // Keyboard Shortcut: Ctrl+S / Cmd+S (Works anywhere on the Surat Edaran Workstation page)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                if (!document.body.contains(workstation)) return;
                e.preventDefault();
                const modal = document.getElementById('modal-surat-config');
                if (modal && !modal.classList.contains('hidden')) {
                    window.closeSuratConfigModal();
                }
                if (form) {
                    syncToHiddenInputs(true);
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.dispatchEvent(new Event('submit', { cancelable: true }));
                    }
                }
            }
        });

        if (form) {
            form.addEventListener('submit', () => {
                syncToHiddenInputs(true);
                if (form.dataset.confirmed === 'true') {
                    isSubmittingForm = true;
                    window.__suratDirtyState = false;
                }
            });
        }
    }
};

window.SuratEdaranWorkstation = SuratEdaranWorkstation;

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    SeamlessNavigation.init();
});