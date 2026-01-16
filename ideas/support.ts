/**
 * Core runtime utilities for generated action functions.
 * This file is copied to the generated output directory.
 */

export type RouteParams = Record<string, string | number>;

export type RouteDefinition = {
    url: string;
    method: string;
};

export type QueryParams = Record<string, string | number | boolean | null | undefined | (string | number | boolean)[]>;

export type RouteOptions = {
    query?: QueryParams;
};

const baseUrl = 'https://flareapp.io.test';

/**
 * Builds a URL by replacing route parameters and appending query string.
 */
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

    return baseUrl + '/' + url;
}

/**
 * Creates an action function for an invokable controller (single action).
 */
export function createInvokableAction<P extends RouteParams | undefined = undefined>(
    path: string,
    method: string = 'get'
) {
    type Params = P extends undefined ? [] : [params: P, options?: RouteOptions];
    type ParamsWithOptions = P extends undefined ? [options?: RouteOptions] : [params: P, options?: RouteOptions];

    const fn = (...args: ParamsWithOptions): RouteDefinition => {
        const params = (args[0] && typeof args[0] === 'object' && !('query' in args[0]))
            ? args[0] as P
            : undefined;
        const options = params ? args[1] as RouteOptions : args[0] as RouteOptions;

        return {
            url: buildUrl(path, params as RouteParams, options),
            method,
        };
    };

    return Object.assign(fn, {
        url: (...args: ParamsWithOptions): string => fn(...args).url,
        definition: { path, method },
    });
}

/**
 * Creates an action function for a specific controller method.
 */
export function createAction<P extends RouteParams | undefined = undefined>(
    path: string,
    method: string = 'get'
) {
    return createInvokableAction<P>(path, method);
}