<?php

use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

function userWithRole(string $roleName): User
{
    $user = User::factory()->create();
    $user->roles()->sync([Role::query()->where('name', $roleName)->sole()->getKey()]);

    return $user->fresh();
}

test('every default role receives permissions', function () {
    $counts = Role::query()->withCount('permissions')->pluck('permissions_count', 'name');

    expect($counts)->each->toBeGreaterThan(0);
});

test('super admin holds every permission', function () {
    $superAdmin = Role::query()->where('name', 'super-admin')->withCount('permissions')->sole();

    expect($superAdmin->permissions_count)->toBe(Permission::query()->count());
});

test('admin is denied the levers that reshape the install', function () {
    $admin = userWithRole('admin');

    expect($admin->hasPermission('pages.create'))->toBeTrue()
        ->and($admin->hasPermission('users.create'))->toBeTrue()
        ->and($admin->hasPermission('feature-flags.update'))->toBeFalse()
        ->and($admin->hasPermission('system-settings.view'))->toBeFalse()
        ->and($admin->hasPermission('roles.delete'))->toBeFalse();
});

test('editor governs content but not people or configuration', function () {
    $editor = userWithRole('editor');

    expect($editor->hasPermission('posts.delete'))->toBeTrue()
        ->and($editor->hasPermission('posts.update.any'))->toBeTrue()
        ->and($editor->hasPermission('pages.create'))->toBeTrue()
        ->and($editor->hasPermission('menus.update'))->toBeTrue()
        ->and($editor->hasPermission('seo.update'))->toBeTrue()
        ->and($editor->hasPermission('users.view'))->toBeFalse()
        ->and($editor->hasPermission('roles.view'))->toBeFalse()
        ->and($editor->hasPermission('website-settings.update'))->toBeFalse()
        ->and($editor->hasPermission('integrations.update'))->toBeFalse();
});

test('author may write posts but not delete them or touch other modules', function () {
    $author = userWithRole('author');

    expect($author->hasPermission('posts.create'))->toBeTrue()
        ->and($author->hasPermission('posts.update'))->toBeTrue()
        ->and($author->hasPermission('media.upload'))->toBeTrue()
        ->and($author->hasPermission('posts.update.any'))->toBeFalse()
        ->and($author->hasPermission('posts.delete'))->toBeFalse()
        ->and($author->hasPermission('pages.create'))->toBeFalse()
        ->and($author->hasPermission('services.create'))->toBeFalse()
        ->and($author->hasPermission('contact-messages.view'))->toBeFalse();
});

test('sales support works the inbox and reads content only', function () {
    $sales = userWithRole('sales-support');

    expect($sales->hasPermission('contact-messages.update'))->toBeTrue()
        ->and($sales->hasPermission('contact-messages.delete'))->toBeTrue()
        ->and($sales->hasPermission('posts.view'))->toBeTrue()
        ->and($sales->hasPermission('posts.create'))->toBeFalse()
        ->and($sales->hasPermission('pages.update'))->toBeFalse()
        ->and($sales->hasPermission('media.upload'))->toBeFalse();
});

test('every role can reach the dashboard and global search', function (string $roleName) {
    $user = userWithRole($roleName);

    expect($user->hasPermission('dashboard.view'))->toBeTrue()
        ->and($user->hasPermission('global-search.view'))->toBeTrue();
})->with(['admin', 'editor', 'author', 'sales-support']);

test('an author may edit their own post but not one by someone else', function () {
    $author = userWithRole('author');
    $own = Post::factory()->create(['author_id' => $author->getKey()]);
    $theirs = Post::factory()->create(['author_id' => User::factory()->create()->getKey()]);

    expect($author->can('update', $own))->toBeTrue()
        ->and($author->can('update', $theirs))->toBeFalse();
});

test('an editor may edit any post regardless of authorship', function () {
    $editor = userWithRole('editor');
    $theirs = Post::factory()->create(['author_id' => User::factory()->create()->getKey()]);

    expect($editor->can('update', $theirs))->toBeTrue();
});

test('an authorless post is not editable by an author-scoped user', function () {
    $author = userWithRole('author');
    $orphan = Post::factory()->create(['author_id' => null]);

    expect($author->can('update', $orphan))->toBeFalse();
});

test('re-running the seeder does not duplicate role permissions', function () {
    $before = Role::query()->where('name', 'editor')->withCount('permissions')->sole()->permissions_count;

    $this->seed(DatabaseSeeder::class);

    $after = Role::query()->where('name', 'editor')->withCount('permissions')->sole()->permissions_count;

    expect($after)->toBe($before);
});
