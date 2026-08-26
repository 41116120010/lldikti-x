@extends('layouts.app')
@section('title','Meeting Report') @section('heading','Meeting Report') @section('subtitle','Evaluasi Bulanan Unit IT')
@section('content')
@php
    $percent = isset($meeting['present'], $meeting['capacity']) && $meeting['capacity'] > 0
        ? round($meeting['present'] / $meeting['capacity'] * 100)
        : 0;
    $circumference = round(2 * 3.14159 * 38);
    $offset = round($circumference * (1 - $percent / 100));
@endphp
<div class="report-actions" style="justify-content:space-between">
    <a class="auth-link" style="font-size:13px;font-weight:600" href="{{ route('admin.notulen') }}">&larr; Back to Reports</a>
    <div style="display:flex;gap:10px">
        <a class="button secondary report-export" href="{{ route('admin.notulen.editor') }}">Edit Minutes</a>
        <button class="button report-export" data-report-action type="button">Export PDF</button>
        <button class="button secondary report-export" data-report-word type="button">Export Word</button>
    </div>
</div>

<section class="report-hero">
    <span class="badge">OFFICIAL MINUTES</span><span class="badge final">Finalized</span>
    <h2>{{ $notulen['title'] ?? $meeting['title'] }}</h2>
    <p class="report-hero-meta">
        <span><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>{{ \Carbon\Carbon::parse($meeting['date'])->translatedFormat('l, d F Y') }}</span>
        <span><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>{{ $meeting['time'] }}</span>
        <span><svg viewBox="0 0 24 24"><path d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.3"/></svg>{{ $meeting['location'] }}</span>
        <span><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/></svg>Led by {{ $meeting['host'] }}</span>
    </p>
</section>

<section class="panel metadata">
    <div><div class="metadata-icon"><svg viewBox="0 0 24 24"><path d="M7 3h7l4 4v14H7z"/><path d="M14 3v4h4"/></svg></div><div><label>Doc No.</label><b>{{ $report['docNo'] }}</b></div></div>
    <div><div class="metadata-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-3.5 3-6 7-6s7 2.5 7 6"/></svg></div><div><label>Author</label><b>{{ $report['author'] }}</b></div></div>
    <div><div class="metadata-icon"><svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg></div><div><label>Approved By</label><b>{{ $report['approvedBy'] }}</b></div></div>
    <div><div class="metadata-icon"><svg viewBox="0 0 24 24"><path d="M12 3v18M5 8h6.5a2.5 2.5 0 1 1 0 5H6a2.5 2.5 0 1 0 0 5h7"/></svg></div><div><label>Version</label><b>{{ $report['version'] }}</b></div></div>
</section>

<div class="report-grid">
    <section class="panel">
        <div class="attendance-head">Attendance Summary</div>
        <div class="attendance-body">
            <div class="attendance-ring-wrap">
                <svg class="attendance-ring" width="88" height="88" viewBox="0 0 88 88">
                    <circle cx="44" cy="44" r="38" fill="none" stroke="#e7edf5" stroke-width="8"/>
                    <circle cx="44" cy="44" r="38" fill="none" stroke="#08a758" stroke-width="8" stroke-linecap="round" stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" transform="rotate(-90 44 44)"/>
                    <text x="44" y="49" text-anchor="middle" font-size="18" font-weight="700" fill="#08a758">{{ $percent }}%</text>
                </svg>
                <div class="attendance-pills">
                    <span class="attendance-pill"><i style="background:#08a758"></i>Present — {{ $meeting['present'] ?? '--' }}</span>
                    <span class="attendance-pill"><i style="background:#dbe4f1"></i>Absent — {{ isset($meeting['present'], $meeting['capacity']) ? $meeting['capacity'] - $meeting['present'] : '--' }}</span>
                    <span class="attendance-pill"><i style="background:#2453d9"></i>Capacity — {{ $meeting['capacity'] ?? '--' }}</span>
                </div>
            </div>
        </div>
        <div class="presence">
            @foreach($users as $user)
                <div class="participant"><span style="color:#08a758">OK</span><div class="user-avatar">{{ $user['avatar'] }}</div><div class="participant-info"><b>{{ $user['name'] }}</b><span>{{ $user['unit'] }}</span></div></div>
            @endforeach
        </div>
    </section>

    <section class="panel">
        <div class="notes-head">Meeting Minutes</div>
        <div class="notes agenda-timeline">
            @foreach($report['agenda'] as $item)
                <article class="agenda" data-step="{{ $loop->iteration }}">
                    <h3>{{ $item['title'] }}</h3>
                    @foreach($item['body'] as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </article>
            @endforeach
            @if($notulen)
                <article class="agenda agenda-note" data-step="{{ count($report['agenda']) + 1 }}">
                    <h3>Discussion & Agenda</h3>
                    <p>{{ nl2br(e($notulen['agenda'])) }}</p>
                </article>
                @if(!empty($notulen['conclusion']))
                    <article class="agenda agenda-note" data-step="{{ count($report['agenda']) + 2 }}">
                        <h3>Conclusion / Follow-up</h3>
                        <p>{{ nl2br(e($notulen['conclusion'])) }}</p>
                    </article>
                @endif
            @endif
        </div>
    </section>
</div>
<section class="panel attachments">
    <div class="attachments-title">Dokumentasi &amp; Lampiran<span class="attachments-meta"><span class="attachments-count">{{ count($report['attachments'] ?? []) }} files</span><button class="button secondary attachments-download-all" type="button" data-toast="Downloading all attachments...">⬇ All</button></span></div>
    <div class="attachment-grid">
        @foreach($report['attachments'] ?? [] as $file)
            <div class="file">
                <div class="file-preview file-preview-{{ $file['type'] }}"><span class="file-badge">{{ strtoupper($file['type']) }}</span>{{ $file['type'] === 'pdf' ? '📄' : '🖼' }}</div>
                <div class="file-body">
                    <b title="{{ $file['name'] }}">{{ $file['name'] }}</b>
                    <span>{{ $file['size'] }}</span>
                    <div class="file-actions">
                        <button type="button" data-file-preview="{{ $file['name'] }}">Preview</button>
                        <button type="button" class="file-download" data-toast="Downloading {{ $file['name'] }}..." aria-label="Download {{ $file['name'] }}">⬇</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <div class="attachments-foot">{{ $report['docNo'] }} · Dokumen resmi, tidak dapat diubah tanpa persetujuan pimpinan.</div>
</section>
@endsection