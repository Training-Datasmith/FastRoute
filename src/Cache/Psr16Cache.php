<?php

declare (strict_types=1);
namespace Fast_Route\Cache;

use Fast_Route\Cache;
use function is_array;
use Psr\Simple_Cache\Cache_Interface;
final class Psr16Cache implements Cache
{
    public function __construct(private readonly Cache_Interface $cache)
    {
    }
    /** @inheritDoc */
    public function get(string $key, callable $loader): array
    {
        $result = $this->cache->get($key);
        if (is_array($result)) {
            // @phpstan-ignore-next-line because we won´t be able to validate the array shape in a performant way
            return $result;
        }
        $data = $loader();
        $this->cache->set($key, $data);
        return $data;
    }
}