{{--
    Partial: agendas.partials.surat_edaran_preview
    Komponen pratinjau langsung Surat Edaran / Undangan Rapat (PDF & Gambar)
    Mendukung Inline Viewer + Fullscreen Modal Lightbox + Kontrol Zoom & Rotasi Interaktif + Aksi Unduh & Tab Baru
--}}
@if(!($modalOnly ?? false))
<div class="panel">
    <div class="toolbar flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y1="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
            <h3 class="font-bold text-slate-900 text-sm">Surat Edaran / Undangan</h3>
        </div>
        @if($agenda->surat_edaran_path)
            @if($agenda->is_surat_edaran_pdf)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    PDF
                </span>
            @elseif($agenda->is_surat_edaran_image)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    GAMBAR
                </span>
            @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                    {{ $agenda->surat_edaran_extension ?? 'BERKAS' }}
                </span>
            @endif
        @endif
    </div>

    <div class="p-4 sm:p-5 space-y-4">
        @if($agenda->surat_edaran_path)
            {{-- Direct Inline Preview --}}
            @if($agenda->is_surat_edaran_pdf)
                <div class="relative rounded-xl border border-slate-300 bg-slate-900 overflow-hidden shadow-2xs group">
                    <object 
                        data="{{ $agenda->surat_edaran_url }}#toolbar=0&navpanes=0&view=Fit" 
                        type="application/pdf"
                        class="w-full h-72 sm:h-80 bg-white" 
                        title="Pratinjau Surat Edaran PDF - {{ $agenda->judul_rapat }}"
                    >
                        <iframe 
                            src="{{ $agenda->surat_edaran_url }}#toolbar=0&navpanes=0&view=Fit" 
                            class="w-full h-72 sm:h-80 bg-white" 
                            title="Pratinjau Surat Edaran PDF - {{ $agenda->judul_rapat }}"
                            loading="lazy"
                        >
                            <div class="w-full h-72 sm:h-80 flex flex-col items-center justify-center p-6 text-center bg-slate-50">
                                <div class="w-12 h-12 rounded-xl bg-rose-600 text-white flex items-center justify-center mx-auto mb-2 shadow-xs">
                                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div class="font-bold text-slate-900 text-xs mb-1">Dokumen Surat Edaran (PDF)</div>
                                <p class="text-[11px] text-slate-500 max-w-xs mx-auto mb-3">Klik tombol di bawah untuk membaca pratinjau penuh atau mengunduh berkas.</p>
                                <button 
                                    type="button" 
                                    onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                                    class="button small secondary text-xs font-bold"
                                >
                                    Buka Pratinjau
                                </button>
                            </div>
                        </iframe>
                    </object>
                    <div class="absolute top-2.5 right-2.5 opacity-90 group-hover:opacity-100 transition-opacity">
                        <button 
                            type="button" 
                            onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                            class="bg-slate-950/80 hover:bg-slate-900 text-white p-2 rounded-lg backdrop-blur-xs shadow-md transition flex items-center justify-center min-w-[36px] min-h-[36px] cursor-pointer"
                            title="Buka pratinjau layar penuh"
                            aria-label="Buka pratinjau layar penuh"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                        </button>
                    </div>
                </div>
            @elseif($agenda->is_surat_edaran_image)
                <div 
                    class="group relative rounded-xl border border-slate-300 bg-slate-100 overflow-hidden flex items-center justify-center p-2 min-h-[16rem] sm:min-h-[18rem] cursor-pointer"
                    onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')"
                    role="button"
                    tabindex="0"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}');}"
                    aria-label="Perbesar pratinjau gambar surat edaran"
                >
                    <img 
                        src="{{ $agenda->surat_edaran_url }}" 
                        alt="Pratinjau Surat Edaran - {{ $agenda->judul_rapat }}" 
                        class="max-h-72 w-auto object-contain rounded-lg transition-transform duration-200 group-hover:scale-[1.02]"
                        loading="lazy"
                    />
                    <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                        <span class="bg-slate-900/90 text-white text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-md backdrop-blur-xs">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                            Klik untuk perbesar
                        </span>
                    </div>
                </div>
            @else
                <div class="text-center p-5 bg-slate-50 border border-slate-300 rounded-xl space-y-2">
                    <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center mx-auto">
                        <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    </div>
                    <div class="text-xs font-bold text-slate-900">Berkas Terlampir</div>
                    <div class="text-[11px] text-slate-600 font-medium">Format berkas: .{{ $agenda->surat_edaran_extension }}</div>
                </div>
            @endif

            {{-- 2-Tier Proportional Action Buttons --}}
            <div class="space-y-2 pt-1">
                <!-- Tier 1: Primary Action (Full Width) -->
                <button 
                    type="button" 
                    onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                    class="w-full min-h-[44px] py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center justify-center gap-2 shadow-xs transition cursor-pointer"
                    title="Buka pratinjau dokumen dalam ukuran layar penuh"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                    <span>Pratinjau Layar Penuh</span>
                </button>

                <!-- Tier 2: Secondary Actions (Balanced 50:50 Grid) -->
                <div class="grid grid-cols-2 gap-2">
                    <a 
                        href="{{ $agenda->surat_edaran_url }}" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="w-full min-h-[44px] py-2 px-3 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold text-xs flex items-center justify-center gap-1.5 transition text-center shadow-2xs"
                        title="Buka dokumen di tab baru browser"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Tab Baru</span>
                    </a>

                    <a 
                        href="{{ $agenda->surat_edaran_url }}" 
                        download 
                        class="w-full min-h-[44px] py-2 px-3 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 font-bold text-xs flex items-center justify-center gap-1.5 transition text-center shadow-2xs"
                        title="Unduh berkas ke perangkat"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span>Unduh File</span>
                    </a>
                </div>
            </div>
        @else
            <div class="text-center py-6 px-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl space-y-2">
                <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
                </div>
                <div class="text-xs text-slate-500 font-medium">
                    Tidak ada berkas surat edaran atau undangan terlampir.
                </div>
            </div>
        @endif
    </div>
</div>
@endif

@if($agenda->surat_edaran_path)
    <!-- Fullscreen Modal Lightbox Dialog -->
    <div 
        id="modal-surat-preview-{{ $agenda->id }}" 
        class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-2 sm:p-4 md:p-6 overflow-y-auto transition-opacity"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-surat-title-{{ $agenda->id }}"
    >
        <div class="bg-white border border-slate-300 rounded-2xl max-w-6xl w-full h-[90vh] sm:h-[94vh] max-h-[calc(100dvh-1.5rem)] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150" style="height: min(94vh, calc(100dvh - 1.5rem)); max-height: calc(100dvh - 1.5rem);">
            <!-- Modal Header -->
            <div class="px-4 sm:px-5 py-3 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                    <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y1="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 id="modal-surat-title-{{ $agenda->id }}" class="text-sm font-bold text-slate-900 truncate">
                                Surat Edaran / Undangan Rapat
                            </h3>
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $agenda->is_surat_edaran_pdf ? 'bg-rose-100 text-rose-800' : ($agenda->is_surat_edaran_image ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800') }}">
                                {{ $agenda->surat_edaran_extension ?? 'BERKAS' }}
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-500 font-medium truncate">
                            {{ $agenda->judul_rapat }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <a 
                        href="{{ $agenda->surat_edaran_url }}" 
                        target="_blank" 
                        rel="noopener noreferrer" 
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-100 transition min-h-[44px]"
                        title="Buka dokumen di tab baru"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        <span>Tab Baru</span>
                    </a>

                    <a 
                        href="{{ $agenda->surat_edaran_url }}" 
                        download 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-semibold hover:bg-slate-100 transition min-h-[44px]"
                        title="Unduh berkas surat"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        <span class="hidden sm:inline">Unduh</span>
                    </a>

                    <button 
                        type="button" 
                        onclick="closeSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                        class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition cursor-pointer flex items-center justify-center min-w-[44px] min-h-[44px]" 
                        aria-label="Tutup pratinjau"
                    >
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
            </div>

            <!-- Modal Content / Document Viewer Frame (Interactive Reader) -->
            <div class="relative bg-slate-950 flex-1 overflow-hidden flex items-center justify-center w-full h-full min-h-0" style="flex: 1 1 0%; min-height: 0; height: 100%;">
                @if($agenda->is_surat_edaran_pdf)
                    <div class="w-full h-full flex flex-col items-center justify-center">
                        <object 
                            data="{{ $agenda->surat_edaran_url }}#toolbar=1&navpanes=0&view=FitH" 
                            type="application/pdf"
                            class="w-full h-full bg-white"
                            title="Pratinjau Layar Penuh Surat Edaran - {{ $agenda->judul_rapat }}"
                        >
                            <div class="w-full h-full flex flex-col items-center justify-center p-6 text-center bg-slate-100">
                                <div class="w-14 h-14 rounded-2xl bg-rose-600 text-white flex items-center justify-center mx-auto mb-3 shadow-sm">
                                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <div class="font-bold text-slate-900 text-sm mb-1">Pratinjau PDF Terlampir</div>
                                <p class="text-xs text-slate-600 max-w-sm mx-auto mb-4">Peramban Anda memerlukan tab baru atau aplikasi eksternal untuk menampilkan dokumen PDF ini secara utuh.</p>
                                <div class="flex items-center gap-2">
                                    <a href="{{ $agenda->surat_edaran_url }}" target="_blank" rel="noopener noreferrer" class="button small text-xs font-bold px-4 py-2 bg-slate-900 text-white rounded-xl">Buka di Tab Baru</a>
                                    <a href="{{ $agenda->surat_edaran_url }}" download class="button small secondary text-xs font-bold px-4 py-2 rounded-xl">Unduh PDF</a>
                                </div>
                            </div>
                        </object>
                    </div>
                @elseif($agenda->is_surat_edaran_image)
                    {{-- Interactive Image Viewer with Pan & Zoom --}}
                    <div 
                        id="image-viewer-viewport-{{ $agenda->id }}" 
                        class="relative w-full h-full overflow-hidden flex items-center justify-center select-none cursor-grab active:cursor-grabbing"
                    >
                        <img 
                            id="image-viewer-img-{{ $agenda->id }}" 
                            src="{{ $agenda->surat_edaran_url }}" 
                            alt="Surat Edaran {{ $agenda->judul_rapat }}" 
                            class="max-w-[92%] max-h-[82vh] object-contain rounded-lg shadow-2xl bg-white transition-transform duration-75 ease-out origin-center pointer-events-none"
                            draggable="false"
                        />
                    </div>

                    {{-- Floating Gov-Tech Image Controls Toolbar --}}
                    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-1.5 px-3 py-1.5 bg-slate-900/90 text-white rounded-full border border-slate-700 shadow-xl backdrop-blur-md">
                        <button 
                            type="button" 
                            onclick="suratEdaranImageViewer.zoomOut({{ $agenda->id }})" 
                            class="p-2 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[36px] min-h-[36px] cursor-pointer"
                            title="Perkecil (-)"
                            aria-label="Perkecil (-)"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>

                        <button 
                            type="button" 
                            onclick="suratEdaranImageViewer.reset({{ $agenda->id }})" 
                            class="px-2.5 py-1 rounded-full hover:bg-white/20 font-mono text-xs font-bold text-white transition flex items-center justify-center min-h-[36px] cursor-pointer"
                            title="Reset Skala 100% (0)"
                            aria-label="Reset Skala 100%"
                        >
                            <span id="zoom-level-{{ $agenda->id }}">100%</span>
                        </button>

                        <button 
                            type="button" 
                            onclick="suratEdaranImageViewer.zoomIn({{ $agenda->id }})" 
                            class="p-2 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[36px] min-h-[36px] cursor-pointer"
                            title="Perbesar (+)"
                            aria-label="Perbesar (+)"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        </button>

                        <div class="h-4 w-px bg-slate-700 mx-1"></div>

                        <button 
                            type="button" 
                            onclick="suratEdaranImageViewer.rotate({{ $agenda->id }})" 
                            class="p-2 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[36px] min-h-[36px] cursor-pointer"
                            title="Putar 90° Searah Jarum Jam (R)"
                            aria-label="Putar 90°"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                        </button>

                        <button 
                            type="button" 
                            onclick="suratEdaranImageViewer.fitWidth({{ $agenda->id }})" 
                            class="p-2 rounded-full hover:bg-white/20 text-slate-200 hover:text-white transition flex items-center justify-center min-w-[36px] min-h-[36px] cursor-pointer"
                            title="Sesuaikan Lebar"
                            aria-label="Sesuaikan Lebar"
                        >
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                        </button>
                    </div>
                @else
                    <div class="text-center p-8 bg-white rounded-xl border border-slate-300 shadow-sm space-y-3">
                        <div class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center mx-auto">
                            <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg>
                        </div>
                        <div class="font-bold text-slate-900 text-sm">Dokumen Terlampir (.{{ $agenda->surat_edaran_extension }})</div>
                        <p class="text-xs text-slate-500 max-w-sm mx-auto">Pratinjau langsung tidak tersedia untuk format berkas ini. Silakan unduh atau buka berkas melalui tautan.</p>
                        <a 
                            href="{{ $agenda->surat_edaran_url }}" 
                            target="_blank" 
                            class="button small bg-slate-900 text-white inline-flex items-center gap-2 text-xs font-bold px-4 py-2 rounded-xl"
                        >
                            <span>Buka Berkas</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- Modal Footer -->
            <div class="px-4 sm:px-5 py-2.5 border-t border-slate-200 bg-white flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3 text-[11px] text-slate-500 font-medium">
                    <span>Format: <strong class="uppercase text-slate-700">{{ $agenda->surat_edaran_extension ?? 'PDF' }}</strong></span>
                    @if($agenda->is_surat_edaran_image)
                        <span class="hidden md:inline-block text-slate-400">&bull;</span>
                        <span class="hidden md:inline-block text-slate-500">Tip: Gunakan scroll mouse atau seret gambar saat diperbesar</span>
                    @endif
                </div>
                <button 
                    type="button" 
                    onclick="closeSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                    class="button small secondary bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-1.5 rounded-xl text-xs min-h-[40px] cursor-pointer"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

    @once
    <script>
        function openSuratEdaranModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                
                // Extract agenda ID from modal ID (modal-surat-preview-{id})
                const parts = modalId.split('modal-surat-preview-');
                if (parts.length > 1 && window.suratEdaranImageViewer) {
                    const agendaId = parts[1];
                    window.suratEdaranImageViewer.init(agendaId);
                }
            }
        }

        function closeSuratEdaranModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');

                const parts = modalId.split('modal-surat-preview-');
                if (parts.length > 1 && window.suratEdaranImageViewer) {
                    const agendaId = parts[1];
                    window.suratEdaranImageViewer.reset(agendaId);
                }
            }
        }

        // Global Lightweight Image Viewer Controller (Vanilla JS, Zero Bloat)
        window.suratEdaranImageViewer = {
            state: {},
            init(agendaId) {
                const img = document.getElementById('image-viewer-img-' + agendaId);
                const viewport = document.getElementById('image-viewer-viewport-' + agendaId);
                if (!img || !viewport) return;

                if (!this.state[agendaId]) {
                    this.state[agendaId] = {
                        scale: 1,
                        rotation: 0,
                        panX: 0,
                        panY: 0,
                        isDragging: false,
                        startX: 0,
                        startY: 0,
                        initializedEvents: false
                    };
                }

                this.applyTransform(agendaId);

                if (!this.state[agendaId].initializedEvents) {
                    this.state[agendaId].initializedEvents = true;

                    // Mouse Drag Pan Events
                    viewport.addEventListener('mousedown', (e) => {
                        const s = this.state[agendaId];
                        if (s.scale > 1) {
                            s.isDragging = true;
                            s.startX = e.clientX - s.panX;
                            s.startY = e.clientY - s.panY;
                            viewport.style.cursor = 'grabbing';
                        }
                    });

                    window.addEventListener('mousemove', (e) => {
                        const s = this.state[agendaId];
                        if (s && s.isDragging) {
                            s.panX = e.clientX - s.startX;
                            s.panY = e.clientY - s.startY;
                            this.applyTransform(agendaId);
                        }
                    });

                    window.addEventListener('mouseup', () => {
                        const s = this.state[agendaId];
                        if (s && s.isDragging) {
                            s.isDragging = false;
                            if (viewport) viewport.style.cursor = s.scale > 1 ? 'grab' : 'default';
                        }
                    });

                    // Touch Pan Events (Mobile & Tablet)
                    viewport.addEventListener('touchstart', (e) => {
                        const s = this.state[agendaId];
                        if (s.scale > 1 && e.touches.length === 1) {
                            s.isDragging = true;
                            s.startX = e.touches[0].clientX - s.panX;
                            s.startY = e.touches[0].clientY - s.panY;
                        }
                    }, { passive: true });

                    viewport.addEventListener('touchmove', (e) => {
                        const s = this.state[agendaId];
                        if (s && s.isDragging && e.touches.length === 1) {
                            s.panX = e.touches[0].clientX - s.startX;
                            s.panY = e.touches[0].clientY - s.startY;
                            this.applyTransform(agendaId);
                        }
                    }, { passive: true });

                    viewport.addEventListener('touchend', () => {
                        const s = this.state[agendaId];
                        if (s) s.isDragging = false;
                    });

                    // Wheel Zoom (Ctrl+Wheel or Standard Wheel)
                    viewport.addEventListener('wheel', (e) => {
                        e.preventDefault();
                        if (e.deltaY < 0) {
                            this.zoomIn(agendaId);
                        } else {
                            this.zoomOut(agendaId);
                        }
                    }, { passive: false });

                    // Double click toggle zoom
                    viewport.addEventListener('dblclick', (e) => {
                        e.preventDefault();
                        const s = this.state[agendaId];
                        if (s.scale > 1) {
                            this.reset(agendaId);
                        } else {
                            s.scale = 2;
                            this.applyTransform(agendaId);
                        }
                    });
                }
            },
            applyTransform(agendaId) {
                const s = this.state[agendaId];
                const img = document.getElementById('image-viewer-img-' + agendaId);
                const zoomEl = document.getElementById('zoom-level-' + agendaId);
                const viewport = document.getElementById('image-viewer-viewport-' + agendaId);
                if (!s || !img) return;

                if (s.scale <= 1) {
                    s.panX = 0;
                    s.panY = 0;
                    if (viewport) viewport.style.cursor = 'default';
                } else {
                    if (viewport && !s.isDragging) viewport.style.cursor = 'grab';
                }

                img.style.transform = `translate(${s.panX}px, ${s.panY}px) scale(${s.scale}) rotate(${s.rotation}deg)`;
                if (zoomEl) {
                    zoomEl.textContent = Math.round(s.scale * 100) + '%';
                }
            },
            zoomIn(agendaId) {
                const s = this.state[agendaId];
                if (!s) return;
                if (s.scale < 4) {
                    s.scale = Math.min(4, Math.round((s.scale + 0.25) * 100) / 100);
                    this.applyTransform(agendaId);
                }
            },
            zoomOut(agendaId) {
                const s = this.state[agendaId];
                if (!s) return;
                if (s.scale > 0.5) {
                    s.scale = Math.max(0.5, Math.round((s.scale - 0.25) * 100) / 100);
                    this.applyTransform(agendaId);
                }
            },
            reset(agendaId) {
                const s = this.state[agendaId];
                if (!s) return;
                s.scale = 1;
                s.rotation = 0;
                s.panX = 0;
                s.panY = 0;
                this.applyTransform(agendaId);
            },
            rotate(agendaId) {
                const s = this.state[agendaId];
                if (!s) return;
                s.rotation = (s.rotation + 90) % 360;
                this.applyTransform(agendaId);
            },
            fitWidth(agendaId) {
                const s = this.state[agendaId];
                if (!s) return;
                s.scale = 1.75;
                s.panX = 0;
                s.panY = 0;
                this.applyTransform(agendaId);
            }
        };

        // Keyboard navigation for active modal
        document.addEventListener('keydown', function(e) {
            const activeModals = document.querySelectorAll('[id^="modal-surat-preview-"]:not(.hidden)');
            if (activeModals.length === 0) return;

            const activeModal = activeModals[0];
            const parts = activeModal.id.split('modal-surat-preview-');
            const agendaId = parts[1];

            if (e.key === 'Escape') {
                activeModals.forEach(m => closeSuratEdaranModal(m.id));
                return;
            }

            if (window.suratEdaranImageViewer && agendaId) {
                if (e.key === '+' || e.key === '=') {
                    window.suratEdaranImageViewer.zoomIn(agendaId);
                } else if (e.key === '-' || e.key === '_') {
                    window.suratEdaranImageViewer.zoomOut(agendaId);
                } else if (e.key === '0') {
                    window.suratEdaranImageViewer.reset(agendaId);
                } else if (e.key === 'r' || e.key === 'R') {
                    window.suratEdaranImageViewer.rotate(agendaId);
                }
            }
        });

        document.addEventListener('click', function(e) {
            if (e.target && e.target.id && e.target.id.startsWith('modal-surat-preview-')) {
                closeSuratEdaranModal(e.target.id);
            }
        });
    </script>
    @endonce
@endif
