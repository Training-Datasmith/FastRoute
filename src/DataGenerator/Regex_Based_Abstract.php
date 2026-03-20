<?php

declare (strict_types=1);
namespace Fast_Route\Data_Generator;

use function array_chunk;
use function array_map;
use function assert;
use function ceil;
use function count;
use Fast_Route\Bad_Route_Exception;
use Fast_Route\Data_Generator;
use Fast_Route\Route;
use Fast_Route\Route_Parser;
use function is_string;
use function max;
use function round;
/**
 * @internal
 *
 * @phpstan-import-type StaticRoutes from DataGenerator
 * @phpstan-import-type DynamicRouteChunk from DataGenerator
 * @phpstan-import-type DynamicRoutes from DataGenerator
 * @phpstan-import-type RouteData from DataGenerator
 * @phpstan-import-type ExtraParameters from DataGenerator
 * @phpstan-import-type ParsedRoute from RouteParser
 */
abstract class Regex_Based_Abstract implements Data_Generator
{
    /** @var StaticRoutes */
    protected array $static_routes = [];
    /** @var array<string, array<string, Route>> */
    protected array $method_to_regex_to_routes_map = [];
    abstract protected function get_approx_chunk_size(): int;
    /**
     * @param array<string, Route> $regexToRoutesMap
     *
     * @return DynamicRouteChunk
     */
    abstract protected function process_chunk(array $regex_to_routes_map): array;
    /** @inheritDoc */
    public function add_route(string $http_method, array $route_data, mixed $handler, array $extra_parameters = []): void
    {
        if ($this->is_static_route($route_data)) {
            $this->add_static_route($http_method, $route_data, $handler, $extra_parameters);
        } else {
            $this->add_variable_route($http_method, $route_data, $handler, $extra_parameters);
        }
    }
    /** @inheritDoc */
    public function get_data(): array
    {
        if ($this->method_to_regex_to_routes_map === []) {
            return [$this->static_routes, []];
        }
        return [$this->static_routes, $this->generate_variable_route_data()];
    }
    /** @return DynamicRoutes */
    private function generate_variable_route_data(): array
    {
        $data = [];
        foreach ($this->method_to_regex_to_routes_map as $method => $regex_to_routes_map) {
            $chunk_size = $this->compute_chunk_size(count($regex_to_routes_map));
            $chunks = array_chunk($regex_to_routes_map, $chunk_size, true);
            $data[$method] = array_map($this->process_chunk(...), $chunks);
        }
        return $data;
    }
    /** @return positive-int */
    private function compute_chunk_size(int $count): int
    {
        $num_parts = max(1, round($count / $this->get_approx_chunk_size()));
        $size = (int) ceil($count / $num_parts);
        assert($size > 0);
        return $size;
    }
    /** @param ParsedRoute $routeData */
    private function is_static_route(array $route_data): bool
    {
        return count($route_data) === 1 && is_string($route_data[0]);
    }
    /**
     * @param ParsedRoute     $routeData
     * @param ExtraParameters $extraParameters
     */
    private function add_static_route(string $http_method, array $route_data, mixed $handler, array $extra_parameters): void
    {
        $route_str = $route_data[0];
        assert(is_string($route_str));
        if (isset($this->static_routes[$http_method][$route_str])) {
            throw Bad_Route_Exception::already_registered($route_str, $http_method);
        }
        if (isset($this->method_to_regex_to_routes_map[$http_method])) {
            foreach ($this->method_to_regex_to_routes_map[$http_method] as $route) {
                if ($route->matches($route_str)) {
                    throw Bad_Route_Exception::shadowed_by_variable_route($route_str, $route->regex, $http_method);
                }
            }
        }
        $this->static_routes[$http_method][$route_str] = [$handler, $extra_parameters];
    }
    /**
     * @param ParsedRoute     $routeData
     * @param ExtraParameters $extraParameters
     */
    private function add_variable_route(string $http_method, array $route_data, mixed $handler, array $extra_parameters): void
    {
        $route = new Route($http_method, $route_data, $handler, $extra_parameters);
        $regex = $route->regex;
        if (isset($this->method_to_regex_to_routes_map[$http_method][$regex])) {
            throw Bad_Route_Exception::already_registered($regex, $http_method);
        }
        $this->method_to_regex_to_routes_map[$http_method][$regex] = $route;
    }
}