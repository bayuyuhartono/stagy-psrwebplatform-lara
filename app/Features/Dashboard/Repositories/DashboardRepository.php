<?php

namespace App\Features\Dashboard\Repositories;

use App\Features\ContactMessages\Enums\ContactMessageStatus;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use App\Models\EmailSetting;
use App\Models\FeatureFlag;
use App\Models\Media;
use App\Models\Page;
use App\Models\Portfolio;
use App\Models\Post;
use App\Models\Seo;
use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{
    /** @return array<string, array<string, int>> */
    public function contentMetrics(): array
    {
        return [
            'pages' => $this->statusMetrics(Page::query()),
            'posts' => $this->statusMetrics(Post::query()),
            'services' => $this->statusMetrics(Service::query()),
            'portfolio' => $this->statusMetrics(Portfolio::query()),
        ];
    }

    /** @return array<string, int> */
    public function postTypeMetrics(): array
    {
        $metrics = Post::query()->toBase()
            ->selectRaw('COUNT(CASE WHEN type = ? THEN 1 END) AS blog', ['blog'])
            ->selectRaw('COUNT(CASE WHEN type = ? THEN 1 END) AS news', ['news'])
            ->first();

        return ['blog' => (int) $metrics->blog, 'news' => (int) $metrics->news];
    }

    /** @return array<string, int> */
    public function contactMetrics(): array
    {
        $metrics = ContactMessage::query()->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS new_count', [ContactMessageStatus::New->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS in_progress', [ContactMessageStatus::InProgress->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS resolved', [ContactMessageStatus::Resolved->value])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS spam', [ContactMessageStatus::Spam->value])
            ->selectRaw('COUNT(CASE WHEN submitted_at >= ? THEN 1 END) AS last_7_days', [now()->subDays(7)])
            ->first();

        return [
            'total' => (int) $metrics->total,
            'new' => (int) $metrics->new_count,
            'in_progress' => (int) $metrics->in_progress,
            'resolved' => (int) $metrics->resolved,
            'spam' => (int) $metrics->spam,
            'last_7_days' => (int) $metrics->last_7_days,
        ];
    }

    /** @return array<string, int|float> */
    public function seoMetrics(int $contentTotal): array
    {
        $metrics = Seo::query()->toBase()
            ->selectRaw('COUNT(*) AS configured')
            ->selectRaw('COALESCE(AVG(score), 0) AS average_score')
            ->selectRaw('COUNT(CASE WHEN score >= ? THEN 1 END) AS good_score', [80])
            ->first();

        return [
            'configured' => (int) $metrics->configured,
            'missing' => max(0, $contentTotal - (int) $metrics->configured),
            'average_score' => round((float) $metrics->average_score, 1),
            'good_score' => (int) $metrics->good_score,
        ];
    }

    /** @return array<string, int> */
    public function mediaMetrics(): array
    {
        $metrics = Media::query()->toBase()->selectRaw('COUNT(*) AS files')->selectRaw('COALESCE(SUM(size), 0) AS bytes')->first();

        return ['files' => (int) $metrics->files, 'bytes' => (int) $metrics->bytes];
    }

    /** @return Collection<int, AuditLog> */
    public function recentActivity(int $limit = 10): Collection
    {
        return AuditLog::query()
            ->select(['id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'created_at'])
            ->with('user:id,name,email')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /** @return array<string, int|bool|string> */
    public function systemMetrics(): array
    {
        $features = FeatureFlag::query()->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN enabled = ? THEN 1 END) AS enabled', [true])
            ->first();

        return [
            'database' => 'ok',
            'environment' => (string) config('app.env'),
            'debug' => (bool) config('app.debug'),
            'queue_connection' => (string) config('queue.default'),
            'cache_store' => (string) config('cache.default'),
            'email_enabled' => (bool) (EmailSetting::query()->value('enabled') ?? false),
            'features_total' => (int) $features->total,
            'features_enabled' => (int) $features->enabled,
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return array<string, int>
     */
    private function statusMetrics($query): array
    {
        $metrics = $query->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS published', ['published'])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS draft', ['draft'])
            ->first();

        return [
            'total' => (int) $metrics->total,
            'published' => (int) $metrics->published,
            'draft' => (int) $metrics->draft,
        ];
    }
}
