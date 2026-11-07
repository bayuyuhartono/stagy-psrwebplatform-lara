<?php

namespace App\Features\EmailSettings\Services;

use App\Models\EmailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class EmailConfigurationService
{
    public function configure(EmailSetting $settings): void
    {
        Config::set('mail.mailers.cms-smtp', [
            'transport' => 'smtp',
            'scheme' => $settings->encryption === 'ssl' ? 'smtps' : 'smtp',
            'host' => $settings->host,
            'port' => $settings->port,
            'username' => $settings->username,
            'password' => $settings->password,
            'timeout' => 10,
            'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
        ]);

        Mail::purge('cms-smtp');
    }
}
