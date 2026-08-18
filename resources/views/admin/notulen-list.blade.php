@extends('layouts.app')
@section('title','Meeting Report')
@section('heading','Meeting Report')
@section('subtitle','Pilih rapat untuk melihat notulen lengkap')
@section('content')
<style>
.report-cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px}
.report-card{display:block;text-decoration:none;color:inherit;background:#fff;border:1px solid var(--line);border-radius:12px;padding:20px;box-shadow:0 1px 2px #1725540a;transition:.15s}
.report-card:hover{border-color:#b8cdf2;box-shadow:0 6px 18px #1725541a;transform:translateY(-1px)}
.report-card-top{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:14px}
.report-card-doc{font:700 11px 'IBM Plex Mono',monospace;color:var(--muted)}
.report-card-title{font-size:15px;font-weight:700;color:var(--ink);margin:0 0 6px}
.report-card-meta{font-size:12px;color:var(--muted);margin:0 0 14px}
.report-card-foot{display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--line);padding-top:12px}
.report-card-attendance{font-size:12px;color:#405474}
.report-card-attendance b{color:#079b55}
.report-card-arrow{color:var(--blue);font-weight:700}
.report-empty{text-align:center;padding:60px 20px;color:var(--muted)}
</style>
@if(count($reports) === 0)
    <div class="report-empty">Belum ada rapat yang selesai dan punya laporan.</div>
@else
    <div class="report-cards">
        @foreach($reports as $r)
            <a class="report-card" href="{{ route('admin.notulen.show', ['index' => $r['index']]) }}">
                <div class="report-card-top">
                    <span class="tag">{{ $r['unit'] }}</span>
                    <span class="report-card-doc">{{ $r['docNo'] }}</span>
                </div>
                <h3 class="report-card-title">{{ $r['title'] }}</h3>
                <p class="report-card-meta">{{ $r['date'] }}</p>
                <div class="report-card-foot">
                    <span class="report-card-attendance">Attendance <b>{{ $r['present'] ?? '--' }}/{{ $r['capacity'] ?? '--' }}</b></span>
                    <span class="report-card-arrow">View →</span>
                </div>
            </a>
        @endforeach
    </div>
@endif
@endsection