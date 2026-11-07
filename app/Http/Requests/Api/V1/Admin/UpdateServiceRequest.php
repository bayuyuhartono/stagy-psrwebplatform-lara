<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContentBlocks\ContentBlockRegistry;
use App\Features\Services\Enums\ServiceStatus;
use App\Models\Service;
use App\Rules\ValidContentBlocks;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('service'));
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
            'slug' => ['sometimes', 'required', 'string', 'max:255', 'alpha_dash:ascii', Rule::unique(Service::class)->ignore($this->route('service'))],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::enum(ServiceStatus::class)],
            'blocks' => ['sometimes', 'array', 'max:100', new ValidContentBlocks($blockRegistry)],
            'position' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
