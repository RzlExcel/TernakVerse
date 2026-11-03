<template>
  <div class="min-h-screen bg-[#FFFFFF] dark:bg-[#090909] text-slate-900 dark:text-[#F5F5F5] font-sans antialiased">
    <!-- ENTERPRISE FORENSIC HEADER -->
    <header class="sticky top-0 z-30 bg-white/95 dark:bg-[#111111]/95 backdrop-blur border-b border-slate-200 dark:border-[#1E1E1E] shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex items-start sm:items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-600 dark:text-emerald-400 shrink-0">
            <FileSearch class="w-6 h-6" />
          </div>
          <div>
            <div class="flex flex-wrap items-center gap-2 mb-1">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold tracking-wider uppercase bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/20">
                AUDIT TRAIL FORENSIK
              </span>
              <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-mono font-semibold bg-slate-100 dark:bg-[#181818] text-slate-600 dark:text-[#B3B3B3] border border-slate-200 dark:border-[#1E1E1E]">
                ROLE: {{ auth?.user?.role?.toUpperCase() || 'ADMIN' }}
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 dark:text-[#F5F5F5] flex items-center gap-2">
              <span>TERNAK<span class="text-emerald-600 dark:text-emerald-400">VERSE</span></span>
              <span class="text-xs font-mono font-semibold px-2 py-0.5 rounded bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">
                LOG AKTIVITAS ADMIN
              </span>
            </h1>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between lg:justify-end gap-3">
          <nav class="flex flex-wrap items-center gap-1.5" aria-label="Navigasi Forensik">
            <a
              href="/admin/panel"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 dark:bg-[#181818] dark:hover:bg-[#1E1E1E] text-slate-700 dark:text-[#F5F5F5] border border-slate-200 dark:border-[#1E1E1E] transition-colors"
            >
              <ArrowLeft class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
              <span>Kembali ke Panel Admin</span>
            </a>
            <a
              href="/internal/admin"
              class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white shadow-sm transition-colors"
            >
              <LayoutDashboard class="w-3.5 h-3.5" />
              <span>Portal Operasional Admin</span>
            </a>
          </nav>

          <ThemeToggle size="sm" compact />

          <div class="pl-3 border-l border-slate-200 dark:border-[#1E1E1E] text-right hidden sm:block">
            <div class="text-xs font-bold text-slate-900 dark:text-[#F5F5F5]">
              {{ auth?.user?.nama || 'Administrator' }}
            </div>
            <div class="text-[11px] font-mono text-slate-500 dark:text-[#B3B3B3]">
              {{ auth?.user?.email || 'admin@banyumaskab.go.id' }}
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
      <section class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-5 shadow-sm">
        <!-- Card Header & Filter Controls -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 mb-4 border-b border-slate-200 dark:border-[#1E1E1E]">
          <div>
            <div class="flex items-center gap-2.5">
              <ShieldAlert class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-900 dark:text-[#F5F5F5]">
                REKAM JEJAK FORENSIK (AUDIT LOGS)
              </h2>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-mono tabular-nums font-semibold bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                Total {{ logs?.total || 0 }} Mutasi Data
              </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-[#B3B3B3] mt-1">
              Catatan kronologis perubahan data spasial, populasi, penyakit, dan konfigurasi sistem.
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
            <!-- Action Filter Pills -->
            <div class="flex items-center gap-1 flex-wrap">
              <button
                v-for="act in actionFilters"
                :key="act.key"
                type="button"
                @click="selectedAction = act.key"
                class="px-2.5 py-1.5 rounded-lg text-[11px] font-mono font-bold transition-colors border"
                :class="
                  selectedAction === act.key
                    ? 'bg-[#171B17] text-white border-[#171B17] dark:bg-[#181818] dark:text-[#00D084] dark:border-[#00D084]/40'
                    : 'bg-slate-50 dark:bg-[#181818] text-slate-600 dark:text-[#B3B3B3] border-slate-200 dark:border-[#1E1E1E] hover:text-slate-900 dark:hover:text-[#F5F5F5]'
                "
              >
                {{ act.label }}
              </button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full sm:w-64">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari operator, tabel, ID..."
                class="w-full h-9 pl-9 pr-3 rounded-lg text-xs bg-slate-50 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] text-slate-900 dark:text-[#F5F5F5] placeholder-slate-400 focus:outline-none focus:border-emerald-500"
              />
            </div>
          </div>
        </div>

        <!-- Audit Log Table -->
        <div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-[#1E1E1E]">
          <table class="w-full text-left border-collapse text-xs">
            <thead class="bg-slate-100/80 dark:bg-[#181818] text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-[#B3B3B3] border-b border-slate-200 dark:border-[#1E1E1E]">
              <tr>
                <th class="py-3 px-4">WAKTU (WIB)</th>
                <th class="py-3 px-4">OPERATOR</th>
                <th class="py-3 px-4">AKSI</th>
                <th class="py-3 px-4">TABEL</th>
                <th class="py-3 px-4">RECORD ID</th>
                <th class="py-3 px-4">IP ADDRESS</th>
                <th class="py-3 px-4">MUTASI DATA</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-[#1E1E1E]">
              <tr
                v-for="log in filteredLogs"
                :key="log.id"
                class="hover:bg-slate-50 dark:hover:bg-[#181818] transition-colors align-top"
              >
                <td class="py-3 px-4 font-mono tabular-nums text-slate-500 dark:text-[#B3B3B3] whitespace-nowrap">
                  {{ log.created_at }}
                </td>
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-[#F5F5F5] whitespace-nowrap">
                  {{ log.user?.nama || 'System/Seeder' }}
                </td>
                <td class="py-3 px-4 whitespace-nowrap">
                  <span
                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-bold border"
                    :class="getActionBadgeClass(log.action)"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="py-3 px-4 font-mono text-teal-600 dark:text-teal-400 whitespace-nowrap">
                  {{ log.table_name }}
                </td>
                <td class="py-3 px-4 font-mono tabular-nums text-slate-700 dark:text-[#B3B3B3] whitespace-nowrap">
                  #{{ log.record_id }}
                </td>
                <td class="py-3 px-4 font-mono tabular-nums text-slate-500 dark:text-[#B3B3B3] whitespace-nowrap">
                  {{ log.ip_address || '127.0.0.1' }}
                </td>
                <td class="py-3 px-4 min-w-[260px]">
                  <details class="group text-xs">
                    <summary class="cursor-pointer font-semibold text-slate-600 dark:text-[#B3B3B3] hover:text-emerald-600 dark:hover:text-emerald-400 select-none inline-flex items-center gap-1.5">
                      <span>Lihat Perubahan</span>
                    </summary>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2 p-2.5 rounded-lg bg-slate-100 dark:bg-[#181818] border border-slate-200 dark:border-[#1E1E1E] font-mono text-[11px]">
                      <div class="p-2 rounded bg-rose-500/5 border border-rose-500/20 overflow-x-auto">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400 mb-1">
                          OLD VALUES
                        </div>
                        <pre class="text-slate-700 dark:text-[#B3B3B3] whitespace-pre-wrap break-all">{{ formatDiffValue(log.old_values) }}</pre>
                      </div>
                      <div class="p-2 rounded bg-emerald-500/5 border border-emerald-500/20 overflow-x-auto">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 mb-1">
                          NEW VALUES
                        </div>
                        <pre class="text-slate-700 dark:text-[#B3B3B3] whitespace-pre-wrap break-all">{{ formatDiffValue(log.new_values) }}</pre>
                      </div>
                    </div>
                  </details>
                </td>
              </tr>
              <tr v-if="filteredLogs.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-500 dark:text-[#B3B3B3]">
                  Tidak ada log aktivitas forensik yang sesuai dengan filter pencarian.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- RAW CONTRACT PROPS INSPECTOR (COLLAPSED BY DEFAULT) -->
      <section class="bg-white dark:bg-[#111111] border border-slate-200 dark:border-[#1E1E1E] rounded-xl p-4 shadow-sm">
        <div class="flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <Terminal class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <span class="font-mono text-xs font-bold text-slate-700 dark:text-slate-200">
              TERMINAL HANDSHAKE PROPS AUDIT LOGS
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
          class="mt-3 p-4 rounded-lg bg-[#090909] text-sky-300 font-mono text-[11px] leading-relaxed max-h-80 overflow-auto border border-[#1E1E1E]"
        >{{ $props }}</pre>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue'
import {
  FileSearch,
  ArrowLeft,
  LayoutDashboard,
  ShieldAlert,
  Search,
  Terminal,
} from 'lucide-vue-next'

const props = defineProps<{
  logs: any
  auth?: any
  flash?: any
  appName?: string
}>()

const showRaw = ref(false)
const searchQuery = ref('')
const selectedAction = ref<'ALL' | 'CREATE' | 'UPDATE' | 'DELETE' | 'CONFIG_CHANGE'>('ALL')

const actionFilters = [
  { key: 'ALL' as const, label: 'SEMUA' },
  { key: 'CREATE' as const, label: 'CREATE' },
  { key: 'UPDATE' as const, label: 'UPDATE' },
  { key: 'DELETE' as const, label: 'DELETE' },
  { key: 'CONFIG_CHANGE' as const, label: 'CONFIG_CHANGE' },
]

const filteredLogs = computed(() => {
  const list = props.logs?.data ?? []
  const q = searchQuery.value.trim().toLowerCase()
  return list.filter((log: any) => {
    const matchesAction = selectedAction.value === 'ALL' || log.action === selectedAction.value
    if (!matchesAction) return false
    if (!q) return true
    const operator = (log.user?.nama || 'System/Seeder').toLowerCase()
    const table = (log.table_name || '').toLowerCase()
    const recordId = String(log.record_id ?? '').toLowerCase()
    const ip = (log.ip_address || '').toLowerCase()
    return operator.includes(q) || table.includes(q) || recordId.includes(q) || ip.includes(q)
  })
})

function getActionBadgeClass(action: string): string {
  switch (action) {
    case 'CREATE':
      return 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border-emerald-500/30'
    case 'UPDATE':
      return 'bg-teal-500/10 text-teal-700 dark:text-teal-300 border-teal-500/30'
    case 'DELETE':
      return 'bg-rose-500/10 text-rose-700 dark:text-rose-400 border-rose-500/30'
    case 'CONFIG_CHANGE':
      return 'bg-amber-500/10 text-amber-700 dark:text-amber-300 border-amber-500/30'
    default:
      return 'bg-slate-500/10 text-slate-700 dark:text-slate-300 border-slate-500/30'
  }
}

function formatDiffValue(val: any): string {
  if (val === null || val === undefined) return 'null'
  if (typeof val === 'string') return val
  try {
    return JSON.stringify(val, null, 2)
  } catch {
    return String(val)
  }
}
</script>

