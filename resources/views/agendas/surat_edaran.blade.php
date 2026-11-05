@extends('layouts.app')

@section('title', 'Lembar Kerja Surat Dinas — ' . $agenda->judul_rapat)
@section('heading', 'Pengolah Kata Surat Dinas & Undangan Resmi')
@section('subtitle', 'Agenda: ' . $agenda->judul_rapat)

@section('content')
@php
    $defaultBundledLogoUrl = asset('images/tut-wuri-handayani.png');
    $currentSavedLogoUrl = (!empty($config['custom_logo_path']) && Storage::disk('public')->exists($config['custom_logo_path']))
        ? Storage::disk('public')->url($config['custom_logo_path'])
        : $defaultBundledLogoUrl;

    $instansiIndukRaw = trim((string) ($config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI"));
    if ($instansiIndukRaw === 'KEMENTERIAN PENDIDIKAN TINGGI, SAINS, DAN TEKNOLOGI') {
        $instansiIndukLines = ['KEMENTERIAN PENDIDIKAN TINGGI,', 'SAINS, DAN TEKNOLOGI'];
    } else {
        $instansiIndukLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $instansiIndukRaw))));
    }

    $instansiPelaksana = trim((string) ($config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X'));

    $alamatRaw = trim((string) ($config['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id"));
    $alamatLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $alamatRaw))));

    $tujuanRaw = trim((string) ($config['tujuan_surat'] ?? ''));
    $tujuanFormatted = preg_replace('/^Yth\.?\s*/i', '', $tujuanRaw);

    $signerRoleClean = rtrim(trim((string) ($config['signer_role'] ?? 'Kepala')), ',');
    $signerNameClean = trim((string) ($config['signer_name'] ?? 'Afdalisma'));
    $signerNipClean = trim((string) ($config['signer_nip'] ?? '197012051992032002'));

    $showLampiranSheet = !empty($config['show_lampiran']) || (!empty($config['lampiran']) && trim((string) $config['lampiran']) !== '-');
@endphp
<div
    id="surat-edaran-workstation"
    class="space-y-4"
    data-agenda-title="{{ $agenda->judul_rapat }}"
    data-default-logo-src="{{ $defaultBundledLogoUrl }}"
    data-current-logo-src="{{ $currentSavedLogoUrl }}"
>
    <!-- 1. Workstation Top Control Bar -->
    <div class="bg-white border border-slate-300 rounded-2xl p-3 sm:p-4 shadow-xs flex flex-wrap items-center justify-between gap-3">
        <!-- Left: Back Navigation & Context -->
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
                <span>{{ $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('d M Y') : '-' }} &bull; {{ ucfirst($agenda->tipe_rapat) }}</span>
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
                    title="Beralih ke format lembar kertas A4 (210 × 297 mm) sesuai Template Resmi"
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

        <!-- Right: Actions -->
        <div class="flex items-center gap-2">
            <!-- Penyesuaian Naskah Surat Trigger -->
            <button 
                type="button" 
                onclick="openSuratConfigModal()" 
                class="button secondary flex items-center gap-1.5 text-xs font-bold shadow-2xs cursor-pointer border-slate-300 hover:bg-slate-100 text-slate-800"
                title="Sesuaikan format naskah dinas, kop surat, nomor, penandatangan, tembusan, dan lampiran daftar undangan"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="4" x2="20" y1="21" y2="21"></line>
                    <line x1="4" x2="20" y1="14" y2="14"></line>
                    <line x1="4" x2="20" y1="7" y2="7"></line>
                    <circle cx="8" cy="7" r="2" fill="currentColor"></circle>
                    <circle cx="16" cy="14" r="2" fill="currentColor"></circle>
                    <circle cx="10" cy="21" r="2" fill="currentColor"></circle>
                </svg>
                <span>Penyesuaian Naskah</span>
            </button>

            <!-- Ekspor PDF & Cetak -->
            <a 
                href="{{ route('admin.agendas.export-surat-edaran-pdf', $agenda) }}" 
                class="button secondary flex items-center gap-1.5 text-xs font-bold shadow-2xs text-slate-800 hover:bg-slate-100"
                title="Unduh dokumen naskah resmi dalam format PDF"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-600"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/></svg>
                <span>Unduh PDF</span>
            </a>

            <!-- Primary Save Action -->
            <button 
                type="submit" 
                form="surat-edaran-form" 
                class="button flex items-center gap-2 text-xs font-bold shadow-xs cursor-pointer bg-slate-900 hover:bg-slate-800 text-white"
                title="Simpan seluruh naskah surat dan pengaturan dokumen dinas (Shortcut: Ctrl+S)"
            >
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                <span>Simpan Naskah</span>
                <span class="hidden md:inline-block text-[10px] bg-slate-800 text-slate-300 px-1.5 py-0.5 rounded font-mono font-normal">Ctrl+S</span>
            </button>
        </div>
    </div>

    <!-- 2. Sticky Universal Word Formatting Ribbon Toolbar -->
    <div class="word-editor-ribbon sticky top-[63px] z-20 bg-white border border-slate-300 rounded-2xl shadow-sm" role="toolbar" aria-label="Toolbar Format Pengolah Kata">
        <!-- Style, Font Family & Font Size Dropdowns -->
        <div class="flex items-center gap-1.5 flex-shrink-0">
            <select class="word-select" data-command="formatBlock" title="Gaya Paragraf">
                <option value="p">Normal</option>
                <option value="h2">Heading 2</option>
                <option value="h3">Heading 3</option>
                <option value="h4">Heading 4</option>
                <option value="blockquote">Kutipan</option>
            </select>
            <select class="word-select" data-action="fontName" title="Jenis Font (Default Batang Tubuh: Arial)">
                <option value="Arial" selected>Arial (Standar Isi)</option>
                <option value="Times New Roman">Times New Roman</option>
                <option value="Georgia">Georgia</option>
                <option value="Courier New">Courier New</option>
            </select>
            <select class="word-select" data-action="fontSize" title="Ukuran Font (Default Batang Tubuh: 12pt)">
                <option value="">12pt (Standar)</option>
                <option value="1">8pt (10px)</option>
                <option value="2">10pt (12px)</option>
                <option value="3">12pt (16px)</option>
                <option value="4">14pt (18px)</option>
                <option value="5">18pt (24px)</option>
                <option value="6">24pt (32px)</option>
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
                <span class="color-indicator" data-indicator="textColor" style="background:#0f172a"></span>
            </button>
            <input type="color" class="word-color-input" data-color-for="textColor" value="#0f172a" tabindex="-1">
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
            <button type="button" class="word-btn is-active" data-command="justifyFull" title="Rata Kiri Kanan (Justify)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="21" x2="3" y1="6" y2="6"/><line x1="21" x2="3" y1="12" y2="12"/><line x1="21" x2="3" y1="18" y2="18"/></svg>
            </button>
        </div>

        <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

        <!-- Lists & Indentation -->
        <div class="word-btn-group">
            <button type="button" class="word-btn" data-command="insertUnorderedList" title="Daftar Poin">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="9" x2="20" y1="6" y2="6"/><line x1="9" x2="20" y1="12" y2="12"/><line x1="9" x2="20" y1="18" y2="18"/><circle cx="4" cy="6" r="1.5" fill="currentColor"/><circle cx="4" cy="12" r="1.5" fill="currentColor"/><circle cx="4" cy="18" r="1.5" fill="currentColor"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="insertOrderedList" title="Daftar Nomor">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="10" x2="21" y1="6" y2="6"/><line x1="10" x2="21" y1="12" y2="12"/><line x1="10" x2="21" y1="18" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="outdent" title="Kurangi Indent">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 8 3 12 7 16"/><line x1="21" x2="11" y1="6" y2="6"/><line x1="21" x2="11" y1="12" y2="12"/><line x1="21" x2="11" y1="18" y2="18"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="indent" title="Tambah Indent">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 8 7 12 3 16"/><line x1="21" x2="11" y1="6" y2="6"/><line x1="21" x2="11" y1="12" y2="12"/><line x1="21" x2="11" y1="18" y2="18"/></svg>
            </button>
        </div>

        <div class="h-5 w-px bg-slate-300 mx-0.5 hidden sm:block"></div>

        <!-- Insert & Tools -->
        <div class="word-btn-group relative">
            <button type="button" class="word-btn" data-action="openTablePicker" title="Sisipkan Tabel">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><line x1="3" x2="21" y1="9" y2="9"/><line x1="3" x2="21" y1="15" y2="15"/><line x1="9" x2="9" y1="3" y2="21"/><line x1="15" x2="15" y1="3" y2="21"/></svg>
            </button>
            <div class="word-table-picker" id="surat-table-picker">
                <div class="word-table-grid" id="surat-table-grid"></div>
                <div class="word-table-picker-label" id="surat-table-label">0 × 0 Tabel</div>
            </div>
            <button type="button" class="word-btn" data-command="insertHorizontalRule" title="Garis Batas Horizontal">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="3" x2="21" y1="12" y2="12"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="removeFormat" title="Hapus Format">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="6" y1="6" y2="18"/><path d="m4 6 7 7"/><path d="m13 15 3 3"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="undo" title="Urungkan (Ctrl+Z)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13"/></svg>
            </button>
            <button type="button" class="word-btn" data-command="redo" title="Ulangi (Ctrl+Y)">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 7v6h-6"/><path d="M3 17a9 9 0 0 1 9-9 9 9 0 0 1 6 2.3l3 2.7"/></svg>
            </button>
        </div>
    </div>

    <!-- 3. Form Utama Penyimpanan Surat Dinas -->
    <form 
        id="surat-edaran-form" 
        action="{{ route('admin.agendas.update-surat-edaran', $agenda) }}" 
        method="POST" 
        enctype="multipart/form-data"
        class="hidden"
        data-confirm="Simpan seluruh naskah surat dan pengaturan dokumen dinas resmi?"
        data-confirm-title="Simpan Naskah Surat Dinas"
        data-confirm-type="confirm"
        data-confirm-btn="Ya, Simpan Naskah"
    >
        @csrf
        @method('PUT')

        <input type="hidden" name="template_type" id="form-template-type" value="{{ $config['template_type'] ?? 'undangan' }}">
        <input type="hidden" name="nomor_surat" id="form-nomor-surat" value="{{ $config['nomor_surat'] ?? '' }}">
        <input type="hidden" name="sifat" id="form-sifat" value="{{ $config['sifat'] ?? 'Biasa' }}">
        <input type="hidden" name="lampiran" id="form-lampiran" value="{{ $config['lampiran'] ?? '-' }}">
        <input type="hidden" name="perihal" id="form-perihal" value="{{ $config['perihal'] ?? ('Undangan Rapat ' . $agenda->judul_rapat) }}">
        <input type="hidden" name="tempat_surat" id="form-tempat-surat" value="{{ $config['tempat_surat'] ?? 'Padang' }}">
        <input type="hidden" name="tanggal_surat" id="form-tanggal-surat" value="{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}">
        <input type="hidden" name="tujuan_surat" id="form-tujuan-surat" value="{{ $config['tujuan_surat'] ?? '' }}">
        
        <input type="hidden" name="pembuka" id="form-pembuka" value="{{ $config['pembuka'] ?? '' }}">
        <input type="hidden" name="surat_edaran_content" id="form-surat-content" value="{{ $agenda->surat_edaran_content ?? '' }}">
        <input type="hidden" name="catatan" id="form-catatan" value="{{ $config['catatan'] ?? '' }}">
        <input type="hidden" name="penutup" id="form-penutup" value="{{ $config['penutup'] ?? '' }}">

        <input type="hidden" name="signer_role" id="form-signer-role" value="{{ $config['signer_role'] ?? 'Kepala' }}">
        <input type="hidden" name="signer_name" id="form-signer-name" value="{{ $config['signer_name'] ?? 'Afdalisma' }}">
        <input type="hidden" name="signer_nip" id="form-signer-nip" value="{{ $config['signer_nip'] ?? '197012051992032002' }}">
        <input type="hidden" name="show_signature" id="form-show-signature" value="{{ ($config['show_signature'] ?? true) ? '1' : '0' }}">
        <input type="hidden" name="tembusan" id="form-tembusan" value="{{ $config['tembusan'] ?? '' }}">

        <!-- Lampiran Halaman 2 Settings -->
        <input type="hidden" name="show_lampiran" id="form-show-lampiran" value="{{ $showLampiranSheet ? '1' : '0' }}">
        <input type="hidden" name="lampiran_content" id="form-lampiran-content" value="{{ $config['lampiran_content'] ?? '' }}">

        <!-- Kop Settings -->
        <input type="hidden" name="show_kop" id="form-show-kop" value="{{ ($config['show_kop'] ?? true) ? '1' : '0' }}">
        <input type="hidden" name="show_logo" id="form-show-logo" value="{{ ($config['show_logo'] ?? true) ? '1' : '0' }}">
        <input type="hidden" name="instansi_induk" id="form-instansi-induk" value="{{ $config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI" }}">
        <input type="hidden" name="instansi_pelaksana" id="form-instansi-pelaksana" value="{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X' }}">
        <input type="hidden" name="alamat_kontak" id="form-alamat-kontak" value="{{ $config['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id" }}">
        <input type="file" name="custom_logo" id="form-custom-logo" class="hidden" accept="image/jpeg,image/png,image/webp">
        <input type="hidden" name="reset_custom_logo" id="form-reset-custom-logo" value="0">
    </form>

    <!-- 4. Lembar Kerja A4 Canvas (Desk Canvas — Margin Kiri 3cm, Kanan 2cm, Bawah 2.5cm) -->
    <div class="office-desk-canvas">
        <div class="office-document-ruler paper-a4" id="office-document-ruler" aria-hidden="true">
            <div class="ruler-margin-zone ruler-margin-left" title="Margin Kiri: 3.0 cm"></div>
            <div class="ruler-scale">
                @for($cm = 0; $cm <= 21; $cm++)
                    <div class="ruler-cm"><span>{{ $cm > 0 ? $cm : '' }}</span></div>
                @endfor
            </div>
            <div class="ruler-margin-zone ruler-margin-right" title="Margin Kanan: 2.0 cm"></div>
        </div>

        <div id="office-zoom-container" class="office-zoom-container" data-zoom="100">
            {{-- ================= HALAMAN 1: NASKAH SURAT UTAMA ================= --}}
            <div id="surat-sheet-page-1" class="office-paper-sheet paper-a4" data-paper-format="a4" data-page-number="1">
                <div class="office-paper-margin-guide">
                    
                    {{-- KOP SURAT RESMI (word/header3.xml: Times New Roman 16pt Reguler / 14pt Bold / 12pt Reguler + Garis Tunggal 2.25pt) --}}
                    <div id="sheet-kop-container" style="{{ ($config['show_kop'] ?? true) ? '' : 'display: none;' }}; margin-bottom: 14pt;">
                        <div class="header-kop" id="sheet-header-kop" style="display: {{ ($config['show_kop'] ?? true) ? 'block' : 'none' }}; margin: 0; padding: 0; text-align: center; width: 100%; font-family: 'Times New Roman', Times, serif;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="width: 100%; table-layout: fixed; border-collapse: collapse; border-bottom: 2.25pt solid #000000; margin: 0; padding: 0;">
                                <tr>
                                    <td id="sheet-logo-cell" width="{{ ($config['show_logo'] ?? true) ? '19.7%' : '0%' }}" align="center" valign="middle" style="width: {{ ($config['show_logo'] ?? true) ? '19.7%' : '0%' }}; display: {{ ($config['show_logo'] ?? true) ? 'table-cell' : 'none' }}; text-align: center; vertical-align: middle; border: none; padding: 0 4pt 4pt 0;">
                                        <img id="sheet-logo-img" src="{{ $currentSavedLogoUrl }}" alt="Logo Instansi" style="width: 98px; height: auto; max-height: 105px; display: {{ ($config['show_logo'] ?? true) ? 'block' : 'none' }}; margin: 0 auto; border: none;">
                                    </td>
                                    <td id="sheet-kop-text-cell" width="{{ ($config['show_logo'] ?? true) ? '80.3%' : '100%' }}" align="center" valign="middle" style="width: {{ ($config['show_logo'] ?? true) ? '80.3%' : '100%' }}; text-align: center; vertical-align: middle; border: none; padding: 0 0 4pt 0; font-family: 'Times New Roman', Times, serif;">
                                        <div id="sheet-instansi-induk" style="margin: 0; padding: 0; font-size: 16pt; line-height: 1.08; font-weight: normal; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">
                                            @foreach($instansiIndukLines as $idx => $line)
                                                @if($idx > 0)<br>@endif{{ $line }}
                                            @endforeach
                                        </div>
                                        <div id="sheet-instansi-pelaksana" style="margin: 1pt 0; padding: 0; font-size: 14pt; line-height: 1.12; font-weight: bold; text-transform: uppercase; font-family: 'Times New Roman', Times, serif;">{{ $instansiPelaksana }}</div>
                                        <div id="sheet-alamat-kontak" style="margin: 0; padding: 0; font-size: 12pt; line-height: 1.12; font-weight: normal; font-style: normal; font-family: 'Times New Roman', Times, serif;">
                                            @foreach($alamatLines as $idx => $addrLine)
                                                @if($idx > 0)<br>@endif{{ $addrLine }}
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- VARIANT A: SURAT UNDANGAN RAPAT KEDINASAN (Template_Surat_Undangan.docx — Arial 12pt, Spasi 1.15) --}}
                    <div id="sheet-undangan-block" style="{{ ($config['template_type'] ?? 'undangan') === 'undangan' ? '' : 'display: none;' }}; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; color: #000000;">
                        <!-- Tabel Kepala Surat 4 Kolom, 3 Baris (Tanpa Baris Sifat, Tanggal di Kanan Tanpa Kota) -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 12pt; border: none; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15;">
                            <tr>
                                <td style="width: 13.5%; vertical-align: top; border: none; padding: 1pt 0;">Nomor</td>
                                <td style="width: 3.1%; vertical-align: top; border: none; padding: 1pt 0;">:</td>
                                <td style="width: 42.7%; vertical-align: top; border: none; padding: 1pt 0;" id="sheet-nomor-surat">{{ $config['nomor_surat'] ?? '-' }}</td>
                                <td style="width: 40.7%; text-align: right; vertical-align: top; border: none; padding: 1pt 0;">
                                    <span id="sheet-tanggal-surat">{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</span>
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 13.5%; vertical-align: top; border: none; padding: 1pt 0;">Lampiran</td>
                                <td style="width: 3.1%; vertical-align: top; border: none; padding: 1pt 0;">:</td>
                                <td colspan="2" style="width: 83.4%; vertical-align: top; border: none; padding: 1pt 0;" id="sheet-lampiran">{{ $config['lampiran'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 13.5%; vertical-align: top; border: none; padding: 1pt 0;">Hal</td>
                                <td style="width: 3.1%; vertical-align: top; border: none; padding: 1pt 0;">:</td>
                                <td colspan="2" style="width: 83.4%; vertical-align: top; border: none; padding: 1pt 0; text-align: justify;" id="sheet-perihal">{{ $config['perihal'] ?? ('Undangan Rapat ' . $agenda->judul_rapat) }}</td>
                            </tr>
                        </table>

                        <!-- Alamat Tujuan (P#2: Yth. ......... rata kiri-kanan sejajar margin kiri) -->
                        <div style="margin: 12pt 0 12pt 0; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; text-align: justify;">
                            <span>Yth. </span><span id="sheet-tujuan-surat">{!! nl2br(e($tujuanFormatted)) !!}</span>
                        </div>

                        <!-- Alinea Pembuka (WYSIWYG Editable — Arial 12pt, line-height 1.15, diakhiri "pada") -->
                        <div style="margin: 8pt 0 6pt 0;">
                            <div
                                id="surat-pembuka-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="pembuka"
                                data-placeholder="Ketik alinea pembuka surat undangan di sini (akhiri dengan kata 'pada')..."
                                style="min-height: 44px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 4pt 6pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $config['pembuka'] ?? '' !!}</div>
                        </div>

                        <!-- Tabel Rincian Pelaksanaan Rapat (TBL#8: 3 kolom, label huruf kecil semua) -->
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 6pt 0 10pt 0; border: none; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15;">
                            <tr>
                                <td style="width: 17.6%; vertical-align: top; border: none; padding: 1.5pt 0;">hari, tanggal</td>
                                <td style="width: 2.6%; vertical-align: top; border: none; padding: 1.5pt 0;">:</td>
                                <td style="width: 79.8%; vertical-align: top; border: none; padding: 1.5pt 0;">{{ $agenda->waktu_mulai ? $agenda->waktu_mulai->translatedFormat('l, d F Y') : '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 17.6%; vertical-align: top; border: none; padding: 1.5pt 0;">pukul</td>
                                <td style="width: 2.6%; vertical-align: top; border: none; padding: 1.5pt 0;">:</td>
                                <td style="width: 79.8%; vertical-align: top; border: none; padding: 1.5pt 0;">
                                    {{ $agenda->waktu_mulai ? $agenda->waktu_mulai->format('H.i') : '-' }}
                                    {{ $agenda->waktu_selesai ? 's.d. ' . $agenda->waktu_selesai->format('H.i') . ' WIB' : 'WIB s.d. selesai' }}
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 17.6%; vertical-align: top; border: none; padding: 1.5pt 0;">tempat</td>
                                <td style="width: 2.6%; vertical-align: top; border: none; padding: 1.5pt 0;">:</td>
                                <td style="width: 79.8%; vertical-align: top; border: none; padding: 1.5pt 0;">
                                    @if($agenda->tipe_rapat === 'online')
                                        Daring (Online Meeting) &mdash; {{ $agenda->link_meeting ?? 'Tautan rapat dibagikan melalui grup koordinasi' }}
                                    @elseif($agenda->tipe_rapat === 'hybrid')
                                        {{ $agenda->lokasi_ruang ?? 'Ruang Rapat LLDIKTI Wilayah X' }} (Hybrid / Luring &amp; Daring)
                                    @else
                                        {{ $agenda->lokasi_ruang ?? 'Ruang Rapat Utama LLDIKTI Wilayah X' }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="width: 17.6%; vertical-align: top; border: none; padding: 1.5pt 0;">acara</td>
                                <td style="width: 2.6%; vertical-align: top; border: none; padding: 1.5pt 0;">:</td>
                                <td style="width: 79.8%; vertical-align: top; border: none; padding: 1.5pt 0;">{{ $agenda->judul_rapat }}</td>
                            </tr>
                        </table>

                        <!-- Batang Tubuh / Rincian Tambahan (WYSIWYG Editable) -->
                        <div style="margin: 6pt 0;">
                            <div
                                id="surat-body-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="body"
                                data-placeholder="Ketik rincian pembahasan, rundown kegiatan, atau instruksi agenda tambahan di sini (opsional)..."
                                style="min-height: 44px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 4pt 6pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $agenda->surat_edaran_content ?? '' !!}</div>
                        </div>

                        <!-- Catatan Khusus (WYSIWYG Editable) -->
                        <div style="margin: 6pt 0;">
                            <div
                                id="surat-catatan-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="catatan"
                                data-placeholder="Catatan tambahan (opsional, kosongkan jika tidak diperlukan)..."
                                style="min-height: 34px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 4pt 6pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $config['catatan'] ?? '' !!}</div>
                        </div>

                        <!-- Alinea Penutup (WYSIWYG Editable) -->
                        <div style="margin: 6pt 0 12pt 0;">
                            <div
                                id="surat-penutup-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="penutup"
                                data-placeholder="Ketik alinea penutup surat undangan di sini..."
                                style="min-height: 42px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 4pt 6pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $config['penutup'] ?? '' !!}</div>
                        </div>
                    </div>

                    {{-- VARIANT B: SURAT EDARAN RESMI --}}
                    <div id="sheet-edaran-block" style="{{ ($config['template_type'] ?? 'undangan') === 'edaran' ? '' : 'display: none;' }}; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; color: #000000;">
                        <div style="text-align: center; margin: 10pt 0 14pt 0; font-family: Arial, Helvetica, sans-serif;">
                            <h2 style="font-size: 12pt; font-weight: bold; text-decoration: underline; margin: 0 0 3pt 0; text-transform: uppercase;">SURAT EDARAN</h2>
                            <div style="font-size: 12pt; margin: 0 0 3pt 0;">NOMOR <span id="sheet-edaran-nomor">{{ $config['nomor_surat'] ?? '-' }}</span></div>
                            <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 0;">TENTANG<br><span id="sheet-edaran-perihal">{{ $config['perihal'] ?? $agenda->judul_rapat }}</span></div>
                        </div>

                        <div style="margin-bottom: 10pt; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15;">
                            <span>Yth. </span><span id="sheet-edaran-tujuan">{!! nl2br(e($tujuanFormatted)) !!}</span>
                        </div>

                        <!-- Batang Tubuh Surat Edaran (WYSIWYG Editable) -->
                        <div style="margin: 10pt 0;">
                            <div
                                id="surat-edaran-alt-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="edaran-body"
                                data-placeholder="Ketik butir-butir Surat Edaran (A. Latar Belakang, B. Maksud dan Tujuan, C. Ruang Lingkup, D. Penutup) di sini..."
                                style="min-height: 220px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 6pt 8pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $agenda->surat_edaran_content ?? '' !!}</div>
                        </div>
                    </div>

                    {{-- AREA PENGESAHAN & TEMBUSAN (P#14–22: Kepala, | TTD | Afdalisma | Tembusan: sejajar NIP <bold>197012051992032002</bold>) --}}
                    <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-top: 14pt; border: none; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; page-break-inside: avoid;">
                        <tr>
                            <td style="width: 55.5%; vertical-align: top; border: none;"></td>
                            <td style="width: 44.5%; vertical-align: top; border: none;">
                                <div><span id="sheet-signer-role">{{ $signerRoleClean }}</span>,</div>
                                <div style="height: 48pt; display: flex; align-items: center;">
                                    <div id="sheet-signature-badge" class="stamp-mark" style="border: 1.5px solid #1e3a8a; color: #1e3a8a; padding: 2px 8px; border-radius: 4px; font-size: 8pt; font-family: Arial, Helvetica, sans-serif; font-weight: bold; text-transform: uppercase; {{ ($config['show_signature'] ?? true) ? '' : 'display: none;' }}">
                                        Tanda Tangan Digital Tersertifikasi SIPERAPAT
                                    </div>
                                </div>
                                <div id="sheet-signer-name" style="font-weight: normal; text-decoration: none;">{{ $signerNameClean }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="width: 55.5%; vertical-align: top; border: none;">
                                <div id="sheet-tembusan-label" style="{{ !empty($config['tembusan']) ? '' : 'display: none;' }}; font-size: 12pt; font-weight: normal;">Tembusan:</div>
                            </td>
                            <td style="width: 44.5%; vertical-align: top; border: none;">
                                <div>NIP <strong id="sheet-signer-nip" style="font-weight: bold;">{{ $signerNipClean }}</strong></div>
                            </td>
                        </tr>
                        <tr id="sheet-tembusan-block" style="{{ !empty($config['tembusan']) ? '' : 'display: none;' }}">
                            <td colspan="2" style="vertical-align: top; border: none; padding-top: 1pt;">
                                <div id="sheet-tembusan-text" style="font-size: 12pt; line-height: 1.15;">
                                    {!! nl2br(e($config['tembusan'] ?? '')) !!}
                                </div>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>

            {{-- ================= PEMISAH HALAMAN & HALAMAN 2: LAMPIRAN SURAT (DAFTAR UNDANGAN) ================= --}}
            <div id="surat-lampiran-page-wrapper" class="w-full flex flex-col items-center" style="{{ $showLampiranSheet ? '' : 'display: none;' }}">
                <div class="office-page-gap" id="surat-page-gap">
                    <div class="gap-line"></div>
                    <div class="gap-indicator">
                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                        <span>Halaman 2 &mdash; Lampiran Daftar Undangan</span>
                    </div>
                    <div class="gap-line"></div>
                </div>

                <div id="surat-sheet-page-2" class="office-paper-sheet paper-a4" data-paper-format="a4" data-page-number="2">
                    <div class="office-paper-margin-guide" style="font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15; color: #000000;">
                        {{-- Running Header Halaman 2 (word/header1.xml: "- 2 -" Times New Roman 12pt Center) --}}
                        <div style="text-align: center; font-family: 'Times New Roman', Times, serif; font-size: 12pt; font-weight: normal; margin: 0 0 18pt 0;">
                            - 2 -
                        </div>

                        {{-- Blok Lampiran Surat (P#32 & TBL#33) --}}
                        <div>Lampiran Surat</div>
                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin: 2pt 0 14pt 0; border: none; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; line-height: 1.15;">
                            <tr>
                                <td style="width: 11.1%; vertical-align: top; border: none; padding: 1pt 0;">Nomor</td>
                                <td style="width: 3.1%; vertical-align: top; border: none; padding: 1pt 0;">:</td>
                                <td style="width: 85.8%; vertical-align: top; border: none; padding: 1pt 0;" id="sheet-lampiran-nomor">{{ $config['nomor_surat'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td style="width: 11.1%; vertical-align: top; border: none; padding: 1pt 0;">Tanggal</td>
                                <td style="width: 3.1%; vertical-align: top; border: none; padding: 1pt 0;">:</td>
                                <td style="width: 85.8%; vertical-align: top; border: none; padding: 1pt 0;" id="sheet-lampiran-tanggal">{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}</td>
                            </tr>
                        </table>

                        <div style="margin: 12pt 0 8pt 0;">Daftar Undangan</div>

                        {{-- Isi Daftar Undangan (WYSIWYG Editable) --}}
                        <div>
                            <div
                                id="surat-lampiran-content"
                                class="office-editable-box prose-gov"
                                contenteditable="true"
                                data-editor="lampiran"
                                data-placeholder="Ketik daftar pejabat / unit kerja / peserta undangan rapat pada lampiran ini..."
                                style="min-height: 180px; font-family: Arial, Helvetica, sans-serif; font-size: 12pt; font-weight: normal; line-height: 1.15; text-align: justify; padding: 6pt 8pt; border: 1px dashed #cbd5e1; background: #ffffff; outline: none;"
                            >{!! $config['lampiran_content'] ?? '' !!}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Status Bar Bawah -->
    <div id="office-status-bar" class="bg-slate-900 text-slate-300 text-xs py-2 px-4 rounded-xl flex items-center justify-between shadow-xs">
        <div class="flex items-center gap-3">
            <span id="office-status-words" class="font-semibold text-white">0 kata</span>
            <span class="text-slate-500">&bull;</span>
            <span class="text-slate-300">Kertas A4 (Margin Kiri 3cm, Kanan 2cm, Bawah 2.5cm) &bull; Kop: Times New Roman &bull; Isi: Arial 12pt</span>
            <span id="status-format-label" class="hidden">{{ $config['template_type'] ?? 'undangan' }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span id="office-status-save-state" class="inline-flex items-center gap-1.5 text-emerald-400 font-semibold">
                <span id="office-status-save-dot" class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                <span id="office-status-save-label">Tersimpan</span>
            </span>
        </div>
    </div>

    <!-- 6. Modal Penyesuaian Naskah Surat Dinas -->
    <div 
        id="modal-surat-config" 
        class="fixed inset-0 z-50 hidden bg-slate-950/70 backdrop-blur-xs flex items-center justify-center p-3 sm:p-5 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-surat-config-title"
    >
        <div class="bg-white border border-slate-300 rounded-2xl max-w-2xl w-full max-h-[92vh] flex flex-col shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="p-4 sm:p-5 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="4" x2="20" y1="21" y2="21"></line><line x1="4" x2="20" y1="14" y2="14"></line><line x1="4" x2="20" y1="7" y2="7"></line><circle cx="8" cy="7" r="2" fill="currentColor"></circle><circle cx="16" cy="14" r="2" fill="currentColor"></circle><circle cx="10" cy="21" r="2" fill="currentColor"></circle></svg>
                    </div>
                    <div>
                        <h3 id="modal-surat-config-title" class="text-sm font-bold text-slate-900">Penyesuaian Naskah Dinas Surat Undangan / Edaran</h3>
                        <p class="text-[11px] text-slate-500 font-medium">Sesuai standar Tata Naskah Dinas Kemdiktisaintek / LLDIKTI Wilayah X (Kop Times New Roman, Isi Arial 12pt).</p>
                    </div>
                </div>
                <button type="button" onclick="closeSuratConfigModal()" class="text-slate-400 hover:text-slate-700 p-1 rounded-lg">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-4 sm:p-6 overflow-y-auto space-y-5 text-xs">
                <!-- 1. Pilihan Format Template -->
                <div class="space-y-2">
                    <label class="text-xs font-bold text-slate-900 block">Pilihan Format Dokumen</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="p-3 border rounded-xl flex items-start gap-3 cursor-pointer transition hover:bg-slate-50 border-slate-300 has-checked:border-slate-950 has-checked:bg-slate-50">
                            <input type="radio" name="modal_template_type" value="undangan" class="mt-0.5 text-slate-900 focus:ring-slate-900" {{ ($config['template_type'] ?? 'undangan') === 'undangan' ? 'checked' : '' }}>
                            <div>
                                <strong class="block text-slate-900 font-bold">Surat Undangan Rapat</strong>
                                <span class="text-[11px] text-slate-500">Nomor, Lampiran, Hal, Tanggal, Rincian Jadwal &amp; Ruang Rapat (Standar Resmi).</span>
                            </div>
                        </label>
                        <label class="p-3 border rounded-xl flex items-start gap-3 cursor-pointer transition hover:bg-slate-50 border-slate-300 has-checked:border-slate-950 has-checked:bg-slate-50">
                            <input type="radio" name="modal_template_type" value="edaran" class="mt-0.5 text-slate-900 focus:ring-slate-900" {{ ($config['template_type'] ?? 'undangan') === 'edaran' ? 'checked' : '' }}>
                            <div>
                                <strong class="block text-slate-900 font-bold">Surat Edaran Resmi (SE)</strong>
                                <span class="text-[11px] text-slate-500">Judul Surat Edaran, Nomor, Tentang, dan Butir Arahan Kebijakan.</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- 2. Kop Surat Settings -->
                <div class="space-y-3 pt-3 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-900">Pengaturan Kop Surat Instansi (Times New Roman)</label>
                        <div class="flex items-center gap-3">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal_show_kop" class="rounded border-slate-300 text-slate-900" {{ ($config['show_kop'] ?? true) ? 'checked' : '' }}>
                                <span class="text-[11px] font-semibold text-slate-700">Tampilkan Kop</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="checkbox" id="modal_show_logo" class="rounded border-slate-300 text-slate-900" {{ ($config['show_logo'] ?? true) ? 'checked' : '' }}>
                                <span class="text-[11px] font-semibold text-slate-700">Tampilkan Logo</span>
                            </label>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Instansi Induk (16pt Reguler, Pisahkan Baris)</label>
                            <textarea id="modal_instansi_induk" rows="2" class="input text-xs font-medium w-full">{{ $config['instansi_induk'] ?? "KEMENTERIAN PENDIDIKAN TINGGI,\nSAINS, DAN TEKNOLOGI" }}</textarea>
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Instansi Pelaksana (14pt Bold)</label>
                            <input type="text" id="modal_instansi_pelaksana" value="{{ $config['instansi_pelaksana'] ?? 'LEMBAGA LAYANAN PENDIDIKAN TINGGI WILAYAH X' }}" class="input text-xs font-medium w-full">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-600 block mb-1">Alamat, Telepon, Laman &amp; Pos-el (12pt Reguler, 3 Baris)</label>
                        <textarea id="modal_alamat_kontak" rows="3" class="input text-xs font-medium w-full">{{ $config['alamat_kontak'] ?? "Jalan Khatib Sulaiman Padang 25137\nTelepon (0751) 70567376\nLaman lldikti10.kemdiktisaintek.go.id  Pos-el info.lldikti10@kemdiktisaintek.go.id" }}</textarea>
                    </div>
                </div>

                <!-- 3. Metadata Surat (Nomor, Lampiran, Hal, Tanggal, Tujuan) -->
                <div class="space-y-3 pt-3 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-900">Metadata Kepala Surat (Arial 12pt)</label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="modal_show_lampiran" class="rounded border-slate-300 text-slate-900" {{ $showLampiranSheet ? 'checked' : '' }}>
                            <span class="text-[11px] font-semibold text-slate-700">Sertakan Halaman 2 (Lampiran Daftar Undangan)</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Nomor Surat</label>
                            <input type="text" id="modal_nomor_surat" value="{{ $config['nomor_surat'] ?? '' }}" class="input text-xs font-medium w-full">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Lampiran</label>
                            <input type="text" id="modal_lampiran" value="{{ $config['lampiran'] ?? '-' }}" class="input text-xs font-medium w-full" placeholder="Contoh: Satu berkas / 1 (satu) lembar">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Tanggal Surat</label>
                            <input type="text" id="modal_tanggal_surat" value="{{ $config['tanggal_surat'] ?? now()->translatedFormat('d F Y') }}" class="input text-xs font-medium w-full" placeholder="Contoh: 5 November 2026">
                            <input type="hidden" id="modal_tempat_surat" value="{{ $config['tempat_surat'] ?? 'Padang' }}">
                            <input type="hidden" id="modal_sifat" value="{{ $config['sifat'] ?? 'Biasa' }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-600 block mb-1">Hal</label>
                        <input type="text" id="modal_perihal" value="{{ $config['perihal'] ?? ('Undangan Rapat ' . $agenda->judul_rapat) }}" class="input text-xs font-medium w-full">
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-600 block mb-1">Tujuan Surat (Otomatis diawali "Yth. ")</label>
                        <textarea id="modal_tujuan_surat" rows="2" class="input text-xs font-medium w-full">{{ $tujuanFormatted }}</textarea>
                    </div>
                </div>

                <!-- 4. Penandatangan & Tembusan -->
                <div class="space-y-3 pt-3 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-bold text-slate-900">Pejabat Penandatangan &amp; Tembusan</label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="checkbox" id="modal_show_signature" class="rounded border-slate-300 text-slate-900" {{ ($config['show_signature'] ?? true) ? 'checked' : '' }}>
                            <span class="text-[11px] font-semibold text-slate-700">Tampilkan Tanda Tangan Digital</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Jabatan Penandatangan</label>
                            <input type="text" id="modal_signer_role" value="{{ $config['signer_role'] ?? 'Kepala' }}" class="input text-xs font-medium w-full" placeholder="Kepala">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">Nama Pejabat (Tanpa Gelar)</label>
                            <input type="text" id="modal_signer_name" value="{{ $config['signer_name'] ?? 'Afdalisma' }}" class="input text-xs font-medium w-full" placeholder="Afdalisma">
                        </div>
                        <div>
                            <label class="text-[11px] font-semibold text-slate-600 block mb-1">NIP (Dicetak Tebal / Bold)</label>
                            <input type="text" id="modal_signer_nip" value="{{ $config['signer_nip'] ?? '197012051992032002' }}" class="input text-xs font-medium w-full" placeholder="197012051992032002">
                        </div>
                    </div>
                    <div>
                        <label class="text-[11px] font-semibold text-slate-600 block mb-1">Daftar Tembusan (Pisahkan dengan baris baru)</label>
                        <textarea id="modal_tembusan" rows="2" class="input text-xs font-medium w-full">{{ $config['tembusan'] ?? '' }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2 shrink-0">
                <button type="button" onclick="closeSuratConfigModal()" class="button secondary text-xs font-bold">Batal</button>
                <button type="button" onclick="applySuratConfigToSheet()" class="button text-xs font-bold bg-slate-900 text-white">Terapkan ke Lembar Naskah</button>
            </div>
        </div>
    </div>
</div>
@endsection
