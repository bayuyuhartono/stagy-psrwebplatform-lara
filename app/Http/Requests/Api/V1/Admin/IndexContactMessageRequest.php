<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\ContactMessages\Enums\ContactFormType;
use App\Features\ContactMessages\Enums\ContactMessageStatus;
use App\Features\ContactMessages\Enums\GuestEstimate;
use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', ContactMessage::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(ContactMessageStatus::class)],
            'form_type' => ['sometimes', Rule::enum(ContactFormType::class)],
            'guest_estimate' => ['sometimes', Rule::enum(GuestEstimate::class)],
            'event_from' => ['sometimes', 'date'],
            'event_to' => ['sometimes', 'date', 'after_or_equal:event_from'],
            'assigned_to' => ['sometimes', 'uuid', Rule::exists(User::class, 'id')],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
