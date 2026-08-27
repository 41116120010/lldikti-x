@extends('layouts.app')

@section('title', 'Notulensi & Dokumentasi')
@section('heading', 'Notulensi, Kesimpulan & Foto Dokumentasi')
@section('subtitle', 'Agenda: ' . $agenda->judul_rapat)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Existing Photos Gallery (with Delete Action) -->
    @if($agenda->documentations->count() > 0)
        <div class="panel p-6">
            <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4">Foto Dokumentasi Saat Ini ({{ $agenda->documentations->count() }})</h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                @foreach($agenda->documentations as $doc)
                    <div class="relative group bg-slate-100 rounded-xl overflow-hidden border border-slate-200">
                        <img src="{{ Storage::disk('public')->url($doc->file_path) }}" alt="Foto Dokumentasi" class="w-full h-32 object-cover">
                        <div class="p-2 bg-white text-[11px] text-slate-600 truncate border-t border-slate-100">
                            {{ $doc->caption ?? 'Tanpa Keterangan' }}
                        </div>
                        <form 
                            action="{{ route('admin.agendas.delete-documentation', [$agenda, $doc]) }}" 
                            method="POST" 
                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition"
                            data-confirm="Apakah Anda yakin ingin menghapus berkas foto dokumentasi ini?"
                            data-confirm-title="Hapus Foto Dokumentasi"
                            data-confirm-type="warning"
                            data-confirm-btn="Ya, Hapus"
                        >
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-7 h-7 rounded-full bg-rose-600 text-white flex items-center justify-center shadow-md hover:bg-rose-700" title="Hapus Foto">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Notulensi & Upload Form -->
    <div class="panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.agendas.update-notulen', $agenda) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Notulensi Jalannya Rapat -->
            <div class="field">
                <label for="notulensi">Notulensi / Catatan Jalannya Rapat</label>
                <textarea 
                    id="notulensi" 
                    name="notulensi" 
                    rows="8" 
                    class="input w-full p-3 font-sans text-xs leading-relaxed @error('notulensi') input-error @enderror" 
                    placeholder="Tuliskan poin-poin pembahasan, arahan pimpinan, tanggapan peserta rapat..."
                >{{ old('notulensi', $agenda->notulensi) }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Uraikan jalannya rapat, isu strategis, dan masukan dari para peserta.</p>
                @error('notulensi')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Kesimpulan & Tindak Lanjut -->
            <div class="field">
                <label for="kesimpulan">Kesimpulan & Rencana Tindak Lanjut (RTL)</label>
                <textarea 
                    id="kesimpulan" 
                    name="kesimpulan" 
                    rows="5" 
                    class="input w-full p-3 font-sans text-xs leading-relaxed @error('kesimpulan') input-error @enderror" 
                    placeholder="Poin-poin kesimpulan akhir, keputusan yang disepakati, PIC penanggung jawab, dan tenggat waktu..."
                >{{ old('kesimpulan', $agenda->kesimpulan) }}</textarea>
                @error('kesimpulan')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Unggah Foto Dokumentasi Baru -->
            <div class="space-y-3 pt-4 border-t border-slate-100">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-400">Tambah Foto Dokumentasi Kegiatan</h3>

                <div class="field">
                    <label for="photos">Pilih Foto Kegiatan (Bisa pilih beberapa foto sekaligus)</label>
                    <input 
                        type="file" 
                        id="photos" 
                        name="photos[]" 
                        multiple 
                        accept="image/jpeg,image/png,image/webp" 
                        class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                    >
                    <p class="text-[11px] text-slate-400 mt-1">Format: JPG, PNG, WebP (Maksimal 3 MB per foto, hingga 10 foto).</p>
                    @error('photos')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('photos.*')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.agendas.show', $agenda) }}" class="button secondary text-xs">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Simpan Notulensi & Foto</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
