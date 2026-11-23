<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAsset;
use App\Services\InvitationPhotos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class EventPhotoController extends Controller
{
    private const DESCRIPTION_RULES = [
        'alt' => ['nullable', 'string', 'max:255'],
        'alt_zh' => ['nullable', 'string', 'max:255'],
    ];

    public function index(Event $event)
    {
        abort_unless(InvitationPhotos::supports($event), 404);
        $placements = InvitationPhotos::PLACEMENTS;
        $photos = InvitationPhotos::query($event)->orderBy('sort_order')->orderBy('id')->get()->groupBy('type');

        return view('admin.events.photos.index', compact('event', 'placements', 'photos'));
    }

    public function store(Request $request, Event $event)
    {
        abort_unless(InvitationPhotos::supports($event), 404);
        $fields = $this->validateUpload($request, [
            'placement' => ['required', Rule::in(array_keys(InvitationPhotos::PLACEMENTS))],
        ]);
        $placement = $fields['placement'];
        $image = InvitationPhotos::store($event, $request->file('photo')->getPathname(), $fields['alt_zh'] ?? null);

        try {
            DB::transaction(function () use ($event, $request, $placement, $fields, $image) {
                Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
                $type = InvitationPhotos::type($placement);
                $lastOrder = $event->assets()->where('type', $type)->max('sort_order');

                if (in_array($placement, InvitationPhotos::SINGLE_PHOTO_PLACEMENTS, true) && $lastOrder !== null) {
                    throw ValidationException::withMessages(['photo' => 'Use Replace to change the current photo in this section.']);
                }

                $event->assets()->create($image + [
                    'type' => $type,
                    'title' => $fields['alt'] ?? null,
                    'sort_order' => ($lastOrder ?? -1) + 1,
                    'uploaded_by' => $request->user()->id,
                ]);
            }, 3);
        } catch (Throwable $exception) {
            InvitationPhotos::delete($image['path']);
            throw $exception;
        }

        return $this->backToPhotos($event, 'Photo uploaded.');
    }

    public function update(Request $request, Event $event, int $photo)
    {
        $asset = $this->findPhoto($event, $photo);
        $fields = $this->validateUpload($request, ['version' => ['required', 'uuid']]);
        $expectedPath = InvitationPhotos::path($event, $fields['version']);
        $image = InvitationPhotos::store($event, $request->file('photo')->getPathname(), $fields['alt_zh'] ?? null);

        try {
            // The matching path prevents an older form from replacing a more recent upload.
            $updated = InvitationPhotos::query($event)->whereKey($asset->id)->where('path', $expectedPath)->update([
                'path' => $image['path'],
                'meta' => json_encode($image['meta'], JSON_THROW_ON_ERROR),
                'title' => $fields['alt'] ?? null,
                'uploaded_by' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            InvitationPhotos::delete($image['path']);
            throw $exception;
        }

        if (!$updated) {
            InvitationPhotos::delete($image['path']);
            return $this->changedPhoto($event);
        }

        InvitationPhotos::delete($expectedPath);

        return $this->backToPhotos($event, 'Photo replaced.');
    }

    public function updateDescription(Request $request, Event $event, int $photo)
    {
        $asset = $this->findPhoto($event, $photo);
        abort_unless($asset->type === InvitationPhotos::type('story'), 404);

        $fields = $request->validate(['version' => ['required', 'uuid']] + self::DESCRIPTION_RULES);
        $expectedPath = InvitationPhotos::path($event, $fields['version']);
        $meta = array_replace($asset->meta ?? [], ['alt_zh' => $fields['alt_zh'] ?? null]);

        $updated = InvitationPhotos::query($event)->whereKey($asset->id)->where('path', $expectedPath)->update([
            'title' => $fields['alt'] ?? null,
            'meta' => json_encode($meta, JSON_THROW_ON_ERROR),
        ]);

        if (!$updated) {
            return $this->changedPhoto($event)->with('description_photo', $asset->id);
        }

        return $this->backToPhotos($event, 'Description saved.')
            ->with('description_photo', $asset->id)
            ->withFragment('story-photo-'.$asset->id);
    }

    public function destroy(Request $request, Event $event, int $photo)
    {
        $asset = $this->findPhoto($event, $photo);
        $fields = $request->validate(['version' => ['required', 'uuid']]);
        $expectedPath = InvitationPhotos::path($event, $fields['version']);
        $deleted = InvitationPhotos::query($event)->whereKey($asset->id)->where('path', $expectedPath)->delete();

        if (!$deleted) {
            return $this->changedPhoto($event);
        }

        InvitationPhotos::delete($expectedPath);

        return $this->backToPhotos($event, 'Photo removed.');
    }

    public function thumbnail(Request $request, Event $event, int $photo)
    {
        return InvitationPhotos::image($request, $this->findPhoto($event, $photo), 'thumb', false);
    }

    private function findPhoto(Event $event, int $photo): EventAsset
    {
        abort_unless(InvitationPhotos::supports($event), 404);

        return InvitationPhotos::query($event)->findOrFail($photo);
    }

    private function validateUpload(Request $request, array $extra): array
    {
        return $request->validate($extra + [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:15360'],
        ] + self::DESCRIPTION_RULES);
    }

    private function backToPhotos(Event $event, string $message)
    {
        return redirect()->route('admin.events.photos.index', $event->id)->with('status', $message);
    }

    private function changedPhoto(Event $event)
    {
        return redirect()->route('admin.events.photos.index', $event->id)
            ->withErrors(['photo' => 'This photo changed since you opened the page. Review the current photo and try again.']);
    }
}
