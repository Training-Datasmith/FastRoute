# FastRoute Architecture

## Purpose

A fast, regex-based HTTP request router for PHP.  It compiles a set of route
definitions into optimised regular expressions at build time and dispatches
incoming requests in O(1)–O(log n) time.

## Directory Structure

```
src/
  Fast_Route.php            — top-level factory (simpleDispatcher / cachedDispatcher)
  functions.php             — procedural entry-point aliases
  Configure_Routes.php      — interface: route registration API
  Route_Collector.php       — default implementation of ConfigureRoutes
  Route_Parser.php          — interface: parses route patterns into token arrays
  RouteParser/
    Std.php                 — standard route parser (handles {param}, {param:\d+})
  Data_Generator.php        — interface: groups parsed routes into dispatch data
  DataGenerator/
    Regex_Based_Abstract.php — base for all regex strategies
    Group_Count_Based.php    — strategy: use capture-group count to identify routes
    Group_Pos_Based.php      — strategy: use capture-group position
    Char_Count_Based.php     — strategy: use character length of first segment
    Mark_Based.php           — strategy: use PCRE (*MARK) verb (fastest on PHP 8+)
  Dispatcher.php            — interface: match a method+path against dispatch data
  Dispatcher/
    Regex_Based_Abstract.php — base dispatcher for all regex strategies
    Group_Count_Based.php / Group_Pos_Based.php / Char_Count_Based.php / Mark_Based.php
    Result/
      Matched.php           — successful match value object
      Not_Matched.php       — no route found
      Method_Not_Allowed.php — route exists but wrong HTTP method
  Generate_Uri.php          — interface: reverse-generate URIs from named routes
  GenerateUri/
    From_Processed_Configuration.php — default URI generator
    Generated_Uri.php       — value object for the generated URI string
    Uri_Could_Not_Be_Generated.php   — exception for invalid generation args
  Cache.php                 — interface: PSR-16 subset used for route caching
  Cache/
    File_Cache.php          — file-based route cache
    Psr16Cache.php          — PSR-16 cache adapter
  Bad_Route_Exception.php   — thrown on invalid route definitions
  Exception.php             — base exception
```

## Key Design Decisions

- **Compile-once dispatch**: route patterns are compiled into large combined
  regexes at startup; matching a request is a single `preg_match` call.
- **Pluggable strategies**: four `DataGenerator`/`Dispatcher` strategy pairs are
  provided; `Mark_Based` is fastest on PHP 8+ thanks to the PCRE `(*MARK)` verb.
- **Caching**: `cachedDispatcher()` writes compiled route data to a PSR-16 cache
  so that the compilation step is skipped on subsequent requests.
- **Named routes & URI generation**: routes registered with the `name` extra
  parameter can be reverse-generated via `Generate_Uri`.

## Extension Points

- Implement `Data_Generator` + `Dispatcher` for a custom dispatch strategy.
- Implement `Cache` to use any storage backend (Redis, APCu, etc.).
- Implement `Route_Parser` for alternative URL pattern syntax.

## Dependency Flow

```
Consumer
  └── Fast_Route::recommendedSettings() / simpleDispatcher()
        ├── Route_Collector  (implements Configure_Routes)
        │     ├── Route_Parser\Std
        │     └── DataGenerator\Mark_Based
        └── Dispatcher\Mark_Based
              └── Dispatcher\Result\{Matched, Not_Matched, Method_Not_Allowed}
```
