@extends('layouts.app')

@section('page-title', 'Invitation Music')

@section('page-header')
    <div class="header bg-gradient-primary pb-8 pt-5 pt-md-8"></div>
@endsection

@section('content')
<div class="container-fluid mt--7">
    <div class="card shadow mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Invitation music</h3>
                <div class="text-sm text-muted">{{ $event->title }}</div>
            </div>
            <div class="mt-2 mt-sm-0">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.events.index') }}">Events</a>
                <a class="btn btn-sm btn-outline-primary" href="{{ url('/e/'.$event->slug) }}" target="_blank" rel="noopener">View invitation</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif
            @include('admin.events.partials.errors')
            <p class="text-sm mb-3">Choose tracks for each language. The invitation picks one at random each time guests open the page.</p>

            <div class="row">
                @foreach ($playlists as $language => $playlist)
                    <section class="col-lg-6 mb-3" aria-labelledby="music-{{ $language }}">
                        <div class="border rounded p-3 h-100">
                            <h4 id="music-{{ $language }}">{{ $playlist['label'] }}</h4>

                            <form method="POST" action="{{ route('admin.events.music.update', [$event->id, $language]) }}" data-music-playlist>
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="version" value="{{ $playlist['version'] }}">
                                @if ($playlist['groups']->isNotEmpty())
                                    <details class="mb-3">
                                        <summary class="border rounded px-3 py-2 font-weight-bold">
                                            Choose tracks <span class="text-muted font-weight-normal" data-track-count aria-live="polite">({{ count($playlist['selected']) }} selected)</span>
                                        </summary>
                                        <div class="border rounded mt-1 px-3" style="max-height: 20rem; overflow-y: auto;">
                                            @foreach ($playlist['groups'] as $group => $tracks)
                                                <fieldset class="pt-3 pb-2">
                                                    <legend class="text-sm font-weight-bold mb-1">{{ $group }}</legend>
                                                    @foreach ($tracks as $id => $track)
                                                        <div class="d-flex align-items-center justify-content-between border-bottom py-1">
                                                            <label class="d-flex align-items-center flex-grow-1 mb-0 py-1 pr-2">
                                                                <input type="checkbox" name="tracks[]" value="{{ $id }}" class="mr-2"
                                                                    @if (in_array($id, $playlist['selected'], true)) checked @endif>
                                                                <span class="text-sm text-break">{{ $track['title'] }}</span>
                                                            </label>
                                                            <button class="btn btn-sm btn-outline-secondary" type="button"
                                                                data-preview="{{ route('admin.events.music.preview', [$event->id, $id]) }}"
                                                                data-title="{{ $track['title'] }}" aria-label="Preview {{ $track['title'] }}">Preview</button>
                                                        </div>
                                                    @endforeach
                                                </fieldset>
                                            @endforeach
                                        </div>
                                    </details>
                                    <p class="text-sm mb-1" data-preview-title aria-live="polite" hidden></p>
                                    <audio class="w-100 mb-3" controls preload="none" hidden></audio>
                                    <button class="btn btn-sm btn-primary" type="submit">Save selection</button>
                                @else
                                    <p class="text-sm text-muted mb-0">The music library is empty.</p>
                                @endif
                            </form>
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.querySelectorAll('[data-music-playlist]').forEach((form) => {
        const count = form.querySelector('[data-track-count]');
        const player = form.querySelector('audio');
        const title = form.querySelector('[data-preview-title]');
        if (!player) return;

        form.addEventListener('change', () => {
            const selected = form.querySelectorAll('input[name="tracks[]"]:checked').length;
            count.textContent = `(${selected} selected)`;
        });

        form.addEventListener('click', (event) => {
            const button = event.target.closest('[data-preview]');
            if (!button) return;

            document.querySelectorAll('[data-music-playlist] audio').forEach((audio) => audio.pause());
            title.hidden = false;
            title.textContent = button.dataset.title;
            player.hidden = false;
            player.setAttribute('aria-label', `Preview ${button.dataset.title}`);
            player.src = button.dataset.preview;
            player.play().catch((error) => {
                if (error.name !== 'AbortError' && player.src === button.dataset.preview) {
                    title.textContent = `${button.dataset.title} (preview couldn’t play)`;
                }
            });
        });
    });
</script>
@endpush
