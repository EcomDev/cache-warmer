<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class IdentifiedNullCommandStub implements Command
{
    /**
     * Identity of the command
     *
     * @var string
     */
    private $identity;

    private function __construct(string $identity)
    {
        $this->identity = $identity;
    }

    public static function create(string $identity): self
    {
        return new self($identity);
    }


    public function execute(CommandObserver $observer): void
    {
    }
}
