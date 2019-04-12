<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class FakeHttpClientObserver implements HttpClientObserver
{
    /** @var array[] */
    private $completedRequests = [];

    /** @var array[] */
    private $errors = [];

    /** @var string[] */
    private $waiting = [];

    public function requestComplete(string $uri, int $status, array $headers, string $body)
    {
        $this->completedRequests[] = [$uri, $status, $headers, $body];
        $this->resolveAwaitedResponse($uri);
    }

    public function requestError(string $uri, \Throwable $error): void
    {
        $this->errors[] = [$uri, $error];
        $this->resolveAwaitedResponse($uri);
    }

    public function flushCompletedRequests(): array
    {
        $completedRequests = $this->completedRequests;
        $this->completedRequests = [];
        return $completedRequests;
    }

    public function flushErrorRequests(): array
    {
        $errors = $this->errors;
        $this->errors = [];
        return $errors;
    }

    public function waitForRequest(string $uri): void
    {
        $this->waiting[] = $uri;
    }

    public function isWaiting(): bool
    {
        return !empty($this->waiting);
    }

    private function resolveAwaitedResponse(string $uri): void
    {
        $index = array_search($uri, $this->waiting, true);
        if ($index !== false) {
            unset($this->waiting[$index]);
        }
    }
}
