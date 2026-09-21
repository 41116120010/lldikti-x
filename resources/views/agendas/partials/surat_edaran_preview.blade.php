{{--
    Partial: agendas.partials.surat_edaran_preview
    Komponen pratinjau langsung Surat Edaran / Undangan Rapat (PDF & Gambar)
    Mendukung Inline Viewer + Fullscreen Modal Lightbox + Aksi Unduh & Tab Baru
--}}
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
                <div class="relative rounded-xl border border-slate-300 bg-slate-900 overflow-hidden shadow-2xs">
                    <iframe 
                        src="{{ $agenda->surat_edaran_url }}#toolbar=0&navpanes=0&view=Fit" 
                        class="w-full h-72 sm:h-80 bg-white" 
                        title="Pratinjau Surat Edaran PDF - {{ $agenda->judul_rapat }}"
                        loading="lazy"
                    ></iframe>
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

@if($agenda->surat_edaran_path)
    <!-- Fullscreen Modal Lightbox Dialog -->
    <div 
        id="modal-surat-preview-{{ $agenda->id }}" 
        class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-950/80 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto transition-opacity"
        tabindex="-1"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-surat-title-{{ $agenda->id }}"
    >
        <div class="bg-white border border-slate-300 rounded-2xl max-w-5xl w-full h-[90vh] sm:h-[92vh] max-h-[calc(100dvh-2rem)] shadow-2xl overflow-hidden flex flex-col animate-in fade-in zoom-in-95 duration-150" style="height: min(90vh, calc(100dvh - 1.5rem)); max-height: calc(100dvh - 1.5rem);">
            <!-- Modal Header -->
            <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                    <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y1="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    </div>
                    <div class="min-w-0">
                        <h3 id="modal-surat-title-{{ $agenda->id }}" class="text-sm font-bold text-slate-900 truncate">
                            Surat Edaran / Undangan Rapat
                        </h3>
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

            <!-- Modal Content / Document Viewer Frame (Centered Dark Mode Reader) -->
            <div class="p-2 sm:p-4 bg-slate-900 flex-1 overflow-hidden flex items-center justify-center w-full h-full min-h-0" style="flex: 1 1 0%; min-height: 0; height: 100%;">
                @if($agenda->is_surat_edaran_pdf)
                    <div class="w-full h-full max-w-4xl mx-auto flex items-center justify-center">
                        <iframe 
                            src="{{ $agenda->surat_edaran_url }}#toolbar=1&navpanes=0&view=FitH" 
                            class="w-full h-full rounded-xl border border-slate-700 bg-white shadow-2xl"
                            title="Pratinjau Layar Penuh Surat Edaran - {{ $agenda->judul_rapat }}"
                        ></iframe>
                    </div>
                @elseif($agenda->is_surat_edaran_image)
                    <div class="w-full h-full max-w-4xl mx-auto overflow-auto flex items-center justify-center p-2">
                        <img 
                            src="{{ $agenda->surat_edaran_url }}" 
                            alt="Surat Edaran {{ $agenda->judul_rapat }}" 
                            class="max-w-full max-h-[78vh] object-contain rounded-xl shadow-2xl bg-white"
                        />
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
            <div class="px-5 py-3 border-t border-slate-200 bg-white flex items-center justify-between shrink-0">
                <div class="text-[11px] text-slate-500 font-medium">
                    Format: <span class="uppercase font-bold text-slate-700">{{ $agenda->surat_edaran_extension ?? 'PDF' }}</span>
                </div>
                <button 
                    type="button" 
                    onclick="closeSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                    class="button small secondary bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-4 py-2 rounded-xl text-xs min-h-[44px] cursor-pointer"
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
            }
        }

        function closeSuratEdaranModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const activeModals = document.querySelectorAll('[id^="modal-surat-preview-"]:not(.hidden)');
                activeModals.forEach(m => {
                    m.classList.add('hidden');
                });
                if (activeModals.length > 0) {
                    document.body.classList.remove('overflow-hidden');
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
