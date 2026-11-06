<template>
  <Head title="Dashboard Eksekutif Dinas - Ternakverse Banyumas" />

  <div class="min-h-screen bg-slate-50 dark:bg-[#090909] text-slate-900 dark:text-[#F5F5F5] font-ui antialiased selection:bg-emerald-500 selection:text-slate-950 flex flex-col">
    <!-- ENTERPRISE AGRITECH DINAS HEADER BAR -->
    <header class="h-16 w-full bg-white/95 dark:bg-[#111111]/95 backdrop-blur-md border-b border-[#E0E4DD] dark:border-[#1E1E1E] px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-40">
      <div class="flex items-center gap-3 min-w-0">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0 shadow-sm">
          <Activity class="w-5 h-5" />
        </div>
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <span class="font-display font-bold text-base sm:text-lg tracking-tight text-slate-900 dark:text-[#F5F5F5] truncate">
              TERNAK<span class="text-emerald-600 dark:text-emerald-400">VERSE</span>
            </span>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30 uppercase tracking-wider shrink-0">
              EKSEKUTIF DINAS
            </span>
          </div>
          <p class="text-xs text-slate-500 dark:text-[#B3B3B3] hidden sm:block leading-none mt-1 truncate">
            Analitik Spasial Peternakan &amp; Kewaspadaan Dini Wabah Kabupaten Banyumas
          </p>
        </div>
      </div>

      <!-- Navigation Links & User Profile -->
      <div class="flex items-center gap-2 sm:gap-3">
        <nav class="hidden md:flex items-center gap-1.5 bg-slate-100 dark:bg-[#181818] p-1 rounded-xl border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs">
          <Link href="/internal/dinas" class="px-3 py-1.5 rounded-lg font-semibold text-slate-700 dark:text-[#B3B3B3] hover:text-emerald-600 dark:hover:text-[#F5F5F5] hover:bg-white dark:hover:bg-[#111111] transition-colors flex items-center gap-1.5">
            <Map class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
            <span>Peta Interaktif</span>
          </Link>
          <Link
            v-if="isAdmin"
            href="/internal/admin"
            class="px-3 py-1.5 rounded-lg font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500/20 transition-colors flex items-center gap-1.5 cursor-pointer"
            title="Kembali ke Dashboard Super Admin"
          >
            <Database class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
            <span>&larr; Kembali ke Panel Admin</span>
          </Link>
        </nav>

        <div class="flex items-center gap-2.5 pl-2 border-l border-[#E0E4DD] dark:border-[#1E1E1E]">
          <ThemeToggle size="sm" compact />
          <span
            class="text-[10px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider border"
            :class="isAdmin ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30' : 'bg-teal-500/15 text-teal-700 dark:text-teal-300 border-teal-500/30'"
          >
            {{ isAdmin ? 'SUPER ADMIN' : (auth?.user?.role?.toUpperCase() || 'STAKEHOLDER') }}
          </span>
          <div class="hidden sm:flex flex-col text-right leading-tight">
            <span class="text-xs font-semibold text-slate-800 dark:text-[#F5F5F5]">{{ currentUserName }}</span>
            <span class="text-[11px] font-mono text-slate-500 dark:text-[#858585]">{{ currentUserEmail }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN BENTO CONTAINER -->
    <main class="max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6 flex-1">
      <!-- FLASH NOTIFICATIONS -->
      <div v-if="flash?.success" class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-300 text-sm font-medium flex items-center gap-2.5 shadow-sm">
        <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-500" />
        <span>{{ flash.success }}</span>
      </div>
      <div v-if="flash?.error" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-700 dark:text-rose-300 text-sm font-medium flex items-center gap-2.5 shadow-sm">
        <AlertTriangle class="w-4 h-4 shrink-0 text-rose-500" />
        <span>{{ flash.error }}</span>
      </div>
      <div v-if="flash?.info" class="p-4 rounded-xl bg-teal-500/10 border border-teal-500/30 text-teal-700 dark:text-teal-300 text-sm font-medium flex items-center gap-2.5 shadow-sm">
        <Info class="w-4 h-4 shrink-0 text-teal-500" />
        <span>{{ flash.info }}</span>
      </div>

      <!-- ROW 1: FILTER & SYNC BAR -->
      <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 sm:p-5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <Filter class="w-4 h-4 text-emerald-600 dark:text-[#00D084]" />
              <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Filter Analitik:</span>
            </div>

            <!-- Tahun Anggaran Selector -->
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-[#181818] px-2.5 py-1.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs">
              <span class="text-slate-500 dark:text-[#B3B3B3] font-medium">Tahun:</span>
              <select
                :value="filterState.tahun"
                @change="updateFilter('tahun', Number(($event.target as HTMLSelectElement).value))"
                aria-label="Pilih Tahun Anggaran"
                class="bg-transparent font-mono tabular-nums font-bold text-slate-900 dark:text-[#F5F5F5] focus:outline-none cursor-pointer"
              >
                <option v-for="yr in [2024, 2025, 2026]" :key="yr" :value="yr" class="bg-white dark:bg-[#181818] dark:text-[#F5F5F5]">{{ yr }}</option>
              </select>
            </div>

            <!-- Triwulan Selector -->
            <div class="flex items-center gap-1 bg-slate-100 dark:bg-[#181818] p-1 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs">
              <span class="px-2 text-slate-500 dark:text-[#B3B3B3] font-medium">Triwulan:</span>
              <button
                v-for="tw in [null, 1, 2, 3, 4]"
                :key="tw ?? 'all'"
                type="button"
                @click="updateFilter('triwulan', tw)"
                class="px-2 py-1 rounded-md font-mono tabular-nums font-semibold transition-colors cursor-pointer"
                :class="filterState.triwulan === tw ? 'bg-[#171B17] text-white font-bold shadow-xs dark:bg-[#181818] dark:text-[#00D084]' : 'text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]'"
              >
                {{ tw === null ? 'SEMUA' : `TW ${tw}` }}
              </button>
            </div>

            <!-- Komoditas Selector -->
            <div class="flex items-center gap-1.5 bg-slate-100 dark:bg-[#181818] px-2.5 py-1.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs">
              <span class="text-slate-500 dark:text-[#B3B3B3] font-medium">Komoditas:</span>
              <select
                :value="filterState.komoditas"
                @change="updateFilter('komoditas', ($event.target as HTMLSelectElement).value)"
                aria-label="Pilih Komoditas Ternak"
                class="bg-transparent font-semibold text-emerald-700 dark:text-[#00D084] focus:outline-none cursor-pointer"
              >
                <option v-for="kom in commodityOptions" :key="kom.kode" :value="kom.kode" class="bg-white dark:bg-[#181818] text-slate-900 dark:text-[#F5F5F5]">
                  {{ kom.label }}
                </option>
              </select>
            </div>
          </div>

          <!-- Right: Live PostGIS Sync, Deferred Balai Desa Status & Export CSV -->
          <div class="flex flex-wrap items-center gap-2.5">
            <button
              type="button"
              @click="exportRingkasanAnalitikCsv"
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors shadow-xs cursor-pointer"
              title="Unduh ringkasan KPI dan pengaturan filter dalam format CSV"
            >
              <FileSpreadsheet class="w-3.5 h-3.5" />
              <span>Ekspor Ringkasan Analitik (CSV)</span>
            </button>

            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              <span>LIVE POSTGIS SYNC</span>
            </div>

            <div v-if="balaiDesaPoints" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-teal-500/10 border border-teal-500/30 text-teal-700 dark:text-teal-300 text-xs font-semibold">
              <CheckCircle2 class="w-3.5 h-3.5 text-teal-500 shrink-0" />
              <span>Deferred Spasial: <strong class="font-mono tabular-nums">{{ balaiDesaCount.toLocaleString('id-ID') }}</strong> Titik Balai Desa Aktif</span>
            </div>
            <div v-else class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-300 text-xs font-semibold">
              <Loader2 class="w-3.5 h-3.5 animate-spin text-amber-500 shrink-0" />
              <span>Streaming 331 Titik Balai Desa (Inertia Defer)...</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ZERO STATE NOTICE -->
      <div
        v-if="(kpiCards?.total_populasi_ternak ?? 0) === 0"
        class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-700 dark:text-amber-400 text-xs font-medium"
      >
        <Info class="w-4 h-4 shrink-0 text-amber-500" />
        <span>
          Belum ada pencatatan survei ternak untuk periode <strong>Tahun {{ filterState.tahun }}{{ filterState.triwulan ? ` Triwulan ${filterState.triwulan}` : '' }}</strong>. Menampilkan data aktual (0 data).
        </span>
      </div>

      <!-- ROW 2: 6 KPI BENTO CARDS -->
      <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <!-- 1. Total Populasi Ternak -->
        <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Total Populasi Ternak</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-slate-700 dark:text-[#F5F5F5]">
              <Layers class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-[#F5F5F5]">
              {{ (kpiCards?.total_populasi_ternak ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">ekor</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Agregat 27 Kecamatan Banyumas</p>
          </div>
        </div>

        <!-- 2. Produksi Daging -->
        <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Produksi Daging</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-slate-700 dark:text-[#F5F5F5]">
              <TrendingUp class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-[#F5F5F5]">
              {{ (kpiCards?.total_produksi_daging_kg ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">kg</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Akumulasi 6 Komoditas Pangan</p>
          </div>
        </div>

        <!-- 3. Produksi Susu -->
        <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Produksi Susu Segar</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-slate-700 dark:text-[#F5F5F5]">
              <Droplets class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-[#F5F5F5]">
              {{ (kpiCards?.total_produksi_susu_liter ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">liter</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Sapi Perah, Kambing &amp; Domba</p>
          </div>
        </div>

        <!-- 4. Produksi Telur -->
        <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Produksi Telur</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-slate-700 dark:text-[#F5F5F5]">
              <BarChart3 class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-[#F5F5F5]">
              {{ (kpiCards?.total_produksi_telur_butir ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">butir</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Ayam Ras Petelur (Layer)</p>
          </div>
        </div>

        <!-- 5. Kasus Aktif Wabah (Semantic Rose #E11D48 when > 0, Neutral when 0) -->
        <div
          class="rounded-xl p-4 shadow-sm flex flex-col justify-between border transition-colors"
          :class="(kpiCards?.total_kasus_aktif ?? 0) > 0 ? 'bg-rose-500/5 dark:bg-rose-950/25 border-rose-500/40' : 'bg-white dark:bg-[#111111] border-[#E0E4DD] dark:border-[#1E1E1E]'"
        >
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal" :class="(kpiCards?.total_kasus_aktif ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-500 dark:text-[#B3B3B3]'">
              Kasus Aktif Wabah
            </span>
            <span
              class="w-8 h-8 rounded-lg border flex items-center justify-center"
              :class="(kpiCards?.total_kasus_aktif ?? 0) > 0 ? 'bg-rose-500/15 border-rose-500/30 text-rose-600 dark:text-rose-400' : 'bg-slate-100 dark:bg-[#181818] border-[#E0E4DD] dark:border-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5]'"
            >
              <ShieldAlert class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div
              class="text-2xl font-bold font-mono tabular-nums"
              :class="(kpiCards?.total_kasus_aktif ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-[#F5F5F5]'"
            >
              {{ (kpiCards?.total_kasus_aktif ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">kasus</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Surveilans Aktif PMK &amp; LSD</p>
          </div>
        </div>

        <!-- 6. Fasilitas RPH Aktif -->
        <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between gap-2 mb-2">
            <span class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3]">Fasilitas RPH Aktif</span>
            <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] flex items-center justify-center text-slate-700 dark:text-[#F5F5F5]">
              <Building2 class="w-4 h-4" />
            </span>
          </div>
          <div>
            <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-[#F5F5F5]">
              {{ (kpiCards?.total_rph_aktif ?? 0).toLocaleString('id-ID') }}
              <span class="text-xs font-normal font-ui text-slate-500 dark:text-[#B3B3B3]">unit</span>
            </div>
            <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-1">Kapasitas Potong Bersertifikat</p>
          </div>
        </div>
      </section>

      <!-- ROW 3: SPATIAL & ANALYTICAL BENTO SPLIT (8 : 4) -->
      <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- LEFT 8 COLUMNS: Spatial Choropleth Matrix + Disease Trends & Production Comparison -->
        <div class="lg:col-span-8 space-y-6">
          <!-- Card 3A: Spatial Choropleth Overview of 27 Kecamatan -->
          <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
              <div>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-semibold tracking-normal text-emerald-600 dark:text-[#00D084]">Zonasi Spasial Choropleth</span>
                  <span class="text-[11px] font-mono tabular-nums px-2 py-0.5 rounded-md bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-slate-600 dark:text-[#B3B3B3]">
                    {{ geoChoropleth?.length || 0 }} Kecamatan
                  </span>
                </div>
                <h2 class="font-display font-bold text-base sm:text-lg text-slate-900 dark:text-[#F5F5F5] mt-0.5">
                  Matriks Kuantil Populasi &amp; Status Zonasi EWS ({{ formatKomoditasLabel(filterState.komoditas) }})
                </h2>
              </div>

              <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto flex-wrap">
                <button
                  type="button"
                  @click="exportChoroplethMatrixCsv"
                  class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5] text-xs font-semibold transition-colors cursor-pointer"
                  title="Ekspor matriks spasial kecamatan ke CSV"
                >
                  <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600 dark:text-[#00D084]" />
                  <span>Ekspor Matriks CSV</span>
                </button>

                <Link href="/internal/dinas" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition-colors shadow-xs">
                  <Map class="w-4 h-4" />
                  <span>Buka WebGIS Interaktif</span>
                  <ArrowUpRight class="w-3.5 h-3.5" />
                </Link>
              </div>
            </div>

            <!-- Search & EWS Status Filter Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 my-4">
              <div class="relative flex-1 max-w-sm">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                <input
                  v-model="searchKecamatan"
                  type="text"
                  placeholder="Cari nama atau kode kecamatan..."
                  aria-label="Cari nama atau kode kecamatan"
                  class="w-full pl-9 pr-3 py-1.5 rounded-lg bg-slate-100 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs text-slate-900 dark:text-[#F5F5F5] placeholder:text-slate-400 dark:placeholder:text-[#858585] focus:outline-none focus:border-emerald-500"
                />
              </div>

              <div class="flex items-center gap-1.5 text-xs flex-wrap">
                <button
                  v-for="zone in (['SEMUA', 'HIJAU', 'KUNING', 'MERAH'] as const)"
                  :key="zone"
                  type="button"
                  @click="selectedZoneFilter = zone"
                  class="px-2.5 py-1 rounded-lg font-semibold border transition-colors cursor-pointer"
                  :class="selectedZoneFilter === zone ? 'bg-[#171B17] text-white border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#1E1E1E]' : 'bg-white text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] border border-[#E0E4DD] dark:bg-[#111111] dark:text-[#B3B3B3] dark:border-[#1E1E1E] dark:hover:text-[#F5F5F5] dark:hover:bg-[#181818]'"
                >
                  {{ zone }}
                </button>
              </div>
            </div>

            <!-- Choropleth Tabular / Card Grid -->
            <div class="overflow-x-auto max-h-96 overflow-y-auto rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E]">
              <table class="w-full text-left border-collapse text-xs">
                <thead class="bg-slate-100 dark:bg-[#181818] text-slate-500 dark:text-[#B3B3B3] sticky top-0 z-10 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                  <tr>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal">Kecamatan</th>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal">Kuantil</th>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal">Zonasi EWS</th>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal text-right">Populasi</th>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal text-right hidden md:table-cell">Daging (kg)</th>
                    <th class="py-2.5 px-3 text-xs font-semibold tracking-normal text-right hidden xl:table-cell">Susu (L)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
                  <tr v-for="kec in filteredChoropleth" :key="kec.objectid" class="hover:bg-slate-50 dark:hover:bg-[#181818]/60 transition-colors">
                    <td class="py-2.5 px-3">
                      <div class="flex items-center gap-2.5">
                        <span class="w-3 h-3 rounded-sm shrink-0 border border-white/20" :style="{ backgroundColor: kec.color_hex }"></span>
                        <div>
                          <div class="font-semibold text-slate-900 dark:text-[#F5F5F5]">{{ kec.nama_kecamatan }}</div>
                          <div class="text-[10px] font-mono tabular-nums text-slate-500 dark:text-[#858585]">Kode: {{ kec.kode_kecamatan }}</div>
                        </div>
                      </div>
                    </td>
                    <td class="py-2.5 px-3">
                      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-mono tabular-nums font-semibold border" :style="{ borderColor: kec.color_hex, backgroundColor: kec.color_hex + '1A' }">
                        Tier {{ kec.class_tier }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3">
                      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border" :class="getEwsBadgeClass(kec.status_zonasi_ews)">
                        <span class="w-1.5 h-1.5 rounded-full" :class="getEwsDotClass(kec.status_zonasi_ews)"></span>
                        {{ kec.status_zonasi_ews }}
                      </span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-mono tabular-nums font-semibold text-slate-900 dark:text-[#F5F5F5]">{{ (kec.total_populasi ?? 0).toLocaleString('id-ID') }}</td>
                    <td class="py-2.5 px-3 text-right font-mono tabular-nums text-slate-600 dark:text-[#B3B3B3] hidden md:table-cell">{{ (kec.produksi_daging_kg ?? 0).toLocaleString('id-ID') }}</td>
                    <td class="py-2.5 px-3 text-right font-mono tabular-nums text-slate-600 dark:text-[#B3B3B3] hidden xl:table-cell">{{ (kec.produksi_susu_liter ?? 0).toLocaleString('id-ID') }}</td>
                  </tr>
                  <tr v-if="filteredChoropleth.length === 0">
                    <td colspan="6" class="py-8 text-center text-slate-500 dark:text-[#B3B3B3]">Tidak ada wilayah kecamatan yang cocok dengan kriteria pencarian.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Card 3B: Disease Surveillance Trends (PMK & LSD) & Production Comparison -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                  <div>
                    <span class="text-xs font-semibold tracking-normal text-rose-600 dark:text-rose-400">Surveilans Kesehatan Hewan</span>
                    <h3 class="font-display font-bold text-sm text-slate-900 dark:text-[#F5F5F5] mt-0.5">Tren Kasus Wabah ({{ filterState.tahun }})</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="exportTrenPenyakitCsv"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[11px] font-semibold text-slate-700 dark:text-[#F5F5F5] transition-colors cursor-pointer"
                      title="Ekspor tren kasus wabah ke CSV"
                    >
                      <FileSpreadsheet class="w-3 h-3 text-rose-500" />
                      <span>Ekspor CSV</span>
                    </button>
                    <ShieldAlert class="w-4 h-4 text-rose-500 shrink-0" />
                  </div>
                </div>

                <div class="mt-4 space-y-4">
                  <div v-for="serie in chartData?.tren_penyakit?.series || []" :key="serie.name" class="p-3 rounded-lg bg-slate-50 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                    <div class="flex items-center justify-between text-xs mb-2">
                      <span class="font-semibold text-slate-800 dark:text-[#F5F5F5] flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: serie.color }"></span>
                        {{ serie.name }}
                      </span>
                      <span class="font-mono tabular-nums font-bold text-slate-900 dark:text-[#F5F5F5]">Total: {{ sumArray(serie.data).toLocaleString('id-ID') }} kasus</span>
                    </div>
                    <div class="grid grid-cols-4 gap-2 pt-1">
                      <div v-for="(cat, idx) in chartData?.tren_penyakit?.categories || []" :key="cat" class="p-2 rounded bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] text-center">
                        <div class="text-[10px] text-slate-500 dark:text-[#B3B3B3]">{{ cat }}</div>
                        <div class="text-xs font-mono tabular-nums font-bold text-slate-900 dark:text-[#F5F5F5] mt-0.5">{{ (serie.data?.[idx] ?? 0).toLocaleString('id-ID') }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-3">Pemantauan berkala penyakit menular strategis PMK (Artiodactyla) &amp; LSD (Bovine).</p>
            </div>

            <!-- Komparasi Produksi Daging & Susu -->
            <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
                  <div>
                    <span class="text-xs font-semibold tracking-normal text-teal-600 dark:text-teal-400">Komparasi Komoditas</span>
                    <h3 class="font-display font-bold text-sm text-slate-900 dark:text-[#F5F5F5] mt-0.5">Rincian Produksi Daging &amp; Susu</h3>
                  </div>
                  <div class="flex items-center gap-2">
                    <button
                      type="button"
                      @click="exportKomparasiProduksiCsv"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[11px] font-semibold text-slate-700 dark:text-[#F5F5F5] transition-colors cursor-pointer"
                      title="Ekspor rincian komparasi produksi ke CSV"
                    >
                      <FileSpreadsheet class="w-3 h-3 text-teal-500" />
                      <span>Ekspor CSV</span>
                    </button>
                    <TrendingUp class="w-4 h-4 text-teal-500 shrink-0" />
                  </div>
                </div>

                <div class="mt-4 space-y-4">
                  <div>
                    <div class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3] mb-2">Kontribusi Daging (kg)</div>
                    <div class="space-y-1.5">
                      <div v-for="item in chartData?.komparasi_produksi?.daging?.slice(0, 4) || []" :key="item.name" class="flex items-center justify-between text-xs py-1 px-2.5 rounded bg-slate-50 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <span class="text-slate-700 dark:text-[#B3B3B3] truncate">{{ item.name }}</span>
                        <span class="font-mono tabular-nums font-semibold text-slate-900 dark:text-[#F5F5F5]">{{ (item.value ?? 0).toLocaleString('id-ID') }} kg</span>
                      </div>
                    </div>
                  </div>

                  <div>
                    <div class="text-xs font-semibold tracking-normal text-slate-500 dark:text-[#B3B3B3] mb-2">Kontribusi Susu Segar (liter)</div>
                    <div class="space-y-1.5">
                      <div v-for="item in chartData?.komparasi_produksi?.susu || []" :key="item.name" class="flex items-center justify-between text-xs py-1 px-2.5 rounded bg-slate-50 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                        <span class="text-slate-700 dark:text-[#B3B3B3] truncate">{{ item.name }}</span>
                        <span class="font-mono tabular-nums font-semibold text-teal-600 dark:text-[#00D084]">{{ (item.value ?? 0).toLocaleString('id-ID') }} L</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-[#B3B3B3] mt-3">Data tervalidasi berdasarkan kamus relasi biologis ternak Kabupaten Banyumas.</p>
            </div>
          </div>
        </div>

        <!-- RIGHT 4 COLUMNS: Top Kecamatan Ranking + Commodity Proportion -->
        <div class="lg:col-span-4 space-y-6">
          <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
              <div>
                <span class="text-xs font-semibold tracking-normal text-emerald-600 dark:text-[#00D084]">Peringkat Wilayah</span>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-[#F5F5F5] mt-0.5">Top Kecamatan ({{ formatKomoditasLabel(filterState.komoditas) }})</h3>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="exportRankingCsv"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[11px] font-semibold text-slate-700 dark:text-[#F5F5F5] transition-colors cursor-pointer"
                  title="Ekspor peringkat kecamatan ke CSV"
                >
                  <FileSpreadsheet class="w-3 h-3 text-emerald-500" />
                  <span>Ekspor CSV</span>
                </button>
                <button type="button" @click="showAllRanking = !showAllRanking" class="text-xs font-semibold text-emerald-600 dark:text-[#00D084] hover:underline cursor-pointer">
                  {{ showAllRanking ? 'Tampilkan Top 10' : `Semua (${rankingItems.length})` }}
                </button>
              </div>
            </div>

            <div class="mt-4 space-y-2.5 max-h-96 overflow-y-auto pr-1">
              <div v-for="(item, idx) in displayedRankingItems" :key="item.name" class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                  <div class="flex items-center gap-2 min-w-0">
                    <span
                      class="w-5 h-5 rounded-md font-mono tabular-nums text-[10px] font-bold flex items-center justify-center shrink-0"
                      :class="idx < 3 ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30' : 'bg-slate-100 dark:bg-[#181818] text-slate-500 dark:text-[#B3B3B3]'"
                    >
                      {{ idx + 1 }}
                    </span>
                    <span class="font-medium text-slate-800 dark:text-[#F5F5F5] truncate">{{ item.name }}</span>
                  </div>
                  <span class="font-mono tabular-nums font-semibold text-slate-900 dark:text-[#F5F5F5] shrink-0">{{ item.value.toLocaleString('id-ID') }} ekor</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 dark:bg-[#181818] rounded-full overflow-hidden">
                  <div class="h-full bg-emerald-500 rounded-full transition-[width] duration-300 ease-out" :style="{ width: `${ item.percent }%` }"></div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between gap-2 pb-3 border-b border-[#E0E4DD] dark:border-[#1E1E1E]">
              <div>
                <span class="text-xs font-semibold tracking-normal text-teal-600 dark:text-teal-400">Distribusi Komoditas</span>
                <h3 class="font-display font-bold text-base text-slate-900 dark:text-[#F5F5F5] mt-0.5">Proporsi Populasi 6 Komoditas</h3>
              </div>
              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="exportProporsiCsv"
                  class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-[11px] font-semibold text-slate-700 dark:text-[#F5F5F5] transition-colors cursor-pointer"
                  title="Ekspor proporsi komoditas ke CSV"
                >
                  <FileSpreadsheet class="w-3 h-3 text-teal-500" />
                  <span>Ekspor CSV</span>
                </button>
                <PieChart class="w-4 h-4 text-teal-500 shrink-0" />
              </div>
            </div>

            <div class="mt-4 space-y-3">
              <div v-for="kom in proporsiWithPercentage" :key="kom.kode" class="p-3 rounded-lg bg-slate-50 dark:bg-[#181818] border border-[#E0E4DD] dark:border-[#1E1E1E]">
                <div class="flex items-center justify-between text-xs mb-1.5">
                  <span class="font-semibold text-slate-800 dark:text-[#F5F5F5]">{{ kom.name }}</span>
                  <div class="flex items-center gap-2 font-mono tabular-nums">
                    <span class="text-slate-900 dark:text-[#F5F5F5] font-bold">{{ kom.value.toLocaleString('id-ID') }}</span>
                    <span class="text-[11px] px-1.5 py-0.5 rounded bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 font-semibold">{{ kom.share }}%</span>
                  </div>
                </div>
                <div class="w-full h-1.5 bg-slate-200 dark:bg-[#1E1E1E] rounded-full overflow-hidden">
                  <div class="h-full bg-teal-500 rounded-full transition-[width] duration-300 ease-out" :style="{ width: `${ kom.shareNum }%` }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ROW 4: COLLAPSIBLE DEVELOPER CONTRACT INSPECTOR (CLOSED BY DEFAULT) -->
      <section class="bg-white dark:bg-[#111111] border border-[#E0E4DD] dark:border-[#1E1E1E] rounded-xl overflow-hidden shadow-sm">
        <div class="px-5 py-3.5 flex items-center justify-between gap-4">
          <div class="flex items-center gap-2.5">
            <Terminal class="w-4 h-4 text-emerald-600 dark:text-[#00D084] shrink-0" />
            <span class="text-xs font-mono font-semibold tracking-normal text-slate-700 dark:text-[#B3B3B3]">Inspektor Kontrak Data Inertia ($props)</span>
          </div>
          <button
            type="button"
            @click="showRaw = !showRaw"
            class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-[#181818] hover:bg-slate-200 dark:hover:bg-[#1E1E1E] border border-[#E0E4DD] dark:border-[#1E1E1E] text-xs font-mono font-semibold text-emerald-700 dark:text-[#00D084] transition-colors cursor-pointer"
          >
            {{ showRaw ? '[Sembunyikan Kontrak JSON]' : '[Tampilkan Kontrak JSON]' }}
          </button>
        </div>
        <div v-if="showRaw" class="border-t border-[#E0E4DD] dark:border-[#1E1E1E] p-4 bg-[#0C0C0C]">
          <pre class="text-[11px] font-mono text-emerald-300 overflow-x-auto max-h-96 leading-relaxed">{{ JSON.stringify($props, null, 2) }}</pre>
        </div>
      </section>
    </main>

    <!-- Chatbot Drawer FAB -->
    <ChatbotDrawer />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue'
import ChatbotDrawer from '@/components/ternakverse/ChatbotDrawer.vue'
import {
  Activity, AlertTriangle, ArrowUpRight, BarChart3, Building2, CheckCircle2,
  Database, Droplets, FileSpreadsheet, Filter, Info, Layers, Loader2, Map, PieChart,
  Search, ShieldAlert, Terminal, TrendingUp,
} from 'lucide-vue-next'

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
  series: Array<{ name: string; kode?: string; data: number[]; color: string }>
}

interface ChartKomparasi {
  susu: Array<{ name: string; value: number; kode?: string }>
  daging: Array<{ name: string; value: number; kode?: string }>
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
const props = defineProps<{
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

const isAdmin = computed(() => {
  return props.auth?.user?.role === 'admin'
    || props.auth?.user?.is_admin
    || (typeof window !== 'undefined' ? localStorage.getItem('ternakverse_role') === 'admin' : false)
})

const currentUserName = computed(() => {
  if (isAdmin.value) {
    return props.auth?.user?.nama || (typeof window !== 'undefined' ? localStorage.getItem('ternakverse_name') : null) || 'Satrio Wibowo'
  }
  return props.auth?.user?.nama || (typeof window !== 'undefined' ? localStorage.getItem('ternakverse_name') : null) || 'drh. Sulistyo'
})

const currentUserEmail = computed(() => {
  if (isAdmin.value) {
    return props.auth?.user?.email || 'admin.diskanak@banyumaskab.go.id'
  }
  return props.auth?.user?.email || 'drh.sulistyo@banyumaskab.go.id'
})

// Developer JSON Contract Inspector — closed by default for Executive UI
const showRaw = ref(false)
const showAllRanking = ref(false)
const searchKecamatan = ref('')
const selectedZoneFilter = ref<'SEMUA' | 'HIJAU' | 'KUNING' | 'MERAH'>('SEMUA')

const commodityOptions = [
  { kode: 'SEMUA', label: 'Semua Komoditas' },
  { kode: 'SAPI_POTONG', label: 'Sapi Potong' },
  { kode: 'SAPI_PERAH', label: 'Sapi Perah' },
  { kode: 'KAMBING', label: 'Kambing' },
  { kode: 'DOMBA', label: 'Domba' },
  { kode: 'AYAM_POTONG', label: 'Ayam Ras Pedaging' },
  { kode: 'AYAM_TELUR', label: 'Ayam Ras Petelur' },
]

function formatKomoditasLabel(kode?: string): string {
  if (!kode || kode === 'SEMUA') return 'Semua Komoditas'
  const found = commodityOptions.find((c) => c.kode === kode)
  return found ? found.label : kode.replace(/_/g, ' ')
}

function updateFilter(key: keyof FilterState, value: number | string | null) {
  const nextQuery: Record<string, any> = {
    tahun: props.filterState?.tahun ?? 2026,
    triwulan: props.filterState?.triwulan ?? '',
    komoditas: props.filterState?.komoditas ?? 'SAPI_POTONG',
    [key]: value === null ? '' : value,
  }
  router.get('/dinas/dashboard', nextQuery, {
    preserveState: true,
    preserveScroll: true,
    only: ['filterState', 'kpiCards', 'chartData', 'geoChoropleth'],
  })
}

const balaiDesaCount = computed<number>(() => {
  if (!props.balaiDesaPoints) return 0
  if (Array.isArray(props.balaiDesaPoints?.features)) {
    return props.balaiDesaPoints.features.length
  }
  if (Array.isArray(props.balaiDesaPoints)) {
    return props.balaiDesaPoints.length
  }
  return 331
})

const filteredChoropleth = computed<GeoChoroplethItem[]>(() => {
  const list = props.geoChoropleth || []
  const q = searchKecamatan.value.trim().toLowerCase()
  return list.filter((item) => {
    const matchesZone =
      selectedZoneFilter.value === 'SEMUA' || item.status_zonasi_ews === selectedZoneFilter.value
    const matchesQuery =
      !q ||
      item.nama_kecamatan?.toLowerCase().includes(q) ||
      item.kode_kecamatan?.toLowerCase().includes(q)
    return matchesZone && matchesQuery
  })
})

const rankingItems = computed(() => {
  const cats = props.chartData?.ranking_kecamatan?.categories || []
  const vals = props.chartData?.ranking_kecamatan?.series?.[0]?.data || []
  const maxVal = Math.max(...vals, 1)
  return cats.map((name, idx) => {
    const value = vals[idx] ?? 0
    return {
      name,
      value,
      percent: Math.min(100, Math.round((value / maxVal) * 100)),
    }
  })
})

const displayedRankingItems = computed(() => {
  return showAllRanking.value ? rankingItems.value : rankingItems.value.slice(0, 10)
})

const proporsiWithPercentage = computed(() => {
  const items = props.chartData?.proporsi_komoditas || []
  const total = items.reduce((acc, cur) => acc + (cur.value || 0), 0) || 1
  return items.map((item) => {
    const shareNum = Math.min(100, Math.max(0, (item.value / total) * 100))
    return {
      ...item,
      share: shareNum.toFixed(1),
      shareNum,
    }
  })
})

function sumArray(arr?: number[]): number {
  if (!Array.isArray(arr)) return 0
  return arr.reduce((acc, v) => acc + (Number(v) || 0), 0)
}

function getEwsBadgeClass(status: 'HIJAU' | 'KUNING' | 'MERAH'): string {
  if (status === 'MERAH') {
    return 'bg-rose-500/15 text-rose-700 dark:text-rose-400 border-rose-500/30'
  }
  if (status === 'KUNING') {
    return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30'
  }
  return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
}

function getEwsDotClass(status: 'HIJAU' | 'KUNING' | 'MERAH'): string {
  if (status === 'MERAH') return 'bg-rose-500'
  if (status === 'KUNING') return 'bg-amber-500'
  return 'bg-emerald-500'
}

// ---------------------------------------------------------------------------
// CSV EXPORT ENGINE (UTF-8 BOM FOR CLEAN EXCEL COMPATIBILITY)
// ---------------------------------------------------------------------------
function sanitizeCsvCell(val: unknown): string {
  const str = String(val ?? '')
  const escaped = str.replace(/"/g, '""')
  if (/^[=+\-@\t\r]/.test(escaped)) {
    return `"'${escaped}"`
  }
  return `"${escaped}"`
}

function downloadCsv(filename: string, headers: string[], rows: (string | number)[][]): void {
  const bom = '\uFEFF'
  const csvContent = [
    headers.map(sanitizeCsvCell).join(','),
    ...rows.map((row) => row.map(sanitizeCsvCell).join(',')),
  ].join('\r\n')

  const blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.setAttribute('href', url)
  link.setAttribute('download', filename)
  link.style.visibility = 'hidden'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
}

function exportRingkasanAnalitikCsv(): void {
  const headers = ['Parameter', 'Nilai', 'Satuan', 'Keterangan']
  const rows: (string | number)[][] = [
    ['Tahun Anggaran', props.filterState?.tahun ?? 2026, 'Tahun', 'Tahun anggaran analitik aktif'],
    ['Triwulan', props.filterState?.triwulan ? `TW ${props.filterState.triwulan}` : 'Semua Triwulan', '-', 'Periode triwulan'],
    ['Komoditas Terpilih', formatKomoditasLabel(props.filterState?.komoditas), '-', props.filterState?.komoditas ?? ''],
    ['Total Populasi Ternak', props.kpiCards?.total_populasi_ternak ?? 0, 'ekor', 'Agregat 27 Kecamatan Banyumas'],
    ['Total Produksi Daging', props.kpiCards?.total_produksi_daging_kg ?? 0, 'kg', 'Akumulasi 6 Komoditas Pangan'],
    ['Total Produksi Susu Segar', props.kpiCards?.total_produksi_susu_liter ?? 0, 'liter', 'Sapi Perah, Kambing & Domba'],
    ['Total Produksi Telur', props.kpiCards?.total_produksi_telur_butir ?? 0, 'butir', 'Ayam Ras Petelur (Layer)'],
    ['Kasus Aktif Wabah', props.kpiCards?.total_kasus_aktif ?? 0, 'kasus', 'Surveilans Aktif PMK & LSD'],
    ['Fasilitas RPH Aktif', props.kpiCards?.total_rph_aktif ?? 0, 'unit', 'Kapasitas Potong Bersertifikat'],
  ]
  downloadCsv(`ringkasan_analitik_dinas_${props.filterState?.tahun || 2026}.csv`, headers, rows)
}

function exportChoroplethMatrixCsv(): void {
  const headers = [
    'Kecamatan',
    'Kode',
    'Kuantil_Tier',
    'Status_Zonasi_EWS',
    'Populasi',
    'Produksi_Daging_Kg',
    'Produksi_Susu_Liter',
  ]
  const rows = filteredChoropleth.value.map((kec) => [
    kec.nama_kecamatan,
    kec.kode_kecamatan,
    `Tier ${kec.class_tier}`,
    kec.status_zonasi_ews,
    kec.total_populasi ?? 0,
    kec.produksi_daging_kg ?? 0,
    kec.produksi_susu_liter ?? 0,
  ])
  downloadCsv(`matriks_zonasi_spasial_${props.filterState?.komoditas || 'ternak'}.csv`, headers, rows)
}

function exportTrenPenyakitCsv(): void {
  const headers = ['Penyakit', 'Kategori', 'Jumlah_Kasus']
  const rows: (string | number)[][] = []
  const cats = props.chartData?.tren_penyakit?.categories || []
  const series = props.chartData?.tren_penyakit?.series || []
  series.forEach((serie) => {
    cats.forEach((cat, idx) => {
      rows.push([serie.name, cat, serie.data?.[idx] ?? 0])
    })
  })
  downloadCsv(`tren_kasus_wabah_${props.filterState?.tahun || 2026}.csv`, headers, rows)
}

function exportKomparasiProduksiCsv(): void {
  const headers = ['Jenis_Produksi', 'Komoditas', 'Jumlah', 'Satuan']
  const rows: (string | number)[][] = []
  ;(props.chartData?.komparasi_produksi?.daging || []).forEach((item) => {
    rows.push(['Daging', item.name, item.value ?? 0, 'kg'])
  })
  ;(props.chartData?.komparasi_produksi?.susu || []).forEach((item) => {
    rows.push(['Susu Segar', item.name, item.value ?? 0, 'liter'])
  })
  downloadCsv(`komparasi_produksi_daging_susu_${props.filterState?.tahun || 2026}.csv`, headers, rows)
}

function exportRankingCsv(): void {
  const headers = ['Peringkat', 'Kecamatan', 'Populasi_Ekor', 'Persen']
  const rows = rankingItems.value.map((item, idx) => [
    idx + 1,
    item.name,
    item.value,
    `${item.percent}%`,
  ])
  downloadCsv(`peringkat_kecamatan_${props.filterState?.komoditas || 'populasi'}.csv`, headers, rows)
}

function exportProporsiCsv(): void {
  const headers = ['Komoditas', 'Kode', 'Populasi_Ekor', 'Persentase']
  const rows = proporsiWithPercentage.value.map((item) => [
    item.name,
    item.kode,
    item.value,
    `${item.share}%`,
  ])
  downloadCsv(`proporsi_komoditas_${props.filterState?.tahun || 2026}.csv`, headers, rows)
}
</script>

