@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')
@section('heading', 'Audit Trail & Log Aktivitas')
@section('subtitle', 'Rekaman seluruh tindakan, mutasi data, dan autentikasi pengguna pada sistem')

@section('content')
<div class="space-y-5">
    <!-- Filters & Search Form -->
    <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-300 shadow-xs">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
            <!-- Keyword Search -->
            <div class="space-y-1 sm:col-span-2">
                <label for="search" class="text-xs font-bold text-slate-900 block">Pencarian Kata Kunci</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="search" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Cari deskripsi, IP, nama, NIP..." 
                        class="input w-full pl-9 text-xs font-medium"
                    >
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </div>
            </div>

            <!-- Tipe Aktivitas -->
            <div class="space-y-1">
                <label for="type" class="text-xs font-bold text-slate-900 block">Tipe Aktivitas</label>
                <select id="type" name="type" class="input w-full text-xs font-semibold">
                    <option value="">Semua Tipe</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ $type }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Pegawai / Aktor -->
            <div class="space-y-1">
                <label for="user_id" class="text-xs font-bold text-slate-900 block">Aktor Pegawai</label>
                <select id="user_id" name="user_id" class="input w-full text-xs font-semibold">
                    <option value="">Semua Pegawai</option>
                    @foreach($users as $userItem)
                        <option value="{{ $userItem->id }}" {{ request('user_id') == $userItem->id ? 'selected' : '' }}>
                            {{ $userItem->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Start Date -->
            <div class="space-y-1">
                <label for="start_date" class="text-xs font-bold text-slate-900 block">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ request('start_date') }}" class="input w-full text-xs font-medium">
            </div>

            <!-- End Date & Submit -->
            <div class="space-y-1">
                <label for="end_date" class="text-xs font-bold text-slate-900 block">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ request('end_date') }}" class="input w-full text-xs font-medium">
            </div>

            <!-- Filter Actions Buttons -->
            <div class="sm:col-span-2 lg:col-span-6 flex items-center justify-end gap-2 pt-2 border-t border-slate-200">
                @if(request()->hasAny(['search', 'type', 'user_id', 'start_date', 'end_date']))
                    <a href="{{ route('admin.logs.index') }}" class="button small secondary text-xs font-bold">Reset Filter</a>
                @endif
                <button type="submit" class="button small text-xs font-bold px-4">Terapkan Filter</button>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="panel">
        <div class="toolbar">
            <div class="flex items-center gap-2">
                <svg class="text-slate-900" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" x2="8" y1="13" y2="13"/><line x1="16" x2="8" y1="17" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <h3 class="font-bold text-slate-900 text-sm">Riwayat Aktivitas & Jejak Audit</h3>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-14 text-center">No</th>
                        <th>Waktu & Tanggal</th>
                        <th>Aktor Pegawai</th>
                        <th>Tipe Aktivitas</th>
                        <th>Deskripsi Tindakan</th>
                        <th>IP & Perangkat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold">{{ $logs->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-xs text-slate-950 font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }} WIB</div>
                                <div class="text-[11px] text-slate-600 font-medium">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="font-bold text-slate-950 text-xs">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-slate-600 font-mono font-medium">
                                        NIP: {{ $log->user->nip }} 
                                        @if($log->user->unit)
                                            &bull; {{ $log->user->unit->kode_unit }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-600 italic font-medium">Sistem / Pengguna Terhapus</span>
                                @endif
                            </td>
                            <td>
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
                                            onclick="viewLogPayload({{ $log->id }})" 
                                            class="inline-flex items-center gap-1 text-[11px] text-slate-900 hover:text-slate-950 font-bold underline cursor-pointer"
                                        >
                                            <svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                                            <span>Lihat Payload Perubahan</span>
                                        </button>
                                        <div id="payload-data-{{ $log->id }}" class="hidden">
                                            <div class="space-y-2 text-left">
                                                <div class="text-xs font-mono font-bold text-slate-900 uppercase">Tipe: {{ $log->activity_type }}</div>
                                                <div class="text-xs text-slate-800 font-medium mb-2">{{ $log->description }}</div>
                                                <pre class="bg-slate-950 text-slate-100 p-3 rounded-lg text-[11px] font-mono overflow-x-auto max-h-60 leading-relaxed">{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-950 font-bold">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                <div class="text-[10px] text-slate-600 truncate max-w-[180px] font-medium" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-search py-10">
                                <div class="space-y-2 text-center">
                                    <p class="text-slate-700 font-bold text-xs">Tidak ada rekaman log aktivitas yang sesuai dengan kriteria filter.</p>
                                    @if(request()->hasAny(['search', 'type', 'user_id', 'start_date', 'end_date']))
                                        <a href="{{ route('admin.logs.index') }}" class="button small secondary inline-flex text-xs font-bold">
                                            Reset Pencarian & Filter
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
function viewLogPayload(logId) {
    const payloadEl = document.getElementById('payload-data-' + logId);
    if (!payloadEl) return;

    window.showModal({
        title: 'Detail Payload Log Aktivitas',
        message: payloadEl.innerHTML,
        type: 'info',
        confirmText: 'Tutup'
    });
}
</script>
@endsection
