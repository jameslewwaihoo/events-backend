@extends('layouts.app')

@section('page-title', 'Events')


@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')


<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.events.create') }}">
                        Create Event
                    </a>

                    <form class="form-inline" method="GET">
                        <input type="text"
                               name="q"
                               class="form-control form-control-sm mr-2"
                               placeholder="Search title / slug"
                               value="{{ $q }}">
                        <button class="btn btn-sm btn-primary" type="submit">Search</button>
                    </form>
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
                                <th>ID</th>
                                <th>Title</th>
                                <th>Package</th>
                                <th>Purchaser</th>
                                <th>Status</th>
                                <th>Event Date</th>
                                <th>RSVP Deadline</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $e)
                                <tr>
                                    <td>{{ $e->id }}</td>
                                    <td>
                                        <div>{{ $e->title }}</div>
                                        <div class="small text-muted">{{ $e->slug }}</div>
                                    </td>
                                    <td>
                                        @if ($e->package)
                                            <span class="badge badge-info text-uppercase">{{ $e->package->code }}</span>
                                            <div class="small text-muted">{{ $e->package->name }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                        <div class="mt-1">
                                            @php
                                                $featureLabels = [
                                                    'video_enabled' => 'Video',
                                                    'sessions_enabled' => 'Sessions',
                                                    'email_invites_enabled' => 'Invites',
                                                    'attendance_enabled' => 'Attendance',
                                                    'meals_enabled' => 'Meals',
                                                    'seating_enabled' => 'Seating',
                                                ];
                                            @endphp
                                            @foreach ($featureLabels as $key => $label)
                                                @if ($e->featureEnabled($key))
                                                    <span class="badge badge-light mr-1 mb-1">{{ $label }}</span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        @if ($e->user)
                                            <div>{{ $e->user->name }}</div>
                                            <div class="small text-muted">{{ $e->user->email }}</div>
                                        @else
                                            <span class="text-muted">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $e->status === 'published' ? 'success' : 'secondary' }}">
                                            {{ $e->status }}
                                        </span>
                                    </td>
                                    <td>{{ optional($e->event_date)->format('Y-m-d') }}</td>
                                    <td>{{ optional($e->rsvp_deadline)->format('Y-m-d') }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.events.rsvps.index', $e->id) }}">
                                            RSVPs
                                        </a>
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('admin.events.edit', $e->id) }}">
                                            Edit
                                        </a>
                                        <form action="{{ route('admin.events.destroy', $e->id) }}"
                                              method="POST"
                                              class="d-inline js-delete-event">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"
                                                    data-event-title="{{ $e->title }}"
                                                    type="submit">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">No events found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer py-4">
                    {{ $events->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('assets/vendor/sweetalert2/dist/sweetalert2.min.css') }}">
@endpush

@push('js')
<script src="{{ asset('assets/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const forms = document.querySelectorAll('.js-delete-event');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const title = btn.dataset.eventTitle || 'this event';

                Swal.fire({
                    title: 'Delete event?',
                    text: `Are you sure you want to delete "${title}"? This cannot be undone.`,
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33'
                }).then(result => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>
@endpush
