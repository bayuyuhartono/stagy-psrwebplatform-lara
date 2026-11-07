<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Integrations\Enums\IntegrationProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('integrations.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = IntegrationProvider::from((string) $this->route('provider'));

        return [
            'enabled' => ['required', 'boolean'],
            ...match ($provider) {
                IntegrationProvider::GoogleAnalytics => [
                    'measurement_id' => ['nullable', 'required_if:enabled,true', 'string', 'regex:/^G-[A-Z0-9]+$/', 'max:32'],
                ],
                IntegrationProvider::GoogleSearchConsole => [
                    'verification_code' => ['nullable', 'required_if:enabled,true', 'string', 'regex:/^[A-Za-z0-9_-]+$/', 'max:255'],
                ],
                IntegrationProvider::GoogleTagManager => [
                    'container_id' => ['nullable', 'required_if:enabled,true', 'string', 'regex:/^GTM-[A-Z0-9]+$/', 'max:32'],
                ],
                IntegrationProvider::MetaPixel => [
                    'pixel_id' => ['nullable', 'required_if:enabled,true', 'string', 'regex:/^[0-9]+$/', 'max:32'],
                ],
                IntegrationProvider::MicrosoftClarity => [
                    'project_id' => ['nullable', 'required_if:enabled,true', 'string', 'alpha_num:ascii', 'max:32'],
                ],
                IntegrationProvider::GoogleMaps => [
                    'api_key' => ['nullable', 'required_if:enabled,true', 'string', 'max:255'],
                ],
                IntegrationProvider::Recaptcha => [
                    'site_key' => ['nullable', 'required_if:enabled,true', 'string', 'max:255'],
                    'secret_key' => ['sometimes', 'nullable', 'string', 'max:255'],
                ],
                IntegrationProvider::CustomScripts => [
                    'head_html' => ['nullable', 'string', 'max:20000'],
                    'body_html' => ['nullable', 'string', 'max:20000'],
                ],
            },
        ];
    }
}
