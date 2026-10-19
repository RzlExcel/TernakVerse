<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriProduksi extends Model
{
    use HasFactory;

    protected $table = 'kategori_produksi';

    const UPDATED_AT = null;

    protected $fillable = [
        'nama',
        'satuan',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Relasi ke rekaman data produksi yang menggunakan kategori ini (Daging, Susu, Telur).
     */
    public function produksiTernak(): HasMany
    {
        return $this->hasMany(ProduksiTernak::class, 'kategori_produksi_id');
    }
}
