<?php

declare (strict_types=1);
namespace Fast_Route\Route_Parser;

use function assert;
use function count;
use Fast_Route\Bad_Route_Exception;
use Fast_Route\Route_Parser;
use function in_array;
use function is_array;
use function preg_match;
use function preg_match_all;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;
use function preg_split;
use function rtrim;
use function str_contains;
use function strlen;
use function substr;
use function trim;
/**
 * Parses route strings of the following form:
 *
 * "/user/{name}[/{id:[0-9]+}]"
 *
 * @phpstan-import-type ParsedRoute from RouteParser
 * @final
 */
class Std implements Route_Parser
{
    public const VARIABLE_REGEX = <<<'REGEX'
    \{
        \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
        (?:
            : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
        )?
    \}
    REGEX;
    public const DEFAULT_DISPATCH_REGEX = '[^/]+';
    private const CAPTURING_GROUPS_REGEX = '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x';
    /** @inheritDoc */
    public function parse(string $route): array
    {
        $route_without_closing_optionals = rtrim($route, ']');
        $num_optionals = strlen($route) - strlen($route_without_closing_optionals);
        // Split on [ while skipping placeholders
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $route_without_closing_optionals);
        assert(is_array($segments));
        if ($num_optionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $route_without_closing_optionals) === 1) {
                throw new Bad_Route_Exception('Optional segments can only occur at the end of a route');
            }
            throw new Bad_Route_Exception("Number of opening '[' and closing ']' does not match");
        }
        $current_route = '';
        $parsed_routes = [];
        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new Bad_Route_Exception('Empty optional part');
            }
            $current_route .= $segment;
            $parsed_routes[] = $this->parse_placeholders($current_route);
        }
        return $parsed_routes;
    }
    /**
     * Parses a route string that does not contain optional segments.
     *
     * @return ParsedRoute
     */
    private function parse_placeholders(string $route): array
    {
        if ((int) preg_match_all('~' . self::VARIABLE_REGEX . '~x', $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER) === 0) {
            return [$route];
        }
        $offset = 0;
        $route_data = [];
        $parsed_variable_names = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $route_data[] = substr($route, $offset, $set[0][1] - $offset);
            }
            if (in_array($set[1][0], $parsed_variable_names, true)) {
                throw Bad_Route_Exception::placeholder_already_defined($set[1][0]);
            }
            if (isset($set[2])) {
                $this->guard_against_capturing_group_usage(trim($set[2][0]), $set[1][0]);
            }
            $parsed_variable_names[] = $set[1][0];
            $route_data[] = [$set[1][0], isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX];
            $offset = $set[0][1] + strlen($set[0][0]);
        }
        if ($offset !== strlen($route)) {
            $route_data[] = substr($route, $offset);
        }
        return $route_data;
    }
    private function guard_against_capturing_group_usage(string $regex, string $variable_name): void
    {
        // Needs to have at least a ( to contain a capturing group
        if (!str_contains($regex, '(')) {
            return;
        }
        // Semi-accurate detection for capturing groups
        if (preg_match(self::CAPTURING_GROUPS_REGEX, $regex) !== 1) {
            return;
        }
        throw Bad_Route_Exception::variable_with_capture_group($regex, $variable_name);
    }
}