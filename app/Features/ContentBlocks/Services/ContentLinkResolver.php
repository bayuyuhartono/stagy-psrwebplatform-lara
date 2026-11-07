<?php

namespace App\Features\ContentBlocks\Services;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use App\Features\WebsiteSettings\Repositories\WebsiteSettingRepository;
use App\Features\WebsiteSettings\Services\WhatsAppLink;
use App\Models\WebsiteSetting;
use App\Rules\ContentLink;

/**
 * Turns a link stored on a content block into an href. The "whatsapp" keyword
 * becomes the site's WhatsApp chat link, or null when no number is configured
 * so the theme can hide that button.
 *
 * Bound as a scoped singleton so the contact settings are read once per request.
 */
class ContentLinkResolver
{
    private ?string $whatsappUrl = null;

    private bool $whatsappResolved = false;

    public function __construct(
        private WebsiteSettingRepository $settings,
        private WhatsAppLink $whatsAppLink,
    ) {}

    public function resolve(?string $link): ?string
    {
        if ($link === null || $link === '') {
            return null;
        }

        return $link === ContentLink::WHATSAPP ? $this->whatsappUrl() : $link;
    }

    /**
     * Whether the link leaves the site and should open in a new tab.
     */
    public function opensNewTab(?string $href): bool
    {
        if ($href === null || ! str_starts_with($href, 'http')) {
            return false;
        }

        return parse_url($href, PHP_URL_HOST) !== parse_url(url('/'), PHP_URL_HOST);
    }

    public function whatsappUrl(): ?string
    {
        if (! $this->whatsappResolved) {
            $contact = $this->settings->public()->first(fn (WebsiteSetting $setting): bool => $setting->group === WebsiteSettingGroup::Contact)?->settings ?? [];
            $this->whatsappUrl = $this->whatsAppLink->url($contact['whatsapp'] ?? $contact['phone'] ?? null);
            $this->whatsappResolved = true;
        }

        return $this->whatsappUrl;
    }
}
