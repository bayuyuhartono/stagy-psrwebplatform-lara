<?php

use App\Features\ContactMessages\Enums\ContactMessageStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Role;
use App\Models\Seo;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'dashboard', 'enabled' => true]);
});

function actingAsDashboardAdministrator(array $permissions): User
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

test('dashboard returns content and post type metrics', function () {
    actingAsDashboardAdministrator(['dashboard.view']);
    Page::factory()->published()->create();
    Page::factory()->create();
    Post::factory()->published()->create(['type' => PostType::Blog]);
    Post::factory()->create(['type' => PostType::News]);
    Service::factory()->published()->create();
    Portfolio::factory()->create();

    $this->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.content.pages.total', 2)
        ->assertJsonPath('data.content.pages.published', 1)
        ->assertJsonPath('data.content.pages.draft', 1)
        ->assertJsonPath('data.content.posts.total', 2)
        ->assertJsonPath('data.content.post_types.blog', 1)
        ->assertJsonPath('data.content.post_types.news', 1)
        ->assertJsonPath('data.content.services.published', 1)
        ->assertJsonPath('data.content.portfolio.draft', 1);
});

test('dashboard returns contact SEO and media metrics', function () {
    actingAsDashboardAdministrator(['dashboard.view']);
    $page = Page::factory()->published()->create();
    Page::factory()->create();
    Post::factory()->create();
    Seo::factory()->create(['seoable_type' => Page::class, 'seoable_id' => $page->id, 'score' => 90]);
    ContactMessage::factory()->create(['status' => ContactMessageStatus::New, 'submitted_at' => now()]);
    ContactMessage::factory()->create(['status' => ContactMessageStatus::Resolved, 'submitted_at' => now()->subDays(10)]);
    Media::factory()->create(['size' => 1024]);
    Media::factory()->create(['size' => 2048]);

    $this->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.contacts.total', 2)
        ->assertJsonPath('data.contacts.new', 1)
        ->assertJsonPath('data.contacts.resolved', 1)
        ->assertJsonPath('data.contacts.last_7_days', 1)
        ->assertJsonPath('data.seo.configured', 1)
        ->assertJsonPath('data.seo.missing', 2)
        ->assertJsonPath('data.seo.average_score', 90)
        ->assertJsonPath('data.seo.good_score', 1)
        ->assertJsonPath('data.media.files', 2)
        ->assertJsonPath('data.media.bytes', 3072);
});

test('dashboard returns safe recent activity and system status', function () {
    $administrator = actingAsDashboardAdministrator(['dashboard.view']);
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => false]);
    EmailSetting::factory()->create(['enabled' => true]);
    AuditLog::factory()->create([
        'user_id' => $administrator->id,
        'event' => 'page.updated',
        'old_values' => ['secret' => 'old'],
        'new_values' => ['secret' => 'new'],
        'ip_address' => '127.0.0.1',
    ]);

    $this->getJson('/api/v1/admin/dashboard')
        ->assertSuccessful()
        ->assertJsonPath('data.recent_activity.0.event', 'page.updated')
        ->assertJsonPath('data.recent_activity.0.user.name', $administrator->name)
        ->assertJsonMissingPath('data.recent_activity.0.old_values')
        ->assertJsonMissingPath('data.recent_activity.0.new_values')
        ->assertJsonMissingPath('data.recent_activity.0.ip_address')
        ->assertJsonPath('data.system.database', 'ok')
        ->assertJsonPath('data.system.email_enabled', true)
        ->assertJsonPath('data.system.features_total', 2)
        ->assertJsonPath('data.system.features_enabled', 1);
});

test('dashboard requires its permission', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
});

test('dashboard respects its feature flag', function () {
    actingAsDashboardAdministrator(['dashboard.view']);
    FeatureFlag::query()->where('name', 'dashboard')->update(['enabled' => false]);

    $this->getJson('/api/v1/admin/dashboard')->assertNotFound();
});
