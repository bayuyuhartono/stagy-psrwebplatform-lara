<?php

namespace App\Features\Localization\Services;

use Illuminate\Database\Eloquent\Model;

class TranslationLinker
{
    /**
     * Turn a validated `translation_of` id into the source's translation group.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function link(string $modelClass, array $attributes): array
    {
        $sourceId = $attributes['translation_of'] ?? null;
        unset($attributes['translation_of']);

        if ($sourceId !== null) {
            $attributes['translation_group_id'] = $modelClass::query()->findOrFail($sourceId)->translation_group_id;
        }

        return $attributes;
    }
}
