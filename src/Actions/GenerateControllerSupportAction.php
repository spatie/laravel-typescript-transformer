<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;

class GenerateControllerSupportAction
{
    public function execute(): Transformed
    {
        $supportCode = <<<'TS'
export type RouteParams = Record<string, string | number>;

export type RouteDefinition = {
    url: string;
    method: string;
};

export type QueryParams = Record<string, string | number | boolean | null | undefined | (string | number | boolean)[]>;

export type RouteOptions = {
    query?: QueryParams;
};

export type MethodRoute = {
    method: string;
    url: string;
};

declare const baseUrl: string;

export function buildUrl(
    path: string,
    params?: RouteParams,
    options?: RouteOptions
): string {
    let url = path;

    if (params) {
        for (const [key, value] of Object.entries(params)) {
            url = url.replace(`{${key}}`, String(value));
        }
    }

    if (options?.query) {
        const searchParams = new URLSearchParams();

        for (const [key, value] of Object.entries(options.query)) {
            if (value === null || value === undefined) {
                continue;
            }

            if (Array.isArray(value)) {
                value.forEach((v) => searchParams.append(`${key}[]`, String(v)));
            } else {
                searchParams.append(key, String(value));
            }
        }

        const queryString = searchParams.toString();

        if (queryString) {
            url += '?' + queryString;
        }
    }

    return (typeof baseUrl !== 'undefined' ? baseUrl : '') + '/' + url;
}

type ActionResult = RouteDefinition & {
    url: string;
};

type ActionFunction<P extends RouteParams | undefined> = P extends undefined
    ? (options?: RouteOptions) => ActionResult
    : (params: P, options?: RouteOptions) => ActionResult;

type ActionWithMethods<P extends RouteParams | undefined, M extends string> = ActionFunction<P> & {
    [K in M]: ActionFunction<P>;
};

export function createActionWithMethods<P extends RouteParams | undefined = undefined, M extends string = string>(
    routes: MethodRoute[]
): ActionWithMethods<P, M> {
    const defaultRoute = routes[0];

    const createFn = (route: MethodRoute): ActionFunction<P> => {
        return ((...args: [P?, RouteOptions?]) => {
            const params = args[0] && typeof args[0] === 'object' && !('query' in args[0])
                ? args[0] as P
                : undefined;
            const options = params ? args[1] as RouteOptions : args[0] as RouteOptions;

            const url = buildUrl(route.url, params as RouteParams, options);

            return {
                url,
                method: route.method,
            };
        }) as ActionFunction<P>;
    };

    const fn = createFn(defaultRoute);

    const methodVariants: Record<string, ActionFunction<P>> = {};
    for (const route of routes) {
        methodVariants[route.method.toLowerCase()] = createFn(route);
    }

    return Object.assign(fn, methodVariants) as ActionWithMethods<P, M>;
}
TS;

        return new Transformed(
            new TypeScriptRaw($supportCode),
            LaravelControllerReference::support(),
            ['controllers'],
            false,
        );
    }
}
