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

        <!-- Right: Primary Save Action -->
        <div class="flex items-center gap-2">
            <button 
                type="submit" 
                form="notulen-form" 
                class="button flex items-center gap-2 text-xs font-bold shadow-xs cursor-pointer bg-slate-900 hover:bg-slate-800 text-white"
                title="Simpan seluruh notulensi, kesimpulan, dan berkas foto dokumentasi (Shortcut: Ctrl+S)"
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

        <!-- Right Side: Focus Indicator & Word Counter -->
        <div class="flex items-center gap-3 ml-auto shrink-0">
            <span id="office-active-editor-indicator" class="text-xs font-bold text-slate-800 bg-slate-100 border border-slate-300 px-2.5 py-1 rounded-lg">
                Fokus: Catatan Jalannya Rapat (A)
            </span>
            <div class="hidden xl:flex items-center gap-2 text-[11px] text-slate-600 font-medium">
                <span class="word-counter-words">0 Kata</span>
                <span>&bull;</span>
                <span class="word-counter-chars">0 Karakter</span>
            </div>
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
                    <div class="header-kop" style="margin-bottom: 6pt; text-align: center; width: 100%;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; border-collapse: collapse; border: none; margin: 0; padding: 0; table-layout: fixed;">
                            <colgroup>
                                <col style="width: 64px;">
                                <col style="width: calc(100% - 128px);">
                                <col style="width: 64px;">
                            </colgroup>
                            <tr>
                                <td align="center" valign="middle" style="width: 64px; text-align: center; vertical-align: middle; border: none; padding: 0 0 4px 0;">
                                    <img src="{{ $logoUrl }}" alt="Logo Tut Wuri Handayani" width="52" height="52" style="width: 52px; height: 52px; max-height: 52px; max-width: 52px; object-fit: contain; display: block; margin: 0 auto; border: none;">
                                </td>
                                <td align="center" valign="middle" style="text-align: center; vertical-align: middle; border: none; padding: 0 4px 4px 4px;">
                                    <h3 style="margin: 0; font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
                                        {{ $config['instansi_induk'] ?? 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI' }}
                                    </h3>
                                    <h2 style="margin: 2px 0; font-size: 11.5pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.3px; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
                                        {{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI (LLDIKTI) WILAYAH X' }}
                                    </h2>
                                    <p style="margin: 0; font-size: 8pt; font-style: normal; font-family: 'Times New Roman', Times, serif; line-height: 1.25;">
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
                        <h1 style="font-size: 11.5pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            {{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}
                        </h1>
                        <div style="font-size: 9pt; font-family: 'Courier New', Courier, monospace; margin-top: 2px;">
                            Nomor: {{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}
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
                    <div style="margin-top: 8pt;">
                        <div style="font-size: 9.5pt; font-weight: bold; margin: 0 0 3pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            II. NOTULENSI &amp; KESIMPULAN RAPAT
                        </div>

                        <!-- Bagian A: Catatan Jalannya Rapat (Notulensi) -->
                        <div style="margin-bottom: 6pt;">
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
                                data-placeholder="Ketik catatan jalannya rapat, dinamika diskusi, arahan pimpinan, dan pembahasan di sini..."
                                style="min-height: 180px;"
                            >{!! old('notulensi', $agenda->notulensi) !!}</div>
                            @error('notulensi')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bagian B: Kesimpulan & Rencana Tindak Lanjut (RTL) -->
                        <div>
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
                                data-placeholder="Ketik poin-poin kesimpulan akhir, keputusan yang disepakati, PIC penanggung jawab, dan tenggat waktu penyelesaian..."
                                style="min-height: 140px;"
                            >{!! old('kesimpulan', $agenda->kesimpulan) !!}</div>
                            @error('kesimpulan')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Running Footer Lembar 1 -->
                    <div class="doc-running-footer">
                        <span>SIPERAPAT &bull; LLDIKTI Wilayah X</span>
                        <span class="font-bold">Halaman 1 dari 2</span>
                    </div>
                </div>

                <!-- ==================== PEMISAH ANTAR HALAMAN (NATURAL PAGE BREAK) ==================== -->
                <div class="office-page-separator" aria-hidden="true">
                    <div class="office-page-gap" id="office-page-gap">
                        <div class="gap-line"></div>
                        <div class="gap-indicator">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span>Pemisah Halaman &bull; Menuju Lembar Pengesahan</span>
                        </div>
                        <div class="gap-line"></div>
                    </div>
                </div>

                <!-- ==================== LEMBAR HALAMAN 2 (PENGESAHAN & LAMPIRAN FOTO) ==================== -->
                <div class="office-paper-sheet paper-a4" data-page="2">
                    <!-- Page Number Badge (Top Right Corner) -->
                    <div class="absolute top-3 right-4 text-[10px] font-mono font-bold text-slate-400 select-none print:hidden">
                        HALAMAN 2
                    </div>

                    <!-- Running Header Lembar 2 -->
                    <div class="doc-running-header">
                        <span class="truncate max-w-sm">Berita Acara Rapat: <strong>{{ $agenda->judul_rapat }}</strong></span>
                        <span class="font-bold shrink-0">Halaman 2 dari 2</span>
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
                                    <strong>{{ $config['signer1_role'] ?? 'Pemimpin Rapat' }}</strong>
                                </td>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    {{ $config['signing_city'] ?? 'Padang' }}, {{ $config['signing_date'] ?? now()->translatedFormat('d F Y') }}<br>
                                    <strong>{{ $config['signer2_role'] ?? 'Notulis Rapat' }}</strong>
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
                                    <strong><u>{{ $config['signer1_name'] ?? $agenda->nama_pimpinan }}</u></strong><br>
                                    NIP. {{ $config['signer1_nip'] ?? $agenda->nip_pimpinan }}
                                </td>
                                <td style="text-align: center; vertical-align: top; border: none; padding: 0 8pt; font-size: 9.5pt;">
                                    <strong><u>{{ $config['signer2_name'] ?? $agenda->nama_notulis }}</u></strong><br>
                                    NIP. {{ $config['signer2_nip'] ?? $agenda->nip_notulis }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Catatan Kaki Dokumen Resmi -->
                    <div style="margin: 10pt 0 14pt 0; padding-top: 3pt; border-top: 1px solid #cbd5e1; font-size: 7.5pt; color: #64748b; text-align: center; font-family: 'Times New Roman', Times, serif;">
                        {{ $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X' }} &bull; <span>Dicetak pada {{ now()->translatedFormat('d F Y H:i') }} WIB</span>
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
                    <span class="word-counter-words">0 Kata</span>
                    <span class="text-slate-600">&bull;</span>
                    <span class="word-counter-chars">0 Karakter</span>
                    <span class="text-slate-600">&bull;</span>
                    <span id="office-status-zoom">Zoom: 100%</span>
                </div>
            </div>
        </div>
    </form>
</div>
    </form>
</div>
@endsection

