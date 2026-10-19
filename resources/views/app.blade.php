<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ config('app.name', 'TERNAKVERSE WebGIS Banyumas') }}</title>

    <!-- Google Fonts: Inter & Outfit & JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-void: #0A0D0E;
            --bg-card: #12171A;
            --accent-primary: #39FF14;
            --accent-cyan: #00F0FF;
            --text-main: #F0F6FC;
            --text-muted: #8B949E;
            --border-dim: rgba(255, 255, 255, 0.08);
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            background-color: var(--bg-void);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        code, pre {
            font-family: 'JetBrains Mono', monospace;
        }
    </style>

    @inertiaHead
</head>
<body class="font-sans antialiased bg-[#0A0D0E] text-[#F0F6FC]">
    @inertia
</body>
</html>
