<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicEventController extends Controller
{
    public function show(string $slug)
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with(['sessions' => fn($q) => $q->orderBy('sort_order')->orderBy('start_at')])
            ->firstOrFail();

        return response()->json([
            'event' => [
                'slug' => $event->slug,
                'title' => $event->title,
                'event_date' => optional($event->event_date)->toDateString(),
                'venue_name' => $event->venue_name,
                'venue_address' => $event->venue_address,
                'venue_map_url' => $event->venue_map_url,
                'welcome_message' => $event->welcome_message,
                'rsvp_deadline' => optional($event->rsvp_deadline)->toIso8601String(),
                'is_rsvp_closed' => $event->isRsvpClosed(),
            ],
            'sessions' => $event->sessions->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'start_at' => optional($s->start_at)->toIso8601String(),
                'location' => $s->location,
            ]),
        ]);
    }

    public function submitRsvp(Request $request, string $slug)
    {
        $event = Event::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        if ($event->isRsvpClosed()) {
            return response()->json(['message' => 'RSVP is closed.'], 403);
        }

        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'phone' => ['nullable','string','max:50'],
            'email' => ['nullable','email','max:255'],
            'attendance_status' => ['required', Rule::in(['attending','declined'])],
            'session_id' => ['nullable','integer'],
            'remarks' => ['nullable','string','max:2000'],
        ]);

        // Require at least ONE contact method for dedupe (C: phone OR email)
        if (empty($data['phone']) && empty($data['email'])) {
            return response()->json([
                'message' => 'Please provide at least a phone number or email.'
            ], 422);
        }

        // Validate session belongs to event (if provided)
        if (!empty($data['session_id'])) {
            $isValidSession = $event->sessions()->whereKey($data['session_id'])->exists();
            if (!$isValidSession) {
                return response()->json(['message' => 'Invalid session.'], 422);
            }
        }

        // Dedupe: match by phone OR email within the same event
        $existing = Rsvp::query()
            ->where('event_id', $event->id)
            ->where(function ($q) use ($data) {
                if (!empty($data['phone'])) {
                    $q->orWhere('phone', $data['phone']);
                }
                if (!empty($data['email'])) {
                    $q->orWhere('email', $data['email']);
                }
            })
            ->orderByDesc('id')
            ->first();

        $rsvp = $existing ?? new Rsvp(['event_id' => $event->id]);

        // Always store latest submitted values
        $rsvp->fill([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'attendance_status' => $data['attendance_status'],
            'session_id' => $data['session_id'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'submitted_at' => now(),
        ]);

        $rsvp->save();

        return response()->json([
            'message' => $existing ? 'RSVP updated.' : 'RSVP submitted.',
            'rsvp' => [
                'id' => $rsvp->id,
                'attendance_status' => $rsvp->attendance_status,
                'session_id' => $rsvp->session_id,
                'submitted_at' => optional($rsvp->submitted_at)->toIso8601String(),
            ],
        ]);
    }
}
