@props([
    'agenda',
    'detailRoute',
    'user' => null,
    'showAttendance' => true,
    'showOrganizer' => false,
])

{{--
    Shared agenda card shell.

    agendas/index, dashboard and attendances/portal each carried their own copy
    of this markup — identical down to the padding classes and the icon paths, with
    only the header badge and the footer actions differing. The drift was already
    visible: agendas/index was missing the line-clamp-2 the other two had, so a long
    meeting title produced a ragged grid.

    Pages that need a different header or footer override the `badges` / `footer`
    slots; everything between them stays in one place.
--}}
<div {{ $attributes->merge(['class' => 'bg-white rounded-2xl border border-slate-300 shadow-xs hover:border-slate-400 transition flex flex-col justify-between overflow-hidden h-full']) }}>
    <div class="flex flex-col h-full">
        <div class="p-5 pb-3 border-b border-slate-200 flex items-start justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5">
                @isset($badges)
                    {{ $badges }}
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] border {{ $agenda->status_meta_soft['class'] }}">
                        {{ $agenda->status_meta_soft['label'] }}
                    </span>

                    <span class="text-[11px] font-mono font-bold uppercase px-2 py-0.5 bg-slate-100 text-slate-800 rounded border border-slate-200">
                        {{ $agenda->tipe_rapat }}
                    </span>
                @endisset
            </div>

            @if ($showAttendance)
                <span class="inline-flex items-center gap-1 text-xs font-bold text-slate-900 bg-slate-100 px-2.5 py-1 rounded-md border border-slate-200 shrink-0" title="Jumlah Peserta Hadir">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    {{ $agenda->attendances_count }} Hadir
                </span>
            @endif
        </div>

        <div class="p-5 space-y-3 flex-1">
            <h3 class="font-bold text-slate-900 text-base leading-snug hover:text-blue-900 transition line-clamp-2">
                <a href="{{ $detailRoute }}">{{ $agenda->judul_rapat }}</a>
            </h3>

            <div class="space-y-1.5 text-xs text-slate-700 font-medium">
                <div class="flex items-center gap-2">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="text-slate-800">{{ $agenda->waktu_mulai->translatedFormat('d M Y, H:i') }} {{ $agenda->waktu_selesai ? '- ' . $agenda->waktu_selesai->format('H:i') . ' WIB' : 'WIB s.d. Selesai' }}</span>
                </div>

                <div class="flex items-center gap-2">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                    <span class="truncate text-slate-800">{{ $agenda->lokasi_ruang ?? 'Daring / Ruang Virtual' }}</span>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <svg class="text-slate-600 shrink-0" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/></svg>
                    @if ($agenda->is_all_units)
                        <span class="text-emerald-900 font-bold">Seluruh Unit LLDIKTI (Pleno)</span>
                    @else
                        <span class="text-slate-900 font-bold">{{ $agenda->units->pluck('kode_unit')->join(', ') }}</span>
                    @endif
                </div>

                @isset($bodyExtra)
                    {{ $bodyExtra }}
                @endisset
            </div>
        </div>
    </div>

    <div class="p-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2">
        @isset($footer)
            {{ $footer }}
        @else
            <a href="{{ $detailRoute }}" class="button small secondary text-xs font-bold">Lihat Detail</a>
        @endisset
    </div>
</div>
