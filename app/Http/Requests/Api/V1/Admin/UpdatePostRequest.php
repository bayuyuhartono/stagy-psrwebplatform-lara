<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Http\Requests\Concerns\ValidatesTranslations;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    use ValidatesTranslations;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
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
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Post::class)->where('locale', $this->route('post')->locale)->ignore($this->route('post'))],
            'type' => ['sometimes', 'required', Rule::enum(PostType::class)],
            'status' => ['sometimes', 'required', Rule::enum(PostStatus::class)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['sometimes', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['sometimes', 'array', 'max:20'],
            'category_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Category::class, 'id')],
            'tag_ids' => ['sometimes', 'array', 'max:50'],
            'tag_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Tag::class, 'id')],
            ...$this->immutableTranslationRules(),
        ];
    }
}
