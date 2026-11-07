<?php

namespace App\Features\ContactMessages\Actions;

use App\Features\ContactMessages\Services\ContactMessageService;
use App\Models\ContactMessage;

class DeleteContactMessageAction
{
    public function __construct(private ContactMessageService $service) {}

    public function handle(ContactMessage $message): void
    {
        $this->service->delete($message);
    }
}
