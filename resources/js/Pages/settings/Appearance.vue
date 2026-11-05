<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Tampilan Tema',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Tampilan Tema" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Tampilan Antarmuka" description="Pilih tema Obsidian Gelap, Porselen Terang, atau mengikuti preferensi sistem perangkat Anda." />
                <AppearanceTabs />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
