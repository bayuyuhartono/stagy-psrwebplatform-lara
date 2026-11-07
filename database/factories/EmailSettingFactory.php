<?php

namespace Database\Factories;

use App\Models\EmailSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailSetting>
 */
class EmailSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => 'default',
            'enabled' => false,
            'host' => 'smtp.example.com',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'mailer@example.com',
            'password' => 'secret-password',
            'from_address' => 'hello@example.com',
            'from_name' => 'Example Website',
            'notification_to' => 'inbox@example.com',
            'auto_reply_enabled' => false,
            'auto_reply_subject' => 'We received your message',
            'auto_reply_body' => 'Thank you for contacting us. We will reply shortly.',
        ];
    }
}
