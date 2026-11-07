<?php

namespace App\Features\Themes\DataTransferObjects;

use InvalidArgumentException;

/**
 * A theme's theme.json, parsed and validated.
 *
 * @phpstan-type SettingSchema array{label: string, type: string, default: mixed, options?: list<string>}
 */
class ThemeManifest
{
    /**
     * @param  array<string, SettingSchema>  $settings
     * @param  array<string, string>  $menuLocations  Menu locations the theme renders, keyed by location with a human label.
     */
    public function __construct(
        public string $slug,
        public string $name,
        public string $version,
        public ?string $author,
        public ?string $description,
        public array $settings,
        public array $menuLocations = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $slug, array $data): self
    {
        foreach (['name', 'version'] as $required) {
            if (! is_string($data[$required] ?? null) || $data[$required] === '') {
                throw new InvalidArgumentException("Theme [{$slug}] manifest is missing a [{$required}].");
            }
        }

        return new self(
            slug: $slug,
            name: $data['name'],
            version: $data['version'],
            author: $data['author'] ?? null,
            description: $data['description'] ?? null,
            settings: self::normalizeSettings($slug, $data['settings'] ?? []),
            menuLocations: self::normalizeMenuLocations($slug, $data['menu_locations'] ?? ['header' => 'Header']),
        );
    }

    /**
     * Default value for every declared setting, keyed by setting name.
     *
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return array_map(fn (array $schema): mixed => $schema['default'], $this->settings);
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeMenuLocations(string $slug, mixed $locations): array
    {
        if (! is_array($locations)) {
            throw new InvalidArgumentException("Theme [{$slug}] declares a non-array [menu_locations].");
        }

        return collect($locations)
            ->mapWithKeys(fn (mixed $label, string|int $location): array => [(string) $location => is_string($label) ? $label : (string) $location])
            ->all();
    }

    /**
     * @return array<string, SettingSchema>
     */
    private static function normalizeSettings(string $slug, mixed $settings): array
    {
        if (! is_array($settings)) {
            throw new InvalidArgumentException("Theme [{$slug}] declares a non-array [settings].");
        }

        $normalized = [];

        foreach ($settings as $key => $schema) {
            if (! is_array($schema) || ! is_string($schema['type'] ?? null)) {
                throw new InvalidArgumentException("Theme [{$slug}] setting [{$key}] must declare a [type].");
            }

            $normalized[$key] = [
                'label' => $schema['label'] ?? str($key)->headline()->toString(),
                'type' => $schema['type'],
                'default' => $schema['default'] ?? null,
                ...(isset($schema['options']) ? ['options' => $schema['options']] : []),
            ];
        }

        return $normalized;
    }
}
