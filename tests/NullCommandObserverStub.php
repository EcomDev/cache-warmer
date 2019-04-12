<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class NullCommandObserverStub implements CommandObserver
{
    public function completeExecution(Command $command): void
    {
        unset($command);
    }
}
