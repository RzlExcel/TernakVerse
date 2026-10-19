<?php

namespace App\Models;

use App\Casts\GeoJsonCast;
use App\Models\Traits\HasSpatialColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Desa extends Model
{
    use HasFactory, HasSpatialColumns;

    protected $table = 'desa';

    protected $fillable = [
        'kecamatan_id',
        'kode_desa',
        'nama_desa',
        'jenis',
        'titik_balai_desa',
        'fcode',
        'remark',
    ];

    protected $casts = [
        'titik_balai_desa' => GeoJsonCast::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Kecamatan induk.
     */
    public function kecamatan(): BelongsTo
    {
        return $this->belongsTo(Kecamatan::class, 'kecamatan_id');
    }

    /**
     * Relasi ke rekaman data populasi ternak berkala di desa ini.
     */
    public function populasiTernak(): HasMany
    {
        return $this->hasMany(PopulasiTernak::class, 'desa_id');
    }

    /**
     * Relasi ke rekaman data hasil produksi ternak di desa ini.
     */
    public function produksiTernak(): HasMany
    {
        return $this->hasMany(ProduksiTernak::class, 'desa_id');
    }

    /**
     * Relasi ke rekaman kejadian kasus penyakit hewan di desa ini.
     */
    public function kasusPenyakit(): HasMany
    {
        return $this->hasMany(KasusPenyakit::class, 'desa_id');
    }

    /**
     * Relasi ke riwayat snapshot zonasi risiko tingkat desa.
     */
    public function zonaSnapshots(): HasMany
    {
        return $this->hasMany(ZonaSnapshotDesa::class, 'desa_id');
    }
}
