<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700..800&family=Plus+Jakarta+Sans:wght@400;500;700&display=swap" rel="stylesheet">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="bg-[#0A0D0E] text-[#F4F7F5] antialiased selection:bg-[#39FF14] selection:text-[#04110A]" style="font-family: 'Plus Jakarta Sans', system-ui, sans-serif;">
        @inertia
    </body>
</html>
