<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class UiUxTransformationVerificationTest extends TestCase
{
    public function test_public_webgis_and_design_rule_routes_render_inertia_pages(): void
    {
        $this->assertFileExists(public_path('build/manifest.json'));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));

        $this->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));

        $this->get('/welcome')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Welcome', false));

        $this->get('/design-rule')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('DesignRule', false));
    }

    public function test_internal_operational_portal_routes_render_inertia_pages(): void
    {
        $this->get('/internal')
            ->assertStatus(302)
            ->assertRedirect(route('internal.login'));

        $this->get('/internal/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internal/Login', false));

        $stakeholder = new \App\Models\User(['id' => 2, 'name' => 'Kadis Peternakan', 'email' => 'kadis@banyumaskab.go.id', 'role' => 'stakeholder', 'aktif' => true]);
        $this->actingAs($stakeholder)->get('/internal/dinas')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internal/DinasPortal', false));

        $admin = new \App\Models\User(['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@banyumaskab.go.id', 'role' => 'admin', 'aktif' => true]);
        $this->actingAs($admin)->get('/internal/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internal/AdminPortal', false));
    }

    public function test_authentication_view_routes_render_inertia_pages(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Login', false));

        $this->get('/register')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/Register', false));

        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('auth/ForgotPassword', false));
    }

    public function test_design_system_tokens_unified_and_legacy_neon_removed(): void
    {
        $quantilePalette = ['#134E4A', '#0F766E', '#059669', '#10B981', '#34D399'];

        $tokenFiles = [
            base_path('tailwind.config.js'),
            resource_path('css/app.css'),
            resource_path('js/data/banyumasData.ts'),
        ];

        foreach ($tokenFiles as $filePath) {
            $this->assertFileExists($filePath);
            $content = (string) file_get_contents($filePath);

            foreach ($quantilePalette as $hex) {
                $this->assertStringContainsString(
                    $hex,
                    $content,
                    "Expected quantile token {$hex} in {$filePath}"
                );
            }

            $this->assertStringNotContainsString(
                '#39FF14',
                strtoupper($content),
                "Legacy neon #39FF14 must not exist in {$filePath}"
            );
        }

        $appTsPath = resource_path('js/app.ts');
        $appBladePath = resource_path('views/app.blade.php');

        $appTsContent = (string) file_get_contents($appTsPath);
        $appBladeContent = (string) file_get_contents($appBladePath);

        $this->assertStringContainsString('#10B981', $appTsContent);
        $this->assertStringNotContainsString('#39FF14', strtoupper($appTsContent));

        $this->assertStringContainsString('#090D16', $appBladeContent);
        $this->assertStringContainsString('selection:bg-emerald-500', $appBladeContent);
        $this->assertStringNotContainsString('#39FF14', strtoupper($appBladeContent));
    }

    public function test_admin_portal_modularization_and_bento_contract_integrity(): void
    {
        $adminPortalPath = resource_path('js/Pages/Internal/AdminPortal.vue');
        $this->assertFileExists($adminPortalPath);

        $adminPortalContent = (string) file_get_contents($adminPortalPath);
        $lineCount = count(file($adminPortalPath) ?: []);

        $this->assertLessThan(
            500,
            $lineCount,
            "AdminPortal.vue should be modularized to < 500 lines (actual: {$lineCount})"
        );

        $this->assertStringContainsString('useAdminPortal', $adminPortalContent);

        $requiredSubComponents = [
            'AdminTopBar',
            'AdminSidebarNav',
            'AdminDashboardTab',
            'AdminDataTab',
            'AdminCreateTab',
            'AdminEwsTab',
            'AdminRphTab',
            'AdminLaporanTab',
            'AdminProfileTab',
            'AdminModals',
        ];

        foreach ($requiredSubComponents as $subComponent) {
            $this->assertStringContainsString(
                $subComponent,
                $adminPortalContent,
                "AdminPortal.vue must import and use {$subComponent}"
            );
            $this->assertFileExists(
                resource_path("js/components/ternakverse/admin/{$subComponent}.vue")
            );
        }

        $bentoContractPages = [
            resource_path('js/Pages/Dinas/Dashboard.vue'),
            resource_path('js/Pages/Admin/Panel.vue'),
            resource_path('js/Pages/Admin/AuditLogs.vue'),
        ];

        foreach ($bentoContractPages as $pagePath) {
            $this->assertFileExists($pagePath);
            $pageContent = (string) file_get_contents($pagePath);
            $this->assertStringContainsString(
                'showRaw = ref(false)',
                $pageContent,
                "Raw JSON inspector must default to closed (showRaw = ref(false)) in {$pagePath}"
            );
        }

        $securityPagePath = resource_path('js/Pages/settings/Security.vue');
        $this->assertFileExists($securityPagePath);
        $securityContent = (string) file_get_contents($securityPagePath);

        $this->assertStringContainsString('<AppLayout', $securityContent);
        $this->assertStringContainsString('<SettingsLayout>', $securityContent);
    }

    public function test_dark_geospatial_command_center_contracts_and_components(): void
    {
        // 1. Verify design-rule.md exists and contains core design system contracts
        $designRuleDocPath = base_path('design-rule.md');
        $this->assertFileExists($designRuleDocPath);
        $designRuleDocContent = (string) file_get_contents($designRuleDocPath);
        $this->assertStringContainsString('TERNAKVERSE --- UI Design Rules', $designRuleDocContent);
        $this->assertStringContainsString('Modern Geospatial Intelligence Dashboard', $designRuleDocContent);

        // 2. Verify Forest Intelligence GIS tokens and Choropleth scale in tailwind.config.js & resources/css/app.css
        $forestGisTokens = [
            '#0B1420',
            '#101D2B',
            '#159B78',
            '#39D6C0',
        ];
        $choroplethScale = [
            '#064B50',
            '#08736B',
            '#159B78',
            '#36C99B',
            '#75E6C5',
        ];

        $tokenFiles = [
            base_path('tailwind.config.js'),
            resource_path('css/app.css'),
        ];

        foreach ($tokenFiles as $filePath) {
            $this->assertFileExists($filePath);
            $content = (string) file_get_contents($filePath);

            foreach ($forestGisTokens as $token) {
                $this->assertStringContainsString(
                    $token,
                    $content,
                    "Expected Forest GIS token {$token} in {$filePath}"
                );
            }

            foreach ($choroplethScale as $color) {
                $this->assertStringContainsString(
                    $color,
                    $content,
                    "Expected Choropleth color {$color} in {$filePath}"
                );
            }
        }

        // 3. Verify banyumasData.ts contains CHOROPLETH_DARK_CLASSES
        $banyumasDataPath = resource_path('js/data/banyumasData.ts');
        $this->assertFileExists($banyumasDataPath);
        $banyumasDataContent = (string) file_get_contents($banyumasDataPath);
        $this->assertStringContainsString('CHOROPLETH_DARK_CLASSES', $banyumasDataContent);

        // 4. Verify Command Center components exist and contain core contracts
        // 4a. WebGisHeader.vue
        $headerPath = resource_path('js/components/ternakverse/WebGisHeader.vue');
        $this->assertFileExists($headerPath);
        $headerContent = (string) file_get_contents($headerPath);
        $headerContracts = ['TERNAKVERSE', 'Peta Satelit', 'Peta Vektor', 'Reset Filter', 'Bantuan'];
        foreach ($headerContracts as $contract) {
            $this->assertStringContainsString(
                $contract,
                $headerContent,
                "WebGisHeader.vue must contain '{$contract}'"
            );
        }

        // 4b. MapSidebar.vue
        $sidebarPath = resource_path('js/components/ternakverse/MapSidebar.vue');
        $this->assertFileExists($sidebarPath);
        $sidebarContent = (string) file_get_contents($sidebarPath);
        $sidebarContracts = ['Data Peta', 'Layer Peta', 'Legenda', 'Peringkat Kecamatan'];
        foreach ($sidebarContracts as $contract) {
            $this->assertStringContainsString(
                $contract,
                $sidebarContent,
                "MapSidebar.vue must contain '{$contract}'"
            );
        }
        $this->assertStringContainsString('getProgressPercent', $sidebarContent);
        $this->assertStringContainsString('maxVal', $sidebarContent);
        $this->assertStringNotContainsString('Tanya Data Peternakan', $sidebarContent, 'MapSidebar.vue must not contain the obsolete Tanya Data Peternakan button');

        // 4c. LeafletWebGisMap.vue
        $leafletPath = resource_path('js/components/ternakverse/LeafletWebGisMap.vue');
        $this->assertFileExists($leafletPath);
        $leafletContent = (string) file_get_contents($leafletPath);
        $this->assertStringContainsString("currentBasemap = ref<'satellite' | 'google_hybrid' | 'dark'>('satellite')", $leafletContent);
        $this->assertStringContainsString('searchQuery', $leafletContent);
        $this->assertStringContainsString('scaleValues', $leafletContent);
        $this->assertStringContainsString('formattedCoordinates', $leafletContent);
        $this->assertStringContainsString('recenter', $leafletContent);

        // 4d. SelectedRegionPanel.vue
        $panelPath = resource_path('js/components/ternakverse/SelectedRegionPanel.vue');
        $this->assertFileExists($panelPath);
        $panelContent = (string) file_get_contents($panelPath);
        $this->assertStringContainsString('Detail Wilayah', $panelContent);
        $this->assertStringContainsString('Rule / Aturan', $panelContent);
        $this->assertStringContainsString('qualitativeStatus', $panelContent);
        $this->assertStringContainsString("currentRole === 'publik'", $panelContent);

        // 4e. ChatbotDrawer.vue
        $drawerPath = resource_path('js/components/ternakverse/ChatbotDrawer.vue');
        $this->assertFileExists($drawerPath);
        $drawerContent = (string) file_get_contents($drawerPath);
        $this->assertStringContainsString('Tanya TERNAKVERSE AI', $drawerContent);
        $this->assertStringContainsString('isChatOpen', $drawerContent);
        $this->assertStringContainsString('TERNAKVERSE AI', $drawerContent);
        $this->assertStringContainsString('MessageSquareText', $drawerContent, 'ChatbotDrawer.vue must use MessageSquareText icon');
        $this->assertStringContainsString('bottom-6 right-6', $drawerContent, 'ChatbotDrawer.vue launcher FAB must be anchored bottom-6 right-6');
        $this->assertStringContainsString('rounded-full', $drawerContent, 'ChatbotDrawer.vue launcher FAB must be circular (rounded-full)');

        // 4f. Public/Portal.vue
        $portalPath = resource_path('js/Pages/Public/Portal.vue');
        $this->assertFileExists($portalPath);
        $portalContent = (string) file_get_contents($portalPath);
        $requiredPortalComponents = [
            'WebGisHeader',
            'MapSidebar',
            'LeafletWebGisMap',
            'SelectedRegionPanel',
            'ChatbotDrawer',
        ];
        foreach ($requiredPortalComponents as $component) {
            $this->assertStringContainsString(
                $component,
                $portalContent,
                "Portal.vue must import and reference {$component}"
            );
        }
        $this->assertStringContainsString('h-screen', $portalContent);
        $this->assertStringContainsString('w-screen', $portalContent);
        $this->assertStringContainsString('overflow-hidden', $portalContent);

        // 5. The GET /portal route renders Public/Portal and GET /design-rule route renders Public/DesignRule (or DesignRule) with HTTP 200
        $this->get('/portal')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Public/Portal', false));

        $designRuleRes = $this->get('/design-rule');
        $designRuleRes->assertOk();
        $designRuleComponent = $designRuleRes->viewData('page')['component'] ?? '';
        $this->assertTrue(
            in_array($designRuleComponent, ['DesignRule', 'Public/DesignRule'], true),
            "Expected /design-rule to render DesignRule or Public/DesignRule, got {$designRuleComponent}"
        );
        $designRuleRes->assertInertia(fn (Assert $page) => $page->component($designRuleComponent, false));
    }

    public function test_admin_panel_dashboard_redesign_contracts_and_components(): void
    {
        // 1. Verify AdminTopBar.vue contracts
        $topBarPath = resource_path('js/components/ternakverse/admin/AdminTopBar.vue');
        $this->assertFileExists($topBarPath);
        $topBarContent = (string) file_get_contents($topBarPath);
        $topBarContracts = [
            'TERNAKVERSE',
            'PANEL ADMIN',
            'Ctrl + K',
            '1 Okt 2026 - 31 Okt 2026',
            'Satrio Wibowo',
            'Portal Publik',
            'WebGIS Dinas',
        ];
        foreach ($topBarContracts as $contract) {
            $this->assertStringContainsString($contract, $topBarContent, "AdminTopBar.vue must contain '{$contract}'");
        }

        // 2. Verify AdminSidebarNav.vue contracts
        $sidebarPath = resource_path('js/components/ternakverse/admin/AdminSidebarNav.vue');
        $this->assertFileExists($sidebarPath);
        $sidebarContent = (string) file_get_contents($sidebarPath);
        $sidebarContracts = [
            'TERNAKVERSE',
            'PANEL ADMIN',
            'Dashboard',
            'Data Peternakan',
            'Input Data Baru',
            'MONITORING &amp; FASILITAS',
            'Monitoring EWS',
            'Fasilitas RPH',
            'Ekspor Laporan',
            'Pengaturan Akun',
            'Sistem Berjalan Normal',
            'Dinas Perikanan dan Peternakan Kabupaten Banyumas',
        ];
        foreach ($sidebarContracts as $contract) {
            $this->assertStringContainsString($contract, $sidebarContent, "AdminSidebarNav.vue must contain '{$contract}'");
        }

        // 3. Verify AdminWelcomeBanner.vue contracts
        $welcomeBannerPath = resource_path('js/components/ternakverse/admin/dashboard/AdminWelcomeBanner.vue');
        $this->assertFileExists($welcomeBannerPath);
        $welcomeBannerContent = (string) file_get_contents($welcomeBannerPath);
        $welcomeContracts = [
            'Selamat Datang',
            'Satrio Wibowo',
            'Impor Data',
            '+ Tambah Komoditas',
        ];
        foreach ($welcomeContracts as $contract) {
            $this->assertStringContainsString($contract, $welcomeBannerContent, "AdminWelcomeBanner.vue must contain '{$contract}'");
        }

        // 4. Verify AdminKpiCards.vue contracts
        $kpiCardsPath = resource_path('js/components/ternakverse/admin/dashboard/AdminKpiCards.vue');
        $this->assertFileExists($kpiCardsPath);
        $kpiCardsContent = (string) file_get_contents($kpiCardsPath);
        $kpiContracts = [
            'Total Populasi Sapi Potong',
            'Total Peternak',
            'Kasus Aktif PMK',
            'Kasus Aktif LSD',
            'Fasilitas RPH',
        ];
        foreach ($kpiContracts as $contract) {
            $this->assertStringContainsString($contract, $kpiCardsContent, "AdminKpiCards.vue must contain '{$contract}'");
        }

        // 5. Verify AdminAnalyticsRow.vue contracts
        $analyticsRowPath = resource_path('js/components/ternakverse/admin/dashboard/AdminAnalyticsRow.vue');
        $this->assertFileExists($analyticsRowPath);
        $analyticsRowContent = (string) file_get_contents($analyticsRowPath);
        $analyticsContracts = [
            'Tren Populasi Ternak per Kecamatan',
            'Komposisi Komoditas',
            'AdminMiniMapWidget',
            'Wangon',
            'Sumbang',
        ];
        foreach ($analyticsContracts as $contract) {
            $this->assertStringContainsString($contract, $analyticsRowContent, "AdminAnalyticsRow.vue must contain '{$contract}'");
        }

        // 6. Verify AdminMiniMapWidget.vue contracts
        $miniMapPath = resource_path('js/components/ternakverse/admin/dashboard/AdminMiniMapWidget.vue');
        $this->assertFileExists($miniMapPath);
        $miniMapContent = (string) file_get_contents($miniMapPath);
        $miniMapContracts = [
            'Sebaran Populasi Wilayah',
            'Lihat Detail Peta',
            'Sangat Tinggi',
            'Sangat Rendah',
        ];
        foreach ($miniMapContracts as $contract) {
            $this->assertStringContainsString($contract, $miniMapContent, "AdminMiniMapWidget.vue must contain '{$contract}'");
        }

        // 7. Verify AdminOperationalRow.vue contracts
        $operationalRowPath = resource_path('js/components/ternakverse/admin/dashboard/AdminOperationalRow.vue');
        $this->assertFileExists($operationalRowPath);
        $operationalRowContent = (string) file_get_contents($operationalRowPath);
        $operationalContracts = [
            'Aktivitas &amp; Mutasi Ternak',
            'Riwayat Input Data',
            'Status Sinkronisasi',
            'Notifikasi EWS',
        ];
        foreach ($operationalContracts as $contract) {
            $this->assertStringContainsString($contract, $operationalRowContent, "AdminOperationalRow.vue must contain '{$contract}'");
        }

        // 8. Verify AdminDashboardTab.vue composite layout
        $dashboardTabPath = resource_path('js/components/ternakverse/admin/AdminDashboardTab.vue');
        $this->assertFileExists($dashboardTabPath);
        $dashboardTabContent = (string) file_get_contents($dashboardTabPath);
        $dashboardSubComponents = [
            'AdminWelcomeBanner',
            'AdminKpiCards',
            'AdminAnalyticsRow',
            'AdminOperationalRow',
        ];
        foreach ($dashboardSubComponents as $sub) {
            $this->assertStringContainsString($sub, $dashboardTabContent, "AdminDashboardTab.vue must import and render {$sub}");
        }

        // 9. Verify AdminPortal.vue top-level composition
        $adminPortalPath = resource_path('js/Pages/Internal/AdminPortal.vue');
        $this->assertFileExists($adminPortalPath);
        $adminPortalContent = (string) file_get_contents($adminPortalPath);
        $adminPortalSubComponents = [
            'AdminTopBar',
            'AdminSidebarNav',
            'AdminDashboardTab',
            'ChatbotDrawer',
        ];
        foreach ($adminPortalSubComponents as $sub) {
            $this->assertStringContainsString($sub, $adminPortalContent, "AdminPortal.vue must import {$sub}");
        }

        // 10. Verify /internal/admin route renders Internal/AdminPortal component
        $admin = new \App\Models\User(['id' => 1, 'name' => 'Super Admin', 'email' => 'admin@banyumaskab.go.id', 'role' => 'admin', 'aktif' => true]);
        $this->actingAs($admin)->get('/internal/admin')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Internal/AdminPortal', false));
    }

    public function test_dinas_dashboard_renders_with_resilient_handshake_props(): void
    {
        $stakeholder = new \App\Models\User([
            'id' => 2,
            'name' => 'Kadis Peternakan',
            'email' => 'kadis@banyumaskab.go.id',
            'role' => 'stakeholder',
            'aktif' => true,
        ]);

        $this->actingAs($stakeholder)
            ->get('/dinas/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dinas/Dashboard', false)
                ->has('filterState')
                ->has('kpiCards')
                ->has('chartData')
                ->has('geoChoropleth')
            );
    }

    public function test_super_admin_webgis_tab_contracts_and_components(): void
    {
        $adminWebGisTabPath = resource_path('js/components/ternakverse/admin/webgis/AdminWebGisTab.vue');
        $this->assertFileExists($adminWebGisTabPath);
        $tabContent = (string) file_get_contents($adminWebGisTabPath);
        $this->assertStringContainsString('SUPER ADMIN GEOSPATIAL COMMAND CENTER', $tabContent);

        $sidebarPath = resource_path('js/components/ternakverse/admin/AdminSidebarNav.vue');
        $this->assertFileExists($sidebarPath);
        $sidebarContent = (string) file_get_contents($sidebarPath);
        $this->assertStringContainsString(
            'WebGIS Command Center',
            $sidebarContent,
            "AdminSidebarNav.vue must contain 'WebGIS Command Center'"
        );

        $portalPath = resource_path('js/Pages/Internal/AdminPortal.vue');
        $this->assertFileExists($portalPath);
        $portalContent = (string) file_get_contents($portalPath);
        $this->assertStringContainsString(
            'AdminWebGisTab',
            $portalContent,
            'AdminPortal.vue must import and render AdminWebGisTab'
        );
        $this->assertStringContainsString(
            "'webgis'",
            $portalContent,
            "AdminPortal.vue must reference tab 'webgis'"
        );

        $composablePath = resource_path('js/composables/ternakverse/admin/useAdminPortal.ts');
        $this->assertFileExists($composablePath);
        $composableContent = (string) file_get_contents($composablePath);
        $this->assertStringContainsString(
            "'webgis'",
            $composableContent,
            "useAdminPortal.ts must include 'webgis' in AdminTab union type"
        );
    }

    public function test_public_webgis_dynamic_user_auth_and_header_navigation(): void
    {
        $portalPath = resource_path('js/Pages/Public/Portal.vue');
        $this->assertFileExists($portalPath);
        $portalContent = (string) file_get_contents($portalPath);
        $this->assertStringContainsString('authUser', $portalContent);
        $this->assertStringContainsString('currentRole', $portalContent);

        $headerPath = resource_path('js/components/ternakverse/WebGisHeader.vue');
        $this->assertFileExists($headerPath);
        $headerContent = (string) file_get_contents($headerPath);
        $this->assertStringContainsString('/internal/admin', $headerContent);
        $this->assertStringContainsString('/internal/dinas', $headerContent);
    }
}


