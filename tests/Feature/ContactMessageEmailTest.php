<?php

use App\Features\EmailSettings\Repositories\EmailSettingRepository;
use App\Features\EmailSettings\Services\EmailConfigurationService;
use App\Jobs\SendContactMessageEmails;
use App\Mail\ContactMessageAutoReply;
use App\Mail\ContactMessageNotification;
use App\Models\ContactMessage;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(LazilyRefreshDatabase::class);

test('a contact submission queues email delivery after persistence', function () {
    FeatureFlag::factory()->create(['name' => 'contact-messages', 'enabled' => true]);
    Queue::fake();

    $this->postJson('/api/v1/public/contact-messages', [
        'name' => 'Bayu Hartono',
        'email' => 'bayu@example.com',
        'subject' => 'New project',
        'message' => 'I would like to discuss a new company website.',
    ])->assertCreated();

    Queue::assertPushed(SendContactMessageEmails::class, fn (SendContactMessageEmails $job): bool => $job->contactMessage->email === 'bayu@example.com');
});

test('the delivery job sends the notification and enabled auto reply', function () {
    $settings = EmailSetting::factory()->create([
        'enabled' => true,
        'notification_to' => 'inbox@example.com',
        'auto_reply_enabled' => true,
    ]);
    $contactMessage = ContactMessage::factory()->create([
        'email' => 'visitor@example.com',
        'name' => 'Website Visitor',
    ]);
    Mail::fake();

    (new SendContactMessageEmails($contactMessage))->handle(
        app(EmailSettingRepository::class),
        app(EmailConfigurationService::class),
    );

    Mail::assertSent(ContactMessageNotification::class, fn (ContactMessageNotification $mail): bool => $mail->hasTo('inbox@example.com'));
    Mail::assertSent(ContactMessageAutoReply::class, fn (ContactMessageAutoReply $mail): bool => $mail->hasTo('visitor@example.com'));
    expect($settings->enabled)->toBeTrue();
});

test('the delivery job sends nothing when email delivery is disabled', function () {
    EmailSetting::factory()->create(['enabled' => false]);
    Mail::fake();

    (new SendContactMessageEmails(ContactMessage::factory()->create()))->handle(
        app(EmailSettingRepository::class),
        app(EmailConfigurationService::class),
    );

    Mail::assertNothingSent();
});

test('auto reply content safely renders the configured message', function () {
    $mail = new ContactMessageAutoReply(
        recipientName: 'Website Visitor',
        replyBody: 'Thanks for your message.',
        replySubject: 'Message received',
        fromAddress: 'hello@example.com',
        fromName: 'Example Website',
    );

    $mail->assertSeeInHtml('Website Visitor')->assertSeeInHtml('Thanks for your message.');
});
