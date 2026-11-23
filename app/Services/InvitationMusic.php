<?php

namespace App\Services;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvitationMusic
{
    public const LANGUAGES = ['en' => 'English', 'zh' => 'Simplified Chinese (简体中文)'];
    public const TRACK_ID_PATTERN = 'library-[0-9]{2}|[a-f0-9-]{36}';

    private const GROUPS = [
        'romantic-and-upbeat' => 'Romantic and upbeat',
        'classical-piano' => 'Classical piano',
        'chinese-inspired' => 'Chinese-inspired',
    ];

    public static function library(): array
    {
        $root = config('wedding.music_library_path');
        $tracks = [];

        foreach (self::GROUPS as $directory => $group) {
            foreach (glob($root.'/'.$directory.'/*.mp3') ?: [] as $path) {
                if (is_readable($path) && is_file($path) && preg_match('/^(\d{2}) (.+)\.mp3$/D', basename($path), $parts)) {
                    $tracks['library-'.$parts[1]] = ['title' => $parts[2], 'path' => $path, 'group' => $group];
                }
            }
        }

        return $tracks;
    }

    public static function legacyTracks(Event $event): Collection
    {
        return $event->assets()->whereIn('type', ['invitation-music-en', 'invitation-music-zh'])->get()->keyBy('type');
    }

    public static function legacyOptions(Collection $tracks, string $language): array
    {
        $track = $tracks->get('invitation-music-'.$language);
        if ($track === null) {
            return [];
        }

        return [pathinfo($track->path, PATHINFO_FILENAME) => [
            'title' => $track->title ?? 'Uploaded track',
            'path' => Storage::disk('local')->path($track->path),
            'group' => 'Previously uploaded',
        ]];
    }

    public static function selection(Event $event, string $language, array $legacy): array
    {
        return $event->invitation_music[$language] ?? array_keys($legacy);
    }

    public static function version(array $selection): string
    {
        sort($selection);

        return hash('sha256', implode('|', $selection));
    }

    public static function catalog(Event $event): array
    {
        $library = self::library();
        $legacyTracks = self::legacyTracks($event);
        $credits = config('wedding.music_credits', []);
        $catalog = ['music' => []];

        foreach (self::LANGUAGES as $language => $label) {
            $legacy = self::legacyOptions($legacyTracks, $language);
            $options = $library + $legacy;
            $tracks = array_intersect(self::selection($event, $language, $legacy), array_keys($options));
            $id = $tracks ? $tracks[array_rand($tracks)] : null;
            $catalog['music'][$language] = $id === null ? null : route('public.events.music.track', [$event->slug, $id], false);
            if (isset($credits[$id])) {
                $catalog['music_credits'][$language] = $credits[$id];
            }
        }

        return $catalog;
    }

    public static function stream(Request $request, Event $event, string $id, bool $public): BinaryFileResponse
    {
        $library = self::library();
        $legacyTracks = self::legacyTracks($event);
        $file = null;

        foreach (self::LANGUAGES as $language => $label) {
            $legacy = self::legacyOptions($legacyTracks, $language);
            if (!$public || in_array($id, self::selection($event, $language, $legacy), true)) {
                $file = ($library + $legacy)[$id]['path'] ?? null;
                if ($file !== null) {
                    break;
                }
            }
        }

        abort_unless($file !== null && is_file($file) && is_readable($file), 404);

        $response = response()->file($file, [
            'Content-Type' => 'audio/mpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->headers->set('Cache-Control', $public ? 'public, no-cache' : 'private, no-store');
        if ($public) {
            $response->setEtag(hash('sha256', $id.'|'.filemtime($file).'|'.filesize($file)));
            $response->isNotModified($request);
        }

        return $response;
    }
}
