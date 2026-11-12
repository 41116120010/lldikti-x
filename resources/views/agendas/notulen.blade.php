@extends('layouts.app')

@section('title', 'Notulensi & Berita Acara — ' . $agenda->judul_rapat)
@section('heading', 'Pengolah Kata Notulensi & Berita Acara')
@section('subtitle', 'Agenda: ' . $agenda->judul_rapat)

@section('content')
@php
    $defaultBundledLogoUrl = asset('images/tut-wuri-handayani.png');
    $currentSavedLogoUrl = (!empty($config['custom_logo_path']) && Storage::disk('public')->exists($config['custom_logo_path']))
        ? Storage::disk('public')->url($config['custom_logo_path'])
        : $defaultBundledLogoUrl;
@endphp
<div
    id="office-workstation"
    class="space-y-4"
    data-agenda-title="{{ $agenda->judul_rapat }}"
    data-default-logo-src="{{ $defaultBundledLogoUrl }}"
    data-current-logo-src="{{ $currentSavedLogoUrl }}"
>
    <!-- 1. Workstation Top Control Bar -->
    <div class="bg-white border border-slate-300 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <!-- Left: Back Navigation -->
        <div class="flex items-center gap-3">
            <a
                href="{{ auth()->user()->isStaff() ? route('agendas.show', $agenda) : route('admin.agendas.show', $agenda) }}"
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

        <!-- Center: Fixed A4 Paper Indicator & Zoom Controls -->
        <div class="flex items-center gap-2">
            <!-- Fixed Paper Size Indicator (A4 Standard) -->
            <div
                data-paper-size="a4"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 rounded-xl border border-slate-300 text-xs font-bold text-slate-800 select-none"
                title="Ukuran kertas dokumen resmi ditetapkan pada standar A4 (210 × 297 mm)"
                aria-label="Ukuran Kertas Tetap A4 (210 × 297 mm)"
            >
                <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-700"><rect width="16" height="20" x="4" y="2" rx="2"/></svg>
                <span>A4 (210 × 297 mm)</span>
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

            <!-- 1.3 Foto Dokumentasi Modal Trigger -->
            <button
                type="button"
                onclick="openDokumentasiModal()"
                class="button flex items-center gap-2 text-xs font-semibold shadow-xs cursor-pointer border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 relative"
                title="Kelola foto dokumentasi kegiatan rapat (di luar lembar dokumen resmi)"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                    <circle cx="9" cy="9" r="2"/>
                    <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                </svg>
                <span>Lampiran Foto</span>
                <span id="nav-photo-count" class="px-1.5 py-0.2 rounded-full bg-slate-100 border border-slate-300 text-[10px] font-bold text-slate-700" data-stored-count="{{ $documentations->count() }}">
                    {{ $documentations->count() }}
                </span>
            </button>

            <!-- 1.4 Surat Undangan / Edaran Modal Trigger (if attached) -->
            @if($agenda->surat_edaran_path)
            <button
                type="button"
                onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')"
                class="button flex items-center gap-2 text-xs font-semibold shadow-xs cursor-pointer border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 min-h-[36px]"
                title="Buka pratinjau surat undangan / edaran resmi rapat ini"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y1="13"/><line x1="16" y1="17" x2="8" y1="17"/>
                </svg>
                <span>Surat Undangan</span>
                <span class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase {{ $agenda->is_surat_edaran_pdf ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ $agenda->surat_edaran_extension }}
                </span>
            </button>
            @endif

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
                        href="{{ route('admin.reports.export.pdf', $agenda) }}"
                        data-export-link="pdf"
                        class="flex items-center gap-2 px-3.5 py-2.5 min-h-[44px] text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                        <span>Unduh PDF Resmi (.pdf)</span>
                    </a>
                    <a
                        href="{{ route('admin.reports.export.word', $agenda) }}"
                        data-export-link="word"
                        class="flex items-center gap-2 px-3.5 py-2.5 min-h-[44px] text-slate-700 hover:bg-slate-100 hover:text-slate-900 font-medium"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-blue-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                        <span>Unduh Dokumen Word (.docx)</span>
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
                    <!-- Kop Surat Resmi Instansi - definisi tunggal, sama persis dengan ekspor Word/PDF -->
                    @php
                        $kopLogoSrc = null;
                        $kopLogoSize = null;

                        if ($config['show_logo'] ?? true) {
                            $kopSourceFile = null;

                            $customLogo = $config['custom_logo_path'] ?? null;
                            if ($customLogo && Storage::disk('public')->exists($customLogo)) {
                                $kopLogoSrc = Storage::disk('public')->url($customLogo);
                                $kopSourceFile = Storage::disk('public')->path($customLogo);
                            } else {
                                $bundledLogo = public_path('images/tut-wuri-handayani.png');
                                if (is_file($bundledLogo)) {
                                    $kopLogoSrc = asset('images/tut-wuri-handayani.png');
                                    $kopSourceFile = $bundledLogo;
                                }
                            }

                            // Rasio asli logo agar tidak terdistorsi. Importer Word
                            // mengabaikan object-fit, sehingga lebar dan tinggi
                            // harus benar-benar sesuai proporsi berkas.
                            if ($kopSourceFile !== null && is_file($kopSourceFile)) {
                                $kopInfo = @getimagesize($kopSourceFile);
                                if (!empty($kopInfo) && $kopInfo[0] > 0 && $kopInfo[1] > 0) {
                                    $kopLogoSize = [$kopInfo[0], $kopInfo[1]];
                                }
                            }
                        }
                    @endphp
                    @include('partials.kop_surat', ['kopLogoSrc' => $kopLogoSrc, 'kopLogo' => $kopLogoSize])

                    <!-- Judul Dokumen & Nomor Berita Acara -->
                    <div class="doc-title" style="text-align: center; margin: {{ \App\Support\DocumentLayout::DOC_TITLE_GAP_TOP_PT }}pt 0 {{ \App\Support\DocumentLayout::DOC_TITLE_GAP_BOTTOM_PT }}pt 0;">
                        <h1 id="sheet-document-title" style="font-size: {{ \App\Support\DocumentLayout::DOCUMENT_TITLE_SIZE_PT }}pt; font-weight: bold; text-decoration: underline; margin: 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            {{ $config['document_title'] ?? 'BERITA ACARA DAN DAFTAR HADIR RAPAT' }}
                        </h1>
                        <div id="sheet-document-number-wrapper" style="font-size: {{ \App\Support\DocumentLayout::BODY_SIZE_PT }}pt; font-family: 'Times New Roman', Times, serif; margin-top: 2px; {{ ($config['show_document_number'] ?? true) ? '' : 'display: none;' }}">
                            Nomor: <span id="sheet-document-number">{{ $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT)) }}</span>
                        </div>
                    </div>

                    <!-- Informasi Pelaksanaan Rapat -->
                    <table id="sheet-meeting-info" class="info-table" width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; margin-bottom: {{ \App\Support\DocumentLayout::BLOCK_GAP_BOTTOM_PT }}pt; border-collapse: collapse; font-size: {{ \App\Support\DocumentLayout::INFO_SIZE_PT }}pt; border: none; font-family: 'Times New Roman', Times, serif; {{ ($config['show_meeting_info'] ?? true) ? '' : 'display: none;' }}">
                        <tr>
                            <td style="font-weight: bold; padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_LABEL_COLUMN }};">Perihal / Agenda</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_COLON_COLUMN }};">:</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: {{ \App\Support\DocumentLayout::DETAIL_VALUE_COLUMN }};"><strong id="sheet-info-title" data-default-title="{{ $agenda->judul_rapat }}">{{ $config['custom_agenda_title'] ?? $agenda->judul_rapat }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_LABEL_COLUMN }};">Hari / Tanggal</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_COLON_COLUMN }};">:</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: {{ \App\Support\DocumentLayout::DETAIL_VALUE_COLUMN }};">{{ $agenda->waktu_mulai->translatedFormat('l, d F Y') }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_LABEL_COLUMN }};">Waktu Pelaksanaan</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_COLON_COLUMN }};">:</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: {{ \App\Support\DocumentLayout::DETAIL_VALUE_COLUMN }};">{{ $agenda->waktu_mulai->format('H:i') }} {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_LABEL_COLUMN }};">Format &amp; Tempat</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_COLON_COLUMN }};">:</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: {{ \App\Support\DocumentLayout::DETAIL_VALUE_COLUMN }};">{{ ucfirst($agenda->tipe_rapat) }} &mdash; <span id="sheet-info-location" data-default-location="{{ $agenda->lokasi_ruang ?? 'Daring (Online Meeting)' }}">{{ $config['custom_location'] ?? ($agenda->lokasi_ruang ?? 'Daring (Online Meeting)') }}</span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_LABEL_COLUMN }};">Penyelenggara Rapat</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; width: {{ \App\Support\DocumentLayout::DETAIL_COLON_COLUMN }};">:</td>
                            <td style="padding: {{ \App\Support\DocumentLayout::INFO_CELL_PAD_PT }}pt 0; vertical-align: top; border: none; word-wrap: break-word; overflow-wrap: break-word; width: {{ \App\Support\DocumentLayout::DETAIL_VALUE_COLUMN }};">{{ $agenda->creator?->name ?? 'Penyelenggara Rapat' }} ({{ $agenda->creator?->unit?->nama_unit ?? 'Tingkat Lembaga' }})</td>
                        </tr>
                    </table>

                    <!-- Seksi I: Notulensi & Kesimpulan Rapat (Interactive WYSIWYG Editors) -->
                    <div id="sheet-seksi-1-container" style="margin-top: {{ \App\Support\DocumentLayout::SECTION_GAP_TOP_PT }}pt; {{ (($config['show_notulensi'] ?? true) || ($config['show_kesimpulan'] ?? true)) ? '' : 'display: none;' }}">
                        <div id="sheet-seksi-1-title" class="section-title" style="font-size: {{ \App\Support\DocumentLayout::SECTION_SIZE_PT }}pt; font-weight: bold; margin: 0 0 {{ \App\Support\DocumentLayout::SECTION_GAP_BOTTOM_PT }}pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                            I. NOTULENSI &amp; KESIMPULAN RAPAT
                        </div>

                        <!-- Bagian A: Catatan Jalannya Rapat (Notulensi) -->
                        <div id="sheet-section-notulensi-wrapper" style="margin-bottom: {{ \App\Support\DocumentLayout::BLOCK_GAP_BOTTOM_PT }}pt; {{ ($config['show_notulensi'] ?? true) ? '' : 'display: none;' }}">
                            <div class="flex items-center justify-between mb-1">
                                <label for="notulensi-content" style="font-weight: bold; font-size: {{ \App\Support\DocumentLayout::SUBHEADING_SIZE_PT }}pt; font-family: 'Times New Roman', Times, serif;" class="text-slate-900">
                                    A. Catatan Jalannya Rapat (Notulensi):
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
                                style="min-height: 80px; font-size: {{ \App\Support\DocumentLayout::BODY_SIZE_PT }}pt; line-height: {{ \App\Support\DocumentLayout::BODY_LINE_PT }}pt; text-align: justify; background: #{{ \App\Support\DocumentLayout::PROSE_BOX_FILL }}; border: {{ \App\Support\DocumentLayout::BORDER_PT }}pt solid #{{ \App\Support\DocumentLayout::BORDER_COLOR }}; border-radius: 0; padding: {{ \App\Support\DocumentLayout::PROSE_PAD_TOP_PT }}pt {{ \App\Support\DocumentLayout::PROSE_PAD_SIDE_PT }}pt;"
                            >{!! \App\Support\Html::sanitize(old('notulensi', $agenda->notulensi)) !!}</div>
                            @error('notulensi')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Bagian B: Kesimpulan & Rencana Tindak Lanjut (RTL) -->
                        <div id="sheet-section-kesimpulan-wrapper" style="{{ ($config['show_kesimpulan'] ?? true) ? '' : 'display: none;' }}">
                            <div class="flex items-center justify-between mb-1">
                                <label for="kesimpulan-content" style="font-weight: bold; font-size: {{ \App\Support\DocumentLayout::SUBHEADING_SIZE_PT }}pt; font-family: 'Times New Roman', Times, serif;" class="text-slate-900">
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
                                style="min-height: 70px; font-size: {{ \App\Support\DocumentLayout::BODY_SIZE_PT }}pt; line-height: {{ \App\Support\DocumentLayout::BODY_LINE_PT }}pt; text-align: justify; background: #{{ \App\Support\DocumentLayout::PROSE_BOX_FILL }}; border: {{ \App\Support\DocumentLayout::BORDER_PT }}pt solid #{{ \App\Support\DocumentLayout::BORDER_COLOR }}; border-radius: 0; padding: {{ \App\Support\DocumentLayout::PROSE_PAD_TOP_PT }}pt {{ \App\Support\DocumentLayout::PROSE_PAD_SIDE_PT }}pt;"
                            >{!! \App\Support\Html::sanitize(old('kesimpulan', $agenda->kesimpulan)) !!}</div>
                            @error('kesimpulan')
                                <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Blok Pengesahan & Tanda Tangan (Dynamic Word Signature Flow) -->
                    <div id="sheet-pengesahan-final" class="sheet-signature-wrapper" style="margin-top: {{ \App\Support\DocumentLayout::TTD_GAP_PT }}pt;">
                        <!-- Header if on standalone page -->
                        <div id="sheet-signature-standalone-header" style="display: none; text-align: center; margin-bottom: 8pt; padding-bottom: 4pt; border-bottom: 1.5pt solid #000;">
                            <h2 style="font-size: 11pt; font-weight: bold; text-transform: uppercase; margin: 0; font-family: 'Times New Roman', Times, serif;">
                                LEMBAR PENGESAHAN BERITA ACARA
                            </h2>
                            <div style="font-size: 8.5pt; color: #334155; margin-top: 2pt; font-family: 'Times New Roman', Times, serif;">
                                Pelaksanaan: {{ $agenda->waktu_mulai->translatedFormat('d F Y') }} &bull; Format: {{ ucfirst($agenda->tipe_rapat) }}
                            </div>
                        </div>

                        <!-- Tanda Tangan Pengesahan. Satu loop untuk semua penanda
                             tangan, dengan tiga baris: label, tanda tangan, nama. -->
                        @php
                            $pimpinanAtt = $agenda->pimpinan_attendance;
                            $notulisAtt = $agenda->notulis_attendance;
                            $showSigner3Initial = (bool) ($config['show_signer3'] ?? false);

                            $signatureSigners = [
                                [
                                    'label' => 'Mengetahui,',
                                    'role' => $config['signer1_role'] ?? 'Pemimpin Rapat',
                                    'roleId' => 'sheet-signer1-role',
                                    'name' => $config['signer1_name'] ?? $agenda->nama_pimpinan,
                                    'nameId' => 'sheet-signer1-name',
                                    'nip' => $config['signer1_nip'] ?? $agenda->nip_pimpinan,
                                    'nipId' => 'sheet-signer1-nip',
                                    'mark' => $pimpinanAtt?->signature_path,
                                    'markId' => 'sheet-signer1-mark',
                                    'showMark' => $config['show_signer1_signature'] ?? true,
                                    'isSigner3' => false,
                                ],
                                [
                                    'label' => 'Menyetujui,',
                                    'role' => $config['signer3_role'] ?? 'Kepala LLDIKTI Wilayah X',
                                    'roleId' => 'sheet-signer3-role',
                                    'name' => ($config['signer3_name'] ?? '') !== '' ? $config['signer3_name'] : '-',
                                    'nameId' => 'sheet-signer3-name',
                                    'nip' => ($config['signer3_nip'] ?? '') !== '' ? $config['signer3_nip'] : '-',
                                    'nipId' => 'sheet-signer3-nip',
                                    'mark' => null,
                                    'markId' => 'sheet-signer3-mark',
                                    'showMark' => false,
                                    'isSigner3' => true,
                                ],
                                [
                                    'label' => '<span id="sheet-signing-city">'
                                        .e($config['signing_city'] ?? 'Padang').'</span>, '
                                        .'<span id="sheet-signing-date">'
                                        .e($config['signing_date'] ?? ($agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d F Y') : now()->translatedFormat('d F Y')))
                                        .'</span>',
                                    'role' => $config['signer2_role'] ?? 'Notulis Rapat',
                                    'roleId' => 'sheet-signer2-role',
                                    'name' => $config['signer2_name'] ?? $agenda->nama_notulis,
                                    'nameId' => 'sheet-signer2-name',
                                    'nip' => $config['signer2_nip'] ?? $agenda->nip_notulis,
                                    'nipId' => 'sheet-signer2-nip',
                                    'mark' => $notulisAtt?->signature_path,
                                    'markId' => 'sheet-signer2-mark',
                                    'showMark' => $config['show_signer2_signature'] ?? true,
                                    'isSigner3' => false,
                                ],
                            ];

                            $signatureColWidth = $showSigner3Initial ? '33.4%' : '50%';
                            $signatureInner = $showSigner3Initial
                                ? \App\Support\DocumentLayout::TTD_INNER_WIDTH_THREE_COLUMN
                                : \App\Support\DocumentLayout::TTD_INNER_WIDTH_TWO_COLUMN;
                            $signatureFont = 'font-size: '.\App\Support\DocumentLayout::BODY_SIZE_PT
                                .'pt; font-family: \'Times New Roman\', Times, serif;';
                        @endphp
                        <div class="signature-block" style="margin-top: {{ \App\Support\DocumentLayout::TTD_GAP_PT }}pt; page-break-inside: avoid; break-inside: avoid;">
                            <table id="sheet-signature-table" class="signature-table" width="100%" border="0" cellspacing="0" cellpadding="0" data-inner-two="{{ \App\Support\DocumentLayout::TTD_INNER_WIDTH_TWO_COLUMN }}" data-inner-three="{{ \App\Support\DocumentLayout::TTD_INNER_WIDTH_THREE_COLUMN }}" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: none; font-family: 'Times New Roman', Times, serif;">
                                <colgroup>
                                    @foreach($signatureSigners as $signatureSigner)
                                        <col @if($signatureSigner['isSigner3']) data-signer3-col @endif data-signer-col style="width: {{ $signatureColWidth }}; {{ ($signatureSigner['isSigner3'] && !$showSigner3Initial) ? 'display: none;' : '' }}">
                                    @endforeach
                                </colgroup>
                                <tr>
                                    @foreach($signatureSigners as $signatureSigner)
                                        <td @if($signatureSigner['isSigner3']) data-signer3-col @endif data-signer-col style="width: {{ $signatureColWidth }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; {{ ($signatureSigner['isSigner3'] && !$showSigner3Initial) ? 'display: none;' : '' }}">
                                            <div data-signer-inner style="display: inline-block; width: {{ $signatureInner }}; text-align: left;">
                                                <span style="{{ $signatureFont }}">{!! $signatureSigner['label'] !!}</span><br>
                                                <strong style="{{ $signatureFont }}">@if($signatureSigner['roleId'])<span id="{{ $signatureSigner['roleId'] }}">@endif{{ $signatureSigner['role'] }}@if($signatureSigner['roleId'])</span>@endif</strong>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach($signatureSigners as $signatureSigner)
                                        <td @if($signatureSigner['isSigner3']) data-signer3-col @endif data-signer-col style="width: {{ $signatureColWidth }}; text-align: center; vertical-align: middle; border: none; padding: 1pt 0; height: {{ \App\Support\DocumentLayout::TTD_ROW_HEIGHT_PT }}pt; {{ ($signatureSigner['isSigner3'] && !$showSigner3Initial) ? 'display: none;' : '' }}">
                                            <div id="{{ $signatureSigner['markId'] }}" data-signer-inner style="display: inline-block; width: {{ $signatureInner }}; text-align: left; {{ $signatureSigner['showMark'] ? '' : 'display: none;' }}">
                                                @if($signatureSigner['mark'])
                                                    <img src="{{ Storage::disk('public')->url($signatureSigner['mark']) }}" alt="TTD" width="95" height="32" style="width: 95px; height: 32px; max-height: 34px; max-width: 100px; object-fit: contain; display: block; border: none;">
                                                @endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                                <tr>
                                    @foreach($signatureSigners as $signatureSigner)
                                        <td @if($signatureSigner['isSigner3']) data-signer3-col @endif data-signer-col style="width: {{ $signatureColWidth }}; text-align: center; vertical-align: top; border: none; padding: 0 4pt; {{ ($signatureSigner['isSigner3'] && !$showSigner3Initial) ? 'display: none;' : '' }}">
                                            <div data-signer-inner style="display: inline-block; width: {{ $signatureInner }}; text-align: left; {{ $signatureFont }}">
                                                <strong>@if($signatureSigner['nameId'])<span id="{{ $signatureSigner['nameId'] }}">@endif{{ $signatureSigner['name'] }}@if($signatureSigner['nameId'])</span>@endif</strong><br>
                                                NIP @if($signatureSigner['nipId'])<span id="{{ $signatureSigner['nipId'] }}">@endif{{ $signatureSigner['nip'] }}@if($signatureSigner['nipId'])</span>@endif
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        <!-- Catatan Kaki Dokumen Resmi (Selalu ada di DOM agar live toggle berfungsi) -->
                        <div id="sheet-footer-note-block" class="document-footer-note" style="margin: {{ \App\Support\DocumentLayout::FOOTER_NOTE_GAP_PT }}pt 0 0 0; padding-top: {{ \App\Support\DocumentLayout::FOOTER_NOTE_PAD_TOP_PT }}pt; border-top: {{ \App\Support\DocumentLayout::FOOTER_NOTE_RULE_PT }}pt solid #{{ \App\Support\DocumentLayout::FOOTER_NOTE_RULE_COLOR }}; font-size: {{ \App\Support\DocumentLayout::FOOTER_NOTE_SIZE_PT }}pt; color: #{{ \App\Support\DocumentLayout::FOOTER_NOTE_COLOR }}; text-align: center; font-family: 'Times New Roman', Times, serif; page-break-inside: avoid; break-inside: avoid; {{ (($config['show_footer_note'] ?? false) && trim((string) ($config['footer_note'] ?? '')) !== '') ? '' : 'display: none;' }}">
                            <span id="sheet-footer-note">{{ $config['footer_note'] ?? '' }}</span>
                        </div>
                    </div>

                    <!-- Seksi II: Daftar Kehadiran Peserta (Spreadsheet / Word Table) -->
                    <div id="sheet-seksi-2-wrapper" style="{{ ($config['show_attendees'] ?? true) ? '' : 'display: none;' }}">
                    <div id="sheet-seksi-2-container" class="section-title" style="font-size: {{ \App\Support\DocumentLayout::SECTION_SIZE_PT }}pt; font-weight: bold; margin: {{ \App\Support\DocumentLayout::SECTION_GAP_TOP_PT + 6 }}pt 0 {{ \App\Support\DocumentLayout::SECTION_GAP_BOTTOM_PT }}pt 0; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                        II. DAFTAR KEHADIRAN PESERTA ({{ $attendances->count() }} Orang)
                    </div>
                    @php
                        // Lebar kolom dihitung sekali di sini, mengikuti urutan yang
                        // sama dengan writer .docx: kolom nama mengambil sisa dari
                        // lebar cetakan. Menyalin persentase tetap per kolom
                        // itulah yang membuat pratinjau dan dokumen cetak tidak
                        // pernah bisa sama begitu lebar kolom berubah.
                        $presenceColumns = \App\Support\DocumentLayout::attendanceColumns();
                        $presenceWidths = [
                            'no' => $presenceColumns[0],
                            'nip' => $presenceColumns[1],
                            'unit' => $presenceColumns[2],
                            'time' => $presenceColumns[3],
                            'photo' => $presenceColumns[4],
                            'sign' => $presenceColumns[5],
                        ];
                        $presenceUsed = (float) rtrim($presenceWidths['no'], '%')
                            + (float) rtrim($presenceWidths['sign'], '%');
                        foreach ([
                            'nip' => 'show_nip',
                            'unit' => 'show_unit',
                            'time' => 'show_attendance_time',
                            'photo' => 'show_selfie_photos',
                        ] as $presenceKey => $presenceOption) {
                            if ($config[$presenceOption] ?? true) {
                                $presenceUsed += (float) rtrim($presenceWidths[$presenceKey], '%');
                            }
                        }
                        $presenceWidths['name'] = max(0, 100 - $presenceUsed).'%';

                        $presenceHeaderPad = fn (string $side): string => '3pt '.$side;
                    @endphp
                    <table id="sheet-attendance-table" class="doc-spreadsheet-table attendance-table" width="100%" border="1" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border: {{ \App\Support\DocumentLayout::BORDER_PT }}px solid #{{ \App\Support\DocumentLayout::BORDER_COLOR }}; font-size: {{ \App\Support\DocumentLayout::ATTENDANCE_SIZE_PT }}pt; line-height: {{ \App\Support\DocumentLayout::ATTENDANCE_LINE_PT }}pt; font-family: 'Times New Roman', Times, serif;">
                        <colgroup>
                            <col data-col="no" data-col-width="{{ $presenceWidths['no'] }}" style="width: {{ $presenceWidths['no'] }};">
                            <col data-col="name" style="width: {{ $presenceWidths['name'] }};">
                            <col data-col="nip" data-col-width="{{ $presenceWidths['nip'] }}" style="width: {{ $presenceWidths['nip'] }}; {{ ($config['show_nip'] ?? true) ? '' : 'display: none;' }}">
                            <col data-col="unit" data-col-width="{{ $presenceWidths['unit'] }}" style="width: {{ $presenceWidths['unit'] }}; {{ ($config['show_unit'] ?? true) ? '' : 'display: none;' }}">
                            <col data-col="time" data-col-width="{{ $presenceWidths['time'] }}" style="width: {{ $presenceWidths['time'] }}; {{ ($config['show_attendance_time'] ?? true) ? '' : 'display: none;' }}">
                            <col data-col="photo" data-col-width="{{ $presenceWidths['photo'] }}" style="width: {{ $presenceWidths['photo'] }}; {{ ($config['show_selfie_photos'] ?? true) ? '' : 'display: none;' }}">
                            <col data-col="sign" data-col-width="{{ $presenceWidths['sign'] }}" style="width: {{ $presenceWidths['sign'] }};">
                        </colgroup>
                        <thead>
                            <tr style="background-color: #{{ \App\Support\DocumentLayout::ATTENDANCE_HEADER_FILL }};">
                                <th style="text-align: center;">No</th>
                                <th style="text-align: left;">Nama Lengkap</th>
                                <th data-col="nip" style="text-align: left; white-space: nowrap; {{ ($config['show_nip'] ?? true) ? '' : 'display: none;' }}">NIP</th>
                                <th data-col="unit" style="text-align: left; {{ ($config['show_unit'] ?? true) ? '' : 'display: none;' }}">Unit Kerja / Pokja</th>
                                <th data-col="time" style="text-align: center; {{ ($config['show_attendance_time'] ?? true) ? '' : 'display: none;' }}">Waktu</th>
                                <th data-col="photo" style="text-align: center; {{ ($config['show_selfie_photos'] ?? true) ? '' : 'display: none;' }}">Foto Kehadiran</th>
                                <th style="text-align: center;">Tanda Tangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $index => $att)
                                <tr>
                                    <td style="text-align: center; vertical-align: middle;">{{ $index + 1 }}</td>
                                    <td style="vertical-align: middle; padding: 2pt 4pt;"><strong>{{ $att->user->name }}</strong></td>
                                    <td data-col="nip" style="vertical-align: middle; padding: 2pt 3pt; white-space: nowrap; {{ ($config['show_nip'] ?? true) ? '' : 'display: none;' }}">{{ $att->user->nip }}</td>
                                    <td data-col="unit" style="vertical-align: middle; padding: 2pt 3pt; {{ ($config['show_unit'] ?? true) ? '' : 'display: none;' }}">{{ $att->user->unit?->kode_unit ?? 'Pusat' }}</td>
                                    <td data-col="time" style="text-align: center; vertical-align: middle; padding: 2pt 2pt; {{ ($config['show_attendance_time'] ?? true) ? '' : 'display: none;' }}">{{ $att->signed_at?->format('H:i') ?? '-' }}</td>
                                    <td data-col="photo" style="text-align: center; vertical-align: middle; padding: 1.5pt; {{ ($config['show_selfie_photos'] ?? true) ? '' : 'display: none;' }}">
                                        @if($att->selfie_path)
                                            <button
                                                type="button"
                                                data-selfie-modal-trigger
                                                data-selfie-url="{{ Storage::disk('public')->url($att->selfie_path) }}"
                                                data-user-name="{{ $att->user->name }}"
                                                data-user-nip="{{ $att->user->nip ?? '-' }}"
                                                data-unit-name="{{ $att->user->unit?->kode_unit ? $att->user->unit->kode_unit . ' — ' . $att->user->unit->nama_unit : 'Tingkat Lembaga' }}"
                                                data-signed-at="{{ $att->signed_at?->format('d/m/Y H:i:s') ?? '-' }} WIB"
                                                data-ip-address="{{ $att->ip_address ?? '127.0.0.1' }}"
                                                class="inline-block p-0 m-0 border-0 bg-transparent cursor-pointer hover:opacity-80 transition"
                                                style="vertical-align: middle; line-height: 0;"
                                                title="Klik untuk memverifikasi foto selfie {{ $att->user->name }}"
                                                aria-label="Verifikasi foto selfie {{ $att->user->name }}"
                                            >
                                                <img src="{{ Storage::disk('public')->url($att->selfie_path) }}" alt="Selfie" width="26" height="26" style="width: 26px; height: 26px; object-fit: cover; border-radius: 2px; border: 1px solid #cbd5e1; display: inline-block; vertical-align: middle;">
                                            </button>
                                        @else
                                            <span style="font-size: 7pt; color: #94a3b8; font-style: italic;">Tanpa Foto</span>
                                        @endif
                                    </td>
                                    <td style="text-align: center; vertical-align: middle; padding: 1.5pt;">
                                        @if($att->signature_path)
                                            <img class="attendee-sig-img" src="{{ Storage::disk('public')->url($att->signature_path) }}" alt="TTD" width="75" height="22" style="width: 75px; height: 22px; max-height: 24px; object-fit: contain; display: {{ ($config['show_attendee_signatures'] ?? true) ? 'block' : 'none' }}; margin: 0 auto; border: none;">
                                            <span class="attendee-sig-fallback" style="font-size: 7.5pt; color: #166534; font-weight: bold; display: {{ ($config['show_attendee_signatures'] ?? true) ? 'none' : 'inline' }};">(HADIR)</span>
                                        @else
                                            <span style="font-size: 7.5pt; color: #166534; font-weight: bold;">(HADIR)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td id="sheet-attendance-empty-cell" colspan="{{ 2 + ($config['show_nip'] ?? true ? 1 : 0) + ($config['show_unit'] ?? true ? 1 : 0) + ($config['show_attendance_time'] ?? true ? 1 : 0) + ($config['show_selfie_photos'] ?? true ? 1 : 0) }}" style="text-align: center; padding: 8pt; font-style: italic; color: #64748b;">
                                        Belum ada peserta yang mengisi presensi rapat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <!-- Lampiran Bersih Foto Dokumentasi pada Lembar Cetak (Selalu ada di DOM agar sinkronisasi live berfungsi) -->
                    <div id="sheet-documentation-annex" class="sheet-documentation-annex" style="margin-top: 10pt; padding-top: 8pt; border-top: 1.5px solid #000; {{ ($documentations->count() > 0 && ($config['show_documentation'] ?? true)) ? '' : 'display: none;' }}">
                        <div style="font-size: {{ \App\Support\DocumentLayout::SECTION_SIZE_PT }}pt; font-weight: bold; margin-bottom: {{ \App\Support\DocumentLayout::BLOCK_GAP_BOTTOM_PT }}pt; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; text-align: center;">
                            III. LAMPIRAN FOTO DOKUMENTASI KEGIATAN
                        </div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="4" style="width: 100%; border-collapse: collapse;">
                            <tbody id="sheet-documentation-tbody">
                                @foreach($documentations->chunk(2) as $chunk)
                                    <tr>
                                        @foreach($chunk as $doc)
                                            <td width="50%" align="center" valign="top" style="padding: 4pt; border: none;" data-sheet-doc-id="{{ $doc->id }}">
                                                <div
                                                    class="cursor-pointer group/doc hover:opacity-90 transition"
                                                    data-doc-modal-trigger
                                                    data-doc-gallery="notulen-sheet-docs"
                                                    data-doc-url="{{ Storage::disk('public')->url($doc->file_path) }}"
                                                    data-doc-caption="{{ $doc->caption ?? basename($doc->file_path) }}"
                                                    data-doc-agenda="{{ $agenda->judul_rapat }}"
                                                    data-doc-date="{{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                                                    style="border: 1px solid #94a3b8; padding: 3pt; background: #ffffff; text-align: center;"
                                                    title="Klik untuk pratinjau foto perbesar"
                                                >
                                                    <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="Foto Dokumentasi" style="width: 100%; max-height: 120px; object-fit: contain; display: block; margin: 0 auto;">
                                                    <div id="sheet-doc-caption-{{ $doc->id }}" style="font-size: 7.5pt; color: #334155; margin-top: 2pt; font-style: italic; font-family: 'Times New Roman', Times, serif; {{ $doc->caption ? '' : 'display: none;' }}">{{ $doc->caption }}</div>
                                                </div>
                                            </td>
                                        @endforeach
                                        @if($chunk->count() === 1)
                                            <td width="50%" style="border: none;">&nbsp;</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Running Footer Lembar 1 -->
                    <div class="doc-running-footer">
                        <span>SIPERAPAT &bull; LLDIKTI Wilayah X</span>
                        <span class="doc-page-number font-bold">Halaman 1 dari 1</span>
                    </div>
                </div>

                <!-- ==================== PEMISAH ANTAR HALAMAN (NATURAL PAGE BREAK) ==================== -->
                <div class="office-page-separator" id="page-separator-1" style="display: none;" aria-hidden="true">
                    <div class="office-page-gap" id="office-page-gap">
                        <div class="gap-line"></div>
                        <div class="gap-indicator">
                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            <span class="gap-indicator-text">Pemisah Halaman &bull; Menuju Halaman 2</span>
                        </div>
                        <div class="gap-line"></div>
                    </div>
                </div>

                <!-- Wadah Lembar Lanjutan Dinamis (Sheet 2, 3, dst.) -->
                <div id="dynamic-continuation-sheets"></div>

            </div>

            <!-- 5. Word Office Bottom Status Bar -->
            <div class="office-status-bar" id="office-status-bar" aria-label="Bilah Status Dokumen">
                <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                    <span id="office-status-page" class="font-semibold text-slate-200">Halaman 1 dari 1</span>
                    <span class="text-slate-600">&bull;</span>
                    <span id="office-status-words" class="text-slate-300">0 Kata</span>
                    <span class="text-slate-600 hidden sm:inline">&bull;</span>
                    <span id="office-status-size" class="hidden sm:inline">A4 (210 × 297 mm)</span>
                </div>
                <div class="flex items-center gap-3">
                    <span id="office-status-save-state" class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold">
                        <span id="office-status-save-dot" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span id="office-status-save-label">Tersimpan</span>
                    </span>
                    <span class="text-slate-600">&bull;</span>
                    <span id="office-status-zoom">Zoom: 100%</span>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- MODAL KELOLA FOTO DOKUMENTASI RAPAT (DI LUAR LEMBAR DOKUMEN FISIK)        -->
        <!-- ========================================================================= -->
        <div
            id="modal-notulen-dokumentasi"
            class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 overflow-y-auto transition-opacity"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modal-notulen-dokumentasi-title"
        >
            <div class="bg-white border border-slate-300 rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden my-auto max-h-[calc(100dvh-2rem)] flex flex-col animate-in fade-in zoom-in-95 duration-150">
                <!-- 1. Header -->
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2"/>
                                <circle cx="9" cy="9" r="2"/>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>
                            </svg>
                        </div>
                        <div>
                            <h3 id="modal-notulen-dokumentasi-title" class="text-sm font-bold text-slate-900">Kelola Lampiran Foto Dokumentasi</h3>
                            <p class="text-[11px] text-slate-500 font-medium truncate max-w-md">{{ $agenda->judul_rapat }}</p>
                        </div>
                    </div>
                    <button type="button" onclick="closeDokumentasiModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition cursor-pointer" aria-label="Tutup modal">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <!-- 2. Body -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1 text-xs">
                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-900 leading-relaxed flex items-start gap-2">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5 text-blue-600"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <div>
                            <strong>Dokumentasi Sah Rapat</strong>
                            <p class="text-[11px] text-blue-800 mt-0.5">Unggah foto suasana rapat, paparan materi, atau dokumen pendukung. Berkas yang dipilih di sini akan otomatis diunggah saat Anda menekan tombol <strong>Simpan Notulensi</strong>.</p>
                        </div>
                    </div>

                    <!-- Interactive Dropzone -->
                    <div id="photo-uploader-container" class="space-y-3">
                        <div
                            id="photo-dropzone"
                            class="border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/75 rounded-xl p-6 text-center transition cursor-pointer relative group font-sans"
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
                                <div class="w-10 h-10 rounded-full bg-slate-200 group-hover:bg-slate-300 transition flex items-center justify-center text-slate-700">
                                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-900 group-hover:underline">Klik untuk memilih foto lampiran</span>
                                    <span class="text-xs text-slate-500"> atau seret &amp; jatuhkan berkas ke sini</span>
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
                        <div id="photo-preview-grid" class="hidden grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 font-sans">
                        </div>
                    </div>

                    <!-- Galeri Foto yang Sudah Tersimpan -->
                    @if($documentations->count() > 0)
                        <div id="modal-stored-docs-section" class="mt-4 pt-3 border-t border-slate-200 font-sans">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wide">
                                    Foto Tersimpan di Database (<span id="modal-stored-photo-count">{{ $documentations->count() }}</span>)
                                </h4>
                            </div>
                            <div id="modal-stored-docs-grid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                @foreach($documentations as $doc)
                                    <div
                                        id="stored-doc-card-{{ $doc->id }}"
                                        data-stored-doc-card="{{ $doc->id }}"
                                        data-doc-url="{{ Storage::disk('public')->url($doc->file_path) }}"
                                        data-doc-filename="{{ basename($doc->file_path) }}"
                                        data-doc-date="{{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                                        class="relative group bg-slate-50 rounded-xl overflow-hidden border border-slate-300 shadow-xs flex flex-col justify-between"
                                    >
                                        <button
                                            type="button"
                                            class="w-full text-left relative block cursor-pointer group/thumb focus:outline-hidden"
                                            data-doc-modal-trigger
                                            data-doc-gallery="modal-stored-docs"
                                            data-doc-url="{{ Storage::disk('public')->url($doc->file_path) }}"
                                            data-doc-caption="{{ $doc->caption ?? basename($doc->file_path) }}"
                                            data-doc-agenda="{{ $agenda->judul_rapat }}"
                                            data-doc-date="{{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                                            aria-label="Lihat foto {{ $doc->caption ?? basename($doc->file_path) }}"
                                        >
                                            <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="Dokumentasi Rapat" class="w-full h-28 object-cover group-hover/thumb:scale-105 transition duration-300">
                                            <div class="absolute inset-0 bg-slate-950/0 group-hover/thumb:bg-slate-950/20 transition flex items-center justify-center">
                                                <div class="w-7 h-7 rounded-full bg-slate-900/80 text-white flex items-center justify-center opacity-0 group-hover/thumb:opacity-100 transition shadow-md">
                                                    <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
                                                </div>
                                            </div>
                                        </button>
                                        <div class="p-2 bg-white border-t border-slate-200 space-y-1.5">
                                            <input
                                                type="text"
                                                name="existing_captions[{{ $doc->id }}]"
                                                value="{{ $doc->caption }}"
                                                data-existing-caption-input="{{ $doc->id }}"
                                                maxlength="255"
                                                placeholder="Ketik keterangan foto (opsional)..."
                                                aria-label="Keterangan foto {{ $loop->iteration }}"
                                                class="w-full text-[11px] rounded-lg border border-slate-200 focus:border-slate-900 focus:ring-slate-900 py-1.5 px-2 text-slate-800 bg-slate-50 focus:bg-white transition font-medium"
                                            >
                                            <div class="flex items-center justify-between gap-1 pt-0.5">
                                                <button
                                                    type="button"
                                                    class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer text-[11px] font-semibold px-1 py-0.5 rounded"
                                                    data-doc-modal-trigger
                                                    data-doc-gallery="modal-stored-docs"
                                                    data-doc-url="{{ Storage::disk('public')->url($doc->file_path) }}"
                                                    data-doc-caption="{{ $doc->caption ?? basename($doc->file_path) }}"
                                                    data-doc-agenda="{{ $agenda->judul_rapat }}"
                                                    data-doc-date="{{ $doc->created_at ? $doc->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                                                >
                                                    Lihat Perbesar
                                                </button>
                                                <button
                                                    type="button"
                                                    data-delete-doc-btn="{{ $doc->id }}"
                                                    data-delete-doc-url="{{ route('admin.agendas.delete-documentation', [$agenda, $doc]) }}"
                                                    data-delete-doc-caption="{{ $doc->caption ?: 'Dokumentasi' }}"
                                                    class="inline-flex items-center gap-1 text-rose-600 hover:text-rose-800 px-2 py-1 rounded-lg hover:bg-rose-50 transition cursor-pointer text-[11px] font-semibold"
                                                    title="Hapus foto dari database tanpa mereset ketikan"
                                                    aria-label="Hapus foto {{ $doc->caption ?? 'Dokumentasi' }}"
                                                >
                                                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                                    <span>Hapus</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- 3. Footer -->
                <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
                    <span id="photo-counter-badge" class="hidden text-xs font-bold text-slate-700 font-mono">
                        0 Foto Terpilih
                    </span>
                    <div class="ml-auto">
                        <button
                            type="button"
                            onclick="closeDokumentasiModal()"
                            class="button text-xs font-semibold border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
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
                                            <img id="modal-logo-preview-img" src="{{ Storage::disk('public')->url($config['custom_logo_path']) }}" alt="Logo Kustom" class="max-h-10 max-w-10 object-contain">
                                        </div>
                                        <div>
                                            <span id="modal-logo-badge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">Logo Kustom Aktif</span>
                                            <label class="flex items-center gap-1 mt-1 text-[11px] text-red-600 font-semibold cursor-pointer">
                                                <input type="checkbox" name="reset_custom_logo" value="1" class="rounded border-slate-300 text-red-600">
                                                <span>Kembalikan ke Tut Wuri Handayani</span>
                                            </label>
                                        </div>
                                    @else
                                        <div class="w-12 h-12 rounded-lg border border-slate-300 bg-white p-1 flex items-center justify-center shrink-0">
                                            <img id="modal-logo-preview-img" src="{{ asset('images/tut-wuri-handayani.png') }}" alt="Logo Tut Wuri" class="max-h-10 max-w-10 object-contain">
                                        </div>
                                        <div>
                                            <span id="modal-logo-badge" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Logo Tut Wuri Handayani</span>
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

                        <div class="space-y-2">
                            <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi I A: Catatan Jalannya Rapat (Notulensi)</div>
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
                                    <div class="font-bold text-slate-900">Seksi I B: Kesimpulan &amp; Rencana Tindak Lanjut (RTL)</div>
                                    <div class="text-[11px] text-slate-500">Mencantumkan kesimpulan pokok dan tindak lanjut kedinasan</div>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="hidden" name="show_kesimpulan" value="0">
                                    <input type="checkbox" name="show_kesimpulan" value="1" class="sr-only peer" @checked($config['show_kesimpulan'] ?? true)>
                                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                                </label>
                            </div>

                        <!-- Attendance Table Settings -->
                        <div class="p-4 border border-slate-200 rounded-xl space-y-3 bg-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-slate-900">Seksi II: Tabel Kehadiran Peserta</div>
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
                                    <input type="checkbox" name="show_footer_note" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_footer_note'] ?? false)>
                                    <span>Tampilkan</span>
                                </label>
                            </div>
                            <input type="text" name="footer_note" value="{{ old('footer_note', $config['footer_note'] ?? '') }}" placeholder="Kosongkan bila dokumen tidak perlu catatan kaki" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
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
    let customLogoPreviewUrl = null;

    function markWorkstationDirty() {
        window.__officeDirtyState = true;
        window.dispatchEvent(new CustomEvent('office:dirty-change', { detail: { dirty: true } }));
    }

    function openDocumentConfigModal() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
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
            modal.querySelectorAll('input, select, textarea').forEach(el => {
                if (el.name in docConfigSnapshot) {
                    if (el.type === 'checkbox') {
                        el.checked = docConfigSnapshot[el.name];
                    } else if (el.type !== 'file') {
                        el.value = docConfigSnapshot[el.name];
                    }
                }
            });
            const fileInput = modal.querySelector('input[type="file"][name="custom_logo"]');
            if (fileInput) {
                fileInput.value = '';
                if (customLogoPreviewUrl) {
                    URL.revokeObjectURL(customLogoPreviewUrl);
                    customLogoPreviewUrl = null;
                }
            }
            applyDocConfigToWorkstation();
            window.dispatchEvent(new CustomEvent('office:reflow'));
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function closeDocumentConfigModal() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
            applyDocConfigToWorkstation();
            window.dispatchEvent(new CustomEvent('office:reflow'));
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function switchDocConfigTab(tabName) {
        const modal = document.getElementById('modal-notulen-document-config');
        if (!modal) return;

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

    function getTotalDocumentationCount() {
        const storedCards = document.querySelectorAll('[data-stored-doc-card]').length;
        const queuedFiles = Array.isArray(window.__queuedNotulenPhotos) ? window.__queuedNotulenPhotos.length : 0;
        return storedCards + queuedFiles;
    }

    function rebuildSheetDocumentationAnnex() {
        const tbody = document.getElementById('sheet-documentation-tbody');
        const annex = document.getElementById('sheet-documentation-annex');
        const modal = document.getElementById('modal-notulen-document-config');
        if (!tbody || !annex) return;

        const workstation = document.getElementById('office-workstation');
        const agendaTitle = workstation ? (workstation.dataset.agendaTitle || '') : '';
        const showDocCheckbox = modal ? modal.querySelector('input[type="checkbox"][name="show_documentation"]') : null;
        const showDoc = showDocCheckbox ? showDocCheckbox.checked : true;

        const items = [];
        document.querySelectorAll('[data-stored-doc-card]').forEach(card => {
            const docId = card.dataset.storedDocCard;
            const url = card.dataset.docUrl || '';
            const filename = card.dataset.docFilename || 'Dokumentasi';
            const dateStr = card.dataset.docDate || '';
            const captionInput = card.querySelector(`[data-existing-caption-input="${docId}"]`);
            const captionText = captionInput ? captionInput.value.trim() : '';

            items.push({
                id: docId,
                url: url,
                caption: captionText,
                displayCaption: captionText || filename,
                date: dateStr,
                isQueued: false,
            });
        });

        if (Array.isArray(window.__queuedNotulenPhotos)) {
            window.__queuedNotulenPhotos.forEach((queued, idx) => {
                const captionText = (queued.caption || '').trim();
                items.push({
                    id: 'queued-' + idx,
                    url: queued.previewUrl,
                    caption: captionText,
                    displayCaption: captionText || queued.name || ('Foto Baru ' + (idx + 1)),
                    date: 'Baru dipilih (belum disimpan)',
                    isQueued: true,
                });
            });
        }

        const escapeHtml = (str) => String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');

        if (items.length === 0) {
            tbody.innerHTML = '';
            annex.style.display = 'none';
            return;
        }

        let html = '';
        for (let i = 0; i < items.length; i += 2) {
            const chunk = items.slice(i, i + 2);
            html += '<tr>';
            chunk.forEach(item => {
                const captionStyle = item.caption ? '' : 'display: none;';
                html += `<td width="50%" align="center" valign="top" style="padding: 4pt; border: none;" data-sheet-doc-id="${escapeHtml(item.id)}">
                    <div
                        class="cursor-pointer group/doc hover:opacity-90 transition"
                        data-doc-modal-trigger
                        data-doc-gallery="notulen-sheet-docs"
                        data-doc-url="${escapeHtml(item.url)}"
                        data-doc-caption="${escapeHtml(item.displayCaption)}"
                        data-doc-agenda="${escapeHtml(agendaTitle)}"
                        data-doc-date="${escapeHtml(item.date)}"
                        style="border: 1px solid #94a3b8; padding: 3pt; background: #ffffff; text-align: center;"
                        title="Klik untuk pratinjau foto perbesar"
                    >
                        <img src="${escapeHtml(item.url)}" alt="Foto Dokumentasi" style="width: 100%; max-height: 120px; object-fit: contain; display: block; margin: 0 auto;">
                        <div id="sheet-doc-caption-${escapeHtml(item.id)}" style="font-size: 7.5pt; color: #334155; margin-top: 2pt; font-style: italic; font-family: 'Times New Roman', Times, serif; ${captionStyle}">${escapeHtml(item.caption)}</div>
                    </div>
                </td>`;
            });
            if (chunk.length === 1) {
                html += '<td width="50%" style="border: none;">&nbsp;</td>';
            }
            html += '</tr>';
        }

        tbody.innerHTML = html;
        annex.style.display = showDoc ? '' : 'none';
    }
    window.rebuildSheetDocumentationAnnex = rebuildSheetDocumentationAnnex;

    function applyDocConfigToWorkstation() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (!modal) return;

        const workstation = document.getElementById('office-workstation');
        const val = (name) => {
            const input = modal.querySelector(`[name="${name}"]`);
            return input ? input.value : '';
        };

        const setVisible = (id, visible) => {
            const el = document.getElementById(id);
            if (el) el.style.display = visible ? '' : 'none';
        };

        const isChecked = (name) => {
            const input = modal.querySelector(`input[type="checkbox"][name="${name}"]`);
            return input ? input.checked : true;
        };

        // Bagian-bagian dokumen
        setVisible('sheet-seksi-1-container', isChecked('show_notulensi') || isChecked('show_kesimpulan'));
        setVisible('sheet-section-notulensi-wrapper', isChecked('show_notulensi'));
        setVisible('sheet-section-kesimpulan-wrapper', isChecked('show_kesimpulan'));
        setVisible('sheet-seksi-2-wrapper', isChecked('show_attendees'));
        setVisible('sheet-seksi-2-container', isChecked('show_attendees'));
        setVisible('sheet-meeting-info', isChecked('show_meeting_info'));
        setVisible('sheet-documentation-annex', isChecked('show_documentation') && getTotalDocumentationCount() > 0);

        const footerNoteVal = val('footer_note').trim();
        setVisible('sheet-footer-note-block', isChecked('show_footer_note') && footerNoteVal !== '');

        // Header Kop & Logo
        const kopEl = document.getElementById('sheet-header-kop');
        if (kopEl) kopEl.style.display = isChecked('show_kop') ? 'block' : 'none';

        const showLogo = isChecked('show_logo');
        const logoCell = document.getElementById('sheet-logo-cell');
        let logoEl = document.getElementById('sheet-logo-img');

        if (showLogo && logoCell && !logoEl) {
            logoEl = document.createElement('img');
            logoEl.id = 'sheet-logo-img';
            logoEl.alt = 'Logo Instansi';
            logoEl.width = 86;
            logoEl.height = 86;
            logoEl.style.cssText = 'width: 65pt; height: 65pt; object-fit: contain; display: block; margin: 0 auto; border: none;';
            logoEl.src = workstation ? (workstation.dataset.currentLogoSrc || workstation.dataset.defaultLogoSrc || '') : '';
            logoCell.appendChild(logoEl);
        }

        if (logoEl && workstation) {
            const resetCustomLogo = modal.querySelector('input[type="checkbox"][name="reset_custom_logo"]')?.checked ?? false;
            let activeLogoSrc = workstation.dataset.currentLogoSrc || workstation.dataset.defaultLogoSrc || '';
            if (customLogoPreviewUrl) {
                activeLogoSrc = customLogoPreviewUrl;
            } else if (resetCustomLogo) {
                activeLogoSrc = workstation.dataset.defaultLogoSrc || '';
            }
            if (activeLogoSrc && logoEl.src !== activeLogoSrc) {
                logoEl.src = activeLogoSrc;
            }
            const modalLogoPreview = document.getElementById('modal-logo-preview-img');
            if (modalLogoPreview && activeLogoSrc) {
                modalLogoPreview.src = activeLogoSrc;
            }
            logoEl.style.display = showLogo ? 'block' : 'none';
        }

        if (logoCell) {
            const logoWidth = showLogo ? '17%' : '0%';
            const textWidth = showLogo ? '83%' : '100%';
            logoCell.style.width = logoWidth;
            logoCell.setAttribute('width', logoWidth);
            if (logoCell.nextElementSibling) {
                logoCell.nextElementSibling.style.width = textWidth;
                logoCell.nextElementSibling.setAttribute('width', textWidth);
            }
        }

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

        // Override Perihal & Tempat pada Tabel Informasi Rapat
        const infoTitleEl = document.getElementById('sheet-info-title');
        if (infoTitleEl) {
            const customTitle = val('custom_agenda_title').trim();
            infoTitleEl.textContent = customTitle || infoTitleEl.dataset.defaultTitle || '';
        }

        const infoLocationEl = document.getElementById('sheet-info-location');
        if (infoLocationEl) {
            const customLoc = val('custom_location').trim();
            infoLocationEl.textContent = customLoc || infoLocationEl.dataset.defaultLocation || '';
        }

        // Toggle Kolom Tabel Kehadiran Peserta
        const attendanceTable = document.getElementById('sheet-attendance-table');
        if (attendanceTable) {
            const colVisibility = {
                nip: isChecked('show_nip'),
                unit: isChecked('show_unit'),
                time: isChecked('show_attendance_time'),
                photo: isChecked('show_selfie_photos'),
            };

            Object.entries(colVisibility).forEach(([colKey, visible]) => {
                attendanceTable.querySelectorAll(`[data-col="${colKey}"]`).forEach(el => {
                    el.style.display = visible ? '' : 'none';
                });
            });

            // Hitung ulang persentase lebar kolom Nama Lengkap agar tabel selalu 100%
            const noCol = attendanceTable.querySelector('col[data-col="no"]');
            const signCol = attendanceTable.querySelector('col[data-col="sign"]');
            const nameCol = attendanceTable.querySelector('col[data-col="name"]');
            if (nameCol) {
                let usedPct = (parseFloat(noCol?.dataset.colWidth) || 5) + (parseFloat(signCol?.dataset.colWidth) || 16);
                Object.entries(colVisibility).forEach(([colKey, visible]) => {
                    if (visible) {
                        const c = attendanceTable.querySelector(`col[data-col="${colKey}"]`);
                        usedPct += parseFloat(c?.dataset.colWidth) || 0;
                    }
                });
                nameCol.style.width = Math.max(15, 100 - usedPct) + '%';
            }

            const emptyCell = document.getElementById('sheet-attendance-empty-cell');
            if (emptyCell) {
                const visibleOptionalCount = Object.values(colVisibility).filter(Boolean).length;
                emptyCell.setAttribute('colspan', String(2 + visibleOptionalCount));
            }

            const showAttendeeSig = isChecked('show_attendee_signatures');
            attendanceTable.querySelectorAll('.attendee-sig-img').forEach(img => {
                img.style.display = showAttendeeSig ? 'block' : 'none';
            });
            attendanceTable.querySelectorAll('.attendee-sig-fallback').forEach(span => {
                span.style.display = showAttendeeSig ? 'none' : 'inline';
            });
        }

        // Blok Tanda Tangan & Penandatangan 3
        for (const markId of ['sheet-signer1-mark', 'sheet-signer2-mark']) {
            const mark = document.getElementById(markId);
            if (mark) {
                mark.style.display = markId === 'sheet-signer1-mark'
                    ? (isChecked('show_signer1_signature') ? 'inline-block' : 'none')
                    : (isChecked('show_signer2_signature') ? 'inline-block' : 'none');
            }
        }

        const showSigner3 = isChecked('show_signer3');
        const sigTable = document.getElementById('sheet-signature-table');
        if (sigTable) {
            const colWidth = showSigner3 ? '33.4%' : '50%';
            const innerWidth = showSigner3
                ? (sigTable.dataset.innerThree || '88%')
                : (sigTable.dataset.innerTwo || '78%');

            sigTable.querySelectorAll('[data-signer3-col]').forEach(el => {
                el.style.display = showSigner3 ? '' : 'none';
            });
            sigTable.querySelectorAll('[data-signer-col]').forEach(el => {
                el.style.width = colWidth;
            });
            sigTable.querySelectorAll('[data-signer-inner]').forEach(el => {
                el.style.width = innerWidth;
            });
        }

        // Signers & Footer Text
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

        const role3El = document.getElementById('sheet-signer3-role');
        if (role3El) role3El.textContent = val('signer3_role') || 'Kepala LLDIKTI Wilayah X';

        const name3El = document.getElementById('sheet-signer3-name');
        if (name3El) name3El.textContent = val('signer3_name') || '-';

        const nip3El = document.getElementById('sheet-signer3-nip');
        if (nip3El) nip3El.textContent = val('signer3_nip') || '-';

        const footerNoteEl = document.getElementById('sheet-footer-note');
        if (footerNoteEl) footerNoteEl.textContent = footerNoteVal;
    }

    function openDokumentasiModal() {
        const modal = document.getElementById('modal-notulen-dokumentasi');
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeDokumentasiModal() {
        const modal = document.getElementById('modal-notulen-dokumentasi');
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDocumentConfigModal();
            closeDokumentasiModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modal-notulen-document-config');
        if (modal) {
            modal.querySelectorAll('input, select, textarea').forEach(input => {
                input.addEventListener('input', () => {
                    markWorkstationDirty();
                    applyDocConfigToWorkstation();
                });
                input.addEventListener('change', () => {
                    if (input.type === 'file' && input.name === 'custom_logo') {
                        if (customLogoPreviewUrl) {
                            URL.revokeObjectURL(customLogoPreviewUrl);
                            customLogoPreviewUrl = null;
                        }
                        const file = input.files && input.files[0];
                        if (file) {
                            customLogoPreviewUrl = URL.createObjectURL(file);
                            const resetCheckbox = modal.querySelector('input[type="checkbox"][name="reset_custom_logo"]');
                            if (resetCheckbox) resetCheckbox.checked = false;
                        }
                    }
                    markWorkstationDirty();
                    applyDocConfigToWorkstation();
                });
            });
        }

        // Live sync untuk edit caption pada foto yang sudah tersimpan
        document.querySelectorAll('[data-existing-caption-input]').forEach(input => {
            input.addEventListener('input', () => {
                const docId = input.dataset.existingCaptionInput;
                const card = document.getElementById('stored-doc-card-' + docId);
                if (card) {
                    const fallbackName = card.dataset.docFilename || 'Dokumentasi';
                    const newCaption = input.value.trim();
                    card.querySelectorAll('[data-doc-modal-trigger]').forEach(btn => {
                        btn.dataset.docCaption = newCaption || fallbackName;
                    });
                }
                markWorkstationDirty();
                rebuildSheetDocumentationAnnex();
                window.dispatchEvent(new CustomEvent('office:reflow'));
            });
        });

        // AJAX Delete untuk foto dokumentasi tersimpan (tanpa reload halaman / kehilangan ketikan)
        document.querySelectorAll('[data-delete-doc-btn]').forEach(btn => {
            btn.addEventListener('click', () => {
                const docId = btn.dataset.deleteDocBtn;
                const deleteUrl = btn.dataset.deleteDocUrl;
                const caption = btn.dataset.deleteDocCaption || 'Dokumentasi';

                const executeDelete = async () => {
                    btn.disabled = true;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<span>Menghapus...</span>';

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                            || document.querySelector('input[name="_token"]')?.value
                            || '';
                        const res = await fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (!res.ok) {
                            throw new Error('Gagal menghapus foto dokumentasi.');
                        }

                        const card = document.getElementById('stored-doc-card-' + docId);
                        if (card) card.remove();

                        const remainingStored = document.querySelectorAll('[data-stored-doc-card]').length;
                        const storedCountEl = document.getElementById('modal-stored-photo-count');
                        if (storedCountEl) storedCountEl.textContent = String(remainingStored);

                        const storedSection = document.getElementById('modal-stored-docs-section');
                        if (storedSection && remainingStored === 0) {
                            storedSection.style.display = 'none';
                        }

                        const navPhotoCount = document.getElementById('nav-photo-count');
                        if (navPhotoCount) {
                            navPhotoCount.dataset.storedCount = String(remainingStored);
                            const queuedCount = Array.isArray(window.__queuedNotulenPhotos) ? window.__queuedNotulenPhotos.length : 0;
                            navPhotoCount.textContent = String(remainingStored + queuedCount);
                        }

                        rebuildSheetDocumentationAnnex();
                        window.dispatchEvent(new CustomEvent('office:reflow'));
                    } catch (err) {
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                        if (typeof window.showModal === 'function') {
                            window.showModal({
                                title: 'Gagal Menghapus Foto',
                                message: 'Terjadi kendala saat menghapus foto dokumentasi. Silakan coba kembali.',
                                type: 'danger',
                                confirmText: 'Tutup',
                            });
                        }
                    }
                };

                if (typeof window.showModal === 'function') {
                    window.showModal({
                        title: 'Hapus Foto Dokumentasi?',
                        message: `Apakah Anda yakin ingin menghapus foto "${caption}" secara permanen? Ketikan notulensi Anda di lembar kerja tidak akan hilang.`,
                        type: 'danger',
                        confirmText: 'Ya, Hapus Foto',
                        cancelText: 'Batal',
                        onConfirm: executeDelete,
                    });
                } else if (confirm('Apakah Anda yakin ingin menghapus foto dokumentasi ini?')) {
                    executeDelete();
                }
            });
        });

        // Guard saat klik tombol Ekspor Cepat (PDF / Word) jika ada perubahan yang belum disimpan
        document.querySelectorAll('[data-export-link]').forEach(link => {
            link.addEventListener('click', (e) => {
                if (!window.__officeDirtyState) return;
                e.preventDefault();
                const exportUrl = link.getAttribute('href');
                const formatName = link.dataset.exportLink === 'pdf' ? 'PDF Resmi (.pdf)' : 'Dokumen Word (.docx)';

                if (typeof window.showModal === 'function') {
                    window.showModal({
                        title: 'Perubahan Belum Disimpan',
                        message: `Terdapat ketikan notulensi atau pengaturan dokumen yang belum disimpan ke server. Jika Anda mengunduh ${formatName} sekarang, berkas hanya akan memuat data terakhir yang tersimpan. Lanjutkan mengunduh versi tersimpan?`,
                        type: 'warning',
                        confirmText: 'Tetap Unduh Versi Tersimpan',
                        cancelText: 'Batal (Simpan Dulu)',
                        onConfirm: () => {
                            window.location.href = exportUrl;
                        },
                    });
                }
            });
        });
    });
</script>

@if($agenda->surat_edaran_path)
    @include('agendas.partials.surat_edaran_preview', ['agenda' => $agenda, 'modalOnly' => true])
@endif

@include('attendances.partials.selfie_preview_modal')
@include('agendas.partials.documentation_preview_modal')
@endsection
