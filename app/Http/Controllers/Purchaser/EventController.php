<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->where('user_id', $request->user()->id)
            ->with('package')
            ->orderByDesc('event_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('purchaser.events.index', [
            'events' => $events,
        ]);
    }

    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return view('purchaser.events.edit', [
            'event' => $event,
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $data = $this->validateEvent($request);
        $event->update($data);

        return redirect()
            ->route('purchaser.events.edit', $event->id)
            ->with('status', 'Event updated.');
    }

    private function validateEvent(Request $request): array
    {
        // Purchasers can only manage safe content fields, not package/status/slug.
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'rsvp_deadline' => ['nullable', 'date'],
            'venue_name' => ['nullable', 'string', 'max:255'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'venue_map_url' => ['nullable', 'url', 'max:255'],
            'welcome_message' => ['nullable', 'string'],
        ]);
    }
}
