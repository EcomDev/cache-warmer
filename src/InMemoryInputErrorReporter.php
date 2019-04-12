<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class InMemoryInputErrorReporter implements InputErrorReporter
{
    private $errors = [];

    public static function create(): self
    {
        return new self();
    }

    public function reportError(string $input, \Throwable $error): void
    {
        $this->errors[] = [$input, $error];
    }

    public function flushErrors(): array
    {
        $flushedErrors = $this->errors;
        $this->errors = [];
        return $flushedErrors;
    }
}
