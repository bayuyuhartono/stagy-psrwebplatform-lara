<?php

namespace App\Models;

use Database\Factories\MenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'location', 'is_active'])]
class Menu extends Model
{
    /** @use HasFactory<MenuFactory> */
    use HasFactory, HasUuids, SoftDeletes;

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position')->orderBy('created_at');
    }

    public function menuItems(): HasMany
    {
        return $this->items();
    }

    public function rootItems(): HasMany
    {
        return $this->items()->whereNull('parent_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
