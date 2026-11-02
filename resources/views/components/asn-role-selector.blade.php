@props([
    'name',
    'label',
    'roleTitle' => '',
    'helpText' => '',
    'selectedId' => null,
    'defaultUser',
    'users',
    'invitedUserIds' => [],
])

@php
    $instanceId = 'role_picker_' . $name . '_' . uniqid();
    $currentVal = (string) ($selectedId ?? '');
    $selectedUser = $currentVal !== '' ? $users->firstWhere('id', (int) $currentVal) : null;
    $isCustom = $selectedUser !== null && (int) $selectedUser->id !== (int) $defaultUser->id;
    $normalizedInvitedIds = is_array($invitedUserIds) 
        ? $invitedUserIds 
        : ($invitedUserIds instanceof \Illuminate\Support\Collection ? $invitedUserIds->toArray() : []);
@endphp

<div class="field asn-role-selector-field" id="{{ $instanceId }}-root" data-instance="{{ $instanceId }}">
    <div class="flex items-center justify-between mb-1.5">
        <label class="text-xs font-bold text-slate-900 block">
            {{ $label }}
            @if($roleTitle)
                <span class="text-[11px] font-normal text-slate-600">{{ $roleTitle }}</span>
            @endif
        </label>
        <span id="{{ $instanceId }}-badge" class="px-2 py-0.5 rounded text-[10px] font-bold {{ $isCustom ? 'bg-amber-100 text-amber-900 border border-amber-300' : 'bg-slate-100 text-slate-700 border border-slate-300' }}">
            {{ $isCustom ? 'Penugasan Khusus' : 'Default (Pembuat)' }}
        </span>
    </div>

    <!-- Hidden Form Input -->
    <input type="hidden" name="{{ $name }}" id="{{ $name }}" value="{{ $isCustom && $selectedUser ? $selectedUser->id : '' }}">

    <!-- Trigger Preview Card -->
    <div class="p-3 bg-white border border-slate-300 rounded-xl flex items-center justify-between gap-3 shadow-2xs hover:border-slate-400 transition min-h-[56px]">
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <div id="{{ $instanceId }}-avatar" class="w-9 h-9 rounded-lg {{ $isCustom ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700 border border-slate-200' }} flex items-center justify-center shrink-0 font-bold text-xs uppercase">
                {{ $selectedUser ? strtoupper(substr($selectedUser->name, 0, 2)) : strtoupper(substr($defaultUser->name, 0, 2)) }}
            </div>
            <div class="min-w-0 flex-1">
                <div id="{{ $instanceId }}-display-name" class="font-bold text-xs text-slate-950 truncate">
                    {{ $selectedUser ? $selectedUser->name : $defaultUser->name }}
                </div>
                <div id="{{ $instanceId }}-display-meta" class="text-[11px] text-slate-600 font-mono truncate">
                    @if($selectedUser && $isCustom)
                        NIP: {{ $selectedUser->nip ?? '-' }} &bull; {{ $selectedUser->unit?->kode_unit ?? 'Pusat' }}
                    @else
                        Default: Pembuat Agenda &bull; {{ $defaultUser->unit?->kode_unit ?? 'Penyelenggara' }}
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            <button 
                type="button" 
                id="{{ $instanceId }}-btn-reset" 
                onclick="window['{{ $instanceId }}'].resetDefault()"
                class="{{ $isCustom ? '' : 'hidden' }} px-2.5 py-1.5 rounded-lg text-xs font-semibold text-rose-700 hover:bg-rose-50 border border-rose-200 transition min-h-[38px] cursor-pointer"
                title="Gunakan pembuat agenda default"
            >
                Reset Default
            </button>
            <button 
                type="button" 
                id="{{ $instanceId }}-btn-open"
                onclick="window['{{ $instanceId }}'].openModal()"
                class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-300 transition min-h-[38px] cursor-pointer flex items-center gap-1.5 shadow-2xs"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-600"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <span id="{{ $instanceId }}-btn-label">{{ $isCustom ? 'Ganti ASN' : 'Pilih ASN' }}</span>
            </button>
        </div>
    </div>

    @if($helpText)
        <p class="text-[11px] text-slate-600 font-medium mt-1">{{ $helpText }}</p>
    @endif
    @error($name)
        <p class="text-xs text-rose-700 font-bold mt-1">{{ $message }}</p>
    @enderror

    <!-- Modal Popover Picker -->
    <div id="{{ $instanceId }}-modal" class="fixed inset-0 z-[60] hidden flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs transition-opacity duration-200" role="dialog" aria-modal="true">
        <div class="bg-white rounded-2xl border border-slate-300 shadow-2xl w-full max-w-lg max-h-[85vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
            <!-- Modal Header -->
            <div class="p-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-slate-900 text-white flex items-center justify-center text-xs font-bold">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-sm text-slate-950">Penugasan {{ $label }}</h4>
                        <p class="text-[11px] text-slate-600 font-medium">Pilih pegawai untuk bertugas pada agenda ini</p>
                    </div>
                </div>
                <button type="button" onclick="window['{{ $instanceId }}'].closeModal()" class="p-2 rounded-lg text-slate-400 hover:text-slate-800 hover:bg-slate-200 transition cursor-pointer min-h-[44px] min-w-[44px] flex items-center justify-center">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <!-- Search & Filter Tabs -->
            <div class="p-3.5 border-b border-slate-200 space-y-2.5 bg-white">
                <!-- Quick Search Input -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>
                    <input 
                        type="text" 
                        id="{{ $instanceId }}-search" 
                        placeholder="Cari berdasarkan nama, NIP, atau unit kerja..." 
                        class="input pl-9 text-xs w-full bg-slate-50 border-slate-300 focus:bg-white"
                        oninput="window['{{ $instanceId }}'].filterList()"
                    >
                </div>

                <!-- Scope Filter Tabs -->
                <div class="flex items-center gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-bold">
                    <button 
                        type="button" 
                        id="{{ $instanceId }}-tab-invited"
                        onclick="window['{{ $instanceId }}'].setTab('invited')"
                        class="flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 bg-white text-slate-950 shadow-2xs"
                    >
                        <span>Peserta Terpilih</span>
                        <span id="{{ $instanceId }}-invited-count" class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-800">
                            0
                        </span>
                    </button>
                    <button 
                        type="button" 
                        id="{{ $instanceId }}-tab-all"
                        onclick="window['{{ $instanceId }}'].setTab('all')"
                        class="flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900"
                    >
                        <span>Semua Pegawai</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-slate-200 text-slate-700">
                            {{ $users->count() }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- List of ASN Options -->
            <div id="{{ $instanceId }}-list" class="flex-1 overflow-y-auto p-3 space-y-1.5 max-h-[50vh]">
                <!-- Default Creator Item -->
                <div 
                    class="p-2.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 transition cursor-pointer flex items-center justify-between gap-2 min-h-[44px]"
                    onclick="window['{{ $instanceId }}'].selectUser('', '{{ addslashes($defaultUser->name) }}', '{{ $defaultUser->nip ?? '-' }}', '{{ $defaultUser->unit?->kode_unit ?? 'Penyelenggara' }}', true)"
                >
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-slate-200 text-slate-800 font-bold flex items-center justify-center text-xs shrink-0">
                            {{ strtoupper(substr($defaultUser->name, 0, 2)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="font-bold text-xs text-slate-900 truncate">
                                Default: {{ $defaultUser->name }}
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono truncate">
                                Pembuat Agenda &bull; {{ $defaultUser->unit?->kode_unit ?? 'Penyelenggara' }}
                            </div>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold bg-white text-slate-700 border border-slate-300 shrink-0">
                        Pilih Default
                    </span>
                </div>

                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400 px-1 pt-1">
                    Daftar Pegawai ASN:
                </div>

                @foreach($users as $u)
                    <div 
                        class="{{ $instanceId }}-item p-2.5 rounded-xl border border-slate-200 hover:border-slate-400 bg-white hover:bg-slate-50 transition cursor-pointer flex items-center justify-between gap-2 min-h-[44px]"
                        data-id="{{ $u->id }}"
                        data-name="{{ strtolower($u->name) }}"
                        data-nip="{{ $u->nip ?? '' }}"
                        data-unit="{{ strtolower($u->unit?->nama_unit ?? '') }} {{ strtolower($u->unit?->kode_unit ?? '') }}"
                        onclick="window['{{ $instanceId }}'].selectUser('{{ $u->id }}', '{{ addslashes($u->name) }}', '{{ $u->nip ?? '-' }}', '{{ $u->unit?->kode_unit ?? 'Pusat' }}', false)"
                    >
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-900 border border-slate-200 font-bold flex items-center justify-center text-xs shrink-0">
                                {{ strtoupper(substr($u->name, 0, 2)) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-slate-900 truncate flex items-center gap-1.5">
                                    <span>{{ $u->name }}</span>
                                    <span class="{{ $instanceId }}-invited-tag hidden px-1.5 py-0.2 rounded text-[9px] font-extrabold bg-blue-50 text-blue-900 border border-blue-200">
                                        Peserta
                                    </span>
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono truncate">
                                    NIP: {{ $u->nip ?? '-' }} &bull; {{ $u->unit?->nama_unit ?? 'Pusat' }} ({{ $u->unit?->kode_unit ?? 'Umum' }})
                                </div>
                            </div>
                        </div>
                        <button type="button" class="px-2.5 py-1 rounded-md text-[11px] font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 border border-slate-300 shrink-0">
                            Pilih
                        </button>
                    </div>
                @endforeach

                <!-- Empty State -->
                <div id="{{ $instanceId }}-empty" class="hidden text-center py-6 text-xs text-slate-500 font-medium">
                    Tidak ditemukan ASN yang cocok dengan kriteria pencarian / tab aktif.
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                <span>Tips: Gunakan pencarian cepat atau tab di atas.</span>
                <button type="button" onclick="window['{{ $instanceId }}'].closeModal()" class="px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-200 transition">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const instanceId = '{{ $instanceId }}';
    const defaultUser = {
        id: '{{ $defaultUser->id }}',
        name: '{{ addslashes($defaultUser->name) }}',
        nip: '{{ $defaultUser->nip ?? '-' }}',
        unit: '{{ $defaultUser->unit?->kode_unit ?? 'Penyelenggara' }}'
    };

    let activeTab = 'invited';
    let invitedUserIds = new Set(@json(array_map('strval', $normalizedInvitedIds)));

    const controller = {
        openModal() {
            const modal = document.getElementById(instanceId + '-modal');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    const search = document.getElementById(instanceId + '-search');
                    if (search) search.focus();
                }, 50);
            }
            this.refreshInvitedFromForm();
            this.filterList();
        },

        closeModal() {
            const modal = document.getElementById(instanceId + '-modal');
            if (modal) modal.classList.add('hidden');
        },

        setTab(tab) {
            activeTab = tab;
            const tabInvited = document.getElementById(instanceId + '-tab-invited');
            const tabAll = document.getElementById(instanceId + '-tab-all');

            if (tab === 'invited') {
                tabInvited.className = 'flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 bg-white text-slate-950 shadow-2xs font-bold';
                tabAll.className = 'flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 font-bold';
            } else {
                tabAll.className = 'flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 bg-white text-slate-950 shadow-2xs font-bold';
                tabInvited.className = 'flex-1 py-1.5 px-3 rounded-lg text-center transition cursor-pointer min-h-[36px] flex items-center justify-center gap-1.5 text-slate-600 hover:text-slate-900 font-bold';
            }

            this.filterList();
        },

        refreshInvitedFromForm() {
            // Read currently checked ASN checkboxes from Section 2
            const checkedBoxes = document.querySelectorAll('.asn-checkbox:checked');
            if (checkedBoxes.length > 0) {
                invitedUserIds = new Set(Array.from(checkedBoxes).map(cb => String(cb.value)));
            } else if (invitedUserIds.size === 0) {
                // If in all_units or no ASN checked yet, default to all
                const targetRadio = document.querySelector('input[name="target_type"]:checked');
                if (targetRadio && targetRadio.value !== 'selected_users') {
                    activeTab = 'all';
                }
            }

            // Update badge count
            const countBadge = document.getElementById(instanceId + '-invited-count');
            if (countBadge) {
                countBadge.textContent = invitedUserIds.size;
            }

            // Update invited tags in item list
            const items = document.querySelectorAll('.' + instanceId + '-item');
            items.forEach(item => {
                const id = String(item.getAttribute('data-id'));
                const tag = item.querySelector('.' + instanceId + '-invited-tag');
                if (tag) {
                    if (invitedUserIds.has(id)) {
                        tag.classList.remove('hidden');
                    } else {
                        tag.classList.add('hidden');
                    }
                }
            });
        },

        filterList() {
            const searchInput = document.getElementById(instanceId + '-search');
            const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const items = document.querySelectorAll('.' + instanceId + '-item');
            const emptyEl = document.getElementById(instanceId + '-empty');
            let visibleCount = 0;

            items.forEach(item => {
                const id = String(item.getAttribute('data-id'));
                const name = item.getAttribute('data-name') || '';
                const nip = item.getAttribute('data-nip') || '';
                const unit = item.getAttribute('data-unit') || '';

                const matchesQuery = !q || name.includes(q) || nip.includes(q) || unit.includes(q);
                const matchesTab = (activeTab === 'all') || (activeTab === 'invited' && invitedUserIds.has(id));

                if (matchesQuery && matchesTab) {
                    item.classList.remove('hidden');
                    visibleCount++;
                } else {
                    item.classList.add('hidden');
                }
            });

            if (emptyEl) {
                if (visibleCount === 0) {
                    emptyEl.classList.remove('hidden');
                } else {
                    emptyEl.classList.add('hidden');
                }
            }
        },

        selectUser(id, name, nip, unit, isDefault) {
            const input = document.getElementById('{{ $name }}');
            const badge = document.getElementById(instanceId + '-badge');
            const avatar = document.getElementById(instanceId + '-avatar');
            const displayName = document.getElementById(instanceId + '-display-name');
            const displayMeta = document.getElementById(instanceId + '-display-meta');
            const btnReset = document.getElementById(instanceId + '-btn-reset');
            const btnLabel = document.getElementById(instanceId + '-btn-label');

            if (isDefault || !id) {
                if (input) input.value = '';
                if (badge) {
                    badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300';
                    badge.textContent = 'Default (Pembuat)';
                }
                if (avatar) {
                    avatar.className = 'w-9 h-9 rounded-lg bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center shrink-0 font-bold text-xs uppercase';
                    avatar.textContent = defaultUser.name.substring(0, 2).toUpperCase();
                }
                if (displayName) displayName.textContent = defaultUser.name;
                if (displayMeta) displayMeta.textContent = 'Default: Pembuat Agenda \u2022 ' + defaultUser.unit;
                if (btnReset) btnReset.classList.add('hidden');
                if (btnLabel) btnLabel.textContent = 'Pilih ASN';
            } else {
                if (input) input.value = id;
                if (badge) {
                    badge.className = 'px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300';
                    badge.textContent = 'Penugasan Khusus';
                }
                if (avatar) {
                    avatar.className = 'w-9 h-9 rounded-lg bg-slate-900 text-white flex items-center justify-center shrink-0 font-bold text-xs uppercase';
                    avatar.textContent = name.substring(0, 2).toUpperCase();
                }
                if (displayName) displayName.textContent = name;
                if (displayMeta) displayMeta.textContent = 'NIP: ' + nip + ' \u2022 ' + unit;
                if (btnReset) btnReset.classList.remove('hidden');
                if (btnLabel) btnLabel.textContent = 'Ganti ASN';
            }

            this.closeModal();
        },

        resetDefault() {
            this.selectUser('', defaultUser.name, defaultUser.nip, defaultUser.unit, true);
        },

        syncWithTargetType(targetType) {
            if (targetType === 'selected_users') {
                this.refreshInvitedFromForm();
                this.setTab('invited');
            } else {
                this.setTab('all');
            }
        }
    };

    window['{{ $instanceId }}'] = controller;

    // Listen to Escape key to close modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            controller.closeModal();
        }
    });

    // Close on outside click
    const modalEl = document.getElementById(instanceId + '-modal');
    if (modalEl) {
        modalEl.addEventListener('click', function(e) {
            if (e.target === this) controller.closeModal();
        });
    }

    // Initialize counts on page load
    setTimeout(() => {
        controller.refreshInvitedFromForm();
    }, 100);
})();
</script>
