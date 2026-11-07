<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Models\Portfolio;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPortfolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Portfolio::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(PortfolioStatus::class)], 'featured' => ['sometimes', 'boolean'],
            'category' => ['sometimes', 'string', 'max:255'], 'tag' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:255'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
