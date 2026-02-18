<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses;

class TypedController
{
    public function index(): string
    {
        return 'index';
    }

    /** @return array<string, int> */
    public function show(): array
    {
        return [];
    }

    public function store(): void
    {
    }

    protected function protectedMethod(): string
    {
        return 'protected';
    }

    private function privateMethod(): string
    {
        return 'private';
    }
}
