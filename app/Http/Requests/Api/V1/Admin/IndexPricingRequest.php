<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Localization\Services\LocaleRegistry;
use App\Models\PricingPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPricingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', PricingPlan::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', Rule::in(app(LocaleRegistry::class)->supported())],
        ];
    }

    public function locale(): string
    {
        return $this->validated('locale') ?? app(LocaleRegistry::class)->default();
    }
}
