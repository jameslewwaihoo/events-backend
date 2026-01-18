@extends('layouts.app')

@section('page-title', 'RSVPs')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0">RSVPs for {{ $event->title }}</h3>
                        <div class="small text-muted">{{ $event->slug }}</div>
                    </div>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchaser.events.edit', $event->id) }}">
                        Back to event
                    </a>
                </div>

                @if (session('status'))
                    <div class="alert alert-success m-3">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Session</th>
                                <th>Submitted</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rsvps as $rsvp)
                                <tr>
                                    <td>
                                        <div>{{ $rsvp->name }}</div>
                                        <div class="small text-muted">{{ $rsvp->email }}</div>
                                    </td>
                                    <td>{{ ucfirst($rsvp->attendance_status) }}</td>
                                    <td>{{ optional($rsvp->session)->name ?? '—' }}</td>
                                    <td>{{ optional($rsvp->submitted_at)->format('Y-m-d H:i') }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('purchaser.rsvps.edit', [$event->id, $rsvp->id]) }}">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No RSVPs yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer py-4">
                    {{ $rsvps->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
