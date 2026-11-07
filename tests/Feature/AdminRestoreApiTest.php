<?php

use App\Models\ContactMessage;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function actingAsRestoreAdministrator(array $permissions): User
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

test('authorized administrators can restore every soft deleted core resource', function () {
    foreach (['pages', 'posts', 'services', 'portfolio', 'contact-messages'] as $feature) {
        FeatureFlag::factory()->create(['name' => $feature, 'enabled' => true]);
    }

    actingAsRestoreAdministrator([
        'pages.restore', 'posts.restore', 'services.restore', 'portfolio.restore',
        'contact-messages.restore', 'users.restore',
    ]);

    $resources = [
        ['pages', Page::factory()->create(), 'page.restored'],
        ['posts', Post::factory()->create(), 'post.restored'],
        ['services', Service::factory()->create(), 'service.restored'],
        ['portfolio', Portfolio::factory()->create(), 'portfolio.restored'],
        ['contact-messages', ContactMessage::factory()->create(), 'contact-message.restored'],
        ['users', User::factory()->create(), 'user.restored'],
    ];

    foreach ($resources as [$path, $model, $event]) {
        $model->delete();

        $response = $this->postJson("/api/v1/admin/{$path}/{$model->getKey()}/restore");
        expect($response->status(), "{$path}: {$response->content()}")->toBeIn(range(200, 299));
        $response->assertJsonPath('data.id', $model->getKey());

        $this->assertNotSoftDeleted($model);
        $this->assertDatabaseHas('audit_logs', ['event' => $event, 'auditable_id' => $model->getKey()]);
    }
});

test('restore requires the resource-specific permission', function () {
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
    Sanctum::actingAs(User::factory()->create());
    $page = Page::factory()->create();
    $page->delete();

    $this->postJson("/api/v1/admin/pages/{$page->id}/restore")->assertForbidden();
    $this->assertSoftDeleted($page);
});
