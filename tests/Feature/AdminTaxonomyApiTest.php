<?php

use App\Models\Category;
use App\Models\Permission;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);
function actAsTaxonomyAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}
test('an authorized administrator can manage categories and tags', function () {
    actAsTaxonomyAdministrator(['taxonomies.create', 'taxonomies.view', 'taxonomies.update', 'taxonomies.delete']);
    $category = $this->postJson('/api/v1/admin/categories', ['name' => 'Web Development', 'slug' => 'web-development'])->assertSuccessful()->json('data');
    $tag = $this->postJson('/api/v1/admin/tags', ['name' => 'Laravel', 'slug' => 'laravel'])->assertSuccessful()->json('data');
    $this->putJson("/api/v1/admin/categories/{$category['id']}", ['name' => 'Web Apps'])->assertSuccessful()->assertJsonPath('data.name', 'Web Apps');
    $this->getJson('/api/v1/admin/tags')->assertSuccessful()->assertJsonPath('data.0.slug', 'laravel');
    $this->deleteJson("/api/v1/admin/tags/{$tag['id']}")->assertNoContent();
    $this->assertDatabaseHas('audit_logs', ['event' => 'taxonomy.created']);
});
test('taxonomy slugs are unique and used taxonomies cannot be deleted', function () {
    actAsTaxonomyAdministrator(['taxonomies.create', 'taxonomies.delete']);
    $category = Category::factory()->create(['slug' => 'used']);
    $post = Post::factory()->create();
    $post->categories()->attach($category);
    $this->postJson('/api/v1/admin/categories', ['name' => 'Duplicate', 'slug' => 'used'])->assertUnprocessable()->assertJsonValidationErrors('slug');
    $this->deleteJson("/api/v1/admin/categories/{$category->id}")->assertUnprocessable()->assertJsonValidationErrors('taxonomy');
});
test('an administrator without taxonomy permission is forbidden', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/admin/categories')->assertForbidden();
});
