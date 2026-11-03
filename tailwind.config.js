import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Bricolage Grotesque', 'sans-serif'],
                ui: ['Plus Jakarta Sans', 'sans-serif'],
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
                '12': '12px',
                '16': '16px',
                '20': '20px',
                '24': '24px',
                'full': '999px',
            },
            colors: {
                // Legacy & Additive Tokens (preserved for regression test compatibility)
                bg: '#090D16',
                panel: '#0F172A',
                neon: '#10B981',
                cyan: '#0EA5E9',
                'kelas-1': '#134E4A',
                'kelas-2': '#0F766E',
                'kelas-3': '#059669',
                'kelas-4': '#10B981',
                'kelas-5': '#34D399',
                bahaya: 'var(--color-bahaya, #DC625F)',
                waspada: 'var(--color-waspada, #E8B84C)',
                aman: 'var(--color-aman, #20B88A)',
                teks: '#F8FAFC',
                'teks-redup': '#94A3B8',
                'teks-di-neon': '#FFFFFF',
                garis: '#1E293B',
                'garis-terang': '#334155',

                // Pure Obsidian Semantic Tokens (Fallbacks)
                'color-bg': 'var(--color-bg, #080808)',
                'color-surface': 'var(--color-surface, #111111)',
                'color-surface-raised': 'var(--color-surface-raised, #181818)',
                'color-surface-hover': 'var(--color-surface-hover, #1E1E1E)',
                'color-surface-active': 'var(--color-surface-active, #242424)',
                'color-border': 'var(--color-border, #1E1E1E)',
                'color-border-strong': 'var(--color-border-strong, #282828)',
                'color-primary': 'var(--color-primary, #00D084)',
                'color-primary-hover': 'var(--color-primary-hover, #00F0A0)',
                'color-primary-active': 'var(--color-primary-active, #00A86B)',
                'color-primary-soft': 'var(--color-primary-soft, #063D2D)',
                'color-primary-text': 'var(--color-primary-text, #062117)',
                'color-accent': '#39D6C0',
                'color-accent-soft': '#123D43',
                'color-text': 'var(--color-text, #F5F5F5)',
                'color-text-primary': 'var(--color-text-primary, #F5F5F5)',
                'color-text-secondary': 'var(--color-text-secondary, #B3B3B3)',
                'color-text-muted': 'var(--color-text-muted, #737373)',
                'color-text-disabled': 'var(--color-text-disabled, #555555)',
                'color-success': '#35C99A',
                'color-warning': '#F2B84B',
                'color-danger': '#F06470',
                'color-info': '#60A5FA',
                'color-choro-1': 'var(--color-choro-1, #064B50)',
                'color-choro-2': 'var(--color-choro-2, #08736B)',
                'color-choro-3': 'var(--color-choro-3, #159B78)',
                'color-choro-4': 'var(--color-choro-4, #36C99B)',
                'color-choro-5': 'var(--color-choro-5, #75E6C5)',

                // Pure Obsidian Palette Extension
                obsidian: {
                    bg: '#080808',
                    'bg-secondary': '#0C0C0C',
                    surface: '#111111',
                    raised: '#181818',
                    hover: '#1E1E1E',
                    active: '#242424',
                    border: '#1E1E1E',
                    'border-strong': '#282828',
                },

                // Supporting Organic Earth Tokens
                harvest: 'var(--color-harvest, #F0A52B)',
                terracotta: 'var(--color-terracotta, #F07845)',
                teal: 'var(--color-teal, #32B9D0)',
                lime: 'var(--color-lime, #A3D94A)',
                moss: 'var(--color-moss, #354B35)',
                'color-harvest': 'var(--color-harvest, #F0A52B)',
                'color-terracotta': 'var(--color-terracotta, #F07845)',
                'color-teal': 'var(--color-teal, #32B9D0)',
                'color-lime': 'var(--color-lime, #A3D94A)',
                'color-moss': 'var(--color-moss, #354B35)',

                // Choropleth Multicolor 5-class Scale
                choro: {
                    1: 'var(--color-choro-1, #3B82A0)',
                    2: 'var(--color-choro-2, #38A69A)',
                    3: 'var(--color-choro-3, #86A65A)',
                    4: 'var(--color-choro-4, #D4A34E)',
                    5: 'var(--color-choro-5, #D9784D)',
                },

                // Disease Classification Statuses
                status: {
                    aman: 'var(--color-aman, #20B88A)',
                    waspada: 'var(--color-waspada, #E8B84C)',
                    bahaya: 'var(--color-bahaya, #DC625F)',
                },
                'color-aman': 'var(--color-aman, #20B88A)',
                'color-waspada': 'var(--color-waspada, #E8B84C)',
                'color-bahaya': 'var(--color-bahaya, #DC625F)',

                // Semantic Theme Tokens (Dark: Obsidian × Electric Emerald, Light: Porcelain × Charcoal)
                surface: {
                    DEFAULT: 'var(--color-surface, #111111)',
                    raised: 'var(--color-surface-raised, #181818)',
                    hover: 'var(--color-surface-hover, #1E1E1E)',
                    active: 'var(--color-surface-active, #242424)',
                },
                'surface-raised': 'var(--color-surface-raised, #181818)',
                'surface-hover': 'var(--color-surface-hover, #1E1E1E)',
                'surface-active': 'var(--color-surface-active, #242424)',

                // Semantic Text Tokens
                text: {
                    DEFAULT: 'var(--color-text, #F5F5F5)',
                    primary: 'var(--color-text-primary, #F5F5F5)',
                    secondary: 'var(--color-text-secondary, #B3B3B3)',
                    muted: 'var(--color-text-muted, #737373)',
                    disabled: 'var(--color-text-disabled, #555555)',
                },

                // GIS namespaces & aliases (Legacy Regression Protection)
                gis: {
                    bg: '#0B1420',
                    surface: '#101D2B',
                    'surface-raised': '#152538',
                    'surface-hover': '#1B3044',
                    border: '#23394D',
                    'border-strong': '#31516A',
                    primary: {
                        DEFAULT: '#159B78',
                        hover: '#1BB18A',
                        soft: '#103F3A',
                    },
                    accent: {
                        DEFAULT: '#39D6C0',
                        soft: '#123D43',
                    },
                    text: {
                        DEFAULT: '#E8F0F6',
                        secondary: '#A6B7C8',
                        muted: '#71869A',
                    },
                    success: '#35C99A',
                    warning: '#F2B84B',
                    danger: '#F06470',
                    info: '#60A5FA',
                    choro: {
                        1: '#064B50',
                        2: '#08736B',
                        3: '#159B78',
                        4: '#36C99B',
                        5: '#75E6C5',
                    },
                },
                background: 'hsl(var(--background))',
                foreground: 'hsl(var(--foreground))',
                card: {
                    DEFAULT: 'hsl(var(--card))',
                    foreground: 'hsl(var(--card-foreground))',
                },
                popover: {
                    DEFAULT: 'hsl(var(--popover))',
                    foreground: 'hsl(var(--popover-foreground))',
                },
                primary: {
                    DEFAULT: 'var(--color-primary, hsl(var(--primary)))',
                    foreground: 'var(--color-primary-text, hsl(var(--primary-foreground)))',
                    hover: 'var(--color-primary-hover)',
                    active: 'var(--color-primary-active)',
                    soft: 'var(--color-primary-soft)',
                    text: 'var(--color-primary-text)',
                },
                secondary: {
                    DEFAULT: 'hsl(var(--secondary))',
                    foreground: 'hsl(var(--secondary-foreground))',
                },
                muted: {
                    DEFAULT: 'hsl(var(--muted))',
                    foreground: 'hsl(var(--muted-foreground))',
                },
                accent: {
                    DEFAULT: 'hsl(var(--accent))',
                    foreground: 'hsl(var(--accent-foreground))',
                },
                destructive: {
                    DEFAULT: 'hsl(var(--destructive))',
                    foreground: 'hsl(var(--destructive-foreground))',
                },
                border: {
                    DEFAULT: 'var(--color-border, hsl(var(--border)))',
                    strong: 'var(--color-border-strong)',
                },
                'border-border': 'var(--color-border, hsl(var(--border)))',
                'border-strong': 'var(--color-border-strong)',
                input: 'hsl(var(--input))',
                ring: 'hsl(var(--ring))',
                chart: {
                    1: 'hsl(var(--chart-1))',
                    2: 'hsl(var(--chart-2))',
                    3: 'hsl(var(--chart-3))',
                    4: 'hsl(var(--chart-4))',
                    5: 'hsl(var(--chart-5))',
                },
                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background))',
                    foreground: 'hsl(var(--sidebar-foreground))',
                    primary: 'hsl(var(--sidebar-primary))',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
                    accent: 'hsl(var(--sidebar-accent))',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
                    border: 'hsl(var(--sidebar-border))',
                    ring: 'hsl(var(--sidebar-ring))',
                },
            },
        },
    },
    plugins: [require('tailwindcss-animate')],
};
