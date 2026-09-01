@extends('layouts.app')

@section('title', 'Edit Pengguna')
@section('heading', 'Edit Data Pengguna')
@section('subtitle', 'Perbarui informasi aparatur: ' . $user->name)

@section('content')
@php
    $currentUser = $currentUser ?? Auth::user();
@endphp
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Form Panel (2 Cols) -->
    <div class="lg:col-span-2 panel p-6 sm:p-8">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <!-- Nama Lengkap -->
                <div class="field md:col-span-2">
                    <label for="name" class="text-xs font-bold text-slate-900 block mb-1">Nama Lengkap & Gelar <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name', $user->name) }}" 
                        class="input w-full font-medium @error('name') input-error @enderror" 
                        required 
                        autofocus
                    >
                    @error('name')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- NIP -->
                <div class="field">
                    <label for="nip" class="text-xs font-bold text-slate-900 block mb-1">Nomor Induk Pegawai (NIP) <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="nip" 
                        name="nip" 
                        value="{{ old('nip', $user->nip) }}" 
                        class="input w-full font-mono font-bold @error('nip') input-error @enderror" 
                        required
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">18 digit angka NIP resmi instansi.</p>
                    @error('nip')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Username -->
                <div class="field">
                    <label for="username" class="text-xs font-bold text-slate-900 block mb-1">Username <span class="text-rose-600">*</span></label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        value="{{ old('username', $user->username) }}" 
                        class="input w-full font-mono font-bold @error('username') input-error @enderror" 
                        required
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Alfanumerik unik, digunakan untuk login alternatif.</p>
                    @error('username')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div class="field">
                    <label for="email" class="text-xs font-bold text-slate-900 block mb-1">Alamat Email Resmi <span class="text-rose-600">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="{{ old('email', $user->email) }}" 
                        class="input w-full font-medium @error('email') input-error @enderror" 
                        required
                    >
                    @error('email')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Telepon / WhatsApp -->
                <div class="field">
                    <label for="phone" class="text-xs font-bold text-slate-900 block mb-1">Nomor Telepon / WhatsApp</label>
                    <input 
                        type="text" 
                        id="phone" 
                        name="phone" 
                        value="{{ old('phone', $user->phone) }}" 
                        class="input w-full font-mono font-medium @error('phone') input-error @enderror" 
                        placeholder="081234567890"
                    >
                    @error('phone')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Peran (Role) -->
                <div class="field">
                    <label for="role" class="text-xs font-bold text-slate-900 block mb-1">Peran Pengguna (Role) <span class="text-rose-600">*</span></label>
                    @if($currentUser->isAdministrator() && $user->id !== $currentUser->id)
                        <select id="role" name="role" class="input w-full font-semibold @error('role') input-error @enderror" required>
                            <option value="administrator" {{ old('role', $user->role) === 'administrator' ? 'selected' : '' }}>Administrator (Akses Penuh)</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin Unit (Pengelola Unit)</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff Pegawai (Presensi Saja)</option>
                        </select>
                    @elseif($currentUser->isAdministrator() && $user->id === $currentUser->id)
                        <input type="text" class="input w-full bg-slate-100 text-slate-900 font-bold" value="Administrator (Akun Anda Sendiri)" readonly disabled>
                        <input type="hidden" name="role" value="{{ $user->role }}">
                    @else
                        <select id="role" name="role" class="input w-full font-semibold @error('role') input-error @enderror" required>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin Unit (Pengelola Unit)</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff Pegawai (Presensi Saja)</option>
                        </select>
                    @endif
                    @error('role')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Unit Kerja -->
                <div class="field">
                    <label for="unit_id" class="text-xs font-bold text-slate-900 block mb-1">Unit Kerja Organisasi</label>
                    @if($currentUser->isAdministrator())
                        <select id="unit_id" name="unit_id" class="input w-full font-semibold @error('unit_id') input-error @enderror">
                            <option value="">-- Tingkat Lembaga (Tanpa Unit) --</option>
                            @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $user->unit_id) == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->kode_unit }} — {{ $unit->nama_unit }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" class="input w-full bg-slate-100 text-slate-900 font-bold" value="{{ $currentUser->unit?->nama_unit }}" readonly disabled>
                        <input type="hidden" name="unit_id" value="{{ $currentUser->unit_id }}">
                    @endif
                    @error('unit_id')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Optional -->
                <div class="field md:col-span-2">
                    <label for="password" class="text-xs font-bold text-slate-900 block mb-1">Kata Sandi Baru (Kosongkan jika tidak diubah)</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input w-full @error('password') input-error @enderror" 
                        placeholder="Minimal 8 karakter baru..."
                    >
                    <p class="text-[11px] text-slate-600 font-medium mt-1">Biarkan kosong jika tetap menggunakan kata sandi saat ini.</p>
                    @error('password')
                        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 accent-slate-950" {{ $user->id === Auth::id() ? 'disabled' : '' }}>
                    <span class="text-sm font-bold text-slate-900">Akun pengguna berstatus aktif</span>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-200">
                <a href="{{ route('admin.users.index') }}" class="button secondary text-xs font-bold">Batal</a>
                <button type="submit" class="button flex items-center gap-2 text-xs font-bold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>

    <!-- User Profile & Activity Summary Card (1 Col) -->
    <div class="space-y-4">
        <div class="panel p-6 bg-slate-950 text-white border-slate-900 shadow-sm">
            <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-800">
                <div class="w-11 h-11 rounded-xl bg-slate-800 text-white font-bold flex items-center justify-center text-sm shadow-xs">
                    {{ substr($user->name, 0, 2) }}
                </div>
                <div>
                    <h3 class="font-extrabold text-sm text-white">{{ $user->name }}</h3>
                    <span class="text-xs text-slate-300 font-mono font-bold">NIP: {{ $user->nip }}</span>
                </div>
            </div>

            <div class="space-y-2 text-xs text-slate-200 font-medium">
                <div>Unit: <strong class="text-white">{{ $user->unit?->nama_unit ?? 'Tingkat Lembaga' }}</strong></div>
                <div>Peran: <span class="uppercase font-mono text-slate-100 font-bold bg-slate-800 px-2 py-0.5 rounded">{{ $user->role }}</span></div>
                <div>Status Akun: <strong class="{{ $user->is_active ? 'text-emerald-400 font-bold' : 'text-amber-400 font-bold' }}">{{ $user->is_active ? 'Aktif' : 'Non-Aktif' }}</strong></div>
                <div>Total Hadir Rapat: <strong class="text-white">{{ $recentAttendances->total() }} kali</strong></div>
                <div class="pt-2 border-t border-slate-800 text-[11px] text-slate-400">Terdaftar sejak: {{ $user->created_at->translatedFormat('d F Y') }}</div>
            </div>
        </div>

        <!-- Paginated Attendance History for this User -->
        <div class="panel flex flex-col justify-between overflow-hidden">
            <div>
                <div class="toolbar">
                    <div class="flex items-center gap-2">
                        <svg class="text-slate-900" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        <h4 class="font-bold text-slate-900 text-xs">Riwayat Presensi ({{ $recentAttendances->total() }})</h4>
                    </div>
                </div>

                <div class="p-4 divide-y divide-slate-200 text-xs">
                    @forelse($recentAttendances as $att)
                        <div class="py-2.5 first:pt-0 last:pb-0 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-bold text-slate-950 truncate">{{ $att->agenda->judul_rapat }}</span>
                                <span class="text-[10px] font-mono text-slate-800 font-bold bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200 shrink-0">
                                    {{ $att->signed_at->format('d/m/Y') }}
                                </span>
                            </div>
                            <div class="text-[11px] text-slate-600 font-mono font-medium">
                                Waktu: {{ $att->signed_at->format('H:i') }} WIB &bull; IP: {{ $att->ip_address ?? '127.0.0.1' }}
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-xs text-slate-500 font-medium">
                            Pegawai ini belum memiliki riwayat presensi.
                        </div>
                    @endforelse
                </div>
            </div>

            @if($recentAttendances->hasPages())
                <div class="mt-auto">
                    {{ $recentAttendances->links('vendor.pagination.compact') }}
                </div>
            @endif
        </div>

        <div class="panel p-5 text-xs text-slate-800 bg-white border-slate-300">
            <h4 class="font-bold text-slate-950 mb-1">Keamanan & Audit Log</h4>
            <p class="leading-relaxed font-medium">Setiap perubahan akun pengguna akan dicatat secara otomatis ke dalam audit trail log aktivitas instansi.</p>
        </div>
    </div>
</div>
@endsection
