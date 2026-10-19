<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\Kecamatan;
use App\Models\FasilitasRph;

class ReportBuilderService
{
    protected DinasAnalyticsService $analyticsService;

    public function __construct(DinasAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * a. Menghasilkan Berkas Excel Multi-Sheet Resmi (.xlsx)
     */
    public function exportExcel(int $tahun, ?int $triwulan = null): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Hapus sheet default kosong

        // 1. Sheet 1: Ringkasan Eksekutif
        $this->buildSheetRingkasan($spreadsheet, $tahun, $triwulan);

        // 2. Sheet 2: Populasi Ternak (27 Kecamatan x 6 Komoditas)
        $this->buildSheetPopulasi($spreadsheet, $tahun, $triwulan);

        // 3. Sheet 3: Produksi Peternakan (Daging, Susu, Telur)
        $this->buildSheetProduksi($spreadsheet, $tahun, $triwulan);

        // 4. Sheet 4: Kesehatan Hewan & EWS
        $this->buildSheetKesehatan($spreadsheet, $tahun, $triwulan);

        // 5. Sheet 5: Sebaran Fasilitas RPH
        $this->buildSheetRph($spreadsheet);

        // Simpan ke direktori storage/app/public/reports
        $dir = storage_path('app/public/reports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $twLabel = $triwulan ? "TW{$triwulan}" : "Tahunan";
        $fileName = "Laporan_Peternakan_Banyumas_{$tahun}_{$twLabel}_" . date('Ymd_His') . ".xlsx";
        $filePath = "{$dir}/{$fileName}";

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return $filePath;
    }

    /**
     * b. Menghasilkan Berkas PDF Resmi Orientasi Landscape
     */
    public function exportPdf(int $tahun, ?int $triwulan = null): string
    {
        $metrics = $this->analyticsService->getSummaryMetrics($tahun, $triwulan);
        $periodeLabel = $triwulan ? "Triwulan {$triwulan} Tahun {$tahun}" : "Tahun {$tahun} (Kumulatif)";

        // Data rekapitulasi 27 kecamatan
        $kecData = DB::table('v_kugi_administrasi_ar_peternakan as p')
            ->leftJoin('v_kugi_administrasi_ar_penyakithewan as k', 'k.objectid', '=', 'p.objectid')
            ->select('p.wadmkc', 'p.kdcpum', 'p.pop_sapipotong', 'p.pop_sapiperah', 'p.pop_kambing', 'p.pop_domba', 'p.pop_ayampotong', 'p.pop_ayamtelur', 'p.pop_total', 'k.kasus_pmk', 'k.kasus_lsd', 'k.kasus_aktif')
            ->orderBy('p.wadmkc')
            ->get();

        $html = $this->renderPdfTemplate($tahun, $periodeLabel, $metrics, $kecData);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $dir = storage_path('app/public/reports');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $twLabel = $triwulan ? "TW{$triwulan}" : "Tahunan";
        $fileName = "Laporan_Resmi_Peternakan_Banyumas_{$tahun}_{$twLabel}_" . date('Ymd_His') . ".pdf";
        $filePath = "{$dir}/{$fileName}";

        file_put_contents($filePath, $dompdf->output());

        return $filePath;
    }

    // -------------------------------------------------------------
    // BUILDER SHEET EXCEL
    // -------------------------------------------------------------

    protected function buildSheetRingkasan(Spreadsheet $spreadsheet, int $tahun, ?int $triwulan): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ringkasan Eksekutif');

        $metrics = $this->analyticsService->getSummaryMetrics($tahun, $triwulan);

        $sheet->setCellValue('A1', 'PEMERINTAH KABUPATEN BANYUMAS');
        $sheet->setCellValue('A2', 'DINAS PERTANIAN DAN KETAHANAN PANGAN');
        $sheet->setCellValue('A3', 'RINGKASAN EKSEKUTIF STATISTIK PETERNAKAN & KESEHATAN HEWAN');
        $sheet->setCellValue('A4', 'Periode: ' . ($triwulan ? "Triwulan {$triwulan} Tahun {$tahun}" : "Tahun {$tahun}"));

        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
        $sheet->getStyle('A1:A3')->getFont()->setSize(12);

        // KPI Box
        $sheet->setCellValue('A6', 'INDIKATOR KINERJA UTAMA (KPI)');
        $sheet->setCellValue('B6', 'NILAI CAPAIAN');
        $sheet->setCellValue('C6', 'SATUAN');
        $sheet->setCellValue('D6', 'CATATAN STRATEGIS');

        $kpiRows = [
            ['Total Populasi Ternak Besar (Sapi Potong & Perah)', $metrics['kpi']['populasi']['ternak_besar'], 'Ekor', 'Sentra di Sumbang, Kalibagor, Baturraden'],
            ['Total Populasi Ternak Kecil (Kambing & Domba)', $metrics['kpi']['populasi']['ternak_kecil'], 'Ekor', 'Sentra di Cilongok, Pekuncen, Somagede'],
            ['Total Unggas (Ayam Potong & Telur)', $metrics['kpi']['populasi']['unggas'], 'Ekor', 'Peternakan rakyat & kemitraan'],
            ['Total Produksi Daging', $metrics['kpi']['produksi']['daging_kg'], 'Kg', 'Berasal dari sapi potong, perah, kambing, domba, ayam'],
            ['Total Produksi Susu Segar', $metrics['kpi']['produksi']['susu_liter'], 'Liter', 'Sentra klaster sapi perah Baturraden & Pekuncen'],
            ['Total Produksi Telur', $metrics['kpi']['produksi']['telur_kg'], 'Kg', 'Berasal dari ayam ras petelur (layer)'],
            ['Kasus Aktif PMK', $metrics['kpi']['kesehatan_hewan']['pmk']['aktif'], 'Kasus', 'Dalam penanganan surveilans ketat'],
            ['Kasus Aktif LSD', $metrics['kpi']['kesehatan_hewan']['lsd']['aktif'], 'Kasus', 'Vaksinasi berkala dan biosekuriti kandang'],
            ['Jumlah Fasilitas RPH Aktif', $metrics['kpi']['fasilitas_rph']['total_aktif'], 'Unit', 'Kapasitas total ' . $metrics['kpi']['fasilitas_rph']['kapasitas_harian_ekor'] . ' ekor/hari'],
        ];

        $r = 7;
        foreach ($kpiRows as $kpi) {
            $sheet->setCellValue("A{$r}", $kpi[0]);
            $sheet->setCellValue("B{$r}", $kpi[1]);
            $sheet->setCellValue("C{$r}", $kpi[2]);
            $sheet->setCellValue("D{$r}", $kpi[3]);
            $r++;
        }

        $this->styleTableHeader($sheet, 'A6:D6');
        $this->styleTableBorder($sheet, "A6:D" . ($r - 1));
        $this->autoFitColumns($sheet, ['A', 'B', 'C', 'D']);
    }

    protected function buildSheetPopulasi(Spreadsheet $spreadsheet, int $tahun, ?int $triwulan): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Populasi Ternak');

        $sheet->setCellValue('A1', 'REKAPITULASI POPULASI TERNAK 27 KECAMATAN');
        $sheet->setCellValue('A2', 'Tahun: ' . $tahun . ($triwulan ? " (Triwulan {$triwulan})" : ""));
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        $headers = [
            'No', 'Nama Kecamatan', 'Kode BPS',
            'Sapi Potong (Ekor)', 'Sapi Perah (Ekor)', 'Kambing (Ekor)',
            'Domba (Ekor)', 'Ayam Potong (Ekor)', 'Ayam Telur (Ekor)',
            'Total Populasi (Ekor)'
        ];

        $sheet->fromArray($headers, null, 'A4');
        $this->styleTableHeader($sheet, 'A4:J4');

        $rows = DB::table('v_kugi_administrasi_ar_peternakan')
            ->select('wadmkc', 'kdcpum', 'pop_sapipotong', 'pop_sapiperah', 'pop_kambing', 'pop_domba', 'pop_ayampotong', 'pop_ayamtelur', 'pop_total')
            ->orderBy('wadmkc')
            ->get();

        $r = 5;
        $no = 1;
        foreach ($rows as $item) {
            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $item->wadmkc);
            $sheet->setCellValue("C{$r}", $item->kdcpum);
            $sheet->setCellValue("D{$r}", $item->pop_sapipotong);
            $sheet->setCellValue("E{$r}", $item->pop_sapiperah);
            $sheet->setCellValue("F{$r}", $item->pop_kambing);
            $sheet->setCellValue("G{$r}", $item->pop_domba);
            $sheet->setCellValue("H{$r}", $item->pop_ayampotong);
            $sheet->setCellValue("I{$r}", $item->pop_ayamtelur);
            $sheet->setCellValue("J{$r}", $item->pop_total);
            $r++;
        }

        // Total Row
        $sheet->setCellValue("B{$r}", 'TOTAL KAB. BANYUMAS');
        for ($col = 'D'; $col <= 'J'; $col++) {
            $sheet->setCellValue("{$col}{$r}", "=SUM({$col}5:{$col}" . ($r - 1) . ")");
        }
        $sheet->getStyle("A{$r}:J{$r}")->getFont()->setBold(true);

        $this->styleTableBorder($sheet, "A4:J{$r}");
        $this->autoFitColumns($sheet, range('A', 'J'));
    }

    protected function buildSheetProduksi(Spreadsheet $spreadsheet, int $tahun, ?int $triwulan): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Produksi Peternakan');

        $sheet->setCellValue('A1', 'REKAPITULASI HASIL PRODUKSI PETERNAKAN 27 KECAMATAN');
        $sheet->setCellValue('A2', 'Tahun: ' . $tahun . ($triwulan ? " (Triwulan {$triwulan})" : ""));
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        $headers = ['No', 'Nama Kecamatan', 'Kode BPS', 'Produksi Daging (Kg)', 'Produksi Susu (Liter)', 'Produksi Telur (Kg/Butir)'];
        $sheet->fromArray($headers, null, 'A4');
        $this->styleTableHeader($sheet, 'A4:F4');

        $kecamatans = Kecamatan::orderBy('nama_kecamatan')->get();
        $r = 5;
        $no = 1;

        foreach ($kecamatans as $kec) {
            $desaIds = $kec->desa()->pluck('id');

            $daging = (float) DB::table('produksi_ternak')
                ->join('kategori_produksi', 'kategori_produksi.id', '=', 'produksi_ternak.kategori_produksi_id')
                ->whereIn('desa_id', $desaIds)
                ->where('kategori_produksi.nama', 'Daging')
                ->where('tahun', $tahun)
                ->sum('jumlah_produksi');

            $susu = (float) DB::table('produksi_ternak')
                ->join('kategori_produksi', 'kategori_produksi.id', '=', 'produksi_ternak.kategori_produksi_id')
                ->whereIn('desa_id', $desaIds)
                ->where('kategori_produksi.nama', 'Susu')
                ->where('tahun', $tahun)
                ->sum('jumlah_produksi');

            $telur = (float) DB::table('produksi_ternak')
                ->join('kategori_produksi', 'kategori_produksi.id', '=', 'produksi_ternak.kategori_produksi_id')
                ->whereIn('desa_id', $desaIds)
                ->where('kategori_produksi.nama', 'Telur')
                ->where('tahun', $tahun)
                ->sum('jumlah_produksi');

            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $kec->nama_kecamatan);
            $sheet->setCellValue("C{$r}", $kec->kode_kecamatan);
            $sheet->setCellValue("D{$r}", round($daging, 2));
            $sheet->setCellValue("E{$r}", round($susu, 2));
            $sheet->setCellValue("F{$r}", round($telur, 2));
            $r++;
        }

        $sheet->setCellValue("B{$r}", 'TOTAL BANYUMAS');
        for ($col = 'D'; $col <= 'F'; $col++) {
            $sheet->setCellValue("{$col}{$r}", "=SUM({$col}5:{$col}" . ($r - 1) . ")");
        }
        $sheet->getStyle("A{$r}:F{$r}")->getFont()->setBold(true);

        $this->styleTableBorder($sheet, "A4:F{$r}");
        $this->autoFitColumns($sheet, range('A', 'F'));
    }

    protected function buildSheetKesehatan(Spreadsheet $spreadsheet, int $tahun, ?int $triwulan): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Kesehatan Hewan & EWS');

        $sheet->setCellValue('A1', 'REKAPITULASI SURVEILANS KESEHATAN HEWAN & STATUS ZONASI EWS');
        $sheet->setCellValue('A2', 'Tahun: ' . $tahun . ($triwulan ? " (Triwulan {$triwulan})" : ""));
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        $headers = ['No', 'Nama Kecamatan', 'Kode BPS', 'Kasus PMK (Kasus)', 'Kasus LSD (Kasus)', 'Total Kasus', 'Kasus Aktif', 'Status Zonasi EWS'];
        $sheet->fromArray($headers, null, 'A4');
        $this->styleTableHeader($sheet, 'A4:H4');

        $rows = DB::table('v_kugi_administrasi_ar_penyakithewan as k')
            ->leftJoin('zona_snapshot_kecamatan as z', function ($join) use ($tahun, $triwulan) {
                $join->on('z.kecamatan_id', '=', 'k.objectid')
                     ->where('z.tahun', '=', $tahun);
                if ($triwulan !== null) {
                    $join->where('z.triwulan', '=', $triwulan);
                }
            })
            ->select('k.wadmkc', 'k.kdcpum', 'k.kasus_pmk', 'k.kasus_lsd', 'k.kasus_total', 'k.kasus_aktif', 'z.status_zona')
            ->orderBy('k.wadmkc')
            ->get();

        $r = 5;
        $no = 1;
        foreach ($rows as $item) {
            $status = $item->status_zona ?? ($item->kasus_aktif > 5 ? 'MERAH' : ($item->kasus_aktif > 0 ? 'KUNING' : 'HIJAU'));
            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $item->wadmkc);
            $sheet->setCellValue("C{$r}", $item->kdcpum);
            $sheet->setCellValue("D{$r}", $item->kasus_pmk);
            $sheet->setCellValue("E{$r}", $item->kasus_lsd);
            $sheet->setCellValue("F{$r}", $item->kasus_total);
            $sheet->setCellValue("G{$r}", $item->kasus_aktif);
            $sheet->setCellValue("H{$r}", $status);
            $r++;
        }

        $sheet->setCellValue("B{$r}", 'TOTAL BANYUMAS');
        for ($col = 'D'; $col <= 'G'; $col++) {
            $sheet->setCellValue("{$col}{$r}", "=SUM({$col}5:{$col}" . ($r - 1) . ")");
        }
        $sheet->getStyle("A{$r}:H{$r}")->getFont()->setBold(true);

        $this->styleTableBorder($sheet, "A4:H{$r}");
        $this->autoFitColumns($sheet, range('A', 'H'));
    }

    protected function buildSheetRph(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Sebaran Fasilitas RPH');

        $sheet->setCellValue('A1', 'DAFTAR RUMAH POTONG HEWAN (RPH) KABUPATEN BANYUMAS');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $headers = ['No', 'Nama RPH', 'Kecamatan', 'Alamat Lengkap', 'Jenis Pengelola', 'Hewan Potong', 'Kapasitas (Ekor/Hari)', 'Status Operasional', 'Kontak'];
        $sheet->fromArray($headers, null, 'A3');
        $this->styleTableHeader($sheet, 'A3:I3');

        $rphs = FasilitasRph::with('kecamatan')->orderBy('nama_rph')->get();
        $r = 4;
        $no = 1;
        foreach ($rphs as $rph) {
            $sheet->setCellValue("A{$r}", $no++);
            $sheet->setCellValue("B{$r}", $rph->nama_rph);
            $sheet->setCellValue("C{$r}", $rph->kecamatan->nama_kecamatan ?? '-');
            $sheet->setCellValue("D{$r}", $rph->alamat_lengkap);
            $sheet->setCellValue("E{$r}", $rph->jenis_pengelola);
            $sheet->setCellValue("F{$r}", $rph->jenis_hewan_potong);
            $sheet->setCellValue("G{$r}", $rph->kapasitas_potong_ekor_hari);
            $sheet->setCellValue("H{$r}", $rph->status_operasional);
            $sheet->setCellValue("I{$r}", $rph->nomor_kontak ?? '-');
            $r++;
        }

        $this->styleTableBorder($sheet, "A3:I" . ($r - 1));
        $this->autoFitColumns($sheet, range('A', 'I'));
    }

    // -------------------------------------------------------------
    // TEMPLATE PDF
    // -------------------------------------------------------------

    protected function renderPdfTemplate(int $tahun, string $periode, array $metrics, $kecData): string
    {
        $tableRows = '';
        $no = 1;
        foreach ($kecData as $row) {
            $status = (int) $row->kasus_aktif > 5 ? 'MERAH' : ((int) $row->kasus_aktif > 0 ? 'KUNING' : 'HIJAU');
            $statusBg = match ($status) {
                'MERAH' => '#FF4D4D',
                'KUNING' => '#FFD166',
                default => '#06D6A0',
            };

            $tableRows .= "
            <tr>
                <td style='text-align: center;'>{$no}</td>
                <td><strong>{$row->wadmkc}</strong></td>
                <td style='text-align: center;'>{$row->kdcpum}</td>
                <td style='text-align: right;'>" . number_format($row->pop_sapipotong) . "</td>
                <td style='text-align: right;'>" . number_format($row->pop_sapiperah) . "</td>
                <td style='text-align: right;'>" . number_format($row->pop_kambing) . "</td>
                <td style='text-align: right;'>" . number_format($row->pop_ayampotong) . "</td>
                <td style='text-align: right; font-weight: bold;'>" . number_format($row->pop_total) . "</td>
                <td style='text-align: center; color: #D00;'>" . $row->kasus_pmk . "</td>
                <td style='text-align: center; color: #D00;'>" . $row->kasus_lsd . "</td>
                <td style='text-align: center; font-weight: bold;'>" . $row->kasus_aktif . "</td>
                <td style='text-align: center; background-color: {$statusBg}; font-weight: bold; color: #000;'>{$status}</td>
            </tr>";
            $no++;
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Statistik Peternakan Banyumas</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9pt; color: #222; margin: 0; padding: 10px; }
        .kop-table { width: 100%; border-bottom: 3px double #000; margin-bottom: 12px; padding-bottom: 6px; }
        .kop-table td { vertical-align: middle; }
        .instansi-header { text-align: center; }
        .instansi-header h3 { margin: 0; font-size: 13pt; text-transform: uppercase; letter-spacing: 1px; }
        .instansi-header h2 { margin: 2px 0; font-size: 15pt; text-transform: uppercase; }
        .instansi-header p { margin: 0; font-size: 8pt; color: #444; }
        .judul-laporan { text-align: center; margin: 10px 0; }
        .judul-laporan h4 { margin: 0; font-size: 11pt; text-decoration: underline; text-transform: uppercase; }
        .judul-laporan span { font-size: 9pt; color: #555; }
        
        .kpi-container { width: 100%; margin-bottom: 12px; }
        .kpi-box { width: 24%; border: 1px solid #999; padding: 6px; display: inline-block; vertical-align: top; background: #F8F9FA; border-radius: 4px; box-sizing: border-box; }
        .kpi-title { font-size: 7.5pt; color: #555; text-transform: uppercase; font-weight: bold; }
        .kpi-val { font-size: 13pt; font-weight: bold; color: #0E7490; margin-top: 2px; }

        table.data-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 8pt; }
        table.data-table th, table.data-table td { border: 1px solid #777; padding: 4px 6px; }
        table.data-table th { background-color: #0E7490; color: #FFF; text-transform: uppercase; font-size: 7.5pt; }

        .ttd-table { width: 100%; margin-top: 20px; border: none; font-size: 9pt; page-break-inside: avoid; }
        .ttd-table td { border: none; }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td class="instansi-header">
                <h3>Pemerintah Kabupaten Banyumas</h3>
                <h2>Dinas Pertanian dan Ketahanan Pangan</h2>
                <p>Bidang Peternakan, Kesehatan Hewan dan Kesmavet</p>
                <p>Jl. Prof. Dr. Soeharso No. 45 Purwokerto 53114 | Telp: (0281) 684123 | dinpertankp@banyumaskab.go.id</p>
            </td>
        </tr>
    </table>

    <div class="judul-laporan">
        <h4>Laporan Rekapitulasi Statistik Peternakan dan Peringatan Dini Kesehatan Hewan</h4>
        <span>Periode Pelaporan: {$periode}</span>
    </div>

    <div class="kpi-container">
        <div class="kpi-box">
            <div class="kpi-title">Ternak Besar (Sapi)</div>
            <div class="kpi-val">{$metrics['kpi']['populasi']['ternak_besar']} Ekor</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Ternak Kecil (Kambing/Domba)</div>
            <div class="kpi-val">{$metrics['kpi']['populasi']['ternak_kecil']} Ekor</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Produksi Daging</div>
            <div class="kpi-val">{$metrics['kpi']['produksi']['daging_kg']} Kg</div>
        </div>
        <div class="kpi-box">
            <div class="kpi-title">Kasus Aktif PMK & LSD</div>
            <div class="kpi-val" style="color: #D00;">{$metrics['kpi']['kesehatan_hewan']['total_kasus_aktif']} Kasus</div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2">No</th>
                <th rowspan="2">Kecamatan</th>
                <th rowspan="2">Kode</th>
                <th colspan="5">Populasi Ternak (Ekor)</th>
                <th colspan="3">Kesehatan Hewan (Kasus)</th>
                <th rowspan="2">Status EWS</th>
            </tr>
            <tr>
                <th>Sapi Potong</th>
                <th>Sapi Perah</th>
                <th>Kambing</th>
                <th>Ayam Potong</th>
                <th>Total</th>
                <th>PMK</th>
                <th>LSD</th>
                <th>Aktif</th>
            </tr>
        </thead>
        <tbody>
            {$tableRows}
        </tbody>
    </table>

    <table class="ttd-table">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%; text-align: center;">
                Purwokerto, {$periode}<br>
                <strong>Kepala Bidang Peternakan, Keswan dan Kesmavet</strong><br>
                Dinas Pertanian Kab. Banyumas<br><br><br><br><br>
                <strong><u>Kusbiono Jumadi Rahman, S.Pt., M.Pt.</u></strong><br>
                NIP. 19780505 201001 1 021
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    // -------------------------------------------------------------
    // HELPERS FORMAT STYLING
    // -------------------------------------------------------------

    protected function styleTableHeader($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0E7490']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
    }

    protected function styleTableBorder($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
            ],
        ]);
    }

    protected function autoFitColumns($sheet, array $columns): void
    {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }
}
