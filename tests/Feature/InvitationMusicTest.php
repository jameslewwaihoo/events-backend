<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\InvitationMusic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvitationMusicTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_selects_separate_playlists_and_public_loads_pick_from_them(): void
    {
        $disk = Storage::fake('local');
        config(['wedding.music_library_path' => $disk->path('library')]);
        $disk->put('library/classical-piano/01 First piano.mp3', str_repeat('audio', 10));
        $disk->put('library/classical-piano/02 Second piano.mp3', str_repeat('audio', 20));
        $disk->put('library/chinese-inspired/03 First guzheng.mp3', str_repeat('audio', 30));
        $disk->put('library/classical-piano/31 Canon in D Major - Kevin MacLeod.mp3', str_repeat('audio', 40));
        $admin = User::factory()->create(['role' => 'admin']);
        $visitor = User::factory()->create(['role' => 'purchaser']);
        $event = $this->wedding($admin);
        $page = "/admin/events/{$event->id}/music";
        $api = "/api/public/events/{$event->slug}";

        $english = ['library-01', 'library-02'];
        $initial = InvitationMusic::version([]);
        $this->get($page)->assertRedirect('/login');
        $this->actingAs($visitor)->put("$page/en", ['tracks' => $english, 'version' => $initial])->assertForbidden();
        $this->get("$page/library-01/preview")->assertForbidden();
        $this->actingAs($admin)->get($page)->assertOk()->assertSee('First piano')->assertSee('Choose tracks');
        $this->getJson($api)->assertJsonPath('music', ['en' => null, 'zh' => null]);
        $this->get("$api/music/library-01")->assertNotFound();
        $this->get("$page/library-01/preview")->assertOk()->assertHeader('Content-Type', 'audio/mpeg');
        $this->put("$page/en", ['tracks' => ['library-99'], 'version' => $initial])->assertSessionHasErrors('tracks.0');
        $this->put("$page/en", ['tracks' => ['library-01', 'library-01'], 'version' => $initial])->assertSessionHasErrors('tracks.0');
        $this->put("$page/en", ['tracks' => $english, 'version' => $initial])->assertRedirect($page);
        $this->put("$page/zh", ['tracks' => ['library-03'], 'version' => $initial])->assertRedirect($page);
        $this->put("$page/en", ['tracks' => ['library-03'], 'version' => $initial])->assertSessionHasErrors('tracks');
        $this->get($page)->assertOk()->assertSee('(2 selected)')->assertSee('(1 selected)');

        // Fix the random sequence while exercising the same endpoint the browser loads.
        mt_srand(8619);
        $picked = [];
        try {
            for ($request = 0; $request < 20; $request++) {
                $response = $this->getJson($api)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
                $track = $response->assertJsonPath('music.zh', "$api/music/library-03")->json('music.en');
                $picked[$track] = true;
            }
        } finally {
            mt_srand();
        }
        $this->assertEqualsCanonicalizing(["$api/music/library-01", "$api/music/library-02"], array_keys($picked));
        $this->get("$api/music/library-01", ['Range' => 'bytes=0-1'])->assertStatus(206)
            ->assertHeader('Content-Range', 'bytes 0-1/50');

        $this->put("$page/en", ['version' => InvitationMusic::version($english)])->assertRedirect($page);
        $this->getJson($api)->assertJsonPath('music.en', null)->assertJsonPath('music.zh', "$api/music/library-03");
        $this->get("$api/music/library-01")->assertNotFound();
        $disk->assertExists('library/classical-piano/01 First piano.mp3');

        $canon = ['library-31'];
        $this->put("$page/en", ['tracks' => $canon, 'version' => $initial])->assertRedirect($page);
        $this->put("$page/zh", ['tracks' => $canon, 'version' => InvitationMusic::version(['library-03'])])->assertRedirect($page);
        $this->getJson($api)->assertJsonPath('music.en', "$api/music/library-31")
            ->assertJsonPath('music.zh', "$api/music/library-31")
            ->assertJsonPath('music_credits.en.artist', 'Kevin MacLeod')
            ->assertJsonPath('music_credits.zh.license', 'CC BY 3.0');
        $event->update(['status' => 'draft']);
        $this->get("$api/music/library-31")->assertNotFound();
    }

    public function test_previous_upload_stays_available_for_its_language_after_deselection(): void
    {
        $disk = Storage::fake('local');
        config(['wedding.music_library_path' => $disk->path('library')]);
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->wedding($admin);
        $id = '6248d421-fbc5-4ed6-9bd3-5814b41cbdbd';
        $path = "invitation-music/{$event->id}/$id.mp3";
        $event->assets()->create(['type' => 'invitation-music-en', 'title' => 'Uploaded song', 'path' => $path]);
        $disk->put($path, str_repeat('audio', 10));
        $page = "/admin/events/{$event->id}/music";
        $api = "/api/public/events/{$event->slug}";

        $this->getJson($api)->assertJsonPath('music.en', "$api/music/$id");
        $this->get("$api/music/$id")->assertOk();
        $this->actingAs($admin)->put("$page/en", ['version' => InvitationMusic::version([$id])])->assertRedirect($page);
        $this->put("$page/zh", ['tracks' => [$id], 'version' => InvitationMusic::version([])])->assertSessionHasErrors('tracks');
        $this->getJson($api)->assertJsonPath('music.en', null);
        $this->get("$api/music/$id")->assertNotFound();
        $this->get($page)->assertOk()->assertSee('Previously uploaded')->assertSee('Uploaded song');
        $this->get("$page/$id/preview")->assertOk();
        $this->put("$page/en", ['tracks' => [$id], 'version' => InvitationMusic::version([])])->assertRedirect($page);
        $this->getJson($api)->assertJsonPath('music.en', "$api/music/$id");
    }

    private function wedding(User $admin): Event
    {
        return Event::create([
            'user_id' => $admin->id,
            'slug' => 'james-and-poh-yee',
            'title' => 'James & Poh Yee',
            'event_type' => 'wedding',
            'status' => 'published',
        ]);
    }
}
