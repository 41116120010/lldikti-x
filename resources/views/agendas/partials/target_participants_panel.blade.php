@php
    $viewer = $currentUser ?? Auth::user();
    $isAllUnitsTarget = $agenda->is_all_units || $agenda->target_type === 'all_units';
    $canAddParticipants = ! $isAllUnitsTarget && $viewer && $viewer->can('manageParticipants', $agenda);
    $invitedList = $agenda->invitedUsers ?? collect();
    $alreadyInvitedIds = $invitedList->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
@endphp

<div class="panel">
    <div class="toolbar flex items-center justify-between gap-2 flex-wrap">
        <h3 class="font-bold text-slate-900 text-sm">Target Partisipan Rapat</h3>
        @if($canAddParticipants)
            <button
                type="button"
                onclick="openAddParticipantsModal()"
                class="btn btn-secondary py-1.5 px-3 text-xs font-bold inline-flex items-center gap-1.5 min-h-[44px] cursor-pointer"
                title="Tambahkan pegawai ASN susulan agar berhak hadir pada rapat ini"
            >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                <span>+ Tambah ASN</span>
            </button>
        @endif
    </div>
    <div class="p-5">
        @if($agenda->target_type === 'selected_users')
            <div class="space-y-3">
                <div class="p-3 bg-slate-100 border border-slate-300 rounded-xl text-xs text-slate-900 font-bold flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-700 shrink-0"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Khusus Pegawai ASN Tertentu</span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-white text-slate-900 border border-slate-300 shadow-2xs">
                        {{ $invitedList->count() }} Pegawai
                    </span>
                </div>
                <div class="text-[11px] text-slate-600 font-semibold">Daftar Pegawai yang Diundang:</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-48 overflow-y-auto pr-1">
                    @forelse($invitedList as $u)
                        @php $isSelf = $viewer && (int) $u->id === (int) $viewer->id; @endphp
                        <div class="p-2 border rounded-lg text-xs {{ $isSelf ? 'border-blue-300 bg-blue-50/50' : 'bg-slate-50 border-slate-200' }}">
                            <div class="font-bold text-slate-900 truncate flex items-center gap-1.5">
                                <span class="truncate">{{ $u->name }}</span>
                                @if($isSelf)
                                    <span class="text-[10px] font-bold text-blue-900 bg-blue-100 border border-blue-200 px-1.5 py-0.5 rounded shrink-0">Anda</span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-500 font-mono truncate">
                                NIP: {{ $u->nip ?? '-' }} &bull; {{ $u->unit?->kode_unit ?? 'Pusat' }}
                            </div>
                        </div>
                    @empty
                        <div class="text-xs text-slate-500 italic">Belum ada ASN yang ditugaskan.</div>
                    @endforelse
                </div>
            </div>
        @elseif($isAllUnitsTarget)
            <div class="p-3 bg-emerald-50 border border-emerald-300 rounded-xl text-xs text-emerald-950 font-bold flex items-center gap-2">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Terbuka untuk Seluruh Unit LLDIKTI</span>
            </div>
        @else
            <div class="space-y-4">
                <div class="space-y-2">
                    <div class="text-xs text-slate-600 font-semibold">Hanya unit kerja berikut:</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($agenda->units as $u)
                            <span class="px-2.5 py-1 bg-slate-100 text-slate-900 font-bold text-xs rounded-md border border-slate-300">
                                {{ $u->kode_unit }} — {{ $u->nama_unit }}
                            </span>
                        @endforeach
                    </div>
                </div>

                @if($invitedList->isNotEmpty())
                    <div class="pt-3 border-t border-slate-200 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-[11px] font-bold text-slate-700">Peserta ASN Tambahan (Susulan)</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                {{ $invitedList->count() }} Pegawai
                            </span>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($invitedList as $u)
                                @php $isSelf = $viewer && (int) $u->id === (int) $viewer->id; @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border text-xs {{ $isSelf ? 'bg-blue-50 border-blue-200 text-blue-950' : 'bg-slate-50 border-slate-200 text-slate-800' }}">
                                    <span class="font-bold">{{ $u->name }}</span>
                                    <span class="text-[10px] font-mono text-slate-500">({{ $u->unit?->kode_unit ?? 'Pusat' }})</span>
                                    @if($isSelf)
                                        <span class="text-[10px] font-bold text-blue-900 bg-blue-100 px-1 rounded">Anda</span>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

@if($canAddParticipants)
<div id="modal-add-participants" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="modal-add-participants-title">
    <div class="bg-white border border-slate-300 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden" onclick="event.stopPropagation()">
        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-xl bg-slate-900 text-white flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                </div>
                <div>
                    <h3 id="modal-add-participants-title" class="text-sm font-bold text-slate-900">Tambah Peserta ASN Susulan</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Pilih pegawai ASN tambahan agar berhak mengisi presensi rapat</p>
                </div>
            </div>
            <button type="button" onclick="closeAddParticipantsModal()" class="text-slate-400 hover:text-slate-700 p-2 rounded-lg hover:bg-slate-200 transition min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer" aria-label="Tutup modal tambah peserta">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form id="form-add-participants" action="{{ route('admin.agendas.add-participants', $agenda) }}" method="POST" class="flex flex-col flex-1 overflow-hidden m-0">
            @csrf
            <div class="p-5 space-y-3 overflow-y-auto flex-1">
                <div class="relative">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input
                        type="search"
                        id="add-asn-search-input"
                        placeholder="Cari nama, NIP, atau unit kerja ASN..."
                        class="input w-full pl-9 text-xs min-h-[44px]"
                        autocomplete="off"
                    >
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-600 font-semibold">
                    <span>Daftar Kandidat Pegawai ASN Aktif</span>
                    <span class="px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-900 font-bold">
                        <span id="add-asn-selected-count">0</span> dipilih
                    </span>
                </div>

                <div id="add-asn-list" class="space-y-1.5 max-h-64 overflow-y-auto pr-1">
                    <div id="add-asn-status" class="py-6 text-center text-xs text-slate-500">Memuat daftar pegawai ASN...</div>
                </div>

                <div id="add-asn-load-more-wrap" class="hidden pt-1 text-center">
                    <button type="button" id="add-asn-load-more-btn" onclick="loadMoreAddParticipants()" class="px-3 py-2 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-lg transition min-h-[44px] cursor-pointer">
                        Muat Pegawai Lainnya...
                    </button>
                </div>
            </div>

            <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2.5 shrink-0">
                <button type="button" onclick="closeAddParticipantsModal()" class="px-4 py-2 text-xs font-bold text-slate-700 bg-slate-200 hover:bg-slate-300 rounded-xl transition min-h-[44px] cursor-pointer">
                    Batal
                </button>
                <button type="submit" id="add-asn-submit-btn" disabled class="px-5 py-2 text-xs font-bold text-white bg-slate-900 hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed rounded-xl shadow-xs transition min-h-[44px] cursor-pointer">
                    Tambahkan Peserta
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const candidatesUrl = @json(route('admin.agendas.candidates'));
    const agendaId = @json($agenda->id);
    const alreadyInvited = new Set(@json($alreadyInvitedIds).map(String));
    const selectedIds = new Set();

    let currentQuery = '';
    let currentPage = 1;
    let hasMore = false;
    let isFetching = false;
    let searchTimer = null;
    let abortCtrl = null;
    let loadedItems = [];

    function updateSelectionUi() {
        const countEl = document.getElementById('add-asn-selected-count');
        const submitBtn = document.getElementById('add-asn-submit-btn');
        if (countEl) countEl.textContent = String(selectedIds.size);
        if (submitBtn) submitBtn.disabled = selectedIds.size === 0;
    }

    function renderList() {
        const listEl = document.getElementById('add-asn-list');
        const moreWrap = document.getElementById('add-asn-load-more-wrap');
        const moreBtn = document.getElementById('add-asn-load-more-btn');
        if (!listEl) return;

        listEl.innerHTML = '';

        if (isFetching && loadedItems.length === 0) {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'py-6 text-center text-xs text-slate-500';
            loadingDiv.textContent = 'Memuat daftar pegawai ASN...';
            listEl.appendChild(loadingDiv);
        } else if (loadedItems.length === 0) {
            const emptyDiv = document.createElement('div');
            emptyDiv.className = 'py-6 text-center text-xs text-slate-500 italic';
            emptyDiv.textContent = 'Tidak ada kandidat ASN yang cocok.';
            listEl.appendChild(emptyDiv);
        } else {
            loadedItems.forEach(u => {
                const idStr = String(u.id);
                const isExisting = alreadyInvited.has(idStr);
                const isChecked = isExisting || selectedIds.has(idStr);

                const label = document.createElement('label');
                label.className = 'flex items-center gap-3 p-2.5 border rounded-xl text-xs min-h-[44px] transition ' +
                    (isExisting
                        ? 'bg-slate-100 border-slate-200 opacity-70 cursor-not-allowed'
                        : 'bg-white border-slate-300 hover:bg-slate-50 cursor-pointer');

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'w-4 h-4 accent-slate-950 rounded shrink-0';
                checkbox.checked = isChecked;
                checkbox.disabled = isExisting;
                if (!isExisting) {
                    checkbox.name = 'user_ids[]';
                    checkbox.value = idStr;
                    checkbox.addEventListener('change', function () {
                        if (checkbox.checked) selectedIds.add(idStr);
                        else selectedIds.delete(idStr);
                        updateSelectionUi();
                    });
                }

                const meta = document.createElement('div');
                meta.className = 'min-w-0 flex-1';

                const nameRow = document.createElement('div');
                nameRow.className = 'font-bold text-slate-900 truncate';
                nameRow.textContent = u.name || '';

                const subRow = document.createElement('div');
                subRow.className = 'text-[10px] text-slate-500 font-mono truncate';
                subRow.textContent = 'NIP: ' + (u.nip || '-') + ' • ' + (u.unit_code || u.unit_name || 'Pusat');

                meta.appendChild(nameRow);
                meta.appendChild(subRow);
                label.appendChild(checkbox);
                label.appendChild(meta);

                if (isExisting) {
                    const badge = document.createElement('span');
                    badge.className = 'px-2 py-0.5 rounded bg-emerald-50 border border-emerald-200 text-emerald-800 text-[10px] font-bold shrink-0';
                    badge.textContent = 'Sudah Diundang';
                    label.appendChild(badge);
                }

                listEl.appendChild(label);
            });
        }

        if (moreWrap) moreWrap.classList.toggle('hidden', !hasMore);
        if (moreBtn) {
            moreBtn.disabled = isFetching;
            moreBtn.textContent = isFetching ? 'Memuat...' : 'Muat Pegawai Lainnya...';
        }
    }

    async function fetchCandidates(query, page, append) {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();
        isFetching = true;
        if (!append) loadedItems = [];
        renderList();

        try {
            const params = new URLSearchParams();
            params.set('agenda_id', String(agendaId));
            if (query) params.set('search', query);
            params.set('page', String(page));
            params.set('per_page', '25');

            const res = await fetch(candidatesUrl + '?' + params.toString(), {
                signal: abortCtrl.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const payload = await res.json();
            const rows = Array.isArray(payload.data) ? payload.data : [];
            if (append) {
                const seen = new Set(loadedItems.map(i => String(i.id)));
                rows.forEach(r => { if (!seen.has(String(r.id))) loadedItems.push(r); });
            } else {
                loadedItems = rows;
            }
            currentPage = payload.meta?.current_page || page;
            hasMore = Boolean(payload.meta?.has_more);
        } catch (err) {
            if (err && err.name === 'AbortError') return;
        } finally {
            isFetching = false;
            renderList();
        }
    }

    window.openAddParticipantsModal = function () {
        const modal = document.getElementById('modal-add-participants');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        if (loadedItems.length === 0) {
            void fetchCandidates(currentQuery, 1, false);
        }
        const input = document.getElementById('add-asn-search-input');
        if (input) setTimeout(() => input.focus(), 50);
    };

    window.closeAddParticipantsModal = function () {
        const modal = document.getElementById('modal-add-participants');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    window.loadMoreAddParticipants = function () {
        if (isFetching || !hasMore) return;
        void fetchCandidates(currentQuery, currentPage + 1, true);
    };

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modal-add-participants');
        const searchInput = document.getElementById('add-asn-search-input');
        const form = document.getElementById('form-add-participants');

        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) window.closeAddParticipantsModal();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const val = searchInput.value.trim();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    currentQuery = val;
                    void fetchCandidates(currentQuery, 1, false);
                }, 250);
            });
        }

        if (form) {
            form.addEventListener('submit', function () {
                // Ensure selected IDs from earlier filtered searches are also submitted
                form.querySelectorAll('input[data-hidden-participant="1"]').forEach(el => el.remove());
                const visibleChecked = new Set(
                    Array.from(form.querySelectorAll('input[name="user_ids[]"]:checked')).map(el => String(el.value))
                );
                selectedIds.forEach(idStr => {
                    if (!visibleChecked.has(idStr)) {
                        const hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'user_ids[]';
                        hidden.value = idStr;
                        hidden.setAttribute('data-hidden-participant', '1');
                        form.appendChild(hidden);
                    }
                });
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                window.closeAddParticipantsModal();
            }
        });
    });
})();
</script>
@endif
