<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\PricingAddon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingAddonRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return $this->user()->can('create', PricingAddon::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...self::addonRules(required: true), ...$this->translationRules(PricingAddon::class)];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function addonRules(bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            'name' => [$presence, 'string', 'max:160'],
            'price' => [$presence, 'integer', 'min:0', 'max:1000000000000'],
            'unit' => ['nullable', 'string', 'max:40'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
