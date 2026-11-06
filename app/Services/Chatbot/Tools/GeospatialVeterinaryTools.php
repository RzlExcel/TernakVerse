<?php

declare(strict_types=1);

namespace App\Services\Chatbot\Tools;

use App\Models\FasilitasRph;
use App\Models\KasusPenyakit;
use App\Models\Kecamatan;
use App\Models\Penyakit;
use App\Models\PopulasiTernak;
use App\Models\ZonaSnapshotKecamatan;
use App\Services\Chatbot\Contracts\ChatbotToolInterface;
use Illuminate\Support\Facades\DB;

/**
 * Geospatial and Veterinary Database Tools for TernakVerse AI Chatbot.
 * Provides ReAct function calling tools for zoning status, outbreak statistics,
 * RPH facilities, and livestock populations.
 */
class GeospatialVeterinaryTools
{
    /**
     * Get all registered database tools.
     *
     * @return list<ChatbotToolInterface>
     */
    public static function all(): array
    {
        return [
            new GetStatusZonasiTool(),
            new GetStatistikKasusTool(),
            new GetFasilitasRphTool(),
            new GetPopulasiTernakTool(),
        ];
    }

    /**
     * Helper dispatcher for getStatusZonasi.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public static function getStatusZonasi(array $arguments): array
    {
        return (new GetStatusZonasiTool())->execute($arguments);
    }

    /**
     * Helper dispatcher for getStatistikKasus.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public static function getStatistikKasus(array $arguments): array
    {
        return (new GetStatistikKasusTool())->execute($arguments);
    }

    /**
     * Helper dispatcher for getFasilitasRph.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public static function getFasilitasRph(array $arguments): array
    {
        return (new GetFasilitasRphTool())->execute($arguments);
    }

    /**
     * Helper dispatcher for getPopulasiTernak.
     *
     * @param array<string, mixed> $arguments
     * @return array<string, mixed>
     */
    public static function getPopulasiTernak(array $arguments): array
    {
        return (new GetPopulasiTernakTool())->execute($arguments);
    }
}

/**
 * Tool 1: getStatusZonasi
 * Mengambil status zonasi epidemiologi terkini (HIJAU, KUNING, MERAH) pada tingkat kecamatan di Kabupaten Banyumas.
 */
class GetStatusZonasiTool implements ChatbotToolInterface
{
    public function getName(): string
    {
        return 'getStatusZonasi';
    }

    public function getDescription(): string
    {
        return 'Mengambil status zonasi risiko epidemiologi PMK, LSD, atau ANTHRAX (HIJAU, KUNING, MERAH) dan jumlah kasus aktif pada kecamatan di Kabupaten Banyumas beserta rekomendasi tindakan operasional.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nama_kecamatan' => [
                    'type' => 'string',
                    'description' => 'Nama kecamatan di Kabupaten Banyumas, misalnya: Cilongok, Ajibarang, Baturraden, Sumbang.',
                ],
                'penyakit' => [
                    'type' => 'string',
                    'enum' => ['PMK', 'LSD', 'ANTHRAX'],
                    'description' => "Kode penyakit yang ingin diperiksa status zonasinya. Default bernilai 'PMK'.",
                ],
                'tahun' => [
                    'type' => 'integer',
                    'description' => 'Tahun snapshot data, misalnya: 2026.',
                ],
            ],
            'required' => ['nama_kecamatan'],
        ];
    }

    public function execute(array $arguments): array
    {
        $namaKecamatan = trim((string) ($arguments['nama_kecamatan'] ?? ''));
        if ($namaKecamatan === '') {
            return [
                'status' => 'error',
                'message' => 'Parameter nama_kecamatan wajib disertakan.',
            ];
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $kecamatan = Kecamatan::where('nama_kecamatan', $likeOperator, '%' . $namaKecamatan . '%')->first();
        if (! $kecamatan) {
            return [
                'status' => 'error',
                'message' => "Kecamatan '{$namaKecamatan}' tidak ditemukan di Kabupaten Banyumas.",
            ];
        }

        $kodePenyakit = strtoupper(trim((string) ($arguments['penyakit'] ?? 'PMK')));
        $penyakit = Penyakit::where('kode', $kodePenyakit)
            ->orWhere('nama', $likeOperator, '%' . $kodePenyakit . '%')
            ->first();

        if (! $penyakit) {
            return [
                'status' => 'error',
                'message' => "Master data penyakit '{$kodePenyakit}' tidak ditemukan.",
            ];
        }

        $snapshotQuery = ZonaSnapshotKecamatan::where('kecamatan_id', $kecamatan->id)
            ->where('penyakit_id', $penyakit->id);

        if (! empty($arguments['tahun'])) {
            $snapshotQuery->where('tahun', (int) $arguments['tahun']);
        }

        /** @var ZonaSnapshotKecamatan|null $snapshot */
        $snapshot = $snapshotQuery->orderByDesc('tahun')
            ->orderByDesc('triwulan')
            ->first();

        if (! $snapshot) {
            return [
                'status' => 'success',
                'wilayah' => 'Kecamatan ' . $kecamatan->nama_kecamatan,
                'penyakit' => $penyakit->kode,
                'status_zona' => 'HIJAU',
                'total_kasus_aktif' => 0,
                'tahun' => ! empty($arguments['tahun']) ? (int) $arguments['tahun'] : (int) date('Y'),
                'triwulan' => 1,
                'waktu_kalkulasi' => now()->format('Y-m-d H:i:s'),
                'rekomendasi_tindakan' => 'Tidak ada rekaman kasus aktif. Wilayah aman (Zona Hijau). Tetap patuhi protokol biosekuriti kandang dan vaksinasi rutin.',
            ];
        }

        $statusZona = strtoupper((string) ($snapshot->status_zona ?? 'HIJAU'));
        $rekomendasi = match ($statusZona) {
            'MERAH' => 'Pengawasan ketat lalu lintas ternak keluar-masuk wilayah, pembatasan pasar hewan, karantina ternak terinfeksi, desinfeksi berkala, dan pelarangan mutasi ternak tanpa sertifikat veteriner.',
            'KUNING' => 'Vigilansi dan pemantauan aktif, pembatasan pergerakan ternak rentan, pemeriksaan klinis berkala di poskeswan setempat, serta percepatan vaksinasi ternak di sekitar episentrum.',
            default => 'Zona aman. Pertahankan biosekuriti kandang standar, lakukan vaksinasi rutin, dan lengkapi Surat Keterangan Kesehatan Hewan (SKKH) sebelum lalu lintas perdagangan ternak.',
        };

        return [
            'status' => 'success',
            'wilayah' => 'Kecamatan ' . $kecamatan->nama_kecamatan,
            'penyakit' => $penyakit->kode,
            'status_zona' => $statusZona,
            'total_kasus_aktif' => (int) $snapshot->total_kasus_aktif,
            'tahun' => (int) $snapshot->tahun,
            'triwulan' => (int) $snapshot->triwulan,
            'waktu_kalkulasi' => $snapshot->waktu_kalkulasi ? $snapshot->waktu_kalkulasi->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'rekomendasi_tindakan' => $rekomendasi,
        ];
    }
}

/**
 * Tool 2: getStatistikKasus
 * Menghitung total kasus penyakit ternak (positif, sembuh, mati, potong bersyarat, kasus aktif).
 */
class GetStatistikKasusTool implements ChatbotToolInterface
{
    public function getName(): string
    {
        return 'getStatistikKasus';
    }

    public function getDescription(): string
    {
        return 'Mengambil data agregat kasus penyakit hewan meliputi jumlah kasus positif, sembuh, mati, potong bersyarat, dan kasus aktif berdasarkan kecamatan, penyakit, dan tahun.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nama_kecamatan' => [
                    'type' => 'string',
                    'description' => 'Nama kecamatan opsional untuk filter wilayah.',
                ],
                'penyakit' => [
                    'type' => 'string',
                    'description' => 'Kode atau nama penyakit seperti PMK, LSD, atau ANTHRAX.',
                ],
                'tahun' => [
                    'type' => 'integer',
                    'description' => 'Tahun pencatatan kasus.',
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = KasusPenyakit::query();

        $namaKecamatan = ! empty($arguments['nama_kecamatan']) ? trim((string) $arguments['nama_kecamatan']) : null;
        if ($namaKecamatan !== null && $namaKecamatan !== '') {
            $query->whereHas('desa.kecamatan', function ($q) use ($namaKecamatan, $likeOperator) {
                $q->where('nama_kecamatan', $likeOperator, '%' . $namaKecamatan . '%');
            });
        }

        $penyakitInput = ! empty($arguments['penyakit']) ? trim((string) $arguments['penyakit']) : null;
        if ($penyakitInput !== null && $penyakitInput !== '') {
            $query->whereHas('penyakit', function ($q) use ($penyakitInput, $likeOperator) {
                $q->where('kode', strtoupper($penyakitInput))
                    ->orWhere('nama', $likeOperator, '%' . $penyakitInput . '%');
            });
        }

        if (! empty($arguments['tahun'])) {
            $query->where('tahun', (int) $arguments['tahun']);
        }

        $stat = $query->selectRaw('
            COALESCE(SUM(kasus_positif), 0) as total_positif,
            COALESCE(SUM(kasus_sembuh), 0) as total_sembuh,
            COALESCE(SUM(kasus_mati), 0) as total_mati,
            COALESCE(SUM(potong_bersyarat), 0) as total_potong_bersyarat,
            COALESCE(SUM(kasus_aktif), 0) as total_aktif
        ')->first();

        $totalPositif = (int) ($stat->total_positif ?? 0);
        $totalSembuh = (int) ($stat->total_sembuh ?? 0);
        $totalMati = (int) ($stat->total_mati ?? 0);
        $totalPotongBersyarat = (int) ($stat->total_potong_bersyarat ?? 0);
        $totalAktif = (int) ($stat->total_aktif ?? 0);

        if ($totalAktif === 0 && $totalPositif > 0) {
            $totalAktif = max(0, $totalPositif - $totalSembuh - $totalMati - $totalPotongBersyarat);
        }

        return [
            'status' => 'success',
            'wilayah' => $namaKecamatan ? 'Kecamatan ' . $namaKecamatan : 'Kabupaten Banyumas (Seluruh Wilayah)',
            'penyakit' => $penyakitInput ? strtoupper($penyakitInput) : 'Semua Penyakit',
            'tahun' => ! empty($arguments['tahun']) ? (int) $arguments['tahun'] : 'Semua Periode',
            'total_positif' => $totalPositif,
            'total_sembuh' => $totalSembuh,
            'total_mati' => $totalMati,
            'total_potong_bersyarat' => $totalPotongBersyarat,
            'total_aktif' => $totalAktif,
        ];
    }
}

/**
 * Tool 3: getFasilitasRph
 * Mencari fasilitas Rumah Potong Hewan (RPH) resmi di Kabupaten Banyumas beserta kapasitas dan lokasi geospasial.
 */
class GetFasilitasRphTool implements ChatbotToolInterface
{
    public function getName(): string
    {
        return 'getFasilitasRph';
    }

    public function getDescription(): string
    {
        return 'Mencari fasilitas Rumah Potong Hewan (RPH) resmi di Kabupaten Banyumas beserta kapasitas potong harian, status operasional, alamat, dan jarak terdekat jika koordinat disediakan.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'nama_kecamatan' => [
                    'type' => 'string',
                    'description' => 'Kecamatan lokasi fasilitas RPH.',
                ],
                'latitude' => [
                    'type' => 'number',
                    'description' => 'Titik latitude pengguna untuk perhitungan jarak terdekat.',
                ],
                'longitude' => [
                    'type' => 'number',
                    'description' => 'Titik longitude pengguna untuk perhitungan jarak terdekat.',
                ],
                'radius_km' => [
                    'type' => 'number',
                    'description' => 'Radius pencarian dalam kilometer. Default 15 km.',
                ],
            ],
        ];
    }

    public function execute(array $arguments): array
    {
        $isPgSql = DB::connection()->getDriverName() === 'pgsql';
        $likeOperator = $isPgSql ? 'ILIKE' : 'LIKE';

        $query = FasilitasRph::with('kecamatan')->rph();

        if (! empty($arguments['nama_kecamatan'])) {
            $namaKecamatan = trim((string) $arguments['nama_kecamatan']);
            $query->whereHas('kecamatan', function ($q) use ($namaKecamatan, $likeOperator) {
                $q->where('nama_kecamatan', $likeOperator, '%' . $namaKecamatan . '%');
            });
        }

        $hasCoordinates = ! empty($arguments['latitude']) && ! empty($arguments['longitude']);
        $lat = $hasCoordinates ? (float) $arguments['latitude'] : 0.0;
        $lng = $hasCoordinates ? (float) $arguments['longitude'] : 0.0;
        $radiusKm = ! empty($arguments['radius_km']) ? (float) $arguments['radius_km'] : 15.0;
        $radiusMeters = $radiusKm * 1000.0;

        if ($hasCoordinates && $isPgSql) {
            $query->whereRaw(
                'ST_DWithin(geom_titik::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?)',
                [$lng, $lat, $radiusMeters]
            )->selectRaw(
                'fasilitas_rph.*, ST_Distance(geom_titik::geography, ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography) as jarak_meter',
                [$lng, $lat]
            )->orderBy('jarak_meter');
        } else {
            $query->orderBy('nama_rph');
        }

        $records = $query->get();

        $facilities = [];
        foreach ($records as $record) {
            $jarakMeter = null;
            if (isset($record->jarak_meter)) {
                $jarakMeter = round((float) $record->jarak_meter);
            } elseif ($hasCoordinates) {
                $geom = $record->geom_titik;
                if (is_array($geom) && isset($geom['coordinates']) && is_array($geom['coordinates']) && count($geom['coordinates']) >= 2) {
                    $pointLng = (float) $geom['coordinates'][0];
                    $pointLat = (float) $geom['coordinates'][1];
                    $distance = $this->calculateHaversineDistance($lat, $lng, $pointLat, $pointLng);
                    if ($distance <= $radiusMeters) {
                        $jarakMeter = round($distance);
                    } else {
                        continue;
                    }
                }
            }

            $facilities[] = [
                'nama_rph' => $record->nama_rph,
                'alamat_lengkap' => $record->alamat_lengkap ?? '-',
                'nama_kecamatan' => $record->kecamatan?->nama_kecamatan ?? 'Kabupaten Banyumas',
                'status_operasional' => $record->status_operasional ?? 'Aktif',
                'kapasitas_potong_harian' => (int) ($record->kapasitas_potong_ekor_hari ?? 0),
                'jarak_meter' => $jarakMeter,
                'penanggung_jawab' => $record->penanggung_jawab ?? null,
                'nomor_kontak' => $record->nomor_kontak ?? null,
            ];
        }

        if ($hasCoordinates && ! $isPgSql) {
            usort($facilities, function ($a, $b) {
                return ($a['jarak_meter'] ?? 0) <=> ($b['jarak_meter'] ?? 0);
            });
        }

        return [
            'status' => 'success',
            'total_ditemukan' => count($facilities),
            'fasilitas' => $facilities,
        ];
    }

    /**
     * Calculate Haversine distance in meters between two lat/lng points.
     */
    protected function calculateHaversineDistance(float $latFrom, float $lngFrom, float $latTo, float $lngTo): float
    {
        $earthRadius = 6371000.0;

        $latDelta = deg2rad($latTo - $latFrom);
        $lngDelta = deg2rad($lngTo - $lngFrom);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($latFrom)) * cos(deg2rad($latTo)) *
            sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}

/**
 * Tool 4: getPopulasiTernak
 * Memperoleh data sebaran jumlah populasi ternak (jantan, betina, total ekor).
 */
class GetPopulasiTernakTool implements ChatbotToolInterface
{
    public function getName(): string
    {
        return 'getPopulasiTernak';
    }

    public function getDescription(): string
    {
        return 'Mengambil data statistik populasi ternak (jantan, betina, total ekor) berdasarkan komoditas dan kecamatan di Kabupaten Banyumas.';
    }

    public function getParametersSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'komoditas' => [
                    'type' => 'string',
                    'description' => "Nama komoditas ternak, contoh: 'Sapi Potong', 'Sapi Perah', 'Kambing', 'Domba'.",
                ],
                'nama_kecamatan' => [
                    'type' => 'string',
                    'description' => 'Nama kecamatan spesifik jika ingin memfilter wilayah.',
                ],
                'tahun' => [
                    'type' => 'integer',
                    'description' => 'Tahun sensus atau pencatatan populasi.',
                ],
            ],
            'required' => ['komoditas'],
        ];
    }

    public function execute(array $arguments): array
    {
        $komoditasInput = trim((string) ($arguments['komoditas'] ?? ''));
        if ($komoditasInput === '') {
            return [
                'status' => 'error',
                'message' => 'Parameter komoditas wajib disertakan (contoh: Sapi Potong, Sapi Perah, Kambing, Domba).',
            ];
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ILIKE' : 'LIKE';

        $query = PopulasiTernak::query();

        $query->whereHas('komoditas', function ($q) use ($komoditasInput, $likeOperator) {
            $q->where('nama', $likeOperator, '%' . $komoditasInput . '%')
                ->orWhere('kode', strtoupper($komoditasInput));
        });

        $namaKecamatan = ! empty($arguments['nama_kecamatan']) ? trim((string) $arguments['nama_kecamatan']) : null;
        if ($namaKecamatan !== null && $namaKecamatan !== '') {
            $query->whereHas('desa.kecamatan', function ($q) use ($namaKecamatan, $likeOperator) {
                $q->where('nama_kecamatan', $likeOperator, '%' . $namaKecamatan . '%');
            });
        }

        if (! empty($arguments['tahun'])) {
            $query->where('tahun', (int) $arguments['tahun']);
        }

        $stat = $query->selectRaw('
            COALESCE(SUM(jumlah_jantan), 0) as total_jantan,
            COALESCE(SUM(jumlah_betina), 0) as total_betina,
            COALESCE(SUM(jumlah_total), 0) as grand_total
        ')->first();

        $totalJantan = (int) ($stat->total_jantan ?? 0);
        $totalBetina = (int) ($stat->total_betina ?? 0);
        $grandTotal = (int) ($stat->grand_total ?? 0);

        if ($grandTotal === 0 && ($totalJantan + $totalBetina) > 0) {
            $grandTotal = $totalJantan + $totalBetina;
        }

        return [
            'status' => 'success',
            'komoditas' => $komoditasInput,
            'nama_kecamatan' => $namaKecamatan ? 'Kecamatan ' . $namaKecamatan : 'Kabupaten Banyumas (Seluruh Wilayah)',
            'tahun' => ! empty($arguments['tahun']) ? (int) $arguments['tahun'] : 'Semua Periode',
            'total_jantan' => $totalJantan,
            'total_betina' => $totalBetina,
            'grand_total' => $grandTotal,
        ];
    }
}
