<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Penyakit extends Model
{
    use HasFactory;

    protected $table = 'penyakit';

    const UPDATED_AT = null;

    protected $fillable = [
        'kode',
        'nama',
        'nama_latin',
        'tingkat_bahaya',
        'fcode',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke rekaman laporan kasus wabah penyakit ini.
     */
    public function kasusPenyakit(): HasMany
    {
        return $this->hasMany(KasusPenyakit::class, 'penyakit_id');
    }

    /**
     * Relasi ke konfigurasi ambang batas peringatan dini (parameter zonasi).
     */
    public function parameterZonasi(): HasOne
    {
        return $this->hasOne(ParameterZonasi::class, 'penyakit_id');
    }

    /**
     * Relasi ke riwayat snapshot zonasi tingkat kecamatan.
     */
    public function zonaSnapshotsKecamatan(): HasMany
    {
        return $this->hasMany(ZonaSnapshotKecamatan::class, 'penyakit_id');
    }

    /**
     * Relasi ke riwayat snapshot zonasi tingkat desa.
     */
    public function zonaSnapshotsDesa(): HasMany
    {
        return $this->hasMany(ZonaSnapshotDesa::class, 'penyakit_id');
    }
}
