import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
export const getPetaKabupaten = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPetaKabupaten.url(options),
    method: 'get',
})

getPetaKabupaten.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/peta/kabupaten',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
getPetaKabupaten.url = (options?: RouteQueryOptions) => {
    return getPetaKabupaten.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
getPetaKabupaten.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getPetaKabupaten.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
getPetaKabupaten.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getPetaKabupaten.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
    const getPetaKabupatenForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getPetaKabupaten.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
        getPetaKabupatenForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPetaKabupaten.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getPetaKabupaten
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:33
 * @route '/api/v1/internal/peta/kabupaten'
 */
        getPetaKabupatenForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getPetaKabupaten.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getPetaKabupaten.form = getPetaKabupatenForm
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
const getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4 = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, options),
    method: 'get',
})

getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/peta/kecamatan/{id}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
    const getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4Form = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
        getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4Form.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}'
 */
        getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4Form.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4.form = getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4Form
    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
const getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990 = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, options),
    method: 'get',
})

getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/peta/kecamatan/{id}/drilldown',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.definition.url
            .replace('{id}', parsedArgs.id.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
    const getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990Form = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
        getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990Form.get = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:187
 * @route '/api/v1/internal/peta/kecamatan/{id}/drilldown'
 */
        getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990Form.head = (args: { id: string | number } | [id: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990.form = getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990Form

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\DinasDashboardController::getDrilldownKecamatan, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `getDrilldownKecamatan['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const getDrilldownKecamatan = {
    '/api/v1/internal/peta/kecamatan/{id}': getDrilldownKecamatan8e08fccd5ca36b111c971d729dc30ec4,
    '/api/v1/internal/peta/kecamatan/{id}/drilldown': getDrilldownKecamatan1589b5081f11af7e5a3c56d403e62990,
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
const getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url(options),
    method: 'get',
})

getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/analitik/data',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url = (options?: RouteQueryOptions) => {
    return getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
    const getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9dForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
        getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9dForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/data'
 */
        getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9dForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d.form = getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9dForm
    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
const getAnalitikData9fd0cf32c976192d1c745ffa14c3747d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url(options),
    method: 'get',
})

getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/analitik/metrik',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url = (options?: RouteQueryOptions) => {
    return getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
    const getAnalitikData9fd0cf32c976192d1c745ffa14c3747dForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
        getAnalitikData9fd0cf32c976192d1c745ffa14c3747dForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:269
 * @route '/api/v1/internal/analitik/metrik'
 */
        getAnalitikData9fd0cf32c976192d1c745ffa14c3747dForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    getAnalitikData9fd0cf32c976192d1c745ffa14c3747d.form = getAnalitikData9fd0cf32c976192d1c745ffa14c3747dForm

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\DinasDashboardController::getAnalitikData, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `getAnalitikData['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const getAnalitikData = {
    '/api/v1/internal/analitik/data': getAnalitikData9446ffd81dea9adb22f1ebf9a9cb7a9d,
    '/api/v1/internal/analitik/metrik': getAnalitikData9fd0cf32c976192d1c745ffa14c3747d,
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
const downloadLaporancdd0eb13036b6ea385cfb0e7341c033d = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url(options),
    method: 'get',
})

downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/laporan/unduh',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url = (options?: RouteQueryOptions) => {
    return downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
    const downloadLaporancdd0eb13036b6ea385cfb0e7341c033dForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
        downloadLaporancdd0eb13036b6ea385cfb0e7341c033dForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/unduh'
 */
        downloadLaporancdd0eb13036b6ea385cfb0e7341c033dForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    downloadLaporancdd0eb13036b6ea385cfb0e7341c033d.form = downloadLaporancdd0eb13036b6ea385cfb0e7341c033dForm
    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
const downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url(options),
    method: 'get',
})

downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.definition = {
    methods: ["get","head"],
    url: '/api/v1/internal/laporan/download',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url = (options?: RouteQueryOptions) => {
    return downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
    const downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
        downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan
 * @see app/Http/Controllers/Internal/DinasDashboardController.php:293
 * @route '/api/v1/internal/laporan/download'
 */
        downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6.form = downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6Form

/**
* Multiple routes resolve to \App\Http\Controllers\Internal\DinasDashboardController::downloadLaporan, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `downloadLaporan['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const downloadLaporan = {
    '/api/v1/internal/laporan/unduh': downloadLaporancdd0eb13036b6ea385cfb0e7341c033d,
    '/api/v1/internal/laporan/download': downloadLaporan271d7941ab2ca2f64568ade1d47a4dd6,
}

const DinasDashboardController = { getPetaKabupaten, getDrilldownKecamatan, getAnalitikData, downloadLaporan }

export default DinasDashboardController