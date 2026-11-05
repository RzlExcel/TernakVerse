<script setup lang="ts">
import { computed } from 'vue';
import { FileDown, FileText, Compass, Layers, Download } from 'lucide-vue-next';
import { KECAMATAN_LIST, type KecamatanData } from '../../../data/banyumasData';

const props = defineProps<{
    kecamatanList?: KecamatanData[];
}>();

const emit = defineEmits<{
    (e: 'showToast', msg: string): void;
}>();

const dataKecamatan = computed<KecamatanData[]>(() => {
    return props.kecamatanList && props.kecamatanList.length > 0
        ? props.kecamatanList
        : KECAMATAN_LIST;
});

function triggerDownload(content: string, filename: string, mimeType: string): void {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function escapeCsv(val: unknown): string {
    if (val === null || val === undefined) return '';
    let str = String(val);
    if (/^[=+\-@\t\r]/.test(str)) {
        str = `'${str}`;
    }
    if (str.includes(',') || str.includes('"') || str.includes('\n') || str.includes('\r')) {
        return `"${str.replace(/"/g, '""')}"`;
    }
    return str;
}

// --------------------------------------------------------
// 1. GENERATOR EXCEL MULTI-SECTION (.CSV)
// --------------------------------------------------------
function downloadMultiSheetExcel(): void {
    const data = dataKecamatan.value;
    const now = new Date();
    const dateStr = now.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });

    const totalSapiPotong = data.reduce((acc, k) => acc + (k.sapi_potong || 0), 0);
    const totalSapiPerah = data.reduce((acc, k) => acc + (k.sapi_perah || 0), 0);
    const totalKambing = data.reduce((acc, k) => acc + (k.kambing || 0), 0);
    const totalDomba = data.reduce((acc, k) => acc + (k.domba || 0), 0);
    const totalBroiler = data.reduce((acc, k) => acc + (k.ayam_broiler ?? k.ayam_potong ?? 0), 0);
    const totalLayer = data.reduce((acc, k) => acc + (k.ayam_petelur || 0), 0);
    const totalDagingSapi = data.reduce((acc, k) => acc + (k.daging_sapi || 0), 0);
    const totalDagingKambing = data.reduce((acc, k) => acc + (k.daging_kambing || 0), 0);
    const totalTelur = data.reduce((acc, k) => acc + (k.telur_ayam || 0), 0);
    const totalSusu = data.reduce((acc, k) => acc + (k.susu_liter ?? k.susu_sapi ?? 0), 0);
    const totalPmk = data.reduce((acc, k) => acc + (k.pmk || 0), 0);
    const totalLsd = data.reduce((acc, k) => acc + (k.lsd || 0), 0);
    const totalPoskeswan = data.reduce((acc, k) => acc + (k.poskeswan || 0), 0);

    const sections: string[] = [];

    // SEKSI 1: RINGKASAN EKSEKUTIF
    sections.push([
        '# ========================================================================================',
        '# SEKSI 1: RINGKASAN EKSEKUTIF SEKTOR PETERNAKAN BANYUMAS 2026',
        '# ========================================================================================',
        'Parameter Indikator,Nilai Agregat,Satuan,Keterangan Operasional',
        `Cakupan Wilayah Data,${data.length},Kecamatan,Kabupaten Banyumas (100% Terpetakan)`,
        `Total Sapi Potong,${totalSapiPotong},Ekor,Sentra Sumbang & Cilongok`,
        `Total Sapi Perah,${totalSapiPerah},Ekor,Kawasan Agrowisata Baturraden`,
        `Total Kambing,${totalKambing},Ekor,Populasi Peternakan Rakyat`,
        `Total Domba,${totalDomba},Ekor,Populasi Sentra Baturraden & Sumbang`,
        `Total Ayam Broiler,${totalBroiler},Ekor,Peternakan Pedaging Kemitraan`,
        `Total Ayam Petelur,${totalLayer},Ekor,Produksi Telur Konsumsi`,
        `Produksi Daging Sapi Tahunan,${totalDagingSapi},Kg/Tahun,Pasokan RPH & Jagal Rakyat`,
        `Produksi Daging Kambing Tahunan,${totalDagingKambing},Kg/Tahun,Pasokan Konsumsi Lokal`,
        `Produksi Telur Ayam Tahunan,${totalTelur},Kg/Tahun,Pasokan Pasar Tradisional & Modern`,
        `Produksi Susu Sapi Segar,${totalSusu},Liter/Tahun,Kemitraan Koperasi Susu Banyumas`,
        `Kasus Aktif PMK Terpantau,${totalPmk},Kasus,Surveilans Medik Veteriner`,
        `Kasus Aktif LSD Terpantau,${totalLsd},Kasus,Pengawasan Vaksinasi Aktif`,
        `Unit Poskeswan Beroperasi,${totalPoskeswan},Unit,Pos Kesehatan Hewan Bersiaga`,
        `Waktu Sinkronisasi Dokumen,${escapeCsv(dateStr)},WIB,Dinas Pertanian dan Ketahanan Pangan Banyumas`,
    ].join('\r\n'));

    // SEKSI 2: POPULASI TERNAK 27 KECAMATAN
    const sec2Header = 'No,Kode Wilayah,Nama Kecamatan,Sapi Potong (Ekor),Sapi Perah (Ekor),Kambing (Ekor),Domba (Ekor),Ayam Broiler (Ekor),Ayam Petelur (Ekor),Total Populasi (Ekor)';
    const sec2Rows = data.map((k, idx) => {
        const broiler = k.ayam_broiler ?? k.ayam_potong ?? 0;
        const total = (k.sapi_potong || 0) + (k.sapi_perah || 0) + (k.kambing || 0) + (k.domba || 0) + broiler + (k.ayam_petelur || 0);
        return [
            idx + 1,
            escapeCsv(k.kode_kec),
            escapeCsv(k.nama),
            k.sapi_potong || 0,
            k.sapi_perah || 0,
            k.kambing || 0,
            k.domba || 0,
            broiler,
            k.ayam_petelur || 0,
            total,
        ].join(',');
    });
    sections.push([
        '',
        '# ========================================================================================',
        '# SEKSI 2: POPULASI TERNAK 27 KECAMATAN KABUPATEN BANYUMAS',
        '# ========================================================================================',
        sec2Header,
        ...sec2Rows,
    ].join('\r\n'));

    // SEKSI 3: HASIL PRODUKSI
    const sec3Header = 'No,Kode Wilayah,Nama Kecamatan,Daging Sapi (Kg),Daging Kambing (Kg),Daging Domba (Kg),Telur Ayam (Kg),Susu Sapi (Liter)';
    const sec3Rows = data.map((k, idx) => {
        return [
            idx + 1,
            escapeCsv(k.kode_kec),
            escapeCsv(k.nama),
            k.daging_sapi || 0,
            k.daging_kambing || 0,
            k.daging_domba || 0,
            k.telur_ayam || 0,
            k.susu_liter ?? k.susu_sapi ?? 0,
        ].join(',');
    });
    sections.push([
        '',
        '# ========================================================================================',
        '# SEKSI 3: HASIL PRODUKSI PETERNAKAN (DAGING, TELUR & SUSU)',
        '# ========================================================================================',
        sec3Header,
        ...sec3Rows,
    ].join('\r\n'));

    // SEKSI 4: SURVEILANS WABAH PMK & LSD
    const sec4Header = 'No,Kode Wilayah,Nama Kecamatan,Kasus Aktif PMK,Kasus Aktif LSD,Total Kasus,Status Zonasi EWS';
    const sec4Rows = data.map((k, idx) => {
        const totalWabah = (k.pmk || 0) + (k.lsd || 0);
        return [
            idx + 1,
            escapeCsv(k.kode_kec),
            escapeCsv(k.nama),
            k.pmk || 0,
            k.lsd || 0,
            totalWabah,
            escapeCsv(k.zona.toUpperCase()),
        ].join(',');
    });
    sections.push([
        '',
        '# ========================================================================================',
        '# SEKSI 4: SURVEILANS WABAH PENYAKIT HEWAN (PMK & LSD)',
        '# ========================================================================================',
        sec4Header,
        ...sec4Rows,
    ].join('\r\n'));

    // SEKSI 5: SEBARAN FASILITAS POSKESWAN & RPH
    const sec5Header = 'No,Kode Wilayah,Nama Kecamatan,Unit Poskeswan,Kesiapsiagaan Poskeswan,Fasilitas RPH Terdekat';
    const sec5Rows = data.map((k, idx) => {
        const statusPoskeswan = (k.poskeswan || 0) > 0 ? 'Tersedia Poskeswan Aktif' : 'Terhubung Poskeswan Rayon';
        const rphNearby = ['Purwokerto Timur', 'Sokaraja', 'Ajibarang', 'Wangon'].includes(k.nama)
            ? 'RPH Pemerintah Kabupaten Terakreditasi'
            : 'RPH Rayon Terdekat';
        return [
            idx + 1,
            escapeCsv(k.kode_kec),
            escapeCsv(k.nama),
            k.poskeswan || 0,
            escapeCsv(statusPoskeswan),
            escapeCsv(rphNearby),
        ].join(',');
    });
    sections.push([
        '',
        '# ========================================================================================',
        '# SEKSI 5: SEBARAN FASILITAS POSKESWAN & RUMAH POTONG HEWAN (RPH)',
        '# ========================================================================================',
        sec5Header,
        ...sec5Rows,
    ].join('\r\n'));

    const content = '\uFEFF' + sections.join('\r\n');
    const filename = 'Laporan_Peternakan_Banyumas_MultiSheet_2026.csv';
    triggerDownload(content, filename, 'text/csv;charset=utf-8');
    emit('showToast', `Berhasil mengunduh ${filename}`);
}

// --------------------------------------------------------
// 2. GENERATOR RINGKASAN EKSEKUTIF (PDF RESMI)
// --------------------------------------------------------
function downloadRingkasanEksekutif(): void {
    const tahun = 2026;
    emit('showToast', 'Mengunduh Laporan Resmi Eksekutif PDF...');
    window.location.href = `/internal/laporan/ekspor-pdf?tahun=${tahun}`;
}

// --------------------------------------------------------
// 3. GENERATOR METADATA GEOSPASIAL ISO 19115 (XML)
// --------------------------------------------------------
function downloadMetadataXml(): void {
    const filename = 'ID-DINPERTAN-BMS-TERNAKVERSE-2026.xml';
    const content = `<?xml version="1.0" encoding="UTF-8"?>
<gmd:MD_Metadata xmlns:gmd="http://www.isotc211.org/2005/gmd"
                 xmlns:gco="http://www.isotc211.org/2005/gco"
                 xmlns:gml="http://www.opengis.net/gml/3.2"
                 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                 xsi:schemaLocation="http://www.isotc211.org/2005/gmd http://schemas.opengis.net/iso/19139/20070417/gmd/gmd.xsd">
    <gmd:fileIdentifier>
        <gco:CharacterString>ID-DINPERTAN-BMS-TERNAKVERSE-2026</gco:CharacterString>
    </gmd:fileIdentifier>
    <gmd:language>
        <gco:CharacterString>ind</gco:CharacterString>
    </gmd:language>
    <gmd:characterSet>
        <gmd:MD_CharacterSetCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_CharacterSetCode" codeListValue="utf8">utf8</gmd:MD_CharacterSetCode>
    </gmd:characterSet>
    <gmd:hierarchyLevel>
        <gmd:MD_ScopeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_ScopeCode" codeListValue="dataset">dataset</gmd:MD_ScopeCode>
    </gmd:hierarchyLevel>
    <gmd:contact>
        <gmd:CI_ResponsibleParty>
            <gmd:organisationName>
                <gco:CharacterString>Dinas Pertanian dan Ketahanan Pangan Kabupaten Banyumas</gco:CharacterString>
            </gmd:organisationName>
            <gmd:positionName>
                <gco:CharacterString>Administrator Data Spasial Peternakan</gco:CharacterString>
            </gmd:positionName>
            <gmd:contactInfo>
                <gmd:CI_Contact>
                    <gmd:address>
                        <gmd:CI_Address>
                            <gmd:deliveryPoint>
                                <gco:CharacterString>Jl. Prof. Dr. Suharso No. 45</gco:CharacterString>
                            </gmd:deliveryPoint>
                            <gmd:city>
                                <gco:CharacterString>Purwokerto</gco:CharacterString>
                            </gmd:city>
                            <gmd:administrativeArea>
                                <gco:CharacterString>Jawa Tengah</gco:CharacterString>
                            </gmd:administrativeArea>
                            <gmd:postalCode>
                                <gco:CharacterString>53114</gco:CharacterString>
                            </gmd:postalCode>
                            <gmd:electronicMailAddress>
                                <gco:CharacterString>dinpertan@banyumaskab.go.id</gco:CharacterString>
                            </gmd:electronicMailAddress>
                        </gmd:CI_Address>
                    </gmd:address>
                </gmd:CI_Contact>
            </gmd:contactInfo>
            <gmd:role>
                <gmd:CI_RoleCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_RoleCode" codeListValue="custodian">custodian</gmd:CI_RoleCode>
            </gmd:role>
        </gmd:CI_ResponsibleParty>
    </gmd:contact>
    <gmd:dateStamp>
        <gco:Date>2026-06-30</gco:Date>
    </gmd:dateStamp>
    <gmd:metadataStandardName>
        <gco:CharacterString>ISO 19115 / KUGI BIG SNI 8489:2018</gco:CharacterString>
    </gmd:metadataStandardName>
    <gmd:metadataStandardVersion>
        <gco:CharacterString>2018</gco:CharacterString>
    </gmd:metadataStandardVersion>
    <gmd:identificationInfo>
        <gmd:MD_DataIdentification>
            <gmd:citation>
                <gmd:CI_Citation>
                    <gmd:title>
                        <gco:CharacterString>Peta Tematik Sebaran Populasi Peternakan dan Zonasi EWS Kabupaten Banyumas 2026</gco:CharacterString>
                    </gmd:title>
                    <gmd:date>
                        <gmd:CI_Date>
                            <gmd:date>
                                <gco:Date>2026-06-01</gco:Date>
                            </gmd:date>
                            <gmd:dateType>
                                <gmd:CI_DateTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#CI_DateTypeCode" codeListValue="publication">publication</gmd:CI_DateTypeCode>
                            </gmd:dateType>
                        </gmd:CI_Date>
                    </gmd:date>
                </gmd:CI_Citation>
            </gmd:citation>
            <gmd:abstract>
                <gco:CharacterString>Dataset geospasial tematik peternakan dan kesehatan hewan Kabupaten Banyumas memuat 27 kecamatan, mencakup sebaran populasi sapi potong, sapi perah, kambing, domba, unggas, data produksi daging/susu/telur, surveilans wabah PMK dan LSD, serta lokasi fasilitas Poskeswan dan RPH.</gco:CharacterString>
            </gmd:abstract>
            <gmd:spatialRepresentationType>
                <gmd:MD_SpatialRepresentationTypeCode codeList="http://standards.iso.org/iso/19139/resources/gmxCodelists.xml#MD_SpatialRepresentationTypeCode" codeListValue="vector">vector</gmd:MD_SpatialRepresentationTypeCode>
            </gmd:spatialRepresentationType>
            <gmd:spatialResolution>
                <gmd:MD_Resolution>
                    <gmd:equivalentScale>
                        <gmd:MD_RepresentativeFraction>
                            <gmd:denominator>
                                <gco:Integer>50000</gco:Integer>
                            </gmd:denominator>
                        </gmd:MD_RepresentativeFraction>
                    </gmd:equivalentScale>
                </gmd:MD_Resolution>
            </gmd:spatialResolution>
            <gmd:language>
                <gco:CharacterString>ind</gco:CharacterString>
            </gmd:language>
            <gmd:extent>
                <gmd:EX_Extent>
                    <gmd:geographicElement>
                        <gmd:EX_GeographicBoundingBox>
                            <gmd:westBoundLongitude>
                                <gco:Decimal>108.98</gco:Decimal>
                            </gmd:westBoundLongitude>
                            <gmd:eastBoundLongitude>
                                <gco:Decimal>109.38</gco:Decimal>
                            </gmd:eastBoundLongitude>
                            <gmd:southBoundLatitude>
                                <gco:Decimal>-7.65</gco:Decimal>
                            </gmd:southBoundLatitude>
                            <gmd:northBoundLatitude>
                                <gco:Decimal>-7.25</gco:Decimal>
                            </gmd:northBoundLatitude>
                        </gmd:EX_GeographicBoundingBox>
                    </gmd:geographicElement>
                </gmd:EX_Extent>
            </gmd:extent>
        </gmd:MD_DataIdentification>
    </gmd:identificationInfo>
</gmd:MD_Metadata>`;

    triggerDownload(content, filename, 'application/xml;charset=utf-8');
    emit('showToast', `Berhasil mengunduh ${filename}`);
}

// --------------------------------------------------------
// 4. GENERATOR PAKET SPASIAL KUGI (GEOJSON)
// --------------------------------------------------------
function downloadGeoJsonKugi(): void {
    const data = dataKecamatan.value;
    const filename = 'BANYUMAS_PETERNAKAN_KUGI_GEOJSON.json';

    const geojson = {
        type: 'FeatureCollection',
        name: 'BANYUMAS_PETERNAKAN_KUGI_2026',
        crs: {
            type: 'name',
            properties: {
                name: 'urn:ogc:def:crs:OGC:1.3:CRS84',
            },
        },
        features: data.map((k) => ({
            type: 'Feature',
            properties: {
                id: k.id,
                kode_kec: k.kode_kec,
                nama: k.nama,
                sapi_potong: k.sapi_potong || 0,
                sapi_perah: k.sapi_perah || 0,
                kambing: k.kambing || 0,
                domba: k.domba || 0,
                ayam_potong: k.ayam_potong || 0,
                ayam_broiler: k.ayam_broiler ?? k.ayam_potong ?? 0,
                ayam_petelur: k.ayam_petelur || 0,
                daging_sapi: k.daging_sapi || 0,
                daging_kambing: k.daging_kambing || 0,
                telur_ayam: k.telur_ayam || 0,
                susu_sapi: k.susu_sapi || 0,
                susu_liter: k.susu_liter ?? k.susu_sapi ?? 0,
                pmk: k.pmk || 0,
                lsd: k.lsd || 0,
                poskeswan: k.poskeswan || 0,
                zona: k.zona,
                last_updated: k.last_updated,
            },
            geometry: {
                type: 'Point',
                coordinates: [k.lng, k.lat],
            },
        })),
    };

    const content = JSON.stringify(geojson, null, 2);
    triggerDownload(content, filename, 'application/geo+json;charset=utf-8');
    emit('showToast', `Berhasil mengunduh ${filename}`);
}
</script>

<template>
    <div class="w-full space-y-6">
        <div>
            <h1 class="font-display font-extrabold text-2xl md:text-3xl tracking-tight text-[#171B17] dark:text-[#F5F5F5]">
                Generator Laporan &amp; Interoperabilitas Metadata
            </h1>
            <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] mt-1">
                Ekspor berkas terstandarisasi untuk Geoportal Banyumas dan Laporan Triwulanan
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 w-full">
            <!-- Kartu 1: Laporan Excel Multi-Sheet -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-emerald-500/40 dark:hover:border-[#00D084]/40 transition-colors shadow-sm">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-[#00D084]">
                    <FileDown class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Buku Kerja Excel Multi-Sheet (.xlsx / .csv)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Berkas lembar kerja terstruktur memuat <span class="font-mono tabular-nums text-[#171B17] dark:text-[#F5F5F5] font-semibold">5</span> seksi terpisah: Ringkasan Eksekutif, Populasi Ternak, Hasil Produksi, Surveilans PMK &amp; LSD, dan Sebaran RPH <span class="font-mono tabular-nums text-[#171B17] dark:text-[#F5F5F5] font-semibold">27</span> kecamatan.
                </p>
                <button
                    type="button"
                    @click="downloadMultiSheetExcel"
                    class="w-full h-10 rounded-lg bg-[#171B17] dark:bg-[#00D084] hover:bg-[#2A2E2A] dark:hover:bg-[#00B98B] text-white dark:text-[#090909] text-xs font-bold flex items-center justify-center gap-2 transition-colors cursor-pointer shadow-xs"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Excel Multi-Sheet</span>
                </button>
            </div>

            <!-- Kartu 2: Dokumen PDF Resmi -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-emerald-500/40 dark:hover:border-[#00D084]/40 transition-colors shadow-sm">
                <div class="flex items-center gap-2.5 text-teal-600 dark:text-teal-400">
                    <FileText class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Laporan Ringkasan Eksekutif (PDF / Dokumen)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Dokumen resmi berstempel dinas format PDF (Landscape A4) memuat ringkasan eksekutif, indikator utama, analisis zonasi, dan lembar pengesahan.
                </p>
                <button
                    type="button"
                    @click="downloadRingkasanEksekutif"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-teal-500/30 hover:border-teal-500 text-teal-700 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Laporan Resmi Eksekutif (PDF)</span>
                </button>
            </div>

            <!-- Kartu 3: Metadata ISO 19115 XML -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-emerald-500/40 dark:hover:border-[#00D084]/40 transition-colors shadow-sm">
                <div class="flex items-center gap-2.5 text-amber-600 dark:text-amber-400">
                    <Compass class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Metadata Geospasial ISO 19115 (XML)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Format XML resmi KAK Bagian 4.d untuk interoperabilitas panen katalog (harvesting) Geoportal Kabupaten Banyumas.
                </p>
                <button
                    type="button"
                    @click="downloadMetadataXml"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-amber-500/30 hover:border-amber-500 text-amber-700 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Metadata XML</span>
                </button>
            </div>

            <!-- Kartu 4: Shapefile KUGI (.zip / GeoJSON) -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-6 space-y-3 hover:border-emerald-500/40 dark:hover:border-[#00D084]/40 transition-colors shadow-sm">
                <div class="flex items-center gap-2.5 text-emerald-600 dark:text-[#00D084]">
                    <Layers class="w-5 h-5" />
                    <h3 class="font-display font-bold text-base text-[#171B17] dark:text-[#F5F5F5]">Paket Geospasial KUGI (GeoJSON)</h3>
                </div>
                <p class="text-xs text-[#6E776D] dark:text-[#B3B3B3] leading-relaxed">
                    Unsur ADMINISTRASI_AR_PETERNAKAN lengkap atribut 27 kecamatan proyeksi SRGI2013-WGS84 standar KUGI BIG SNI.
                </p>
                <button
                    type="button"
                    @click="downloadGeoJsonKugi"
                    class="w-full h-10 rounded-lg bg-[#F4F5F2] dark:bg-[#181818] border border-emerald-500/30 hover:border-[#00D084] text-emerald-700 dark:text-[#00D084] hover:text-emerald-800 dark:hover:text-emerald-300 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
                >
                    <Download class="w-4 h-4" />
                    <span>Unduh Paket GeoJSON KUGI</span>
                </button>
            </div>
        </div>
    </div>
</template>
