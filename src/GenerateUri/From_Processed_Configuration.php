<?php

declare (strict_types=1);
namespace Fast_Route\Generate_Uri;

use function array_key_exists;
use function array_keys;
use function assert;
use function count;
use Fast_Route\Generate_Uri;
use Fast_Route\Route_Parser;
use function is_string;
use function preg_match;
/**
 * @phpstan-import-type RoutesForUriGeneration from GenerateUri
 * @phpstan-import-type UriSubstitutions from GenerateUri
 * @phpstan-import-type ParsedRoute from RouteParser
 */
final class From_Processed_Configuration implements Generate_Uri
{
    /** @param RoutesForUriGeneration $processedConfiguration */
    public function __construct(private readonly array $processed_configuration)
    {
    }
    /** @inheritDoc */
    public function for_route(string $name, array $substitutions = []): Generated_Uri
    {
        if (!array_key_exists($name, $this->processed_configuration)) {
            throw Uri_Could_Not_Be_Generated::route_is_undefined($name);
        }
        $missing_parameters = [];
        foreach ($this->processed_configuration[$name] as $parsed_route) {
            $missing_parameters = $this->missing_parameters($parsed_route, $substitutions);
            // Only attempt to generate the path if we have the necessary info
            if (count($missing_parameters) === 0) {
                return $this->generate_path($name, $parsed_route, $substitutions);
            }
        }
        assert(count($missing_parameters) > 0);
        throw Uri_Could_Not_Be_Generated::insufficient_parameters($name, $missing_parameters, array_keys($substitutions));
    }
    /**
     * Returns the expected parameters that were not passed as substitutions
     *
     * @param ParsedRoute      $parts
     * @param UriSubstitutions $substitutions
     *
     * @return list<string>
     */
    private function missing_parameters(array $parts, array $substitutions): array
    {
        $missing_parameters = [];
        foreach ($parts as $part) {
            if (is_string($part)) {
                continue;
            }
            if (array_key_exists($part[0], $substitutions)) {
                continue;
            }
            $missing_parameters[] = $part[0];
        }
        return $missing_parameters;
    }
    /**
     * @param ParsedRoute      $parsedRoute
     * @param UriSubstitutions $substitutions
     */
    private function generate_path(string $route, array $parsed_route, array $substitutions): Generated_Uri
    {
        $path = '';
        foreach ($parsed_route as $part) {
            if (is_string($part)) {
                $path .= $part;
                continue;
            }
            [$parameter_name, $regex] = $part;
            if (preg_match('~^' . $regex . '$~u', $substitutions[$parameter_name]) !== 1) {
                throw Uri_Could_Not_Be_Generated::parameter_does_not_match_the_pattern($route, $parameter_name, $regex);
            }
            $path .= $substitutions[$parameter_name];
            unset($substitutions[$parameter_name]);
        }
        assert($path !== '');
        return new Generated_Uri($path, $substitutions);
    }
}