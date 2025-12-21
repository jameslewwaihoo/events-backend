<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
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

        return response()->json([
            'title'           => $event->title,
            'event_date'      => optional($event->event_date)->toDateString(),
            'venue_name'      => $event->venue_name,
            'venue_address'   => $event->venue_address,
            'venue_map_url'   => $event->venue_map_url,
            'welcome_message' => $event->welcome_message,
            'rsvp_deadline'   => optional($event->rsvp_deadline)->toDateString(),
            'sessions'        => $event->sessions->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'start_at' => optional($s->start_at)->toIso8601String(),
                'location' => $s->location,
            ])->values(),
        ]);
    }

    public function submitRsvp(Request $request, Event $event)
    {
        if ($event->status !== Event::STATUS_PUBLISHED) {
            return response()->json(['message' => 'Event not found'], 404);
        }

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
            'invite_code'           => ['nullable', 'string', 'max:50'], // for future guest flow
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

        if (!empty($validated['invite_code'])) {
            $identity = ['invite_code' => $validated['invite_code']];
        } elseif (!empty($validated['email'])) {
            $identity = ['email' => $validated['email']];
        } elseif (!empty($validated['phone'])) {
            $identity = ['phone' => $validated['phone']];
        }

        if ($identity) {
            $rsvp = Rsvp::updateOrCreate(
                array_merge(['event_id' => $event->id], $identity),
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
            'guest_id'              => null, // we’ll wire invite_code -> guest later
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

        $validated = $request->validate([
            'invite_code' => ['nullable', 'string', 'max:50'],
            'email'       => ['nullable', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
        ]);

        // Must provide at least one identity key
        if (empty($validated['invite_code']) && empty($validated['email']) && empty($validated['phone'])) {
            return response()->json([
                'message' => 'Please provide invite_code, email, or phone.',
                'errors'  => ['identity' => ['Missing identity parameter.']],
            ], 422);
        }

        $q = Rsvp::query()->where('event_id', $event->id);

        // Priority: invite_code > email > phone (match your upsert strategy)
        if (!empty($validated['invite_code'])) {
            $q->where('invite_code', $validated['invite_code']);
        } elseif (!empty($validated['email'])) {
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
                'invite_code'       => $rsvp->invite_code,
                'submitted_at'      => optional($rsvp->submitted_at)->toISOString(),
            ],
        ]);
    }
}
