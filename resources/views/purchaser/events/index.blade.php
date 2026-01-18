@extends('layouts.app')

@section('page-title', 'My Events')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">My Events</h3>
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
                                <th>Title</th>
                                <th>Package</th>
                                <th>Status</th>
                                <th>Event Date</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($events as $event)
                                <tr>
                                    <td>
                                        <div>{{ $event->title }}</div>
                                        <div class="small text-muted">{{ $event->slug }}</div>
                                    </td>
                                    <td>
                                        @if ($event->package)
                                            <span class="badge badge-info text-uppercase">{{ $event->package->code }}</span>
                                            <div class="small text-muted">{{ $event->package->name }}</div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $event->status === 'published' ? 'success' : 'secondary' }}">
                                            {{ $event->status }}
                                        </span>
                                    </td>
                                    <td>{{ optional($event->event_date)->format('Y-m-d') }}</td>
                                    <td class="text-right">
                                        @include('purchaser.events.index-actions', ['event' => $event])
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">No events assigned to you.</td>
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
