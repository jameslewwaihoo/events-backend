<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RsvpController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);

        $rsvps = $event->reportableRsvps()
            ->with(['session'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('purchaser.rsvps.index', [
            'event' => $event,
            'rsvps' => $rsvps,
        ]);
    }

    public function edit(Event $event, Rsvp $rsvp)
    {
        $this->authorize('manageGuests', $event);

        if ($rsvp->event_id !== $event->id || ($event->private_rsvp && !$rsvp->household_reply)) {
            abort(404);
        }

        return view('purchaser.rsvps.edit', [
            'event' => $event,
            'rsvp' => $rsvp,
        ]);
    }

    public function update(Request $request, Event $event, Rsvp $rsvp)
    {
        $this->authorize('manageGuests', $event);

        if ($rsvp->event_id !== $event->id || ($event->private_rsvp && !$rsvp->household_reply)) {
            abort(404);
        }

        $data = $request->validate([
            'attendance_status' => ['required', 'in:attending,declined'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($rsvp, $request, $data) {
            if ($rsvp->household_reply) {
                $guest = $rsvp->guest_id ? Guest::whereKey($rsvp->guest_id)->lockForUpdate()->first() : null;
                $headcount = $request->validate([
                    'confirmed_seats' => [
                        'exclude_unless:attendance_status,attending', 'required', 'integer', 'min:1', 'max:'.($guest->party_size ?? 65535),
                    ],
                    'halal_meal' => ['exclude_unless:attendance_status,attending', 'nullable', 'boolean'],
                ]);
                $data['confirmed_seats'] = $headcount['confirmed_seats'] ?? 0;
                if ($data['attendance_status'] === Rsvp::STATUS_DECLINED || array_key_exists('halal_meal', $headcount)) {
                    $data['halal_meal'] = $headcount['halal_meal'] ?? null;
                }
            }
            $rsvp->fill($data)->save();
        }, 5);

        return redirect()
            ->route('purchaser.rsvps.index', $event->id)
            ->with('status', 'RSVP updated.');
    }
}
