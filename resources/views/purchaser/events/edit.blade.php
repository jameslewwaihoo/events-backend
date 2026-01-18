@extends('layouts.app')

@section('page-title', 'Edit Event')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('argon/vendor/bootstrap-datepicker/dist/css/bootstrap-datepicker3.min.css') }}">
@endpush

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Edit Event</h3>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchaser.events.index') }}">
                        Back to list
                    </a>
                </div>
                <div class="card-body">
                    @include('admin.events.partials.errors')

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('purchaser.events.update', $event->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="d-flex flex-wrap mb-3">
                            <a class="btn btn-sm btn-outline-primary mr-2 mb-2" href="{{ route('purchaser.guests.index', $event->id) }}">
                                Guests
                            </a>
                            <a class="btn btn-sm btn-outline-primary mr-2 mb-2" href="{{ route('purchaser.rsvps.index', $event->id) }}">
                                RSVPs
                            </a>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Package</label>
                                    <input class="form-control" value="{{ optional($event->package)->name ?? 'Not set' }}" disabled>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <input class="form-control" value="{{ ucfirst($event->status) }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
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
                                @if ($event->featureEnabled($key))
                                    <span class="badge badge-light mr-1 mb-1">{{ $label }}</span>
                                @endif
                            @endforeach
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="title">Title</label>
                                    <input type="text"
                                           class="form-control"
                                           id="title"
                                           name="title"
                                           value="{{ old('title', $event->title) }}"
                                           required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label for="event_date">Event Date</label>
                                    <input type="text"
                                           class="form-control js-datepicker"
                                           id="event_date"
                                           name="event_date"
                                           autocomplete="off"
                                           placeholder="YYYY-MM-DD"
                                           value="{{ old('event_date', optional($event->event_date)->format('Y-m-d')) }}">
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="form-group">
                                    <label for="rsvp_deadline">RSVP Deadline</label>
                                    <input type="text"
                                           class="form-control js-datepicker"
                                           id="rsvp_deadline"
                                           name="rsvp_deadline"
                                           autocomplete="off"
                                           placeholder="YYYY-MM-DD"
                                           value="{{ old('rsvp_deadline', optional($event->rsvp_deadline)->format('Y-m-d')) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 col-md-12">
                                <div class="form-group">
                                    <label for="venue_name">Venue Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="venue_name"
                                           name="venue_name"
                                           value="{{ old('venue_name', $event->venue_name) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="form-group">
                                    <label for="venue_address">Venue Address</label>
                                    <input type="text"
                                           class="form-control"
                                           id="venue_address"
                                           name="venue_address"
                                           value="{{ old('venue_address', $event->venue_address) }}">
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-12">
                                <div class="form-group">
                                    <label for="venue_map_url">Venue Map URL</label>
                                    <input type="url"
                                           class="form-control"
                                           id="venue_map_url"
                                           name="venue_map_url"
                                           value="{{ old('venue_map_url', $event->venue_map_url) }}">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="welcome_message">Welcome Message</label>
                            <textarea class="form-control"
                                      id="welcome_message"
                                      name="welcome_message"
                                      rows="4">{{ old('welcome_message', $event->welcome_message) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('argon/vendor/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"></script>
<script>
    $(function () {
        $('.js-datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom'
        });
    });
</script>
@endpush
