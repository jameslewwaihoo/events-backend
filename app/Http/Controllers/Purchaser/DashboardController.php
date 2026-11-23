<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Guest;
use App\Models\Rsvp;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $eventsQuery = Event::query()->where('user_id', $userId);

        $stats = [
            'total' => (clone $eventsQuery)->count(),
            'published' => (clone $eventsQuery)->where('status', 'published')->count(),
            'draft' => (clone $eventsQuery)->where('status', 'draft')->count(),
        ];

        $upcoming = (clone $eventsQuery)
            ->whereNotNull('event_date')
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $recent = (clone $eventsQuery)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        $firstEventId = (clone $eventsQuery)->orderBy('event_date')->orderBy('id')->value('id');

        $privateEventIds = (clone $eventsQuery)->where('private_rsvp', true)->pluck('id');
        $publicEventIds = (clone $eventsQuery)->where('private_rsvp', false)->pluck('id');
        $rsvpQuery = Rsvp::query()->where(function ($query) use ($publicEventIds, $privateEventIds) {
            $query->where(fn ($public) => $public->whereIn('event_id', $publicEventIds)->whereNull('household_reply'))
                ->orWhere(fn ($private) => $private->whereIn('event_id', $privateEventIds)->where('household_reply', true));
        });
        $rsvpStats = [
            'total' => (clone $rsvpQuery)->count(),
            'attending' => (clone $rsvpQuery)->where('attendance_status', 'attending')->count(),
            'declined' => (clone $rsvpQuery)->where('attendance_status', 'declined')->count(),
            'confirmed_seats' => Rsvp::whereIn('event_id', $privateEventIds)
                ->where('household_reply', true)->where('attendance_status', 'attending')->sum('confirmed_seats'),
            'pending_invitations' => Guest::whereIn('event_id', $privateEventIds)
                ->invited()->whereDoesntHave('householdReply')->count(),
        ];

        return view('purchaser.dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'rsvpStats' => $rsvpStats,
            'firstEventId' => $firstEventId,
            'hasPrivateEvents' => $privateEventIds->isNotEmpty(),
        ]);
    }
}
