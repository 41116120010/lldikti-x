@props([
    'variant' => 'signature',
    'label' => 'Dilindungi Privasi',
    'size' => 'md',
])

@if($variant === 'avatar')
    @php
        $dimClass = $size === 'sm' ? 'w-[26px] h-[26px] rounded' : 'w-8 h-8 rounded-full';
        $iconSize = $size === 'sm' ? 12 : 14;
    @endphp
    <span
        class="inline-flex items-center justify-center {{ $dimClass }} bg-slate-100 border border-slate-200 text-slate-500 shrink-0 mx-auto"
        title="Foto dilindungi privasi"
        aria-label="Foto dilindungi privasi"
    >
        <svg viewBox="0 0 24 24" width="{{ $iconSize }}" height="{{ $iconSize }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
    </span>
@elseif($variant === 'pill')
    <span
        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-[10px] font-semibold text-slate-500 whitespace-nowrap"
        title="{{ $label }}"
        aria-label="{{ $label }}"
    >
        <svg viewBox="0 0 24 24" width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
        <span>{{ $label }}</span>
    </span>
@else
    <div
        class="inline-flex items-center justify-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-[11px] font-semibold text-slate-500"
        title="{{ $label }}"
        aria-label="{{ $label }}"
    >
        <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0" aria-hidden="true">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2" />
            <path d="M7 11V7a5 5 0 0 1 10 0v4" />
        </svg>
        <span>{{ $label }}</span>
    </div>
@endif
