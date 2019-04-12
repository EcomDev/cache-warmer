<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CleanPageCacheCommand implements Command, HttpClientObserver
{
    /** @var HttpClient */
    private $httpClient;

    /** @var string */
    private $baseUrl;

    /** @var UrlRegexp */
    private $regexp;
    /**
     * @var int
     */
    private $headerLimit;

    /** @var CommandObserver[] */
    private $observers = [];

    /** @var int */
    private $openRequests = 0;

    public function __construct(HttpClient $httpClient, string $baseUrl, UrlRegexp $regexp, int $headerLimit)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = $baseUrl;
        $this->regexp = $regexp;
        $this->headerLimit = $headerLimit;
    }

    public function execute(CommandObserver $observer): void
    {
        $this->observers[] = $observer;

        $regExpRequests = $this->regexp->splitByLimit($this->headerLimit);
        $this->openRequests += count($regExpRequests);

        foreach ($regExpRequests as $request) {
            $this->httpClient->visitUrl('BAN', $this->baseUrl, ['X-Ban-Regexp' => (string)$request], $this);
        }
    }

    public function requestComplete(string $uri, int $status, array $headers, string $body)
    {
        unset($uri, $status, $headers, $body);
        $this->openRequests --;
        if ($this->openRequests === 0) {
            $this->notifyObserversOfCompletedExecution();
        }
    }

    public function requestError(string $uri, \Throwable $error): void
    {
        unset($uri, $error);
        $this->notifyObserversOfCompletedExecution();
    }

    private function notifyObserversOfCompletedExecution(): void
    {
        foreach ($this->observers as $observer) {
            $observer->completeExecution($this);
        }

        $this->observers = [];
    }
}
