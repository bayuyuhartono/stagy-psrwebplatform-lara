<?php

use App\Models\ContactMessage;
use App\Models\FeatureFlag;
use App\Models\Integration;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'contact-messages']);
});

/** @return array<string, string> */
function contactPayload(array $overrides = []): array
{
    return [
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'subject' => 'Hello',
        'message' => 'I would like to discuss a project with your team.',
        ...$overrides,
    ];
}

function enableRecaptcha(): void
{
    Integration::factory()->create([
        'provider' => 'recaptcha',
        'enabled' => true,
        'public_config' => ['site_key' => 'test-site-key'],
        'secret_config' => ['secret_key' => 'test-secret-key'],
    ]);
}

test('submissions succeed without a token while recaptcha is disabled', function () {
    Http::fake();

    $this->postJson('/api/v1/public/contact-messages', contactPayload())
        ->assertCreated();

    expect(ContactMessage::query()->count())->toBe(1);
    Http::assertNothingSent();
});

test('an enabled recaptcha rejects a submission with no token', function () {
    enableRecaptcha();

    $this->postJson('/api/v1/public/contact-messages', contactPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recaptcha_token');

    expect(ContactMessage::query()->count())->toBe(0);
});

test('an enabled recaptcha rejects a token Google marks invalid', function () {
    enableRecaptcha();
    Http::fake(['www.google.com/*' => Http::response(['success' => false])]);

    $this->postJson('/api/v1/public/contact-messages', contactPayload(['recaptcha_token' => 'bad-token']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recaptcha_token');

    expect(ContactMessage::query()->count())->toBe(0);
});

test('a verified token is accepted and is not persisted on the message', function () {
    enableRecaptcha();
    Http::fake(['www.google.com/*' => Http::response(['success' => true])]);

    $this->postJson('/api/v1/public/contact-messages', contactPayload(['recaptcha_token' => 'good-token']))
        ->assertCreated();

    $message = ContactMessage::query()->sole();

    expect($message->getAttributes())->not->toHaveKey('recaptcha_token')
        ->and($message->name)->toBe('Ada Lovelace');

    Http::assertSent(fn ($request) => $request['secret'] === 'test-secret-key' && $request['response'] === 'good-token');
});

test('verification fails closed when Google is unreachable', function () {
    enableRecaptcha();
    Http::fake(fn () => throw new ConnectionException('network down'));

    $this->postJson('/api/v1/public/contact-messages', contactPayload(['recaptcha_token' => 'good-token']))
        ->assertUnprocessable();

    expect(ContactMessage::query()->count())->toBe(0);
});

test('verification fails closed when enabled but no secret is configured', function () {
    Integration::factory()->create([
        'provider' => 'recaptcha',
        'enabled' => true,
        'public_config' => ['site_key' => 'test-site-key'],
        'secret_config' => null,
    ]);

    $this->postJson('/api/v1/public/contact-messages', contactPayload(['recaptcha_token' => 'any-token']))
        ->assertUnprocessable();

    expect(ContactMessage::query()->count())->toBe(0);
});

test('the contact page renders the widget only when recaptcha is enabled', function () {
    $this->get('/contact')->assertSuccessful()->assertDontSee('g-recaptcha');

    enableRecaptcha();

    $this->get('/contact')
        ->assertSuccessful()
        ->assertSee('g-recaptcha')
        ->assertSee('test-site-key')
        ->assertDontSee('test-secret-key');
});
