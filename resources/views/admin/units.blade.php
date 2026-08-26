@extends('layouts.app')
@section('title','Manage Units') @section('heading','Manage Units') @section('subtitle','Kelola unit dan kepala unit organisasi')
@section('content')
<div class="stats">
    <div class="stat-card"><div class="stat-icon">{{ $totalUnits }}</div><label>Total Units</label></div>
    <div class="stat-card"><div class="stat-icon purple">{{ $totalMembers }}</div><label>Total Members</label></div>
    <div class="stat-card"><div class="stat-icon green">{{ $totalUnits - $totalWithoutHead }}</div><label>Units with Head</label></div>
</div>

<section class="panel">
    <div class="toolbar">
        <input class="input" type="text" placeholder="Search units..." data-table-search data-table="#units-table">
        <button class="button small" type="button" data-add-unit>+ Add Unit</button>
    </div>
    <div class="table-wrap">
        <table id="units-table">
            <thead>
                <tr><th>Unit Name</th><th>Head of Unit</th><th>Members</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @foreach($units as $unit)
                    <tr>
                        <td><strong>{{ $unit['name'] }}</strong></td>
                        <td>@if($unit['head']){{ $unit['head'] }}@else<span class="tiny">— No head assigned —</span>@endif</td>
                        <td>{{ $unit['members'] }} {{ Str::plural('member', $unit['members']) }}</td>
                        <td>
                            <div class="row-actions">
                                <button class="action-btn action-icon action-edit" type="button" data-edit-unit aria-label="Edit {{ $unit['name'] }}" title="Edit"><svg viewBox="0 0 24 24"><path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/></svg></button>
                                <button class="action-btn action-icon action-delete" type="button" data-delete-unit aria-label="Delete {{ $unit['name'] }}" title="Delete"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v5m4-5v5M9 7l1-2h4l1 2m-9 0 1 13h10l1-13"/></svg></button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="empty-search hidden-row" id="units-table-empty">No units match your search.</div>
    </div>
    <div class="table-foot">Showing {{ count($units) }} of {{ count($units) }} units</div>
</section>

<template id="unit-head-options">
    <option value="">— No head assigned —</option>
    @foreach($admins as $admin)
        <option value="{{ $admin['name'] }}">{{ $admin['name'] }} ({{ $admin['unit'] }})</option>
    @endforeach
</template>
@endsection