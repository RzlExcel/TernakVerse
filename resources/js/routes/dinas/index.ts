import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/dinas/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
    const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: dashboard.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
        dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\DinasDashboardController::dashboard
 * @see app/Http/Controllers/DinasDashboardController.php:28
 * @route '/dinas/dashboard'
 */
        dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: dashboard.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    dashboard.form = dashboardForm
const dinas = {
    dashboard: Object.assign(dashboard, dashboard),
}

export default dinas