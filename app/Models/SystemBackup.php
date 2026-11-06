<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemBackup extends Model
{
    use HasFactory;

    protected $table = 'system_backups';

    protected $fillable = [
        'filename',
        'disk_path',
        'size_bytes',
        'backup_type',
        'checksum',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'created_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'human_size',
    ];

    /**
     * Relationship to user who triggered the backup.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper accessor for human readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = (float) $this->size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
