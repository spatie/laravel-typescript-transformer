# Transform PHP to TypeScript

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-typescript-transformer/run-tests?label=tests)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Styling](https://github.com/spatie/laravel-typescript-transformer/workflows/Check%20&%20fix%20styling/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3A%22Check+%26+fix+styling%22)
[![Psalm](https://github.com/spatie/laravel-typescript-transformer/workflows/Psalm/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3APsalm)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)

This package allows you to convert PHP classes and more to TypeScript.

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

In order to configure TypeScript Transformer, we recommend you to now continue reading the documentation on the
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

Laravel provides a great way to define routes and then generate URLs to those routes in PHP using the `route()` helper.
While this all works in PHP, it can be a bit of a pain to do the same in TypeScript. TypeScript transformer can help you
here by providing exact copy of the `route()` helper in TypeScript.

To add the helper, add the following provider to your `TypeScriptTransformerServiceProvider`:

```php
use Spatie\LaravelTypeScriptTransformer\TransformedProviders\LaravelRouteTransformedProvider;

protected function configure(TypeScriptTransformerConfigFactory $config): void
{
    $config->provider(new LaravelRouteTransformedProvider());
}
```

The next time you run the `typescript:transform` command, a TypeScript function called `route` will be generated in
the `helpers/route.ts` file.

You can now use the `route` function in your TypeScript code like this:

```ts
import {route} from './helpers/route';

// Without parameters
const indexUrl = route('users.index');
// https://laravel.dev/users

// With parameters
const userUrl = route('users.show', {user: 1});
// https://laravel.dev/users/1
```

TypeScript will be smart enough to provide you autocompletion on these controllers and their parameters.

Sometimes you might want to exclude certain routes from being included in the generated TypeScript. You can do this by
adding a route filter. The package provides three types of route filters:

**NamedRouteFilter**

Allows you to remove routes by their name. It is possible to use wildcards.

```php
use Spatie\LaravelTypeScriptTransformer\RouteFilters\NamedRouteFilter;

$config->provider(new LaravelRouteTransformedProvider(
    routeFilters: [
        new NamedRouteFilter('debugbar.*', 'hidden'),
    ],
));
```

**ControllerRouteFilter**

Allows you to remove routes by their controller class or namespace using wildcards.

```php
use Spatie\LaravelTypeScriptTransformer\RouteFilters\ControllerRouteFilter;

$config->provider(new LaravelRouteTransformedProvider(
    routeFilters: [
        new ControllerRouteFilter(['App\Http\Controllers\Admin\*', 'HiddenController']),
    ],
));
```

**ClosureRouteFilter**

Allows you to provide a closure that will be called for each route. If the closure returns `true`, the route will be
excluded.

```php
use Spatie\LaravelTypeScriptTransformer\RouteFilters\ClosureRouteFilter;

$config->provider(new LaravelRouteTransformedProvider(
    routeFilters: [
        new ClosureRouteFilter(function (Route $route) {
            return str_starts_with($route->uri(), 'internal/');
        }),
    ],
));
```

By default, the helper will generate absolute URLs meaning it includes the app URL. This URL will be fetched from the
window object in JavaScript. If you want to generate relative URLs instead, you can pass `false` as the third parameter,
indicating you don't want absolute URLs:

```ts
const indexUrl = route('users.index', {}, false);
// /users
```

The default value of the absolute parameter can be changed by setting a default for the provider:

```php
$config->provider(new LaravelRouteTransformedProvider(
    absoluteUrlsByDefault: false,
));
```

Now when using the `route` helper in TypeScript, URLs will be relative by default:

```ts
const indexUrl = route('users.index');
// /users
```

TypeScript transformer will automatically generate the `helpers/route.ts` file in the output directory you configured
for TypeScript transformer. It is possible to change the path of this file as such:

```php
$config->provider(new LaravelRouteTransformedProvider(
    path: 'route.ts',
));
```

When running in the watch mode of the package, the generated `route.ts` file will automatically be updated when you
change your routes in Laravel. By default the watcher will monitor the following directories for changes:

- `routes`
- `bootstrap`
- `app/Providers`

It is possible to customize the directories that are monitored as such:

```php
$config->provider(new LaravelRouteTransformedProvider(
    routeDirectories: [
        'custom/routes/directory',
        'another/directory/to/watch',
    ],
));

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