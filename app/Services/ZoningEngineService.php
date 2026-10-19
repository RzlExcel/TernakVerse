<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Kecamatan;
use App\Models\Desa;
use App\Models\Penyakit;
use App\Models\ParameterZonasi;
use App\Models\KasusPenyakit;
use App\Models\ZonaSnapshotKecamatan;
use App\Models\ZonaSnapshotDesa;

class ZoningEngineService
{
    /**
     * Menjalankan kalkulasi ulang status zonasi Early Warning System (EWS)
     * untuk seluruh 27 Kecamatan di Kabupaten Banyumas.
     */
    public function recalculateZoning(int $tahun, int $triwulan): array
    {
        $penyakitList = Penyakit::with('parameterZonasi')->get();
        $kecamatanList = Kecamatan::all();

        $summary = [
            'tahun' => $tahun,
            'triwulan' => $triwulan,
            'total_kecamatan' => $kecamatanList->count(),
            'zonasi_rekap' => [
                'HIJAU' => 0,
                'KUNING' => 0,
                'MERAH' => 0,
            ],
            'per_penyakit' => [],
            'kecamatan_detail' => [],
        ];

        foreach ($penyakitList as $penyakit) {
            $summary['per_penyakit'][$penyakit->kode] = [
                'HIJAU' => 0,
                'KUNING' => 0,
                'MERAH' => 0,
            ];
        }

        foreach ($kecamatanList as $kec) {
            $desaIds = Desa::where('kecamatan_id', $kec->id)->pluck('id');

            $worstStatus = 'HIJAU';
            $kecamatanKasusAktifTotal = 0;
            $statusPerPenyakit = [];

            foreach ($penyakitList as $penyakit) {
                $param = $penyakit->parameterZonasi;
                $ambangKuningMaks = $param ? (int) $param->ambang_kuning_maks : 5;

                // Hitung total kasus aktif pada kecamatan ini untuk penyakit bersangkutan
                // kasus_aktif = GREATEST(0, (kasus_suspek + kasus_positif) - (kasus_sembuh + kasus_mati + potong_bersyarat))
                $totalAktif = (int) KasusPenyakit::whereIn('desa_id', $desaIds)
                    ->where('penyakit_id', $penyakit->id)
                    ->where('tahun', $tahun)
                    ->where('triwulan', $triwulan)
                    ->sum('kasus_aktif');

                $statusPenyakit = 'HIJAU';
                if ($totalAktif > $ambangKuningMaks) {
                    $statusPenyakit = 'MERAH';
                } elseif ($totalAktif > 0) {
                    $statusPenyakit = 'KUNING';
                }

                // Upsert snapshot tingkat kecamatan untuk penyakit ini
                ZonaSnapshotKecamatan::updateOrCreate(
                    [
                        'kecamatan_id' => $kec->id,
                        'penyakit_id'  => $penyakit->id,
                        'tahun'         => $tahun,
                        'triwulan'      => $triwulan,
                    ],
                    [
                        'status_zona'        => $statusPenyakit,
                        'total_kasus_aktif'  => $totalAktif,
                        'waktu_kalkulasi'    => now(),
                    ]
                );

                $summary['per_penyakit'][$penyakit->kode][$statusPenyakit]++;
                $statusPerPenyakit[$penyakit->kode] = [
                    'status' => $statusPenyakit,
                    'kasus_aktif' => $totalAktif,
                ];

                $kecamatanKasusAktifTotal += $totalAktif;

                // Terapkan worst-case scenario: MERAH > KUNING > HIJAU
                if ($statusPenyakit === 'MERAH') {
                    $worstStatus = 'MERAH';
                } elseif ($statusPenyakit === 'KUNING' && $worstStatus !== 'MERAH') {
                    $worstStatus = 'KUNING';
                }
            }

            $summary['zonasi_rekap'][$worstStatus]++;
            $summary['kecamatan_detail'][] = [
                'kecamatan_id' => $kec->id,
                'kode_kecamatan' => $kec->kode_kecamatan,
                'nama_kecamatan' => $kec->nama_kecamatan,
                'status_final' => $worstStatus,
                'total_kasus_aktif' => $kecamatanKasusAktifTotal,
                'rincian' => $statusPerPenyakit,
            ];
        }

        return $summary;
    }
}
