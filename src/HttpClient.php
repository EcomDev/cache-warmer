<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

/**
 * Http Client for pre-warming activities
 *
 */
interface HttpClient
{
    /**
     * Should start HTTP request with provided parameters and notify client observer on completion or error
     */
    public function visitUrl(string $method, string $uri, array $headers, HttpClientObserver $observer): void;

    /** Configures client to request all subsequent urls with provided base url */
    public function withBaseUrl(string $baseUrl): self;
}
