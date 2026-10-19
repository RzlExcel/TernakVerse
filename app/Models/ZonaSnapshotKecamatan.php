<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZonaSnapshotKecamatan extends Model
{
    use HasFactory;

    protected $table = 'zona_snapshot_kecamatan';

    public $timestamps = false;

    protected $fillable = [
        'kecamatan_id',
        'penyakit_id',
        'tahun',
        'triwulan',
        'status_zona',
        'total_kasus_aktif',
        'waktu_kalkulasi',
    ];

    protected $casts = [
        'kecamatan_id' => 'integer',
        'penyakit_id' => 'integer',
        'tahun' => 'integer',
        'triwulan' => 'integer',
        'total_kasus_aktif' => 'integer',
        'waktu_kalkulasi' => 'datetime',
    ];

    /**
     * Relasi ke Kecamatan.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    /**
     * Relasi ke Penyakit yang dipetakan.
     */
    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class, 'penyakit_id');
    }

    /**
     * Scope untuk memfilter berdasarkan status risiko zonasi ('HIJAU', 'KUNING', 'MERAH').
     */
    public function scopeZona($query, string $status)
    {
        return $query->where('status_zona', strtoupper($status));
    }
}
