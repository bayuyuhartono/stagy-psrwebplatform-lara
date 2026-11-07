<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContactMessages\Enums\ContactMessageStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contact_message'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::enum(ContactMessageStatus::class)],
            'assigned_to' => ['nullable', 'uuid', Rule::exists(User::class, 'id')],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
