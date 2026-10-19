<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Komoditas extends Model
{
    use HasFactory;

    protected $table = 'komoditas';

    const UPDATED_AT = null;

    protected $fillable = [
        'kode',
        'nama',
        'kelompok',
        'aktif',
    ];

    protected $casts = [
        'aktif' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Scope untuk komoditas yang berstatus aktif.
     */
    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }

    /**
     * Relasi ke rekaman populasi ternak untuk komoditas ini.
     */
    public function populasiTernak(): HasMany
    {
        return $this->hasMany(PopulasiTernak::class, 'komoditas_id');
    }

    /**
     * Relasi ke rekaman hasil produksi ternak untuk komoditas ini.
     */
    public function produksiTernak(): HasMany
    {
        return $this->hasMany(ProduksiTernak::class, 'komoditas_id');
    }

    /**
     * Relasi ke rekaman kasus penyakit hewan yang menjangkiti komoditas ini.
     */
    public function kasusPenyakit(): HasMany
    {
        return $this->hasMany(KasusPenyakit::class, 'komoditas_id');
    }
}
