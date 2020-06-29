<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Searching path
    |--------------------------------------------------------------------------
    |
    | The path where typescript transformer will look for PHP classes
    | to transform, this will be the `app` path by default.
    |
    */

    'searching_path' => app_path(),

    /*
    |--------------------------------------------------------------------------
    | Transformers
    |--------------------------------------------------------------------------
    |
    | In these classes you transform your PHP classes(e.g. enums) to
    | their Typescript counterparts.
    |
    */

    'transformers' => [
        Spatie\LaravelTypescriptTransformer\Transformers\EnumTransformer::class,
        Spatie\LaravelTypescriptTransformer\Transformers\StateTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output file
    |--------------------------------------------------------------------------
    |
    | Typescript transformer will write it's Typescript structures to this
    | file.
    |
    */

    'output_file' => resource_path('types/generated.d.ts'),
];
