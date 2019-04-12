<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactiveSocket\Stream;
use EcomDev\ReactiveSocket\StreamClient;
use EcomDev\ReactiveSocket\StreamObserver;

class CommandSocketObserver implements StreamObserver
{
    public function handleConnected(Stream $stream): void
    {
        // TODO: Implement handleConnected() method.
    }

    public function handleWritable(Stream $stream, StreamClient $client): void
    {
        // TODO: Implement handleWritable() method.
    }

    public function handleReadable(Stream $stream, StreamClient $client): void
    {
        // TODO: Implement handleReadable() method.
    }

    public function handleDisconnected(Stream $stream, string ...$unsentData): void
    {
        // TODO: Implement handleDisconnected() method.
    }
}
