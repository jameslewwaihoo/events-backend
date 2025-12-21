@extends('layouts.app')

@section('page-title', 'RSVPs - ' . $event->title)

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <form class="form-inline" method="GET">
                        <div class="col-md-4">
                            <label class="form-label">Session</label>
                            <select name="session_id" class="form-select">
                                <option value="">All sessions</option>
                                @foreach($sessions as $s)
                                    <option value="{{ $s->id }}" {{ ($filters['session_id']==$s->id) ? 'selected' : '' }}>
                                        {{ $s->name }} @if($s->start_at) ({{ $s->start_at }}) @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Attendance</label>
                            <select name="attendance_status" class="form-select">
                                <option value="">All</option>
                                <option value="attending" {{ $filters['attendance_status']=='attending' ? 'selected' : '' }}>Attending</option>
                                <option value="declined" {{ $filters['attendance_status']=='declined' ? 'selected' : '' }}>Declined</option>
                            </select>
                        </div>

                        <div class="col-md-5 d-flex align-items-end gap-2">
                            <button class="btn btn-primary" type="submit">Filter</button>

                            <a class="btn btn-outline-secondary"
                            href="{{ route('admin.events.rsvps.export', $event->id) }}?{{ http_build_query(request()->query()) }}">
                                Export CSV
                            </a>
                        </div>
                    </form>
                </div>


                <div class="mb-2">
                    <small>
                        Showing {{ $rsvps->total() }} RSVP(s)
                    </small>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Session</th>
                                <th>Remarks</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rsvps as $r)
                                <tr>
                                    <td>{{ $r->attendance_status }}</td>
                                    <td>{{ $r->name }}</td>
                                    <td>{{ $r->email }}</td>
                                    <td>{{ $r->phone }}</td>
                                    <td>{{ optional($r->session)->name }}</td>
                                    <td style="max-width: 360px; white-space: pre-wrap;">{{ $r->remarks }}</td>
                                    <td>{{ optional($r->submitted_at)->toDateTimeString() }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7">No RSVPs found.</td></tr>
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
