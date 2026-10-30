import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/panel',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\AdminPanelController::index
 * @see app/Http/Controllers/AdminPanelController.php:25
 * @route '/admin/panel'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
const AdminPanelController = { index }

export default AdminPanelController