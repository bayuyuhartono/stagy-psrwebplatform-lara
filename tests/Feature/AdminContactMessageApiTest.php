<?php

use App\Models\ContactMessage;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'contact-messages']);
});

function actAsContactAdministrator(array $permissions): User
{
    $role = Role::factory()->create();
    $role->permissions()->attach(collect($permissions)->map(fn (string $name): string => Permission::factory()->create(['name' => $name])->id));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    return $user;
}

test('an authorized administrator can assign and progress a contact message', function () {
    actAsContactAdministrator(['contact-messages.update']);
    $assignee = User::factory()->create();
    $message = ContactMessage::factory()->create();

    $this->putJson("/api/v1/admin/contact-messages/{$message->id}", [
        'status' => 'in_progress', 'assigned_to' => $assignee->id, 'internal_notes' => 'Call tomorrow.',
    ])->assertSuccessful()->assertJsonPath('data.status', 'in_progress')->assertJsonPath('data.assignee.id', $assignee->id);

    $this->assertDatabaseHas('audit_logs', ['event' => 'contact-message.updated']);
});

test('admin listing filters by status date assignment and search', function () {
    actAsContactAdministrator(['contact-messages.view']);
    $assignee = User::factory()->create();
    ContactMessage::factory()->create(['name' => 'Matching Lead', 'status' => 'resolved', 'assigned_to' => $assignee->id, 'submitted_at' => '2026-08-07 10:00:00']);
    ContactMessage::factory()->create(['status' => 'new', 'submitted_at' => '2026-08-07 10:00:00']);

    $this->getJson("/api/v1/admin/contact-messages?status=resolved&assigned_to={$assignee->id}&date_from=2026-08-07&date_to=2026-08-07&search=Matching")
        ->assertSuccessful()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Matching Lead');
});

test('an administrator without permission cannot view messages', function () {
    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/admin/contact-messages')->assertForbidden();
});
