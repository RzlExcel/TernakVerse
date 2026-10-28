/**
 * TERNAKVERSE Route Helpers
 * Fallback route definitions for frontend navigation & Ziggy / Wayfinder compatibility.
 */

export const home = () => '/';
export const publicPortal = () => '/';
export const welcome = () => '/welcome';
export const dashboard = () => '/dashboard';
export const login = () => '/login';
export const register = () => '/register';
export const logout = () => '/logout';

export const internalLogin = () => '/internal/login';
export const internalDinas = () => '/internal/dinas';
export const internalAdmin = () => '/internal/admin';
export const designRule = () => '/design-rule';

export const profile = () => '/settings/profile';
export const profileEdit = () => '/settings/profile';
export const securityEdit = () => '/settings/security';
export const appearanceEdit = () => '/settings/appearance';

export const dinasDashboard = () => '/dinas/dashboard';
export const adminPanel = () => '/admin/panel';
export const adminAuditLogs = () => '/admin/audit-logs';

export default {
    home,
    publicPortal,
    welcome,
    dashboard,
    login,
    register,
    logout,
    internalLogin,
    internalDinas,
    internalAdmin,
    designRule,
    profile,
    profileEdit,
    securityEdit,
    appearanceEdit,
    dinasDashboard,
    adminPanel,
    adminAuditLogs,
};
