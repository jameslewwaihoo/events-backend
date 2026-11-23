<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\InvitationMusic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EventMusicController extends Controller
{
    public function index(Event $event)
    {
        abort_unless($event->usesWeddingInvitation(), 404);
        $library = InvitationMusic::library();
        $legacyTracks = InvitationMusic::legacyTracks($event);
        $playlists = [];

        foreach (InvitationMusic::LANGUAGES as $language => $label) {
            $legacy = InvitationMusic::legacyOptions($legacyTracks, $language);
            $options = $library + $legacy;
            $selection = InvitationMusic::selection($event, $language, $legacy);
            $playlists[$language] = [
                'label' => $label,
                'groups' => collect($options)->groupBy('group', true),
                'selected' => array_intersect($selection, array_keys($options)),
                'version' => InvitationMusic::version($selection),
            ];
        }

        return view('admin.events.music.index', compact('event', 'playlists'));
    }

    public function update(Request $request, Event $event, string $language)
    {
        abort_unless($event->usesWeddingInvitation(), 404);
        $library = InvitationMusic::library();

        DB::transaction(function () use ($request, $event, $language, $library) {
            $lockedEvent = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $legacy = InvitationMusic::legacyOptions(InvitationMusic::legacyTracks($lockedEvent), $language);
            $options = $library + $legacy;
            $fields = $request->validate([
                'version' => ['required', 'regex:/^[a-f0-9]{64}$/D'],
                'tracks' => ['sometimes', 'array', 'max:'.count($options)],
                'tracks.*' => ['required', 'string', 'distinct', Rule::in(array_keys($options))],
            ], ['tracks.*.in' => 'Choose tracks from the music library.']);

            $selection = InvitationMusic::selection($lockedEvent, $language, $legacy);
            if ($fields['version'] !== InvitationMusic::version($selection)) {
                throw ValidationException::withMessages(['tracks' => 'This playlist changed. Review the current selection and try again.']);
            }

            $playlists = $lockedEvent->invitation_music ?? [];
            $playlists[$language] = array_values($fields['tracks'] ?? []);
            $lockedEvent->invitation_music = $playlists;
            $lockedEvent->save();
        }, 3);

        return redirect()->route('admin.events.music.index', $event->id)->with('status', 'Music selection saved.');
    }

    public function preview(Request $request, Event $event, string $track)
    {
        abort_unless($event->usesWeddingInvitation(), 404);

        return InvitationMusic::stream($request, $event, $track, false);
    }
}
