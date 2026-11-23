@extends('layouts.app')

@section('page-title', 'Edit RSVP')

@section('page-header')
  <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header border-0 d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Edit RSVP</h3>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('purchaser.rsvps.index', $event->id) }}">
                        Back to RSVPs
                    </a>
                </div>
                <div class="card-body">
                    @include('admin.events.partials.errors')

                    <form method="POST" action="{{ route('purchaser.rsvps.update', [$event->id, $rsvp->id]) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Guest</label>
                                    <input class="form-control" value="{{ $rsvp->name }}" disabled>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label>Phone or email</label>
                                    <input class="form-control" value="{{ $rsvp->email ?? $rsvp->phone }}" disabled>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="attendance_status">Attendance</label>
                                    <select class="form-control" id="attendance_status" name="attendance_status" required>
                                        @foreach (['attending' => 'Attending', 'declined' => 'Declined'] as $value => $label)
                                            <option value="{{ $value }}" {{ old('attendance_status', $rsvp->attendance_status) === $value ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="form-group">
                                    <label for="remarks">Remarks</label>
                                    <textarea class="form-control"
                                              id="remarks"
                                              name="remarks"
                                              rows="3">{{ old('remarks', $rsvp->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>

                        @if ($rsvp->household_reply)
                            <div class="form-group">
                                <label for="confirmed_seats">Confirmed seats if attending</label>
                                <input type="number" class="form-control" id="confirmed_seats" name="confirmed_seats"
                                       min="0" max="{{ $rsvp->guest->party_size ?? 65535 }}"
                                       value="{{ old('confirmed_seats', $rsvp->confirmed_seats) }}">
                            </div>
                            <div class="form-group">
                                <label for="halal_meal">Halal meal needed if attending</label>
                                @php($halalMeal = old('halal_meal', $rsvp->halal_meal === null ? '' : (string) (int) $rsvp->halal_meal))
                                <select class="form-control" id="halal_meal" name="halal_meal">
                                    <option value="" {{ $halalMeal === '' || $halalMeal === null ? 'selected' : '' }}>Not specified</option>
                                    <option value="1" {{ $halalMeal === '1' ? 'selected' : '' }}>Yes</option>
                                    <option value="0" {{ $halalMeal === '0' ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">Save RSVP</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
