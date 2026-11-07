<?php

namespace App\Features\Taxonomies\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Localization\Services\TranslationLinker;
use App\Features\Taxonomies\Repositories\TaxonomyRepository;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Validation\ValidationException;

class TaxonomyService
{
    public function __construct(
        private TaxonomyRepository $taxonomies,
        private AuditLogger $auditLogger,
        private TranslationLinker $translations,
    ) {}

    /** @param class-string<Category|Tag> $modelClass
     * @param  array<string, mixed>  $attributes
     */
    public function create(string $modelClass, array $attributes): Category|Tag
    {
        $taxonomy = $this->taxonomies->create($modelClass, $this->translations->link($modelClass, $attributes));
        $this->auditLogger->record('taxonomy.created', $taxonomy, newValues: $taxonomy->getAttributes());

        return $taxonomy->loadCount(['posts', 'portfolios']);
    }

    /** @param array<string, mixed> $attributes */
    public function update(Category|Tag $taxonomy, array $attributes): Category|Tag
    {
        $oldValues = $taxonomy->getAttributes();
        $taxonomy = $this->taxonomies->update($taxonomy, $attributes);
        $this->auditLogger->record('taxonomy.updated', $taxonomy, $oldValues, $taxonomy->getAttributes());

        return $taxonomy->loadCount(['posts', 'portfolios']);
    }

    public function delete(Category|Tag $taxonomy): void
    {
        if ($taxonomy->posts()->exists() || $taxonomy->portfolios()->exists()) {
            throw ValidationException::withMessages(['taxonomy' => 'This taxonomy is in use and cannot be deleted.']);
        }

        $this->auditLogger->record('taxonomy.deleted', $taxonomy, oldValues: $taxonomy->getAttributes());
        $taxonomy->delete();
    }
}
