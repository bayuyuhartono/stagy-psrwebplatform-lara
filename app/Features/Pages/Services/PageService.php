<?php

namespace App\Features\Pages\Services;

use App\Features\Audit\Services\AuditLogger;
use App\Features\Pages\Enums\PageStatus;
use App\Features\Pages\Repositories\PageRepository;
use App\Models\Page;

class PageService
{
    public function __construct(
        private PageRepository $pages,
        private AuditLogger $auditLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Page
    {
        $attributes = $this->normalizePublication($attributes);
        $page = $this->pages->create($attributes);
        $this->auditLogger->record('page.created', $page, newValues: $page->getAttributes());

        return $page;
    }

    /** @param array<string, mixed> $attributes */
    public function update(Page $page, array $attributes): Page
    {
        $oldValues = $page->getAttributes();
        $updatedPage = $this->pages->update($page, $this->normalizePublication($attributes, $page));
        $this->auditLogger->record('page.updated', $updatedPage, $oldValues, $updatedPage->getAttributes());

        return $updatedPage;
    }

    public function delete(Page $page): void
    {
        $this->auditLogger->record('page.deleted', $page, oldValues: $page->getAttributes());
        $page->delete();
    }

    public function restore(Page $page): Page
    {
        $page->restore();
        $this->auditLogger->record('page.restored', $page, newValues: $page->getAttributes());

        return $page;
    }

    /** @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function normalizePublication(array $attributes, ?Page $page = null): array
    {
        $status = $attributes['status'] ?? $page?->status?->value ?? PageStatus::Draft->value;

        if ($status === PageStatus::Published->value && empty($attributes['published_at']) && $page?->published_at === null) {
            $attributes['published_at'] = now();
        }

        if ($status === PageStatus::Draft->value) {
            $attributes['published_at'] = null;
        }

        return $attributes;
    }
}
