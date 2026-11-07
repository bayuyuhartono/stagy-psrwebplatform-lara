<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Posts\Enums\PostStatus;
use App\Features\Posts\Enums\PostType;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Post::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::enum(PostType::class)],
            'status' => ['sometimes', Rule::enum(PostStatus::class)],
            'category' => ['sometimes', 'string', 'max:255'],
            'tag' => ['sometimes', 'string', 'max:255'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
