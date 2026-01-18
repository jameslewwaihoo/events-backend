<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);

        $rsvps = Rsvp::query()
            ->where('event_id', $event->id)
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

        if ($rsvp->event_id !== $event->id) {
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

        if ($rsvp->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'attendance_status' => ['required', 'in:attending,declined'],
            'remarks' => ['nullable', 'string'],
        ]);

        $rsvp->fill($data);
        $rsvp->save();

        return redirect()
            ->route('purchaser.rsvps.index', $event->id)
            ->with('status', 'RSVP updated.');
    }
}
