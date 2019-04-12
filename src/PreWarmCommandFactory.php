<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class PreWarmCommandFactory implements CommandFactory
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
     * Creates prew-arm command based on array input.
     *
     * Expected input is array with key "url" filled with page to pre-warm
     */
    public function createFromInput(array $input): Command
    {
        // TODO: Implement createFromInput() method.
    }
}
