<?php

namespace App\Features\ContactMessages\Actions;

use App\Features\ContactMessages\Services\ContactMessageService;
use App\Models\ContactMessage;

class UpdateContactMessageAction
{
    public function __construct(private ContactMessageService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(ContactMessage $message, array $attributes): ContactMessage
    {
        return $this->service->update($message, $attributes);
    }
}
