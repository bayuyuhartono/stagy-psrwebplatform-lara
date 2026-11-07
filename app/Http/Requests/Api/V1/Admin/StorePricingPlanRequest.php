<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\PricingPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingPlanRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return $this->user()->can('create', PricingPlan::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [...self::planRules(required: true), ...$this->translationRules(PricingPlan::class)];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function planRules(bool $required): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            'name' => [$presence, 'string', 'max:80'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'price' => [$presence, 'integer', 'min:0', 'max:1000000000000'],
            'extra_guest_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'features' => [$required ? 'present' : 'sometimes', 'array', 'max:20'],
            'features.*' => ['string', 'max:200'],
            'support_summary' => ['nullable', 'string', 'max:160'],
            'is_highlighted' => ['sometimes', 'boolean'],
            'highlight_label' => ['nullable', 'string', 'max:40'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:2048', 'content_link'],
            'position' => ['sometimes', 'integer', 'min:0', 'max:1000'],
        ];
    }
}
