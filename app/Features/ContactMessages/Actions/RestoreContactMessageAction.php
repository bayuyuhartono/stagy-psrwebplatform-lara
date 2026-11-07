<?php

namespace App\Features\ContactMessages\Actions;

use App\Features\ContactMessages\Services\ContactMessageService;
use App\Models\ContactMessage;

class RestoreContactMessageAction
{
    public function __construct(private ContactMessageService $service) {}

    public function handle(ContactMessage $message): ContactMessage
    {
        return $this->service->restore($message);
    }
}
