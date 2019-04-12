<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactiveSocket\InMemoryStreamBuffer;
use EcomDev\ReactiveSocket\InMemoryStreamBufferFactory;
use PHPUnit\Framework\TestCase;

class CommandSocketObserverTest extends TestCase
{
    /**
     * @var InMemoryStreamBuffer
     */
    private $streamClient;

    protected function setUp(): void
    {
        $factory = new InMemoryStreamBufferFactory();
        $this->streamClient = $factory->create();
    }
}
