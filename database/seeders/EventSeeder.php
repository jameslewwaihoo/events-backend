<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rsvp;
use App\Models\Guest;
use App\Models\EventSession;
use App\Models\Event;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    public function run(): void
    {
                // Clear tables in FK-safe order (children → parents)
        Rsvp::query()->delete();
        Guest::query()->delete();
        EventSession::query()->delete();
        Event::query()->delete();

        // 1) Published event: demo
        $event = Event::create([
            'slug'            => 'demo',
            'title'           => 'Demo Wedding Celebration',
            'event_date'      => Carbon::create(2026, 3, 21),
            'venue_name'      => 'The Grand Ballroom',
            'venue_address'   => '123 Orchard Road, Singapore',
            'venue_map_url'   => 'https://maps.google.com',
            'welcome_message' => 'We are delighted to celebrate this special day with you.',
            'rsvp_deadline'   => Carbon::now()->addWeeks(4),
            'status'          => 'published',
        ]);

        // Sessions (sorted by sort_order)
        $ceremony = EventSession::create([
            'event_id'   => $event->id,
            'name'       => 'Solemnization Ceremony',
            'start_at'   => Carbon::create(2026, 3, 21, 10, 30),
            'location'   => 'Garden Pavilion',
            'sort_order' => 1,
        ]);

        $dinner = EventSession::create([
            'event_id'   => $event->id,
            'name'       => 'Wedding Dinner',
            'start_at'   => Carbon::create(2026, 3, 21, 19, 0),
            'location'   => 'Grand Ballroom',
            'sort_order' => 2,
        ]);

        // Optional: some guests
        $guestA = Guest::create([
            'event_id'     => $event->id,
            'name'         => 'Alice Tan',
            'email'        => 'alice@example.com',
            'phone'        => '91234567',
            'invite_code'  => 'ALICE123',
            'status'       => 'active',
        ]);

        $guestB = Guest::create([
            'event_id'     => $event->id,
            'name'         => 'Ben Lim',
            'email'        => 'ben@example.com',
            'phone'        => '98765432',
            'invite_code'  => 'BEN456',
            'status'       => 'active',
        ]);

        // Optional: some RSVPs (mix attending/declined)
        Rsvp::create([
            'event_id'          => $event->id,
            'guest_id'          => $guestA->id,
            'session_id'        => $dinner->id,
            'attendance_status' => 'attending',
            'name'              => 'Alice Tan',
            'email'             => 'alice@example.com',
            'phone'             => '91234567',
            'remarks'           => 'Excited!',
            'submitted_at'      => now(),
        ]);

        Rsvp::create([
            'event_id'          => $event->id,
            'guest_id'          => $guestB->id,
            'session_id'        => $ceremony->id,
            'attendance_status' => 'declined',
            'name'              => 'Ben Lim',
            'email'             => 'ben@example.com',
            'phone'             => '98765432',
            'remarks'           => 'Sorry, overseas.',
            'submitted_at'      => now(),
        ]);

        // 2) Draft event (should not be visible via public API)
        Event::create([
            'slug'       => 'draft-event',
            'title'      => 'Draft Event (Hidden)',
            'event_date' => Carbon::create(2026, 6, 1),
            'status'     => 'draft',
        ]);
    }
}
