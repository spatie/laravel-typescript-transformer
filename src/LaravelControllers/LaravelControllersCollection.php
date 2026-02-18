<?php

namespace Spatie\LaravelTypeScriptTransformer\LaravelControllers;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<LaravelController>
 */
class LaravelControllersCollection implements IteratorAggregate, Countable
{
    /** @var array<string, LaravelController> */
    protected array $items = [];

    /** @var array<string, string> */
    protected array $fileMapping = [];

    public function add(LaravelController $controller): void
    {
        $this->remove($controller->fqcn);

        $this->items[$controller->fqcn] = $controller;
        $this->fileMapping[$this->cleanupFilePath($controller->filePath)] = $controller->fqcn;
    }

    public function get(string $fqcn): ?LaravelController
    {
        return $this->items[$fqcn] ?? null;
    }

    public function has(string $fqcn): bool
    {
        return array_key_exists($fqcn, $this->items);
    }

    public function findByFile(string $path): ?LaravelController
    {
        $path = $this->cleanupFilePath($path);
        $fqcn = $this->fileMapping[$path] ?? null;

        if ($fqcn === null) {
            return null;
        }

        return $this->items[$fqcn] ?? null;
    }

    public function remove(string $fqcn): void
    {
        $controller = $this->items[$fqcn] ?? null;

        if ($controller === null) {
            return;
        }

        $path = $this->cleanupFilePath($controller->filePath);

        unset($this->items[$fqcn], $this->fileMapping[$path]);
    }

    public function removeByFile(string $path): void
    {
        $path = $this->cleanupFilePath($path);
        $fqcn = $this->fileMapping[$path] ?? null;

        if ($fqcn === null) {
            return;
        }

        $this->remove($fqcn);
    }

    public function removeByDirectory(string $path): void
    {
        $path = $this->cleanupFilePath($path);

        foreach ($this->fileMapping as $filePath => $fqcn) {
            if (str_starts_with($filePath, $path)) {
                $this->remove($fqcn);
            }
        }
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    protected function cleanupFilePath(string $path): string
    {
        return realpath($path) ?: $path;
    }
}
