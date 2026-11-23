<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuestController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);

        $guests = $event->guests()
            ->with('householdReply')
            ->when($event->private_rsvp, fn ($query) => $query->latest('id'), fn ($query) => $query->orderBy('name'))
            ->paginate(25)
            ->withQueryString();

        return response()->view('purchaser.guests.index', [
            'event' => $event,
            'guests' => $guests,
            'invitationWebUrl' => rtrim((string) config('services.invitation.web_url'), '/'),
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);
        abort_unless($event->private_rsvp, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'party_size' => ['required', 'integer', 'min:1', 'max:65535'],
        ] + $this->contactRules());

        $event->guests()->create($data);

        return redirect()->route('purchaser.guests.index', $event->id)
            ->with('status', 'Guest added.');
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
            'rsvp_status' => ['nullable', 'in:pending,yes,no,maybe'],
            'notes' => ['nullable', 'string'],
            'meal_choice' => ['nullable', 'string', 'max:255'],
            'party_size' => $event->private_rsvp ? ['required', 'integer', 'min:1', 'max:65535'] : ['exclude'],
        ] + ($event->private_rsvp ? $this->contactRules() : [
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]));

        // Only allow meal choice if meals feature is enabled
        if (!$event->featureEnabled('meals_enabled')) {
            unset($data['meal_choice']);
        }

        DB::transaction(function () use ($event, $guest, $data) {
            $guest = Guest::whereKey($guest->id)->lockForUpdate()->firstOrFail();
            if ($event->private_rsvp) {
                unset($data['rsvp_status']);
                $confirmedSeats = (int) $guest->householdReply()->value('confirmed_seats');
                if ($data['party_size'] < $confirmedSeats) {
                    throw ValidationException::withMessages(['party_size' => 'The seat limit cannot be lower than the confirmed headcount.']);
                }
            }

            $guest->fill($data)->save();
            if ($event->private_rsvp && $guest->wasChanged('name')) {
                $guest->householdReply()->update(['name' => $guest->name]);
            }
        }, 5);

        return redirect()
            ->route('purchaser.guests.index', $event->id)
            ->with('status', 'Guest updated.');
    }

    private function contactRules(): array
    {
        return [
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50',
                function ($attribute, $phone, $fail) {
                    if (is_string($phone) && Guest::normalizedRsvpPhone($phone) === null) {
                        $fail('Enter a valid phone number, including the country code if needed.');
                    }
                },
            ],
        ];
    }
}
