<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use React\EventLoop\LoopInterface;
use React\HttpClient\Client;

class ReactHttpClientFactory
{
    /** @var Client */
    private $reactHttpClient;

    public function __construct(Client $reactHttpClient)
    {
        $this->reactHttpClient = $reactHttpClient;
    }

    public static function createWithEventLoop(LoopInterface $loop): self
    {
        return new self(new Client($loop));
    }

    public function create(): ReactHttpClient
    {
        return new ReactHttpClient($this->reactHttpClient);
    }
}
