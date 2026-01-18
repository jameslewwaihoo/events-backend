@extends('layouts.app')

@section('page-title', 'Dashboard')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-xl-4 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Total Events</h5>
                            <span class="h2 font-weight-bold mb-0">{{ $stats['total'] }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-primary text-white rounded-circle shadow">
                                <i class="ni ni-calendar-grid-58"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-6">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">Published</h5>
                            <span class="h2 font-weight-bold mb-0">{{ $stats['published'] }}</span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-success text-white rounded-circle shadow">
                                <i class="ni ni-check-bold"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-12">
            <div class="card card-stats mb-4 mb-xl-0">
                <div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title text-uppercase text-muted mb-0">RSVPs</h5>
                            <span class="h2 font-weight-bold mb-0">
                                @if ($firstEventId)
                                    <a href="{{ route('purchaser.rsvps.index', $firstEventId) }}"
                                       class="text-primary">
                                        {{ $rsvpStats['total'] ?? 0 }}
                                    </a>
                                @else
                                    {{ $rsvpStats['total'] ?? 0 }}
                                @endif
                            </span>
                        </div>
                        <div class="col-auto">
                            <div class="icon icon-shape bg-info text-white rounded-circle shadow">
                                <i class="ni ni-email-83"></i>
                            </div>
                        </div>
                    </div>
                    <p class="mt-3 mb-0 text-sm">
                        <span class="text-success mr-2">
                            @if ($firstEventId)
                                <a class="text-success" href="{{ route('purchaser.rsvps.index', $firstEventId) }}">
                                    {{ $rsvpStats['attending'] ?? 0 }} attending
                                </a>
                            @else
                                {{ $rsvpStats['attending'] ?? 0 }} attending
                            @endif
                        </span>
                        <span class="text-danger">
                            @if ($firstEventId)
                                <a class="text-danger" href="{{ route('purchaser.rsvps.index', $firstEventId) }}">
                                    {{ $rsvpStats['declined'] ?? 0 }} declined
                                </a>
                            @else
                                {{ $rsvpStats['declined'] ?? 0 }} declined
                            @endif
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">

        <div class="col-xl-6">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Upcoming Events</h3>
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('purchaser.events.index') }}">Manage</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($upcoming as $event)
                                <tr>
                                    <td>{{ $event->title }}</td>
                                    <td>{{ optional($event->event_date)->format('Y-m-d') ?: 'TBD' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $event->status === 'published' ? 'success' : 'secondary' }}">
                                            {{ ucfirst($event->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No upcoming events scheduled.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mt-4 mt-xl-0">
            <div class="card shadow">
                <div class="card-header border-0">
                    <h3 class="mb-0">Recently Updated</h3>
                </div>
                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Title</th>
                                <th>Updated</th>
                                <th>Package</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recent as $event)
                                <tr>
                                    <td>{{ $event->title }}</td>
                                    <td>{{ optional($event->updated_at)->diffForHumans() }}</td>
                                    <td>{{ optional($event->package)->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">No recent changes.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
