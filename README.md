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
php artisan vendor:publish --provider="Spatie\Skeleton\SkeletonServiceProvider" --tag="config"
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
    | Default file
    |--------------------------------------------------------------------------
    |
    | When transforming PHP classes an output file can be declared for the class
    | in the annotations. When left empty, the type for the class 
    | will be written to this file.
    |
    */

    'default_file' => 'types/generated.d.ts',

    /*
    |--------------------------------------------------------------------------
    | Output path
    |--------------------------------------------------------------------------
    |
    | In the end typescript files will be written to the following
    | directory, by default this is the `resources/js` directory
    |
    */

    'output_path' => resource_path('js'),
];
```

## Usage



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
