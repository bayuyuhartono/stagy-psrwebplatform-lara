<?php

use App\Models\FeatureFlag;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    FeatureFlag::factory()->create(['name' => 'menus']);
});

test('a public menu can be fetched by active location in display order', function () {
    $menu = Menu::factory()->create(['location' => 'footer']);
    MenuItem::factory()->for($menu)->create(['label' => 'Second', 'position' => 2]);
    MenuItem::factory()->for($menu)->create(['label' => 'First', 'position' => 1]);

    $this->getJson('/api/v1/public/menus/footer')
        ->assertSuccessful()
        ->assertJsonPath('data.location', 'footer')
        ->assertJsonPath('data.items.0.label', 'First')
        ->assertJsonPath('data.items.1.label', 'Second');
});

test('an inactive menu is not publicly available', function () {
    Menu::factory()->create(['location' => 'hidden', 'is_active' => false]);

    $this->getJson('/api/v1/public/menus/hidden')->assertNotFound();
});
