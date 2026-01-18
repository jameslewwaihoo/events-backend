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
                                    <label>Email</label>
                                    <input class="form-control" value="{{ $rsvp->email }}" disabled>
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
