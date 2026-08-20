@extends('layouts.app')
@section('title','Meeting Report')
@section('heading','Meeting Report')
@section('subtitle','Pilih rapat untuk melihat notulen lengkap')
@section('content')
<style>
.report-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px}
.report-card{display:flex;flex-direction:column;text-decoration:none;color:inherit;background:#fff;border:1px solid var(--color-line);border-radius:14px;padding:0;box-shadow:0 1px 2px #1725540a;transition:.15s;overflow:hidden}
.report-card:hover{border-color:#b8cdf2;box-shadow:0 10px 24px #1725541a;transform:translateY(-2px)}
.report-card-header{background:linear-gradient(110deg,#243fa1,#076fa9);padding:16px 20px;display:flex;justify-content:space-between;align-items:center}
.report-card-tag{background:#ffffff26;border:1px solid #ffffff40;color:#fff;border-radius:6px;padding:5px 10px;font-size:12px;font-weight:700}
.report-card-doc{font:700 10.5px 'IBM Plex Mono',monospace;color:#dbe6ff;letter-spacing:.03em}
.report-card-body{padding:18px 20px;flex:1}
.report-card-title{font-size:15.5px;font-weight:700;color:var(--color-ink);margin:0 0 8px;line-height:1.35}
.report-card-date{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--color-muted);margin:0 0 16px}
.report-card-date svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:1.8;flex:0 0 auto}
.report-card-attendance{display:flex;align-items:center;gap:10px}
.report-card-bar{flex:1;height:6px;border-radius:10px;background:#e7edf5;overflow:hidden}
.report-card-bar div{height:100%;background:#08a758;border-radius:10px}
.report-card-percent{font:700 12px 'DM Sans',sans-serif;color:#08a758;flex:0 0 auto}
.report-card-foot{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--color-line);padding:13px 20px;background:#fafcff}
.report-card-count{font-size:12px;color:#405474}
.report-card-count b{color:var(--color-ink)}
.report-card-view{display:inline-flex;align-items:center;gap:5px;color:var(--color-blue);font-size:12.5px;font-weight:700}
.report-card-view svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;transition:transform .15s}
.report-card:hover .report-card-view svg{transform:translateX(3px)}
.report-empty{text-align:center;padding:70px 20px;color:var(--color-muted)}
.report-empty svg{width:40px;height:40px;stroke:var(--color-muted);fill:none;stroke-width:1.5;margin-bottom:12px}
</style>
@if(count($reports) === 0)
    <div class="report-empty">
        <svg viewBox="0 0 24 24"><path d="M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
        <div>Belum ada rapat yang selesai dan punya laporan.</div>
    </div>
@else
    <div class="report-cards">
        @foreach($reports as $r)
            <a class="report-card" href="{{ route('admin.notulen.show', ['index' => $r['index']]) }}">
                <div class="report-card-header">
                    <span class="report-card-tag">{{ $r['unit'] }}</span>
                    <span class="report-card-doc">{{ $r['docNo'] }}</span>
                </div>
                <div class="report-card-body">
                    <h3 class="report-card-title">{{ $r['title'] }}</h3>
                    <p class="report-card-date">
                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                        {{ \Carbon\Carbon::parse($r['date'])->translatedFormat('d M Y') }}
                    </p>
                    @if($r['percent'] !== null)
                        <div class="report-card-attendance">
                            <div class="report-card-bar"><div style="width:{{ $r['percent'] }}%"></div></div>
                            <span class="report-card-percent">{{ $r['percent'] }}%</span>
                        </div>
                    @endif
                </div>
                <div class="report-card-foot">
                    <span class="report-card-count">Attendance <b>{{ $r['present'] ?? '--' }}/{{ $r['capacity'] ?? '--' }}</b></span>
                    <span class="report-card-view">View<svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection