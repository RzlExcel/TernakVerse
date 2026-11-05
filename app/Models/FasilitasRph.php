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
        'tipe_fasilitas',
        'alamat_lengkap',
        'jenis_pengelola',
        'jenis_hewan_potong',
        'kapasitas_potong_ekor_hari',
        'status_operasional',
        'hari_pasaran',
        'wilayah_binaan',
        'penanggung_jawab',
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
     * Scope fasilitas Rumah Potong Hewan (RPH).
     */
    public function scopeRph($query)
    {
        return $query->where('tipe_fasilitas', 'RPH');
    }

    /**
     * Scope fasilitas Pusat Kesehatan Hewan (Puskeswan).
     */
    public function scopePuskeswan($query)
    {
        return $query->where('tipe_fasilitas', 'Puskeswan');
    }

    /**
     * Scope fasilitas Pasar Hewan.
     */
    public function scopePasarHewan($query)
    {
        return $query->where('tipe_fasilitas', 'Pasar Hewan');
    }

    /**
     * Relasi ke Kecamatan lokasi fasilitas RPH berada.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }
}
