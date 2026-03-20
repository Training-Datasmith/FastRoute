<?php

declare (strict_types=1);
namespace Fast_Route\Dispatcher;

use function count;
use Fast_Route\Dispatcher\Result\Matched;
use function preg_match;
/** @final */
class Group_Count_Based extends Regex_Based_Abstract
{
    /** @inheritDoc */
    protected function dispatch_variable_route(array $route_data, string $uri): ?Matched
    {
        foreach ($route_data as $data) {
            if (preg_match($data['regex'], $uri, $matches) !== 1) {
                continue;
            }
            [$handler, $var_names, $extra_parameters] = $data['routeMap'][count($matches)];
            $vars = [];
            $i = 0;
            foreach ($var_names as $var_name) {
                $vars[$var_name] = $matches[++$i];
            }
            $result = new Matched();
            $result->handler = $handler;
            $result->variables = $vars;
            $result->extra_parameters = $extra_parameters;
            return $result;
        }
        return null;
    }
}