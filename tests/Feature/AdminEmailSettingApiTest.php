<?php

use App\Mail\TestEmail;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'email', 'enabled' => true]);
});

function actingAsEmailSettingsAdministrator(array $permissions): User
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

test('an authorized administrator can view email settings without credentials', function () {
    actingAsEmailSettingsAdministrator(['email-settings.view']);
    EmailSetting::factory()->create();

    $this->getJson('/api/v1/admin/email-settings')
        ->assertSuccessful()
        ->assertJsonPath('data.host', 'smtp.example.com')
        ->assertJsonPath('data.credentials_configured', true)
        ->assertJsonMissingPath('data.username')
        ->assertJsonMissingPath('data.password');
});

test('an authorized administrator can update email settings with encrypted credentials', function () {
    actingAsEmailSettingsAdministrator(['email-settings.update']);

    $response = $this->putJson('/api/v1/admin/email-settings', emailSettingPayload())
        ->assertSuccessful()
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.auto_reply_enabled', true)
        ->assertJsonMissingPath('data.password');

    $settings = EmailSetting::query()->firstOrFail();
    expect($settings->username)->toBe('mailer@example.com')
        ->and($settings->password)->toBe('private-password')
        ->and($settings->getRawOriginal('username'))->not->toContain('mailer@example.com')
        ->and($settings->getRawOriginal('password'))->not->toContain('private-password')
        ->and($response->getContent())->not->toContain('private-password');
    $this->assertDatabaseHas('audit_logs', ['event' => 'email_setting.updated']);
});

test('an omitted password is preserved when updating settings', function () {
    actingAsEmailSettingsAdministrator(['email-settings.update']);
    EmailSetting::factory()->create(['password' => 'existing-password']);
    $payload = emailSettingPayload();
    unset($payload['password']);

    $this->putJson('/api/v1/admin/email-settings', $payload)->assertSuccessful();

    expect(EmailSetting::query()->firstOrFail()->password)->toBe('existing-password');
});

test('an authorized administrator can send a test email', function () {
    actingAsEmailSettingsAdministrator(['email-settings.test']);
    EmailSetting::factory()->create();
    Mail::fake();

    $this->postJson('/api/v1/admin/email-settings/test', [
        'recipient' => 'developer@example.com',
    ])->assertSuccessful()->assertJsonPath('message', 'Test email sent successfully.');

    Mail::assertSent(TestEmail::class, fn (TestEmail $mail): bool => $mail->hasTo('developer@example.com'));
    $this->assertDatabaseHas('audit_logs', ['event' => 'email_setting.test_sent']);
});

test('email settings validate enabled SMTP and auto reply configuration', function () {
    actingAsEmailSettingsAdministrator(['email-settings.update']);

    $this->putJson('/api/v1/admin/email-settings', [
        'enabled' => true,
        'port' => 70000,
        'auto_reply_enabled' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors([
        'host', 'port', 'from_address', 'from_name', 'auto_reply_subject', 'auto_reply_body',
    ]);
});

test('email settings endpoints require granular permissions', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/admin/email-settings')->assertForbidden();
    $this->putJson('/api/v1/admin/email-settings', emailSettingPayload())->assertForbidden();
    $this->postJson('/api/v1/admin/email-settings/test', ['recipient' => 'developer@example.com'])->assertForbidden();
});

/** @return array<string, mixed> */
function emailSettingPayload(): array
{
    return [
        'enabled' => true,
        'host' => 'smtp.example.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'mailer@example.com',
        'password' => 'private-password',
        'from_address' => 'hello@example.com',
        'from_name' => 'Example Website',
        'notification_to' => 'inbox@example.com',
        'auto_reply_enabled' => true,
        'auto_reply_subject' => 'We received your message',
        'auto_reply_body' => 'Thank you for contacting us. We will reply shortly.',
    ];
}
