<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Compass,
    Database,
    FolderKanban,
    LayoutGrid,
    Map,
    Settings,
    ShieldCheck,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

interface NavGroup {
    label: string;
    items: NavItem[];
}

const page = usePage();
const user = computed(() => (page.props.auth as { user?: { role?: string } } | undefined)?.user);

const overviewNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Portal Publik WebGIS',
        href: '/',
        icon: Map,
    },
];

const dinasNavItems: NavItem[] = [
    {
        title: 'Analitik Eksekutif Dinas',
        href: '/dinas/dashboard',
        icon: BarChart3,
    },
    {
        title: 'WebGIS Kuantitatif Dinas',
        href: '/internal/dinas',
        icon: Compass,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Panel Kelola Data',
        href: '/admin/panel',
        icon: Database,
    },
    {
        title: 'Portal Operasional Admin',
        href: '/internal/admin',
        icon: FolderKanban,
    },
    {
        title: 'Audit Trail Forensik',
        href: '/admin/audit-logs',
        icon: ShieldCheck,
    },
];

const navGroups = computed<NavGroup[]>(() => {
    const groups: NavGroup[] = [
        {
            label: 'Overview & Geospatial',
            items: overviewNavItems,
        },
        {
            label: 'Operasional Dinas',
            items: dinasNavItems,
        },
    ];

    if (!user.value?.role || user.value?.role === 'admin') {
        groups.push({
            label: 'Administrasi & Forensik',
            items: adminNavItems,
        });
    }

    return groups;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Pengaturan Akun',
        href: '/settings/profile',
        icon: Settings,
    },
    {
        title: 'Panduan Sistem',
        href: '/design-rule',
        icon: BookOpen,
    },
];

const isActiveRoute = (href: string): boolean => {
    const currentUrl = page.url.split('?')[0];
    if (href === '/') {
        return currentUrl === '/';
    }
    if (href === '/settings/profile') {
        return currentUrl.startsWith('/settings');
    }
    return currentUrl === href || currentUrl.startsWith(`${href}/`);
};
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="border-r border-border dark:border-[#1E1E1E] bg-white dark:bg-[#111111]">
        <SidebarHeader class="border-b border-border p-2">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-4 p-2">
            <SidebarGroup v-for="group in navGroups" :key="group.label" class="p-0">
                <SidebarGroupLabel class="px-2 py-1.5 text-xs font-semibold tracking-normal text-slate-500 dark:text-[#858585]">
                    {{ group.label }}
                </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu class="gap-1">
                        <SidebarMenuItem v-for="item in group.items" :key="item.href">
                            <SidebarMenuButton
                                as-child
                                :is-active="isActiveRoute(item.href)"
                                :tooltip="item.title"
                                :class="
                                    isActiveRoute(item.href)
                                        ? 'bg-[#171B17] text-white font-semibold shadow-xs dark:bg-[#181818] dark:text-white'
                                        : 'border border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-[#1C1C1C] dark:hover:text-slate-100'
                                "
                            >
                                <Link :href="item.href" class="flex items-center gap-2.5">
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        :class="[
                                            'size-4 shrink-0',
                                            isActiveRoute(item.href)
                                                ? 'text-[#00D084]'
                                                : 'text-slate-400 dark:text-slate-500',
                                        ]"
                                    />
                                    <span class="truncate">{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="gap-2 border-t border-border p-2">
            <SidebarGroup class="p-0 group-data-[collapsible=icon]:p-0">
                <SidebarGroupContent>
                    <SidebarMenu class="gap-1">
                        <SidebarMenuItem v-for="item in footerNavItems" :key="item.href">
                            <SidebarMenuButton
                                as-child
                                :is-active="isActiveRoute(item.href)"
                                :tooltip="item.title"
                                :class="
                                    isActiveRoute(item.href)
                                        ? 'bg-[#171B17] text-white font-semibold shadow-xs dark:bg-[#181818] dark:text-white'
                                        : 'border border-transparent text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-[#1C1C1C] dark:hover:text-slate-100'
                                "
                            >
                                <Link :href="item.href" class="flex items-center gap-2.5">
                                    <component
                                        :is="item.icon"
                                        v-if="item.icon"
                                        :class="[
                                            'size-4 shrink-0',
                                            isActiveRoute(item.href)
                                                ? 'text-[#00D084]'
                                                : 'text-slate-400 dark:text-slate-500',
                                        ]"
                                    />
                                    <span class="truncate">{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
