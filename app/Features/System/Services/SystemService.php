<?php

namespace App\Features\System\Services;

use App\Features\System\Repositories\SystemRepository;

class SystemService
{
    public function __construct(private SystemRepository $system) {}

    /** @return array<string, mixed> */
    public function settings(): array
    {
        return [
            'application' => [
                'name' => (string) config('app.name'),
                'environment' => (string) config('app.env'),
                'debug' => (bool) config('app.debug'),
                'locale' => (string) config('app.locale'),
                'fallback_locale' => (string) config('app.fallback_locale'),
                'timezone' => (string) config('app.timezone'),
                'maintenance_mode' => app()->isDownForMaintenance(),
            ],
            'runtime' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'services' => [
                'database_connection' => (string) config('database.default'),
                'database_driver' => (string) config('database.connections.'.config('database.default').'.driver'),
                'cache_store' => (string) config('cache.default'),
                'queue_connection' => (string) config('queue.default'),
                'filesystem_disk' => (string) config('filesystems.default'),
                'media_disk' => (string) config('media.disk'),
                'media_max_size' => (string) config('media.max_size'),
            ],
            'email' => $this->system->emailReadiness(),
        ];
    }

    /** @return array<string, mixed> */
    public function health(): array
    {
        $checks = [
            'database' => $this->system->databaseHealth(),
            'cache' => $this->system->cacheHealth(),
            'storage' => $this->storageHealth(),
            'queue' => $this->system->queueHealth(),
            'email' => ['status' => $this->system->emailReadiness()['configured'] ? 'ok' : 'warning'],
        ];

        return [
            'status' => collect($checks)->contains(fn (array $check): bool => $check['status'] === 'error') ? 'degraded' : 'ok',
            'checks' => $checks,
            'checked_at' => now(),
        ];
    }

    /** @return array{status: string, disk: string, driver: string, writable?: bool, free_bytes?: int, total_bytes?: int} */
    private function storageHealth(): array
    {
        $disk = (string) config('media.disk');
        $driver = (string) config("filesystems.disks.{$disk}.driver");
        $health = ['status' => 'ok', 'disk' => $disk, 'driver' => $driver];

        if ($driver !== 'local') {
            return $health;
        }

        $root = (string) config("filesystems.disks.{$disk}.root");
        $writable = is_dir($root) && is_writable($root);
        $freeBytes = @disk_free_space($root);
        $totalBytes = @disk_total_space($root);

        return [
            ...$health,
            'status' => $writable ? 'ok' : 'error',
            'writable' => $writable,
            'free_bytes' => $freeBytes === false ? 0 : (int) $freeBytes,
            'total_bytes' => $totalBytes === false ? 0 : (int) $totalBytes,
        ];
    }
}
