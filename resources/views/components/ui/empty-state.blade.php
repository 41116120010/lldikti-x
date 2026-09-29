@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => null,
])

@php
    /*
     * Single empty-state treatment.
     *
     * Three different patterns existed: a CSS class (.empty-search) in some
     * tables, an inline Tailwind card in others, and a bare unbordered block on the
     * attendance portal — so the same "nothing here" moment looked different on
     * every page.
     */
    $paths = [
        'inbox' => 'M22 12h-6l-2 3h-4l-2-3H2',
        'calendar' => 'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35',
        'folder' => 'M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'col-span-full flex flex-col items-center justify-center gap-2 py-12 px-6 text-center bg-white rounded-2xl border border-slate-300']) }}>
    <svg class="text-slate-300" viewBox="0 0 24 24" width="44" height="44" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="{{ $paths[$icon] ?? $paths['inbox'] }}"/>
    </svg>
    <p class="text-sm font-bold text-slate-800 m-0">{{ $title }}</p>
    @if ($description)
        <p class="text-xs text-slate-500 m-0 max-w-md">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
