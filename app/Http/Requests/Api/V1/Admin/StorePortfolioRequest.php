<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Models\Category;
use App\Models\Media;
use App\Models\Portfolio;
use App\Models\Tag;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePortfolioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Portfolio::class);
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Portfolio::class)],
            'excerpt' => ['nullable', 'string', 'max:1000'], 'client' => ['nullable', 'string', 'max:255'],
            'project_date' => ['nullable', 'date'], 'external_url' => ['nullable', 'url:http,https', 'max:2048'],
            'cover_image_id' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')],
            'status' => ['required', Rule::enum(PortfolioStatus::class)], 'is_featured' => ['sometimes', 'boolean'],
            'blocks' => ['present', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'], 'published_at' => ['nullable', 'date'],
            'category_ids' => ['sometimes', 'array', 'max:20'], 'category_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Category::class, 'id')],
            'tag_ids' => ['sometimes', 'array', 'max:50'], 'tag_ids.*' => ['uuid', 'distinct:strict', Rule::exists(Tag::class, 'id')],
        ];
    }
}
