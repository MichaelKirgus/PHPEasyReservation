<?php

namespace App\Services;

use App\Models\JobLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class DiagnosticsService
{
    public function snapshot(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'app' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'queue_connection' => config('queue.default'),
                'cache_store' => config('cache.default'),
            ],
            'latency' => [
                'mysql' => $this->measure(fn () => DB::select('select 1')),
                'redis' => $this->measure(fn () => Redis::ping()),
            ],
            'queue' => $this->queueInfo(),
        ];
    }

    private function measure(callable $callback): array
    {
        $started = microtime(true);
        $status = 'ok';
        $error = null;

        try {
            $callback();
        } catch (\Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        $latency = (int) round((microtime(true) - $started) * 1000);

        return [
            'status' => $status,
            'latency_ms' => $latency,
            'error' => $error,
        ];
    }

    private function queueInfo(): array
    {
        $recent = [];
        $error = null;

        try {
            $recent = JobLog::query()
                ->latest('finished_at')
                ->latest('id')
                ->limit(25)
                ->get(['id', 'job', 'queue', 'status', 'runtime_ms', 'message', 'started_at', 'finished_at'])
                ->map(function (JobLog $log) {
                    return [
                        'id' => $log->id,
                        'job' => $log->job,
                        'queue' => $log->queue,
                        'status' => $log->status,
                        'runtime_ms' => $log->runtime_ms,
                        'message' => $log->message,
                        'started_at' => optional($log->started_at)->toIso8601String(),
                        'finished_at' => optional($log->finished_at)->toIso8601String(),
                    ];
                })
                ->all();
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'connection' => config('queue.default'),
            'recent' => $recent,
            'error' => $error,
        ];
    }
}
