<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProduksiTernak extends Model
{
    use HasFactory;

    protected $table = 'produksi_ternak';

    protected $fillable = [
        'desa_id',
        'komoditas_id',
        'kategori_produksi_id',
        'tahun',
        'triwulan',
        'jumlah_produksi',
    ];

    protected $casts = [
        'desa_id' => 'integer',
        'komoditas_id' => 'integer',
        'kategori_produksi_id' => 'integer',
        'tahun' => 'integer',
        'triwulan' => 'integer',
        'jumlah_produksi' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke Desa / Kelurahan lokasi produksi.
     */
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class, 'desa_id');
    }

    /**
     * Relasi ke Komoditas penghasil.
     */
    public function komoditas(): BelongsTo
    {
        return $this->belongsTo(Komoditas::class, 'komoditas_id');
    }

    /**
     * Relasi ke Kategori Hasil Produksi (Daging, Susu, Telur).
     */
    public function kategoriProduksi(): BelongsTo
    {
        return $this->belongsTo(KategoriProduksi::class, 'kategori_produksi_id');
    }

    /**
     * Scope query untuk memfilter periode waktu.
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
