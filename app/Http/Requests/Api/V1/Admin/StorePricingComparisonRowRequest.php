<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\PricingComparisonRow;
use App\Rules\ComparisonValues;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePricingComparisonRowRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return $this->user()->can('create', PricingComparisonRow::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_name' => ['required', 'string', 'max:80'],
            'label' => ['required', 'string', 'max:160'],
            'values' => ['present', 'array', new ComparisonValues($this->requestedLocale())],
            'position' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            ...$this->translationRules(PricingComparisonRow::class),
        ];
    }
}
