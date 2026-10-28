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

    <!-- Google Fonts: Inter, Outfit, & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="font-sans antialiased bg-[#0A0D0E] text-[#F0F6FC]">
    @inertia
</body>
</html>
