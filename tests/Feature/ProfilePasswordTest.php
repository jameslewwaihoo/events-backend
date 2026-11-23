<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_event_navbar_links_the_account_avatar_directly_to_the_profile(): void
    {
        $admin = User::factory()->create(['name' => 'Wedding Admin', 'role' => 'admin']);
        $response = $this->actingAs($admin)->get('/admin/events')->assertOk();
        $page = new DOMDocument();
        $page->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($page);

        foreach (['navbar-main', 'sidenav-main'] as $navbar) {
            $link = $xpath->query('//*[@id="'.$navbar.'"]//a[@aria-label="Profile for Wedding Admin"][.//img]')->item(0);
            $this->assertNotNull($link);
            $this->assertSame(route('profile.edit'), $link->getAttribute('href'));
            $this->assertFalse($link->hasAttribute('data-toggle'));
        }

        $this->get(route('profile.edit'))->assertOk()->assertSee('Change password');
    }

    public function test_the_wedding_admin_can_change_their_password_using_the_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => Hash::make(' original password '),
        ]);

        $this->get('/profile')->assertRedirect('/login');
        $this->actingAs($admin)->get('/profile')->assertOk()->assertSee('Change password');

        $this->from('/profile')->put('/profile/password', [
            'old_password' => 'incorrect password',
            'password' => 'replacement password',
            'password_confirmation' => 'replacement password',
        ])->assertRedirect('/profile')
            ->assertSessionHasErrors('old_password')
            ->assertSessionMissing('_old_input.old_password');
        $this->assertTrue(Hash::check(' original password ', $admin->fresh()->password));

        $this->from('/profile')->put('/profile/password', [
            'old_password' => ' original password ',
            'password' => 'replacement password',
            'password_confirmation' => 'replacement password',
        ])->assertRedirect('/profile')->assertSessionHas('password_status');

        $this->assertTrue(Hash::check('replacement password', $admin->fresh()->password));
        $this->assertFalse(Hash::check(' original password ', $admin->fresh()->password));
    }

    public function test_the_seeded_demo_account_keeps_its_password_protection(): void
    {
        $demo = User::findOrFail(1);
        $demo->update(['password' => Hash::make('demo password')]);

        $this->actingAs($demo)->from('/profile')->put('/profile/password', [
            'old_password' => 'demo password',
            'password' => 'replacement password',
            'password_confirmation' => 'replacement password',
        ])->assertRedirect('/profile')->assertSessionHasErrors('not_allow_password');

        $this->assertTrue(Hash::check('demo password', $demo->fresh()->password));
    }
}
