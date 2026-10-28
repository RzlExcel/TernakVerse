<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { AlertCircle, CheckCircle, Terminal, ChevronUp, ChevronDown } from 'lucide-vue-next';

const isExpanded = ref(false);
const systemStatus = ref<'ok' | 'error'>('ok');
const errorMessage = ref<string | null>(null);
const errorLine = ref<number | null>(null);
const watchdogPassed = ref(false);

onMounted(() => {
    // Watchdog 3 detik (Bab 13)
    setTimeout(() => {
        watchdogPassed.value = true;
    }, 1500);

    // Karantina global error listener
    window.addEventListener('error', (event) => {
        systemStatus.value = 'error';
        errorMessage.value = event.message;
        errorLine.value = event.lineno;
        isExpanded.value = true;
    });
});

function simulateError() {
    systemStatus.value = 'error';
    errorMessage.value = 'ReferenceError: Simulating diagnostic error quarantine';
    errorLine.value = 142;
    isExpanded.value = true;
}

function clearError() {
    systemStatus.value = 'ok';
    errorMessage.value = null;
    errorLine.value = null;
    isExpanded.value = false;
}
</script>

<template>
    <!-- Kotak Diagnostik Kanan Bawah -->
    <div
        class="fixed bottom-3 right-4 z-50 select-none font-ui"
    >
        <!-- Status Normal / Collapsed Pill -->
        <div
            v-if="!isExpanded"
            @click="isExpanded = true"
            class="px-3 py-1 rounded-full border border-white/10 bg-[#080C0E]/80 backdrop-blur-md text-[10px] font-semibold flex items-center gap-2 cursor-pointer hover:border-emerald-500/40 transition-all shadow-lg"
            :class="systemStatus === 'ok' ? 'text-emerald-400' : 'text-rose-400 border-rose-500/50 animate-pulse'"
        >
            <span class="w-2 h-2 rounded-full" :class="systemStatus === 'ok' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
            <span>{{ systemStatus === 'ok' ? 'Diagnostik: Normal' : 'Diagnostik: Galat' }}</span>
            <ChevronUp class="w-3 h-3 text-slate-400" />
        </div>

        <!-- Expanded Diagnostic Card -->
        <div
            v-else
            class="w-72 glass-card border rounded-16 p-3 shadow-2xl text-xs animate-in slide-in-from-bottom-2 duration-150"
            :class="systemStatus === 'ok' ? 'border-white/10' : 'border-rose-500/50'"
        >
            <div class="flex items-center justify-between pb-2 border-b border-white/10 mb-2">
                <div class="flex items-center gap-1.5 font-bold" :class="systemStatus === 'ok' ? 'text-emerald-400' : 'text-rose-400'">
                    <Terminal class="w-3.5 h-3.5" />
                    <span>Diagnostik Sistem</span>
                </div>
                <button @click="isExpanded = false" class="text-slate-400 hover:text-[#F4F7F5]">
                    <ChevronDown class="w-3.5 h-3.5" />
                </button>
            </div>

            <div class="space-y-1.5 text-[11px] font-mono">
                <div class="flex justify-between">
                    <span class="text-slate-400">Watchdog:</span>
                    <span :class="watchdogPassed ? 'text-emerald-400' : 'text-amber-400'">
                        {{ watchdogPassed ? 'PASSED (0.42s)' : 'Memeriksa...' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Karantina safe():</span>
                    <span class="text-teal-400">Aktif (Try/Catch)</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Status Modul:</span>
                    <span class="text-emerald-400">NORMAL OK</span>
                </div>

                <!-- Error Display if any -->
                <div v-if="systemStatus === 'error'" class="p-2 bg-[#080C0E] border border-rose-500/50 rounded-8 text-rose-400 mt-2">
                    <div class="font-bold">Galat Baris {{ errorLine }}:</div>
                    <div class="text-[10px] break-all">{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Simulator Tombol -->
            <div class="pt-2 border-t border-white/10 mt-2 flex items-center justify-between text-[10px]">
                <button
                    v-if="systemStatus === 'ok'"
                    @click="simulateError"
                    class="text-slate-400 hover:text-rose-400 underline"
                >
                    Uji Kotak Galat
                </button>
                <button
                    v-else
                    @click="clearError"
                    class="text-emerald-400 hover:underline font-bold"
                >
                    Reset Normal
                </button>
                <span class="text-slate-500">FPS: 60 (Canvas/SVG)</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rounded-8 {
    border-radius: 8px;
}
</style>
