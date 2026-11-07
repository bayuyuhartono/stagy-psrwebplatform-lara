<?php

use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
});

function actingAsBlockAdministrator(string $permission): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => $permission]));
    $administrator = User::factory()->create();
    $administrator->roles()->attach($role);
    Sanctum::actingAs($administrator);

    return $administrator;
}

test('supported content blocks pass their strict schemas', function () {
    actingAsBlockAdministrator('pages.create');
    $media = Media::factory()->create();

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Home',
        'slug' => 'home',
        'status' => 'draft',
        'blocks' => [
            ['type' => 'hero', 'data' => [
                'heading' => 'Build a better website',
                'media_id' => $media->id,
                'primary_action' => ['label' => 'Contact us', 'url' => 'https://example.com/contact'],
            ]],
            ['type' => 'rich_text', 'data' => ['content' => 'Company introduction']],
            ['type' => 'image', 'data' => ['media_id' => $media->id, 'alt' => 'Team photo']],
            ['type' => 'call_to_action', 'data' => [
                'heading' => 'Ready to start?', 'label' => 'Get started', 'url' => 'https://example.com/start',
            ]],
            ['type' => 'feature_grid', 'data' => ['items' => [
                ['title' => 'Fast', 'description' => 'Optimized delivery'],
            ]]],
        ],
    ])->assertSuccessful()->assertJsonCount(5, 'data.blocks');
});

test('unknown block types and extra data keys are rejected', function () {
    actingAsBlockAdministrator('pages.create');

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Invalid',
        'slug' => 'invalid',
        'status' => 'draft',
        'blocks' => [['type' => 'unknown', 'data' => ['html' => '<script>alert(1)</script>']]],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks');

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Invalid Rich Text',
        'slug' => 'invalid-rich-text',
        'status' => 'draft',
        'blocks' => [['type' => 'rich_text', 'data' => ['content' => 'Safe', 'script' => 'unsafe']]],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks');
});

test('block URLs and media references are validated', function () {
    actingAsBlockAdministrator('pages.create');

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Unsafe CTA',
        'slug' => 'unsafe-cta',
        'status' => 'draft',
        'blocks' => [['type' => 'call_to_action', 'data' => [
            'heading' => 'Click', 'label' => 'Run', 'url' => 'javascript:alert(1)',
        ]]],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks');

    $this->postJson('/api/v1/admin/pages', [
        'title' => 'Missing Image',
        'slug' => 'missing-image',
        'status' => 'draft',
        'blocks' => [['type' => 'image', 'data' => [
            'media_id' => '0198f56e-8985-7df5-8df2-57b46f9cf977',
        ]]],
    ])->assertUnprocessable()->assertJsonValidationErrors('blocks');
});

test('authorized clients can discover supported block schemas', function () {
    actingAsBlockAdministrator('pages.view');

    $this->getJson('/api/v1/admin/content-blocks/schema')
        ->assertSuccessful()
        ->assertJsonPath('data.hero.fields.heading.required', true)
        ->assertJsonPath('data.image.fields.media_id.type', 'uuid')
        ->assertJsonStructure(['data' => ['rich_text', 'hero', 'image', 'call_to_action', 'feature_grid']]);
});
