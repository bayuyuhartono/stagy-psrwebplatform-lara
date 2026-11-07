<?php

namespace App\Models;

use App\Features\Integrations\Enums\IntegrationProvider;
use Database\Factories\IntegrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['provider', 'enabled', 'public_config', 'secret_config'])]
class Integration extends Model
{
    /** @use HasFactory<IntegrationFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    /** @var list<string> */
    protected $hidden = ['secret_config'];

    protected function casts(): array
    {
        return [
            'provider' => IntegrationProvider::class,
            'enabled' => 'boolean',
            'public_config' => 'array',
            'secret_config' => 'encrypted:array',
        ];
    }
}
