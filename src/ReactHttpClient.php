<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use React\HttpClient\Client;
use React\HttpClient\Response;

class ReactHttpClient implements HttpClient
{
    /** @var Client */
    private $httpClient;

    /**
     * @var string
     */
    private $baseUrl = '';

    public function __construct(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Should start HTTP request with provided parameters and notify client observer on completion or error
     */
    public function visitUrl(string $method, string $uri, array $headers, HttpClientObserver $observer): void
    {
        if ($this->baseUrl) {
            $parts = parse_url($uri);
            if (empty($parts['host'])) {
                $uri = $this->baseUrl . ltrim($uri, '/');
            }
        }

        $this->httpClient->request($method, $uri, $headers)
            ->on('response', function (Response $response) use ($observer, $uri) {
                $body = '';

                $response->on('data', function ($chunk) use (&$body) {
                    $body .= $chunk;
                });

                $response->on('end', function () use ($observer, $uri, $response, &$body) {
                    $observer->requestComplete($uri, $response->getCode(), $response->getHeaders(), $body);
                });
            })
            ->on('error', function (\Throwable $error) use ($observer, $uri) {
                $observer->requestError($uri, $error);
            })
            ->end();
    }

    /** Configures client to request all subsequent urls with provided base url */
    public function withBaseUrl(string $baseUrl): HttpClient
    {
        $client = clone $this;
        $client->baseUrl = $baseUrl;
        return $client;
    }
}
