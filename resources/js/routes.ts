/**
 * TERNAKVERSE Route Helpers
 * Fallback route definitions for frontend navigation & Wayfinder compatibility.
 */

export const home = () => '/';
export const dashboard = () => '/dashboard';
export const login = () => '/login';
export const register = () => '/register';
export const logout = () => '/logout';

export const profile = () => '/settings/profile';
export const profileEdit = () => '/settings/profile';
export const securityEdit = () => '/settings/security';
export const appearanceEdit = () => '/settings/appearance';

export const dinasDashboard = () => '/dinas/dashboard';
export const adminPanel = () => '/admin/panel';
export const adminAuditLogs = () => '/admin/audit-logs';

export default {
    home,
    dashboard,
    login,
    register,
    logout,
    profile,
    profileEdit,
    securityEdit,
    appearanceEdit,
    dinasDashboard,
    adminPanel,
    adminAuditLogs,
};
