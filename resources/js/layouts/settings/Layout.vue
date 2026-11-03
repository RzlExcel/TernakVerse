<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { KeyRound, Palette, ShieldCheck, User } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profil Akun',
        href: '/settings/profile',
        icon: User,
    },
    {
        title: 'Kata Sandi',
        href: '/settings/password',
        icon: KeyRound,
    },
    {
        title: 'Keamanan & 2FA',
        href: '/settings/security',
        icon: ShieldCheck,
    },
    {
        title: 'Tampilan Tema',
        href: '/settings/appearance',
        icon: Palette,
    },
];

const isActiveTab = computed(() => (href: string) => page.url.startsWith(href));
</script>

<template>
    <div class="px-4 py-6 sm:px-6">
        <Heading
            title="Pengaturan Akun & Keamanan"
            description="Kelola profil pengguna, kata sandi, autentikasi dua faktor (2FA), dan preferensi tema antarmuka TernakVerse."
        />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-10 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-56">
                <nav class="flex flex-col space-x-0 space-y-1.5 rounded-xl border border-border bg-white p-2 dark:bg-[#111111]">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="[
                            'w-full justify-start gap-2.5 rounded-lg px-3 py-2 text-sm transition-colors',
                            isActiveTab(item.href)
                                ? 'bg-[#171B17] text-white font-bold shadow-xs border border-[#171B17] dark:bg-[#181818] dark:text-[#00D084]'
                                : 'border border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-100',
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            <component
                                :is="item.icon"
                                v-if="item.icon"
                                :class="[
                                    'size-4 shrink-0',
                                    isActiveTab(item.href) ? 'text-[#00D084]' : 'text-slate-400 dark:text-slate-500',
                                ]"
                            />
                            <span>{{ item.title }}</span>
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-10 rounded-xl border border-border bg-white p-6 shadow-sm dark:bg-[#111111]">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
