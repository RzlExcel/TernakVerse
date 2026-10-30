import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
export const indexPopulasi = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexPopulasi.url(options),
    method: 'get',
})

indexPopulasi.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/populasi',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
indexPopulasi.url = (options?: RouteQueryOptions) => {
    return indexPopulasi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
indexPopulasi.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexPopulasi.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
indexPopulasi.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexPopulasi.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
    const indexPopulasiForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: indexPopulasi.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
        indexPopulasiForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexPopulasi.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:29
 * @route '/api/v1/internal/admin/populasi'
 */
        indexPopulasiForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexPopulasi.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    indexPopulasi.form = indexPopulasiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::storePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:51
 * @route '/api/v1/internal/admin/populasi'
 */
export const storePopulasi = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePopulasi.url(options),
    method: 'post',
})

storePopulasi.definition = {
    methods: ["post"],
    url: '/api/v1/internal/admin/populasi',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:51
 * @route '/api/v1/internal/admin/populasi'
 */
storePopulasi.url = (options?: RouteQueryOptions) => {
    return storePopulasi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:51
 * @route '/api/v1/internal/admin/populasi'
 */
storePopulasi.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storePopulasi.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::storePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:51
 * @route '/api/v1/internal/admin/populasi'
 */
    const storePopulasiForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storePopulasi.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::storePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:51
 * @route '/api/v1/internal/admin/populasi'
 */
        storePopulasiForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storePopulasi.url(options),
            method: 'post',
        })
    
    storePopulasi.form = storePopulasiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
export const showPopulasi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showPopulasi.url(args, options),
    method: 'get',
})

showPopulasi.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/populasi/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
showPopulasi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return showPopulasi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
showPopulasi.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showPopulasi.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
showPopulasi.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showPopulasi.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
    const showPopulasiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: showPopulasi.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
        showPopulasiForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showPopulasi.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:65
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
        showPopulasiForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showPopulasi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    showPopulasi.form = showPopulasiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
export const updatePopulasi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePopulasi.url(args, options),
    method: 'put',
})

updatePopulasi.definition = {
    methods: ["put","patch"],
    url: '/api/v1/internal/admin/populasi/{id}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
updatePopulasi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return updatePopulasi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
updatePopulasi.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updatePopulasi.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
updatePopulasi.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updatePopulasi.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
    const updatePopulasiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updatePopulasi.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
        updatePopulasiForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updatePopulasi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updatePopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:71
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
        updatePopulasiForm.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updatePopulasi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updatePopulasi.form = updatePopulasiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:87
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
export const destroyPopulasi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyPopulasi.url(args, options),
    method: 'delete',
})

destroyPopulasi.definition = {
    methods: ["delete"],
    url: '/api/v1/internal/admin/populasi/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:87
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
destroyPopulasi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return destroyPopulasi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:87
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
destroyPopulasi.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyPopulasi.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:87
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
    const destroyPopulasiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroyPopulasi.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyPopulasi
 * @see app/Http/Controllers/Internal/AdminDataController.php:87
 * @route '/api/v1/internal/admin/populasi/{id}'
 */
        destroyPopulasiForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroyPopulasi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroyPopulasi.form = destroyPopulasiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
export const indexProduksi = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexProduksi.url(options),
    method: 'get',
})

indexProduksi.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/produksi',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
indexProduksi.url = (options?: RouteQueryOptions) => {
    return indexProduksi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
indexProduksi.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexProduksi.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
indexProduksi.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexProduksi.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
    const indexProduksiForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: indexProduksi.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
        indexProduksiForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexProduksi.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:105
 * @route '/api/v1/internal/admin/produksi'
 */
        indexProduksiForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexProduksi.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    indexProduksi.form = indexProduksiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:124
 * @route '/api/v1/internal/admin/produksi'
 */
export const storeProduksi = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeProduksi.url(options),
    method: 'post',
})

storeProduksi.definition = {
    methods: ["post"],
    url: '/api/v1/internal/admin/produksi',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:124
 * @route '/api/v1/internal/admin/produksi'
 */
storeProduksi.url = (options?: RouteQueryOptions) => {
    return storeProduksi.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:124
 * @route '/api/v1/internal/admin/produksi'
 */
storeProduksi.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeProduksi.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:124
 * @route '/api/v1/internal/admin/produksi'
 */
    const storeProduksiForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeProduksi.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:124
 * @route '/api/v1/internal/admin/produksi'
 */
        storeProduksiForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeProduksi.url(options),
            method: 'post',
        })
    
    storeProduksi.form = storeProduksiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
export const showProduksi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showProduksi.url(args, options),
    method: 'get',
})

showProduksi.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/produksi/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
showProduksi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return showProduksi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
showProduksi.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showProduksi.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
showProduksi.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showProduksi.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
    const showProduksiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: showProduksi.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
        showProduksiForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showProduksi.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:138
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
        showProduksiForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showProduksi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    showProduksi.form = showProduksiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
export const updateProduksi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProduksi.url(args, options),
    method: 'put',
})

updateProduksi.definition = {
    methods: ["put","patch"],
    url: '/api/v1/internal/admin/produksi/{id}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
updateProduksi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return updateProduksi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
updateProduksi.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateProduksi.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
updateProduksi.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateProduksi.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
    const updateProduksiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateProduksi.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
        updateProduksiForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateProduksi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:144
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
        updateProduksiForm.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateProduksi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateProduksi.form = updateProduksiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:160
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
export const destroyProduksi = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyProduksi.url(args, options),
    method: 'delete',
})

destroyProduksi.definition = {
    methods: ["delete"],
    url: '/api/v1/internal/admin/produksi/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:160
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
destroyProduksi.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return destroyProduksi.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:160
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
destroyProduksi.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyProduksi.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:160
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
    const destroyProduksiForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroyProduksi.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyProduksi
 * @see app/Http/Controllers/Internal/AdminDataController.php:160
 * @route '/api/v1/internal/admin/produksi/{id}'
 */
        destroyProduksiForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroyProduksi.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroyProduksi.form = destroyProduksiForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
export const indexKasus = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexKasus.url(options),
    method: 'get',
})

indexKasus.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/penyakit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
indexKasus.url = (options?: RouteQueryOptions) => {
    return indexKasus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
indexKasus.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexKasus.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
indexKasus.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexKasus.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
    const indexKasusForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: indexKasus.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
        indexKasusForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexKasus.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:178
 * @route '/api/v1/internal/admin/penyakit'
 */
        indexKasusForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexKasus.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    indexKasus.form = indexKasusForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:193
 * @route '/api/v1/internal/admin/penyakit'
 */
export const storeKasus = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeKasus.url(options),
    method: 'post',
})

storeKasus.definition = {
    methods: ["post"],
    url: '/api/v1/internal/admin/penyakit',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:193
 * @route '/api/v1/internal/admin/penyakit'
 */
storeKasus.url = (options?: RouteQueryOptions) => {
    return storeKasus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:193
 * @route '/api/v1/internal/admin/penyakit'
 */
storeKasus.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeKasus.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:193
 * @route '/api/v1/internal/admin/penyakit'
 */
    const storeKasusForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeKasus.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:193
 * @route '/api/v1/internal/admin/penyakit'
 */
        storeKasusForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeKasus.url(options),
            method: 'post',
        })
    
    storeKasus.form = storeKasusForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
export const showKasus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showKasus.url(args, options),
    method: 'get',
})

showKasus.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/penyakit/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
showKasus.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return showKasus.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
showKasus.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showKasus.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
showKasus.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showKasus.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
    const showKasusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: showKasus.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
        showKasusForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showKasus.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:210
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
        showKasusForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showKasus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    showKasus.form = showKasusForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
export const updateKasus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateKasus.url(args, options),
    method: 'put',
})

updateKasus.definition = {
    methods: ["put","patch"],
    url: '/api/v1/internal/admin/penyakit/{id}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
updateKasus.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return updateKasus.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
updateKasus.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateKasus.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
updateKasus.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateKasus.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
    const updateKasusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateKasus.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
        updateKasusForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateKasus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:216
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
        updateKasusForm.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateKasus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateKasus.form = updateKasusForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:235
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
export const destroyKasus = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyKasus.url(args, options),
    method: 'delete',
})

destroyKasus.definition = {
    methods: ["delete"],
    url: '/api/v1/internal/admin/penyakit/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:235
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
destroyKasus.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return destroyKasus.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:235
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
destroyKasus.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyKasus.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:235
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
    const destroyKasusForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroyKasus.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyKasus
 * @see app/Http/Controllers/Internal/AdminDataController.php:235
 * @route '/api/v1/internal/admin/penyakit/{id}'
 */
        destroyKasusForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroyKasus.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroyKasus.form = destroyKasusForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
export const indexRph = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexRph.url(options),
    method: 'get',
})

indexRph.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/rph',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
indexRph.url = (options?: RouteQueryOptions) => {
    return indexRph.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
indexRph.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: indexRph.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
indexRph.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: indexRph.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
    const indexRphForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: indexRph.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
        indexRphForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexRph.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::indexRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:259
 * @route '/api/v1/internal/admin/rph'
 */
        indexRphForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: indexRph.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    indexRph.form = indexRphForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:265
 * @route '/api/v1/internal/admin/rph'
 */
export const storeRph = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeRph.url(options),
    method: 'post',
})

storeRph.definition = {
    methods: ["post"],
    url: '/api/v1/internal/admin/rph',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:265
 * @route '/api/v1/internal/admin/rph'
 */
storeRph.url = (options?: RouteQueryOptions) => {
    return storeRph.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::storeRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:265
 * @route '/api/v1/internal/admin/rph'
 */
storeRph.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeRph.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:265
 * @route '/api/v1/internal/admin/rph'
 */
    const storeRphForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: storeRph.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::storeRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:265
 * @route '/api/v1/internal/admin/rph'
 */
        storeRphForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: storeRph.url(options),
            method: 'post',
        })
    
    storeRph.form = storeRphForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
export const showRph = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showRph.url(args, options),
    method: 'get',
})

showRph.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/rph/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
showRph.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return showRph.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
showRph.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showRph.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
showRph.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showRph.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
    const showRphForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: showRph.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
        showRphForm.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showRph.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::showRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:290
 * @route '/api/v1/internal/admin/rph/{id}'
 */
        showRphForm.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: showRph.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    showRph.form = showRphForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
export const updateRph = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateRph.url(args, options),
    method: 'put',
})

updateRph.definition = {
    methods: ["put","patch"],
    url: '/api/v1/internal/admin/rph/{id}',
} satisfies RouteDefinition<["put","patch"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
updateRph.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return updateRph.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
updateRph.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateRph.url(args, options),
    method: 'put',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
updateRph.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateRph.url(args, options),
    method: 'patch',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
    const updateRphForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateRph.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
        updateRphForm.put = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateRph.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:296
 * @route '/api/v1/internal/admin/rph/{id}'
 */
        updateRphForm.patch = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateRph.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PATCH',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateRph.form = updateRphForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:322
 * @route '/api/v1/internal/admin/rph/{id}'
 */
export const destroyRph = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyRph.url(args, options),
    method: 'delete',
})

destroyRph.definition = {
    methods: ["delete"],
    url: '/api/v1/internal/admin/rph/{id}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:322
 * @route '/api/v1/internal/admin/rph/{id}'
 */
destroyRph.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { id: args }
    }

    
    if (Array.isArray(args)) {
        args = {
                    id: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        id: args.id,
                }

    return destroyRph.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:322
 * @route '/api/v1/internal/admin/rph/{id}'
 */
destroyRph.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroyRph.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:322
 * @route '/api/v1/internal/admin/rph/{id}'
 */
    const destroyRphForm = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroyRph.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::destroyRph
 * @see app/Http/Controllers/Internal/AdminDataController.php:322
 * @route '/api/v1/internal/admin/rph/{id}'
 */
        destroyRphForm.delete = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroyRph.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroyRph.form = destroyRphForm
/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
const getParameters559aaf0e9ed06327b62013245a06aece = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'get',
})

getParameters559aaf0e9ed06327b62013245a06aece.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/ews/parameter',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
getParameters559aaf0e9ed06327b62013245a06aece.url = (options?: RouteQueryOptions) => {
    return getParameters559aaf0e9ed06327b62013245a06aece.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
getParameters559aaf0e9ed06327b62013245a06aece.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
getParameters559aaf0e9ed06327b62013245a06aece.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
    const getParameters559aaf0e9ed06327b62013245a06aeceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getParameters559aaf0e9ed06327b62013245a06aece.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
        getParameters559aaf0e9ed06327b62013245a06aeceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getParameters559aaf0e9ed06327b62013245a06aece.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameter'
 */
        getParameters559aaf0e9ed06327b62013245a06aeceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getParameters559aaf0e9ed06327b62013245a06aece.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getParameters559aaf0e9ed06327b62013245a06aece.form = getParameters559aaf0e9ed06327b62013245a06aeceForm
    /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
const getParametersbe884e1159ddf44948d96cef538010ce = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'get',
})

getParametersbe884e1159ddf44948d96cef538010ce.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/ews/parameters',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
getParametersbe884e1159ddf44948d96cef538010ce.url = (options?: RouteQueryOptions) => {
    return getParametersbe884e1159ddf44948d96cef538010ce.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
getParametersbe884e1159ddf44948d96cef538010ce.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
getParametersbe884e1159ddf44948d96cef538010ce.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
    const getParametersbe884e1159ddf44948d96cef538010ceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getParametersbe884e1159ddf44948d96cef538010ce.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
        getParametersbe884e1159ddf44948d96cef538010ceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getParametersbe884e1159ddf44948d96cef538010ce.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::getParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:340
 * @route '/api/v1/internal/admin/ews/parameters'
 */
        getParametersbe884e1159ddf44948d96cef538010ceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getParametersbe884e1159ddf44948d96cef538010ce.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getParametersbe884e1159ddf44948d96cef538010ce.form = getParametersbe884e1159ddf44948d96cef538010ceForm

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\AdminDataController::getParameters, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `getParameters['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const getParameters = {
    '/api/v1/internal/admin/ews/parameter': getParameters559aaf0e9ed06327b62013245a06aece,
    '/api/v1/internal/admin/ews/parameters': getParametersbe884e1159ddf44948d96cef538010ce,
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
const updateParameters559aaf0e9ed06327b62013245a06aece = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'post',
})

updateParameters559aaf0e9ed06327b62013245a06aece.definition = {
    methods: ["post","put"],
    url: '/api/v1/internal/admin/ews/parameter',
} satisfies RouteDefinition<["post","put"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
updateParameters559aaf0e9ed06327b62013245a06aece.url = (options?: RouteQueryOptions) => {
    return updateParameters559aaf0e9ed06327b62013245a06aece.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
updateParameters559aaf0e9ed06327b62013245a06aece.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'post',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
updateParameters559aaf0e9ed06327b62013245a06aece.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateParameters559aaf0e9ed06327b62013245a06aece.url(options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
    const updateParameters559aaf0e9ed06327b62013245a06aeceForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateParameters559aaf0e9ed06327b62013245a06aece.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
        updateParameters559aaf0e9ed06327b62013245a06aeceForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateParameters559aaf0e9ed06327b62013245a06aece.url(options),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameter'
 */
        updateParameters559aaf0e9ed06327b62013245a06aeceForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateParameters559aaf0e9ed06327b62013245a06aece.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateParameters559aaf0e9ed06327b62013245a06aece.form = updateParameters559aaf0e9ed06327b62013245a06aeceForm
    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
const updateParametersbe884e1159ddf44948d96cef538010ce = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'post',
})

updateParametersbe884e1159ddf44948d96cef538010ce.definition = {
    methods: ["post","put"],
    url: '/api/v1/internal/admin/ews/parameters',
} satisfies RouteDefinition<["post","put"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
updateParametersbe884e1159ddf44948d96cef538010ce.url = (options?: RouteQueryOptions) => {
    return updateParametersbe884e1159ddf44948d96cef538010ce.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
updateParametersbe884e1159ddf44948d96cef538010ce.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: updateParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'post',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
updateParametersbe884e1159ddf44948d96cef538010ce.put = (options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateParametersbe884e1159ddf44948d96cef538010ce.url(options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
    const updateParametersbe884e1159ddf44948d96cef538010ceForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: updateParametersbe884e1159ddf44948d96cef538010ce.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
        updateParametersbe884e1159ddf44948d96cef538010ceForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateParametersbe884e1159ddf44948d96cef538010ce.url(options),
            method: 'post',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::updateParameters
 * @see app/Http/Controllers/Internal/AdminDataController.php:346
 * @route '/api/v1/internal/admin/ews/parameters'
 */
        updateParametersbe884e1159ddf44948d96cef538010ceForm.put = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: updateParametersbe884e1159ddf44948d96cef538010ce.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    updateParametersbe884e1159ddf44948d96cef538010ce.form = updateParametersbe884e1159ddf44948d96cef538010ceForm

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\AdminDataController::updateParameters, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `updateParameters['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const updateParameters = {
    '/api/v1/internal/admin/ews/parameter': updateParameters559aaf0e9ed06327b62013245a06aece,
    '/api/v1/internal/admin/ews/parameters': updateParametersbe884e1159ddf44948d96cef538010ce,
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
const eksporKugifbdd567851dfac526371cf7d8579ed4c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporKugifbdd567851dfac526371cf7d8579ed4c.url(options),
    method: 'get',
})

eksporKugifbdd567851dfac526371cf7d8579ed4c.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/geospasial/ekspor-kugi',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
eksporKugifbdd567851dfac526371cf7d8579ed4c.url = (options?: RouteQueryOptions) => {
    return eksporKugifbdd567851dfac526371cf7d8579ed4c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
eksporKugifbdd567851dfac526371cf7d8579ed4c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporKugifbdd567851dfac526371cf7d8579ed4c.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
eksporKugifbdd567851dfac526371cf7d8579ed4c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eksporKugifbdd567851dfac526371cf7d8579ed4c.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
    const eksporKugifbdd567851dfac526371cf7d8579ed4cForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: eksporKugifbdd567851dfac526371cf7d8579ed4c.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
        eksporKugifbdd567851dfac526371cf7d8579ed4cForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporKugifbdd567851dfac526371cf7d8579ed4c.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/geospasial/ekspor-kugi'
 */
        eksporKugifbdd567851dfac526371cf7d8579ed4cForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporKugifbdd567851dfac526371cf7d8579ed4c.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    eksporKugifbdd567851dfac526371cf7d8579ed4c.form = eksporKugifbdd567851dfac526371cf7d8579ed4cForm
    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
const eksporKugifde97f9bf80246accbfd5bca8289501e = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporKugifde97f9bf80246accbfd5bca8289501e.url(options),
    method: 'get',
})

eksporKugifde97f9bf80246accbfd5bca8289501e.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/kugi/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
eksporKugifde97f9bf80246accbfd5bca8289501e.url = (options?: RouteQueryOptions) => {
    return eksporKugifde97f9bf80246accbfd5bca8289501e.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
eksporKugifde97f9bf80246accbfd5bca8289501e.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporKugifde97f9bf80246accbfd5bca8289501e.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
eksporKugifde97f9bf80246accbfd5bca8289501e.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eksporKugifde97f9bf80246accbfd5bca8289501e.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
    const eksporKugifde97f9bf80246accbfd5bca8289501eForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: eksporKugifde97f9bf80246accbfd5bca8289501e.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
        eksporKugifde97f9bf80246accbfd5bca8289501eForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporKugifde97f9bf80246accbfd5bca8289501e.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporKugi
 * @see app/Http/Controllers/Internal/AdminDataController.php:402
 * @route '/api/v1/internal/admin/kugi/export'
 */
        eksporKugifde97f9bf80246accbfd5bca8289501eForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporKugifde97f9bf80246accbfd5bca8289501e.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    eksporKugifde97f9bf80246accbfd5bca8289501e.form = eksporKugifde97f9bf80246accbfd5bca8289501eForm

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\AdminDataController::eksporKugi, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `eksporKugi['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const eksporKugi = {
    '/api/v1/internal/admin/geospasial/ekspor-kugi': eksporKugifbdd567851dfac526371cf7d8579ed4c,
    '/api/v1/internal/admin/kugi/export': eksporKugifde97f9bf80246accbfd5bca8289501e,
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
const eksporIsoXml4343143cc735242c3348915475a35ae6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporIsoXml4343143cc735242c3348915475a35ae6.url(options),
    method: 'get',
})

eksporIsoXml4343143cc735242c3348915475a35ae6.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/geospasial/metadata-iso',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
eksporIsoXml4343143cc735242c3348915475a35ae6.url = (options?: RouteQueryOptions) => {
    return eksporIsoXml4343143cc735242c3348915475a35ae6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
eksporIsoXml4343143cc735242c3348915475a35ae6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporIsoXml4343143cc735242c3348915475a35ae6.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
eksporIsoXml4343143cc735242c3348915475a35ae6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eksporIsoXml4343143cc735242c3348915475a35ae6.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
    const eksporIsoXml4343143cc735242c3348915475a35ae6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: eksporIsoXml4343143cc735242c3348915475a35ae6.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
        eksporIsoXml4343143cc735242c3348915475a35ae6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporIsoXml4343143cc735242c3348915475a35ae6.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/geospasial/metadata-iso'
 */
        eksporIsoXml4343143cc735242c3348915475a35ae6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporIsoXml4343143cc735242c3348915475a35ae6.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    eksporIsoXml4343143cc735242c3348915475a35ae6.form = eksporIsoXml4343143cc735242c3348915475a35ae6Form
    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
const eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url(options),
    method: 'get',
})

eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/admin/iso-xml/export',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url = (options?: RouteQueryOptions) => {
    return eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
    const eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
        eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml
 * @see app/Http/Controllers/Internal/AdminDataController.php:449
 * @route '/api/v1/internal/admin/iso-xml/export'
 */
        eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25.form = eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25Form

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\AdminDataController::eksporIsoXml, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `eksporIsoXml['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const eksporIsoXml = {
    '/api/v1/internal/admin/geospasial/metadata-iso': eksporIsoXml4343143cc735242c3348915475a35ae6,
    '/api/v1/internal/admin/iso-xml/export': eksporIsoXml8572a3b4c356753e1a3d9786b2a7fe25,
}

const AdminDataController = { indexPopulasi, storePopulasi, showPopulasi, updatePopulasi, destroyPopulasi, indexProduksi, storeProduksi, showProduksi, updateProduksi, destroyProduksi, indexKasus, storeKasus, showKasus, updateKasus, destroyKasus, indexRph, storeRph, showRph, updateRph, destroyRph, getParameters, updateParameters, eksporKugi, eksporIsoXml }

export default AdminDataController