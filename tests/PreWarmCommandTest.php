<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class PreWarmCommandTest extends TestCase
{
    /** @var PreWarmCommandFactory */
    private $factory;

    /** @var FakeHttpClient */
    private $httpClient;

    /** @var Command[] */
    private $completedCommands = [];

    protected function setUp(): void
    {
        $this->httpClient = new FakeHttpClient();
        $this->factory = PreWarmCommandFactory::create($this->httpClient);
    }
}
