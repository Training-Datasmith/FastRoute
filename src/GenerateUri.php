<?php

declare (strict_types=1);
namespace Fast_Route;

use Fast_Route\Generate_Uri\Generated_Uri;
use Fast_Route\Generate_Uri\Uri_Could_Not_Be_Generated;
/**
 * @phpstan-import-type ParsedRoutes from RouteParser
 * @phpstan-type RoutesForUriGeneration array<non-empty-string, ParsedRoutes>
 * @phpstan-type UriSubstitutions array<non-empty-string, non-empty-string>
 */
interface Generate_Uri
{
    /**
     * @param UriSubstitutions $substitutions
     *
     * @throws UriCouldNotBeGenerated
     */
    public function for_route(string $name, array $substitutions = []): Generated_Uri;
}