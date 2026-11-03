<script setup lang="ts">
import { Compass } from 'lucide-vue-next';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div
        class="flex aspect-square size-8 shrink-0 items-center justify-center rounded-lg bg-[#059669] text-white shadow-sm ring-1 ring-[#10B981]/30 dark:bg-[#10B981] dark:text-slate-950"
    >
        <Compass class="size-4 stroke-[2.25]" />
    </div>
    <div :class="['ml-1.5 grid flex-1 text-left text-sm leading-tight', $props.class]">
        <span class="truncate font-extrabold tracking-tight text-slate-900 dark:text-slate-100">
            TERNAK<span class="text-[#059669] dark:text-[#10B981]">VERSE</span>
        </span>
        <span class="truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">
            WebGIS Peternakan Banyumas
        </span>
    </div>
</template>
