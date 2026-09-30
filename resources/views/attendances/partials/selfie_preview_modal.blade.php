{{--
    Partial: attendances.partials.selfie_preview_modal
    Komponen modal lightbox pratinjau foto selfie kehadiran rapat (Gov-Tech Enterprise Standard).
    Mendukung:
    - Viewport responsif bebas distorsi dengan rasio aspek terkunci.
    - Kontrol interaktif: Zoom In, Zoom Out, Reset Fit, Rotasi 90° CW, dan Drag / Pan kursor grab.
    - Keyboard shortcuts (+, -, 0, r, Escape).
    - Kartu metadata integritas resmi (Nama, NIP 18 digit, Unit Kerja, Waktu Sah WIB, IP Address).
    - Tombol aksi: Buka di Tab Baru, Unduh Berkas, dan Tutup.
    - Vanilla JS Controller mandiri (window.selfiePreviewModal), zero-bloatware & bebas memory leak.
--}}
@once
<div 
    id="selfie-preview-modal" 
    class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-slate-950/80 backdrop-blur-xs transition-opacity duration-200 hidden"
    role="dialog"
    aria-modal="true"
    aria-labelledby="selfie-modal-title"
    tabindex="-1"
>
    {{-- Dialog Box --}}
    <div 
        id="selfie-modal-dialog"
        class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-300 overflow-hidden flex flex-col max-h-[94dvh] transition-transform duration-200 scale-95"
        onclick="event.stopPropagation()"
    >
        {{-- Header Dialog --}}
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="3"/>
                        <path d="M19 4h-3.5L14 2H10L8.5 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <h3 id="selfie-modal-title" class="font-bold text-slate-950 text-xs sm:text-sm truncate">
                        Verifikasi Foto Selfie Kehadiran
                    </h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="inline-flex items-center gap-1 px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Presensi Sah</span>
                        </span>
                        <span class="text-[10px] text-slate-600 font-medium hidden sm:inline">&bull; SIPERAPAT LLDIKTI</span>
                    </div>
                </div>
            </div>

            {{-- Close Button (Touch target >= 44x44px) --}}
            <button 
                type="button" 
                id="selfie-modal-close-btn"
                onclick="window.selfiePreviewModal.close()" 
                class="min-w-[44px] min-h-[44px] -mr-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer"
                title="Tutup Pratinjau (Esc)"
                aria-label="Tutup pratinjau foto selfie"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Viewport Kanvas Foto Selfie Interaktif --}}
        <div 
            id="selfie-modal-viewport"
            class="relative w-full bg-slate-950 flex items-center justify-center overflow-hidden min-h-[260px] sm:min-h-[340px] max-h-[460px] select-none touch-none"
        >
            {{-- Foto Selfie --}}
            <img 
                id="selfie-modal-image" 
                src="" 
                alt="Foto Selfie Kehadiran" 
                class="max-h-[420px] w-auto max-w-full object-contain transition-transform duration-100 ease-out origin-center cursor-default"
                loading="eager"
                decoding="async"
                draggable="false"
                onerror="this.classList.add('hidden'); document.getElementById('selfie-modal-fallback').classList.remove('hidden');"
                onload="this.classList.remove('hidden'); document.getElementById('selfie-modal-fallback').classList.add('hidden');"
            />

            {{-- Fallback Card if media fails to load --}}
            <div id="selfie-modal-fallback" class="hidden text-center p-6 text-slate-300 space-y-2 select-none">
                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center mx-auto shadow-inner">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19 4h-3.5L14 2H10L8.5 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/></svg>
                </div>
                <div class="text-xs font-bold text-slate-200">Berkas Foto Selfie Tidak Ditemukan</div>
                <div class="text-[11px] text-slate-400 max-w-xs mx-auto">Berkas fisik foto selfie belum tersimpan atau telah diarsipkan dari server penyimpanan.</div>
            </div>

            {{-- Floating Interactive Toolbar --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-slate-900/85 hover:bg-slate-900 text-white px-3 py-1.5 rounded-full flex items-center gap-1.5 shadow-lg backdrop-blur-xs border border-white/10 z-10 transition">
                {{-- Zoom Out --}}
                <button 
                    type="button" 
                    onclick="window.selfiePreviewModal.zoomOut()" 
                    class="p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[34px] min-h-[34px] cursor-pointer"
                    title="Perkecil (-)"
                    aria-label="Perkecil"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                {{-- Zoom Level Indicator --}}
                <span id="selfie-modal-zoom-val" class="text-xs font-mono font-bold text-white px-1.5 min-w-[46px] text-center select-none">
                    100%
                </span>

                {{-- Zoom In --}}
                <button 
                    type="button" 
                    onclick="window.selfiePreviewModal.zoomIn()" 
                    class="p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[34px] min-h-[34px] cursor-pointer"
                    title="Perbesar (+)"
                    aria-label="Perbesar"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="h-4 w-px bg-white/20 mx-0.5"></div>

                {{-- Rotate 90 CW --}}
                <button 
                    type="button" 
                    onclick="window.selfiePreviewModal.rotate()" 
                    class="p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[34px] min-h-[34px] cursor-pointer"
                    title="Putar 90° Searah Jarum Jam (R)"
                    aria-label="Putar 90 derajat"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                </button>

                {{-- Reset Fit --}}
                <button 
                    type="button" 
                    onclick="window.selfiePreviewModal.reset()" 
                    class="p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[34px] min-h-[34px] cursor-pointer"
                    title="Reset Ukuran (0)"
                    aria-label="Reset Ukuran"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </button>
            </div>
        </div>

        {{-- Metadata Card (Integritas Data Presensi Pegawai) --}}
        <div class="p-4 sm:p-5 bg-slate-50 border-t border-slate-200 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                {{-- Identitas Pegawai --}}
                <div class="p-3 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-mono">Pegawai / Peserta</div>
                    <div id="selfie-modal-user-name" class="font-bold text-slate-950 text-xs sm:text-sm truncate">
                        -
                    </div>
                    <div id="selfie-modal-user-nip" class="text-[11px] text-slate-600 font-mono font-medium truncate">
                        NIP -
                    </div>
                </div>

                {{-- Unit Kerja --}}
                <div class="p-3 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-mono">Unit Kerja / Pokja</div>
                    <div id="selfie-modal-unit-name" class="font-bold text-slate-900 text-xs sm:text-sm truncate">
                        -
                    </div>
                    <div class="text-[11px] text-slate-500 font-medium">
                        LLDIKTI Wilayah X
                    </div>
                </div>

                {{-- Stempel Waktu Presensi --}}
                <div class="p-3 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-mono">Waktu Presensi Sah</div>
                    <div id="selfie-modal-signed-at" class="font-mono text-xs font-bold text-slate-950">
                        -
                    </div>
                    <div class="text-[10px] text-emerald-700 font-medium flex items-center gap-1">
                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Terekam Server Jaringan</span>
                    </div>
                </div>

                {{-- Jejak Autentikasi WebRTC & IP --}}
                <div class="p-3 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 font-mono">Autentikasi & Jaringan</div>
                    <div class="text-xs font-semibold text-slate-800">
                        IP: <span id="selfie-modal-ip-address" class="font-mono font-bold text-slate-950">127.0.0.1</span>
                    </div>
                    <div class="text-[10px] text-slate-500 font-medium">
                        Kamera WebRTC Terverifikasi
                    </div>
                </div>
            </div>
        </div>

        {{-- Footer Aksi Fungsional --}}
        <div class="px-4 sm:px-5 py-3 border-t border-slate-200 bg-white flex flex-wrap items-center justify-between gap-2.5 shrink-0">
            <div class="text-[11px] text-slate-500 font-medium hidden sm:flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Gunakan scroll mouse atau seret foto saat diperbesar</span>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                {{-- Buka Tab Baru --}}
                <a 
                    id="selfie-modal-btn-tab" 
                    href="#" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="button small secondary min-h-[40px] px-3.5 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                    title="Buka gambar asli di tab baru"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    <span>Tab Baru</span>
                </a>

                {{-- Unduh Foto --}}
                <a 
                    id="selfie-modal-btn-download" 
                    href="#" 
                    download 
                    class="button small secondary min-h-[40px] px-3.5 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                    title="Unduh berkas foto selfie"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    <span>Unduh</span>
                </a>

                {{-- Tutup --}}
                <button 
                    type="button" 
                    onclick="window.selfiePreviewModal.close()" 
                    class="button small bg-slate-900 hover:bg-slate-800 text-white min-h-[40px] px-4 rounded-xl text-xs font-bold cursor-pointer transition shadow-2xs"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Global Lightweight Selfie Preview Modal Controller (Vanilla JS, Zero Bloat)
 * Mengontrol dialog, zoom in/out, rotasi 90°, pan/drag, dan keyboard navigation.
 */
window.selfiePreviewModal = {
    isOpen: false,
    scale: 1,
    rotation: 0,
    panX: 0,
    panY: 0,
    isDragging: false,
    startX: 0,
    startY: 0,

    escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, (ch) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
        }[ch]));
    },

    open(data) {
        const modal = document.getElementById('selfie-preview-modal');
        const dialog = document.getElementById('selfie-modal-dialog');
        const img = document.getElementById('selfie-modal-image');
        const btnTab = document.getElementById('selfie-modal-btn-tab');
        const btnDownload = document.getElementById('selfie-modal-btn-download');

        if (!modal || !dialog || !img) return;

        // Resolve and normalize mediaUrl (handle different dev ports gracefully)
        let mediaUrl = data.url || '';
        if (mediaUrl.includes('/storage/')) {
            try {
                const parsed = new URL(mediaUrl, window.location.origin);
                if ((parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') && parsed.port !== window.location.port) {
                    mediaUrl = parsed.pathname + parsed.search + parsed.hash;
                }
            } catch (_) {}
        }

        const fallback = document.getElementById('selfie-modal-fallback');
        if (fallback) fallback.classList.add('hidden');
        img.classList.remove('hidden');

        img.src = mediaUrl;
        img.alt = `Foto Selfie Kehadiran: ${data.name || 'Pegawai'}`;
        if (btnTab) btnTab.href = mediaUrl;
        if (btnDownload) {
            btnDownload.href = mediaUrl;
            btnDownload.download = `selfie_${(data.nip || 'peserta').replace(/[^a-zA-Z0-9]/g, '_')}.jpg`;
        }

        // Set Metadata Texts safely
        const elName = document.getElementById('selfie-modal-user-name');
        const elNip = document.getElementById('selfie-modal-user-nip');
        const elUnit = document.getElementById('selfie-modal-unit-name');
        const elSigned = document.getElementById('selfie-modal-signed-at');
        const elIp = document.getElementById('selfie-modal-ip-address');

        if (elName) elName.textContent = data.name || '-';
        if (elNip) {
            const nipVal = data.nip && data.nip !== '-' ? data.nip : '-';
            elNip.textContent = nipVal !== '-' ? `NIP ${nipVal}` : 'NIP Tidak Tersedia';
        }
        if (elUnit) elUnit.textContent = data.unit || 'Tingkat Lembaga';
        if (elSigned) elSigned.textContent = data.signedAt ? `${data.signedAt}` : 'Waktu Tercatat Server';
        if (elIp) elIp.textContent = data.ip || '127.0.0.1';

        // Reset Transform State
        this.reset();

        // Show Modal
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        this.isOpen = true;

        // Animate Dialog
        requestAnimationFrame(() => {
            dialog.classList.remove('scale-95');
            dialog.classList.add('scale-100');
        });

        // Focus dialog for accessibility
        modal.focus();
    },

    close() {
        const modal = document.getElementById('selfie-preview-modal');
        const dialog = document.getElementById('selfie-modal-dialog');
        if (!modal) return;

        if (dialog) {
            dialog.classList.remove('scale-100');
            dialog.classList.add('scale-95');
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            this.isOpen = false;
            this.reset();
        }, 150);
    },

    zoomIn() {
        if (this.scale < 3.5) {
            this.scale = Math.min(3.5, +(this.scale + 0.25).toFixed(2));
            this.applyTransform();
        }
    },

    zoomOut() {
        if (this.scale > 0.5) {
            this.scale = Math.max(0.5, +(this.scale - 0.25).toFixed(2));
            if (this.scale <= 1) {
                this.panX = 0;
                this.panY = 0;
            }
            this.applyTransform();
        }
    },

    rotate() {
        this.rotation = (this.rotation + 90) % 360;
        this.applyTransform();
    },

    reset() {
        this.scale = 1;
        this.rotation = 0;
        this.panX = 0;
        this.panY = 0;
        this.applyTransform();
    },

    applyTransform() {
        const img = document.getElementById('selfie-modal-image');
        const zoomVal = document.getElementById('selfie-modal-zoom-val');

        if (img) {
            img.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale}) rotate(${this.rotation}deg)`;
            img.style.cursor = this.scale > 1 ? (this.isDragging ? 'grabbing' : 'grab') : 'default';
        }

        if (zoomVal) {
            zoomVal.textContent = Math.round(this.scale * 100) + '%';
        }
    },

    setupListeners() {
        const modal = document.getElementById('selfie-preview-modal');
        const viewport = document.getElementById('selfie-modal-viewport');
        const img = document.getElementById('selfie-modal-image');

        if (!modal || !viewport || modal.dataset.listenersBound === 'true') return;
        modal.dataset.listenersBound = 'true';

        // Backdrop Click to Close
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close();
            }
        });

        // Mouse Drag / Touch Pan
        const onPointerDown = (e) => {
            if (this.scale <= 1) return;
            this.isDragging = true;
            this.startX = e.clientX - this.panX;
            this.startY = e.clientY - this.panY;
            if (img) img.style.cursor = 'grabbing';
            viewport.setPointerCapture?.(e.pointerId);
            e.preventDefault();
        };

        const onPointerMove = (e) => {
            if (!this.isDragging || this.scale <= 1) return;
            this.panX = e.clientX - this.startX;
            this.panY = e.clientY - this.startY;
            this.applyTransform();
        };

        const onPointerUp = (e) => {
            if (this.isDragging) {
                this.isDragging = false;
                if (img) img.style.cursor = this.scale > 1 ? 'grab' : 'default';
                try {
                    viewport.releasePointerCapture?.(e.pointerId);
                } catch (_) {}
            }
        };

        viewport.addEventListener('pointerdown', onPointerDown);
        viewport.addEventListener('pointermove', onPointerMove);
        viewport.addEventListener('pointerup', onPointerUp);
        viewport.addEventListener('pointercancel', onPointerUp);

        // Mouse Wheel Zoom
        viewport.addEventListener('wheel', (e) => {
            e.preventDefault();
            if (e.deltaY < 0) {
                this.zoomIn();
            } else {
                this.zoomOut();
            }
        }, { passive: false });

        // Keyboard Shortcuts
        document.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;

            if (e.key === 'Escape') {
                this.close();
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.zoomIn();
            } else if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.zoomOut();
            } else if (e.key === '0') {
                e.preventDefault();
                this.reset();
            } else if (e.key === 'r' || e.key === 'R') {
                e.preventDefault();
                this.rotate();
            }
        });
    },

    bindTriggers() {
        document.querySelectorAll('[data-selfie-modal-trigger]').forEach((el) => {
            if (el.dataset.selfieBound === 'true') return;
            el.dataset.selfieBound = 'true';

            el.addEventListener('click', (e) => {
                e.preventDefault();
                this.open({
                    url: el.dataset.selfieUrl || '',
                    name: el.dataset.userName || '',
                    nip: el.dataset.userNip || '',
                    unit: el.dataset.unitName || '',
                    signedAt: el.dataset.signedAt || '',
                    ip: el.dataset.ipAddress || '',
                });
            });
        });
    },

    init() {
        this.setupListeners();
        this.bindTriggers();
    }
};

// Auto initialize on load and page swaps
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.selfiePreviewModal.init());
} else {
    window.selfiePreviewModal.init();
}
window.addEventListener('page:loaded', () => window.selfiePreviewModal.init());
</script>
@endonce
