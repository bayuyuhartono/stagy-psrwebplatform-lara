<?php

namespace App\Http\Requests\Concerns;

use App\Features\Localization\Services\LocaleRegistry;
use App\Rules\AvailableTranslation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Shared rules for content written once per locale (see HasTranslations).
 *
 * @mixin FormRequest
 */
trait ValidatesTranslations
{
    /**
     * Rules for creating a row: an optional locale and an optional source
     * record this row translates.
     *
     * @param  class-string<Model>  $modelClass
     * @return array<string, array<int, mixed>>
     */
    protected function translationRules(string $modelClass): array
    {
        return [
            'locale' => ['sometimes', 'required', Rule::in(app(LocaleRegistry::class)->supported())],
            'translation_of' => ['sometimes', 'nullable', 'uuid', new AvailableTranslation($modelClass)],
        ];
    }

    /**
     * A row's locale is fixed once created; translate it into a new row instead.
     *
     * @return array<string, array<int, string>>
     */
    protected function immutableTranslationRules(): array
    {
        return [
            'locale' => ['prohibited'],
            'translation_of' => ['prohibited'],
        ];
    }

    /**
     * The locale the new row will be stored in, used to scope uniqueness rules.
     */
    protected function requestedLocale(): string
    {
        $registry = app(LocaleRegistry::class);
        $locale = $this->input('locale');

        return is_string($locale) && $registry->isSupported($locale) ? $locale : $registry->default();
    }
}
