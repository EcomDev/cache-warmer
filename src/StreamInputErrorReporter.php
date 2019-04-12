<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use EcomDev\ReactiveSocket\StreamClient;
use EcomDev\ReactiveSocket\WriteLimitReachedException;

class StreamInputErrorReporter implements InputErrorReporter
{
    private $data = [];

    /** {@inheritdoc} */
    public function reportError(string $input, \Throwable $error): void
    {
        $this->data[] = json_encode(['type' => 'error', 'error' => get_class($error), 'data' => $input]);
    }

    /**
     * Flushes reported errors into stream
     */
    public function flushErrorsIntoStream(StreamClient $client): void
    {
        while ($this->data) {
            $line = array_shift($this->data);
            try {
                $client->write($line . "\n");
            } catch (WriteLimitReachedException $exception) {
                array_unshift($this->data, $line);
                break;
            }
        }
    }
}
