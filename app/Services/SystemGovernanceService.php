<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemGovernanceService
{
    /**
     * Cache TTL in seconds (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * In-memory cache key prefix.
     */
    protected const CACHE_PREFIX = 'sys_setting_';

    /**
     * Default catalog of feature flags.
     *
     * @var array<string, bool>
     */
    protected const DEFAULT_FEATURES = [
        'ai_chatbot' => true,
        'interactive_gis' => true,
        'spatial_analysis' => true,
        'public_visualization' => true,
        'external_api' => true,
        'spatial_kugi_export' => true,
        'public_registration' => true,
        'two_factor_auth' => true,
        'maintenance_banner' => false,
        'dark_mode_forced' => false,
        'api_rate_limiting' => true,
        'auto_backup_daily' => true,
    ];

    /**
     * Retrieve a system setting by key with caching and decryption support.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$key;

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            try {
                if (! Schema::hasTable('system_settings')) {
                    return $default;
                }

                $record = DB::table('system_settings')->where('key', $key)->first();

                // If not found, try alternative key notations (e.g. dot vs underscore)
                if (! $record && str_contains($key, '_')) {
                    $altKey = str_replace('_', '.', $key);
                    $record = DB::table('system_settings')->where('key', $altKey)->first();
                } elseif (! $record && str_contains($key, '.')) {
                    $altKey = str_replace('.', '_', $key);
                    $record = DB::table('system_settings')->where('key', $altKey)->first();
                }

                if (! $record) {
                    return $default;
                }

                $isEncrypted = (bool) ($record->is_encrypted ?? false) || ($record->type ?? '') === 'encrypted';

                return $this->castValueFromDb($record->value, $record->type ?? 'string', $isEncrypted);
            } catch (Throwable $e) {
                Log::warning("SystemGovernanceService::get error for key [{$key}]: ".$e->getMessage());

                return $default;
            }
        });
    }

    /**
     * Store or update a system setting, invalidate cache, and log audit trail.
     */
    public function set(string $key, mixed $value, ?string $type = null, ?int $userId = null): void
    {
        $isEncrypted = $type === 'encrypted';
        $resolvedType = $type ?? $this->detectValueType($value);
        if ($resolvedType === 'encrypted') {
            $resolvedType = 'string';
            $isEncrypted = true;
        }

        $storedValue = $this->prepareValueForDb($value, $resolvedType, $isEncrypted);
        $group = $this->resolveGroupFromKey($key);

        try {
            if (Schema::hasTable('system_settings')) {
                $existing = DB::table('system_settings')->where('key', $key)->first();

                $payload = [
                    'key' => $key,
                    'value' => $storedValue,
                    'type' => $resolvedType,
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('system_settings', 'is_encrypted')) {
                    $payload['is_encrypted'] = $isEncrypted;
                }

                if (Schema::hasColumn('system_settings', 'group')) {
                    $payload['group'] = $group;
                }

                if ($userId !== null) {
                    if (Schema::hasColumn('system_settings', 'updated_by')) {
                        $payload['updated_by'] = $userId;
                    } elseif (Schema::hasColumn('system_settings', 'user_id')) {
                        $payload['user_id'] = $userId;
                    }
                }

                if (! $existing && Schema::hasColumn('system_settings', 'created_at')) {
                    $payload['created_at'] = now();
                }

                DB::table('system_settings')->updateOrInsert(['key' => $key], $payload);

                if (class_exists(AuditLog::class)) {
                    $oldValDisplay = $existing
                        ? (($existing->is_encrypted ?? false) || ($existing->type ?? '') === 'encrypted' ? '***' : $existing->value)
                        : null;
                    $newValDisplay = $isEncrypted ? '***' : $storedValue;

                    AuditLog::catat(
                        $existing ? 'SETTING_UPDATE' : 'SETTING_CREATE',
                        'system_settings',
                        isset($existing->id) && is_numeric($existing->id) ? (int) $existing->id : null,
                        $existing ? ['key' => $key, 'value' => $oldValDisplay] : null,
                        ['key' => $key, 'value' => $newValDisplay, 'type' => $resolvedType]
                    );
                }
            }
        } catch (Throwable $e) {
            Log::error("SystemGovernanceService::set failed for key [{$key}]: ".$e->getMessage());
        } finally {
            Cache::forget(self::CACHE_PREFIX.$key);
            if (str_contains($key, '.')) {
                Cache::forget(self::CACHE_PREFIX.str_replace('.', '_', $key));
            } elseif (str_contains($key, '_')) {
                Cache::forget(self::CACHE_PREFIX.str_replace('_', '.', $key));
            }
        }
    }

    /**
     * Check if a specific feature flag is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $cleanFeature = preg_replace('/^feature[._]/', '', $feature);

        // Check feature.<name> then feature_<name> then <name>
        $val = $this->get("feature.{$cleanFeature}", null);
        if ($val === null) {
            $val = $this->get("feature_{$cleanFeature}", null);
        }
        if ($val === null) {
            $val = $this->get($feature, null);
        }

        if ($val === null) {
            return self::DEFAULT_FEATURES[$cleanFeature] ?? false;
        }

        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Toggle or update a feature flag.
     */
    public function setFeature(string $feature, bool $enabled, ?int $userId = null): void
    {
        $cleanFeature = preg_replace('/^feature[._]/', '', $feature);

        // Set using dot notation matching seeded database pattern
        $this->set("feature.{$cleanFeature}", $enabled, 'boolean', $userId);
        Cache::forget(self::CACHE_PREFIX."feature_{$cleanFeature}");
    }

    /**
     * Get all registered feature flags and their current status.
     *
     * @return array<string, bool>
     */
    public function getAllFeatures(): array
    {
        $features = self::DEFAULT_FEATURES;

        try {
            if (Schema::hasTable('system_settings')) {
                $dbFeatures = DB::table('system_settings')
                    ->where('key', 'like', 'feature.%')
                    ->orWhere('key', 'like', 'feature_%')
                    ->orWhere('group', 'feature')
                    ->get();

                foreach ($dbFeatures as $row) {
                    $key = (string) $row->key;
                    $key = preg_replace('/^feature[._]/', '', $key);
                    $features[$key] = filter_var($row->value, FILTER_VALIDATE_BOOLEAN);
                }
            }
        } catch (Throwable $e) {
            Log::warning('SystemGovernanceService::getAllFeatures error: '.$e->getMessage());
        }

        return $features;
    }

    /**
     * Active Session Governance: list all active user sessions with metadata.
     *
     * @return array<int, array{
     *     id: string,
     *     user_id: int|null,
     *     user_name: string,
     *     user_email: string|null,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     last_activity: string|null,
     *     last_activity_timestamp: int|null,
     *     is_current: bool
     * }>
     */
    public function getActiveSessions(): array
    {
        try {
            if (! Schema::hasTable('sessions')) {
                return [];
            }

            $currentSessionId = session()->isStarted() ? session()->getId() : null;

            $sessions = DB::table('sessions')
                ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
                ->select([
                    'sessions.id as session_id',
                    'sessions.user_id',
                    'users.nama as user_nama',
                    'users.email as user_email',
                    'sessions.ip_address',
                    'sessions.user_agent',
                    'sessions.last_activity',
                ])
                ->orderByDesc('sessions.last_activity')
                ->get();

            return $sessions->map(function ($s) use ($currentSessionId) {
                $lastActivityTs = is_numeric($s->last_activity) ? (int) $s->last_activity : null;
                $formattedTime = $lastActivityTs !== null
                    ? Carbon::createFromTimestamp($lastActivityTs)->toIso8601String()
                    : null;

                $userName = $s->user_nama ?? ($s->user_id ? "User #{$s->user_id}" : 'Tamu (Guest)');

                return [
                    'id' => (string) $s->session_id,
                    'user_id' => $s->user_id !== null ? (int) $s->user_id : null,
                    'user_name' => (string) $userName,
                    'user_email' => $s->user_email !== null ? (string) $s->user_email : null,
                    'ip_address' => $s->ip_address !== null ? (string) $s->ip_address : null,
                    'user_agent' => $s->user_agent !== null ? (string) $s->user_agent : null,
                    'last_activity' => $formattedTime,
                    'last_activity_timestamp' => $lastActivityTs,
                    'is_current' => $currentSessionId !== null && (string) $s->session_id === (string) $currentSessionId,
                ];
            })->all();
        } catch (Throwable $e) {
            Log::error('SystemGovernanceService::getActiveSessions error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Revoke a single active session by session ID.
     */
    public function revokeSession(string $sessionId): bool
    {
        try {
            if (! Schema::hasTable('sessions')) {
                return false;
            }

            $deleted = DB::table('sessions')->where('id', $sessionId)->delete();

            if ($deleted > 0 && class_exists(AuditLog::class)) {
                AuditLog::catat(
                    'SESSION_REVOKED',
                    'sessions',
                    null,
                    ['session_id' => $sessionId],
                    null
                );
            }

            return $deleted > 0;
        } catch (Throwable $e) {
            Log::error("SystemGovernanceService::revokeSession failed for [{$sessionId}]: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Terminate all active sessions for a specific user.
     */
    public function forceLogoutUser(int $userId): int
    {
        try {
            if (! Schema::hasTable('sessions')) {
                return 0;
            }

            $count = DB::table('sessions')->where('user_id', $userId)->delete();

            if ($count > 0 && class_exists(AuditLog::class)) {
                AuditLog::catat(
                    'USER_FORCE_LOGOUT',
                    'sessions',
                    $userId,
                    ['user_id' => $userId, 'terminated_sessions_count' => $count],
                    null
                );
            }

            return $count;
        } catch (Throwable $e) {
            Log::error("SystemGovernanceService::forceLogoutUser failed for [{$userId}]: ".$e->getMessage());

            return 0;
        }
    }

    /**
     * IP Rule Validator: check if an IP address is permitted by active firewall/access rules.
     */
    public function isIpAllowed(string $ip): bool
    {
        $ip = trim($ip);
        if ($ip === '') {
            return false;
        }

        try {
            if (! Schema::hasTable('ip_access_rules')) {
                return true;
            }

            $rules = DB::table('ip_access_rules')->where('is_active', true)->get();
            if ($rules->isEmpty()) {
                return true;
            }

            // 1. Blacklist check: if any active blacklist rule matches, immediately block
            $blacklistRules = $rules->filter(function ($rule) {
                $type = strtolower((string) ($rule->rule_type ?? $rule->type ?? ''));

                return in_array($type, ['blacklist', 'block', 'deny'], true);
            });

            foreach ($blacklistRules as $rule) {
                $rulePattern = (string) ($rule->ip_address ?? $rule->ip_range ?? '');
                if ($this->ipMatchesRule($ip, $rulePattern)) {
                    return false;
                }
            }

            // 2. Whitelist check: if any whitelist rules exist, IP must match at least one
            $whitelistRules = $rules->filter(function ($rule) {
                $type = strtolower((string) ($rule->rule_type ?? $rule->type ?? ''));

                return in_array($type, ['whitelist', 'allow'], true);
            });

            if ($whitelistRules->isNotEmpty()) {
                foreach ($whitelistRules as $rule) {
                    $rulePattern = (string) ($rule->ip_address ?? $rule->ip_range ?? '');
                    if ($this->ipMatchesRule($ip, $rulePattern)) {
                        return true;
                    }
                }

                return false;
            }

            // 3. No whitelist active and not blacklisted -> allowed
            return true;
        } catch (Throwable $e) {
            Log::error("SystemGovernanceService::isIpAllowed error for [{$ip}]: ".$e->getMessage());

            return true;
        }
    }

    /**
     * Maintenance Mode: check if maintenance mode is active.
     */
    public function isMaintenanceActive(): bool
    {
        try {
            $isDown = app()->isDownForMaintenance();
            $enabledFromDb = (bool) $this->get('maintenance.enabled', false)
                || (bool) $this->get('maintenance_mode', false);

            return $isDown || $enabledFromDb;
        } catch (Throwable $e) {
            Log::warning('SystemGovernanceService::isMaintenanceActive error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Maintenance Mode: activate or deactivate maintenance mode.
     */
    public function setMaintenance(bool $active, ?string $message = null, ?string $scheduledEnd = null): void
    {
        $this->set('maintenance.enabled', $active, 'boolean');
        $this->set('maintenance_mode', $active, 'boolean');

        if ($message !== null) {
            $this->set('maintenance.message', $message, 'string');
            $this->set('maintenance_message', $message, 'string');
        }

        if ($scheduledEnd !== null) {
            $this->set('maintenance.scheduled_end', $scheduledEnd, 'string');
            $this->set('maintenance_scheduled_end', $scheduledEnd, 'string');
        }

        if (class_exists(AuditLog::class)) {
            AuditLog::catat(
                $active ? 'MAINTENANCE_ENABLED' : 'MAINTENANCE_DISABLED',
                'system_settings',
                null,
                null,
                [
                    'active' => $active,
                    'message' => $message,
                    'scheduled_end' => $scheduledEnd,
                ]
            );
        }
    }

    /**
     * Check if an IP address matches a given pattern (exact, CIDR, wildcard, comma-delimited).
     */
    protected function ipMatchesRule(string $ip, string $rule): bool
    {
        $ip = trim($ip);
        $rule = trim($rule);

        if ($rule === '' || $ip === '') {
            return false;
        }

        // Support multiple comma-separated entries in a single rule
        if (str_contains($rule, ',')) {
            foreach (explode(',', $rule) as $subRule) {
                if ($this->ipMatchesRule($ip, trim($subRule))) {
                    return true;
                }
            }

            return false;
        }

        // Exact match
        if ($ip === $rule) {
            return true;
        }

        // CIDR match (IPv4 and IPv6)
        if (str_contains($rule, '/')) {
            [$subnet, $mask] = explode('/', $rule, 2);
            $subnet = trim($subnet);
            $maskInt = (int) trim($mask);

            // IPv4 CIDR calculation
            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) &&
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ) {
                if ($maskInt < 0 || $maskInt > 32) {
                    return false;
                }
                if ($maskInt === 0) {
                    return true;
                }
                $ipLong = ip2long($ip);
                $subnetLong = ip2long($subnet);
                $netmask = ~((1 << (32 - $maskInt)) - 1);

                return ($ipLong & $netmask) === ($subnetLong & $netmask);
            }

            // IPv6 CIDR calculation
            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) &&
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ) {
                if ($maskInt < 0 || $maskInt > 128) {
                    return false;
                }
                $ipBin = inet_pton($ip);
                $subnetBin = inet_pton($subnet);
                if ($ipBin === false || $subnetBin === false) {
                    return false;
                }
                $bytes = intdiv($maskInt, 8);
                $bits = $maskInt % 8;

                if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
                    return false;
                }

                if ($bits > 0) {
                    $byteMask = (0xFF << (8 - $bits)) & 0xFF;
                    $ipByte = ord($ipBin[$bytes]) & $byteMask;
                    $subnetByte = ord($subnetBin[$bytes]) & $byteMask;

                    return $ipByte === $subnetByte;
                }

                return true;
            }
        }

        // Wildcard match (e.g., 192.168.1.*)
        if (str_contains($rule, '*')) {
            $pattern = '/^'.str_replace(['.', '*'], ['\.', '.*'], $rule).'$/';

            return (bool) preg_match($pattern, $ip);
        }

        return false;
    }

    /**
     * Cast raw database value according to the specified setting type.
     */
    protected function castValueFromDb(?string $rawValue, string $type, bool $isEncrypted = false): mixed
    {
        if ($rawValue === null) {
            return null;
        }

        $raw = $rawValue;
        if ($isEncrypted) {
            $raw = $this->decryptValue($rawValue);
        }

        return match ($type) {
            'encrypted' => $raw,
            'boolean', 'bool' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $raw,
            'float', 'double' => (float) $raw,
            'json', 'array' => $this->decodeJson($raw),
            default => $raw,
        };
    }

    /**
     * Prepare a PHP value for database storage based on its resolved type.
     */
    protected function prepareValueForDb(mixed $value, string $type, bool $isEncrypted = false): ?string
    {
        if ($value === null) {
            return null;
        }

        $serialized = match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'json', 'array' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        if ($isEncrypted) {
            return Crypt::encryptString($serialized);
        }

        return $serialized;
    }

    /**
     * Safely decrypt string value, returning raw string on decryption failure.
     */
    protected function decryptValue(string $encrypted): string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (Throwable $e) {
            Log::warning('SystemGovernanceService::decryptValue failed: '.$e->getMessage());

            return $encrypted;
        }
    }

    /**
     * Safely decode JSON value.
     */
    protected function decodeJson(string $json): mixed
    {
        $decoded = json_decode($json, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $json;
    }

    /**
     * Auto-detect value type from native PHP variable.
     */
    protected function detectValueType(mixed $value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Resolve group name from dot or underscore key.
     */
    protected function resolveGroupFromKey(string $key): string
    {
        if (str_contains($key, '.')) {
            return explode('.', $key, 2)[0];
        }
        if (str_contains($key, '_')) {
            return explode('_', $key, 2)[0];
        }

        return 'general';
    }
}
