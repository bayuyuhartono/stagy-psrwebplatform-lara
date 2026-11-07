<?php

use App\Models\FeatureFlag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'contact-messages']);
});

test('a visitor can submit a validated contact message without exposing sensitive metadata', function () {
    $response = $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10', 'HTTP_USER_AGENT' => 'Contact Test'])
        ->postJson('/api/v1/public/contact-messages', [
            'name' => 'Jane Doe', 'email' => 'jane@example.com', 'phone' => '+62 812 3456',
            'company' => 'Acme', 'subject' => 'Project inquiry', 'message' => 'Please tell me about your services.',
        ]);

    $response->assertCreated()->assertJsonPath('data.status', 'new')->assertJsonMissingPath('data.ip_address');
    expect($response->json('data.id'))->toBeUuid();
    $this->assertDatabaseHas('contact_messages', ['email' => 'jane@example.com', 'ip_address' => '203.0.113.10']);
    $this->assertDatabaseHas('audit_logs', ['event' => 'contact-message.submitted']);
});

test('public contact input is validated', function () {
    $this->postJson('/api/v1/public/contact-messages', [
        'name' => '', 'email' => 'bad', 'phone' => '<script>', 'subject' => '', 'message' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'phone', 'subject', 'message']);
});

test('contact submissions are rate limited by IP address', function () {
    $payload = ['name' => 'Jane', 'email' => 'jane@example.com', 'subject' => 'Hello', 'message' => 'A sufficiently long message.'];
    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/v1/public/contact-messages', $payload)->assertCreated();
    }
    $this->postJson('/api/v1/public/contact-messages', $payload)->assertTooManyRequests();
});
