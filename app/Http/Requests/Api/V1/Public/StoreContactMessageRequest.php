<?php

namespace App\Http\Requests\Api\V1\Public;

use App\Features\ContactMessages\Enums\ContactFormType;
use App\Features\ContactMessages\Enums\GuestEstimate;
use App\Features\Integrations\Services\RecaptchaVerifier;
use App\Rules\ValidRecaptcha;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $captcha = [
            // Only demanded while the reCAPTCHA integration is enabled, so installs
            // that have not configured it keep a working contact form.
            'recaptcha_token' => $verifier->isEnabled()
                ? ['required', 'string', new ValidRecaptcha($verifier, $this->ip())]
                : ['nullable', 'string'],
        ];

        if ($this->isDemoRequest()) {
            return [
                'form_type' => ['required', Rule::enum(ContactFormType::class)],
                'name' => ['required', 'string', 'max:255'],
                'company' => ['nullable', 'string', 'max:255'],
                'whatsapp' => ['required', 'string', 'max:50', 'regex:/^[0-9+() .-]{6,}$/'],
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'guest_estimate' => ['required', Rule::enum(GuestEstimate::class)],
                'event_date' => ['nullable', 'date', 'after_or_equal:today'],
                'interested_plan' => ['nullable', 'string', 'max:80'],
                'message' => ['nullable', 'string', 'max:10000'],
                'consent' => ['accepted'],
                ...$captcha,
            ];
        }

        return [
            'form_type' => ['sometimes', Rule::enum(ContactFormType::class)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+() .-]+$/'],
            'company' => ['nullable', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:10000'],
            ...$captcha,
        ];
    }

    public function isDemoRequest(): bool
    {
        return $this->input('form_type') === ContactFormType::Demo->value;
    }

    /**
     * The captcha token is a transport concern — keep it out of the persisted
     * payload — and consent is recorded as the moment it was given.
     *
     * @return array<string, mixed>
     */
    public function submissionData(): array
    {
        $data = collect($this->validated())->except(['recaptcha_token', 'consent'])->all();

        if ($this->isDemoRequest()) {
            $data['consented_at'] = now();
        }

        return $data;
    }
}
