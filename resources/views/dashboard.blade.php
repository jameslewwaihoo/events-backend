@extends('layouts.app')

@section('content')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

<div class="container-fluid mt--7">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3>Overview of events & RSVPs</h3>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border">
                {{ now()->format('D, d M Y H:i') }}
            </span>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Total Events</div>
                            <div class="fs-3 fw-bold">{{ $stats['events_total'] ?? 0 }}</div>
                        </div>
                        <div class="fs-3">📅</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Published Events</div>
                            <div class="fs-3 fw-bold">{{ $stats['events_published'] ?? 0 }}</div>
                        </div>
                        <div class="fs-3">✅</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">Total RSVPs</div>
                            <div class="fs-3 fw-bold">{{ $stats['rsvps_total'] ?? 0 }}</div>
                        </div>
                        <div class="fs-3">🧾</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <div class="text-muted small">RSVPs Today</div>
                            <div class="fs-3 fw-bold">{{ $stats['rsvps_today'] ?? 0 }}</div>
                        </div>
                        <div class="fs-3">⚡</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Panels --}}
    <div class="row g-3">
        {{-- Upcoming / Published Events --}}
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">Published Events</div>
                    <a href="{{ url('/events') }}" class="btn btn-sm btn-outline-secondary">
                        Manage Events
                    </a>
                </div>
                <div class="card-body">
                    @if(($upcomingEvents ?? collect())->count() === 0)
                        <div class="text-muted">No published events yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Event</th>
                                        <th class="text-nowrap">Slug</th>
                                        <th class="text-nowrap">Sessions</th>
                                        <th class="text-nowrap text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($upcomingEvents as $event)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $event->title ?? ('Event #' . $event->id) }}</div>
                                                <div class="text-muted small">
                                                    Created: {{ optional($event->created_at)->format('d M Y') }}
                                                </div>
                                            </td>
                                            <td class="text-muted">{{ $event->slug ?? '-' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ ($event->sessions ?? collect())->count() }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a class="btn btn-sm btn-primary"
                                                   href="{{ url('/events/' . $event->id . '/rsvps') }}">
                                                    View RSVPs
                                                </a>
                                                <a class="btn btn-sm btn-outline-secondary"
                                                   target="_blank"
                                                   href="{{ url('/api/public/events/' . ($event->slug ?? $event->id)) }}">
                                                    Public JSON
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Latest RSVPs --}}
        <div class="col-12 col-xl-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0 d-flex align-items-center justify-content-between">
                    <div class="fw-semibold">Latest RSVPs</div>
                    <a href="{{ url('/admin/rsvps') }}" class="btn btn-sm btn-outline-secondary">
                        All RSVPs
                    </a>
                </div>
                <div class="card-body">
                    @if(($latestRsvps ?? collect())->count() === 0)
                        <div class="text-muted">No RSVPs yet.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Event</th>
                                        <th class="text-nowrap">Session</th>
                                        <th class="text-nowrap">Status</th>
                                        <th class="text-nowrap text-end">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($latestRsvps as $rsvp)
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $rsvp->name ?? '-' }}</div>
                                                <div class="text-muted small">{{ $rsvp->email ?? '' }}</div>
                                            </td>
                                            <td>
                                                <div class="fw-semibold">{{ optional($rsvp->event)->title ?? '-' }}</div>
                                                <div class="text-muted small">{{ optional($rsvp->event)->slug ?? '' }}</div>
                                            </td>
                                            <td class="text-muted">
                                                {{ optional($rsvp->session)->title ?? '—' }}
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">
                                                    {{ $rsvp->attendance_status ?? '-' }}
                                                </span>
                                            </td>
                                            <td class="text-end text-muted">
                                                {{ optional($rsvp->created_at)->format('d M, H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
