<?php

namespace App\Features\Portfolios\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Portfolios\Enums\PortfolioStatus;
use App\Features\Portfolios\Repositories\PortfolioRepository;
use App\Models\Portfolio;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function __construct(private PortfolioRepository $portfolios, private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Portfolio
    {
        return DB::transaction(function () use ($attributes): Portfolio {
            [$attributes, $categoryIds, $tagIds] = $this->extractTaxonomies($this->normalizePublication($attributes));
            $portfolio = $this->portfolios->create($attributes);
            $portfolio->categories()->sync($categoryIds);
            $portfolio->tags()->sync($tagIds);
            $this->auditLogger->record('portfolio.created', $portfolio, newValues: $portfolio->getAttributes());

            return $portfolio->load(['author:id,name', 'coverImage', 'categories:id,name,slug', 'tags:id,name,slug']);
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Portfolio $portfolio, array $attributes): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $attributes): Portfolio {
            $oldValues = $portfolio->getAttributes();
            [$attributes, $categoryIds, $tagIds] = $this->extractTaxonomies($this->normalizePublication($attributes, $portfolio));
            $portfolio = $this->portfolios->update($portfolio, $attributes);
            if ($categoryIds !== null) {
                $portfolio->categories()->sync($categoryIds);
            }
            if ($tagIds !== null) {
                $portfolio->tags()->sync($tagIds);
            }
            $this->auditLogger->record('portfolio.updated', $portfolio, $oldValues, $portfolio->getAttributes());

            return $portfolio->load(['author:id,name', 'coverImage', 'categories:id,name,slug', 'tags:id,name,slug']);
        });
    }

    public function delete(Portfolio $portfolio): void
    {
        $this->auditLogger->record('portfolio.deleted', $portfolio, oldValues: $portfolio->getAttributes());
        $portfolio->delete();
    }

    public function restore(Portfolio $portfolio): Portfolio
    {
        $portfolio->restore();
        $this->auditLogger->record('portfolio.restored', $portfolio, newValues: $portfolio->getAttributes());

        return $portfolio->load(['author:id,name', 'coverImage', 'categories:id,name,slug', 'tags:id,name,slug']);
    }

    /** @param array<string, mixed> $attributes
     * @return array{array<string, mixed>, ?array<int, string>, ?array<int, string>}
     */
    private function extractTaxonomies(array $attributes): array
    {
        $categoryIds = Arr::pull($attributes, 'category_ids');
        $tagIds = Arr::pull($attributes, 'tag_ids');

        return [$attributes, $categoryIds, $tagIds];
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizePublication(array $attributes, ?Portfolio $portfolio = null): array
    {
        $status = $attributes['status'] ?? $portfolio?->status?->value ?? PortfolioStatus::Draft->value;
        if ($status === PortfolioStatus::Published->value && empty($attributes['published_at']) && $portfolio?->published_at === null) {
            $attributes['published_at'] = now();
        }
        if ($status === PortfolioStatus::Draft->value) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }
}
