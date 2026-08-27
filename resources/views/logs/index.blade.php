@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem')
@section('heading', 'Audit Trail & Log Aktivitas')
@section('subtitle', 'Rekaman seluruh tindakan, mutasi data, dan autentikasi pengguna pada sistem')

@section('content')
<div class="space-y-5">
    <!-- Filters & Search Form -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-center">
            <!-- Keyword Search -->
            <div class="relative lg:col-span-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari deskripsi, IP, nama pegawai, NIP..." 
                    class="input w-full pl-9 text-xs"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Tipe Aktivitas -->
            <div>
                <select name="type" class="input w-full text-xs">
                    <option value="">Semua Tipe Aktivitas</option>
                    @foreach($activityTypes as $type)
                        <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Pegawai / Aktor -->
            <div>
                <select name="user_id" class="input w-full text-xs">
                    <option value="">Semua Aktor Pegawai</option>
                    @foreach($users as $userItem)
                        <option value="{{ $userItem->id }}" {{ request('user_id') == $userItem->id ? 'selected' : '' }}>
                            {{ $userItem->name }} ({{ $userItem->nip }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-center gap-2">
                <button type="submit" class="button small flex-1 text-xs">Terapkan Filter</button>
                @if(request()->hasAny(['search', 'type', 'user_id', 'start_date', 'end_date']))
                    <a href="{{ route('admin.logs.index') }}" class="button small secondary text-xs">Reset</a>
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
                        <th class="w-16 text-center">No</th>
                        <th>Waktu & Tanggal</th>
                        <th>Aktor Pegawai</th>
                        <th>Tipe Aktivitas</th>
                        <th>Deskripsi Aktivitas</th>
                        <th>Alamat IP & Perangkat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $index => $log)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="text-center text-xs text-slate-400 font-mono">{{ $logs->firstItem() + $index }}</td>
                            <td>
                                <div class="font-bold text-xs text-slate-800 font-mono">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                <div class="text-[11px] text-slate-400 font-sans">{{ $log->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($log->user)
                                    <div class="font-bold text-slate-900 text-xs">{{ $log->user->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">
                                        NIP: {{ $log->user->nip }} 
                                        @if($log->user->unit)
                                            &bull; {{ $log->user->unit->kode_unit }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500 italic">Sistem / Pengguna Terhapus</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeClass = match(true) {
                                        str_contains($log->activity_type, 'CREATE') => 'bg-blue-50 text-blue-700 border-blue-200',
                                        str_contains($log->activity_type, 'UPDATE') || str_contains($log->activity_type, 'TOGGLE') => 'bg-amber-50 text-amber-700 border-amber-200',
                                        str_contains($log->activity_type, 'DELETE') => 'bg-rose-50 text-rose-700 border-rose-200',
                                        str_contains($log->activity_type, 'LOGIN') => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold border {{ $badgeClass }}">
                                    {{ $log->activity_type }}
                                </span>
                            </td>
                            <td class="text-xs text-slate-700 leading-relaxed max-w-md">
                                {{ $log->description }}
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-600">{{ $log->ip_address ?? '127.0.0.1' }}</div>
                                <div class="text-[10px] text-slate-400 truncate max-w-[200px]" title="{{ $log->user_agent }}">
                                    {{ $log->user_agent }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-search">
                                Tidak ada rekaman log aktivitas yang sesuai dengan kriteria filter.
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
@endsection
