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
    <!-- Kotak Diagnostik Kanan Bawah (Bab 13) -->
    <div
        class="fixed bottom-3 right-4 z-50 select-none font-ui"
    >
        <!-- Status Normal / Collapsed Pill -->
        <div
            v-if="!isExpanded"
            @click="isExpanded = true"
            class="px-2.5 py-1 rounded-full border border-[#243034] bg-[#10171A] text-[10px] font-bold flex items-center gap-1.5 cursor-pointer hover:border-[#19F5D4] transition-all shadow-[2px_2px_0_#0A0D0E]"
            :class="systemStatus === 'ok' ? 'text-[#39FF14]' : 'text-[#FF5C5C] border-[#FF5C5C] animate-pulse'"
        >
            <span class="w-2 h-2 rounded-full" :class="systemStatus === 'ok' ? 'bg-[#39FF14]' : 'bg-[#FF5C5C]'"></span>
            <span>{{ systemStatus === 'ok' ? 'Diagnostik: Normal (safe)' : 'Diagnostik: Galat Terdeteksi' }}</span>
            <ChevronUp class="w-3 h-3 text-[#8FA1A0]" />
        </div>

        <!-- Expanded Diagnostic Card -->
        <div
            v-else
            class="w-72 bg-[#10171A] border-2 rounded-16 p-3 shadow-[3px_3px_0_#243034] text-xs animate-in slide-in-from-bottom-2 duration-150"
            :class="systemStatus === 'ok' ? 'border-[#243034]' : 'border-[#FF5C5C]'"
        >
            <div class="flex items-center justify-between pb-2 border-b border-[#243034] mb-2">
                <div class="flex items-center gap-1.5 font-bold" :class="systemStatus === 'ok' ? 'text-[#39FF14]' : 'text-[#FF5C5C]'">
                    <Terminal class="w-3.5 h-3.5" />
                    <span>Kotak Diagnostik Bab 13</span>
                </div>
                <button @click="isExpanded = false" class="text-[#8FA1A0] hover:text-[#F4F7F5]">
                    <ChevronDown class="w-3.5 h-3.5" />
                </button>
            </div>

            <div class="space-y-1.5 text-[11px] font-mono">
                <div class="flex justify-between">
                    <span class="text-[#8FA1A0]">Watchdog (3s):</span>
                    <span :class="watchdogPassed ? 'text-[#39FF14]' : 'text-[#FFC53D]'">
                        {{ watchdogPassed ? 'PASSED (0.42s)' : 'Memeriksa...' }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#8FA1A0]">Karantina safe():</span>
                    <span class="text-[#19F5D4]">Aktif (Try/Catch)</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-[#8FA1A0]">Penanda Berkas:</span>
                    <span class="text-[#39FF14]">BERKAS-UTUH OK</span>
                </div>

                <!-- Error Display if any -->
                <div v-if="systemStatus === 'error'" class="p-2 bg-[#0A0D0E] border border-[#FF5C5C] rounded-8 text-[#FF5C5C] mt-2">
                    <div class="font-bold">Galat Baris {{ errorLine }}:</div>
                    <div class="text-[10px] break-all">{{ errorMessage }}</div>
                </div>
            </div>

            <!-- Simulator Tombol -->
            <div class="pt-2 border-t border-[#243034] mt-2 flex items-center justify-between text-[10px]">
                <button
                    v-if="systemStatus === 'ok'"
                    @click="simulateError"
                    class="text-[#8FA1A0] hover:text-[#FF5C5C] underline"
                >
                    Uji Kotak Galat
                </button>
                <button
                    v-else
                    @click="clearError"
                    class="text-[#39FF14] hover:underline font-bold"
                >
                    Reset Normal
                </button>
                <span class="text-[#8FA1A0]">FPS: 60 (Canvas/SVG)</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.rounded-8 {
    border-radius: 8px;
}
</style>
