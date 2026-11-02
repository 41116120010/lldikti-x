@extends('layouts.app')

@section('title', 'Buat Agenda Rapat')
@section('heading', 'Buat Agenda Rapat Baru')
@section('subtitle', 'Jadwalkan pertemuan kedinasan, tentukan sasaran unit kerja, dan lampirkan surat edaran')

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
    $defaultMulai = now()->addHour()->format('Y-m-d\TH:i');
    $defaultSelesai = now()->addHours(3)->format('Y-m-d\TH:i');
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.agendas.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @if($errors->any())
                <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-900 space-y-1.5 shadow-2xs" role="alert">
                    <div class="font-extrabold flex items-center gap-2 text-rose-950">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="text-rose-600 shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        <span>Pemberitahuan Validasi & Deteksi Konflik Jadwal</span>
                    </div>
                    <ul class="list-disc list-inside space-y-1 text-rose-800 font-medium pl-1">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 border-b border-slate-200 pb-2">1. Informasi Utama Pertemuan</h3>

                <!-- Judul Rapat -->
                <div class="field">
                    <label for="judul_rapat" class="text-xs font-bold text-slate-900 block mb-1">Judul / Perihal Pertemuan Rapat <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="judul_rapat" 
                        name="judul_rapat" 
                        value="{{ old('judul_rapat') }}" 
                        class="input w-full font-medium @error('judul_rapat') input-error @enderror" 
                        placeholder="Contoh: Rapat Koordinasi Evaluasi Beban Kerja Dosen (BKD)" 
                        required 
                        autofocus
                    >
                    @error('judul_rapat')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Jenis Rapat -->
                    <div class="field">
                        <label for="jenis_rapat" class="text-xs font-bold text-slate-900 block mb-1">Jenis Pertemuan <span class="text-rose-600">*</span></label>
                        <input 
                            type="text" 
                            id="jenis_rapat" 
                            name="jenis_rapat" 
                            list="jenis_rapat_suggestions"
                            value="{{ old('jenis_rapat', config('agenda.jenis_rapat_default')) }}" 
                            class="input w-full font-semibold @error('jenis_rapat') input-error @enderror" 
                            placeholder="Contoh: Rapat Koordinasi, Workshop, Rapat Pleno, dsb."
                            maxlength="100"
                            required
                        >
                        <datalist id="jenis_rapat_suggestions">
                            @foreach ((config('agenda.jenis_rapat_suggestions') ?? []) as $suggestion)
                                <option value="{{ $suggestion }}">
                            @endforeach
                        </datalist>
                        <p class="text-[11px] text-slate-500 mt-1 font-medium">Ketik jenis pertemuan bebas atau pilih dari daftar saran.</p>
                        @error('jenis_rapat')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Format Penyelenggaraan -->
                    <div class="field">
                        <label for="tipe_rapat" class="text-xs font-bold text-slate-900 block mb-1">Format Pelaksanaan <span class="text-rose-600">*</span></label>
                        <select id="tipe_rapat" name="tipe_rapat" class="input w-full font-semibold @error('tipe_rapat') input-error @enderror" onchange="toggleFormatFields(this.value)" required>
                            <option value="offline" {{ old('tipe_rapat', 'offline') === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring di Kantor)</option>
                            <option value="online" {{ old('tipe_rapat') === 'online' ? 'selected' : '' }}>Daring (Pertemuan Virtual)</option>
                            <option value="hybrid" {{ old('tipe_rapat') === 'hybrid' ? 'selected' : '' }}>Hibrida (Luring & Daring)</option>
                        </select>
                        @error('tipe_rapat')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Lokasi Ruang & Tautan Daring -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="field" id="wrap-lokasi">
                        <label for="lokasi_ruang" class="text-xs font-bold text-slate-900 block mb-1">Lokasi / Nama Ruang Rapat <span id="req-lokasi" class="text-rose-600">*</span></label>
                        <input 
                            type="text" 
                            id="lokasi_ruang" 
                            name="lokasi_ruang" 
                            value="{{ old('lokasi_ruang') }}" 
                            class="input w-full font-medium @error('lokasi_ruang') input-error @enderror" 
                            placeholder="Contoh: Ruang Sidang Utama Lantai 2"
                        >
                        @error('lokasi_ruang')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field" id="wrap-link">
                        <label for="link_meeting" class="text-xs font-bold text-slate-900 block mb-1">Tautan Daring (Zoom / GMeet) <span id="req-link" class="text-rose-600 hidden">*</span></label>
                        <input 
                            type="text" 
                            id="link_meeting" 
                            name="link_meeting" 
                            value="{{ old('link_meeting') }}" 
                            class="input w-full font-mono text-xs @error('link_meeting') input-error @enderror" 
                            placeholder="https://zoom.us/j/... atau meet.google.com/..."
                        >
                        @error('link_meeting')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Waktu Pelaksanaan (Format 24 Jam WIB) -->
                <div class="field">
                    <x-wib-schedule-picker :waktuMulai="$defaultMulai" :waktuSelesai="$defaultSelesai" />
                </div>
            </div>

            <!-- 2. Target Peserta & Scoping Unit -->
            <div class="space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">2. Sasaran Peserta & Unit Kerja</h3>

                <div class="space-y-3">
                    @if($currentUser->isAdministrator())
                        {{-- Opsi 1: Pleno Seluruh Unit (Khusus Administrator) --}}
                        <label class="flex items-center gap-3.5 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition min-h-[44px]">
                            <input 
                                type="radio" 
                                name="target_type" 
                                value="all_units" 
                                {{ old('target_type', 'all_units') === 'all_units' ? 'checked' : '' }} 
                                onchange="handleTargetTypeChange('all_units')"
                                class="w-4 h-4 accent-slate-950"
                            >
                            <div>
                                <span class="font-bold text-slate-950 text-xs block">Terbuka untuk Seluruh Unit Kerja (Pleno / Universal)</span>
                                <span class="text-[11px] text-slate-600 font-medium">Seluruh pegawai dari semua bagian/Pokja LLDIKTI berhak mengikuti rapat.</span>
                            </div>
                        </label>

                        {{-- Opsi 2: Unit Kerja Tertentu --}}
                        <label class="flex items-center gap-3.5 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition min-h-[44px]">
                            <input 
                                type="radio" 
                                name="target_type" 
                                value="selected_units" 
                                {{ old('target_type') === 'selected_units' ? 'checked' : '' }} 
                                onchange="handleTargetTypeChange('selected_units')"
                                class="w-4 h-4 accent-slate-950"
                            >
                            <div>
                                <span class="font-bold text-slate-950 text-xs block">Terbatas untuk Unit Kerja Tertentu</span>
                                <span class="text-[11px] text-slate-600 font-medium">Hanya pegawai dari unit kerja terpilih yang dapat mengisi presensi.</span>
                            </div>
                        </label>
                    @else
                        {{-- Untuk Admin Unit: Opsi Rapat Unit Sendiri --}}
                        <label class="flex items-center gap-3.5 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition min-h-[44px]">
                            <input 
                                type="radio" 
                                name="target_type" 
                                value="selected_units" 
                                {{ old('target_type', 'selected_units') === 'selected_units' ? 'checked' : '' }} 
                                onchange="handleTargetTypeChange('selected_units')"
                                class="w-4 h-4 accent-slate-950"
                            >
                            <div>
                                <span class="font-bold text-slate-950 text-xs block">Internal Unit Kerja: {{ $currentUser->unit?->nama_unit ?? 'Unit Anda' }}</span>
                                <span class="text-[11px] text-slate-600 font-medium">Agenda ditujukan khusus untuk seluruh pegawai di lingkungan unit kerja Anda.</span>
                            </div>
                        </label>
                    @endif

                    {{-- Opsi 3: Khusus ASN Tertentu (Personal Terpilih) --}}
                    <label class="flex items-center gap-3.5 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition min-h-[44px]">
                        <input 
                            type="radio" 
                            name="target_type" 
                            value="selected_users" 
                            {{ old('target_type') === 'selected_users' ? 'checked' : '' }} 
                            onchange="handleTargetTypeChange('selected_users')"
                            class="w-4 h-4 accent-slate-950"
                        >
                        <div>
                            <span class="font-bold text-slate-950 text-xs flex items-center gap-2">
                                <span>Khusus ASN Tertentu (Personal Terpilih)</span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-indigo-100 text-indigo-900 border border-indigo-200">Personal</span>
                            </span>
                            <span class="text-[11px] text-slate-600 font-medium">Hanya pegawai ASN tertentu yang dipilih secara khusus yang dapat mengisi presensi rapat.</span>
                        </div>
                    </label>
                </div>

                <!-- 1. Checklist Unit Kerja (Ditampilkan jika target_type = selected_units dan user adalah Administrator) -->
                @if($currentUser->isAdministrator())
                    <div id="unit-selection-wrap" class="{{ old('target_type', 'all_units') === 'selected_units' ? '' : 'hidden' }} p-4 bg-slate-50 border border-slate-300 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold text-slate-900">Pilih Unit Kerja yang Ditugaskan:</div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="selectAllUnits(true)" class="text-[11px] font-bold text-blue-900 hover:underline">Pilih Semua</button>
                                <span class="text-slate-300">|</span>
                                <button type="button" onclick="selectAllUnits(false)" class="text-[11px] font-bold text-slate-600 hover:underline">Hapus Semua</button>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2">
                            @foreach($units as $unit)
                                <label class="flex items-center gap-2 p-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-100 text-xs font-medium min-h-[44px]">
                                    <input 
                                        type="checkbox" 
                                        name="unit_ids[]" 
                                        value="{{ $unit->id }}" 
                                        {{ in_array($unit->id, old('unit_ids', [])) ? 'checked' : '' }}
                                        class="w-4 h-4 accent-slate-950 rounded unit-checkbox"
                                    >
                                    <span class="font-mono font-bold text-slate-950">{{ $unit->kode_unit }}</span>
                                    <span class="text-slate-700 truncate">— {{ $unit->nama_unit }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('unit_ids')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    {{-- Hidden input unit_ids jika Admin Unit memilih selected_units --}}
                    <input type="hidden" id="admin-unit-default-id" name="unit_ids[]" value="{{ $currentUser->unit_id }}" {{ old('target_type', 'selected_units') === 'selected_users' ? 'disabled' : '' }}>
                @endif

                <!-- 2. Checklist ASN Tertentu (Ditampilkan jika target_type = selected_users) -->
                <div id="user-selection-wrap" class="{{ old('target_type') === 'selected_users' ? '' : 'hidden' }} p-4 bg-slate-50 border border-slate-300 rounded-xl space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-slate-900">Pilih ASN yang Diundang:</span>
                            <span id="selected-asn-badge" class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-100 text-indigo-900 border border-indigo-200">
                                0 ASN Terpilih
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-xs">
                            <button type="button" onclick="selectAllFilteredAsn(true)" class="text-[11px] font-bold text-indigo-900 hover:underline">Pilih Semua Terlihat</button>
                            <span class="text-slate-300">|</span>
                            <button type="button" onclick="selectAllFilteredAsn(false)" class="text-[11px] font-bold text-slate-600 hover:underline">Kosongkan Pilihan</button>
                        </div>
                    </div>

                    <!-- Filter Pencarian Cepat ASN -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        </div>
                        <input 
                            type="text" 
                            id="asn-search-input" 
                            placeholder="Cari berdasarkan nama pegawai, NIP, atau unit kerja..." 
                            oninput="filterAsnList(this.value)"
                            class="input pl-9 text-xs w-full bg-white border-slate-300"
                        >
                    </div>

                    <!-- Daftar Pegawai ASN -->
                    <div id="asn-list-container" class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto pr-1">
                        @foreach($users as $u)
                            <label class="asn-item flex items-center gap-2.5 p-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-100 text-xs font-medium min-h-[44px]"
                                data-name="{{ strtolower($u->name) }}"
                                data-nip="{{ $u->nip ?? '' }}"
                                data-unit="{{ strtolower($u->unit?->nama_unit ?? '') }}"
                            >
                                <input 
                                    type="checkbox" 
                                    name="user_ids[]" 
                                    value="{{ $u->id }}" 
                                    {{ in_array($u->id, old('user_ids', [])) ? 'checked' : '' }}
                                    onchange="updateAsnCount()"
                                    class="w-4 h-4 accent-indigo-950 rounded asn-checkbox shrink-0"
                                >
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-slate-900 truncate">{{ $u->name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono truncate">
                                        NIP: {{ $u->nip ?? '-' }} &bull; {{ $u->unit?->kode_unit ?? 'Umum' }}
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <div id="asn-empty-state" class="hidden text-center py-4 text-xs text-slate-500 font-medium">
                        Tidak ditemukan ASN yang cocok dengan kata kunci pencarian.
                    </div>
                    @error('user_ids')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 3. Penugasan Pimpinan & Notulis Rapat (Hybrid / Fleksibel) -->
            <div class="space-y-4 pt-4 border-t border-slate-200">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">3. Penugasan Pimpinan & Notulis Rapat</h3>
                    <p class="text-[11px] text-slate-600 font-medium mt-0.5">Opsional. Jika tidak dipilih, pimpinan dan notulis otomatis menggunakan akun pembuat agenda (alur hybrid).</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Pemimpin Rapat -->
                    <div class="field">
                        <label for="pimpinan_id" class="text-xs font-bold text-slate-900 block mb-1">
                            Pemimpin Rapat
                            <span class="text-[11px] font-normal text-slate-700">(Penanggung Jawab Acara)</span>
                        </label>
                        <select id="pimpinan_id" name="pimpinan_id" class="input w-full font-medium @error('pimpinan_id') input-error @enderror">
                            <option value="">Default: Pembuat Agenda ({{ $currentUser->name }})</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('pimpinan_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} (NIP: {{ $user->nip ?? '-' }}) — {{ $user->unit?->kode_unit ?? 'Pusat' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-600 font-medium mt-1">Nama dan NIP akan otomatis tertera di lembar pengesahan Berita Acara.</p>
                        @error('pimpinan_id')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notulis Rapat -->
                    <div class="field">
                        <label for="notulis_id" class="text-xs font-bold text-slate-900 block mb-1">
                            Notulis Rapat
                            <span class="text-[11px] font-normal text-slate-700">(Pencatat Notulensi & Dokumentasi)</span>
                        </label>
                        <select id="notulis_id" name="notulis_id" class="input w-full font-medium @error('notulis_id') input-error @enderror">
                            <option value="">Default: Pembuat Agenda ({{ $currentUser->name }})</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('notulis_id') == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }} (NIP: {{ $user->nip ?? '-' }}) — {{ $user->unit?->kode_unit ?? 'Pusat' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-600 font-medium mt-1">Pegawai yang ditunjuk otomatis berhak mengisi notulen saat rapat berlangsung.</p>
                        @error('notulis_id')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 4. Berkas Surat Edaran -->
            <div class="space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">4. Surat Undangan / Edaran Resmi</h3>

                <div class="field">
                    <label for="surat_edaran" class="text-xs font-bold text-slate-900 block mb-1">Unggah Dokumen Undangan Resmi (PDF / Gambar)</label>
                    <input 
                        type="file" 
                        id="surat_edaran" 
                        name="surat_edaran" 
                        accept=".pdf,image/jpeg,image/png,image/webp" 
                        class="block w-full text-xs text-slate-700 font-medium file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-950 file:text-white hover:file:bg-slate-800 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Format didukung: PDF, JPG, PNG, WebP (Maksimal 5 MB). Bersifat opsional sebagai lampiran resmi bagi peserta.</p>
                    @error('surat_edaran')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Live Client-side Preview Container -->
                <div id="surat-edaran-client-preview" class="hidden p-3.5 bg-slate-50 border border-slate-300 rounded-xl space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Thumbnail / Format Icon Container -->
                            <div id="preview-icon-box" class="w-12 h-12 rounded-lg bg-slate-200 border border-slate-300 flex items-center justify-center shrink-0 overflow-hidden">
                                <!-- Dynamic Icon / Thumbnail injected here -->
                            </div>
                            <div class="min-w-0">
                                <div id="preview-filename" class="text-xs font-bold text-slate-900 truncate">nama_dokumen.pdf</div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-600 font-medium mt-0.5">
                                    <span id="preview-filesize">0 KB</span>
                                    <span>&bull;</span>
                                    <span id="preview-filetype" class="uppercase font-mono font-bold text-slate-700">PDF</span>
                                </div>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            onclick="clearSuratEdaranInput()" 
                            class="button small secondary text-xs font-bold text-rose-700 border-rose-200 hover:bg-rose-50 flex items-center gap-1.5 shrink-0 min-h-[36px] cursor-pointer"
                            title="Batalkan pilihan berkas ini"
                        >
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span>Batal</span>
                        </button>
                    </div>

                    <!-- Image Preview Viewport (visible only for image uploads) -->
                    <div id="preview-image-viewport" class="hidden rounded-lg border border-slate-200 bg-white p-2 flex items-center justify-center max-h-56 overflow-hidden">
                        <img id="preview-image-img" src="" alt="Pratinjau Gambar Berkas" class="max-h-52 w-auto object-contain rounded">
                    </div>
                </div>
            </div>

            <!-- Status Rapat & Action Buttons -->
            <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <label for="status" class="text-xs font-bold text-slate-900 block mb-1">Status Publikasi Awal</label>
                    <select id="status" name="status" class="input text-xs font-semibold @error('status') input-error @enderror">
                        <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>Terjadwal (Langsung Aktif)</option>
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Konsep (Simpan Sementara)</option>
                    </select>
                    @error('status')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.agendas.index') }}" class="button secondary text-xs font-bold">Batal</a>
                    <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Simpan Agenda Rapat</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Agenda Planning Information (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <h3 class="font-extrabold text-sm text-white mb-3 flex items-center gap-2">
                <svg class="text-slate-300" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <span>Alur Pelaksanaan Agenda</span>
            </h3>
            <div class="space-y-3 text-xs text-slate-200 leading-relaxed font-medium">
                <div>
                    <strong class="text-white block font-bold">1. Terjadwal:</strong>
                    Agenda diumumkan dan siap dibuka presensi saat waktu rapat dimulai.
                </div>
                <div>
                    <strong class="text-white block font-bold">2. Berlangsung (Aktif):</strong>
                    Pegawai dapat mengisi presensi kehadiran, selfie wajah, dan tanda tangan digital.
                </div>
                <div>
                    <strong class="text-white block font-bold">3. Selesai:</strong>
                    Sesi presensi ditutup, notulen digital diunggah, dan laporan PDF/Word dapat diekspor.
                </div>
            </div>
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Verifikasi Presensi Digital</h4>
            <p class="leading-relaxed font-medium">Setiap presensi pegawai akan diverifikasi secara otomatis dengan foto selfie WebRTC terkompresi dan tanda tangan canvas HTML5.</p>
        </div>
    </div>
</div>

<script>
function toggleFormatFields(format) {
    const wrapLokasi = document.getElementById('wrap-lokasi');
    const wrapLink = document.getElementById('wrap-link');
    const reqLokasi = document.getElementById('req-lokasi');
    const reqLink = document.getElementById('req-link');

    if (format === 'offline') {
        wrapLokasi.style.display = 'block';
        wrapLink.style.display = 'none';
        if (reqLokasi) reqLokasi.classList.remove('hidden');
        if (reqLink) reqLink.classList.add('hidden');
    } else if (format === 'online') {
        wrapLokasi.style.display = 'none';
        wrapLink.style.display = 'block';
        if (reqLokasi) reqLokasi.classList.add('hidden');
        if (reqLink) reqLink.classList.remove('hidden');
    } else {
        wrapLokasi.style.display = 'block';
        wrapLink.style.display = 'block';
        if (reqLokasi) reqLokasi.classList.remove('hidden');
        if (reqLink) reqLink.classList.remove('hidden');
    }
}

function toggleUnitList(show) {
    const wrap = document.getElementById('unit-selection-wrap');
    if (wrap) {
        if (show) {
            wrap.classList.remove('hidden');
        } else {
            wrap.classList.add('hidden');
        }
    }
}

function handleTargetTypeChange(targetType) {
    const unitWrap = document.getElementById('unit-selection-wrap');
    const userWrap = document.getElementById('user-selection-wrap');
    const adminUnitDefaultId = document.getElementById('admin-unit-default-id');

    if (targetType === 'all_units') {
        if (unitWrap) unitWrap.classList.add('hidden');
        if (userWrap) userWrap.classList.add('hidden');
        if (adminUnitDefaultId) adminUnitDefaultId.disabled = true;
    } else if (targetType === 'selected_units') {
        if (unitWrap) unitWrap.classList.remove('hidden');
        if (userWrap) userWrap.classList.add('hidden');
        if (adminUnitDefaultId) adminUnitDefaultId.disabled = false;
    } else if (targetType === 'selected_users') {
        if (unitWrap) unitWrap.classList.add('hidden');
        if (userWrap) userWrap.classList.remove('hidden');
        if (adminUnitDefaultId) adminUnitDefaultId.disabled = true;
        updateAsnCount();
    }
}

function selectAllUnits(checked) {
    const checkboxes = document.querySelectorAll('.unit-checkbox');
    checkboxes.forEach(cb => cb.checked = checked);
}

function updateAsnCount() {
    const checked = document.querySelectorAll('.asn-checkbox:checked').length;
    const badge = document.getElementById('selected-asn-badge');
    if (badge) {
        badge.textContent = `${checked} ASN Terpilih`;
        if (checked > 0) {
            badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-600 text-white shadow-xs';
        } else {
            badge.className = 'px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-indigo-100 text-indigo-900 border border-indigo-200';
        }
    }
}

function filterAsnList(query) {
    const q = (query || '').toLowerCase().trim();
    const items = document.querySelectorAll('.asn-item');
    const emptyState = document.getElementById('asn-empty-state');
    let visibleCount = 0;

    items.forEach(item => {
        const name = item.getAttribute('data-name') || '';
        const nip = item.getAttribute('data-nip') || '';
        const unit = item.getAttribute('data-unit') || '';

        if (!q || name.includes(q) || nip.includes(q) || unit.includes(q)) {
            item.classList.remove('hidden');
            visibleCount++;
        } else {
            item.classList.add('hidden');
        }
    });

    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
        } else {
            emptyState.classList.add('hidden');
        }
    }
}

function selectAllFilteredAsn(checked) {
    const items = document.querySelectorAll('.asn-item');
    items.forEach(item => {
        if (!item.classList.contains('hidden')) {
            const cb = item.querySelector('.asn-checkbox');
            if (cb) cb.checked = checked;
        }
    });
    updateAsnCount();
}

function handleSuratEdaranChange(event) {
    const fileInput = event.target;
    const previewContainer = document.getElementById('surat-edaran-client-preview');
    const iconBox = document.getElementById('preview-icon-box');
    const filenameEl = document.getElementById('preview-filename');
    const filesizeEl = document.getElementById('preview-filesize');
    const filetypeEl = document.getElementById('preview-filetype');
    const imageViewport = document.getElementById('preview-image-viewport');
    const imageImg = document.getElementById('preview-image-img');

    if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        if (previewContainer) previewContainer.classList.add('hidden');
        return;
    }

    const file = fileInput.files[0];
    const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
    const sizeInKB = Math.round(file.size / 1024);
    const sizeText = file.size > 1024 * 1024 ? `${sizeInMB} MB` : `${sizeInKB} KB`;

    filenameEl.textContent = file.name;
    filesizeEl.textContent = sizeText;

    const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
    const isImage = file.type.startsWith('image/');

    if (isImage) {
        filetypeEl.textContent = 'Gambar (' + (file.name.split('.').pop() || 'IMG').toUpperCase() + ')';
        const reader = new FileReader();
        reader.onload = function(e) {
            imageImg.src = e.target.result;
            imageViewport.classList.remove('hidden');
            iconBox.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
        };
        reader.readAsDataURL(file);
    } else if (isPdf) {
        filetypeEl.textContent = 'Dokumen PDF';
        imageViewport.classList.add('hidden');
        imageImg.src = '';
        iconBox.innerHTML = `
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" class="text-rose-600">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
            </svg>
        `;
    } else {
        filetypeEl.textContent = (file.name.split('.').pop() || 'BERKAS').toUpperCase();
        imageViewport.classList.add('hidden');
        imageImg.src = '';
        iconBox.innerHTML = `
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-600">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
            </svg>
        `;
    }

    previewContainer.classList.remove('hidden');
}

function clearSuratEdaranInput() {
    const fileInput = document.getElementById('surat_edaran');
    const previewContainer = document.getElementById('surat-edaran-client-preview');
    const imageViewport = document.getElementById('preview-image-viewport');
    const imageImg = document.getElementById('preview-image-img');

    if (fileInput) fileInput.value = '';
    if (previewContainer) previewContainer.classList.add('hidden');
    if (imageViewport) imageViewport.classList.add('hidden');
    if (imageImg) imageImg.src = '';
}

function initAgendaForm() {
    const tipeRapat = document.getElementById('tipe_rapat');
    if (tipeRapat) {
        toggleFormatFields(tipeRapat.value);
    }

    const activeTargetRadio = document.querySelector('input[name="target_type"]:checked');
    if (activeTargetRadio) {
        handleTargetTypeChange(activeTargetRadio.value);
    }
    updateAsnCount();

    const suratEdaranInput = document.getElementById('surat_edaran');
    if (suratEdaranInput && !suratEdaranInput.dataset.previewBound) {
        suratEdaranInput.dataset.previewBound = 'true';
        suratEdaranInput.addEventListener('change', handleSuratEdaranChange);
    }
}

if (document.readyState !== 'loading') {
    initAgendaForm();
} else {
    document.addEventListener('DOMContentLoaded', initAgendaForm);
}
window.addEventListener('page:loaded', initAgendaForm);
</script>
@endsection
