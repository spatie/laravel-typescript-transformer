<?php

namespace Spatie\LaravelTypeScriptTransformer\Tests\FakeClasses;

use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class TypedController
{
    public function returnsPhpType(): string
    {
        return 'index';
    }

    /** @return array<string, int> */
    public function returnsPhpStanType(): array
    {
        return [];
    }

    public function returnsVoid(): void
    {
    }

    public function returnsUnknownType(): Response
    {
        return response();
    }

    public function returnsNothing()
    {
    }

    public function returnsDataObject(): FakeData
    {
        return new FakeData('test', 1);
    }

    /** @return array{name: string, age: int} */
    public function returnsArrayShape(): array
    {
        return ['name' => 'test', 'age' => 1];
    }

    /** @return array<array{name: string, age: int}> */
    public function returnsArrayOfArrayShapes(): array
    {
        return [];
    }

    /** @return Collection<int, FakeData> */
    public function returnsCollectionOfDataObjects(): Collection
    {
        return collect();
    }

    /** @return Collection<FakeData> */
    public function returnsCollectionOfDataObjectsWithoutKey(): Collection
    {
        return collect();
    }

    /** @return Collection<array{name: string, age: int}> */
    public function returnsCollectionOfArrayShapes(): Collection
    {
        return collect();
    }

    /** @return DataCollection<int, FakeData> */
    public function returnsDataCollectionOfDataObjects(): DataCollection
    {
        return new DataCollection(FakeData::class, []);
    }

    /** @return Response<FakeData> */
    public function returnsResponseWrappingDataObject(): Response
    {
        return response();
    }

    /** @return Response<DataCollection<int, FakeData>> */
    public function returnsResponseWrappingDataCollection(): Response
    {
        return response();
    }

    public function acceptsDataObject(FakeData $data): void
    {
    }

    public function acceptsDataObjectWithOtherParams(string $id, FakeData $data, string $slug): void
    {
    }

    public function acceptsNoDataObject(string $id): void
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
