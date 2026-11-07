<?php

namespace App\Models;

use Database\Factories\EmailSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'key', 'enabled', 'host', 'port', 'encryption', 'username', 'password',
    'from_address', 'from_name', 'notification_to', 'auto_reply_enabled',
    'auto_reply_subject', 'auto_reply_body',
])]
class EmailSetting extends Model
{
    /** @use HasFactory<EmailSettingFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /** @var list<string> */
    protected $hidden = ['username', 'password'];

    protected $attributes = [
        'key' => 'default',
        'enabled' => false,
        'port' => 587,
        'auto_reply_enabled' => false,
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'port' => 'integer',
            'username' => 'encrypted',
            'password' => 'encrypted',
            'auto_reply_enabled' => 'boolean',
        ];
    }
}
