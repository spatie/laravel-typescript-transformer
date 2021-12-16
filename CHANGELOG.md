# Changelog

All notable changes to `typescript-transformer` will be documented in this file

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
