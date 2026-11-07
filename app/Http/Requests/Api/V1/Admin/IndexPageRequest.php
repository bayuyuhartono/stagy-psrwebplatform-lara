<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Localization\Services\LocaleRegistry;
use App\Features\Pages\Enums\PageStatus;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Page::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', Rule::enum(PageStatus::class)],
            'locale' => ['sometimes', Rule::in(app(LocaleRegistry::class)->supported())],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
