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
     * @param ParsedRoute     $routeData
     * @param ExtraParameters $extraParameters
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
     * Tests whether this route matches the given string.
     */
    public function matches(string $str): bool
    {
        $regex = '~^' . $this->regex . '$~';
        return (bool) preg_match($regex, $str);
    }
}