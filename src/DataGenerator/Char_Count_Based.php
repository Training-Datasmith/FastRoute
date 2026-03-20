<?php

declare (strict_types=1);
namespace Fast_Route\Data_Generator;

use function count;
use function implode;
/** @final */
class Char_Count_Based extends Regex_Based_Abstract
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
        $suffix_len = 0;
        $suffix = '';
        $count = count($regex_to_routes_map);
        foreach ($regex_to_routes_map as $regex => $route) {
            $suffix_len++;
            $suffix .= "\t";
            $regexes[] = '(?:' . $regex . '/(\t{' . $suffix_len . '})\t{' . ($count - $suffix_len) . '})';
            $route_map[$suffix] = [$route->handler, $route->variables, $route->extra_parameters];
        }
        $regex = '~^(?|' . implode('|', $regexes) . ')$~';
        return ['regex' => $regex, 'suffix' => '/' . $suffix, 'routeMap' => $route_map];
    }
}