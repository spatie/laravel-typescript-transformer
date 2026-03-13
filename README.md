# Transform PHP to TypeScript

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/laravel-typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/laravel-typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/spatie/laravel-typescript-transformer/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Styling](https://github.com/spatie/laravel-typescript-transformer/workflows/Check%20&%20fix%20styling/badge.svg)](https://github.com/spatie/laravel-typescript-transformer/actions?query=workflow%3A%22Check+%26+fix+styling%22)
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

You can find the full documentation [here](https://spatie.be/docs/typescript-transformer/v3/introduction).

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
