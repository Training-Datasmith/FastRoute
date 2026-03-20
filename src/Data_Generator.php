<?php

declare (strict_types=1);
namespace Fast_Route;

/**
 * @phpstan-import-type ParsedRoute from RouteParser
 * @phpstan-type ExtraParameters array<string, string|int|bool|float>
 * @phpstan-type StaticRoutes array<string, array<string, array{mixed, ExtraParameters}>>
 * @phpstan-type DynamicRouteChunk array{regex: string, suffix?: string, routeMap: array<int|string, array{mixed, array<string, string>, ExtraParameters}>}
 * @phpstan-type DynamicRouteChunks list<DynamicRouteChunk>
 * @phpstan-type DynamicRoutes array<string, DynamicRouteChunks>
 * @phpstan-type RouteData array{StaticRoutes, DynamicRoutes}
 */
interface Data_Generator
{
    /**
     * Adds a route to the data generator. The route data uses the
     * same format that is returned by RouterParser::parser().
     *
     * The handler doesn't necessarily need to be a callable, it
     * can be arbitrary data that will be returned when the route
     * matches.
     *
     * @param ParsedRoute     $routeData
     * @param ExtraParameters $extraParameters
     */
    public function add_route(string $http_method, array $route_data, mixed $handler, array $extra_parameters = []): void;
    /**
     * Returns dispatcher data in some unspecified format, which
     * depends on the used method of dispatch.
     *
     * @return RouteData
     */
    public function get_data(): array;
}