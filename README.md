# Transform your PHP structures to Typescript types

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spatie/typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/typescript-transformer)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/spatie/typescript-transformer/run-tests?label=tests)](https://github.com/spatie/typescript-transformer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/typescript-transformer.svg?style=flat-square)](https://packagist.org/packages/spatie/typescript-transformer)

**This package is still under heavy development, please do not use it (yet)**

Always wanted type safety within PHP and Typescript without duplicating a lot of code? Then you will like this package! Let's say you have an enum:

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

You can even take it a bit further and generate Typescript from classes:

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

```typescript
export type User = {
    int: number;
    name: string;
    address: string | null;
}
```

Want to know more? You can find the documentation [here](https://docs.spatie.be/typescript-transformer/v1/introduction/).

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
