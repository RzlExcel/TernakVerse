<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemLogService
{
    /**
     * Maximum tail chunk read size in bytes (2 MB).
     */
    protected const MAX_READ_BYTES = 2097152;

    /**
     * Parse and retrieve logs from storage/logs/laravel.log safely.
     *
     * @return array<int, array{
     *     timestamp: string,
     *     environment: string,
     *     level: string,
     *     message: string,
     *     context: mixed
     * }>
     */
    public function getLogs(int $limit = 200, ?string $level = null, ?string $search = null): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (! file_exists($logPath) || ! is_readable($logPath)) {
            return [];
        }

        $fileSize = (int) filesize($logPath);
        if ($fileSize === 0) {
            return [];
        }

        try {
            $bytesToRead = min($fileSize, self::MAX_READ_BYTES);
            $handle = fopen($logPath, 'rb');
            if (! $handle) {
                return [];
            }

            if ($fileSize > $bytesToRead) {
                fseek($handle, -$bytesToRead, SEEK_END);
            }

            $content = (string) stream_get_contents($handle);
            fclose($handle);

            $lines = explode("\n", $content);
            // Drop potential partial line from the beginning if reading from a middle offset
            if ($fileSize > $bytesToRead && count($lines) > 1) {
                array_shift($lines);
            }

            $pattern = '/^\[(\d{4}-\d{2}-\d{2}[^\]]+)\]\s+([a-zA-Z0-9_-]+)\.([A-Z]+):\s+(.*)$/';
            $entries = [];
            $currentEntry = null;

            foreach ($lines as $rawLine) {
                $line = rtrim($rawLine, "\r");
                if (preg_match($pattern, $line, $matches)) {
                    if ($currentEntry !== null) {
                        $entries[] = $currentEntry;
                    }
                    $currentEntry = [
                        'timestamp' => $matches[1],
                        'environment' => $matches[2],
                        'level' => strtoupper($matches[3]),
                        'message' => $matches[4],
                        'context' => '',
                    ];
                } elseif ($currentEntry !== null) {
                    if ($currentEntry['context'] === '') {
                        $currentEntry['context'] = $line;
                    } else {
                        $currentEntry['context'] .= "\n".$line;
                    }
                }
            }

            if ($currentEntry !== null) {
                $entries[] = $currentEntry;
            }

            // Extract embedded JSON from message into context if context is empty
            foreach ($entries as &$entry) {
                $msg = $entry['message'];
                if (preg_match('/^(.*?)\s*(\{(?:[^{}]|(?R))*\}|\[(?:[^\[\]]|(?R))*\])\s*$/s', $msg, $jsonMatch)) {
                    $parsedJson = json_decode($jsonMatch[2], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $entry['message'] = trim($jsonMatch[1]);
                        if (empty($entry['context'])) {
                            $entry['context'] = $parsedJson;
                        } else {
                            $entry['context'] = [
                                'payload' => $parsedJson,
                                'trace' => $entry['context'],
                            ];
                        }
                    }
                }
            }
            unset($entry);

            // Reverse to display newest first
            $entries = array_reverse($entries);

            // Filter by level
            if ($level !== null && trim($level) !== '' && strtolower($level) !== 'all') {
                $targetLevel = strtoupper(trim($level));
                $entries = array_filter($entries, fn ($e) => $e['level'] === $targetLevel);
            }

            // Filter by search term
            if ($search !== null && trim($search) !== '') {
                $term = trim($search);
                $entries = array_filter($entries, function ($e) use ($term) {
                    $contextStr = is_array($e['context']) ? json_encode($e['context']) : (string) $e['context'];

                    return stripos($e['message'], $term) !== false
                        || stripos($e['timestamp'], $term) !== false
                        || stripos($e['level'], $term) !== false
                        || stripos($e['environment'], $term) !== false
                        || stripos($contextStr, $term) !== false;
                });
            }

            return array_values(array_slice($entries, 0, max(1, $limit)));
        } catch (Throwable $e) {
            Log::error('SystemLogService::getLogs error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Retrieve comprehensive system health status across database, cache, queues, and storage.
     *
     * @return array{
     *     status: string,
     *     database: array{
     *         status: string,
     *         latency_ms: float,
     *         database_name: string|null,
     *         driver: string
     *     },
     *     cache: array{
     *         status: string,
     *         driver: string
     *     },
     *     queue: array{
     *         failed_jobs: int,
     *         pending_jobs: int
     *     },
     *     storage: array{
     *         free_bytes: int,
     *         total_bytes: int,
     *         free_percentage: float
     *     },
     *     server: array{
     *         php_version: string,
     *         laravel_version: string,
     *         server_time: string,
     *         memory_usage: int,
     *         memory_usage_formatted: string
     *     }
     * }
     */
    public function getSystemHealth(): array
    {
        // 1. Database Health Check
        $dbStatus = 'ok';
        $dbLatency = 0.0;
        $dbName = null;
        $dbDriver = (string) config('database.default', 'pgsql');

        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
            $dbName = (string) DB::connection()->getDatabaseName();
            $dbDriver = (string) DB::getDriverName();
        } catch (Throwable $e) {
            $dbStatus = 'error';
            Log::warning('SystemLogService health check: Database unreachable: '.$e->getMessage());
        }

        // 2. Cache Health Check
        $cacheStatus = 'ok';
        $cacheDriver = (string) config('cache.default', 'file');

        try {
            $testKey = '_healthcheck_ping_'.uniqid();
            Cache::put($testKey, '1', 5);
            $cachedVal = Cache::get($testKey);
            Cache::forget($testKey);

            if ($cachedVal !== '1') {
                $cacheStatus = 'error';
            }
        } catch (Throwable $e) {
            $cacheStatus = 'error';
            Log::warning('SystemLogService health check: Cache error: '.$e->getMessage());
        }

        // 3. Queue Health Check
        $failedJobs = 0;
        $pendingJobs = 0;

        try {
            if (Schema::hasTable('failed_jobs')) {
                $failedJobs = (int) DB::table('failed_jobs')->count();
            }
            if (Schema::hasTable('jobs')) {
                $pendingJobs = (int) DB::table('jobs')->count();
            }
        } catch (Throwable $e) {
            Log::warning('SystemLogService health check: Queue query error: '.$e->getMessage());
        }

        // 4. Storage Health Check
        $storagePath = storage_path();
        $freeBytes = (int) (@disk_free_space($storagePath) ?: 0);
        $totalBytes = (int) (@disk_total_space($storagePath) ?: 0);
        $freePercentage = $totalBytes > 0 ? round(($freeBytes / $totalBytes) * 100, 2) : 0.0;

        // 5. Server Information
        $serverTime = now()->toIso8601String();
        $memoryUsage = memory_get_usage(true);
        $memoryFormatted = round($memoryUsage / (1024 * 1024), 2).' MB';

        $overallStatus = ($dbStatus === 'ok' && $cacheStatus === 'ok') ? 'healthy' : 'degraded';

        return [
            'status' => $overallStatus,
            'database' => [
                'status' => $dbStatus,
                'latency_ms' => $dbLatency,
                'database_name' => $dbName,
                'driver' => $dbDriver,
            ],
            'cache' => [
                'status' => $cacheStatus,
                'driver' => $cacheDriver,
            ],
            'queue' => [
                'failed_jobs' => $failedJobs,
                'pending_jobs' => $pendingJobs,
            ],
            'storage' => [
                'free_bytes' => $freeBytes,
                'total_bytes' => $totalBytes,
                'free_percentage' => $freePercentage,
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => $serverTime,
                'memory_usage' => $memoryUsage,
                'memory_usage_formatted' => $memoryFormatted,
            ],
        ];
    }
}
