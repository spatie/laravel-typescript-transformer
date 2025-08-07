<div align="left">
    <a href="https://spatie.be/open-source?utm_source=github&utm_medium=banner&utm_campaign=laravel-typescript-transformer">
      <picture>
        <source media="(prefers-color-scheme: dark)" srcset="https://spatie.be/packages/header/laravel-typescript-transformer/html/dark.webp?1744124203">
        <img alt="Logo for laravel-typescript-transformer" src="https://spatie.be/packages/header/laravel-typescript-transformer/html/light.webp?1744124203">
      </picture>
    </a>

<h1>Transform PHP types to TypeScript</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/laravel-typescript-transformer/run-tests?label=tests)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Styling](https://github.com/spatie/laravel-typescript-transformer/workflows/Check%20&%20fix%20styling/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3A%22Check+%26+fix+styling%22)
[![Psalm](https://github.com/spatie/laravel-typescript-transformer/workflows/Psalm/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3APsalm)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)
    
</div>

Always wanted type safety within PHP and TypeScript without duplicating a lot of code? Then you will like this package! Let's say you have an enum:

```php
class Languages extends Enum
{
    const TYPESCRIPT = 'typescript';
    const PHP = 'php';
}
```

Wouldn't it be cool if you could have an automatically generated TypeScript definition like this:

```ts
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

You can even take it a bit further and generate TypeScript from classes:

```php
/** @typescript */
class User
{
    public int $id;

    public string $name;

    public ?string $address;
}
```

This will be transformed to:

```ts
export type User = {
    id: number;
    name: string;
    address: string | null;
}
```

Want to know more? You can find the documentation [here](https://docs.spatie.be/typescript-transformer/v2/introduction/).

## GitHub Actions Integration

The `--check-only` flag for the `typescript:transform` artisan command verifies whether your TypeScript files are up to date, without making any changes to them. Use this in your GitHub Actions workflow to ensure that your TypeScript files are always in sync with your PHP code.

```yaml
name: Check TypeScript Files
on: [push, pull_request] # Use any trigger you prefer

jobs:
  check-typescript:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v2
      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2' # Specify your PHP version
      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader
      - name: Check TypeScript files
        run: php artisan typescript:transform --check-only
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security

If you've found a bug regarding security please mail [security@spatie.be](mailto:security@spatie.be) instead of using the issue tracker.

## Credits

- [Ruben Van Assche](https://github.com/rubenvanassche)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
