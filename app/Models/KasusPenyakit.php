<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KasusPenyakit extends Model
{
    use HasFactory;

    protected $table = 'kasus_penyakit';

    /**
     * Catatan arsitektur: 'kasus_aktif' tidak dimasukkan ke dalam $fillable
     * karena merupakan STORED GENERATED COLUMN PostGIS/PostgreSQL.
     */
    protected $fillable = [
        'desa_id',
        'penyakit_id',
        'komoditas_id',
        'tanggal_laporan',
        'tahun',
        'triwulan',
        'kasus_suspek',
        'kasus_positif',
        'kasus_sembuh',
        'kasus_mati',
        'potong_bersyarat',
        'catatan_lapangan',
    ];

    protected $casts = [
        'desa_id' => 'integer',
        'penyakit_id' => 'integer',
        'komoditas_id' => 'integer',
        'tanggal_laporan' => 'date',
        'tahun' => 'integer',
        'triwulan' => 'integer',
        'kasus_suspek' => 'integer',
        'kasus_positif' => 'integer',
        'kasus_sembuh' => 'integer',
        'kasus_mati' => 'integer',
        'potong_bersyarat' => 'integer',
        'kasus_aktif' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Desa tempat kejadian wabah dilaporkan.
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    /**
     * Relasi ke Master Penyakit (PMK, LSD).
     */
    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class, 'penyakit_id');
    }

    /**
     * Relasi ke Jenis Komoditas ternak yang terdampak.
     */
    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    /**
     * Scope query untuk kasus yang masih aktif dalam rentang hari tertentu (misal: 14 hari terakhir).
     */
    public function scopeAktifDalamHari($query, int $hari = 14)
    {
        return $query->where('tanggal_laporan', '>=', now()->subDays($hari))
                     ->where('kasus_aktif', '>', 0);
    }
}
