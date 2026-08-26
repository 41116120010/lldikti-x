@extends('layouts.app')

@section('title', 'Meeting Management')
@section('heading', 'Meeting Management')
@section('subtitle', 'Schedule, monitor, and close all meetings')

@section('content')
    <style>
        .meeting-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }
        .meeting-actions button,
        .meeting-report,
        .meeting-minutes {
            height: 36px;
            padding: 0 12px;
            border-radius: 7px;
            font: 13px Arial, sans-serif;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        .meeting-start {
            border: 1px solid #2453d9;
            background: #2453d9;
            color: #fff;
            font-weight: 700;
        }
        .meeting-start:hover {
            background: #193fbd;
        }
        .meeting-details,
        .meeting-report,
        .meeting-live,
        .meeting-minutes {
            border: 1px solid #d8e2ef;
            background: #fff;
            color: #345273;
        }
        .meeting-details:hover,
        .meeting-report:hover,
        .meeting-live:hover,
        .meeting-minutes:hover {
            border-color: #abc7f8;
            background: #f3f7ff;
            color: #174fce;
        }
        .meeting-end {
            border: 1px solid #fecaca;
            background: #fff;
            color: #dc2626;
        }
        .meeting-end:hover {
            background: #fff1f2;
        }
        .in-progress {
            color: #e85c00;
            font-size: 11px;
        }
    </style>

    <div class="stats meeting-stats">
        <div class="stat-card meeting-stat">
            <div>
                <div class="sidebar-label" style="padding:0">TOTAL</div>
                <div class="stat-value">{{ count($meetings) }}</div>
            </div>
        </div>
        <div class="stat-card meeting-stat">
            <div>
                <div class="sidebar-label" style="padding:0">UPCOMING</div>
                <div class="stat-value" style="color:#2453d9">
                    {{ collect($meetings)->where('status', 'Upcoming')->count() }}
                </div>
            </div>
        </div>
        <div class="stat-card meeting-stat">
            <div>
                <div class="sidebar-label" style="padding:0">ONGOING</div>
                <div class="stat-value" style="color:#cf4e00">
                    {{ collect($meetings)->where('status', 'Ongoing')->count() }}
                </div>
            </div>
        </div>
        <div class="stat-card meeting-stat">
            <div>
                <div class="sidebar-label" style="padding:0">COMPLETED</div>
                <div class="stat-value" style="color:#0b9e57">
                    {{ collect($meetings)->where('status', 'Completed')->count() }}
                </div>
            </div>
        </div>
    </div>

    <section class="panel">
        <div class="filterbar">
            <div class="tabs">
                <button type="button" class="tab active" data-tab-status="All">
                    All {{ count($meetings) }}
                </button>
                <button type="button" class="tab" data-tab-status="Upcoming">
                    Upcoming {{ collect($meetings)->where('status', 'Upcoming')->count() }}
                </button>
                <button type="button" class="tab" data-tab-status="Ongoing">
                    Ongoing {{ collect($meetings)->where('status', 'Ongoing')->count() }}
                </button>
                <button type="button" class="tab" data-tab-status="Completed">
                    Completed {{ collect($meetings)->where('status', 'Completed')->count() }}
                </button>
            </div>
            <div>
                <input class="input" data-table-search data-table="#meetings-table" placeholder="Search meetings...">
                <a class="button small" href="{{ route('admin.notulen.create') }}">+ New</a>
            </div>
        </div>

        <div class="table-wrap">
            <table id="meetings-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Meeting</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Unit</th>
                        <th>Participants</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($meetings as $index => $m)
                        <tr data-meeting-row data-status="{{ $m['status'] }}">
                            <td class="tiny">{{ sprintf('%02d', $index + 1) }}</td>
                            <td>
                                <div class="meet-title">{{ $m['title'] }}</div>
                                @if ($m['status'] === 'Ongoing')
                                    <small class="in-progress">In progress</small>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $m['date'] }}</strong><br>
                                <span class="tiny">{{ $m['time'] }}</span>
                            </td>
                            <td>{{ $m['location'] }}</td>
                            <td><span class="tag">{{ $m['unit'] }}</span></td>
                            <td>{{ $m['present'] ?? '--' }}/{{ $m['capacity'] ?? '--' }}</td>
                            <td>
                                <span class="status {{ strtolower($m['status']) }}">{{ $m['status'] }}</span>
                            </td>
                            <td>
                                <div class="meeting-actions">
                                    @if ($m['status'] === 'Completed')
                                        <a class="meeting-report" href="{{ route('admin.notulen.show', ['index' => $index]) }}">
                                            View Report
                                        </a>
                                        <a class="meeting-minutes" href="{{ route('admin.notulen.editor') }}">
                                            Edit Minutes
                                        </a>
                                    @elseif ($m['status'] === 'Ongoing')
                                        <button class="meeting-live" type="button" data-toast="Live attendance opened.">
                                            Live
                                        </button>
                                        <a class="meeting-minutes" href="{{ route('admin.notulen.editor') }}">
                                            Minutes
                                        </a>
                                        <button class="meeting-end" type="button" data-end-meeting>
                                            End
                                        </button>
                                    @else
                                        <button class="meeting-start" type="button" data-start-meeting>
                                            Start
                                        </button>
                                        <button class="meeting-details" type="button" data-toast="Meeting details opened.">
                                            Details
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="empty-search hidden-row" id="meetings-table-empty">
            No meetings match your search.
        </div>
    </section>
@endsection