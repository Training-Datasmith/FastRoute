<?php

declare (strict_types=1);
namespace Fast_Route\Generate_Uri;

use Fast_Route\Generate_Uri;
use function http_build_query;
use Psr\Http\Message\Uri_Interface;
use Stringable;
/** @phpstan-import-type UriSubstitutions from GenerateUri */
final class Generated_Uri implements Stringable
{
    /**
     * @param non-empty-string $path
     * @param UriSubstitutions $unmatchedSubstitutions
     */
    public function __construct(public readonly string $path, public readonly array $unmatched_substitutions)
    {
    }
    public function as_uri(Uri_Interface $base_uri): Uri_Interface
    {
        return $base_uri->with_path($this->path)->with_query(http_build_query($this->unmatched_substitutions));
    }
    public function __toString(): string
    {
        return $this->path;
    }
}