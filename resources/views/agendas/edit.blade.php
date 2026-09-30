@extends('layouts.app')

@section('title', 'Edit Agenda Rapat')
@section('heading', 'Edit Agenda Rapat')
@section('subtitle', 'Perbarui informasi agenda: ' . $agenda->judul_rapat)

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.agendas.update', $agenda) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

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
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 border-b border-slate-200 pb-2">1. Informasi Utama Rapat</h3>

                <!-- Judul Rapat -->
                <div class="field">
                    <label for="judul_rapat" class="text-xs font-bold text-slate-900 block mb-1">Judul / Perihal Pertemuan Rapat <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="judul_rapat" 
                        name="judul_rapat" 
                        value="{{ old('judul_rapat', $agenda->judul_rapat) }}" 
                        class="input w-full font-medium @error('judul_rapat') input-error @enderror" 
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
                            value="{{ old('jenis_rapat', $agenda->jenis_rapat) }}" 
                            class="input w-full font-semibold @error('jenis_rapat') input-error @enderror" 
                            placeholder="Contoh: Rapat Koordinasi, Workshop, Rapat Pleno, dsb."
                            maxlength="100"
                            required
                        >
                        <datalist id="jenis_rapat_suggestions">
                            @foreach (config('agenda.jenis_rapat_suggestions') as $suggestion)
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
                            <option value="offline" {{ old('tipe_rapat', $agenda->tipe_rapat) === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring di Kantor)</option>
                            <option value="online" {{ old('tipe_rapat', $agenda->tipe_rapat) === 'online' ? 'selected' : '' }}>Daring (Pertemuan Virtual)</option>
                            <option value="hybrid" {{ old('tipe_rapat', $agenda->tipe_rapat) === 'hybrid' ? 'selected' : '' }}>Hibrida (Luring & Daring)</option>
                        </select>
                        @error('tipe_rapat')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Ruangan & Tautan Link -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="field" id="wrap-lokasi">
                        <label for="lokasi_ruang" class="text-xs font-bold text-slate-900 block mb-1">Lokasi / Nama Ruang Rapat <span id="req-lokasi" class="text-rose-600">*</span></label>
                        <input 
                            type="text" 
                            id="lokasi_ruang" 
                            name="lokasi_ruang" 
                            value="{{ old('lokasi_ruang', $agenda->lokasi_ruang) }}" 
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
                            value="{{ old('link_meeting', $agenda->link_meeting) }}" 
                            class="input w-full font-mono text-xs @error('link_meeting') input-error @enderror" 
                            placeholder="https://zoom.us/j/... atau meet.google.com/..."
                        >
                        @error('link_meeting')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Jadwal Waktu Mulai & Selesai (Format 24 Jam WIB) -->
                <div class="field">
                    <x-wib-schedule-picker :waktuMulai="$agenda->waktu_mulai" :waktuSelesai="$agenda->waktu_selesai" />
                </div>
            </div>

            <!-- Target Partisipan Section -->
            <div class="space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">2. Target Peserta & Unit Kerja</h3>

                @if($currentUser->isAdministrator())
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                            <input 
                                type="radio" 
                                name="is_all_units" 
                                value="1" 
                                {{ old('is_all_units', $agenda->is_all_units ? '1' : '0') == '1' ? 'checked' : '' }} 
                                onchange="toggleUnitList(false)"
                                class="w-4 h-4 accent-slate-950"
                            >
                            <div>
                                <span class="font-bold text-slate-950 text-xs block">Terbuka untuk Seluruh Unit Kerja (Pleno / Universal)</span>
                                <span class="text-[11px] text-slate-600 font-medium">Seluruh pegawai dari semua bagian/Pokja LLDIKTI berhak mengikuti rapat.</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 bg-slate-50 border border-slate-300 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                            <input 
                                type="radio" 
                                name="is_all_units" 
                                value="0" 
                                {{ old('is_all_units', $agenda->is_all_units ? '1' : '0') == '0' ? 'checked' : '' }} 
                                onchange="toggleUnitList(true)"
                                class="w-4 h-4 accent-slate-950"
                            >
                            <div>
                                <span class="font-bold text-slate-950 text-xs block">Pilih Unit Kerja Tertentu (Lintas Unit / Terbatas)</span>
                                <span class="text-[11px] text-slate-600 font-medium">Hanya pegawai dari Pokja/Bagian yang dipilih yang dapat melihat & melakukan presensi.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Unit Checkboxes -->
                    @php
                        $selectedUnitIds = old('unit_ids', $agenda->units->pluck('id')->toArray());
                    @endphp
                    <div id="unit-selection-wrap" class="{{ old('is_all_units', $agenda->is_all_units ? '1' : '0') == '1' ? 'hidden' : '' }} p-4 bg-slate-50 border border-slate-300 rounded-xl space-y-2">
                        <div class="text-xs font-bold text-slate-900 mb-2">Pilih Unit yang Diundang:</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach($units as $unit)
                                <label class="flex items-center gap-2.5 p-2 bg-white rounded-lg border border-slate-300 text-xs font-medium cursor-pointer hover:border-slate-900">
                                    <input 
                                        type="checkbox" 
                                        name="unit_ids[]" 
                                        value="{{ $unit->id }}" 
                                        {{ in_array($unit->id, $selectedUnitIds) ? 'checked' : '' }}
                                        class="w-4 h-4 accent-slate-950 rounded"
                                    >
                                    <span class="font-mono font-bold text-slate-950">{{ $unit->kode_unit }}</span>
                                    <span class="text-slate-700 truncate">- {{ $unit->nama_unit }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('unit_ids')
                            <p class="text-xs text-rose-700 font-bold mt-2">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="p-3.5 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-900 flex items-center gap-2">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span>Agenda ini otomatis ditugaskan untuk unit Anda: <strong class="text-slate-950">{{ $currentUser->unit?->nama_unit }}</strong>.</span>
                    </div>
                    <input type="hidden" name="is_all_units" value="0">
                    <input type="hidden" name="unit_ids[]" value="{{ $currentUser->unit_id }}">
                @endif
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
                            <option value="">Default: Pembuat Agenda ({{ $agenda->creator?->name }})</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('pimpinan_id', $agenda->pimpinan_id) == $user->id ? 'selected' : '' }}>
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
                            <option value="">Default: Pembuat Agenda ({{ $agenda->creator?->name }})</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ old('notulis_id', $agenda->notulis_id) == $user->id ? 'selected' : '' }}>
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

            <!-- 4. Berkas Surat Edaran Section -->
            <div class="space-y-4 pt-4 border-t border-slate-200">
                <h3 class="text-sm font-extrabold uppercase tracking-wider text-slate-900">4. Berkas Surat Edaran / Undangan Rapat</h3>

                @if($agenda->surat_edaran_path)
                    <div class="p-4 bg-slate-50 border border-slate-300 rounded-2xl space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0 shadow-xs">
                                    @if($agenda->is_surat_edaran_pdf)
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y1="13"/><line x1="16" y1="17" x2="8" y1="17"/></svg>
                                    @elseif($agenda->is_surat_edaran_image)
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold text-slate-950">Berkas Undangan Aktif Terlampir</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $agenda->is_surat_edaran_pdf ? 'bg-rose-100 text-rose-800' : ($agenda->is_surat_edaran_image ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800') }}">
                                            {{ $agenda->surat_edaran_extension ?? 'BERKAS' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-medium mt-0.5 truncate">
                                        Tersedia untuk dilihat langsung oleh seluruh peserta rapat.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <button 
                                    type="button" 
                                    onclick="openSuratEdaranModal('modal-surat-preview-{{ $agenda->id }}')" 
                                    class="button small bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 shadow-2xs min-h-[36px] cursor-pointer"
                                    title="Buka pratinjau surat edaran aktif"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
                                    <span>Pratinjau Berkas</span>
                                </button>
                                <a 
                                    href="{{ $agenda->surat_edaran_url }}" 
                                    download 
                                    class="button small secondary font-bold text-xs flex items-center gap-1.5 min-h-[36px]"
                                    title="Unduh berkas aktif"
                                >
                                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                                    <span>Unduh</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="field">
                    <label for="surat_edaran" class="text-xs font-bold text-slate-900 block mb-1">
                        {{ $agenda->surat_edaran_path ? 'Unggah Surat Undangan Pengganti (Kosongkan jika tidak diganti)' : 'Unggah Surat Undangan / Edaran Resmi (PDF / Gambar)' }}
                    </label>
                    <input 
                        type="file" 
                        id="surat_edaran" 
                        name="surat_edaran" 
                        accept=".pdf,image/jpeg,image/png,image/webp" 
                        class="block w-full text-xs text-slate-700 font-medium file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-950 file:text-white hover:file:bg-slate-800 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Format didukung: PDF, JPG, PNG, WebP (Maksimal 5 MB).</p>
                    @error('surat_edaran')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Live Client-side Preview Container for Newly Selected File -->
                <div id="surat-edaran-client-preview" class="hidden p-3.5 bg-slate-50 border border-slate-300 rounded-xl space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div id="preview-icon-box" class="w-12 h-12 rounded-lg bg-slate-200 border border-slate-300 flex items-center justify-center shrink-0 overflow-hidden"></div>
                            <div class="min-w-0">
                                <div id="preview-filename" class="text-xs font-bold text-slate-900 truncate">nama_dokumen.pdf</div>
                                <div class="flex items-center gap-2 text-[11px] text-slate-600 font-medium mt-0.5">
                                    <span id="preview-filesize">0 KB</span>
                                    <span>&bull;</span>
                                    <span id="preview-filetype" class="uppercase font-mono font-bold text-slate-700">PDF</span>
                                    <span class="text-amber-700 font-semibold">(Berkas Baru Terpilih)</span>
                                </div>
                            </div>
                        </div>
                        <button 
                            type="button" 
                            onclick="clearSuratEdaranInput()" 
                            class="button small secondary text-xs font-bold text-rose-700 border-rose-200 hover:bg-rose-50 flex items-center gap-1.5 shrink-0 min-h-[36px] cursor-pointer"
                            title="Batalkan pilihan berkas ini"
                        >
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            <span>Batal</span>
                        </button>
                    </div>

                    <div id="preview-image-viewport" class="hidden rounded-lg border border-slate-200 bg-white p-2 flex items-center justify-center max-h-56 overflow-hidden">
                        <img id="preview-image-img" src="" alt="Pratinjau Gambar Berkas Baru" class="max-h-52 w-auto object-contain rounded">
                    </div>
                </div>
            </div>

            <!-- Status Rapat & Action Buttons -->
            <div class="pt-4 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <label for="status" class="text-xs font-bold text-slate-900 block mb-1">Status Siklus Agenda</label>
                    <select id="status" name="status" class="input text-xs font-semibold @error('status') input-error @enderror">
                        <option value="draft" {{ old('status', $agenda->status) === 'draft' ? 'selected' : '' }}>Konsep</option>
                        <option value="scheduled" {{ old('status', $agenda->status) === 'scheduled' ? 'selected' : '' }}>Terjadwal</option>
                        <option value="ongoing" {{ old('status', $agenda->status) === 'ongoing' ? 'selected' : '' }}>Sedang Berlangsung</option>
                        <option value="completed" {{ old('status', $agenda->status) === 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status', $agenda->status) === 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button secondary text-xs font-bold">Batal</a>
                    <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Agenda Status & Quick Info (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <h3 class="font-extrabold text-sm text-white mb-3 flex items-center gap-2">
                <svg class="text-slate-300" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                <span>Statistik Agenda Rapat</span>
            </h3>
            <div class="space-y-2 text-xs text-slate-200 font-medium">
                <div>Total Presensi Tercatat: <strong class="text-white">{{ $agenda->attendances()->count() }} orang</strong></div>
                <div class="flex items-center justify-between">
                    <div>Dokumentasi Foto: <strong class="text-white">{{ $agenda->documentations()->count() }} berkas</strong></div>
                    @if($agenda->documentations()->count() > 0)
                        @php
                            $firstDoc = $agenda->documentations()->first();
                        @endphp
                        @if($firstDoc)
                            <button 
                                type="button" 
                                class="text-[11px] font-bold text-emerald-400 hover:text-emerald-300 underline cursor-pointer"
                                data-doc-modal-trigger
                                data-doc-gallery="edit-agenda-docs"
                                data-doc-url="{{ Storage::disk('public')->url($firstDoc->file_path) }}"
                                data-doc-caption="{{ $firstDoc->caption ?? basename($firstDoc->file_path) }}"
                                data-doc-agenda="{{ $agenda->judul_rapat }}"
                                data-doc-date="{{ $firstDoc->created_at ? $firstDoc->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                            >
                                Lihat Galeri
                            </button>
                        @endif
                    @endif
                </div>
                @if($agenda->documentations()->count() > 0)
                    <div class="flex gap-1.5 pt-1 overflow-x-auto pb-1">
                        @foreach($agenda->documentations()->take(4)->get() as $d)
                            <button
                                type="button"
                                class="w-10 h-10 rounded-lg overflow-hidden border border-slate-700 hover:border-emerald-400 shrink-0 cursor-pointer transition"
                                data-doc-modal-trigger
                                data-doc-gallery="edit-agenda-docs"
                                data-doc-url="{{ Storage::disk('public')->url($d->file_path) }}"
                                data-doc-caption="{{ $d->caption ?? basename($d->file_path) }}"
                                data-doc-agenda="{{ $agenda->judul_rapat }}"
                                data-doc-date="{{ $d->created_at ? $d->created_at->format('d/m/Y H:i') . ' WIB' : '' }}"
                                title="{{ $d->caption ?? 'Dokumentasi' }}"
                            >
                                <img src="{{ Storage::disk('public')->url($d->file_path) }}" alt="Dokumentasi" class="w-full h-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
                <div>Notulensi: <strong class="{{ $agenda->notulensi ? 'text-emerald-400 font-bold' : 'text-slate-400' }}">{{ $agenda->notulensi ? 'Tersedia' : 'Belum Diisi' }}</strong></div>
                <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-400">Dibuat oleh: {{ $agenda->creator?->name ?? 'Sistem' }}</div>
            </div>
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Perubahan Status Selesai</h4>
            <p class="leading-relaxed font-medium">Mengubah status menjadi <strong>Selesai</strong> akan menutup penerimaan presensi kehadiran baru dari pegawai secara otomatis.</p>
        </div>

        @can('delete', $agenda)
            @if(!$agenda->hasAttendances())
            <div class="p-4 rounded-xl bg-rose-50/50 border border-rose-200 text-xs">
                <h4 class="font-bold text-rose-950 mb-1 flex items-center gap-1.5">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-rose-600"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                    <span>Zona Bahaya</span>
                </h4>
                <p class="leading-relaxed font-medium mb-3 text-rose-800">Menghapus agenda rapat ini akan menghapus seluruh data kehadiran, foto presensi, dan lampiran dokumentasi secara permanen.</p>
                <form 
                    action="{{ route('admin.agendas.destroy', $agenda) }}" 
                    method="POST" 
                    class="block"
                    data-confirm="Apakah Anda yakin ingin menghapus agenda rapat '{{ $agenda->judul_rapat }}'? Seluruh berkas dan data kehadiran akan dihapus permanen!"
                    data-confirm-title="Hapus Agenda Rapat"
                    data-confirm-type="danger"
                    data-confirm-btn="Ya, Hapus Agenda"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full py-2 px-3 rounded-lg text-xs font-bold text-rose-700 bg-white hover:bg-rose-100 border border-rose-300 transition cursor-pointer shadow-2xs">
                        Hapus Agenda Ini
                    </button>
                </form>
            </div>
            @endif
        @endcan
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

function initAgendaEditForm() {
    const tipeRapat = document.getElementById('tipe_rapat');
    if (tipeRapat) {
        toggleFormatFields(tipeRapat.value);
    }

    const suratEdaranInput = document.getElementById('surat_edaran');
    if (suratEdaranInput && !suratEdaranInput.dataset.previewBound) {
        suratEdaranInput.dataset.previewBound = 'true';
        suratEdaranInput.addEventListener('change', handleSuratEdaranChange);
    }
}

if (document.readyState !== 'loading') {
    initAgendaEditForm();
} else {
    document.addEventListener('DOMContentLoaded', initAgendaEditForm);
}
window.addEventListener('page:loaded', initAgendaEditForm);
</script>

@if($agenda->surat_edaran_path)
    @include('agendas.partials.surat_edaran_preview', ['agenda' => $agenda, 'modalOnly' => true])
@endif

@include('agendas.partials.documentation_preview_modal')
@endsection
