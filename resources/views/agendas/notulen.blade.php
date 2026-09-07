@extends('layouts.app')

@section('title', 'Notulensi & Dokumentasi')
@section('heading', 'Notulensi, Kesimpulan & Foto Dokumentasi')
@section('subtitle', 'Agenda: ' . $agenda->judul_rapat)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
    <!-- Left: Notulensi & Upload Form (2 cols) -->
    <div class="lg:col-span-2 space-y-6">
        <div class="panel p-6 sm:p-8">
            <form method="POST" action="{{ route('admin.agendas.update-notulen', $agenda) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- Notulensi Jalannya Rapat (Word Ribbon Editor) -->
                <x-word-editor 
                    id="notulensi" 
                    name="notulensi" 
                    label="Notulensi / Catatan Jalannya Rapat" 
                    :value="old('notulensi', $agenda->notulensi)" 
                    placeholder="Tuliskan poin-poin pembahasan, arahan pimpinan, tanggapan peserta rapat..."
                    hint="Uraikan jalannya rapat, dinamika diskusi, dan tanggapan peserta dengan format resmi."
                    minHeight="260px"
                />

                <!-- Kesimpulan & Tindak Lanjut (Word Ribbon Editor) -->
                <x-word-editor 
                    id="kesimpulan" 
                    name="kesimpulan" 
                    label="Kesimpulan & Rencana Tindak Lanjut (RTL)" 
                    :value="old('kesimpulan', $agenda->kesimpulan)" 
                    placeholder="Poin-poin kesimpulan akhir, keputusan yang disepakati, PIC penanggung jawab, dan tenggat waktu..."
                    hint="Cantumkan kesimpulan penting, PIC, dan tenggat waktu penyelesaian."
                    minHeight="180px"
                />

                <!-- Unggah Foto Dokumentasi Baru dengan Preview Interaktif -->
                <div class="space-y-3 pt-4 border-t border-slate-200" id="photo-uploader-container">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Tambah Foto Dokumentasi Kegiatan</h3>
                            <p class="text-[11px] text-slate-600 font-medium">Unggah foto dokumentasi suasana rapat, materi paparan, atau presensi fisik.</p>
                        </div>
                        <span id="photo-counter-badge" class="hidden text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-slate-900 text-white font-mono">0 Foto Terpilih</span>
                    </div>

                    <!-- Dropzone & File Input -->
                    <div 
                        id="photo-dropzone"
                        class="border-2 border-dashed border-slate-300 hover:border-slate-400 bg-slate-50/75 rounded-xl p-5 text-center transition cursor-pointer relative group"
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
                                <span class="text-xs font-bold text-slate-900 group-hover:underline">Klik untuk memilih foto</span>
                                <span class="text-xs text-slate-500"> atau seret & jatuhkan berkas ke sini</span>
                            </div>
                            <p class="text-[11px] text-slate-500">Format: JPG, JPEG, PNG, WEBP (Maksimal 3 MB per foto, hingga 10 foto).</p>
                        </div>
                    </div>

                    @error('photos')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Preview Grid Container for newly selected files -->
                    <div id="photo-preview-grid" class="hidden grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3 pt-2">
                        <!-- Kartu pratinjau foto akan dirender secara dinamis oleh JavaScript -->
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button secondary text-xs font-bold inline-flex items-center gap-1.5">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                        <span>Kembali ke Detail Agenda</span>
                    </a>
                    <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Simpan Notulensi & Foto</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Agenda Summary & Existing Photos (1 col) -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Agenda Quick Info -->
        <div class="panel p-5 space-y-3">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Informasi Agenda</h3>
            <div class="space-y-2 text-xs font-medium text-slate-800">
                <div>
                    <span class="text-slate-600 block text-[11px] font-bold">Judul Rapat:</span>
                    <strong class="text-slate-950">{{ $agenda->judul_rapat }}</strong>
                </div>
                <div>
                    <span class="text-slate-600 block text-[11px] font-bold">Waktu:</span>
                    <span class="font-mono font-bold text-slate-950">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} - {{ $agenda->waktu_selesai->format('H:i') }} WIB</span>
                </div>
                <div>
                    <span class="text-slate-600 block text-[11px] font-bold">Format & Lokasi:</span>
                    <span class="uppercase font-mono font-bold text-slate-950">{{ $agenda->tipe_rapat }}</span> &bull; 
                    <span class="text-slate-800">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                </div>
                <div>
                    <span class="text-slate-600 block text-[11px] font-bold">Status Rapat:</span>
                    <span class="status {{ $agenda->status }}">{{ ucfirst($agenda->status) }}</span>
                </div>
            </div>
        </div>

        <!-- Existing Photos Gallery (with Delete Action) -->
        <div class="panel flex flex-col justify-between overflow-hidden">
            <div class="p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Dokumentasi Tersimpan</h3>
                    <span class="text-xs font-mono font-bold text-slate-900">{{ $documentations->total() }} Foto</span>
                </div>

                @if($documentations->count() > 0)
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($documentations as $doc)
                            <div class="relative group bg-slate-100 rounded-xl overflow-hidden border border-slate-300">
                                <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="Foto Dokumentasi" class="w-full h-24 object-cover">
                                <div class="p-1.5 bg-white text-[10px] text-slate-900 font-bold truncate border-t border-slate-200">
                                    {{ $doc->caption ?? 'Dokumentasi' }}
                                </div>
                                <form 
                                    action="{{ route('admin.agendas.delete-documentation', [$agenda, $doc]) }}" 
                                    method="POST" 
                                    class="absolute top-1.5 right-1.5 opacity-0 group-hover:opacity-100 transition"
                                    data-confirm="Apakah Anda yakin ingin menghapus berkas foto dokumentasi ini?"
                                    data-confirm-title="Hapus Foto Dokumentasi"
                                    data-confirm-type="warning"
                                    data-confirm-btn="Ya, Hapus"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-6 h-6 rounded-full bg-rose-700 text-white flex items-center justify-center shadow-md hover:bg-rose-800 cursor-pointer" title="Hapus Foto">
                                        <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-6 text-center bg-slate-50 rounded-xl border border-dashed border-slate-300 text-xs text-slate-600 font-medium">
                        Belum ada foto dokumentasi diunggah.
                    </div>
                @endif
            </div>

            @if($documentations->hasPages())
                <div class="mt-auto">
                    {{ $documentations->links('vendor.pagination.compact') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
