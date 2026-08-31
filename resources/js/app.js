document.addEventListener('DOMContentLoaded', () => {
    // Password Toggle Utility
    const eyeIcon = '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    const eyeOffIcon = '<path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.24 4.24M9.88 4.24A10.94 10.94 0 0 1 12 4c6.5 0 10 7 10 7a13.2 13.2 0 0 1-3.11 4.24M6.11 6.11A13.2 13.2 0 0 0 2 11s3.5 7 10 7a10.9 10.9 0 0 0 4.11-.8"/>';
    
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
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

    // Form Loading State
    document.querySelectorAll('form.auth-form').forEach(form => {
        form.addEventListener('submit', () => {
            const submit = form.querySelector('.auth-submit');
            if (!submit || submit.disabled) return;
            submit.disabled = true;
            submit.dataset.originalLabel = submit.textContent;
            submit.textContent = submit.dataset.loadingLabel || 'Memproses...';
        });
    });

    // Universal Modal System
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
        type = 'info', // 'success', 'error', 'warning', 'confirm', 'info'
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

    if (modalBackdrop) {
        document.querySelectorAll('[data-close-modal]').forEach(el => el.addEventListener('click', window.closeModal));
        modalBackdrop.addEventListener('click', (event) => {
            if (event.target === modalBackdrop) window.closeModal();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') window.closeModal();
        });
    }

    // Process Server Flash Data on Page Load
    const flashData = document.querySelector('#flash-modal-data');
    if (flashData) {
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

    // Form Client-side Validation Interceptor (Ensure User Feedback on Missing Conditions)
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('invalid', (e) => {
            // Only trigger modal on the first invalid field
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

    if (sidebarToggle) sidebarToggle.addEventListener('click', openMobileSidebar);
    if (sidebarClose) sidebarClose.addEventListener('click', closeMobileSidebar);
    if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeMobileSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar && sidebar.classList.contains('open')) {
            closeMobileSidebar();
        }
    });

    document.querySelectorAll('#app-sidebar .nav-link').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 1024) closeMobileSidebar();
        });
    });

    // User Profile Dropdown Controller
    const userDropdownBtn = document.querySelector('#user-profile-dropdown-btn');
    const userDropdownMenu = document.querySelector('#user-profile-dropdown-menu');
    const userDropdownChevron = document.querySelector('#user-profile-chevron');

    if (userDropdownBtn && userDropdownMenu) {
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
});