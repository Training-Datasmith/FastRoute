<?php

declare (strict_types=1);
namespace Fast_Route;

use function array_key_exists;
use function array_reverse;
use function is_string;
/**
 * @phpstan-import-type ProcessedData from ConfigureRoutes
 * @phpstan-import-type ExtraParameters from DataGenerator
 * @phpstan-import-type RoutesForUriGeneration from GenerateUri
 * @phpstan-import-type ParsedRoutes from RouteParser
 * @final
 */
class Route_Collector implements Configure_Routes
{
    protected string $current_group_prefix = '';
    /** @var RoutesForUriGeneration */
    private array $named_routes = [];
    public function __construct(protected readonly Route_Parser $route_parser, protected readonly Data_Generator $data_generator)
    {
    }
    /** @inheritDoc */
    public function add_route(string|array $http_method, string $route, mixed $handler, array $extra_parameters = []): void
    {
        $route = $this->current_group_prefix . $route;
        $parsed_routes = $this->route_parser->parse($route);
        $extra_parameters = [self::ROUTE_REGEX => $route] + $extra_parameters;
        foreach ((array) $http_method as $method) {
            foreach ($parsed_routes as $parsed_route) {
                $this->data_generator->add_route($method, $parsed_route, $handler, $extra_parameters);
            }
        }
        if (array_key_exists(self::ROUTE_NAME, $extra_parameters)) {
            $this->register_named_route($extra_parameters[self::ROUTE_NAME], $parsed_routes);
        }
    }
    /** @param ParsedRoutes $parsedRoutes */
    private function register_named_route(mixed $name, array $parsed_routes): void
    {
        if (!is_string($name) || $name === '') {
            throw Bad_Route_Exception::invalid_route_name($name);
        }
        if (array_key_exists($name, $this->named_routes)) {
            throw Bad_Route_Exception::named_route_already_defined($name);
        }
        $this->named_routes[$name] = array_reverse($parsed_routes);
    }
    public function add_group(string $prefix, callable $callback): void
    {
        $previous_group_prefix = $this->current_group_prefix;
        $this->current_group_prefix = $previous_group_prefix . $prefix;
        $callback($this);
        $this->current_group_prefix = $previous_group_prefix;
    }
    /** @inheritDoc */
    public function any(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('*', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function get(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('GET', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function post(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('POST', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function put(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('PUT', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function delete(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('DELETE', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function patch(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('PATCH', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function head(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('HEAD', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function options(string $route, mixed $handler, array $extra_parameters = []): void
    {
        $this->add_route('OPTIONS', $route, $handler, $extra_parameters);
    }
    /** @inheritDoc */
    public function processed_routes(): array
    {
        $data = $this->data_generator->get_data();
        $data[] = $this->named_routes;
        return $data;
    }
    /**
     * @deprecated
     *
     * @see ConfigureRoutes::processedRoutes()
     *
     * @return ProcessedData
     */
    public function get_data(): array
    {
        return $this->processed_routes();
    }
}