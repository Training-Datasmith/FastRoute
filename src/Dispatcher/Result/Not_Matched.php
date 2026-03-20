<?php

declare (strict_types=1);
namespace Fast_Route\Dispatcher\Result;

use ArrayAccess;
use Fast_Route\Dispatcher;
use OutOfBoundsException;
use RuntimeException;
/** @implements ArrayAccess<int, Dispatcher::NOT_FOUND> */
final class Not_Matched implements ArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0;
    }
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => Dispatcher::NOT_FOUND,
            default => throw new OutOfBoundsException(),
        };
    }
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new RuntimeException('Result cannot be changed');
    }
    public function offsetUnset(mixed $offset): void
    {
        throw new RuntimeException('Result cannot be changed');
    }
}