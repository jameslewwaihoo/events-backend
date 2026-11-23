<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Guest;
use App\Models\Rsvp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdRsvpTest extends TestCase
{
    use RefreshDatabase;

    public function test_households_can_reply_and_change_headcount_using_their_personal_link()
    {
        $event = $this->event();
        $solo = $this->invite($event, 'Solo Guest', 1);
        $couple = $this->invite($event, 'The Couple', 2);
        $family = $this->invite($event, 'The Family', 4);

        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $this->getJson($url, $this->headers($family))
            ->assertOk()
            ->assertJson(['name' => 'The Family', 'seat_limit' => 4, 'closed' => false, 'reply' => null]);

        $this->reply($event, $solo, 'attending', 1)->assertOk();
        $this->reply($event, $couple, 'attending', 1)->assertOk();
        $this->reply($event, $family, 'attending', 5)->assertJsonValidationErrors('confirmed_seats');
        $this->reply($event, $family, 'attending', 4)->assertOk();
        $firstId = $family->householdReply()->firstOrFail()->id;
        $this->reply($event, $family, 'attending', 2)->assertOk();

        $this->getJson($url, $this->headers($family))
            ->assertOk()->assertJsonPath('reply.confirmed_seats', 2);
        $this->assertSame($firstId, $family->householdReply()->firstOrFail()->id);
        $this->assertSame(3, $event->reportableRsvps()->count());
        $this->assertEquals(4, $event->reportableRsvps()->sum('confirmed_seats'));

        $this->reply($event, $family, 'declined', 2)->assertOk()->assertJsonPath('reply.confirmed_seats', 0);
        $this->assertEquals(2, $event->reportableRsvps()->sum('confirmed_seats'));
    }

    public function test_private_replies_require_the_right_event_link_and_respect_the_local_deadline()
    {
        $event = $this->event(['rsvp_deadline' => '2027-09-10']);
        $guest = $this->invite($event, 'Private Guest', 2);
        $otherEvent = $this->event(['slug' => 'another-event']);
        $url = "/api/public/events/{$event->slug}";

        $this->getJson("$url/rsvp?email=guest@example.test")->assertNotFound();
        $this->postJson("$url/rsvp", ['name' => 'Test', 'attendance_status' => 'attending'])->assertNotFound();
        $this->getJson("$url/household-rsvp")->assertNotFound();
        $this->getJson("/api/public/events/{$otherEvent->slug}/household-rsvp", $this->headers($guest))->assertNotFound();

        try {
            \Carbon\Carbon::setTestNow('2027-09-10 15:59:00 UTC');
            $this->reply($event, $guest, 'attending', 2)->assertOk();
            \Carbon\Carbon::setTestNow('2027-09-10 16:00:00 UTC');
            $this->reply($event, $guest, 'declined', 0)->assertStatus(422);
            $this->getJson("$url/household-rsvp", $this->headers($guest))
                ->assertOk()->assertJsonPath('closed', true)->assertJsonPath('reply.confirmed_seats', 2);
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_the_admin_can_create_invitations_and_export_confirmed_seats()
    {
        config(['services.invitation.web_url' => 'http://localhost:5173']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->get('/login')->assertOk();
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect('/dashboard');
        $this->get('/admin/events/create')->assertOk()->assertSee('Use the shared RSVP form with a guest count');
        $this->post('/admin/events', [
            'user_id' => $admin->id,
            'event_type' => 'wedding',
            'title' => 'Wedding Invitation',
            'slug' => 'wedding',
            'status' => 'published',
            'event_date' => '2027-09-25',
            'private_rsvp' => '1',
        ])->assertRedirect('/admin/events');
        $event = Event::where('slug', 'wedding')->firstOrFail();
        $path = "/my-events/{$event->id}/guests";
        $this->post($path, ['name' => 'Family Guest', 'party_size' => 4])->assertRedirect($path);
        $family = $event->guests()->firstOrFail();
        $family->issueInvitation();
        $family->save();
        $this->invite($event, 'Awaiting Reply', 2);

        $this->get($path)->assertOk()->assertSee('data-invitation-url="http://localhost:5173/e/wedding"', false)
            ->assertDontSee('#invite=');
        $this->reply($event, $family, 'attending', 3)->assertOk();

        $this->put("$path/{$family->id}", ['name' => $family->name, 'party_size' => 2, 'email' => $family->email])
            ->assertSessionHasErrors('party_size');

        $rsvpPath = "/admin/events/{$event->id}/rsvps";
        $this->get($rsvpPath)->assertOk()->assertSeeInOrder(['Confirmed seats', '<dd>3</dd>', 'Pending invitations', '<dd>1</dd>'], false);
        $this->get('/dashboard')->assertOk()->assertSee('3 confirmed seats')->assertSee('1 pending invitations');
        $csv = $this->get("$rsvpPath/export")->assertOk()->streamedContent();
        $this->assertStringContainsString("submitted_at,confirmed_seats,halal_meal\n", $csv);
        $this->assertMatchesRegularExpression('/^attending,"Family Guest",.*?,3,$/m', $csv);

        $reply = $family->householdReply()->firstOrFail();
        $this->put("/my-events/{$event->id}/rsvps/{$reply->id}", ['attendance_status' => 'declined'])
            ->assertRedirect("/my-events/{$event->id}/rsvps");
        $this->assertSame(0, $reply->fresh()->confirmed_seats);

        $this->actingAs(User::factory()->create())->get($path)->assertForbidden();
    }

    public function test_shared_rsvp_accepts_new_guests_and_blocks_repeat_contacts_without_overwriting()
    {
        $event = $this->event();
        $event->guests()->create(['name' => 'Pending Guest', 'party_size' => 1, 'phone' => '+60 12-345 6789']);
        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $reply = ['name' => 'New Guest', 'contact' => '0060 (12) 345-6789', 'attendance_status' => 'attending', 'confirmed_seats' => 3];

        $this->getJson($url)->assertNotFound();
        $this->postJson($url, ['contact' => 'bad contact'] + $reply)->assertStatus(422)->assertExactJson(['code' => 'invalid_contact']);
        $this->postJson($url, $reply)->assertOk()->assertExactJson(['attendance_status' => 'attending', 'confirmed_seats' => 3, 'halal_meal' => null]);
        $this->postJson($url, ['name' => 'Another Guest', 'contact' => '+60 12-345 6789', 'attendance_status' => 'declined'] + $reply)
            ->assertStatus(409)->assertExactJson(['code' => 'duplicate_contact']);
        $saved = $event->reportableRsvps()->sole();
        $this->assertSame('New Guest', $saved->name);
        $this->assertSame(3, $saved->confirmed_seats);
        $this->assertSame('60123456789', $saved->phone);
        $this->assertNull($saved->guest_id);

        $this->postJson($url, ['contact' => 'New@EXAMPLE.test', 'attendance_status' => 'declined'] + $reply)
            ->assertOk()->assertJsonPath('confirmed_seats', 0);
        $this->postJson($url, ['contact' => 'new@example.test'] + $reply)->assertStatus(409);
        $this->assertSame(2, $event->reportableRsvps()->count());
        $other = $this->event(['slug' => 'another-wedding']);
        $this->postJson("/api/public/events/{$other->slug}/household-rsvp", $reply)->assertOk();
        $event->update(['rsvp_deadline' => '2000-01-01']);
        $this->postJson($url, $reply)->assertStatus(422)->assertExactJson(['code' => 'closed']);
    }

    public function test_shared_and_legacy_replies_are_deduplicated_and_admin_can_edit_shared_replies()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event(['user_id' => $admin->id]);
        $legacy = $this->invite($event, 'Legacy Guest', 4);
        $legacy->update(['email' => 'legacy@example.test']);
        $this->reply($event, $legacy, 'attending', 2)->assertOk();
        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $reply = ['name' => 'Guest', 'contact' => 'LEGACY@example.test', 'attendance_status' => 'attending', 'confirmed_seats' => 2];
        $this->postJson($url, $reply)->assertStatus(409);

        $newGuest = $this->invite($event, 'New Guest', 4);
        $newGuest->update(['email' => 'new@example.test']);
        $this->postJson($url, ['contact' => $newGuest->email] + $reply)->assertOk();
        $this->reply($event, $newGuest, 'attending', 2)->assertStatus(409);
        $this->reply($event, $legacy, 'attending', 3)->assertOk();

        $submitted = $event->reportableRsvps()->where('email', $newGuest->email)->sole();
        $adminPage = "/my-events/{$event->id}/rsvps/{$submitted->id}";
        $this->actingAs($admin)->get("$adminPage/edit")->assertOk()->assertSee($newGuest->email);
        $this->put($adminPage, ['attendance_status' => 'declined'])->assertRedirect("/my-events/{$event->id}/rsvps");
        $this->assertSame(0, $submitted->fresh()->confirmed_seats);
    }

    public function test_shared_rsvp_validates_mobile_numbers_for_the_selected_country()
    {
        $event = $this->event();
        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $reply = ['name' => 'Guest', 'attendance_status' => 'declined'];

        foreach ([
            ['60', '012-345 6789', '60123456789'],
            ['60', '+60 11-1234 5678', '601112345678'],
            ['65', '8123 4567', '6581234567'],
            ['65', '+65 9123 4567', '6591234567'],
        ] as [$country, $contact, $savedPhone]) {
            $this->postJson($url, $reply + ['contact' => $contact, 'phone_country' => $country])->assertOk();
            $this->assertDatabaseHas('rsvps', ['event_id' => $event->id, 'phone' => $savedPhone]);
        }

        foreach ([
            ['60', '03-1234 5678'],
            ['60', '011-123 4567'],
            ['65', '6123 4567'],
            ['65', '9123 456'],
            ['60', '+65 9234 5678'],
        ] as [$country, $contact]) {
            $this->postJson($url, $reply + ['contact' => $contact, 'phone_country' => $country])
                ->assertStatus(422)->assertExactJson(['code' => 'invalid_mobile']);
        }

        $this->postJson($url, $reply + ['contact' => '0060 12-345 6789', 'phone_country' => '60'])
            ->assertStatus(409)->assertExactJson(['code' => 'duplicate_contact']);
        $this->assertSame(4, $event->reportableRsvps()->count());
    }

    public function test_shared_rsvp_uses_the_configured_limit_from_public_event_details()
    {
        config(['wedding.shared_rsvp_seat_limit' => 4]);
        $event = $this->event();
        $url = "/api/public/events/{$event->slug}";
        $data = ['name' => 'Guest', 'contact' => 'guest@example.test', 'attendance_status' => 'attending'];

        $this->getJson($url)->assertOk()->assertJsonPath('shared_rsvp_seat_limit', 4);
        $this->postJson("$url/household-rsvp", $data + ['confirmed_seats' => 5])
            ->assertUnprocessable()->assertJsonValidationErrors('confirmed_seats');
        $this->postJson("$url/household-rsvp", $data + ['confirmed_seats' => 4])
            ->assertOk()->assertJsonPath('confirmed_seats', 4);

        $household = $this->invite($event, 'Larger household', 7);
        $this->reply($event, $household, 'attending', 7)->assertOk();
    }

    public function test_rsvp_contact_is_required_only_when_attending()
    {
        $event = $this->event();
        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $declined = ['name' => 'Guest', 'attendance_status' => 'declined'];
        $attending = ['attendance_status' => 'attending', 'confirmed_seats' => 1] + $declined;

        $this->postJson($url, $attending)->assertJsonValidationErrors('contact');
        $this->postJson($url, $attending + ['contact' => ' '])->assertJsonValidationErrors('contact');
        $this->postJson($url, $declined)->assertOk()->assertJsonPath('confirmed_seats', 0);
        $this->postJson($url, $declined + ['contact' => '', 'phone_country' => '60'])->assertOk();
        $this->assertSame(2, $event->reportableRsvps()->whereNull('phone')->whereNull('email')->count());

        $this->postJson($url, $declined + ['contact' => '6123 4567', 'phone_country' => '65'])
            ->assertStatus(422)->assertExactJson(['code' => 'invalid_mobile']);
        $this->postJson($url, $attending + ['contact' => 'guest@example.test'])->assertOk();
        $this->postJson($url, $declined + ['contact' => 'GUEST@example.test'])
            ->assertStatus(409)->assertExactJson(['code' => 'duplicate_contact']);
    }

    public function test_existing_public_events_keep_their_rsvp_flow()
    {
        $event = $this->event(['private_rsvp' => false]);
        $url = "/api/public/events/{$event->slug}/rsvp";
        $reply = ['name' => 'Existing Guest', 'email' => 'guest@example.test', 'attendance_status' => 'attending'];

        $this->postJson($url, $reply)->assertOk()->assertJsonPath('mode', 'created');
        $this->getJson($url . '?email=guest@example.test')->assertOk()->assertJsonPath('data.attendance_status', 'attending');
        $this->postJson($url, array_replace($reply, ['attendance_status' => 'declined']))
            ->assertOk()->assertJsonPath('mode', 'updated');
        $this->assertSame(1, Rsvp::where('event_id', $event->id)->count());
    }

    public function test_halal_meal_preferences_are_optional_and_available_in_admin_reports()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->event(['user_id' => $admin->id]);
        $url = "/api/public/events/{$event->slug}/household-rsvp";
        $data = ['name' => 'Guest', 'attendance_status' => 'attending', 'confirmed_seats' => 2];
        $this->postJson($url, $data + ['contact' => 'yes@example.test', 'halal_meal' => 'maybe'])
            ->assertJsonValidationErrors('halal_meal');
        $this->postJson($url, $data + ['contact' => 'yes@example.test', 'halal_meal' => '1'])->assertOk()
            ->assertJsonPath('halal_meal', true);
        $this->postJson($url, $data + ['contact' => 'no@example.test', 'halal_meal' => '0'])->assertOk()
            ->assertJsonPath('halal_meal', false);
        $this->postJson($url, $data + ['contact' => 'omitted@example.test'])->assertOk();
        $this->postJson($url, ['attendance_status' => 'declined'] + $data + [
            'contact' => 'declined@example.test', 'halal_meal' => '1',
        ])->assertOk();

        $replies = $event->reportableRsvps()->get()->keyBy('email');
        $this->assertTrue($replies['yes@example.test']->halal_meal);
        $this->assertFalse($replies['no@example.test']->halal_meal);
        $this->assertNull($replies['omitted@example.test']->halal_meal);
        $this->assertNull($replies['declined@example.test']->halal_meal);

        $legacy = $this->invite($event, 'Legacy Guest', 4);
        $this->putJson($url, $data + ['halal_meal' => true], $this->headers($legacy))->assertOk()
            ->assertJsonPath('reply.halal_meal', true);
        $this->reply($event, $legacy, 'attending', 3)->assertOk()->assertJsonPath('reply.halal_meal', true);
        $this->getJson($url, $this->headers($legacy))->assertOk()->assertJsonPath('reply.halal_meal', true);

        $this->actingAs($admin)->get("/admin/events/{$event->id}/rsvps")->assertOk()->assertSee('Halal meal');
        $csv = $this->get("/admin/events/{$event->id}/rsvps/export")->assertOk()->streamedContent();
        $this->assertMatchesRegularExpression('/^attending,Guest,yes@example\.test,.*?,2,Yes$/m', $csv);
        $this->assertMatchesRegularExpression('/^attending,Guest,no@example\.test,.*?,2,No$/m', $csv);
        $editor = "/my-events/{$event->id}/rsvps/{$replies['no@example.test']->id}";
        $this->get("$editor/edit")->assertOk()->assertSee('Halal meal needed if attending');
        $this->put($editor, $data + ['halal_meal' => '1'])->assertRedirect();
        $this->assertTrue($replies['no@example.test']->fresh()->halal_meal);
        $this->put($editor, ['attendance_status' => 'declined', 'halal_meal' => '1'])->assertRedirect();
        $this->assertNull($replies['no@example.test']->fresh()->halal_meal);
    }

    private function event(array $attributes = []): Event
    {
        return Event::create(array_replace([
            'title' => 'Wedding Invitation',
            'slug' => 'wedding',
            'status' => Event::STATUS_PUBLISHED,
            'event_date' => '2027-09-25',
            'private_rsvp' => true,
        ], $attributes));
    }

    private function invite(Event $event, string $name, int $seats): Guest
    {
        $guest = $event->guests()->make(['name' => $name, 'party_size' => $seats]);
        $guest->issueInvitation();
        $guest->save();

        return $guest;
    }

    private function headers(Guest $guest): array
    {
        return ['Authorization' => 'Bearer ' . $guest->invitation_token];
    }

    private function reply(Event $event, Guest $guest, string $status, int $seats)
    {
        return $this->putJson("/api/public/events/{$event->slug}/household-rsvp", [
            'attendance_status' => $status,
            'confirmed_seats' => $seats,
        ], $this->headers($guest));
    }
}
