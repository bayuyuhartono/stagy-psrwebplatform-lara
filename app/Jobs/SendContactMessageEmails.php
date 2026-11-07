<?php

namespace App\Jobs;

use App\Features\EmailSettings\Repositories\EmailSettingRepository;
use App\Features\EmailSettings\Services\EmailConfigurationService;
use App\Mail\ContactMessageAutoReply;
use App\Mail\ContactMessageNotification;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendContactMessageEmails implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(public ContactMessage $contactMessage)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(
        EmailSettingRepository $emailSettings,
        EmailConfigurationService $emailConfiguration,
    ): void {
        $settings = $emailSettings->get();

        if (! $settings->enabled) {
            return;
        }

        $emailConfiguration->configure($settings);
        $mailer = Mail::mailer('cms-smtp');

        if (filled($settings->notification_to)) {
            $mailer->to($settings->notification_to)->send(new ContactMessageNotification(
                contactMessage: $this->contactMessage,
                fromAddress: $settings->from_address,
                fromName: $settings->from_name,
            ));
        }

        if ($settings->auto_reply_enabled) {
            $mailer->to($this->contactMessage->email)->send(new ContactMessageAutoReply(
                recipientName: $this->contactMessage->name,
                replyBody: (string) $settings->auto_reply_body,
                replySubject: (string) $settings->auto_reply_subject,
                fromAddress: $settings->from_address,
                fromName: $settings->from_name,
            ));
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Contact message email delivery failed.', [
            'contact_message_id' => $this->contactMessage->getKey(),
            'error' => $exception?->getMessage(),
        ]);
    }
}
