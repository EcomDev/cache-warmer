<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class FakeCommandQueue implements CommandQueue
{
    /** @var Command[] */
    private $commandsToExecute = [];

    /** @var Command[] */
    private $addedCommands = [];

    /** @var Command[] */
    private $executedCommands = [];

    public static function create(): self
    {
        return new self();
    }

    public function add(Command $command)
    {
        $this->commandsToExecute[] = $command;
        $this->addedCommands[] = $command;
    }

    public function execute()
    {
        $this->executedCommands = array_merge($this->commandsToExecute, $this->executedCommands);
        $this->commandsToExecute = [];
    }

    public function flushAddedCommands(): array
    {
        $commands = $this->addedCommands;
        $this->addedCommands = [];
        return $commands;
    }

    public function flushExecutedCommands(): array
    {
        $commands = $this->executedCommands;
        $this->executedCommands = [];
        return $commands;
    }
}
