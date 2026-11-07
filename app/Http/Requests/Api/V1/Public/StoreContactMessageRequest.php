<?php

namespace App\Http\Requests\Api\V1\Public;

use App\Features\Integrations\Services\RecaptchaVerifier;
use App\Rules\ValidRecaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $verifier = app(RecaptchaVerifier::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+() .-]+$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            // Only demanded while the reCAPTCHA integration is enabled, so installs
            // that have not configured it keep a working contact form.
            'recaptcha_token' => $verifier->isEnabled()
                ? ['required', 'string', new ValidRecaptcha($verifier, $this->ip())]
                : ['nullable', 'string'],
        ];
    }

    /**
     * The captcha token is a transport concern — keep it out of the persisted payload.
     *
     * @return array<string, mixed>
     */
    public function submissionData(): array
    {
        return collect($this->validated())->except('recaptcha_token')->all();
    }
}
