<?php

declare (strict_types=1);
namespace Fast_Route\Generate_Uri;

use function count;
use Fast_Route\Exception;
use function implode;
use LogicException;
use function sprintf;
final class Uri_Could_Not_Be_Generated extends LogicException implements Exception
{
    public static function route_is_undefined(string $name): self
    {
        return new self('There is no route with name "' . $name . '" defined');
    }
    public static function parameter_does_not_match_the_pattern(string $route, string $parameter, string $expected_pattern): self
    {
        return new self(sprintf('Route "%s" expects the parameter [%s] to match the regex `%s`', $route, $parameter, $expected_pattern));
    }
    /**
     * @param non-empty-list<string> $missingParameters
     * @param list<string>           $givenParameters
     */
    public static function insufficient_parameters(string $route, array $missing_parameters, array $given_parameters): self
    {
        return new self(sprintf('Route "%s" expects at least parameter values for [%s], but received %s', $route, implode(',', $missing_parameters), count($given_parameters) === 0 ? 'none' : '[' . implode(',', $given_parameters) . ']'));
    }
}