<script setup lang="ts">
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import DiagnosticBox from '../../components/ternakverse/DiagnosticBox.vue';
import AdminTopBar from '../../components/ternakverse/admin/AdminTopBar.vue';
import AdminSidebarNav from '../../components/ternakverse/admin/AdminSidebarNav.vue';
import AdminDashboardTab from '../../components/ternakverse/admin/AdminDashboardTab.vue';
import AdminWebGisTab from '../../components/ternakverse/admin/webgis/AdminWebGisTab.vue';
import AdminDataTab from '../../components/ternakverse/admin/AdminDataTab.vue';
import AdminCreateTab from '../../components/ternakverse/admin/AdminCreateTab.vue';
import AdminEwsTab from '../../components/ternakverse/admin/AdminEwsTab.vue';
import AdminRphTab from '../../components/ternakverse/admin/AdminRphTab.vue';
import AdminLaporanTab from '../../components/ternakverse/admin/AdminLaporanTab.vue';
import AdminProfileTab from '../../components/ternakverse/admin/AdminProfileTab.vue';
import AdminRbacTab from '../../components/ternakverse/admin/rbac/AdminRbacTab.vue';
import AdminModals from '../../components/ternakverse/admin/AdminModals.vue';
import ChatbotDrawer from '../../components/ternakverse/ChatbotDrawer.vue';
import { useAdminPortal } from '../../composables/ternakverse/admin/useAdminPortal';

const isChatOpen = ref(false);

const {
    currentTab,
    isMobileMenuOpen,
    isSidebarCollapsed,
    selectTab,
    kecamatanList,
    auditLogs,
    toastMsg,
    showToast,
    searchQueryDashboard,
    isImportModalOpen,
    handleDashboardSearch,
    handleImportSuccess,
    searchQuery,
    selectedCategoryFilter,
    selectedZonaFilter,
    selectedWabahFilter,
    selectedPoskeswanFilter,
    minPopulasiFilter,
    isFilterModalOpen,
    isExportModalOpen,
    selectedRowIds,
    rowsPerPage,
    currentPage,
    filteredKecamatan,
    paginatedKecamatan,
    totalPages,
    hasActiveFilters,
    activeFilterCount,
    toggleSelectAll,
    toggleSelectRow,
    resetFilters,
    handleExport,
    isEditing,
    formData,
    startCreateNew,
    startEditKecamatan,
    handleSaveForm,
    deleteKecamatan,
    rphList,
    toggleRphStatus,
    ewsParams,
    recalculateZonasi,
    broadcastEwsAlert,
    profileSettings,
    saveProfileSettings,
    handleLogout,
    goToPublicPortal,
    goToDinasMap,
} = useAdminPortal();

function handleGlobalSearch(query: string): void {
    searchQuery.value = query;
    selectTab('data');
}
</script>

<template>
    <Head title="Kelola data peternakan - TERNAKVERSE // SYS_ADMIN" />

    <!-- CONTAINER UTAMA VIEWPORT TINGGI PENUH (Obsidian Dark / Porcelain Light) -->
    <div class="h-screen w-screen overflow-hidden dark:bg-[#090909] bg-[#FFFFFF] dark:text-[#F5F5F5] text-[#171B17] flex flex-col font-ui antialiased selection:bg-[#00D084] selection:text-[#062117] transition-colors">
        <!-- TOP NAVBAR -->
        <AdminTopBar
            v-model:isMobileMenuOpen="isMobileMenuOpen"
            v-model:isSidebarCollapsed="isSidebarCollapsed"
            @selectTab="selectTab"
            @globalSearch="handleGlobalSearch"
            @goToPublicPortal="goToPublicPortal"
            @goToDinasMap="goToDinasMap"
        />

        <!-- ROW LAYOUT: SIDEBAR (COLLAPSIBLE DESKTOP + MOBILE DRAWER) + MAIN -->
        <div class="flex-1 flex w-full overflow-hidden">
            <AdminSidebarNav
                :currentTab="currentTab"
                v-model:isMobileMenuOpen="isMobileMenuOpen"
                :isSidebarCollapsed="isSidebarCollapsed"
                @selectTab="selectTab"
                @startCreateNew="startCreateNew"
                @goToPublicPortal="goToPublicPortal"
                @goToDinasMap="goToDinasMap"
                @logout="handleLogout"
            />

            <!-- MAIN CONTENT AREA -->
            <main
                class="flex-1 h-full w-full dark:bg-[#090909] bg-[#FFFFFF] transition-colors"
                :class="currentTab === 'webgis' ? 'overflow-hidden p-2 sm:p-3' : 'overflow-y-auto p-4 sm:p-6'"
            >
                <!-- 1. DASHBOARD TAB -->
                <AdminDashboardTab
                    v-if="currentTab === 'dashboard'"
                    v-model:searchQueryDashboard="searchQueryDashboard"
                    :auditLogs="auditLogs"
                    @search="handleDashboardSearch"
                    @openImportModal="isImportModalOpen = true"
                    @startCreateNew="startCreateNew"
                    @selectTab="selectTab"
                />

                <!-- 2. WEBGIS COMMAND CENTER TAB -->
                <AdminWebGisTab
                    v-else-if="currentTab === 'webgis'"
                    @selectTab="selectTab"
                    @startCreateNew="startCreateNew"
                />

                <!-- 3. DATA PETERNAKAN TAB -->
                <AdminDataTab
                    v-else-if="currentTab === 'data'"
                    v-model:searchQuery="searchQuery"
                    v-model:selectedCategoryFilter="selectedCategoryFilter"
                    v-model:selectedZonaFilter="selectedZonaFilter"
                    v-model:rowsPerPage="rowsPerPage"
                    v-model:currentPage="currentPage"
                    :hasActiveFilters="hasActiveFilters"
                    :activeFilterCount="activeFilterCount"
                    :selectedRowIds="selectedRowIds"
                    :filteredCount="filteredKecamatan.length"
                    :paginatedKecamatan="paginatedKecamatan"
                    :totalPages="totalPages"
                    @openFilterModal="isFilterModalOpen = true"
                    @openExportModal="isExportModalOpen = true"
                    @resetFilters="resetFilters"
                    @toggleSelectAll="toggleSelectAll"
                    @toggleSelectRow="toggleSelectRow"
                    @startCreateNew="startCreateNew"
                    @editKecamatan="startEditKecamatan"
                    @deleteKecamatan="deleteKecamatan"
                />

                <!-- 3. FORM CREATE / EDIT TAB -->
                <AdminCreateTab
                    v-else-if="currentTab === 'create'"
                    :isEditing="isEditing"
                    :formData="formData"
                    @save="handleSaveForm"
                    @cancel="selectTab('data')"
                />

                <!-- 4. MONITORING EWS TAB -->
                <AdminEwsTab
                    v-else-if="currentTab === 'ews'"
                    :ewsParams="ewsParams"
                    :kecamatanList="kecamatanList"
                    @recalculate="recalculateZonasi"
                    @broadcastAlert="broadcastEwsAlert"
                />

                <!-- 5. FASILITAS RPH TAB -->
                <AdminRphTab
                    v-else-if="currentTab === 'rph'"
                    :rphList="rphList"
                    @toggleStatus="toggleRphStatus"
                />

                <!-- 6. EKSPOR LAPORAN TAB -->
                <AdminLaporanTab
                    v-else-if="currentTab === 'laporan'"
                    @showToast="showToast"
                />

                <!-- 7. PENGATURAN AKUN / PROFILE TAB -->
                <AdminProfileTab
                    v-else-if="currentTab === 'profile'"
                    :profileSettings="profileSettings"
                    @saveSettings="saveProfileSettings"
                    @logout="handleLogout"
                />

                <!-- 8. MANAJEMEN RBAC TAB -->
                <AdminRbacTab
                    v-else-if="currentTab === 'rbac'"
                />
            </main>
        </div>

        <!-- MODALS & TOAST NOTIFICATION -->
        <AdminModals
            v-model:isImportModalOpen="isImportModalOpen"
            v-model:isExportModalOpen="isExportModalOpen"
            v-model:isFilterModalOpen="isFilterModalOpen"
            v-model:selectedWabahFilter="selectedWabahFilter"
            v-model:selectedPoskeswanFilter="selectedPoskeswanFilter"
            v-model:minPopulasiFilter="minPopulasiFilter"
            :selectedRowCount="selectedRowIds.length"
            :filteredCount="filteredKecamatan.length"
            :toastMsg="toastMsg"
            @importSuccess="handleImportSuccess"
            @exportData="handleExport"
            @resetFilters="resetFilters"
        />

        <!-- FLOATING CHATBOT ASISTEN AI (Living Assistant) -->
        <ChatbotDrawer
            :kecamatan-list="kecamatanList"
            :is-open="isChatOpen"
            @open="isChatOpen = true"
            @close="isChatOpen = false"
        />

        <!-- DIAGNOSTIC BOX -->
        <DiagnosticBox />
    </div>
</template>

/* BERKAS-UTUH: AdminPortal.vue */
