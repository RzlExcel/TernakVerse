<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CarryingCapacityService
{
    /**
     * Konversi populasi ternak ke Satuan Ternak (ST) sesuai standar Kementan & BPS.
     * - Sapi Potong / Sapi Perah = 1.0 ST
     * - Kambing / Domba / Kambing Perah = 0.14 ST
     * - Unggas (Ayam Potong / Ayam Petelur / Kampung / Itik) = 0.01 ST
     */
    public function getConversionFactor(string $kode): float
    {
        $kodeUpper = strtoupper(trim($kode));

        return match ($kodeUpper) {
            'SAPI_POTONG', 'SAPI_PERAH' => 1.0,
            'KAMBING', 'DOMBA', 'KAMBING_PERAH' => 0.14,
            'AYAM_POTONG', 'AYAM_TELUR', 'AYAM_KAMPUNG', 'ITIK' => 0.01,
            default => match (true) {
                str_contains($kodeUpper, 'SAPI') || str_contains($kodeUpper, 'KERBAU') || str_contains($kodeUpper, 'KUDA') => 1.0,
                str_contains($kodeUpper, 'KAMBING') || str_contains($kodeUpper, 'DOMBA') => 0.14,
                str_contains($kodeUpper, 'AYAM') || str_contains($kodeUpper, 'UNGGAS') || str_contains($kodeUpper, 'ITIK') => 0.01,
                default => 0.14,
            },
        };
    }

    /**
     * Menghitung daya dukung pakan dan Satuan Ternak (ST) untuk satu kecamatan.
     *
     * @return array{
     *     kecamatan_id: int,
     *     nama_kecamatan: string,
     *     total_st: float,
     *     luas_hmt_ha: float,
     *     kapasitas_tampung_st: float,
     *     indeks: float,
     *     status: 'SURPLUS'|'WASPADA'|'DEFISIT',
     *     status_label: string
     * }
     */
    public function calculateKecamatan(int $kecamatanId, int $tahun = 2026, ?int $triwulan = 3): array
    {
        $kecamatan = DB::table('kecamatan')->where('id', $kecamatanId)->first();
        $namaKecamatan = $kecamatan ? (string) $kecamatan->nama_kecamatan : "Kecamatan #{$kecamatanId}";
        $rawHmt = $kecamatan ? (float) ($kecamatan->luas_hmt_ha ?? 0.0) : 0.0;
        $luasHmtHa = $rawHmt > 0.0 ? round($rawHmt, 2) : 100.0;

        $populasiRows = DB::table('populasi_ternak as p')
            ->join('desa as d', 'd.id', '=', 'p.desa_id')
            ->join('komoditas as k', 'k.id', '=', 'p.komoditas_id')
            ->where('d.kecamatan_id', $kecamatanId)
            ->where('p.tahun', $tahun)
            ->when($triwulan !== null, fn ($q) => $q->where('p.triwulan', $triwulan))
            ->select('k.kode', DB::raw('SUM(p.jumlah_total) as total_ekor'))
            ->groupBy('k.kode')
            ->get();

        $totalSt = 0.0;
        foreach ($populasiRows as $row) {
            $factor = $this->getConversionFactor((string) $row->kode);
            $totalSt += ((float) $row->total_ekor) * $factor;
        }
        $totalSt = round($totalSt, 2);

        // Kapasitas Tampung Hijauan = Luas HMT * 6.0 ST/ha (standard Kementan 1 Ha rumput gajah menopang 6 ST)
        $kapasitasTampungSt = round($luasHmtHa * 6.0, 2);

        // Indeks Daya Dukung = Kapasitas Tampung / (Total ST > 0 ? Total ST : 1)
        $denominator = $totalSt > 0.0 ? $totalSt : 1.0;
        $indeks = round($kapasitasTampungSt / $denominator, 2);

        [$status, $statusLabel] = $this->resolveStatus($indeks);

        return [
            'kecamatan_id' => $kecamatanId,
            'nama_kecamatan' => $namaKecamatan,
            'total_st' => $totalSt,
            'luas_hmt_ha' => $luasHmtHa,
            'kapasitas_tampung_st' => $kapasitasTampungSt,
            'indeks' => $indeks,
            'status' => $status,
            'status_label' => $statusLabel,
        ];
    }

    /**
     * Mengagregasikan metrik daya dukung pakan seluruh Kabupaten Banyumas (27 Kecamatan).
     *
     * @return array{
     *     total_st: float,
     *     luas_hmt_ha: float,
     *     kapasitas_tampung_st: float,
     *     indeks: float,
     *     status: 'SURPLUS'|'WASPADA'|'DEFISIT',
     *     status_label: string,
     *     by_kecamatan: list<array<string, mixed>>
     * }
     */
    public function calculateKabupaten(int $tahun = 2026, ?int $triwulan = 3): array
    {
        $kecamatans = DB::table('kecamatan')->orderBy('id')->get();

        if ($kecamatans->isEmpty()) {
            return [
                'total_st' => 0.0,
                'luas_hmt_ha' => 2700.0,
                'kapasitas_tampung_st' => 16200.0,
                'indeks' => 16200.0,
                'status' => 'SURPLUS',
                'status_label' => 'Surplus Pakan Hijauan',
                'by_kecamatan' => [],
            ];
        }

        // Ambil data populasi ternak per kecamatan & komoditas dalam 1 query optimal
        $populasiRows = DB::table('populasi_ternak as p')
            ->join('desa as d', 'd.id', '=', 'p.desa_id')
            ->join('komoditas as k', 'k.id', '=', 'p.komoditas_id')
            ->where('p.tahun', $tahun)
            ->when($triwulan !== null, fn ($q) => $q->where('p.triwulan', $triwulan))
            ->select('d.kecamatan_id', 'k.kode', DB::raw('SUM(p.jumlah_total) as total_ekor'))
            ->groupBy('d.kecamatan_id', 'k.kode')
            ->get()
            ->groupBy('kecamatan_id');

        $totalStKabupaten = 0.0;
        $totalLuasHmtKabupaten = 0.0;
        $totalKapasitasKabupaten = 0.0;
        $byKecamatan = [];

        foreach ($kecamatans as $kecamatan) {
            $kecId = (int) $kecamatan->id;
            $namaKec = (string) $kecamatan->nama_kecamatan;
            $rawHmt = (float) ($kecamatan->luas_hmt_ha ?? 0.0);
            $kecHmt = $rawHmt > 0.0 ? round($rawHmt, 2) : 100.0;

            $rows = $populasiRows->get($kecId, collect());
            $kecSt = 0.0;
            foreach ($rows as $r) {
                $factor = $this->getConversionFactor((string) $r->kode);
                $kecSt += ((float) $r->total_ekor) * $factor;
            }
            $kecSt = round($kecSt, 2);

            $kecKapasitas = round($kecHmt * 6.0, 2);
            $kecIndeks = round($kecKapasitas / ($kecSt > 0.0 ? $kecSt : 1.0), 2);
            [$kecStatus, $kecStatusLabel] = $this->resolveStatus($kecIndeks);

            $totalStKabupaten += $kecSt;
            $totalLuasHmtKabupaten += $kecHmt;
            $totalKapasitasKabupaten += $kecKapasitas;

            $byKecamatan[] = [
                'kecamatan_id' => $kecId,
                'nama_kecamatan' => $namaKec,
                'kode_kecamatan' => (string) ($kecamatan->kode_kecamatan ?? ''),
                'total_st' => $kecSt,
                'luas_hmt_ha' => $kecHmt,
                'kapasitas_tampung_st' => $kecKapasitas,
                'indeks' => $kecIndeks,
                'status' => $kecStatus,
                'status_label' => $kecStatusLabel,
            ];
        }

        $totalStKabupaten = round($totalStKabupaten, 2);
        $totalLuasHmtKabupaten = round($totalLuasHmtKabupaten, 2);
        $totalKapasitasKabupaten = round($totalKapasitasKabupaten, 2);

        $indeksKabupaten = round($totalKapasitasKabupaten / ($totalStKabupaten > 0.0 ? $totalStKabupaten : 1.0), 2);
        [$statusKab, $statusLabelKab] = $this->resolveStatus($indeksKabupaten);

        return [
            'total_st' => $totalStKabupaten,
            'luas_hmt_ha' => $totalLuasHmtKabupaten,
            'kapasitas_tampung_st' => $totalKapasitasKabupaten,
            'indeks' => $indeksKabupaten,
            'status' => $statusKab,
            'status_label' => $statusLabelKab,
            'by_kecamatan' => $byKecamatan,
        ];
    }

    /**
     * Evaluasi status daya dukung pakan berdasarkan rasio indeks.
     *
     * @return array{0: 'SURPLUS'|'WASPADA'|'DEFISIT', 1: string}
     */
    protected function resolveStatus(float $indeks): array
    {
        if ($indeks >= 1.0) {
            return ['SURPLUS', 'Surplus Pakan Hijauan'];
        }

        if ($indeks >= 0.7) {
            return ['WASPADA', 'Waspada / Daya Tampung Pas-pasan'];
        }

        return ['DEFISIT', 'Defisit Pakan Hijauan'];
    }
}
