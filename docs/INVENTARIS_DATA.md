# Inventaris Data Sistem TERNAKVERSE
**Sistem Informasi Geospasial & Intelijen Peternakan Terpadu Kabupaten Banyumas**

Dokumen ini memuat daftar lengkap seluruh data yang diambil dari basis data (*database* PostgreSQL / PostGIS) maupun berkas referensi spasial, serta bagaimana data tersebut diproses dan ditampilkan pada setiap modul antarmuka pengguna (*Portal Publik, Portal Dinas, dan Panel Super Admin*).

---

## 1. Ringkasan Sumber Daya & Entitas Basis Data

| No | Kategori Entitas | Tabel Sumber di Database | Jumlah Baris Data | Format / Satuan | Keterangan Sumber |
|:--:|---|---|:---:|:---:|---|
| **1** | **Wilayah Kecamatan** | `kecamatan` | 27 baris | Geometri Poligon & Atribut | BIG / BPS Banyumas (EPSG:4326) |
| **2** | **Wilayah Desa / Kelurahan** | `desa` | 331 baris | Titik Geospasial Balai Desa | BIG / BPS Banyumas (EPSG:4326) |
| **3** | **Master Komoditas Ternak** | `master_komoditas` | 10 baris | Klasifikasi Rumpun | Standar KAK Dinkannak Banyumas |
| **4** | **Master Kategori Produksi** | `master_kategori_produksi` | 3 baris | Daging, Susu, Telur | Dinas Perikanan dan Peternakan |
| **5** | **Master Penyakit Hewan** | `master_penyakit` | 8 baris | Nama & Ambang EWS | Medik Veteriner Dinkannak |
| **6** | **Populasi Ternak** | `populasi_ternak` | 3.972 baris | Ekor (*Headcount*) | Multi-Periode (2024 & 2026) BPS |
| **7** | **Produksi Hasil Peternakan** | `produksi_ternak` | 232 baris | Kg & Liter | Rekapitulasi Tahunan & Triwulanan |
| **8** | **Kasus Penyakit & Wabah** | `kasus_penyakit` | 186 baris | Jumlah Kasus Aktif | Sistem Pelaporan Kesehatan Hewan |
| **9** | **Snapshot Zonasi Wilayah** | `zona_snapshot_kecamatan` | 432 baris | Hijau, Kuning, Merah | Mesin Kalkulasi Otomatis EWS |
| **10** | **Fasilitas RPH** | `fasilitas_rph` | 5 baris | Titik PostGIS, Ekor/Hari | RPH Pemerintah & Swasta Banyumas |
| **11** | **Pengguna & RBAC** | `users` & `roles` | Akun Terdaftar | Kredensial & Hak Akses | Multi-User (Admin, Dinas, Mitra) |
| **12** | **Audit Trail Log** | `audit_logs` | Real-time Stream | Event Log Operasional | Jejak Rekam Modifikasi Data |

---

## 2. Rincian Data Master yang Dikelola

### A. Komoditas Ternak & Unggas (10 Komoditas)
1. **Sapi Potong** *(Ternak Besar Ruminansia)*
2. **Sapi Perah** *(Friesian Holstein - Sentra Lereng Slamet)*
3. **Kerbau** *(Ternak Kerja / Potong)*
4. **Kuda** *(Ternak Kerja / Pariwisata Baturraden)*
5. **Kambing** *(Kambing Jawa Randu & PE)*
6. **Domba** *(Domba Batur & Ekor Gemuk)*
7. **Ayam Ras Pedaging / Broiler** *(Unggas Potong)*
8. **Ayam Ras Petelur / Layer** *(Unggas Telur)*
9. **Ayam Buras / Kampung** *(Unggas Lokal)*
10. **Itik / Bebek** *(Unggas Air / Telur & Daging)*

### B. Hasil Produksi Peternakan (Pangan)
- **Daging**: Daging Sapi (kg), Daging Ayam (kg), Daging Kambing (kg), Daging Domba (kg).
- **Susu**: Susu Sapi Segar (liter), Susu Kambing (liter), Susu Domba (liter).
- **Telur**: Telur Ayam Ras & Lokal (kg).

### C. Penyakit Hewan & Parameter Early Warning System (EWS)
1. **PMK (Penyakit Mulut dan Kuku)** — Ambang Bahaya: $\ge 15$ kasus / kecamatan
2. **LSD (Lumpy Skin Disease)** — Ambang Bahaya: $\ge 10$ kasus / kecamatan
3. **BEF (Bovine Ephemeral Fever / Demam 3 Hari)**
4. **Scabies (Kudis Ternak)**
5. **Mastitis (Radang Ambing Sapi Perah)**
6. **Helminthiasis (Cacingan Saluran Cerna)**
7. **Antraks / Radang Limpa** *(Penyakit Zoonosis Strategis Prioritas 1)*
8. **Rabies (Penyakit Anjing Gila)** *(Penyakit Zoonosis Strategis)*

### D. Fasilitas Rumah Potong Hewan (RPH) Geospasial
1. **RPH Tambaksogra** (Kec. Sumbang) — Kapasitas: 25 ekor/hari (Pemerintah)
2. **RPH Kradenan Sumpiuh** (Kec. Sumpiuh) — Kapasitas: 15 ekor/hari (Pemerintah)
3. **RPH Wangon** (Kec. Wangon) — Kapasitas: 15 ekor/hari (Pemerintah)
4. **RPH Sokaraja Lor** (Kec. Sokaraja) — Kapasitas: 20 ekor/hari (Pemerintah)
5. **RPH Mitra Mandiri Ajibarang** (Kec. Ajibarang) — Kapasitas: 10 ekor/hari (Swasta)

---

## 3. Matriks Data yang Ditampilkan per Halaman Antarmuka

### 1. Halaman WebGIS Publik (`/` dan `/portal`)
*Tujuan: Layanan visualisasi geospasial transparansi data untuk masyarakat dan peternak.*

| Elemen UI | Data yang Ditampilkan | Sumber Data | Format Tampilan |
|---|---|---|---|
| **Peta Interaktif (Leaflet)** | Batas 27 Kecamatan, Batas 331 Desa, Poligon Choropleth | `banyumas_kecamatan_web.json`, `banyumas_desa_web.json` | Gradasi Warna Gelap (Obsidian Choropleth), Outline Emerald `#00D084` |
| **Layer Peta** | - Batas Kabupaten & Kecamatan<br>- Batas Desa<br>- Heatmap Populasi<br>- Titik Fasilitas RPH & Poskeswan<br>- Jalur Transportasi Ternak | GeoJSON + `fasilitas_rph` (PostGIS) | Toggle Layer interaktif di sidebar |
| **Sidebar Kontrol & Metrik** | - Pilihan Kategori (Populasi, Penyakit, Produksi, Fasilitas)<br>- Pilihan Komoditas (10 komoditas)<br>- Legenda Kuantil 5 Kelas (Sangat Rendah $\rightarrow$ Sangat Tinggi) | `METRIC_LIST`, `calculateQuantiles()` | Dropdown metrik, pill navigasi, statistik kuantil |
| **Panel Wilayah Terpilih (`SelectedRegionPanel`)** | - Nama Kecamatan & Kode BPS<br>- Luas Wilayah ($\text{km}^2$) & Jumlah Desa<br>- Total Populasi Ternak & Persentase Komposisi<br>- Daftar Desa dan Status EWS | Relasi data atribut kecamatan terhadap desa | Card panel terapung (*floating panel*) di pojok kanan atas |
| **Modal Detail Analitik (`RegionDetailAnalyticsModal`)** | - Rincian Populasi (Sapi Potong, Sapi Perah, Kambing, Domba, Unggas)<br>- Rincian Produksi Daging & Susu<br>- Tabel Desa dengan tombol navigasi **`Fokus`** | Agregasi data komoditas per kecamatan | Modal pop-up dialog interaktif |
| **Chatbot Asisten AI (`ChatbotDrawer`)** | Pengetahuan seputar sentra peternakan, jumlah populasi tiap wilayah, dan panduan penyakit | `banyumasData.ts` & Knowledge Engine | Drawer obrolan terapung di pojok kanan bawah |

---

### 2. Halaman WebGIS Dinas (`/internal/dinas`)
*Tujuan: Monitoring operasional spasial harian untuk aparat Dinas Peternakan dan Medik Veteriner.*

| Elemen UI | Data yang Ditampilkan | Sumber Data | Format Tampilan |
|---|---|---|---|
| **Header Identitas Petugas** | Nama Aparat / Dokter Hewan, Hak Akses ("Petugas Dinas"), Tombol Kembali | Session Auth (`auth.user`) | Header gelap dengan status badge terverifikasi |
| **Surveilans Wabah & EWS** | Kasus aktif PMK & LSD per kecamatan, riwayat vaksinasi | Tabel `kasus_penyakit` & `zona_snapshot` | Visualisasi choropleth status bahaya (Merah/Kuning/Hijau) |
| **Navigasi Cepat** | Shortcut menuju Dashboard Analitik Dinas (`/dinas/dashboard`) | Router Inertia | Tombol aksi cepat di header & sidebar |

---

### 3. Dashboard Operasional Dinas (`/dinas/dashboard`)
*Tujuan: Ringkasan statistik eksekutif dan analitik tren peternakan tingkat kabupaten.*

| Elemen UI | Data yang Ditampilkan | Sumber Data | Format Tampilan |
|---|---|---|---|
| **KPI Ringkasan Eksekutif** | - Total Populasi Ternak Kabupaten (1.795.275 ekor)<br>- Total Peternak Terdata (12.482 peternak)<br>- Kasus Aktif PMK (52 kasus) & LSD (32 kasus)<br>- Total Fasilitas RPH Aktif (8 unit) | Query SQL Agregasi Live (`DinasDashboardController.php`) | 4 Bento Card dengan indikator pertumbuhan (% vs periode lalu) |
| **Grafik Tren Kecamatan** | 5 Kecamatan dengan Populasi Ternak Terbanyak (Wangon, Sumbang, Baturraden, Purwokerto Selatan, Kedungbanteng) | Query SQL Group By Kecamatan | Bar Chart vertikal interaktif dengan opsi ekspor CSV/PNG |
| **Grafik Komposisi Komoditas** | Rasio persentase populasi ternak (Ayam Broiler, Kambing, Domba, Sapi) | Query Agregasi per Komoditas | Donut Chart interaktif |
| **Feed Aktivitas Lapangan** | Log mutasi ternak, pemeriksaan kesehatan, dan vaksinasi terbaru | Tabel operasional harian | Tabel linimasa aktivitas petugas lapangan |

---

### 4. Portal Super Admin (`/internal/admin`)
*Tujuan: Pengendalian sistem penuh, manajemen basis data, konfigurasi EWS, dan administrasi pengguna.*

| Modul Tab | Data yang Ditampilkan | Aksi yang Tersedia |
|---|---|---|
| **1. Dashboard Ringkasan** | - 4 Bento Card Interaktif (Populasi, Produksi Pangan, Kasus Wabah/EWS, Fasilitas RPH)<br>- Matriks data dinamis sesuai tombol kartu yang dipilih<br>- Filter Rentang Waktu (2024, 2026, Semua Periode) | Klik kartu untuk mengubah visualisasi data pendukung; tombol Impor Data & Tambah Komoditas. |
| **2. WebGIS Command Center** | Peta Leaflet administratif terintegrasi langsung di dalam panel admin | Navigasi spasial, inspeksi poligon, dan verifikasi titik koordinat. |
| **3. Data Peternakan** | - Tabel master 27 kecamatan<br>- Nama Kecamatan, Kode BPS, Kategori Rumpun Ternak<br>- Populasi (Ekor) dinamis sesuai filter yang dipilih<br>- Sub-rincian rincian (*Ayam, Sapi, Kambing/Domba*)<br>- Kolom Tahun aktif (2024, 2026, atau 2024-2026)<br>- Status EWS & Tanggal Pembaruan Terakhir | - Filter komoditas (Semua, Unggas, Sapi Perah, Sapi Potong, Kambing/Domba)<br>- Pencarian teks instan<br>- Tombol Edit Data & Hapus<br>- Ekspor Data (Excel/CSV)<br>- Impor Data pintar (*Smart Alias Mapping*) |
| **4. Input Data Baru** | Formulir pencatatan populasi baru per desa/kecamatan, kapasitas produksi susu/daging, serta laporan temuan kasus penyakit | Input formulir reaktif dengan validasi skema ketat. |
| **5. Monitoring EWS** | Parameter ambang batas zonasi (Jendela hari aktif surveilans, ambang kuning, ambang merah per penyakit) | Ubah nilai batas peringatan dini wabah hewan. |
| **6. Fasilitas RPH** | Tabel 5 fasilitas RPH resmi, kapasitas harian, penanggung jawab/pengelola, nomor kontak, koordinat lintang/bujur | Kelola data sarana pemotongan hewan berstandar ASUH. |
| **7. Ekspor Laporan** | Laporan periodik rekapitulasi triwulanan dan tahunan | Unduh berkas format XLSX, CSV, dan PDF. |
| **8. Pengaturan Akun** | Profil pengguna yang sedang login (Nama, NIP, Email, Password, Foto Profil) | Pembaruan profil & kata sandi akun admin. |
| **9. Manajemen RBAC** | Daftar seluruh akun aparat dinas, petugas lapangan, dan stakeholder; penetapan role dan status aktif | Tambah pengguna baru, ubah role (*admin, dinas, stakeholder*), nonaktifkan akun. |
| **10. Audit Trail Logs** | Riwayat log kronologis aktivitas sistem (Siapa, Melakukan Aksi Apa, Tabel Mana, Waktu, Status) | Audit transparansi dan akuntabilitas perubahan data peternakan. |

---

## 4. Karakteristik & Aturan Transformasi Data Sistem

1. **Prinsip Bebas Nilai Hardcode (*Zero Hardcode Rule*)**:
   - Seluruh metrik populasi, kategori dominan, dan produksi pangan dihitung secara dinamis melalui resolver fungsi reaktif (`getCommodityCategoryLabel`, `getCommodityPopulation`, dsb.).
2. **Kesesuaian Skala Kuantitatif (*Satuan Ekor*)**:
   - Sistem mengakomodasi realitas statistik di mana populasi unggas mencapai jutaan ekor, sementara sapi berkisar belasan ribu ekor, dengan menyediakan filter terpisah per komoditas serta badge sub-rincian.
3. **Sinkronisasi Multi-Tahun**:
   - Pemilihan tahun pada *Top Bar* secara instan menghitung ulang seluruh data di tabel, kartu analitik, dan peta (Mendukung data historis baseline 2024 dan data riil terupdate 2026).
