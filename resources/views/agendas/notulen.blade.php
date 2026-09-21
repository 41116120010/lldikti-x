@extends('layouts.app')

@section('title', 'Notulensi & Berita Acara — ' . $agenda->judul_rapat)
@section('heading', 'Pengolah Kata Notulensi & Berita Acara')
@section('subtitle', 'Agenda: ' . $agenda->judul_rapat)

@section('content')
<div id="office-workstation" class="space-y-4">
    <!-- 1. Workstation Top Control Bar -->
    <div class="bg-white border border-slate-300 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <!-- Left: Back Navigation -->
        <div class="flex items-center gap-3">
            <a 
                href="{{ auth()->user()->isPegawai() ? route('agendas.show', $agenda) : route('admin.agendas.show', $agenda) }}" 
                class="button secondary text-xs font-bold inline-flex items-center gap-1.5"
                title="Kembali ke halaman rincian agenda rapat"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                <span>Kembali ke Detail Agenda</span>
            </a>
            <div class="hidden lg:block h-6 w-px bg-slate-200"></div>
            <div class="hidden lg:block text-xs font-medium text-slate-600 truncate max-w-xs">
                <span class="font-bold text-slate-900 block truncate">{{ $agenda->judul_rapat }}</span>
                <span>{{ $agenda->waktu_mulai->translatedFormat('d M Y') }} &bull; {{ ucfirst($agenda->tipe_rapat) }}</span>
            </div>
        </div>

        <!-- Center: Paper Size Switcher & Zoom Controls -->
        <div class="flex items-center gap-2">
            <!-- Paper Size Selector (A4 vs F4) -->
            <div class="inline-flex p-0.5 bg-slate-100 rounded-xl border border-slate-300 shadow-2xs" role="group" aria-label="Pilih Ukuran Kertas">
                <button 
                    type="button" 
                    data-paper-size="a4" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1.5 bg-slate-900 text-white shadow-xs"
                    title="Beralih ke format lembar kertas A4 (210 × 297 mm)"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="20" x="4" y="2" rx="2"/></svg>
                    <span>A4</span>
                </button>
                <button 
                    type="button" 
                    data-paper-size="f4" 
                    class="px-3 py-1.5 text-xs font-bold rounded-lg transition cursor-pointer flex items-center gap-1.5 text-slate-700 hover:bg-slate-200"
                    title="Beralih ke format lembar kertas F4 / Folio (215 × 330 mm)"
                >
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2"><rect width="16" height="22" x="4" y="1" rx="2"/></svg>
                    <span>F4 / Folio</span>
                </button>
            </div>

            <!-- Zoom Controls -->
            <div class="hidden sm:inline-flex items-center gap-1 bg-slate-100 p-0.5 rounded-xl border border-slate-300" role="group" aria-label="Skala Lembar Kerja">
                <button type="button" data-zoom="75" class="px-2 py-1 text-[11px] font-bold rounded-md text-slate-700 hover:bg-slate-200 transition cursor-pointer">75%</button>
                <button type="button" data-zoom="90" class="px-2 py-1 text-[11px] font-bold rounded-md text-slate-700 hover:bg-slate-200 transition cursor-pointer">90%</button>
                <button type="button" data-zoom="100" class="px-2 py-1 text-[11px] font-bold rounded-md active-zoom bg-slate-900 text-white transition cursor-pointer">100%</button>
                <button type="button" data-zoom="125" class="px-2 py-1 text-[11px] font-bold rounded-md text-slate-700 hover:bg-slate-200 transition cursor-pointer">125%</button>
                <button type="button" data-zoom="fit" class="px-2 py-1 text-[11px] font-bold rounded-md text-slate-700 hover:bg-slate-200 transition cursor-pointer">Pas Layar</button>
            </div>
        </div>

        <!-- Right: Actions (Penyesuaian Dokumen, Ekspor Cepat, & Simpan) -->
        <div class="flex items-center gap-2">
            <!-- Penyesuaian Dokumen Button -->
            <button 
                type="button" 
                onclick="openDocumentConfigModal()" 
                class="button secondary flex items-center gap-1.5 text-xs font-bold shadow-2xs cursor-pointer border-slate-300 hover:bg-slate-100 text-slate-800"
                title="Buka pengaturan kop surat, nomor surat, identitas rapat, dan pejabat penandatangan"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" x2="20" y1="21" y2="21"></line>
                    <line x1="4" x2="20" y1="14" y2="14"></line>
                    <line x1="4" x2="20" y1="7" y2="7"></line>
                    <circle cx="8" cy="7" r="2" fill="currentColor"></circle>
                    <circle cx="16" cy="14" r="2" fill="currentColor"></circle>
                    <circle cx="10" cy="21" r="2" fill="currentColor"></circle>
                </svg>
                <span>Penyesuaian Dokumen</span>
            </button>

            <!-- Quick Export Dropdown -->
            <div class="relative inline-block text-left" x-data="{ open: false }" @click.outside="open = false">
                <button 
                    type="button" 
                    @click="open = !open" 
                    class="button secondary flex items-center gap-1.5 text-xs font-bold shadow-2xs cursor-pointer border-slate-300 hover:bg-slate-100 text-slate-800"
                    title="Menu opsi ekspor cepat dokumen berita acara"
                >
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    <span>Ekspor</span>
                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
                </button>
                <div 
                    x-show="open" 
                    x-transition 
                    style="display: none;" 
                    class="absolute right-0 z-30 mt-2 w-56 origin-top-right rounded-xl bg-white border border-slate-200 shadow-xl py-1 text-xs"
                >
                    <a 
                        href="{{ route('admin.reports.export.pdf', ['agenda' => $agenda, 'download' => 'pdf']) }}" 
                        class="flex items-center gap-2 px-3.5 py-2.5 text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                        <span>Unduh PDF Resmi (.pdf)</span>
                    </a>
                    <a 
                        href="{{ route('admin.reports.export.word', $agenda) }}" 
                        class="flex items-center gap-2 px-3.5 py-2.5 text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <span>Unduh Dokumen Word (.doc)</span>
                    </a>
                    <div class="h-px bg-slate-200 my-1"></div>
                    <a 
                        href="{{ route('admin.reports.export.pdf', $agenda) }}" 
                        target="_blank" 
                        class="flex items-center gap-2 px-3.5 py-2.5 text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                        <span>Pratinjau / Cetak A4 Tab Baru</span>
                    </a>
                </div>
            </div>

            <!-- Primary Save Action -->
            <button 
                type="submit" 
                form="notulen-form" 
                class="button flex items-center gap-2 text-xs font-bold shadow-xs cursor-pointer bg-slate-900 hover:bg-slate-800 text-white"
                title="Simpan seluruh notulensi, kesimpulan, berkas foto dokumentasi, dan penyesuaian dokumen (Shortcut: Ctrl+S)"
            >
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Notulensi</span>
                <span class="hidden md:inline-block text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded font-mono font-normal">Ctrl+S</span>
            </button>
        </div>
    </div>

    <!-- 2. Sticky Universal Word Formatting Ribbon Toolbar -->
    <div class="word-editor-ribbon sticky top-[63px] z-20 bg-white border border-slate-300 rounded-2xl shadow-sm" role="toolbar" aria-label="Toolbar Format Pengolah Kata">
        <!-- Style & Font Size Dropdowns -->
        <div class="flex items-center gap-1.5 flex-shrink-0">
            <select class="word-select" data-command="formatBlock" title="Gaya Paragraf">
                <option value="p">Normal</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
                <option value="h4">Heading 4</option>
                <option value="blockquote">Kutipan</option>
            </select>
            <select class="word-select" data-action="fontSize" title="Ukuran Font">
                <option value="">Ukuran</option>
                <option value="1">10px</option>
                <option value="2">12px</option>
                <option value="3">14px</option>
                <option value="4">16px</option>
                <option value="5">20px</option>
                <option value="6">24px</option>
            </select>
        </div>

        <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

        <!-- Font Styles: Bold, Italic, Underline, Strikethrough -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="bold" title="Tebal (Ctrl+B)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="italic" title="Miring (Ctrl+I)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" x2="10" y1="4" y2="4"/><line x1="14" x2="5" y1="20" y2="20"/><line x1="15" x2="9" y1="4" y2="20"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="underline" title="Garis Bawah (Ctrl+U)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 4v6a6 6 0 0 0 12 0V4"/><line x1="4" x2="20" y1="20" y2="20"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="strikeThrough" title="Coret">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4H9a3 3 0 0 0-2.83 4"/><path d="M14 12a4 4 0 0 1 0 8H6"/><line x1="4" x2="20" y1="12" y2="12"/></svg>
            </button>
        </div>

        <!-- Text Color & Highlight -->
        <div class="word-btn-group">
            <button type="button" class="word-btn word-color-btn" data-action="textColor" title="Warna Teks">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 18 6-14h2l6 14"/><path d="M8.5 12h7"/></svg>
                <span class="color-indicator" data-indicator="textColor" style="background:#c0392b"></span>
            </button>
            <input type="color" class="word-color-input" data-color-for="textColor" value="#c0392b" tabindex="-1">
            <button type="button" class="word-btn word-color-btn" data-action="highlight" title="Sorot Teks">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg>
                <span class="color-indicator" data-indicator="highlight" style="background:#fef08a"></span>
            </button>
            <input type="color" class="word-color-input" data-color-for="highlight" value="#fef08a" tabindex="-1">
        </div>

        <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

        <!-- Alignment -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="justifyLeft" title="Rata Kiri">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="15" x2="3" y1="12" y2="12"/><line x1="17" x2="3" y1="18" y2="18"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="justifyCenter" title="Rata Tengah">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="17" x2="7" y1="12" y2="12"/><line x1="19" x2="5" y1="18" y2="18"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="justifyRight" title="Rata Kanan">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="7" y1="18" y2="18"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="justifyFull" title="Rata Kanan-Kiri">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="3" y1="12" y2="12"/><line x1="21" x2="3" y1="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Lists & Indents -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="insertUnorderedList" title="Daftar Poin">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="9" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="9" y1="18" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="insertOrderedList" title="Daftar Nomor">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="9" y1="6" y2="6"/><line x1="21" x2="9" y1="12" y2="12"/><line x1="21" x2="9" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="outdent" title="Kurangi Indentasi">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 8 3 12 7 16"/><line x1="21" x2="11" y1="6" y2="6"/><line x1="21" x2="11" y1="12" y2="12"/><line x1="21" x2="11" y1="18" y2="18"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="indent" title="Tambah Indentasi">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 8 7 12 3 16"/><line x1="21" x2="11" y1="6" y2="6"/><line x1="21" x2="11" y1="12" y2="12"/><line x1="21" x2="11" y1="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Subscript / Superscript -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="superscript" title="Superscript (X²)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" stroke="none"><text x="2" y="18" font-size="14" font-weight="700">X</text><text x="15" y="10" font-size="10" font-weight="700">2</text></svg>
            </button>
            <button type="button" class="word-btn" data-command="subscript" title="Subscript (X₂)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor" stroke="none"><text x="2" y="16" font-size="14" font-weight="700">X</text><text x="15" y="22" font-size="10" font-weight="700">2</text></svg>
            </button>
        </div>

        <!-- Insert Table & Utilities -->
        <div class="word-btn-group relative">
            <button type="button" class="word-btn" data-command="blockquote" title="Kutipan">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="insertHorizontalRule" title="Garis Pemisah">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="2" x2="22" y1="12" y2="12"/></svg>
            </button>
            <button type="button" class="word-btn" data-action="insertTable" title="Sisipkan Tabel">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="3" x2="21" y1="15" y2="15"/><line x1="9" x2="9" y1="3" y2="21"/><line x1="15" x2="15" y1="3" y2="21"/></svg>
            </button>
            <div class="word-table-picker"></div>
        </div>

        <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

        <!-- History & Utilities -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="undo" title="Batal (Ctrl+Z)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="redo" title="Ulangi (Ctrl+Y)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
            </button>
            <button type="button" class="word-btn text-rose-600 hover:text-rose-700 hover:bg-rose-50" data-command="removeFormat" title="Hapus Format">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" x2="18"/><path d="M4 6h16"/><path d="M10 6v2a4 4 0 0 0 4 4"/></svg>
            </button>
            <button type="button" class="word-btn text-rose-600 hover:text-rose-700 hover:bg-rose-50" data-action="clearAll" title="Kosongkan Teks Bagian Aktif">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
        </div>
    </div>

    <!-- 3. Form Binding: Hidden Inputs for Notulensi & Kesimpulan -->
    <form 
        id="notulen-form" 
        method="POST" 
        action="{{ route('admin.agendas.update-notulen', $agenda) }}" 
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        <textarea id="notulensi-hidden" name="notulensi" class="hidden">{{ old('notulensi', $agenda->notulensi) }}</textarea>
        <textarea id="kesimpulan-hidden" name="kesimpulan" class="hidden">{{ old('kesimpulan', $agenda->kesimpulan) }}</textarea>

        <!-- 4. Real Desk Canvas with Word Document Ruler & Scaler -->
        <div class="office-desk-canvas">
            <!-- Horizontal Word Document Ruler -->
            <div class="office-document-ruler paper-a4" id="office-document-ruler" aria-hidden="true">
                <div class="ruler-margin-zone ruler-margin-left"></div>
                <div class="ruler-scale">
                    @for($cm = 0; $cm <= 21; $cm++)
                        <div class="ruler-cm"><span>{{ $cm > 0 ? $cm : '' }}</span></div>
                    @endfor
                </div>
                <div class="ruler-margin-zone ruler-margin-right"></div>
            </div>

            <div class="office-zoom-container" id="office-zoom-container">

                <!-- ==================== LEMBAR HALAMAN 1 (BERITA ACARA & NOTULENSI) ==================== -->
                <div class="office-paper-sheet paper-a4" data-page="1">
                    <!-- Page Number Badge (Top Right Corner) -->
                    <div class="absolute top-3 right-4 text-[10px] font-mono font-bold text-slate-400 select-none print:hidden">
                        HALAMAN 1
                    </div>

                    <!-- Kop Surat Resmi Instansi (Single Source of Truth, 100% Margin Locked) -->
                    @php
                        $logoUrl = ($config['custom_logo_path'] ?? null) && Storage::disk('public')->exists($config['custom_logo_path'])
                            ? Storage::disk('public')->url($config['custom_logo_path'])
                            : asset('images/tut-wuri-handayani.png');
                    @endphp
                    <div class="header-kop" id="sheet-header-kop" style="margin-bottom: 6pt; text-align: center; width: 100%; {{ ($config['show_kop'] ?? true) ? '' : 'display: none;' }}">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 64px;">
                                <col style="width: calc(100% - 128px);">
                                <col style="width: 64px;">
                            </colgroup>
                            <tr>
                                <td align="center" valign="middle" style="width: 64px; text-align: center; vertical-align: middle; border: none; padding: 0 0 4px 0;">
                                    <img id="sheet-logo-img" src="{{ $logoUrl }}" alt="Logo Instansi" width="52" height="52" style="width: 52px; height: 52px; max-height: 52px; max-width: 52px; object-fit: contain; display: {{ ($config['show_logo'] ?? true) ? 'block' : 'none' }}; margin: 0 auto; border: none;">
                                </td>
                                <td align="center" valign="middle" style="text-align: center; vertical-align: middle; border: none; padding: 0 4px 4px 4px;">
                                    <h3 id="sheet-instansi-induk" style="margin: 0; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
                                        {{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}
                                    </h3>
                                    <h2 id="sheet-instansi-pelaksana" style="margin: 2px 0; font-size: 11.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
                                        {{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}
                                    </h2>
                                    <p id="sheet-alamat-kontak" style="margin: 0; font-size: 8pt; font-style: normal; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
                                        {{ $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id' }}
                                    </p>
                                </td>
                                <td style="width: 64px; border: none; padding: 0 0 4px 0;">&nbsp;</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="border: none; border-bottom: 2.25pt double #000000; height: 2px; font-size: 1pt; line-height: 1pt; padding: 0 0 2pt 0;">&nbsp;</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Judul Dokumen & Nomor Berita Acara -->
                    <div class="doc-title" style="text-align: center; margin: 6pt 0 6pt 0;">
                        <h1 id="sheet-document-title" style="font-size: 11.5pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            {{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}
                        </h1>
                        <div id="sheet-document-number-wrapper" style="font-size: 9pt; font-family: 'Courier New', Courier, monospace; margin-top: 2px; {{ ($config['show_document_number'] ?? true) ? '' : 'display: none;' }}">
                            Nomor: <span id="sheet-document-number">{{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</span>
                        </div>
                    </div>

                    <!-- Informasi Pelaksanaan Rapat -->
                    <table class="info-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; margin-bottom: 6pt; border-collapse: collapse; font-size: 9pt; border: none; font-family: 'Times New Roman', Times, serif;">
                        <colgroup>
                            <col style="width: 24%;">
                            <col style="width: 2%;">
                            <col style="width: 74%;">
                        </colgroup>
                        <tr>
                            <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Perihal / Agenda</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;"><strong>{{ $agenda->judul_rapat }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Hari / Tanggal</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Waktu Pelaksanaan</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Format &amp; Tempat</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ ucfirst($agenda->tipe_rapat) }} &mdash; {{ $agenda->lokasi_ruang ?? 'Daring (Online Meeting)' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: 1.5pt 0; vertical-align: top; border: none;">Penyelenggara Rapat</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">:</td>
                            <td style="padding: 1.5pt 0; vertical-align: top; border: none;">{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
                        </tr>
                    </table>

                    <!-- Seksi I: Daftar Kehadiran Peserta (Spreadsheet / Word Table) -->
                    <div style="font-size: 9.5pt; font-weight: bold; margin: 6pt 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                        I. DAFTAR KEHADIRAN PESERTA ({{ $attendances->count() }} Orang)
                    </div>
                    <table class="doc-spreadsheet-table" width="100%" border="1" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: 1px solid #000000;">
                        <colgroup>
                            <col style="width: 5%;">
                            <col style="width: 27%;">
                            <col style="width: 21%;">
                            <col style="width: 17%;">
                            <col style="width: 10%;">
                            <col style="width: 8%;">
                            <col style="width: 12%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th style="text-align: left; padding-left: 5pt;">Nama Lengkap</th>
                                <th style="text-align: left; padding-left: 4pt;">NIP</th>
                                <th style="text-align: left; padding-left: 4pt;">Unit Kerja</th>
                                <th>Waktu</th>
                                <th>Foto</th>
                                <th>Tanda Tangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $index => $att)
                                <tr>
                                    <td style="text-align: center;">{{ $index + 1 }}</td>
                                    <td style="padding: 3pt 5pt;"><strong>{{ $att->user->name }}</strong></td>
                                    <td style="padding: 3pt 4pt; font-family: monospace; font-size: 8pt;">{{ $att->user->nip }}</td>
                                    <td style="padding: 3pt 4pt; font-size: 8pt;">{{ $att->user->unit?->kode_unit ?? 'Pusat' }}</td>
                                    <td style="text-align: center; font-size: 8pt; font-family: monospace;">{{ $att->signed_at->format('H:i') }}</td>
                                    <td style="text-align: center; padding: 2pt;">
                                        @if($att->selfie_path)
                                            <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" width="26" height="26" style="width: 26px; height: 26px; object-fit: cover; border-radius: 2px; border: 1px solid #cbd5e1; display: inline-block; vertical-align: middle;">
                                        @else
                                            <span style="font-size: 7pt; color: #94a3b8; font-style: italic;">Tanpa Foto</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center; padding: 2pt;">
                                        @if($att->signature_path)
                                            <img src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" width="70" height="22" style="width: 70px; height: 22px; max-height: 24px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                                        @else
                                            <span style="font-size: 7.5pt; color: #166534; font-weight: bold;">(HADIR)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 8px; font-style: italic; color: #64748b;">
                                        Belum ada peserta yang mengisi presensi rapat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <!-- Seksi II: Notulensi & Kesimpulan Rapat (Interactive WYSIWYG Editors) -->
                    <div id="sheet-seksi-2-container" style="margin-top: 6pt;">
                        <div id="sheet-seksi-2-title" style="font-size: 9.5pt; font-weight: bold; margin: 0 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            II. NOTULENSI &amp; KESIMPULAN RAPAT
                        </div>

                        <!-- Bagian A: Catatan Jalannya Rapat (Notulensi) -->
                        <div id="sheet-section-notulensi-wrapper" style="margin-bottom: 6pt;">
                            <div class="flex items-center justify-between mb-1">
                                <label for="notulensi-content" style="font-weight: bold; font-size: 8.5pt; font-family: 'Times New Roman', Times, serif;" class="text-slate-900">
                                    A. Notulensi / Catatan Jalannya Rapat:
                                </label>
                                <span class="text-[10px] text-slate-500 italic">Klik kolom di bawah untuk mulai mengetik</span>
                            </div>
                            <div 
                                id="notulensi-content" 
                                class="office-editable-box prose-gov" 
                                contenteditable="true" 
                                data-editor="notulensi"
                                data-page="1"
                                data-placeholder="Ketik catatan jalannya rapat, dinamika diskusi, arahan pimpinan, dan pembahasan di sini..."
                                style="min-height: 80px;"
                            >{!! old('notulensi', $agenda->notulensi) !!}</div>
                            @error('notulensi')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bagian B: Kesimpulan & Rencana Tindak Lanjut (RTL) -->
                        <div id="sheet-section-kesimpulan-wrapper">
                            <div class="flex items-center justify-between mb-1">
                                <label for="kesimpulan-content" style="font-weight: bold; font-size: 8.5pt; font-family: 'Times New Roman', Times, serif;" class="text-slate-900">
                                    B. Kesimpulan &amp; Rencana Tindak Lanjut (RTL):
                                </label>
                                <span class="text-[10px] text-slate-500 italic">Klik kolom di bawah untuk mulai mengetik</span>
                            </div>
                            <div 
                                id="kesimpulan-content" 
                                class="office-editable-box prose-gov" 
                                contenteditable="true" 
                                data-editor="kesimpulan"
                                data-page="1"
                                data-placeholder="Ketik poin-poin kesimpulan akhir, keputusan yang disepakati, PIC penanggung jawab, dan tenggat waktu penyelesaian..."
                                style="min-height: 70px;"
                            >{!! old('kesimpulan', $agenda->kesimpulan) !!}</div>
                            @error('kesimpulan')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Running Footer Lembar 1 -->
                    <div class="doc-running-footer">
                        <span>SIPERAPAT &bull; LLDIKTI Wilayah X</span>
                        <span class="doc-page-number font-bold">Halaman 1 dari 2</span>
                    </div>
                </div>

                <!-- ==================== PEMISAH ANTAR HALAMAN (NATURAL PAGE BREAK) ==================== -->
                <div class="office-page-separator" id="page-separator-1" aria-hidden="true">
                    <div class="office-page-gap" id="office-page-gap">
                        <div class="gap-line"></div>
                        <div class="gap-indicator">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="gap-indicator-text">Pemisah Halaman &bull; Menuju Lembar Pengesahan</span>
                        </div>
                        <div class="gap-line"></div>
                    </div>
                </div>

                <!-- Wadah Lembar Lanjutan Dinamis (Sheet 2, 3, dst.) -->
                <div id="dynamic-continuation-sheets"></div>

                <!-- ==================== LEMBAR FINAL (PENGESAHAN & LAMPIRAN FOTO) ==================== -->
                <div class="office-paper-sheet paper-a4" id="sheet-pengesahan-final" data-page="2">
                    <!-- Page Number Badge (Top Right Corner) -->
                    <div class="doc-badge-page absolute top-3 right-4 text-[10px] font-mono font-bold text-slate-400 select-none print:hidden">
                        HALAMAN 2
                    </div>

                    <!-- Running Header Lembar Final -->
                    <div class="doc-running-header">
                        <span class="truncate max-w-sm">Berita Acara Rapat: <strong>{{ $agenda->judul_rapat }}</strong></span>
                        <span class="doc-page-number font-bold shrink-0">Halaman 2 dari 2</span>
                    </div>

                    <!-- Header Lembar Pengesahan -->
                    <div style="text-align: center; margin-bottom: 10pt; padding-bottom: 5pt; border-bottom: 1.5pt solid #000;">
                        <h2 style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0; font-family: 'Times New Roman', Times, serif;">
                            LEMBAR PENGESAHAN &amp; LAMPIRAN DOKUMENTASI
                        </h2>
                        <div style="font-size: 8.5pt; color: #334155; margin-top: 2pt; font-family: 'Times New Roman', Times, serif;">
                            Pelaksanaan: {{ $agenda->waktu_mulai->translatedFormat('d F Y') }} &bull; Format: {{ ucfirst($agenda->tipe_rapat) }}
                        </div>
                    </div>

                    <!-- Tanda Tangan Pengesahan (Pemimpin Rapat & Notulis) -->
                    @php
                        $pimpinanAtt = $agenda->pimpinan_attendance;
                        $notulisAtt = $agenda->notulis_attendance;
                    @endphp
                    <div class="signature-block" style="margin: 12pt 0 10pt 0;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: none; font-family: 'Times New Roman', Times, serif;">
                            <colgroup>
                                <col style="width: 50%;">
                                <col style="width: 50%;">
                            </colgroup>
                            <tr>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    Mengetahui,<br>
                                    <strong id="sheet-signer1-role">{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
                                </td>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    <span id="sheet-signing-city">{{ $config['signing_city'] ?? 'Padang' }}</span>, <span id="sheet-signing-date">{{ $config['signing_date'] ?? ($agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y')) }}</span><br>
                                    <strong id="sheet-signer2-role">{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
                                </td>
                            </tr>
                            <tr>
                                <td style="height: 38pt; text-align: center; vertical-align: middle; border: none; padding: 2pt 0;">
                                    @if($pimpinanAtt && $pimpinanAtt->signature_path)
                                        <img src="{{ Storage::disk('public')->url($pimpinanAtt->signature_path) }}" alt="TTD Pimpinan" width="95" height="32" style="width: 95px; height: 32px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                                    @else
                                        <span style="font-size: 7.5pt; color: #64748b; font-style: italic;">(Tanda tangan tercatat saat presensi)</span>
                                    @endif
                                </td>
                                <td style="height: 38pt; text-align: center; vertical-align: middle; border: none; padding: 2pt 0;">
                                    @if($notulisAtt && $notulisAtt->signature_path)
                                        <img src="{{ Storage::disk('public')->url($notulisAtt->signature_path) }}" alt="TTD Notulis" width="95" height="32" style="width: 95px; height: 32px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                                    @else
                                        <span style="font-size: 7.5pt; color: #64748b; font-style: italic;">(Tanda tangan tercatat saat presensi)</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    <strong><u id="sheet-signer1-name">{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</u></strong><br>
                                    NIP. <span id="sheet-signer1-nip">{{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}</span>
                                </td>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    <strong><u id="sheet-signer2-name">{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</u></strong><br>
                                    NIP. <span id="sheet-signer2-nip">{{ $config['signer2_nip'] ?? $agenda->nip_notulis }}</span>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Catatan Kaki Dokumen Resmi -->
                    <div style="margin: 10pt 0 14pt 0; padding-top: 3pt; border-top: 1px solid #cbd5e1; font-size: 7.5pt; color: #64748b; text-align: center; font-family: 'Times New Roman', Times, serif;">
                        <span id="sheet-footer-note">{{ $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X' }}</span> &bull; <span>Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} WIB</span>
                    </div>

                    <!-- Seksi III: Lampiran Foto Dokumentasi Kegiatan -->
                    <div style="border-top: 2px dashed #cbd5e1; padding-top: 10pt; margin-top: 10pt;">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 font-sans">
                                    III. Lampiran Foto Dokumentasi Kegiatan
                                </h3>
                                <p class="text-[11px] text-slate-600 font-sans">Unggah foto suasana rapat, dokumen pendukung, atau paparan materi sebagai lampiran sah.</p>
                            </div>
                            <span id="photo-counter-badge" class="hidden text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-mono">
                                0 Foto Terpilih
                            </span>
                        </div>

                        <!-- Dropzone & File Input -->
                        <div id="photo-uploader-container" class="space-y-3">
                            <div 
                                id="photo-dropzone"
                                class="border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/75 rounded-xl p-5 text-center transition cursor-pointer relative group font-sans"
                            >
                                <input 
                                    type="file" 
                                    id="photos" 
                                    name="photos[]" 
                                    multiple 
                                    accept="image/jpeg,image/png,image/webp" 
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                    title="Pilih atau seret berkas foto dokumentasi"
                                >
                                <div class="flex flex-col items-center justify-center pointer-events-none space-y-2">
                                    <div class="w-9 h-9 rounded-full bg-slate-200 group-hover:bg-slate-300 transition flex items-center justify-center text-slate-700">
                                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold text-slate-900 group-hover:underline">Klik untuk memilih foto lampiran</span>
                                        <span class="text-xs text-slate-500"> atau seret & jatuhkan berkas ke sini</span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Format: JPG, JPEG, PNG, WEBP (Maksimal 3 MB per foto, hingga 10 foto).</p>
                                </div>
                            </div>

                            @error('photos')
                                <p class="text-xs text-rose-700 font-bold mt-1 font-sans">{{ $message }}</p>
                            @enderror
                            @error('photos.*')
                                <p class="text-xs text-rose-700 font-bold mt-1 font-sans">{{ $message }}</p>
                            @enderror

                            <!-- Preview Grid Container for newly selected files -->
                            <div id="photo-preview-grid" class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 pt-2 font-sans">
                                <!-- Pratinjau foto akan dirender dinamis via JavaScript -->
                            </div>
                        </div>

                        <!-- Galeri Dokumentasi Foto Tersimpan -->
                        @if($documentations->count() > 0)
                            <div class="mt-4 pt-3 border-t border-slate-200 font-sans">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                                        Foto Dokumentasi Tersimpan ({{ $documentations->total() }})
                                    </h4>
                                </div>
                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                    @foreach($documentations as $doc)
                                        <div class="relative group bg-slate-50 rounded-xl overflow-hidden border border-slate-300">
                                            <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="Dokumentasi Rapat" class="w-full h-24 object-cover">
                                            <div class="p-1.5 bg-white text-[10px] text-slate-900 font-bold truncate border-t border-slate-200">
                                                {{ $doc->caption ?? 'Dokumentasi' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($documentations->hasPages())
                                    <div class="mt-3">
                                        {{ $documentations->links('vendor.pagination.compact') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Running Footer Lembar Final -->
                    <div class="doc-running-footer">
                        <span>SIPERAPAT &bull; LLDIKTI Wilayah X</span>
                        <span class="doc-page-number font-bold">Halaman 2 dari 2</span>
                    </div>
                </div>

            </div>

            <!-- 5. Word Office Bottom Status Bar -->
            <div class="office-status-bar" id="office-status-bar" aria-label="Bilah Status Dokumen">
                <div class="flex items-center gap-3">
                    <span id="office-status-page" class="font-semibold text-slate-200">Halaman 1 dari 2</span>
                    <span class="text-slate-600">&bull;</span>
                    <span id="office-status-size">A4 (210 × 297 mm)</span>
                    <span class="text-slate-600">&bull;</span>
                    <span>Margin: 18mm Normal</span>
                </div>
                <div class="flex items-center gap-3">
                    <span id="office-status-zoom">Zoom: 100%</span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL PENYESUAIAN DOKUMEN BERITA ACARA (SINGLE SOURCE OF TRUTH)           -->
        <!-- ========================================================================= -->
        <div 
            id="modal-notulen-document-config" 
            class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 overflow-y-auto transition-opacity"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-notulen-document-config-title"
        >
            <div class="bg-white border border-slate-300 rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden my-auto max-h-[calc(100dvh-2rem)] flex flex-col animate-in fade-in zoom-in-95 duration-150">
                <!-- 1. Modal Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="4" x2="20" y1="21" y2="21"></line>
                                <line x1="4" x2="20" y1="14" y2="14"></line>
                                <line x1="4" x2="20" y1="7" y2="7"></line>
                                <circle cx="8" cy="7" r="2" fill="currentColor"></circle>
                                <circle cx="16" cy="14" r="2" fill="currentColor"></circle>
                                <circle cx="10" cy="21" r="2" fill="currentColor"></circle>
                            </svg>
                        </div>
                        <div>
                            <h3 id="modal-notulen-document-config-title" class="text-sm font-bold text-slate-900">Penyesuaian Format &amp; Pengaturan Dokumen</h3>
                            <p class="text-[11px] text-slate-500 font-medium truncate max-w-md">{{ $agenda->judul_rapat }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeDocumentConfigModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition cursor-pointer" aria-label="Tutup modal">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- Flag indicating document configuration is present -->
                <input type="hidden" name="has_document_config" value="1">

                <!-- 2. Navigation Tabs Bar -->
                <div class="px-6 border-b border-slate-200 bg-white flex items-center gap-4 shrink-0 overflow-x-auto text-xs font-semibold text-slate-600">
                    <button type="button" onclick="switchDocConfigTab('tab-header')" id="btn-doc-tab-header" class="doc-config-tab-btn py-3 border-b-2 border-slate-900 text-slate-900 flex items-center gap-1.5 cursor-pointer">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/></svg>
                        <span>1. Header &amp; Kop Surat</span>
                    </button>
                    <button type="button" onclick="switchDocConfigTab('tab-content')" id="btn-doc-tab-content" class="doc-config-tab-btn py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 cursor-pointer">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <span>2. Konten &amp; Kehadiran</span>
                    </button>
                    <button type="button" onclick="switchDocConfigTab('tab-footer')" id="btn-doc-tab-footer" class="doc-config-tab-btn py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 cursor-pointer">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>3. Footer &amp; Pengesahan</span>
                    </button>
                </div>

                <!-- 3. Scrollable Body -->
                <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">

                    {{-- ====== TAB 1: HEADER & KOP ====== --}}
                    <div id="pane-doc-tab-header" class="doc-config-tab-pane space-y-4">
                        <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 leading-relaxed flex items-start gap-2">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <div>
                                <strong>Pengaturan Kop &amp; Identitas Dokumen</strong>
                                <p class="text-[11px] text-blue-800 mt-0.5">Matikan "Tampilkan Kop Surat Resmi" jika Anda mencetak laporan langsung di atas kertas berkop fisik resmi instansi.</p>
                            </div>
                        </div>

                        <!-- Toggle Show Kop -->
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <div>
                                <div class="font-bold text-slate-900">Tampilkan Kop Surat Resmi Instansi</div>
                                <div class="text-[11px] text-slate-500">Mencetak header instansi dan garis pembatas kop di bagian atas dokumen</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="show_kop" value="0">
                                <input type="checkbox" name="show_kop" value="1" class="sr-only peer" @checked($config['show_kop'] ?? true)>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                            </label>
                        </div>

                        <!-- Toggle Show Logo & Logo Preview / Custom Upload -->
                        <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">Tampilkan Logo Lambang pada Kop Surat</div>
                                    <div class="text-[11px] text-slate-500">Menyisipkan logo resmi instansi di sisi kiri kop surat (ukuran standar dinas ~65×65 px)</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_logo" value="0">
                                    <input type="checkbox" name="show_logo" value="1" class="sr-only peer" @checked($config['show_logo'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>

                            <div class="pt-2 border-t border-slate-200/80 flex flex-col sm:flex-row sm:items-center gap-3">
                                <div class="flex items-center gap-2.5 shrink-0">
                                    @if(!empty($config['custom_logo_path']) && Storage::disk('public')->exists($config['custom_logo_path']))
                                        <div class="w-12 h-12 rounded-lg border border-slate-300 bg-white p-1 flex items-center justify-center shrink-0">
                                            <img src="{{ Storage::disk('public')->url($config['custom_logo_path']) }}" alt="Logo Kustom" class="max-h-10 max-w-10 object-contain">
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Logo Kustom Aktif</span>
                                            <label class="flex items-center gap-1 mt-1 text-[11px] text-red-600 font-semibold cursor-pointer">
                                                <input type="checkbox" name="reset_custom_logo" value="1" class="rounded border-slate-300 text-red-600">
                                                <span>Kembalikan ke Tut Wuri Handayani</span>
                                            </label>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-lg border border-slate-300 bg-white p-1 flex items-center justify-center shrink-0">
                                            <img src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri" class="max-h-10 max-w-10 object-contain">
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Logo Tut Wuri Handayani</span>
                                            <div class="text-[10px] text-slate-500 mt-0.5">Bawaan standar resmi kementerian</div>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex-1">
                                    <label class="text-[11px] font-semibold text-slate-700 block mb-1">Ganti dengan Logo Khusus / Satker (Opsional)</label>
                                    <input type="file" name="custom_logo" accept="image/png,image/jpeg,image/webp" class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-200 file:text-slate-800 hover:file:bg-slate-300 cursor-pointer">
                                    <span class="text-[10px] text-slate-400 block mt-0.5">Format PNG/JPEG/WebP, maks. 512 KB</span>
                                </div>
                            </div>
                        </div>

                        <!-- Instansi Induk & Pelaksana -->
                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-800 block">Nama Kementerian / Lembaga Induk</label>
                            <input type="text" name="instansi_induk" value="{{ old('instansi_induk', $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-800 block">Nama Satuan Kerja / Instansi Pelaksana</label>
                            <input type="text" name="instansi_pelaksana" value="{{ old('instansi_pelaksana', $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-slate-800 block">Alamat, Kontak &amp; Laman Resmi</label>
                            <input type="text" name="alamat_kontak" value="{{ old('alamat_kontak', $config['alamat_kontak'] ?? 'Jalan Khatib Sulaiman, Padang, Sumatera Barat • Laman: lldikti10.kemdikbud.go.id') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-800 block">Judul Dokumen</label>
                                <input type="text" name="document_title" value="{{ old('document_title', $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                            </div>
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label class="font-bold text-slate-800 block">Nomor Surat / Berita Acara</label>
                                    <label class="flex items-center gap-1 text-[11px] text-slate-500 cursor-pointer">
                                        <input type="hidden" name="show_document_number" value="0">
                                        <input type="checkbox" name="show_document_number" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_document_number'] ?? true)>
                                        <span>Tampilkan</span>
                                    </label>
                                </div>
                                <input type="text" name="document_number" value="{{ old('document_number', $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT))) }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3 font-mono">
                            </div>
                        </div>
                    </div>

                    {{-- ====== TAB 2: KONTEN & KEHADIRAN ====== --}}
                    <div id="pane-doc-tab-content" class="doc-config-tab-pane hidden space-y-4">
                        <!-- Toggle Meeting Info -->
                        <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                            <div>
                                <div class="font-bold text-slate-900">Tampilkan Informasi Pelaksanaan Rapat</div>
                                <div class="text-[11px] text-slate-500">Tabel ringkasan perihal, tanggal, waktu, format/tempat, dan penyelenggara</div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="show_meeting_info" value="0">
                                <input type="checkbox" name="show_meeting_info" value="1" class="sr-only peer" @checked($config['show_meeting_info'] ?? true)>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-800 block">Override Perihal / Judul Rapat</label>
                                <input type="text" name="custom_agenda_title" value="{{ old('custom_agenda_title', $config['custom_agenda_title'] ?? $agenda->judul_rapat) }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                            </div>
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-800 block">Override Format &amp; Tempat</label>
                                <input type="text" name="custom_location" value="{{ old('custom_location', $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)')) }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                            </div>
                        </div>

                        <!-- Attendance Table Settings -->
                        <div class="p-4 border border-slate-200 rounded-xl space-y-3 bg-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi I: Tabel Kehadiran Peserta</div>
                                    <div class="text-[11px] text-slate-500">Menampilkan daftar pegawai yang telah mengisi presensi resmi rapat ini</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_attendees" value="0">
                                    <input type="checkbox" name="show_attendees" value="1" class="sr-only peer" @checked($config['show_attendees'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 pt-2 border-t border-slate-100 text-xs">
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="hidden" name="show_nip" value="0">
                                    <input type="checkbox" name="show_nip" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_nip'] ?? true)>
                                    <span class="font-medium text-slate-800">Kolom NIP</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="hidden" name="show_unit" value="0">
                                    <input type="checkbox" name="show_unit" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_unit'] ?? true)>
                                    <span class="font-medium text-slate-800">Kolom Unit</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="hidden" name="show_attendance_time" value="0">
                                    <input type="checkbox" name="show_attendance_time" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_attendance_time'] ?? true)>
                                    <span class="font-medium text-slate-800">Kolom Waktu</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="hidden" name="show_attendee_signatures" value="0">
                                    <input type="checkbox" name="show_attendee_signatures" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_attendee_signatures'] ?? true)>
                                    <span class="font-medium text-slate-800">Tanda Tangan</span>
                                </label>
                                <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors col-span-2 sm:col-span-1">
                                    <input type="hidden" name="show_selfie_photos" value="0">
                                    <input type="checkbox" name="show_selfie_photos" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_selfie_photos'] ?? true)>
                                    <span class="font-medium text-slate-800">Foto Kehadiran</span>
                                </label>
                            </div>
                        </div>

                        <!-- Minutes, Conclusions & Documentation Toggles -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi II A: Catatan Jalannya Rapat (Notulensi)</div>
                                    <div class="text-[11px] text-slate-500">Mencantumkan seluruh notulensi hasil ketikan notulis</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_notulensi" value="0">
                                    <input type="checkbox" name="show_notulensi" value="1" class="sr-only peer" @checked($config['show_notulensi'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi II B: Kesimpulan &amp; Rencana Tindak Lanjut (RTL)</div>
                                    <div class="text-[11px] text-slate-500">Mencantumkan kesimpulan pokok dan tindak lanjut kedinasan</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_kesimpulan" value="0">
                                    <input type="checkbox" name="show_kesimpulan" value="1" class="sr-only peer" @checked($config['show_kesimpulan'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi III: Lampiran Foto Dokumentasi Kegiatan</div>
                                    <div class="text-[11px] text-slate-500">Melampirkan galeri foto kegiatan rapat yang diunggah ke dokumen</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_documentation" value="0">
                                    <input type="checkbox" name="show_documentation" value="1" class="sr-only peer" @checked($config['show_documentation'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- ====== TAB 3: FOOTER & PENGESAHAN ====== --}}
                    <div id="pane-doc-tab-footer" class="doc-config-tab-pane hidden space-y-4">
                        @if($agenda->pimpinan || $agenda->notulis)
                            <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2.5 text-xs text-blue-900">
                                <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <span class="font-bold">Sinkronisasi Peran Aktif:</span>
                                    Nama dan NIP penandatangan otomatis disinkronkan dengan pimpinan rapat dan notulis yang ditugaskan.
                                </div>
                            </div>
                        @endif

                        <!-- City & Date -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-800 block">Kota Penandatanganan</label>
                                <input type="text" name="signing_city" value="{{ old('signing_city', $config['signing_city'] ?? 'Padang') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                            </div>
                            <div class="space-y-1.5">
                                <label class="font-bold text-slate-800 block">Tanggal Pengesahan Dokumen</label>
                                <input type="text" name="signing_date" value="{{ old('signing_date', $config['signing_date'] ?? ($agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y'))) }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                            </div>
                        </div>

                        <!-- Signer 1 (Left / Pemimpin Rapat) -->
                        <div class="p-4 border border-slate-200 rounded-xl space-y-3 bg-slate-50/70">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">Penandatangan 1 (Sisi Kiri)</span>
                                    @if($agenda->pimpinan)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Peran: {{ $agenda->pimpinan->name }}
                                        </span>
                                    @endif
                                </div>
                                <label class="flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer">
                                    <input type="hidden" name="show_signer1_signature" value="0">
                                    <input type="checkbox" name="show_signer1_signature" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_signer1_signature'] ?? true)>
                                    <span>Sertakan Gambar TTD</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Jabatan</label>
                                    <input type="text" name="signer1_role" value="{{ old('signer1_role', $config['signer1_role'] ?? 'Pemimpin Rapat') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Nama Pejabat</label>
                                    <input type="text" name="signer1_name" value="{{ old('signer1_name', $config['signer1_name'] ?? $agenda->nama_pimpinan) }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-bold">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">NIP</label>
                                    <input type="text" name="signer1_nip" value="{{ old('signer1_nip', $config['signer1_nip'] ?? $agenda->nip_pimpinan) }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Signer 2 (Right / Notulis Rapat) -->
                        <div class="p-4 border border-slate-200 rounded-xl space-y-3 bg-slate-50/70">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-slate-900">Penandatangan 2 (Sisi Kanan)</span>
                                    @if($agenda->notulis)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                            Peran: {{ $agenda->notulis->name }}
                                        </span>
                                    @endif
                                </div>
                                <label class="flex items-center gap-1 text-[11px] text-slate-600 cursor-pointer">
                                    <input type="hidden" name="show_signer2_signature" value="0">
                                    <input type="checkbox" name="show_signer2_signature" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_signer2_signature'] ?? true)>
                                    <span>Sertakan Gambar TTD</span>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Jabatan</label>
                                    <input type="text" name="signer2_role" value="{{ old('signer2_role', $config['signer2_role'] ?? 'Notulis Rapat') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Nama Notulis</label>
                                    <input type="text" name="signer2_name" value="{{ old('signer2_name', $config['signer2_name'] ?? $agenda->nama_notulis) }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-bold">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">NIP</label>
                                    <input type="text" name="signer2_nip" value="{{ old('signer2_nip', $config['signer2_nip'] ?? $agenda->nip_notulis) }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Signer 3 (Optional Middle / Mengetahui Kepala Lembaga) -->
                        <div class="p-4 border border-slate-200 rounded-xl space-y-3 bg-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-900">Penandatangan 3 (Opsional: Mengetahui Pimpinan Tinggi)</span>
                                    <p class="text-[11px] text-slate-500">Mencantumkan kolom pengesahan ketiga di tengah (misal Kepala Lembaga/PPK)</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_signer3" value="0">
                                    <input type="checkbox" name="show_signer3" value="1" class="sr-only peer" @checked($config['show_signer3'] ?? false)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Jabatan Pengesah</label>
                                    <input type="text" name="signer3_role" value="{{ old('signer3_role', $config['signer3_role'] ?? 'Kepala LLDIKTI Wilayah X') }}" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">Nama Pejabat</label>
                                    <input type="text" name="signer3_name" value="{{ old('signer3_name', $config['signer3_name'] ?? '') }}" placeholder="Contoh: Dr. H. Afrizal, M.Pd." class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-bold">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 block mb-1">NIP Pejabat</label>
                                    <input type="text" name="signer3_nip" value="{{ old('signer3_nip', $config['signer3_nip'] ?? '-') }}" placeholder="18 digit NIP" class="w-full text-xs rounded-lg border-slate-300 bg-white py-2 px-2.5 font-mono">
                                </div>
                            </div>
                        </div>

                        <!-- Footer Note -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="font-bold text-slate-900 block">Teks Catatan Kaki Dokumen</label>
                                <label class="flex items-center gap-1 text-[11px] text-slate-500 cursor-pointer">
                                    <input type="hidden" name="show_footer_note" value="0">
                                    <input type="checkbox" name="show_footer_note" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_footer_note'] ?? true)>
                                    <span>Tampilkan</span>
                                </label>
                            </div>
                            <input type="text" name="footer_note" value="{{ old('footer_note', $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                        </div>
                    </div>

                </div>

                <!-- 4. Pinned Modal Footer with Clean Dual Actions (Batal & Simpan-Tutup) -->
                <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 text-slate-400"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span>Format dokumen akan otomatis tersimpan bersama notulensi saat Anda menekan tombol "Simpan Notulensi".</span>
                    </div>

                    <div class="flex items-center justify-end gap-2 w-full sm:w-auto shrink-0">
                        <button 
                            type="button" 
                            onclick="cancelDocumentConfigModal()" 
                            class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 rounded-xl hover:bg-slate-200/80 transition cursor-pointer min-h-[44px] min-w-[70px] flex items-center justify-center"
                        >
                            Batal
                        </button>

                        <button 
                            type="button" 
                            onclick="closeDocumentConfigModal()" 
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 active:bg-slate-950 transition shadow-xs cursor-pointer min-h-[44px]"
                            title="Terapkan format dokumen ke lembar kerja dan lanjutkan penulisan notulensi"
                        >
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>Simpan &amp; Tutup</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    let docConfigSnapshot = {};

    function openDocumentConfigModal() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
            // Snapshot current input values
            docConfigSnapshot = {};
            modal.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.type === 'checkbox') {
                    docConfigSnapshot[el.name] = el.checked;
                } else if (el.type !== 'file') {
                    docConfigSnapshot[el.name] = el.value;
                }
            });
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function cancelDocumentConfigModal() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
            // Restore snapshot values
            modal.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.name in docConfigSnapshot) {
                    if (el.type === 'checkbox') {
                        el.checked = docConfigSnapshot[el.name];
                    } else if (el.type !== 'file') {
                        el.value = docConfigSnapshot[el.name];
                    }
                }
            });
            // Re-apply original snapshot values to workstation preview
            applyDocConfigToWorkstation();
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function closeDocumentConfigModal() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
            // Ensure workstation live preview is up to date with latest settings
            applyDocConfigToWorkstation();
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function switchDocConfigTab(tabName) {
        const modal = document.getElementById('modal-notulen-document-config');
        if (!modal) return;

        // Hide all panes
        modal.querySelectorAll('.doc-config-tab-pane').forEach(pane => pane.classList.add('hidden'));
        modal.querySelectorAll('.doc-config-tab-btn').forEach(btn => {
            btn.classList.remove('border-slate-900', 'text-slate-900');
            btn.classList.add('border-transparent', 'text-slate-500');
        });

        const targetPane = document.getElementById('pane-doc-' + tabName);
        const targetBtn = document.getElementById('btn-doc-' + tabName);

        if (targetPane) targetPane.classList.remove('hidden');
        if (targetBtn) {
            targetBtn.classList.remove('border-transparent', 'text-slate-500');
            targetBtn.classList.add('border-slate-900', 'text-slate-900');
        }
    }

    function applyDocConfigToWorkstation() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (!modal) return;

        const val = (name) => {
            const input = modal.querySelector(`[name="${name}"]`);
            return input ? input.value : '';
        };

        const isChecked = (name) => {
            const input = modal.querySelector(`input[type="checkbox"][name="${name}"]`);
            return input ? input.checked : true;
        };

        // Header Kop
        const kopEl = document.getElementById('sheet-header-kop');
        if (kopEl) kopEl.style.display = isChecked('show_kop') ? '' : 'none';

        const logoEl = document.getElementById('sheet-logo-img');
        if (logoEl) logoEl.style.display = isChecked('show_logo') ? 'block' : 'none';

        const indukEl = document.getElementById('sheet-instansi-induk');
        if (indukEl && val('instansi_induk')) indukEl.textContent = val('instansi_induk');

        const pelaksanaEl = document.getElementById('sheet-instansi-pelaksana');
        if (pelaksanaEl && val('instansi_pelaksana')) pelaksanaEl.textContent = val('instansi_pelaksana');

        const kontakEl = document.getElementById('sheet-alamat-kontak');
        if (kontakEl && val('alamat_kontak')) kontakEl.textContent = val('alamat_kontak');

        // Document Title & Number
        const titleEl = document.getElementById('sheet-document-title');
        if (titleEl && val('document_title')) titleEl.textContent = val('document_title');

        const numWrap = document.getElementById('sheet-document-number-wrapper');
        if (numWrap) numWrap.style.display = isChecked('show_document_number') ? '' : 'none';

        const numEl = document.getElementById('sheet-document-number');
        if (numEl && val('document_number')) numEl.textContent = val('document_number');

        // Signers & Footer
        const cityEl = document.getElementById('sheet-signing-city');
        if (cityEl && val('signing_city')) cityEl.textContent = val('signing_city');

        const dateEl = document.getElementById('sheet-signing-date');
        if (dateEl && val('signing_date')) dateEl.textContent = val('signing_date');

        const role1El = document.getElementById('sheet-signer1-role');
        if (role1El && val('signer1_role')) role1El.textContent = val('signer1_role');

        const name1El = document.getElementById('sheet-signer1-name');
        if (name1El && val('signer1_name')) name1El.textContent = val('signer1_name');

        const nip1El = document.getElementById('sheet-signer1-nip');
        if (nip1El && val('signer1_nip')) nip1El.textContent = val('signer1_nip');

        const role2El = document.getElementById('sheet-signer2-role');
        if (role2El && val('signer2_role')) role2El.textContent = val('signer2_role');

        const name2El = document.getElementById('sheet-signer2-name');
        if (name2El && val('signer2_name')) name2El.textContent = val('signer2_name');

        const nip2El = document.getElementById('sheet-signer2-nip');
        if (nip2El && val('signer2_nip')) nip2El.textContent = val('signer2_nip');

        const footerNoteEl = document.getElementById('sheet-footer-note');
        if (footerNoteEl && val('footer_note')) footerNoteEl.textContent = val('footer_note');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDocumentConfigModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (!modal) return;
        modal.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', applyDocConfigToWorkstation);
            input.addEventListener('change', applyDocConfigToWorkstation);
        });
    });
</script>
@endsection

