@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="w-full flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs p-4 bg-slate-50 border-t border-slate-300 rounded-b-2xl">
        <!-- Text Counter on Desktop & Mobile -->
        <div class="text-slate-700 font-medium text-center sm:text-left">
            <span>Menampilkan</span>
            <span class="font-bold text-slate-950">{{ $paginator->firstItem() }}</span>
            <span>sampai</span>
            <span class="font-bold text-slate-950">{{ $paginator->lastItem() }}</span>
            <span>dari</span>
            <span class="font-bold text-slate-950">{{ $paginator->total() }}</span>
            <span>data</span>
        </div>

        <!-- Mobile Simplified Navigation Buttons -->
        <div class="flex items-center justify-between sm:hidden gap-2">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-slate-400 bg-white border border-slate-200 rounded-lg cursor-not-allowed opacity-60">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition shadow-2xs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            <span class="text-xs text-slate-900 font-bold">
                Hal. {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-slate-900 bg-white border border-slate-300 rounded-lg hover:bg-slate-100 transition shadow-2xs">
                    <span>Selanjutnya</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span class="inline-flex items-center gap-1 px-3 py-2 text-xs font-bold text-slate-400 bg-white border border-slate-200 rounded-lg cursor-not-allowed opacity-60">
                    <span>Selanjutnya</span>
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>

        <!-- Desktop Full Pagination Links -->
        <div class="hidden sm:flex items-center gap-1">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-300 bg-white cursor-not-allowed select-none">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-900 bg-white hover:bg-slate-100 hover:text-slate-950 transition shadow-2xs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
                </a>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex items-center justify-center w-8 h-8 text-xs font-bold text-slate-600 select-none">
                        {{ $element }}
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex items-center justify-center min-w-8 h-8 px-2.5 rounded-lg bg-slate-950 border border-slate-950 text-white font-extrabold text-xs shadow-xs select-none">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex items-center justify-center min-w-8 h-8 px-2.5 rounded-lg bg-white border border-slate-300 text-slate-900 hover:bg-slate-100 hover:text-slate-950 transition text-xs font-bold shadow-2xs">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Selanjutnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-300 text-slate-900 bg-white hover:bg-slate-100 hover:text-slate-950 transition shadow-2xs">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Selanjutnya" class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-slate-200 text-slate-300 bg-white cursor-not-allowed select-none">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                </span>
            @endif
        </div>
    </nav>
@endif
