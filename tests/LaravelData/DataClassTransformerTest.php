<?php

use Spatie\LaravelTypeScriptTransformer\LaravelData\Transformers\DataClassTransformer;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\CustomLazy;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\DataWithAttributes;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\DataWithClassMapper;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\Data\WritingContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;

function renderDataClass(
    string $class,
    array $customLazyTypes = [],
    bool $nullableAsOptional = false,
): string {
    $transformer = new DataClassTransformer(
        customLazyTypes: $customLazyTypes,
        nullableAsOptional: $nullableAsOptional,
    );
    $classNode = PhpClassNode::fromClassString($class);
    $context = TransformationContext::createFromPhpClass($classNode);

    return $transformer->transform($classNode, $context)
        ->getNode()
        ->write(new WritingContext([]));
}

it('transforms a data class with attributes', function () {
    $output = renderDataClass(DataWithAttributes::class);

    expect($output)->toMatchSnapshot();
});

it('supports custom lazy types', function () {
    $output = renderDataClass(DataWithAttributes::class, [CustomLazy::class]);

    expect($output)->toMatchSnapshot();
});

it('transforms a data class with a class-level mapper', function () {
    $output = renderDataClass(DataWithClassMapper::class);

    expect($output)->toMatchSnapshot();
});

it('converts nullable properties to optional when configured', function () {
    $output = renderDataClass(DataWithAttributes::class, nullableAsOptional: true);

    expect($output)->toMatchSnapshot();
});
