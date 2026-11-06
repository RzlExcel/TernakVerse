<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Radio, X } from 'lucide-vue-next';
import type { EmergencyBannerConfig } from '../../../../composables/ternakverse/admin/useAdminGovernance';

const props = defineProps<{
    banner?: EmergencyBannerConfig;
}>();

const page = usePage();
const isDismissed = ref(false);

const bannerConfig = computed<EmergencyBannerConfig | undefined>(() => {
    const rawProps = page.props as Record<string, unknown>;
    const pageBanner = rawProps.emergency_banner as EmergencyBannerConfig | undefined;
    if (pageBanner && typeof pageBanner === 'object' && pageBanner.enabled && pageBanner.message) {
        return pageBanner;
    }
    return props.banner;
});

const isVisible = computed(() => {
    return !isDismissed.value && Boolean(bannerConfig.value?.enabled && bannerConfig.value?.message);
});
</script>

<template>
    <div
        v-if="isVisible && bannerConfig"
        class="mb-4 p-3.5 rounded-xl border flex items-center justify-between gap-3 shadow-xs transition-all"
        :class="[
            bannerConfig.level === 'danger' || bannerConfig.level === 'alert'
                ? 'bg-rose-500/10 border-rose-500/30 text-rose-700 dark:text-rose-300'
                : bannerConfig.level === 'warning'
                ? 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300'
                : 'bg-sky-500/10 border-sky-500/30 text-sky-700 dark:text-sky-300'
        ]"
        role="alert"
    >
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="flex h-2.5 w-2.5 relative flex-shrink-0">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                    :class="bannerConfig.level === 'warning' ? 'bg-amber-400' : bannerConfig.level === 'info' ? 'bg-sky-400' : 'bg-rose-400'"
                ></span>
                <span
                    class="relative inline-flex rounded-full h-2.5 w-2.5"
                    :class="bannerConfig.level === 'warning' ? 'bg-amber-500' : bannerConfig.level === 'info' ? 'bg-sky-500' : 'bg-rose-500'"
                ></span>
            </span>
            <Radio class="w-4 h-4 flex-shrink-0" />
            <div class="text-xs font-semibold leading-relaxed truncate sm:text-clip">
                <span class="font-bold uppercase tracking-wider mr-1.5 text-[10px] px-1.5 py-0.5 rounded bg-black/10 dark:bg-white/10">
                    {{ bannerConfig.level === 'danger' || bannerConfig.level === 'alert' ? 'Siaran Darurat' : bannerConfig.level === 'warning' ? 'Peringatan' : 'Pengumuman' }}
                </span>
                {{ bannerConfig.message }}
            </div>
        </div>
        <button
            type="button"
            @click="isDismissed = true"
            class="p-1 rounded-lg hover:bg-black/10 dark:hover:bg-white/10 text-current flex-shrink-0 cursor-pointer transition-colors"
            aria-label="Tutup banner siaran darurat"
        >
            <X class="w-4 h-4" />
        </button>
    </div>
</template>
