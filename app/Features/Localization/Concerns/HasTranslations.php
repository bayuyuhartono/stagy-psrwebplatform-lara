<?php

namespace App\Features\Localization\Concerns;

use App\Features\Localization\Services\LocaleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Content written once per locale. Rows that translate each other share a
 * `translation_group_id`; each locale may appear at most once per group.
 *
 * @mixin Model
 */
trait HasTranslations
{
    public static function bootHasTranslations(): void
    {
        static::creating(function (self $model): void {
            $model->locale ??= app(LocaleRegistry::class)->default();
            $model->translation_group_id ??= (string) Str::uuid7();
        });
    }

    public function initializeHasTranslations(): void
    {
        $this->mergeFillable(['locale', 'translation_group_id']);
    }

    /**
     * Every row in the translation group, this one included.
     *
     * @return HasMany<static, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(static::class, 'translation_group_id', 'translation_group_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where($this->qualifyColumn('locale'), $locale);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCurrentLocale(Builder $query): Builder
    {
        return $this->scopeForLocale($query, app(LocaleRegistry::class)->current());
    }
}
