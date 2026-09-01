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
    const toast = document.querySelector('#app-toast');
    let toastTimer;

    window.showToast = (message, type = 'info') => {
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast show';
        if (type === 'success') toast.style.borderColor = '#059669';
        else if (type === 'error') toast.style.borderColor = '#dc2626';
        else if (type === 'warning') toast.style.borderColor = '#d97706';
        else toast.style.borderColor = '#334155';

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3200);
    };

    window.closeModal = () => {
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
        isHtml = true
    }) => {
        if (!modalBackdrop || !modalContent) return;

        let iconSvg = '';
        let headerColor = 'text-blue-600';
        let btnColor = 'bg-blue-600 hover:bg-blue-700 text-white';

        if (type === 'success') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>';
            headerColor = 'text-emerald-800';
            btnColor = 'bg-emerald-600 hover:bg-emerald-700 text-white';
        } else if (type === 'error') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>';
            headerColor = 'text-rose-800';
            btnColor = 'bg-rose-600 hover:bg-rose-700 text-white';
        } else if (type === 'warning') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>';
            headerColor = 'text-amber-800';
            btnColor = 'bg-amber-600 hover:bg-amber-700 text-white';
        } else if (type === 'confirm') {
            iconSvg = '<div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center mx-auto mb-3 shadow-xs"><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>';
            headerColor = 'text-slate-900';
            btnColor = 'bg-blue-600 hover:bg-blue-700 text-white';
        }

        let actionsHtml = '';
        if (cancelText) {
            actionsHtml = `
                <div class="modal-actions mt-5 flex items-center justify-end gap-2.5">
                    <button type="button" class="button secondary text-xs px-4 py-2" id="modal-cancel-btn">${cancelText}</button>
                    <button type="button" class="button ${btnColor} text-xs px-4 py-2 font-semibold" id="modal-confirm-btn">${confirmText}</button>
                </div>
            `;
        } else {
            actionsHtml = `
                <div class="modal-actions mt-5 flex items-center justify-end">
                    <button type="button" class="button ${btnColor} text-xs px-5 py-2.5 w-full sm:w-auto font-semibold" id="modal-confirm-btn">${confirmText}</button>
                </div>
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
    }

    // Process Server Flash Data on Load
    const flashData = document.querySelector('#flash-modal-data');
    if (flashData && !flashData.dataset.processed) {
        flashData.dataset.processed = 'true';
        const type = flashData.dataset.type || 'info';
        const title = flashData.dataset.title || 'Pemberitahuan';
        const message = flashData.dataset.message || '';
        if (message) {
            window.showModal({
                title: title,
                message: message,
                type: type,
                confirmText: 'Mengerti & Tutup'
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
}

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
            if (path.includes('/export-') || path.startsWith('/storage/')) return false;

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
            if (message && window.showModal) {
                window.showModal({
                    title: title,
                    message: message,
                    type: type,
                    confirmText: 'Mengerti & Tutup'
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