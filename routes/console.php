<?php

use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Enforces config('audit.retention_days'); a retention of 0 makes this a no-op.
Schedule::command('model:prune', ['--model' => [AuditLog::class]])
    ->daily()
    ->onOneServer()
    ->withoutOverlapping();
