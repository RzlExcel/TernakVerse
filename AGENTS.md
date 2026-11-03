# Project facts: TernakVerse

Facts about this repository only, checked against its files on 2026-10-31. Kit behaviour lives
in `.agents/GEMINI.md` and `.agents/rules/`. Refresh this file with `/onboard-repo` when the stack changes.

## Stack
- Laravel 12 (composer.json: laravel/framework ^12.0, require.php >= 8.2), PHP 8.2+
- Inertia.js v2 (@inertiajs/vue3 ^2.0.3), Vue 3 (vue ^3.5.13)
- Tailwind CSS 3.4 (tailwindcss ^3.4.1), Vite 6 (vite ^6.0.3), TypeScript 5.2
- Package manager: npm (package-lock.json) and composer (composer.lock)
- Map engines: Leaflet (^1.9.4) for satellite/hybrid WebGIS, inline SVG for vector map

## Layout
- `app/` - Laravel backend: Http Controllers, Middleware, Models, Requests, Services
- `resources/js/` - Frontend: Pages (Public, Internal, Admin, Dinas, auth), components, composables, data
- `resources/js/components/ternakverse/` - WebGIS components (LeafletWebGisMap, MapSidebar, WebGisHeader, SelectedRegionPanel)
- `resources/css/app.css` - Global stylesheet with Forest Intelligence GIS tokens and dark mode styling
- `routes/` - `web.php` (Inertia view and operational portal routes), `api.php`, `auth.php`
- `tests/` - PHPUnit / Pest feature tests (`tests/Feature/UiUxTransformationVerificationTest.php`)
- `storage/`, `vendor/`, `node_modules/`, `.agents/` - Runtime, dependencies, and kit infrastructure

## Commands (npm + composer; run from repo root, one per call)
- Install: `composer install` and `npm install`
- Dev / run: `npm run dev` and `php artisan serve`
- Build: `npm run build`
- Typecheck: `npm run types:check`
- Test: `php artisan test` or `php artisan test --filter=UiUxTransformationVerificationTest`
- Everything: `node .agents/scripts/verify.mjs`

## Conventions & Gotchas
- Dark Geospatial Command Center: fixed viewport layout uses `h-[calc(100vh-56px)]` on main canvas with root `overflow-hidden` to avoid double scrollbars
- GeoJSON satellite layers: semi-transparent choropleth fills (0.45-0.55 opacity) with emerald outline (#00B98B, weight 3.5) preserve terrain visibility
- Additive design tokens: new palette variables in `tailwind.config.js` and `app.css` must coexist with legacy tokens to preserve regression tests
- Inertia component name assertions in PHPUnit: pass `false` as second argument (`$page->component('Public/Portal', false)`) to avoid case sensitivity mismatches
