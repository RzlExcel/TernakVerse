# Dataset Batas Administrasi Spasial Kabupaten Banyumas
**Proyek:** TERNAKVERSE - WebGIS Persebaran Populasi Hewan Ternak Banyumas  
**Sumber Data:** Badan Informasi Geospasial (BIG) / BPS / Kemendagri via Geoportal Standar Indonesia  
**Sistem Proyeksi (CRS):** EPSG:4326 - WGS 84 (Geographic Coordinate System)  
**Tahun Rujukan:** 2026 (Sesuai KAK & Master Data KUGI)

---

## 1. Daftar File Spasial

### A. Batas Kecamatan (Layer Utama WebGIS - 27 Kecamatan)
* **`banyumas_kecamatan.geojson`** (3.3 MB): Dataset GeoJSON presisi tinggi (Full Polygon/MultiPolygon). Cocok untuk PostGIS database seeding (`ST_GeomFromGeoJSON`).
* **`banyumas_kecamatan_web.geojson`** (360 KB): Dataset GeoJSON teroptimasi (Topology-Preserved Simplification). Sangat ringan untuk rendering frontend Leaflet / MapLibre (render time < 35ms).
* **`banyumas_kecamatan_shp.zip`** (406 KB): ESRI Shapefile lengkap (`.shp`, `.shx`, `.dbf`, `.prj`) siap pakai untuk QGIS, ArcGIS, dan PostGIS `shp2pgsql`.
* **`shapefile_unpacked/banyumas_kecamatan.*`**: File Shapefile mentah siap buka langsung di GIS desktop.

### B. Batas Kabupaten (Outer Boundary - 1 Feature)
* **`banyumas_kabupaten.geojson`** (613 KB) & **`banyumas_kabupaten_web.geojson`** (68 KB): Batas luar terluar Kabupaten Banyumas untuk masking choropleth atau bounding boundary.
* **`banyumas_kabupaten_shp.zip`** (80 KB) & file di folder `shapefile_unpacked/`.

### C. Batas Desa / Kelurahan (331 Feature)
* **`banyumas_desa.geojson`** (10.3 MB): 301 Desa + 30 Kelurahan seluruh Banyumas.
* **`banyumas_desa_shp.zip`** (1.25 MB) & file di folder `shapefile_unpacked/`.

---

## 2. Verifikasi 27 Kecamatan (100% Cocok dengan KAK 2026)
1. Ajibarang (`33.02.10`)
2. Banyumas (`33.02.19`)
3. Baturraden (`33.02.26`)
4. Cilongok (`33.02.17`)
5. Gumelar (`33.02.11`)
6. Jatilawang (`33.02.01`)
7. Kalibagor (`33.02.18`)
8. Karanglewas (`33.02.14`)
9. Kebasen (`33.02.07`)
10. Kedungbanteng (`33.02.22`)
11. Kembaran (`33.02.27`)
12. Kemranjen (`33.02.08`)
13. Lumbir (`33.02.03`)
14. Patikraja (`33.02.12`)
15. Pekuncen (`33.02.16`)
16. Purwojati (`33.02.13`)
17. Purwokerto Barat (`33.02.20`)
18. Purwokerto Selatan (`33.02.23`)
19. Purwokerto Timur (`33.02.21`)
20. Purwokerto Utara (`33.02.24`)
21. Rawalo (`33.02.02`)
22. Sokaraja (`33.02.15`)
23. Somagede (`33.02.09`)
24. Sumbang (`33.02.25`)
25. Sumpiuh (`33.02.05`)
26. Tambak (`33.02.06`)
27. Wangon (`33.02.04`)

---

## 3. Atribut Tabel (DBF / GeoJSON Properties)
* `KODE_KEC`: Kode standar Kemendagri / BPS (Contoh: `33.02.03`)
* `KECAMATAN`: Nama kecamatan resmi (Contoh: `Jatilawang`)
* `KODE_KAB`: Kode kabupaten (`33.02`)
* `KABUPATEN`: `Banyumas`
* `PROVINSI`: `Jawa Tengah`
* `NAMA`: Nama lengkap (Contoh: `Kecamatan Jatilawang`)

---

## 4. Panduan Import ke Database PostGIS
```bash
# Opsi 1: Menggunakan shp2pgsql
shp2pgsql -s 4326 -I banyumas_kecamatan.shp master_kecamatan | psql -U postgres -d ternakverse_db

# Opsi 2: Menggunakan ogr2ogr (GeoJSON langsung ke PostGIS)
ogr2ogr -f "PostgreSQL" PG:"dbname=ternakverse_db user=postgres password=secret host=localhost port=5432" \
  banyumas_kecamatan.geojson -nln master_kecamatan -overwrite
```

---

## 5. Koordinat Bounding Box & Titik Tengah (Centroid)
* **Bounding Box Banyumas:** `[108.8893, -7.6658, 109.4464, -7.2463]`
* **Centroid Banyumas:** `Lat: -7.4560, Lng: 109.1678`
* **Leaflet View Setting Rekomendasi:**
  ```javascript
  const map = L.map('map').setView([-7.4560, 109.2150], 11);
  ```