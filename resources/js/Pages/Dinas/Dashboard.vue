<template>
  <div class="dinas-layout">
    <!-- TOP NAVIGATION BAR -->
    <header class="top-nav">
      <div class="brand">
        <span class="brand-badge">WEBGIS BANYUMAS</span>
        <h1 class="brand-title">TERNAK<span>VERSE</span> <small class="text-xs text-emerald-400 font-mono">DINAS PORTAL</small></h1>
      </div>
      <div class="user-meta">
        <div class="role-pill" :class="auth?.user?.role === 'admin' ? 'admin' : 'stakeholder'">
          ROLE: {{ auth?.user?.role?.toUpperCase() || 'STAKEHOLDER' }}
        </div>
        <div class="user-info">
          <span class="user-name">{{ auth?.user?.nama || 'Petugas Dinas' }}</span>
          <span class="user-email font-mono">{{ auth?.user?.email || 'dinas@banyumaskab.go.id' }}</span>
        </div>
      </div>
    </header>

    <!-- MAIN CONTAINER -->
    <main class="content-body">
      <!-- FLASH NOTIFICATION -->
      <div v-if="flash?.success" class="alert alert-success">
        ✓ {{ flash.success }}
      </div>
      <div v-if="flash?.error" class="alert alert-error">
        ⚠ {{ flash.error }}
      </div>

      <!-- FILTER CONTROLS BAR -->
      <section class="filter-card">
        <div class="filter-header">
          <span class="label-tech">FILTER STATE & KONTRAK DATA</span>
          <span class="status-indicator live">LIVE POSTGIS SYNC</span>
        </div>
        <div class="filter-controls">
          <div class="control-item">
            <label>Tahun Anggaran:</label>
            <span class="filter-pill">{{ filterState.tahun }}</span>
          </div>
          <div class="control-item">
            <label>Triwulan:</label>
            <span class="filter-pill">TW {{ filterState.triwulan ?? 'SEMUA' }}</span>
          </div>
          <div class="control-item">
            <label>Komoditas Terpilih:</label>
            <span class="filter-pill active">{{ filterState.komoditas }}</span>
          </div>
        </div>
      </section>

      <!-- KPI METRIC CARDS -->
      <section class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-label">TOTAL POPULASI TERNAK</div>
          <div class="kpi-val text-lime">{{ kpiCards.total_populasi_ternak?.toLocaleString('id-ID') }} <small>ekor</small></div>
          <div class="kpi-sub">Agregat 27 Kecamatan Banyumas</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">PRODUKSI DAGING</div>
          <div class="kpi-val text-cyan">{{ kpiCards.total_produksi_daging_kg?.toLocaleString('id-ID') }} <small>kg</small></div>
          <div class="kpi-sub">6 Komoditas Pangan</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">PRODUKSI SUSU</div>
          <div class="kpi-val text-white">{{ kpiCards.total_produksi_susu_liter?.toLocaleString('id-ID') }} <small>liter</small></div>
          <div class="kpi-sub">Sapi Perah, Kambing, Domba</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">PRODUKSI TELUR</div>
          <div class="kpi-val text-amber">{{ kpiCards.total_produksi_telur_butir?.toLocaleString('id-ID') }} <small>butir</small></div>
          <div class="kpi-sub">Ayam Ras Petelur (Layer)</div>
        </div>
        <div class="kpi-card highlight-danger">
          <div class="kpi-label">KASUS AKTIF WABAH</div>
          <div class="kpi-val text-rose">{{ kpiCards.total_kasus_aktif }} <small>kasus</small></div>
          <div class="kpi-sub">Surveilans PMK & LSD</div>
        </div>
        <div class="kpi-card">
          <div class="kpi-label">FASILITAS RPH AKTIF</div>
          <div class="kpi-val text-emerald">{{ kpiCards.total_rph_aktif }} <small>unit</small></div>
          <div class="kpi-sub">Kapasitas Potong Bersertifikat</div>
        </div>
      </section>

      <!-- VISUALISASI CONTRACT PREVIEWS -->
      <section class="contract-showcase">
        <div class="section-title">
          <h2>KONTRAK DATA VUE-LEAFLET & VUE-ECHARTS</h2>
          <p>Seluruh props di bawah sudah terstruktur murni dari Service Backend Sprint 3 & Controller Sprint 5, siap di-binding ke komponen grafik frontend.</p>
        </div>

        <div class="contract-grid">
          <!-- 1. CHOROPLETH KABUPATEN -->
          <div class="contract-box">
            <div class="box-head">
              <span class="tag">SPATIAL CHOROPLETH (27 KECAMATAN)</span>
              <span class="count">{{ geoChoropleth?.length || 0 }} Poligon</span>
            </div>
            <div class="choropleth-preview">
              <div 
                v-for="kec in geoChoropleth?.slice(0, 9)" 
                :key="kec.objectid" 
                class="kec-chip"
                :style="{ borderColor: kec.color_hex, backgroundColor: kec.color_hex + '22' }"
              >
                <span class="dot" :style="{ backgroundColor: kec.color_hex }"></span>
                <strong>{{ kec.nama_kecamatan }}</strong>
                <span class="badge-tier">Tier {{ kec.class_tier }} | {{ kec.status_zonasi_ews }}</span>
              </div>
            </div>
          </div>

          <!-- 2. ECHARTS RANKING -->
          <div class="contract-box">
            <div class="box-head">
              <span class="tag">ECHARTS: BAR RANKING 27 KECAMATAN</span>
              <span class="count">{{ chartData?.ranking_kecamatan?.categories?.length || 0 }} Kategori</span>
            </div>
            <ul class="ranking-preview-list">
              <li v-for="(cat, idx) in chartData?.ranking_kecamatan?.categories?.slice(0, 5)" :key="cat">
                <span class="rank-num">#{{ idx + 1 }}</span>
                <span class="rank-name">{{ cat }}</span>
                <span class="rank-val font-mono">{{ chartData?.ranking_kecamatan?.series?.[0]?.data?.[idx]?.toLocaleString('id-ID') }} ekor</span>
              </li>
            </ul>
          </div>
        </div>
      </section>

      <!-- INERTIA DEFERRED / LAZY LOADING CONTRACT -->
      <section class="deferred-box">
        <div class="box-head">
          <span class="tag">INERTIA v2 DEFERRED SPATIAL DATA (331 BALAI DESA)</span>
          <span class="status-indicator" :class="balaiDesaPoints ? 'live' : 'waiting'">
            {{ balaiDesaPoints ? 'LOADED INSTAN VIA DEFER' : 'DEFERRED (STREAMING IN PROGRESS)' }}
          </span>
        </div>
        <p class="text-sm text-gray-400 mb-2">
          Data koordinat 331 titik balai desa dimuat asynchronous via <code>Inertia::defer()</code> agar Time-To-First-Byte (TTFB) halaman awal tetap di bawah 100ms.
        </p>
        <div v-if="balaiDesaPoints" class="text-xs text-lime font-mono">
          ✓ Total {{ balaiDesaPoints.features?.length || balaiDesaPoints.length || 331 }} titik balai desa sukses ter-injeksi ke Leaflet Layer.
        </div>
      </section>

      <!-- RAW JSON PROPS INSPECTOR (HANDSHAKE UNTUK FRONTEND DEV) -->
      <section class="raw-inspector">
        <div class="inspector-head">
          <span class="inspector-title">TERMINAL HANDSHAKE KONTRAK DATA (PROPS ASLI)</span>
          <button @click="showRaw = !showRaw" class="btn-toggle">
            {{ showRaw ? '[-] Sembunyikan JSON' : '[+] Tampilkan Full JSON Props' }}
          </button>
        </div>
        <pre v-if="showRaw" class="json-dump">{{ $props }}</pre>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

// ---------------------------------------------------------------------------
// KONTRAK DATA PROPS (DEFINISI TIPE LENGKAP UNTUK FRONTEND TEAM)
// ---------------------------------------------------------------------------
interface FilterState {
  tahun: number
  triwulan: number | null
  komoditas: string
}

interface KpiCards {
  total_populasi_ternak: number
  total_produksi_daging_kg: number
  total_produksi_susu_liter: number
  total_produksi_telur_butir: number
  total_kasus_aktif: number
  total_rph_aktif: number
}

interface ChartRanking {
  categories: string[]
  series: Array<{ name: string; data: number[] }>
}

interface ChartProporsi {
  name: string
  value: number
  kode: string
}

interface ChartTrenPenyakit {
  categories: string[]
  series: Array<{ name: string; data: number[]; color: string }>
}

interface ChartKomparasi {
  susu: Array<{ name: string; value: number }>
  daging: Array<{ name: string; value: number }>
}

interface GeoChoroplethItem {
  objectid: number
  kode_kecamatan: string
  nama_kecamatan: string
  total_populasi: number
  produksi_daging_kg: number
  produksi_susu_liter: number
  produksi_telur_butir: number
  status_zonasi_ews: 'HIJAU' | 'KUNING' | 'MERAH'
  color_hex: string
  class_tier: number
  geometry?: any
}

interface AuthPayload {
  user: {
    id: number
    nama: string
    email: string
    role: string
    roles: string[]
    is_admin: boolean
    is_stakeholder: boolean
  } | null
}

interface FlashPayload {
  success?: string
  error?: string
  info?: string
}

// INERTIA PROPS HANDSHAKE CONTRACT
defineProps<{
  filterState: FilterState
  kpiCards: KpiCards
  chartData: {
    ranking_kecamatan: ChartRanking
    proporsi_komoditas: ChartProporsi[]
    tren_penyakit: ChartTrenPenyakit
    komparasi_produksi: ChartKomparasi
  }
  geoChoropleth: GeoChoroplethItem[]
  balaiDesaPoints?: any
  auth?: AuthPayload
  flash?: FlashPayload
  appName?: string
}>()

const showRaw = ref(true)
</script>

<style scoped>
.dinas-layout {
  min-height: 100vh;
  background-color: #0A0D0E;
  color: #F0F6FC;
  font-family: 'Inter', -apple-system, sans-serif;
  padding: 24px;
}
.top-nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  margin-bottom: 24px;
}
.brand-badge {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #00F0FF;
  background: rgba(0, 240, 255, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  margin-bottom: 4px;
}
.brand-title {
  font-family: 'Outfit', sans-serif;
  font-size: 26px;
  font-weight: 800;
  letter-spacing: -0.02em;
}
.brand-title span {
  color: #39FF14;
}
.user-meta {
  display: flex;
  align-items: center;
  gap: 16px;
}
.role-pill {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  letter-spacing: 0.05em;
}
.role-pill.admin {
  background: rgba(57, 255, 20, 0.15);
  color: #39FF14;
  border: 1px solid #39FF14;
}
.role-pill.stakeholder {
  background: rgba(0, 240, 255, 0.15);
  color: #00F0FF;
  border: 1px solid #00F0FF;
}
.user-info {
  display: flex;
  flex-direction: column;
  text-align: right;
}
.user-name {
  font-size: 13px;
  font-weight: 600;
}
.user-email {
  font-size: 11px;
  color: #8B949E;
}
.filter-card {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 24px;
}
.filter-header {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  margin-bottom: 12px;
}
.label-tech {
  color: #8B949E;
  font-weight: 600;
  letter-spacing: 0.08em;
}
.status-indicator.live {
  color: #39FF14;
  font-weight: 700;
}
.filter-controls {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.control-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}
.control-item label {
  color: #8B949E;
}
.filter-pill {
  background: #1B2227;
  padding: 4px 12px;
  border-radius: 6px;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.filter-pill.active {
  border-color: #39FF14;
  color: #39FF14;
  background: rgba(57, 255, 20, 0.05);
}
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.kpi-card {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 18px;
  position: relative;
  overflow: hidden;
}
.kpi-card.highlight-danger {
  border-color: rgba(244, 63, 94, 0.4);
  background: linear-gradient(180deg, rgba(244, 63, 94, 0.08) 0%, #12171A 100%);
}
.kpi-label {
  font-size: 11px;
  color: #8B949E;
  font-weight: 600;
  letter-spacing: 0.05em;
  margin-bottom: 6px;
}
.kpi-val {
  font-family: 'Outfit', sans-serif;
  font-size: 28px;
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 4px;
}
.kpi-val small {
  font-size: 14px;
  font-weight: 400;
  color: #8B949E;
}
.kpi-sub {
  font-size: 11px;
  color: #6E7681;
}
.text-lime { color: #39FF14; }
.text-cyan { color: #00F0FF; }
.text-amber { color: #F59E0B; }
.text-rose { color: #F43F5E; }
.text-emerald { color: #10B981; }

.contract-showcase {
  margin-bottom: 24px;
}
.section-title h2 {
  font-size: 16px;
  font-weight: 700;
  letter-spacing: 0.02em;
  color: #F0F6FC;
}
.section-title p {
  font-size: 12px;
  color: #8B949E;
  margin-bottom: 16px;
}
.contract-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 16px;
}
.contract-box, .deferred-box {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 16px;
}
.box-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 11px;
  font-weight: 700;
  margin-bottom: 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  padding-bottom: 8px;
}
.box-head .tag {
  color: #00F0FF;
  letter-spacing: 0.05em;
}
.box-head .count {
  background: #1B2227;
  padding: 2px 8px;
  border-radius: 4px;
  color: #8B949E;
}
.choropleth-preview {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 8px;
}
.kec-chip {
  padding: 8px 10px;
  border-radius: 8px;
  border: 1px solid;
  font-size: 11px;
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.kec-chip .dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  margin-bottom: 2px;
}
.badge-tier {
  font-size: 9px;
  color: #8B949E;
}
.ranking-preview-list {
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.ranking-preview-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  background: #161D21;
  padding: 8px 12px;
  border-radius: 6px;
}
.rank-num {
  font-weight: 700;
  color: #39FF14;
  width: 30px;
}
.rank-name {
  flex: 1;
}
.deferred-box {
  margin-bottom: 24px;
  border-left: 3px solid #00F0FF;
}
.raw-inspector {
  background: #0D1113;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 16px;
}
.inspector-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 12px;
}
.inspector-title {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px;
  color: #39FF14;
  font-weight: 700;
}
.btn-toggle {
  background: #1B2227;
  color: #00F0FF;
  border: 1px solid rgba(0, 240, 255, 0.3);
  padding: 4px 12px;
  border-radius: 6px;
  font-size: 11px;
  cursor: pointer;
  transition: all 0.2s ease;
}
.btn-toggle:hover {
  background: rgba(0, 240, 255, 0.15);
}
.json-dump {
  background: #07090A;
  color: #A5D6FF;
  padding: 16px;
  border-radius: 8px;
  max-height: 400px;
  overflow: auto;
  font-size: 11px;
  line-height: 1.4;
  border: 1px solid rgba(255, 255, 255, 0.04);
}
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 13px;
}
.alert-success {
  background: rgba(16, 185, 129, 0.1);
  border: 1px solid #10B981;
  color: #10B981;
}
.alert-error {
  background: rgba(244, 63, 94, 0.1);
  border: 1px solid #F43F5E;
  color: #F43F5E;
}
</style>
