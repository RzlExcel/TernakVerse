<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import {
    Check,
    UserCheck,
    AlertTriangle,
    ShieldAlert,
    Building2,
} from 'lucide-vue-next';

export interface KpiCardItem {
    id: string;
    title: string;
    value: string | number;
    unit?: string;
    trend: string;
    trendSuffix: string;
    trendColorClass: string;
    iconBoxClass: string;
    iconType: 'cow' | 'farmer' | 'pmk' | 'lsd' | 'rph';
    sparklineColor: string;
    sparklineCurve: string;
    sparklineArea: string;
}

interface Props {
    cards?: KpiCardItem[];
    selectedCardId?: string;
}

const defaultCards: KpiCardItem[] = [
    {
        id: 'populasi-sapi',
        title: 'Total Populasi Sapi Potong',
        value: '342.895',
        unit: 'ekor',
        trend: '↑ 2,4%',
        trendSuffix: 'vs periode sebelumnya',
        trendColorClass: 'text-[#00D084]',
        iconBoxClass: 'dark:bg-[#00D084]/10 bg-[#00D084]/10 text-[#00D084] rounded-lg p-2',
        iconType: 'cow',
        sparklineColor: '#00D084',
        sparklineCurve: 'M0 18 Q 30 16, 55 11 T 100 4',
        sparklineArea: 'M0 18 Q 30 16, 55 11 T 100 4 L 100 24 L 0 24 Z',
    },
    {
        id: 'total-peternak',
        title: 'Total Peternak',
        value: '12.482',
        trend: '↑ 3,1%',
        trendSuffix: 'vs periode sebelumnya',
        trendColorClass: 'text-[#32B9D0]',
        iconBoxClass: 'dark:bg-[#32B9D0]/10 bg-[#32B9D0]/10 text-[#32B9D0] rounded-lg p-2',
        iconType: 'farmer',
        sparklineColor: '#32B9D0',
        sparklineCurve: 'M0 19 Q 28 17, 60 12 T 100 5',
        sparklineArea: 'M0 19 Q 28 17, 60 12 T 100 5 L 100 24 L 0 24 Z',
    },
    {
        id: 'kasus-pmk',
        title: 'Kasus Aktif PMK',
        value: '52',
        unit: 'kasus',
        trend: '↑ 12,7%',
        trendSuffix: 'vs periode sebelumnya',
        trendColorClass: 'text-[#DC625F]',
        iconBoxClass: 'dark:bg-[#DC625F]/10 bg-[#DC625F]/10 text-[#DC625F] rounded-lg p-2',
        iconType: 'pmk',
        sparklineColor: '#DC625F',
        sparklineCurve: 'M0 20 Q 35 18, 65 9 T 100 3',
        sparklineArea: 'M0 20 Q 35 18, 65 9 T 100 3 L 100 24 L 0 24 Z',
    },
    {
        id: 'kasus-lsd',
        title: 'Kasus Aktif LSD',
        value: '32',
        unit: 'kasus',
        trend: '↑ 9,4%',
        trendSuffix: 'vs periode sebelumnya',
        trendColorClass: 'text-[#DC625F]',
        iconBoxClass: 'dark:bg-[#DC625F]/10 bg-[#DC625F]/10 text-[#DC625F] rounded-lg p-2',
        iconType: 'lsd',
        sparklineColor: '#DC625F',
        sparklineCurve: 'M0 19 Q 30 18, 65 11 T 100 5',
        sparklineArea: 'M0 19 Q 30 18, 65 11 T 100 5 L 100 24 L 0 24 Z',
    },
    {
        id: 'fasilitas-rph',
        title: 'Fasilitas RPH',
        value: '8',
        unit: 'lokasi',
        trend: '↑ 0,0%',
        trendSuffix: 'tetap',
        trendColorClass: 'dark:text-[#B3B3B3] text-[#555E54]',
        iconBoxClass: 'dark:bg-[#181818] bg-[#F4F5F2] dark:text-[#00D084] text-[#171B17] rounded-lg p-2',
        iconType: 'rph',
        sparklineColor: '#00D084',
        sparklineCurve: 'M0 13 Q 30 11, 60 15 T 100 13',
        sparklineArea: 'M0 13 Q 30 11, 60 15 T 100 13 L 100 24 L 0 24 Z',
    },
];

const props = withDefaults(defineProps<Props>(), {
    selectedCardId: 'populasi-sapi',
});

const emit = defineEmits<{
    (e: 'selectCard', id: string): void;
}>();

const currentSelectedId = ref(props.selectedCardId);

watch(
    () => props.selectedCardId,
    (newVal) => {
        if (newVal) currentSelectedId.value = newVal;
    }
);

function handleCardClick(id: string): void {
    currentSelectedId.value = id;
    emit('selectCard', id);
}

const displayCards = computed<KpiCardItem[]>(() => {
    return props.cards && props.cards.length > 0 ? props.cards : defaultCards;
});
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 w-full">
        <div
            v-for="(card, index) in displayCards"
            :key="card.id"
            @click="handleCardClick(card.id)"
            class="rounded-xl p-4 shadow-xs transition-all flex flex-col justify-between group cursor-pointer relative"
            :class="[
                currentSelectedId === card.id
                    ? 'border-2 border-[#171B17] bg-white dark:border-[#00D084] dark:bg-[#111111] shadow-sm'
                    : 'dark:bg-[#111111] bg-white border dark:border-[#1E1E1E] border-[#E0E4DD] hover:dark:border-[#00D084]/40 hover:border-[#171B17]/40',
            ]"
        >
            <!-- Card Header: Icon & Selected Checkmark -->
            <div class="flex items-center justify-between mb-3">
                <div :class="['w-9 h-9 flex items-center justify-center', card.iconBoxClass]">
                    <!-- Cow Icon (Card 1) -->
                    <svg
                        v-if="card.iconType === 'cow'"
                        class="w-4 h-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M4 6 C4 4, 2 3, 2 3 M20 6 C20 4, 22 3, 22 3 M5 8 L19 8 C20 8, 21 9, 21 11 L21 15 C21 18, 18 20, 15 20 L9 20 C6 20, 3 18, 3 15 L3 11 C3 9, 4 8, 5 8 Z" />
                    </svg>

                    <!-- Farmer / UserCheck Icon (Card 2) -->
                    <UserCheck
                        v-else-if="card.iconType === 'farmer'"
                        class="w-4 h-4"
                    />

                    <!-- Kasus PMK / AlertTriangle Icon (Card 3) -->
                    <AlertTriangle
                        v-else-if="card.iconType === 'pmk'"
                        class="w-4 h-4"
                    />

                    <!-- Kasus LSD / ShieldAlert Icon (Card 4) -->
                    <ShieldAlert
                        v-else-if="card.iconType === 'lsd'"
                        class="w-4 h-4"
                    />

                    <!-- Fasilitas RPH / Building2 Icon (Card 5) -->
                    <Building2
                        v-else-if="card.iconType === 'rph'"
                        class="w-4 h-4"
                    />
                </div>

                <!-- Emerald checkmark indicator when card is selected -->
                <div
                    v-if="currentSelectedId === card.id"
                    class="flex items-center justify-center w-5 h-5 rounded-full bg-[#00D084]/15 border border-[#00D084]/30 text-[#00D084]"
                    title="Pilihan Aktif"
                >
                    <Check class="w-3.5 h-3.5 stroke-[2.5]" />
                </div>
            </div>

            <!-- Card Body: Title & Big Metric Value -->
            <div>
                <span class="text-xs dark:text-[#858585] text-[#737B71] font-medium block truncate">
                    {{ card.title }}
                </span>
                <div class="flex items-baseline gap-1.5 mt-1">
                    <span class="font-bold font-mono text-2xl dark:text-[#F5F5F5] text-[#171B17] tabular-nums">
                        {{ card.value }}
                    </span>
                    <span v-if="card.unit" class="text-xs dark:text-[#858585] text-[#737B71] font-mono">
                        {{ card.unit }}
                    </span>
                </div>
            </div>

            <!-- Sparkline Graphic: Subtle Gradient Fill + Curve Stroke -->
            <div class="w-full h-7 flex items-end mt-2.5">
                <svg class="w-full h-6" viewBox="0 0 100 24" preserveAspectRatio="none">
                    <defs>
                        <linearGradient :id="`sparkline-grad-${card.id}-${index}`" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" :stop-color="card.sparklineColor" stop-opacity="0.22" />
                            <stop offset="100%" :stop-color="card.sparklineColor" stop-opacity="0.0" />
                        </linearGradient>
                    </defs>
                    <path
                        :d="card.sparklineArea"
                        :fill="`url(#sparkline-grad-${card.id}-${index})`"
                    />
                    <path
                        :d="card.sparklineCurve"
                        fill="none"
                        :stroke="card.sparklineColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    />
                </svg>
            </div>

            <!-- Card Footer: Trend & Period Comparison -->
            <div class="flex items-center gap-1.5 mt-2 pt-2 border-t dark:border-[#1E1E1E] border-[#E0E4DD]">
                <span :class="[card.trendColorClass, 'font-mono font-semibold text-[11px] tabular-nums']">
                    {{ card.trend }}
                </span>
                <span class="dark:text-[#858585] text-[#737B71] text-[10px]">
                    {{ card.trendSuffix }}
                </span>
            </div>
        </div>
    </div>
</template>
