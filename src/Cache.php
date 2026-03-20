<?php

declare (strict_types=1);
namespace Fast_Route;

/** @phpstan-import-type ProcessedData from ConfigureRoutes */
interface Cache
{
    /**
     * @param callable():ProcessedData $loader
     *
     * @return ProcessedData
     */
    public function get(string $key, callable $loader): array;
}