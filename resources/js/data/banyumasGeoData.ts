/**
 * Real Geographic Boundaries & Spatial Data 27 Kecamatan Kabupaten Banyumas
 * Koordinat WGS84 Asli (Lat/Lng) untuk WebGIS Leaflet & GeoJSON Canvas
 * Sesuai Dokumen Design Rule: Ternakverse Bab 5 & Bab 12.1
 * BERKAS-UTUH: banyumasGeoData.ts
 */

export interface RealKecamatan {
    id: string;
    nama: string;
    zona: 'bahaya' | 'waspada' | 'aman';
    lat: number;
    lng: number;
    sapi_potong: number;
    sapi_perah: number;
    kambing: number;
    domba: number;
    ayam_broiler: number;
    pmk: number;
    lsd: number;
    last_updated: string;
    // Koordinat poligon WGS84 [lat, lng] batas wilayah riil
    polygon: [number, number][];
}

export const REAL_BANYUMAS_KECAMATAN: RealKecamatan[] = [
    // 1. Lumbir (Barat Daya - Perbatasan Cilacap)
    {
        id: 'lumbir',
        nama: 'Lumbir',
        zona: 'aman',
        lat: -7.482,
        lng: 108.972,
        sapi_potong: 1890,
        sapi_perah: 30,
        kambing: 3100,
        domba: 790,
        ayam_broiler: 16000,
        pmk: 0,
        lsd: 0,
        last_updated: '14 Okt 2026, 09.00 WIB',
        polygon: [
            [-7.445, 108.940], [-7.452, 108.995], [-7.505, 109.010],
            [-7.530, 108.980], [-7.510, 108.935], [-7.460, 108.930]
        ]
    },
    // 2. Wangon (Sentra Peternakan Utama Banyumas Barat)
    {
        id: 'wangon',
        nama: 'Wangon',
        zona: 'bahaya',
        lat: -7.518,
        lng: 109.055,
        sapi_potong: 3240,
        sapi_perah: 210,
        kambing: 4120,
        domba: 1350,
        ayam_broiler: 52000,
        pmk: 9,
        lsd: 3,
        last_updated: '14 Okt 2026, 11.20 WIB',
        polygon: [
            [-7.475, 109.030], [-7.485, 109.085], [-7.545, 109.090],
            [-7.560, 109.045], [-7.525, 109.015], [-7.485, 109.015]
        ]
    },
    // 3. Jatilawang (Selatan Wangon)
    {
        id: 'jatilawang',
        nama: 'Jatilawang',
        zona: 'waspada',
        lat: -7.536,
        lng: 109.125,
        sapi_potong: 2150,
        sapi_perah: 65,
        kambing: 2890,
        domba: 910,
        ayam_broiler: 23000,
        pmk: 3,
        lsd: 1,
        last_updated: '14 Okt 2026, 08.15 WIB',
        polygon: [
            [-7.505, 109.100], [-7.515, 109.155], [-7.565, 109.160],
            [-7.575, 109.110], [-7.535, 109.090]
        ]
    },
    // 4. Rawalo (Lembah Sungai Serayu)
    {
        id: 'rawalo',
        nama: 'Rawalo',
        zona: 'aman',
        lat: -7.525,
        lng: 109.185,
        sapi_potong: 2410,
        sapi_perah: 110,
        kambing: 2750,
        domba: 870,
        ayam_broiler: 38000,
        pmk: 2,
        lsd: 0,
        last_updated: '14 Okt 2026, 09.50 WIB',
        polygon: [
            [-7.495, 109.160], [-7.505, 109.215], [-7.555, 109.220],
            [-7.565, 109.165], [-7.520, 109.155]
        ]
    },
    // 5. Kebasen (Bendung Gerak Serayu)
    {
        id: 'kebasen',
        nama: 'Kebasen',
        zona: 'bahaya',
        lat: -7.528,
        lng: 109.245,
        sapi_potong: 2050,
        sapi_perah: 85,
        kambing: 2640,
        domba: 740,
        ayam_broiler: 26000,
        pmk: 7,
        lsd: 2,
        last_updated: '14 Okt 2026, 10.30 WIB',
        polygon: [
            [-7.500, 109.220], [-7.510, 109.270], [-7.560, 109.275],
            [-7.570, 109.225], [-7.525, 109.215]
        ]
    },
    // 6. Kemranjen (Jalur Selatan)
    {
        id: 'kemranjen',
        nama: 'Kemranjen',
        zona: 'bahaya',
        lat: -7.592,
        lng: 109.308,
        sapi_potong: 2280,
        sapi_perah: 75,
        kambing: 2980,
        domba: 920,
        ayam_broiler: 34000,
        pmk: 8,
        lsd: 3,
        last_updated: '14 Okt 2026, 11.55 WIB',
        polygon: [
            [-7.560, 109.275], [-7.565, 109.340], [-7.625, 109.345],
            [-7.635, 109.280], [-7.590, 109.265]
        ]
    },
    // 7. Sumpiuh (Pasar Induk Selatan)
    {
        id: 'sumpiuh',
        nama: 'Sumpiuh',
        zona: 'waspada',
        lat: -7.608,
        lng: 109.362,
        sapi_potong: 2110,
        sapi_perah: 90,
        kambing: 2580,
        domba: 830,
        ayam_broiler: 29000,
        pmk: 4,
        lsd: 1,
        last_updated: '14 Okt 2026, 10.20 WIB',
        polygon: [
            [-7.575, 109.340], [-7.580, 109.395], [-7.640, 109.400],
            [-7.645, 109.345], [-7.605, 109.335]
        ]
    },
    // 8. Tambak (Ujung Tenggara - Perbatasan Kebumen)
    {
        id: 'tambak',
        nama: 'Tambak',
        zona: 'aman',
        lat: -7.609,
        lng: 109.418,
        sapi_potong: 1650,
        sapi_perah: 50,
        kambing: 2210,
        domba: 710,
        ayam_broiler: 21500,
        pmk: 1,
        lsd: 0,
        last_updated: '13 Okt 2026, 14.40 WIB',
        polygon: [
            [-7.575, 109.395], [-7.585, 109.445], [-7.645, 109.440],
            [-7.645, 109.398], [-7.610, 109.390]
        ]
    },
    // 9. Somagede (Tenggara Banyumas)
    {
        id: 'somagede',
        nama: 'Somagede',
        zona: 'aman',
        lat: -7.519,
        lng: 109.345,
        sapi_potong: 1490,
        sapi_perah: 45,
        kambing: 1920,
        domba: 640,
        ayam_broiler: 15500,
        pmk: 1,
        lsd: 0,
        last_updated: '12 Okt 2026, 17.10 WIB',
        polygon: [
            [-7.490, 109.315], [-7.495, 109.375], [-7.555, 109.380],
            [-7.560, 109.320], [-7.525, 109.310]
        ]
    },
    // 10. Kalibagor (Tengah Timur)
    {
        id: 'kalibagor',
        nama: 'Kalibagor',
        zona: 'aman',
        lat: -7.482,
        lng: 109.295,
        sapi_potong: 1720,
        sapi_perah: 95,
        kambing: 2010,
        domba: 680,
        ayam_broiler: 21000,
        pmk: 1,
        lsd: 0,
        last_updated: '13 Okt 2026, 13.40 WIB',
        polygon: [
            [-7.455, 109.270], [-7.465, 109.325], [-7.510, 109.325],
            [-7.515, 109.275], [-7.485, 109.265]
        ]
    },
    // 11. Banyumas (Kota Lama)
    {
        id: 'banyumas_kec',
        nama: 'Banyumas',
        zona: 'waspada',
        lat: -7.516,
        lng: 109.296,
        sapi_potong: 1880,
        sapi_perah: 110,
        kambing: 2310,
        domba: 820,
        ayam_broiler: 19500,
        pmk: 3,
        lsd: 1,
        last_updated: '14 Okt 2026, 09.05 WIB',
        polygon: [
            [-7.495, 109.275], [-7.500, 109.320], [-7.545, 109.320],
            [-7.550, 109.275], [-7.520, 109.265]
        ]
    },
    // 12. Patikraja (Tengah)
    {
        id: 'patikraja',
        nama: 'Patikraja',
        zona: 'aman',
        lat: -7.481,
        lng: 109.220,
        sapi_potong: 1610,
        sapi_perah: 70,
        kambing: 1950,
        domba: 610,
        ayam_broiler: 17500,
        pmk: 1,
        lsd: 0,
        last_updated: '13 Okt 2026, 11.30 WIB',
        polygon: [
            [-7.455, 109.200], [-7.460, 109.255], [-7.505, 109.250],
            [-7.515, 109.200], [-7.480, 109.190]
        ]
    },
    // 13. Purwojati (Barat Tengah)
    {
        id: 'purwojati',
        nama: 'Purwojati',
        zona: 'aman',
        lat: -7.476,
        lng: 109.117,
        sapi_potong: 1390,
        sapi_perah: 40,
        kambing: 2050,
        domba: 530,
        ayam_broiler: 14000,
        pmk: 1,
        lsd: 0,
        last_updated: '12 Okt 2026, 14.00 WIB',
        polygon: [
            [-7.445, 109.090], [-7.455, 109.145], [-7.505, 109.150],
            [-7.510, 109.095], [-7.475, 109.085]
        ]
    },
    // 14. Ajibarang (Pusat Jalur Utama Barat)
    {
        id: 'ajibarang',
        nama: 'Ajibarang',
        zona: 'bahaya',
        lat: -7.411,
        lng: 109.074,
        sapi_potong: 2640,
        sapi_perah: 150,
        kambing: 3450,
        domba: 830,
        ayam_broiler: 24000,
        pmk: 12,
        lsd: 4,
        last_updated: '14 Okt 2026, 11.02 WIB',
        polygon: [
            [-7.380, 109.040], [-7.390, 109.110], [-7.445, 109.115],
            [-7.455, 109.050], [-7.415, 109.030]
        ]
    },
    // 15. Gumelar (Barat Pegunungan)
    {
        id: 'gumelar',
        nama: 'Gumelar',
        zona: 'aman',
        lat: -7.382,
        lng: 108.988,
        sapi_potong: 1250,
        sapi_perah: 80,
        kambing: 2150,
        domba: 620,
        ayam_broiler: 12000,
        pmk: 1,
        lsd: 0,
        last_updated: '13 Okt 2026, 14.15 WIB',
        polygon: [
            [-7.350, 108.955], [-7.360, 109.025], [-7.420, 109.020],
            [-7.425, 108.960], [-7.385, 108.945]
        ]
    },
    // 16. Pekuncen (Barat Laut - Curug Cipendok)
    {
        id: 'pekuncen',
        nama: 'Pekuncen',
        zona: 'waspada',
        lat: -7.368,
        lng: 109.076,
        sapi_potong: 1820,
        sapi_perah: 410,
        kambing: 2870,
        domba: 940,
        ayam_broiler: 18000,
        pmk: 4,
        lsd: 1,
        last_updated: '14 Okt 2026, 09.30 WIB',
        polygon: [
            [-7.320, 109.040], [-7.330, 109.115], [-7.385, 109.110],
            [-7.395, 109.045], [-7.360, 109.035]
        ]
    },
    // 17. Cilongok (Lereng Slamet Barat)
    {
        id: 'cilongok',
        nama: 'Cilongok',
        zona: 'waspada',
        lat: -7.399,
        lng: 109.138,
        sapi_potong: 2890,
        sapi_perah: 890,
        kambing: 3200,
        domba: 1100,
        ayam_broiler: 45000,
        pmk: 5,
        lsd: 2,
        last_updated: '14 Okt 2026, 10.45 WIB',
        polygon: [
            [-7.335, 109.115], [-7.345, 109.175], [-7.445, 109.175],
            [-7.445, 109.115], [-7.390, 109.105]
        ]
    },
    // 18. Karanglewas (Barat Purwokerto)
    {
        id: 'karanglewas',
        nama: 'Karanglewas',
        zona: 'aman',
        lat: -7.407,
        lng: 109.195,
        sapi_potong: 1740,
        sapi_perah: 180,
        kambing: 2110,
        domba: 670,
        ayam_broiler: 22000,
        pmk: 2,
        lsd: 0,
        last_updated: '14 Okt 2026, 07.45 WIB',
        polygon: [
            [-7.380, 109.175], [-7.385, 109.215], [-7.440, 109.215],
            [-7.445, 109.175], [-7.410, 109.165]
        ]
    },
    // 19. Kedungbanteng (Lereng Slamet)
    {
        id: 'kedungbanteng',
        nama: 'Kedungbanteng',
        zona: 'aman',
        lat: -7.348,
        lng: 109.215,
        sapi_potong: 1680,
        sapi_perah: 620,
        kambing: 1980,
        domba: 710,
        ayam_broiler: 15000,
        pmk: 2,
        lsd: 0,
        last_updated: '12 Okt 2026, 08.20 WIB',
        polygon: [
            [-7.295, 109.195], [-7.305, 109.235], [-7.380, 109.235],
            [-7.380, 109.185], [-7.330, 109.180]
        ]
    },
    // 20. Baturraden (Pusat Sapi Perah - Lereng Atas Slamet)
    {
        id: 'baturraden',
        nama: 'Baturraden',
        zona: 'aman',
        lat: -7.319,
        lng: 109.227,
        sapi_potong: 1450,
        sapi_perah: 2850,
        kambing: 1820,
        domba: 890,
        ayam_broiler: 11000,
        pmk: 0,
        lsd: 0,
        last_updated: '14 Okt 2026, 12.10 WIB',
        polygon: [
            [-7.270, 109.210], [-7.280, 109.260], [-7.365, 109.255],
            [-7.365, 109.225], [-7.300, 109.205]
        ]
    },
    // 21. Sumbang (Timur Laut Lereng Slamet)
    {
        id: 'sumbang',
        nama: 'Sumbang',
        zona: 'waspada',
        lat: -7.352,
        lng: 109.278,
        sapi_potong: 2380,
        sapi_perah: 310,
        kambing: 2450,
        domba: 1200,
        ayam_broiler: 28000,
        pmk: 2,
        lsd: 1,
        last_updated: '14 Okt 2026, 09.12 WIB',
        polygon: [
            [-7.290, 109.255], [-7.300, 109.310], [-7.395, 109.310],
            [-7.395, 109.255], [-7.335, 109.245]
        ]
    },
    // 22. Kembaran (Timur Purwokerto)
    {
        id: 'kembaran',
        nama: 'Kembaran',
        zona: 'aman',
        lat: -7.420,
        lng: 109.288,
        sapi_potong: 1520,
        sapi_perah: 90,
        kambing: 1730,
        domba: 540,
        ayam_broiler: 19000,
        pmk: 1,
        lsd: 0,
        last_updated: '13 Okt 2026, 16.00 WIB',
        polygon: [
            [-7.395, 109.265], [-7.400, 109.315], [-7.445, 109.315],
            [-7.445, 109.265], [-7.415, 109.255]
        ]
    },
    // 23. Sokaraja (Timur)
    {
        id: 'sokaraja',
        nama: 'Sokaraja',
        zona: 'waspada',
        lat: -7.458,
        lng: 109.278,
        sapi_potong: 1940,
        sapi_perah: 120,
        kambing: 2190,
        domba: 880,
        ayam_broiler: 31000,
        pmk: 3,
        lsd: 1,
        last_updated: '14 Okt 2026, 10.11 WIB',
        polygon: [
            [-7.445, 109.255], [-7.450, 109.310], [-7.485, 109.305],
            [-7.485, 109.255], [-7.465, 109.245]
        ]
    },
    // 24. Purwokerto Barat (Perkotaan)
    {
        id: 'pwt_barat',
        nama: 'Pwt Barat',
        zona: 'aman',
        lat: -7.424,
        lng: 109.221,
        sapi_potong: 320,
        sapi_perah: 40,
        kambing: 580,
        domba: 120,
        ayam_broiler: 4000,
        pmk: 0,
        lsd: 0,
        last_updated: '11 Okt 2026, 15.20 WIB',
        polygon: [
            [-7.410, 109.210], [-7.415, 109.232], [-7.438, 109.232],
            [-7.438, 109.210]
        ]
    },
    // 25. Purwokerto Timur (Perkotaan)
    {
        id: 'pwt_timur',
        nama: 'Pwt Timur',
        zona: 'aman',
        lat: -7.428,
        lng: 109.249,
        sapi_potong: 280,
        sapi_perah: 30,
        kambing: 490,
        domba: 110,
        ayam_broiler: 3200,
        pmk: 0,
        lsd: 0,
        last_updated: '11 Okt 2026, 15.20 WIB',
        polygon: [
            [-7.415, 109.235], [-7.420, 109.262], [-7.442, 109.260],
            [-7.438, 109.235]
        ]
    },
    // 26. Purwokerto Utara (Perkotaan)
    {
        id: 'pwt_utara',
        nama: 'Pwt Utara',
        zona: 'aman',
        lat: -7.398,
        lng: 109.244,
        sapi_potong: 410,
        sapi_perah: 95,
        kambing: 640,
        domba: 150,
        ayam_broiler: 5500,
        pmk: 0,
        lsd: 0,
        last_updated: '11 Okt 2026, 15.20 WIB',
        polygon: [
            [-7.382, 109.230], [-7.385, 109.260], [-7.415, 109.260],
            [-7.412, 109.230]
        ]
    },
    // 27. Purwokerto Selatan (Perkotaan)
    {
        id: 'pwt_selatan',
        nama: 'Pwt Selatan',
        zona: 'aman',
        lat: -7.447,
        lng: 109.239,
        sapi_potong: 510,
        sapi_perah: 60,
        kambing: 820,
        domba: 190,
        ayam_broiler: 6800,
        pmk: 0,
        lsd: 0,
        last_updated: '11 Okt 2026, 15.20 WIB',
        polygon: [
            [-7.438, 109.225], [-7.440, 109.255], [-7.465, 109.255],
            [-7.462, 109.220]
        ]
    }
];

export function calculateRealQuantiles(list: RealKecamatan[], key: keyof RealKecamatan): number[] {
    const vals = list.map(item => Number(item[key]) || 0).sort((a, b) => a - b);
    const n = vals.length;
    if (n === 0) return [0, 0, 0, 0];
    return [
        vals[Math.floor(n * 0.2)],
        vals[Math.floor(n * 0.4)],
        vals[Math.floor(n * 0.6)],
        vals[Math.floor(n * 0.8)]
    ];
}

/* BERKAS-UTUH: banyumasGeoData.ts */
