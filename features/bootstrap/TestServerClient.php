<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;

class TestServerClient
{
    const URL_FORMAT_FOR_TEST_SERVER = 'http://127.0.0.1:%d/%s';

    /**
     * Server HTTP port
     *
     * @var int
     */
    private $port;

    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(int $port, HttpClient $httpClient)
    {
        $this->port = $port;
        $this->httpClient = $httpClient;
    }

    public static function create(int $port): self
    {
        return new self($port, new HttpClient());
    }

    public function visitPage(string $path, array $headers = []): ResponseInterface
    {
        return $this->httpClient->request(
            'GET',
            $this->createPageUrl($path),
            ['headers' => $headers]
        );
    }

    public function banByPath(string $path): void
    {
        $this->httpClient->request(
            'BAN',
            $this->createPageUrl('/'),
            [
                'headers' => [
                    'X-Ban-Url' => $path
                ]
            ]
        );
    }

    public function banByRegExp(string $banRegExp): void
    {
        $this->httpClient->request(
            'BAN',
            $this->createPageUrl('/'),
            [
                'headers' => [
                    'X-Ban-Regexp' => $banRegExp
                ]
            ]
        );
    }

    public function isCached(string $path): bool
    {
        $response = $this->httpClient->request(
            'CACHED',
            $this->createPageUrl($path),
            ['exceptions' => false]
        );

        return $response->getStatusCode() === 200;
    }

    public function banByHeaderValue(string $headerName, string $headerValue)
    {
        $this->httpClient->request(
            'BAN',
            $this->createPageUrl('/'),
            [
                'headers' => [
                    'X-Ban-Header-Name' => $headerName,
                    'X-Ban-Header-Value' => $headerValue,
                ]
            ]
        );
    }

    private function createPageUrl(string $path): string
    {
        return sprintf(self::URL_FORMAT_FOR_TEST_SERVER, $this->port, ltrim($path, '/'));
    }
}
