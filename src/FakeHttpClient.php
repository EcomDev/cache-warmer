<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class FakeHttpClient implements HttpClient
{
    /** @var array  */
    private $availablePages = [];

    /** @var string[][] */
    private $hangRequests = [];

    /** @var string[][] */
    private $errorRequests = [];

    /** @var array */
    private $visitedUrls = [];

    /** @var string */
    private $baseUrl = '';

    /**
     * {@inheritdoc}
     */
    public function visitUrl(string $method, string $uri, array $headers, HttpClientObserver $observer): void
    {
        $uri = $this->generateUri($uri);

        $this->visitedUrls[] = [$method, $uri, $headers];

        if (isset($this->hangRequests[$method][$uri])) {
            if ($this->hangRequests[$method][$uri] === 0) {
                return;
            }
            $this->hangRequests[$method][$uri]--;
        }

        if (isset($this->errorRequests[$method][$uri])) {
            $observer->requestError($uri, new \RuntimeException(sprintf('Failed to load %s', $uri)));
            return;
        }

        $status = 404;
        $responseText = 'Page not found';
        $responseHeaders = [];

        if (isset($this->availablePages[$method][$uri])) {
            list($status, $responseHeaders, $responseText) = $this->availablePages[$method][$uri];
        }

        $responseHeaders += ['Content-Type' => 'text/plain', 'Content-Length' => strlen($responseText)];

        $observer->requestComplete($uri, $status, $responseHeaders, $responseText);
    }

    public function withPageContent(
        string $method,
        string $uri,
        string $responseText,
        array $responseHeaders = [],
        int $status = 200
    ): self {
        $configured = clone $this;
        $configured->availablePages[$method][$uri] = [$status, $responseHeaders, $responseText];
        return $configured;
    }

    public function withHangRequest(string $method, string $uri): self
    {
        return $this->withHangRequestAfter($method, $uri, 0);
    }

    public function withErrorRequest(string $method, string $uri): self
    {
        $configured = clone $this;
        $configured->errorRequests[$method][$uri] = $uri;
        return $configured;
    }

    public function flushVisitedUrls(): array
    {
        $visitedUrls = $this->visitedUrls;
        $this->visitedUrls = [];
        return $visitedUrls;
    }

    public function withHangRequestAfter(string $method, string $uri, int $afterTimes): self
    {
        $configured = clone $this;
        $configured->hangRequests[$method][$uri] = $afterTimes;
        return $configured;
    }


    /**
     * {@inheritdoc}
     * @return FakeHttpClient
     */
    public function withBaseUrl(string $baseUrl): HttpClient
    {
        $httpClient = clone $this;
        $httpClient->baseUrl = $baseUrl;
        return $httpClient;
    }

    private function generateUri(string $uri): string
    {
        $url = Url::createFromString($uri);
        if (!$url->isAbsolute()) {
            return rtrim($this->baseUrl, '/')  . '/' . ltrim($url->buildRelativeUrl(), '/');
        }

        return $uri;
    }
}
