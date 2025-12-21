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
                    {{-- <h3 class="mb-0">Events</h3> --}}

                    <form class="form-inline" method="GET">
                        <input type="text"
                               name="q"
                               class="form-control form-control-sm mr-2"
                               placeholder="Search title / slug"
                               value="{{ $q }}">
                        <button class="btn btn-sm btn-primary" type="submit">Search</button>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Slug</th>
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
                                    <td>{{ $e->title }}</td>
                                    <td>{{ $e->slug }}</td>
                                    <td>
                                        <span class="badge badge-{{ $e->status === 'published' ? 'success' : 'secondary' }}">
                                            {{ $e->status }}
                                        </span>
                                    </td>
                                    <td>{{ $e->event_date }}</td>
                                    <td>{{ $e->rsvp_deadline }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="{{ route('admin.events.rsvps.index', $e->id) }}">
                                            RSVPs
                                        </a>
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
