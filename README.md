[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/support-ukraine.svg?t=1" />](https://supportukrainenow.org)

# Transform PHP types to TypeScript

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-typescript-transformer/run-tests?label=tests)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Styling](https://github.com/spatie/laravel-typescript-transformer/workflows/Check%20&%20fix%20styling/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3A%22Check+%26+fix+styling%22)
[![Psalm](https://github.com/spatie/laravel-typescript-transformer/workflows/Psalm/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3APsalm)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)

A
This package allows you to convert PHP classes to TypeScript.

This class...

```php
#[TypeScript]
class User
{
    public int $id;
    public string $name;
    public ?string $address;
}
```

... will be converted to this TypeScript type:

```ts
export type User = {
    id: number;
    name: string;
    address: string | null;
}
```

Here's another example.

```php
enum Languages: string
{
    case TYPESCRIPT = 'typescript';
    case PHP = 'php';
}
```

The `Languages` enum will be converted to:

```tsx
export type Languages = 'typescript' | 'php';
```

And that's just the beginning! TypeScript transformer can handle complex types, generics and even allows you to create
TypeScript functions.

## Installation

You can install the package via composer:

```bash
composer require spatie/laravel-typescript-transformer
```

## Setting up TypeScript transformer

When using Laravel, first install the specific `TypeScriptTransformerServiceProvider`:

```bash
php artisan typescript:install
```

This command will create a `TypeScriptTransformerServiceProvider` in your `app/Providers` directory. Which looks like
this:

```php
class TypeScriptTransformerServiceProvider extends BaseTypeScriptTransformerServiceProvider
{
    protected function configure(TypeScriptTransformerConfigFactory $config): void
    {
        $config; // We'll come back to this in a minute
    }
}
```

And it will also register the service provider in your `bootstrap/providers.php` file (when running Laravel 11 or
above). Or in your `config/app.php` file when running Laravel 10 or below.

Now you can transform types as such:

```bash
php artisan typescript:transform
```

Since we haven't configured TypeScript transformer yet, this command won't do anything.

In order to configure TypeScript Transformer, we recommand you to now continue reading the documentation on the
framework-agnostic [typescript-transformer](https://github.com/spatie/typescript-transformer) package. The docs will
explain how to configure the package which is by modifying the `$config` object we saw earlier in the
`TypeScriptTransformerServiceProvider`.

After you're done reading the framework-agnostic docs, you can return here to read about Laravel-specific features this
package provides.

## Watching changes and live updating TypeScript

It is possible to have TypeScript transformer watch your PHP files for changes and automatically update the generated 
TypeScript files. You can do this by running:

```bash
php artisan typescript:transform --watch
```

## Laravel-specific features

This package provides some extra features on top of the base TypeScript transformer package tailed for Laravel
applications. Let's go through them.

### Your routes in TypeScript

Laravel provides a great way to define routes and then generate URLs to those routes in PHP using the `route()` and 
`action()` helpers. While this all works in PHP, it can be a bit of a pain to do the same in TypeScript.

TypeScript transformer can help you here by providing exact copies of these helpers in TypeScript.

To add an action helper, add the following provider to your `TypeScriptTransformerServiceProvider`:

```php
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelRouteActionTransformedProvider;

protected function configure(TypeScriptTransformerConfigFactory $config): void
{
    $config->provider(new LaravelRouteActionTransformedProvider());
}
```

The next time you run the `typescript:transform` command, a TypeScript function called `action` will be generated in 
the `helpers/action.ts` file. 

You can now use the `action` function in your TypeScript code like this:

```ts
import { action } from './helpers/action';

// Resource controller
const userUrl = action(['/App/Http/Controllers/UserController', 'show'], { user: 1 });

// Invokable controller
const refreshUrl = action('/App/Http/Controllers/RefreshUserController');
```




### Wayfinder support

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using
the issue tracker.

## Credits

- [Ruben Van Assche](https://github.com/rubenvanassche)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
