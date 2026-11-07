<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * The granular permission matrix (PRD §18).
 *
 * `super-admin` is intentionally absent from the matrix below — it is synced with
 * every permission that exists, so new permissions reach it without an edit here.
 *
 * Roles are synced rather than merged, so this seeder is the single source of
 * truth: removing a permission from a role here removes it on the next run.
 */
class RolePermissionSeeder extends Seeder
{
    /** Modules whose full CRUD an Editor may exercise. */
    private const EDITOR_CONTENT_MODULES = ['pages', 'posts', 'services', 'portfolio', 'pricing'];

    public function run(): void
    {
        $permissions = Permission::query()->pluck('id', 'name');

        $superAdmin = Role::query()->where('name', 'super-admin')->first();
        $superAdmin?->permissions()->sync($permissions->values()->all());

        foreach ($this->matrix() as $roleName => $names) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null) {
                continue;
            }

            // Guard against typos silently granting nothing.
            $ids = collect($names)
                ->map(fn (string $name): ?string => $permissions[$name] ?? null)
                ->filter()
                ->values()
                ->all();

            $role->permissions()->sync($ids);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    private function matrix(): array
    {
        return [
            'admin' => $this->adminPermissions(),
            'editor' => $this->editorPermissions(),
            'author' => $this->authorPermissions(),
            'sales-support' => $this->salesSupportPermissions(),
        ];
    }

    /**
     * Everything except the levers that can lock others out or reshape the install:
     * feature flags, system settings, and destroying roles.
     *
     * @return list<string>
     */
    private function adminPermissions(): array
    {
        return Permission::query()
            ->whereNotIn('name', ['feature-flags.view', 'feature-flags.update', 'system-settings.view', 'roles.delete'])
            ->pluck('name')
            ->all();
    }

    /**
     * Full authority over content and its presentation; none over people or configuration.
     *
     * @return list<string>
     */
    private function editorPermissions(): array
    {
        $content = collect(self::EDITOR_CONTENT_MODULES)
            ->flatMap(fn (string $module): array => [
                "{$module}.view", "{$module}.create", "{$module}.update", "{$module}.delete", "{$module}.restore",
            ]);

        return $content->merge([
            // Editors curate the whole publication, not just their own drafts.
            'posts.update.any',
            'media.view', 'media.upload', 'media.update', 'media.delete', 'media.restore',
            'taxonomies.view', 'taxonomies.create', 'taxonomies.update', 'taxonomies.delete',
            'menus.view', 'menus.create', 'menus.update', 'menus.delete',
            'seo.view', 'seo.update',
            'contact-messages.view', 'contact-messages.update',
            'dashboard.view', 'global-search.view',
        ])->all();
    }

    /**
     * Writes posts and nothing else. Deletion is withheld, and PostPolicy narrows
     * update/view further to posts the author owns.
     *
     * @return list<string>
     */
    private function authorPermissions(): array
    {
        return [
            'posts.view', 'posts.create', 'posts.update',
            'media.view', 'media.upload',
            'taxonomies.view',
            'seo.view',
            'dashboard.view', 'global-search.view',
        ];
    }

    /**
     * Works the contact inbox; reads content for context but cannot change it.
     *
     * @return list<string>
     */
    private function salesSupportPermissions(): array
    {
        return [
            'contact-messages.view', 'contact-messages.update', 'contact-messages.delete', 'contact-messages.restore',
            'pages.view', 'posts.view', 'services.view', 'portfolio.view', 'pricing.view',
            'media.view',
            'dashboard.view', 'global-search.view',
        ];
    }
}
