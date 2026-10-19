<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PopulasiTernak extends Model
{
    use HasFactory;

    protected $table = 'populasi_ternak';

    /**
     * Catatan arsitektur: Kolom 'jumlah_total' tidak dimasukkan ke dalam $fillable
     * karena merupakan STORED GENERATED COLUMN yang dihitung otomatis oleh PostgreSQL.
     */
    protected $fillable = [
        'desa_id',
        'komoditas_id',
        'tahun',
        'triwulan',
        'jumlah_jantan',
        'jumlah_betina',
    ];

    protected $casts = [
        'desa_id' => 'integer',
        'komoditas_id' => 'integer',
        'tahun' => 'integer',
        'triwulan' => 'integer',
        'jumlah_jantan' => 'integer',
        'jumlah_betina' => 'integer',
        'jumlah_total' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Desa / Kelurahan lokasi pencatatan populasi.
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    /**
     * Relasi ke Master Komoditas ternak.
     */
    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    /**
     * Scope query untuk memfilter periode tahun dan triwulan tertentu.
     */
    public function scopePeriode($query, int $tahun, ?int $triwulan = null)
    {
        $query->where('tahun', $tahun);
        if ($triwulan !== null) {
            $query->where('triwulan', $triwulan);
        }
        return $query;
    }
}
