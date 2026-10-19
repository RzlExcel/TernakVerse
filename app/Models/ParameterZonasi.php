<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParameterZonasi extends Model
{
    use HasFactory;

    protected $table = 'parameter_zonasi';

    protected $fillable = [
        'penyakit_id',
        'jendela_hari_aktif',
        'ambang_kuning_maks',
    ];

    protected $casts = [
        'penyakit_id' => 'integer',
        'jendela_hari_aktif' => 'integer',
        'ambang_kuning_maks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Master Penyakit terkait.
     */
    public function penyakit(): BelongsTo
    {
        return $this->belongsTo(Penyakit::class, 'penyakit_id');
    }

    /**
     * Helper kalkulasi status zona berdasarkan jumlah kasus aktif.
     * Aturan:
     * - 0 kasus => HIJAU
     * - 1 s/d ambang_kuning_maks => KUNING
     * - > ambang_kuning_maks => MERAH
     */
    public function hitungStatusZona(int $totalKasusAktif): string
    {
        if ($totalKasusAktif <= 0) {
            return 'HIJAU';
        }

        if ($totalKasusAktif <= $this->ambang_kuning_maks) {
            return 'KUNING';
        }

        return 'MERAH';
    }
}
