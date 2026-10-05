{{-- ========================================================================= --}}
{{-- MODAL LIGHTBOX PRATINJAU DOKUMENTASI KEGIATAN RAPAT (ENTERPRISE GOV-TECH) --}}
{{-- Standardized Activity Documentation Viewer: Zoom, Rotate, Pan, Carousel  --}}
{{-- ========================================================================= --}}

<div 
    id="documentation-preview-modal" 
    class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-3 sm:p-5 md:p-6 bg-slate-950/80 backdrop-blur-xs transition-opacity duration-200 hidden !m-0 m-0"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="doc-modal-title"
    aria-describedby="doc-modal-caption"
>
    <div 
        id="doc-modal-dialog"
        class="relative my-auto w-full max-w-2xl sm:max-w-3xl bg-white border border-slate-300 rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3.5rem)] transition-transform duration-200 transform scale-95"
        onclick="event.stopPropagation()"
    >
        {{-- 1. Modal Header --}}
        <div class="px-4 sm:px-5 py-2.5 sm:py-3 border-b border-slate-200 bg-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-2xs">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 id="doc-modal-title" class="font-bold text-slate-950 text-xs sm:text-sm truncate">
                            Pratinjau Dokumentasi Kegiatan
                        </h3>
                        <span id="doc-modal-counter" class="inline-flex items-center px-2 py-0.2 rounded-full text-[10px] font-mono font-bold bg-slate-100 text-slate-800 border border-slate-300">
                            Foto 1 dari 1
                        </span>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span id="doc-modal-type-badge-top" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 font-mono">
                            <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Dokumentasi Sah</span>
                        </span>
                        <span id="doc-modal-subtitle" class="text-[10px] text-slate-500 font-medium truncate hidden sm:inline">&bull; LLDIKTI Wilayah X</span>
                    </div>
                </div>
            </div>

            {{-- Close Button (Touch target >= 44x44px) --}}
            <button 
                type="button" 
                id="doc-modal-close-btn"
                onclick="window.documentationPreviewModal.close()" 
                class="min-w-[44px] min-h-[44px] -mr-2 rounded-xl text-slate-500 hover:text-slate-900 hover:bg-slate-100 flex items-center justify-center transition cursor-pointer"
                aria-label="Tutup pratinjau (Escape)"
                title="Tutup (Esc)"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- 2. Interactive Viewport Stage --}}
        <div 
            id="doc-modal-stage"
            class="relative w-full bg-slate-950 flex items-center justify-center overflow-hidden flex-1 min-h-[160px] sm:min-h-[200px] max-h-[250px] sm:max-h-[280px] select-none touch-none"
        >
            {{-- Tombol Navigasi Prev (Foto Sebelumnya) --}}
            <button 
                type="button" 
                id="doc-modal-prev-btn"
                onclick="window.documentationPreviewModal.prev()"
                class="absolute left-2 sm:left-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-slate-900/80 hover:bg-slate-900 border border-white/20 text-white flex items-center justify-center shadow-lg transition cursor-pointer backdrop-blur-xs disabled:opacity-20 disabled:cursor-not-allowed group"
                aria-label="Foto sebelumnya (Panah Kiri)"
                title="Foto Sebelumnya (←)"
            >
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:-translate-x-0.5 transition-transform">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            {{-- Tombol Navigasi Next (Foto Selanjutnya) --}}
            <button 
                type="button" 
                id="doc-modal-next-btn"
                onclick="window.documentationPreviewModal.next()"
                class="absolute right-2 sm:right-3 top-1/2 -translate-y-1/2 z-20 w-9 h-9 sm:w-11 sm:h-11 rounded-full bg-slate-900/80 hover:bg-slate-900 border border-white/20 text-white flex items-center justify-center shadow-lg transition cursor-pointer backdrop-blur-xs disabled:opacity-20 disabled:cursor-not-allowed group"
                aria-label="Foto selanjutnya (Panah Kanan)"
                title="Foto Selanjutnya (→)"
            >
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>

            {{-- Floating Toolbar Kontrol (Zoom, Rotate, Reset) --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 bg-slate-900/85 hover:bg-slate-900 text-white px-2.5 sm:px-3 py-1.5 rounded-full flex items-center gap-1 sm:gap-1.5 shadow-lg backdrop-blur-xs border border-white/10 z-20 transition">
                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.zoomOut()"
                    class="p-1 sm:p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[30px] min-h-[30px] sm:min-w-[34px] sm:min-h-[34px] cursor-pointer"
                    aria-label="Perkecil zoom (-)"
                    title="Perkecil (-)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <button 
                    type="button" 
                    id="doc-modal-scale-indicator"
                    onclick="window.documentationPreviewModal.resetZoom()"
                    class="text-xs font-mono font-bold text-white px-1 sm:px-1.5 min-w-[42px] sm:min-w-[46px] text-center select-none hover:text-slate-300 transition"
                    title="Klik untuk reset zoom (0)"
                >
                    100%
                </button>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.zoomIn()"
                    class="p-1 sm:p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[30px] min-h-[30px] sm:min-w-[34px] sm:min-h-[34px] cursor-pointer"
                    aria-label="Perbesar zoom (+)"
                    title="Perbesar (+)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="h-4 w-px bg-white/20 mx-0.5"></div>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.rotate()"
                    class="p-1 sm:p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[30px] min-h-[30px] sm:min-w-[34px] sm:min-h-[34px] cursor-pointer"
                    aria-label="Putar orientasi 90 derajat (r)"
                    title="Putar 90° (r)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                </button>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.reset()"
                    class="p-1 sm:p-1.5 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[30px] min-h-[30px] sm:min-w-[34px] sm:min-h-[34px] cursor-pointer"
                    aria-label="Reset ukuran dan orientasi"
                    title="Sesuaikan ke Layar"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </button>
            </div>

            {{-- Elemen Gambar Dokumentasi --}}
            <img 
                id="doc-modal-img" 
                src="" 
                alt="Dokumentasi Kegiatan" 
                class="max-h-[210px] sm:max-h-[260px] w-auto max-w-full object-contain transition-transform duration-75 cursor-grab active:cursor-grabbing will-change-transform"
                draggable="false"
            >

            {{-- Container Fallback untuk Media Non-Gambar (PDF / Video / Berkas Lain) --}}
            <div id="doc-modal-pdf-container" class="hidden w-full h-[210px] sm:h-[260px] flex items-center justify-center bg-slate-900">
                <object id="doc-modal-pdf-object" data="" type="application/pdf" class="w-full h-full">
                    <div class="text-center text-white p-6">
                        <p class="text-sm font-semibold mb-2">Pratinjau PDF tidak didukung peramban ini.</p>
                        <a id="doc-modal-pdf-fallback-link" href="#" target="_blank" class="button text-xs bg-white text-slate-900 font-bold">Buka Berkas PDF</a>
                    </div>
                </object>
            </div>

            <div id="doc-modal-video-container" class="hidden w-full max-h-[210px] sm:max-h-[260px] flex items-center justify-center bg-slate-950 p-3">
                <video id="doc-modal-video" controls class="max-h-[190px] sm:max-h-[240px] max-w-full rounded-lg shadow-lg">
                    <source src="" type="video/mp4">
                    Peramban Anda tidak mendukung pemutaran video.
                </video>
            </div>

            {{-- Fallback Card jika Gambar Gagal Dimuat --}}
            <div id="doc-modal-fallback" class="hidden flex-col items-center justify-center p-6 text-center bg-slate-900 text-white rounded-xl max-w-md mx-auto my-4 border border-slate-800">
                <div class="w-12 h-12 rounded-xl bg-slate-800 text-slate-400 flex items-center justify-center mb-2 mx-auto">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                    </svg>
                </div>
                <h4 class="text-xs font-bold text-slate-200 mb-0.5">Berkas Foto Tidak Ditemukan</h4>
                <p class="text-[11px] text-slate-400 leading-relaxed mb-3">
                    Berkas fisik foto dokumentasi belum tersimpan atau telah diarsipkan.
                </p>
                <a id="doc-modal-fallback-link" href="#" target="_blank" class="button small secondary text-xs font-bold">
                    Coba Buka Tautan Asli
                </a>
            </div>
        </div>

        {{-- 3. Metadata & Caption Card (Overflow Y Auto - Bebas Clipping) --}}
        <div class="p-2.5 sm:p-3 bg-slate-50 border-t border-slate-200 shrink-0 max-h-[110px] sm:max-h-[120px] overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                {{-- Keterangan / Caption Foto --}}
                <div class="p-2.5 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-0.5">
                    <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 font-mono">
                        Keterangan Foto Kegiatan
                    </div>
                    <div id="doc-modal-caption" class="text-xs font-bold text-slate-950 leading-snug line-clamp-2" title="">
                        Dokumentasi Rapat
                    </div>
                    <div id="doc-modal-agenda-title" class="text-[10px] text-slate-500 font-medium truncate">
                        -
                    </div>
                </div>

                {{-- Informasi Berkas & Status Arsip --}}
                <div class="p-2.5 bg-white rounded-xl border border-slate-300 shadow-2xs space-y-0.5 flex flex-col justify-between">
                    <div>
                        <div class="text-[9px] font-bold uppercase tracking-wider text-slate-500 font-mono">
                            Waktu Unggah / Berkas
                        </div>
                        <div id="doc-modal-date" class="font-mono text-xs font-bold text-slate-950">
                            -
                        </div>
                    </div>
                    <div id="doc-modal-type-badge" class="text-[10px] font-medium text-emerald-700 flex items-center gap-1 mt-0.5">
                        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                        <span>Arsip Dokumentasi Sah</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Footer & Action Buttons --}}
        <div class="px-4 sm:px-5 py-2.5 sm:py-3 border-t border-slate-200 bg-white flex flex-wrap items-center justify-between gap-2.5 shrink-0">
            <div class="text-[11px] text-slate-500 font-medium hidden sm:flex items-center gap-1.5">
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Navigasi: <strong class="text-slate-700">← / →</strong> atau geser gambar saat diperbesar</span>
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                {{-- Buka Tab Baru --}}
                <a 
                    id="doc-modal-tab-btn" 
                    href="#" 
                    target="_blank" 
                    rel="noopener noreferrer" 
                    class="button small secondary min-h-[40px] px-3.5 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                    title="Buka gambar di tab baru"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>Tab Baru</span>
                </a>

                {{-- Unduh Foto --}}
                <a 
                    id="doc-modal-download-btn" 
                    href="#" 
                    download 
                    class="button small secondary min-h-[40px] px-3.5 rounded-xl text-xs font-bold flex items-center gap-1.5 cursor-pointer"
                    title="Unduh berkas foto"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh</span>
                </a>

                {{-- Tutup --}}
                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.close()" 
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
 * DocumentationPreviewModalController
 * Universal Lightbox & Gallery Controller for Activity Documentation (Zero Bloatware)
 */
window.documentationPreviewModal = {
    isOpen: false,
    items: [],
    currentIndex: 0,
    scale: 1,
    rotation: 0,
    panX: 0,
    panY: 0,
    isDragging: false,
    startX: 0,
    startY: 0,
    currentX: 0,
    currentY: 0,

    init() {
        this.bindTriggers();
        this.bindEvents();
    },

    bindTriggers() {
        const triggers = document.querySelectorAll('[data-doc-modal-trigger]');
        triggers.forEach((trigger) => {
            if (trigger.dataset.docBound === 'true') return;
            trigger.dataset.docBound = 'true';

            trigger.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();

                // Determine gallery group
                const galleryName = trigger.dataset.docGallery || 'default';
                const groupTriggers = Array.from(document.querySelectorAll(`[data-doc-modal-trigger][data-doc-gallery="${galleryName}"]`));
                
                let playlist = [];
                let activeIndex = 0;

                if (groupTriggers.length > 0) {
                    playlist = groupTriggers.map((el, idx) => {
                        if (el === trigger) activeIndex = idx;
                        return {
                            url: el.dataset.docUrl || el.getAttribute('href') || '',
                            caption: el.dataset.docCaption || el.getAttribute('alt') || el.getAttribute('title') || 'Dokumentasi Rapat',
                            agendaTitle: el.dataset.docAgenda || '',
                            date: el.dataset.docDate || '',
                            downloadUrl: el.dataset.docDownload || el.dataset.docUrl || el.getAttribute('href') || '',
                            fileType: el.dataset.docType || 'image'
                        };
                    });
                } else {
                    playlist = [{
                        url: trigger.dataset.docUrl || trigger.getAttribute('href') || '',
                        caption: trigger.dataset.docCaption || 'Dokumentasi Rapat',
                        agendaTitle: trigger.dataset.docAgenda || '',
                        date: trigger.dataset.docDate || '',
                        downloadUrl: trigger.dataset.docDownload || trigger.dataset.docUrl || trigger.getAttribute('href') || '',
                        fileType: trigger.dataset.docType || 'image'
                    }];
                    activeIndex = 0;
                }

                this.open({
                    items: playlist,
                    currentIndex: activeIndex
                });
            });
        });
    },

    bindEvents() {
        const stage = document.getElementById('doc-modal-stage');
        const img = document.getElementById('doc-modal-img');
        const modal = document.getElementById('documentation-preview-modal');

        if (stage && img) {
            img.addEventListener('pointerdown', (e) => {
                if (this.scale <= 1) return;
                this.isDragging = true;
                this.startX = e.clientX - this.panX;
                this.startY = e.clientY - this.panY;
                img.classList.remove('cursor-grab');
                img.classList.add('cursor-grabbing');
                if (img.setPointerCapture) img.setPointerCapture(e.pointerId);
                e.preventDefault();
            });

            window.addEventListener('pointermove', (e) => {
                if (!this.isDragging) return;
                this.panX = e.clientX - this.startX;
                this.panY = e.clientY - this.startY;
                this.applyTransform();
            });

            const stopDrag = (e) => {
                if (!this.isDragging) return;
                this.isDragging = false;
                if (img) {
                    img.classList.remove('cursor-grabbing');
                    img.classList.add('cursor-grab');
                }
            };
            window.addEventListener('pointerup', stopDrag);
            window.addEventListener('pointercancel', stopDrag);

            stage.addEventListener('wheel', (e) => {
                if (!this.isOpen) return;
                e.preventDefault();
                if (e.deltaY < 0) {
                    this.zoomIn();
                } else {
                    this.zoomOut();
                }
            }, { passive: false });
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close();
                }
            });
        }

        window.addEventListener('keydown', (e) => {
            if (!this.isOpen) return;

            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.prev();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.next();
            } else if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.zoomIn();
            } else if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.zoomOut();
            } else if (e.key === '0') {
                e.preventDefault();
                this.resetZoom();
            } else if (e.key === 'r' || e.key === 'R') {
                e.preventDefault();
                this.rotate();
            }
        });
    },

    open({ items = [], currentIndex = 0 }) {
        if (!items || items.length === 0) return;

        this.items = items;
        this.currentIndex = Math.max(0, Math.min(currentIndex, items.length - 1));
        this.isOpen = true;

        const modal = document.getElementById('documentation-preview-modal');
        const dialog = document.getElementById('doc-modal-dialog');
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        requestAnimationFrame(() => {
            if (dialog) {
                dialog.classList.remove('scale-95');
                dialog.classList.add('scale-100');
            }
        });

        this.reset();
        this.renderCurrentItem();
        modal.focus();
    },

    close() {
        const modal = document.getElementById('documentation-preview-modal');
        const dialog = document.getElementById('doc-modal-dialog');
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

            // Stop any playing video
            const video = document.getElementById('doc-modal-video');
            if (video) video.pause();
        }, 150);
    },

    normalizeUrl(rawUrl) {
        if (!rawUrl) return '';
        try {
            if (rawUrl.includes('/storage/')) {
                const currentPort = window.location.port;
                const urlObj = new URL(rawUrl, window.location.origin);
                if ((urlObj.hostname === 'localhost' || urlObj.hostname === '127.0.0.1') && urlObj.port !== currentPort) {
                    return urlObj.pathname + urlObj.search;
                }
            }
        } catch (e) {
            // keep rawUrl
        }
        return rawUrl;
    },

    renderCurrentItem() {
        const current = this.items[this.currentIndex];
        if (!current) return;

        const total = this.items.length;
        const normalizedUrl = this.normalizeUrl(current.url);

        // Update Counter
        const counterEl = document.getElementById('doc-modal-counter');
        if (counterEl) {
            counterEl.textContent = `Foto ${this.currentIndex + 1} dari ${total}`;
        }

        // Update Navigation buttons state
        const prevBtn = document.getElementById('doc-modal-prev-btn');
        const nextBtn = document.getElementById('doc-modal-next-btn');
        if (prevBtn) {
            prevBtn.disabled = total <= 1;
            prevBtn.style.display = total <= 1 ? 'none' : 'flex';
        }
        if (nextBtn) {
            nextBtn.disabled = total <= 1;
            nextBtn.style.display = total <= 1 ? 'none' : 'flex';
        }

        // Update Metadata
        const captionEl = document.getElementById('doc-modal-caption');
        if (captionEl) {
            captionEl.textContent = current.caption || 'Dokumentasi Rapat';
            captionEl.title = current.caption || '';
        }

        const agendaEl = document.getElementById('doc-modal-agenda-title');
        if (agendaEl) agendaEl.textContent = current.agendaTitle || '';

        const subtitleEl = document.getElementById('doc-modal-subtitle');
        if (subtitleEl && current.agendaTitle) subtitleEl.textContent = current.agendaTitle;

        const dateEl = document.getElementById('doc-modal-date');
        if (dateEl) dateEl.textContent = current.date || 'Tercatat di Sistem';

        // Update Links
        const tabBtn = document.getElementById('doc-modal-tab-btn');
        if (tabBtn) tabBtn.href = normalizedUrl;

        const downloadBtn = document.getElementById('doc-modal-download-btn');
        if (downloadBtn) {
            downloadBtn.href = this.normalizeUrl(current.downloadUrl || current.url);
        }

        // Reset transforms
        this.reset();

        // Handle Media Types
        const ext = (normalizedUrl.split('?')[0].split('.').pop() || '').toLowerCase();
        const isPdf = current.fileType === 'pdf' || ext === 'pdf';
        const isVideo = current.fileType === 'video' || ['mp4', 'webm', 'mov'].includes(ext);

        const imgEl = document.getElementById('doc-modal-img');
        const pdfContainer = document.getElementById('doc-modal-pdf-container');
        const pdfObject = document.getElementById('doc-modal-pdf-object');
        const pdfFallbackLink = document.getElementById('doc-modal-pdf-fallback-link');
        const videoContainer = document.getElementById('doc-modal-video-container');
        const videoEl = document.getElementById('doc-modal-video');
        const fallbackEl = document.getElementById('doc-modal-fallback');
        const fallbackLink = document.getElementById('doc-modal-fallback-link');

        // Hide all first
        if (imgEl) imgEl.classList.add('hidden');
        if (pdfContainer) pdfContainer.classList.add('hidden');
        if (videoContainer) videoContainer.classList.add('hidden');
        if (fallbackEl) fallbackEl.classList.add('hidden');

        if (isPdf) {
            if (pdfContainer && pdfObject) {
                pdfObject.data = normalizedUrl;
                if (pdfFallbackLink) pdfFallbackLink.href = normalizedUrl;
                pdfContainer.classList.remove('hidden');
            }
        } else if (isVideo) {
            if (videoContainer && videoEl) {
                videoEl.src = normalizedUrl;
                videoContainer.classList.remove('hidden');
            }
        } else {
            // Default Image
            if (imgEl) {
                imgEl.classList.remove('hidden');
                imgEl.src = normalizedUrl;
                imgEl.alt = current.caption || 'Dokumentasi Kegiatan';

                imgEl.onerror = () => {
                    imgEl.classList.add('hidden');
                    if (fallbackEl) {
                        fallbackEl.classList.remove('hidden');
                        fallbackEl.classList.add('flex');
                    }
                    if (fallbackLink) fallbackLink.href = normalizedUrl;
                };

                imgEl.onload = () => {
                    if (fallbackEl) fallbackEl.classList.add('hidden');
                };
            }
        }
    },

    next() {
        if (this.items.length <= 1) return;
        this.currentIndex = (this.currentIndex + 1) % this.items.length;
        this.renderCurrentItem();
    },

    prev() {
        if (this.items.length <= 1) return;
        this.currentIndex = (this.currentIndex - 1 + this.items.length) % this.items.length;
        this.renderCurrentItem();
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

    resetZoom() {
        this.scale = 1;
        this.panX = 0;
        this.panY = 0;
        this.applyTransform();
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
        const img = document.getElementById('doc-modal-img');
        const indicator = document.getElementById('doc-modal-scale-indicator');

        if (img) {
            img.style.transform = `translate(${this.panX}px, ${this.panY}px) scale(${this.scale}) rotate(${this.rotation}deg)`;
            if (this.scale > 1) {
                img.classList.remove('cursor-default');
                img.classList.add('cursor-grab');
            } else {
                img.classList.remove('cursor-grab', 'cursor-grabbing');
                img.classList.add('cursor-default');
            }
        }

        if (indicator) {
            indicator.textContent = `${Math.round(this.scale * 100)}%`;
        }
    }
};

// Initialize on DOM load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => window.documentationPreviewModal.init());
} else {
    window.documentationPreviewModal.init();
}
</script>
