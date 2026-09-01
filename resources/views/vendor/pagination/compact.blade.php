@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman Kartu" class="w-full flex items-center justify-between gap-2 p-3 bg-slate-50 border-t border-slate-300 rounded-b-2xl text-xs">
        <!-- Compact Page Indicator -->
        <div class="text-slate-700 font-medium truncate">
            <span class="font-bold text-slate-950">Hal. {{ $paginator->currentPage() }}</span>
            <span>/</span>
            <span class="font-bold text-slate-950">{{ $paginator->lastPage() }}</span>
            <span class="text-slate-500 text-[11px] hidden sm:inline">({{ $paginator->total() }} total)</span>
        </div>

        <!-- Compact Navigation Controls -->
        <div class="flex items-center gap-1.5 shrink-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-300 bg-white cursor-not-allowed select-none">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-900 bg-white hover:bg-slate-100 hover:text-slate-950 transition shadow-2xs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Selanjutnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-900 bg-white hover:bg-slate-100 hover:text-slate-950 transition shadow-2xs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Selanjutnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-300 bg-white cursor-not-allowed select-none">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
