<?php

namespace App\Features\Integrations\Services;

use App\Features\Integrations\Enums\IntegrationProvider;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server-side verification of a reCAPTCHA response token.
 *
 * The secret is read from the encrypted `secret_config` and never leaves the
 * server. Verification is skipped entirely while the provider is disabled, so
 * the contact form keeps working on installs that have not configured it.
 */
class RecaptchaVerifier
{
    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function isEnabled(): bool
    {
        return $this->integration() !== null;
    }

    public function siteKey(): ?string
    {
        return $this->integration()?->public_config['site_key'] ?? null;
    }

    /**
     * Verify a token with Google. Returns false on a failed or unverifiable response.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        $secret = $this->integration()?->secret_config['secret_key'] ?? null;

        if ($secret === null) {
            // Enabled but unconfigured: fail closed rather than wave submissions through.
            Log::warning('reCAPTCHA is enabled but no secret key is configured; rejecting submission.');

            return false;
        }

        if (! is_string($token) || $token === '') {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post(self::VERIFY_ENDPOINT, array_filter([
                    'secret' => $secret,
                    'response' => $token,
                    'remoteip' => $ip,
                ]));
        } catch (\Throwable $exception) {
            Log::warning('reCAPTCHA verification request failed.', ['exception' => $exception->getMessage()]);

            return false;
        }

        return $response->successful() && ($response->json('success') === true);
    }

    private function integration(): ?Integration
    {
        return Integration::query()
            ->where('provider', IntegrationProvider::Recaptcha)
            ->where('enabled', true)
            ->first();
    }
}
