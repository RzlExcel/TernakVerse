# Project facts: TernakVerse

Facts about this repository only, checked on 2026-11-06. Kit behaviour in
`.agents/GEMINI.md` and `.agents/rules/`. Refresh with `/onboard-repo` on stack changes.

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
<<<<<<< HEAD
- Ingest knowledge: `php artisan ternakverse:ingest-knowledge {path?}`
- Test: `php artisan test` or `php artisan test --filter=UiUxTransformationVerificationTest`
=======
- Test: `php artisan test`
>>>>>>> 93d471c88545259e539c67b3b302cdea6148aed8
- Everything: `node .agents/scripts/verify.mjs`

## Conventions & Gotchas
- Dark Geospatial Command Center: fixed viewport layout uses `h-[calc(100vh-56px)]` on main canvas with root `overflow-hidden` to avoid double scrollbars
<<<<<<< HEAD
- GeoJSON satellite layers: semi-transparent choropleth fills (0.45-0.55 opacity, #00B98B outline, weight 3.5) preserve terrain visibility
- Additive design tokens: new palette variables in `tailwind.config.js` and `app.css` coexist with legacy tokens to preserve regression tests
- Inertia component assertions in PHPUnit: pass `false` as second argument (`$page->component('Public/Portal', false)`) to avoid case mismatches
- Cross-portal session persistence: dual server session fallback in `HandleInertiaRequests` with client `localStorage` maintains Super Admin identity
- Database seeding: guard engine-specific DDL/truncation with `DB::getDriverName() === 'pgsql'` to preserve SQLite test compatibility
- Vite dev server on Windows: explicitly bind `server.host` to '127.0.0.1' in `vite.config.ts` to avoid IPv6 loopback timeouts
- Multi-role RBAC: supported roles include superadmin, admin_dinas, admin, and stakeholder; auth checks must allow all permitted roles
- Geospatial Analytics Performance: Dinas aggregations cached for 300s via `Cache::remember` with composite indexes; filter updates use Inertia partial reloads (`only: [...]`)

=======
- GeoJSON satellite layers: semi-transparent choropleth fills (0.45-0.55 opacity) with emerald outline (#00B98B, weight 3.5) preserve terrain visibility
- Additive design tokens: new palette variables in `tailwind.config.js` and `app.css` must coexist with legacy tokens to preserve regression tests
- Inertia component name assertions in PHPUnit: pass `false` as second argument (`$page->component('Public/Portal', false)`) to avoid case sensitivity mismatches
- Cross-portal session persistence: dual server session fallback in `HandleInertiaRequests` with client `localStorage` maintains Super Admin identity across external views
- Database seeding: guard engine-specific DDL/truncation with `DB::getDriverName() === 'pgsql'` to preserve SQLite test environment compatibility
- Vite dev server on Windows: explicitly bind server.host to '127.0.0.1' in vite.config.ts to avoid IPv6 loopback connection timeouts
- Multi-role RBAC: supported roles include superadmin, admin_dinas, admin, and stakeholder; authorization checks must allow all permitted roles instead of strict single-role equality
- Geospatial Analytics: Dinas dashboard aggregations cached 300s via Cache::remember with composite indexes; filter updates use Inertia partial reloads (only: [...]) to avoid re-fetching GeoJSON
- Quarterly Survey Sync: POST /admin/api/survey-quarterly upserts populasi_ternak and kasus_penyakit by year and triwulan; non-negative zero counts ($count >= 0) allow metric resets
- System Governance & Security: /admin/api/governance endpoints require superadmin role and CheckIpAccessAndMaintenance firewall; maintenance mode bypasses Super Admin and auth routes
>>>>>>> 93d471c88545259e539c67b3b302cdea6148aed8
