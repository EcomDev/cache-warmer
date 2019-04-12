<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

interface HttpClientObserver
{
    public function requestComplete(string $uri, int $status, array $headers, string $body);

    public function requestError(string $uri, \Throwable $error): void;
}
