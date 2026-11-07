<?php

namespace Database\Seeders;

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Integration;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Creates the permission rows; RolePermissionSeeder then allocates them to roles.
        collect([
            'pages.view', 'pages.create', 'pages.update', 'pages.delete', 'pages.restore',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.restore',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'feature-flags.view', 'feature-flags.update',
            'audit-logs.view',
            'media.view', 'media.upload', 'media.update', 'media.delete', 'media.restore',
            'seo.view', 'seo.update',
            'menus.view', 'menus.create', 'menus.update', 'menus.delete',
            // posts.update scopes to own posts; posts.update.any lifts that to all posts.
            'posts.view', 'posts.create', 'posts.update', 'posts.update.any', 'posts.delete', 'posts.restore',
            'services.view', 'services.create', 'services.update', 'services.delete', 'services.restore',
            'portfolio.view', 'portfolio.create', 'portfolio.update', 'portfolio.delete', 'portfolio.restore',
            'pricing.view', 'pricing.create', 'pricing.update', 'pricing.delete', 'pricing.restore',
            'contact-messages.view', 'contact-messages.update', 'contact-messages.delete', 'contact-messages.restore',
            'taxonomies.view', 'taxonomies.create', 'taxonomies.update', 'taxonomies.delete',
            'website-settings.view', 'website-settings.update',
            'integrations.view', 'integrations.update',
            'email-settings.view', 'email-settings.update', 'email-settings.test',
            'dashboard.view',
            'system-settings.view', 'system-health.view',
            'global-search.view',
        ])->map(fn (string $name): Permission => Permission::query()->firstOrCreate(
            ['name' => $name],
            ['label' => str($name)->replace('.', ' ')->headline()->toString()],
        ));

        foreach (['super-admin', 'admin', 'editor', 'author', 'sales-support'] as $roleName) {
            Role::query()->firstOrCreate(
                ['name' => $roleName],
                ['label' => str($roleName)->replace('-', ' ')->headline()->toString()],
            );
        }

        $superAdmin = Role::query()->where('name', 'super-admin')->firstOrFail();
        // Every role's permissions, including super-admin's, are assigned by RolePermissionSeeder.
        $this->call(RolePermissionSeeder::class);

        FeatureFlag::query()->firstOrCreate(['name' => 'pages'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'media'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'seo'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'menus'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'posts'], ['enabled' => true]);
        // The Stagy site has no services or portfolio; they start switched off there.
        $isStagy = config('themes.active') === 'stagy';

        FeatureFlag::query()->firstOrCreate(['name' => 'services'], ['enabled' => ! $isStagy]);
        FeatureFlag::query()->firstOrCreate(['name' => 'portfolio'], ['enabled' => ! $isStagy]);
        FeatureFlag::query()->firstOrCreate(['name' => 'pricing'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'contact-messages'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'website-settings'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'integrations'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'email'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'dashboard'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'system'], ['enabled' => true]);
        FeatureFlag::query()->firstOrCreate(['name' => 'global-search'], ['enabled' => true]);

        foreach (WebsiteSettingGroup::cases() as $group) {
            WebsiteSetting::query()->firstOrCreate(
                ['group' => $group],
                ['settings' => $group->defaults(), 'is_public' => true],
            );
        }

        foreach (IntegrationProvider::cases() as $provider) {
            Integration::query()->firstOrCreate(
                ['provider' => $provider],
                ['enabled' => false, 'public_config' => $provider->defaultPublicConfig()],
            );
        }

        EmailSetting::query()->firstOrCreate(
            ['key' => 'default'],
            ['enabled' => false, 'port' => 587, 'auto_reply_enabled' => false],
        );

        $user = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );
        $user->roles()->sync([$superAdmin->getKey()]);

        if ($isStagy) {
            $this->call(StagySiteSeeder::class);

            return;
        }

        // MenuSeeder links page-typed items, so it must run after PageSeeder.
        $this->call([
            PageSeeder::class,
            MenuSeeder::class,
            DemoContentSeeder::class,
        ]);
    }
}
