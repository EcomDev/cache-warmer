<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

/**
 * Service for using in HTTP server that manages cache controls
 */
class PageCacheService
{
    /**
     * Cached page requests storage
     *
     * @var string[][][]
     */
    private $cachedPages = [];


    /**
     * Factory for page cache service
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Check page cache
     */
    public function isCached(string $path): bool
    {
        return isset($this->cachedPages[$path]);
    }

    /**
     * Records page visit
     */
    public function visit(string $path, array $requestHeaders): void
    {
        $this->cachedPages[$path] = $requestHeaders;
    }

    /**
     * Clears cache by path
     */
    public function clearByPath(string $path): void
    {
        $this->filterCachedPages(
            function ($cacheEntry) use ($path) {
                return $cacheEntry !== $path;
            }
        );
    }

    /**
     * Clears cache by regular expression
     */
    public function clearByRegExp(string $regExp): void
    {
        $this->filterCachedPages(
            function ($cacheEntry) use ($regExp) {
                return !preg_match(
                    sprintf('~%s~', addcslashes($regExp, '~')),
                    $cacheEntry
                );
            }
        );
    }

    /**
     * Clears cache by header match
     */
    public function clearByHeader(string $headerName, string $headerValue): void
    {
        $this->filterCachedPages(
            function ($cacheEntry, $requestHeaders) use ($headerName, $headerValue) {
                unset($cacheEntry);

                if (!isset($requestHeaders[$headerName])) {
                    return true;
                }

                return !in_array($headerValue, $requestHeaders[$headerName], true);
            }
        );
    }

    private function filterCachedPages(callable $condition): void
    {
        foreach ($this->cachedPages as $path => $requestHeaders) {
            if (!$condition($path, $requestHeaders)) {
                unset($this->cachedPages[$path]);
            }
        }
    }

    public function clearAll(): void
    {
        $this->cachedPages = [];
    }
}
