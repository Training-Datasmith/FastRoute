<?php

declare (strict_types=1);
namespace Fast_Route;

use function is_string;
use function preg_match;
use function preg_quote;
/**
 * @internal
 *
 * @phpstan-import-type ExtraParameters from DataGenerator
 * @phpstan-import-type ParsedRoute from RouteParser
 */
class Route
{
    public readonly string $regex;
    /** @var array<string, string> $variables */
    public readonly array $variables;
    /**
     * Constructs an immutable Route value object from a parsed route definition.
     *
     * @param string          $http_method       The HTTP verb (e.g. 'GET', 'POST', '*' for any).
     * @param ParsedRoute     $route_data        The token array produced by {@see Route_Parser::parse()}.
     * @param mixed           $handler           The handler associated with this route (callable, controller string, etc.).
     * @param ExtraParameters $extra_parameters  Named extra data stored alongside the route (e.g. route name, regex).
     */
    public function __construct(public readonly string $http_method, array $route_data, public readonly mixed $handler, public readonly array $extra_parameters)
    {
        [$this->regex, $this->variables] = self::extract_regex($route_data);
    }
    /**
     * @param ParsedRoute $routeData
     *
     * @return array{string, array<string, string>}
     */
    private static function extract_regex(array $route_data): array
    {
        $regex = '';
        $variables = [];
        foreach ($route_data as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }
            [$var_name, $regex_part] = $part;
            $variables[$var_name] = $var_name;
            $regex .= '(' . $regex_part . ')';
        }
        return [$regex, $variables];
    }
    /**
     * Tests whether this route's compiled regex matches the given URI string.
     *
     * @param string $str The URI path segment to match against (without query string).
     *
     * @return bool `true` if the full URI matches the route pattern.
     *
     * @complexity O(n) where n = length of $str (single preg_match call)
     */
    public function matches(string $str): bool
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}