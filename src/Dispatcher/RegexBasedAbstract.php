<?php

declare (strict_types=1);
namespace Fast_Route\Dispatcher;

use Fast_Route\Data_Generator;
use Fast_Route\Dispatcher;
use Fast_Route\Dispatcher\Result\Matched;
use Fast_Route\Dispatcher\Result\Method_Not_Allowed;
use Fast_Route\Dispatcher\Result\Not_Matched;
/**
 * @internal
 *
 * @phpstan-import-type StaticRoutes from DataGenerator
 * @phpstan-import-type DynamicRouteChunk from DataGenerator
 * @phpstan-import-type DynamicRouteChunks from DataGenerator
 * @phpstan-import-type DynamicRoutes from DataGenerator
 * @phpstan-import-type RouteData from DataGenerator
 */
abstract class Regex_Based_Abstract implements Dispatcher
{
    /** @var StaticRoutes */
    protected array $static_route_map = [];
    /** @var DynamicRoutes */
    protected array $variable_route_data = [];
    /** @param RouteData $data */
    public function __construct(array $data)
    {
        [$this->static_route_map, $this->variable_route_data] = $data;
    }
    /** @param DynamicRouteChunks $routeData */
    abstract protected function dispatch_variable_route(array $route_data, string $uri): ?Matched;
    public function dispatch(string $http_method, string $uri): Matched|Not_Matched|Method_Not_Allowed
    {
        if (isset($this->static_route_map[$http_method][$uri])) {
            $result = new Matched();
            $result->handler = $this->static_route_map[$http_method][$uri][0];
            $result->extra_parameters = $this->static_route_map[$http_method][$uri][1];
            return $result;
        }
        if (isset($this->variable_route_data[$http_method])) {
            $result = $this->dispatch_variable_route($this->variable_route_data[$http_method], $uri);
            if ($result !== null) {
                return $result;
            }
        }
        // For HEAD requests, attempt fallback to GET
        if ($http_method === 'HEAD') {
            if (isset($this->static_route_map['GET'][$uri])) {
                $result = new Matched();
                $result->handler = $this->static_route_map['GET'][$uri][0];
                $result->extra_parameters = $this->static_route_map['GET'][$uri][1];
                return $result;
            }
            if (isset($this->variable_route_data['GET'])) {
                $result = $this->dispatch_variable_route($this->variable_route_data['GET'], $uri);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        // If nothing else matches, try fallback routes
        if (isset($this->static_route_map['*'][$uri])) {
            $result = new Matched();
            $result->handler = $this->static_route_map['*'][$uri][0];
            $result->extra_parameters = $this->static_route_map['*'][$uri][1];
            return $result;
        }
        if (isset($this->variable_route_data['*'])) {
            $result = $this->dispatch_variable_route($this->variable_route_data['*'], $uri);
            if ($result !== null) {
                return $result;
            }
        }
        // Find allowed methods for this URI by matching against all other HTTP methods as well
        $allowed_methods = [];
        foreach ($this->static_route_map as $method => $uri_map) {
            if ($method === $http_method) {
                continue;
            }
            if (!isset($uri_map[$uri])) {
                continue;
            }
            $allowed_methods[] = $method;
        }
        foreach ($this->variable_route_data as $method => $route_data) {
            if ($method === $http_method) {
                continue;
            }
            $result = $this->dispatch_variable_route($route_data, $uri);
            if ($result === null) {
                continue;
            }
            $allowed_methods[] = $method;
        }
        // If there are no allowed methods the route simply does not exist
        if ($allowed_methods !== []) {
            $result = new Method_Not_Allowed();
            $result->allowed_methods = $allowed_methods;
            return $result;
        }
        return new Not_Matched();
    }
}