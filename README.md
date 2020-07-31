# Transform your PHP structures to Typescript types

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/typescript-transformer/run-tests?label=tests)](https://github.com/spatie/typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/typescript-transformer)

**This package is still under heavy development, please do not use it (yet)**

Always wanted type safety within PHP and Typescript without duplicating a lot of code? Then you will like this package! Let's say you have a enum:

```php
class Languages extends Enum
{
    const TYPESCRIPT = 'typescript';
    const PHP = 'php';
}
```

Wouldn't it be cool if you could have an automatically generated Typescript definition like this:

```typescript
export type Languages = 'typescript' | 'php';
```

This package will automatically generate such definitions for you, the only thing you have to do is adding this annotation:

```php
/** @typescript **/
class Languages extends Enum
{
    const TYPESCRIPT = 'typescript';
    const PHP = 'php';
}
```

You just need to run one command:

```bash
php artisan typescript:transform
```

Want to use this outside of a Laravel application, take a look at: [typescript-transformer](https://github.com/spatie/typescript-transformer).

## Support us

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us). 

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-typescript-transformer
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Spatie\LaravelTypescriptTransformer\TypescriptTransformerServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
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
    | Collectors
    |--------------------------------------------------------------------------
    |
    | In these classes you define which classes will be collected and fed to
    | transformers. By default, we include an AnnotationCollector which will
    | search for @typescript annotated classes to transform.
    |
    */

    'collectors' => [
        Spatie\TypescriptTransformer\Collectors\AnnotationCollector::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Transformers
    |--------------------------------------------------------------------------
    |
    | In these classes, you transform your PHP classes(e.g., enums) to
    | their Typescript counterparts.
    |
    */

    'transformers' => [
        Spatie\LaravelTypescriptTransformer\Transformers\EnumTransformer::class,
        Spatie\LaravelTypescriptTransformer\Transformers\StateTransformer::class,
        Spatie\LaravelTypescriptTransformer\Transformers\DtoTransformer::class,
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
```

## Usage

Please, first read the documentation of the [typescript-transformer](https://github.com/spatie/typescript-transformer/blob/master/README.md) package. It contains all the information on how the package works and how to create transformers, collectors, and property processors.

When you've configured the package with the config file, you can run the following command:

```bash
php artisan typescript:transform
```

It is also possible to only transform one class:

```bash
php artisan typescript:transform --class=app/Enums/RoleEnum.php
```

Or you can define another output file than the default one:

```bash
php artisan typescript:transform --output=types.d.ts
```

This file will be stored in the resource's path.

## Transformers

By default, the `typescript-transformer` package delivers some default Transformers, and this Laravel package adds some extra default transformers you can use:

- `EnumTransformer` convert enums from the `spatie/enum` package
- `StateTransformer` convert states from the `spatie/enum` package
- `DtoTransformer` an extended DTO transformer that also will recognize Laravel collections and Carbon objects

When using the `DtoTransformer` in your config, be assured to use the transformer of the `laravel-typescript-transformer` package, since the one in the `typescript-transformer` package has no support for Laravel.

## Class property processors

In addition to some extra transformers specific for Laravel we've also added some extra class property processors specific for Laravel:

- `LaravelCollectionClassPropertyProcessor` will replace Laravel Collections as arrays
- `LaravelDateClassPropertyProcessor` will replace date objects(`DateTime` and `Carbon`) with a string

When using the `DtoTransformer` these processors will automatically be applied.

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email freek@spatie.be instead of using the issue tracker.

## Credits

- [Ruben Van Assche](https://github.com/rubenvanassche)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
