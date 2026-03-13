<?php

namespace Spatie\LaravelTypeScriptTransformer\Actions;

use Spatie\LaravelTypeScriptTransformer\References\LaravelControllerReference;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptAlias;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptArray;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptBoolean;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptCallable;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptConditional;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptFunctionDeclaration;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGeneric;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGenericTypeParameter;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIntersection;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptMappedType;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNull;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptOperator;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptParameter;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptRaw;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUndefined;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptUnion;

class GenerateControllerSupportAction
{
    /** @var ?array<Transformed> */
    protected static ?array $cachedSupport = null;

    /** @return array<Transformed> */
    public function execute(): array
    {
        if (static::$cachedSupport !== null) {
            return static::$cachedSupport;
        }

        return static::$cachedSupport = [
            new Transformed(
                new TypeScriptAlias(
                    'RouteParams',
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('Record'),
                        [new TypeScriptString(), new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber()])]
                    )
                ),
                LaravelControllerReference::supportItem('RouteParams'),
                [],
                true,
            ),

            new Transformed(
                new TypeScriptAlias(
                    'RouteDefinition',
                    new TypeScriptObject([
                        new TypeScriptProperty('url', new TypeScriptString()),
                        new TypeScriptProperty('method', new TypeScriptString()),
                    ])
                ),
                LaravelControllerReference::supportItem('RouteDefinition'),
                [],
                true,
            ),

            new Transformed(
                new TypeScriptAlias(
                    'QueryParams',
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('Record'),
                        [
                            new TypeScriptString(),
                            new TypeScriptUnion([
                                new TypeScriptString(),
                                new TypeScriptNumber(),
                                new TypeScriptBoolean(),
                                new TypeScriptNull(),
                                new TypeScriptUndefined(),
                                new TypeScriptArray([
                                    new TypeScriptUnion([new TypeScriptString(), new TypeScriptNumber(), new TypeScriptBoolean()]),
                                ]),
                            ]),
                        ]
                    )
                ),
                LaravelControllerReference::supportItem('QueryParams'),
                [],
                true,
            ),

            new Transformed(
                new TypeScriptAlias(
                    'RouteOptions',
                    new TypeScriptObject([
                        new TypeScriptProperty('query', new TypeScriptIdentifier('QueryParams'), isOptional: true),
                    ])
                ),
                LaravelControllerReference::supportItem('RouteOptions'),
                [],
                true,
            ),

            new Transformed(
                new TypeScriptAlias(
                    'MethodRoute',
                    new TypeScriptObject([
                        new TypeScriptProperty('method', new TypeScriptString()),
                        new TypeScriptProperty('url', new TypeScriptString()),
                    ])
                ),
                LaravelControllerReference::supportItem('MethodRoute'),
                [],
                true,
            ),

            (new Transformed(
                new TypeScriptRaw('declare const baseUrl: string;'),
                LaravelControllerReference::supportItem('baseUrl'),
                [],
                false,
            ))->nameAs('baseUrl'),

            new Transformed(
                new TypeScriptFunctionDeclaration(
                    'buildUrl',
                    [
                        new TypeScriptParameter('path', new TypeScriptString()),
                        new TypeScriptParameter('params', new TypeScriptIdentifier('RouteParams'), isOptional: true),
                        new TypeScriptParameter('options', new TypeScriptIdentifier('RouteOptions'), isOptional: true),
                    ],
                    new TypeScriptString(),
                    new TypeScriptRaw(<<<'TS'
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
                    TS),
                ),
                LaravelControllerReference::supportItem('buildUrl'),
                [],
                true,
            ),

            new Transformed(
                new TypeScriptAlias(
                    'ActionResult',
                    new TypeScriptIntersection([
                        new TypeScriptIdentifier('RouteDefinition'),
                        new TypeScriptObject([
                            new TypeScriptProperty('url', new TypeScriptString()),
                        ]),
                    ])
                ),
                LaravelControllerReference::supportItem('ActionResult'),
                [],
                false,
            ),

            new Transformed(
                new TypeScriptAlias(
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('ActionFunction'),
                        [new TypeScriptGenericTypeParameter(
                            new TypeScriptIdentifier('P'),
                            extends: new TypeScriptUnion([new TypeScriptIdentifier('RouteParams'), new TypeScriptUndefined()])
                        )]
                    ),
                    new TypeScriptConditional(
                        TypeScriptOperator::extends(new TypeScriptIdentifier('P'), new TypeScriptUndefined()),
                        new TypeScriptCallable(
                            [new TypeScriptParameter('options', new TypeScriptIdentifier('RouteOptions'), isOptional: true)],
                            new TypeScriptIdentifier('ActionResult')
                        ),
                        new TypeScriptCallable(
                            [
                                new TypeScriptParameter('params', new TypeScriptIdentifier('P')),
                                new TypeScriptParameter('options', new TypeScriptIdentifier('RouteOptions'), isOptional: true),
                            ],
                            new TypeScriptIdentifier('ActionResult')
                        ),
                    )
                ),
                LaravelControllerReference::supportItem('ActionFunction'),
                [],
                false,
            ),

            new Transformed(
                new TypeScriptAlias(
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('ActionWithMethods'),
                        [
                            new TypeScriptGenericTypeParameter(
                                new TypeScriptIdentifier('P'),
                                extends: new TypeScriptUnion([new TypeScriptIdentifier('RouteParams'), new TypeScriptUndefined()])
                            ),
                            new TypeScriptGenericTypeParameter(
                                new TypeScriptIdentifier('M'),
                                extends: new TypeScriptString()
                            ),
                        ]
                    ),
                    new TypeScriptIntersection([
                        new TypeScriptGeneric(new TypeScriptIdentifier('ActionFunction'), [new TypeScriptIdentifier('P')]),
                        new TypeScriptMappedType(
                            'K',
                            new TypeScriptIdentifier('M'),
                            new TypeScriptGeneric(new TypeScriptIdentifier('ActionFunction'), [new TypeScriptIdentifier('P')])
                        ),
                    ])
                ),
                LaravelControllerReference::supportItem('ActionWithMethods'),
                [],
                false,
            ),

            new Transformed(
                new TypeScriptFunctionDeclaration(
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('createActionWithMethods'),
                        [
                            new TypeScriptGenericTypeParameter(
                                new TypeScriptIdentifier('P'),
                                extends: new TypeScriptUnion([new TypeScriptIdentifier('RouteParams'), new TypeScriptUndefined()]),
                                default: new TypeScriptUndefined()
                            ),
                            new TypeScriptGenericTypeParameter(
                                new TypeScriptIdentifier('M'),
                                extends: new TypeScriptString(),
                                default: new TypeScriptString()
                            ),
                        ]
                    ),
                    [
                        new TypeScriptParameter(
                            'routes',
                            new TypeScriptArray([new TypeScriptIdentifier('MethodRoute')])
                        ),
                    ],
                    new TypeScriptGeneric(
                        new TypeScriptIdentifier('ActionWithMethods'),
                        [new TypeScriptIdentifier('P'), new TypeScriptIdentifier('M')]
                    ),
                    new TypeScriptRaw(<<<'TS'
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
                    TS),
                ),
                LaravelControllerReference::supportItem('createActionWithMethods'),
                [],
                true,
            ),
        ];
    }
}
