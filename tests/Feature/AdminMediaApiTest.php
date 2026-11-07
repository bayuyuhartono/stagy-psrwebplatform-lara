<?php

use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    FeatureFlag::factory()->create(['name' => 'media', 'enabled' => true]);
});

function actingAsMediaAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $administrator = User::factory()->create();
    $administrator->roles()->attach($role);
    Sanctum::actingAs($administrator);

    return $administrator;
}

test('an authorized administrator can securely upload media', function () {
    actingAsMediaAdministrator(['media.upload']);

    $response = $this->postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('hero.jpg', 1200, 630)->size(250),
        'folder' => 'pages/home',
        'alt_text' => 'Homepage hero',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.folder', 'pages/home')
        ->assertJsonPath('data.alt_text', 'Homepage hero')
        ->assertJsonPath('data.mime_type', 'image/jpeg')
        ->assertJsonPath('data.metadata.width', 1200)
        ->assertJsonPath('data.metadata.height', 630);

    Storage::disk('public')->assertExists($response->json('data.path'));
    expect($response->json('data.id'))->toBeUuid();
    $this->assertDatabaseHas('audit_logs', ['event' => 'media.uploaded']);
});

test('SVG uploads are rejected', function () {
    actingAsMediaAdministrator(['media.upload']);

    $this->postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->createWithContent(
            'unsafe.svg',
            '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
        ),
    ])->assertUnprocessable()->assertJsonValidationErrors('file');
});

test('duplicate uploads reuse the existing media record', function () {
    actingAsMediaAdministrator(['media.upload']);

    $first = $this->postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('first.jpg', 100, 100),
    ])->assertSuccessful();

    $second = $this->postJson('/api/v1/admin/media', [
        'file' => UploadedFile::fake()->image('second.jpg', 100, 100),
    ])->assertSuccessful();

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(Media::query()->count())->toBe(1);
    Storage::disk('public')->assertCount('media', 1);
});

test('media can be moved between folders and soft deleted', function () {
    actingAsMediaAdministrator(['media.update', 'media.delete']);
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => 'media/original.jpg',
        'folder' => null,
    ]);
    Storage::disk('public')->put($media->path, 'image-content');

    $updated = $this->putJson("/api/v1/admin/media/{$media->id}", [
        'folder' => 'archive',
        'alt_text' => 'Archived image',
    ])->assertSuccessful();

    Storage::disk('public')->assertMissing('media/original.jpg');
    Storage::disk('public')->assertExists($updated->json('data.path'));

    $this->deleteJson("/api/v1/admin/media/{$media->id}")->assertNoContent();
    $this->assertSoftDeleted($media);
    Storage::disk('public')->assertExists($updated->json('data.path'));
});

test('media routes are hidden when the module is disabled', function () {
    FeatureFlag::query()->where('name', 'media')->update(['enabled' => false]);
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/media')->assertNotFound();
});

test('authorized administrators can browse only trashed media', function () {
    actingAsMediaAdministrator(['media.view', 'media.restore']);
    $active = Media::factory()->create(['original_name' => 'active.jpg']);
    $trashed = Media::factory()->create(['original_name' => 'trashed.jpg']);
    $trashed->delete();

    $this->getJson('/api/v1/admin/media?trashed=1')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $trashed->id)
        ->assertJsonPath('data.0.original_name', 'trashed.jpg')
        ->assertJsonMissing(['id' => $active->id]);
});

test('browsing trashed media requires restore permission', function () {
    actingAsMediaAdministrator(['media.view']);

    $this->getJson('/api/v1/admin/media?trashed=1')->assertForbidden();
});
