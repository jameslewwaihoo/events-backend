@extends('layouts.app')

@section('page-title', 'Edit Guest')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Edit Guest</h3>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchaser.guests.index', $event->id) }}">
                        Back to guests
                    </a>
                </div>
                <div class="card-body">
                    @include('admin.events.partials.errors')

                    <form method="POST" action="{{ route('purchaser.guests.update', [$event->id, $guest->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="name">Name</label>
                                    <input type="text"
                                           class="form-control"
                                           id="name"
                                           name="name"
                                           value="{{ old('name', $guest->name) }}"
                                           required>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="rsvp_status">RSVP Status</label>
                                    <select class="form-control" id="rsvp_status" name="rsvp_status">
                                        @foreach (['pending' => 'Pending', 'yes' => 'Yes', 'no' => 'No', 'maybe' => 'Maybe'] as $value => $label)
                                            <option value="{{ $value }}"
                                                {{ old('rsvp_status', $guest->rsvp_status) === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email"
                                           class="form-control"
                                           id="email"
                                           name="email"
                                           value="{{ old('email', $guest->email) }}">
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text"
                                           class="form-control"
                                           id="phone"
                                           name="phone"
                                           value="{{ old('phone', $guest->phone) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @if ($event->featureEnabled('meals_enabled'))
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label for="meal_choice">Meal Choice</label>
                                        <input type="text"
                                               class="form-control"
                                               id="meal_choice"
                                               name="meal_choice"
                                               value="{{ old('meal_choice', $guest->meal_choice) }}">
                                    </div>
                                </div>
                            @endif
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="notes">Notes</label>
                                    <textarea class="form-control"
                                              id="notes"
                                              name="notes"
                                              rows="3">{{ old('notes', $guest->notes) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save Guest</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
