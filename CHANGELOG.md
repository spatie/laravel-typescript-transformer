# Changelog

All notable changes to `typescript-transformer` will be documented in this file

## 2.4.0 - 2024-02-16

- Laravel 11 support

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.3.2...2.4.0

## 2.3.2 - 2023-12-01

- Use Laravel typescript transformer by default (#34)
- Replace CarbonInterface with a string (#33)

## 2.3.1 - 2023-10-12

- Replace CarbonInterface with a string (#33)

## 2.3.0 - 2023-04-14

- Drop support for PHP 8.0
- Enable collecting of enums by default

## 2.2.0 - 2023-03-24

- Add a native enum transformer by default

## 2.1.7 - 2023-01-24

### What's Changed

- Refactor tests to pest by @AyoobMH in https://github.com/spatie/laravel-typescript-transformer/pull/18
- Add Laravel 10 support by @njoguamos in https://github.com/spatie/laravel-typescript-transformer/pull/20

### New Contributors

- @AyoobMH made their first contribution in https://github.com/spatie/laravel-typescript-transformer/pull/18
- @njoguamos made their first contribution in https://github.com/spatie/laravel-typescript-transformer/pull/20

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.1.6...2.1.7

## 2.1.6 - 2022-11-18

- composer bump for typescript-transformer
- add test suite for php 8.2

## 2.1.5 - 2022-08-22

- do not fail when spatie/enum is not installed

## 2.1.4 - 2022-08-22

- use package service provider to fix publishing config

## 2.1.3 - 2022-01-25

## What's Changed

- Add force option to command by @erikgaal in https://github.com/spatie/laravel-typescript-transformer/pull/14

## New Contributors

- @erikgaal made their first contribution in https://github.com/spatie/laravel-typescript-transformer/pull/14

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.1.2...2.1.3

## 2.1.2 - 2022-01-19

## What's Changed

- Laravel 9.x by @aidan-casey in https://github.com/spatie/laravel-typescript-transformer/pull/13

## New Contributors

- @aidan-casey made their first contribution in https://github.com/spatie/laravel-typescript-transformer/pull/13

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.1.1...2.1.2

## 2.1.1 - 2021-12-16

- add support for transforming to native TypeScript enums

## 2.1.0 - 2021-12-16

- add support for PHP 8.1
- drop support for Laravel 7
- fix issue with union types and Laravel collection transformer

## 2.0.0 - 2021-04-08

- The package is now PHP 8 only
- Added TypeReflectors to reflect method return types, method parameters & class properties within your transformers
- Added support for attributes
- Added support for manually adding TypeScript to a class or property
- Added formatters like Prettier which can format TypeScript code
- Added support for inlining types directly
- Updated the DtoTransformer to be a lot more flexible for your own projects
- Added support for PHP 8 union types

## 1.1.2 - 2021-01-15

- Add support for configuring the writers (#7)

## 1.1.1 - 2020-11-26

- Add support for PHP 8

## 1.1.0 - 2020-11-26

- Moved `SpatieEnumTransformer` to the `typescript-transformer` package

## 1.0.1 - 2020-09-09

- Add support for Laravel 8

## 1.0.0 - 2020-09-02

- Initial release
