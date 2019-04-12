<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CallableCommand implements Command
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @var array
     */
    private $arguments;

    public function __construct(callable $callback, array $arguments = [])
    {
        $this->callback = $callback;
        $this->arguments = $arguments;
    }

    public function execute(CommandObserver $observer): void
    {
        call_user_func($this->callback, ...$this->arguments);
        $observer->completeExecution($this);
    }
}
