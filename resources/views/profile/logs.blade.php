@extends('layouts.app')

@section('title', 'Log Aktivitas Akun')
@section('heading', 'Log Aktivitas Saya')
@section('subtitle', 'Rekaman riwayat tindakan dan jejak audit keamanan akun Anda pada sistem SIPERAPAT')

@section('content')
<div class="space-y-5">
    <!-- Header Card & User Summary -->
    <div class="panel p-5 bg-white border border-slate-300 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <div class="avatar bg-slate-950 text-white font-bold text-sm uppercase shrink-0 shadow-xs">
                {{ substr($user->name, 0, 2) }}
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="font-extrabold text-slate-950 text-base leading-tight">{{ $user->name }}</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-900 border border-slate-300">
                        @if($user->isAdministrator())
                            Administrator
                        @elseif($user->isAdmin())
                            Admin Unit: {{ $user->unit?->kode_unit ?? '-' }}
                        @else
                            Pegawai: {{ $user->unit?->kode_unit ?? '-' }}
                        @endif
                    </span>
                </div>
                <div class="text-xs text-slate-600 font-medium mt-0.5">
                    <span>NIP: <strong class="font-mono text-slate-900">{{ $user->nip ?? '-' }}</strong></span>
                    <span class="mx-1.5 text-slate-400">&bull;</span>
                    <span>Username: <strong class="font-mono text-slate-900">{{ '@' . $user->username }}</strong></span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 self-start sm:self-auto">
            @if($user->isAdministrator())
                <a href="{{ route('admin.logs.index') }}" class="button small secondary flex items-center gap-1.5 text-xs font-bold whitespace-nowrap">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    <span>Master Log Audit Sistem</span>
                </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="button small secondary flex items-center gap-1.5 text-xs font-bold whitespace-nowrap">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <span>Pengaturan Profil</span>
            </a>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="bg-white p-4 rounded-xl border border-slate-300 shadow-xs">
        <form method="GET" action="{{ route('profile.logs') }}" class="flex flex-col md:flex-row md:items-center gap-3">
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[200px]">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari deskripsi atau alamat IP..." 
                    class="input w-full pl-9 text-xs font-medium"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Activity Type Filter -->
            <select name="type" onchange="this.form.submit()" class="input text-xs md:w-52 font-semibold">
                <option value="">Semua Jenis Aktivitas</option>
                @foreach($activityTypes as $type)
                    <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </select>

            <!-- Date Range Filters -->
            <div class="flex items-center gap-2">
                <input 
                    type="date" 
                    name="start_date" 
                    value="{{ request('start_date') }}" 
                    class="input text-xs w-36 font-mono" 
                    title="Tanggal Mulai"
                >
                <span class="text-xs text-slate-400 font-bold">&ndash;</span>
                <input 
                    type="date" 
                    name="end_date" 
                    value="{{ request('end_date') }}" 
                    class="input text-xs w-36 font-mono" 
                    title="Tanggal Selesai"
                >
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs font-bold">Filter</button>
                @if(request()->hasAny(['search', 'type', 'start_date', 'end_date']))
                    <a href="{{ route('profile.logs') }}" class="button small secondary text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-12 text-center whitespace-nowrap">No</th>
                        <th class="whitespace-nowrap min-w-[140px]">Waktu Aktivitas</th>
                        <th class="whitespace-nowrap min-w-[140px]">Jenis Tindakan</th>
                        <th class="min-w-[280px]">Deskripsi Riwayat</th>
                        <th class="whitespace-nowrap min-w-[160px]">Alamat IP & Perangkat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold whitespace-nowrap">
                                {{ $logs->firstItem() + $index }}
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-bold text-xs text-slate-950 font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }} WIB</div>
                                <div class="text-[11px] text-slate-600 font-medium mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="whitespace-nowrap">
                                @php
                                    $badgeClass = match(true) {
                                        str_contains($log->activity_type, 'CREATE') => 'bg-slate-100 text-slate-900 border-slate-300',
                                        str_contains($log->activity_type, 'UPDATE') || str_contains($log->activity_type, 'TOGGLE') => 'bg-amber-50 text-amber-900 border-amber-300',
                                        str_contains($log->activity_type, 'DELETE') => 'bg-rose-50 text-rose-900 border-rose-300',
                                        str_contains($log->activity_type, 'LOGIN') || str_contains($log->activity_type, 'ATTENDANCE') => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                                        str_contains($log->activity_type, 'EXPORT') => 'bg-slate-900 text-white border-slate-900',
                                        default => 'bg-slate-100 text-slate-900 border-slate-300'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold border {{ $badgeClass }}">
                                    {{ $log->activity_type }}
                                </span>
                            </td>
                            <td class="text-xs text-slate-800 leading-relaxed max-w-md">
                                <div class="font-semibold text-slate-950">{{ $log->description }}</div>
                                @if($log->properties && count($log->properties) > 0)
                                    <div class="mt-1">
                                        <button 
                                            type="button" 
                                            onclick="viewPersonalLogPayload({{ $log->id }})" 
                                            class="inline-flex items-center gap-1 text-[11px] text-slate-900 hover:text-slate-950 font-bold underline cursor-pointer"
                                        >
                                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                            <span>Lihat Rincian Data Perubahan</span>
                                        </button>
                                        <div id="personal-payload-data-{{ $log->id }}" class="hidden">
                                            <div class="space-y-2 text-left">
                                                <div class="text-xs font-mono font-bold text-slate-900 uppercase">Aktivitas: {{ $log->activity_type }}</div>
                                                <div class="text-xs text-slate-800 font-medium mb-2">{{ $log->description }}</div>
                                                <pre class="bg-slate-950 text-slate-100 p-3 rounded-lg text-[11px] font-mono overflow-x-auto max-h-60 leading-relaxed">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap">
                                <div class="font-mono text-xs text-slate-950 font-bold">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                <div class="text-[10px] text-slate-600 truncate max-w-[200px] font-medium mt-0.5" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-search py-10">
                                <div class="space-y-2 text-center">
                                    <p class="text-slate-700 font-bold text-xs">Tidak ada riwayat aktivitas akun yang sesuai dengan filter.</p>
                                    @if(request()->hasAny(['search', 'type', 'start_date', 'end_date']))
                                        <a href="{{ route('profile.logs') }}" class="button small secondary inline-flex text-xs font-bold">
                                            Reset Filter Pencarian
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            {{ $logs->links() }}
        @endif
    </div>
</div>

<script>
function viewPersonalLogPayload(logId) {
    const payloadEl = document.getElementById('personal-payload-data-' + logId);
    if (!payloadEl) return;

    window.showModal({
        title: 'Detail Rincian Aktivitas',
        message: payloadEl.innerHTML,
        type: 'info',
        confirmText: 'Tutup'
    });
}
</script>
@endsection
