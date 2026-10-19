<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\PopulasiTernak;
use App\Models\ProduksiTernak;
use App\Models\KasusPenyakit;
use App\Models\FasilitasRph;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Komoditas;
use App\Models\KategoriProduksi;
use App\Models\Penyakit;

class AdminPanelController extends Controller
{
    /**
     * Halaman Utama Admin Data Geospasial & Mutasi (Inertia v2 + Vue 3).
     * Menyajikan Handshake Props tabel terpaginasi, kamus relasi biologis, dan master options.
     */
    public function index(Request $request): Response
    {
        // 1. DATA RESOURCE TERPAGINASI
        $tables = [
            'populasi' => PopulasiTernak::with(['desa.kecamatan', 'komoditas'])
                ->orderByDesc('tahun')
                ->orderByDesc('triwulan')
                ->paginate(15, ['*'], 'populasi_page')
                ->withQueryString(),

            'produksi' => ProduksiTernak::with(['desa.kecamatan', 'komoditas', 'kategoriProduksi'])
                ->orderByDesc('tahun')
                ->orderByDesc('triwulan')
                ->paginate(15, ['*'], 'produksi_page')
                ->withQueryString(),

            'kasus_penyakit' => KasusPenyakit::with(['desa.kecamatan', 'penyakit', 'komoditas'])
                ->orderByDesc('tanggal_laporan')
                ->paginate(15, ['*'], 'kasus_page')
                ->withQueryString(),

            'fasilitas_rph' => FasilitasRph::with('kecamatan')
                ->orderBy('nama_rph')
                ->paginate(15, ['*'], 'rph_page')
                ->withQueryString(),
        ];

        // 2. KAMUS ATURAN BIOLOGIS (ALLOWED RELATIONS UNTUK DROPDOWN MODAL DINAMIS)
        $allowedRelations = [
            'susu'   => ['SAPI_PERAH', 'KAMBING', 'DOMBA'],
            'telur'  => ['AYAM_TELUR'],
            'daging' => ['SAPI_POTONG', 'SAPI_PERAH', 'KAMBING', 'DOMBA', 'AYAM_POTONG', 'AYAM_TELUR'],
            'pmk'    => ['SAPI_POTONG', 'SAPI_PERAH', 'KAMBING', 'DOMBA'],
            'lsd'    => ['SAPI_POTONG', 'SAPI_PERAH'],
        ];

        // 3. MASTER OPTIONS UNTUK FORM FILTER & INPUT
        $masterOptions = [
            'kecamatan' => Kecamatan::select('id', 'nama_kecamatan', 'kode_kecamatan')
                ->orderBy('nama_kecamatan')
                ->get(),

            'desa' => Desa::select('id', 'nama_desa', 'kecamatan_id')
                ->orderBy('nama_desa')
                ->get(),

            'komoditas' => Komoditas::select('id', 'nama', 'kode', 'kelompok')
                ->get(),

            'kategori_produksi' => KategoriProduksi::select('id', 'nama', 'satuan')
                ->get(),

            'penyakit' => Penyakit::select('id', 'nama', 'kode', 'tingkat_bahaya')
                ->get(),

            'status_zonasi' => ['HIJAU', 'KUNING', 'MERAH'],
        ];

        return Inertia::render('Admin/Panel', [
            'tables' => $tables,
            'allowedRelations' => $allowedRelations,
            'masterOptions' => $masterOptions,
        ]);
    }
}
