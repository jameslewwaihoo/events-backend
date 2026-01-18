<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rsvp;
use App\Models\Guest;
use App\Models\EventSession;
use App\Models\Event;
use App\Models\User;
use App\Models\EventPackage;
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

        // Create sample purchaser users
        $basicPurchaser = User::firstOrCreate(
            ['email' => 'basic@event.test'],
            [
                'name' => 'Basic Purchaser',
                'password' => bcrypt('password'),
                'is_admin' => 0,
                'role' => 'purchaser',
            ]
        );
        $midPurchaser = User::firstOrCreate(
            ['email' => 'mid@event.test'],
            [
                'name' => 'Mid Purchaser',
                'password' => bcrypt('password'),
                'is_admin' => 0,
                'role' => 'purchaser',
            ]
        );
        $premiumPurchaser = User::firstOrCreate(
            ['email' => 'premium@event.test'],
            [
                'name' => 'Premium Purchaser',
                'password' => bcrypt('password'),
                'is_admin' => 0,
                'role' => 'purchaser',
            ]
        );

        $basic = EventPackage::where('code', 'basic')->first();
        $mid = EventPackage::where('code', 'mid')->first();
        $premium = EventPackage::where('code', 'premium')->first();

        // 1) Basic package draft
        Event::create([
            'user_id'        => $basicPurchaser->id,
            'slug'            => 'basic-wedding',
            'title'           => 'Basic Package Wedding',
            'event_type'      => 'wedding',
            'event_package_id'=> optional($basic)->id,
            'event_date'      => Carbon::create(2026, 2, 14),
            'venue_name'      => 'City Hall',
            'venue_address'   => '1 City Hall St',
            'welcome_message' => 'Join us for a simple celebration.',
            'rsvp_deadline'   => Carbon::now()->addWeeks(3),
            'status'          => 'draft',
        ]);

        // 2) Mid package published
        $midEvent = Event::create([
            'user_id'        => $midPurchaser->id,
            'slug'            => 'mid-wedding',
            'title'           => 'Mid Package Wedding',
            'event_type'      => 'wedding',
            'event_package_id'=> optional($mid)->id,
            'event_date'      => Carbon::create(2026, 4, 10),
            'venue_name'      => 'Lakeside Pavilion',
            'venue_address'   => '22 Lake Rd',
            'welcome_message' => 'We can’t wait to celebrate with you.',
            'rsvp_deadline'   => Carbon::now()->addWeeks(5),
            'status'          => 'published',
            'published_at'    => now(),
        ]);

        // Sessions for mid package
        $midSession = EventSession::create([
            'event_id'   => $midEvent->id,
            'name'       => 'Dinner',
            'start_at'   => Carbon::create(2026, 4, 10, 18, 0),
            'location'   => 'Main Hall',
            'sort_order' => 1,
        ]);

        // Guests and RSVPs for mid package
        $midGuest = Guest::create([
            'event_id'     => $midEvent->id,
            'name'         => 'Mia Guest',
            'email'        => 'mia@example.com',
            'phone'        => '81112222',
            'invite_code'  => 'MIA123',
            'status'       => 'active',
        ]);

        Rsvp::create([
            'event_id'          => $midEvent->id,
            'guest_id'          => $midGuest->id,
            'session_id'        => $midSession->id,
            'attendance_status' => 'attending',
            'name'              => $midGuest->name,
            'email'             => $midGuest->email,
            'phone'             => $midGuest->phone,
            'remarks'           => 'Excited!',
            'submitted_at'      => now(),
        ]);

        // 3) Premium package published
        $premiumEvent = Event::create([
            'user_id'        => $premiumPurchaser->id,
            'slug'            => 'premium-wedding',
            'title'           => 'Premium Package Wedding',
            'event_type'      => 'wedding',
            'event_package_id'=> optional($premium)->id,
            'event_date'      => Carbon::create(2026, 6, 1),
            'venue_name'      => 'Grand Ballroom',
            'venue_address'   => '88 Orchard Rd',
            'welcome_message' => 'Full-service celebration awaits.',
            'rsvp_deadline'   => Carbon::now()->addWeeks(6),
            'status'          => 'published',
            'published_at'    => now(),
        ]);

        // Sessions for premium package
        $premiumCeremony = EventSession::create([
            'event_id'   => $premiumEvent->id,
            'name'       => 'Solemnization',
            'start_at'   => Carbon::create(2026, 6, 1, 11, 0),
            'location'   => 'Garden',
            'sort_order' => 1,
        ]);
        $premiumDinner = EventSession::create([
            'event_id'   => $premiumEvent->id,
            'name'       => 'Dinner',
            'start_at'   => Carbon::create(2026, 6, 1, 19, 0),
            'location'   => 'Ballroom',
            'sort_order' => 2,
        ]);

        // Guests and RSVPs for premium package
        $premiumGuest = Guest::create([
            'event_id'     => $premiumEvent->id,
            'name'         => 'Priya Guest',
            'email'        => 'priya@example.com',
            'phone'        => '89998888',
            'invite_code'  => 'PRIYA123',
            'status'       => 'active',
        ]);

        Rsvp::create([
            'event_id'          => $premiumEvent->id,
            'guest_id'          => $premiumGuest->id,
            'session_id'        => $premiumDinner->id,
            'attendance_status' => 'attending',
            'name'              => $premiumGuest->name,
            'email'             => $premiumGuest->email,
            'phone'             => $premiumGuest->phone,
            'remarks'           => 'Plus-one included.',
            'submitted_at'      => now(),
        ]);
    }
}
