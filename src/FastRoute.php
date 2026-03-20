<?php

declare (strict_types=1);
namespace Fast_Route;

use function assert;
use Closure;
use Fast_Route\Cache\File_Cache;
use function is_string;
/** @phpstan-import-type ProcessedData from ConfigureRoutes */
final class Fast_Route
{
    /** @var ProcessedData|null */
    private ?array $processed_configuration = null;
    /**
     * @param Closure(ConfigureRoutes):void  $routeDefinitionCallback
     * @param class-string<RouteParser>      $routeParser
     * @param class-string<DataGenerator>    $dataGenerator
     * @param class-string<Dispatcher>       $dispatcher
     * @param class-string<ConfigureRoutes>  $routesConfiguration
     * @param class-string<GenerateUri>      $uriGenerator
     * @param Cache|class-string<Cache>|null $cacheDriver
     * @param non-empty-string|null          $cacheKey
     */
    private function __construct(private readonly Closure $route_definition_callback, private readonly string $route_parser, private readonly string $data_generator, private readonly string $dispatcher, private readonly string $routes_configuration, private readonly string $uri_generator, private readonly Cache|string|null $cache_driver, private readonly ?string $cache_key)
    {
    }
    /**
     * @param Closure(ConfigureRoutes):void $routeDefinitionCallback
     * @param non-empty-string              $cacheKey
     */
    public static function recommended_settings(Closure $route_definition_callback, string $cache_key): self
    {
        return new self($route_definition_callback, Route_Parser\Std::class, Data_Generator\Mark_Based::class, Dispatcher\Mark_Based::class, Route_Collector::class, Generate_Uri\From_Processed_Configuration::class, File_Cache::class, $cache_key);
    }
    public function disable_cache(): self
    {
        return new self($this->route_definition_callback, $this->route_parser, $this->data_generator, $this->dispatcher, $this->routes_configuration, $this->uri_generator, null, null);
    }
    /**
     * @param Cache|class-string<Cache> $driver
     * @param non-empty-string          $cacheKey
     */
    public function with_cache(Cache|string $driver, string $cache_key): self
    {
        return new self($this->route_definition_callback, $this->route_parser, $this->data_generator, $this->dispatcher, $this->routes_configuration, $this->uri_generator, $driver, $cache_key);
    }
    public function use_char_count_dispatcher(): self
    {
        return $this->use_custom_dispatcher(Data_Generator\Char_Count_Based::class, Dispatcher\Char_Count_Based::class);
    }
    public function use_group_count_dispatcher(): self
    {
        return $this->use_custom_dispatcher(Data_Generator\Group_Count_Based::class, Dispatcher\Group_Count_Based::class);
    }
    public function use_group_pos_dispatcher(): self
    {
        return $this->use_custom_dispatcher(Data_Generator\Group_Pos_Based::class, Dispatcher\Group_Pos_Based::class);
    }
    public function use_mark_dispatcher(): self
    {
        return $this->use_custom_dispatcher(Data_Generator\Mark_Based::class, Dispatcher\Mark_Based::class);
    }
    /**
     * @param class-string<DataGenerator> $dataGenerator
     * @param class-string<Dispatcher>    $dispatcher
     */
    public function use_custom_dispatcher(string $data_generator, string $dispatcher): self
    {
        return new self($this->route_definition_callback, $this->route_parser, $data_generator, $dispatcher, $this->routes_configuration, $this->uri_generator, $this->cache_driver, $this->cache_key);
    }
    /** @param class-string<GenerateUri> $uriGenerator */
    public function with_uri_generator(string $uri_generator): self
    {
        return new self($this->route_definition_callback, $this->route_parser, $this->data_generator, $this->dispatcher, $this->routes_configuration, $uri_generator, $this->cache_driver, $this->cache_key);
    }
    /** @return ProcessedData */
    private function build_configuration(): array
    {
        if ($this->processed_configuration !== null) {
            return $this->processed_configuration;
        }
        $loader = function (): array {
            $configured_routes = new $this->routes_configuration(new $this->route_parser(), new $this->data_generator());
            ($this->route_definition_callback)($configured_routes);
            return $configured_routes->processed_routes();
        };
        if ($this->cache_driver === null) {
            return $this->processed_configuration = $loader();
        }
        assert(is_string($this->cache_key));
        $cache = is_string($this->cache_driver) ? new $this->cache_driver() : $this->cache_driver;
        return $this->processed_configuration = $cache->get($this->cache_key, $loader);
    }
    public function dispatcher(): Dispatcher
    {
        return new $this->dispatcher($this->build_configuration());
    }
    public function uri_generator(): Generate_Uri
    {
        return new $this->uri_generator($this->build_configuration()[2]);
    }
}