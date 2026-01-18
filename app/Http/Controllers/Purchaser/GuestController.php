<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);

        $guests = Guest::query()
            ->where('event_id', $event->id)
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('purchaser.guests.index', [
            'event' => $event,
            'guests' => $guests,
        ]);
    }

    public function edit(Event $event, Guest $guest)
    {
        $this->authorize('manageGuests', $event);

        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        return view('purchaser.guests.edit', [
            'event' => $event,
            'guest' => $guest,
        ]);
    }

    public function update(Request $request, Event $event, Guest $guest)
    {
        $this->authorize('manageGuests', $event);

        if ($guest->event_id !== $event->id) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'rsvp_status' => ['nullable', 'in:pending,yes,no,maybe'],
            'notes' => ['nullable', 'string'],
            'meal_choice' => ['nullable', 'string', 'max:255'],
        ]);

        // Only allow meal choice if meals feature is enabled
        if (!$event->featureEnabled('meals_enabled')) {
            unset($data['meal_choice']);
        }

        $guest->fill($data);
        $guest->save();

        return redirect()
            ->route('purchaser.guests.index', $event->id)
            ->with('status', 'Guest updated.');
    }
}
