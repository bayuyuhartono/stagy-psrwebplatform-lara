<?php

namespace App\Models;

use App\Features\WebsiteSettings\Enums\WebsiteSettingGroup;
use Database\Factories\WebsiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['group', 'settings', 'is_public'])]
class WebsiteSetting extends Model
{
    /** @use HasFactory<WebsiteSettingFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'group' => WebsiteSettingGroup::class,
            'settings' => 'array',
            'is_public' => 'boolean',
        ];
    }
}
