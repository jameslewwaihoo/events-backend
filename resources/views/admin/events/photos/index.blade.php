@extends('layouts.app')

@section('page-title', 'Invitation Photos')

@section('page-header')
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="card shadow mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Invitation photos</h3>
                <div class="text-sm text-muted">{{ $event->title }}</div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.events.index') }}">Events</a>
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/e/'.$event->slug) }}" target="_blank" rel="noopener">View invitation</a>
            </div>
        </div>
        <div class="card-body">
            @php($descriptionPhotoId = (int) session('description_photo', old('description_photo')))
            @if (session('status') && !$descriptionPhotoId)
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            @include('admin.events.partials.errors')
            <p class="text-sm">Changes appear when guests reload the invitation. Upload a JPG, PNG or WebP up to 15 MB.</p>

            @foreach ($placements as $placement => $label)
                @php($group = $photos->get(\App\Services\InvitationPhotos::type($placement), collect()))
                <details class="border rounded p-3 mb-3" id="photos-{{ $placement }}"
                    @if ($loop->first || ($placement === 'story' && $descriptionPhotoId)) open @endif>
                    <summary class="font-weight-bold">
                        {{ $label }} <span class="text-muted font-weight-normal">({{ $group->count() }})</span>
                    </summary>

                    @if ($placement === 'story')
                        <p class="text-sm mt-3 mb-0">Add an optional description to show underneath the photo in the invitation preview.</p>
                    @endif

                    @if (!in_array($placement, \App\Services\InvitationPhotos::SINGLE_PHOTO_PLACEMENTS, true) || $group->isEmpty())
                        <form class="mt-3 mb-3" method="POST" enctype="multipart/form-data"
                            action="{{ route('admin.events.photos.store', $event->id) }}">
                            @csrf
                            <input type="hidden" name="placement" value="{{ $placement }}">
                            @include('admin.events.photos.fields', ['fieldId' => 'new-'.$placement])
                            <button type="submit" class="btn btn-sm btn-primary">Upload photo</button>
                        </form>
                    @endif

                    @if ($group->isEmpty())
                        <p class="text-muted text-sm mb-0 mt-3">No photos in this section.</p>
                    @endif

                    <div class="row mx-n2 mt-3">
                        @foreach ($group as $photo)
                            <div class="col-sm-6 col-lg-4 col-xl-3 px-2 mb-3"
                                @if ($placement === 'story') id="story-photo-{{ $photo->id }}" @endif>
                                <div class="card border h-100">
                                    <img src="{{ route('admin.events.photos.thumbnail', [$event->id, $photo->id]) }}"
                                        class="card-img-top bg-secondary" loading="lazy" width="384" height="200"
                                        style="height: 200px; object-fit: contain;"
                                        alt="{{ $photo->title ?? $label.' '.$loop->iteration }}">
                                    <div class="card-body p-3">
                                        <div class="text-sm font-weight-bold mb-2" style="overflow-wrap: anywhere;">
                                            {{ $photo->title ?? $photo->meta['alt_zh'] ?? 'Photo '.$loop->iteration }}
                                        </div>
                                        @if ($placement === 'story')
                                            @php($retryDescription = (int) old('description_photo') === $photo->id)
                                            <details class="mb-2" @if ($descriptionPhotoId === $photo->id) open @endif>
                                                <summary class="text-primary text-sm">
                                                    {{ $photo->title || !empty($photo->meta['alt_zh']) ? 'Edit description' : 'Add description' }}
                                                </summary>
                                                <form class="mt-2" method="POST"
                                                    action="{{ route('admin.events.photos.description', [$event->id, $photo->id]) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="version" value="{{ pathinfo($photo->path, PATHINFO_FILENAME) }}">
                                                    <input type="hidden" name="description_photo" value="{{ $photo->id }}">
                                                    @include('admin.events.photos.description-fields', [
                                                        'fieldId' => 'description-'.$photo->id,
                                                        'description' => $retryDescription ? old('alt') : $photo->title,
                                                        'descriptionZh' => $retryDescription ? old('alt_zh') : ($photo->meta['alt_zh'] ?? null),
                                                    ])
                                                    <button type="submit" class="btn btn-sm btn-primary">Save description</button>
                                                </form>
                                                @if ($descriptionPhotoId === $photo->id && session('status'))
                                                    <p class="text-success text-sm mt-2 mb-0" role="status">{{ session('status') }}</p>
                                                @endif
                                            </details>
                                        @endif
                                        <details class="mb-2">
                                            <summary class="text-primary text-sm">Replace photo</summary>
                                            <form class="mt-2" method="POST" enctype="multipart/form-data"
                                                action="{{ route('admin.events.photos.update', [$event->id, $photo->id]) }}">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="version" value="{{ pathinfo($photo->path, PATHINFO_FILENAME) }}">
                                                @include('admin.events.photos.fields', ['fieldId' => 'replace-'.$photo->id])
                                                <button type="submit" class="btn btn-sm btn-primary">Save replacement</button>
                                            </form>
                                        </details>
                                        <form class="js-remove-photo" method="POST"
                                            action="{{ route('admin.events.photos.destroy', [$event->id, $photo->id]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="version" value="{{ pathinfo($photo->path, PATHINFO_FILENAME) }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.querySelectorAll('.js-remove-photo').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm('Remove this photo from the invitation?')) event.preventDefault();
        });
    });
</script>
@endpush
