<?php
/**
 * Copyright © EcomDev B.V. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace EcomDev\CacheWarmer;

use PHPUnit\Framework\TestCase;

class CommandTesterTest extends TestCase
{
    /** @var CommandSequenceRecorder */
    private $commandTester;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->commandTester = new CommandSequenceRecorder();
    }

    /** @test */
    public function recordsSingleExecutionOfCommand()
    {
        $command = $this->commandTester->createCommand('Command #1');

        $command->execute(new NullCommandObserverStub());

        $this->assertEquals(['Command #1'], $this->commandTester->flushCommandSequence());
    }

    /** @test */
    public function recordsDirectExecutionOfCommand()
    {
        $this->commandTester->executeCommand('Command #1');

        $this->assertEquals(['Command #1'], $this->commandTester->flushCommandSequence());
    }

    /** @test */
    public function recordsMultipleExecutionsOfCommand()
    {
        $this->commandTester->executeCommand('Command #1');
        $this->commandTester->executeCommand('Command #1');
        $this->commandTester->executeCommand('Command #1');

        $this->assertEquals(['Command #1', 'Command #1', 'Command #1'], $this->commandTester->flushCommandSequence());
    }

    /** @test */
    public function recordsMultipleExecutionOfMultipleCommand()
    {
        $this->commandTester->executeCommand('Command #1');
        $this->commandTester->executeCommand('Command #2');

        $this->assertEquals(['Command #1', 'Command #2'], $this->commandTester->flushCommandSequence());
    }

    /** @test */
    public function clearsSequenceAfterFlush()
    {
        $this->commandTester->executeCommand('Command #1');
        $this->commandTester->executeCommand('Command #2');


        $this->commandTester->flushCommandSequence();

        $this->commandTester->executeCommand('Command #3');

        $this->assertEquals(['Command #3'], $this->commandTester->flushCommandSequence());
    }
}
