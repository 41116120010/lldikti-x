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

                <!-- Notulensi Jalannya Rapat -->
                <div class="field">
                    <label for="notulensi" class="text-xs font-bold text-slate-900 block mb-1">Notulensi / Catatan Jalannya Rapat</label>
                    <textarea 
                        id="notulensi" 
                        name="notulensi" 
                        rows="9" 
                        class="input w-full p-3 font-sans text-xs leading-relaxed font-medium @error('notulensi') input-error @enderror" 
                        placeholder="Tuliskan poin-poin pembahasan, arahan pimpinan, tanggapan peserta rapat..."
                    >{{ old('notulensi', $agenda->notulensi) }}</textarea>
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Uraikan jalannya rapat, dinamika diskusi, dan tanggapan peserta.</p>
                    @error('notulensi')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kesimpulan & Tindak Lanjut -->
                <div class="field">
                    <label for="kesimpulan" class="text-xs font-bold text-slate-900 block mb-1">Kesimpulan & Rencana Tindak Lanjut (RTL)</label>
                    <textarea 
                        id="kesimpulan" 
                        name="kesimpulan" 
                        rows="6" 
                        class="input w-full p-3 font-sans text-xs leading-relaxed font-medium @error('kesimpulan') input-error @enderror" 
                        placeholder="Poin-poin kesimpulan akhir, keputusan yang disepakati, PIC penanggung jawab, dan tenggat waktu..."
                    >{{ old('kesimpulan', $agenda->kesimpulan) }}</textarea>
                    @error('kesimpulan')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unggah Foto Dokumentasi Baru -->
                <div class="space-y-3 pt-4 border-t border-slate-200">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Tambah Foto Dokumentasi Kegiatan</h3>

                    <div class="field">
                        <label for="photos" class="text-xs font-bold text-slate-900 block mb-1">Pilih Berkas Foto (Mendukung Multi-Upload)</label>
                        <input 
                            type="file" 
                            id="photos" 
                            name="photos[]" 
                            multiple 
                            accept="image/jpeg,image/png,image/webp" 
                            class="block w-full text-xs text-slate-700 font-medium file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-950 file:text-white hover:file:bg-slate-800 cursor-pointer"
                        >
                        <p class="text-[11px] text-slate-600 font-medium mt-1">Format: JPG, PNG, WebP (Maksimal 3 MB per foto, hingga 10 foto).</p>
                        @error('photos')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                        @error('photos.*')
                            <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                    <a href="{{ route('admin.agendas.show', $agenda) }}" class="button secondary text-xs font-bold">Kembali</a>
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
                    <span class="font-mono font-bold text-slate-950">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} WIB</span>
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
        <div class="panel p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Dokumentasi Tersimpan</h3>
                <span class="text-xs font-mono font-bold text-slate-900">{{ $agenda->documentations->count() }} Foto</span>
            </div>

            @if($agenda->documentations->count() > 0)
                <div class="grid grid-cols-2 gap-3">
                    @foreach($agenda->documentations as $doc)
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
    </div>
</div>
@endsection
