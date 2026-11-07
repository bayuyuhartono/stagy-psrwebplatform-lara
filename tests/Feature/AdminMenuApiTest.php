<?php

use App\Models\FeatureFlag;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'menus']);
});

function actAsMenuAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(
        fn (string $name): string => Permission::factory()->create(['name' => $name])->id,
    ));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('an authorized administrator can manage a menu and hierarchical items', function () {
    actAsMenuAdministrator(['menus.create', 'menus.update', 'menus.view']);

    $menuResponse = $this->postJson('/api/v1/admin/menus', [
        'name' => 'Main navigation',
        'location' => 'header',
    ])->assertSuccessful()->assertJsonPath('data.location', 'header');

    $menuId = $menuResponse->json('data.id');
    $parentResponse = $this->postJson("/api/v1/admin/menus/{$menuId}/items", [
        'label' => 'Company',
        'type' => 'custom',
        'url' => '/company',
        'position' => 1,
    ])->assertSuccessful();

    $page = Page::factory()->create(['slug' => 'about']);
    $this->postJson("/api/v1/admin/menus/{$menuId}/items", [
        'label' => 'About',
        'type' => 'page',
        'page_id' => $page->id,
        'parent_id' => $parentResponse->json('data.id'),
        'target' => '_blank',
    ])->assertSuccessful()->assertJsonPath('data.url', '/about');

    $this->getJson("/api/v1/admin/menus/{$menuId}")
        ->assertSuccessful()
        ->assertJsonPath('data.items.0.children.0.label', 'About');

    $this->assertDatabaseHas('audit_logs', ['event' => 'menu.created']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'menu-item.created']);
});

test('menu item validation blocks unsafe URLs, foreign parents, and cycles', function () {
    actAsMenuAdministrator(['menus.update']);
    $menu = Menu::factory()->create();
    $otherMenu = Menu::factory()->create();
    $foreignParent = MenuItem::factory()->for($otherMenu)->create();

    $this->postJson("/api/v1/admin/menus/{$menu->id}/items", [
        'label' => 'Unsafe', 'type' => 'custom', 'url' => 'javascript:alert(1)',
    ])->assertUnprocessable()->assertJsonValidationErrors('url');

    $this->postJson("/api/v1/admin/menus/{$menu->id}/items", [
        'label' => 'Foreign', 'type' => 'custom', 'url' => '/', 'parent_id' => $foreignParent->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');

    $parent = MenuItem::factory()->for($menu)->create();
    $child = MenuItem::factory()->for($menu)->create(['parent_id' => $parent->id]);
    $this->putJson("/api/v1/admin/menus/{$menu->id}/items/{$parent->id}", [
        'parent_id' => $child->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');
});

test('menu item bindings are scoped to their menu', function () {
    actAsMenuAdministrator(['menus.update']);
    $menu = Menu::factory()->create();
    $item = MenuItem::factory()->create();

    $this->deleteJson("/api/v1/admin/menus/{$menu->id}/items/{$item->id}")->assertNotFound();
});

test('an administrator without permission cannot create a menu', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/admin/menus', ['name' => 'Main', 'location' => 'header'])->assertForbidden();
});
