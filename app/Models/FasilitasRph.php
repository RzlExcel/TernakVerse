<?php

namespace App\Models;

use App\Casts\GeoJsonCast;
use App\Models\Traits\HasSpatialColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FasilitasRph extends Model
{
    use HasFactory, HasSpatialColumns;

    protected $table = 'fasilitas_rph';

    protected $fillable = [
        'kecamatan_id',
        'nama_rph',
        'alamat_lengkap',
        'jenis_pengelola',
        'jenis_hewan_potong',
        'kapasitas_potong_ekor_hari',
        'status_operasional',
        'geom_titik',
        'nomor_kontak',
    ];

    protected $casts = [
        'geom_titik' => GeoJsonCast::class,
        'kapasitas_potong_ekor_hari' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Kecamatan lokasi fasilitas RPH berada.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
