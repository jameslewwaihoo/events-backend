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

                @if ($event->private_rsvp)
                    <div class="card-body">
                        @include('admin.events.partials.errors')
                        @if ($invitationWebUrl && $event->status === 'published')
                            <button type="button" class="btn btn-sm btn-outline-primary mb-3"
                                data-invitation-url="{{ $invitationWebUrl.'/e/'.rawurlencode($event->slug) }}">Copy shared RSVP link</button>
                        @endif
                        <p>Send the shared link directly to your guests. Their responses appear under RSVPs.</p>
                        <form method="POST" action="{{ route('purchaser.guests.store', $event->id) }}">
                            @csrf
                            <div class="form-row align-items-end">
                                <div class="form-group col-md-6">
                                    <label for="name">Guest or household name</label>
                                    <input id="name" name="name" class="form-control" value="{{ old('name') }}" maxlength="255" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="party_size">Invited seats</label>
                                    <input type="number" id="party_size" name="party_size" class="form-control"
                                           value="{{ old('party_size', 1) }}" min="1" max="65535" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="guest-email">Email</label>
                                    <input type="email" id="guest-email" name="email" class="form-control" value="{{ old('email') }}" maxlength="255">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="guest-phone">Phone</label>
                                    <input type="tel" id="guest-phone" name="phone" class="form-control" value="{{ old('phone') }}" maxlength="50">
                                </div>
                                <div class="form-group col-md-3">
                                    <button type="submit" class="btn btn-primary">Add invitation</button>
                                </div>
                            </div>
                        </form>
                        @if (!$invitationWebUrl)
                            <p class="mb-0 text-muted">Set INVITATION_WEB_URL to enable guest links.</p>
                        @elseif ($event->status !== 'published')
                            <p class="mb-0 text-muted">Publish the event before sharing guest links.</p>
                        @endif
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table align-items-center table-flush">
                        <thead class="thead-light">
                            <tr>
                                <th>Name</th>
                                <th>Contact</th>
                                <th>RSVP</th>
                                @if ($event->private_rsvp)
                                    <th>Confirmed / invited</th>
                                @endif
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
                                    @if ($event->private_rsvp)
                                        <td>{{ ucfirst(optional($guest->householdReply)->attendance_status ?? 'pending') }}</td>
                                        <td>{{ optional($guest->householdReply)->confirmed_seats ?? '—' }} / {{ $guest->party_size }}</td>
                                    @else
                                        <td>{{ ucfirst($guest->rsvp_status ?? 'pending') }}</td>
                                    @endif
                                    <td class="text-right">
                                        <a class="btn btn-sm btn-outline-secondary"
                                           href="{{ route('purchaser.guests.edit', [$event->id, $guest->id]) }}">
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $event->private_rsvp ? 5 : 4 }}">No guests yet.</td>
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

@if ($event->private_rsvp)
    @push('js')
        <script>
            document.querySelectorAll('[data-invitation-url]').forEach((button) => {
                button.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(button.dataset.invitationUrl);
                        button.textContent = 'Copied';
                    } catch {
                        window.prompt('Copy this invitation link', button.dataset.invitationUrl);
                    }
                });
            });
        </script>
    @endpush
@endif
