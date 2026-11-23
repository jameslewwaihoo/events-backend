<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class HouseholdRsvpController extends Controller
{
    public function show(Request $request, Event $event)
    {
        $guest = $this->invitation($request, $event)->with('householdReply')->firstOrFail();

        return $this->response($event, $guest, $guest->householdReply);
    }

    public function update(Request $request, Event $event)
    {
        $invitation = $this->invitation($request, $event);

        return DB::transaction(function () use ($request, $event, $invitation) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            $guest = $invitation->lockForUpdate()->firstOrFail();

            if ($event->privateRsvpIsClosed()) {
                return response()->json(['message' => 'RSVP is closed.'], 422);
            }

            $data = $request->validate($this->replyRules($guest->party_size));
            $reply = $guest->householdReply()->firstOrNew();
            if (!$reply->exists && $this->hasPriorReply($event, [
                'email' => $this->normalizeContact('email', $guest->email),
                'phone' => $this->normalizeContact('phone', $guest->phone),
            ])) {
                return response()->json(['message' => 'An RSVP has already been submitted with this phone number or email.'], 409);
            }

            $reply->household_reply = true;
            $reply->fill($this->replyAttributes($event, $guest->name, $data));
            $guest->householdReply()->save($reply);

            return $this->response($event, $guest, $reply);
        }, 5);
    }

    public function submitShared(Request $request, Event $event)
    {
        abort_unless($event->status === Event::STATUS_PUBLISHED && $event->private_rsvp, 404);

        if ($event->privateRsvpIsClosed()) {
            return response()->json(['code' => 'closed'], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['required_if:attendance_status,attending', 'nullable', 'string', 'max:255'],
            'phone_country' => ['nullable', 'string', Rule::in(['60', '65'])],
        ] + $this->replyRules($event->sharedRsvpSeatLimit()));
        $contacts = [];
        if (isset($data['contact'])) {
            $country = $data['phone_country'] ?? null;
            $field = !$country && str_contains($data['contact'], '@') ? 'email' : 'phone';
            $contact = $field === 'phone' ? Guest::normalizedRsvpMobile($data['contact'], $country)
                : $this->normalizeContact($field, $data['contact']);
            if ($contact === null) {
                return response()->json(['code' => $country ? 'invalid_mobile' : 'invalid_contact'], 422);
            }
            if ($field === 'email' && Validator::make(['contact' => $contact], ['contact' => 'email'])->fails()) {
                return response()->json(['code' => 'invalid_contact'], 422);
            }
            $contacts[$field] = $contact;
        }

        return DB::transaction(function () use ($event, $contacts, $data) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();
            abort_unless($event->status === Event::STATUS_PUBLISHED && $event->private_rsvp, 404);
            if ($event->privateRsvpIsClosed()) {
                return response()->json(['code' => 'closed'], 422);
            }

            if ($this->hasPriorReply($event, $contacts)) {
                return response()->json(['code' => 'duplicate_contact'], 409)->header('Cache-Control', 'no-store');
            }

            $reply = new Rsvp($this->replyAttributes($event, $data['name'], $data) + $contacts);
            $reply->household_reply = true;
            $reply->save();

            return response()->json($reply->only(['attendance_status', 'confirmed_seats', 'halal_meal']))
                ->header('Cache-Control', 'no-store');
        }, 5);
    }

    private function normalizeContact(string $field, ?string $value): ?string
    {
        return $field === 'phone'
            ? (Guest::normalizedRsvpMobile($value) ?? Guest::normalizedRsvpPhone($value))
            : mb_strtolower(trim($value ?? ''));
    }

    private function hasPriorReply(Event $event, array $contacts): bool
    {
        $contacts = array_filter($contacts);
        if (!$contacts) {
            return false;
        }

        return $event->rsvps()->with('guest')->lazyById()->contains(function (Rsvp $reply) use ($contacts) {
            foreach ($contacts as $field => $contact) {
                if ($this->normalizeContact($field, $reply->{$field}) === $contact
                    || ($reply->household_reply && $this->normalizeContact($field, optional($reply->guest)->{$field}) === $contact)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function replyRules(int $seatLimit): array
    {
        return [
            'attendance_status' => ['required', Rule::in([Rsvp::STATUS_ATTENDING, Rsvp::STATUS_DECLINED])],
            'confirmed_seats' => [
                'exclude_unless:attendance_status,attending', 'required', 'integer', 'min:1', 'max:'.$seatLimit,
            ],
            'halal_meal' => ['exclude_unless:attendance_status,attending', 'nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function replyAttributes(Event $event, string $name, array $data): array
    {
        $attributes = [
            'event_id' => $event->id,
            'name' => $name,
            'attendance_status' => $data['attendance_status'],
            'confirmed_seats' => $data['confirmed_seats'] ?? 0,
            'remarks' => $data['remarks'] ?? null,
            'submitted_at' => now(),
        ];

        if ($data['attendance_status'] === Rsvp::STATUS_DECLINED || array_key_exists('halal_meal', $data)) {
            $attributes['halal_meal'] = $data['halal_meal'] ?? null;
        }

        return $attributes;
    }

    private function invitation(Request $request, Event $event): Builder
    {
        abort_unless($event->status === Event::STATUS_PUBLISHED && $event->private_rsvp, 404);

        $token = $request->bearerToken();
        abort_unless($token && preg_match('/\A[a-f0-9]{64}\z/', $token), 404);

        return $event->guests()->where('invitation_token', $token)->getQuery();
    }

    private function response(Event $event, Guest $guest, ?Rsvp $reply)
    {
        return response()->json([
            'name' => $guest->name,
            'seat_limit' => (int) $guest->party_size,
            'closed' => $event->privateRsvpIsClosed(),
            'reply' => $reply ? $reply->only(['attendance_status', 'confirmed_seats', 'remarks', 'halal_meal']) : null,
        ])->header('Cache-Control', 'no-store, private');
    }
}
