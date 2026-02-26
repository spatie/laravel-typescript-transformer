<?php

use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelTypeScriptTransformer\Actions\ResolveLaravelControllerAction;
use Spatie\LaravelTypeScriptTransformer\LaravelControllers\LaravelController;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\FakeData;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\TypedController;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptArray;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGeneric;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptNumber;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptObject;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptProperty;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptString;

function resolveTypedController(): LaravelController
{
    $classNode = PhpClassNode::fromClassString(TypedController::class);

    return (new ResolveLaravelControllerAction())->execute($classNode);
}

it('produces a LaravelController from a PhpClassNode', function () {
    $result = resolveTypedController();

    expect($result)->toBeInstanceOf(LaravelController::class);
    expect($result->fqcn)->toBe(TypedController::class);
    expect($result->filePath)->toContain('TypedController.php');
});

it('only includes public methods declared on the class', function () {
    $result = resolveTypedController();

    expect($result->methods)->toHaveKeys([
        'returnsPhpType',
        'returnsPhpStanType',
        'returnsVoid',
        'returnsUnknownType',
        'returnsNothing',
        'returnsDataObject',
        'returnsArrayShape',
        'returnsArrayOfArrayShapes',
        'returnsCollectionOfDataObjects',
        'returnsCollectionOfDataObjectsWithoutKey',
        'returnsCollectionOfArrayShapes',
        'returnsDataCollectionOfDataObjects',
        'returnsResponseWrappingDataObject',
        'returnsResponseWrappingDataCollection',
        'acceptsDataObject',
        'acceptsDataObjectWithOtherParams',
        'acceptsNoDataObject',
    ]);
    expect($result->methods)->not->toHaveKey('protectedMethod');
    expect($result->methods)->not->toHaveKey('privateMethod');
});

it('resolves response types', function (string $method, mixed $expected) {
    $response = resolveTypedController()->methods[$method]['response'];

    expect($response)->toEqual($expected);
})->with([
    'native PHP type' => ['returnsPhpType', new TypeScriptString()],
    'PHPStan docblock type' => ['returnsPhpStanType', new TypeScriptGeneric(
        new TypeScriptIdentifier('Record'),
        [new TypeScriptString(), new TypeScriptNumber()],
    )],
    'data object' => ['returnsDataObject', TypeScriptReference::referencingPhpClass(FakeData::class)],
    'array shape' => ['returnsArrayShape', new TypeScriptObject([
        new TypeScriptProperty('name', new TypeScriptString()),
        new TypeScriptProperty('age', new TypeScriptNumber()),
    ])],
    'array of array shapes' => ['returnsArrayOfArrayShapes', new TypeScriptArray([new TypeScriptObject([
        new TypeScriptProperty('name', new TypeScriptString()),
        new TypeScriptProperty('age', new TypeScriptNumber()),
    ])])],
    'collection of data objects' => ['returnsCollectionOfDataObjects', new TypeScriptGeneric(
        TypeScriptReference::referencingPhpClass(Collection::class),
        [new TypeScriptNumber(), TypeScriptReference::referencingPhpClass(FakeData::class)],
    )],
    'collection of data objects without key' => ['returnsCollectionOfDataObjectsWithoutKey', new TypeScriptGeneric(
        TypeScriptReference::referencingPhpClass(Collection::class),
        [TypeScriptReference::referencingPhpClass(FakeData::class)],
    )],
    'collection of array shapes' => ['returnsCollectionOfArrayShapes', new TypeScriptGeneric(
        TypeScriptReference::referencingPhpClass(Collection::class),
        [new TypeScriptObject([
            new TypeScriptProperty('name', new TypeScriptString()),
            new TypeScriptProperty('age', new TypeScriptNumber()),
        ])],
    )],
    'data collection of data objects' => ['returnsDataCollectionOfDataObjects', new TypeScriptGeneric(
        TypeScriptReference::referencingPhpClass(DataCollection::class),
        [new TypeScriptNumber(), TypeScriptReference::referencingPhpClass(FakeData::class)],
    )],
    'response wrapping data object unwraps' => [
        'returnsResponseWrappingDataObject',
        TypeScriptReference::referencingPhpClass(FakeData::class),
    ],
    'response wrapping data collection unwraps' => ['returnsResponseWrappingDataCollection', new TypeScriptGeneric(
        TypeScriptReference::referencingPhpClass(DataCollection::class),
        [new TypeScriptNumber(), TypeScriptReference::referencingPhpClass(FakeData::class)],
    )],
    'void returns null' => ['returnsVoid', null],
    'unknown type returns null' => ['returnsUnknownType', null],
    'missing type returns null' => ['returnsNothing', null],
]);

it('resolves request types', function (string $method, mixed $expected) {
    $request = resolveTypedController()->methods[$method]['request'];

    expect($request)->toEqual($expected);
})->with([
    'data object parameter' => ['acceptsDataObject', TypeScriptReference::referencingPhpClass(FakeData::class)],
    'data object among other parameters' => ['acceptsDataObjectWithOtherParams', TypeScriptReference::referencingPhpClass(FakeData::class)],
    'no data object parameter' => ['acceptsNoDataObject', null],
    'no parameters' => ['returnsPhpType', null],
]);
