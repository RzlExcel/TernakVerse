import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { initializeTheme } from './composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'TERNAKVERSE WebGIS Banyumas';

<<<<<<< Updated upstream
const getInitialPage = () => {
    if (typeof window === 'undefined') return undefined;
    const el = document.getElementById('app');
    if (el?.dataset?.page) {
        try {
            return JSON.parse(el.dataset.page);
        } catch {
            // ignore
=======
void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
            case name.startsWith('Public/'):
            case name.startsWith('Internal/'):
            case name === 'DesignRule':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
>>>>>>> Stashed changes
        }
    }
    const script = document.querySelector('script[data-page="app"]');
    if (script?.textContent) {
        try {
            return JSON.parse(script.textContent);
        } catch {
            // ignore
        }
    }
    return undefined;
};

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    page: getInitialPage(),
    resolve: async (name) => {
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');
        if (pages[`./Pages/${name}.vue`]) {
            return (await pages[`./Pages/${name}.vue`]()).default;
        }
        // Fallback pencarian case-insensitive (Public/Portal vs public/portal, dsb)
        const match = Object.keys(pages).find(
            (p) => p.toLowerCase() === `./pages/${name.toLowerCase()}.vue`
        );
        if (match) {
            return (await pages[match]()).default;
        }
        return (await resolvePageComponent(`./Pages/${name}.vue`, pages));
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#39FF14',
    },
});

initializeTheme();
