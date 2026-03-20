<?php

declare (strict_types=1);
namespace Fast_Route\Dispatcher\Result;

use ArrayAccess;
use Fast_Route\Dispatcher;
use OutOfBoundsException;
use RuntimeException;
/** @implements ArrayAccess<int, Dispatcher::METHOD_NOT_ALLOWED|non-empty-list<string>> */
final class Method_Not_Allowed implements ArrayAccess
{
    /**
     * @readonly
     * @var non-empty-list<string> $allowedMethods
     */
    public array $allowed_methods;
    public function offsetExists(mixed $offset): bool
    {
        return $offset === 0 || $offset === 1;
    }
    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            0 => Dispatcher::METHOD_NOT_ALLOWED,
            1 => $this->allowed_methods,
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