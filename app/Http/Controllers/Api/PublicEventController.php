<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use App\Services\InvitationMusic;
use App\Services\InvitationPhotos;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicEventController extends Controller
{
    public function show(Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        $event->load(['sessions' => fn ($q) => $q->orderBy('sort_order')]);

        $data = [
            'title'           => $event->title,
            'event_date'      => optional($event->event_date)->toDateString(),
            'venue_name'      => $event->venue_name,
            'venue_address'   => $event->venue_address,
            'venue_map_url'   => $event->venue_map_url,
            'welcome_message' => $event->welcome_message,
            'rsvp_deadline'   => optional($event->rsvp_deadline)->toDateString(),
            'private_rsvp'    => (bool) $event->private_rsvp,
            'rsvp_closed'     => $event->private_rsvp ? $event->privateRsvpIsClosed() : $event->isRsvpClosed(),
            'sessions'        => $event->sessions->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'start_at' => optional($s->start_at)->toIso8601String(),
                'end_at'   => optional($s->end_at)->toIso8601String(),
                'location' => $s->location,
            ])->values(),
        ];

        if ($event->private_rsvp) {
            $data['shared_rsvp_seat_limit'] = $event->sharedRsvpSeatLimit();
        }

        if (InvitationPhotos::supports($event)) {
            $data['photos'] = InvitationPhotos::catalog($event);
            $data += InvitationMusic::catalog($event);

            return response()->json($data)->header('Cache-Control', 'private, no-store');
        }

        return response()->json($data);
    }

    public function photo(Request $request, Event $event, string $version, string $variant)
    {
        abort_unless($event->status === Event::STATUS_PUBLISHED && InvitationPhotos::supports($event), 404);

        $photo = InvitationPhotos::query($event)->where('path', InvitationPhotos::path($event, $version))->firstOrFail();

        return InvitationPhotos::image($request, $photo, $variant, true);
    }

    public function music(Request $request, Event $event, string $track)
    {
        abort_unless($event->status === Event::STATUS_PUBLISHED && $event->usesWeddingInvitation(), 404);

        return InvitationMusic::stream($request, $event, $track, true);
    }

    public function submitRsvp(Request $request, Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        abort_if($event->private_rsvp, 404);

        // Optional: enforce RSVP deadline
        if ($event->rsvp_deadline && now()->startOfDay()->gt($event->rsvp_deadline)) {
            return response()->json(['message' => 'RSVP is closed'], 422);
        }

        $validated = $request->validate([
            'attendance_status'     => ['required', Rule::in([Rsvp::STATUS_ATTENDING, Rsvp::STATUS_DECLINED])],
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['nullable', 'email', 'max:255'],
            'phone'                 => ['nullable', 'string', 'max:50'],
            'remarks'               => ['nullable', 'string', 'max:1000'],
            'session_id'            => ['nullable', 'integer'],
            'allow_public_share'    => ['nullable', 'boolean'],

        ]);

        // If session_id is provided, ensure it belongs to this event
        if (!empty($validated['session_id'])) {
            $ok = $event->sessions()->whereKey($validated['session_id'])->exists();
            if (!$ok) {
                return response()->json([
                    'message' => 'Invalid session',
                    'errors'  => ['session_id' => ['Session does not belong to this event.']],
                ], 422);
            }
        }

        $identity = null;

        if (!empty($validated['email'])) {
            $identity = ['email' => $validated['email']];
        } elseif (!empty($validated['phone'])) {
            $identity = ['phone' => $validated['phone']];
        }

        if ($identity) {
            $rsvp = Rsvp::updateOrCreate(
                array_merge(['event_id' => $event->id, 'household_reply' => null], $identity),
                [
                    'guest_id'              => null,
                    'session_id'            => $validated['session_id'] ?? null,
                    'attendance_status'     => $validated['attendance_status'],
                    'name'                  => $validated['name'],
                    'email'                 => $validated['email'] ?? null,
                    'phone'                 => $validated['phone'] ?? null,
                    'remarks'               => $validated['remarks'] ?? null,
                    'allow_public_share'    => $validated['allow_public_share'] ?? false,
                    'submitted_at'          => now(),
                ]
            );

            return response()->json([
                'success' => true,
                'rsvp_id' => $rsvp->id,
                'mode'    => $rsvp->wasRecentlyCreated ? 'created' : 'updated',
            ]);
        }

        $rsvp = Rsvp::create([
            'event_id'              => $event->id,
            'guest_id'              => null,
            'session_id'            => $validated['session_id'] ?? null,
            'attendance_status'     => $validated['attendance_status'],
            'name'                  => $validated['name'],
            'email'                 => $validated['email'] ?? null,
            'phone'                 => $validated['phone'] ?? null,
            'remarks'               => $validated['remarks'] ?? null,
            'allow_public_share'    => $validated['allow_public_share'] ?? false,
            'submitted_at'          => now(),
        ]);

        return response()->json([
            'success' => true,
            'rsvp_id' => $rsvp->id,
            'mode'    => 'created',
        ]);
    }

    public function getRsvp(Request $request, Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Event not found'], 404);
        }

        abort_if($event->private_rsvp, 404);

        $validated = $request->validate([
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
        ]);

        // Must provide at least one identity key
        if (empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'message' => 'Please provide an email address or phone number.',
                'errors'  => ['identity' => ['Missing identity parameter.']],
            ], 422);
        }

        $q = Rsvp::query()->where('event_id', $event->id)->whereNull('household_reply');

        if (!empty($validated['email'])) {
            $q->where('email', $validated['email']);
        } else {
            $q->where('phone', $validated['phone']);
        }

        $rsvp = $q->latest('id')->first();

        if (!$rsvp) {
            return response()->json([
                'success' => true,
                'found'   => false,
                'data'    => null,
            ]);
        }

        // Public-safe fields only
        return response()->json([
            'success' => true,
            'found'   => true,
            'data'    => [
                'attendance_status' => $rsvp->attendance_status,
                'name'              => $rsvp->name,
                'email'             => $rsvp->email,
                'phone'             => $rsvp->phone,
                'remarks'           => $rsvp->remarks,
                'session_id'        => $rsvp->session_id,
                'submitted_at'      => optional($rsvp->submitted_at)->toISOString(),
            ],
        ]);
    }
}
