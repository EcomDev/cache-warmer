<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CleanPageCacheByHeaderCommand implements Command, HttpClientObserver
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $headerName;

    /**
     * @var string
     */
    private $headerValue;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /** @var CommandObserver[] */
    private $observers = [];

    public function __construct(string $url, string $headerName, string $headerValue, HttpClient $httpClient)
    {
        $this->url = $url;
        $this->headerName = $headerName;
        $this->headerValue = $headerValue;
        $this->httpClient = $httpClient;
    }

    public function execute(CommandObserver $observer): void
    {
        $this->observers[] = $observer;

        $this->httpClient->visitUrl(
            'BAN',
            $this->url,
            [
                'X-Ban-Header-Name' => $this->headerName,
                'X-Ban-Header-Value' => $this->headerValue
            ],
            $this
        );
    }

    public function requestComplete(string $uri, int $status, array $headers, string $body)
    {
        unset($uri, $status, $headers, $body);
        $this->notifyObserversOfCompletedExecution();
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
