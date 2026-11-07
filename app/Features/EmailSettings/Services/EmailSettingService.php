<?php

namespace App\Features\EmailSettings\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\EmailSettings\Repositories\EmailSettingRepository;
use App\Mail\TestEmail;
use App\Models\EmailSetting;
use Illuminate\Support\Facades\Mail;

class EmailSettingService
{
    public function __construct(
        private EmailSettingRepository $emailSettings,
        private EmailConfigurationService $emailConfiguration,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function update(array $attributes): EmailSetting
    {
        $emailSetting = $this->emailSettings->get();
        $oldValues = $this->auditValues($emailSetting);

        if (! array_key_exists('password', $attributes)) {
            unset($attributes['password']);
        }

        $emailSetting = $this->emailSettings->update($emailSetting, $attributes);
        $this->auditLogger->record('email_setting.updated', $emailSetting, $oldValues, $this->auditValues($emailSetting));

        return $emailSetting;
    }

    public function sendTest(string $recipient): void
    {
        $emailSetting = $this->emailSettings->get();
        $this->emailConfiguration->configure($emailSetting);

        Mail::mailer('cms-smtp')->to($recipient)->send(new TestEmail(
            fromAddress: $emailSetting->from_address,
            fromName: $emailSetting->from_name,
        ));
        $this->auditLogger->record('email_setting.test_sent', $emailSetting, newValues: ['recipient' => $recipient]);
    }

    /** @return array<string, mixed> */
    private function auditValues(EmailSetting $emailSetting): array
    {
        return [
            'enabled' => $emailSetting->enabled,
            'host' => $emailSetting->host,
            'port' => $emailSetting->port,
            'encryption' => $emailSetting->encryption,
            'from_address' => $emailSetting->from_address,
            'from_name' => $emailSetting->from_name,
            'notification_to' => $emailSetting->notification_to,
            'auto_reply_enabled' => $emailSetting->auto_reply_enabled,
            'auto_reply_subject' => $emailSetting->auto_reply_subject,
            'auto_reply_body' => $emailSetting->auto_reply_body,
            'credentials_configured' => filled($emailSetting->username) || filled($emailSetting->password),
        ];
    }
}
