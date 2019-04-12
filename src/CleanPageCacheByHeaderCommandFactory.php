<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CleanPageCacheByHeaderCommandFactory implements CommandFactory
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public static function create(HttpClient $httpClient): self
    {
        return new self($httpClient);
    }

    /**
     * {@inheritdoc}
     *
     * @return CleanPageCacheByHeaderCommand
     */
    public function createFromInput(array $input): Command
    {
        $url = Url::createFromString($input['url']);

        return new CleanPageCacheByHeaderCommand(
            $url->buildBaseUrl(),
            $input['header_name'],
            $input['header_value'],
            $this->httpClient
        );
    }

    /**
     * Adds custom HTTP client for creating commands
     *
     */
    public function withHttpClient(HttpClient $httpClient): self
    {
        $factory = clone $this;
        $factory->httpClient = $httpClient;
        return $factory;
    }
}
