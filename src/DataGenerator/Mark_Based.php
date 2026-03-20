<?php

declare (strict_types=1);
namespace Fast_Route\Data_Generator;

use function implode;
/** @final */
class Mark_Based extends Regex_Based_Abstract
{
    protected function get_approx_chunk_size(): int
    {
        return 30;
    }
    /** @inheritDoc */
    protected function process_chunk(array $regex_to_routes_map): array
    {
        $route_map = [];
        $regexes = [];
        $mark_name = 'a';
        foreach ($regex_to_routes_map as $regex => $route) {
            $regexes[] = $regex . '(*MARK:' . $mark_name . ')';
            $route_map[$mark_name] = [$route->handler, $route->variables, $route->extra_parameters];
            ++$mark_name;
        }
        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $route_map];
    }
}