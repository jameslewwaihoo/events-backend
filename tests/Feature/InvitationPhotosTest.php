<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvitationPhotosTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_changes_are_reflected_in_the_public_gallery_and_old_urls_stop_serving()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $event = $this->wedding($admin);
        $page = "/admin/events/{$event->id}/photos";
        $api = "/api/public/events/{$event->slug}";

        $this->get($page)->assertRedirect('/login');
        $this->actingAs($admin)->get($page)->assertOk()->assertSee('Story photo sphere');
        $this->getJson($api)->assertJsonPath('photos.cover', null)->assertJsonCount(0, 'photos.rom');

        $this->post($page, ['placement' => 'rom', 'photo' => UploadedFile::fake()->create('document.pdf')])
            ->assertSessionHasErrors('photo');
        $this->post($page, [
            'placement' => 'rom',
            'photo' => UploadedFile::fake()->image('first.jpg', 1920, 1080),
            'alt' => 'First photo',
            'alt_zh' => '第一张照片',
        ])->assertRedirect($page)->assertSessionHas('status', 'Photo uploaded.');

        $before = $event->assets()->firstOrFail();
        $version = pathinfo($before->path, PATHINFO_FILENAME);
        $first = $this->getJson($api)->assertJsonPath('photos.rom.0.alt', 'First photo')
            ->assertJsonPath('photos.rom.0.altZh', '第一张照片')
            ->assertJsonPath('photos.rom.0.width', 1600)
            ->json('photos.rom.0');
        $this->get($first['src'])->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->get($first['thumbnail'], ['If-None-Match' => '"'.$version.'-thumb"'])->assertStatus(304);
        $this->get("$page/{$before->id}/thumbnail")->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        $this->post("$page/{$before->id}", [
            '_method' => 'PUT',
            'version' => $version,
            'photo' => UploadedFile::fake()->image('replacement.png', 650, 900),
            'alt' => 'Replacement',
        ])->assertRedirect($page)->assertSessionHas('status', 'Photo replaced.');

        $current = $before->fresh();
        $replacement = $this->getJson($api)->assertJsonPath('photos.rom.0.id', $before->id)
            ->assertJsonPath('photos.rom.0.alt', 'Replacement')
            ->assertJsonPath('photos.rom.0.height', 900)
            ->json('photos.rom.0');
        $this->assertNotSame($first['src'], $replacement['src']);
        $this->get($first['src'])->assertNotFound();
        Storage::disk('local')->assertMissing($before->path);

        $this->delete("$page/{$before->id}", ['version' => $version])->assertSessionHasErrors('photo');
        $this->get($replacement['src'])->assertOk();

        $this->delete("$page/{$current->id}", ['version' => pathinfo($current->path, PATHINFO_FILENAME)])
            ->assertRedirect($page)->assertSessionHas('status', 'Photo removed.');
        $this->getJson($api)->assertJsonCount(0, 'photos.rom');
        $this->get($replacement['thumbnail'])->assertNotFound();
        Storage::disk('local')->assertMissing($current->path);
    }

    public function test_single_photo_slots_and_event_permissions()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $purchaser = User::factory()->create(['role' => 'purchaser']);
        $event = $this->wedding($admin);
        $other = $this->wedding($admin, 'wedding-preview');
        $page = "/admin/events/{$event->id}/photos";
        $upload = ['placement' => 'cover', 'photo' => UploadedFile::fake()->image('cover.jpg', 100, 100)];

        $this->actingAs($purchaser)->get($page)->assertForbidden();
        $this->post($page, $upload)->assertForbidden();
        $this->actingAs($admin)->post($page, $upload)->assertSessionHas('status', 'Photo uploaded.');
        $this->post($page, $upload)->assertSessionHasErrors('photo');

        $photo = $event->assets()->sole();
        $version = pathinfo($photo->path, PATHINFO_FILENAME);
        $this->get("/admin/events/{$other->id}/photos/{$photo->id}/thumbnail")->assertNotFound();
        $this->get("/api/public/events/{$other->slug}/photos/$version/full")->assertNotFound();

        $api = "/api/public/events/{$event->slug}";
        $this->getJson($api)->assertJsonPath('photos.cover.id', $photo->id);
        $event->update(['status' => 'draft']);
        $this->get("$api/photos/$version/full")->assertNotFound();
        $this->get("$page/{$photo->id}/thumbnail")->assertOk();
    }

    public function test_admin_can_edit_and_clear_a_story_description_without_replacing_the_photo()
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $purchaser = User::factory()->create(['role' => 'purchaser']);
        $event = $this->wedding($admin);
        $other = $this->wedding($admin, 'wedding-preview');
        $page = "/admin/events/{$event->id}/photos";
        $api = "/api/public/events/{$event->slug}";

        $this->actingAs($admin)->post($page, [
            'placement' => 'story',
            'photo' => UploadedFile::fake()->image('story.jpg', 235, 117),
        ])->assertRedirect($page);

        $photo = $event->assets()->sole();
        $edit = "$page/{$photo->id}/description";
        $fields = [
            'version' => pathinfo($photo->path, PATHINFO_FILENAME),
            'alt' => 'Our first visit to the waterfront.',
            'alt_zh' => '我们第一次来到海边。',
        ];
        $initial = $this->getJson($api)->assertJsonPath('photos.story.0.alt', null)->json('photos.story.0');
        $this->get($page)->assertOk()->assertSee('Add description')->assertSee($edit);

        $this->actingAs($purchaser)->patch($edit, $fields)->assertForbidden();
        $this->actingAs($admin)->patch("/admin/events/{$other->id}/photos/{$photo->id}/description", $fields)->assertNotFound();
        $this->patch($edit, ['version' => '3cc1bb06-247f-44e4-b553-7e53711e574b'] + $fields)->assertSessionHasErrors('photo');
        $this->patch($edit, ['alt' => str_repeat('a', 256)] + $fields)->assertSessionHasErrors('alt');

        $this->patch($edit, $fields)->assertRedirect("$page#story-photo-{$photo->id}")
            ->assertSessionHas('status', 'Description saved.');
        $this->get($page)->assertOk()->assertSee('Edit description')->assertSee($fields['alt'])->assertSee($fields['alt_zh']);
        $this->getJson($api)->assertJsonPath('photos.story.0.alt', $fields['alt'])
            ->assertJsonPath('photos.story.0.altZh', $fields['alt_zh'])
            ->assertJsonPath('photos.story.0.src', $initial['src'])
            ->assertJsonPath('photos.story.0.thumbnail', $initial['thumbnail'])
            ->assertJsonPath('photos.story.0.width', 235)
            ->assertJsonPath('photos.story.0.height', 117);
        Storage::disk('local')->assertExists($photo->path);

        $this->patch($edit, ['alt' => '', 'alt_zh' => ' '] + $fields)->assertRedirect("$page#story-photo-{$photo->id}");
        $this->getJson($api)->assertJsonPath('photos.story.0.alt', null)->assertJsonPath('photos.story.0.altZh', null);
    }

    private function wedding(User $admin, string $slug = 'james-and-poh-yee'): Event
    {
        return Event::create([
            'user_id' => $admin->id,
            'slug' => $slug,
            'title' => 'Wedding',
            'event_type' => 'wedding',
            'status' => 'published',
            'event_date' => '2027-09-25',
        ]);
    }
}
