<?php

use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

function passwordPasses(string $password): bool
{
    return Validator::make(
        ['password' => $password],
        ['password' => Password::defaults()],
    )->passes();
}

test('the default password policy rejects weak passwords', function (string $password) {
    expect(passwordPasses($password))->toBeFalse();
})->with([
    'too short' => 'Ab1!xyz',
    'no uppercase' => 'lowercase-123!',
    'no lowercase' => 'UPPERCASE-123!',
    'no digits' => 'NoDigitsHere!!',
    'no symbols' => 'NoSymbolsHere123',
]);

test('the default password policy accepts a strong password', function () {
    expect(passwordPasses('Correct-Horse-Battery-9!'))->toBeTrue();
});

test('user creation enforces the password policy', function () {
    $permission = Permission::factory()->create(['name' => 'users.create']);
    $role = Role::factory()->create();
    $role->permissions()->attach($permission);
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/admin/users', [
        'name' => 'Weak Person',
        'email' => 'weak@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

test('audit logs older than the retention window are prunable', function () {
    config(['audit.retention_days' => 365]);

    $stale = AuditLog::factory()->create(['created_at' => now()->subDays(400)]);
    $recent = AuditLog::factory()->create(['created_at' => now()->subDays(10)]);

    $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

    expect(AuditLog::query()->whereKey($stale->getKey())->exists())->toBeFalse()
        ->and(AuditLog::query()->whereKey($recent->getKey())->exists())->toBeTrue();
});

test('an entry exactly inside the window survives pruning', function () {
    config(['audit.retention_days' => 30]);

    $inside = AuditLog::factory()->create(['created_at' => now()->subDays(29)]);
    $outside = AuditLog::factory()->create(['created_at' => now()->subDays(31)]);

    $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

    expect(AuditLog::query()->whereKey($inside->getKey())->exists())->toBeTrue()
        ->and(AuditLog::query()->whereKey($outside->getKey())->exists())->toBeFalse();
});

test('a retention of zero disables pruning entirely', function () {
    config(['audit.retention_days' => 0]);

    AuditLog::factory()->create(['created_at' => now()->subYears(5)]);

    $this->artisan('model:prune', ['--model' => [AuditLog::class]])->assertSuccessful();

    expect(AuditLog::query()->count())->toBe(1);
});

test('the audit prune is scheduled daily', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event): bool => str_contains($event->command ?? '', 'model:prune'));

    expect($events)->toHaveCount(1)
        ->and($events->first()->expression)->toBe('0 0 * * *');
});
