<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZonaSnapshotDesa extends Model
{
    use HasFactory;

    protected $table = 'zona_snapshot_desa';

    public $timestamps = false;

    protected $fillable = [
        'desa_id',
        'penyakit_id',
        'tahun',
        'triwulan',
        'status_zona',
        'total_kasus_aktif',
        'waktu_kalkulasi',
    ];

    protected $casts = [
        'desa_id' => 'integer',
        'penyakit_id' => 'integer',
        'tahun' => 'integer',
        'triwulan' => 'integer',
        'total_kasus_aktif' => 'integer',
        'waktu_kalkulasi' => 'datetime',
    ];

    /**
     * Relasi ke Desa.
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    /**
     * Relasi ke Penyakit yang dipetakan.
     */
    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class, 'penyakit_id');
    }
}
