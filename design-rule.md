# TERNAKVERSE --- UI Design Rules

**Design system:** OBSIDIAN × ELECTRIC EMERALD × PORCELAIN × CHARCOAL (Forest Intelligence GIS Dual Theme)\
**Product:** TERNAKVERSE --- Peta Persebaran Populasi Ternak Kabupaten Banyumas\
**Dokumen:** `design-rule.md`\
**Versi:** 2.0 (Dual-Theme Modernized)

------------------------------------------------------------------------

## 1. Tujuan dan Prinsip Desain

Dokumen ini menjadi acuan spesifikasi teknis dan panduan desain utama untuk merancang, mengembangkan, dan memperbarui seluruh antarmuka WebGIS publik maupun portal internal TERNAKVERSE. Seluruh halaman dan komponen visual wajib mematuhi sistem visual terpadu ini demi menjamin konsistensi, aksesibilitas tinggi, serta performa rendering peta yang optimal.

### 1.1 Arah visual

Sistem visual TERNAKVERSE dibangun di atas konsep **Modern Geospatial Intelligence Dashboard** dengan dua mode tema terkalibrasi tinggi:

1. **Dark Mode (Obsidian × Electric Emerald):**
   - Latar kanvas berbasis batu obsidian legam (`#080A09`) yang mengurangi ketegangan mata operator pada ruangan gelap.
   - Aksen utama menggunakan Electric Emerald (`#00D084`) yang melambangkan vitalitas sektor peternakan dan kecerdasan geospasial modern.
   - Elemen organik bumi (Harvest, Terracotta, Teal, Lime, Moss) memperkaya visualisasi data tematik tanpa membuat antarmuka menjadi bising.

2. **Light Mode (Porcelain × Charcoal):**
   - Latar dokumen dan kanvas berbasis porselen bersih (`#FFFFFF` dan `#F4F5F2`) dengan keterbacaan tajam di lingkungan terang/luar ruangan.
   - Kontrol primer menggunakan Charcoal taktil (`#171B17`) dengan teks putih murni (`#FFFFFF`), memberikan wibawa formal, ketegasan instansi pemerintah, dan kontras sempurna tanpa kesan murah.
   - Batas panel dan pembatas menggunakan rona bumi abu-abu kehijauan lembut (`#E0E4DD`) yang tenang dan teratur.

Karakter umum antarmuka:
- Profesional, kredibel, dan kokoh layaknya sistem komando geospasial pertahanan atau kementerian.
- Modern, presisi, berorientasi data kuantitatif dan analisis spasial.
- Menempatkan peta sebagai ruang kerja utama (*map-first interface*).
- Menghindari ilusi futuristik murahan (AI-slop) dan mengutamakan keterbacaan, efisiensi interaksi, dan tactile responsiveness.

### 1.2 Prinsip wajib

1. **Map-first:** Peta merupakan fokus utama dan berhak atas area visual terbesar di viewport.
2. **Sidebar-driven:** Seluruh kendali data, pemilihan komoditas, manajemen layer, legenda interaktif, dan peringkat kecamatan berpusat di sidebar kiri.
3. **Data-driven:** Warna, label status, indikator, dan grafik choropleth harus merefleksikan signifikansi data aktual, bukan dekorasi bebas.
4. **Progressive disclosure:** Sajikan informasi ringkas tingkat tinggi terlebih dahulu; rincian statistik desa dan tren historis dibuka secara kontekstual.
5. **Tactile Ergonomics:** Seluruh elemen interaktif memiliki feedback taktil yang jelas (elevasi border, perpindahan 1px pada state aktif, ring fokus yang kontras).
6. **Anti-AI-slop Discipline:** Menolak estetika artifisial murahan (tanpa glowing neon berlebih, tanpa border gradien ungu/kebiruan acak, tanpa live-dot palsu, tanpa pita tepi warna-warni pada card).
7. **Responsive by design:** Layout fixed application shell harus beradaptasi mulus dari monitor desktop ultrawide hingga layar perangkat mobile lapangan.
8. **Accessible (WCAG 2.2 AA):** Menjamin rasio kontras teks minimal 4.5:1 untuk teks biasa dan 3:1 untuk kontrol UI; jangan pernah menyampaikan status hanya lewat warna tanpa teks pendukung.
9. **Seamless Dual-Theme Resilience:** Peralihan mode Light dan Dark harus instan, tanpa kedipan visual (FOUC), dan tanpa merusak atau merender ulang map instance Leaflet.

------------------------------------------------------------------------

## 2. Design Tokens

Seluruh komponen wajib mengonsumsi semantic tokens berikut. Dilarang mendeklarasikan nilai hex sembarang di dalam template komponen tanpa merujuk ke token sistem.

### 2.1 Dark Mode Palette (Obsidian × Electric Emerald)

| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-bg` | `#080A09` | Latar utama aplikasi (Obsidian Deep Base) |
| `--color-surface` | `#111512` | Latar sidebar kiri, panel kontrol, dan card dasar |
| `--color-surface-raised` | `#1A201A` | Panel bertingkat, dropdown menu, modal dialog, popover |
| `--color-surface-hover` | `#202820` | State hover pada baris tabel, item daftar, atau card |
| `--color-surface-active` | `#293329` | State aktif/terpilih pada navigasi dan item daftar |
| `--color-border` | `#293129` | Border standar dan garis pemisah antar seksi |
| `--color-border-strong` | `#3A4639` | Border fokus, card hover, atau batas panel aktif |
| `--color-primary` | `#00D084` | Tombol utama dan aksi primer (Electric Emerald) |
| `--color-primary-hover` | `#00F0A0` | State hover tombol primer |
| `--color-primary-active` | `#00A86B` | State tekan/aktif tombol primer |
| `--color-primary-soft` | `#063D2D` | Latar belakang badge aktif dan ring fokus lembut |
| `--color-primary-text` | `#062117` | Teks kontras tinggi di atas permukaan Electric Emerald |

#### Supporting Organic Earth (Dark Mode)
| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-harvest` | `#F0A52B` | Aksen hasil panen, produksi telur/susu, peringatan sedang |
| `--color-terracotta` | `#F07845` | Aksen pakan ternak, populasi unggas, peringatan bahaya |
| `--color-teal` | `#32B9D0` | Sorotan geospasial, batas hidrologi, aksen batas wilayah |
| `--color-lime` | `#A3D94A` | Indikator hijauan pakan ternak & pertumbuhan positif |
| `--color-moss` | `#354B35` | Garis kontur pendukung, vegetasi, batas sekunder |

#### Typography Tokens (Dark Mode)
| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-text` | `#F0F4F1` | Teks judul utama dan nilai metrik utama (kontras 95%) |
| `--color-text-secondary` | `#A2AEA2` | Teks paragraf, label formulir, nama kecamatan |
| `--color-text-muted` | `#687668` | Metadata, timestamp, placeholder input, satuan |
| `--color-text-disabled` | `#445144` | Teks tombol/input yang dinonaktifkan |

---

### 2.2 Light Mode Palette (Porcelain × Charcoal)

| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-bg` | `#FFFFFF` | Latar utama kanvas & dokumen (Porcelain Pure White) |
| `--color-surface` | `#F4F5F2` | Latar sidebar, container kontrol, dan area kerja sekunder |
| `--color-surface-raised` | `#FFFFFF` | Kartu terangkat (*raised card*), dropdown, modal dialog |
| `--color-surface-hover` | `#EDEFEB` | State hover pada baris tabel dan item daftar |
| `--color-surface-active` | `#E4E8E1` | State aktif/terpilih pada navigasi dan item filter |
| `--color-border` | `#E0E4DD` | Border standar dan garis pemisah komponen |
| `--color-border-strong` | `#C8CEC4` | Border penekanan, input aktif, hover border |

#### Primary Controls & Accent (Light Mode)
| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-primary` | `#171B17` | Tombol utama dan kontrol primer (Charcoal Deep) |
| `--color-primary-hover` | `#262D26` | State hover tombol primer Charcoal |
| `--color-primary-active` | `#080A09` | State tekan/aktif tombol primer Charcoal |
| `--color-primary-soft` | `#E4E8E1` | Latar belakang seleksi lembut dan ring fokus |
| `--color-primary-text` | `#FFFFFF` | Teks kontras murni di atas tombol Charcoal |

#### Typography Tokens (Light Mode)
| Token CSS | Nilai Hex | Peran & Penggunaan |
| :--- | :--- | :--- |
| `--color-text-primary` (`--color-text`) | `#171B17` | Teks judul utama dan nilai metrik (Charcoal legam) |
| `--color-text-secondary` | `#444C43` | Paragraf penjelasan, label input, judul kartu |
| `--color-text-muted` | `#737B71` | Helper text, placeholder, satuan metrik, timestamp |
| `--color-text-disabled` | `#A8AEA5` | Elemen teks nonaktif |

> [!IMPORTANT]
> **Aturan Khusus Tombol Primer Light Mode:**
> Tombol aksi primer pada Light Mode wajib menggunakan Charcoal `#171B17` dengan teks putih `#FFFFFF` (bukan hijau muda atau emerald pucat). Hal ini memastikan tombol primer memiliki ketegasan visual instansial, keterbacaan tanpa silau, dan kontras WCAG AAA.

---

### 2.3 Legacy & Additive Design Tokens

Untuk menjamin kompatibilitas regresi pada seluruh unit dan integration test otomatis (`UiUxTransformationVerificationTest.php`), token legacy Forest Intelligence GIS dan AgriTech Slate tetap dipelihara berdampingan secara aditif di `resources/css/app.css` dan `tailwind.config.js`:

- Legacy Forest GIS: `--legacy-forest-bg: #0B1420`, `--legacy-forest-surface: #101D2B`, `--legacy-forest-primary: #159B78`, `--legacy-forest-accent: #39D6C0`.
- Legacy Quantile Emerald: `#134E4A`, `#0F766E`, `#059669`, `#10B981`, `#34D399`.
- Legacy Neon (#39FF14) tetap dilarang keras dan telah dieliminasi total dari seluruh berkas proyek.

---

### 2.4 Population Legend Palette (5-Class Ordered Choropleth Scale)

Klasifikasi choropleth poligon populasi ternak menggunakan skala diskrit 5 kelas berurutan multikromatik yang dirancang khusus agar tetap terbaca jelas baik di atas basemap satelit gelap maupun peta vektor terang:

| Kelas Populasi | Token CSS | Nilai Hex | Deskripsi & Nuansa Visual |
| :--- | :--- | :--- | :--- |
| **Sangat Rendah** | `--color-choro-1` | `#3B82A0` | Teal Slate Muted (populasi minimal) |
| **Rendah** | `--color-choro-2` | `#38A69A` | Teal Sage Seimbang |
| **Sedang** | `--color-choro-3` | `#86A65A` | Olive Gold / Moss Hijau-Kuning |
| **Tinggi** | `--color-choro-4` | `#D4A34E` | Harvest Amber Hangat |
| **Sangat Tinggi** | `--color-choro-5` | `#D9784D` | Terracotta Vibrant (sentra populasi) |

Aturan rendering poligon choropleth:
- Opasitas isi (*fill opacity*) poligon diatur antara 0.45 hingga 0.55 untuk mempertahankan kontur topografi dan jalan basemap satelit.
- Stroke batas poligon default memiliki ketebalan 1.5px dengan warna `var(--color-border)`.
- Poligon kecamatan yang sedang dipilih (*selected region*) diberi stroke tebal 3.5px berwarna Electric Emerald (`#00D084`) dengan opasitas 1.0.

---

### 2.5 Disease Legend Palette (EWS Peternakan)

Sistem Peringatan Dini Penyakit Hewan (PMK, LSD, Antraks) menggunakan skala status tiga tingkat standar:

| Status Risiko | Token CSS | Nilai Hex | Makna & Penggunaan |
| :--- | :--- | :--- | :--- |
| **Aman** | `--color-aman` | `#20B88A` | Wilayah hijau bebas kasus aktif (kondisi terkendali) |
| **Waspada** | `--color-waspada` | `#E8B84C` | Wilayah kuning dengan temuan kasus suspek/pemantauan |
| **Bahaya** | `--color-bahaya` | `#DC625F` | Wilayah merah dengan wabah aktif atau penularan tinggi |

Aturan penandaan risiko penyakit:
- Jangan pernah mengandalkan warna semata. Setiap indikator penyakit wajib menyertakan label tekstual (contoh: **Bahaya: 14 Kasus Aktif**) dan ikon status yang relevan.
- Pada poligon peta, area dengan status Bahaya dapat diperkuat dengan arsiran grafis garis diagonal (`.pattern-bahaya`) dan status Waspada dengan pola titik rapat (`.pattern-waspada`) guna memastikan aksesibilitas bagi pengguna dengan buta warna (color vision deficiency).

---

### 2.6 Typography

- **Font Family Utama:** `'Plus Jakarta Sans'`, dengan fallback `'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif`.
- **Angka Tabular Wajib:** Semua nilai statistik, nominal populasi ternak, jumlah kasus, nilai koordinat lintang/bujur, persentase, dan ranking wajib menggunakan utilitas `tabular-nums` atau `font-mono` agar angka tidak bergeser saat data diperbarui secara dinamis.
- **Skala Ukuran Teks:**
  - Halaman / Judul Dashboard: 20--24 px, font-weight 700--800.
  - Header Panel & Subtitle: 13--15 px, font-weight 600.
  - Teks Body & Deskripsi: 12--14 px, font-weight 400--500, line-height 1.5.
  - Label Metrik & Helper Text: 11--12 px, font-weight 500.
  - Angka Statistik Utama (KPI): 24--32 px, font-weight 700--800, `tabular-nums`.

---

### 2.7 Spacing, Radius, dan Elevation

Sistem penataan ruang mengikuti ritme 8-point spatial system berbasis kelipatan 4px:
- Skala spacing: `space-1` (4px), `space-2` (8px), `space-3` (12px), `space-4` (16px), `space-5` (20px), `space-6` (24px), `space-8` (32px).
- Radius sudut:
  - Kartu & Panel Kontainer: 12--16 px (`rounded-xl` atau `rounded-2xl`).
  - Tombol Taktil & Input: 8 px (`rounded-lg`).
  - Badge Status & Pill Tag: 999 px (`rounded-full`).
- Elevasi & Kedalaman:
  - Bangun kedalaman melalui perbedaan permukaan semantic (`--color-surface` vs `--color-surface-raised`) dan garis tepi 1px (`--color-border`).
  - Bayangan (*box-shadow*) hanya digunakan secara halus pada elemen mengambang: `0 4px 20px -2px rgba(0, 0, 0, 0.15)` pada Light Mode dan `0 4px 20px -2px rgba(0, 0, 0, 0.5)` pada Dark Mode. Dilarang menggunakan bayangan neon tebal.

------------------------------------------------------------------------

## 3. Struktur Layout WebGIS

### 3.1 Desktop Layout Shell

WebGIS TERNAKVERSE menerapkan pola arsitektur **Fixed Application Shell** yang responsif dan mengisi penuh tinggi viewport (`h-[calc(100vh-56px)]` dengan root `overflow-hidden`):

```text
┌─────────────────────────────────────────────────────────────────────────────────┐
│ HEADER (52-60px): Logo | Breadcrumb | Basemap Toggle | Reset | Help | Theme Nav │
├──────────────────────┬───────────────────────────────────────────┬──────────────┤
│                      │                                           │              │
│ SIDEBAR KIRI         │                                           │ DETAIL KANAN │
│ (340-380px)          │                MAP CANVAS                 │ (300-340px)  │
│ 1. Data Peta         │                                           │ Kontekstual  │
│ 2. Layer Peta        │        Choropleth Polygon / Markers       │ Kecamatan /  │
│ 3. Legenda Dinamis   │                                           │ Desa         │
│ 4. Peringkat Top 5   │                              Map Controls │ Terpilih     │
│ (Scroll independen)  │                                   Chatbot │ (Dapat tutup)│
└──────────────────────┴───────────────────────────────────────────┴──────────────┘
```

Rekomendasi dimensi layar desktop (>= 1200px):
- Header: tinggi tetap 52--60 px.
- Sidebar Kiri (Expanded): lebar 340--380 px.
- Sidebar Kiri (Collapsed): lebar 56--64 px.
- Map Canvas: mengisi 100% sisa ruang viewport di antara header dan panel.
- Panel Detail Wilayah Kanan: lebar 300--340 px, muncul kontekstual di atas map canvas sisi kanan.
- Chatbot Launcher: tombol mengambang 48--56 px di pojok kanan bawah terluar.

---

### 3.2 Header WebGIS

Komponen `WebGisHeader.vue` berfungsi sebagai bilah komando atas dengan komponen wajib:
1. Logo peternakan TERNAKVERSE dan label identitas geospasial Kabupaten Banyumas.
2. Segmented toggle basemap: **Peta Satelit** (Hybrid) dan **Peta Vektor** (CartoDB / OpenStreetMap).
3. Tombol aksi cepat: **Reset Filter** dan **Bantuan**.
4. Switcher Tema Visual (Light / Dark / System) yang terikat pada `useAppearance.ts`.
5. Tautan portal operasional internal dinas bagi aparatur berwenang.

---

### 3.3 Sidebar Kiri (Pusat Kontrol Operasional)

Sidebar kiri (`MapSidebar.vue`) merupakan pusat orkestrasi seluruh visualisasi peta dengan 4 panel inti:
1. **Panel Data Peta:** Pilihan kategori utama (Populasi, Penyakit, Produksi, Fasilitas) dengan selector komoditas (Sapi Potong, Sapi Perah, Kambing, Domba, Ayam Broiler, dll.).
2. **Panel Layer Peta:** Switcher kontrol layer aktif (Batas Kecamatan, Batas Desa, Heatmap, Titik RPH/Puskeswan).
3. **Panel Legenda Dinamis:** Legenda 5 kelas kuantil choropleth atau status penyakit dengan nilai batas dinamis dan satuan yang sesuai.
4. **Panel Peringkat Kecamatan:** Daftar peringkat Top 5 kecamatan dengan progress bar proporsional terhadap nilai tertinggi. Mengklik item peringkat otomatis memfokuskan peta (*flyTo / fitBounds*) ke geometri kecamatan terkait.

---

### 3.4 Panel Detail Wilayah Terpilih

Panel `SelectedRegionPanel.vue` muncul di sisi kanan kanvas peta ketika pengguna memilih suatu poligon atau mengklik item peringkat kecamatan:
- Menampilkan nama kecamatan/desa terpilih, status kuantil, total populasi komoditas aktif, dan statistik fasilitas.
- Berfungsi secara kontekstual tanpa menutupi poligon yang sedang aktif di tengah kanvas.
- Dapat ditutup pengguna kapan saja tanpa me-reset filter komoditas yang sedang aktif di sidebar kiri.

------------------------------------------------------------------------

## 4. Aturan Map Canvas & Theme Behavior

### 4.1 Peran Utama Peta

Peta adalah pusat gravitasi aplikasi. Seluruh interaksi di sidebar kiri maupun panel kanan berfungsi sebagai pendukung pemahaman geospasial di atas kanvas peta.

### 4.2 Manajemen Basemap

Aplikasi menyediakan minimal 2 sumber basemap legal dan stabil:
1. **Peta Satelit (Google Satellite / Esri World Imagery):** Menampilkan bentang alam nyata dan tutupan lahan peternakan.
2. **Peta Vektor (CartoDB Dark Matter / Voyager):** Menampilkan jaringan jalan dan batas administrasi dengan kontras maksimal.

### 4.3 Map & Chart Theme Behavior (Resilience across Theme Switches)

Untuk menjamin kenyamanan pengguna dan efisiensi memori tingkat tinggi:

1. **Leaflet Instance Preservation:**
   - Map instance Leaflet (`L.Map`) **DILARANG KERAS** dihancurkan (`map.remove()`) atau di-reinstantiate dari nol saat pengguna berganti tema antara Light Mode dan Dark Mode.
   - Peta harus tetap mempertahankan posisi center, level zoom, layer GeoJSON aktif, dan state poligon terpilih saat tema berubah.

2. **CSS Variable & Class-Driven Controls & Tooltips:**
   - Kotak pencarian koordinat, tombol zoom (`+` dan `-`), tombol geolocation, dan scale bar beradaptasi secara otomatis menggunakan token CSS (`var(--color-surface)`, `var(--color-border)`, dan `var(--color-text)`).
   - Tooltip poligon Leaflet (`.custom-leaflet-tooltip`, `.custom-desa-tooltip`) dan latar belakang wadah (`.leaflet-container`) merespons selektor `.dark` vs `:root:not(.dark)`:
     - Dark Mode: latar tooltip `#111512`, teks `#F0F4F1`, border `#293129`.
     - Light Mode: latar tooltip `#FFFFFF`, teks `#171B17`, border `#E0E4DD`.
   - Perubahan basemap satelit/vektor dapat disinkronkan secara mulus tanpa mengganggu geometri poligon yang telah di-cache.

3. **Chart Adaptation:**
   - Seluruh grafik statistik (Chart.js / ApexCharts) memperbarui warna grid, label sumbu, dan legendanya secara reaktif lewat pembaruan opsi konfigurasi tanpa memicu unmount DOM kanvas grafik.

------------------------------------------------------------------------

## 5. Chatbot TERNAKVERSE AI

### 5.1 Penempatan & Posisi

Chatbot TERNAKVERSE AI ditempatkan pada **pojok kanan bawah paling ujung viewport**:
- Menggunakan `position: fixed` di pojok kanan bawah dengan jarak 20--24 px dari tepi kanan dan bawah layar.
- Tidak boleh menutupi koordinat pointer peta, attribution bar Leaflet, maupun scale bar.
- Memiliki `z-index` di atas map canvas namun tetap di bawah modal dialog sistem.

### 5.2 Tampilan Launcher & Jendela Percakapan

- **Launcher:** Berbentuk tombol apung bulat/pill dengan ikon kecerdasan buatan, teks **Tanya TERNAKVERSE AI**, dan badge status kesiapan.
- **Jendela Chat:** Floating card selebar 360--400 px dan tinggi 480--560 px pada desktop, mengonsumsi token `--color-surface-raised` dan `--color-border`.
- **Konteks Spasial:** Chatbot mampu membaca konteks filter aktif di sidebar (kategori, komoditas terpilih, dan kecamatan yang sedang disorot).

------------------------------------------------------------------------

## 6. Komponen UI, Surface Hierarchy & Tactile Buttons

### 6.1 Hirarki Permukaan (Surface Hierarchy)

Sistem antarmuka membangun kedalaman secara tegas melalui 3 tingkat permukaan kontras tinggi:
1. **Latar Kanvas (`--color-bg`):** Dasar terluar aplikasi (`#080A09` Dark / `#FFFFFF` Light).
2. **Permukaan Panel (`--color-surface` / `.glass-panel`):** Wadah sidebar dan panel kontrol berbatas 1px (`#111512` Dark / `#F4F5F2` Light).
3. **Permukaan Terangkat (`--color-surface-raised` / `.glass-card`):** Kartu data, dropdown, dan modal (`#1A201A` Dark / `#FFFFFF` Light) dengan elevasi border presisi.

---

### 6.2 Tombol & Input Taktil (Tactile Components)

Seluruh elemen interaktif utama wajib mengimplementasikan kelas taktil standar di `resources/css/app.css`:

#### A. `.btn-primary-tactile`
Tombol aksi primer berketinggian 40px, sudut membulat 8px, font-weight 600, dengan feedback fisik perpindahan 1px saat ditekan:
- **Light Mode:** Latar Charcoal `#171B17`, teks putih `#FFFFFF`, border `#293129`. Hover: latar `#262D26`, border `#3A4639`. Active: `transform: translateY(1px); background: #080A09`.
- **Dark Mode:** Latar Electric Emerald `#00D084`, teks `#062117`, border `#00F0A0`. Hover: latar `#00F0A0`, border `#00F0A0`. Active: `transform: translateY(1px); background: #00A86B`.

#### B. `.btn-secondary-tactile`
Tombol aksi sekunder berketinggian 40px:
- Menggunakan latar `var(--color-surface)`, teks `var(--color-text)`, dan border `var(--color-border)`.
- Hover: latar `var(--color-surface-hover)` dan border `var(--color-border-strong)`.
- Active: `transform: translateY(1px); background: var(--color-surface-active)`.

#### C. `.btn-danger-tactile`
Tombol aksi berbahaya/destruktif berketinggian 40px:
- Menggunakan aksen Terracotta Red `#DC625F` dengan latar transparan halus (12% opasitas).
- Active: `transform: translateY(1px)`.

#### D. `.input-tactile`
Elemen input teks dan filter formulir berketinggian 40px:
- Border 1px `var(--color-border)`, sudut 8px, latar `var(--color-bg)`, teks `var(--color-text)`.
- Placeholder: `var(--color-text-muted)`.
- Focus State: border `var(--color-primary)` dengan focus ring 2px halus menggunakan `var(--color-primary-soft)`.

---

### 6.3 Iconography

- Seluruh ikon antarmuka menggunakan **Lucide Icons** dengan stroke width 1.75--2.0.
- Dimensi ikon standar: 14--16 px untuk tombol kecil/metadata, 18--20 px untuk navigasi dan judul panel.
- Dilarang keras menggunakan emoji sebagai ikon navigasi atau penanda status struktural.

------------------------------------------------------------------------

## 7. Responsive Behavior

### 7.1 Desktop (>= 1200 px)
- Fixed application shell utuh: Header horizontal, Sidebar kiri expanded (340-380px), Kanvas peta penuh, Panel detail kontekstual kanan (300-340px), Chatbot di pojok kanan bawah.

### 7.2 Tablet (768 px -- 1199 px)
- Sidebar kiri dapat di-collapse menjadi mode mini-bar (56-64px) atau drawer geser.
- Panel detail wilayah kanan berubah menjadi drawer overlay transparan yang dapat di-dismiss.
- Chatbot menyesuaikan ukuran jendela agar tidak mendominasi layar.

### 7.3 Mobile (< 768 px)
- Kanvas peta menjadi latar belakang penuh.
- Sidebar kontrol data dan filter berpindah ke dalam *bottom sheet* yang dapat ditarik (*expandable bottom drawer*).
- Target sentuh interaktif seluruh tombol dan input minimal 44 × 44 px.
- Panel chat AI terbuka dalam mode full-screen modal dengan penutup yang mudah diakses jempol.

------------------------------------------------------------------------

## 8. Motion dan Micro-interactions

- Transisi warna dan border: 150 ms ease-out.
- Pembukaan dropdown dan drawer: 180--220 ms cubic-bezier(0.16, 1, 0.3, 1).
- Pergeseran kamera peta (*flyTo / fitBounds*): 800--1200 ms dengan easing geospasial mulus.
- Animasi tombol tekan: `translateY(1px)` instan (100 ms).
- **Reduced Motion:** Seluruh transisi wajib menghormati media query `@media (prefers-reduced-motion: reduce)`.

------------------------------------------------------------------------

## 9. UX States yang Wajib Disediakan

Setiap komponen pemroses data geospasial wajib menyediakan 7 state baku:
1. **Loading State:** Skeleton loader berdenyut halus pada panel statistik; indikator spinner ringkas pada peta saat GeoJSON sedang diunduh.
2. **Empty State:** Ilustrasi grafis minimalis dan pesan instruktif saat data komoditas belum dipilih atau kosong.
3. **Error State:** Kotak pesan informatif dengan opsi *Coba Lagi* (*Retry*) saat API gagal merespons.
4. **Success State:** Feedback visual cepat saat filter atau konfigurasi berhasil diterapkan.
5. **Disabled State:** Tampilan opasitas 50% dengan kursor `not-allowed` jika tombol/kontrol belum memenuhi prasyarat.
6. **Selected State:** Border kontras tinggi dan latar belakang `var(--color-surface-active)` atau `var(--color-primary-soft)`.
7. **No Results State:** Pesan pencarian kosong dengan tombol satu klik untuk *Reset Pencarian*.

------------------------------------------------------------------------

## 10. Aksesibilitas (WCAG 2.2 AA)

1. **Rasio Kontras Minimum:** Seluruh teks utama terhadap latar wajib melampaui 4.5:1. Teks judul besar dan kontrol tombol melampaui 3:1.
2. **Navigasi Keyboard Penuh:** Seluruh tombol, switch layer, tab kategori, dan ranking dapat diakses menggunakan tombol `Tab`, `Enter`, dan `Space`.
3. **Visible Focus Rings:** Seluruh kontrol yang menerima fokus keyboard wajib menampilkan cincin fokus (*focus outline*) yang tegas menggunakan `var(--color-primary)`.
4. **Accessible Labels:** Seluruh tombol ikon-saja (seperti tombol zoom, tutup panel, launcher chat) wajib memiliki atribut `aria-label` yang deskriptif.
5. **Non-color Information Delivery:** Jangan pernah menyampaikan informasi bahaya, waspada, atau sentra peternakan hanya dengan warna; selalu kombinasikan dengan label teks, ikon, atau pola arsiran.

------------------------------------------------------------------------

## 11. Aturan Performa WebGIS (High-Performance GIS Engine)

1. **Geometry Caching & Canvas Renderer:** Gunakan opsi `preferCanvas: true` pada Leaflet untuk merender ratusan batas poligon desa/kecamatan dengan konsumsi CPU rendah.
2. **Debounce User Search:** Pencarian wilayah dan komoditas wajib di-debounce minimal 250--300 ms.
3. **No Unnecessary Re-mount:** Dilarang me-render ulang instance peta saat state UI lokal (seperti membuka tab legenda atau mengetik di chat) berubah.
4. **Lazy Loading Layers:** Layer detail seperti batas desa atau sebaran titik peternak hanya dimuat saat tingkat zoom mencapai level tertentu (>= zoom 12).
5. **Memory Leak Prevention:** Selalu bersihkan event listeners, timer interval, dan tooltip Leaflet ketika komponen di-unmount.

------------------------------------------------------------------------

## 12. Aturan Konsistensi Data

1. **Format Angka Indonesia:** Format numerik wajib menggunakan standar locale `id-ID` (titik sebagai pemisah ribuan dan koma sebagai pemisah desimal, contoh: `12.450 ekor`).
2. **Satuan Terbaca Jelas:** Setiap angka statistik wajib didampingi satuan resmi (`ekor`, `kasus`, `unit`, `ton/tahun`, `km²`).
3. **Data Integrity:** Dilarang mengarang angka statistik dummy atau koordinat acak pada lingkungan produksi. Gunakan nilai aktual dari repositori basis data Kabupaten Banyumas.
4. **Sinkronisasi Nilai:** Nilai yang tertera pada poligon peta, panel legenda, panel ranking, dan kartu ringkasan wajib bersumber dari state data yang sama persis.

------------------------------------------------------------------------

## 13. Hal yang Dilarang & Anti-AI-Slop Restrictions

Untuk menjaga kualitas desain berstandar industri tinggi dan menghindari tren murahan (*generic AI-slop*):

1. **DILARANG Efek Glowing Neon Berlebih:**
   - Dilarang menambahkan `box-shadow` berpendar hijau menyala, border neon elektrik berlebihan, atau animasi aura pulsing pada elemen kartu dan panel.
2. **DILARANG Menempatkan Live-Status Dot Sembarangan:**
   - Dilarang menaruh titik hijau berkedip (*blinking status dot*) di setiap judul, kartu metrik, atau label teks jika tidak merepresentasikan koneksi streaming data IoT secara riil.
3. **DILARANG Pita Warna-Warni pada Tepi Kartu (*Card Edge Strips*):**
   - Dilarang memasang border tebal warna-warni di sisi kiri/kanan kartu (`border-l-4 border-emerald-500`) yang mengotori ritme tata letak dan merusak hirarki visual.
4. **DILARANG Navigasi Aktif Berbasis Garis Outline Kasar:**
   - Elemen navigasi atau tab aktif harus menggunakan permukaan lembut (`var(--color-surface-active)` atau `var(--color-primary-soft)`), bukan garis tepi outline tebal yang menyilaukan.
5. **DILARANG Mengubah Skema Warna Secara Acak:**
   - Dilarang menggunakan kombinasi warna acak di luar Obsidian/Electric Emerald (Dark) dan Porcelain/Charcoal (Light).
6. **DILARANG Merusak Instance Peta Saat Ganti Tema:**
   - Dilarang memanggil `map.remove()` atau memuat ulang seluruh peta saat pengguna beralih mode tampilan.
7. **DILARANG Menampilkan Popup Raksasa yang Menutupi Poligon:**
   - Rincian wilayah wajib tampil di panel kontekstual sisi kanan, bukan popup raksasa di tengah layar yang menghalangi pengamatan spasial.
8. **DILARANG Menggunakan Emoji Struktural:**
   - Gunakan SVG Lucide Icons yang konsisten; dilarang menggunakan karakter emoji teks sebagai ikon sistem.

------------------------------------------------------------------------

## 14. Panduan Implementasi pada Kode Eksisting

Saat memperbarui atau menambahkan komponen antarmuka pada basis kode TERNAKVERSE:

1. **Gunakan Semantic Classes yang Sudah Tersedia:**
   - Manfaatkan `.glass-panel`, `.glass-card`, `.btn-primary-tactile`, `.btn-secondary-tactile`, `.btn-danger-tactile`, dan `.input-tactile`.
2. **Pisahkan Logika Komponen Secara Modular:**
   - Pertahankan struktur komponen WebGIS yang bersih:
     ```text
     resources/js/components/ternakverse/
     ├── LeafletWebGisMap.vue       # Kanvas Leaflet & GeoJSON layers
     ├── WebGisHeader.vue           # Header navigasi & switcher basemap/tema
     ├── MapSidebar.vue             # Pusat kontrol data, layer, legenda, ranking
     ├── SelectedRegionPanel.vue    # Panel detail kontekstual wilayah terpilih
     ├── TernakverseChatbot.vue     # Chatbot AI asisten geospasial
     └── ...
     ```
3. **Patuhi Arsitektur State Terpusat:**
   - Gunakan props dan emitted events atau composables Vue 3 (`useAppearance.ts`, dsb.) untuk pertukaran state antar-komponen tanpa membuat tight coupling.

------------------------------------------------------------------------

## 15. Definition of Done (Checklist Verifikasi)

Pembaruan atau penambahan antarmuka WebGIS dinyatakan **SELESAI (DONE)** jika memenuhi seluruh kriteria berikut:

- [ ] Seluruh token warna mengacu pada Obsidian × Electric Emerald (Dark) dan Porcelain × Charcoal (Light).
- [ ] Tombol primer Light Mode menggunakan Charcoal `#171B17` dengan teks putih `#FFFFFF`.
- [ ] Tombol primer Dark Mode menggunakan Electric Emerald `#00D084` dengan teks `#062117`.
- [ ] Tombol dan input interaktif mengimplementasikan kelas `.btn-*-tactile` dan `.input-tactile`.
- [ ] Poligon populasi menggunakan palet 5 kelas terurut (`#3B82A0`, `#38A69A`, `#86A65A`, `#D4A34E`, `#D9784D`).
- [ ] Status penyakit menggunakan palet resmi Aman (`#20B88A`), Waspada (`#E8B84C`), Bahaya (`#DC625F`).
- [ ] Map instance Leaflet tetap utuh dan stabil saat beralih antara tema gelap dan terang.
- [ ] Tooltip Leaflet dan kontrol kanvas beradaptasi otomatis terhadap tema aktif melalui CSS variables.
- [ ] Bebas dari AI-slop: tanpa glow berlebih, tanpa live-dot dekoratif, tanpa pita tepi pada card.
- [ ] Chatbot AI berada di pojok kanan bawah terluar dan tidak menutupi kontrol kanvas peta.
- [ ] Navigasi keyboard dan focus ring terlihat jelas di seluruh elemen interaktif (WCAG 2.2 AA).
- [ ] Angka statistik dan koordinat menggunakan `tabular-nums` dengan format locale Indonesia.
- [ ] Suite pengujian otomatis (`php artisan test --filter=UiUxTransformationVerificationTest`) lulus tanpa regresi.

------------------------------------------------------------------------

## 16. Instruksi Prioritas untuk AI Coding Agent & Theme Persistence

Saat berkas ini dibaca oleh AI Coding Agent sebagai pedoman desain arsitektur antarmuka:

### 16.1 Theme Persistence Engine (`useAppearance.ts`)

Peralihan tema visual diatur secara terpusat oleh composable `resources/js/composables/useAppearance.ts` melalui arsitektur sinkronisasi tiga lapis (*Triple-Layer Synchronization*):

1. **Reactive State (Vue 3 Client):**
   - State reaktif `appearance` (`ref<Appearance>`) bernilai `'light' | 'dark' | 'system'` dengan computed property `resolvedAppearance`.
2. **Local Storage Persistence:**
   - Preferensi pengguna disimpan di browser melalui `localStorage.setItem('appearance', value)`.
3. **SSR / HTTP Cookie:**
   - Disimpan juga dalam cookie `appearance=${value};path=/;max-age=31536000;SameSite=Lax` agar backend Laravel dan template Blade dapat langsung menyuntikkan kelas `.dark` pada elemen `<html>` sebelum JavaScript selesai dieksekusi, mencegah fenomena kedipan putih (*Flash of Unstyled Content / FOUC*).
4. **System Preference Listener:**
   - Ketika opsi `'system'` dipilih, sistem memantau media query `window.matchMedia('(prefers-color-scheme: dark)')` secara dinamis.

Mekanisme DOM: Fungsi `updateTheme()` secara eksklusif memanipulasi keberadaan kelas `.dark` pada `document.documentElement`. Seluruh token CSS semantik di `app.css` dan utilitas Tailwind merespons kelas ini secara otomatis.

### 16.2 Instruksi Khusus Agent

1. **Jadikan dokumen ini pedoman tertinggi** untuk setiap pekerjaan styling, refactoring template, dan pembangunan komponen di TERNAKVERSE.
2. **Pertahankan kontrak nama file dan rute:** Dokumen ini bernama `design-rule.md` dan diakses publik melalui rute `/design-rule`.
3. **Pelihara integritas fungsional:** Dilarang memodifikasi logika bisnis, validasi request, skema basis data, atau kontrak otorisasi semata-mata untuk penyesuaian visual.
4. **Verifikasi hasil kerja:** Selalu jalankan `php artisan test --filter=UiUxTransformationVerificationTest` untuk memastikan seluruh kontrak pengujian regresi tetap berstatus hijau (PASS).

**Prinsip Akhir:** Hasil implementasi antarmuka harus memberikan pengalaman dashboard geospasial kelas dunia yang kokoh, berwibawa, responsif, dan sangat nyaman digunakan dalam durasi operasional panjang.
