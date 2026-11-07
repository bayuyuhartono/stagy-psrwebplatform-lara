<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Services\Enums\ServiceStatus;
use App\Models\Service;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Service::class);
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
            'slug' => ['required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Service::class)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::enum(ServiceStatus::class)],
            'blocks' => ['present', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
