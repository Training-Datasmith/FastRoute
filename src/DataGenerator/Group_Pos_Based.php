<?php

declare (strict_types=1);
namespace Fast_Route\Data_Generator;

use function count;
use function implode;
/** @final */
class Group_Pos_Based extends Regex_Based_Abstract
{
    protected function get_approx_chunk_size(): int
    {
        return 10;
    }
    /** @inheritDoc */
    protected function process_chunk(array $regex_to_routes_map): array
    {
        $route_map = [];
        $regexes = [];
        $offset = 1;
        foreach ($regex_to_routes_map as $regex => $route) {
            $regexes[] = $regex;
            $route_map[$offset] = [$route->handler, $route->variables, $route->extra_parameters];
            $offset += count($route->variables);
        }
        $regex = '~^(?:' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $route_map];
    }
}