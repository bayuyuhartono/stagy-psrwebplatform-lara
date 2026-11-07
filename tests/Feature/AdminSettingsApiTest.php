<?php

use App\Models\AuditLog;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function actingAsSettingsAdministrator(array $permissions): User
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

test('an authorized administrator can toggle a feature flag', function () {
    actingAsSettingsAdministrator(['feature-flags.update']);
    $featureFlag = FeatureFlag::factory()->create(['enabled' => true]);

    $this->putJson("/api/v1/admin/feature-flags/{$featureFlag->id}", [
        'enabled' => false,
        'metadata' => ['reason' => 'maintenance'],
    ])->assertSuccessful()->assertJsonPath('data.enabled', false);

    expect($featureFlag->refresh()->enabled)->toBeFalse();
    $this->assertDatabaseHas('audit_logs', ['event' => 'feature_flag.updated']);
});

test('audit logs are available only with permission', function () {
    $administrator = actingAsSettingsAdministrator(['audit-logs.view']);
    AuditLog::factory()->create(['user_id' => $administrator->id, 'event' => 'test.event']);

    $this->getJson('/api/v1/admin/audit-logs')
        ->assertSuccessful()
        ->assertJsonPath('data.0.event', 'test.event');

    Sanctum::actingAs(User::factory()->create());
    $this->getJson('/api/v1/admin/audit-logs')->assertForbidden();
});
