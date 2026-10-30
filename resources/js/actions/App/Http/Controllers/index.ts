import Settings from './Settings'
import DinasDashboardController from './DinasDashboardController'
import AdminPanelController from './AdminPanelController'
import AdminAuditLogController from './AdminAuditLogController'
import Internal from './Internal'
const Controllers = {
    Settings: Object.assign(Settings, Settings),
DinasDashboardController: Object.assign(DinasDashboardController, DinasDashboardController),
AdminPanelController: Object.assign(AdminPanelController, AdminPanelController),
AdminAuditLogController: Object.assign(AdminAuditLogController, AdminAuditLogController),
Internal: Object.assign(Internal, Internal),
}

export default Controllers