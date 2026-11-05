<script setup lang="ts">
import { computed } from 'vue';
import { Sun, Moon } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';

interface ThemeToggleProps {
    size?: 'sm' | 'md' | 'lg';
    compact?: boolean;
}

const props = withDefaults(defineProps<ThemeToggleProps>(), {
    size: 'md',
    compact: false,
});

const { updateAppearance, resolvedAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');

function toggleTheme(): void {
    const next = isDark.value ? 'light' : 'dark';
    updateAppearance(next);
}

const buttonSizeClasses = computed(() => {
    if (props.compact) {
        switch (props.size) {
            case 'sm':
                return 'w-7 h-7';
            case 'lg':
                return 'w-10 h-10';
            case 'md':
            default:
                return 'w-9 h-9';
        }
    }

    switch (props.size) {
        case 'sm':
            return 'h-7 px-2.5 text-xs gap-1.5';
        case 'lg':
            return 'h-10 px-3.5 text-sm gap-2.5';
        case 'md':
        default:
            return 'h-9 px-3 text-xs gap-2';
    }
});

const iconSizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'w-3.5 h-3.5';
        case 'lg':
            return 'w-5 h-5';
        case 'md':
        default:
            return 'w-4 h-4';
    }
});

const titleText = computed(() => {
    return isDark.value ? 'Mode Terang' : 'Mode Gelap';
});

const ariaLabelText = computed(() => {
    return isDark.value ? 'Mode Terang' : 'Mode Gelap';
});
</script>

<template>
    <button
        type="button"
        @click="toggleTheme"
        :class="[
            buttonSizeClasses,
            'inline-flex items-center justify-center font-medium rounded-lg border transition-colors duration-150 cursor-pointer select-none',
            'focus:outline-none focus-visible:ring-2 focus-visible:ring-[#171B17] dark:focus-visible:ring-[#00D084]',
            'dark:bg-[#111111] dark:hover:bg-[#181818] dark:active:bg-[#1E1E1E] dark:border-[#1E1E1E] dark:hover:border-[#282828] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]',
            'bg-[#FFFFFF] hover:bg-[#F4F5F2] active:bg-[#E8EBE5] border-[#E0E4DD] hover:border-[#C8CEC4] text-[#444C43] hover:text-[#171B17]',
            'shadow-xs'
        ]"
        :title="titleText"
        :aria-label="ariaLabelText"
    >
        <span class="inline-flex items-center justify-center transition-transform duration-150">
            <Sun v-if="isDark" :class="[iconSizeClasses, 'dark:text-[#00D084] text-[#00D084] transition-colors']" />
            <Moon v-else :class="[iconSizeClasses, 'text-[#171B17] transition-colors']" />
        </span>
        <span v-if="!compact" class="select-none leading-none font-semibold">
            {{ isDark ? 'Mode Terang' : 'Mode Gelap' }}
        </span>
    </button>
</template>
