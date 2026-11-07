<?php

namespace App\Features\System\Repositories;

use App\Models\EmailSetting;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class SystemRepository
{
    /** @return array{status: string, latency_ms?: float, message?: string} */
    public function databaseHealth(): array
    {
        $startedAt = hrtime(true);

        try {
            DB::connection()->getPdo();

            return [
                'status' => 'ok',
                'latency_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'error', 'message' => 'Database connection failed.'];
        }
    }

    /** @return array{status: string, message?: string} */
    public function cacheHealth(): array
    {
        $key = 'system-health:'.Str::uuid();

        try {
            Cache::put($key, 'ok', 10);
            $healthy = Cache::get($key) === 'ok';
            Cache::forget($key);

            return $healthy
                ? ['status' => 'ok']
                : ['status' => 'error', 'message' => 'Cache read/write verification failed.'];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'error', 'message' => 'Cache connection failed.'];
        }
    }

    /** @return array{status: string, pending?: int, delayed?: int, reserved?: int, failed?: int, message?: string} */
    public function queueHealth(): array
    {
        try {
            $queue = Queue::connection();
            $failedJobs = app(FailedJobProviderInterface::class);

            return [
                'status' => 'ok',
                'pending' => $queue->pendingSize(),
                'delayed' => $queue->delayedSize(),
                'reserved' => $queue->reservedSize(),
                'failed' => count($failedJobs->ids()),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return ['status' => 'error', 'message' => 'Queue status could not be read.'];
        }
    }

    /** @return array{enabled: bool, configured: bool} */
    public function emailReadiness(): array
    {
        $settings = EmailSetting::query()->first();

        return [
            'enabled' => (bool) $settings?->enabled,
            'configured' => $settings !== null
                && filled($settings->host)
                && filled($settings->from_address)
                && filled($settings->username)
                && filled($settings->password),
        ];
    }
}
