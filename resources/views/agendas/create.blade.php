@extends('layouts.app')

@section('title', 'Buat Agenda Rapat')
@section('heading', 'Buat Agenda Rapat Baru')
@section('subtitle', 'Jadwalkan pertemuan kedinasan, tentukan sasaran unit kerja, dan lampirkan surat edaran')

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
    $defaultMulai = date('Y-m-d\TH:i', strtotime('+1 hour'));
    $defaultSelesai = date('Y-m-d\TH:i', strtotime('+3 hours'));
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.agendas.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div class="space-y-5">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">1. Informasi Utama Pertemuan</h3>

                <!-- Judul Rapat -->
                <div class="field">
                    <label for="judul_rapat">Judul / Perihal Pertemuan Rapat <span class="text-rose-500">*</span></label>
                    <input 
                        type="text" 
                        id="judul_rapat" 
                        name="judul_rapat" 
                        value="{{ old('judul_rapat') }}" 
                        class="input w-full @error('judul_rapat') input-error @enderror" 
                        placeholder="Contoh: Rapat Koordinasi Evaluasi Beban Kerja Dosen (BKD)" 
                        required 
                        autofocus
                    >
                    @error('judul_rapat')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Jenis Rapat -->
                    <div class="field">
                        <label for="jenis_rapat">Jenis Pertemuan <span class="text-rose-500">*</span></label>
                        <select id="jenis_rapat" name="jenis_rapat" class="input w-full @error('jenis_rapat') input-error @enderror" required>
                            <option value="koordinasi" {{ old('jenis_rapat', 'koordinasi') === 'koordinasi' ? 'selected' : '' }}>Rapat Koordinasi</option>
                            <option value="pleno" {{ old('jenis_rapat') === 'pleno' ? 'selected' : '' }}>Rapat Pleno</option>
                            <option value="evaluasi" {{ old('jenis_rapat') === 'evaluasi' ? 'selected' : '' }}>Rapat Evaluasi & Monev</option>
                            <option value="konsinyasi" {{ old('jenis_rapat') === 'konsinyasi' ? 'selected' : '' }}>Konsinyasi / FGD</option>
                            <option value="terbatas" {{ old('jenis_rapat') === 'terbatas' ? 'selected' : '' }}>Rapat Terbatas / Pimpinan</option>
                            <option value="lainnya" {{ old('jenis_rapat') === 'lainnya' ? 'selected' : '' }}>Pertemuan Lainnya</option>
                        </select>
                        @error('jenis_rapat')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Format Penyelenggaraan -->
                    <div class="field">
                        <label for="tipe_rapat">Format Pelaksanaan <span class="text-rose-500">*</span></label>
                        <select id="tipe_rapat" name="tipe_rapat" class="input w-full @error('tipe_rapat') input-error @enderror" onchange="toggleFormatFields(this.value)" required>
                            <option value="offline" {{ old('tipe_rapat', 'offline') === 'offline' ? 'selected' : '' }}>Tatap Muka (Luring di Kantor)</option>
                            <option value="online" {{ old('tipe_rapat') === 'online' ? 'selected' : '' }}>Daring (Pertemuan Virtual)</option>
                            <option value="hybrid" {{ old('tipe_rapat') === 'hybrid' ? 'selected' : '' }}>Hibrida (Luring & Daring)</option>
                        </select>
                        @error('tipe_rapat')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Lokasi Ruang & Tautan Daring -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="field" id="wrap-lokasi">
                        <label for="lokasi_ruang">Lokasi / Nama Ruang Rapat <span id="req-lokasi" class="text-rose-500">*</span></label>
                        <input 
                            type="text" 
                            id="lokasi_ruang" 
                            name="lokasi_ruang" 
                            value="{{ old('lokasi_ruang') }}" 
                            class="input w-full @error('lokasi_ruang') input-error @enderror" 
                            placeholder="Contoh: Ruang Sidang Utama Lantai 2"
                        >
                        @error('lokasi_ruang')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field" id="wrap-link">
                        <label for="link_meeting">Tautan Daring (Zoom / GMeet) <span id="req-link" class="text-rose-500 hidden">*</span></label>
                        <input 
                            type="text" 
                            id="link_meeting" 
                            name="link_meeting" 
                            value="{{ old('link_meeting') }}" 
                            class="input w-full font-mono text-xs @error('link_meeting') input-error @enderror" 
                            placeholder="https://zoom.us/j/... atau meet.google.com/..."
                        >
                        @error('link_meeting')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Waktu Pelaksanaan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="field">
                        <label for="waktu_mulai">Waktu Mulai <span class="text-rose-500">*</span></label>
                        <input 
                            type="datetime-local" 
                            id="waktu_mulai" 
                            name="waktu_mulai" 
                            value="{{ old('waktu_mulai', $defaultMulai) }}" 
                            class="input w-full @error('waktu_mulai') input-error @enderror" 
                            required
                        >
                        @error('waktu_mulai')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field">
                        <label for="waktu_selesai">Waktu Selesai (Opsional)</label>
                        <input 
                            type="datetime-local" 
                            id="waktu_selesai" 
                            name="waktu_selesai" 
                            value="{{ old('waktu_selesai', $defaultSelesai) }}" 
                            class="input w-full @error('waktu_selesai') input-error @enderror"
                        >
                        <p class="text-[11px] text-slate-400 mt-1">Kosongkan jika rapat berlangsung hingga selesai.</p>
                        @error('waktu_selesai')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- 2. Target Peserta & Scoping Unit -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">2. Sasaran Peserta & Unit Kerja</h3>

                @if($currentUser->isAdministrator())
                    <div class="space-y-3">
                        <label class="flex items-center gap-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-blue-50/50 transition">
                            <input 
                                type="radio" 
                                name="is_all_units" 
                                value="1" 
                                {{ old('is_all_units', '1') == '1' ? 'checked' : '' }} 
                                onchange="toggleUnitList(false)"
                                class="w-4 h-4 accent-blue-600"
                            >
                            <div>
                                <span class="font-bold text-slate-900 text-xs block">Terbuka untuk Seluruh Unit Kerja (Pleno / Universal)</span>
                                <span class="text-[11px] text-slate-500">Seluruh pegawai dari semua bagian/Pokja LLDIKTI berhak mengikuti rapat.</span>
                            </div>
                        </label>

                        <label class="flex items-center gap-3 p-3.5 bg-slate-50 border border-slate-200 rounded-xl cursor-pointer hover:bg-blue-50/50 transition">
                            <input 
                                type="radio" 
                                name="is_all_units" 
                                value="0" 
                                {{ old('is_all_units') === '0' ? 'checked' : '' }} 
                                onchange="toggleUnitList(true)"
                                class="w-4 h-4 accent-blue-600"
                            >
                            <div>
                                <span class="font-bold text-slate-900 text-xs block">Terbatas untuk Unit Kerja Tertentu</span>
                                <span class="text-[11px] text-slate-500">Hanya pegawai dari unit kerja terpilih yang dapat mengisi presensi.</span>
                            </div>
                        </label>
                    </div>

                    <!-- Checklist Unit Kerja -->
                    <div id="unit-selection-wrap" class="{{ old('is_all_units', '1') === '1' ? 'hidden' : '' }} p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="text-xs font-bold text-slate-700">Pilih Unit Kerja yang Ditugaskan:</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-2">
                            @foreach($units as $unit)
                                <label class="flex items-center gap-2 p-2 bg-white border border-slate-200 rounded-lg cursor-pointer hover:bg-blue-50 text-xs">
                                    <input 
                                        type="checkbox" 
                                        name="unit_ids[]" 
                                        value="{{ $unit->id }}" 
                                        {{ in_array($unit->id, old('unit_ids', [])) ? 'checked' : '' }}
                                        class="w-4 h-4 accent-blue-600 rounded"
                                    >
                                    <span class="font-mono font-bold text-slate-900">{{ $unit->kode_unit }}</span>
                                    <span class="text-slate-600 truncate">— {{ $unit->nama_unit }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('unit_ids')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div class="p-3.5 bg-blue-50 border border-blue-200 rounded-xl text-xs text-blue-800 flex items-center gap-2">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <span>Agenda ini otomatis ditugaskan untuk unit Anda: <strong>{{ $currentUser->unit?->nama_unit }}</strong>.</span>
                    </div>
                    <input type="hidden" name="is_all_units" value="0">
                    <input type="hidden" name="unit_ids[]" value="{{ $currentUser->unit_id }}">
                @endif
            </div>

            <!-- 3. Berkas Surat Edaran -->
            <div class="space-y-4 pt-4 border-t border-slate-100">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">3. Surat Undangan / Edaran Resmi</h3>

                <div class="field">
                    <label for="surat_edaran">Unggah Dokumen Undangan Resmi (PDF / Gambar)</label>
                    <input 
                        type="file" 
                        id="surat_edaran" 
                        name="surat_edaran" 
                        accept=".pdf,image/jpeg,image/png,image/webp" 
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Format didukung: PDF, JPG, PNG, WebP (Maksimal 5 MB). Bersifat opsional sebagai lampiran resmi bagi peserta.</p>
                    @error('surat_edaran')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Rapat & Action Buttons -->
            <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <label for="status" class="text-xs font-bold text-slate-700 block mb-1">Status Publikasi Awal</label>
                    <select id="status" name="status" class="input text-xs">
                        <option value="scheduled" {{ old('status', 'scheduled') === 'scheduled' ? 'selected' : '' }}>Terjadwal (Langsung Aktif)</option>
                        <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Konsep (Simpan Sementara)</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.agendas.index') }}" class="button secondary text-xs">Batal</a>
                    <button type="submit" class="button flex items-center gap-2 text-xs">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Simpan Agenda Rapat</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Agenda Planning Information (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-900 text-white border-slate-800">
            <h3 class="font-bold text-sm text-white mb-3 flex items-center gap-2">
                <svg class="text-blue-400" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>
                <span>Alur Pelaksanaan Agenda</span>
            </h3>
            <div class="space-y-3 text-xs text-slate-300 leading-relaxed">
                <div>
                    <strong class="text-blue-400 block">1. Terjadwal:</strong>
                    Agenda diumumkan dan siap dibuka presensi saat waktu rapat dimulai.
                </div>
                <div>
                    <strong class="text-emerald-400 block">2. Berlangsung (Aktif):</strong>
                    Pegawai dapat mengisi presensi kehadiran, selfie wajah, dan tanda tangan digital.
                </div>
                <div>
                    <strong class="text-amber-400 block">3. Selesai:</strong>
                    Sesi presensi ditutup, notulen digital diunggah, dan laporan PDF/Word dapat diekspor.
                </div>
            </div>
        </div>

        <div class="panel p-5 text-xs text-slate-600 bg-white border-slate-200">
            <h4 class="font-bold text-slate-800 mb-1">Verifikasi Presensi Digital</h4>
            <p class="leading-relaxed">Setiap presensi pegawai akan diverifikasi secara otomatis dengan foto selfie WebRTC terkompresi dan tanda tangan canvas HTML5.</p>
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

document.addEventListener('DOMContentLoaded', () => {
    const tipeRapat = document.getElementById('tipe_rapat');
    if (tipeRapat) {
        toggleFormatFields(tipeRapat.value);
    }
});
</script>
@endsection
