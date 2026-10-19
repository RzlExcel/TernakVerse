<template>
  <div class="audit-layout">
    <header class="top-nav">
      <div class="brand">
        <span class="brand-badge admin">AUDIT TRAIL FORENSIK</span>
        <h1 class="brand-title">TERNAK<span>VERSE</span> <small class="text-xs text-lime font-mono">LOG AKTIVITAS ADMIN</small></h1>
      </div>
      <div class="user-meta">
        <div class="role-pill admin">ROLE: ADMIN</div>
        <div class="user-info">
          <span class="user-name">{{ auth?.user?.nama || 'Administrator' }}</span>
        </div>
      </div>
    </header>

    <main class="content-body">
      <section class="log-card">
        <div class="card-header">
          <h2>REKAM JEJAK FORENSIK (AUDIT LOGS)</h2>
          <span class="count-badge">Total {{ logs?.total || 0 }} Mutasi Data</span>
        </div>
        
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>WAKTU (WIB)</th>
                <th>OPERATOR</th>
                <th>AKSI</th>
                <th>TABEL</th>
                <th>RECORD ID</th>
                <th>IP ADDRESS</th>
                <th>MUTASI DATA</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in logs?.data" :key="log.id">
                <td class="font-mono text-xs text-gray-400">{{ log.created_at }}</td>
                <td class="font-semibold text-white">{{ log.user?.nama || 'System/Seeder' }}</td>
                <td>
                  <span 
                    class="badge-action" 
                    :class="{
                      'act-create': log.action === 'CREATE',
                      'act-update': log.action === 'UPDATE',
                      'act-delete': log.action === 'DELETE',
                      'act-config': log.action === 'CONFIG_CHANGE'
                    }"
                  >
                    {{ log.action }}
                  </span>
                </td>
                <td class="font-mono text-xs text-cyan">{{ log.table_name }}</td>
                <td class="font-mono text-xs">#{{ log.record_id }}</td>
                <td class="font-mono text-xs text-gray-500">{{ log.ip_address || '127.0.0.1' }}</td>
                <td>
                  <details class="cursor-pointer text-xs">
                    <summary class="text-gray-400 hover:text-lime">Lihat Perubahan</summary>
                    <pre class="diff-json">Old: {{ log.old_values }}&#10;New: {{ log.new_values }}</pre>
                  </details>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section class="raw-inspector">
        <pre class="json-dump">{{ $props }}</pre>
      </section>
    </main>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  logs: any
  auth?: any
  flash?: any
  appName?: string
}>()
</script>

<style scoped>
.audit-layout {
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
  font-size: 10px;
  font-weight: 700;
  color: #39FF14;
  background: rgba(57, 255, 20, 0.1);
  padding: 2px 8px;
  border-radius: 4px;
  display: inline-block;
}
.brand-title { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; }
.brand-title span { color: #39FF14; }
.role-pill.admin {
  font-size: 11px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(57, 255, 20, 0.15);
  color: #39FF14;
  border: 1px solid #39FF14;
}
.log-card {
  background: #12171A;
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 24px;
}
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}
.card-header h2 { font-size: 15px; font-weight: 700; }
.count-badge { font-size: 11px; background: #1B2227; padding: 4px 10px; border-radius: 6px; color: #8B949E; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table th {
  text-align: left;
  font-size: 10px;
  font-weight: 700;
  color: #8B949E;
  padding: 10px 12px;
  background: #161D21;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}
.data-table td { padding: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.04); }
.badge-action {
  font-size: 10px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 4px;
  font-family: 'JetBrains Mono', monospace;
}
.act-create { background: rgba(57, 255, 20, 0.15); color: #39FF14; }
.act-update { background: rgba(0, 240, 255, 0.15); color: #00F0FF; }
.act-delete { background: rgba(244, 63, 94, 0.15); color: #F43F5E; }
.act-config { background: rgba(245, 158, 11, 0.15); color: #F59E0B; }
.diff-json {
  background: #07090A;
  padding: 8px;
  border-radius: 4px;
  margin-top: 4px;
  font-size: 10px;
  color: #A5D6FF;
}
.raw-inspector {
  background: #0D1113;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 12px;
  padding: 16px;
}
.json-dump {
  background: #07090A;
  color: #A5D6FF;
  padding: 16px;
  border-radius: 8px;
  max-height: 300px;
  overflow: auto;
  font-size: 11px;
}
</style>
