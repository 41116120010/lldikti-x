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

        button.addEventListener('click', () => {
            const input = document.querySelector('#' + button.dataset.togglePassword);
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            const svg = button.querySelector('svg');
            if (svg) svg.innerHTML = show ? eyeOffIcon : eyeIcon;
            button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
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
        type = 'info',
        confirmText = 'Tutup',
        cancelText = null,
        onConfirm = null,
        onCancel = null,
        isHtml = true,
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
                    <button type="button" class="button secondary text-xs px-4 py-2" id="modal-cancel-btn">${cancelText}</button>
                    <button type="button" class="button ${btnColor} text-xs px-4 py-2 font-semibold" id="modal-confirm-btn">${buttonDisplay}</button>
                </div>
                ${timerHintHtml}
            `;
        } else {
            actionsHtml = `
                <div class="modal-actions mt-5 flex flex-col sm:flex-row items-center justify-end gap-2">
                    <button type="button" class="button ${btnColor} text-xs px-5 py-2.5 w-full sm:w-auto font-semibold" id="modal-confirm-btn">${buttonDisplay}</button>
                </div>
                ${timerHintHtml}
            `;
        }

        modalContent.innerHTML = `
            <div class="text-center sm:text-left">
                ${iconSvg}
                <h3 class="text-base font-bold ${headerColor} mb-2 text-center">${title}</h3>
                <div class="text-xs text-slate-600 leading-relaxed text-center">${isHtml ? message : document.createTextNode(message).data}</div>
                ${actionsHtml}
            </div>
        `;

        const confirmBtn = modalContent.querySelector('#modal-confirm-btn');
        const cancelBtn = modalContent.querySelector('#modal-cancel-btn');
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
        if (message) {
            window.showModal({
                title: title,
                message: message,
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
                    message: `Mohon lengkapi data wajib pada formulir: <strong>${label}</strong> sebelum menyimpan data.`,
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
                currentFiles.forEach(file => dt.items.add(file));
                fileInput.files = dt.files;
            } catch (err) {
                console.warn('DataTransfer sync warning:', err);
            }
        };

        const render = () => {
            revokeAllUrls();
            previewGrid.innerHTML = '';

            if (currentFiles.length === 0) {
                previewGrid.classList.add('hidden');
                if (counterBadge) counterBadge.classList.add('hidden');
                return;
            }

            previewGrid.classList.remove('hidden');
            if (counterBadge) {
                counterBadge.textContent = `${currentFiles.length} Foto Terpilih`;
                counterBadge.classList.remove('hidden');
            }

            currentFiles.forEach((file, index) => {
                const url = URL.createObjectURL(file);
                objectUrls.push(url);

                const card = document.createElement('div');
                card.className = 'relative group bg-white rounded-xl border border-slate-200 overflow-hidden shadow-xs hover:border-slate-300 transition';
                card.innerHTML = `
                    <div class="aspect-4/3 relative bg-slate-100 overflow-hidden flex items-center justify-center">
                        <img src="${url}" alt="${file.name}" class="w-full h-24 sm:h-28 object-cover">
                        <button 
                            type="button" 
                            class="photo-preview-remove absolute top-1.5 right-1.5 w-7 h-7 rounded-full bg-rose-600 hover:bg-rose-700 text-white flex items-center justify-center shadow-md transition cursor-pointer"
                            title="Hapus foto ini dari antrean upload"
                            aria-label="Hapus foto ${file.name}"
                            data-index="${index}"
                        >
                            <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </div>
                    <div class="p-2 border-t border-slate-100 bg-white">
                        <div class="text-[11px] font-bold text-slate-800 truncate" title="${file.name}">${file.name}</div>
                        <div class="text-[10px] text-slate-500 font-mono font-medium">${formatSize(file.size)}</div>
                    </div>
                `;

                const removeBtn = card.querySelector('.photo-preview-remove');
                removeBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    removeFile(index);
                });

                previewGrid.appendChild(card);
            });
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
                const isDuplicate = currentFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
                if (!isDuplicate) {
                    currentFiles.push(file);
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
        const sheets = workstation.querySelectorAll('.office-paper-sheet');
        
        const notulensiBox = workstation.querySelector('#notulensi-content');
        const kesimpulanBox = workstation.querySelector('#kesimpulan-content');
        const notulensiHidden = workstation.querySelector('#notulensi-hidden');
        const kesimpulanHidden = workstation.querySelector('#kesimpulan-hidden');

        const activeIndicator = workstation.querySelector('#office-active-editor-indicator');
        const wordsCountEl = workstation.querySelector('.word-counter-words');
        const charsCountEl = workstation.querySelector('.word-counter-chars');

        let activeBox = notulensiBox;
        let activeHidden = notulensiHidden;
        let savedRange = null;

        const updateCounters = () => {
            const notulText = notulensiBox ? notulensiBox.innerText.trim() : '';
            const kesimpText = kesimpulanBox ? kesimpulanBox.innerText.trim() : '';
            const totalText = (notulText + ' ' + kesimpText).trim();
            const words = totalText ? totalText.split(/\s+/).filter(Boolean).length : 0;
            const chars = totalText.length;
            if (wordsCountEl) wordsCountEl.textContent = `${words} Kata`;
            if (charsCountEl) charsCountEl.textContent = `${chars} Karakter`;
        };

        const syncContent = () => {
            if (notulensiBox && notulensiHidden) notulensiHidden.value = notulensiBox.innerHTML;
            if (kesimpulanBox && kesimpulanHidden) kesimpulanHidden.value = kesimpulanBox.innerHTML;
            updateCounters();
        };

        const setActiveEditor = (box, hidden, name) => {
            activeBox = box;
            activeHidden = hidden;
            if (notulensiBox) notulensiBox.classList.remove('is-active-editor');
            if (kesimpulanBox) kesimpulanBox.classList.remove('is-active-editor');
            if (box) box.classList.add('is-active-editor');

            if (activeIndicator) {
                activeIndicator.textContent = name === 'notulensi'
                    ? 'Fokus: Catatan Jalannya Rapat (A)'
                    : 'Fokus: Kesimpulan & RTL (B)';
            }
        };

        if (notulensiBox) {
            notulensiBox.addEventListener('focus', () => setActiveEditor(notulensiBox, notulensiHidden, 'notulensi'));
            notulensiBox.addEventListener('click', () => setActiveEditor(notulensiBox, notulensiHidden, 'notulensi'));
            notulensiBox.addEventListener('input', syncContent);
            notulensiBox.addEventListener('blur', syncContent);
        }

        if (kesimpulanBox) {
            kesimpulanBox.addEventListener('focus', () => setActiveEditor(kesimpulanBox, kesimpulanHidden, 'kesimpulan'));
            kesimpulanBox.addEventListener('click', () => setActiveEditor(kesimpulanBox, kesimpulanHidden, 'kesimpulan'));
            kesimpulanBox.addEventListener('input', syncContent);
            kesimpulanBox.addEventListener('blur', syncContent);
        }

        // Initialize active editor to notulensi by default
        setActiveEditor(notulensiBox, notulensiHidden, 'notulensi');
        syncContent();

        const rulerEl = workstation.querySelector('#office-document-ruler');
        const statusBarEl = workstation.querySelector('#office-status-bar');
        const statusSizeEl = workstation.querySelector('#office-status-size');
        const statusPageEl = workstation.querySelector('#office-status-page');
        const statusZoomEl = workstation.querySelector('#office-status-zoom');

        // 1. Paper Format Switcher (A4 vs F4)
        const paperButtons = workstation.querySelectorAll('[data-paper-size]');
        const setPaperSize = (size) => {
            sheets.forEach(sheet => {
                sheet.classList.remove('paper-a4', 'paper-f4');
                sheet.classList.add(`paper-${size}`);
            });
            if (rulerEl) {
                rulerEl.classList.remove('paper-a4', 'paper-f4', 'ruler-f4');
                if (size === 'f4') {
                    rulerEl.classList.add('paper-f4', 'ruler-f4');
                } else {
                    rulerEl.classList.add('paper-a4');
                }
            }
            if (statusBarEl) {
                statusBarEl.classList.remove('paper-a4', 'paper-f4', 'status-f4');
                if (size === 'f4') {
                    statusBarEl.classList.add('paper-f4', 'status-f4');
                } else {
                    statusBarEl.classList.add('paper-a4');
                }
            }
            if (statusSizeEl) {
                statusSizeEl.textContent = size === 'f4' ? 'F4 / Folio (215 × 330 mm)' : 'A4 (210 × 297 mm)';
            }
            paperButtons.forEach(btn => {
                if (btn.dataset.paperSize === size) {
                    btn.classList.add('bg-slate-900', 'text-white', 'shadow-xs');
                    btn.classList.remove('text-slate-700', 'hover:bg-slate-200');
                } else {
                    btn.classList.remove('bg-slate-900', 'text-white', 'shadow-xs');
                    btn.classList.add('text-slate-700', 'hover:bg-slate-200');
                }
            });
            try {
                localStorage.setItem('siperapat_paper_format', size);
            } catch (e) {}
        };

        paperButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setPaperSize(btn.dataset.paperSize);
            });
        });

        // Load saved paper format preference (default A4)
        const savedFormat = (() => {
            try { return localStorage.getItem('siperapat_paper_format') || 'a4'; } catch (e) { return 'a4'; }
        })();
        setPaperSize(savedFormat);

        // 2. Zoom & Scaling Controls
        const zoomButtons = workstation.querySelectorAll('[data-zoom]');
        const setZoom = (zoomVal) => {
            if (!zoomContainer) return;
            zoomButtons.forEach(b => b.classList.remove('active-zoom', 'bg-slate-900', 'text-white'));

            const activeBtn = workstation.querySelector(`[data-zoom="${zoomVal}"]`);
            if (activeBtn) activeBtn.classList.add('active-zoom', 'bg-slate-900', 'text-white');

            let currentZoomText = `Zoom: ${zoomVal}%`;
            if (zoomVal === 'fit') {
                const canvasWidth = deskCanvas ? deskCanvas.clientWidth - 48 : window.innerWidth;
                const sheetWidthPx = sheets[0] ? sheets[0].offsetWidth : 794;
                const scale = Math.min(1, Math.max(0.4, canvasWidth / sheetWidthPx));
                zoomContainer.style.transform = `scale(${scale})`;
                currentZoomText = `Zoom: Sesuaikan (${Math.round(scale * 100)}%)`;
            } else {
                const scale = parseFloat(zoomVal) / 100;
                zoomContainer.style.transform = `scale(${scale})`;
            }
            if (statusZoomEl) {
                statusZoomEl.textContent = currentZoomText;
            }
        };

        zoomButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                setZoom(btn.dataset.zoom);
            });
        });

        // On small viewports (<768px), auto-fit zoom
        if (window.innerWidth < 768) {
            setZoom('fit');
        } else {
            setZoom('100');
        }

        // 2.1. Viewport Scroll Detection for Active Sheet Indicator
        if (sheets.length > 0 && statusPageEl) {
            const handleScroll = () => {
                const midY = window.innerHeight / 2;
                let activePage = 1;
                sheets.forEach((sheet, idx) => {
                    const rect = sheet.getBoundingClientRect();
                    if (rect.top <= midY && rect.bottom >= midY) {
                        activePage = idx + 1;
                    }
                });
                statusPageEl.textContent = `Halaman ${activePage} dari ${sheets.length}`;
            };
            window.addEventListener('scroll', handleScroll, { passive: true });
            if (deskCanvas) {
                deskCanvas.addEventListener('scroll', handleScroll, { passive: true });
            }
            handleScroll();
        }

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
                    syncContent();
                });
            });

            // Paragraph Block Select
            if (formatSelect) {
                formatSelect.addEventListener('change', (e) => {
                    restoreSelection();
                    const val = e.target.value;
                    if (val) {
                        document.execCommand('formatBlock', false, val);
                        syncContent();
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
                        syncContent();
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
                        syncContent();
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
                        syncContent();
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

            // Clear All Button
            const clearAllBtn = ribbon.querySelector('button[data-action="clearAll"]');
            if (clearAllBtn) {
                clearAllBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    if (!activeBox || !activeBox.innerText.trim()) return;
                    if (confirm('Kosongkan teks pada bagian yang sedang aktif?')) {
                        activeBox.innerHTML = '';
                        syncContent();
                    }
                });
            }
        }

        // 4. Keyboard Shortcuts: Ctrl+S / Cmd+S to Save
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                const saveForm = workstation.querySelector('form');
                if (saveForm) {
                    e.preventDefault();
                    syncContent();
                    saveForm.requestSubmit ? saveForm.requestSubmit() : saveForm.submit();
                }
            }
        });

        // Form Submit: Ensure latest sync
        const form = workstation.querySelector('form');
        if (form) {
            form.addEventListener('submit', () => syncContent());
        }
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
            if (message && window.showModal) {
                window.showModal({
                    title: title,
                    message: message,
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

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    SeamlessNavigation.init();
});