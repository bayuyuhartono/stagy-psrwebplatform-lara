<?php

namespace App\Features\Taxonomies\Repositories;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TaxonomyRepository
{
    /** @param class-string<Category|Tag> $modelClass */
    public function paginate(string $modelClass, int $perPage = 50): LengthAwarePaginator
    {
        return $modelClass::query()->withCount(['posts', 'portfolios'])->orderBy('name')->paginate($perPage);
    }

    /**
     * Every term for public listings. Categories are translatable, so only the
     * current locale's categories are returned; tags are shared across locales.
     *
     * @param  class-string<Category|Tag>  $modelClass
     */
    public function all(string $modelClass): Collection
    {
        return $modelClass::query()
            ->when($modelClass === Category::class, fn ($query) => $query->forCurrentLocale())
            ->withCount(['posts', 'portfolios'])
            ->orderBy('name')
            ->get();
    }

    public function findCategoryBySlug(string $slug): Category
    {
        return Category::query()->forCurrentLocale()->where('slug', $slug)->firstOrFail();
    }

    /** @param class-string<Category|Tag> $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $modelClass, array $attributes): Category|Tag
    {
        return $modelClass::query()->create($attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Category|Tag $taxonomy, array $attributes): Category|Tag
    {
        $taxonomy->update($attributes);

        return $taxonomy->refresh();
    }
}
