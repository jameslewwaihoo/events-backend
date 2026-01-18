<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Rsvp;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventRsvpController extends Controller
{
    public function index(Request $request, Event $event)
    {
        $this->authorize('manageGuests', $event);

        // Filters
        $sessionId = $request->get('session_id');
        $status    = $request->get('attendance_status'); // attending|declined

        $query = Rsvp::query()
            ->where('event_id', $event->id)
            ->with(['session']); // assuming Rsvp belongsTo EventSession as "session"

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($status) {
            $query->where('attendance_status', $status);
        }

        $rsvps = $query
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $sessions = $event->sessions()->orderBy('start_at')->get();

        return view('admin.events.rsvps.index', [
            'event'    => $event,
            'sessions' => $sessions,
            'rsvps'    => $rsvps,
            'filters'  => [
                'session_id'         => $sessionId,
                'attendance_status'  => $status,
            ],
        ]);
    }

    public function exportCsv(Request $request, Event $event): StreamedResponse
    {
        $this->authorize('manageGuests', $event);

        $sessionId = $request->get('session_id');
        $status    = $request->get('attendance_status');

        $query = Rsvp::query()
            ->where('event_id', $event->id)
            ->with(['session']);

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($status) {
            $query->where('attendance_status', $status);
        }

        $filename = 'event_' . $event->id . '_rsvps_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            // CSV Header
            fputcsv($out, [
                'attendance_status',
                'name',
                'email',
                'phone',
                'session',
                'remarks',
                'submitted_at',
            ]);

            // Stream rows (chunk to avoid memory spikes)
            $query->orderByDesc('submitted_at')->orderByDesc('id')
                ->chunk(500, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->attendance_status,
                            $r->name,
                            $r->email,
                            $r->phone,
                            optional($r->session)->name,
                            $r->remarks,
                            optional($r->submitted_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
