<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
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

        $query = $event->reportableRsvps()
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
        $pendingInvitations = $event->private_rsvp
            ? $event->guests()->invited()->whereDoesntHave('householdReply')->count()
            : null;
        $confirmedSeats = $event->private_rsvp
            ? $event->reportableRsvps()->where('attendance_status', 'attending')->sum('confirmed_seats')
            : null;

        return view('admin.events.rsvps.index', [
            'event'    => $event,
            'sessions' => $sessions,
            'rsvps'    => $rsvps,
            'pendingInvitations' => $pendingInvitations,
            'confirmedSeats' => $confirmedSeats,
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

        $query = $event->reportableRsvps()
            ->with(['session']);

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($status) {
            $query->where('attendance_status', $status);
        }

        $filename = 'event_' . $event->id . '_rsvps_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query, $event) {
            $out = fopen('php://output', 'w');

            // CSV Header
            $headers = [
                'attendance_status',
                'name',
                'email',
                'phone',
                'session',
                'remarks',
                'submitted_at',
            ];
            if ($event->private_rsvp) {
                $headers[] = 'confirmed_seats';
                $headers[] = 'halal_meal';
            }
            fputcsv($out, $headers, ',', '"', '');

            // Stream rows (chunk to avoid memory spikes)
            $query->orderByDesc('submitted_at')->orderByDesc('id')
                ->chunk(500, function ($rows) use ($out, $event) {
                    foreach ($rows as $r) {
                        $row = [
                            $r->attendance_status,
                            $r->name,
                            $r->email,
                            $r->phone,
                            optional($r->session)->name,
                            $r->remarks,
                            optional($r->submitted_at)->toDateTimeString(),
                        ];
                        if ($event->private_rsvp) {
                            $row[] = $r->confirmed_seats;
                            $row[] = match ($r->halal_meal) {
                                true => 'Yes',
                                false => 'No',
                                null => '',
                            };
                        }
                        $row = array_map(static function ($value) {
                            return is_string($value) && preg_match('/^(?:[\t\r\n]|\s*[=+@-])/u', $value)
                                ? "'" . $value
                                : $value;
                        }, $row);
                        fputcsv($out, $row, ',', '"', '');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
