<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThematicInterval extends Model
{
    use HasFactory;

    protected $table = 'thematic_intervals';

    protected $fillable = [
        'kategori',
        'tier',
        'label',
        'min_value',
        'max_value',
        'color_hex',
        'satuan',
        'calculation_mode',
        'is_active',
        'effective_date',
        'updated_by',
    ];

    protected $casts = [
        'tier' => 'integer',
        'min_value' => 'float',
        'max_value' => 'float',
        'is_active' => 'boolean',
        'effective_date' => 'date',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationship to the user who updated the interval.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Filter by category.
     */
    public function scopeKategori(Builder $query, string $kategori): Builder
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Filter active intervals.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Determine the interval tier for a given value.
     */
    public static function getTierForValue(string $kategori, float $val): ?self
    {
        return static::where('kategori', $kategori)
            ->where('is_active', true)
            ->where('min_value', '<=', $val)
            ->where(function (Builder $query) use ($val): void {
                $query->whereNull('max_value')
                    ->orWhere('max_value', '>=', $val);
            })
            ->orderBy('tier')
            ->first();
    }
}
