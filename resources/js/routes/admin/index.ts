import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
export const panel = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: panel.url(options),
    method: 'get',
})

panel.definition = {
    methods: ["get","head"],
    url: '/admin/panel',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
panel.url = (options?: RouteQueryOptions) => {
    return panel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
panel.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: panel.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
panel.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: panel.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
    const panelForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: panel.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
        panelForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: panel.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminPanelController::panel
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
        panelForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: panel.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    panel.form = panelForm
/**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
export const auditLogs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: auditLogs.url(options),
    method: 'get',
})

auditLogs.definition = {
    methods: ["get","head"],
    url: '/admin/audit-logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
auditLogs.url = (options?: RouteQueryOptions) => {
    return auditLogs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
auditLogs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: auditLogs.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
auditLogs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: auditLogs.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
    const auditLogsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: auditLogs.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
        auditLogsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: auditLogs.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminAuditLogController::auditLogs
 * @see app/Http/Controllers/AdminAuditLogController.php:16
 * @route '/admin/audit-logs'
 */
        auditLogsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: auditLogs.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    auditLogs.form = auditLogsForm
const admin = {
    panel: Object.assign(panel, panel),
auditLogs: Object.assign(auditLogs, auditLogs),
}

export default admin