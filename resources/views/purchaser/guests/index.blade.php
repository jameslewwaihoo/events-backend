@extends('layouts.app')

@section('page-title', 'Guests')

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
                        <h3 class="mb-0">Guests for {{ $event->title }}</h3>
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
                                <th>Contact</th>
                                <th>RSVP</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($guests as $guest)
                                <tr>
                                    <td>{{ $guest->name }}</td>
                                    <td>
                                        <div>{{ $guest->email }}</div>
                                        <div class="small text-muted">{{ $guest->phone }}</div>
                                    </td>
                                    <td>{{ ucfirst($guest->rsvp_status ?? 'pending') }}</td>
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('purchaser.guests.edit', [$event->id, $guest->id]) }}">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4">No guests yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer py-4">
                    {{ $guests->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
