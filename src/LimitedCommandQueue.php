<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use SplDoublyLinkedList;

class LimitedCommandQueue implements CommandQueue, CommandObserver
{

    /**
     * Limit of commands in queue
     *
     * @var int
     */
    private $limit;

    /** @var Command[] */
    private $runningCommands;

    /** @var \SplObjectStorage */
    private $commandBacklog;

    public function __construct(int $limit, SplDoublyLinkedList $commandBacklog)
    {
        $this->limit = $limit;
        $this->runningCommands = [];
        $this->commandBacklog = $commandBacklog;
    }

    public static function create(int $limit): self
    {
        return new self($limit, new SplDoublyLinkedList());
    }


    public function add(Command $command)
    {
        $this->commandBacklog->push($command);
    }

    public function execute()
    {
        $currentlyActiveCommands = count($this->runningCommands);

        while (!$this->commandBacklog->isEmpty() && $currentlyActiveCommands < $this->limit) {
            $command = $this->commandBacklog->shift();
            $this->runningCommands[spl_object_hash($command)] = $command;
            $command->execute($this);
            $currentlyActiveCommands++;
        }
    }

    public function completeExecution(Command $command): void
    {
        unset($this->runningCommands[spl_object_hash($command)]);
    }
}
