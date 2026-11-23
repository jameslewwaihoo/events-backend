@extends('layouts.app')

@section('page-title', 'RSVPs')
@section('hide-navbar-search', 'true')

@push('css')
    <link rel="stylesheet" href="{{ asset('css/rsvp-report.css') }}">
@endpush

@section('page-header')
    <div class="header bg-gradient-primary rsvp-report-band"></div>
@endsection

@section('content')
@php
    $reportUrl = route('admin.events.rsvps.index', $event->id);
    $activeFilters = array_filter($filters, fn ($value) => $value !== null && $value !== '');
    $exportUrl = route('admin.events.rsvps.export', ['event' => $event->id] + $activeFilters);
@endphp

<main class="container-fluid rsvp-report" aria-labelledby="rsvp-event-title">
    <header class="rsvp-report__overview">
        <h1 id="rsvp-event-title">{{ $event->title }}</h1>
        <div class="rsvp-report__actions">
            @if ($event->private_rsvp)
                <a class="btn btn-white" href="{{ route('purchaser.guests.index', $event->id) }}">Manage invitations</a>
            @endif
            <a class="btn btn-white" href="{{ $exportUrl }}">Export CSV</a>
        </div>
    </header>

    @if ($event->private_rsvp)
        <dl class="rsvp-report__totals" aria-label="Event totals">
            <div>
                <dt>Confirmed seats</dt>
                <dd>{{ number_format($confirmedSeats) }}</dd>
            </div>
            <div>
                <dt>Pending invitations</dt>
                <dd>{{ number_format($pendingInvitations) }}</dd>
            </div>
        </dl>
    @endif

    <section class="rsvp-report__panel" aria-labelledby="rsvp-responses-title">
        <header class="rsvp-report__toolbar">
            <div>
                <h2 id="rsvp-responses-title">Responses</h2>
                <p>
                    @if ($rsvps->count())
                        Showing {{ $rsvps->firstItem() }}–{{ $rsvps->lastItem() }} of {{ number_format($rsvps->total()) }}
                    @else
                        {{ number_format($rsvps->total()) }} {{ Str::plural('response', $rsvps->total()) }}
                    @endif
                </p>
            </div>

            <form class="rsvp-report__filters" action="{{ $reportUrl }}" method="GET" aria-label="Filter responses">
                @if ($sessions->isNotEmpty())
                    <div class="rsvp-report__filter">
                        <label for="rsvp-session">Session</label>
                        <select id="rsvp-session" name="session_id" class="form-control">
                            <option value="">All sessions</option>
                            @foreach ($sessions as $session)
                                <option value="{{ $session->id }}" {{ $filters['session_id'] == $session->id ? 'selected' : '' }}>
                                    {{ $session->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="rsvp-report__filter">
                    <label for="rsvp-attendance">Attendance</label>
                    <select id="rsvp-attendance" name="attendance_status" class="form-control">
                        <option value="">All statuses</option>
                        <option value="attending" {{ $filters['attendance_status'] === 'attending' ? 'selected' : '' }}>Attending</option>
                        <option value="declined" {{ $filters['attendance_status'] === 'declined' ? 'selected' : '' }}>Declined</option>
                    </select>
                </div>
                <div class="rsvp-report__filter-actions">
                    <button class="btn btn-primary" type="submit">Apply filters</button>
                    @if ($activeFilters)
                        <a class="btn btn-link px-0" href="{{ $reportUrl }}">Clear filters</a>
                    @endif
                </div>
            </form>
        </header>

        @if ($rsvps->isNotEmpty())
            <table class="rsvp-report__table" role="table" aria-labelledby="rsvp-responses-title">
                <thead role="rowgroup">
                    <tr role="row">
                        <th scope="col" role="columnheader" class="rsvp-report__guest">Guest</th>
                        <th scope="col" role="columnheader">Attendance</th>
                        @if ($event->private_rsvp)
                            <th scope="col" role="columnheader">Seats</th>
                            <th scope="col" role="columnheader">Halal meal</th>
                        @endif
                        @if ($sessions->isNotEmpty())
                            <th scope="col" role="columnheader">Session</th>
                        @endif
                        <th scope="col" role="columnheader">Remarks</th>
                        <th scope="col" role="columnheader">Submitted</th>
                    </tr>
                </thead>
                <tbody role="rowgroup">
                    @foreach ($rsvps as $reply)
                        <tr role="row">
                            <th scope="row" role="rowheader" class="rsvp-report__guest">
                                <strong>{{ $reply->name }}</strong>
                                @if ($reply->email)
                                    <span>{{ $reply->email }}</span>
                                @endif
                                @if ($reply->phone)
                                    <span>{{ $reply->phone }}</span>
                                @endif
                            </th>
                            <td role="cell">
                                <span class="rsvp-report__label" aria-hidden="true">Attendance</span>
                                <span class="rsvp-report__status rsvp-report__status--{{ $reply->attendance_status === 'attending' ? 'attending' : 'declined' }}">
                                    {{ ucfirst($reply->attendance_status) }}
                                </span>
                            </td>
                            @if ($event->private_rsvp)
                                <td role="cell">
                                    <span class="rsvp-report__label" aria-hidden="true">Seats</span>
                                    <strong>{{ $reply->confirmed_seats ?? '—' }}</strong>
                                </td>
                                <td role="cell">
                                    <span class="rsvp-report__label" aria-hidden="true">Halal meal</span>
                                    {{ $reply->halal_meal === null ? '—' : ($reply->halal_meal ? 'Yes' : 'No') }}
                                </td>
                            @endif
                            @if ($sessions->isNotEmpty())
                                <td role="cell">
                                    <span class="rsvp-report__label" aria-hidden="true">Session</span>
                                    {{ $reply->session->name ?? '—' }}
                                </td>
                            @endif
                            <td role="cell" class="rsvp-report__remark">
                                <span class="rsvp-report__label" aria-hidden="true">Remarks</span>
                                <span class="rsvp-report__remark-text">{{ filled($reply->remarks) ? $reply->remarks : '—' }}</span>
                            </td>
                            <td role="cell" class="rsvp-report__submitted">
                                <span class="rsvp-report__label" aria-hidden="true">Submitted</span>
                                @if ($reply->submitted_at)
                                    <time datetime="{{ $reply->submitted_at->toIso8601String() }}">
                                        {{ $reply->submitted_at->format('j M Y') }}
                                        <small>{{ $reply->submitted_at->format('H:i T') }}</small>
                                    </time>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="rsvp-report__empty">{{ $activeFilters ? 'No responses match these filters.' : 'No RSVPs yet.' }}</p>
        @endif

        @if ($rsvps->hasPages())
            <footer class="rsvp-report__pagination">
                {{ $rsvps->links('pagination::bootstrap-4') }}
            </footer>
        @endif
    </section>
</main>
@endsection
