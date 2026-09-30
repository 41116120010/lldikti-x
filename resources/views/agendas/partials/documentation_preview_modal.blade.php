{{-- ========================================================================= --}}
{{-- MODAL LIGHTBOX PRATINJAU DOKUMENTASI KEGIATAN RAPAT (ENTERPRISE GOV-TECH) --}}
{{-- Standardized Activity Documentation Viewer: Zoom, Rotate, Pan, Carousel  --}}
{{-- ========================================================================= --}}

<div 
    id="documentation-preview-modal" 
    class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-4 md:p-6 transition-opacity"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="doc-modal-title"
    aria-describedby="doc-modal-caption"
>
    <div 
        id="doc-modal-dialog"
        class="relative bg-white border border-slate-300 rounded-2xl max-w-4xl w-full shadow-2xl overflow-hidden flex flex-col my-auto max-h-[calc(100dvh-1.5rem)] transition-transform duration-200 transform scale-95"
    >
        {{-- 1. Modal Header --}}
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-xs">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <circle cx="9" cy="9" r="2"/>
                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h3 id="doc-modal-title" class="font-extrabold text-slate-950 text-sm sm:text-base leading-tight">
                            Pratinjau Dokumentasi Kegiatan
                        </h3>
                        <span id="doc-modal-counter" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-slate-200/80 text-slate-800 border border-slate-300">
                            Foto 1 dari 1
                        </span>
                    </div>
                    <p id="doc-modal-subtitle" class="text-xs text-slate-500 font-medium truncate max-w-xs sm:max-w-md mt-0.5">
                        Dokumentasi Resmi LLDIKTI Wilayah X
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                <button 
                    type="button" 
                    id="doc-modal-close-btn"
                    onclick="window.documentationPreviewModal.close()" 
                    class="w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition cursor-pointer"
                    aria-label="Tutup pratinjau (Escape)"
                    title="Tutup (Esc)"
                >
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        {{-- 2. Interactive Viewport Stage --}}
        <div 
            id="doc-modal-stage"
            class="relative bg-slate-950 flex items-center justify-center overflow-hidden min-h-[260px] sm:min-h-[380px] max-h-[58vh] select-none"
        >
            {{-- Tombol Navigasi Prev (Foto Sebelumnya) --}}
            <button 
                type="button" 
                id="doc-modal-prev-btn"
                onclick="window.documentationPreviewModal.prev()"
                class="absolute left-3 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-slate-900 border border-white/20 text-white flex items-center justify-center shadow-lg transition cursor-pointer backdrop-blur-xs disabled:opacity-20 disabled:cursor-not-allowed group"
                aria-label="Foto sebelumnya (Panah Kiri)"
                title="Foto Sebelumnya (←)"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:-translate-x-0.5 transition-transform">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>

            {{-- Tombol Navigasi Next (Foto Selanjutnya) --}}
            <button 
                type="button" 
                id="doc-modal-next-btn"
                onclick="window.documentationPreviewModal.next()"
                class="absolute right-3 top-1/2 -translate-y-1/2 z-20 w-11 h-11 rounded-full bg-slate-900/80 hover:bg-slate-900 border border-white/20 text-white flex items-center justify-center shadow-lg transition cursor-pointer backdrop-blur-xs disabled:opacity-20 disabled:cursor-not-allowed group"
                aria-label="Foto selanjutnya (Panah Kanan)"
                title="Foto Selanjutnya (→)"
            >
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="group-hover:translate-x-0.5 transition-transform">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </button>

            {{-- Floating Toolbar Kontrol (Zoom, Rotate, Reset) --}}
            <div class="absolute bottom-3 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1 sm:gap-1.5 bg-slate-900/90 border border-slate-700/80 rounded-full px-2.5 sm:px-3 py-1.5 shadow-2xl backdrop-blur-md">
                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.zoomOut()"
                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    aria-label="Perkecil zoom (-)"
                    title="Perkecil (-)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <button 
                    type="button" 
                    id="doc-modal-scale-indicator"
                    onclick="window.documentationPreviewModal.resetZoom()"
                    class="px-2 py-0.5 text-xs font-mono font-bold text-slate-200 hover:text-white hover:bg-slate-800 rounded transition cursor-pointer select-none"
                    title="Klik untuk reset zoom (0)"
                >
                    100%
                </button>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.zoomIn()"
                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    aria-label="Perbesar zoom (+)"
                    title="Perbesar (+)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>

                <div class="h-4 w-px bg-slate-700/80 mx-0.5"></div>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.rotate()"
                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    aria-label="Putar orientasi 90 derajat (r)"
                    title="Putar 90° (r)"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                </button>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.reset()"
                    class="w-7 h-7 sm:w-8 sm:h-8 flex items-center justify-center rounded-full text-slate-300 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                    aria-label="Reset ukuran dan orientasi"
                    title="Sesuaikan ke Layar"
                >
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                </button>
            </div>

            {{-- Elemen Gambar Dokumentasi --}}
            <img 
                id="doc-modal-img" 
                src="" 
                alt="Dokumentasi Kegiatan" 
                class="max-h-[56vh] max-w-full object-contain transition-transform duration-75 cursor-grab active:cursor-grabbing will-change-transform"
                draggable="false"
            >

            {{-- Container Fallback untuk Media Non-Gambar (PDF / Video / Berkas Lain) --}}
            <div id="doc-modal-pdf-container" class="hidden w-full h-[56vh] flex items-center justify-center bg-slate-900">
                <object id="doc-modal-pdf-object" data="" type="application/pdf" class="w-full h-full">
                    <div class="text-center text-white p-6">
                        <p class="text-sm font-semibold mb-2">Pratinjau PDF tidak didukung peramban ini.</p>
                        <a id="doc-modal-pdf-fallback-link" href="#" target="_blank" class="button text-xs bg-white text-slate-900 font-bold">Buka Berkas PDF</a>
                    </div>
                </object>
            </div>

            <div id="doc-modal-video-container" class="hidden w-full max-h-[56vh] flex items-center justify-center bg-slate-950 p-4">
                <video id="doc-modal-video" controls class="max-h-[52vh] max-w-full rounded-lg shadow-lg">
                    <source src="" type="video/mp4">
                    Peramban Anda tidak mendukung pemutaran video.
                </video>
            </div>

            {{-- Fallback Card jika Gambar Gagal Dimuat --}}
            <div id="doc-modal-fallback" class="hidden flex-col items-center justify-center p-8 text-center bg-slate-900 text-white rounded-xl max-w-md mx-auto my-6 border border-slate-800">
                <div class="w-14 h-14 rounded-2xl bg-slate-800 text-slate-400 flex items-center justify-center mb-3">
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-slate-200 mb-1">Foto Sedang Tidak Tersedia</h4>
                <p class="text-xs text-slate-400 leading-relaxed mb-4">
                    Berkas foto fisik sedang tidak dapat diakses atau berada di penyimpanan terpisah.
                </p>
                <div class="flex items-center gap-2">
                    <a id="doc-modal-fallback-link" href="#" target="_blank" class="button text-xs font-semibold bg-white hover:bg-slate-100 text-slate-900">
                        Coba Buka Tautan Asli
                    </a>
                </div>
            </div>
        </div>

        {{-- 3. Metadata & Caption Card --}}
        <div class="p-4 sm:p-5 border-t border-slate-200 bg-white">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                        Keterangan Foto Kegiatan
                    </div>
                    <div id="doc-modal-caption" class="text-xs sm:text-sm font-bold text-slate-900 leading-snug break-words">
                        Dokumentasi Rapat
                    </div>
                    <div id="doc-modal-agenda-title" class="text-[11px] text-slate-500 font-medium mt-1 truncate">
                        -
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl flex flex-col justify-between">
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">
                            Informasi Berkas
                        </div>
                        <div id="doc-modal-date" class="text-xs font-mono font-semibold text-slate-800">
                            -
                        </div>
                    </div>
                    <div id="doc-modal-type-badge" class="mt-2 inline-flex items-center gap-1.5 text-[11px] font-medium text-emerald-700">
                        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        <span>Arsip Kegiatan Sah</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- 4. Footer & Action Buttons --}}
        <div class="px-5 py-3 border-t border-slate-200 bg-slate-50 flex flex-wrap items-center justify-between gap-3 shrink-0">
            <div class="hidden sm:flex items-center gap-2 text-xs text-slate-500">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-slate-400">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <span>Navigasi: Gunakan tombol panah keyboard <strong class="text-slate-700">← / →</strong> atau seret mouse saat diperbesar.</span>
            </div>

            <div class="flex items-center gap-2 ml-auto w-full sm:w-auto justify-end">
                <a 
                    id="doc-modal-tab-btn" 
                    href="#" 
                    target="_blank" 
                    class="button text-xs font-semibold border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 transition"
                    title="Buka gambar di tab baru"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                        <polyline points="15 3 21 3 21 9"></polyline>
                        <line x1="10" y1="14" x2="21" y2="3"></line>
                    </svg>
                    <span>Tab Baru</span>
                </a>

                <a 
                    id="doc-modal-download-btn" 
                    href="#" 
                    download 
                    class="button text-xs font-semibold border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 transition"
                    title="Unduh berkas foto"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Unduh</span>
                </a>

                <button 
                    type="button" 
                    onclick="window.documentationPreviewModal.close()" 
                    class="button text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white transition cursor-pointer"
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
        if (captionEl) captionEl.textContent = current.caption || 'Dokumentasi Rapat';

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
