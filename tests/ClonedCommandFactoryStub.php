<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class ClonedCommandFactoryStub implements CommandFactory
{
    /**
     * Command for returning on every item
     *
     * @var Command
     */
    private $command;

    private function __construct(Command $command)
    {
        $this->command = $command;
    }

    public static function create(Command $command): self
    {
        return new self($command);
    }

    public function createFromInput(array $input): Command
    {
        unset($input);

        return clone $this->command;
    }
}
