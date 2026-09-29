@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
])

@php
    /*
     * One button, one look.
     *
     * The codebase previously carried four separate button conventions: the
     * .button CSS class, a long Tailwind string repeated nine times inside
     * agendas/show.blade.php, a third one-off combination, and `btn btn-secondary`
     * — classes that do not exist in app.css at all, which rendered the "Edit
     * Notulensi" button on the agenda page as an unstyled, transparent link.
     *
     * Every variant is at least 44px tall (AGENTS.md §4.2).
     */
    $sizes = [
        'sm' => 'min-h-11 px-3 py-2 text-xs',
        'md' => 'min-h-11 px-4 py-2.5 text-xs',
        'lg' => 'min-h-12 px-6 py-3 text-sm',
    ];

    $variants = [
        'primary' => 'bg-slate-900 text-white hover:bg-slate-800 border border-slate-900',
        'secondary' => 'bg-white text-slate-900 hover:bg-slate-50 border border-slate-300',
        'danger' => 'bg-rose-600 text-white hover:bg-rose-700 border border-rose-600',
        'success' => 'bg-emerald-600 text-white hover:bg-emerald-700 border border-emerald-600',
        'ghost' => 'bg-transparent text-slate-700 hover:bg-slate-100 border border-transparent',
        // For use on the dark slate hero blocks.
        'onDark' => 'bg-white/10 text-white border border-white/20 hover:bg-white/20',
    ];

    $classes = trim(sprintf(
        'inline-flex items-center justify-center gap-1.5 font-bold rounded-lg transition cursor-pointer select-none disabled:opacity-50 disabled:cursor-not-allowed %s %s',
        $sizes[$size] ?? $sizes['md'],
        $variants[$variant] ?? $variants['primary'],
    ));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
