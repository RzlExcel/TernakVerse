<?php

namespace App\Models;

use App\Casts\GeoJsonCast;
use App\Models\Traits\HasSpatialColumns;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Kecamatan extends Model
{
    use HasFactory, HasSpatialColumns;

    protected $table = 'kecamatan';

    protected $fillable = [
        'kode_kecamatan',
        'nama_kecamatan',
        'geom',
        'luas_wilayah_km2',
        'fcode',
        'metadata_id',
        'srs_id',
        'remark',
    ];

    protected $casts = [
        'geom' => GeoJsonCast::class,
        'luas_wilayah_km2' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke seluruh Desa / Kelurahan di bawah kecamatan ini.
     */
    public function desa(): HasMany
    {
        return $this->hasMany(Desa::class, 'kecamatan_id');
    }

    /**
     * Relasi ke Fasilitas Rumah Potong Hewan (RPH) di wilayah kecamatan ini.
     */
    public function fasilitasRph(): HasMany
    {
        return $this->hasMany(FasilitasRph::class, 'kecamatan_id');
    }

    /**
     * Relasi ke riwayat status snapshot zonasi risiko EWS kecamatan.
     */
    public function zonaSnapshots(): HasMany
    {
        return $this->hasMany(ZonaSnapshotKecamatan::class, 'kecamatan_id');
    }

    /**
     * Relasi tidak langsung ke data populasi ternak melalui desa.
     */
    public function populasiTernak(): HasManyThrough
    {
        return $this->hasManyThrough(PopulasiTernak::class, Desa::class, 'kecamatan_id', 'desa_id');
    }

    /**
     * Relasi tidak langsung ke data produksi ternak melalui desa.
     */
    public function produksiTernak(): HasManyThrough
    {
        return $this->hasManyThrough(ProduksiTernak::class, Desa::class, 'kecamatan_id', 'desa_id');
    }

    /**
     * Relasi tidak langsung ke rekaman kasus penyakit hewan melalui desa.
     */
    public function kasusPenyakit(): HasManyThrough
    {
        return $this->hasManyThrough(KasusPenyakit::class, Desa::class, 'kecamatan_id', 'desa_id');
    }
}
