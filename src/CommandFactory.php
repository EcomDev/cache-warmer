<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

interface CommandFactory
{
    /**
     * Creates a command instance from input array
     *
     * @throws InvalidCommandInput
     */
    public function createFromInput(array $input): Command;
}
