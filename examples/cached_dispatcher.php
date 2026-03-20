<?php

declare(strict_types=1);

/**
 * Example: Use a file-based cache to avoid recompiling routes on every request.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use function FastRoute\cachedDispatcher;

$cacheFile  = sys_get_temp_dir() . '/fastroute_cache.php';
$dispatcher = cachedDispatcher(
    function (\Fast_Route\Configure_Routes $r): void {
        $r->get('/api/ping', 'ApiController::ping');
        $r->get('/api/users/{id:\d+}', 'ApiController::user');
        $r->post('/api/users', 'ApiController::createUser');
    },
    [
        'cacheFile'     => $cacheFile,
        'cacheDisabled' => false, // set true during development
    ]
);

$result = $dispatcher->dispatch('GET', '/api/users/7');

if ($result[0] === \FastRoute\Dispatcher::FOUND) {
    echo 'Handler: ' . $result[1] . ', id=' . $result[2]['id'] . PHP_EOL;
}
