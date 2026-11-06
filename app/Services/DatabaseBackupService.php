<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\SystemBackup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    /**
     * Storage path for database backup files.
     */
    protected string $backupDir;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backups');
        $this->ensureDirectoryExists();
    }

    /**
     * Ensure the backup directory exists with 0755 permissions.
     */
    protected function ensureDirectoryExists(): void
    {
        if (! is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * Create a new database backup snapshot (SQLite or PostgreSQL).
     *
     * @return object|array<string, mixed>
     */
    public function createBackup(?int $userId = null, string $notes = ''): mixed
    {
        $this->ensureDirectoryExists();

        $driver = DB::getDriverName();
        $timestamp = now()->format('Y-m-d_His');
        $randomSuffix = bin2hex(random_bytes(4));

        $extension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $filename = "backup_{$driver}_{$timestamp}_{$randomSuffix}.{$extension}";
        $filepath = $this->backupDir.DIRECTORY_SEPARATOR.$filename;

        try {
            if ($driver === 'sqlite') {
                $this->backupSqlite($filepath);
            } else {
                $this->backupPostgresOrGeneric($filepath, $driver);
            }

            if (! file_exists($filepath) || filesize($filepath) === 0) {
                throw new \RuntimeException("Backup file could not be created or is empty: {$filepath}");
            }

            $sizeBytes = (int) filesize($filepath);
            $checksum = (string) hash_file('sha256', $filepath);

            $backupData = [
                'filename' => $filename,
                'disk_path' => $filepath,
                'size_bytes' => $sizeBytes,
                'backup_type' => $driver === 'sqlite' ? 'sqlite' : 'full',
                'checksum' => $checksum,
                'status' => 'completed',
                'notes' => $notes,
                'created_by' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $backupId = null;

            if (Schema::hasTable('system_backups')) {
                // Adapt to any column variation if needed
                if (! Schema::hasColumn('system_backups', 'disk_path') && Schema::hasColumn('system_backups', 'file_path')) {
                    $backupData['file_path'] = $filepath;
                    unset($backupData['disk_path']);
                }
                if (! Schema::hasColumn('system_backups', 'checksum') && Schema::hasColumn('system_backups', 'checksum_sha256')) {
                    $backupData['checksum_sha256'] = $checksum;
                    unset($backupData['checksum']);
                }
                if (! Schema::hasColumn('system_backups', 'created_by') && Schema::hasColumn('system_backups', 'user_id')) {
                    $backupData['user_id'] = $userId;
                    unset($backupData['created_by']);
                }

                $backupId = (int) DB::table('system_backups')->insertGetId($backupData);
                $backupData['id'] = $backupId;
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::catat(
                    'BACKUP_CREATE',
                    'system_backups',
                    $backupId,
                    null,
                    [
                        'filename' => $filename,
                        'size_bytes' => $sizeBytes,
                        'checksum' => $checksum,
                        'driver' => $driver,
                        'notes' => $notes,
                    ]
                );
            }

            if ($backupId !== null && class_exists(SystemBackup::class)) {
                $modelInstance = SystemBackup::find($backupId);
                if ($modelInstance !== null) {
                    return $modelInstance;
                }
            }

            return (object) $backupData;
        } catch (Throwable $e) {
            Log::error('DatabaseBackupService::createBackup failed: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            if (file_exists($filepath)) {
                @unlink($filepath);
            }

            throw $e;
        }
    }

    /**
     * List all database backups.
     */
    public function listBackups(): Collection
    {
        try {
            if (Schema::hasTable('system_backups')) {
                if (class_exists(SystemBackup::class)) {
                    return SystemBackup::orderByDesc('created_at')->get();
                }

                return DB::table('system_backups')->orderByDesc('created_at')->get();
            }

            return $this->scanBackupsFromDisk();
        } catch (Throwable $e) {
            Log::error('DatabaseBackupService::listBackups error: '.$e->getMessage());

            return collect([]);
        }
    }

    /**
     * Restore a database backup after verifying sha256 checksum and creating a safety snapshot.
     */
    public function restoreBackup(int $backupId, ?int $userId = null): bool
    {
        try {
            if (! Schema::hasTable('system_backups')) {
                Log::warning('DatabaseBackupService::restoreBackup: system_backups table does not exist.');

                return false;
            }

            $backup = DB::table('system_backups')->where('id', $backupId)->first();
            if (! $backup) {
                Log::warning("DatabaseBackupService::restoreBackup: backup record #{$backupId} not found.");

                return false;
            }

            $filepath = $this->resolveBackupPath($backup);
            if (! $filepath || ! file_exists($filepath)) {
                Log::error("DatabaseBackupService::restoreBackup: physical file missing for backup #{$backupId}");

                return false;
            }

            // Verify sha256 checksum
            $actualChecksum = hash_file('sha256', $filepath);
            $expectedChecksum = $backup->checksum ?? $backup->checksum_sha256 ?? null;

            if ($expectedChecksum !== null && ! hash_equals((string) $expectedChecksum, (string) $actualChecksum)) {
                Log::error("DatabaseBackupService::restoreBackup: checksum mismatch for #{$backupId}. Expected {$expectedChecksum}, got {$actualChecksum}");

                return false;
            }

            // Create pre-restore safety snapshot
            try {
                $this->createBackup($userId, "Automated safety snapshot prior to restoring backup #{$backupId}");
            } catch (Throwable $snapshotEx) {
                Log::warning('DatabaseBackupService: pre-restore safety snapshot could not be completed: '.$snapshotEx->getMessage());
            }

            // Execute restore
            $driver = $backup->driver ?? DB::getDriverName();

            if ($driver === 'sqlite') {
                $this->restoreSqlite($filepath);
            } else {
                $this->restorePostgresOrGeneric($filepath);
            }

            if (Schema::hasColumn('system_backups', 'status')) {
                DB::table('system_backups')->where('id', $backupId)->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
            }

            if (class_exists(AuditLog::class)) {
                AuditLog::catat(
                    'BACKUP_RESTORE',
                    'system_backups',
                    $backupId,
                    null,
                    ['filename' => $backup->filename]
                );
            }

            return true;
        } catch (Throwable $e) {
            Log::error("DatabaseBackupService::restoreBackup failed for #{$backupId}: ".$e->getMessage(), [
                'exception' => $e,
            ]);

            if (Schema::hasTable('system_backups') && Schema::hasColumn('system_backups', 'status')) {
                DB::table('system_backups')->where('id', $backupId)->update([
                    'status' => 'restore_failed',
                    'updated_at' => now(),
                ]);
            }

            return false;
        }
    }

    /**
     * Delete a backup record and its physical file from storage.
     */
    public function deleteBackup(int $backupId): bool
    {
        try {
            if (! Schema::hasTable('system_backups')) {
                return false;
            }

            $backup = DB::table('system_backups')->where('id', $backupId)->first();
            if (! $backup) {
                return false;
            }

            $filepath = $this->resolveBackupPath($backup);
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            DB::table('system_backups')->where('id', $backupId)->delete();

            if (class_exists(AuditLog::class)) {
                AuditLog::catat(
                    'BACKUP_DELETE',
                    'system_backups',
                    $backupId,
                    ['filename' => $backup->filename],
                    null
                );
            }

            return true;
        } catch (Throwable $e) {
            Log::error("DatabaseBackupService::deleteBackup failed for #{$backupId}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Return the absolute path of a backup file for secure download.
     */
    public function getBackupFile(int $backupId): ?string
    {
        try {
            if (! Schema::hasTable('system_backups')) {
                return null;
            }

            $backup = DB::table('system_backups')->where('id', $backupId)->first();
            if (! $backup) {
                return null;
            }

            $filepath = $this->resolveBackupPath($backup);
            if ($filepath && file_exists($filepath)) {
                $realFile = realpath($filepath);
                $realBackupDir = realpath($this->backupDir);

                if ($realFile && $realBackupDir && str_starts_with($realFile, $realBackupDir.DIRECTORY_SEPARATOR)) {
                    return $realFile;
                }
            }

            return null;
        } catch (Throwable $e) {
            Log::error("DatabaseBackupService::getBackupFile error for #{$backupId}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Backup SQLite database by file copying or SQL generation.
     */
    protected function backupSqlite(string $filepath): void
    {
        $dbPath = config('database.connections.sqlite.database');

        if (is_string($dbPath) && file_exists($dbPath) && $dbPath !== ':memory:') {
            if (! copy($dbPath, $filepath)) {
                throw new \RuntimeException("Failed to copy SQLite database from {$dbPath} to {$filepath}");
            }
        } else {
            $this->generatePhpSqlDump($filepath, 'sqlite');
        }
    }

    /**
     * Backup PostgreSQL using pg_dump if available, with pure PHP SQL dump fallback.
     */
    protected function backupPostgresOrGeneric(string $filepath, string $driver): void
    {
        if ($driver === 'pgsql') {
            $pgDumpSuccess = $this->runPgDump($filepath);
            if ($pgDumpSuccess && file_exists($filepath) && filesize($filepath) > 0) {
                return;
            }
        }

        // Fallback: Pure PHP SQL Statement Generator
        $this->generatePhpSqlDump($filepath, $driver);
    }

    /**
     * Attempt pg_dump execution via process.
     */
    protected function runPgDump(string $filepath): bool
    {
        try {
            $host = (string) config('database.connections.pgsql.host', '127.0.0.1');
            $port = (string) config('database.connections.pgsql.port', '5432');
            $database = (string) config('database.connections.pgsql.database');
            $username = (string) config('database.connections.pgsql.username');
            $password = (string) config('database.connections.pgsql.password');

            $cmd = [
                'pg_dump',
                '-h', $host,
                '-p', $port,
                '-U', $username,
                '-d', $database,
                '-F', 'p',
                '--no-owner',
                '--no-acl',
                '-f', $filepath,
            ];

            $process = new Process($cmd);
            $process->setEnv(['PGPASSWORD' => $password]);
            $process->setTimeout(300);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable $e) {
            Log::warning('pg_dump process execution unavailable or failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Pure PHP SQL Statement Generator for schemas and records.
     */
    protected function generatePhpSqlDump(string $filepath, string $driver): void
    {
        $handle = fopen($filepath, 'wb');
        if (! $handle) {
            throw new \RuntimeException("Unable to open file for writing: {$filepath}");
        }

        $timestamp = now()->toIso8601String();
        fwrite($handle, "-- TernakVerse Database Backup\n");
        fwrite($handle, "-- Driver: {$driver}\n");
        fwrite($handle, "-- Generated: {$timestamp}\n\n");

        if ($driver === 'pgsql') {
            fwrite($handle, "SET client_encoding = 'UTF8';\nSET standard_conforming_strings = on;\n\n");
        }

        $tables = $this->getAllTableNames($driver);

        foreach ($tables as $table) {
            fwrite($handle, "\n-- -----------------------------------------------------\n");
            fwrite($handle, "-- Table: {$table}\n");
            fwrite($handle, "-- -----------------------------------------------------\n");

            try {
                $count = DB::table($table)->count();
                if ($count === 0) {
                    continue;
                }

                if ($driver === 'pgsql') {
                    fwrite($handle, sprintf("TRUNCATE TABLE \"%s\" CASCADE;\n", $table));
                } elseif ($driver === 'sqlite') {
                    fwrite($handle, sprintf("DELETE FROM `%s`;\n", $table));
                } else {
                    fwrite($handle, sprintf("TRUNCATE TABLE `%s`;\n", $table));
                }

                DB::table($table)->orderBy(DB::raw('1'))->chunk(200, function ($rows) use ($handle, $table, $driver) {
                    foreach ($rows as $row) {
                        $rowArray = (array) $row;
                        $columns = array_keys($rowArray);
                        $escapedColumns = array_map(fn ($col) => $driver === 'pgsql' ? "\"{$col}\"" : "`{$col}`", $columns);

                        $values = [];
                        foreach ($rowArray as $val) {
                            if ($val === null) {
                                $values[] = 'NULL';
                            } elseif (is_bool($val)) {
                                $values[] = $val ? 'TRUE' : 'FALSE';
                            } elseif (is_numeric($val) && ! is_string($val)) {
                                $values[] = (string) $val;
                            } else {
                                $values[] = "'".addcslashes((string) $val, "'\\")."'";
                            }
                        }

                        $sql = sprintf(
                            "INSERT INTO %s (%s) VALUES (%s);\n",
                            $driver === 'pgsql' ? "\"{$table}\"" : "`{$table}`",
                            implode(', ', $escapedColumns),
                            implode(', ', $values)
                        );

                        fwrite($handle, $sql);
                    }
                });
            } catch (Throwable $e) {
                Log::warning("Could not dump table {$table}: ".$e->getMessage());
            }
        }

        fclose($handle);
    }

    /**
     * Restore SQLite database.
     */
    protected function restoreSqlite(string $filepath): void
    {
        $dbPath = config('database.connections.sqlite.database');
        if (is_string($dbPath) && $dbPath !== ':memory:') {
            DB::disconnect();
            if (! copy($filepath, $dbPath)) {
                throw new \RuntimeException("Failed to overwrite SQLite file from {$filepath}");
            }
            DB::reconnect();
        } else {
            $sql = file_get_contents($filepath);
            if ($sql !== false && trim($sql) !== '') {
                DB::unprepared($sql);
            }
        }
    }

    /**
     * Restore PostgreSQL or generic SQL dump.
     */
    protected function restorePostgresOrGeneric(string $filepath): void
    {
        $sql = file_get_contents($filepath);
        if ($sql === false || trim($sql) === '') {
            throw new \RuntimeException("Backup file is unreadable or empty: {$filepath}");
        }

        DB::unprepared($sql);
    }

    /**
     * Retrieve all base table names in the current database.
     *
     * @return list<string>
     */
    protected function getAllTableNames(string $driver): array
    {
        try {
            if ($driver === 'pgsql') {
                $rows = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");

                return array_map(fn ($r) => (string) $r->table_name, $rows);
            }

            if ($driver === 'sqlite') {
                $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

                return array_map(fn ($r) => (string) $r->name, $rows);
            }

            return Schema::getTableListing();
        } catch (Throwable $e) {
            Log::warning('DatabaseBackupService::getAllTableNames error: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Resolve the absolute physical path of a backup record.
     */
    protected function resolveBackupPath(object $backup): ?string
    {
        $realBackupDir = realpath($this->backupDir);
        if (! $realBackupDir) {
            return null;
        }

        $pathCandidates = [
            $backup->disk_path ?? null,
            $backup->file_path ?? null,
        ];

        foreach ($pathCandidates as $candidate) {
            if (! empty($candidate) && file_exists((string) $candidate)) {
                $real = realpath((string) $candidate);
                if ($real && str_starts_with($real, $realBackupDir.DIRECTORY_SEPARATOR)) {
                    return $real;
                }
            }
        }

        $fallback = $this->backupDir.DIRECTORY_SEPARATOR.basename($backup->filename ?? '');
        if (file_exists($fallback)) {
            $realFallback = realpath($fallback);
            if ($realFallback && str_starts_with($realFallback, $realBackupDir.DIRECTORY_SEPARATOR)) {
                return $realFallback;
            }
        }

        return null;
    }

    /**
     * Scan storage/app/backups folder if system_backups table is not migrated yet.
     */
    protected function scanBackupsFromDisk(): Collection
    {
        $results = [];

        if (is_dir($this->backupDir)) {
            $files = scandir($this->backupDir);
            if ($files !== false) {
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') {
                        continue;
                    }

                    $fullPath = $this->backupDir.DIRECTORY_SEPARATOR.$file;
                    if (is_file($fullPath)) {
                        $results[] = (object) [
                            'id' => null,
                            'filename' => $file,
                            'disk_path' => $fullPath,
                            'file_path' => $fullPath,
                            'disk' => 'local',
                            'size_bytes' => filesize($fullPath),
                            'checksum' => hash_file('sha256', $fullPath),
                            'checksum_sha256' => hash_file('sha256', $fullPath),
                            'backup_type' => str_contains($file, 'sqlite') ? 'sqlite' : 'full',
                            'driver' => str_contains($file, 'sqlite') ? 'sqlite' : 'pgsql',
                            'status' => 'completed',
                            'notes' => 'Scanned from local storage',
                            'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                            'updated_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                        ];
                    }
                }
            }
        }

        return collect($results);
    }
}
