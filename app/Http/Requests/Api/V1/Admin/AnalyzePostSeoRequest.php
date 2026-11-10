<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The post editor's unsaved state, sent for a live SEO analysis. Nothing is
 * stored, so drafts may be incomplete: every field is optional.
 */
class AnalyzePostSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Post::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['nullable', 'array', 'max:100'],
            'blocks.*.type' => ['required', 'string', 'max:50'],
            'blocks.*.data' => ['nullable', 'array'],
            'has_cover_image' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:1000'],
            'focus_keyword' => ['nullable', 'string', 'max:255'],
            'robots_index' => ['boolean'],
        ];
    }
}
