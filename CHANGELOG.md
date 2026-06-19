# Changelog

All notable changes to `typescript-transformer` will be documented in this file

## 3.3.0 - 2026-06-19

A round of fixes for the route generator and the `typescript:install` command.

### Fix provider config file detection in install command (#84)

`typescript:install` failed for apps created before Laravel 11 that later upgraded, because those apps have no `bootstrap/providers.php` file and the command assumed it was always present. The command now detects the correct provider registration target instead of relying on that file existing.

Thanks @TheoGibbons.

### Fix silent failure registering the service provider in `typescript:install` (#89)

`typescript:install` injected the service provider into `bootstrap/providers.php` with a `str_replace` anchored on a literal fully qualified class string, so it silently did nothing (while still reporting success) whenever that file used short, imported class names (#86). It now uses Laravel's `ServiceProvider::addProviderToBootstrapFile()`, which evaluates the file rather than matching raw text and therefore handles every layout, and it reports an honest error when registration is not possible.

The misleading `typescript:transform` message now points users to `typescript:install`, and the unreachable namespace rewriting was dropped to keep the command simple.

Thanks @rubenvanassche.

### Keep routes that share a controller in the route generator (#90)

Routes were indexed by controller identity (invokable class, or `class@method`), so any two routes pointing at the same endpoint overwrote each other and only the last one survived in the generated TypeScript (#87). This silently dropped `Route::inertia()` pages and the per-locale routes registered by localized routing packages. `RouteController::$actions` is now a flat list of route bindings instead of a map keyed by method, so the resolver appends every route and the helper emits all named routes.

Thanks @rubenvanassche.

### Throw on missing route in generated `route()`, add `hasRoute` predicate (#85)

Calling the generated `route()` with an unknown name returned the literal string `/undefined` (an accidental result of `'/' + undefined`), which silently produced broken URLs in `href` attributes and elsewhere. `route()` now throws when the name is not in the manifest, matching Laravel's server-side `RouteNotFoundException` and surfacing the bug at the call site (spatie/typescript-transformer#151).

A new exported `hasRoute(name): name is keyof RouteParameters` predicate is emitted alongside `route()`, mirroring Laravel's `Route::has()`. It is the safe way to guard the throw for callers that work with dynamic names, such as locale routing wrappers or runtime-composed strings.

```ts
// before: could silently produce /undefined
const url = route(maybeName);

// after
if (hasRoute(maybeName)) {
    const url = route(maybeName);
}

```
For well-typed TypeScript this is impossible to hit, since the parameter type already constrains the name. Dynamic-name callers should guard with `hasRoute()` first.

Thanks @rubenvanassche.

### What's Changed

* Fix provider config file detection in install command by @TheoGibbons in https://github.com/spatie/laravel-typescript-transformer/pull/84
* Throw on missing route in generated `route()`, add `hasRoute` predicate by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/85
* Fix silent failure registering the service provider in typescript:install by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/89
* Keep routes that share a controller in the route generator by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/90

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/3.2.0...3.3.0

## 3.2.0 - 2026-05-08

### Narrow the controller `method` type and prioritize HTTP methods (#83)

Fixes #76. The generated `RouteDefinition` and `MethodRoute` types previously declared `method: string`, which forced casts when handing the result to libraries like Inertia that expect `'get' | 'post' | 'put' | 'patch' | 'delete'`. The output now uses a literal union, so:

```ts
router.visit(SomeController.update(), { data: { ... } });


```
just works. On top of that, `LaravelControllerTransformedProvider` accepts a new `httpMethodsPriority` argument that doubles as both filter and sort order. Methods absent from the list are dropped from the output, and the ones that survive are emitted in list order. The default is `['get', 'post', 'put', 'patch', 'delete']`, so HEAD and OPTIONS (auto-registered by Laravel for every GET route, never invoked by SPAs) are no longer emitted.

If you actually relied on the HEAD entries, pass a custom list including `'head'`. Thanks @rubenvanassche.

## 3.1.0 - 2026-05-08

A round of bug fixes for route generation, laravel-data integration, and the writer.

### Honor laravel-data `name_mapping_strategy.output` config (#81)

If your `config/data.php` looked like this:

```php
'name_mapping_strategy' => [
    'output' => SnakeCaseMapper::class,
],



```
The transformer was silently ignoring it and emitting camelCase keys. The processor now resolves property output names through `DataConfig::getDataClass()`, so the global mapper, class level, and property level `MapName` / `MapOutputName` attributes all flow through one source. Thanks @rubenvanassche.

### Fix `route()` helper producing `//` for the root route (#82)

`Route::get('/')` produced `route('home') === '//'` because Laravel's `$route->uri` returns `/` for the root and bare paths (without a leading slash) for everything else. The runtime helper then prepended another `/`. After this fix:

```ts
route('home');         // '/'  (was '//')
route('help.index');   // '/help' (unchanged)



```
Thanks @rubenvanassche.

### Fix null byte crash in the route watcher when using filters (#80)

Watching routes with any `RouteFilter` configured crashed with `Command array element 4 contains a null byte`. PHP's `serialize()` emits `\0*\0` markers for protected properties, and Symfony Process rejects command arguments that contain null bytes. Every shipped filter (`NamedRouteFilter`, `ControllerRouteFilter`, `ClosureRouteFilter`) hit this. The serialized payload is now base64 encoded across the process boundary. Thanks @rubenvanassche.

### Support custom data collections in controller types (#73)

Controller type generation now uses `is_a()` instead of `in_array()` when checking for data collection classes, so subclasses of `DataCollection` are recognised. `buildActionCallExpression` was also renamed to `buildActionCallNode` to make it overridable. Thanks @iamrgroot.

### Fix `GlobalNamespaceWriter` producing a broken path when given an absolute path (#79)

Passing an absolute path resulted in the output directory being concatenated in front of it:

```
resources/js/generated/home/user/project/resources/types/generated.d.ts



```
Pass a relative filename instead, and the writer will resolve it correctly against the configured output directory. Thanks @A909M.

### What's Changed

* Fix null-byte crash in route watcher when using filters by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/80
* Honor laravel-data `name_mapping_strategy.output` config by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/81
* Fix `route()` helper producing `//` for the root route by @rubenvanassche in https://github.com/spatie/laravel-typescript-transformer/pull/82
* Support custom data collections in controller types by @iamrgroot in https://github.com/spatie/laravel-typescript-transformer/pull/73
* Fix `GlobalNamespaceWriter` producing a broken path when given an absolute path by @A909M in https://github.com/spatie/laravel-typescript-transformer/pull/79

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/3.0.3...3.0.4

## 3.0.3 - 2026-03-17

### What's Changed

* Fix method name in TypeScriptTransformerServiceProvider configuration by @dominosaurs in https://github.com/spatie/laravel-typescript-transformer/pull/71
* fix: laravel data MapName attribute output by @bensherred in https://github.com/spatie/laravel-typescript-transformer/pull/70

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/3.0.2...3.0.3

## 3.0.2 - 2026-03-16

### What's fixed

- Fixed incorrect namespace and non-existent class in `typescript:install` command stub (#69)
  - `Spatie\TypeScriptTransformer\Laravel\TypeScriptTransformerApplicationServiceProvider` → `Spatie\LaravelTypeScriptTransformer\TypeScriptTransformerApplicationServiceProvider`
  - `NamespaceWriter` → `GlobalNamespaceWriter`
  

## 3.0.1 - 2026-03-16

### What's Changed

- Fix generic arity mismatch in paginator interface type aliases
- Inject Runner via handle() method for testability
- Add tests for TransformTypeScriptCommand
- Update publishable service provider stub path

## 3.0.0 - 2026-03-13

This is a major release built on top of the completely rewritten [spatie/typescript-transformer v3](https://github.com/spatie/typescript-transformer/releases/tag/3.0.0).

### What's New

- Everything new in [spatie/typescript-transformer v3](https://github.com/spatie/typescript-transformer/releases/tag/3.0.0)
- **Service provider configuration** - Configure the package in a service provider instead of a config file
- **Controller type generation** - Automatically generate TypeScript types for your Laravel controller actions, including request parameters and response types
- **Route type generation** - Generate a typed route helper with full autocompletion for route names and parameters
- **Watch mode** - File watcher that automatically regenerates TypeScript types as you develop

### Breaking Changes

- Requires PHP 8.2+ and Laravel 10+
- Configuration moved from config file to service provider
- Depends on `spatie/typescript-transformer ^3.0`

Since it is a complete rewrite, we recommend reading through the new docs and updating your application accodingly.

## 2.6.0 - 2026-02-25

- Added support for Laravel 13
- Dropped support for Laravel 8 and 9

## 2.5.2 - 2025-04-25

### What's Changed

* fix: don't create a compound type when the type is already a TypeScript type by @Bloemendaal in https://github.com/spatie/laravel-typescript-transformer/pull/53

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.5.1...2.5.2

## 2.5.1 - 2025-02-14

Allow Laravel 12

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.5.0...2.5.1

## 2.5.0 - 2024-10-04

### What's Changed

* Use service container to resolve TypescriptTransformer by @rasmuscnielsen in https://github.com/spatie/laravel-typescript-transformer/pull/47
* feat: support `nullToOptional` by @innocenzi in https://github.com/spatie/laravel-typescript-transformer/pull/46

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.4.1...2.5.0

## 2.4.1 - 2024-05-03

### What's Changed

* Let artisan handle the exceptions by @Tofandel in https://github.com/spatie/laravel-typescript-transformer/pull/41
* 

**Full Changelog**: https://github.com/spatie/laravel-typescript-transformer/compare/2.4.0...2.4.1

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
