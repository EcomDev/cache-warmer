<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

class CommandSequenceRecorder
{
    private $sequence = [];

    public function createCommand(string $label): Command
    {
        return (new CallableCommand($this, [$label]));
    }

    public function flushCommandSequence(): array
    {
        $sequence = $this->sequence;
        $this->sequence = [];
        return $sequence;
    }

    public function __invoke(string $label)
    {
        $this->sequence[] = $label;
    }

    public function executeCommand(string $label)
    {
        $this->createCommand($label)->execute(new NullCommandObserverStub());
    }
}
