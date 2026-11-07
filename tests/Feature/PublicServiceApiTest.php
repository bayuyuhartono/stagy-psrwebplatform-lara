<?php

use App\Models\FeatureFlag;
use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'services']);
});

test('public services are published, searchable, and returned in configured order', function () {
    Service::factory()->published()->create(['title' => 'Second Service', 'slug' => 'second', 'position' => 2]);
    Service::factory()->published()->create(['title' => 'First Service', 'slug' => 'first', 'position' => 1]);
    Service::factory()->create(['title' => 'Draft Service', 'position' => 0]);

    $this->getJson('/api/v1/public/services?search=Service')
        ->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.slug', 'first')
        ->assertJsonPath('data.1.slug', 'second');
});

test('published service detail is public while drafts and scheduled services are hidden', function () {
    Service::factory()->published()->create(['slug' => 'available']);
    Service::factory()->create(['slug' => 'draft']);
    Service::factory()->published()->create(['slug' => 'scheduled', 'published_at' => now()->addDay()]);

    $this->getJson('/api/v1/public/services/available')->assertSuccessful()->assertJsonPath('data.slug', 'available');
    $this->getJson('/api/v1/public/services/draft')->assertNotFound();
    $this->getJson('/api/v1/public/services/scheduled')->assertNotFound();
});
