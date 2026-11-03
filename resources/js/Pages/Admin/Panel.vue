<template>
  <div class="min-h-screen bg-[#FFFFFF] dark:bg-[#090909] text-slate-900 dark:text-[#F5F5F5] font-sans antialiased">
    <!-- ENTERPRISE ADMIN HEADER BAR -->
    <header class="sticky top-0 z-30 bg-white/95 dark:bg-[#111111]/95 backdrop-blur border-b border-slate-200 dark:border-[#1E1E1E] shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
            <ShieldCheck class="w-6 h-6" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold tracking-wider uppercase bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                ADMIN GEOSPASIAL &amp; RBAC
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-slate-100 dark:bg-[#181818] text-slate-600 dark:text-[#F5F5F5] border border-slate-200 dark:border-[#1E1E1E]">
                ROLE: {{ auth?.user?.role?.toUpperCase() || 'ADMIN' }}
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
              <span>TERNAK<span class="text-emerald-600 dark:text-emerald-400">VERSE</span></span>
              <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                PANEL KELOLA DATA
              </span>
            </h1>
          </div>
        </div>

        <!-- Navigation & User Meta -->
        <div class="flex flex-wrap items-center justify-between lg:justify-end gap-3">
          <nav class="flex flex-wrap items-center gap-1.5" aria-label="Navigasi Admin">
            <a
              href="/internal/admin"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-colors"
            >
              <LayoutDashboard class="w-3.5 h-3.5" />
              <span>Portal Operasional Admin</span>
            </a>
            <a
              href="/admin/audit-logs"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5] border border-slate-200 dark:border-[#1E1E1E] transition-colors"
            >
              <FileSpreadsheet class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" />
              <span>Audit Trail Forensik</span>
            </a>
            <a
              href="/dinas/dashboard"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5] border border-slate-200 dark:border-[#1E1E1E] transition-colors"
            >
              <Activity class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
              <span>Dashboard Dinas</span>
            </a>
            <a
              href="/"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5] border border-slate-200 dark:border-[#1E1E1E] transition-colors"
            >
              <Globe class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
              <span>WebGIS Publik</span>
            </a>
          </nav>

          <ThemeToggle size="sm" compact />

          <div class="pl-3 border-l border-slate-200 dark:border-[#1E1E1E] text-right hidden sm:block">
            <div class="text-xs font-bold text-slate-900 dark:text-slate-100">
              {{ auth?.user?.nama || 'Administrator WebGIS' }}
            </div>
            <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
              {{ auth?.user?.email || 'admin@banyumaskab.go.id' }}
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      <!-- FLASH NOTIFICATIONS -->
      <div
        v-if="flash?.success"
        class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium shadow-sm"
      >
        <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-500" />
        <span>{{ flash.success }}</span>
      </div>
      <div
        v-if="flash?.error"
        class="flex items-center gap-2.5 px-4 py-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm font-medium shadow-sm"
      >
        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-500" />
        <span>{{ flash.error }}</span>
      </div>

      <!-- 4 MASTER OPTIONS KPI SUMMARY CARDS -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex items-center justify-between">
          <div>
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
              KECAMATAN
            </span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-extrabold font-mono tabular-nums text-slate-900 dark:text-white">
                {{ masterOptions?.kecamatan?.length || 27 }}
              </span>
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Wilayah</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-lg bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
            <MapPin class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex items-center justify-between">
          <div>
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
              KOMODITAS
            </span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-extrabold font-mono tabular-nums text-slate-900 dark:text-white">
                {{ masterOptions?.komoditas?.length || 6 }}
              </span>
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Jenis</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-lg bg-teal-500/10 border border-teal-500/20 flex items-center justify-center text-teal-600 dark:text-teal-400">
            <Layers class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex items-center justify-between">
          <div>
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
              KATEGORI PRODUKSI
            </span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-extrabold font-mono tabular-nums text-slate-900 dark:text-white">
                {{ masterOptions?.kategori_produksi?.length || 3 }}
              </span>
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Jenis</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-lg bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400">
            <Database class="w-5 h-5" />
          </div>
        </div>

        <div class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex items-center justify-between">
          <div>
            <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1">
              PENYAKIT TEREGISTRASI
            </span>
            <div class="flex items-baseline gap-1.5">
              <span class="text-2xl font-extrabold font-mono tabular-nums text-rose-600 dark:text-rose-400">
                {{ masterOptions?.penyakit?.length || 2 }}
              </span>
              <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Wabah</span>
            </div>
          </div>
          <div class="w-10 h-10 rounded-lg bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-600 dark:text-rose-400">
            <AlertTriangle class="w-5 h-5" />
          </div>
        </div>
      </section>

      <!-- BIOLOGICAL VALIDATION RULE MATRIX (allowedRelations) -->
      <section class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2">
          <div class="flex items-center gap-2">
            <FlaskConical class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-200">
              KAMUS ATURAN BIOLOGIS &amp; VALIDASI MODAL DINAMIS
            </h2>
          </div>
          <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 w-fit">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            ACTIVE ENFORCEMENT
          </span>
        </div>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">
          Kamus <code class="font-mono text-emerald-600 dark:text-emerald-400 bg-slate-100 dark:bg-[#181818] px-1.5 py-0.5 rounded">allowedRelations</code> memvalidasi kecocokan komoditas ternak terhadap kategori hasil produksi dan kerentanan inang penyakit secara biologis.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- SUSU RULE -->
          <div class="bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-teal-600 dark:text-teal-400">PRODUKSI SUSU</span>
                <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                  {{ allowedRelations?.susu?.length || 0 }} Komoditas
                </span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                Komoditas Diizinkan:
              </div>
              <div class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="k in allowedRelations.susu"
                  :key="k"
                  class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/30"
                >
                  {{ k }}
                </span>
              </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/70 dark:border-[#1E1E1E]">
              Ayam Potong &amp; Telur otomatis di-disable.
            </div>
          </div>

          <!-- TELUR RULE -->
          <div class="bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-amber-600 dark:text-amber-400">PRODUKSI TELUR</span>
                <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                  {{ allowedRelations?.telur?.length || 0 }} Komoditas
                </span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                Komoditas Diizinkan:
              </div>
              <div class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="k in allowedRelations.telur"
                  :key="k"
                  class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/30"
                >
                  {{ k }}
                </span>
              </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/70 dark:border-[#1E1E1E]">
              Hanya berlaku untuk Ayam Ras Petelur.
            </div>
          </div>

          <!-- LSD RULE -->
          <div class="bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-rose-600 dark:text-rose-400">WABAH PENYAKIT LSD</span>
                <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/20">
                  {{ allowedRelations?.lsd?.length || 0 }} Inang
                </span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                Inang Rentan:
              </div>
              <div class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="k in allowedRelations.lsd"
                  :key="k"
                  class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-rose-500/10 text-rose-700 dark:text-rose-300 border border-rose-500/30"
                >
                  {{ k }}
                </span>
              </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/70 dark:border-[#1E1E1E]">
              Secara biologis HANYA menyerang sapi (Bovine).
            </div>
          </div>

          <!-- PMK RULE -->
          <div class="bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 flex flex-col justify-between">
            <div>
              <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">WABAH PENYAKIT PMK</span>
                <span class="text-[10px] font-mono font-semibold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                  {{ allowedRelations?.pmk?.length || 0 }} Inang
                </span>
              </div>
              <div class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                Inang Rentan:
              </div>
              <div class="flex flex-wrap gap-1.5 mb-3">
                <span
                  v-for="k in allowedRelations.pmk"
                  :key="k"
                  class="px-2 py-0.5 rounded text-[11px] font-mono font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/30"
                >
                  {{ k }}
                </span>
              </div>
            </div>
            <div class="text-[11px] text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-200/70 dark:border-[#1E1E1E]">
              Menyerang hewan berkuku belah (Artiodactyla).
            </div>
          </div>
        </div>
      </section>

      <!-- TABBED RESOURCE DATA TABLES -->
      <section class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-slate-200 dark:border-[#1E1E1E]">
          <!-- Tab Pills -->
          <div class="flex items-center gap-2 overflow-x-auto pb-1 lg:pb-0">
            <button
              v-for="t in tabDefinitions"
              :key="t.key"
              type="button"
              @click="activeTab = t.key"
              class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg text-xs font-bold whitespace-nowrap transition-colors border"
              :class="
                activeTab === t.key
                  ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/40'
                  : 'bg-slate-50 dark:bg-[#181818] text-slate-600 dark:text-[#B3B3B3] border-transparent hover:text-slate-900 dark:hover:text-[#F5F5F5] hover:bg-slate-100 dark:hover:bg-[#1E1E1E]'
              "
            >
              <span>{{ t.label }}</span>
              <span
                class="px-1.5 py-0.5 rounded text-[11px] font-mono tabular-nums"
                :class="
                  activeTab === t.key
                    ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300'
                    : 'bg-slate-200/70 dark:bg-[#1E1E1E] text-slate-600 dark:text-[#B3B3B3]'
                "
              >
                {{ t.total }}
              </span>
            </button>
          </div>

          <!-- Search Filter Input -->
          <div class="relative w-full lg:w-72">
            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari desa, kecamatan, komoditas..."
              class="w-full h-9 pl-9 pr-3 rounded-lg text-xs bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] text-slate-900 dark:text-[#F5F5F5] placeholder-slate-400 focus:outline-none focus:border-emerald-500"
            />
          </div>
        </div>

        <!-- TAB POPULASI -->
        <div v-if="activeTab === 'populasi'">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#1E1E1E]">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-100/80 dark:bg-[#181818] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-[#B3B3B3] border-b border-slate-200 dark:border-[#1E1E1E]">
                <tr>
                  <th class="py-3 px-4">DESA / KECAMATAN</th>
                  <th class="py-3 px-4">KOMODITAS</th>
                  <th class="py-3 px-4">PERIODE</th>
                  <th class="py-3 px-4 text-right">JANTAN</th>
                  <th class="py-3 px-4 text-right">BETINA</th>
                  <th class="py-3 px-4 text-right">TOTAL</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
                <tr
                  v-for="item in filteredPopulasi"
                  :key="item.id"
                  class="hover:bg-slate-50 dark:hover:bg-[#181818] transition-colors"
                >
                  <td class="py-3 px-4 font-semibold text-slate-900 dark:text-[#F5F5F5]">
                    {{ item.desa?.nama_desa || '-' }}
                    <span class="text-slate-500 dark:text-slate-400 font-normal ml-1">
                      ({{ item.desa?.kecamatan?.nama_kecamatan || '-' }})
                    </span>
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                      {{ item.komoditas?.nama || '-' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 font-mono tabular-nums text-slate-600 dark:text-slate-300">
                    {{ item.tahun }} TW{{ item.triwulan }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums text-teal-600 dark:text-teal-400">
                    {{ item.jumlah_jantan?.toLocaleString('id-ID') ?? 0 }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums text-amber-600 dark:text-amber-400">
                    {{ item.jumlah_betina?.toLocaleString('id-ID') ?? 0 }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-emerald-600 dark:text-emerald-400">
                    {{ item.jumlah_total?.toLocaleString('id-ID') ?? 0 }}
                  </td>
                </tr>
                <tr v-if="filteredPopulasi.length === 0">
                  <td colspan="6" class="py-8 text-center text-slate-500 dark:text-slate-400">
                    Tidak ada data populasi ternak yang cocok dengan filter pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <!-- Pagination Metadata -->
          <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-slate-400 font-mono tabular-nums">
            <span>
              Menampilkan {{ tables?.populasi?.from ?? 0 }}–{{ tables?.populasi?.to ?? 0 }} dari {{ tables?.populasi?.total ?? 0 }} rekaman
            </span>
            <span>
              Halaman {{ tables?.populasi?.current_page ?? 1 }} dari {{ tables?.populasi?.last_page ?? 1 }}
            </span>
          </div>
        </div>

        <!-- TAB PRODUKSI -->
        <div v-else-if="activeTab === 'produksi'">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#1E1E1E]">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-100/80 dark:bg-[#181818] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-[#B3B3B3] border-b border-slate-200 dark:border-[#1E1E1E]">
                <tr>
                  <th class="py-3 px-4">DESA / KECAMATAN</th>
                  <th class="py-3 px-4">KOMODITAS</th>
                  <th class="py-3 px-4">KATEGORI</th>
                  <th class="py-3 px-4">PERIODE</th>
                  <th class="py-3 px-4 text-right">JUMLAH PRODUKSI</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
                <tr
                  v-for="item in filteredProduksi"
                  :key="item.id"
                  class="hover:bg-slate-50 dark:hover:bg-[#181818] transition-colors"
                >
                  <td class="py-3 px-4 font-semibold text-slate-900 dark:text-[#F5F5F5]">
                    {{ item.desa?.nama_desa || '-' }}
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-[#B3B3B3]">
                    {{ item.komoditas?.nama || '-' }}
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                      {{ item.kategori_produksi?.nama || '-' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 font-mono tabular-nums text-slate-600 dark:text-[#B3B3B3]">
                    {{ item.tahun }} TW{{ item.triwulan }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-emerald-600 dark:text-emerald-400">
                    {{ item.jumlah_produksi }} {{ item.kategori_produksi?.satuan }}
                  </td>
                </tr>
                <tr v-if="filteredProduksi.length === 0">
                  <td colspan="5" class="py-8 text-center text-slate-500 dark:text-[#B3B3B3]">
                    Tidak ada data hasil produksi yang cocok dengan filter pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-[#B3B3B3] font-mono tabular-nums">
            <span>
              Menampilkan {{ tables?.produksi?.from ?? 0 }}–{{ tables?.produksi?.to ?? 0 }} dari {{ tables?.produksi?.total ?? 0 }} rekaman
            </span>
            <span>
              Halaman {{ tables?.produksi?.current_page ?? 1 }} dari {{ tables?.produksi?.last_page ?? 1 }}
            </span>
          </div>
        </div>

        <!-- TAB KASUS PENYAKIT -->
        <div v-else-if="activeTab === 'kasus_penyakit'">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#1E1E1E]">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-100/80 dark:bg-[#181818] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-[#B3B3B3] border-b border-slate-200 dark:border-[#1E1E1E]">
                <tr>
                  <th class="py-3 px-4">TANGGAL</th>
                  <th class="py-3 px-4">LOKASI</th>
                  <th class="py-3 px-4">PENYAKIT</th>
                  <th class="py-3 px-4">KOMODITAS</th>
                  <th class="py-3 px-4 text-right">POSITIF</th>
                  <th class="py-3 px-4 text-right">AKTIF</th>
                  <th class="py-3 px-4">STATUS</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
                <tr
                  v-for="item in filteredKasus"
                  :key="item.id"
                  class="hover:bg-slate-50 dark:hover:bg-[#181818] transition-colors"
                >
                  <td class="py-3 px-4 font-mono tabular-nums text-slate-600 dark:text-[#B3B3B3]">
                    {{ item.tanggal_laporan }}
                  </td>
                  <td class="py-3 px-4 font-semibold text-slate-900 dark:text-[#F5F5F5]">
                    {{ item.desa?.nama_desa || '-' }}
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-rose-500/10 text-rose-700 dark:text-rose-400 border border-rose-500/20">
                      {{ item.penyakit?.nama || '-' }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-[#B3B3B3]">
                    {{ item.komoditas?.nama || '-' }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums text-rose-600 dark:text-rose-400">
                    {{ item.kasus_positif }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums font-bold text-rose-600 dark:text-rose-400">
                    {{ item.kasus_aktif }}
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                      {{ item.status_kasus || 'Terkonfirmasi' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="filteredKasus.length === 0">
                  <td colspan="7" class="py-8 text-center text-slate-500 dark:text-[#B3B3B3]">
                    Tidak ada rekaman kasus penyakit yang cocok dengan filter pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-[#B3B3B3] font-mono tabular-nums">
            <span>
              Menampilkan {{ tables?.kasus_penyakit?.from ?? 0 }}–{{ tables?.kasus_penyakit?.to ?? 0 }} dari {{ tables?.kasus_penyakit?.total ?? 0 }} rekaman
            </span>
            <span>
              Halaman {{ tables?.kasus_penyakit?.current_page ?? 1 }} dari {{ tables?.kasus_penyakit?.last_page ?? 1 }}
            </span>
          </div>
        </div>

        <!-- TAB FASILITAS RPH -->
        <div v-else-if="activeTab === 'fasilitas_rph'">
          <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#1E1E1E]">
            <table class="w-full text-left border-collapse text-xs">
              <thead class="bg-slate-100/80 dark:bg-[#181818] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-[#B3B3B3] border-b border-slate-200 dark:border-[#1E1E1E]">
                <tr>
                  <th class="py-3 px-4">NAMA RPH</th>
                  <th class="py-3 px-4">KECAMATAN</th>
                  <th class="py-3 px-4">PENGELOLA</th>
                  <th class="py-3 px-4">HEWAN POTONG</th>
                  <th class="py-3 px-4 text-right">KAPASITAS</th>
                  <th class="py-3 px-4">STATUS</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
                <tr
                  v-for="item in filteredRph"
                  :key="item.id"
                  class="hover:bg-slate-50 dark:hover:bg-[#181818] transition-colors"
                >
                  <td class="py-3 px-4 font-bold text-slate-900 dark:text-white">
                    {{ item.nama_rph }}
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-[#B3B3B3]">
                    {{ item.kecamatan?.nama_kecamatan || '-' }}
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-[#B3B3B3]">
                    {{ item.jenis_pengelola }}
                  </td>
                  <td class="py-3 px-4 text-slate-700 dark:text-[#B3B3B3]">
                    {{ item.jenis_hewan_potong }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono tabular-nums text-teal-600 dark:text-teal-400 font-semibold">
                    {{ item.kapasitas_potong_ekor_hari }} ekor/hari
                  </td>
                  <td class="py-3 px-4">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                      {{ item.status_operasional }}
                    </span>
                  </td>
                </tr>
                <tr v-if="filteredRph.length === 0">
                  <td colspan="6" class="py-8 text-center text-slate-500 dark:text-[#B3B3B3]">
                    Tidak ada fasilitas RPH yang cocok dengan filter pencarian.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="mt-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-slate-500 dark:text-[#B3B3B3] font-mono tabular-nums">
            <span>
              Menampilkan {{ tables?.fasilitas_rph?.from ?? 0 }}–{{ tables?.fasilitas_rph?.to ?? 0 }} dari {{ tables?.fasilitas_rph?.total ?? 0 }} rekaman
            </span>
            <span>
              Halaman {{ tables?.fasilitas_rph?.current_page ?? 1 }} dari {{ tables?.fasilitas_rph?.last_page ?? 1 }}
            </span>
          </div>
        </div>
      </section>

      <!-- RAW CONTRACT PROPS INSPECTION (COLLAPSED BY DEFAULT) -->
      <section class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Terminal class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-200">
              TERMINAL HANDSHAKE PROPS PANEL ADMIN
            </span>
          </div>
          <button
            type="button"
            @click="showRaw = !showRaw"
            class="px-3 py-1.5 rounded-lg text-xs font-mono font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] text-teal-700 dark:text-teal-300 border border-slate-200 dark:border-[#1E1E1E] transition-colors"
          >
            {{ showRaw ? '[-] Sembunyikan JSON' : '[+] Tampilkan Full JSON Props' }}
          </button>
        </div>
        <pre
          v-if="showRaw"
          class="mt-3 p-4 rounded-lg bg-[#090909] text-sky-300 font-mono text-[11px] leading-relaxed max-h-96 overflow-auto border border-[#1E1E1E]"
        >{{ $props }}</pre>
      </section>
    </main>

    <!-- Chatbot Drawer FAB -->
    <ChatbotDrawer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue'
import ChatbotDrawer from '@/components/ternakverse/ChatbotDrawer.vue'
import {
  ShieldCheck,
  LayoutDashboard,
  FileSpreadsheet,
  Activity,
  Globe,
  CheckCircle2,
  AlertTriangle,
  MapPin,
  Layers,
  Database,
  FlaskConical,
  Search,
  Terminal,
} from 'lucide-vue-next'

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
const props = defineProps<{
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

const activeTab = ref<'populasi' | 'produksi' | 'kasus_penyakit' | 'fasilitas_rph'>('populasi')
const searchQuery = ref('')
const showRaw = ref(false)

const tabDefinitions = computed(() => [
  { key: 'populasi' as const, label: 'Populasi Ternak', total: props.tables?.populasi?.total ?? 0 },
  { key: 'produksi' as const, label: 'Hasil Produksi', total: props.tables?.produksi?.total ?? 0 },
  { key: 'kasus_penyakit' as const, label: 'Kasus Penyakit', total: props.tables?.kasus_penyakit?.total ?? 0 },
  { key: 'fasilitas_rph' as const, label: 'Fasilitas RPH', total: props.tables?.fasilitas_rph?.total ?? 0 },
])

const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase())

const filteredPopulasi = computed(() => {
  const rows = props.tables?.populasi?.data?.slice(0, 10) ?? []
  if (!normalizedQuery.value) return rows
  return rows.filter((item: any) => {
    const desa = item.desa?.nama_desa?.toLowerCase() ?? ''
    const kec = item.desa?.kecamatan?.nama_kecamatan?.toLowerCase() ?? ''
    const kom = item.komoditas?.nama?.toLowerCase() ?? ''
    return desa.includes(normalizedQuery.value) || kec.includes(normalizedQuery.value) || kom.includes(normalizedQuery.value)
  })
})

const filteredProduksi = computed(() => {
  const rows = props.tables?.produksi?.data?.slice(0, 10) ?? []
  if (!normalizedQuery.value) return rows
  return rows.filter((item: any) => {
    const desa = item.desa?.nama_desa?.toLowerCase() ?? ''
    const kom = item.komoditas?.nama?.toLowerCase() ?? ''
    const kat = item.kategori_produksi?.nama?.toLowerCase() ?? ''
    return desa.includes(normalizedQuery.value) || kom.includes(normalizedQuery.value) || kat.includes(normalizedQuery.value)
  })
})

const filteredKasus = computed(() => {
  const rows = props.tables?.kasus_penyakit?.data?.slice(0, 10) ?? []
  if (!normalizedQuery.value) return rows
  return rows.filter((item: any) => {
    const desa = item.desa?.nama_desa?.toLowerCase() ?? ''
    const penyakit = item.penyakit?.nama?.toLowerCase() ?? ''
    const kom = item.komoditas?.nama?.toLowerCase() ?? ''
    return desa.includes(normalizedQuery.value) || penyakit.includes(normalizedQuery.value) || kom.includes(normalizedQuery.value)
  })
})

const filteredRph = computed(() => {
  const rows = props.tables?.fasilitas_rph?.data?.slice(0, 10) ?? []
  if (!normalizedQuery.value) return rows
  return rows.filter((item: any) => {
    const nama = item.nama_rph?.toLowerCase() ?? ''
    const kec = item.kecamatan?.nama_kecamatan?.toLowerCase() ?? ''
    const hewan = item.jenis_hewan_potong?.toLowerCase() ?? ''
    return nama.includes(normalizedQuery.value) || kec.includes(normalizedQuery.value) || hewan.includes(normalizedQuery.value)
  })
})
</script>

