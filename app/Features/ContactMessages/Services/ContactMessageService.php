<?php

namespace App\Features\ContactMessages\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\ContactMessages\Enums\ContactFormType;
use App\Features\ContactMessages\Repositories\ContactMessageRepository;
use App\Jobs\SendContactMessageEmails;
use App\Models\ContactMessage;

class ContactMessageService
{
    public function __construct(private ContactMessageRepository $messages, private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function submit(array $attributes): ContactMessage
    {
        $attributes = [...$attributes, 'locale' => $attributes['locale'] ?? app()->getLocale(), 'submitted_at' => now()];

        if (($attributes['form_type'] ?? null) === ContactFormType::Demo->value) {
            $attributes['subject'] ??= $this->demoSubject($attributes);
        }

        $message = $this->messages->create($attributes);
        $this->auditLogger->record('contact-message.submitted', $message, newValues: ['status' => $message->status->value]);
        SendContactMessageEmails::dispatch($message);

        return $message;
    }

    /**
     * Demo requests carry no subject of their own; name them after who is asking.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function demoSubject(array $attributes): string
    {
        $requester = filled($attributes['company'] ?? null) ? $attributes['company'] : $attributes['name'];

        return ($attributes['locale'] === 'id' ? 'Permintaan demo — ' : 'Demo request — ').$requester;
    }

    /** @param array<string, mixed> $attributes */
    public function update(ContactMessage $message, array $attributes): ContactMessage
    {
        $oldValues = $message->getAttributes();
        $message = $this->messages->update($message, $attributes)->load('assignee:id,name,email');
        $this->auditLogger->record('contact-message.updated', $message, $oldValues, $message->getAttributes());

        return $message;
    }

    public function delete(ContactMessage $message): void
    {
        $this->auditLogger->record('contact-message.deleted', $message, oldValues: $message->getAttributes());
        $message->delete();
    }

    public function restore(ContactMessage $message): ContactMessage
    {
        $message->restore();
        $this->auditLogger->record('contact-message.restored', $message, newValues: $message->getAttributes());

        return $message->load('assignee:id,name,email');
    }
}
