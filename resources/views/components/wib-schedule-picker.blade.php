@props([
    'waktuMulai' => null,
    'waktuSelesai' => null,
])

@php
    use Carbon\Carbon;

    // Normalisasi Waktu Mulai (dari old() atau prop)
    $valMulai = old('waktu_mulai');
    if ($valMulai === null) {
        if ($waktuMulai instanceof Carbon) {
            $valMulai = $waktuMulai->format('Y-m-d\TH:i');
        } elseif (is_string($waktuMulai) && filled($waktuMulai)) {
            $valMulai = date('Y-m-d\TH:i', strtotime($waktuMulai));
        } else {
            $valMulai = now()->addHour()->format('Y-m-d\TH:i');
        }
    }

    $partsMulai = explode('T', str_replace(' ', 'T', (string) $valMulai));
    $tglMulai = $partsMulai[0] ?? now()->format('Y-m-d');
    $jamMulai = isset($partsMulai[1]) ? substr($partsMulai[1], 0, 5) : now()->addHour()->format('H:i');

    // Normalisasi Waktu Selesai (dari old() atau prop)
    $valSelesai = old('waktu_selesai');
    $hasOldSelesai = session()->hasOldInput('waktu_selesai');
    if (!$hasOldSelesai) {
        if ($waktuSelesai instanceof Carbon) {
            $valSelesai = $waktuSelesai->format('Y-m-d\TH:i');
        } elseif (is_string($waktuSelesai) && filled($waktuSelesai)) {
            $valSelesai = date('Y-m-d\TH:i', strtotime($waktuSelesai));
        } elseif ($waktuSelesai === null && $waktuMulai !== null) {
            // Selesai null saat edit berarti 'Hingga Selesai'
            $valSelesai = null;
        } else {
            $valSelesai = now()->addHours(3)->format('Y-m-d\TH:i');
        }
    }

    $isSampaiSelesai = ($hasOldSelesai && empty($valSelesai)) || (!$hasOldSelesai && empty($waktuSelesai) && $waktuMulai !== null);

    $partsSelesai = $valSelesai ? explode('T', str_replace(' ', 'T', (string) $valSelesai)) : [];
    $tglSelesai = $partsSelesai[0] ?? $tglMulai;
    $jamSelesai = isset($partsSelesai[1]) ? substr($partsSelesai[1], 0, 5) : '12:00';
@endphp

<div class="space-y-4" id="wib-schedule-picker-root">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <!-- Kolom Waktu Mulai -->
        <div class="p-4 rounded-xl border border-slate-300 bg-slate-50/70 space-y-3 shadow-xs">
            <div>
                <label class="text-xs font-bold text-slate-950 flex items-center gap-1.5">
                    <span>Waktu Mulai Rapat</span>
                    <span class="text-rose-600 font-black">*</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                <div class="sm:col-span-3">
                    <label for="input_tanggal_mulai" class="text-[11px] font-bold text-slate-700 block mb-1">Tanggal Mulai</label>
                    <input 
                        type="date" 
                        id="input_tanggal_mulai" 
                        value="{{ $tglMulai }}"
                        class="input w-full font-semibold text-xs text-slate-900 bg-white" 
                        required
                    >
                </div>
                <div class="sm:col-span-2">
                    <label for="input_jam_mulai" class="text-[11px] font-bold text-slate-700 block mb-1">Pukul (WIB)</label>
                    <div class="relative flex items-center">
                        <input 
                            type="text" 
                            id="input_jam_mulai" 
                            value="{{ $jamMulai }}"
                            placeholder="09:00" 
                            maxlength="5" 
                            inputmode="numeric"
                            class="input w-full font-mono font-bold text-xs text-slate-900 pr-12 bg-white @error('waktu_mulai') input-error @enderror" 
                            required
                        >
                        <span class="absolute right-2.5 text-[11px] font-black text-slate-600 select-none pointer-events-none">
                            WIB
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Waktu Selesai -->
        <div class="p-4 rounded-xl border border-slate-300 bg-slate-50/70 space-y-3 shadow-xs">
            <div>
                <label class="text-xs font-bold text-slate-950 flex items-center gap-1.5">
                    <span>Waktu Selesai Rapat</span>
                </label>
            </div>

            <div id="wrap_waktu_selesai" class="grid grid-cols-1 sm:grid-cols-5 gap-2.5 {{ $isSampaiSelesai ? 'hidden' : '' }}">
                <div class="sm:col-span-3">
                    <label for="input_tanggal_selesai" class="text-[11px] font-bold text-slate-700 block mb-1">Tanggal Selesai</label>
                    <input 
                        type="date" 
                        id="input_tanggal_selesai" 
                        value="{{ $tglSelesai }}"
                        class="input w-full font-semibold text-xs text-slate-900 bg-white"
                        {{ $isSampaiSelesai ? 'disabled' : '' }}
                    >
                </div>
                <div class="sm:col-span-2">
                    <label for="input_jam_selesai" class="text-[11px] font-bold text-slate-700 block mb-1">Pukul (WIB)</label>
                    <div class="relative flex items-center">
                        <input 
                            type="text" 
                            id="input_jam_selesai" 
                            value="{{ $jamSelesai }}"
                            placeholder="12:00" 
                            maxlength="5" 
                            inputmode="numeric"
                            class="input w-full font-mono font-bold text-xs text-slate-900 pr-12 bg-white @error('waktu_selesai') input-error @enderror"
                            {{ $isSampaiSelesai ? 'disabled' : '' }}
                        >
                        <span class="absolute right-2.5 text-[11px] font-black text-slate-600 select-none pointer-events-none">
                            WIB
                        </span>
                    </div>
                </div>
            </div>

            <div id="notice_sampai_selesai" class="p-3 rounded-lg bg-slate-100 border border-slate-200 text-xs text-slate-700 font-medium {{ $isSampaiSelesai ? '' : 'hidden' }}">
                Rapat dijadwalkan berlangsung sampai selesai (tanpa jam penutupan kaku).
            </div>

            <div class="pt-0.5 flex items-center justify-end">
                <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-700 cursor-pointer select-none">
                    <input 
                        type="checkbox" 
                        id="chk_sampai_selesai" 
                        onchange="window.WibSchedule.toggleSampaiSelesai(this.checked)" 
                        class="rounded text-slate-950 accent-slate-950 w-4 h-4 cursor-pointer"
                        {{ $isSampaiSelesai ? 'checked' : '' }}
                    >
                    <span>Hingga Selesai</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Live Confirmation Preview Banner -->
    <div id="live_jadwal_preview" class="p-3.5 rounded-xl border border-blue-200 bg-blue-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-blue-950 transition">
        <div class="flex items-center gap-2.5 font-bold">
            <svg id="preview_icon" class="w-4 h-4 text-blue-700 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/>
                <polyline points="12 6 12 12 16 14"/>
            </svg>
            <span id="preview_text">Menghitung jadwal rapat...</span>
        </div>
        <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
            <span id="preview_duration_badge" class="font-mono text-[11px] font-extrabold text-blue-900 bg-blue-100 px-2.5 py-0.5 rounded-md border border-blue-300">
                WIB (UTC+7)
            </span>
        </div>
    </div>

    <!-- Hidden Native Inputs for Backend Form Serialization -->
    <input type="hidden" id="waktu_mulai" name="waktu_mulai" value="{{ $valMulai }}">
    <input type="hidden" id="waktu_selesai" name="waktu_selesai" value="{{ $isSampaiSelesai ? '' : $valSelesai }}">

    @error('waktu_mulai')
        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
    @enderror
    @error('waktu_selesai')
        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
    @enderror
</div>

<script>
window.WibSchedule = {
    initialized: false,

    init() {
        const tglMulai = document.getElementById('input_tanggal_mulai');
        const jamMulai = document.getElementById('input_jam_mulai');
        const tglSelesai = document.getElementById('input_tanggal_selesai');
        const jamSelesai = document.getElementById('input_jam_selesai');
        const chkSelesai = document.getElementById('chk_sampai_selesai');

        if (!tglMulai || !jamMulai) return;

        // Masking / formatting events for time
        this.bindTimeInput(jamMulai, () => this.sync());
        if (jamSelesai) this.bindTimeInput(jamSelesai, () => this.sync());

        // Date changes
        tglMulai.addEventListener('change', () => {
            if (tglSelesai && (!tglSelesai.value || tglSelesai.dataset.autoSynced === 'true')) {
                tglSelesai.value = tglMulai.value;
                tglSelesai.dataset.autoSynced = 'true';
            }
            this.sync();
        });

        if (tglSelesai) {
            tglSelesai.addEventListener('change', () => {
                tglSelesai.dataset.autoSynced = 'false';
                this.sync();
            });
        }

        // Form submit protection
        const form = tglMulai.closest('form');
        if (form && !form.dataset.wibBound) {
            form.dataset.wibBound = 'true';
            form.addEventListener('submit', () => {
                this.sync();
            });
        }

        this.initialized = true;
        this.sync();
    },

    bindTimeInput(input, onUpdate) {
        input.addEventListener('input', (e) => {
            let val = input.value.replace(/[^0-9:]/g, '');
            if (val.length === 2 && !val.includes(':') && e.inputType !== 'deleteContentBackward') {
                val = val + ':';
            }
            input.value = val;
            onUpdate();
        });

        input.addEventListener('blur', () => {
            let val = input.value.trim();
            if (/^\d{1,2}$/.test(val)) {
                val = val.padStart(2, '0') + ':00';
            } else if (/^\d{1,2}:\d{1}$/.test(val)) {
                val = val + '0';
            } else if (/^\d{3,4}$/.test(val)) {
                const clean = val.padStart(4, '0');
                val = clean.slice(0, 2) + ':' + clean.slice(2, 4);
            }
            
            // Validate limits
            const parts = val.split(':');
            if (parts.length === 2) {
                let hh = parseInt(parts[0], 10);
                let mm = parseInt(parts[1], 10);
                if (isNaN(hh) || hh < 0) hh = 8;
                if (hh > 23) hh = 23;
                if (isNaN(mm) || mm < 0) mm = 0;
                if (mm > 59) mm = 59;
                val = String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
            }

            input.value = val;
            onUpdate();
        });
    },

    toggleSampaiSelesai(isChecked) {
        const wrapWaktu = document.getElementById('wrap_waktu_selesai');
        const notice = document.getElementById('notice_sampai_selesai');
        const tglSelesai = document.getElementById('input_tanggal_selesai');
        const jamSelesai = document.getElementById('input_jam_selesai');

        if (isChecked) {
            if (wrapWaktu) wrapWaktu.classList.add('hidden');
            if (notice) notice.classList.remove('hidden');
            if (tglSelesai) tglSelesai.disabled = true;
            if (jamSelesai) jamSelesai.disabled = true;
        } else {
            if (wrapWaktu) wrapWaktu.classList.remove('hidden');
            if (notice) notice.classList.add('hidden');
            if (tglSelesai) tglSelesai.disabled = false;
            if (jamSelesai) jamSelesai.disabled = false;
        }
        this.sync();
    },

    formatIndonesianDate(d) {
        const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    },

    formatShortDate(d) {
        const bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
    },

    sync() {
        const tglMulai = document.getElementById('input_tanggal_mulai');
        const jamMulai = document.getElementById('input_jam_mulai');
        const tglSelesai = document.getElementById('input_tanggal_selesai');
        const jamSelesai = document.getElementById('input_jam_selesai');
        const chkSelesai = document.getElementById('chk_sampai_selesai');

        const hiddenMulai = document.getElementById('waktu_mulai');
        const hiddenSelesai = document.getElementById('waktu_selesai');

        const previewBanner = document.getElementById('live_jadwal_preview');
        const previewText = document.getElementById('preview_text');
        const previewBadge = document.getElementById('preview_duration_badge');

        if (!tglMulai || !jamMulai || !hiddenMulai) return;

        // Clean time formats
        let startH = jamMulai.value.trim();
        if (!startH.includes(':')) startH = startH.padStart(2, '0') + ':00';
        const startParts = startH.split(':');
        const cleanStartH = String(Math.min(23, parseInt(startParts[0] || '9', 10))).padStart(2, '0') + ':' + String(Math.min(59, parseInt(startParts[1] || '0', 10))).padStart(2, '0');

        const fullStart = `${tglMulai.value}T${cleanStartH}`;
        hiddenMulai.value = fullStart;

        const isSampaiSelesai = chkSelesai && chkSelesai.checked;

        if (isSampaiSelesai) {
            if (hiddenSelesai) hiddenSelesai.value = '';
            
            // Format preview
            if (tglMulai.value) {
                const [y, m, d] = tglMulai.value.split('-').map(Number);
                const startDateObj = new Date(y, m - 1, d);
                if (previewText) {
                    previewText.textContent = `${this.formatIndonesianDate(startDateObj)} • Pukul ${cleanStartH} WIB s.d. Selesai`;
                }
                if (previewBadge) {
                    previewBadge.textContent = 'Hingga Selesai';
                    previewBadge.className = 'font-mono text-[11px] font-extrabold text-slate-800 bg-slate-200 px-2.5 py-0.5 rounded-md border border-slate-300';
                }
                if (previewBanner) {
                    previewBanner.className = 'p-3.5 rounded-xl border border-blue-200 bg-blue-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-blue-950 transition';
                }
            }
            return;
        }

        let endH = jamSelesai ? jamSelesai.value.trim() : '';
        if (endH && !endH.includes(':')) endH = endH.padStart(2, '0') + ':00';
        const endParts = endH.split(':');
        const cleanEndH = String(Math.min(23, parseInt(endParts[0] || '12', 10))).padStart(2, '0') + ':' + String(Math.min(59, parseInt(endParts[1] || '0', 10))).padStart(2, '0');

        const fullEnd = tglSelesai && tglSelesai.value ? `${tglSelesai.value}T${cleanEndH}` : '';
        if (hiddenSelesai) hiddenSelesai.value = fullEnd;

        // Calculate preview & duration
        if (tglMulai.value && fullEnd) {
            const startDObj = new Date(`${tglMulai.value}T${cleanStartH}:00`);
            const endDObj = new Date(`${tglSelesai.value}T${cleanEndH}:00`);

            const diffMs = endDObj - startDObj;

            if (diffMs <= 0) {
                if (previewText) {
                    previewText.textContent = 'Perhatian: Waktu selesai harus lebih lambat dari waktu mulai!';
                }
                if (previewBadge) {
                    previewBadge.textContent = 'Waktu Tidak Valid';
                    previewBadge.className = 'font-mono text-[11px] font-extrabold text-rose-800 bg-rose-100 px-2.5 py-0.5 rounded-md border border-rose-300';
                }
                if (previewBanner) {
                    previewBanner.className = 'p-3.5 rounded-xl border border-rose-300 bg-rose-50/90 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-rose-950 transition';
                }
                return;
            }

            const totalMins = Math.floor(diffMs / 60000);
            const durHours = Math.floor(totalMins / 60);
            const durMins = totalMins % 60;
            let durationStr = durHours > 0 ? `${durHours} Jam` : '';
            if (durMins > 0) durationStr += (durationStr ? ` ${durMins} Menit` : `${durMins} Menit`);

            const isSameDay = tglMulai.value === tglSelesai.value;
            let dateText = '';
            if (isSameDay) {
                dateText = `${this.formatIndonesianDate(startDObj)} • ${cleanStartH} s.d. ${cleanEndH} WIB`;
            } else {
                dateText = `${this.formatShortDate(startDObj)} (${cleanStartH} WIB) s.d. ${this.formatShortDate(endDObj)} (${cleanEndH} WIB)`;
            }

            if (previewText) previewText.textContent = dateText;
            if (previewBadge) {
                previewBadge.textContent = `Durasi: ${durationStr}`;
                previewBadge.className = 'font-mono text-[11px] font-extrabold text-emerald-900 bg-emerald-100 px-2.5 py-0.5 rounded-md border border-emerald-300';
            }
            if (previewBanner) {
                previewBanner.className = 'p-3.5 rounded-xl border border-emerald-200 bg-emerald-50/70 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 text-xs text-emerald-950 transition';
            }
        }
    }
};

// Automatic hydration
if (document.readyState !== 'loading') {
    window.WibSchedule.init();
} else {
    document.addEventListener('DOMContentLoaded', () => window.WibSchedule.init());
}
window.addEventListener('page:loaded', () => window.WibSchedule.init());
</script>
