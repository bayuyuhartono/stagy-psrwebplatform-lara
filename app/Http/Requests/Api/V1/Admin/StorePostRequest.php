<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(ContentBlockRegistry $blockRegistry): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Post::class)],
            'type' => ['required', Rule::enum(PostType::class)],
            'status' => ['required', Rule::enum(PostStatus::class)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'blocks' => ['present', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'published_at' => ['nullable', 'date'],
            'category_ids' => ['sometimes', 'array', 'max:20'],
            'category_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Category::class, 'id')],
            'tag_ids' => ['sometimes', 'array', 'max:50'],
            'tag_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Tag::class, 'id')],
        ];
    }
}
