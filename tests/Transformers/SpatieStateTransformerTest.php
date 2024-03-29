<?php

use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\ChildState;
use Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses\State;
use Spatie\LaravelTypeScriptTransformer\Transformers\SpatieStateTransformer;

beforeEach(function () {
    $this->transformer = new SpatieStateTransformer();
});

it('will only convert states', function () {
    expect($this->transformer->transform(
        new ReflectionClass(State::class),
        'State'
    ))->not->toBeNull();

    expect($this->transformer->transform(
        new ReflectionClass(ChildState::class),
        'State'
    ))->toBeNull();

    expect($this->transformer->transform(
        new ReflectionClass(DateTime::class),
        'State'
    ))->toBeNull();
});

it('can transform an state', function () {
    $type = $this->transformer->transform(
        new ReflectionClass(State::class),
        'FakeState'
    );

    expect($type->transformed)->toEqual("'child' | 'other_child'");
    expect($type->missingSymbols->isEmpty())->toBeTrue();
    expect($type->isInline)->toBeFalse();
});
