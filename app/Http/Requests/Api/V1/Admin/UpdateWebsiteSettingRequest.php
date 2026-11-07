<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Features\Themes\Services\ThemeManager;
use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Models\Media;
use App\Rules\LocalizedText;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebsiteSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasPermission('website-settings.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return match (WebsiteSettingGroup::from((string) $this->route('group'))) {
            WebsiteSettingGroup::General => [
                'site_name' => ['required', 'string', 'max:255'],
                'tagline' => ['nullable', new LocalizedText(500)],
                'locale' => ['required', 'string', 'regex:/^[a-z]{2}(?:_[A-Z]{2})?$/'],
                'timezone' => ['required', 'timezone:all'],
            ],
            WebsiteSettingGroup::Branding => [
                'logo_id' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')->whereNull('deleted_at')],
                'favicon_id' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')->whereNull('deleted_at')],
                'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
                'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            ],
            WebsiteSettingGroup::Contact => [
                'email' => ['nullable', 'email:rfc', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\-\s]+$/'],
                'whatsapp' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+().\-\s]+$/'],
                'address' => ['nullable', 'string', 'max:1000'],
                'business_hours' => ['nullable', new LocalizedText(160)],
            ],
            WebsiteSettingGroup::Social => [
                'facebook_url' => ['nullable', 'url:http,https', 'max:2048'],
                'instagram_url' => ['nullable', 'url:http,https', 'max:2048'],
                'linkedin_url' => ['nullable', 'url:http,https', 'max:2048'],
                'tiktok_url' => ['nullable', 'url:http,https', 'max:2048'],
                'youtube_url' => ['nullable', 'url:http,https', 'max:2048'],
                'x_url' => ['nullable', 'url:http,https', 'max:2048'],
            ],
            WebsiteSettingGroup::Seo => [
                'default_title' => ['nullable', new LocalizedText(70)],
                'default_description' => ['nullable', new LocalizedText(170)],
                'social_image_id' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')->whereNull('deleted_at')],
            ],
            WebsiteSettingGroup::Experience => [
                'maintenance_mode' => ['required', 'boolean'],
                'announcement_enabled' => ['required', 'boolean'],
                'announcement' => ['nullable', new LocalizedText(1000), 'required_if:announcement_enabled,true'],
            ],
            WebsiteSettingGroup::Theme => $this->themeRules(),
        };
    }

    /**
     * Theme settings are declared by the active theme's manifest rather than fixed
     * in code, so the rules are derived from its schema at request time.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    private function themeRules(): array
    {
        $manifest = app(ThemeManager::class)->active();

        return collect($manifest->settings)
            ->mapWithKeys(fn (array $schema, string $key): array => [
                $key => match ($schema['type']) {
                    'boolean' => ['required', 'boolean'],
                    'select' => ['required', Rule::in($schema['options'] ?? [])],
                    'integer' => ['required', 'integer'],
                    'media' => ['nullable', 'uuid', Rule::exists(Media::class, 'id')->whereNull('deleted_at')],
                    default => ['nullable', 'string', 'max:1000'],
                },
            ])
            ->all();
    }
}
