<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelData;

use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\LaravelData\CursorPaginatedDataCollection;
use Spatie\LaravelData\PaginatedDataCollection;
use Spatie\TypeScriptTransformer\References\ClassStringReference;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\TransformedProviders\TransformedProvider;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptAlias;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptGeneric;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptIdentifier;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptReference;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class LaravelDataTransformedProvider implements TransformedProvider
{
    public function provide(TypeScriptTransformerConfig $config): array
    {
        return [
            $this->paginatedCollection(),
            $this->cursorPaginatedCollection(),
        ];
    }

    protected function paginatedCollection(): Transformed
    {
        return new Transformed(
            new TypeScriptAlias(
                new TypeScriptGeneric(
                    new TypeScriptIdentifier('PaginatedDataCollection'),
                    [new TypeScriptIdentifier('TKey'), new TypeScriptIdentifier('TValue')],
                ),
                new TypeScriptGeneric(
                    new TypeScriptReference(new ClassStringReference(LengthAwarePaginator::class)),
                    [new TypeScriptIdentifier('TKey'), new TypeScriptIdentifier('TValue')],
                ),
            ),
            new ClassStringReference(PaginatedDataCollection::class),
            ['Spatie', 'LaravelData'],
            true,
        );
    }

    protected function cursorPaginatedCollection(): Transformed
    {
        return new Transformed(
            new TypeScriptAlias(
                new TypeScriptGeneric(
                    new TypeScriptIdentifier('CursorPaginatedDataCollection'),
                    [new TypeScriptIdentifier('TKey'), new TypeScriptIdentifier('TValue')],
                ),
                new TypeScriptGeneric(
                    new TypeScriptReference(new ClassStringReference(CursorPaginator::class)),
                    [new TypeScriptIdentifier('TKey'), new TypeScriptIdentifier('TValue')],
                ),
            ),
            new ClassStringReference(CursorPaginatedDataCollection::class),
            ['Spatie', 'LaravelData'],
            true,
        );
    }
}
