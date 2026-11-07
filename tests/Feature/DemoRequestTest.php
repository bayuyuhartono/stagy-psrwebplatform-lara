<?php

use App\Features\EmailSettings\Repositories\EmailSettingRepository;
use App\Features\EmailSettings\Services\EmailConfigurationService;
use App\Features\Themes\Services\ThemeManager;
use App\Jobs\SendContactMessageEmails;
use App\Mail\ContactMessageAutoReply;
use App\Mail\ContactMessageNotification;
use App\Models\ContactMessage;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Page;
use App\Models\Permission;
use App\Models\PricingPlan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'contact-messages', 'enabled' => true]);
});

/** @return array<string, mixed> */
function demoRequestPayload(array $overrides = []): array
{
    return [
        'form_type' => 'demo',
        'name' => 'Rina Wijaya',
        'company' => 'Nusantara Event Pro',
        'whatsapp' => '0812-3456-7890',
        'guest_estimate' => '500_2000',
        'event_date' => now()->addMonth()->toDateString(),
        'interested_plan' => 'Professional',
        'message' => 'Gala dinner di Jakarta.',
        'consent' => true,
        ...$overrides,
    ];
}

test('a visitor can request a demo with only a WhatsApp number', function () {
    Queue::fake();

    $this->postJson('/api/v1/public/contact-messages', demoRequestPayload())->assertCreated();

    $message = ContactMessage::query()->sole();
    expect($message->form_type->value)->toBe('demo')
        ->and($message->email)->toBeNull()
        ->and($message->whatsapp)->toBe('0812-3456-7890')
        ->and($message->guest_estimate->value)->toBe('500_2000')
        ->and($message->subject)->toBe('Permintaan demo — Nusantara Event Pro')
        ->and($message->locale)->toBe('id')
        ->and($message->consented_at)->not->toBeNull();
    Queue::assertPushed(SendContactMessageEmails::class);
});

test('a demo request records the language it was sent in', function () {
    Queue::fake();

    $this->postJson('/api/v1/public/contact-messages?locale=en', demoRequestPayload(['company' => null]))->assertCreated();

    expect(ContactMessage::query()->sole())
        ->locale->toBe('en')
        ->subject->toBe('Demo request — Rina Wijaya');
});

test('a demo request requires consent, a WhatsApp number and a guest estimate', function (string $field, mixed $value) {
    $this->postJson('/api/v1/public/contact-messages', demoRequestPayload([$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'no consent' => ['consent', false],
    'no whatsapp' => ['whatsapp', ''],
    'letters in whatsapp' => ['whatsapp', 'call me maybe'],
    'unknown guest estimate' => ['guest_estimate', 'a_million'],
    'event in the past' => ['event_date', '2020-01-01'],
    'invalid email' => ['email', 'not-an-email'],
]);

test('plain contact messages keep their original requirements', function () {
    $this->postJson('/api/v1/public/contact-messages', ['name' => 'Budi', 'whatsapp' => '0812'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'subject', 'message']);
});

test('the notification email lists the demo details and no auto reply is sent without an email', function () {
    EmailSetting::factory()->create(['enabled' => true, 'notification_to' => 'sales@stagy.id', 'auto_reply_enabled' => true]);
    $message = ContactMessage::factory()->create([
        'form_type' => 'demo', 'email' => null, 'whatsapp' => '0812 0000 1111', 'guest_estimate' => 'over_2000',
        'event_date' => '2026-12-12', 'interested_plan' => 'Enterprise', 'company' => 'PT Contoh',
    ]);
    Mail::fake();

    (new SendContactMessageEmails($message))->handle(app(EmailSettingRepository::class), app(EmailConfigurationService::class));

    Mail::assertSent(ContactMessageNotification::class, function (ContactMessageNotification $mail): bool {
        $mail->assertSeeInHtml('New demo request')
            ->assertSeeInHtml('0812 0000 1111')
            ->assertSeeInHtml('> 2,000 guests')
            ->assertSeeInHtml('12 Dec 2026')
            ->assertSeeInHtml('Enterprise');

        return $mail->hasTo('sales@stagy.id');
    });
    Mail::assertNotSent(ContactMessageAutoReply::class);
});

test('the inbox can be filtered to demo requests and exposes their details', function () {
    $role = Role::factory()->create();
    $role->permissions()->attach(Permission::factory()->create(['name' => 'contact-messages.view']));
    $user = User::factory()->create();
    $user->roles()->attach($role);
    Sanctum::actingAs($user);
    ContactMessage::factory()->create(['form_type' => 'contact']);
    ContactMessage::factory()->create(['form_type' => 'demo', 'guest_estimate' => 'under_500', 'whatsapp' => '0811', 'event_date' => '2026-12-01']);

    $this->getJson('/api/v1/admin/contact-messages?form_type=demo')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.form_type', 'demo')
        ->assertJsonPath('data.0.guest_estimate', 'under_500')
        ->assertJsonPath('data.0.guest_estimate_label', '< 500 guests')
        ->assertJsonPath('data.0.event_date', '2026-12-01');

    $this->getJson('/api/v1/admin/contact-messages?guest_estimate=over_2000')->assertSuccessful()->assertJsonCount(0, 'data');
});

test('the demo form block renders the form with plans in the current language', function () {
    config(['themes.active' => 'stagy']);
    $themes = app(ThemeManager::class);
    $themes->flush();
    $themes->boot();
    view()->getFinder()->flush();
    FeatureFlag::factory()->create(['name' => 'pages', 'enabled' => true]);
    PricingPlan::factory()->create(['name' => 'Professional', 'locale' => 'id']);
    PricingPlan::factory()->create(['name' => 'English Plan', 'locale' => 'en']);

    Page::factory()->published()->create(['slug' => 'demo', 'locale' => 'id', 'blocks' => [['type' => 'demo_form', 'data' => [
        'heading' => 'Ceritakan event klien Anda, kami *siapkan* demonya.',
        'next_steps' => ['Tim kami akan menghubungi Anda melalui WhatsApp.', 'Demo singkat.'],
        'privacy_url' => '/kebijakan-privasi',
    ]]]]);

    $this->get('/demo')
        ->assertSuccessful()
        ->assertSee('data-demo-form', false)
        ->assertSee(url('/api/v1/public/contact-messages').'?locale=id', false)
        ->assertSee('No. WhatsApp')
        ->assertSee('500–2.000 tamu')
        ->assertSee('<option value="Professional">Professional</option>', false)
        ->assertDontSee('English Plan')
        ->assertSee('href="/kebijakan-privasi"', false)
        ->assertSee('Demo singkat.');
});
