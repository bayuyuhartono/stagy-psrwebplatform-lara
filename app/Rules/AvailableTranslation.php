<?php

namespace App\Rules;

use App\Features\Localization\Services\LocaleRegistry;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

/**
 * Validates `translation_of`: the source record exists and its translation
 * group does not already hold a row in the requested locale.
 */
class AvailableTranslation implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @param class-string<Model> $modelClass */
    public function __construct(private string $modelClass) {}

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $source = $this->modelClass::query()->find($value);

        if ($source === null) {
            $fail('The selected :attribute is invalid.');

            return;
        }

        $locale = $this->data['locale'] ?? app(LocaleRegistry::class)->default();
        $exists = $this->modelClass::query()
            ->where('translation_group_id', $source->translation_group_id)
            ->where('locale', $locale)
            ->exists();

        if ($exists) {
            $fail("A translation in [{$locale}] already exists for this content.");
        }
    }
}
