<template>
  <div class="admin-layout">
    <!-- TOP NAVIGATION -->
    <header class="top-nav">
      <div class="brand">
        <span class="brand-badge admin">ADMIN GEOSPASIAL & RBAC</span>
        <h1 class="brand-title">TERNAK<span>VERSE</span> <small class="text-xs text-lime font-mono">PANEL KELOLA DATA</small></h1>
      </div>
      <div class="user-meta">
        <div class="role-pill admin">
          ROLE: {{ auth?.user?.role?.toUpperCase() || 'ADMIN' }}
        </div>
        <div class="user-info">
          <span class="user-name">{{ auth?.user?.nama || 'Administrator WebGIS' }}</span>
          <span class="user-email font-mono">{{ auth?.user?.email || 'admin@banyumaskab.go.id' }}</span>
        </div>
      </div>
    </header>

    <!-- MAIN BODY -->
    <main class="content-body">
      <!-- FLASH NOTIFICATIONS -->
      <div v-if="flash?.success" class="alert alert-success">
        ✓ {{ flash.success }}
      </div>
      <div v-if="flash?.error" class="alert alert-error">
        ⚠ {{ flash.error }}
      </div>

      <!-- MASTER OPTIONS OVERVIEW -->
      <section class="master-bar">
        <div class="master-item">
          <span class="m-label">KECAMATAN</span>
          <span class="m-val">{{ masterOptions?.kecamatan?.length || 27 }} Wilayah</span>
        </div>
        <div class="master-item">
          <span class="m-label">KOMODITAS</span>
          <span class="m-val">{{ masterOptions?.komoditas?.length || 6 }} Jenis</span>
        </div>
        <div class="master-item">
          <span class="m-label">KATEGORI PRODUKSI</span>
          <span class="m-val">{{ masterOptions?.kategori_produksi?.length || 3 }} Jenis</span>
        </div>
        <div class="master-item">
          <span class="m-label">PENYAKIT TEREGISTRASI</span>
          <span class="m-val">{{ masterOptions?.penyakit?.length || 2 }} Wabah</span>
        </div>
      </section>

      <!-- DYNAMIC BIOLOGICAL RELATIONS HANDSHAKE PREVIEW -->
      <section class="rules-card">
        <div class="rules-header">
          <span class="label-tech">KAMUS ATURAN BIOLOGIS & VALIDASI MODAL DINAMIS</span>
          <span class="badge-enforced">ACTIVE ENFORCEMENT</span>
        </div>
        <p class="rules-desc">
          Frontend developer dapat menggunakan kamus <code>allowedRelations</code> ini untuk memfilter opsi dropdown pada modal input tanpa perlu hardcode logic di frontend.
        </p>

        <div class="rules-grid">
          <!-- SUSU RULE -->
          <div class="rule-box">
            <div class="rule-title text-cyan">PRODUKSI SUSU</div>
            <div class="rule-compat">
              Komoditas Diizinkan:
              <div class="compat-tags">
                <span v-for="k in allowedRelations.susu" :key="k" class="compat-tag">{{ k }}</span>
              </div>
            </div>
            <div class="rule-note">Ayam Potong & Telur otomatis di-disable.</div>
          </div>

          <!-- TELUR RULE -->
          <div class="rule-box">
            <div class="rule-title text-amber">PRODUKSI TELUR</div>
            <div class="rule-compat">
              Komoditas Diizinkan:
              <div class="compat-tags">
                <span v-for="k in allowedRelations.telur" :key="k" class="compat-tag active-amber">{{ k }}</span>
              </div>
            </div>
            <div class="rule-note">Hanya berlaku untuk Ayam Ras Petelur.</div>
          </div>

          <!-- LSD RULE -->
          <div class="rule-box">
            <div class="rule-title text-rose">WABAH PENYAKIT LSD</div>
            <div class="rule-compat">
              Inang Rentan:
              <div class="compat-tags">
                <span v-for="k in allowedRelations.lsd" :key="k" class="compat-tag active-rose">{{ k }}</span>
              </div>
            </div>
            <div class="rule-note">Secara biologis HANYA menyerang sapi (Bovine).</div>
          </div>

          <!-- PMK RULE -->
          <div class="rule-box">
            <div class="rule-title text-lime">WABAH PENYAKIT PMK</div>
            <div class="rule-compat">
              Inang Rentan:
              <div class="compat-tags">
                <span v-for="k in allowedRelations.pmk" :key="k" class="compat-tag active-lime">{{ k }}</span>
              </div>
            </div>
            <div class="rule-note">Menyerang hewan berkuku belah (Artiodactyla).</div>
          </div>
        </div>
      </section>

      <!-- TABBED RESOURCE DATA TABLES -->
      <section class="tables-section">
        <div class="tab-header">
          <button 
            v-for="t in [
              { key: 'populasi', label: 'Populasi Ternak (' + (tables?.populasi?.total ?? 0) + ')' },
              { key: 'produksi', label: 'Hasil Produksi (' + (tables?.produksi?.total ?? 0) + ')' },
              { key: 'kasus_penyakit', label: 'Kasus Penyakit (' + (tables?.kasus_penyakit?.total ?? 0) + ')' },
              { key: 'fasilitas_rph', label: 'Fasilitas RPH (' + (tables?.fasilitas_rph?.total ?? 0) + ')' }
            ]" 
            :key="t.key"
            @click="activeTab = t.key"
            class="tab-btn"
            :class="{ active: activeTab === t.key }"
          >
            {{ t.label }}
          </button>
        </div>

        <div class="tab-content">
          <!-- TAB POPULASI -->
          <div v-if="activeTab === 'populasi'" class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>DESA / KECAMATAN</th>
                  <th>KOMODITAS</th>
                  <th>PERIODE</th>
                  <th>JANTAN</th>
                  <th>BETINA</th>
                  <th>TOTAL</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in tables.populasi?.data?.slice(0, 10)" :key="item.id">
                  <td>{{ item.desa?.nama_desa }} <small class="text-gray-500">({{ item.desa?.kecamatan?.nama_kecamatan }})</small></td>
                  <td><span class="badge-kod">{{ item.komoditas?.nama }}</span></td>
                  <td class="font-mono">{{ item.tahun }} TW{{ item.triwulan }}</td>
                  <td class="font-mono text-cyan">{{ item.jumlah_jantan?.toLocaleString('id-ID') }}</td>
                  <td class="font-mono text-amber">{{ item.jumlah_betina?.toLocaleString('id-ID') }}</td>
                  <td class="font-mono font-bold text-lime">{{ item.jumlah_total?.toLocaleString('id-ID') }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB PRODUKSI -->
          <div v-else-if="activeTab === 'produksi'" class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>DESA / KECAMATAN</th>
                  <th>KOMODITAS</th>
                  <th>KATEGORI</th>
                  <th>PERIODE</th>
                  <th>JUMLAH PRODUKSI</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in tables.produksi?.data?.slice(0, 10)" :key="item.id">
                  <td>{{ item.desa?.nama_desa }}</td>
                  <td>{{ item.komoditas?.nama }}</td>
                  <td><span class="badge-kod">{{ item.kategori_produksi?.nama }}</span></td>
                  <td class="font-mono">{{ item.tahun }} TW{{ item.triwulan }}</td>
                  <td class="font-mono font-bold text-lime">{{ item.jumlah_produksi }} {{ item.kategori_produksi?.satuan }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB KASUS -->
          <div v-else-if="activeTab === 'kasus_penyakit'" class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>TANGGAL</th>
                  <th>LOKASI</th>
                  <th>PENYAKIT</th>
                  <th>KOMODITAS</th>
                  <th>POSITIF</th>
                  <th>AKTIF</th>
                  <th>STATUS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in tables.kasus_penyakit?.data?.slice(0, 10)" :key="item.id">
                  <td class="font-mono text-xs">{{ item.tanggal_laporan }}</td>
                  <td>{{ item.desa?.nama_desa }}</td>
                  <td><span class="badge-danger">{{ item.penyakit?.nama }}</span></td>
                  <td>{{ item.komoditas?.nama }}</td>
                  <td class="font-mono text-rose">{{ item.kasus_positif }}</td>
                  <td class="font-mono font-bold text-rose">{{ item.kasus_aktif }}</td>
                  <td><span class="badge-status">{{ item.status_kasus || 'Terkonfirmasi' }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- TAB RPH -->
          <div v-else-if="activeTab === 'fasilitas_rph'" class="table-wrap">
            <table class="data-table">
              <thead>
                <tr>
                  <th>NAMA RPH</th>
                  <th>KECAMATAN</th>
                  <th>PENGELOLA</th>
                  <th>HEWAN POTONG</th>
                  <th>KAPASITAS</th>
                  <th>STATUS</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in tables.fasilitas_rph?.data?.slice(0, 10)" :key="item.id">
                  <td class="font-bold text-white">{{ item.nama_rph }}</td>
                  <td>{{ item.kecamatan?.nama_kecamatan }}</td>
                  <td>{{ item.jenis_pengelola }}</td>
                  <td>{{ item.jenis_hewan_potong }}</td>
                  <td class="font-mono">{{ item.kapasitas_potong_ekor_hari }} ekor/hari</td>
                  <td><span class="badge-success">{{ item.status_operasional }}</span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- RAW CONTRACT PROPS INSPECTION -->
      <section class="raw-inspector">
        <div class="inspector-head">
          <span class="inspector-title">TERMINAL HANDSHAKE PROPS PANEL ADMIN</span>
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
// KONTRAK DATA PROPS ADMIN (DEFINISI TIPE LENGKAP)
// ---------------------------------------------------------------------------
interface PaginatedData<T = any> {
  current_page: number
  data: T[]
  first_page_url: string
  from: number
  last_page: number
  last_page_url: string
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number
  total: number
}

interface AllowedRelations {
  susu: string[]
  telur: string[]
  daging: string[]
  pmk: string[]
  lsd: string[]
}

interface MasterOptions {
  kecamatan: Array<{ id: number; nama_kecamatan: string; kode_kecamatan: string }>
  desa?: Array<{ id: number; nama_desa: string; kecamatan_id: number }>
  komoditas: Array<{ id: number; nama: string; kode: string; kategori_hewan?: string }>
  kategori_produksi: Array<{ id: number; nama: string; satuan: string }>
  penyakit: Array<{ id: number; nama: string; kode: string; tingkat_bahaya?: string }>
  status_zonasi: string[]
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

// INERTIA PROPS CONTRACT
defineProps<{
  tables: {
    populasi: PaginatedData
    produksi: PaginatedData
    kasus_penyakit: PaginatedData
    fasilitas_rph: PaginatedData
  }
  allowedRelations: AllowedRelations
  masterOptions: MasterOptions
  auth?: AuthPayload
  flash?: FlashPayload
  appName?: string
}>()

const activeTab = ref('populasi')
const showRaw = ref(true)
</script>

<style scoped>
.admin-layout {
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
.brand-badge.admin {
  display: inline-block;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: 0.1em;
  color: #39FF14;
  background: rgba(57, 255, 20, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  margin-bottom: 4px;
}
.brand-title {
  font-family: 'Outfit', sans-serif;
  font-size: 26px;
  font-weight: 800;
}
.brand-title span { color: #39FF14; }
.user-meta { display: flex; align-items: center; gap: 16px; }
.role-pill.admin {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(57, 255, 20, 0.15);
  color: #39FF14;
  border: 1px solid #39FF14;
}
.user-info { display: flex; flex-direction: column; text-align: right; }
.user-name { font-size: 13px; font-weight: 600; }
.user-email { font-size: 11px; color: #8B949E; }

.master-bar {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}
.master-item {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 10px;
  padding: 14px 16px;
}
.m-label {
  display: block;
  font-size: 10px;
  font-weight: 700;
  color: #8B949E;
  letter-spacing: 0.08em;
  margin-bottom: 4px;
}
.m-val {
  font-family: 'Outfit', sans-serif;
  font-size: 20px;
  font-weight: 700;
  color: #00F0FF;
}

.rules-card {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
}
.rules-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}
.label-tech {
  font-size: 11px;
  color: #8B949E;
  font-weight: 700;
  letter-spacing: 0.08em;
}
.badge-enforced {
  font-size: 10px;
  font-weight: 700;
  color: #39FF14;
  background: rgba(57, 255, 20, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  border: 1px solid rgba(57, 255, 20, 0.3);
}
.rules-desc {
  font-size: 12px;
  color: #8B949E;
  margin-bottom: 16px;
}
.rules-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
}
.rule-box {
  background: #182024;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  padding: 14px;
}
.rule-title {
  font-size: 12px;
  font-weight: 700;
  margin-bottom: 8px;
}
.rule-compat {
  font-size: 11px;
  color: #8B949E;
  margin-bottom: 6px;
}
.compat-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}
.compat-tag {
  font-size: 10px;
  font-weight: 600;
  font-family: 'JetBrains Mono', monospace;
  background: #202A30;
  color: #F0F6FC;
  padding: 2px 6px;
  border-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.08);
}
.compat-tag.active-amber { border-color: #F59E0B; color: #F59E0B; background: rgba(245, 158, 11, 0.1); }
.compat-tag.active-rose { border-color: #F43F5E; color: #F43F5E; background: rgba(244, 63, 94, 0.1); }
.compat-tag.active-lime { border-color: #39FF14; color: #39FF14; background: rgba(57, 255, 20, 0.1); }
.rule-note {
  font-size: 10px;
  color: #6E7681;
}

.tables-section {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
}
.tab-header {
  display: flex;
  gap: 8px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 12px;
  margin-bottom: 16px;
  overflow-x: auto;
}
.tab-btn {
  background: transparent;
  color: #8B949E;
  border: 1px solid transparent;
  padding: 8px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}
.tab-btn:hover {
  color: #F0F6FC;
  background: #182024;
}
.tab-btn.active {
  background: rgba(57, 255, 20, 0.1);
  color: #39FF14;
  border-color: #39FF14;
}
.table-wrap {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.data-table th {
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  color: #8B949E;
  letter-spacing: 0.08em;
  padding: 10px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  background: #161D21;
}
.data-table td {
  padding: 12px 14px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}
.data-table tr:hover td {
  background: rgba(255, 255, 255, 0.02);
}
.badge-kod {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  background: #1B2227;
  color: #00F0FF;
  border: 1px solid rgba(0, 240, 255, 0.2);
}
.badge-danger {
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(244, 63, 94, 0.15);
  color: #F43F5E;
}
.badge-success {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 4px;
  background: rgba(16, 185, 129, 0.15);
  color: #10B981;
}
.badge-status {
  font-size: 11px;
  color: #8B949E;
}

.text-lime { color: #39FF14; }
.text-cyan { color: #00F0FF; }
.text-amber { color: #F59E0B; }
.text-rose { color: #F43F5E; }

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
}
.alert {
  padding: 12px 16px;
  border-radius: 8px;
  margin-bottom: 16px;
  font-size: 13px;
}
.alert-success { background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; color: #10B981; }
.alert-error { background: rgba(244, 63, 94, 0.1); border: 1px solid #F43F5E; color: #F43F5E; }
</style>
