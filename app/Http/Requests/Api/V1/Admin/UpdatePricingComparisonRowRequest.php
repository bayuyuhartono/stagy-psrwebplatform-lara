<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Rules\ComparisonValues;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePricingComparisonRowRequest extends FormRequest
{
    use ValidatesTranslations;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('pricing_comparison_row'));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group_name' => ['sometimes', 'required', 'string', 'max:80'],
            'label' => ['sometimes', 'required', 'string', 'max:160'],
            'values' => ['sometimes', 'array', new ComparisonValues($this->route('pricing_comparison_row')->locale)],
            'position' => ['sometimes', 'integer', 'min:0', 'max:1000'],
            ...$this->immutableTranslationRules(),
        ];
    }
}
