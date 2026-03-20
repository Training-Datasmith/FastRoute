<?php

declare(strict_types=1);

/**
 * Example: Define routes and dispatch an incoming HTTP request.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

$dispatcher = simpleDispatcher(function (\Fast_Route\Configure_Routes $r): void {
    $r->get('/', 'HomeController::index');
    $r->get('/users', 'UserController::list');
    $r->post('/users', 'UserController::create');
    $r->get('/users/{id:\d+}', 'UserController::show');
    $r->put('/users/{id:\d+}', 'UserController::update');
    $r->delete('/users/{id:\d+}', 'UserController::delete');
});

// Simulate an incoming request.
$httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri        = $_SERVER['REQUEST_URI'] ?? '/users/42';

// Strip query string.
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo '404 Not Found';
        break;

    case Dispatcher::METHOD_NOT_ALLOWED:
        http_response_code(405);
        header('Allow: ' . implode(', ', $routeInfo[1]));
        echo '405 Method Not Allowed';
        break;

    case Dispatcher::FOUND:
        $handler = $routeInfo[1];   // e.g. 'UserController::show'
        $vars    = $routeInfo[2];   // e.g. ['id' => '42']
        echo "Handler: {$handler}, Params: " . http_build_query($vars);
        break;
}
