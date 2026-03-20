<?php

declare (strict_types=1);
namespace Fast_Route\Data_Generator;

use function count;
use function implode;
use function max;
use function str_repeat;
/** @final */
class Group_Count_Based extends Regex_Based_Abstract
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
        $num_groups = 0;
        foreach ($regex_to_routes_map as $regex => $route) {
            $num_variables = count($route->variables);
            $num_groups = max($num_groups, $num_variables);
            $regexes[] = $regex . str_repeat('()', $num_groups - $num_variables);
            $route_map[$num_groups + 1] = [$route->handler, $route->variables, $route->extra_parameters];
            ++$num_groups;
        }
        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'routeMap' => $route_map];
    }
}