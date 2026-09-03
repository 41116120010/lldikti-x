@extends('layouts.app')

@php
    $currentUser = $currentUser ?? Auth::user();
@endphp
@section('title', 'Kelola Pengguna')
@section('heading', $currentUser->isAdministrator() ? 'Kelola Seluruh Pengguna' : 'Pegawai Unit: ' . ($currentUser->unit?->nama_unit ?? ''))
@section('subtitle', 'Daftar aparatur dan staf terdaftar di sistem presensi')

@section('content')
<div class="space-y-5">
    <!-- Quick Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div>
                <label>{{ $currentUser->isAdministrator() ? 'Total Pengguna' : 'Pegawai di Unit' }}</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $userStats->total ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
            <div>
                <label>Akun Aktif</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $userStats->active ?? 0 }}</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
            </div>
            <div>
                <label>Akun Non-Aktif</label>
                <div class="font-bold text-2xl text-slate-900 font-mono mt-0.5">{{ $userStats->inactive ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Action Bar & Filters -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white p-4 rounded-xl border border-slate-300 shadow-xs">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
            <!-- Search -->
            <div class="relative flex-1 min-w-[200px] max-w-xs">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ request('search') }}" 
                    placeholder="Cari nama, NIP, username..." 
                    class="input w-full pl-9 text-xs font-medium"
                >
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            </div>

            <!-- Unit Filter (Superadmin Only) -->
            @if($currentUser->isAdministrator())
                <select name="unit_id" onchange="this.form.submit()" class="input text-xs w-48 font-semibold">
                    <option value="">Semua Unit Kerja</option>
                    <option value="none" {{ request('unit_id') === 'none' ? 'selected' : '' }}>Tanpa Unit (Tingkat Lembaga)</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                            {{ $unit->kode_unit }} — {{ $unit->nama_unit }}
                        </option>
                    @endforeach
                </select>
            @endif

            <!-- Role Filter -->
            <select name="role" onchange="this.form.submit()" class="input text-xs w-36 font-semibold">
                <option value="">Semua Peran</option>
                @if($currentUser->isAdministrator())
                    <option value="administrator" {{ request('role') === 'administrator' ? 'selected' : '' }}>Administrator</option>
                @endif
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin Unit</option>
                <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>Staff Pegawai</option>
            </select>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="input text-xs w-32 font-semibold">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif Saja</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
            </select>

            <div class="flex items-center gap-2">
                <button type="submit" class="button small secondary text-xs font-bold">Filter</button>
                @if(request()->hasAny(['search', 'unit_id', 'role', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="button small secondary text-xs font-bold">Reset</a>
                @endif
            </div>
        </form>

        <a href="{{ route('admin.users.create') }}" class="button small flex items-center gap-2 text-xs self-start xl:self-auto shrink-0 font-bold">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            <span>Tambah Pengguna</span>
        </a>
    </div>

    <!-- Users Table -->
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th class="w-16 text-center">No</th>
                        <th>Pegawai</th>
                        <th>NIP & Username</th>
                        <th>Unit Kerja</th>
                        <th class="text-center">Peran (Role)</th>
                        <th class="text-center">Status</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $index => $userItem)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="text-center text-xs text-slate-900 font-mono font-bold">{{ $users->firstItem() + $index }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-900 text-white flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ substr($userItem->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $userItem->name }}</div>
                                        <div class="text-[11px] text-slate-600 font-medium">{{ $userItem->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-mono text-xs text-slate-950 font-bold">{{ $userItem->nip }}</div>
                                <div class="text-[11px] font-mono text-slate-600 font-medium">@<span>{{ $userItem->username }}</span></div>
                            </td>
                            <td class="whitespace-nowrap">
                                @if($userItem->unit)
                                    <span class="inline-flex items-center gap-1 font-bold text-xs text-slate-900 bg-slate-100 px-2 py-0.5 rounded border border-slate-300 whitespace-nowrap">
                                        {{ $userItem->unit->kode_unit }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-600 italic font-medium whitespace-nowrap">Pusat / Lembaga</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($userItem->isAdministrator())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-900 text-white">
                                        Administrator
                                    </span>
                                @elseif($userItem->isAdmin())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-900 border border-slate-300">
                                        Admin Unit
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        Staff
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($userItem->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-300">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                        Non-Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="row-actions justify-end">
                                    <!-- Toggle Status Button -->
                                    @if($userItem->id !== Auth::id())
                                        <form 
                                            action="{{ route('admin.users.toggle-status', $userItem) }}" 
                                            method="POST" 
                                            class="inline"
                                            data-confirm="Apakah Anda yakin ingin {{ $userItem->is_active ? 'menonaktifkan' : 'mengaktifkan kembali' }} akun pegawai '{{ $userItem->name }}'?"
                                            data-confirm-title="{{ $userItem->is_active ? 'Nonaktifkan Akun Pengguna' : 'Aktifkan Akun Pengguna' }}"
                                            data-confirm-type="{{ $userItem->is_active ? 'warning' : 'confirm' }}"
                                            data-confirm-btn="Ya, Lanjutkan"
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <button 
                                                type="submit" 
                                                class="action-btn text-xs font-bold {{ $userItem->is_active ? 'text-amber-800 hover:bg-amber-50' : 'text-emerald-800 hover:bg-emerald-50' }}"
                                                title="{{ $userItem->is_active ? 'Non-aktifkan Akun' : 'Aktifkan Akun' }}"
                                            >
                                                {{ $userItem->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                        </form>
                                    @endif

                                    <!-- Edit Button -->
                                    <a 
                                        href="{{ route('admin.users.edit', $userItem) }}" 
                                        class="action-btn action-icon action-edit" 
                                        title="Edit Pengguna"
                                    >
                                        <svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg>
                                    </a>

                                    <!-- Delete Button (Only for other accounts) -->
                                    @if($userItem->id !== Auth::id() && (!$userItem->isAdministrator() || $currentUser->isAdministrator()))
                                        <form 
                                            action="{{ route('admin.users.destroy', $userItem) }}" 
                                            method="POST" 
                                            class="inline"
                                            data-confirm="Apakah Anda yakin ingin menghapus akun pegawai '{{ $userItem->name }}' (NIP: {{ $userItem->nip }}) secara permanen?"
                                            data-confirm-title="Hapus Akun Pengguna"
                                            data-confirm-type="warning"
                                            data-confirm-btn="Ya, Hapus Pengguna"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="action-btn action-icon action-delete" 
                                                title="Hapus Pengguna"
                                            >
                                                <svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v5m4-5v5M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-search py-10">
                                <p class="text-slate-700 font-bold text-xs">Tidak ada data pengguna yang sesuai dengan filter pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            {{ $users->links() }}
        @endif
    </div>
</div>
@endsection
