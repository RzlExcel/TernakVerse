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
- `app/Services/Chatbot/` - Cognitive Architecture: RAG + ReAct function calling, multi-engine LLM adapter, geospatial tools
- `resources/js/` - Frontend: Pages (Public, Internal, Admin, Dinas, auth), components, composables, data
- `resources/js/components/ternakverse/` - WebGIS components (LeafletWebGisMap, MapSidebar, WebGisHeader, SelectedRegionPanel), ChatbotDrawer
- `resources/css/app.css` - Global stylesheet with Forest Intelligence GIS tokens and dark mode styling
- `config/chatbot.php` - Multi-engine LLM provider, vector embedding, and similarity search configuration
- `routes/` - `web.php` (Inertia view and operational portal routes), `api.php` (chatbot streaming/feedback/history), `auth.php`
- `tests/` - PHPUnit / Pest feature tests (`tests/Feature/UiUxTransformationVerificationTest.php`, `tests/Feature/Chatbot/`)
- `storage/`, `vendor/`, `node_modules/`, `.agents/` - Runtime, dependencies, and kit infrastructure

## Commands (npm + composer; run from repo root, one per call)
- Install: `composer install` and `npm install`
- Dev / run: `npm run dev` and `php artisan serve`
- Build: `npm run build`
- Typecheck: `npm run types:check`
- Ingest knowledge: `php artisan ternakverse:ingest-knowledge {path?}`
- Test: `php artisan test` or `php artisan test --filter=UiUxTransformationVerificationTest`
- Everything: `node .agents/scripts/verify.mjs`

## Conventions & Gotchas
- Dark Geospatial Command Center: fixed viewport layout uses `h-[calc(100vh-56px)]` on main canvas with root `overflow-hidden` to avoid double scrollbars
- GeoJSON satellite layers: semi-transparent choropleth fills (0.45-0.55 opacity, #00B98B outline, weight 3.5) preserve terrain visibility
- Additive design tokens: new palette variables in `tailwind.config.js` and `app.css` coexist with legacy tokens to preserve regression tests
- Inertia component assertions in PHPUnit: pass `false` as second argument (`$page->component('Public/Portal', false)`) to avoid case mismatches
- Cross-portal session persistence: dual server session fallback in `HandleInertiaRequests` with client `localStorage` maintains Super Admin identity
- Database seeding: guard engine-specific DDL/truncation with `DB::getDriverName() === 'pgsql'` to preserve SQLite test compatibility
- Vite dev server on Windows: explicitly bind `server.host` to '127.0.0.1' in `vite.config.ts` to avoid IPv6 loopback timeouts
- Multi-role RBAC: supported roles include superadmin, admin_dinas, admin, and stakeholder; auth checks must allow all permitted roles
- Geospatial Analytics Performance: Dinas aggregations cached for 300s via `Cache::remember` with composite indexes; filter updates use Inertia partial reloads (`only: [...]`)

