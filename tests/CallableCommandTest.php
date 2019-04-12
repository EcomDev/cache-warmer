<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class CallableCommandTest extends TestCase implements CommandObserver
{
    private $calls = [];

    private $executedCommands = [];

    /** @test */
    public function executesCallableOnceOnExecution()
    {
        $this->createCommand()->execute($this);

        $this->assertCommandExecutionSequence([]);
    }

    /** @test */
    public function callsCallbackEveryTimeExecuteIsInvoked()
    {
        $command = $this->createCommand();

        $command->execute($this);
        $command->execute($this);

        $this->assertCommandExecutionSequence([], []);
    }
    
    /** @test */
    public function passesArgumentsToCallableWhenExecuteIsInvoked()
    {
        $command = $this->createCommand(['argument 1', 'argument 2']);
        $command->execute($this);

        $this->assertCommandExecutionSequence(['argument 1', 'argument 2']);
    }

    /** @test */
    public function notifiesCommandObserverOfCompletedOperation()
    {
        $command = $this->createCommand();
        $command->execute($this);

        $this->assertCommandCompletionSequence($command);
    }

    /** @test */
    public function notifiesCommandObserverEverySingleTimeCommandIsExecuted()
    {
        $command = $this->createCommand();
        $command->execute($this);
        $command->execute($this);
        $command->execute($this);

        $this->assertCommandCompletionSequence($command, $command, $command);
    }

    private function createCommand(array $arguments = []): CallableCommand
    {
        return new CallableCommand(
            function (...$arguments) {
                $this->calls[] = $arguments;
            },
            $arguments
        );
    }

    private function assertCommandExecutionSequence(array ...$sequence): void
    {
        $this->assertEquals($sequence, $this->calls, 'Execution sequence does not match expected one');
    }

    private function assertCommandCompletionSequence(Command ...$sequence): void
    {
        $this->assertEquals($sequence, $this->executedCommands, 'Executed commands sequence does not match');
    }

    public function completeExecution(Command $command): void
    {
        $this->executedCommands[] = $command;
    }
}
