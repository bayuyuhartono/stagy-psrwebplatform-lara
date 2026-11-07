<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Pages\Enums\PageStatus;
use App\Models\Page;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('page'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ContentBlockRegistry $blockRegistry): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Page::class)->ignore($this->route('page'))],
            'status' => ['sometimes', 'required', Rule::enum(PageStatus::class)],
            'blocks' => ['sometimes', 'present', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'blocks.*' => ['array:id,type,data'],
            'blocks.*.id' => ['nullable', 'uuid', 'distinct'],
            'blocks.*.type' => ['required', 'string', 'max:100'],
            'blocks.*.data' => ['required', 'array'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
