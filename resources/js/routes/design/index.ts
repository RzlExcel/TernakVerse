import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
export const rule = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rule.url(options),
    method: 'get',
})

rule.definition = {
    methods: ["get","head"],
    url: '/design-rule',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
rule.url = (options?: RouteQueryOptions) => {
    return rule.definition.url + queryParams(options)
}

/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
rule.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: rule.url(options),
    method: 'get',
})
/**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
rule.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: rule.url(options),
    method: 'head',
})

    /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
    const ruleForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: rule.url(options),
        method: 'get',
    })

            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
        ruleForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: rule.url(options),
            method: 'get',
        })
            /**
* @see \Inertia\Controller::__invoke
 * @see vendor/inertiajs/inertia-laravel/src/Controller.php:13
 * @route '/design-rule'
 */
        ruleForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: rule.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    rule.form = ruleForm
const design = {
    rule: Object.assign(rule, rule),
}

export default design