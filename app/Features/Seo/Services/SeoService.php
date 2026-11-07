<?php

namespace App\Features\Seo\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Seo\Repositories\SeoRepository;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;

class SeoService
{
    public function __construct(
        private SeoRepository $seo,
        private SeoScoreCalculator $scoreCalculator,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function updateForPage(Page $page, array $attributes): Seo
    {
        return $this->updateForContent($page, $attributes);
    }

    /** @param array<string, mixed> $attributes */
    public function updateForContent(Page|Portfolio|Post|Service $content, array $attributes): Seo
    {
        $existingSeo = $this->seo->forContent($content);
        $oldValues = $existingSeo?->getAttributes() ?? [];
        $attributes['score'] = $this->scoreCalculator->calculate($attributes);
        $updatedSeo = $this->seo->updateOrCreateForContent($content, $attributes);
        $this->auditLogger->record('seo.updated', $updatedSeo, $oldValues, $updatedSeo->getAttributes());

        return $updatedSeo;
    }
}
