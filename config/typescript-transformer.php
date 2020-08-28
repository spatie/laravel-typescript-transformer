<?php

use Carbon\Carbon;
use Carbon\CarbonImmutable;

return [
    /*
    |--------------------------------------------------------------------------
    | Searching path
    |--------------------------------------------------------------------------
    |
    | The path where typescript-transformer will look for PHP classes
    | to transform, this will be the `app` path by default.
    |
    */

    'searching_path' => app_path(),

    /*
    |--------------------------------------------------------------------------
    | Collectors
    |--------------------------------------------------------------------------
    |
    | In these classes you define which classes will be collected and fed to
    | transformers. By default, we include an AnnotationCollector which will
    | search for @typescript annotated classes to transform.
    |
    */

    'collectors' => [
        Spatie\TypeScriptTransformer\Collectors\AnnotationCollector::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transformers
    |--------------------------------------------------------------------------
    |
    | In these classes, you transform your PHP classes(e.g., enums) to
    | their TypeScript counterparts.
    |
    */

    'transformers' => [
        Spatie\LaravelTypeScriptTransformer\Transformers\SpatieEnumTransformer::class,
        Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer::class,
        Spatie\TypeScriptTransformer\Transformers\DtoTransformer::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class property replacements
    |--------------------------------------------------------------------------
    |
    | In your DTO's you sometimes have properties that should always be replaced
    | by TypeScript representations. For example, you can replace a Datetime
    | always with a string. These replacements can be defined here.
    |
    */

    'class_property_replacements' => [
        DateTime::class => 'string',
        DateTimeImmutable::class => 'string',
        Carbon::class => 'string',
        CarbonImmutable::class => 'string',
    ],

    /*
    |--------------------------------------------------------------------------
    | Output file
    |--------------------------------------------------------------------------
    |
    | TypeScript transformer will write it's TypeScript structures to this
    | file.
    |
    */

    'output_file' => resource_path('types/generated.d.ts'),
];
