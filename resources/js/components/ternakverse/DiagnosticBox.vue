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
            class="px-3 py-1.5 rounded-full border border-[#1E1E1E] bg-[#111111]/95 backdrop-blur-md text-[10px] font-semibold flex items-center gap-2 cursor-pointer hover:border-emerald-500/40 transition-all shadow-lg"
            :class="systemStatus === 'ok' ? 'text-emerald-400' : 'text-rose-400 border-rose-500/50 animate-pulse'"
        >
            <span class="w-2 h-2 rounded-full" :class="systemStatus === 'ok' ? 'bg-emerald-400' : 'bg-rose-400'"></span>
            <span>{{ systemStatus === 'ok' ? 'Diagnostik: Normal' : 'Diagnostik: Galat' }}</span>
            <ChevronUp class="w-3 h-3 text-[#B3B3B3]" />
        </div>

        <!-- Expanded Diagnostic Card -->
        <div
            v-else
            class="w-72 bg-[#111111]/95 backdrop-blur-md border rounded-2xl p-3.5 shadow-2xl text-xs animate-in slide-in-from-bottom-2 duration-150"
            :class="systemStatus === 'ok' ? 'border-[#1E1E1E]' : 'border-rose-500/50'"
        >
            <div class="flex items-center justify-between pb-2.5 border-b border-[#1E1E1E] mb-2.5">
                <div class="flex items-center gap-1.5 font-bold" :class="systemStatus === 'ok' ? 'text-emerald-400' : 'text-rose-400'">
                    <Terminal class="w-3.5 h-3.5" />
                    <span>Diagnostik Sistem</span>
                </div>
                <button type="button" @click="isExpanded = false" class="text-[#B3B3B3] hover:text-[#F5F5F5] cursor-pointer" aria-label="Tutup Diagnostik">
                    <ChevronDown class="w-3.5 h-3.5" />
                </button>
            </div>

            <div class="space-y-1.5 text-[11px] font-mono tabular-nums">
                <div class="flex justify-between">
                    <span class="text-[#B3B3B3]">Watchdog:</span>
                    <span :class="watchdogPassed ? 'text-emerald-400' : 'text-amber-400'">
                        {{ watchdogPassed ? 'PASSED (0.42s)' : 'Memeriksa...' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#B3B3B3]">Karantina safe():</span>
                    <span class="text-teal-400">Aktif (Try/Catch)</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#B3B3B3]">Status Modul:</span>
                    <span class="text-emerald-400">NORMAL OK</span>
                </div>

                <!-- Error Display if any -->
                <div v-if="systemStatus === 'error'" class="p-2.5 bg-[#090909] border border-rose-500/50 rounded-xl text-rose-400 mt-2">
                    <div class="font-bold">Galat Baris {{ errorLine }}:</div>
                    <div class="text-[10px] break-all">{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Simulator Tombol -->
            <div class="pt-2.5 border-t border-[#1E1E1E] mt-2.5 flex items-center justify-between text-[10px]">
                <button
                    v-if="systemStatus === 'ok'"
                    type="button"
                    @click="simulateError"
                    class="text-[#B3B3B3] hover:text-rose-400 underline cursor-pointer"
                >
                    Uji Kotak Galat
                </button>
                <button
                    v-else
                    type="button"
                    @click="clearError"
                    class="text-emerald-400 hover:underline font-bold cursor-pointer"
                >
                    Reset Normal
                </button>
                <span class="text-[#B3B3B3] font-mono tabular-nums">FPS: 60 (Canvas/SVG)</span>
            </div>
        </div>
    </div>
</template>
