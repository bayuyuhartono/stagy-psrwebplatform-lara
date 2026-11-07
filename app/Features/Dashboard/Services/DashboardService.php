<?php

namespace App\Features\Dashboard\Services;

use App\Features\Dashboard\Repositories\DashboardRepository;

class DashboardService
{
    public function __construct(private DashboardRepository $dashboard) {}

    /** @return array<string, mixed> */
    public function get(): array
    {
        $content = $this->dashboard->contentMetrics();
        $contentTotal = collect($content)->sum('total');

        return [
            'content' => [...$content, 'post_types' => $this->dashboard->postTypeMetrics()],
            'contacts' => $this->dashboard->contactMetrics(),
            'seo' => $this->dashboard->seoMetrics($contentTotal),
            'media' => $this->dashboard->mediaMetrics(),
            'recent_activity' => $this->dashboard->recentActivity()->map(fn ($activity): array => [
                'id' => $activity->id,
                'event' => $activity->event,
                'user' => $activity->user ? [
                    'id' => $activity->user->id,
                    'name' => $activity->user->name,
                ] : null,
                'auditable_type' => $activity->auditable_type,
                'auditable_id' => $activity->auditable_id,
                'created_at' => $activity->created_at,
            ])->all(),
            'system' => $this->dashboard->systemMetrics(),
            'generated_at' => now(),
        ];
    }
}
