<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('email-settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'host' => ['nullable', 'required_if:enabled,true', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', 'in:tls,ssl'],
            'username' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'from_address' => ['nullable', 'required_if:enabled,true', 'email:rfc', 'max:255'],
            'from_name' => ['nullable', 'required_if:enabled,true', 'string', 'max:255'],
            'notification_to' => ['nullable', 'email:rfc', 'max:255'],
            'auto_reply_enabled' => ['required', 'boolean'],
            'auto_reply_subject' => ['nullable', 'required_if:auto_reply_enabled,true', 'string', 'max:255'],
            'auto_reply_body' => ['nullable', 'required_if:auto_reply_enabled,true', 'string', 'max:10000'],
        ];
    }
}
