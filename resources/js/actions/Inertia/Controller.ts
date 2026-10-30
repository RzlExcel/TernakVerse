import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
const Controller980bb49ee7ae63891f1d891d2fbcf1c9 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})

Controller980bb49ee7ae63891f1d891d2fbcf1c9.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.url = (options?: RouteQueryOptions) => {
    return Controller980bb49ee7ae63891f1d891d2fbcf1c9.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
Controller980bb49ee7ae63891f1d891d2fbcf1c9.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
    const Controller980bb49ee7ae63891f1d891d2fbcf1c9Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
        Controller980bb49ee7ae63891f1d891d2fbcf1c9Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/'
 */
        Controller980bb49ee7ae63891f1d891d2fbcf1c9Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller980bb49ee7ae63891f1d891d2fbcf1c9.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller980bb49ee7ae63891f1d891d2fbcf1c9.form = Controller980bb49ee7ae63891f1d891d2fbcf1c9Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
const Controllerbbbc08286ef6a2400dbe9df4f48889ac = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url(options),
    method: 'get',
})

Controllerbbbc08286ef6a2400dbe9df4f48889ac.definition = {
    methods: ["get","head"],
    url: '/portal',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
Controllerbbbc08286ef6a2400dbe9df4f48889ac.url = (options?: RouteQueryOptions) => {
    return Controllerbbbc08286ef6a2400dbe9df4f48889ac.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
Controllerbbbc08286ef6a2400dbe9df4f48889ac.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
Controllerbbbc08286ef6a2400dbe9df4f48889ac.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
    const Controllerbbbc08286ef6a2400dbe9df4f48889acForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
        Controllerbbbc08286ef6a2400dbe9df4f48889acForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/portal'
 */
        Controllerbbbc08286ef6a2400dbe9df4f48889acForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerbbbc08286ef6a2400dbe9df4f48889ac.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllerbbbc08286ef6a2400dbe9df4f48889ac.form = Controllerbbbc08286ef6a2400dbe9df4f48889acForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
const Controllerdf064c0f8e4271f3080b85da77bc3bbf = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url(options),
    method: 'get',
})

Controllerdf064c0f8e4271f3080b85da77bc3bbf.definition = {
    methods: ["get","head"],
    url: '/internal/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
Controllerdf064c0f8e4271f3080b85da77bc3bbf.url = (options?: RouteQueryOptions) => {
    return Controllerdf064c0f8e4271f3080b85da77bc3bbf.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
Controllerdf064c0f8e4271f3080b85da77bc3bbf.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
Controllerdf064c0f8e4271f3080b85da77bc3bbf.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
    const Controllerdf064c0f8e4271f3080b85da77bc3bbfForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
        Controllerdf064c0f8e4271f3080b85da77bc3bbfForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/login'
 */
        Controllerdf064c0f8e4271f3080b85da77bc3bbfForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllerdf064c0f8e4271f3080b85da77bc3bbf.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllerdf064c0f8e4271f3080b85da77bc3bbf.form = Controllerdf064c0f8e4271f3080b85da77bc3bbfForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
const Controller68491b430775af3a987e021e56b187c5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller68491b430775af3a987e021e56b187c5.url(options),
    method: 'get',
})

Controller68491b430775af3a987e021e56b187c5.definition = {
    methods: ["get","head"],
    url: '/internal/dinas',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
Controller68491b430775af3a987e021e56b187c5.url = (options?: RouteQueryOptions) => {
    return Controller68491b430775af3a987e021e56b187c5.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
Controller68491b430775af3a987e021e56b187c5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controller68491b430775af3a987e021e56b187c5.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
Controller68491b430775af3a987e021e56b187c5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controller68491b430775af3a987e021e56b187c5.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
    const Controller68491b430775af3a987e021e56b187c5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controller68491b430775af3a987e021e56b187c5.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
        Controller68491b430775af3a987e021e56b187c5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller68491b430775af3a987e021e56b187c5.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/dinas'
 */
        Controller68491b430775af3a987e021e56b187c5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controller68491b430775af3a987e021e56b187c5.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controller68491b430775af3a987e021e56b187c5.form = Controller68491b430775af3a987e021e56b187c5Form
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
const Controllereb8a0de0393c4c5746b57f44f022df4a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllereb8a0de0393c4c5746b57f44f022df4a.url(options),
    method: 'get',
})

Controllereb8a0de0393c4c5746b57f44f022df4a.definition = {
    methods: ["get","head"],
    url: '/internal/admin',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
Controllereb8a0de0393c4c5746b57f44f022df4a.url = (options?: RouteQueryOptions) => {
    return Controllereb8a0de0393c4c5746b57f44f022df4a.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
Controllereb8a0de0393c4c5746b57f44f022df4a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllereb8a0de0393c4c5746b57f44f022df4a.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
Controllereb8a0de0393c4c5746b57f44f022df4a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllereb8a0de0393c4c5746b57f44f022df4a.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
    const Controllereb8a0de0393c4c5746b57f44f022df4aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllereb8a0de0393c4c5746b57f44f022df4a.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
        Controllereb8a0de0393c4c5746b57f44f022df4aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllereb8a0de0393c4c5746b57f44f022df4a.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/internal/admin'
 */
        Controllereb8a0de0393c4c5746b57f44f022df4aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllereb8a0de0393c4c5746b57f44f022df4a.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllereb8a0de0393c4c5746b57f44f022df4a.form = Controllereb8a0de0393c4c5746b57f44f022df4aForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
const Controllera07d183345a0a6e373198c69d603dd0a = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllera07d183345a0a6e373198c69d603dd0a.url(options),
    method: 'get',
})

Controllera07d183345a0a6e373198c69d603dd0a.definition = {
    methods: ["get","head"],
    url: '/design-rule',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
Controllera07d183345a0a6e373198c69d603dd0a.url = (options?: RouteQueryOptions) => {
    return Controllera07d183345a0a6e373198c69d603dd0a.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
Controllera07d183345a0a6e373198c69d603dd0a.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllera07d183345a0a6e373198c69d603dd0a.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
Controllera07d183345a0a6e373198c69d603dd0a.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllera07d183345a0a6e373198c69d603dd0a.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
    const Controllera07d183345a0a6e373198c69d603dd0aForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllera07d183345a0a6e373198c69d603dd0a.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
        Controllera07d183345a0a6e373198c69d603dd0aForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllera07d183345a0a6e373198c69d603dd0a.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
        Controllera07d183345a0a6e373198c69d603dd0aForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllera07d183345a0a6e373198c69d603dd0a.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllera07d183345a0a6e373198c69d603dd0a.form = Controllera07d183345a0a6e373198c69d603dd0aForm
    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
const Controllere19ee86e9cf603ce1a59a1ec5d21dec5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url = (options?: RouteQueryOptions) => {
    return Controllere19ee86e9cf603ce1a59a1ec5d21dec5.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
Controllere19ee86e9cf603ce1a59a1ec5d21dec5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
    const Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
        Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/settings/appearance'
 */
        Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: Controllere19ee86e9cf603ce1a59a1ec5d21dec5.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    Controllere19ee86e9cf603ce1a59a1ec5d21dec5.form = Controllere19ee86e9cf603ce1a59a1ec5d21dec5Form

/**
* Multiple routes resolve to \Inertia\Controller::Controller, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `Controller['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
const Controller = {
    '/': Controller980bb49ee7ae63891f1d891d2fbcf1c9,
    '/portal': Controllerbbbc08286ef6a2400dbe9df4f48889ac,
    '/internal/login': Controllerdf064c0f8e4271f3080b85da77bc3bbf,
    '/internal/dinas': Controller68491b430775af3a987e021e56b187c5,
    '/internal/admin': Controllereb8a0de0393c4c5746b57f44f022df4a,
    '/design-rule': Controllera07d183345a0a6e373198c69d603dd0a,
    '/settings/appearance': Controllere19ee86e9cf603ce1a59a1ec5d21dec5,
}

export default Controller