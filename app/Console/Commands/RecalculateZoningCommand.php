<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ZoningEngineService;

class RecalculateZoningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ews:recalculate 
                            {--tahun= : Tahun analisis (default: tahun berjalan)} 
                            {--triwulan= : Triwulan analisis 1-4 (default: triwulan berjalan)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kalkulasi ulang status zonasi EWS risiko penyakit PMK & LSD pada 27 Kecamatan Banyumas';

    /**
     * Execute the console command.
     */
    public function handle(ZoningEngineService $engine): int
    {
        $tahun = (int) ($this->option('tahun') ?: date('Y'));
        $triwulan = (int) ($this->option('triwulan') ?: ceil(date('n') / 3));

        $this->info("===========================================================");
        $this->info("  TERNAKVERSE: Mesin Kalkulasi EWS Wabah PMK & LSD");
        $this->info("  Periode Analisis: Tahun {$tahun} - Triwulan {$triwulan}");
        $this->info("===========================================================");

        $result = $engine->recalculateZoning($tahun, $triwulan);

        $this->info("\n[SELESAI] Hasil Agregasi Zonasi 27 Kecamatan:");
        $this->table(
            ['Status Risiko', 'Jumlah Kecamatan', 'Tindakan Biosekuriti'],
            [
                ['MERAH (Tertular / Bahaya)', $result['zonasi_rekap']['MERAH'], 'Karantina ketat & larangan lalu lintas ternak'],
                ['KUNING (Waspada Penyebaran)', $result['zonasi_rekap']['KUNING'], 'Perketat desinfeksi & pembatasan ternak masuk'],
                ['HIJAU (Aman / Bebas)', $result['zonasi_rekap']['HIJAU'], 'Pertahankan sanitasi & vaksinasi preventif'],
            ]
        );

        return Command::SUCCESS;
    }
}
