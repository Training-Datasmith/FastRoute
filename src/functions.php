<?php

declare (strict_types=1);
namespace Fast_Route;

use Fast_Route\Cache\File_Cache;
use function function_exists;
use function is_string;
use LogicException;
if (!function_exists('FastRoute\simpleDispatcher')) {
    /**
     * @deprecated since v2.0 and will be removed in v3.0
     *
     * @see FastRoute::recommendedSettings()
     * @see FastRoute::disableCache()
     *
     * @param callable(ConfigureRoutes):void                                                                                                                                                                                                                                                           $routeDefinitionCallback
     * @param array{routeParser?: class-string<RouteParser>, dataGenerator?: class-string<DataGenerator>, dispatcher?: class-string<Dispatcher>, routeCollector?: class-string<ConfigureRoutes>, cacheDisabled?: bool, cacheKey?: string, cacheFile?: string, cacheDriver?: class-string<Cache>|Cache} $options
     */
    function simple_dispatcher(callable $route_definition_callback, array $options = []): Dispatcher
    {
        return Fast_Route\cached_dispatcher($route_definition_callback, ['cacheDisabled' => true] + $options);
    }
    /**
     * @deprecated since v2.0 and will be removed in v3.0
     *
     * @see FastRoute::recommendedSettings()
     *
     * @param callable(ConfigureRoutes):void                                                                                                                                                                                                                                                           $routeDefinitionCallback
     * @param array{routeParser?: class-string<RouteParser>, dataGenerator?: class-string<DataGenerator>, dispatcher?: class-string<Dispatcher>, routeCollector?: class-string<ConfigureRoutes>, cacheDisabled?: bool, cacheKey?: string, cacheFile?: string, cacheDriver?: class-string<Cache>|Cache} $options
     */
    function cached_dispatcher(callable $route_definition_callback, array $options = []): Dispatcher
    {
        $options += ['routeParser' => Route_Parser\Std::class, 'dataGenerator' => Data_Generator\Mark_Based::class, 'dispatcher' => Dispatcher\Mark_Based::class, 'routeCollector' => Route_Collector::class, 'cacheDisabled' => false, 'cacheDriver' => File_Cache::class];
        $loader = static function () use ($route_definition_callback, $options): array {
            $route_collector = new $options['routeCollector'](new $options['routeParser'](), new $options['dataGenerator']());
            $route_definition_callback($route_collector);
            return $route_collector->processed_routes();
        };
        if ($options['cacheDisabled'] === true) {
            return new $options['dispatcher']($loader());
        }
        $cache_key = $options['cacheKey'] ?? $options['cacheFile'] ?? null;
        if ($cache_key === null) {
            throw new LogicException('Must specify "cacheKey" option');
        }
        $cache = $options['cacheDriver'];
        if (is_string($cache)) {
            $cache = new $cache();
        }
        return new $options['dispatcher']($cache->get($cache_key, $loader));
    }
}