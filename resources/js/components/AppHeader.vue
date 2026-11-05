<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue';
import ThemeToggle from '@/components/ternakverse/ThemeToggle.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Breadcrumb,
    BreadcrumbItem as UiBreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuLink,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { getInitials } from '@/composables/useInitials';
import type { BreadcrumbItem, NavItem, User } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Compass,
    Database,
    LayoutGrid,
    Map,
    Menu,
    Settings,
    ShieldCheck,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItem[];
}

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const user = computed<User | undefined>(() => (page.props.auth as { user?: User } | undefined)?.user);

const isCurrentRoute = (url: string): boolean => {
    const currentUrl = page.url.split('?')[0];
    if (url === '/') {
        return currentUrl === '/';
    }
    return currentUrl === url || currentUrl.startsWith(`${url}/`);
};

const activeItemStyles = computed(
    () => (url: string) =>
        isCurrentRoute(url)
            ? 'bg-[#171B17] text-white font-semibold shadow-xs dark:bg-[#181818] dark:text-[#00D084]'
            : 'text-[#444C43] hover:text-[#171B17] hover:bg-[#EDEFEB] dark:text-[#B3B3B3] dark:hover:text-[#F5F5F5] dark:hover:bg-[#181818]',
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
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
        {
            title: 'Analitik Dinas',
            href: '/dinas/dashboard',
            icon: BarChart3,
        },
        {
            title: 'WebGIS Dinas',
            href: '/internal/dinas',
            icon: Compass,
        },
    ];

    const role = (user.value as (User & { role?: string }) | undefined)?.role;
    if (!role || role === 'admin') {
        items.push(
            {
                title: 'Panel Kelola Data',
                href: '/admin/panel',
                icon: Database,
            },
            {
                title: 'Audit Forensik',
                href: '/admin/audit-logs',
                icon: ShieldCheck,
            },
        );
    }

    return items;
});

const utilityNavItems: NavItem[] = [
    {
        title: 'Panduan Sistem',
        href: '/design-rule',
        icon: BookOpen,
    },
    {
        title: 'Pengaturan Akun',
        href: '/settings/profile',
        icon: Settings,
    },
];
</script>

<template>
    <div>
        <div class="border-b border-[#E0E4DD] dark:border-[#1E1E1E] bg-white dark:bg-[#090909]">
            <div class="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button variant="ghost" size="icon" class="mr-2 h-9 w-9">
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-72 border-r border-[#E0E4DD] dark:border-[#1E1E1E] bg-white p-6 dark:bg-[#111111]">
                            <SheetTitle class="sr-only">Menu Navigasi TernakVerse</SheetTitle>
                            <SheetHeader class="flex justify-start border-b border-border pb-4 text-left">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <AppLogo />
                                </Link>
                            </SheetHeader>
                            <div class="flex h-full flex-1 flex-col justify-between space-y-4 py-6">
                                <nav class="-mx-2 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.href"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors hover:bg-slate-100 dark:hover:bg-[#1C1C1C]"
                                        :class="activeItemStyles(item.href)"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="h-4 w-4 shrink-0" />
                                        <span>{{ item.title }}</span>
                                    </Link>
                                </nav>
                                <div class="space-y-3 border-t border-border pt-4">
                                    <div class="rounded-lg border border-[#E0E4DD] bg-[#F4F5F2] px-3 py-2 text-xs font-medium text-[#444C43] dark:border-[#292929] dark:bg-[#171717] dark:text-[#B3B3B3]">
                                        Kabupaten Banyumas • Dinas Perikanan &amp; Peternakan
                                    </div>
                                    <div class="flex flex-col space-y-1">
                                        <Link
                                            v-for="item in utilityNavItems"
                                            :key="item.href"
                                            :href="item.href"
                                            class="flex items-center space-x-2 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-[#1C1C1C]"
                                        >
                                            <component v-if="item.icon" :is="item.icon" class="h-4 w-4 shrink-0" />
                                            <span>{{ item.title }}</span>
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="route('dashboard')" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-8 flex h-full items-stretch">
                        <NavigationMenuList class="flex h-full items-stretch space-x-1">
                            <NavigationMenuItem v-for="item in mainNavItems" :key="item.href" class="relative flex h-full items-center">
                                <Link :href="item.href">
                                    <NavigationMenuLink
                                        :class="[navigationMenuTriggerStyle(), activeItemStyles(item.href), 'h-9 cursor-pointer px-3 text-xs']"
                                    >
                                        <component v-if="item.icon" :is="item.icon" class="mr-1.5 h-4 w-4" />
                                        {{ item.title }}
                                    </NavigationMenuLink>
                                </Link>
                                <div
                                    v-if="isCurrentRoute(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-[#00D084]"
                                />
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                </div>

                <div class="ml-auto flex items-center space-x-3">
                    <div
                        class="hidden items-center gap-2 rounded-full border border-[#E0E4DD] bg-[#F4F5F2] px-3 py-1 text-xs font-medium text-[#444C43] xl:flex dark:border-[#292929] dark:bg-[#171717] dark:text-[#B3B3B3]"
                    >
                        <span class="size-1.5 rounded-full bg-[#00D084]" />
                        <span>Kabupaten Banyumas • Dinas Perikanan &amp; Peternakan</span>
                    </div>

                    <!-- ThemeToggle (Compact) -->
                    <ThemeToggle size="sm" compact />

                    <div class="hidden items-center space-x-1 lg:flex">
                        <template v-for="item in utilityNavItems" :key="item.href">
                            <TooltipProvider :delay-duration="0">
                                <Tooltip>
                                    <TooltipTrigger>
                                        <Button variant="ghost" size="icon" as-child class="group h-9 w-9 cursor-pointer">
                                            <Link :href="item.href">
                                                <span class="sr-only">{{ item.title }}</span>
                                                <component :is="item.icon" class="size-4 opacity-80 group-hover:opacity-100" />
                                            </Link>
                                        </Button>
                                    </TooltipTrigger>
                                    <TooltipContent>
                                        <p>{{ item.title }}</p>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                    </div>

                    <DropdownMenu v-if="user">
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-emerald-500"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full border border-border">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback class="rounded-full bg-emerald-600 font-semibold text-white dark:bg-emerald-500 dark:text-slate-950">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div v-if="props.breadcrumbs.length > 1" class="flex w-full border-b border-[#E0E4DD] dark:border-[#1E1E1E] bg-slate-50/60 dark:bg-[#111111]/60">
            <div class="mx-auto flex h-11 w-full items-center justify-start px-4 text-slate-500 md:max-w-7xl">
                <Breadcrumb>
                    <BreadcrumbList>
                        <template v-for="(item, index) in props.breadcrumbs" :key="index">
                            <UiBreadcrumbItem>
                                <template v-if="index === props.breadcrumbs.length - 1">
                                    <BreadcrumbPage class="font-medium text-slate-900 dark:text-slate-100">{{ item.title }}</BreadcrumbPage>
                                </template>
                                <template v-else>
                                    <BreadcrumbLink :href="item.href ?? '#'">
                                        {{ item.title }}
                                    </BreadcrumbLink>
                                </template>
                            </UiBreadcrumbItem>
                            <BreadcrumbSeparator v-if="index !== props.breadcrumbs.length - 1" />
                        </template>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    </div>
</template>
