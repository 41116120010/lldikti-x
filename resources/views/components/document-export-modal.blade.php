@props([
    'agenda',
    'id' => 'modal-export-config-' . $agenda->id,
])

@php
    $config = $agenda->resolved_report_config;
@endphp

<!-- Document Export Configuration Modal Dialog -->
<div 
    id="{{ $id }}" 
    class="fixed inset-0 z-50 hidden !m-0 m-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6 overflow-y-auto transition-opacity"
    tabindex="-1"
    role="dialog"
    aria-modal="true"
    aria-labelledby="{{ $id }}-title"
>
    <div class="bg-white border border-slate-300 rounded-2xl max-w-3xl w-full shadow-2xl overflow-hidden my-auto max-h-[calc(100dvh-2rem)] flex flex-col animate-in fade-in zoom-in-95 duration-150">
        <!-- 1. Sticky Modal Header -->
        <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                </div>
                <div>
                    <h3 id="{{ $id }}-title" class="text-sm font-bold text-slate-900">Konfigurasi Dokumen Laporan</h3>
                    <p class="text-[11px] text-slate-500 font-medium truncate max-w-md">{{ $agenda->judul_rapat }}</p>
                </div>
            </div>
            <button type="button" onclick="closeExportModal('{{ $id }}')" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition cursor-pointer" aria-label="Tutup modal">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <!-- Form for Generating Configured Export -->
        <form id="{{ $id }}-form" action="{{ route('admin.reports.export.pdf', $agenda) }}" method="POST" enctype="multipart/form-data" target="_blank" class="flex flex-col flex-1 overflow-hidden m-0">
            @csrf

            <!-- Navigation Tabs Bar -->
            <div class="px-6 border-b border-slate-200 bg-white flex items-center gap-4 shrink-0 overflow-x-auto text-xs font-semibold text-slate-600">
                <button type="button" onclick="switchExportTab('{{ $id }}', 'tab-header')" id="{{ $id }}-btn-tab-header" class="export-tab-btn py-3 border-b-2 border-slate-900 text-slate-900 flex items-center gap-1.5 cursor-pointer">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/></svg>
                    <span>1. Header &amp; Kop Surat</span>
                </button>
                <button type="button" onclick="switchExportTab('{{ $id }}', 'tab-content')" id="{{ $id }}-btn-tab-content" class="export-tab-btn py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 cursor-pointer">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/></svg>
                    <span>2. Konten &amp; Kehadiran</span>
                </button>
                <button type="button" onclick="switchExportTab('{{ $id }}', 'tab-footer')" id="{{ $id }}-btn-tab-footer" class="export-tab-btn py-3 border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-1.5 cursor-pointer">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>3. Footer &amp; Pengesahan</span>
                </button>
            </div>

            <!-- 2. Scrollable Body -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">

                {{-- ====== TAB 1: HEADER & KOP ====== --}}
                <div id="{{ $id }}-pane-tab-header" class="export-tab-pane space-y-4">
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
                                    <input type="checkbox" name="show_document_number" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_document_number'] ?? true)>
                                    <span>Tampilkan</span>
                                </label>
                            </div>
                            <input type="text" name="document_number" value="{{ old('document_number', $config['document_number'] ?? ('BA-RAPAT/' . date('Y') . '/' . str_pad($agenda->id, 4, '0', STR_PAD_LEFT))) }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3 font-mono">
                        </div>
                    </div>
                </div>

                {{-- ====== TAB 2: KONTEN & KEHADIRAN ====== --}}
                <div id="{{ $id }}-pane-tab-content" class="export-tab-pane hidden space-y-4">
                    <!-- Toggle Meeting Info -->
                    <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <div>
                            <div class="font-bold text-slate-900">Tampilkan Informasi Pelaksanaan Rapat</div>
                            <div class="text-[11px] text-slate-500">Tabel ringkasan perihal, tanggal, waktu, format/tempat, dan penyelenggara</div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
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
                                <input type="checkbox" name="show_attendees" value="1" class="sr-only peer" @checked($config['show_attendees'] ?? true)>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 pt-2 border-t border-slate-100 text-xs">
                            <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="show_nip" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_nip'] ?? true)>
                                <span class="font-medium text-slate-800">Kolom NIP</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="show_unit" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_unit'] ?? true)>
                                <span class="font-medium text-slate-800">Kolom Unit Kerja</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                <input type="checkbox" name="show_attendance_time" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_attendance_time'] ?? true)>
                                <span class="font-medium text-slate-800">Kolom Waktu</span>
                            </label>
                            <label class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
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
                                <input type="checkbox" name="show_documentation" value="1" class="sr-only peer" @checked($config['show_documentation'] ?? true)>
                                <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-slate-900"></div>
                            </label>
                        </div>
                    </div>
                </div>

                {{-- ====== TAB 3: FOOTER & PENGESAHAN ====== --}}
                <div id="{{ $id }}-pane-tab-footer" class="export-tab-pane hidden space-y-4">
                    @if($agenda->pimpinan || $agenda->notulis)
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-2.5 text-xs text-blue-900">
                            <svg class="w-5 h-5 text-blue-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <span class="font-bold">Sinkronisasi Peran Aktif:</span>
                                Nama dan NIP penandatangan otomatis disinkronkan dengan pimpinan rapat dan notulis yang ditugaskan. Nilai di bawah dapat Anda sesuaikan sebelum mengunduh.
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
                                <input type="checkbox" name="show_footer_note" value="1" class="rounded border-slate-300 text-slate-900" @checked($config['show_footer_note'] ?? true)>
                                <span>Tampilkan</span>
                            </label>
                        </div>
                        <input type="text" name="footer_note" value="{{ old('footer_note', $config['footer_note'] ?? 'Dokumen ini diterbitkan secara resmi melalui Sistem Informasi Presensi Rapat (SIPERAPAT) LLDIKTI Wilayah X') }}" class="w-full text-xs rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900 py-2.5 px-3">
                    </div>
                </div>

            </div>

            <!-- 3. Pinned Modal Footer with Actions -->
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2">
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                        <input type="checkbox" name="save_as_default" value="1" class="rounded border-slate-300 text-slate-900 focus:ring-slate-900" checked>
                        <span>Simpan setelan untuk agenda ini</span>
                    </label>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 w-full sm:w-auto">
                    <button type="button" onclick="closeExportModal('{{ $id }}')" class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 rounded-xl hover:bg-slate-200 transition cursor-pointer">
                        Batal
                    </button>

                    <!-- Export Word Button -->
                    <button 
                        type="submit" 
                        formaction="{{ route('admin.reports.export.word', $agenda) }}" 
                        onclick="this.form.target='_self';" 
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-700 hover:bg-blue-800 transition shadow-xs cursor-pointer"
                        title="Unduh berkas dokumen format Microsoft Word (.doc)"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><polyline points="8 13 12 17 16 13"/><line x1="12" y1="9" x2="12" y2="17"/></svg>
                        <span>Unduh Word (.doc)</span>
                    </button>

                    <!-- Direct Binary PDF Download Button -->
                    <button 
                        type="submit" 
                        formaction="{{ route('admin.reports.export.pdf', $agenda) }}" 
                        onclick="this.form.target='_self';" 
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-xs cursor-pointer"
                        title="Unduh langsung berkas PDF resmi"
                    >
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <span>Unduh PDF (.pdf)</span>
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

<script>
    function openExportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeExportModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }

    function switchExportTab(modalId, tabName) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        // Reset all tabs
        modal.querySelectorAll('.export-tab-pane').forEach(el => el.classList.add('hidden'));
        modal.querySelectorAll('.export-tab-btn').forEach(el => {
            el.classList.remove('border-slate-900', 'text-slate-900');
            el.classList.add('border-transparent', 'text-slate-500');
        });

        // Activate selected
        const targetPane = document.getElementById(modalId + '-pane-' + tabName);
        const targetBtn = document.getElementById(modalId + '-btn-' + tabName);

        if (targetPane) targetPane.classList.remove('hidden');
        if (targetBtn) {
            targetBtn.classList.remove('border-transparent', 'text-slate-500');
            targetBtn.classList.add('border-slate-900', 'text-slate-900');
        }
    }

    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="modal-export-config-"]').forEach(m => {
                if (!m.classList.contains('hidden')) {
                    m.classList.add('hidden');
                    document.body.style.overflow = '';
                }
            });
        }
    });
</script>
