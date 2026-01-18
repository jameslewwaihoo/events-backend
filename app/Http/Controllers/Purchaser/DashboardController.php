<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Event;
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

        $eventIds = $eventsQuery->pluck('id');
        $rsvpQuery = Rsvp::query()->whereIn('event_id', $eventIds);
        $rsvpStats = [
            'total' => (clone $rsvpQuery)->count(),
            'attending' => (clone $rsvpQuery)->where('attendance_status', 'attending')->count(),
            'declined' => (clone $rsvpQuery)->where('attendance_status', 'declined')->count(),
        ];

        return view('purchaser.dashboard', [
            'stats' => $stats,
            'upcoming' => $upcoming,
            'recent' => $recent,
            'rsvpStats' => $rsvpStats,
            'firstEventId' => $firstEventId,
        ]);
    }
}
