<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

interface InputErrorReporter
{
    public function reportError(string $input, \Throwable $error): void;
}
