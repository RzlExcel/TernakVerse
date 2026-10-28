<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'TERNAKVERSE WebGIS Banyumas') }}</title>

    {{-- Script deteksi preferensi tema (Light/Dark/System) --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? "system" }}';
            if (appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            } else if (appearance === 'light') {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    {{-- Fallback warna dasar antarmuka --}}
    <style>
        html {
            background-color: #0A0D0E;
        }
        html:not(.dark) {
            background-color: #F8FAFC;
        }
    </style>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    <!-- Google Fonts: Bricolage Grotesque, Plus Jakarta Sans, Inter, JetBrains Mono, Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700..800&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">

    <!-- Leaflet CSS for WebGIS maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="bg-[#0A0D0E] text-[#F4F7F5] antialiased selection:bg-[#39FF14] selection:text-[#04110A]" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
    @inertia
</body>
</html>
