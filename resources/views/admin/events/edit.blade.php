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
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.events.index') }}">
                        Back to list
                    </a>
                </div>
                <div class="card-body">
                    @include('admin.events.partials.errors')

                    <div class="mb-3">
                        <div class="d-flex align-items-center">
                            <div class="mr-3">
                                <div class="small text-muted">Package</div>
                                <div class="h5 mb-0">{{ optional($event->package)->name ?? 'Not set' }}</div>
                            </div>
                            <div>
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
                                        <span class="badge badge-light mr-1">{{ $label }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.events.update', $event->id) }}">
                        @csrf
                        @method('PUT')

                        @include('admin.events.partials.form-fields')

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
