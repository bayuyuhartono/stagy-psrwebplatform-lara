<?php

namespace App\Features\ContactMessages\Actions;

use App\Features\ContactMessages\Services\ContactMessageService;
use App\Models\ContactMessage;

class SubmitContactMessageAction
{
    public function __construct(private ContactMessageService $service) {}

    /** @param array<string,mixed> $attributes */
    public function handle(array $attributes): ContactMessage
    {
        return $this->service->submit($attributes);
    }
}
