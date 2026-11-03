<script setup lang="ts">
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbPage, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';
import { Map } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-4 border-b border-[#E0E4DD] dark:border-[#1E1E1E] bg-white px-6 transition-[width,height] ease-linear group-has-[[data-collapsible=icon]]/sidebar-wrapper:h-12 md:px-4 dark:bg-[#111111]"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1 text-slate-600 hover:text-slate-900 dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]" />
            <template v-if="props.breadcrumbs.length > 0">
                <div class="mx-1 h-4 w-px bg-[#E0E4DD] dark:bg-[#1E1E1E]" />
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in props.breadcrumbs" :key="index">
                            <BreadcrumbItem>
                                <template v-if="index === props.breadcrumbs.length - 1">
                                    <BreadcrumbPage class="font-semibold text-slate-900 dark:text-[#F5F5F5]">
                                        {{ item.title }}
                                    </BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href ?? '#'" class="text-slate-500 hover:text-slate-900 dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5]">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </template>
        </div>

        <div class="flex shrink-0 items-center gap-3">
            <div
                class="hidden items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 sm:flex dark:border-emerald-500/30 dark:bg-emerald-500/15 dark:text-[#00D084]"
            >
                <span class="size-1.5 rounded-full bg-emerald-500" />
                <span>Kabupaten Banyumas • Dinas Perikanan &amp; Peternakan</span>
            </div>

            <!-- ThemeToggle (Compact) -->
            <ThemeToggle size="sm" compact />

            <Link
                href="/"
                class="inline-flex items-center gap-1.5 rounded-lg border border-[#E0E4DD] dark:border-[#1E1E1E] bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:border-emerald-500/30 hover:bg-emerald-500/10 hover:text-emerald-700 dark:bg-[#181818] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#1E1E1E]"
            >
                <Map class="size-3.5 text-emerald-600 dark:text-[#00D084]" />
                <span class="hidden md:inline">Peta WebGIS</span>
            </Link>
        </div>
    </header>
</template>
