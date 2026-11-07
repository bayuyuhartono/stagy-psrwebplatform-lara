<?php

namespace App\Features\ContentBlocks;

class ContentBlockRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return collect(config('content_blocks'))
            ->map(fn (array $definition): array => [
                'label' => $definition['label'],
                'editor' => $definition['editor'] ?? 'custom',
                'fields' => $definition['fields'],
            ])->all();
    }

    public function supports(string $type): bool
    {
        return config("content_blocks.{$type}") !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rulesFor(string $type): array
    {
        return config("content_blocks.{$type}.rules", []);
    }
}
